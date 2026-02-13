<?php
require_once 'core/config.php';

// Handle Adding Menu Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_menu'])) {
    $parent = $_POST['parent_id'] === 'none' ? null : $_POST['parent_id'];
    $stmt = $db->prepare("INSERT INTO menus (title, url, parent_id) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['title'], $_POST['url'], $parent]);
}

// Handle Delete
if (isset($_GET['delete_menu'])) {
    $stmt = $db->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->execute([$_GET['delete_menu']]);
}

$main_menus = $db->query("SELECT * FROM menus WHERE parent_id IS NULL ORDER BY position")->fetchAll();
$all_pages = $db->query("SELECT title, slug FROM pages")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-xl border shadow-sm mb-8">
        <h3 class="font-bold text-slate-700 mb-4">Add Menu Item</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="title" placeholder="Label (e.g. Services)" required class="border p-2 rounded-lg text-sm">
            <input type="text" name="url" placeholder="URL (e.g. services)" required class="border p-2 rounded-lg text-sm">
            <select name="parent_id" class="border p-2 rounded-lg text-sm">
                <option value="none">No Parent (Main Menu)</option>
                <?php foreach($main_menus as $m): ?>
                    <option value="<?= $m['id'] ?>">Under <?= $m['title'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_menu" class="bg-blue-600 text-white rounded-lg font-bold text-sm py-2">Add Item</button>
        </form>
    </div>

    <div class="space-y-4">
        <?php foreach ($main_menus as $m): ?>
            <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b">
                    <span class="font-bold text-slate-800"><i class="fa fa-bars mr-2 text-slate-400"></i> <?= $m['title'] ?></span>
                    <a href="?page=menus&delete_menu=<?= $m['id'] ?>" class="text-red-400 hover:text-red-600"><i class="fa fa-trash"></i></a>
                </div>
                
                <div class="p-2 space-y-1 bg-white">
                    <?php 
                    $subs = $db->prepare("SELECT * FROM menus WHERE parent_id = ? ORDER BY position");
                    $subs->execute([$m['id']]);
                    foreach ($subs->fetchAll() as $sub): 
                    ?>
                        <div class="ml-8 p-2 border-l-2 border-slate-100 flex justify-between items-center hover:bg-slate-50 rounded-r">
                            <span class="text-sm text-slate-600"><i class="fa fa-level-up-alt fa-rotate-90 mr-2 text-slate-300"></i> <?= $sub['title'] ?></span>
                            <a href="?page=menus&delete_menu=<?= $sub['id'] ?>" class="text-slate-300 hover:text-red-500 text-xs"><i class="fa fa-times"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>