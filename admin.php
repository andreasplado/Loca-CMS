<?php
require_once 'core/config.php';
// 2. CHECK FOR SAVE REQUEST IMMEDIATELY
if (isset($_POST['save_blocks'])) {
    
    // Clear any accidental spaces or PHP warnings that might have popped up
    if (ob_get_length()) ob_clean(); 
    
    header('Content-Type: application/json');

    try {
        $page_id = (int)$_POST['page_id'];
        $block_data = $_POST['block_data'];

        $stmt = $db->prepare("UPDATE pages SET content = ? WHERE id = ?");
        $success = $stmt->execute([$block_data, $page_id]);

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SQL execution failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    // 3. THIS IS MANDATORY. It stops the rest of the HTML from loading.
    exit; 
}

check_auth();

// 1. SESSION & PERMISSIONS
$user_role = $_SESSION['role'] ?? 'viewer';
$username  = $_SESSION['username'] ?? 'User';
$page      = $_GET['page'] ?? 'dashboard';
$editing_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// 2. SECURITY GATE
$restricted_pages = ['users', 'settings', 'pages', 'editor', 'plugins'];
if (in_array($page, $restricted_pages) && $user_role !== 'admin') {
    header("Location: ?page=dashboard&error=unauthorized");
    exit;
}

// 3. HANDLE PLUGIN UPLOAD (New Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip']) && $user_role === 'admin') {
    $zipFile = $_FILES['plugin_zip'];
    $extractTo = __DIR__ . '/plugins/'; // Use absolute path

    if (!is_writable($extractTo)) {
        die("Error: The 'plugins' folder is not writable. Check permissions.");
    }

    if ($zipFile['error'] === UPLOAD_ERR_OK) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile['tmp_name']) === TRUE) {
            $pluginSlug = trim($zip->getNameIndex(0), '/');

            if ($zip->extractTo($extractTo)) {
                $zip->close();
                $stmt = $db->prepare("INSERT IGNORE INTO plugins (name, slug, is_active) VALUES (?, ?, 0)");
                $stmt->execute([ucfirst($pluginSlug), $pluginSlug]);
                header("Location: ?page=plugins&msg=uploaded");
                exit;
            } else {
                die("Error: Could not extract ZIP.");
            }
        } else {
            die("Error: Could not open ZIP file.");
        }
    } else {
        die("Upload Error Code: " . $zipFile['error']);
    }
}
// 5. FETCH DATA FOR THE EDITOR
$current_page_data = '[]';
$current_page_title = 'Dashboard';
if ($page === 'editor') {
    $stmt = $db->prepare("SELECT title, content FROM pages WHERE id = ?");
    $stmt->execute([$editing_id]);
    $row = $stmt->fetch();
    if ($row) {
        $current_page_data = $row['content'];
        $current_page_title = $row['title'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Locawork Admin | <?= htmlspecialchars(ucfirst($page)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .sortable-ghost {
            opacity: 0.3;
            background: #f1f5f9;
            border: 2px dashed #3b82f6 !important;
        }

        .drag-handle {
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-900">

    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shadow-xl z-20">
        <div class="p-6 text-white text-2xl font-bold tracking-tight border-b border-slate-800">
            <i class="fa fa-cubes text-blue-500 mr-2"></i>Locawork
        </div>

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="?page=dashboard" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'dashboard' ? 'bg-slate-800 text-white' : '' ?>">
                <i class="fa fa-home w-8"></i> Dashboard
            </a>

            <?php if ($user_role === 'admin'): ?>
                <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Site Management</div>
                <a href="?page=pages" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'pages' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-file-alt w-8"></i> Pages
                </a>
                <a href="?page=media" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'media' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-images w-8"></i> Media Gallery
                </a>
                <a href="?page=plugins" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'plugins' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-plug w-8"></i> Plugins
                </a>
                <a href="?page=users" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'users' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-users w-8"></i> User Management
                </a>
                <a href="?page=settings" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'settings' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-cog w-8"></i> Settings
                </a>
            <?php endif; ?>

            <div class="pt-10 border-t border-slate-800">
                <a href="logout.php" class="flex items-center p-3 rounded text-red-400 hover:bg-red-900/20 transition">
                    <i class="fa fa-power-off w-8"></i> Logout
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b p-4 flex justify-between items-center px-8 z-10 shadow-sm">
            <h2 class="text-xl font-bold text-slate-800 capitalize"><?= $page ?></h2>
            <div class="flex items-center gap-6">
                <span id="save-status" class="hidden text-green-600 text-sm font-bold animate-bounce"><i class="fa fa-check-circle mr-1"></i> Saved</span>
                <div class="text-right">
                    <div class="text-sm font-bold text-slate-700"><?= htmlspecialchars($username) ?></div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-purple-500"><?= $user_role ?></div>
                </div>
            </div>
        </header>

        <section class="flex-1 overflow-y-auto p-8">
            <?php
            switch ($page) {
                case 'dashboard':
                    echo '<h1 class="text-2xl font-bold">Welcome, ' . htmlspecialchars($username) . '!</h1>';
                    break;
                case 'plugins':
                    include 'admin_plugins.php';
                    break;
                case 'pages':
                    include 'admin_pages.php';
                    break;
                case 'users':
                    include 'admin_users.php';
                    break;
                case 'media':
                    include 'admin_media.php';
                    break;
                case 'settings':
                    include 'admin_settings.php';
                    break;
                case 'editor':
                    // Define variables the editor needs
                    $page_id = $editing_id;
                    $page_title = $current_page_title;
                    $page_json = $current_page_data ?: '[]';

                    include 'admin_editor.php';
                    break;
            }
            ?>
        </section>
    </main>
</body>

</html>