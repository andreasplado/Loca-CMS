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

// 4. Decode the Draggable Grid Data (Matches the new 'Publish' JSON format)
$blocks = json_decode($page_data['content'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?> | <?= htmlspecialchars($page_data['title']) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet" />

    <style>
        body { background-color: #ffffff; }
        .grid-stack { background: transparent; }
        .grid-stack-item-content { border: none !important; overflow: visible !important; }
        /* Smooth fade in for the grid */
        .grid-stack { opacity: 0; transition: opacity 0.5s ease-in; }
        .grid-stack.grid-stack-instance { opacity: 1; }
    </style>
</head>

<body>
    <nav class="py-6 border-b mb-10">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <h1 class="text-2xl font-black uppercase tracking-tighter"><?= htmlspecialchars($site_title) ?></h1>
            <a href="admin.php?page=editor&id=<?= $page_id ?>" class="text-[10px] font-bold uppercase tracking-widest bg-slate-100 px-4 py-2 rounded-full hover:bg-slate-200 transition">Edit Layout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pb-20">
        <?php if (empty($blocks)): ?>
            <div class="text-center py-20 text-slate-400 border-2 border-dashed rounded-xl">
                This page has no content yet. Open the editor to start building.
            </div>
        <?php else: ?>
            <div class="grid-stack">
                <?php foreach ($blocks as $b): 
                    // Support both old 'content_type' and new 'type' keys
                    $type = $b['type'] ?? ($b['content_type'] ?? 'text');
                    $content = $b['content'] ?? '';
                    $extra = $b['extra'] ?? ''; // New styles from inspector
                ?>
                    <div class="grid-stack-item" 
                         gs-x="<?= $b['x'] ?>" gs-y="<?= $b['y'] ?>" 
                         gs-w="<?= $b['w'] ?>" gs-h="<?= $b['h'] ?>">
                        
                        <div class="grid-stack-item-content">
                            <?php switch ($type):
                                case 'heading': ?>
                                    <h2 class="<?= $extra ?>"><?= htmlspecialchars($content) ?></h2>
                                <?php break;

                                case 'text': ?>
                                    <div class="<?= $extra ?>">
                                        <?= nl2br(htmlspecialchars($content)) ?>
                                    </div>
                                <?php break;

                                case 'image': ?>
                                    <img src="<?= htmlspecialchars($content) ?>" class="<?= $extra ?> w-full h-full object-cover">
                                <?php break;

                                case 'button': ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <a href="#" class="<?= $extra ?> inline-block transition hover:scale-105">
                                            <?= htmlspecialchars($content ?: 'Click Here') ?>
                                        </a>
                                    </div>
                                <?php break;

                                case 'video':
                                    $embed = str_replace("watch?v=", "embed/", $content); ?>
                                    <iframe class="w-full h-full <?= $extra ?>" src="<?= htmlspecialchars($embed) ?>" frameborder="0" allowfullscreen></iframe>
                                <?php break;

                                case 'map': ?>
                                    <iframe width="100%" height="100%" class="rounded-lg <?= $extra ?>" frameborder="0" src="https://maps.google.com/maps?q=<?= urlencode($content) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>
                                <?php break;

                                case 'html':
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize GridStack in Static Mode
            const grid = GridStack.init({
                staticGrid: true,    // Users can't drag or resize
                margin: 0,           // Matches the "snapped" look
                cellHeight: 50,      // MUST match your builder cellHeight
                column: 12,          // Standard 12-column grid
                animate: true,
                float: true
            });

            // Handle responsive stacking (makes it work on mobile)
            const adjustForMobile = () => {
                if (window.innerWidth < 768) {
                    grid.column(1); // Stack everything in 1 column on mobile
                } else {
                    grid.column(12); // Use full grid on desktop
                }
            };

            window.addEventListener('resize', adjustForMobile);
            adjustForMobile(); // Run on initial load
        });
    </script>
</body>
</html>