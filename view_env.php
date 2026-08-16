<?php
// Quick check of .env file content
$env_file = '/home/u965322812/domains/citas-smart-archive.com/public_html/../.env';
if (file_exists($env_file)) {
    echo "<pre>";
    echo "File: $env_file\n\n";
    echo file_get_contents($env_file);
    echo "</pre>";
} else {
    echo "File not found: $env_file";
}
?>
