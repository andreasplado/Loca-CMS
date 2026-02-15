<?php
require_once 'core/config.php';

// 1. Get Home Page ID from Settings
$settings_query = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_query->fetchAll(PDO::FETCH_KEY_PAIR);

// If ?id is in URL, use it; otherwise, use the one from settings
$default_home = $settings['home_page_id'] ?? 1;
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$default_home;

// 2. Fetch Page Data
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$page_id]);
$page_data = $stmt->fetch();

if (!$page_data) {
    die("Page not found. Check your Settings or Page ID.");
}

$page_title = $page_data['title'];
// Prepare the JSON for JavaScript
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
        
        /* Matching your builder exactly */
        .grid-stack-item-content { 
            background: white; 
            border: none !important; 
            overflow: visible !important; 
        }
        
        /* The flex centering used in your builder */
        .content-viewport { 
            width: 100%; height: 100%; 
            display: flex; align-items: center; justify-content: center; 
            padding: 10px; 
        }

        .content-viewport img { max-width: 100%; height: auto; }
    </style>
</head>
<body>

    <div class="grid-stack"></div>

<script>
    // 1. Init Grid in Static (View Only) Mode
    let grid = GridStack.init({
        cellHeight: 50,
        margin: 5,
        staticGrid: true, // This stops the "dragging" but keeps the layout
        float: true
    });

    // 2. Match the Builder's Component Generator
    function generateViewHTML(type, content, extra = '') {
        let view = '';
        if (type === 'heading') view = `<h2 class="${extra}">${content}</h2>`;
        if (type === 'text')    view = `<p class="${extra}">${content}</p>`;
        if (type === 'image')   view = `<img src="${content}" class="${extra}">`;
        if (type === 'button')  view = `<button class="${extra}">${content}</button>`;

        return `
            <div class="grid-stack-item-content">
                <div class="content-viewport">
                    ${view}
                </div>
            </div>`;
    }

    // 3. Load the data from your database JSON
    document.addEventListener('DOMContentLoaded', function() {
        const savedData = <?= $saved_json ?>;
        
        if (savedData && savedData.length > 0) {
            const items = savedData.map(item => ({
                x: item.x, 
                y: item.y, 
                w: item.w, 
                h: item.h,
                id: item.id,
                content: generateViewHTML(item.type, item.content, item.extra)
            }));
            
            // Re-render the grid
            grid.load(items);
        }
    });

    // 4. Mobile stacking logic
    window.addEventListener('resize', () => {
        grid.column(window.innerWidth < 768 ? 1 : 12);
    });
</script>
</body>
</html>