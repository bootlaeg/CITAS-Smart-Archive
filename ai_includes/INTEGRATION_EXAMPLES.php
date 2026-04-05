<?php
/**
 * Example Integration - How to add AI Classification to existing pages
 * This file contains code snippets you can use to integrate classification into your app
 */

// ============================================
// EXAMPLE 1: Display Classification on Thesis View Page
// ============================================
// Add this to view_thesis.php after fetching thesis data

?>
<!-- In view_thesis.php, add this section after thesis details -->
<?php
$thesisId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($thesisId > 0) {
    // Include the classification display component
    require_once __DIR__ . '/ai_includes/classification_display.php';
}
?>

<?php
// ============================================
// EXAMPLE 2: Show Classification Summary in Listings
// ============================================
// Use this simplified version in browse.php for thesis listings
?>

<div style="margin-top: 10px; font-size: 0.9em;">
    <?php
    // Quick classification summary for each thesis
    require_once __DIR__ . '/ai_includes/thesis_classifier.php';
    
    $thesisId = $row['id']; // Assuming you have $row from database
    
    try {
        $classifier = new ThesisClassifier('phi');
        $classification = $classifier->getClassification($thesisId);
        
        if ($classification) {
            echo '<div style="margin-top: 8px;">';
            
            // Subject badge
            echo '<span style="background: #007bff; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.85em; margin-right: 5px;">'
                . htmlspecialchars($classification['subject_category']) . '</span>';
            
            // Complexity badge
            $complexity_color = [
                'beginner' => '#17a2b8',
                'intermediate' => '#ffc107',
                'advanced' => '#dc3545'
            ][$classification['complexity_level']] ?? '#6c757d';
            
            echo '<span style="background: ' . $complexity_color . '; color: ' 
                . ($classification['complexity_level'] === 'intermediate' ? '#000' : '#fff') 
                . '; padding: 3px 8px; border-radius: 12px; font-size: 0.85em;">'
                . ucfirst($classification['complexity_level']) . '</span>';
            
            echo '</div>';
        }
    } catch (Exception $e) {
        // Silently fail - classification not available yet
    }
    ?>
</div>

<?php
// ============================================
// EXAMPLE 3: Add Classification Button to Admin Panel
// ============================================
// Add this button to admin_includes/admin_view_thesis.php
?>

<button class="btn btn-primary btn-sm" onclick="spawnClassification(<?php echo $thesisId; ?>)">
    <i class="fas fa-magic"></i> AI Analyze
</button>

<div id="classify-status" style="display:none; margin-top: 10px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
    <i class="fas fa-spinner fa-spin"></i> Classification in progress...
</div>

<script>
function spawnClassification(thesisId) {
    const statusDiv = document.getElementById('classify-status');
    statusDiv.style.display = 'block';
    
    fetch('../../ai_includes/classify_thesis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=classify&thesis_id=' + thesisId
    })
    .then(response => response.json())
    .then(data => {
        statusDiv.style.display = 'none';
        
        if (data.success) {
            alert('✓ Classification complete!');
            location.reload();
        } else {
            alert('✗ Error: ' + data.error);
        }
    })
    .catch(error => {
        statusDiv.style.display = 'none';
        alert('✗ Request failed: ' + error);
    });
}
</script>

<?php
// ============================================
// EXAMPLE 4: Filter by Subject in Browse
// ============================================
// Add subject filter to browse.php
?>

<div style="margin-bottom: 20px;">
    <label for="subjectFilter" style="font-weight: bold;">Filter by Subject:</label>
    <select id="subjectFilter" class="form-select" style="width: 300px;">
        <option value="">All Subjects</option>
        <?php
        require_once __DIR__ . '/ai_includes/thesis_classifier.php';
        try {
            $classifier = new ThesisClassifier('phi');
            $subjects = $classifier->getAvailableSubjects();
            foreach ($subjects as $subject) {
                echo '<option value="' . htmlspecialchars($subject) . '">' . htmlspecialchars($subject) . '</option>';
            }
        } catch (Exception $e) {
            echo '<!-- Classification unavailable -->';
        }
        ?>
    </select>
</div>

<script>
document.getElementById('subjectFilter')?.addEventListener('change', function(e) {
    if (e.target.value) {
        fetch('../../ai_includes/classify_thesis.php?action=search_by_subject&category=' + encodeURIComponent(e.target.value))
            .then(r => r.json())
            .then(data => {
                if (data.results) {
                    // Update thesis display with filtered results
                    window.filteredResults = data.results;
                    // Implement your display logic here
                }
            });
    }
});
</script>

<?php
// ============================================
// EXAMPLE 5: Add to Admin Dashboard
// ============================================
// Create a new admin page: admin/classification_dashboard.php
?>

<!DOCTYPE html>
<html>
<head>
    <title>Classification Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Classification Statistics</h1>
    
    <div id="stats" class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Total Classified</h6>
                    <h2 id="totalClassified">-</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <h5>Subject Distribution</h5>
        <div id="subjectChart"></div>
    </div>
</div>

<script>
fetch('../../ai_includes/classify_thesis.php?action=stats')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('totalClassified').textContent = data.stats.total_classified;
            
            // Display subject distribution
            let subjectHtml = '<ul>';
            for (let [subject, count] of Object.entries(data.stats.subjects)) {
                subjectHtml += `<li>${subject}: ${count}</li>`;
            }
            subjectHtml += '</ul>';
            document.getElementById('subjectChart').innerHTML = subjectHtml;
        }
    });
</script>
</body>
</html>

<?php
// ============================================
// EXAMPLE 6: Search Page Integration
// ============================================
// Add to browse.php or create search_by_classification.php
?>

<form method="get" action="" class="card p-3 mb-4">
    <h4>Advanced Search</h4>
    
    <div class="row">
        <div class="col-md-6">
            <label>Research Subject:</label>
            <select name="subject" class="form-select">
                <option value="">All Subjects</option>
                <?php
                require_once __DIR__ . '/ai_includes/thesis_classifier.php';
                $classifier = new ThesisClassifier('phi');
                foreach ($classifier->getAvailableSubjects() as $subject) {
                    $selected = ($_GET['subject'] ?? '') === $subject ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($subject) . "' $selected>" . htmlspecialchars($subject) . "</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="col-md-6">
            <label>Complexity Level:</label>
            <select name="complexity" class="form-select">
                <option value="">All Levels</option>
                <option value="beginner" <?php echo ($_GET['complexity'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                <option value="intermediate" <?php echo ($_GET['complexity'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                <option value="advanced" <?php echo ($_GET['complexity'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
            </select>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary mt-3">Search</button>
</form>

<?php
// Display search results
if (!empty($_GET['subject']) || !empty($_GET['complexity'])) {
    require_once __DIR__ . '/ai_includes/thesis_classifier.php';
    $classifier = new ThesisClassifier('phi');
    
    $results = [];
    if (!empty($_GET['subject'])) {
        $results = $classifier->searchBySubject($_GET['subject'], 20);
    } elseif (!empty($_GET['complexity'])) {
        $results = $classifier->searchByComplexity($_GET['complexity'], 20);
    }
    
    if (!empty($results)) {
        echo '<h5>Found ' . count($results) . ' results</h5>';
        foreach ($results as $thesis) {
            echo '<div class="thesis-result mb-3 p-3 border rounded">';
            echo '<h6>' . htmlspecialchars($thesis['title']) . '</h6>';
            echo '<p class="text-muted">' . htmlspecialchars($thesis['author']) . ' - ' . $thesis['year'] . '</p>';
            echo '</div>';
        }
    }
}
?>

<?php
// ============================================
// EXAMPLE 7: Email Notification on Classification Complete
// ============================================
// Add to async_classifier.php or create a webhook
?>

<?php
// After successful classification in async_classifier.php:

// Send email notification
$admin_email = ADMIN_EMAIL; // Define this in your config
$subject = "Thesis #$thesisId has been automatically classified";
$message = <<<EOT
The thesis has been analyzed:

Title: {$thesis['title']}
Category: {$classification['subject']['category']}
Complexity: {$classification['complexity']['level']}
Research Method: {$classification['research_method']['method']}

View details: http://yoursite.com/ctrws-fix/view_thesis.php?id=$thesisId
EOT;

mail($admin_email, $subject, $message, "From: noreply@yoursite.com");
?>

<?php
// ============================================
// EXAMPLE 8: Batch Classification Admin Tool
// ============================================
// Create admin/batch_classify.php
?>

<h3>Batch Classification Tool</h3>

<div class="card p-4">
    <p>Classify all pending/unclassified theses in the database.</p>
    
    <button class="btn btn-warning btn-lg" onclick="startBatchClassification()">
        <i class="fas fa-tasks"></i> Start Batch Classification
    </button>
    
    <div id="batchStatus" style="display:none; margin-top: 20px;">
        <div class="progress">
            <div id="batchProgress" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%"></div>
        </div>
        <p id="batchMessage" class="mt-2">Starting batch classification...</p>
    </div>
</div>

<script>
function startBatchClassification() {
    const statusDiv = document.getElementById('batchStatus');
    statusDiv.style.display = 'block';
    
    fetch('../../ai_includes/classify_thesis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=classify_batch&limit=50'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('batchMessage').textContent = 
                `✓ Classified ${data.classified_count} theses. Errors: ${data.error_count}`;
        } else {
            document.getElementById('batchMessage').textContent = '✗ Error: ' + data.error;
        }
    });
}
</script>

<?php
// This file is a reference guide - do not execute it directly
// Copy and paste the relevant sections into your actual page files
?>
