<?php
/**
 * Add New Thesis Handler
 * Citas Smart Archive System
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_once '../client_includes/create_notification.php';
require_login();
require_admin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$title = sanitize_input($_POST['title'] ?? '');
$author = sanitize_input($_POST['author'] ?? '');
$course = sanitize_input($_POST['course'] ?? '');
$year = intval($_POST['year'] ?? 0);
$abstract = sanitize_input($_POST['abstract'] ?? '');
$status = sanitize_input($_POST['status'] ?? 'pending');

// Validate required fields
if (empty($title) || empty($author) || empty($course) || empty($year) || empty($abstract)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'approved', 'archived'];
if (!in_array($status, $valid_statuses)) {
    $status = 'pending';
}

// Handle file upload
$file_path = '';
$file_type = '';
$file_size = 0;

if (isset($_FILES['thesis_file']) && $_FILES['thesis_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['thesis_file'];
    
    // Validate file type
    $allowed_types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, array_keys($allowed_types))) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and DOCX are allowed']);
        exit;
    }

    // Check file size (max 50MB)
    $max_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 50MB limit']);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $base_dir = dirname(__DIR__); // Goes to c:\xampp\htdocs\ctrws-fix
    $upload_dir = $base_dir . '/uploads/thesis_files/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }

    // Generate unique filename
    $filename = uniqid('thesis_') . '_' . time() . '.' . $file_ext;
    $file_path = 'uploads/thesis_files/' . $filename;
    $full_path = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $full_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file. Please check directory permissions.']);
        exit;
    }

    $file_type = $file_ext;
    $file_size = $file['size'];
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

    // Insert thesis into database
    $stmt = $conn->prepare("
        INSERT INTO thesis (title, author, course, year, abstract, file_path, file_type, file_size, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Bind parameters
    $bind_result = $stmt->bind_param(
        "sssisssss",
        $title,
        $author,
        $course,
        $year,
        $abstract,
        $file_path,
        $file_type,
        $file_size,
        $status
    );

    if (!$bind_result) {
        echo json_encode(['success' => false, 'message' => 'Bind error: ' . $stmt->error]);
        exit;
    }

    // Execute statement
    if ($stmt->execute()) {
        $thesis_id = $stmt->insert_id;
        $stmt->close();
        
        // Save classification data if provided
        $subject_category = sanitize_input($_POST['subject_category'] ?? '');
        $subject_confidence = floatval($_POST['subject_confidence'] ?? 100);
        $research_method = sanitize_input($_POST['research_method'] ?? '');
        $method_confidence = floatval($_POST['method_confidence'] ?? 100);
        $complexity_level = sanitize_input($_POST['complexity_level'] ?? 'intermediate');
        $complexity_confidence = floatval($_POST['complexity_confidence'] ?? 100);
        $keywords = $_POST['keywords'] ?? '';
        $citations = $_POST['citations'] ?? '[]';
        $related_thesis_ids = $_POST['related_thesis_ids'] ?? '[]';

        // If any classification data was provided, save it
        if (!empty($subject_category)) {
            // Process keywords - ensure it's valid JSON string
            $keywords_json = '[]';
            if (!empty($keywords)) {
                if (strpos($keywords, '[') === 0 || strpos($keywords, '{') === 0) {
                    // Already JSON format
                    $keywords_json = $keywords;
                } else {
                    // Convert comma-separated to JSON array
                    $kw_list = array_map('trim', explode(',', $keywords));
                    $keywords_json = json_encode($kw_list);
                }
            }

            // Validate and ensure JSON strings
            if (empty($citations) || json_decode($citations) === null) {
                $citations = '[]';
            }
            if (empty($related_thesis_ids) || json_decode($related_thesis_ids) === null) {
                $related_thesis_ids = '[]';
            }

            // Insert or update classification
            $classif_stmt = $conn->prepare("
                INSERT INTO thesis_classification 
                (thesis_id, subject_category, subject_confidence, keywords, research_method, method_confidence, complexity_level, complexity_confidence, citations, related_thesis_ids)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                subject_category=VALUES(subject_category),
                subject_confidence=VALUES(subject_confidence),
                keywords=VALUES(keywords),
                research_method=VALUES(research_method),
                method_confidence=VALUES(method_confidence),
                complexity_level=VALUES(complexity_level),
                complexity_confidence=VALUES(complexity_confidence),
                citations=VALUES(citations),
                related_thesis_ids=VALUES(related_thesis_ids),
                last_updated=NOW()
            ");

            if ($classif_stmt) {
                $classif_stmt->bind_param(
                    "isdssdsdss",
                    $thesis_id,
                    $subject_category,
                    $subject_confidence,
                    $keywords_json,
                    $research_method,
                    $method_confidence,
                    $complexity_level,
                    $complexity_confidence,
                    $citations,
                    $related_thesis_ids
                );
                $classif_stmt->execute();
                $classif_stmt->close();
            }
        }
        
        // CREATE NOTIFICATION: If thesis is approved, notify all users
        try {
            if ($status === 'approved') {
                $user_ids = get_all_users_except_admin();
                if (!empty($user_ids)) {
                    $notification_title = "New Thesis Available: " . $title;
                    $notification_message = "A new thesis \"" . htmlspecialchars($title) . "\" by " . htmlspecialchars($author) . " is now available for viewing.";
                    
                    $notified_count = create_bulk_notification(
                        $user_ids,
                        'thesis_upload',
                        $notification_title,
                        $notification_message,
                        $thesis_id
                    );
                    
                    error_log("Created " . $notified_count . " notifications for new approved thesis ID: " . $thesis_id);
                }
            }
        } catch (Exception $e) {
            // Log notification error but don't fail the thesis creation
            error_log("Failed to create notifications for new thesis: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Thesis added successfully with classification',
            'thesis_id' => $thesis_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add thesis: ' . $stmt->error]);
    }

$conn->close();
?>
