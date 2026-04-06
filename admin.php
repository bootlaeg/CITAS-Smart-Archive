<?php
/**
 * Admin Panel - Redesigned
 * Citas Smart Archive System
 */

require_once 'db_includes/db_connect.php';
require_login();
require_admin();

// Get statistics
$total_thesis = $conn->query("SELECT COUNT(*) as count FROM thesis")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'active'")->fetch_assoc()['count'];
$pending_access = $conn->query("SELECT COUNT(*) as count FROM thesis_access WHERE status = 'pending'")->fetch_assoc()['count'];

// Get all theses
$thesis_result = $conn->query("SELECT * FROM thesis ORDER BY created_at DESC LIMIT 20");

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");

// Get pending access requests
$access_result = $conn->query("
    SELECT ta.id, ta.user_id, ta.thesis_id, ta.requested_at,
           u.full_name, u.student_id,
           t.title
    FROM thesis_access ta
    JOIN users u ON ta.user_id = u.id
    JOIN thesis t ON ta.thesis_id = t.id
    WHERE ta.status = 'pending'
    ORDER BY ta.requested_at DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Citas Smart Archive</title>
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

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .page-header h1 {
            color: var(--primary-orange);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-gray);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            border-left: 5px solid var(--primary-orange);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(230, 126, 34, 0.15);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-orange);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .admin-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-light);
        }

        .admin-tab {
            background: none;
            border: none;
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-gray);
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .admin-tab.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
        }

        .admin-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .admin-table table {
            margin-bottom: 0;
        }

        .admin-table thead {
            background: #FAFAFA;
            border-bottom: 2px solid var(--border-light);
        }

        .admin-table th {
            padding: 1rem;
            color: var(--primary-orange);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-gray);
        }

        .admin-table tbody tr:hover {
            background: var(--light-cream);
        }

        .badge-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #D5F4E6;
            color: #27AE60;
        }

        .badge-warning {
            background: #FFF3CD;
            color: #F39C12;
        }

        .badge-danger {
            background: #F8D7DA;
            color: #E74C3C;
        }

        .action-btn {
            background: var(--light-cream);
            border: 1px solid var(--border-light);
            color: var(--primary-orange);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-right: 0.5rem;
        }

        .action-btn:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-orange);
        }

        .action-btn-danger {
            background: #F8D7DA;
            color: #E74C3C;
            border-color: #E74C3C;
        }

        .action-btn-danger:hover {
            background: #E74C3C;
            color: white;
            border-color: #E74C3C;
        }

        .empty-state {
            background: white;
            padding: 3rem 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-orange);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--primary-orange);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-gray);
        }

        .btn-success {
            background: #27AE60 !important;
            color: white !important;
            border: none !important;
            padding: 0.5rem 1.5rem !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .btn-success:hover {
            background: #229954 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3) !important;
        }

        .btn-secondary {
            background: #7F8C8D !important;
            color: white !important;
            border: none !important;
        }

        .btn-secondary:hover {
            background: #5D6D7B !important;
            color: white !important;
        }

        .btn-primary {
            background: var(--primary-orange) !important;
            color: white !important;
            border: none !important;
        }

        .btn-primary:hover {
            background: var(--hover-orange) !important;
            color: white !important;
        }

        .alert {
            padding: 1rem !important;
            border-radius: 6px !important;
            margin-bottom: 1rem !important;
        }

        .alert-success {
            background: #D5F4E6 !important;
            color: #27AE60 !important;
            border: 1px solid #27AE60 !important;
        }

        .alert-danger {
            background: #F8D7DA !important;
            color: #E74C3C !important;
            border: 1px solid #E74C3C !important;
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
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .mobile-user-menu .profile-info {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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

        /* Mobile Login Menu Section */
        .mobile-login-menu {
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .mobile-login-btn {
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
            margin: 0;
        }

        .mobile-login-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .mobile-user-menu .profile-info {
            cursor: pointer;
        }

        .mobile-user-menu .profile-info:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .header-container {
                justify-content: space-between;
                align-items: center;
                flex-direction: row;
                gap: 1rem;
            }

            .logo {
                flex-shrink: 0;
            }

            .search-bar {
                flex: 1;
                max-width: 100%;
                order: 3;
                width: 100%;
            }

            /* Hide desktop navigation on mobile */
            .nav-links {
                display: none;
            }

            /* Show hamburger menu on mobile */
            .hamburger-menu {
                display: flex;
                order: 2;
            }

            .container-main {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .sidebar {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-table {
                overflow-x: auto;
            }

            .action-btn {
                display: block;
                margin-bottom: 0.5rem;
                width: 100%;
                text-align: center;
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
        
        <!-- Desktop Navigation -->
        <nav class="nav-links">
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
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
        <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
        <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
        <li><a href="admin.php" class="active"><i class="fas fa-lock"></i> Admin Panel</a></li>
    </ul>

    <?php if (is_logged_in()): ?>
    <div class="mobile-user-menu">
        <a href="my_profile.php" style="text-decoration: none; color: inherit;">
            <div class="profile-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </a>
        <button class="logout-btn" onclick="handleLogout(event)">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>
    <?php else: ?>
    <div class="mobile-login-menu">
        <button class="mobile-login-btn" onclick="openAuthModal(event)">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </div>
    <?php endif; ?>
</nav>

<!-- Main Container -->
<div class="container-main">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <h3>Navigation</h3>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="admin.php" class="active"><i class="fas fa-lock"></i> Admin Panel</a></li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <section class="page-header">
            <h1><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</h1>
            <p>Centralized management system for the Thesis Repository</p>
        </section>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card" onclick="switchTab('thesis-tab')">
                <div class="stat-value"><?php echo $total_thesis; ?></div>
                <div class="stat-label">Total Theses</div>
            </div>
            <div class="stat-card" onclick="switchTab('user-tab')">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card" onclick="switchTab('access-tab')">
                <div class="stat-value"><?php echo $pending_access; ?></div>
                <div class="stat-label">Pending Access Requests</div>
            </div>
        </div>

        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="switchTab('thesis-tab')">
                <i class="fas fa-file-alt me-2"></i>Thesis Management
            </button>
            <button class="admin-tab" onclick="switchTab('user-tab')">
                <i class="fas fa-users me-2"></i>User Management
            </button>
            <button class="admin-tab" onclick="switchTab('access-tab')">
                <i class="fas fa-key me-2"></i>Access Requests
            </button>
        </div>

        <!-- Thesis Management Tab -->
        <div id="thesis-tab" class="admin-section">
            <div style="margin-bottom: 1.5rem;">
                <a href="admin_includes/admin_add_thesis_page.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Add New Thesis
                </a>
            </div>
            <div class="admin-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Course/Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($thesis_result && $thesis_result->num_rows > 0): ?>
                            <?php while ($thesis = $thesis_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo str_pad($thesis['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars(substr($thesis['title'], 0, 50)); ?></td>
                                <td><?php echo htmlspecialchars($thesis['author']); ?></td>
                                <td><?php echo htmlspecialchars($thesis['course'] . ' / ' . $thesis['year']); ?></td>
                                <td>
                                    <?php
                                        $status = $thesis['status'];
                                        $badgeClass = '';
                                        
                                        if ($status === 'approved') {
                                            $badgeClass = 'badge-success';
                                        } elseif ($status === 'pending') {
                                            $badgeClass = 'badge-warning';
                                        } elseif ($status === 'archived') {
                                            $badgeClass = 'badge-danger';
                                        }
                                    ?>
                                    <span class="badge-status <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn" onclick="viewThesis(<?php echo $thesis['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn" onclick="editThesis(<?php echo $thesis['id']; ?>)" title="Edit Thesis">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn action-btn-danger" onclick="deleteThesis(<?php echo $thesis['id']; ?>, '<?php echo htmlspecialchars($thesis['title'], ENT_QUOTES); ?>')" title="Delete Thesis">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No theses found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Management Tab -->
        <div id="user-tab" class="admin-section" style="display: none;">
            <div class="admin-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && $users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['user_role'] ?? 'student')); ?></td>
                                <td><?php echo htmlspecialchars($user['course']); ?></td>
                                <td>
                                    <span class="badge-status badge-<?php echo $user['account_status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($user['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn" onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['student_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['course'], ENT_QUOTES); ?>', '<?php echo $user['account_status']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($user['account_status'] === 'pending'): ?>
                                    <button class="action-btn" onclick="verifyUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No users found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Access Requests Tab -->
        <div id="access-tab" class="admin-section" style="display: none;">
            <div class="admin-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Student Name</th>
                            <th>Thesis Title</th>
                            <th>Requested Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($access_result && $access_result->num_rows > 0): ?>
                            <?php while ($request = $access_result->fetch_assoc()): ?>
                            <tr>
                                <td>REQ<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($request['title'], 0, 40)); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <button class="action-btn" onclick="approveAccess(<?php echo $request['id']; ?>, <?php echo $request['user_id']; ?>, <?php echo $request['thesis_id']; ?>, '<?php echo htmlspecialchars($request['full_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn action-btn-danger" onclick="rejectAccess(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No pending access requests</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-orange); color: white;">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Full Name</strong></label>
                        <input type="text" class="form-control" id="editUserName" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Email</strong></label>
                        <input type="email" class="form-control" id="editUserEmail" disabled>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label"><strong>Student ID</strong></label>
                        <input type="text" class="form-control" id="editUserStudentId" disabled>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label"><strong>Course</strong></label>
                        <input type="text" class="form-control" id="editUserCourse" disabled>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label"><strong>Account Status</strong></label>
                        <div id="accountStatusDisplay" class="p-2 border rounded bg-light"></div>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3"><i class="fas fa-tools me-2"></i>Account Management Actions</h6>
                
                <div id="actionButtonsContainer" class="d-grid gap-2">
                    <!-- Buttons will be inserted here based on status -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Thesis Modal -->

<!-- View Thesis Modal -->
<div class="modal fade" id="viewThesisModal" tabindex="-1" aria-labelledby="viewThesisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-orange); color: white;">
                <h5 class="modal-title" id="viewThesisModalLabel">
                    <i class="fas fa-eye me-2"></i>View Thesis Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="viewThesisId">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label"><strong>Title</strong></label>
                        <p id="viewThesisTitle" style="color: var(--primary-orange); font-weight: 600;"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Author</strong></label>
                        <p id="viewThesisAuthor"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Course</strong></label>
                        <p id="viewThesisCourse"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Year</strong></label>
                        <p id="viewThesisYear"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Status</strong></label>
                        <p id="viewThesisStatus"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Abstract / Description</strong></label>
                    <p id="viewThesisDescription" style="border: 1px solid var(--border-light); padding: 1rem; border-radius: 6px; background: var(--light-cream); max-height: 300px; overflow-y: auto;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Thesis Modal -->
<div class="modal fade" id="editThesisModal" tabindex="-1" aria-labelledby="editThesisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-orange); color: white;">
                <h5 class="modal-title" id="editThesisModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Thesis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editThesisMessage" class="alert alert-dismissible fade" role="alert" style="display: none;">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <span id="editThesisMessageText"></span>
                </div>
                <form id="editThesisForm">
                    <input type="hidden" id="editThesisId" name="thesis_id">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="editThesisTitle" class="form-label"><strong>Thesis Title</strong></label>
                            <input type="text" class="form-control" id="editThesisTitle" name="title" placeholder="Enter thesis title" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editThesisAuthor" class="form-label"><strong>Author Name</strong></label>
                            <input type="text" class="form-control" id="editThesisAuthor" name="author" placeholder="Enter author name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editThesisCourse" class="form-label"><strong>Course</strong></label>
                            <select class="form-select" id="editThesisCourse" name="course" required>
                                <option value="">Select Course</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSIS">BSIS</option>
                                <option value="BSED">BSED</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editThesisYear" class="form-label"><strong>Year</strong></label>
                            <select class="form-select" id="editThesisYear" name="year" required>
                                <option value="">Select Year</option>
                                <option value="2020">2020</option>
                                <option value="2021">2021</option>
                                <option value="2022">2022</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editThesisStatus" class="form-label"><strong>Status</strong></label>
                            <select class="form-select" id="editThesisStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editThesisAbstract" class="form-label"><strong>Abstract / Description</strong></label>
                        <textarea class="form-control" id="editThesisAbstract" name="abstract" rows="5" placeholder="Enter thesis abstract or description" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditThesisForm()">
                    <i class="fas fa-save me-2"></i>Update Thesis
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script>
// Ensure all functions are available when page is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel JavaScript loaded');
    
    // Mobile Menu Toggle
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-menu a');

    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function() {
            hamburgerMenu.classList.toggle('active');
            mobileNavMenu.classList.toggle('active');
            mobileNavOverlay.classList.toggle('active');
        });
    }

    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', function() {
            hamburgerMenu.classList.remove('active');
            mobileNavMenu.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
        });
    }

    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            hamburgerMenu.classList.remove('active');
            mobileNavMenu.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
        });
    });
});

// Switch Tabs - Define in global scope
window.switchTab = function(tabName) {
    document.querySelectorAll('.admin-section').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.admin-tab').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabName).style.display = 'block';
    
    // Find and activate the corresponding tab button
    const tabButtons = document.querySelectorAll('.admin-tab');
    tabButtons.forEach(btn => {
        if (btn.getAttribute('onclick').includes(tabName)) {
            btn.classList.add('active');
        }
    });
};

// Admin Functions - Define in global scope

window.verifyUser = function(userId, userName) {
    if (!confirm(`Verify account for "${userName}"?`)) return;
    
    fetch('admin_includes/admin_verify_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Verify error:', error);
        alert('Error verifying user: ' + error.message);
    });
};

window.viewThesis = function(thesisId) {
    // Redirect to view_thesis.php with the thesis ID parameter
    window.location.href = 'view_thesis.php?id=' + thesisId;
}

window.editThesis = function(thesisId) {
    fetch('admin_includes/admin_view_thesis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'thesis_id=' + thesisId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editThesisId').value = data.thesis.id;
            document.getElementById('editThesisTitle').value = data.thesis.title;
            document.getElementById('editThesisAuthor').value = data.thesis.author;
            document.getElementById('editThesisCourse').value = data.thesis.course;
            document.getElementById('editThesisYear').value = data.thesis.year;
            document.getElementById('editThesisAbstract').value = data.thesis.description;
            
            // Fix: Properly set the status dropdown value
            const statusSelect = document.getElementById('editThesisStatus');
            statusSelect.value = data.thesis.status;
            
            // Debug: Log to verify
            console.log('Setting status to:', data.thesis.status);
            console.log('Dropdown value is now:', statusSelect.value);
            
            document.getElementById('editThesisMessage').style.display = 'none';
            const modal = new bootstrap.Modal(document.getElementById('editThesisModal'));
            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Error loading thesis details'));
}

window.submitEditThesisForm = function() {
    const form = document.getElementById('editThesisForm');
    const messageDiv = document.getElementById('editThesisMessage');
    const formData = new FormData(form);
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating Thesis...';
    
    fetch('admin_includes/admin_edit_thesis.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            messageDiv.className = 'alert alert-success alert-dismissible fade show';
            messageDiv.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' + data.message;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editThesisModal'));
                modal.hide();
                location.reload();
            }, 2000);
        } else {
            messageDiv.className = 'alert alert-danger alert-dismissible fade show';
            messageDiv.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' + data.message;
            messageDiv.style.display = 'block';
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        messageDiv.className = 'alert alert-danger alert-dismissible fade show';
        messageDiv.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>An error occurred. Please try again.';
        messageDiv.style.display = 'block';
    });
}

// Delete Thesis
window.deleteThesis = function(thesisId, title) {
    if (!confirm(`Are you sure you want to delete the thesis "${title}"? This action cannot be undone.`)) return;
    
    fetch('admin_includes/admin_delete_thesis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'thesis_id=' + thesisId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Error deleting thesis: ' + error.message);
    });
}

// Approve Access Request
window.approveAccess = function(requestId, userId, thesisId, userName) {
    if (!confirm(`Approve access for "${userName}"?`)) return;
    
    fetch('admin_includes/admin_approve_access.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'request_id=' + requestId + '&user_id=' + userId + '&thesis_id=' + thesisId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        }
    })
    .catch(error => alert('Error approving access'));
}

// Reject Access Request
window.rejectAccess = function(requestId, userName) {
    if (!confirm(`Reject access for "${userName}"?`)) return;
    
    fetch('admin_includes/admin_deny_access.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'request_id=' + requestId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Reject error:', error);
        alert('Error rejecting access: ' + error.message);
    });
}

// Edit User Modal
window.openEditUserModal = function(userId, fullName, email, studentId, course, status) {
    // Populate form fields
    document.getElementById('editUserName').value = fullName;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserStudentId').value = studentId;
    document.getElementById('editUserCourse').value = course;

    // Display account status with badge
    const statusBadge = document.createElement('span');
    statusBadge.className = 'badge-status badge-' + (status === 'active' ? 'success' : (status === 'pending' ? 'warning' : 'danger'));
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    const statusDisplay = document.getElementById('accountStatusDisplay');
    statusDisplay.innerHTML = '';
    statusDisplay.appendChild(statusBadge);

    // Build action buttons based on status
    const actionContainer = document.getElementById('actionButtonsContainer');
    actionContainer.innerHTML = '';

    if (status === 'pending') {
        // Pending: Show Verify and Suspend buttons
        const verifyBtn = document.createElement('button');
        verifyBtn.type = 'button';
        verifyBtn.className = 'btn btn-success';
        verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Verify & Activate Account';
        verifyBtn.onclick = () => {
            if (confirm(`Verify and activate account for "${fullName}"?`)) {
                verifyUserFromModal(userId, fullName);
            }
        };

        const suspendBtn = document.createElement('button');
        suspendBtn.type = 'button';
        suspendBtn.className = 'btn btn-warning';
        suspendBtn.innerHTML = '<i class="fas fa-user-lock me-2"></i>Suspend Account';
        suspendBtn.onclick = () => {
            if (confirm(`Suspend account for "${fullName}"?`)) {
                suspendUserFromModal(userId, fullName);
            }
        };

        actionContainer.appendChild(verifyBtn);
        actionContainer.appendChild(suspendBtn);
    } 
    else if (status === 'active') {
        // Active: Show Suspend button only
        const suspendBtn = document.createElement('button');
        suspendBtn.type = 'button';
        suspendBtn.className = 'btn btn-warning';
        suspendBtn.innerHTML = '<i class="fas fa-user-lock me-2"></i>Suspend Account';
        suspendBtn.onclick = () => {
            if (confirm(`Suspend account for "${fullName}"?`)) {
                suspendUserFromModal(userId, fullName);
            }
        };

        actionContainer.appendChild(suspendBtn);
    } 
    else if (status === 'suspended') {
        // Suspended: Show Unsuspend and Verify buttons
        const unsuspendBtn = document.createElement('button');
        unsuspendBtn.type = 'button';
        unsuspendBtn.className = 'btn btn-info';
        unsuspendBtn.innerHTML = '<i class="fas fa-undo me-2"></i>Restore Account';
        unsuspendBtn.onclick = () => {
            if (confirm(`Restore account for "${fullName}" to pending verification?`)) {
                unsuspendUserFromModal(userId, fullName);
            }
        };

        const verifyBtn = document.createElement('button');
        verifyBtn.type = 'button';
        verifyBtn.className = 'btn btn-success';
        verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Verify & Activate Account';
        verifyBtn.onclick = () => {
            if (confirm(`Verify and activate account for "${fullName}"?`)) {
                verifyUserFromModal(userId, fullName);
            }
        };

        actionContainer.appendChild(unsuspendBtn);
        actionContainer.appendChild(verifyBtn);
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

// Verify user from modal
window.verifyUserFromModal = function(userId, userName) {
    fetch('admin_includes/admin_verify_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Verify error:', error);
        alert('Error verifying user: ' + error.message);
    });
}

// Suspend user from modal
// Suspend user from modal
window.suspendUserFromModal = function(userId, userName) {
    fetch('admin_includes/admin_suspend_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Suspend error:', error);
        alert('Error suspending user: ' + error.message);
    });
}

// Unsuspend user from modal
window.unsuspendUserFromModal = function(userId, userName) {
    fetch('admin_includes/admin_unsuspend_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Unsuspend error:', error);
        alert('Error restoring user: ' + error.message);
    });
}

// ========== NOTIFICATION SYSTEM ==========

// Toggle notification panel
window.toggleNotificationPanel = function() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown.style.display === 'none') {
        dropdown.style.display = 'block';
        loadNotifications();
    } else {
        dropdown.style.display = 'none';
    }
}

// Load and display notifications
window.loadNotifications = function() {
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
window.updateNotificationDisplay = function(data) {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    
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
window.markNotificationRead = function(notificationId) {
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
window.markAllAsRead = function() {
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
window.clearAllNotifications = function() {
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
window.escapeHtml = function(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
};

// Handle Logout
window.handleLogout = function(event) {
    event.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'client_includes/logout.php';
    }
};

// View User Details
window.viewUserDetails = function(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    // Load user details via AJAX
    fetch('admin_includes/admin_view_thesis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user) {
            // Populate modal with user details
            const details = data.user;
            console.log('Loading user details:', details);
        }
    })
    .catch(error => console.error('Error loading user details:', error));
};

// Auto-refresh notifications every 10 seconds
setInterval(() => {
    if (document.getElementById('notificationDropdown') && document.getElementById('notificationDropdown').style.display !== 'none') {
        window.loadNotifications();
    }
}, 10000);

// Initial load when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin panel ready');
    window.loadNotifications();
});
</script>

<!-- User Details Modal -->
<?php include 'admin_includes/user_details_modal.html'; ?>
                            
</body>
</html>
<?php $conn->close(); ?>