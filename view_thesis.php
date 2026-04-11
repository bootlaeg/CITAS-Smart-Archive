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
            overflow-x: hidden;
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

        /* Hamburger Menu Button - Only on Mobile */
        .hamburger-menu {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            gap: 0.5rem;
            padding: 0.5rem;
            z-index: 1001;
        }

        .hamburger-menu span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .hamburger-menu.active span:nth-child(1) {
            transform: rotate(45deg) translate(10px, 10px);
        }

        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-menu.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Mobile Navigation Overlay */
        .mobile-nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999;
        }

        .mobile-nav-overlay.active {
            display: block;
        }

        /* Mobile Sidebar Navigation */
        .mobile-nav-menu {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: var(--primary-orange);
            z-index: 1002;
            overflow-y: auto;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            padding-top: 60px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.25);
        }

        .mobile-nav-menu.active {
            transform: translateX(0);
        }

        .mobile-nav-menu .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .mobile-nav-menu .sidebar-menu li {
            margin: 0;
        }

        .mobile-nav-menu .sidebar-menu a {
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .mobile-nav-menu .sidebar-menu a:hover,
        .mobile-nav-menu .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left-color: white;
        }

        /* Mobile User Menu Section */
        .mobile-user-menu {
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .mobile-user-menu .profile-info {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            border-bottom: none;
        }

        .mobile-user-menu .profile-info i {
            font-size: 1.5rem;
        }

        .mobile-user-menu .profile-info span {
            flex: 1;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }

        .mobile-user-menu .logout-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: white;
            width: 100%;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-family: inherit;
        }

        .mobile-user-menu .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .mobile-user-menu .profile-info {
            cursor: pointer;
        }

        .mobile-user-menu .profile-info:hover {
            background: rgba(255, 255, 255, 0.2);
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
            /* Hide desktop navigation on mobile */
            .nav-links {
                display: none;
            }

            /* Show hamburger menu on mobile */
            .hamburger-menu {
                display: flex;
                order: 2;
            }

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

        /* ============== CHATBOT UI STYLES ============== */

        /* Floating Chat Bubble */
        .chatbot-bubble {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            transition: all 0.3s ease;
            border: none;
            color: white;
            font-size: 1.5rem;
        }

        .chatbot-bubble:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(230, 126, 34, 0.4);
        }

        .chatbot-bubble.active {
            opacity: 0;
            pointer-events: none;
        }

        /* Session Panel */
        .session-panel {
            flex: 1;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        /* Chat Panel */
        .chatbot-panel {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 16px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            z-index: 2100;
            animation: slideInRight 0.3s ease;
            border-left: 1px solid var(--border-light);
        }

        .chatbot-panel.open {
            display: flex;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }

        /* Chat Panel Header */
        .chatbot-header {
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary-dark);
        }

        .chatbot-header-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chatbot-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .chatbot-close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* Chat Messages Container */
        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-color: #FAFAFA;
        }

        /* Message Styles */
        .chat-message {
            display: flex;
            margin-bottom: 0.75rem;
            animation: slideInMessage 0.3s ease;
        }

        @keyframes slideInMessage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-message.user {
            justify-content: flex-end;
        }

        .chat-message.bot {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 85%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .message-content.user {
            background: var(--primary-orange);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-content.bot {
            background: var(--light-cream);
            color: var(--text-dark);
            border-bottom-left-radius: 4px;
            border-left: 3px solid var(--primary-orange);
        }

        /* Loading Indicator */
        .chat-message.loading .message-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .loading-dots {
            display: flex;
            gap: 4px;
        }

        .loading-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary-orange);
            animation: bounce 1.4s infinite;
        }

        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                opacity: 0.5;
                transform: translateY(0);
            }
            40% {
                opacity: 1;
                transform: translateY(-8px);
            }
        }

        /* Chat Input Area */
        .chatbot-input-area {
            padding: 1rem;
            border-top: 1px solid var(--border-light);
            display: flex;
            gap: 0.5rem;
            background: white;
        }

        .chatbot-input-field {
            flex: 1;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .chatbot-input-field:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
        }

        .chatbot-send-btn {
            background: var(--primary-orange);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
        }

        .chatbot-send-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .chatbot-send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Access Control Overlay */
        .chatbot-access-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2200;
            padding: 2rem;
            text-align: center;
            border-radius: 12px;
        }

        .access-overlay-icon {
            font-size: 3rem;
            color: var(--primary-orange);
            margin-bottom: 1rem;
        }

        .access-overlay-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .access-overlay-message {
            color: var(--text-gray);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .access-overlay-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-request-access-overlay {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-request-access-overlay:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
        }

        .btn-close-access-overlay {
            background: var(--border-light);
            color: var(--text-gray);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-close-access-overlay:hover {
            background: #d1d5db;
            color: var(--text-dark);
        }

        /* Access Status Messages */
        .access-status-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .access-status-pending {
            background: #FFF8E1;
            color: #F57F17;
            border: 1px solid #FFC107;
        }

        .access-status-denied {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #EF5350;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .chatbot-panel {
                width: 100%;
                right: 0;
            }

            .chatbot-bubble {
                bottom: 16px;
                right: 16px;
                width: 56px;
                height: 56px;
                font-size: 1.25rem;
            }

            .message-content {
                max-width: 90%;
            }

            .chatbot-input-area {
                padding: 0.75rem;
            }

            .chatbot-input-field {
                padding: 0.65rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .chatbot-panel {
                width: 100%;
            }

            .chatbot-header-title {
                font-size: 0.9rem;
            }

            .message-content {
                max-width: 95%;
                padding: 0.65rem 0.85rem;
                font-size: 0.9rem;
            }

            .access-overlay-icon {
                font-size: 2.5rem;
            }

            .access-overlay-title {
                font-size: 1.1rem;
            }
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
        
        <!-- Mobile Hamburger Menu Button -->
        <button class="hamburger-menu" id="hamburgerMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

<!-- Mobile Navigation Menu -->
<nav class="mobile-nav-menu" id="mobileNavMenu">
    <?php if (is_logged_in()): ?>
    <div class="mobile-user-menu">
        <a href="my_profile.php" style="text-decoration: none; color: inherit;">
            <div class="profile-info" style="gap: 1rem;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <?php 
                    // Get user profile picture if logged in
                    $user_pic_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                    if ($user_pic_stmt) {
                        $user_pic_stmt->bind_param("i", $_SESSION['user_id']);
                        $user_pic_stmt->execute();
                        $user_pic_result = $user_pic_stmt->get_result();
                        $user_pic_data = $user_pic_result->fetch_assoc();
                        if (!empty($user_pic_data['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user_pic_data['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary-orange);"></i>
                        <?php endif;
                        $user_pic_stmt->close();
                    }
                    ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </a>
        <button class="logout-btn" onclick="handleLogout(event)">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>
    <?php endif; ?>
    
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
</nav>

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

// Hamburger Menu Handler
const hamburgerMenu = document.getElementById('hamburgerMenu');
const mobileNavOverlay = document.getElementById('mobileNavOverlay');
const mobileNavMenu = document.getElementById('mobileNavMenu');

if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', () => {
        hamburgerMenu.classList.toggle('active');
        mobileNavOverlay.classList.toggle('active');
        mobileNavMenu.classList.toggle('active');
    });

    // Close menu when clicking overlay
    mobileNavOverlay.addEventListener('click', () => {
        hamburgerMenu.classList.remove('active');
        mobileNavOverlay.classList.remove('active');
        mobileNavMenu.classList.remove('active');
    });

    // Close menu when clicking a link
    const mobileNavLinks = mobileNavMenu.querySelectorAll('a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            hamburgerMenu.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
            mobileNavMenu.classList.remove('active');
        });
    });
}
</script>

<!-- Floating Chat Bubble -->
<button class="chatbot-bubble" id="chatbotBubble" title="Open AI Chatbot">
    <i class="fas fa-comments"></i>
</button>

<!-- Chatbot Panel -->
<div class="chatbot-panel" id="chatbotPanel">
    <div class="chatbot-header">
        <h2 class="chatbot-header-title">
            <i class="fas fa-robot"></i>
            Thesis Assistant
        </h2>
        <button class="chatbot-close-btn" id="chatbotCloseBtn" title="Close chatbot">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Access Control Overlay -->
    <div class="chatbot-access-overlay" id="chatbotAccessOverlay">
        <div class="access-overlay-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h3 class="access-overlay-title">Chatbot Access Required</h3>
        <p class="access-overlay-message" id="accessOverlayMessage">
            To use the AI chatbot for thesis analysis, you need to request access first. This helps us maintain security and ensure proper usage.
        </p>
        <div class="access-overlay-buttons">
            <button class="btn-close-access-overlay" id="closeAccessOverlayBtn">
                Close
            </button>
        </div>
    </div>

    <!-- Session Management Panel -->
    <div id="sessionPanel" class="session-panel" style="
        display: none;
        flex: 1;
        overflow-y: auto;
        background: #f8f9fa;
        padding: 0;
    ">
        <div style="padding: 15px;">
            <button id="newChatBtn" style="
                width: 100%;
                padding: 12px;
                background: #E67E22;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin-bottom: 15px;
                transition: background 0.3s;
            " onmouseover="this.style.background='#D35400'" onmouseout="this.style.background='#E67E22'">
                <i class="fas fa-plus"></i>New Chat
            </button>

            <div id="sessionsList" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Sessions will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Quota Status Display -->
    <div id="chatbotQuotaStatus" style="padding: 10px; border-bottom: 1px solid #eee; display: none;"></div>

    <!-- Chat Messages Container -->
    <div class="chatbot-messages" id="chatbotMessages" style="display: none;">
        <div class="chat-message bot">
            <div class="message-content bot">
                <i class="fas fa-smile"></i> Hello! I'm the Thesis Assistant. I can help you with analysis, summaries, and questions about this thesis. How can I assist you today?
            </div>
        </div>
    </div>

    <!-- Chat Input Area -->
    <div class="chatbot-input-area" id="chatbotInputArea" style="display: none;">
        <input 
            type="text" 
            class="chatbot-input-field" 
            id="chatbotInput" 
            placeholder="Ask me anything about this thesis..."
            autocomplete="off"
        >
        <button class="chatbot-send-btn" id="chatbotSendBtn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>

    <!-- Back button (shown during chat) -->
    <div id="chatbackButton" style="
        display: none;
        padding: 10px;
        border-top: 1px solid #eee;
        background: white;
    ">
        <button id="backToSessionsBtn" style="
            width: 100%;
            padding: 8px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        ">
            <i class="fas fa-arrow-left me-2"></i>Back to Sessions
        </button>
    </div>
</div>

<script>
// ============== CHATBOT INITIALIZATION ==============

const chatbotBubble = document.getElementById('chatbotBubble');
const chatbotPanel = document.getElementById('chatbotPanel');
const chatbotCloseBtn = document.getElementById('chatbotCloseBtn');
const chatbotMessages = document.getElementById('chatbotMessages');
const chatbotInputArea = document.getElementById('chatbotInputArea');
const chatbotInput = document.getElementById('chatbotInput');
const chatbotSendBtn = document.getElementById('chatbotSendBtn');
const chatbotAccessOverlay = document.getElementById('chatbotAccessOverlay');
const closeAccessOverlayBtn = document.getElementById('closeAccessOverlayBtn');
const sessionPanel = document.getElementById('sessionPanel');
const sessionsList = document.getElementById('sessionsList');
const newChatBtn = document.getElementById('newChatBtn');
const backToSessionsBtn = document.getElementById('backToSessionsBtn');
const chatbackButton = document.getElementById('chatbackButton');

const thesisId = <?php echo $thesis_id; ?>;
let hasChatbotAccess = false;
let accessCheckInProgress = false;
let currentSessionId = null;
let sessionMessages = [];
const maxSessions = 5;

// Open chatbot panel
chatbotBubble.addEventListener('click', () => {
    chatbotPanel.classList.add('open');
    chatbotBubble.classList.add('active');
    
    // Check access status when opening
    if (!accessCheckInProgress) {
        checkChatbotAccessStatus();
    }
});

// Close chatbot panel
chatbotCloseBtn.addEventListener('click', () => {
    closeChatbotPanel();
});

closeAccessOverlayBtn.addEventListener('click', () => {
    closeChatbotPanel();
});

// New chat button
newChatBtn.addEventListener('click', () => {
    createNewSession();
});

// Back to sessions button
backToSessionsBtn.addEventListener('click', () => {
    showSessionView();
});

// Close panel function
function closeChatbotPanel() {
    chatbotPanel.classList.remove('open');
    chatbotBubble.classList.remove('active');
}

// Send message when button clicked
chatbotSendBtn.addEventListener('click', sendMessage);

// Send message on Enter key
chatbotInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Check chatbot access status
function checkChatbotAccessStatus() {
    if (accessCheckInProgress) return;
    
    accessCheckInProgress = true;
    
    fetch('chatbot_includes/check_chatbot_access.php?thesis_id=' + thesisId)
        .then(response => response.json())
        .then(data => {
            accessCheckInProgress = false;
            
            if (data.has_access) {
                // User has access
                hasChatbotAccess = true;
                enableChatbot();
            } else {
                // No access - show appropriate message
                hasChatbotAccess = false;
                showAccessControl(data.status);
            }
        })
        .catch(error => {
            accessCheckInProgress = false;
            console.error('Error checking chatbot access:', error);
            showAccessControl('error');
        });
}

// Show access control overlay with appropriate message
function showAccessControl(status) {
    chatbotMessages.style.display = 'none';
    chatbotInputArea.style.display = 'none';
    chatbotAccessOverlay.style.display = 'flex';
    
    const messageElement = document.getElementById('accessOverlayMessage');
    
    switch(status) {
        case 'pending':
            messageElement.textContent = 'Your access request is pending approval. Once an administrator approves your access to this thesis, you\'ll be able to use the chatbot. You\'ll also be able to view the full thesis content.';
            break;
        case 'denied':
            messageElement.textContent = 'Your access request was denied. Please contact an administrator if you believe this was a mistake.';
            break;
        case 'no_request':
            messageElement.textContent = 'To view the full thesis and use the AI chatbot, you need to request access to this thesis. Visit the "Browse Thesis" section to request access.';
            break;
        case 'error':
            messageElement.textContent = 'There was an error checking your access status. Please try again later.';
            break;
        default:
            messageElement.textContent = 'You do not have access to this thesis or its chatbot features.';
    }
}

// Enable chatbot functionality
function enableChatbot() {
    chatbotAccessOverlay.style.display = 'none';
    sessionPanel.style.display = 'flex';
    chatbotMessages.style.display = 'none';
    chatbotInputArea.style.display = 'none';
    chatbackButton.style.display = 'none';
    
    // Load and display sessions
    loadAndDisplaySessions();
}

// Show chat view
function showChatView() {
    sessionPanel.style.display = 'none';
    chatbotMessages.style.display = 'flex';
    chatbotInputArea.style.display = 'flex';
    chatbackButton.style.display = 'block';
    chatbotInput.focus();
}

// Show session list view
function showSessionView() {
    sessionPanel.style.display = 'flex';
    chatbotMessages.style.display = 'none';
    chatbotInputArea.style.display = 'none';
    chatbackButton.style.display = 'none';
    loadAndDisplaySessions();
}

// ============== SESSION MANAGEMENT ==============

// Load and display sessions in session panel
async function loadAndDisplaySessions() {
    try {
        const response = await fetch('chatbot_includes/list_sessions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'thesis_id=' + thesisId
        });
        const data = await response.json();
        console.log('List sessions response:', data);
        
        if (data.success) {
            console.log('Sessions loaded:', data.session_count + '/' + data.max_sessions);
            if (data.sessions && data.sessions.length > 0) {
                console.log('First session:', data.sessions[0]);
            }
            renderSessionsList(data);
        } else {
            console.error('Error loading sessions:', data.message);
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        sessionsList.innerHTML = '<p style="padding: 10px; color: #999;">Error loading sessions</p>';
    }
}

// Render sessions list UI
function renderSessionsList(data) {
    sessionsList.innerHTML = '';
    
    if (data.sessions.length === 0) {
        sessionsList.innerHTML = `
            <div style="
                padding: 20px;
                text-align: center;
                color: #999;
            ">
                <p><i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i></p>
                <p>No chat sessions yet</p>
                <p style="font-size: 0.9rem;">Click "New Chat" to start</p>
            </div>
        `;
        return;
    }
    
    data.sessions.forEach(session => {
        console.log('Rendering session:', session);
        const createdDate = new Date(session.created_at).toLocaleString();
        const sessionEl = document.createElement('div');
        sessionEl.style.cssText = `
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        sessionEl.innerHTML = `
            <div style="flex: 1; cursor: pointer; pointer-events: none;">
                <div style="font-weight: 600; color: #2C3E50; margin-bottom: 4px;">
                    ${session.session_name}
                </div>
                <div style="font-size: 0.85rem; color: #666;">
                    <i class="fas fa-message me-1"></i>${session.message_count} messages · ${createdDate}
                </div>
            </div>
            <button onclick="event.stopPropagation(); deleteSessionConfirm(${session.id})" style="
                background: #dc3545;
                color: white;
                border: none;
                padding: 6px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85rem;
                margin-left: 10px;
            " title="Delete session">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        // Click to load session - attach to main element
        sessionEl.addEventListener('click', (e) => {
            console.log('Session clicked, full object:', session);
            console.log('Session ID:', session.id, 'Type:', typeof session.id);
            e.preventDefault();
            loadSession(session.id);
        });
        
        sessionEl.addEventListener('mouseover', () => {
            sessionEl.style.background = '#f0f0f0';
            sessionEl.style.borderColor = '#E67E22';
        });
        sessionEl.addEventListener('mouseout', () => {
            sessionEl.style.background = 'white';
            sessionEl.style.borderColor = '#ddd';
        });
        
        sessionsList.appendChild(sessionEl);
    });
    
    // Show quota status
    const quotaEl = document.createElement('div');
    quotaEl.style.cssText = 'padding: 10px; text-align: center; font-size: 0.85rem; color: #666; margin-top: 10px; border-top: 1px solid #eee;';
    if (data.quota_exceeded) {
        quotaEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.session_count}/${data.max_sessions} sessions (limit reached)`;
        quotaEl.style.color = '#E67E22';
    } else {
        quotaEl.innerHTML = `${data.session_count}/${data.max_sessions} sessions`;
    }
    sessionsList.appendChild(quotaEl);
}

// Load a session and show chat
async function loadSession(sessionId) {
    console.log('loadSession called with sessionId:', sessionId);
    try {
        const response = await fetch('chatbot_includes/load_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'session_id=' + sessionId
        });
        const data = await response.json();
        console.log('Load session response:', data);
        
        if (data.success) {
            currentSessionId = data.session.id;
            sessionMessages = data.messages;
            
            console.log('currentSessionId set to:', currentSessionId);
            
            // Clear and reload messages
            clearChatMessages();
            
            // Add all previous messages
            data.messages.forEach(msg => {
                addMessageToChat('user', msg.user_message);
                addMessageToChat('bot', msg.bot_response);
            });
            
            // Show chat view
            showChatView();
            console.log('Loaded session:', data.session.session_name);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error loading session:', error);
        alert('Failed to load session');
    }
}

// Delete session with confirmation
async function deleteSessionConfirm(sessionId) {
    if (!confirm('Are you sure? This will permanently delete this session and all messages.')) {
        return;
    }
    
    try {
        const response = await fetch('chatbot_includes/delete_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'session_id=' + sessionId
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            loadAndDisplaySessions();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error deleting session:', error);
        alert('Failed to delete session');
    }
}

// Create new session and start chat
async function createNewSession() {
    try {
        const response = await fetch('chatbot_includes/create_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:'thesis_id=' + thesisId + '&session_name=Chat ' + new Date().toLocaleString()
        });
        const data = await response.json();
        
        if (data.success) {
            currentSessionId = data.session_id;
            sessionMessages = [];
            clearChatMessages();
            showChatView();
            console.log('New session created:', currentSessionId);
            return true;
        } else if (data.quota_exceeded) {
            alert(data.message);
            loadAndDisplaySessions();
            return false;
        } else {
            alert('Error: ' + data.message);
            return false;
        }
    } catch (error) {
        console.error('Error creating session:', error);
        alert('Failed to create session');
        return false;
    }
}

// Save message to session
async function saveMessageToSession(userMessage, botResponse) {
    if (!currentSessionId) return;
    
    try {
        const response = await fetch('chatbot_includes/save_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'session_id=' + currentSessionId + 
                  '&user_message=' + encodeURIComponent(userMessage) +
                  '&bot_response=' + encodeURIComponent(botResponse)
        });
        const data = await response.json();
        
        if (data.success) {
            console.log('Message saved to session');
        }
    } catch (error) {
        console.error('Error saving message:', error);
    }
}


// Send message function
function sendMessage() {
    if (!hasChatbotAccess) {
        alert('You do not have access to the chatbot. Please request access first.');
        return;
    }
    
    if (!currentSessionId) {
        alert('Please select or create a chat session first.');
        return;
    }
    
    const message = chatbotInput.value.trim();
    if (!message) return;
    
    sendMessageContinue(message);
}

function sendMessageContinue(message) {
    // Add user message to chat
    addMessageToChat('user', message);
    chatbotInput.value = '';
    
    // Show loading indicator
    showLoadingIndicator();
    
    // Send to server
    fetch('chatbot_includes/chatbot_response.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'thesis_id=' + thesisId + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        removeLoadingIndicator();
        
        if (data.success) {
            addMessageToChat('bot', data.response);
            
            // Save message to session database
            saveMessageToSession(message, data.response);
        } else {
            addMessageToChat('bot', 'Sorry, I encountered an error processing your request. Please try again.');
        }
    })
    .catch(error => {
        removeLoadingIndicator();
        console.error('Error:', error);
        addMessageToChat('bot', 'Sorry, I encountered a connection error. Please check your internet and try again.');
    });
}

// Add message to chat display
function addMessageToChat(sender, text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ' + sender;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content ' + sender;
    contentDiv.textContent = text;
    
    messageDiv.appendChild(contentDiv);
    chatbotMessages.appendChild(messageDiv);
    
    // Scroll to bottom
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Show loading indicator
function showLoadingIndicator() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message bot loading';
    messageDiv.id = 'loadingIndicator';
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content bot';
    
    const dotsDiv = document.createElement('div');
    dotsDiv.className = 'loading-dots';
    dotsDiv.innerHTML = '<div class="loading-dot"></div><div class="loading-dot"></div><div class="loading-dot"></div>';
    
    contentDiv.appendChild(dotsDiv);
    messageDiv.appendChild(contentDiv);
    chatbotMessages.appendChild(messageDiv);
    
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Remove loading indicator
function removeLoadingIndicator() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.remove();
    }
}

// Clear all messages from chat
function clearChatMessages() {
    chatbotMessages.innerHTML = '';
}

// Check access on page load if logged in
document.addEventListener('DOMContentLoaded', () => {
    if (<?php echo is_logged_in() ? 'true' : 'false'; ?>) {
        // Show access overlay by default when panel opens
        chatbotAccessOverlay.style.display = 'flex';
    } else {
        // Show login requirement if not logged in
        const messageElement = document.getElementById('accessOverlayMessage');
        messageElement.textContent = 'Please login to access the thesis chatbot features.';
        const buttons = document.querySelector('.access-overlay-buttons');
        buttons.innerHTML = '<button class="btn-request-access-overlay" onclick="window.location.href=\'index.php\'"><i class="fas fa-sign-in-alt me-2"></i>Login</button>';
    }
});
</script>

</body>
</html>
