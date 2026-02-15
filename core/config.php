<?php

$host = '127.0.0.1';
$db_name = 'vhost137745s3';
$user = 'vhost137745s3';
$pass = 'Z66v7yC1.1';



// Top of core/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection Error: " . $e->getMessage());
}
/**
 * Security Check: Redirect to login if not authenticated
 */
function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Now call it

// --- PLUGIN LOADER SYSTEM ---
$active_plugins = $db->query("SELECT slug FROM plugins WHERE is_active = 1")->fetchAll();

foreach ($active_plugins as $plugin) {
    // Path to the plugin's folder
    $plugin_path = __DIR__ . "/../plugins/" . $plugin['slug'] . "/";

    if (is_dir($plugin_path)) {
        // This scans the folder for ANY .php file and includes it
        // This handles 'hello-world.php', 'plugin.php', or 'index.php'
        $files = glob($plugin_path . "*.php");
        foreach ($files as $file) {
            include_once $file;
        }
    }
}