<?php
/**
 * Test the journal_converter_sync.php endpoint
 */

// Create a test POST request
$data = [
    'file_path' => 'uploads/thesis_files/thesis_69e64b599d215_1776700249.pdf',
    'title' => 'Test Thesis',
    'author' => 'Test Author',
    'abstract' => 'This is a test abstract for testing the conversion',
    'year' => 2024
];

echo "=== Testing journal_converter_sync.php Endpoint ===\n\n";
echo "Posting data:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Use curl to test the endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GITHUB-SYNC/CITAS-Smart-Archive/admin_includes/journal_converter_sync.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response:\n";
echo $response . "\n";

if ($error) {
    echo "CURL Error: $error\n";
}

// Try to parse as JSON
if ($http_code == 200) {
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\n=== Parsed Response ===\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        echo "Keys: " . implode(', ', array_keys($json)) . "\n";
        if (!$json['success']) {
            echo "Error: " . ($json['error'] ?? 'none') . "\n";
        }
    } else {
        echo "\nJSON Parse Error: " . json_last_error_msg() . "\n";
    }
}
?>
