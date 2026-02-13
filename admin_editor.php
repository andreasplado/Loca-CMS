<?php
// 1. Database Connection & Data Fetching
require_once 'core/config.php';

$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing page data
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$page_id]);
$page_data = $stmt->fetch();

if (!$page_data) {
    die("Page not found.");
}

$page_title = $page_data['title'];
// Prepare the JSON for JavaScript; default to empty array if null
$saved_json = !empty($page_data['content']) ? $page_data['content'] : '[]';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Builder | <?= htmlspecialchars($page_title) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .grid-stack { 
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px); 
            background-size: 20px 20px; 
            min-height: 1000px;
        }
        .grid-stack-item-content { 
            background: white; 
            border: 1px solid transparent !important; 
            overflow: visible !important; 
        }
        .grid-stack-item:hover .grid-stack-item-content { 
            border: 1px solid #3b82f6 !important; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
        }
        
        /* The Blue Control Bar */
        .widget-controls { 
            position: absolute; top: -26px; left: 0; right: 0; height: 26px; 
            background: #3b82f6; display: flex; align-items: center; justify-content: space-between; 
            padding: 0 10px; opacity: 0; pointer-events: none; transition: 0.2s; z-index: 100; color: white;
        }
        .grid-stack-item:hover .widget-controls { opacity: 1; pointer-events: auto; }
        
        .widget-card { 
            border: 1px solid #e2e8f0; border-radius: 12px; background: white; padding: 15px;
            text-align: center; cursor: grab; transition: all 0.2s; display: flex; flex-direction: column; align-items: center;
        }
        .widget-card span { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-top: 4px;}
        .widget-card:hover { border-color: #3b82f6; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        
        .content-viewport { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; padding: 10px; cursor: pointer; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="fixed inset-0 bg-slate-100 flex flex-col font-sans text-slate-900">

    <header class="h-[65px] bg-white border-b flex justify-between items-center px-6 shadow-sm z-50">
        <div class="flex items-center gap-4">
            <a href="?page=pages" class="text-slate-400 hover:text-blue-600 transition"><i class="fa fa-chevron-left"></i></a>
            <div>
                <h2 class="font-bold text-sm tracking-tight">Visual Builder</h2>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Editing: <?= htmlspecialchars($page_title) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span id="status" class="hidden text-[10px] font-bold text-green-500 uppercase px-3 py-1 bg-green-50 rounded-full">Saved</span>
            <button onclick="saveGrid()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold text-xs shadow-md transition-all active:scale-95 uppercase tracking-wider">
                Publish Changes
            </button>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        <aside class="w-80 bg-white border-r flex flex-col z-40 shadow-xl">
            <div id="sidebar-nav" class="p-4 border-b flex gap-2">
                <button onclick="showSidebar('widgets')" id="tab-widgets" class="flex-1 py-2 text-[10px] font-bold uppercase tracking-widest border-b-2 border-blue-600">Widgets</button>
                <button onclick="showSidebar('settings')" id="tab-settings" class="flex-1 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Settings</button>
            </div>
            
            <div id="widgets-panel" class="p-4 grid grid-cols-2 gap-3 overflow-y-auto custom-scrollbar">
                <div class="grid-stack-item sidebar-item widget-card" gs-w="12" gs-h="2" data-type="heading">
                    <div class="grid-stack-item-content"><i class="fa fa-heading text-slate-400 text-xl"></i><span>Heading</span></div>
                </div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="12" gs-h="2" data-type="text">
                    <div class="grid-stack-item-content"><i class="fa fa-align-left text-slate-400 text-xl"></i><span>Paragraph</span></div>
                </div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="6" gs-h="4" data-type="image">
                    <div class="grid-stack-item-content"><i class="fa fa-image text-slate-400 text-xl"></i><span>Image</span></div>
                </div>
                <div class="grid-stack-item sidebar-item widget-card" gs-w="6" gs-h="2" data-type="button">
                    <div class="grid-stack-item-content"><i class="fa fa-mouse-pointer text-slate-400 text-xl"></i><span>Button</span></div>
                </div>
            </div>

            <div id="inspector" class="hidden flex-1 flex flex-col bg-slate-50">
                <div class="p-4 border-b bg-white flex justify-between items-center">
                    <span class="text-[10px] font-black text-blue-600 uppercase">Edit Widget</span>
                    <button onclick="closeInspector()" class="text-slate-300 hover:text-slate-600"><i class="fa fa-times"></i></button>
                </div>
                <div id="inspector-content" class="p-5"></div>
            </div>
        </aside>

        <main class="flex-1 bg-slate-200 p-8 overflow-y-auto relative custom-scrollbar">
            <div class="max-w-5xl mx-auto shadow-2xl">
                 <div class="grid-stack bg-white rounded-sm"></div>
            </div>
        </main>
    </div>

<script>
    // 1. Initialize GridStack
    let grid = GridStack.init({
        cellHeight: 50,
        margin: 5,
        acceptWidgets: true,
        dragIn: '.sidebar-item',
        dragInOptions: { appendTo: 'body', helper: 'clone' },
        float: true,
        resizable: { handles: 'se' },
        handle: '.widget-controls' 
    });

    GridStack.setupDragIn('.sidebar-item');

    const widgetDefaults = {
        heading: { content: 'New Heading', extra: 'text-3xl font-black text-slate-800' },
        text: { content: 'Start typing your paragraph here...', extra: 'text-base text-slate-600' },
        image: { content: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800', extra: 'rounded-lg w-full' },
        button: { content: 'Get Started', extra: 'bg-blue-600 text-white px-8 py-2 rounded-full font-bold' }
    };

    // 2. HTML Generator
    function generateHTML(type, content, id, extra = '') {
        let view = '';
        if (type === 'heading') view = `<h2 class="${extra}">${content}</h2>`;
        if (type === 'text')    view = `<p class="${extra}">${content}</p>`;
        if (type === 'image')   view = `<img src="${content}" class="${extra}">`;
        if (type === 'button')  view = `<button class="${extra}">${content}</button>`;

        return `
            <div class="grid-stack-item-content">
                <div class="widget-controls">
                    <span class="text-[8px] font-bold uppercase tracking-widest">${type}</span>
                    <div class="flex gap-3">
                        <i onclick="duplicateWidget('${id}')" class="fa fa-clone text-[10px] cursor-pointer hover:text-blue-200" title="Duplicate"></i>
                        <i onclick="grid.removeWidget(this.closest('.grid-stack-item'))" class="fa fa-trash text-[10px] cursor-pointer hover:text-red-300"></i>
                    </div>
                </div>
                <div class="content-viewport" onclick="openInspector('${id}', this)" 
                     data-type="${type}" data-content="${content}" data-extra="${extra}">
                    ${view}
                </div>
            </div>`;
    }

    // 3. Load Saved Content from PHP
    document.addEventListener('DOMContentLoaded', function() {
        const savedData = <?= $saved_json ?>;
        if (savedData && savedData.length > 0) {
            const items = savedData.map(item => ({
                ...item,
                content: generateHTML(item.type, item.content, item.id, item.extra)
            }));
            grid.load(items);
            
            // Sync gs-id attributes after load
            document.querySelectorAll('.grid-stack-item').forEach(el => {
                const id = el.getAttribute('gs-id');
                if (id) el.setAttribute('gs-id', id);
            });
        }
    });

    // 4. Sidebar Drag Handler
    grid.on('added', function(e, items) {
        items.forEach(item => {
            if (item.el.classList.contains('sidebar-item')) {
                const type = item.el.getAttribute('data-type');
                const id = 'el_' + Math.random().toString(36).substr(2, 9);
                const data = widgetDefaults[type];
                
                item.el.innerHTML = generateHTML(type, data.content, id, data.extra);
                item.el.classList.remove('sidebar-item', 'widget-card');
                item.el.setAttribute('gs-id', id);
                grid.makeWidget(item.el);
            }
        });
    });

    // 5. Duplicate Widget
    function duplicateWidget(id) {
        const original = document.querySelector(`[gs-id="${id}"]`);
        if (!original) return;
        const vp = original.querySelector('.content-viewport');
        const node = original.gridstackNode;
        const newId = 'el_' + Math.random().toString(36).substr(2, 9);
        
        grid.addWidget({
            x: node.x, y: node.y + node.h, w: node.w, h: node.h,
            id: newId,
            content: generateHTML(vp.dataset.type, vp.dataset.content, newId, vp.dataset.extra)
        });
    }

    // 6. Inspector
    function openInspector(id, el) {
        const type = el.dataset.type;
        const content = el.dataset.content;
        const extra = el.dataset.extra;
        
        document.getElementById('widgets-panel').classList.add('hidden');
        document.getElementById('inspector').classList.remove('hidden');

        document.getElementById('inspector-content').innerHTML = `
            <div class="space-y-4">
                <div>
                    <label class="text-[9px] font-bold text-slate-400 uppercase">Content / URL</label>
                    <textarea id="edit-content" class="w-full mt-1 border rounded p-2 text-sm h-24 focus:ring-2 focus:ring-blue-500 outline-none">${content}</textarea>
                </div>
                <div>
                    <label class="text-[9px] font-bold text-slate-400 uppercase">Classes (Tailwind)</label>
                    <input type="text" id="edit-extra" value="${extra}" class="w-full mt-1 border rounded p-2 text-sm outline-none">
                </div>
                <button onclick="updateWidget('${id}')" class="w-full bg-blue-600 text-white py-2 rounded font-bold text-xs uppercase shadow-lg">Apply</button>
            </div>`;
    }

    function updateWidget(id) {
        const item = document.querySelector(`[gs-id="${id}"]`);
        const vp = item.querySelector('.content-viewport');
        const type = vp.dataset.type;
        const content = document.getElementById('edit-content').value;
        const extra = document.getElementById('edit-extra').value;

        item.innerHTML = generateHTML(type, content, id, extra);
        grid.makeWidget(item); 
        closeInspector();
    }

    function closeInspector() {
        document.getElementById('widgets-panel').classList.remove('hidden');
        document.getElementById('inspector').classList.add('hidden');
    }

    // 7. Save Function
    async function saveGrid() {
        const status = document.getElementById('status');
        const nodes = grid.save();
        
        const data = nodes.map(node => {
            const itemEl = document.querySelector(`[gs-id="${node.id}"]`);
            const vp = itemEl ? itemEl.querySelector('.content-viewport') : null;
            if (!vp) return null;
            return {
                x: node.x, y: node.y, w: node.w, h: node.h, id: node.id,
                type: vp.dataset.type, content: vp.dataset.content, extra: vp.dataset.extra
            };
        }).filter(i => i !== null);

        if (status) { status.classList.remove('hidden'); status.textContent = "Publishing..."; }

        const fd = new FormData();
        fd.append('save_blocks', '1');
        fd.append('page_id', '<?= $page_id ?>');
        fd.append('block_data', JSON.stringify(data));

        try {
            const response = await fetch('admin_save_builder_handler', { method: 'POST', body: fd });
            const result = await response.json();
            if (result.status === 'success') {
                status.textContent = "Published!";
                setTimeout(() => status.classList.add('hidden'), 2000);
            }
        } catch (err) {
            alert("Save failed. Make sure save_handler.php exists.");
        }
    }

    function showSidebar(panel) {
        const tabs = ['widgets', 'settings'];
        tabs.forEach(t => {
            document.getElementById('tab-' + t).classList.remove('border-blue-600', 'text-slate-900');
            document.getElementById('tab-' + t).classList.add('text-slate-400');
        });
        document.getElementById('tab-' + panel).classList.add('border-blue-600', 'text-slate-900');
        // Simple logic for toggle; currently only widgets/inspector are active
    }
</script>
</body>
</html>