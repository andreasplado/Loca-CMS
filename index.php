<?php
require_once 'core/config.php';

// 1. Get Settings with Fallbacks
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
    die("Page not found. Check your database IDs.");
}

$blocks = json_decode($page_data['content'] ?? '[]', true) ?: [];

/**
 * Helper function to render blocks. 
 * This is now a function so it can call itself for nested grids.
 */
function renderBlocks($blocks, $db) {
    if (empty($blocks)) return;

    foreach ($blocks as $block) {
        $type = $block['type'] ?? 'text';
        
        // Wrapper for spacing
        echo '<div class="block-render mb-6">';

        switch ($type) {
            case 'grid':
                $gap = $block['gap'] ?? '20';
                $height = $block['height'] ?? 'auto';
                echo '<div class="flex flex-wrap" style="gap: '.$gap.'px; min-height: '.$height.';">';
                
                foreach (($block['columns'] ?? []) as $col) {
                    $width = $col['width'] ?? '100%';
                    // Calculate width minus gap for clean rows
                    echo '<div style="flex: 1 1 calc('.$width.' - '.$gap.'px); max-width: '.$width.';">';
                    
                    // RENDER NESTED BLOCKS INSIDE COLUMN
                    if (!empty($col['blocks'])) {
                        renderBlocks($col['blocks'], $db);
                    } 
                    // Fallback for old data where content was just a string
                    else if (!empty($col['content'])) {
                        echo $col['content'];
                    }
                    
                    echo '</div>';
                }
                echo '</div>';
                break;

            case 'menu':
                echo '<div class="bg-white rounded-xl shadow-sm border border-slate-100">';
                renderCmsMenu($db);
                echo '</div>';
                break;

            case 'html':
                echo $block['content'] ?? '';
                break;

            case 'container':
                $css = htmlspecialchars($block['css'] ?? '');
                echo '<div style="'.$css.'">'.($block['content'] ?? '').'</div>';
                break;

            case 'text':
                echo '<div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 text-slate-700 leading-relaxed">';
                echo nl2br(htmlspecialchars($block['content'] ?? ''));
                echo '</div>';
                break;

            case 'image':
                $src = htmlspecialchars($block['content'] ?? '');
                echo '<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">';
                if ($src) {
                    echo '<img src="'.$src.'" class="w-full h-auto block">';
                } else {
                    echo '<div class="bg-slate-100 h-48 flex items-center justify-center text-slate-400"><i class="fa fa-image text-2xl"></i></div>';
                }
                echo '</div>';
                break;
        }

        echo '</div>';
    }
}

/**
 * Navigation Menu Renderer
 */
function renderCmsMenu($db) {
    $main_items = $db->query("SELECT * FROM menus WHERE parent_id IS NULL ORDER BY position ASC")->fetchAll();
    echo '<nav class="flex flex-wrap gap-6 p-4">';
    foreach ($main_items as $item) {
        $sub_stmt = $db->prepare("SELECT * FROM menus WHERE parent_id = ? ORDER BY position ASC");
        $sub_stmt->execute([$item['id']]);
        $subs = $sub_stmt->fetchAll();

        echo '<div class="relative group">';
        echo '<a href="'.htmlspecialchars($item['url']).'" class="text-slate-700 hover:text-blue-600 font-bold">';
        echo htmlspecialchars($item['title']) . (count($subs) ? ' <i class="fa fa-chevron-down text-[10px]"></i>' : '');
        echo '</a>';

        if (count($subs)) {
            echo '<div class="absolute hidden group-hover:block bg-white shadow-xl border rounded-lg p-2 min-w-[160px] z-50 mt-2">';
            foreach ($subs as $sub) {
                echo '<a href="'.htmlspecialchars($sub['url']).'" class="block p-2 text-sm hover:bg-slate-50 rounded">'.htmlspecialchars($sub['title']).'</a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</nav>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?> | <?= htmlspecialchars($page_data['title'] ?? 'Home') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; min-height: 100vh; }
        /* Prevent layout shift from large images */
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body class="py-10 px-4">

    <div class="max-w-5xl mx-auto">
        <?php 
            if (empty($blocks)) {
                echo '<div class="text-center py-20 text-slate-400 border-2 border-dashed rounded-xl">This page is empty. Start adding blocks in the editor.</div>';
            } else {
                renderBlocks($blocks, $db); 
            }
        ?>
    </div>

</body>
</html>