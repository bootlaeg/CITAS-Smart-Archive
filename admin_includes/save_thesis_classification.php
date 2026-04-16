<?php
/**
 * Save Thesis & Classification
 * Saves both thesis metadata and classification data to database
 */

require_once '../db_includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No input data provided');
    }

    error_log("=== Save Thesis & Classification Request ===");
    error_log("Input data received");

    // Validate required fields
    $requiredFields = ['title', 'author', 'course', 'year', 'abstract'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Extract thesis data
    $title = trim($input['title']);
    $author = trim($input['author']);
    $course = trim($input['course']);
    $year = intval($input['year']);
    $abstract = trim($input['abstract']);
    $status = $input['status'] ?? 'pending';
    $filePath = $input['file_path'] ?? null;
    $fileType = $input['file_type'] ?? 'pdf';
    $fileSize = $input['file_size'] ?? null;
    $documentType = $input['document_type'] ?? 'thesis';
    $pageCount = $input['page_count'] ?? null;
    if ($pageCount !== null) {
        $pageCount = intval($pageCount);
    }

    // Log received file info
    error_log("📄 File info: path=" . ($filePath ? 'YES' : 'NULL') . ", type=$fileType, size=$fileSize");
    if (empty($filePath)) {
        error_log("⚠️ WARNING: No file_path received! File upload may have failed");
    }

    // Extract classification data
    $subjectCategory = $input['subject_category'] ?? '';
    $researchMethod = $input['research_method'] ?? '';
    $complexityLevel = $input['complexity_level'] ?? 'intermediate';
    $keywords = $input['keywords'] ?? [];
    $citations = $input['citations'] ?? [];

    error_log("Thesis title: $title");
    error_log("File path: $filePath");
    error_log("File type: $fileType");
    error_log("Subject category: $subjectCategory");
    error_log("Research method: $researchMethod");
    error_log("Complexity level: $complexityLevel");
    error_log("Keywords count: " . count($keywords));
    error_log("Citations count: " . count($citations));
    error_log("--- PHASE 2: Auto-converting to journal format post-upload ---");

    // Start transaction
    $conn->begin_transaction();

    // Check if this is an existing thesis (has thesis_id in file_path or from input)
    $thesisId = $input['thesis_id'] ?? null;
    
    if ($thesisId && $thesisId !== 'new') {
        // Update existing thesis
        error_log("Updating existing thesis ID: $thesisId");
        
        $updateStmt = $conn->prepare("
            UPDATE thesis 
            SET title = ?, author = ?, course = ?, year = ?, abstract = ?, status = ?
            WHERE id = ?
        ");
        
        $updateStmt->bind_param("sssisis", $title, $author, $course, $year, $abstract, $status, $thesisId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update thesis: " . $updateStmt->error);
        }
        
        $updateStmt->close();
    } else {
        // Insert new thesis
        error_log("Inserting new thesis");
        
        // Use the file_path from the upload, or generate a placeholder if not provided
        if (!$filePath) {
            $uniqueId = uniqid();
            $filePath = "uploads/thesis_files/thesis_" . $uniqueId . ".pdf";
        }
        
        $insertStmt = $conn->prepare("
            INSERT INTO thesis (title, author, course, year, abstract, file_path, file_type, file_size, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->bind_param("sssisssss", $title, $author, $course, $year, $abstract, $filePath, $fileType, $fileSize, $status);
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert thesis: " . $insertStmt->error);
        }
        
        $thesisId = $conn->insert_id;
        $insertStmt->close();
        
        error_log("New thesis inserted with ID: $thesisId");
    }

    // Prepare keywords JSON
    $keywordsJson = json_encode($keywords);
    $citationsJson = json_encode($citations);

    // Save or update classification
    // First check if classification already exists
    $checkStmt = $conn->prepare("SELECT id FROM thesis_classification WHERE thesis_id = ?");
    $checkStmt->bind_param("i", $thesisId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $classificationExists = $result->num_rows > 0;
    $checkStmt->close();

    if ($classificationExists) {
        // Update existing classification
        error_log("Updating existing classification for thesis ID: $thesisId");
        
        $classStmt = $conn->prepare("
            UPDATE thesis_classification 
            SET subject_category = ?, research_method = ?, complexity_level = ?, 
                keywords = ?, citations = ?
            WHERE thesis_id = ?
        ");
        
        $classStmt->bind_param("sssssi", $subjectCategory, $researchMethod, $complexityLevel, 
                              $keywordsJson, $citationsJson, $thesisId);
        
        if (!$classStmt->execute()) {
            throw new Exception("Failed to update classification: " . $classStmt->error);
        }
        
        $classStmt->close();
    } else {
        // Insert new classification
        error_log("Inserting new classification for thesis ID: $thesisId");
        
        $classStmt = $conn->prepare("
            INSERT INTO thesis_classification (thesis_id, subject_category, 
                                             research_method, keywords, citations)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $classStmt->bind_param("issss", $thesisId, $subjectCategory, $researchMethod,
                              $keywordsJson, $citationsJson);
        
        if (!$classStmt->execute()) {
            throw new Exception("Failed to insert classification: " . $classStmt->error);
        }
        
        $classStmt->close();
    }

    // Commit transaction
    $conn->commit();
    
    error_log("✅ Thesis and classification saved successfully. ID: $thesisId");
    
    // NOTE: Journal conversion is now triggered asynchronously from the frontend
    // (see admin_add_thesis_page.php convertThesisToImradAfterSave function)
    // This ensures we have the correct thesis_id before attempting conversion
    
    echo json_encode([
        'success' => true,
        'thesis_id' => $thesisId,
        'message' => 'Thesis and classification saved successfully. Journal conversion will be triggered by frontend.',
        'file_path' => $filePath
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("❌ Error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
