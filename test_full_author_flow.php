<?php
/**
 * Test Full Author Extraction Flow (Step 1 + Step 2)
 * Simulates the complete extraction process
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';
require_once 'ai_includes/ollama_service.php';
require_once 'db_includes/db_connect.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.pdf';

echo "=== FULL AUTHOR EXTRACTION FLOW TEST ===\n\n";

// ============================================
// STEP 1: Extract from document
// ============================================
echo "STEP 1: Extracting author from document...\n";
echo "-------------------------------------------\n";

$extractResult = DocumentParser::extractText($pdfPath);
if (!$extractResult['success']) {
    echo "ERROR: Failed to extract text\n";
    exit;
}

$fullText = $extractResult['text'];
$step1Author = KeywordAnalyzer::extractAuthorFromText($fullText);

echo "Step 1 Author Extraction Result: " . ($step1Author ?: '(empty)') . "\n";
echo "Length: " . strlen($step1Author ?? '') . " chars\n\n";

// ============================================
// STEP 2: AI Enhancement
// ============================================
echo "STEP 2: AI Enhancement (Enhance with AI)...\n";
echo "-------------------------------------------\n";

// Extract title and abstract for AI
$lines = explode("\n", $fullText);
$title = "How well can Digital Health and Artificial Intelligence (AI) improve patient safety?";
$abstract = "This study examines the use of digital health technologies in improving patient safety and reducing medical errors.";

// Call AI classification with author hint
$classification = KeywordAnalyzer::generateAIClassification(
    $fullText,
    $abstract,
    $title,
    'mistral'
);

echo "Step 2 Results:\n";
echo "  Subject Category: " . ($classification['subject_category'] ?? 'N/A') . "\n";
echo "  Research Method: " . ($classification['research_method'] ?? 'N/A') . "\n";
echo "  Complexity Level: " . ($classification['complexity_level'] ?? 'N/A') . "\n";
echo "  Author (AI): " . ($classification['author'] ?? '(empty)') . "\n";
echo "  Error: " . ($classification['error'] ?? '(none)') . "\n\n";

// ============================================
// FINAL RESULT
// ============================================
echo "=== FINAL AUTHOR ===\n";
$finalAuthor = $classification['author'] ?? $step1Author ?? '';
echo "Final Author: " . ($finalAuthor ?: '(empty)') . "\n";

if ($finalAuthor === 'Chou Kue') {
    echo "\n✅ SUCCESS: Author extraction is working correctly!\n";
} else {
    echo "\n⚠️ WARNING: Expected 'Chou Kue' but got: '$finalAuthor'\n";
}
?>
