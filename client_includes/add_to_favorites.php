<?php
/**
 * Add Thesis to Favorites
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

// Check if already favorited
$check_stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND thesis_id = ?");
$check_stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This thesis is already in your favorites']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Add to favorites
$stmt = $conn->prepare("INSERT INTO favorites (user_id, thesis_id) VALUES (?, ?)");
$stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Added to favorites successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
}

$stmt->close();
$conn->close();
?>
