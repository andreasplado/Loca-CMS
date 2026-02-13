<link href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.js"></script>

<div class="fixed inset-0 z-[100] bg-slate-100 flex flex-col font-sans">
    <header class="h-[65px] bg-slate-900 text-white flex justify-between items-center px-6 shadow-xl">
        <div class="flex items-center gap-4">
            <a href="?page=pages" class="text-slate-400 hover:text-white transition"><i class="fa fa-arrow-left"></i></a>
            <h2 class="font-bold text-sm tracking-wide uppercase">Visual Editor: <span class="text-blue-400"><?= htmlspecialchars($page_title) ?></span></h2>
        </div>
        <button onclick="saveGrid()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-full font-bold text-xs shadow-lg transition uppercase tracking-widest">
            Save Changes
        </button>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <aside class="w-72 bg-white border-r shadow-xl flex flex-col z-20">
            <div class="p-4 border-b bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">Drag Elements</div>
            <div class="p-4 grid grid-cols-2 gap-3">
                <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:border-blue-400 text-center transition shadow-sm" data-type="text">
                    <i class="fa fa-font text-slate-400 mb-1 block"></i><span class="text-[9px] font-bold">TEXT</span>
                </div>
                <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:border-blue-400 text-center transition shadow-sm" data-type="image">
                    <i class="fa fa-image text-slate-400 mb-1 block"></i><span class="text-[9px] font-bold">IMAGE</span>
                </div>
                <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:border-blue-400 text-center transition shadow-sm" data-type="video">
                    <i class="fa fa-play text-slate-400 mb-1 block"></i><span class="text-[9px] font-bold">VIDEO</span>
                </div>
                <div class="newWidget p-4 border rounded-xl bg-white cursor-grab hover:border-blue-400 text-center transition shadow-sm" data-type="button">
                    <i class="fa fa-link text-slate-400 mb-1 block"></i><span class="text-[9px] font-bold">BUTTON</span>
                </div>
            </div>

            <div id="inspector" class="hidden flex-1 border-t bg-slate-50 p-5 overflow-y-auto">
                <h3 class="text-[10px] font-black uppercase text-blue-600 mb-4 border-b pb-2 tracking-widest">Properties</h3>
                <div id="inspector-content"></div>
            </div>
        </aside>

        <main class="flex-1 bg-slate-200 p-8 overflow-y-auto">
            <div class="grid-stack bg-white min-h-screen rounded-xl shadow-inner p-4"></div>
        </main>
    </div>
</div>

<style>
    .grid-stack { background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px; }
    .grid-stack-item-content { background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .block-header { background: #f8fafc; padding: 6px 10px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .block-type { font-size: 8px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
</style>

<script>
    // Initialize GridStack
    let grid = GridStack.init({
        cellHeight: 70,
        acceptWidgets: true,
        dragIn: '.newWidget',
        dragInOptions: { revert: 'invalid', scroll: false, appendTo: 'body', helper: 'clone' },
        margin: 10,
        float: true // Items stay where you put them
    });

    // Load Data & Map correctly
    let savedData = <?= $page_json ?>;
    
    // We transform saved data to ensure it has a proper ID for HTML mapping
    grid.load(savedData.map(item => ({
        ...item,
        content: createInnerHtml(item.content_type || item.type, item.content, item.id)
    })));

    // Function to generate the HTML inside the widget box
    function createInnerHtml(type, content, id) {
        return `
            <div class="grid-stack-item-content" data-type="${type}" data-content="${content || ''}">
                <div class="block-header drag-handle">
                    <span class="block-type">${type}</span>
                    <button onclick="grid.removeWidget(this.closest('.grid-stack-item'))" class="text-slate-300 hover:text-red-500 transition">
                        <i class="fa fa-times text-[10px]"></i>
                    </button>
                </div>
                <div class="p-4 text-[10px] text-slate-500 cursor-pointer hover:bg-slate-50" onclick="editWidget('${id}', this)">
                    ${content ? content.substring(0, 40) + '...' : '<i>Click to edit content</i>'}
                </div>
            </div>`;
    }

    // Capture "Added" event for new drops
    grid.on('added', function(e, items) {
        items.forEach(item => {
            let type = item.el.getAttribute('data-type');
            if(type) {
                // Assign a unique ID if it doesn't have one
                let newId = 'block_' + Date.now();
                item.el.setAttribute('gs-id', newId); 
                item.el.innerHTML = createInnerHtml(type, '', newId);
            }
        });
    });

    function editWidget(id, displayEl) {
        const itemEl = displayEl.closest('.grid-stack-item');
        const contentDiv = itemEl.querySelector('.grid-stack-item-content');
        const inspector = document.getElementById('inspector');
        const contentArea = document.getElementById('inspector-content');
        
        inspector.classList.remove('hidden');
        contentArea.innerHTML = `
            <div class="space-y-4">
                <label class="block text-[10px] font-bold text-slate-500 uppercase">Content / URL / HTML</label>
                <textarea id="temp-content" class="w-full border rounded-lg p-3 text-sm h-48 focus:ring-2 focus:ring-blue-500 outline-none">${contentDiv.getAttribute('data-content') || ''}</textarea>
                <button onclick="updateWidgetData('${id}')" class="w-full bg-blue-600 text-white p-2 rounded-lg text-xs font-bold uppercase tracking-widest shadow-md">Update Block</button>
            </div>`;
    }

    function updateWidgetData(id) {
        const val = document.getElementById('temp-content').value;
        const itemEl = document.querySelector(`[gs-id="${id}"]`);
        const contentDiv = itemEl.querySelector('.grid-stack-item-content');
        
        contentDiv.setAttribute('data-content', val);
        itemEl.querySelector('.p-4').innerHTML = val ? val.substring(0, 40) + '...' : '<i>Click to edit content</i>';
        alert("Content updated! Click Save Changes to store in database.");
    }

    function saveGrid() {
        // grid.save() returns an array of objects with {x, y, w, h, id}
        let data = grid.save();
        
        // Enrich data with our custom content attributes
        let fullData = data.map(node => {
            const el = document.querySelector(`[gs-id="${node.id}"]`);
            const contentDiv = el.querySelector('.grid-stack-item-content');
            return {
                ...node,
                content_type: contentDiv.getAttribute('data-type'),
                content: contentDiv.getAttribute('data-content')
            };
        });

        const fd = new FormData();
        fd.append('save_blocks', '1');
        fd.append('page_id', '<?= $page_id ?>');
        fd.append('block_data', JSON.stringify(fullData));
        
        fetch('admin.php', { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json()).then(res => {
            if(res.status === 'success') {
                alert("Layout and Sizes Saved Successfully!");
            } else {
                alert("Error saving layout.");
            }
        });
    }
</script>