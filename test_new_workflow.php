<?php
// Quick test to verify the new workflow
// Uses local database credentials instead of Hostinger

// Hardcode local DB credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';  // No password for local development
$db_name = 'citas_smart_archive';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    // Try Hostinger credentials if local fails
    $db_host_hostinger = 'localhost';
    $db_user_hostinger = 'u965322812_CITAS_Smart';
    $db_pass_hostinger = 'ErLv@g1e*';
    $db_name_hostinger = 'u965322812_thesis_db';
    
    $conn = new mysqli($db_host_hostinger, $db_user_hostinger, $db_pass_hostinger, $db_name_hostinger);
    
    if ($conn->connect_error) {
        die("<h2>❌ Connection Error</h2><p>Local error: " . $conn->connect_error . "</p>");
    }
}

echo "<h2>Thesis Database Status Check</h2>";

// Check most recent thesis
$sql = "SELECT id, title, is_journal_converted, journal_file_path, journal_page_count, created_at 
        FROM thesis 
        ORDER BY id DESC 
        LIMIT 5";

$result = $conn->query($sql);

echo "<h3>Most Recent 5 Theses:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr>";
echo "<th>ID</th>";
echo "<th>Title</th>";
echo "<th>Journal Converted</th>";
echo "<th>Journal File Path</th>";
echo "<th>Journal Pages</th>";
echo "<th>Created</th>";
echo "</tr>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_converted = $row['is_journal_converted'] == 1 ? '✅ YES' : '❌ NO';
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . substr($row['title'], 0, 40) . "..." . "</td>";
        echo "<td>" . $is_converted . "</td>";
        echo "<td>" . (empty($row['journal_file_path']) ? '(empty)' : $row['journal_file_path']) . "</td>";
        echo "<td>" . ($row['journal_page_count'] ?? '(null)') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No theses found</td></tr>";
}

echo "</table>";

echo "<h3>Check the latest thesis for journal conversion:</h3>";
echo "<p>If 'Journal Converted' is ✅ YES and 'Journal File Path' is populated, the new workflow is working!</p>";

$conn->close();
?>
