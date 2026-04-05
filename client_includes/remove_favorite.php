<?php
/**
 * Remove Thesis from Favorites
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;

if ($thesis_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid thesis ID']);
    exit();
}

// Remove from favorites
$stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND thesis_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
}

$stmt->close();
$conn->close();
?>
