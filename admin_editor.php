<?php
require_once 'core/config.php';
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$page_id]);
$page_data = $stmt->fetch();
if (!$page_data) { die("Page not found."); }
$page_title = $page_data['title'];
$saved_json = !empty($page_data['content']) ? $page_data['content'] : '[]';
$media_query = $db->query("SELECT * FROM media ORDER BY id DESC");
$media_items = $media_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visual Builder | <?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .grid-stack { background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px; min-height: 800px; }
        .grid-stack-item-content { background: white; border: 1px solid transparent !important; }
        .content-viewport { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .content-viewport video, .content-viewport img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
        .widget-controls { position: absolute; top: -26px; left: 0; right: 0; height: 26px; background: #3b82f6; display: flex; align-items: center; justify-content: space-between; padding: 0 10px; opacity: 0; pointer-events: none; z-index: 100; color: white; }
        .grid-stack-item:hover .widget-controls { opacity: 1; pointer-events: auto; }
        .widget-card { border: 1px solid #e2e8f0; border-radius: 12px; background: white; padding: 15px; text-align: center; cursor: grab; }
    </style>
</head>
<body class="bg-slate-100 flex flex-col h-screen">

    <header class="h-[65px] bg-white border-b flex justify-between items-center px-6 shadow-sm z-50">
        <div>
            <h2 class="font-bold text-sm">Visual Builder</h2>
            <p class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($page_title) ?></p>
        </div>
        <div class="flex items-center gap-3">
            <span id="status" class="hidden text-[10px] font-bold text-green-500 uppercase px-3 py-1 bg-green-50 rounded-full">Saved</span>
            <button onclick="saveGrid()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-xs uppercase">Publish Changes</button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <aside class="w-80 bg-white border-r flex flex-col shadow-xl">
            <div id="widgets-panel" class="p-4 grid grid-cols-2 gap-3 overflow-y-auto">
                <div class="grid-stack-item sidebar-item widget-card" gs-w="12" gs-h="2" data-type="heading">Heading</div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="12" gs-h="2" data-type="text">Text</div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="6" gs-h="4" data-type="image">Image</div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="6" gs-h="4" data-type="video">Video</div>
            </div>
            <div id="inspector" class="hidden flex-1 p-5 bg-slate-50">
                <button onclick="closeInspector()" class="text-xs mb-4 text-blue-600 underline">← Back</button>
                <div id="inspector-content"></div>
            </div>
        </aside>

        <main class="flex-1 bg-slate-200 p-8 overflow-y-auto">
            <div class="max-w-5xl mx-auto shadow-2xl bg-white min-h-screen">
                 <div class="grid-stack"></div>
            </div>
        </main>
    </div>

    <div id="mediaModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/80 p-6">
        <div class="bg-white w-full max-w-4xl max-h-[80vh] rounded-2xl flex flex-col overflow-hidden">
            <div class="p-4 border-b flex justify-between">
                <h3 class="font-bold">Media Library</h3>
                <button onclick="closeMediaModal()" class="text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto grid grid-cols-4 gap-4">
                <?php foreach($media_items as $item): 
                    $is_vid = in_array(strtolower(pathinfo($item['filename'], PATHINFO_EXTENSION)), ['mp4', 'webm']);
                ?>
                    <div onclick="selectMedia('<?= $item['filepath'] ?>')" class="cursor-pointer border-2 hover:border-blue-500 rounded aspect-square overflow-hidden bg-slate-100">
                        <?php if($is_vid): ?>
                            <video src="<?= $item['filepath'] ?>" class="w-full h-full object-cover"></video>
                        <?php else: ?>
                            <img src="<?= $item['filepath'] ?>" class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<script>
    let grid = GridStack.init({
        cellHeight: 50, margin: 5, acceptWidgets: true,
        dragIn: '.sidebar-item', dragInOptions: { appendTo: 'body', helper: 'clone' },
        float: true, resizable: { handles: 'se' }, handle: '.widget-controls' 
    });
    GridStack.setupDragIn('.sidebar-item');

    const widgetDefaults = {
        heading: { content: 'New Heading', extra: 'text-2xl font-bold' },
        text: { content: 'Paragraph text...', extra: 'text-base' },
        image: { content: 'https://placehold.co/600x400', extra: 'w-full rounded' },
        video: { content: '', extra: 'w-full h-full object-cover' },
        button: { content: 'Button', extra: 'bg-blue-500 text-white px-4 py-2' }
    };

    function generateHTML(type, content, id, extra = '') {
        let view = '';
        if (type === 'heading') view = `<h2 class="${extra}">${content}</h2>`;
        if (type === 'text')    view = `<p class="${extra}">${content}</p>`;
        if (type === 'image')   view = `<img src="${content}" class="${extra}">`;
        if (type === 'video')   view = `<video src="${content}" class="${extra}" autoplay muted loop playsinline></video>`;
        if (type === 'button')  view = `<button class="${extra}">${content}</button>`;

        return `
            <div class="grid-stack-item-content">
                <div class="widget-controls">
                    <span class="text-[8px] font-bold uppercase">${type}</span>
                    <i onclick="grid.removeWidget(this.closest('.grid-stack-item'))" class="fa fa-trash text-[10px] cursor-pointer"></i>
                </div>
                <div class="content-viewport" onclick="openInspector('${id}', this)" 
                     data-type="${type}" data-content="${content}" data-extra="${extra}">
                    ${view}
                </div>
            </div>`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedData = <?= $saved_json ?>;
        grid.load(savedData.map(i => ({...i, content: generateHTML(i.type, i.content, i.id, i.extra)})));
    });

    grid.on('added', (e, items) => {
        items.forEach(item => {
            if (item.el.classList.contains('sidebar-item')) {
                const type = item.el.dataset.type;
                const id = 'el_' + Math.random().toString(36).substr(2, 9);
                const data = widgetDefaults[type];
                item.el.innerHTML = generateHTML(type, data.content, id, data.extra);
                item.el.classList.remove('sidebar-item');
                item.el.setAttribute('gs-id', id);
                grid.makeWidget(item.el);
            }
        });
    });

    let activeEditId = null;
    function openInspector(id, el) {
        activeEditId = id;
        const type = el.dataset.type;
        const isMedia = (type === 'image' || type === 'video');
        document.getElementById('widgets-panel').classList.add('hidden');
        document.getElementById('inspector').classList.remove('hidden');
        document.getElementById('inspector-content').innerHTML = `
            <div class="space-y-4">
                <label class="block text-xs font-bold uppercase">Content</label>
                <textarea id="edit-content" class="w-full border p-2 text-sm rounded">${el.dataset.content}</textarea>
                ${isMedia ? `<button onclick="openMediaModal()" class="w-full bg-slate-200 py-2 text-xs font-bold rounded">Browse Library</button>` : ''}
                <label class="block text-xs font-bold uppercase">CSS Classes</label>
                <input type="text" id="edit-extra" value="${el.dataset.extra}" class="w-full border p-2 text-sm rounded">
                <button onclick="updateWidget('${id}')" class="w-full bg-blue-600 text-white py-2 rounded font-bold text-xs uppercase">Apply</button>
            </div>`;
    }

    function updateWidget(id) {
        const item = document.querySelector(`[gs-id="${id}"]`);
        if(!item) return;
        const vp = item.querySelector('.content-viewport');
        const type = vp.dataset.type;
        const content = document.getElementById('edit-content').value;
        const extra = document.getElementById('edit-extra').value;

        // CRITICAL FIX: Use grid.update to change content without breaking the node
        grid.update(item, { content: generateHTML(type, content, id, extra) });
        item.setAttribute('gs-id', id); // Re-assign ID
        closeInspector();
    }

    function selectMedia(path) {
        document.getElementById('edit-content').value = path;
        closeMediaModal();
        updateWidget(activeEditId);
    }

    function closeInspector() { 
        document.getElementById('widgets-panel').classList.remove('hidden'); 
        document.getElementById('inspector').classList.add('hidden'); 
    }
    function openMediaModal() { document.getElementById('mediaModal').classList.remove('hidden'); }
    function closeMediaModal() { document.getElementById('mediaModal').classList.add('hidden'); }

    async function saveGrid() {
        const status = document.getElementById('status');
        const nodes = grid.save();
        
        const data = nodes.map(node => {
            const itemEl = document.querySelector(`[gs-id="${node.id}"]`);
            if (!itemEl) return null;
            const vp = itemEl.querySelector('.content-viewport');
            return {
                x: node.x, y: node.y, w: node.w, h: node.h, id: node.id,
                type: vp.dataset.type, content: vp.dataset.content, extra: vp.dataset.extra
            };
        }).filter(i => i !== null);

        status.textContent = "Publishing...";
        status.classList.remove('hidden');

        const fd = new FormData();
        fd.append('save_blocks', '1');
        fd.append('page_id', '<?= $page_id ?>');
        fd.append('block_data', JSON.stringify(data));

        try {
            // FIX: Ensure this filename exactly matches your PHP file
            const response = await fetch('admin_save_builder_handler', { method: 'POST', body: fd });
            const result = await response.json();
            if (result.status === 'success') {
                status.textContent = "Published!";
                setTimeout(() => status.classList.add('hidden'), 2000);
            } else {
                alert("Error: " + result.message);
            }
        } catch (err) {
            console.error(err);
            alert("Connection error.");
        }
    }
</script>
</body>
</html>