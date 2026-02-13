<?php
require_once 'core/config.php';
check_auth();

// 1. SESSION & PERMISSIONS
$user_role = $_SESSION['role'] ?? 'viewer';
$username  = $_SESSION['username'] ?? 'User';
$page      = $_GET['page'] ?? 'dashboard';

// 2. IDENTIFY WHICH PAGE IS BEING EDITED
$editing_id = isset($_GET['id']) ? (int)$_GET['id'] : 1; 

// 3. SECURITY GATE: Block Viewers from Management Pages
$restricted_pages = ['users', 'settings', 'pages', 'editor'];
if (in_array($page, $restricted_pages) && $user_role !== 'admin') {
    header("Location: ?page=dashboard&error=unauthorized");
    exit;
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
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .sortable-ghost { opacity: 0.4; border: 2px solid #3b82f6 !important; }
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
                <a href="?page=users" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'users' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-users w-8"></i> User Management
                </a>
                <a href="?page=settings" class="flex items-center p-3 rounded hover:bg-slate-800 transition <?= $page == 'settings' ? 'bg-slate-800 text-white' : '' ?>">
                    <i class="fa fa-cog w-8"></i> Settings
                </a>
            <?php else: ?>
                <div class="mt-4 p-4 bg-slate-800/40 rounded-lg border border-slate-700 mx-2">
                    <p class="text-[10px] text-slate-500 italic uppercase font-bold tracking-tighter">
                        <i class="fa fa-lock mr-1"></i> Viewer Access Only
                    </p>
                </div>
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
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold text-slate-800 capitalize"><?= $page ?></h2>
                <?php if($page === 'editor'): ?>
                    <span class="text-slate-300 mx-2">/</span>
                    <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-xs font-bold"><?= htmlspecialchars($current_page_title) ?></span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-6">
                <span id="save-status" class="hidden text-green-600 text-sm font-bold animate-bounce">
                    <i class="fa fa-check-circle mr-1"></i> Changes Saved
                </span>
                <div class="text-right">
                    <div class="text-sm font-bold text-slate-700"><?= htmlspecialchars($username) ?></div>
                    <div class="text-[10px] font-black uppercase tracking-widest <?= $user_role === 'admin' ? 'text-purple-500' : 'text-slate-400' ?>">
                        <?= $user_role ?>
                    </div>
                </div>
            </div>
        </header>

        <section class="flex-1 overflow-y-auto p-8">

            <?php if ($page === 'dashboard'): ?>
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-2xl font-bold mb-6">Welcome back, <?= htmlspecialchars($username) ?>!</h1>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl border shadow-sm">
                            <i class="fa fa-eye text-blue-500 mb-2"></i>
                            <div class="text-slate-400 text-xs font-bold uppercase">Account Role</div>
                            <div class="text-xl font-bold"><?= ucfirst($user_role) ?></div>
                        </div>
                    </div>
                </div>

            <?php elseif ($page === 'pages'): ?>
                <?php include 'admin_pages.php'; ?>

            <?php elseif ($page === 'users'): ?>
                <?php include 'admin_users.php'; ?>

            <?php elseif ($page === 'media'): ?>
                <?php include 'admin_media.php'; ?>

            <?php elseif ($page === 'settings'): ?>
                <?php include 'admin_settings.php'; ?>

            <?php elseif ($page === 'editor'): ?>
                <div class="flex h-full gap-6">
                    <div class="w-full lg:w-1/2 flex flex-col">
                        <div class="flex flex-wrap items-center gap-2 mb-4 bg-white p-4 rounded-xl border shadow-sm">
                            <button onclick="addBlock('text')" class="bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg font-bold transition text-xs">
                                <i class="fa fa-font mr-1 text-blue-500"></i> Text
                            </button>
                            <button onclick="addBlock('image')" class="bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg font-bold transition text-xs">
                                <i class="fa fa-image mr-1 text-purple-500"></i> Image
                            </button>
                            <button onclick="addBlock('grid')" class="bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg font-bold transition text-xs">
                                <i class="fa fa-columns mr-1 text-indigo-500"></i> Grid
                            </button>
                            <button onclick="addBlock('container')" class="bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg font-bold transition text-xs">
                                <i class="fa fa-square mr-1 text-green-500"></i> Box
                            </button>
                            
                            <button onclick="saveAjax()" class="ml-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-bold shadow-lg shadow-blue-200 transition text-sm">
                                <i class="fa fa-save mr-2"></i> Save Page
                            </button>
                        </div>

                        <div id="block-list" class="space-y-4 overflow-y-auto pb-20">
                            </div>
                    </div>

                    <div class="hidden lg:flex lg:w-1/2 flex-col border-l pl-4">
                        <div class="bg-slate-800 text-white text-[10px] font-bold uppercase p-2 rounded-t-lg flex justify-between items-center">
                            <span><i class="fa fa-eye mr-2"></i>Live Preview</span>
                            <button onclick="refreshPreview()" class="hover:text-blue-400"><i class="fa fa-sync"></i></button>
                        </div>
                        <iframe id="preview-frame" src="index.php?id=<?= $editing_id ?>&preview=1" class="w-full flex-1 border bg-white rounded-b-lg shadow-inner"></iframe>
                    </div>
                </div>

                <script>
                    let blocks = <?= $current_page_data ?: '[]' ?>;
                    const currentPageId = <?= $editing_id ?>;

                    function render() {
                        const container = document.getElementById('block-list');
                        container.innerHTML = blocks.map((b) => `
                            <div class="block-item group bg-white border p-4 rounded-xl shadow-sm hover:shadow-md transition" data-id="${b.id}">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><i class="fa fa-bars mr-2 cursor-move"></i>${b.type}</span>
                                    <button onclick="deleteBlock(${b.id})" class="text-slate-300 hover:text-red-500"><i class="fa fa-times text-xs"></i></button>
                                </div>
                                ${b.type === 'text' ? `
                                    <textarea oninput="updateContent(${b.id}, this.value)" class="w-full text-sm border rounded-lg p-3 h-24 focus:ring-2 focus:ring-blue-100 outline-none transition">${b.content}</textarea>
                                ` : b.type === 'image' ? `
                                    <input type="text" value="${b.content}" oninput="updateContent(${b.id}, this.value)" class="w-full text-xs border p-2 rounded" placeholder="Image URL...">
                                ` : `
                                    <div class="bg-slate-50 p-4 rounded border-dashed border-2 text-center text-slate-400 text-xs italic">Block content editor for ${b.type} coming soon...</div>
                                `}
                            </div>
                        `).join('');
                        
                        // Re-init sortable
                        Sortable.create(container, {
                            animation: 150,
                            handle: '.fa-bars',
                            onEnd: (evt) => {
                                const movedItem = blocks.splice(evt.oldIndex, 1)[0];
                                blocks.splice(evt.newIndex, 0, movedItem);
                            }
                        });
                    }

                    function addBlock(type) {
                        blocks.push({ id: Date.now(), type: type, content: '' });
                        render();
                    }

                    function updateContent(id, val) {
                        const b = blocks.find(x => x.id == id);
                        if (b) b.content = val;
                    }

                    function deleteBlock(id) {
                        blocks = blocks.filter(x => x.id !== id);
                        render();
                    }

                    function saveAjax() {
                        const formData = new FormData();
                        formData.append('save_blocks', '1');
                        formData.append('page_id', currentPageId);
                        formData.append('block_data', JSON.stringify(blocks));

                        const status = document.getElementById('save-status');
                        fetch('admin.php', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                status.classList.remove('hidden');
                                setTimeout(() => status.classList.add('hidden'), 2000);
                                refreshPreview();
                            }
                        });
                    }

                    function refreshPreview() {
                        document.getElementById('preview-frame').contentWindow.location.reload();
                    }

                    render();
                </script>
            <?php endif; ?>

        </section>
    </main>
</body>
</html>