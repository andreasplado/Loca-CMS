<?php
// admin_pages.php
require_once 'core/config.php';


// Handle New Page Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_page'])) {
    
    $title = !empty($_POST['title']) ? $_POST['title'] : 'Untitled Page';
    
    // Create a URL-friendly slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Check if slug already exists to avoid SQL errors
    $check = $db->prepare("SELECT id FROM pages WHERE slug = ?");
    $check->execute([$slug]);
    
    if ($check->fetch()) {
        // If "about" exists, make it "about-1715800000"
        $slug .= '-' . time();
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, '[]')");
        $stmt->execute([$title, $slug]);
        header("Location: ?page=pages&msg=created");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Deletion
if (isset($_GET['delete_page'])) {
    $stmt = $db->prepare("DELETE FROM pages WHERE id = ? AND id != 1"); // Prevent deleting home
    $stmt->execute([$_GET['delete_page']]);
    header("Location: ?page=pages");
}

$pages = $db->query("SELECT * FROM pages ORDER BY id ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-xl border mb-8">
        <h3 class="font-bold mb-4 text-slate-700">Create New Page</h3>
        <form method="POST" class="flex gap-4">
            <input type="text" name="title" placeholder="Page Title (e.g. About Us)" required class="flex-1 border p-2 rounded-lg">
            <button type="submit" name="create_page" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold">Create</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="p-4 text-xs font-bold uppercase text-slate-500">Title</th>
                    <th class="p-4 text-xs font-bold uppercase text-slate-500">Slug</th>
                    <th class="p-4 text-xs font-bold uppercase text-slate-500 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr class="border-b hover:bg-slate-50">
                    <td class="p-4 font-medium"><?= htmlspecialchars($p['title']) ?></td>
                    <td class="p-4 text-slate-400 text-sm">/<?= $p['slug'] ?></td>
                    <td class="p-4 text-right space-x-2">
                        <a href="?page=editor&id=<?= $p['id'] ?>" class="text-blue-600 hover:underline font-bold text-sm">Edit Design</a>
                        <?php if($p['id'] != 1): ?>
                            <a href="?page=pages&delete_page=<?= $p['id'] ?>" onclick="return confirm('Delete page?')" class="text-red-400 hover:text-red-600"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>