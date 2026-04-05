<?php
/**
 * View Thesis Details Page - Redesigned
 * Citas Smart Archive System
 */

require_once 'db_includes/db_connect.php';

// Check if thesis ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$thesis_id = intval($_GET['id']);

// Build query based on user role - admins can view any thesis status
if (is_admin()) {
    $query = "
        SELECT t.*, tc.subject_category, tc.subject_confidence, tc.keywords, tc.research_method, 
               tc.method_confidence, tc.complexity_level, tc.complexity_confidence, tc.citations
        FROM thesis t
        LEFT JOIN thesis_classification tc ON t.id = tc.thesis_id
        WHERE t.id = ?
    ";
} else {
    // Regular users can only view approved theses
    $query = "
        SELECT t.*, tc.subject_category, tc.subject_confidence, tc.keywords, tc.research_method, 
               tc.method_confidence, tc.complexity_level, tc.complexity_confidence, tc.citations
        FROM thesis t
        LEFT JOIN thesis_classification tc ON t.id = tc.thesis_id
        WHERE t.id = ? AND t.status = 'approved'
    ";
}

// Get thesis details with classification data
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$thesis = $result->fetch_assoc();
$stmt->close();

// Update view count
$update_stmt = $conn->prepare("UPDATE thesis SET views = views + 1 WHERE id = ?");
if (!$update_stmt) {
    die("Database Error: " . $conn->error);
}
$update_stmt->bind_param("i", $thesis_id);
$update_stmt->execute();
$update_stmt->close();

// Check if user has access code for this thesis
$has_access_code = false;
if (is_logged_in()) {
    $access_check = $conn->prepare("SELECT id FROM thesis_access WHERE user_id = ? AND thesis_id = ? AND status = 'approved'");
    
    if ($access_check) {
        $access_check->bind_param("ii", $_SESSION['user_id'], $thesis_id);
        $access_check->execute();
        $has_access_code = $access_check->get_result()->num_rows > 0;
        $access_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Thesis - <?php echo htmlspecialchars($thesis['title']); ?></title>
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-orange: #E67E22;
            --primary-dark: #D35400;
            --secondary-brown: #8B4513;
            --accent-gold: #F39C12;
            --light-cream: #FFF8F0;
            --text-dark: #2C3E50;
            --text-gray: #7F8C8D;
            --border-light: #ECF0F1;
            --hover-orange: #D65911;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background-color: #FAFAFA;
            color: var(--text-dark);
            line-height: 1.6;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            padding: 0.75rem 2rem;
            box-shadow: 0 2px 8px rgba(230, 126, 34, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 2rem;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .logo i {
            font-size: 1.5rem;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 0.5rem 1rem;
            gap: 0.5rem;
        }

        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.95rem;
        }

        .search-bar button {
            background: none;
            border: none;
            color: var(--primary-orange);
            cursor: pointer;
            font-size: 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link.logout {
            border: 1px solid white;
        }

        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 0 2rem;
        }

        .sidebar {
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .sidebar-section h3 {
            color: var(--primary-orange);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.75rem;
        }

        .sidebar-menu a {
            color: var(--text-gray);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-menu a:hover {
            background: var(--light-cream);
            color: var(--primary-orange);
        }

        .sidebar-menu a.active {
            background: var(--light-cream);
            color: var(--primary-orange);
            font-weight: 600;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .thesis-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            border-left: 5px solid var(--primary-orange);
        }

        .thesis-header h1 {
            color: var(--primary-orange);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .thesis-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .thesis-meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .thesis-meta-label {
            color: var(--text-gray);
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .thesis-meta-value {
            color: var(--primary-orange);
            font-size: 1rem;
            font-weight: 600;
        }

        .thesis-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .section-title {
            color: var(--primary-orange);
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-light);
        }

        .abstract-text {
            color: var(--text-gray);
            line-height: 1.8;
            font-size: 1rem;
            text-align: justify;
        }

        .access-section {
            margin-top: 2rem;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .access-locked {
            background: #FFF3CD;
            border: 2px solid #FFC107;
        }

        .access-locked-icon {
            font-size: 3rem;
            color: #FFC107;
            margin-bottom: 1rem;
        }

        .access-locked h3 {
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .access-locked p {
            color: #856404;
            margin-bottom: 1.5rem;
        }

        .access-granted {
            background: #D5F4E6;
            border: 2px solid #27AE60;
        }

        .access-granted-icon {
            font-size: 3rem;
            color: #27AE60;
            margin-bottom: 1rem;
        }

        .access-granted h3 {
            color: #27AE60;
            margin-bottom: 0.5rem;
        }

        .access-granted p {
            color: #27AE60;
            margin-bottom: 1.5rem;
        }

        .btn-access {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .btn-access-primary {
            background: var(--primary-orange);
            color: white;
        }

        .btn-access-primary:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            color: white;
        }

        .btn-access-warning {
            background: #FFC107;
            color: white;
        }

        .btn-access-warning:hover {
            background: #E0A800;
            transform: translateY(-2px);
            color: white;
        }

        /* Protected Document Viewer */
        .document-viewer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            user-select: none;
        }

        .document-viewer-overlay.active {
            display: flex;
        }

        .document-viewer-container {
            width: 95%;
            height: 95%;
            background: white;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            user-select: none;
            -webkit-user-select: none;
        }

        .document-viewer-header {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary-dark);
        }

        .document-viewer-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .document-viewer-close {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .document-viewer-close:hover {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }

        .document-viewer-content {
            flex: 1;
            overflow: auto;
            background: #f5f5f5;
            user-select: none;
            -webkit-user-select: none;
        }

        .document-viewer-content iframe {
            width: 100%;
            height: 100%;
            border: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .document-protection-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
            border-bottom: 2px solid #ffc107;
        }

        @media (max-width: 768px) {
            .container-main {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .thesis-header h1 {
                font-size: 1.5rem;
            }

            .thesis-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .thesis-header,
            .thesis-content {
                padding: 1.5rem;
            }
        }

        .abstract-text,
        .thesis-header h1 {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }

        /* Reference Links Styling */
        a.link-orange {
            color: var(--primary-orange);
            text-decoration: none;
            transition: all 0.3s ease;
            word-break: break-all;
        }

        a.link-orange:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-container">
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <span>Citas Smart Archive</span>
        </div>
        <nav class="nav-links">
            <?php if (is_logged_in()): ?>
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <div class="notification-center" id="notificationCenter" style="position: relative;">
                <a href="#" class="nav-link" onclick="event.preventDefault(); toggleNotificationPanel()" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none; position: absolute; top: 5px; right: 5px; background: #E74C3C; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">0</span>
                </a>
                <div class="notification-dropdown" id="notificationDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ECF0F1; border-radius: 8px; width: 350px; max-height: 400px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000;">
                    <div style="padding: 1rem; border-bottom: 1px solid #ECF0F1; display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0; font-size: 1rem;">Notifications</h4>
                        <button style="background: none; border: none; cursor: pointer; color: #7F8C8D;" onclick="toggleNotificationPanel()">&times;</button>
                    </div>
                    <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                        <p style="padding: 1rem; text-align: center; color: #7F8C8D;">Loading notifications...</p>
                    </div>
                    <div style="padding: 0.75rem; border-top: 1px solid #ECF0F1; display: flex; gap: 0.5rem;">
                        <button onclick="markAllAsRead()" style="flex: 1; padding: 0.5rem; background: #F8F9F9; border: 1px solid #ECF0F1; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Mark as Read</button>
                        <button onclick="clearAllNotifications()" style="flex: 1; padding: 0.5rem; background: #F8F9F9; border: 1px solid #ECF0F1; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Clear All</button>
                    </div>
                </div>
            </div>
            <a href="my_profile.php" class="nav-link">
                <i class="fas fa-user-circle"></i>
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </a>
            <a href="#" class="nav-link logout" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="#" class="nav-link" onclick="alert('Please login to view full details')"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Main Container -->
<div class="container-main">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <h3>Navigation</h3>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <?php if (is_logged_in()): ?>
                <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <?php if (is_admin()): ?>
                <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="sidebar-section">
            <h3>Thesis Info</h3>
            <div class="thesis-meta-item mb-3">
                <span class="thesis-meta-label">Views</span>
                <span class="thesis-meta-value"><?php echo $thesis['views'] + 1; ?></span>
            </div>
            <div class="thesis-meta-item mb-3">
                <span class="thesis-meta-label">Year</span>
                <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['year']); ?></span>
            </div>
            <div class="thesis-meta-item">
                <span class="thesis-meta-label">Course</span>
                <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['course']); ?></span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Thesis Header -->
        <section class="thesis-header">
            <h1><i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($thesis['title']); ?></h1>
            
            <div class="thesis-meta">
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-user me-2"></i>Author</span>
                    <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['author']); ?></span>
                </div>
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-book me-2"></i>Course</span>
                    <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['course']); ?></span>
                </div>
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-calendar me-2"></i>Year</span>
                    <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['year']); ?></span>
                </div>
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-tags me-2"></i>Keywords</span>
                    <span class="thesis-meta-value">
                        <?php 
                        if (!empty($thesis['keywords'])) {
                            $keywords = $thesis['keywords'];
                            // Try to parse as JSON
                            if (strpos($keywords, '[') === 0) {
                                $kw_array = json_decode($keywords, true);
                                if (is_array($kw_array)) {
                                    // Array of keyword objects or strings
                                    $kw_list = [];
                                    foreach ($kw_array as $kw) {
                                        if (is_array($kw) && isset($kw['keyword'])) {
                                            $kw_list[] = htmlspecialchars($kw['keyword']);
                                        } elseif (is_string($kw)) {
                                            $kw_list[] = htmlspecialchars($kw);
                                        }
                                    }
                                    echo !empty($kw_list) ? implode(', ', $kw_list) : 'N/A';
                                } else {
                                    echo htmlspecialchars($keywords);
                                }
                            } else {
                                // Plain text
                                echo htmlspecialchars($keywords);
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                
                <!-- AI Classification Details -->
                <?php if (!empty($thesis['subject_category'])): ?>
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-brain me-2"></i>Subject Category</span>
                    <span class="thesis-meta-value"><?php echo htmlspecialchars($thesis['subject_category']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-microscope me-2"></i>Research Method</span>
                    <span class="thesis-meta-value"><?php echo !empty($thesis['research_method']) ? htmlspecialchars($thesis['research_method']) : 'Not specified'; ?></span>
                </div>
                
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-bar-chart me-2"></i>Complexity Level</span>
                    <span class="thesis-meta-value">
                        <?php 
                        if (!empty($thesis['complexity_level'])) {
                            $level = strtoupper($thesis['complexity_level']);
                            $badge_color = ($level === 'BEGINNER') ? 'success' : (($level === 'INTERMEDIATE') ? 'warning' : 'danger');
                            echo '<span class="badge bg-' . $badge_color . '">' . $level . '</span>';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                
                <!-- Raw References Section -->
                <?php if (!empty($thesis['citations'])): ?>
                <div class="thesis-meta-item">
                    <span class="thesis-meta-label"><i class="fas fa-link me-2"></i>Raw References Found</span>
                    <span class="thesis-meta-value">
                        <?php 
                        $citations = $thesis['citations'];
                        // Try to parse as JSON
                        if (strpos($citations, '[') === 0) {
                            $citations_array = json_decode($citations, true);
                            if (is_array($citations_array) && !empty($citations_array)) {
                                echo '<div style="max-height: 200px; overflow-y: auto;">';
                                echo '<ol style="margin: 0; padding-left: 1.5rem;">';
                                foreach ($citations_array as $index => $citation) {
                                    $url = is_array($citation) ? ($citation['url'] ?? $citation) : $citation;
                                    if (!empty($url)) {
                                        // Make URLs clickable
                                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                                            echo '<li style="margin-bottom: 0.5rem;"><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer" class="link-orange">' . htmlspecialchars($url) . '</a></li>';
                                        } else {
                                            echo '<li style="margin-bottom: 0.5rem;">' . htmlspecialchars($url) . '</li>';
                                        }
                                    }
                                }
                                echo '</ol>';
                                echo '</div>';
                            } else {
                                echo 'No references found';
                            }
                        } else {
                            // Plain text reference
                            echo htmlspecialchars($citations);
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Thesis Content -->
        <section class="thesis-content">
            <h2 class="section-title"><i class="fas fa-file-pdf me-2"></i>Abstract</h2>
            <p class="abstract-text">
                <?php echo nl2br(htmlspecialchars($thesis['abstract'])); ?>
            </p>

            <!-- Access Control Section -->
            <div class="access-section" id="accessSection">
                <?php if (!is_logged_in()): ?>
                    <!-- Not Logged In -->
                    <div class="access-locked">
                        <div class="access-locked-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Full Access Requires Login</h3>
                        <p>To view complete thesis details and access full content, please log in or create an account.</p>
                        <a href="index.php" class="btn-access btn-access-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                        </a>
                    </div>

                <?php elseif (!$has_access_code): ?>
                    <!-- Logged In but No Access Code -->
                    <div class="access-locked">
                        <div class="access-locked-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h3>Access Code Required</h3>
                        <p>You are verified, but you need to request an access code to view the full thesis details. Your request will be reviewed by administrators.</p>
                        <button class="btn-access btn-access-warning" onclick="requestAccessCode(<?php echo $thesis_id; ?>)">
                            <i class="fas fa-paper-plane me-2"></i>Request Access Code
                        </button>
                    </div>

                <?php else: ?>
                    <!-- Has Access Code -->
                    <div class="access-granted">
                        <div class="access-granted-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Full Access Granted</h3>
                        <p>You have approved access to view the complete thesis details. All content is protected and monitored.</p>
                        <div style="margin-top: 1rem; margin-bottom: 1.5rem;">
                            <i class="fas fa-shield-alt me-2"></i>
                            <small>Content is protected. Screenshots and copying are monitored and disabled.</small>
                        </div>
                        
                        <?php if (!empty($thesis['file_path']) && !empty($thesis['file_type'])): ?>
                            <button class="btn-access btn-access-primary" onclick="openThesisViewer('<?php echo htmlspecialchars($thesis['file_path']); ?>', '<?php echo htmlspecialchars($thesis['file_type']); ?>')">
                                <i class="fas fa-file-pdf me-2"></i>View Full Thesis Document
                            </button>
                        <?php else: ?>
                            <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                                <i class="fas fa-info-circle me-2"></i>
                                <small style="color: #856404;">No file uploaded for this thesis yet.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- Document Viewer Overlay -->
<div class="document-viewer-overlay" id="documentViewerOverlay" onclick="if(event.target === this) closeThesisViewer()">
    <div class="document-viewer-container" onclick="event.stopPropagation()">
        <div class="document-viewer-header">
            <h3><i class="fas fa-file-pdf me-2"></i>Thesis Document Viewer</h3>
            <button type="button" class="document-viewer-close" onclick="closeThesisViewer()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="document-protection-warning">
            <i class="fas fa-shield-alt me-2"></i>This document is protected. Screenshot and copy functions are disabled.
        </div>
        <div class="document-viewer-content" id="pdfViewerContainer">
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-right: 1rem;"></i>
                Loading document...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
// Set up PDF.js worker with CORS
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// Request Access Code
function requestAccessCode(thesisId) {
    fetch('client_includes/request_access_code.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'thesis_id=' + thesisId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Error requesting access code'));
}

// Protected Document Viewer using PDF.js
function openThesisViewer(filePath, fileType) {
    const overlay = document.getElementById('documentViewerOverlay');
    const container = document.getElementById('pdfViewerContainer');
    
    // Get thesis ID from the page
    const thesisId = '<?php echo $thesis_id; ?>';
    
    // Use secure file server with thesis_id for access verification
    const secureUrl = window.location.origin + '/ctrws/serve_thesis_file.php?file=' + encodeURIComponent(filePath) + '&thesis_id=' + thesisId;
    
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Check file type
    if (fileType === 'pdf') {
        loadPdfViewer(secureUrl, container);
    } else {
        // For DOC/DOCX, use Google Docs Viewer
        loadDocViewer(secureUrl, container, fileType);
    }
    
    // Disable right-click
    overlay.addEventListener('contextmenu', (e) => e.preventDefault());
    
    // Disable keyboard shortcuts
    overlay.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && ['p', 's', 'c', 'a', 'x'].includes(e.key.toLowerCase())) {
            e.preventDefault();
            alert('Document protection: This action is not allowed');
        }
    });
}

function loadPdfViewer(fileUrl, container) {
    // Load PDF with proper CORS handling
    const pdf = pdfjsLib.getDocument({
        url: fileUrl,
        withCredentials: true
    });
    
    pdf.promise.then(function(pdfDoc) {
        const totalPages = pdfDoc.numPages;
        
        container.innerHTML = `
            <div style="display: flex; flex-direction: column; height: 100%; background: #f5f5f5;">
                <div style="background: #fff; border-bottom: 1px solid #ddd; padding: 1rem; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <div>
                        <button onclick="window.previousPage()" class="btn btn-sm btn-secondary" style="margin-right: 0.5rem;">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <span id="pageInfo" style="margin: 0 1rem;">Page 1 of ${totalPages}</span>
                        <button onclick="window.nextPage()" class="btn btn-sm btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <input type="number" id="pageInput" min="1" max="${totalPages}" value="1" style="width: 60px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" onchange="window.goToPage(this.value)">
                </div>
                <div style="flex: 1; overflow: auto; background: #f5f5f5; display: flex; justify-content: center; align-items: flex-start; padding: 1rem;">
                    <canvas id="pdfCanvas" style="background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 100%;"></canvas>
                </div>
            </div>
        `;
        
        // Store pdf object globally for page navigation
        window.pdfDoc = pdfDoc;
        window.currentPage = 1;
        window.totalPages = totalPages;
        
        window.renderPage(1);
    }).catch(function(error) {
        console.error('PDF Error:', error);
        container.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #e74c3c; padding: 2rem; text-align: center;"><div><strong>Error loading PDF:</strong><br>' + error.message + '</div></div>';
    });
}

window.renderPage = function(pageNum) {
    if (!window.pdfDoc) return;
    
    window.pdfDoc.getPage(pageNum).then(function(page) {
        const canvas = document.getElementById('pdfCanvas');
        if (!canvas) return;
        
        const context = canvas.getContext('2d');
        const viewport = page.getViewport({scale: 1.5});
        
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        const renderContext = {
            canvasContext: context,
            viewport: viewport
        };
        
        page.render(renderContext).promise.then(function() {
            const pageInfo = document.getElementById('pageInfo');
            const pageInput = document.getElementById('pageInput');
            if (pageInfo) pageInfo.textContent = 'Page ' + pageNum + ' of ' + window.totalPages;
            if (pageInput) pageInput.value = pageNum;
            window.currentPage = pageNum;
        });
    });
};

window.previousPage = function() {
    if (window.currentPage > 1) {
        window.currentPage--;
        window.renderPage(window.currentPage);
    }
};

window.nextPage = function() {
    if (window.currentPage < window.totalPages) {
        window.currentPage++;
        window.renderPage(window.currentPage);
    }
};

window.goToPage = function(pageNum) {
    pageNum = parseInt(pageNum);
    if (pageNum >= 1 && pageNum <= window.totalPages) {
        window.renderPage(pageNum);
    }
};

function loadDocViewer(fileUrl, container, fileType) {
    container.innerHTML = `
        <div style="display: flex; flex-direction: column; height: 100%; background: #f5f5f5;">
            <div style="background: #fff; border-bottom: 1px solid #ddd; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <small style="color: #999;">
                    <i class="fas fa-file-word"></i> Viewing ${fileType.toUpperCase()} document...
                </small>
                <small id="docPreviewStatus" style="color: #666;"></small>
            </div>
            <div style="flex: 1; overflow: auto; background: #f5f5f5; display: flex; align-items: center; justify-content: center; position: relative;">
                <iframe 
                    id="docViewer"
                    style="width: 100%; height: 100%; border: none; background: white; display: none;"
                    sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
                </iframe>
                <div id="docViewerLoader" style="text-align: center; color: #666;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: #E67E22;"></i>
                    <p>Loading ${fileType.toUpperCase()} preview...</p>
                    <small style="color: #999; display: block; margin-top: 1rem;">This may take up to 10 seconds</small>
                </div>
            </div>
        </div>
    `;
    
    // Use Google Docs Viewer
    const googleViewerUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(fileUrl) + '&embedded=true';
    
    const iframe = document.getElementById('docViewer');
    const loader = document.getElementById('docViewerLoader');
    const statusText = document.getElementById('docPreviewStatus');
    
    // Set timeout for loading
    let loadTimeout = setTimeout(() => {
        if (iframe.style.display === 'none') {
            showDocViewerFallback(container, fileUrl, fileType);
        }
    }, 8000);
    
    iframe.onload = function() {
        clearTimeout(loadTimeout);
        loader.style.display = 'none';
        iframe.style.display = 'block';
        statusText.textContent = 'Loaded successfully';
    };
    
    iframe.onerror = function() {
        clearTimeout(loadTimeout);
        showDocViewerFallback(container, fileUrl, fileType);
    };
    
    iframe.src = googleViewerUrl;
}

function showDocViewerFallback(container, fileUrl, fileType) {
    container.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; padding: 2rem; text-align: center; background: #fff; flex-direction: column;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #F39C12; margin-bottom: 1rem;"></i>
            <strong style="font-size: 1.25rem; color: #2C3E50; margin-bottom: 0.5rem;">
                Unable to preview ${fileType.toUpperCase()} file in browser
            </strong>
            <p style="color: #7F8C8D; margin-bottom: 1.5rem; max-width: 500px;">
                The preview service is temporarily unavailable. You can download the file and open it with your local application instead.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="${fileUrl}" download style="background: #E67E22; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" 
                   onmouseover="this.style.background='#D65911'" 
                   onmouseout="this.style.background='#E67E22'">
                    <i class="fas fa-download"></i> Download File
                </a>
                <button onclick="location.reload()" style="background: #7F8C8D; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#5D6D7B'"
                    onmouseout="this.style.background='#7F8C8D'">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        </div>
    `;
}

function closeThesisViewer() {
    const overlay = document.getElementById('documentViewerOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('pdfViewerContainer').innerHTML = '';
        window.pdfDoc = null;
    }
}

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeThesisViewer();
    }
});

// Search Functions
function performHeaderSearch() {
    const searchTerm = document.getElementById('headerSearchInput').value;
    if (searchTerm.trim()) {
        window.location.href = 'browse.php?search=' + encodeURIComponent(searchTerm);
    }
}

// Allow Enter key to submit search
document.getElementById('headerSearchInput')?.addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        performHeaderSearch();
    }
});

// ========== NOTIFICATION SYSTEM ==========

// Toggle notification panel
function toggleNotificationPanel() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown && dropdown.style.display === 'none') {
        dropdown.style.display = 'block';
        loadNotifications();
    } else if (dropdown) {
        dropdown.style.display = 'none';
    }
}

// Load and display notifications
function loadNotifications() {
    fetch('client_includes/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationDisplay(data);
            }
        })
        .catch(error => console.log('Error loading notifications:', error));
}

// Update notification display
function updateNotificationDisplay(data) {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    
    if (!badge || !list) return;
    
    // Update badge
    if (data.unread_count > 0) {
        badge.textContent = data.unread_count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
    
    // Update notification list
    if (data.notifications.length > 0) {
        let html = '';
        data.notifications.forEach(notif => {
            const readClass = notif.is_read ? '' : ' style="background: #FFF8F0; border-left: 3px solid #E67E22;"';
            html += `
                <div class="notification-item"${readClass} style="padding: 1rem; border-bottom: 1px solid #ECF0F1; cursor: pointer;" onclick="markNotificationRead(${notif.id})">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #2C3E50; font-size: 0.95rem;">${escapeHtml(notif.title)}</div>
                            <div style="color: #7F8C8D; font-size: 0.85rem; margin-top: 0.25rem;">${escapeHtml(notif.message)}</div>
                            <div style="color: #95A5A6; font-size: 0.75rem; margin-top: 0.5rem;">${notif.time_ago}</div>
                        </div>
                        ${notif.is_read ? '' : '<div style="width: 8px; height: 8px; background: #E67E22; border-radius: 50%; margin-left: 0.5rem; flex-shrink: 0; margin-top: 0.5rem;"></div>'}
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    } else {
        list.innerHTML = '<p style="padding: 1rem; text-align: center; color: #7F8C8D;">No notifications yet</p>';
    }
}

// Mark single notification as read
function markNotificationRead(notificationId) {
    fetch('client_includes/mark_notifications_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_ids[]=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.log('Error marking notification as read:', error));
}

// Mark all notifications as read
function markAllAsRead() {
    fetch('client_includes/mark_notifications_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: ''
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.log('Error marking all as read:', error));
}

// Clear all notifications
function clearAllNotifications() {
    if (!confirm('Are you sure you want to clear all notifications?')) return;
    
    fetch('client_includes/clear_all_notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: ''
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.log('Error clearing notifications:', error));
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Auto-refresh notifications every 10 seconds
setInterval(() => {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown && dropdown.style.display !== 'none') {
        loadNotifications();
    }
}, 10000);

// Initial load when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
});
</script>

</body>
</html>
<?php $conn->close(); ?>