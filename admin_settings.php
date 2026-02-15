<?php
require_once 'core/config.php';

// Handle Saving Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Added 404_page_id to the array below
    foreach (['site_title', 'home_page_id', '404_page_id'] as $key) {
        $val = $_POST[$key] ?? '';
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $val, $val]);
    }
    $success = "Settings updated successfully!";
}

// Fetch current settings
$settings_raw = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$all_pages = $db->query("SELECT id, title FROM pages")->fetchAll();
?>

<div class="max-w-2xl mx-auto">
    <?php if(isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fa fa-check-circle mr-2"></i><?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-8 rounded-xl border shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-700 border-b pb-4">Global Site Settings</h3>
        
        <div>
            <label class="block text-sm font-bold text-slate-600 mb-2">Website Title</label>
            <input type="text" name="site_title" value="<?= htmlspecialchars($settings_raw['site_title'] ?? 'My CMS') ?>" 
                   class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-slate-600 mb-2">Homepage Display</label>
                <select name="home_page_id" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <?php foreach ($all_pages as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($settings_raw['home_page_id'] ?? 1) == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-600 mb-2">404 Error Page</label>
                <select name="404_page_id" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <option value="0">-- System Default --</option>
                    <?php foreach ($all_pages as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($settings_raw['404_page_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <p class="text-xs text-slate-400 italic">
            Tip: The 404 page is displayed whenever a user tries to access a URL that doesn't exist.
        </p>

        <button type="submit" name="save_settings" class="w-full bg-slate-900 text-white font-bold py-3 rounded-lg hover:bg-blue-600 transition shadow-lg">
            Save All Settings
        </button>
    </form>
</div>