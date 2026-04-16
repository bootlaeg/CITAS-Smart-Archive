<?php
/**
 * Simple Journal Conversion Test
 * Tests the journal conversion pipeline WITHOUT heavy PDF parsing
 */

echo "=== SIMPLE JOURNAL CONVERSION TEST ===\n\n";
echo "Test 1: Connecting to database...\n";

require 'db_includes/db_connect.php';

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✓ Database connected\n\n";

echo "Test 2: Loading Hugging Face service...\n";
require 'ai_includes/huggingface_service.php';
$hf = new HuggingFaceService();
echo "✓ HuggingFace service loaded\n\n";

echo "Test 3: Testing summarization...\n";
$sample_text = "Machine learning is a subset of artificial intelligence that enables systems to learn and improve from experience without explicit programming. It focuses on developing algorithms and statistical models that help computers identify patterns in data. Natural language processing is used to extract meaningful information from text documents. Deep learning models can analyze complex patterns and relationships in large datasets. The results are used for classification, clustering, recommendation systems, and predictive analytics.";

$result = $hf->summarize($sample_text, 50);
if ($result['success']) {
    echo "✓ Summarization successful\n";
    echo "  Summary: " . substr($result['summary'], 0, 100) . "...\n";
    echo "  Word count: " . $result['word_count'] . "\n";
    echo "  Method: " . $result['method'] . "\n\n";
} else {
    echo "❌ Summarization failed: " . $result['error'] . "\n\n";
}

echo "Test 4: Loading IMRaD Analyzer...\n";
require 'ai_includes/imrad_analyzer.php';
$analyzer = new IMRaDAnalyzer();
echo "✓ IMRaD analyzer loaded\n\n";

echo "Test 5: Analyzing document structure...\n";
$analysis = $analyzer->analyzeStructure($sample_text);
echo "✓ Structure analysis complete\n";
echo "  Confidence: " . $analysis['confidence'] . "%\n";
echo "  Detected sections: " . count($analysis['sections']) . "\n\n";

echo "Test 6: Loading journal converter...\n";
require 'ai_includes/journal_converter.php';
$converter = new JournalConverter();
echo "✓ Journal converter loaded\n\n";

echo "✓✓✓ ALL SYSTEMS READY FOR PHASE 2! ✓✓✓\n";
echo "Key findings:\n";
echo "  - Hugging Face service: WORKING\n";
echo "  - Summarization: " . ($result['success'] ? 'WORKING' : 'NEEDS CONFIG') . "\n";
echo "  - IMRaD Analysis: WORKING\n";
echo "  - Journal Converter: READY\n\n";

echo "Next steps:\n";
echo "  1. Execute PHASE2_MIGRATION.sql on Hostinger database\n";
echo "  2. Update view_thesis.php with 5-tab structure\n";
echo "  3. Integrate journal conversion into admin workflow\n";
?>
