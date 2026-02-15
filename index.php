<?php
require_once 'core/config.php';

// 1. Get Settings
$settings_query = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_query->fetchAll(PDO::FETCH_KEY_PAIR);

$default_home  = $settings['home_page_id'] ?? 1;
$error_page_id = $settings['404_page_id'] ?? 0;

// 2. Determine if we should show Home or a 404
$page_id = 0;
$trigger_404 = false;

if (isset($_GET['id'])) {
    $page_id = (int)$_GET['id'];
} else {
    // Get the clean path (e.g., /contact or /asdf)
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    
    // If path is empty, it's the homepage. Otherwise, it's a potential 404.
    if ($request_uri === "" || $request_uri === "index.php" || $request_uri === "index") {
        $page_id = (int)$default_home;
    } else {
        $trigger_404 = true;
    }
}

// 3. Fetch Data
$page_data = null;
if (!$trigger_404) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$page_id]);
    $page_data = $stmt->fetch();
    if (!$page_data) $trigger_404 = true;
}

// 4. Handle 404 Logic
if ($trigger_404) {
    header("HTTP/1.1 404 Not Found");

    // Try to load the custom 404 page from settings
    if ($error_page_id > 0) {
        $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$error_page_id]);
        $page_data = $stmt->fetch();
    }

    // If no page found in DB for 404, show the "Must set in settings" error
    if (!$page_data) {
        die("
        <div style='text-align:center; padding-top:100px; font-family:sans-serif; color:#334155; background:#f8fafc; height:100vh;'>
            <div style='background:white; display:inline-block; padding:40px; border-radius:20px; shadow:0 10px 15px -3px rgba(0,0,0,0.1); border:1px solid #e2e8f0;'>
                <h1 style='font-size:80px; margin:0; color:#f43f5e;'>404</h1>
                <h2 style='margin-top:10px; color:#1e293b;'>Custom 404 Page Not Set</h2>
                <p style='color:#64748b; max-width:400px; line-height:1.6;'>The URL you requested does not exist. To replace this message with a custom design, please <b>set a 404 page in your Admin Settings</b>.</p>
                <div style='margin-top:25px;'>
                    <a href='admin.php?page=settings' style='background:#0f172a; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Settings</a>
                    <a href='index.php' style='margin-left:10px; color:#64748b; text-decoration:none;'>Return Home</a>
                </div>
            </div>
        </div>");
    }
}

$page_title = $page_data['title'];
$saved_json = !empty($page_data['content']) ? $page_data['content'] : '[]';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { margin: 0; background: white; }
        .grid-stack { min-height: 100vh; }
        .grid-stack-item-content { background: white; border: none !important; overflow: visible !important; }
        .content-viewport { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; padding: 10px; }
        .content-viewport img { max-width: 100%; height: auto; }
    </style>
</head>
<body>

    <div class="grid-stack"></div>

<script>
    const grid = GridStack.init({
        cellHeight: 50,
        margin: 5,
        staticGrid: true, 
        float: true
    });

    function generateViewHTML(type, content, extra = '') {
        let view = '';
        if (type === 'heading') view = `<h2 class="${extra}">${content}</h2>`;
        if (type === 'text')    view = `<p class="${extra}">${content}</p>`;
        if (type === 'image')   view = `<img src="${content}" class="${extra}">`;
        if (type === 'button')  view = `<button class="${extra}">${content}</button>`;

        return `<div class="grid-stack-item-content"><div class="content-viewport">${view}</div></div>`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            const savedData = <?= $saved_json ?>;
            if (savedData && savedData.length > 0) {
                const items = savedData.map(item => ({
                    ...item,
                    content: generateViewHTML(item.type, item.content, item.extra)
                }));
                grid.load(items);
            }
        } catch(e) { console.error("Data error:", e); }
    });

    const handleResize = () => grid.column(window.innerWidth < 768 ? 1 : 12);
    window.addEventListener('resize', handleResize);
    handleResize();
</script>
</body>
</html>