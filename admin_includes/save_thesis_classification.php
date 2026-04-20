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
    $tempJournalPath = $input['temp_journal_path'] ?? null; // ✅ NEW: Get temp journal path
    if ($pageCount !== null) {
        $pageCount = intval($pageCount);
    }

    // Log received file info
    error_log("📄 File info: path=" . ($filePath ? 'YES' : 'NULL') . ", type=$fileType, size=$fileSize");
    if (empty($filePath)) {
        error_log("⚠️ WARNING: No file_path received! File upload may have failed");
    }
    
    // ✅ NEW: Validate temp journal path
    $journalFilePath = null;
    $journalPageCount = 0;
    if ($tempJournalPath) {
        error_log("📄 Temp journal path provided: $tempJournalPath");
        // Validate the temp file exists
        $tempFileFullPath = __DIR__ . '/../' . $tempJournalPath;
        if (!file_exists($tempFileFullPath)) {
            throw new Exception("Temporary journal file not found at: $tempJournalPath");
        }
        error_log("✓ Temp journal file validated: $tempFileFullPath");
    } else {
        error_log("⚠️ WARNING: No temp_journal_path provided! Journal conversion may have been skipped");
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

    // ✅ NEW: Handle journal file if conversion was successful
    if ($tempJournalPath) {
        error_log("Processing journal conversion file...");
        
        $tempFileFullPath = __DIR__ . '/../' . $tempJournalPath;
        
        // Generate permanent filename
        $journalFileName = 'thesis_' . $thesisId . '_journal_' . uniqid() . '.html';
        $journalDirPath = __DIR__ . '/../uploads/thesis_files/';
        $permanentJournalPath = $journalDirPath . $journalFileName;
        
        // Ensure directory exists
        if (!is_dir($journalDirPath)) {
            if (!mkdir($journalDirPath, 0755, true)) {
                throw new Exception("Failed to create journal directory");
            }
            error_log("✓ Created journal directory: $journalDirPath");
        }
        
        // Move temp file to permanent location
        if (!rename($tempFileFullPath, $permanentJournalPath)) {
            throw new Exception("Failed to move journal file from $tempFileFullPath to $permanentJournalPath");
        }
        
        error_log("✓ Journal file moved to: $permanentJournalPath");
        
        // Store relative path for database
        $journalFilePath = 'uploads/thesis_files/' . $journalFileName;
        $journalPageCount = intval($input['journal_page_count'] ?? 0);
        
        // Update thesis record with journal file path and conversion status
        $updateJournalStmt = $conn->prepare("
            UPDATE thesis 
            SET journal_file_path = ?, is_journal_converted = 1, 
                journal_conversion_status = 'completed', journal_page_count = ?,
                journal_converted_at = NOW()
            WHERE id = ?
        ");
        
        $updateJournalStmt->bind_param("sii", $journalFilePath, $journalPageCount, $thesisId);
        
        if (!$updateJournalStmt->execute()) {
            throw new Exception("Failed to update journal file path: " . $updateJournalStmt->error);
        }
        
        $updateJournalStmt->close();
        
        error_log("✓ Updated thesis record with journal file path");
        error_log("  - Journal path: $journalFilePath");
        error_log("  - Is converted: 1");
        error_log("  - Page count: $journalPageCount");
    }

    // Commit transaction
    $conn->commit();
    
    error_log("✅ Thesis and classification saved successfully. ID: $thesisId");
    if ($journalFilePath) {
        error_log("✅ Journal conversion file finalized and stored");
    }
    
    // NOTE: Journal conversion is now triggered BEFORE database save
    // (see admin_add_thesis_page.php convertToIMRaD function)
    // The workflow is: Upload → Classify → Convert → Save
    // This ensures conversion validation before committing to database
    
    echo json_encode([
        'success' => true,
        'thesis_id' => $thesisId,
        'message' => 'Thesis and classification saved successfully. ' . ($journalFilePath ? 'Journal conversion completed.' : 'No journal file provided.'),
        'file_path' => $filePath,
        'journal_file_path' => $journalFilePath,
        'journal_converted' => !empty($journalFilePath)
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
