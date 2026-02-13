<?php
// admin_media.php
require_once 'core/config.php';

// 1. HANDLE DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $stmt = $db->prepare("SELECT filepath FROM media WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $file = $stmt->fetchColumn();
    
    if ($file && file_exists($file)) {
        unlink($file); // Remove physical file
        $db->prepare("DELETE FROM media WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: ?page=media&msg=deleted");
        exit;
    }
}

// 2. HANDLE THE UPLOAD LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cms_file'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = strtolower(pathinfo($_FILES["cms_file"]["name"], PATHINFO_EXTENSION));
    $new_name = uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $new_name;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($file_ext, $allowed)) {
        if (move_uploaded_file($_FILES["cms_file"]["tmp_name"], $target_file)) {
            $stmt = $db->prepare("INSERT INTO media (filename, filepath) VALUES (?, ?)");
            $stmt->execute([$new_name, $target_file]);
            // If AJAX upload (Drag & Drop)
            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { exit(json_encode(['status'=>'success'])); }
        }
    }
}

$images = $db->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
?>

<div class="mb-8">
    <div id="drop-zone" class="relative group border-4 border-dashed border-slate-200 bg-white p-12 rounded-2xl flex flex-col items-center justify-center transition-all hover:border-blue-400 hover:bg-blue-50/50">
        <input type="file" id="file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
        <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
            <i class="fa fa-cloud-upload-alt text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-700">Drag & Drop Images</h3>
        <p class="text-slate-400 text-sm">or click to browse files (JPG, PNG, WebP)</p>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
    <?php foreach ($images as $img): ?>
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm group relative overflow-hidden">
            <img src="<?= $img['filepath'] ?>" class="h-40 w-full object-cover rounded-lg">
            
            <div class="absolute inset-x-3 top-3 flex justify-between opacity-0 group-hover:opacity-100 transition-all transform translate-y-[-10px] group-hover:translate-y-0">
                <button onclick="navigator.clipboard.writeText('<?= $img['filepath'] ?>'); alert('URL Copied!')" 
                        class="bg-white text-blue-600 w-8 h-8 rounded shadow-lg hover:bg-blue-600 hover:text-white transition">
                    <i class="fa fa-link"></i>
                </button>
                <a href="?page=media&delete_id=<?= $img['id'] ?>" 
                   onclick="return confirm('Delete this image permanently?')"
                   class="bg-white text-red-500 w-8 h-8 rounded shadow-lg hover:bg-red-500 hover:text-white transition flex items-center justify-center">
                    <i class="fa fa-trash"></i>
                </a>
            </div>
            
            <div class="mt-2 text-[10px] font-mono text-slate-400 truncate"><?= $img['filename'] ?></div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Logic for AJAX Drag & Drop Upload
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');

fileInput.onchange = (e) => handleFiles(e.target.files);

function handleFiles(files) {
    if(files.length === 0) return;
    
    const formData = new FormData();
    formData.append('cms_file', files[0]);

    dropZone.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-4 text-blue-600 font-bold">Uploading...</p>';

    fetch('admin_media.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
        window.location.reload(); // Refresh to show new image
    });
}
</script>