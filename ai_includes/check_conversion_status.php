<?php
/**
 * Check Journal Conversion Status
 * Polls the database to check if conversion is complete
 * Also processes queued conversions synchronously
 */

set_time_limit(120);  // Allow up to 2 minutes for Ollama processing
ini_set('default_socket_timeout', 120);

header('Content-Type: application/json');

try {
    // Get thesis_id from query parameter
    if (!isset($_GET['thesis_id'])) {
        throw new Exception("Missing thesis_id parameter");
    }
    
    $thesis_id = (int)$_GET['thesis_id'];
    
    // Connect to database
    $db_config = [
        'host' => 'localhost',
        'user' => 'u965322812_CITAS_Smart',
        'pass' => 'ErLv@g1e*',
        'name' => 'u965322812_thesis_db'
    ];
    
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Check conversion status
    $query = "SELECT id, title, is_journal_converted, journal_file_path, journal_page_count, journal_conversion_status 
              FROM thesis WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    $stmt->close();
    
    // Check if there are queued items and process ONE synchronously
    $queue_result = $conn->query("SELECT COUNT(*) as count FROM thesis WHERE journal_conversion_status = 'queued'");
    $queue_row = $queue_result->fetch_assoc();
    $queued_count = (int)$queue_row['count'];
    
    if ($queued_count > 0) {
        error_log("[check_conversion_status] Found $queued_count queued items, processing one now");
        
        // Get first queued item
        $item_result = $conn->query("SELECT id, title, author, abstract, year, file_path FROM thesis WHERE journal_conversion_status = 'queued' LIMIT 1");
        if ($item_result && $item = $item_result->fetch_assoc()) {
            $item_thesis_id = $item['id'];
            error_log("[check_conversion_status] Processing thesis $item_thesis_id: " . $item['title']);
            
            try {
                // Mark as processing
                $conn->query("UPDATE thesis SET journal_conversion_status = 'processing' WHERE id = $item_thesis_id");
                
                // Load converter
                require_once __DIR__ . '/document_parser.php';
                require_once __DIR__ . '/journal_converter.php';
                
                // Resolve file path
                $file_path = $item['file_path'];
                if (!file_exists($file_path)) {
                    $file_path = __DIR__ . '/../' . $file_path;
                }
                
                if (!file_exists($file_path)) {
                    throw new Exception("File not found: $file_path");
                }
                
                // Extract text from file
                $document_text = null;
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                
                if ($ext === 'pdf') {
                    $parse_result = DocumentParser::extractText($file_path);
                    if (!$parse_result['success']) {
                        throw new Exception("PDF parsing failed");
                    }
                    $document_text = $parse_result['text'];
                } else {
                    $document_text = file_get_contents($file_path);
                }
                
                if (!$document_text) {
                    throw new Exception("Could not extract text");
                }
                
                // Build metadata
                $metadata = [
                    'title' => $item['title'],
                    'author' => $item['author'],
                    'abstract' => $item['abstract'],
                    'year' => $item['year']
                ];
                
                // Convert
                error_log("[check_conversion_status] Starting Ollama conversion for thesis $item_thesis_id");
                $converter = new JournalConverter($item_thesis_id, $document_text, $metadata, $conn);
                $conv_result = $converter->convert();
                
                if ($conv_result['success']) {
                    error_log("[check_conversion_status] ✅ Conversion successful for thesis $item_thesis_id");
                } else {
                    throw new Exception("Conversion returned false");
                }
                
            } catch (Throwable $e) {
                error_log("[check_conversion_status] ❌ ERROR processing thesis $item_thesis_id: " . $e->getMessage());
                $conn->query("UPDATE thesis SET journal_conversion_status = 'failed' WHERE id = $item_thesis_id");
            }
        }
    }
    
    $conn->close();
    
    if (!$thesis) {
        throw new Exception("Thesis not found: $thesis_id");
    }
    
    // Return status
    $response = [
        'success' => true,
        'thesis_id' => $thesis_id,
        'is_converted' => (bool)$thesis['is_journal_converted'],
        'status' => $thesis['journal_conversion_status'] ?? 'unknown',
        'page_count' => (int)$thesis['journal_page_count'],
        'journal_file_path' => $thesis['journal_file_path'],
        'title' => $thesis['title']
    ];
    
    // If conversion is done, return full success response
    if ($thesis['is_journal_converted']) {
        $response['success'] = true;
        $response['message'] = 'Conversion complete!';
        $response['journal_page_count'] = $thesis['journal_page_count'];
    } else {
        $response['message'] = 'Still converting... please wait';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
