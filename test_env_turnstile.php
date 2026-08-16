<?php
header('Content-Type: text/plain');

echo "=== MANUAL .ENV PARSING TEST ===\n";

$env_site_key = '0x4AAAAAADR4GIO_qHahugQh'; // Default Turnstile dummy sitekey
$env_secret_key = '0x4AAAAAADR4GCqGzyeG0upe3DrF61UvgCQ'; // Default Turnstile dummy secret key

$env_path = __DIR__ . '/.env';
echo "Checking .env at: " . $env_path . "\n";
echo "File exists? " . (file_exists($env_path) ? 'YES' : 'NO') . "\n";

if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "Total non-empty lines in .env: " . count($lines) . "\n";
    foreach ($lines as $index => $line) {
        $line = trim($line);
        echo "Line " . ($index + 1) . ": [" . $line . "]\n";
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            echo "   -> Key: '$key', Value: '$value'\n";
            if ($key === 'TURNSTILE_SITE_KEY') {
                $env_site_key = $value;
            } elseif ($key === 'TURNSTILE_SECRET_KEY') {
                $env_secret_key = $value;
            }
        }
    }
}

echo "\n--- PARSED RESULTS ---\n";
echo "Parsed Site Key: " . $env_site_key . "\n";
echo "Parsed Secret Key: " . $env_secret_key . "\n";
echo "=== END TEST ===\n";
