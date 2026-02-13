<?php
require_once 'core/config.php';
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

// 4. HANDLE AJAX SAVE (Page Specific)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blocks'])) {
    if ($user_role !== 'admin') {
        exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
    }
    $target_id = (int)$_POST['page_id'];
    $stmt = $db->prepare("UPDATE pages SET content = ? WHERE id = ?");
    $success = $stmt->execute([$_POST['block_data'], $target_id]);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        exit(json_encode(['status' => $success ? 'success' : 'error']));
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
    ?>
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>

    <div class="fixed inset-0 z-[100] bg-slate-100 flex flex-col font-sans">
        
        <header class="h-[65px] bg-slate-900 text-white flex justify-between items-center px-6 shadow-xl">
            <div class="flex items-center gap-4">
                <a href="?page=pages" class="text-slate-400 hover:text-white"><i class="fa fa-times text-xl"></i></a>
                <h2 class="font-bold">Visual Designer: <span class="text-blue-400"><?= htmlspecialchars($current_page_title) ?></span></h2>
            </div>
            <div class="flex gap-3">
                <button onclick="saveGrid()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-full font-bold text-sm shadow-lg transition">Save Layout</button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <aside class="w-72 bg-white border-r shadow-xl flex flex-col z-20">
                <div class="p-4 border-b bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">Drag to Canvas</div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:bg-blue-50 text-center transition shadow-sm" data-type="text">
                        <i class="fa fa-font text-slate-400 mb-1 block"></i><span class="text-[10px] font-bold">TEXT</span>
                    </div>
                    <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:bg-blue-50 text-center transition shadow-sm" data-type="image">
                        <i class="fa fa-image text-slate-400 mb-1 block"></i><span class="text-[10px] font-bold">IMAGE</span>
                    </div>
                    <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:bg-blue-50 text-center transition shadow-sm" data-type="video">
                        <i class="fa fa-play text-slate-400 mb-1 block"></i><span class="text-[10px] font-bold">VIDEO</span>
                    </div>
                    <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:bg-blue-50 text-center transition shadow-sm" data-type="button">
                        <i class="fa fa-link text-slate-400 mb-1 block"></i><span class="text-[10px] font-bold">BUTTON</span>
                    </div>
                </div>

                <div id="inspector" class="hidden flex-1 border-t bg-slate-50 p-4 overflow-y-auto">
                    <h3 class="text-[10px] font-black uppercase text-blue-600 mb-4 border-b pb-2">Properties</h3>
                    <div id="inspector-content"></div>
                </div>
            </aside>

            <main class="flex-1 bg-slate-200 p-8 overflow-y-auto">
                <div class="grid-stack bg-white min-h-screen rounded-xl shadow-inner p-4"></div>
            </main>
        </div>
    </div>

    <style>
        .grid-stack { background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 30px 30px; }
        .grid-stack-item-content { background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden !important; }
        .block-header { background: #f8fafc; padding: 5px 10px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; cursor: move; }
        .block-type { font-size: 8px; font-weight: 900; color: #94a3b8; text-transform: uppercase; }
    </style>

    <script>
        let grid = GridStack.init({
            cellHeight: 70,
            acceptWidgets: true,
            dragIn: '.newWidget',
            dragInOptions: { revert: 'invalid', scroll: false, appendTo: 'body', helper: 'clone' },
            removable: '#trash', 
            margin: 10
        });

        // Load existing data
        let savedData = <?= $current_page_data ?: '[]' ?>;
        grid.load(savedData);

        // When a new widget is dropped from the sidebar
        grid.on('added', function(e, items) {
            items.forEach(item => {
                let type = item.el.getAttribute('data-type');
                if(type) {
                    item.el.setAttribute('data-content', '');
                    item.el.innerHTML = `
                        <div class="grid-stack-item-content">
                            <div class="block-header">
                                <span class="block-type">${type}</span>
                                <button onclick="removeWidget(this)" class="text-slate-300 hover:text-red-500"><i class="fa fa-times text-[10px]"></i></button>
                            </div>
                            <div class="p-4 text-[10px] text-slate-400 italic" onclick="editWidget('${item._id}')">Click to configure</div>
                        </div>`;
                }
            });
        });

        function removeWidget(btn) {
            grid.removeWidget(btn.closest('.grid-stack-item'));
        }

        function editWidget(id) {
            const el = document.querySelector(`[gs-id="${id}"]`);
            const inspector = document.getElementById('inspector');
            const content = document.getElementById('inspector-content');
            inspector.classList.remove('hidden');

            content.innerHTML = `
                <div class="space-y-4">
                    <label class="block text-[10px] font-bold">CONTENT / URL</label>
                    <textarea id="temp-content" class="w-full border rounded p-2 text-sm h-32">${el.getAttribute('data-content') || ''}</textarea>
                    <button onclick="saveWidgetData('${id}')" class="w-full bg-slate-900 text-white p-2 rounded text-xs font-bold uppercase">Update Block</button>
                </div>
            `;
        }

        function saveWidgetData(id) {
            const el = document.querySelector(`[gs-id="${id}"]`);
            const val = document.getElementById('temp-content').value;
            el.setAttribute('data-content', val);
            el.querySelector('.italic').innerText = val.substring(0, 30) + '...';
        }

        function saveGrid() {
            let data = grid.save();
            // We loop to ensure our custom content stays in the JSON
            data.forEach(d => {
                let el = document.querySelector(`[gs-id="${d.id}"]`);
                d.content = el.getAttribute('data-content');
            });

            const fd = new FormData();
            fd.append('save_blocks', '1');
            fd.append('page_id', <?= $editing_id ?>);
            fd.append('block_data', JSON.stringify(data));
            
            fetch('admin.php', { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json()).then(() => alert("Layout Saved!"));
        }
    </script>
    <?php
    break;
            }
            ?>
        </section>
    </main>
</body>

</html>