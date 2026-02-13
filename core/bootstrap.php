<?php
require_once 'HookManager.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../cms.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS plugins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE,
        is_active INTEGER DEFAULT 0
    )");

    // Load active plugins
    $stmt = $db->query("SELECT slug FROM plugins WHERE is_active = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $path = __DIR__ . "/../plugins/{$row['slug']}/{$row['slug']}.php";
        if (file_exists($path)) {
            include_once $path;
        }
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}