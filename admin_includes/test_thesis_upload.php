<?php
/**
 * Thesis Upload Debugger
 * Test file to debug thesis file upload issues
 */

require_once 'db_includes/db_connect.php';
require_login();
require_admin();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Upload Debugger - CITAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem;
        }
        .debug-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .debug-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-section h3 {
            color: #E67E22;
            margin-bottom: 1rem;
            border-bottom: 2px solid #E67E22;
            padding-bottom: 0.5rem;
        }
        .success { color: #27AE60; font-weight: bold; }
        .error { color: #E74C3C; font-weight: bold; }
        .warning { color: #F39C12; font-weight: bold; }
        .info { color: #3498DB; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .file-info {
            background: #ecf0f1;
            padding: 1rem;
            border-radius: 6px;
            margin: 0.5rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .form-section {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #E67E22;
        }
        button {
            background-color: #E67E22 !important;
        }
    </style>
</head>
<body>

<div class="debug-container">
    <h1 style="color: #E67E22; margin-bottom: 2rem;">
        <i class="fas fa-bug"></i> Thesis Upload Debugger
    </h1>

    <!-- System Information -->
    <div class="debug-section">
        <h3><i class="fas fa-server"></i> System Information</h3>
        
        <div style="margin-bottom: 1rem;">
            <strong>PHP Version:</strong>
            <span class="success"><?php echo phpversion(); ?></span>
        </div>

        <div style="margin-bottom: 1rem;">
            <strong>Current User:</strong>
            <span class="info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </div>

        <div style="margin-bottom: 1rem;">
            <strong>Is Admin:</strong>
            <span class="<?php echo is_admin() ? 'success' : 'error'; ?>">
                <?php echo is_admin() ? 'YES ✓' : 'NO ✗'; ?>
            </span>
        </div>

        <div>
            <strong>Max Upload Size (php.ini):</strong>
            <span class="info"><?php echo ini_get('upload_max_filesize'); ?></span>
        </div>

        <div>
            <strong>Post Max Size (php.ini):</strong>
            <span class="info"><?php echo ini_get('post_max_size'); ?></span>
        </div>
    </div>

    <!-- Directory Checks -->
    <div class="debug-section">
        <h3><i class="fas fa-folder"></i> Directory Checks</h3>
        
        <?php
        $upload_dir = __DIR__ . '/uploads/thesis_files/';
        $parent_dir = __DIR__ . '/uploads/';
        
        echo '<div style="margin-bottom: 1rem;">';
        echo '<strong>Parent Directory (uploads/):</strong><br>';
        echo '<div class="file-info">' . htmlspecialchars($parent_dir) . '</div>';
        
        if (is_dir($parent_dir)) {
            echo '<span class="success"><i class="fas fa-check"></i> EXISTS</span>';
            echo ' | Permissions: ' . decoct(fileperms($parent_dir)) . ' | ';
            echo is_writable($parent_dir) ? '<span class="success">WRITABLE</span>' : '<span class="error">NOT WRITABLE</span>';
        } else {
            echo '<span class="error"><i class="fas fa-times"></i> DOES NOT EXIST</span>';
            echo ' | <button class="btn btn-sm btn-warning" onclick="createDirectory(\'parent\')">Create</button>';
        }
        echo '</div>';

        echo '<div>';
        echo '<strong>Thesis Files Directory (uploads/thesis_files/):</strong><br>';
        echo '<div class="file-info">' . htmlspecialchars($upload_dir) . '</div>';
        
        if (is_dir($upload_dir)) {
            echo '<span class="success"><i class="fas fa-check"></i> EXISTS</span>';
            echo ' | Permissions: ' . decoct(fileperms($upload_dir)) . ' | ';
            echo is_writable($upload_dir) ? '<span class="success">WRITABLE</span>' : '<span class="error">NOT WRITABLE</span>';
        } else {
            echo '<span class="error"><i class="fas fa-times"></i> DOES NOT EXIST</span>';
            echo ' | <button class="btn btn-sm btn-warning" onclick="createDirectory(\'thesis\')">Create</button>';
        }
        echo '</div>';
        ?>
    </div>

    <!-- Database Checks -->
    <div class="debug-section">
        <h3><i class="fas fa-database"></i> Database Checks</h3>
        
        <?php
        // Check if thesis table exists
        $result = $conn->query("SHOW COLUMNS FROM thesis LIKE 'file%'");
        
        echo '<strong>Thesis Table Columns (file_*):</strong><br>';
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>Column</th><th>Type</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr><td>' . htmlspecialchars($row['Field']) . '</td>';
                echo '<td><span class="success">' . htmlspecialchars($row['Type']) . '</span></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<span class="error"><i class="fas fa-times"></i> Columns NOT FOUND</span><br>';
            echo '<small>Run this SQL in phpMyAdmin:</small><br>';
            echo '<code>ALTER TABLE thesis ADD file_path VARCHAR(255) NULL AFTER abstract, ADD file_type ENUM(\'pdf\', \'doc\', \'docx\') NULL AFTER file_path, ADD file_size INT NULL AFTER file_type;</code>';
        }
        ?>
    </div>

    <!-- File Upload Test -->
    <div class="debug-section">
        <h3><i class="fas fa-upload"></i> Test File Upload</h3>
        
        <form method="POST" enctype="multipart/form-data" class="form-section">
            <div class="mb-3">
                <label for="testFile" class="form-label">Select a Test File (PDF, DOC, DOCX):</label>
                <input type="file" class="form-control" id="testFile" name="test_file" accept=".pdf,.doc,.docx" required>
                <small class="text-muted">Max 50MB</small>
            </div>
            <button type="submit" name="test_upload" class="btn btn-primary">
                <i class="fas fa-upload"></i> Test Upload
            </button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_upload'])) {
            echo '<hr><h5>Upload Test Results:</h5>';
            
            if (!isset($_FILES['test_file'])) {
                echo '<span class="error"><i class="fas fa-times"></i> No file in $_FILES</span>';
            } else {
                $file = $_FILES['test_file'];
                
                echo '<div class="file-info">';
                echo '<strong>File Information:</strong><br>';
                echo 'Name: ' . htmlspecialchars($file['name']) . '<br>';
                echo 'Type: ' . htmlspecialchars($file['type']) . '<br>';
                echo 'Size: ' . ($file['size'] / 1024) . ' KB<br>';
                echo 'Tmp Name: ' . htmlspecialchars($file['tmp_name']) . '<br>';
                echo 'Error Code: ' . $file['error'];
                echo '</div>';
                
                // Check error code
                $error_messages = [
                    0 => 'No error',
                    1 => 'File exceeds upload_max_filesize',
                    2 => 'File exceeds MAX_FILE_SIZE',
                    3 => 'File partially uploaded',
                    4 => 'No file uploaded',
                    6 => 'Missing temp directory',
                    7 => 'Cannot write to disk',
                    8 => 'Upload stopped by extension'
                ];
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo '<span class="error"><i class="fas fa-times"></i> Error: ' . $error_messages[$file['error']] . '</span>';
                } else {
                    echo '<span class="success"><i class="fas fa-check"></i> File received OK</span><br>';
                    
                    // Validate extension
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'doc', 'docx'];
                    
                    if (!in_array($ext, $allowed)) {
                        echo '<span class="error"><i class="fas fa-times"></i> Invalid file type: ' . htmlspecialchars($ext) . '</span>';
                    } else {
                        echo '<span class="success"><i class="fas fa-check"></i> Valid file type: ' . htmlspecialchars($ext) . '</span><br>';
                        
                        // Test move
                        $unique_name = 'test_' . time() . '.' . $ext;
                        $destination = $upload_dir . $unique_name;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            echo '<span class="success"><i class="fas fa-check"></i> File moved successfully</span><br>';
                            echo '<small>Location: ' . htmlspecialchars($destination) . '</small><br>';
                            
                            // Cleanup
                            unlink($destination);
                            echo '<span class="info"><i class="fas fa-info-circle"></i> Test file deleted</span>';
                        } else {
                            echo '<span class="error"><i class="fas fa-times"></i> Failed to move file</span><br>';
                            echo '<small>Check directory permissions</small>';
                        }
                    }
                }
            }
        }
        ?>
    </div>

    <!-- Bind Parameter Test -->
    <div class="debug-section">
        <h3><i class="fas fa-code"></i> Database Insert Test</h3>
        
        <form method="POST" class="form-section">
            <div class="mb-3">
                <label for="testTitle" class="form-label">Thesis Title:</label>
                <input type="text" class="form-control" id="testTitle" name="test_title" value="Test Thesis <?php echo date('Y-m-d H:i:s'); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="testAuthor" class="form-label">Author:</label>
                <input type="text" class="form-control" id="testAuthor" name="test_author" value="Test Author" required>
            </div>
            
            <button type="submit" name="test_insert" class="btn btn-primary">
                <i class="fas fa-database"></i> Test Database Insert
            </button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
            echo '<hr><h5>Insert Test Results:</h5>';
            
            $title = 'Test - ' . date('Y-m-d H:i:s');
            $author = 'Debug Tester';
            $course = 'BSIT';
            $year = 2024;
            $abstract = 'This is a test thesis for debugging purposes.';
            $file_path = 'uploads/thesis_files/test.pdf';
            $file_type = 'pdf';
            $file_size = 1024;
            $status = 'pending';
            
            $stmt = $conn->prepare("
                INSERT INTO thesis (title, author, course, year, abstract, file_path, file_type, file_size, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if (!$stmt) {
                echo '<span class="error"><i class="fas fa-times"></i> Prepare Error: ' . $conn->error . '</span>';
            } else {
                echo '<span class="success"><i class="fas fa-check"></i> Statement prepared OK</span><br>';
                
                // Correct type string: s=string(9), i=integer(2)
                // Parameters: title(s), author(s), course(s), year(i), abstract(s), file_path(s), file_type(s), file_size(i), status(s)
                $bind_result = $stmt->bind_param(
                    "sssissssi",
                    $title,
                    $author,
                    $course,
                    $year,
                    $abstract,
                    $file_path,
                    $file_type,
                    $file_size,
                    $status
                );
                
                if (!$bind_result) {
                    echo '<span class="error"><i class="fas fa-times"></i> Bind Error: ' . $stmt->error . '</span>';
                } else {
                    echo '<span class="success"><i class="fas fa-check"></i> Bind parameters OK</span><br>';
                    echo '<small>Type string: "sssissssi" (9 parameters total)</small><br>';
                    
                    if ($stmt->execute()) {
                        echo '<span class="success"><i class="fas fa-check"></i> Execute successful (Test data inserted)</span>';
                        
                        // Delete test data
                        $delete_stmt = $conn->prepare("DELETE FROM thesis WHERE title LIKE 'Test - %' LIMIT 1");
                        $delete_stmt->execute();
                        $delete_stmt->close();
                        echo '<br><span class="info"><i class="fas fa-info-circle"></i> Test data cleaned up</span>';
                    } else {
                        echo '<span class="error"><i class="fas fa-times"></i> Execute Error: ' . $stmt->error . '</span>';
                    }
                }
                
                $stmt->close();
            }
        }
        ?>
    </div>

    <!-- Instructions -->
    <div class="debug-section">
        <h3><i class="fas fa-lightbulb"></i> Troubleshooting Guide</h3>
        
        <ol>
            <li><strong>Check System Information:</strong> Ensure you're logged in as admin and PHP is working</li>
            <li><strong>Create Directories:</strong> Make sure uploads/ and uploads/thesis_files/ exist and are writable</li>
            <li><strong>Check Database:</strong> Verify file_path, file_type, file_size columns exist</li>
            <li><strong>Test Upload:</strong> Use the file upload test to ensure files can be moved</li>
            <li><strong>Test Database:</strong> Verify insert operations work with the bind parameters</li>
        </ol>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="admin.php" class="btn btn-secondary">Back to Admin Panel</a>
    </div>
</div>

<script>
function createDirectory(type) {
    const path = type === 'parent' ? 'uploads' : 'uploads/thesis_files';
    fetch('test_thesis_upload.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'create_dir=' + type
    })
    .then(response => response.text())
    .then(() => {
        alert('Directory creation request sent. Refresh the page.');
        location.reload();
    });
}
</script>

<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_dir'])) {
    $type = $_POST['create_dir'];
    $dir = $type === 'parent' ? __DIR__ . '/uploads/' : __DIR__ . '/uploads/thesis_files/';
    
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Directory created']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
        }
    }
    exit();
}
?>

</body>
</html>
<?php $conn->close(); ?>
