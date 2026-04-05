<?php
/**
 * Search Theses Endpoint
 * Performs comprehensive search across Title, Author, and Keywords
 * Returns JSON with matching theses
 */

header('Content-Type: application/json');

require_once '../db_includes/db_connect.php';

// Check if search query is provided
if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['success' => false, 'results' => [], 'message' => 'No search query provided']);
    exit;
}

$search_query = $_GET['q'];
$search_lower = strtolower($search_query);

// Sanitize and prepare search term for SQL
$search_term = '%' . $conn->real_escape_string($search_query) . '%';

// Build comprehensive search query
$query = "SELECT 
    t.id,
    t.title,
    t.author,
    t.course,
    t.year,
    t.abstract,
    t.views,
    tc.keywords,
    tc.subject_category,
    tc.complexity_level,
    tc.research_method
FROM thesis t
LEFT JOIN thesis_classification tc ON t.id = tc.thesis_id
WHERE t.status = 'approved'
AND (
    t.title LIKE ? 
    OR t.author LIKE ? 
    OR t.keywords LIKE ?
    OR tc.keywords LIKE ?
    OR t.course LIKE ?
    OR t.abstract LIKE ?
)
ORDER BY t.created_at DESC
LIMIT 50";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'results' => [], 'message' => 'Database error: ' . $conn->error]);
    exit;
}

// Bind parameters for all search fields
$stmt->bind_param(
    "ssssss",
    $search_term,
    $search_term,
    $search_term,
    $search_term,
    $search_term,
    $search_term
);

$stmt->execute();
$result = $stmt->get_result();
$theses = [];

while ($row = $result->fetch_assoc()) {
    // Parse keywords JSON if available
    $keywords = [];
    
    if (!empty($row['keywords'])) {
        // Try to decode as JSON array (from thesis_classification)
        $decoded = json_decode($row['keywords'], true);
        if (is_array($decoded)) {
            $keywords = array_slice($decoded, 0, 5); // Take first 5 keywords
        } elseif (is_string($row['keywords'])) {
            // Fallback: split by comma if string
            $keywords = array_map('trim', explode(',', $row['keywords']));
            $keywords = array_slice($keywords, 0, 5);
        }
    }
    
    // Highlight matching keywords
    $highlighted_keywords = [];
    foreach ($keywords as $keyword) {
        if (is_string($keyword)) {
            $keyword_text = $keyword;
        } else {
            $keyword_text = isset($keyword['keyword']) ? $keyword['keyword'] : (isset($keyword['text']) ? $keyword['text'] : '');
        }
        
        if ($keyword_text && stripos($keyword_text, $search_query) !== false) {
            $highlighted_keywords[] = [
                'text' => $keyword_text,
                'highlighted' => true
            ];
        } else {
            $highlighted_keywords[] = [
                'text' => $keyword_text,
                'highlighted' => false
            ];
        }
    }
    
    // Truncate abstract for preview
    $abstract_preview = substr($row['abstract'], 0, 300) . (strlen($row['abstract']) > 300 ? '...' : '');
    
    $theses[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'author' => $row['author'],
        'course' => $row['course'],
        'year' => $row['year'],
        'abstract' => $abstract_preview,
        'views' => $row['views'],
        'keywords' => $highlighted_keywords,
        'subject_category' => $row['subject_category'],
        'complexity_level' => $row['complexity_level'],
        'research_method' => $row['research_method']
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'results' => $theses,
    'count' => count($theses),
    'query' => $search_query
]);
?>
