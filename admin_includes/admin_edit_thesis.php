<?php
/**
 * Edit Thesis Handler
 * Citas Smart Archive System
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
    $title = sanitize_input($_POST['title'] ?? '');
    $author = sanitize_input($_POST['author'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $abstract = sanitize_input($_POST['abstract'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'pending');

    // Validate required fields
    if (empty($thesis_id) || empty($title) || empty($author) || empty($course) || empty($year) || empty($abstract)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Validate status
    $valid_statuses = ['pending', 'approved', 'archived'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'pending';
    }

    // Update thesis
    $stmt = $conn->prepare("
        UPDATE thesis 
        SET title = ?, author = ?, course = ?, year = ?, abstract = ?, status = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Bind parameters
    $bind_result = $stmt->bind_param(
        "sssissi",
        $title,
        $author,
        $course,
        $year,
        $abstract,
        $status,
        $thesis_id
    );

    if (!$bind_result) {
        echo json_encode(['success' => false, 'message' => 'Bind error: ' . $stmt->error]);
        exit;
    }

    // Execute statement
    if ($stmt->execute()) {
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thesis updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update thesis: ' . $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
