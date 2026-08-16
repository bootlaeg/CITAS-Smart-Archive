<?php
// Quick test
require_once 'ai_includes/simple_pdf.php';

$pdf = new PureTextPDF();
$pdf->addPage("CAPSTONE PROJECT TITLE\n\nThis is a test document.\n\nSubmitted by: John Doe\n\nFor the requirement of CITAS Smart Archive System");
file_put_contents('test_document.pdf', $pdf->generate());
echo "PDF created: " . filesize('test_document.pdf') . " bytes\n";