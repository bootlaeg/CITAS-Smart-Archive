<?php
// Check file ending for issues
$file = 'c:\xampp\htdocs\GITHUB-SYNC\CITAS-Smart-Archive\ai_includes\DocumentMetadataExtractor.php';
$content = file_get_contents($file);
$end = substr($content, -100);
echo "File ending (last 100 chars):\n";
echo bin2hex($end) . "\n\n";
echo "Visible: " . $end;
?>
