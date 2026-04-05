<?php
/**
 * OPTIONAL: Setup for Database-stored Profile Pictures (BLOB)
 * Use this if you want to store profile pictures directly in the database
 * instead of in the file system
 */

// To switch to database storage, you would:
// 1. Add LONGBLOB column to users table:

$sql = "ALTER TABLE users MODIFY COLUMN profile_picture LONGBLOB;
        ALTER TABLE users ADD COLUMN profile_picture_mime_type VARCHAR(20);
        ALTER TABLE users ADD COLUMN profile_picture_size INT;";

// 2. Then modify client_includes/update_profile.php to:

/*
// Instead of saving to disk:
$profile_picture = file_get_contents($_FILES['profile_picture']['tmp_name']);
$mime_type = $_FILES['profile_picture']['type'];
$size = $_FILES['profile_picture']['size'];

// Store in database:
$stmt = $conn->prepare("UPDATE users SET profile_picture = ?, 
                        profile_picture_mime_type = ?, 
                        profile_picture_size = ? 
                        WHERE id = ?");
$stmt->bind_param("bssi", $profile_picture, $mime_type, $size, $user_id);
$stmt->execute();
*/

// 3. Then display as data URL in my_profile.php:

/*
<?php if (!empty($user['profile_picture'])): ?>
    <img src="data:<?php echo htmlspecialchars($user['profile_picture_mime_type']); ?>;base64,<?php echo base64_encode($user['profile_picture']); ?>" 
         alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
<?php else: ?>
    <i class="fas fa-user"></i>
<?php endif; ?>
*/

// PROS of database storage:
// ✓ Easier to backup (everything in database)
// ✓ No file system permissions issues
// ✓ Works on restrictive hosting environments

// CONS of database storage:
// ✗ Larger database size (images are big)
// ✗ Slower image loading (retrieval from DB)
// ✗ More database server load
// ✗ More expensive backups
// ✗ Poor caching behavior

// ✓✓✓ RECOMMENDED: Keep current file system approach ✓✓✓
// It's faster, more scalable, and works great on Hostinger

echo "Current implementation uses FILE SYSTEM storage.
This is the BEST choice for performance and compatibility!

To deploy to Hostinger:
1. Upload all files to your public_html folder
2. Create 'uploads/profile_pictures/' folder manually (or let PHP create it)
3. Set folder permissions to 755
4. That's it! Works out of the box.";
?>
