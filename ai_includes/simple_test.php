<?php
// === ULTRA SIMPLE TEST ===
// If this works, server responds. If blank, there's a fatal PHP error.

echo json_encode([
    'success' => true,
    'message' => 'Server is responding',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>
