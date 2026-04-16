<?php
/**
 * Add New Thesis Handler
 * CITAS Smart Archive System
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_once '../client_includes/create_notification.php';
require_login();
require_admin();

/**
 * Extract page count from PDF file
 * @param string $file_path Full path to PDF file
 * @return int Page count or 0 if unable to detect
 */
function get_pdf_page_count($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path) || filesize($file_path) === 0) {
        return 0;
    }
    
    try {
        $content = file_get_contents($file_path, false, NULL, 0, min(1000000, filesize($file_path)));
        
        if (empty($content)) {
            return 0;
        }
        
        // Look for /Type /Pages and /Count value
        if (preg_match('/\/Type\s*\/Pages.*?\/Count\s+(\d+)/s', $content, $matches)) {
            return intval($matches[1]);
        }
        
        // Fallback: count /Page objects (not /Pages)
        preg_match_all('/\/Type\s*\/Page(?!s)\b/i', $content, $matches);
        return count($matches[0]) > 0 ? count($matches[0]) : 0;
    } catch (Exception $e) {
        error_log("PDF page count extraction error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Trigger journal conversion for newly uploaded thesis
 * Runs asynchronously to not block the upload response
 * @param int $thesis_id ID of thesis to convert
 * @param string $file_path Path to thesis file
 * @return void
 */
function trigger_journal_conversion($thesis_id, $file_path) {
    global $conn;
    
    try {
        // Load journal converter
        require_once __DIR__ . '/../ai_includes/journal_converter.php';
        
        // Update status to processing
        $update_stmt = $conn->prepare("UPDATE thesis SET journal_conversion_status = 'processing' WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("i", $thesis_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        error_log("Journal conversion started for thesis ID: $thesis_id");
        
        // Attempt conversion (non-blocking)
        $converter = new JournalConverter();
        $result = $converter->convert($thesis_id, $file_path);
        
        if ($result['success']) {
            error_log("Journal conversion completed for thesis ID: $thesis_id");
        } else {
            error_log("Journal conversion failed for thesis ID: $thesis_id - " . ($result['error'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        error_log("Error in trigger_journal_conversion: " . $e->getMessage());
        
        // Update status to failed
        $update_stmt = $conn->prepare("UPDATE thesis SET journal_conversion_status = 'failed' WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("i", $thesis_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
}

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
$document_type = sanitize_input($_POST['document_type'] ?? 'thesis');
$page_count = intval($_POST['page_count'] ?? 0);

// Validate required fields
if (empty($title) || empty($author) || empty($course) || empty($year) || empty($abstract)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate document type
$valid_document_types = ['journal', 'book', 'thesis', 'report'];
if (!in_array($document_type, $valid_document_types)) {
    $document_type = 'thesis';
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
    
    // Extract page count from PDF
    if ($file_ext === 'pdf') {
        $page_count = get_pdf_page_count($full_path);
    }
    
    // Validate page count for journal type (must be 10-20 pages)
    if ($document_type === 'journal') {
        if ($page_count === 0) {
            echo json_encode(['success' => false, 'message' => 'Unable to detect page count for journal type. Please verify the PDF is valid.']);
            exit;
        }
        if ($page_count < 10 || $page_count > 20) {
            echo json_encode(['success' => false, 'message' => 'Journal must be 10-20 pages. Current file has ' . $page_count . ' pages.']);
            exit;
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

    // Insert thesis into database
    $stmt = $conn->prepare("
        INSERT INTO thesis (title, author, course, year, abstract, file_path, file_type, file_size, document_type, page_count, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Bind parameters
    $bind_result = $stmt->bind_param(
        "sssissssiss",
        $title,
        $author,
        $course,
        $year,
        $abstract,
        $file_path,
        $file_type,
        $file_size,
        $document_type,
        $page_count,
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
        
        // TRIGGER JOURNAL CONVERSION (Phase 2)
        // This converts the uploaded thesis to journal format asynchronously
        $full_path = dirname(__DIR__) . '/uploads/thesis_files/' . basename($file_path);
        trigger_journal_conversion($thesis_id, $full_path);
        
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
            'message' => 'Thesis added successfully. Journal conversion started (Phase 2).',
            'thesis_id' => $thesis_id,
            'journal_conversion' => 'processing'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add thesis: ' . $stmt->error]);
    }

$conn->close();
?>
