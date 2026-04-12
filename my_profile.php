<?php
/**
 * My Profile Page - Redesigned
 * CITAS Smart Archive System
 */

require_once 'db_includes/db_connect.php';
require_login();

// Get user data from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get unread notification count
$notif_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = {$_SESSION['user_id']} AND is_read = FALSE")->fetch_assoc()['count'];

// Get all notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CITAS Smart Archive</title>
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

        /* Main Container */
        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 0 2rem;
        }

        /* Sidebar */
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

        /* Main Content */
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

        /* Profile Card */
        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            border-left: 5px solid var(--primary-orange);
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 2rem;
            align-items: start;
        }

        .profile-avatar {
            text-align: center;
        }

        .profile-avatar-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            border: 4px solid var(--primary-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            transition: transform 0.3s ease;
        }

        .profile-avatar-img:hover {
            transform: scale(1.05);
        }

        .profile-avatar-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            color: var(--primary-orange);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
            justify-content: center;
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

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: var(--text-gray);
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: var(--primary-orange);
            font-size: 1rem;
            font-weight: 600;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-primary-custom {
            background: var(--primary-orange);
            color: white;
        }

        .btn-primary-custom:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: var(--light-cream);
            color: var(--primary-orange);
            border: 1px solid var(--primary-orange);
        }

        .btn-secondary-custom:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-orange);
        }

        /* Notifications Section */
        .notifications-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .notifications-section h3 {
            color: var(--primary-orange);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-orange);
            background: var(--light-cream);
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.15);
            transform: translateX(4px);
        }

        .notification-title {
            color: var(--primary-orange);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-message {
            color: var(--text-gray);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .notification-time {
            color: var(--text-gray);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .empty-notifications {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
        }

        .empty-notifications i {
            font-size: 2rem;
            color: var(--primary-orange);
            opacity: 0.5;
            margin-bottom: 0.75rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .search-bar {
                max-width: 100%;
            }

            .container-main {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .sidebar {
                position: relative;
                top: 0;
            }

            .profile-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .profile-avatar {
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }

        .info-value {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
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
            cursor: pointer;
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

        .mobile-user-menu .profile-info:hover {
            background: rgba(255, 255, 255, 0.2);
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
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-container">
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <span>CITAS Smart Archive</span>
        </div>
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
            <a href="my_profile.php" class="nav-link" style="gap: 0.75rem;">
                <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-orange); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid white; flex-shrink: 0;">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    <?php endif; ?>
                </div>
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
    <?php if (is_logged_in()): ?>
    <div class="mobile-user-menu">
        <a href="my_profile.php" style="text-decoration: none; color: inherit;">
            <div class="profile-info" style="gap: 1rem;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary-orange);"></i>
                    <?php endif; ?>
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
        <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
        <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
        <?php if (is_admin()): ?>
        <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
        <?php endif; ?>
    </ul>
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
                <?php if (is_admin()): ?>
                <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="sidebar-section">
            <h3>Quick Info</h3>
            <div class="info-item">
                <span class="info-label">Account Status</span>
                <span class="badge-status badge-<?php echo $user['account_status'] === 'active' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($user['account_status']); ?>
                </span>
            </div>
            <div class="info-item mt-2">
                <span class="info-label">Member Since</span>
                <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <section class="page-header">
            <h1><i class="fas fa-user-circle me-2"></i>My Profile</h1>
            <p>Manage your account information and preferences</p>
        </section>

        <!-- Profile Card -->
        <section class="profile-card">
            <div class="profile-avatar">
                <div class="profile-avatar-img" id="profileAvatarDisplay">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <p class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <div class="profile-role">
                    <span class="badge-status badge-<?php echo $user['account_status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($user['account_status']); ?>
                    </span>
                    <span class="badge-status badge-<?php echo $user['user_role'] === 'admin' ? 'danger' : 'primary'; ?>" style="background: #E8F4F8; color: #0284C7;">
                        <?php echo ucfirst($user['user_role']); ?>
                    </span>
                </div>
            </div>

            <div class="profile-info">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-id-card me-1"></i>Student ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['student_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope me-1"></i>Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-book me-1"></i>Course</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['course']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-graduation-cap me-1"></i>Year Level</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['year_level']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt me-1"></i>Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['address']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone me-1"></i>Contact Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-custom btn-primary-custom" onclick="openEditProfileModal()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="btn-custom btn-secondary-custom" onclick="openChangePasswordModal()">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </div>
            </div>
        </section>

        <!-- Notifications Section -->
        <section class="notifications-section">
            <h3>
                <i class="fas fa-bell"></i> Recent Activity & Notifications
                <?php if ($notif_count > 0): ?>
                <span class="badge-status badge-danger" style="margin-left: auto;">
                    <?php echo $notif_count; ?> New
                </span>
                <?php endif; ?>
            </h3>

            <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div class="notifications-list">
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                <div class="notification-item">
                    <div class="notification-title">
                        <i class="fas fa-<?php 
                            echo $notif['type'] === 'approved' ? 'check-circle' : 
                                 ($notif['type'] === 'denied' ? 'times-circle' : 
                                 ($notif['type'] === 'pending' ? 'hourglass-half' : 'info-circle')); 
                        ?>"></i>
                        <?php echo htmlspecialchars($notif['title']); ?>
                    </div>
                    <p class="notification-message">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                    <div class="notification-time">
                        <i class="fas fa-clock me-1"></i><?php echo date('M d, Y • H:i', strtotime($notif['created_at'])); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-notifications">
                <i class="fas fa-inbox"></i>
                <p>No notifications yet</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">You're all caught up!</p>
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-orange); color: white;">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editProfileMessage" class="alert alert-dismissible fade" role="alert" style="display: none;">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <span id="editProfileMessageText"></span>
                </div>
                <form id="editProfileForm" enctype="multipart/form-data">
                    <!-- Profile Picture Section -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="flex-shrink: 0;">
                                    <img id="profilePicturePreview" src="" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-orange); display: none;">
                                    <div id="profilePictureDefault" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark)); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; border: 3px solid var(--primary-orange);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div style="flex-grow: 1;">
                                    <label for="editProfilePicture" class="form-label" style="margin-bottom: 8px;"><strong>Profile Picture</strong></label>
                                    <input type="file" class="form-control" id="editProfilePicture" name="profile_picture" accept="image/*" onchange="previewProfilePicture(event)">
                                    <small class="text-muted d-block mt-2">Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                                </div>
                            </div>
                            <hr class="my-4">
                        </div>
                    </div>
                    <!-- End Profile Picture Section -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editFullName" class="form-label"><strong>Full Name</strong></label>
                            <input type="text" class="form-control" id="editFullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label"><strong>Email Address</strong></label>
                            <input type="email" class="form-control" id="editEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editAddress" class="form-label"><strong>Address</strong></label>
                            <input type="text" class="form-control" id="editAddress" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editContactNumber" class="form-label"><strong>Contact Number</strong></label>
                            <input type="tel" class="form-control" id="editContactNumber" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editCourse" class="form-label"><strong>Course</strong></label>
                            <select id="editCourse" name="course" class="form-select" required>
                                <option value="BSIT" <?php echo $user['course'] === 'BSIT' ? 'selected' : ''; ?>>BSIT</option>
                                <option value="BSCS" <?php echo $user['course'] === 'BSCS' ? 'selected' : ''; ?>>BSCS</option>
                                <option value="BSIS" <?php echo $user['course'] === 'BSIS' ? 'selected' : ''; ?>>BSIS</option>
                                <option value="BSED" <?php echo $user['course'] === 'BSED' ? 'selected' : ''; ?>>BSED</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editYearLevel" class="form-label"><strong>Year Level</strong></label>
                            <select id="editYearLevel" name="year_level" class="form-select" required>
                                <option value="1st Year" <?php echo $user['year_level'] === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo $user['year_level'] === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo $user['year_level'] === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th Year" <?php echo $user['year_level'] === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditProfileForm()">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-orange); color: white;">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="fas fa-lock me-2"></i> Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="changePasswordMessage" class="alert alert-dismissible fade" role="alert" style="display: none;">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <span id="changePasswordMessageText"></span>
                </div>
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label"><strong>Current Password</strong></label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="Enter your current password" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label"><strong>New Password</strong></label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Enter new password (minimum 6 characters)" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmNewPassword" class="form-label"><strong>Confirm New Password</strong></label>
                        <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitChangePasswordForm()">
                    <i class="fas fa-key me-1"></i> Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script>
// Mark notifications as read when modal is opened
function markNotificationsAsRead() {
    fetch('client_includes/mark_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error marking notifications as read:', error));
}

function clearAllNotifications() {
    if (!confirm('Clear all notifications? This action cannot be undone.')) return;
    
    fetch('client_includes/clear_all_notifications.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('All notifications cleared');
            location.reload();
        }
    })
    .catch(error => alert('Error clearing notifications'));
}

// Search Function
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

// Open Edit Profile Modal
function openEditProfileModal() {
    document.getElementById('editProfileMessage').style.display = 'none';
    document.getElementById('editProfileForm').reset();
    
    // Hide preview by default
    document.getElementById('profilePicturePreview').style.display = 'none';
    document.getElementById('profilePictureDefault').style.display = 'flex';
    
    const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}

// Preview profile picture before upload
function previewProfilePicture(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profilePicturePreview');
            const defaultImage = document.getElementById('profilePictureDefault');
            preview.src = e.target.result;
            preview.style.display = 'block';
            defaultImage.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

// Submit Edit Profile Form
function submitEditProfileForm() {
    const form = document.getElementById('editProfileForm');
    const messageDiv = document.getElementById('editProfileMessage');
    const formData = new FormData(form);
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    
    fetch('client_includes/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            messageDiv.className = 'alert alert-success alert-dismissible fade show';
            document.getElementById('editProfileMessageText').textContent = data.message;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                modal.hide();
                location.reload();
            }, 2000);
        } else {
            messageDiv.className = 'alert alert-danger alert-dismissible fade show';
            document.getElementById('editProfileMessageText').textContent = data.message;
            messageDiv.style.display = 'block';
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        messageDiv.className = 'alert alert-danger alert-dismissible fade show';
        document.getElementById('editProfileMessageText').textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
    });
}

// Open Change Password Modal
function openChangePasswordModal() {
    document.getElementById('changePasswordMessage').style.display = 'none';
    document.getElementById('changePasswordForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

// Submit Change Password Form
function submitChangePasswordForm() {
    const form = document.getElementById('changePasswordForm');
    const messageDiv = document.getElementById('changePasswordMessage');
    const formData = new FormData(form);
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';
    
    fetch('client_includes/change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            messageDiv.className = 'alert alert-success alert-dismissible fade show';
            document.getElementById('changePasswordMessageText').textContent = data.message;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                modal.hide();
                document.getElementById('changePasswordForm').reset();
            }, 2000);
        } else {
            messageDiv.className = 'alert alert-danger alert-dismissible fade show';
            document.getElementById('changePasswordMessageText').textContent = data.message;
            messageDiv.style.display = 'block';
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        messageDiv.className = 'alert alert-danger alert-dismissible fade show';
        document.getElementById('changePasswordMessageText').textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
    });
}

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
    
    // Mobile Menu Toggle
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
});
</script>

</body>
</html>
<?php $conn->close(); ?>