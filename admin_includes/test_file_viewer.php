<?php
/**
 * File Viewer Debugger
 * Test file path and viewer functionality
 */

require_once 'db_includes/db_connect.php';
require_login();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Viewer Debugger - CITAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 2rem; }
        .debug-section { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .debug-section h3 { color: #E67E22; margin-bottom: 1rem; border-bottom: 2px solid #E67E22; padding-bottom: 0.5rem; }
        .success { color: #27AE60; font-weight: bold; }
        .error { color: #E74C3C; font-weight: bold; }
        .warning { color: #F39C12; font-weight: bold; }
        .info { color: #3498DB; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
        .file-info { background: #ecf0f1; padding: 1rem; border-radius: 6px; margin: 0.5rem 0; font-family: monospace; font-size: 0.9rem; }
        button { background-color: #E67E22 !important; }
    </style>
</head>
<body>

<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="color: #E67E22; margin-bottom: 2rem;">
        <i class="fas fa-file-pdf"></i> File Viewer Debugger
    </h1>

    <!-- Check Theses with Files -->
    <div class="debug-section">
        <h3><i class="fas fa-database"></i> Theses with Uploaded Files</h3>
        
        <?php
        $result = $conn->query("SELECT id, title, file_path, file_type, file_size FROM thesis WHERE file_path IS NOT NULL AND file_path != '' LIMIT 10");
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>ID</th><th>Title</th><th>File Path</th><th>Type</th><th>Size</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['title'], 0, 30)) . '...</td>';
                echo '<td><code>' . htmlspecialchars($row['file_path']) . '</code></td>';
                echo '<td>' . htmlspecialchars($row['file_type']) . '</td>';
                echo '<td>' . ($row['file_size'] ? round($row['file_size'] / 1024, 2) . ' KB' : 'N/A') . '</td>';
                echo '<td><button class="btn btn-sm btn-info" onclick="testFile(\'' . htmlspecialchars($row['file_path']) . '\', ' . $row['id'] . '\')">Test</button></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<span class="error"><i class="fas fa-times"></i> No theses with files found</span>';
        }
        ?>
    </div>

    <!-- File Path Checker -->
    <div class="debug-section">
        <h3><i class="fas fa-folder"></i> File Path Analysis</h3>
        
        <form method="POST" style="margin-bottom: 1.5rem;">
            <div class="input-group">
                <input type="text" class="form-control" name="file_path" placeholder="Enter file path (e.g., uploads/thesis_files/thesis_123.pdf)" required>
                <button type="submit" name="check_file" class="btn btn-primary">Check File</button>
            </div>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_file'])) {
            $file_path = $_POST['file_path'];
            $full_path = __DIR__ . '/' . $file_path;
            
            echo '<div class="file-info">';
            echo '<strong>File Path:</strong> ' . htmlspecialchars($file_path) . '<br>';
            echo '<strong>Full Path:</strong> ' . htmlspecialchars($full_path) . '<br>';
            echo '<strong>URL:</strong> ' . htmlspecialchars(rtrim($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost', '/') . '/' . $file_path) . '<br>';
            echo '</div>';
            
            if (file_exists($full_path)) {
                echo '<span class="success"><i class="fas fa-check"></i> FILE EXISTS</span><br>';
                echo 'File Size: ' . filesize($full_path) . ' bytes (' . round(filesize($full_path) / 1024, 2) . ' KB)<br>';
                echo 'Is Readable: ' . (is_readable($full_path) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . '<br>';
                
                // Test with Google Docs Viewer
                $url = rtrim($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost', '/') . '/' . $file_path;
                $google_viewer_url = 'https://docs.google.com/viewer?url=' . urlencode($url) . '&embedded=true';
                
                echo '<div style="margin-top: 1rem;">';
                echo '<button class="btn btn-sm btn-warning" onclick="testViewer(\'' . htmlspecialchars(addslashes($url)) . '\')">Preview with Google Docs</button>';
                echo '</div>';
            } else {
                echo '<span class="error"><i class="fas fa-times"></i> FILE NOT FOUND</span>';
            }
        }
        ?>
    </div>

    <!-- Google Docs Viewer Test -->
    <div class="debug-section">
        <h3><i class="fas fa-eye"></i> Viewer Test</h3>
        <div id="viewerResult" style="margin-bottom: 1rem;"></div>
        <div id="iframeContainer" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
            <iframe id="testIframe" width="100%" height="600" style="border: none;"></iframe>
        </div>
    </div>

    <!-- Direct File Access Test -->
    <div class="debug-section">
        <h3><i class="fas fa-link"></i> Direct File Access</h3>
        
        <?php
        $recent_file = $conn->query("SELECT file_path FROM thesis WHERE file_path IS NOT NULL AND file_path != '' ORDER BY created_at DESC LIMIT 1");
        
        if ($recent_file && $recent_file->num_rows > 0) {
            $file_row = $recent_file->fetch_assoc();
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $server_name = $_SERVER['SERVER_NAME'];
            $base_url = $protocol . $server_name;
            $file_url = $base_url . '/' . $file_row['file_path'];
            $full_path = __DIR__ . '/' . $file_row['file_path'];
            
            echo '<div class="file-info">';
            echo '<strong>File URL:</strong><br>';
            echo '<a href="' . htmlspecialchars($file_url) . '" target="_blank">' . htmlspecialchars($file_url) . '</a><br>';
            echo '<strong>File Path in DB:</strong> ' . htmlspecialchars($file_row['file_path']) . '<br>';
            echo '<strong>Full Server Path:</strong> ' . htmlspecialchars($full_path) . '<br>';
            echo '<strong>File Exists:</strong> ' . (file_exists($full_path) ? '<span class="success">YES</span>' : '<span class="error">NO</span>');
            echo '</div>';
            
            echo '<div style="margin-top: 1rem;">';
            echo '<button class="btn btn-sm btn-info" onclick="testDirectAccess(\'' . htmlspecialchars(addslashes($file_url)) . '\')">Test Direct Access</button>';
            echo '</div>';
            
            echo '<div id="directAccessResult"></div>';
        }
        ?>
    </div>

    <!-- View Thesis Button -->
    <div class="debug-section">
        <h3><i class="fas fa-graduation-cap"></i> View Thesis with PDF</h3>
        
        <?php
        $thesis_with_file = $conn->query("
            SELECT t.id, t.title, t.file_path, t.file_type 
            FROM thesis t 
            WHERE t.file_path IS NOT NULL 
            AND t.file_path != '' 
            AND t.status = 'approved' 
            ORDER BY t.created_at DESC 
            LIMIT 1
        ");
        
        if ($thesis_with_file && $thesis_with_file->num_rows > 0) {
            $thesis_row = $thesis_with_file->fetch_assoc();
            echo '<p>Most recent thesis with file:</p>';
            echo '<strong>' . htmlspecialchars($thesis_row['title']) . '</strong><br>';
            echo '<a href="view_thesis.php?id=' . $thesis_row['id'] . '" class="btn btn-primary mt-2" target="_blank">';
            echo '<i class="fas fa-book"></i> View Thesis Page';
            echo '</a>';
        } else {
            echo '<span class="warning">No approved thesis with uploaded file found</span>';
        }
        ?>
    </div>

</div>

<script>
function testFile(filePath, thesisId) {
    const fullUrl = window.location.origin + '/' + filePath;
    console.log('Testing file:', fullUrl);
    testViewer(fullUrl);
}

function testViewer(fileUrl) {
    const result = document.getElementById('viewerResult');
    result.innerHTML = '<span class="info">Loading...</span>';
    
    const googleViewerUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(fileUrl) + '&embedded=true';
    
    document.getElementById('testIframe').src = googleViewerUrl;
    
    result.innerHTML = '<span class="info"><i class="fas fa-info-circle"></i> Loaded in Google Docs Viewer</span><br>' +
                       '<small>File URL: ' + fileUrl + '</small>';
}

function testDirectAccess(fileUrl) {
    const result = document.getElementById('directAccessResult');
    result.innerHTML = '<span class="info">Testing direct access...</span>';
    
    fetch(fileUrl, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                result.innerHTML = '<span class="success"><i class="fas fa-check"></i> Direct access: SUCCESS</span><br>' +
                                  '<small>Status: ' + response.status + ' ' + response.statusText + '</small>';
            } else {
                result.innerHTML = '<span class="error"><i class="fas fa-times"></i> Direct access: FAILED</span><br>' +
                                  '<small>Status: ' + response.status + ' ' + response.statusText + '</small>';
            }
        })
        .catch(error => {
            result.innerHTML = '<span class="error"><i class="fas fa-times"></i> Direct access: ERROR</span><br>' +
                              '<small>' + error.message + '</small>';
        });
}
</script>

<?php $conn->close(); ?>
</body>
</html>
