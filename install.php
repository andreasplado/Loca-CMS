<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['install'])) {
    $host = $_POST['host'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $name = $_POST['name'];

    try {
        // 1. Create Connection
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name` text-slate-900");

        // 3. Create Tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255)
        )");

        // 4. Insert Initial Data
        // Create the first page
        $stmt = $pdo->prepare("INSERT INTO pages (title, content) VALUES (?, ?)");
        $stmt->execute(['Home Page', '[]']);
        $first_page_id = $pdo->lastInsertId();

        // Set this page as the Home Page in settings
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?)");
        $stmt->execute([
            'home_page_id', $first_page_id,
            'site_title', 'My Visual Site'
        ]);

        // 5. Create config.php file automatically
        $config_content = "<?php
try {
    \$db = new PDO('mysql:host=$host;dbname=$name;charset=utf8mb4', '$user', '$pass');
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}
?>";
        
        if(!is_dir('core')) mkdir('core');
        file_put_contents('core/config.php', $config_content);

        $success = "Installation successful! <a href='index.php' class='text-blue-500 underline'>View Site</a> or <a href='builder.php' class='text-blue-500 underline'>Open Builder</a>";

    } catch (PDOException $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-slate-800">Database Installer</h2>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $success ?></div>
        <?php elseif (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Database Host</label>
                <input type="text" name="host" value="localhost" class="w-full border rounded-lg p-2 mt-1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Username</label>
                <input type="text" name="user" value="root" class="w-full border rounded-lg p-2 mt-1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="pass" class="w-full border rounded-lg p-2 mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Database Name</label>
                <input type="text" name="name" value="visual_builder_db" class="w-full border rounded-lg p-2 mt-1" required>
            </div>
            <button type="submit" name="install" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Run Installer</button>
        </form>
    </div>
</body>
</html>