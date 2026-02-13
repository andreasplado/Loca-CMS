<?php
require_once 'core/config.php';
check_auth();

// Fetch the most recent image
$stmt = $db->query("SELECT filepath FROM media ORDER BY id DESC LIMIT 1");
$image = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
if ($image) {
    echo json_encode($image);
} else {
    echo json_encode(['error' => 'No images found', 'filepath' => '']);
}