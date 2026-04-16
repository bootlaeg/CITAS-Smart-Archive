<?php
/**
 * Test full journal conversion with NeuroGuard thesis
 */

echo "=== JOURNAL CONVERSION TEST ===\n\n";

// Test 1: Database connection
echo "Test 1: Connecting to database...\n";
try {
    require_once 'db_includes/db_connect.php';
    echo "✓ Database connected\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 2: Get thesis record
echo "Test 2: Loading NeuroGuard thesis (ID 67)...\n";
$thesis_id = 67;

$sql = "SELECT id, title, author, abstract, file_path, file_type FROM thesis WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("✗ Prepare failed: " . $conn->error . "\n");
}

$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();
$thesis = $result->fetch_assoc();

if (!$thesis) {
    die("✗ Thesis ID 67 not found! Check your database.\n");
}

echo "✓ Thesis loaded: " . $thesis['title'] . "\n";
echo "  File: " . $thesis['file_path'] . "\n";
echo "  Author: " . $thesis['author'] . "\n";

echo "\n";

// Test 3: Check if file exists
echo "Test 3: Checking file existence...\n";
$file_full_path = __DIR__ . '/' . $thesis['file_path'];
if (file_exists($file_full_path)) {
    $file_size = filesize($file_full_path);
    echo "✓ File exists: " . ($file_size / 1024 / 1024) . " MB\n";
} else {
    echo "⚠ File not found at: " . $thesis['file_path'] . "\n";
    echo "  Full path: " . $file_full_path . "\n";
    die("Cannot proceed without file.\n");
}

echo "\n";

// Test 4: Check if DocumentParser exists
echo "Test 4: Loading document parser...\n";
if (file_exists('ai_includes/document_parser.php')) {
    require_once 'ai_includes/document_parser.php';
    echo "✓ DocumentParser loaded\n";
} else {
    die("✗ DocumentParser not found!\n");
}

echo "\n";

// Test 5: Parse document
echo "Test 5: Parsing PDF document...\n";
try {
    $parser = new DocumentParser();
    $parsed = $parser->parseFile($thesis['file_path']);
    
    if ($parsed['success']) {
        echo "✓ Document parsed successfully\n";
        echo "  Text length: " . strlen($parsed['text']) . " characters\n";
        echo "  Metadata: " . json_encode($parsed['metadata'] ?? []) . "\n";
    } else {
        echo "✗ Parsing failed: " . ($parsed['error'] ?? 'Unknown error') . "\n";
        die("Cannot proceed without parsed text.\n");
    }
} catch (Exception $e) {
    die("✗ Parser exception: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 6: Check if IMRaD analyzer exists
echo "Test 6: Loading IMRaD analyzer...\n";
if (file_exists('ai_includes/imrad_analyzer.php')) {
    require_once 'ai_includes/imrad_analyzer.php';
    echo "✓ IMRaD Analyzer loaded\n";
} else {
    die("✗ IMRaD Analyzer not found!\n");
}

echo "\n";

// Test 7: Analyze structure
echo "Test 7: Analyzing document structure...\n";
try {
    $analyzer = new IMRaDAnalyzer($parsed['text']);
    $analysis = $analyzer->analyze();
    
    if ($analysis['success']) {
        echo "✓ Structure analyzed\n";
        echo "  Sections found: " . $analysis['section_count'] . "\n";
        echo "  Confidence: " . $analysis['confidence'] . "%\n";
        foreach ($analysis['sections'] as $section) {
            echo "    - " . $section['type'] . " (" . strlen($section['content']) . " chars)\n";
        }
    } else {
        echo "⚠ Structure analysis issue\n";
    }
} catch (Exception $e) {
    die("✗ Analyzer exception: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 8: Check if JournalConverter exists
echo "Test 8: Loading JournalConverter...\n";
if (file_exists('ai_includes/journal_converter.php')) {
    require_once 'ai_includes/journal_converter.php';
    echo "✓ JournalConverter loaded\n";
} else {
    die("✗ JournalConverter not found!\n");
}

echo "\n";

// Test 9: Create converter instance
echo "Test 9: Creating converter instance...\n";
try {
    $metadata = [
        'title' => $thesis['title'],
        'author' => $thesis['author'],
        'abstract' => $thesis['abstract'] ?? ''
    ];
    
    $converter = new JournalConverter($thesis_id, $parsed['text'], $metadata, $conn);
    echo "✓ Converter instance created\n";
} catch (Exception $e) {
    die("✗ Failed to create converter: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 10: Run conversion
echo "Test 10: Running journal conversion...\n";
echo "This may take 10-30 seconds depending on API response time...\n";
$start_time = microtime(true);

try {
    $conversion_result = $converter->convert();
    $duration = microtime(true) - $start_time;
    
    echo "\nConversion completed in " . round($duration, 2) . " seconds\n";
    echo "\nResult:\n";
    foreach ($conversion_result as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . "\n";
        } else {
            echo "  $key: " . $value . "\n";
        }
    }
    
    if ($conversion_result['success']) {
        echo "\n✓✓✓ CONVERSION SUCCESSFUL! ✓✓✓\n";
    } else {
        echo "\n✗ Conversion failed\n";
    }
} catch (Exception $e) {
    echo "✗ Conversion exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 11: Check database update
echo "Test 11: Verifying database update...\n";
$verify_sql = "SELECT journal_file_path, is_journal_converted, journal_conversion_status, journal_page_count FROM thesis WHERE id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $thesis_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_row = $verify_result->fetch_assoc();

if ($verify_row) {
    echo "Database values:\n";
    foreach ($verify_row as $key => $value) {
        echo "  $key: " . ($value ?? 'NULL') . "\n";
    }
    
    if ($verify_row['is_journal_converted'] == 1) {
        echo "✓ Database updated correctly\n";
    } else {
        echo "⚠ Database may not have been updated\n";
    }
}

echo "\n";

// Test 12: Check logs
echo "Test 12: Checking logs...\n";
$log_file = 'logs/huggingface_api.log';
if (file_exists($log_file)) {
    echo "✓ Hugging Face log file exists\n";
    $lines = file($log_file);
    echo "  Total entries: " . count($lines) . "\n";
    echo "  Recent entries:\n";
    $recent = array_slice($lines, -10);
    foreach ($recent as $line) {
        echo "    " . trim($line) . "\n";
    }
} else {
    echo "⚠ No log file yet\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
<?php
/**
 * Test full journal conversion with NeuroGuard thesis
 */

echo "=== JOURNAL CONVERSION TEST ===\n\n";

// Test 1: Database connection
echo "Test 1: Connecting to database...\n";
try {
    require_once 'db_includes/db_connect.php';
    echo "✓ Database connected\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 2: Get thesis record
echo "Test 2: Loading NeuroGuard thesis (ID 67)...\n";
$thesis_id = 67;

$sql = "SELECT id, title, author, abstract, file_path, file_type FROM thesis WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("✗ Prepare failed: " . $conn->error . "\n");
}

$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();
$thesis = $result->fetch_assoc();

if (!$thesis) {
    die("✗ Thesis ID 67 not found! Check your database.\n");
}

echo "✓ Thesis loaded: " . $thesis['title'] . "\n";
echo "  File: " . $thesis['file_path'] . "\n";
echo "  Author: " . $thesis['author'] . "\n";

echo "\n";

// Test 3: Check if file exists
echo "Test 3: Checking file existence...\n";
$file_full_path = __DIR__ . '/' . $thesis['file_path'];
if (file_exists($file_full_path)) {
    $file_size = filesize($file_full_path);
    echo "✓ File exists: " . ($file_size / 1024 / 1024) . " MB\n";
} else {
    echo "⚠ File not found at: " . $thesis['file_path'] . "\n";
    echo "  Full path: " . $file_full_path . "\n";
    die("Cannot proceed without file.\n");
}

echo "\n";

// Test 4: Check if DocumentParser exists
echo "Test 4: Loading document parser...\n";
if (file_exists('ai_includes/document_parser.php')) {
    require_once 'ai_includes/document_parser.php';
    echo "✓ DocumentParser loaded\n";
} else {
    die("✗ DocumentParser not found!\n");
}

echo "\n";

// Test 5: Parse document
echo "Test 5: Parsing PDF document...\n";
try {
    $parser = new DocumentParser();
    $parsed = $parser->parseFile($thesis['file_path']);
    
    if ($parsed['success']) {
        echo "✓ Document parsed successfully\n";
        echo "  Text length: " . strlen($parsed['text']) . " characters\n";
        echo "  Metadata: " . json_encode($parsed['metadata'] ?? []) . "\n";
    } else {
        echo "✗ Parsing failed: " . ($parsed['error'] ?? 'Unknown error') . "\n";
        die("Cannot proceed without parsed text.\n");
    }
} catch (Exception $e) {
    die("✗ Parser exception: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 6: Check if IMRaD analyzer exists
echo "Test 6: Loading IMRaD analyzer...\n";
if (file_exists('ai_includes/imrad_analyzer.php')) {
    require_once 'ai_includes/imrad_analyzer.php';
    echo "✓ IMRaD Analyzer loaded\n";
} else {
    die("✗ IMRaD Analyzer not found!\n");
}

echo "\n";

// Test 7: Analyze structure
echo "Test 7: Analyzing document structure...\n";
try {
    $analyzer = new IMRaDAnalyzer($parsed['text']);
    $analysis = $analyzer->analyze();
    
    if ($analysis['success']) {
        echo "✓ Structure analyzed\n";
        echo "  Sections found: " . $analysis['section_count'] . "\n";
        echo "  Confidence: " . $analysis['confidence'] . "%\n";
        foreach ($analysis['sections'] as $section) {
            echo "    - " . $section['type'] . " (" . strlen($section['content']) . " chars)\n";
        }
    } else {
        echo "⚠ Structure analysis issue\n";
    }
} catch (Exception $e) {
    die("✗ Analyzer exception: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 8: Check if JournalConverter exists
echo "Test 8: Loading JournalConverter...\n";
if (file_exists('ai_includes/journal_converter.php')) {
    require_once 'ai_includes/journal_converter.php';
    echo "✓ JournalConverter loaded\n";
} else {
    die("✗ JournalConverter not found!\n");
}

echo "\n";

// Test 9: Create converter instance
echo "Test 9: Creating converter instance...\n";
try {
    $metadata = [
        'title' => $thesis['title'],
        'author' => $thesis['author'],
        'abstract' => $thesis['abstract'] ?? ''
    ];
    
    $converter = new JournalConverter($thesis_id, $parsed['text'], $metadata, $conn);
    echo "✓ Converter instance created\n";
} catch (Exception $e) {
    die("✗ Failed to create converter: " . $e->getMessage() . "\n");
}

echo "\n";

// Test 10: Run conversion
echo "Test 10: Running journal conversion...\n";
echo "This may take 10-30 seconds depending on API response time...\n";
$start_time = microtime(true);

try {
    $conversion_result = $converter->convert();
    $duration = microtime(true) - $start_time;
    
    echo "\nConversion completed in " . round($duration, 2) . " seconds\n";
    echo "\nResult:\n";
    foreach ($conversion_result as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . "\n";
        } else {
            echo "  $key: " . $value . "\n";
        }
    }
    
    if ($conversion_result['success']) {
        echo "\n✓✓✓ CONVERSION SUCCESSFUL! ✓✓✓\n";
    } else {
        echo "\n✗ Conversion failed\n";
    }
} catch (Exception $e) {
    echo "✗ Conversion exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 11: Check database update
echo "Test 11: Verifying database update...\n";
$verify_sql = "SELECT journal_file_path, is_journal_converted, journal_conversion_status, journal_page_count FROM thesis WHERE id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $thesis_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_row = $verify_result->fetch_assoc();

if ($verify_row) {
    echo "Database values:\n";
    foreach ($verify_row as $key => $value) {
        echo "  $key: " . ($value ?? 'NULL') . "\n";
    }
    
    if ($verify_row['is_journal_converted'] == 1) {
        echo "✓ Database updated correctly\n";
    } else {
        echo "⚠ Database may not have been updated\n";
    }
}

echo "\n";

// Test 12: Check logs
echo "Test 12: Checking logs...\n";
$log_file = 'logs/huggingface_api.log';
if (file_exists($log_file)) {
    echo "✓ Hugging Face log file exists\n";
    $lines = file($log_file);
    echo "  Total entries: " . count($lines) . "\n";
    echo "  Recent entries:\n";
    $recent = array_slice($lines, -10);
    foreach ($recent as $line) {
        echo "    " . trim($line) . "\n";
    }
} else {
    echo "⚠ No log file yet\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
