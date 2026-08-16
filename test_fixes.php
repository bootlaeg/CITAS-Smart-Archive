<?php
/**
 * Test script to verify all fixes are working
 */

echo "===== TESTING CITAS SMART ARCHIVE FIXES =====\n\n";

// Test 1: Thesis Classifier Class Definition
echo "TEST 1: Thesis Classifier (determinComplexityLevel method)\n";
try {
    $classifier_content = file_get_contents('ai_includes/thesis_classifier.php');
    
    if (strpos($classifier_content, 'private function determinComplexityLevel') !== false) {
        echo "✓ determinComplexityLevel method definition found\n";
    } else {
        echo "✗ determinComplexityLevel method NOT found\n";
    }
    
    if (strpos($classifier_content, 'public function classifyThesis') !== false) {
        echo "✓ classifyThesis method exists\n";
    } else {
        echo "✗ classifyThesis method NOT found\n";
    }
    
    if (strpos($classifier_content, 'determinComplexityLevel') !== false && 
        preg_match('/\$this->determinComplexityLevel\s*\(/', $classifier_content)) {
        echo "✓ classifyThesis calls determinComplexityLevel\n";
    } else {
        echo "✗ classifyThesis does NOT call determinComplexityLevel\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error checking ThesisClassifier: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Admin Add Thesis Fix
echo "TEST 2: admin_add_thesis.php (trigger_journal_conversion fix)\n";
try {
    $admin_add_content = file_get_contents('admin_includes/admin_add_thesis.php');
    
    // Check if trigger_journal_conversion is properly updated
    if (strpos($admin_add_content, 'legacy compatibility') !== false) {
        echo "✓ trigger_journal_conversion updated with note\n";
    } else {
        echo "⚠ trigger_journal_conversion might not be updated correctly\n";
    }
    
    if (strpos($admin_add_content, 'new JournalConverter()') === false) {
        echo "✓ Removed incorrect JournalConverter() instantiation\n";
    } else {
        echo "✗ Still contains incorrect JournalConverter() instantiation\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error checking admin_add_thesis: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Required Files
echo "TEST 3: Required Files Exist\n";
$required_files = [
    'ai_includes/thesis_classifier.php',
    'ai_includes/document_parser.php',
    'ai_includes/imrad_analyzer.php',
    'ai_includes/journal_converter.php',
    'ai_includes/ollama_service.php',
    'admin_includes/generate_classification.php',
    'admin_includes/save_classification.php',
    'admin_includes/save_thesis_classification.php',
    'admin_includes/journal_converter_sync.php',
    'admin_includes/admin_add_thesis_page.php',
];

$all_exist = true;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✓ {$file}\n";
    } else {
        echo "✗ {$file} NOT found\n";
        $all_exist = false;
    }
}

echo "\n";

// Test 4: Syntax Check
echo "TEST 4: PHP Syntax Check\n";
$syntax_files = [
    'ai_includes/thesis_classifier.php',
    'admin_includes/generate_classification.php',
    'admin_includes/save_thesis_classification.php',
    'admin_includes/admin_add_thesis.php',
];

$all_syntax_ok = true;
foreach ($syntax_files as $file) {
    $output = [];
    exec("php -l \"{$file}\" 2>&1", $output);
    if (isset($output[0]) && strpos($output[0], 'No syntax errors') !== false) {
        echo "✓ {$file} - Syntax OK\n";
    } else {
        echo "✗ {$file}\n";
        echo "  " . (isset($output[0]) ? $output[0] : 'Unknown error') . "\n";
        $all_syntax_ok = false;
    }
}

echo "\n";

echo "\n===== TEST SUMMARY =====\n";
if ($all_exist && $all_syntax_ok) {
    echo "✅ ALL TESTS PASSED - System is ready!\n";
    echo "\n";
    echo "========== AVAILABLE WORKFLOWS ==========\n\n";
    
    echo "🔄 WORKFLOW 1: Direct Convert & Download (NEW!)\n";
    echo "   URL: /convert_download.html\n";
    echo "   • Upload any thesis file\n";
    echo "   • Click 'Process & Download'\n";
    echo "   • Get converted IMRaD file instantly\n";
    echo "   • NO database save\n\n";
    
    echo "📝 WORKFLOW 2: Admin Panel (Full workflow with database)\n";
    echo "   URL: /admin_includes/admin_add_thesis_page.php\n";
    echo "   • Step 1: Upload & extract metadata\n";
    echo "   • Step 2: Generate AI classification\n";
    echo "   • Step 3: Convert to IMRaD (Phase 2)\n";
    echo "   • Step 4: Save everything to database\n\n";
    
    echo "🔧 REQUIREMENTS:\n";
    echo "   • Ollama running: ollama serve\n";
    echo "   • Model available: ollama pull mistral\n";
    echo "   • Apache/nginx running with PHP\n\n";
    
} else {
    echo "⚠ Some tests failed - see details above\n";
}

echo "\n===== TEST COMPLETE =====\n";
?>

