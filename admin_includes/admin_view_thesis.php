<?php
/**
 * View Thesis Handler
 * CITAS Smart Archive System
 */

require_once '../db_includes/db_connect.php';
require_login();
require_admin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $thesis_id = intval($_POST['thesis_id'] ?? 0);

    if (empty($thesis_id)) {
        echo json_encode(['success' => false, 'message' => 'Thesis ID is required']);
        exit;
    }

    // Fetch thesis from database
    $stmt = $conn->prepare("SELECT id, title, author, course, year, abstract, abstract as description, status, file_path FROM thesis WHERE id = ?");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Thesis not found']);
        exit;
    }

    $thesis = $result->fetch_assoc();
    $stmt->close();

    // Return thesis data
    echo json_encode([
        'success' => true,
        'thesis' => $thesis
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
