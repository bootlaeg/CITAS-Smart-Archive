<?php
/**
 * View Thesis Details Page - Redesigned with Tabbed Interface
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
        }

        /* Header */
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
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Container with Sidebar Layout */
        .container-wrapper {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        /* Sidebar */
        .thesis-sidebar {
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .sidebar-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            margin-bottom: 1.5rem;
        }

        .sidebar-section-title {
            color: var(--primary-orange);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.75rem;
        }

        .sidebar-menu li:last-child {
            margin-bottom: 0;
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
            font-size: 0.95rem;
        }

        .sidebar-menu a:hover {
            background: var(--light-cream);
            color: var(--primary-orange);
        }

        .sidebar-info-item {
            margin-bottom: 1rem;
        }

        .sidebar-info-item:last-child {
            margin-bottom: 0;
        }

        .sidebar-info-label {
            color: var(--primary-orange);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 0.5rem;
        }

        .sidebar-info-value {
            color: var(--text-gray);
            font-size: 1rem;
        }

        .main-content-area {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Thesis Header Section */
        .thesis-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            margin-bottom: 0;
        }

        .thesis-title {
            color: var(--primary-orange);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        /* Tab Navigation - CENTERED */
        .tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            margin-bottom: 0;
            overflow: hidden;
        }

        .tabs-navigation {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            border-bottom: 2px solid var(--border-light);
            background: #FAFAFA;
            gap: 0;
        }

        .tab-button {
            background: none;
            border: none;
            color: var(--text-gray);
            padding: 1.25rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tab-button:hover {
            color: var(--primary-orange);
            background: rgba(230, 126, 34, 0.05);
        }

        .tab-button.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
            background: white;
        }

        .tab-button i {
            font-size: 1.1rem;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Overview Tab */
        .abstract-section {
            margin-bottom: 2rem;
        }

        .abstract-title {
            color: var(--primary-orange);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .abstract-text {
            background: var(--light-cream);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-orange);
            color: var(--text-gray);
            line-height: 1.8;
            text-align: justify;
            margin-bottom: 2rem;
        }

        /* Document Viewer Section */
        .document-section {
            margin-top: 2rem;
        }

        .document-title {
            color: var(--primary-orange);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .access-code-required {
            background: #fffbea;
            border: 2px solid #f39c12;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }

        .access-code-icon {
            font-size: 3rem;
            color: #f39c12;
            margin-bottom: 1rem;
        }

        .access-code-title {
            color: var(--text-dark);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .access-code-message {
            color: var(--text-gray);
            margin-bottom: 1.5rem;
        }

        .btn-request-access {
            background: #f39c12;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-request-access:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
            text-decoration: none;
            color: white;
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

        /* Thesis Info Tab */
        .info-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .info-section {
            background: var(--light-cream);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-orange);
        }

        .info-section-title {
            color: var(--primary-orange);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-section-content {
            color: var(--text-gray);
            line-height: 1.8;
        }

        .metadata-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(230, 126, 34, 0.1);
        }

        .metadata-row:last-child {
            border-bottom: none;
        }

        .metadata-label {
            color: var(--primary-orange);
            font-weight: 600;
        }

        .metadata-value {
            color: var(--text-gray);
        }

        /* Citations Tab */
        .citations-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .citation-item {
            background: var(--light-cream);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-orange);
            color: var(--text-gray);
            line-height: 1.6;
        }

        .citation-number {
            color: var(--primary-orange);
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .citations-scroll-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 1rem;
        }

        .citations-scroll-container::-webkit-scrollbar {
            width: 8px;
        }

        .citations-scroll-container::-webkit-scrollbar-track {
            background: var(--light-cream);
            border-radius: 10px;
        }

        .citations-scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary-orange);
            border-radius: 10px;
        }

        .citations-scroll-container::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Related Thesis Tab */
        .coming-soon {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, var(--light-cream) 0%, rgba(230, 126, 34, 0.05) 100%);
            border-radius: 12px;
            border: 2px dashed var(--primary-orange);
            min-height: 400px;
        }

        .coming-soon-icon {
            font-size: 4rem;
            color: var(--primary-orange);
            margin-bottom: 1rem;
        }

        .coming-soon-title {
            color: var(--primary-orange);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .coming-soon-badge {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .coming-soon-description {
            color: var(--text-gray);
            max-width: 600px;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .coming-soon-note {
            background: white;
            border: 1px solid var(--primary-orange);
            padding: 1rem;
            border-radius: 8px;
            color: var(--text-dark);
            max-width: 600px;
            font-size: 0.9rem;
        }

        /* Document Viewer */
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
        }

        .document-viewer-content iframe {
            width: 100%;
            height: 100%;
            border: none;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .container-wrapper {
                grid-template-columns: 1fr;
            }

            .thesis-sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
                position: static;
            }

            .sidebar-section {
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .thesis-title {
                font-size: 1.5rem;
            }

            .tabs-navigation {
                flex-wrap: wrap;
            }

            .tab-button {
                padding: 1rem 1rem;
                font-size: 0.9rem;
            }

            .tab-button span {
                display: none;
            }

            .info-layout {
                grid-template-columns: 1fr;
            }

            .tab-content {
                padding: 1.5rem;
            }

            .thesis-sidebar {
                grid-template-columns: 1fr;
            }
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
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
            <a href="my_profile.php" class="nav-link">
                <i class="fas fa-user-circle"></i>
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </a>
            <a href="#" class="nav-link" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="#" class="nav-link" onclick="alert('Please login to view full details')"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Main Container with Sidebar -->
<div class="container-wrapper">
    <!-- Left Sidebar -->
    <aside class="thesis-sidebar">
        <!-- Navigation Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">Navigation</h3>
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

        <!-- Quick Summary Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">Quick Summary</h3>
            <div class="sidebar-info-item">
                <span class="sidebar-info-label"><i class="fas fa-user"></i> Author</span>
                <span class="sidebar-info-value"><?php echo htmlspecialchars($thesis['author']); ?></span>
            </div>
            <div class="sidebar-info-item">
                <span class="sidebar-info-label"><i class="fas fa-graduation-cap"></i> Course</span>
                <span class="sidebar-info-value"><?php echo htmlspecialchars($thesis['course']); ?></span>
            </div>
            <div class="sidebar-info-item">
                <span class="sidebar-info-label"><i class="fas fa-calendar"></i> Year</span>
                <span class="sidebar-info-value"><?php echo htmlspecialchars($thesis['year']); ?></span>
            </div>
            <div class="sidebar-info-item">
                <span class="sidebar-info-label"><i class="fas fa-eye"></i> Views</span>
                <span class="sidebar-info-value"><?php echo $thesis['views'] + 1; ?></span>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content-area">
        <!-- Thesis Header -->
        <div class="thesis-header">
            <h1 class="thesis-title">
                <i class="fas fa-file-text"></i>
                <?php echo htmlspecialchars($thesis['title']); ?>
            </h1>
        </div>

        <!-- Tabs Container -->
        <div class="tabs-container">
            <!-- Tab Navigation - CENTERED -->
            <div class="tabs-navigation">
                <button class="tab-button active" onclick="switchTab('overview')">
                    <i class="fas fa-book"></i>
                    <span>Overview</span>
                </button>
                <button class="tab-button" onclick="switchTab('thesis-info')">
                    <i class="fas fa-info-circle"></i>
                    <span>Thesis Info</span>
                </button>
                <button class="tab-button" onclick="switchTab('citations')">
                    <i class="fas fa-quote-left"></i>
                    <span>Citations & References</span>
                </button>
                <button class="tab-button" onclick="switchTab('related')">
                    <i class="fas fa-link"></i>
                    <span>Related Thesis</span>
                </button>
            </div>

            <!-- TAB 1: OVERVIEW -->
            <div id="overview" class="tab-content active">
                <!-- Abstract Section -->
                <div class="abstract-section">
                    <div class="abstract-title">
                        <i class="fas fa-file-alt"></i>Abstract
                    </div>
                    <p class="abstract-text">
                        <?php echo nl2br(htmlspecialchars($thesis['abstract'])); ?>
                    </p>
                </div>

                <!-- Document Section -->
                <div class="document-section">
                    <div class="document-title">
                        <i class="fas fa-file-pdf"></i>Full Thesis Document
                    </div>
                    <div id="accessSection">
                        <?php if (!is_logged_in()): ?>
                            <!-- Not Logged In -->
                            <div class="access-section access-locked">
                                <div class="access-locked-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <h3>Full Access Requires Login</h3>
                                <p>To view complete thesis details and access full content, please log in or create an account.</p>
                                <a href="index.php" class="btn-access btn-access-primary">
                                    <i class="fas fa-sign-in-alt"></i>Login to Continue
                                </a>
                            </div>

                        <?php elseif (!$has_access_code): ?>
                            <!-- Logged In but No Access Code -->
                            <div class="access-section access-locked">
                                <div class="access-locked-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <h3>Access Code Required</h3>
                                <p>You are verified, but you need to request an access code to view the full thesis details. Your request will be reviewed by administrators.</p>
                                <button class="btn-access btn-access-warning" onclick="requestAccessCode(<?php echo $thesis_id; ?>)">
                                    <i class="fas fa-paper-plane"></i>Request Access Code
                                </button>
                            </div>

                        <?php else: ?>
                            <!-- Has Access Code -->
                            <div class="access-section access-granted">
                                <div class="access-granted-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3>Full Access Granted</h3>
                                <p>You have approved access to view the complete thesis details. All content is protected and monitored.</p>
                                <div style="margin-top: 1rem; margin-bottom: 1.5rem;">
                                    <i class="fas fa-shield-alt"></i>
                                    <small>Content is protected. Screenshots and copying are monitored and disabled.</small>
                                </div>
                                
                                <?php if (!empty($thesis['file_path']) && !empty($thesis['file_type'])): ?>
                                    <button class="btn-access btn-access-primary" onclick="openThesisViewer('<?php echo htmlspecialchars($thesis['file_path']); ?>', '<?php echo htmlspecialchars($thesis['file_type']); ?>')">
                                        <i class="fas fa-file-pdf"></i>View Full Thesis Document
                                    </button>
                                <?php else: ?>
                                    <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                                        <i class="fas fa-info-circle"></i>
                                        <small style="color: #856404;">No file uploaded for this thesis yet.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: THESIS INFO -->
            <div id="thesis-info" class="tab-content">
                <div class="info-layout">
                    <!-- Left Column -->
                    <div>
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-user-circle"></i> Author Information
                            </div>
                            <div class="info-section-content">
                                <div class="metadata-row">
                                    <span class="metadata-label">Author:</span>
                                    <span class="metadata-value"><?php echo htmlspecialchars($thesis['author']); ?></span>
                                </div>
                                <div class="metadata-row">
                                    <span class="metadata-label">Course:</span>
                                    <span class="metadata-value"><?php echo htmlspecialchars($thesis['course']); ?></span>
                                </div>
                                <div class="metadata-row">
                                    <span class="metadata-label">Year:</span>
                                    <span class="metadata-value"><?php echo htmlspecialchars($thesis['year']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="info-section" style="margin-top: 1.5rem;">
                            <div class="info-section-title">
                                <i class="fas fa-key"></i> Research Method
                            </div>
                            <div class="info-section-content">
                                <div class="metadata-row">
                                    <span class="metadata-label">Research Type:</span>
                                    <span class="metadata-value"><?php echo !empty($thesis['research_method']) ? htmlspecialchars($thesis['research_method']) : 'Not specified'; ?></span>
                                </div>
                                <div class="metadata-row">
                                    <span class="metadata-label">Complexity Level:</span>
                                    <span class="metadata-value"><?php echo !empty($thesis['complexity_level']) ? strtoupper(htmlspecialchars($thesis['complexity_level'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-brain"></i> Classification
                            </div>
                            <div class="info-section-content">
                                <div class="metadata-row">
                                    <span class="metadata-label">Category:</span>
                                    <span class="metadata-value"><?php echo !empty($thesis['subject_category']) ? htmlspecialchars($thesis['subject_category']) : 'N/A'; ?></span>
                                </div>
                                <div class="metadata-row">
                                    <span class="metadata-label">Keywords:</span>
                                    <span class="metadata-value">
                                        <?php 
                                        if (!empty($thesis['keywords'])) {
                                            $keywords = $thesis['keywords'];
                                            if (strpos($keywords, '[') === 0) {
                                                $kw_array = json_decode($keywords, true);
                                                if (is_array($kw_array)) {
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
                                                echo htmlspecialchars($keywords);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="info-section" style="margin-top: 1.5rem;">
                            <div class="info-section-title">
                                <i class="fas fa-book"></i> Publication Details
                            </div>
                            <div class="info-section-content">
                                <div class="metadata-row">
                                    <span class="metadata-label">Views:</span>
                                    <span class="metadata-value"><?php echo $thesis['views'] + 1; ?></span>
                                </div>
                                <div class="metadata-row">
                                    <span class="metadata-label">Status:</span>
                                    <span class="metadata-value"><?php echo htmlspecialchars(ucfirst($thesis['status'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CITATIONS & REFERENCES -->
            <div id="citations" class="tab-content">
                <h3 style="color: var(--primary-orange); margin-bottom: 1.5rem;">
                    <i class="fas fa-quote-left"></i> References (Raw Citations Found)
                </h3>
                
                <div class="citations-scroll-container">
                    <div class="citations-list">
                        <?php
                        if (!empty($thesis['citations'])) {
                            $citations = $thesis['citations'];
                            if (strpos($citations, '[') === 0) {
                                $citations_array = json_decode($citations, true);
                                if (is_array($citations_array) && !empty($citations_array)) {
                                    foreach ($citations_array as $index => $citation) {
                                        $url = is_array($citation) ? ($citation['url'] ?? $citation) : $citation;
                                        if (!empty($url)) {
                                            $is_url = filter_var($url, FILTER_VALIDATE_URL);
                                            echo '<div class="citation-item">';
                                            echo '<span class="citation-number">[' . ($index + 1) . ']</span>';
                                            if ($is_url) {
                                                echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer" class="link-orange">' . htmlspecialchars($url) . '</a>';
                                            } else {
                                                echo htmlspecialchars($url);
                                            }
                                            echo '</div>';
                                        }
                                    }
                                } else {
                                    echo '<p style="padding: 1rem; color: var(--text-gray);">No references found</p>';
                                }
                            } else {
                                echo '<div class="citation-item"><span class="citation-number">[1]</span>' . htmlspecialchars($citations) . '</div>';
                            }
                        } else {
                            echo '<p style="padding: 1rem; color: var(--text-gray);">No citations available for this thesis.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- TAB 4: RELATED THESIS -->
            <div id="related" class="tab-content">
                <div class="coming-soon">
                    <div class="coming-soon-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="coming-soon-badge">Coming Soon</div>
                    <h3 class="coming-soon-title">Related Thesis Feature</h3>
                    <p class="coming-soon-description">
                        We're working on a smart recommendation engine that will automatically suggest similar theses based on keywords, research methodology, and academic focus areas.
                    </p>
                    <div class="coming-soon-note">
                        <strong><i class="fas fa-info-circle"></i> Future Feature:</strong><br><br>
                        This section is reserved for future development. Once implemented, Related Thesis will display academically similar works based on:<br>
                        • Matching keywords<br>
                        • Similar research methodologies<br>
                        • Related academic categories<br>
                        • Comparable complexity levels<br><br>
                        This intelligent matching system will help researchers discover complementary studies and build upon existing research in their field.
                    </div>
                </div>
            </div>
        </div>
    </div>
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
// Set up PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// Tab Switching Function
function switchTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Add active class to clicked button
    event.target.closest('.tab-button').classList.add('active');
}

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
    
    const thesisId = '<?php echo $thesis_id; ?>';
    const secureUrl = window.location.origin + '/serve_thesis_file.php?file=' + encodeURIComponent(filePath) + '&thesis_id=' + thesisId;
    
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    if (fileType === 'pdf') {
        loadPdfViewer(secureUrl, container);
    } else {
        loadDocViewer(secureUrl, container, fileType);
    }
    
    overlay.addEventListener('contextmenu', (e) => e.preventDefault());
    overlay.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && ['p', 's', 'c', 'a', 'x'].includes(e.key.toLowerCase())) {
            e.preventDefault();
        }
    });
}

function loadPdfViewer(fileUrl, container) {
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
        
        window.pdfDoc = pdfDoc;
        window.currentPage = 1;
        window.totalPages = totalPages;
        window.renderPage(1);
    }).catch(function(error) {
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

function closeThesisViewer() {
    const overlay = document.getElementById('documentViewerOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('pdfViewerContainer').innerHTML = '';
        window.pdfDoc = null;
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeThesisViewer();
    }
});
</script>

</body>
</html>
