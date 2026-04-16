<?php
/**
 * Check Journal Conversion Status API
 * CITAS Smart Archive System
 * 
 * Returns the status of journal conversion for a thesis
 * Usage: GET /api_includes/check_conversion_status.php?thesis_id=67
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';

// Check if admin
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get thesis ID
$thesis_id = intval($_GET['thesis_id'] ?? 0);

if ($thesis_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid thesis ID']);
    exit;
}

// Query conversion status
$stmt = $conn->prepare("
    SELECT 
        id,
        title,
        author,
        is_journal_converted,
        journal_conversion_status,
        journal_page_count,
        journal_converted_at,
        journal_file_path
    FROM thesis
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Thesis not found']);
    $stmt->close();
    exit;
}

$thesis = $result->fetch_assoc();
$stmt->close();

// Prepare response
$response = [
    'success' => true,
    'thesis_id' => $thesis['id'],
    'title' => $thesis['title'],
    'author' => $thesis['author'],
    'conversion' => [
        'status' => $thesis['journal_conversion_status'] ?? 'pending',
        'is_converted' => boolval($thesis['is_journal_converted']),
        'page_count' => intval($thesis['journal_page_count'] ?? 0),
        'completed_at' => $thesis['journal_converted_at'],
        'journal_file' => $thesis['journal_file_path']
    ]
];

// Add human-readable status message
switch ($thesis['journal_conversion_status']) {
    case 'pending':
        $response['status_message'] = 'Conversion queued - will start processing soon';
        break;
    case 'processing':
        $response['status_message'] = 'Converting document to journal format...';
        break;
    case 'completed':
        $response['status_message'] = 'Successfully converted to ' . $thesis['journal_page_count'] . '-page journal format';
        break;
    case 'failed':
        $response['status_message'] = 'Conversion failed - original document is still available';
        break;
    default:
        $response['status_message'] = ucfirst($thesis['journal_conversion_status']);
}

echo json_encode($response);
$conn->close();
?>
