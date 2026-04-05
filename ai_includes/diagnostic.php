<?php
/**
 * Diagnostic Checker - Verifies all required files and extensions
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostic Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        h1 { color: #E67E22; }
        .check { padding: 10px; margin: 10px 0; border-radius: 4px; display: flex; align-items: center; }
        .check.pass { background: #d4edda; border-left: 4px solid #28a745; }
        .check.fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        .check.warn { background: #fff3cd; border-left: 4px solid #ffc107; }
        .check-icon { width: 30px; font-weight: bold; font-size: 18px; margin-right: 10px; }
        .check-content { flex: 1; }
        .check-content strong { display: block; margin-bottom: 5px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 System Diagnostic Check</h1>
        
        <?php
        $checks = [];
        
        // Check PHP version
        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, '7.0.0', '>=') ? 'pass' : 'fail',
            'details' => 'Current: ' . PHP_VERSION . ' (Required: 7.0.0+)'
        ];
        
        // Check required files
        $requiredFiles = [
            'document_parser.php' => __DIR__ . '/document_parser.php',
            'keyword_analyzer.php' => __DIR__ . '/keyword_analyzer.php',
            'extract_keywords_from_upload.php' => __DIR__ . '/extract_keywords_from_upload.php',
            'test_pdf_extraction.php' => __DIR__ . '/test_pdf_extraction.php',
            'db_connect.php' => __DIR__ . '/../db_includes/db_connect.php'
        ];
        
        foreach ($requiredFiles as $name => $path) {
            $checks[] = [
                'name' => 'File: ' . $name,
                'status' => file_exists($path) ? 'pass' : 'fail',
                'details' => file_exists($path) ? '✓ Exists' : '✗ Not found at ' . $path
            ];
        }
        
        // Check PHP extensions
        $extensions = [
            'ZipArchive' => extension_loaded('zip'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'pdo' => extension_loaded('pdo')
        ];
        
        foreach ($extensions as $ext => $loaded) {
            $checks[] = [
                'name' => 'PHP Extension: ' . $ext,
                'status' => $loaded ? 'pass' : 'warn',
                'details' => $loaded ? '✓ Loaded' : '⚠ Not loaded (PDF extraction may be limited)'
            ];
        }
        
        // Check temp directory
        $tempDir = __DIR__ . '/../uploads/temp';
        $tempWritable = is_dir($tempDir) && is_writable($tempDir);
        $checks[] = [
            'name' => 'Temp Directory',
            'status' => $tempWritable ? 'pass' : 'fail',
            'details' => $tempWritable ? '✓ Exists and writable' : '✗ Missing or not writable at ' . $tempDir
        ];
        
        // Check uploads directory
        $uploadsDir = __DIR__ . '/../uploads';
        $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);
        $checks[] = [
            'name' => 'Uploads Directory',
            'status' => $uploadsWritable ? 'pass' : 'fail',
            'details' => $uploadsWritable ? '✓ Exists and writable' : '✗ Missing or not writable'
        ];
        
        // Check command line tools
        $tools = ['pdftotext' => 'PDF extraction', 'catdoc' => 'DOC extraction'];
        foreach ($tools as $tool => $purpose) {
            $exists = trim(shell_exec("where $tool 2>&1")) !== '';
            $checks[] = [
                'name' => 'Command: ' . $tool,
                'status' => $exists ? 'pass' : 'warn',
                'details' => $exists ? '✓ Available (' . $purpose . ')' : '⚠ Not installed (optional)'
            ];
        }
        
        // Check Ollama
        $curlExists = function_exists('curl_init');
        if ($curlExists) {
            $ch = curl_init('http://localhost:11434/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $ollamaOk = $httpCode === 200;
            $checks[] = [
                'name' => 'Ollama Service',
                'status' => $ollamaOk ? 'pass' : 'warn',
                'details' => $ollamaOk ? '✓ Running on localhost:11434' : '⚠ Not responding (AI fallback disabled)'
            ];
        }
        
        // Render checks
        foreach ($checks as $check) {
            $icon = ($check['status'] === 'pass') ? '✓' : (($check['status'] === 'warn') ? '⚠' : '✗');
            echo '<div class="check ' . $check['status'] . '">';
            echo '<div class="check-icon">' . $icon . '</div>';
            echo '<div class="check-content">';
            echo '<strong>' . $check['name'] . '</strong>';
            echo '<span>' . $check['details'] . '</span>';
            echo '</div></div>';
        }
        
        // Summary
        $passes = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));
        $total = count($checks);
        echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #E67E22;">';
        echo '<strong>Summary: ' . $passes . '/' . $total . ' checks passed</strong>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
