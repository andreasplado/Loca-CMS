<?php
require_once 'core/config.php';

/**
 * 1. AJAX HANDLER (Must be at the very top)
 */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    while (ob_get_level()) { ob_end_clean(); }
    $response = ['status' => 'error', 'message' => 'Unknown error occurred'];

    if (isset($_FILES['cms_file'])) {
        if ($_FILES['cms_file']['error'] !== UPLOAD_ERR_OK) {
            $err_codes = [1 => 'File too large (php.ini)', 2 => 'File too large (HTML)', 3 => 'Partial', 4 => 'No file', 6 => 'No temp', 7 => 'Disk fail'];
            echo json_encode(['status' => 'error', 'message' => $err_codes[$_FILES['cms_file']['error']] ?? 'Upload error']);
            exit;
        }

        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $file_ext = strtolower(pathinfo($_FILES["cms_file"]["name"], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_name;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'txt', 'html', 'css'];
        
        if (in_array($file_ext, $allowed)) {
            if (move_uploaded_file($_FILES["cms_file"]["tmp_name"], $target_file)) {
                $stmt = $db->prepare("INSERT INTO media (filename, filepath) VALUES (?, ?)");
                $stmt->execute([$new_name, $target_file]);
                $response = ['status' => 'success'];
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_text') {
        if (file_exists($_POST['filepath'])) {
            if (file_put_contents($_POST['filepath'], $_POST['content']) !== false) {
                $response = ['status' => 'success'];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; 
}

/**
 * 2. DELETE LOGIC
 */
if (isset($_GET['delete_id'])) {
    $stmt = $db->prepare("SELECT filepath FROM media WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists($file)) {
        unlink($file);
        $db->prepare("DELETE FROM media WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: ?page=media");
        exit;
    }
}

$media = $db->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();

function get_media_type($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) return 'image';
    if (in_array($ext, ['mp4', 'webm'])) return 'video';
    if (in_array($ext, ['txt', 'html', 'css'])) return 'text';
    return 'other';
}
?>

<div class="p-6 bg-slate-50 min-h-screen font-sans">
    
    <div class="mb-8">
        <div id="drop-zone" class="relative group border-4 border-dashed border-slate-200 bg-white p-12 rounded-2xl flex flex-col items-center justify-center transition-all hover:border-blue-400 hover:bg-blue-50/50">
            <input type="file" id="file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fa fa-cloud-upload-alt text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700">Multimedia Library</h3>
            <p class="text-slate-400 text-sm">Drop Images, SVGs, Videos, or Code files</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($media as $item): 
            $type = get_media_type($item['filename']);
        ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            
            <div class="h-52 bg-slate-100 relative group flex items-center justify-center overflow-hidden">
                
                <?php if ($type === 'image'): ?>
                    <img src="<?= $item['filepath'] ?>" class="max-w-full max-h-full object-contain p-2">

                <?php elseif ($type === 'video'): ?>
                    <video 
                        src="<?= $item['filepath'] ?>" 
                        class="w-full h-full object-cover" 
                        muted 
                        loop
                        onmouseover="this.play()" 
                        onmouseout="this.pause(); this.currentTime = 0;">
                    </video>
                    <div class="absolute bottom-2 right-2 bg-black/60 text-white text-[9px] px-2 py-1 rounded font-bold pointer-events-none">
                        <i class="fa fa-play mr-1"></i> VIDEO
                    </div>

                <?php elseif ($type === 'text'): ?>
                    <textarea onchange="updateText('<?= $item['filepath'] ?>', this.value)" 
                              class="w-full h-full p-3 text-[10px] font-mono bg-slate-900 text-green-400 border-none outline-none resize-none"><?= htmlspecialchars(@file_get_contents($item['filepath'])) ?></textarea>
                    <div class="absolute top-2 right-2 bg-blue-600 text-[8px] text-white px-2 py-0.5 rounded uppercase font-bold pointer-events-none">Editable</div>
                <?php endif; ?>

                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-4 z-10">
                    
                    <?php if ($type === 'video'): ?>
                    <button onclick="openVideoPopup('<?= $item['filepath'] ?>')" title="Play Fullscreen" class="bg-blue-600 p-3 rounded-full text-white hover:bg-blue-500 shadow-lg transform scale-90 group-hover:scale-100 transition">
                        <i class="fa fa-play"></i>
                    </button>
                    <?php endif; ?>

                    <button onclick="copyToClipboard('<?= $item['filepath'] ?>')" title="Copy URL" class="bg-white p-3 rounded-full text-slate-700 hover:text-blue-600 shadow-lg transform scale-90 group-hover:scale-100 transition">
                        <i class="fa fa-link"></i>
                    </button>
                    <a href="?page=media&delete_id=<?= $item['id'] ?>" onclick="return confirm('Delete permanently?')" class="bg-white p-3 rounded-full text-red-500 hover:bg-red-500 hover:text-white shadow-lg transform scale-90 group-hover:scale-100 transition">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>
            </div>
            
            <div class="p-3 bg-white border-t flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-600 truncate w-3/4"><?= $item['filename'] ?></span>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-400 uppercase font-black"><?= pathinfo($item['filename'], PATHINFO_EXTENSION) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="videoModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm">
    <button onclick="closeVideoPopup()" class="absolute top-6 right-6 text-white text-3xl hover:text-red-500 transition">
        <i class="fa fa-times"></i>
    </button>
    <div class="w-full max-w-4xl aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl">
        <video id="modalVideoPlayer" class="w-full h-full" controls autoplay></video>
    </div>
</div>

<script>
/**
 * VIDEO POPUP LOGIC
 */
const modal = document.getElementById('videoModal');
const player = document.getElementById('modalVideoPlayer');

function openVideoPopup(src) {
    player.src = src;
    modal.classList.remove('hidden');
    player.play();
}

function closeVideoPopup() {
    modal.classList.add('hidden');
    player.pause();
    player.src = "";
}

// Close modal if clicking background
modal.addEventListener('click', (e) => {
    if (e.target === modal) closeVideoPopup();
});

/**
 * AJAX UPLOAD & TEXT SAVE
 */
const fileInput = document.getElementById('file-input');
fileInput.addEventListener('change', function() {
    if(!this.files[0]) return;
    const formData = new FormData();
    formData.append('cms_file', this.files[0]);

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async (res) => {
        const text = await res.text();
        try { return JSON.parse(text); } catch(e) { throw new Error("Invalid response"); }
    })
    .then(data => {
        if(data.status === 'success') window.location.reload();
        else alert("Upload Error: " + data.message);
    })
    .catch(err => alert(err.message));
});

function updateText(path, content) {
    const formData = new FormData();
    formData.append('action', 'save_text');
    formData.append('filepath', path);
    formData.append('content', content);

    fetch(window.location.href, { 
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => { if(data.status === 'success') console.log('Saved'); });
}

function copyToClipboard(text) {
    const fullUrl = window.location.origin + '/' + text;
    navigator.clipboard.writeText(fullUrl).then(() => alert('URL Copied!'));
}
</script>