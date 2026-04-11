<?php
$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx';

$zip = new ZipArchive();
if ($zip->open($file) !== true) {
    echo "Failed to open DOCX file\n";
    exit(1);
}

echo "=== FILES IN DOCX ARCHIVE ===\n";
for ($i = 0; $i < $zip->numFiles; $i++) {
    echo $zip->getNameIndex($i) . "\n";
}

echo "\n=== CHECKING FOR APP.XML ===\n";
$app_xml = $zip->getFromName('docProps/app.xml');
if ($app_xml) {
    echo "Found docProps/app.xml\n";
    echo "Content:\n";
    echo substr($app_xml, 0, 1000) . "\n...";
} else {
    echo "docProps/app.xml NOT FOUND\n";
}

echo "\n=== CHECKING FOR CORE.XML ===\n";
$core_xml = $zip->getFromName('docProps/core.xml');
if ($core_xml) {
    echo "Found docProps/core.xml\n";
    echo "Content:\n";
    echo substr($core_xml, 0, 1000) . "\n...";
} else {
    echo "docProps/core.xml NOT FOUND\n";
}

$zip->close();
?>
