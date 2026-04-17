<?php
// Process Journal Conversion Queue
// This script processes all queued conversions
// Should be run periodically via cron every 5 minutes
// Cron: 0,5,10,15,20,25,30,35,40,45,50,55 * * * * /usr/bin/php /home/u965322812/domains/citas-smart-archive.com/public_html/ai_includes/process_queue.php

set_time_limit(300);  // Allow up to 5 minutes per queue run
ini_set('default_socket_timeout', 300);

// Check if called via HTTP
$is_http = isset($_SERVER['HTTP_HOST']);

if ($is_http) {
    header('Content-Type: text/plain');
}

try {
    error_log("[process_queue] Starting queue processor at " . date('Y-m-d H:i:s'));
    
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
    
    // Get all queued conversions
    $query = "SELECT id, title, author, abstract, year FROM thesis WHERE journal_conversion_status = 'queued' LIMIT 5";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $count = 0;
    while ($thesis = $result->fetch_assoc()) {
        $thesis_id = $thesis['id'];
        $count++;
        
        error_log("[process_queue] Processing thesis $thesis_id: " . $thesis['title']);
        
        try {
            // Mark as processing
            $conn->query("UPDATE thesis SET journal_conversion_status = 'processing' WHERE id = $thesis_id");
            
            // Get file path
            $file_result = $conn->query("SELECT file_path FROM thesis WHERE id = $thesis_id");
            if (!$file_result) {
                throw new Exception("Could not get file path");
            }
            
            $file_row = $file_result->fetch_assoc();
            $file_path = $file_row['file_path'];
            
            error_log("[process_queue] File path: $file_path");
            
            // Resolve file path
            if (!file_exists($file_path)) {
                $file_path = __DIR__ . '/../' . $file_path;
            }
            
            if (!file_exists($file_path)) {
                throw new Exception("File not found: $file_path");
            }
            
            // Load converter
            require_once __DIR__ . '/document_parser.php';
            require_once __DIR__ . '/journal_converter.php';
            
            // Extract text from file
            $document_text = null;
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            
            if ($ext === 'pdf') {
                $parse_result = DocumentParser::extractText($file_path);
                if (!$parse_result['success']) {
                    throw new Exception("PDF parsing failed: " . ($parse_result['error'] ?? 'Unknown error'));
                }
                $document_text = $parse_result['text'];
            } else {
                $document_text = file_get_contents($file_path);
            }
            
            if (!$document_text) {
                throw new Exception("Could not extract text from file");
            }
            
            // Build metadata
            $metadata = [
                'title' => $thesis['title'],
                'author' => $thesis['author'],
                'abstract' => $thesis['abstract'],
                'year' => $thesis['year']
            ];
            
            // Create converter and convert
            error_log("[process_queue] Creating converter for thesis $thesis_id");
            
            $converter = new JournalConverter($thesis_id, $document_text, $metadata, $conn);
            
            error_log("[process_queue] Starting conversion for thesis $thesis_id");
            
            $result = $converter->convert();
            
            if ($result['success']) {
                error_log("[process_queue] ✅ Conversion successful for thesis $thesis_id");
                error_log("[process_queue] Pages: " . $result['journal_page_count']);
                error_log("[process_queue] File: " . $result['journal_file_path']);
            } else {
                throw new Exception("Conversion returned false");
            }
            
        } catch (Throwable $e) {
            error_log("[process_queue] ❌ ERROR processing thesis $thesis_id: " . $e->getMessage());
            error_log("[process_queue] Trace: " . $e->getTraceAsString());
            
            // Mark as failed
            $conn->query("UPDATE thesis SET journal_conversion_status = 'failed' WHERE id = $thesis_id");
        }
    }
    
    $conn->close();
    
    error_log("[process_queue] Queue processor completed. Processed $count items at " . date('Y-m-d H:i:s'));
    
    if ($is_http) {
        echo "[process_queue] Completed processing $count items\n";
    }
    
} catch (Exception $e) {
    error_log("[process_queue] FATAL ERROR: " . $e->getMessage());
    error_log("[process_queue] Trace: " . $e->getTraceAsString());
    
    if ($is_http) {
        http_response_code(500);
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

exit;
?>
