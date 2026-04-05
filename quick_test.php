<?php
$pdfPath = "C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf";

require_once __DIR__ . '/ai_includes/keyword_analyzer.php';
require_once __DIR__ . '/ai_includes/document_parser.php';

if (file_exists($pdfPath)) {
    echo "PDF found: Design of Web-Based Student Academic Information System.pdf\n";
    $result = DocumentParser::extractText($pdfPath);
    if ($result['success']) {
        $text = DocumentParser::cleanText($result['text']);
        echo "Text extracted: " . strlen($text) . " bytes\n\n";
        
        echo "Calling AI Classification...\n";
        $ai = KeywordAnalyzer::generateAIClassification($text, '', 'Thesis Title');
        
        echo "\n=== RESULTS ===\n";
        echo "Subject: " . ($ai['subject_category'] ?: 'EMPTY') . "\n";
        echo "Method: " . ($ai['research_method'] ?: 'EMPTY') . "\n";
        echo "Complexity: " . ($ai['complexity_level'] ?: 'intermediate') . "\n";
        echo "Keywords: " . (count($ai['keywords'] ?? []) > 0 ? implode(', ', $ai['keywords']) : 'EMPTY') . "\n";
        echo "\nCitations Found: " . count($ai['citations'] ?? []) . "\n";
        if (count($ai['citations'] ?? []) > 0) {
            foreach ($ai['citations'] as $c) {
                echo "  ✓ $c\n";
            }
        } else {
            echo "  (No citations extracted)\n";
        }
        
        if ($ai['error']) {
            echo "\nError: " . $ai['error'] . "\n";
        }
    }
} else {
    echo "PDF not found: $pdfPath\n";
}
?>
