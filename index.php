<?php
require_once 'core/config.php';

// 1. Get Settings
$settings_query = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_query->fetchAll(PDO::FETCH_KEY_PAIR);

$default_home = $settings['home_page_id'] ?? 1;
$site_title   = $settings['site_title'] ?? 'Locawork Site';

// 2. Determine Page ID
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : $default_home;

// 3. Fetch Page Data
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$page_id]);
$page_data = $stmt->fetch();

if (!$page_data) {
    die("Page not found.");
}

// Decode the Draggable Grid Data
$blocks = json_decode($page_data['content'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?> | <?= htmlspecialchars($page_data['title']) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
    
    <style>
        body { background-color: #f8fafc; }
        /* Clean up GridStack defaults for the public view */
        .grid-stack { background: transparent; }
        .grid-stack-item-content { border: none !important; }
        .custom-btn { padding: 12px 24px; border-radius: 8px; display: inline-block; transition: all 0.2s; }
        .custom-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    </style>
</head>
<body class="py-10">

    <div class="max-w-7xl mx-auto px-4">
        <header class="mb-10 flex justify-between items-center">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tighter"><?= htmlspecialchars($site_title) ?></h1>
            <a href="admin.php?page=editor&id=<?= $page_id ?>" class="text-xs bg-white border px-4 py-2 rounded-lg shadow-sm hover:bg-slate-50">Edit Page</a>
        </header>

        <?php if (empty($blocks)): ?>
            <div class="text-center py-20 text-slate-400 border-2 border-dashed rounded-xl">
                Page is empty.
            </div>
        <?php else: ?>
            <div class="grid-stack">
                <?php foreach ($blocks as $b): ?>
                    <div class="grid-stack-item" 
                         gs-x="<?= $b['x'] ?>" 
                         gs-y="<?= $b['y'] ?>" 
                         gs-w="<?= $b['w'] ?>" 
                         gs-h="<?= $b['h'] ?>">
                        
                        <div class="grid-stack-item-content">
                            <?php 
                            $type = $b['content_type'] ?? 'text'; 
                            $content = $b['content'] ?? '';
                            
                            switch($type):
                                case 'text': ?>
                                    <div class="prose prose-slate max-w-none">
                                        <?= nl2br(htmlspecialchars($content)) ?>
                                    </div>
                                <?php break;

                                case 'image': ?>
                                    <img src="<?= htmlspecialchars($content) ?>" class="w-full h-full object-cover rounded-xl shadow-md">
                                <?php break;

                                case 'button': ?>
                                    <div class="h-full flex items-center justify-center">
                                        <a href="#" class="custom-btn bg-blue-600 text-white shadow-lg">
                                            <?= htmlspecialchars($content ?: 'Click Here') ?>
                                        </a>
                                    </div>
                                <?php break;

                                case 'video':
                                    $embed = str_replace("watch?v=", "embed/", $content); ?>
                                    <iframe class="w-full h-full rounded-xl shadow-lg" src="<?= htmlspecialchars($embed) ?>" frameborder="0" allowfullscreen></iframe>
                                <?php break;

                                case 'html':
                                case 'code':
                                    echo $content;
                                break;
                            endswitch; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>
    <script>
        // staticGrid: true makes it non-draggable for the public
        GridStack.init({ 
            staticGrid: true, 
            margin: 10,
            cellHeight: 70 
        });
    </script>
</body>
</html>