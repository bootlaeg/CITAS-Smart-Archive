<?php
/**
 * Classification Display Component
 * Shows AI-generated classification information for theses
 * Include this in thesis view pages
 */

if (!isset($thesisId) || !isset($GLOBALS['mysqli'])) {
    return;
}

require_once __DIR__ . '/thesis_classifier.php';

try {
    $classifier = new ThesisClassifier('phi');
    $classification = $classifier->getClassification($thesisId);
    
    if (!$classification) {
        return;
    }
} catch (Exception $e) {
    error_log("Classification Display Error: " . $e->getMessage());
    return;
}
?>

<div class="classification-panel" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 15px; margin: 15px 0;">
    <h5 style="margin-top: 0; color: #333;">AI Analysis & Classification</h5>
    
    <!-- Subject Classification -->
    <div style="margin-bottom: 15px;">
        <strong>Research Subject:</strong>
        <div style="margin-top: 5px;">
            <span style="display: inline-block; background: #007bff; color: white; padding: 5px 10px; border-radius: 15px; margin-right: 10px;">
                <?php echo htmlspecialchars($classification['subject_category']); ?>
            </span>
            <small style="color: #666;">Confidence: <?php echo round($classification['subject_confidence'], 1); ?>%</small>
        </div>
    </div>
    
    <!-- Keywords -->
    <div style="margin-bottom: 15px;">
        <strong>Key Topics:</strong>
        <div style="margin-top: 5px;">
            <?php if (!empty($classification['keywords'])): ?>
                <?php foreach ($classification['keywords'] as $kw): ?>
                    <span style="display: inline-block; background: #e9ecef; padding: 4px 10px; border-radius: 12px; margin: 3px 5px 3px 0; font-size: 0.9em;">
                        <?php echo htmlspecialchars($kw['keyword'] ?? $kw); ?>
                        <?php if (isset($kw['relevance'])): ?>
                            <small style="color: #666;">(<?php echo $kw['relevance']; ?>%)</small>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                <span style="color: #999;">No keywords extracted</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Research Method -->
    <div style="margin-bottom: 15px;">
        <strong>Research Method:</strong>
        <div style="margin-top: 5px;">
            <span style="display: inline-block; background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; margin-right: 10px;">
                <?php echo htmlspecialchars($classification['research_method']); ?>
            </span>
            <small style="color: #666;">Confidence: <?php echo round($classification['method_confidence'], 1); ?>%</small>
        </div>
    </div>
    
    <!-- Complexity Level -->
    <div style="margin-bottom: 15px;">
        <strong>Complexity Level:</strong>
        <div style="margin-top: 5px;">
            <?php
                $complexity = $classification['complexity_level'];
                $color = 'secondary';
                if ($complexity === 'beginner') $color = '#17a2b8';
                elseif ($complexity === 'intermediate') $color = '#ffc107';
                elseif ($complexity === 'advanced') $color = '#dc3545';
            ?>
            <span style="display: inline-block; background: <?php echo $color; ?>; color: <?php echo ($complexity === 'intermediate' ? '#000' : '#fff'); ?>; padding: 5px 10px; border-radius: 15px; margin-right: 10px;">
                <?php echo ucfirst($complexity); ?>
            </span>
            <small style="color: #666;">Confidence: <?php echo round($classification['complexity_confidence'], 1); ?>%</small>
        </div>
    </div>
    
    <!-- Citations -->
    <?php if (!empty($classification['citations'])): ?>
    <div style="margin-bottom: 15px;">
        <strong>Cited Works:</strong>
        <div style="margin-top: 5px;">
            <ul style="margin: 0; padding-left: 20px; font-size: 0.9em;">
                <?php foreach (array_slice($classification['citations'], 0, 3) as $citation): ?>
                    <li style="margin-bottom: 5px; color: #555;">
                        <?php echo htmlspecialchars($citation['citation'] ?? $citation); ?>
                    </li>
                <?php endforeach; ?>
                <?php if (count($classification['citations']) > 3): ?>
                    <li style="color: #999;">... and <?php echo count($classification['citations']) - 3; ?> more</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Related Theses -->
    <?php if (!empty($classification['related_thesis_ids'])): ?>
    <div style="margin-bottom: 0;">
        <strong>Related Research:</strong>
        <div style="margin-top: 5px;">
            <?php
                $relatedIds = $classification['related_thesis_ids'];
                if (count($relatedIds) > 0) {
                    $placeholders = implode(',', array_fill(0, count($relatedIds), '?'));
                    $query = "SELECT id, title FROM thesis WHERE id IN ($placeholders) LIMIT 3";
                    $stmt = $GLOBALS['mysqli']->prepare($query);
                    $stmt->bind_param(str_repeat('i', count($relatedIds)), ...$relatedIds);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        echo '<ul style="margin: 0; padding-left: 20px; font-size: 0.9em;">';
                        while ($row = $result->fetch_assoc()) {
                            echo '<li style="margin-bottom: 5px;"><a href="view_thesis.php?id=' . $row['id'] . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($row['title']) . '</a></li>';
                        }
                        echo '</ul>';
                    }
                    $stmt->close();
                }
            ?>
        </div>
    </div>
    <?php endif; ?>
    
    <small style="color: #999; display: block; margin-top: 10px;">
        Last analyzed: <?php echo date('M d, Y H:i', strtotime($classification['last_updated'])); ?>
    </small>
</div>

<style>
.classification-panel a {
    transition: color 0.2s ease;
}

.classification-panel a:hover {
    color: #0056b3;
}
</style>
