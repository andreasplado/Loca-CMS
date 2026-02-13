<?php
if ($user_role !== 'admin') die("Unauthorized");

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // 1. Get the slug first so we know which folder to delete
    $stmt = $db->prepare("SELECT slug FROM plugins WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    
    if ($p) {
        $folder = 'plugins/' . $p['slug'];
        
        // 2. Delete the folder and its contents
        if (is_dir($folder)) {
            $files = array_diff(scandir($folder), array('.', '..'));
            foreach ($files as $file) unlink("$folder/$file");
            rmdir($folder);
        }
        
        // 3. Delete from database
        $db->query("DELETE FROM plugins WHERE id = $id");
    }
    header("Location: ?page=plugins&msg=deleted");
    exit;
}

// Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->query("UPDATE plugins SET is_active = 1 - is_active WHERE id = $id");
    header("Location: ?page=plugins");
    exit;
}

$plugins = $db->query("SELECT * FROM plugins")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-extrabold text-slate-800">Plugin Manager</h2>
        <span class="text-xs bg-slate-200 px-3 py-1 rounded-full font-bold text-slate-500">
            <?= count($plugins) ?> Installed
        </span>
    </div>

    <form method="POST" enctype="multipart/form-data" class="mb-10">
        <div class="bg-white border-2 border-dashed border-blue-200 rounded-2xl p-8 text-center hover:border-blue-400 transition-all group">
            <i class="fa fa-cloud-upload-alt text-4xl text-blue-100 group-hover:text-blue-500 mb-4 transition-colors"></i>
            <h3 class="text-slate-700 font-bold">Upload New Plugin</h3>
            <p class="text-slate-400 text-xs mb-4">Select a plugin .zip file to install</p>
            
            <input type="file" name="plugin_zip" id="plugin_zip" class="hidden" onchange="this.form.submit()">
            <label for="plugin_zip" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold cursor-pointer transition shadow-lg shadow-blue-100">
                Browse Files
            </label>
        </div>
    </form>

    <div class="space-y-4">
        <?php foreach($plugins as $p): ?>
            <div class="bg-white border p-5 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 <?= $p['is_active'] ? 'bg-blue-600' : 'bg-slate-100' ?> rounded-lg flex items-center justify-center text-white text-xl shadow-inner">
                        <i class="fa fa-plug"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800"><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest">slug: <?= $p['slug'] ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="?page=plugins&toggle=<?= $p['id'] ?>" 
                       class="px-4 py-2 rounded-lg text-xs font-bold transition <?= $p['is_active'] ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400' ?>">
                        <?= $p['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                    </a>
                    <a href="?page=plugins&delete=<?= $p['id'] ?>" 
                        onclick="return confirm('Are you sure? This will delete the plugin files forever!')"
                        class="text-slate-300 hover:text-red-500 transition px-2">
                            <i class="fa fa-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(empty($plugins)): ?>
            <div class="text-center py-10">
                <p class="text-slate-400 italic text-sm">No plugins installed. Upload a ZIP to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>