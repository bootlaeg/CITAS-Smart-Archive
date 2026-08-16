<?php
/**
 * Fix .env file - remove duplicate hf_ prefix
 */

$env_file = '/home/u965322812/domains/citas-smart-archive.com/public_html/../.env';

if (file_exists($env_file)) {
    $content = file_get_contents($env_file);
    
    // Fix the duplicate hf_ prefix
    $content = str_replace('HUGGING_FACE_API_KEY=hf_hf_', 'HUGGING_FACE_API_KEY=hf_', $content);
    
    // Write back
    file_put_contents($env_file, $content);
    
    echo "✅ Fixed .env file\n";
    echo "New content:\n<pre>";
    echo htmlspecialchars(file_get_contents($env_file));
    echo "</pre>";
} else {
    echo "❌ .env file not found";
}
?>
