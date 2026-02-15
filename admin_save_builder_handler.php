<?php
require_once 'core/config.php';

// Prevent any unexpected white space from breaking the JSON response
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    try {
        if (!isset($_POST['save_blocks'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Key']);
            exit;
        }

        $page_id = (int)$_POST['page_id'];
        $block_data = $_POST['block_data']; // This is the JSON string from JavaScript

        // Update the database
        $stmt = $db->prepare("UPDATE pages SET content = ? WHERE id = ?");
        $success = $stmt->execute([$block_data, $page_id]);

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'No POST data received']);
    exit;
}