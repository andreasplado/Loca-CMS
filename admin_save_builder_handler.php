<?php
require_once 'core/config.php';
ob_clean();
header('Content-Type: application/json');

// Simplified check: just check if ANY post data exists
if (!empty($_POST)) {
    try {
        // If save_blocks isn't there, let's see what IS there
        if (!isset($_POST['save_blocks'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing save_blocks key. Found keys: ' . implode(', ', array_keys($_POST))]);
            exit;
        }

        $page_id = (int)$_POST['page_id'];
        $block_data = $_POST['block_data'];

        $stmt = $db->prepare("UPDATE pages SET content = ? WHERE id = ?");
        $success = $stmt->execute([$block_data, $page_id]);

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'No POST data received at all.']);
    exit;
}