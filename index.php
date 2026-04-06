<?php
/**
 * Main Homepage - Redesigned
 * Citas Smart Archive System
 */

require_once 'db_includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas Smart Archive Web System</title>
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

        .btn-primary-custom {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        .btn-primary-custom:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .featured-banner {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--accent-gold) 100%);
            color: white;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
        }

        .featured-banner h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        .featured-banner p {
            font-size: 1.1rem;
            opacity: 0.95;
            color: white;
        }

        /* Content Sections */
        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .content-section h3 {
            color: var(--primary-orange);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .content-section p {
            color: var(--text-gray);
            line-height: 1.8;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .content-section p:last-child {
            margin-bottom: 0;
        }

        /* Search Input Section */
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            margin-bottom: 2rem;
        }

        .search-section h3 {
            color: var(--primary-orange);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .search-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .search-input-group input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-input-group input:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
        }

        .search-input-group button {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-input-group button:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        }

        /* Thesis Cards Carousel */
        .thesis-carousel-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .carousel-slide {
            padding: 1.5rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }

        .carousel-slide:hover {
            border-color: var(--primary-orange);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.15);
        }

        .carousel-slide h5 {
            color: var(--primary-orange);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .carousel-slide p {
            color: var(--text-gray);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .carousel-slide p strong {
            color: var(--text-dark);
        }

        .carousel-slide .btn {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .carousel-slide .btn:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
            color: white;
        }

        /* Carousel Controls */
        .carousel-control-prev,
        .carousel-control-next {
            width: auto !important;
            height: auto !important;
            background: var(--primary-orange) !important;
            border-radius: 50% !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            padding: 0.75rem 1rem !important;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: var(--hover-orange) !important;
        }

        .carousel-control-prev-icon::before,
        .carousel-control-next-icon::before {
            content: '';
        }

        /* Authentication Modal */
        .auth-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .auth-modal-overlay.active {
            display: flex;
        }

        .auth-modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Animations for login/logout */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .fa-spinner { animation: spin 1s linear infinite; }

        @keyframes successPulse { 0% { transform: scale(1); } 50% { transform: scale(1.04);} 100% { transform: scale(1);} }
        .login-success { animation: successPulse 0.6s ease; }

        @keyframes shake { 0%,100% { transform: translateX(0);} 25% { transform: translateX(-10px);} 75% { transform: translateX(10px);} }
        .shake { animation: shake 0.45s ease; }

        @keyframes logoutFade { from { opacity: 1; transform: scale(1); } to { opacity: 0; transform: scale(0.98); } }
        body.logout-animation { animation: logoutFade 0.38s ease forwards; }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .auth-modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            margin-bottom: 1.5rem;
            flex-shrink: 0;
        }

        .auth-modal-header h2 {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin: 0;
            flex: 1;
            text-align: center;
        }

        .auth-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-gray);
            cursor: pointer;
            transition: color 0.3s ease;
            position: absolute;
            right: 0;
            top: 0;
        }

        .auth-modal-close:hover {
            color: var(--primary-orange);
        }

        .auth-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-light);
            justify-content: center;
            flex-shrink: 0;
        }

        .auth-tab {
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

        .auth-tab.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
        }

        .auth-tab-content {
            overflow-y: auto;
            padding-right: 0.5rem;
            flex: 1;
        }

        .auth-tab-content::-webkit-scrollbar {
            width: 6px;
        }

        .auth-tab-content::-webkit-scrollbar-track {
            background: var(--border-light);
            border-radius: 10px;
        }

        .auth-tab-content::-webkit-scrollbar-thumb {
            background: var(--primary-orange);
            border-radius: 10px;
        }

        .auth-tab-content::-webkit-scrollbar-thumb:hover {
            background: var(--hover-orange);
        }

        .auth-form-group {
            margin-bottom: 1rem;
        }

        .auth-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-orange);
            text-align: left;
        }

        .auth-form-group input,
        .auth-form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .auth-form-group input:focus,
        .auth-form-group select:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
        }

        .auth-submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-orange);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .auth-submit-btn:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        }

        .auth-footer-text {
            text-align: center;
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .auth-footer-text a {
            color: var(--primary-orange);
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
        }

        .auth-footer-text a:hover {
            text-decoration: underline;
        }

        .alert-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
            text-align: center;
        }

        .alert-success {
            background: #D5F4E6;
            color: #27AE60;
            border: 1px solid #27AE60;
        }

        .alert-danger {
            background: #F8D7DA;
            color: #E74C3C;
            border: 1px solid #E74C3C;
        }

        /* Modal centered text and content */
        .auth-modal-header {
            justify-content: center;
            text-align: center;
            position: relative;
        }

        .auth-modal-header h2 {
            flex: 1;
            text-align: center;
        }

        .auth-modal-close {
            position: absolute;
            right: 0;
            top: 0;
        }

        .auth-modal-content > p {
            text-align: center !important;
        }

        #loginForm, #signupForm {
            text-align: center;
        }

        .auth-tab-content-wrapper {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .auth-tab-content-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .auth-tab-content-wrapper::-webkit-scrollbar-track {
            background: var(--border-light);
            border-radius: 10px;
        }

        .auth-tab-content-wrapper::-webkit-scrollbar-thumb {
            background: var(--primary-orange);
            border-radius: 10px;
        }

        .auth-tab-content-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--hover-orange);
        }

        /* Responsive Design */
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

            .featured-banner {
                padding: 1.5rem;
            }

            .featured-banner h2 {
                font-size: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Allow text selection only in specific areas */
        .content-section p,
        .featured-banner p {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }

        .page-header h1 {
            color: var(--primary-orange);
            font-size: 2rem;
            margin-bottom: 0.5rem;
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
            <a href="#" class="nav-link" onclick="openAuthModal(event)"><i class="fas fa-sign-in-alt"></i> Login</a>
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
    <ul class="sidebar-menu">
        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
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

<!-- Main Container -->
<div class="container-main">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <h3>Navigation</h3>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
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

        <?php if (!is_logged_in()): ?>
        <div class="sidebar-section">
            <button class="btn-primary-custom" onclick="openAuthModal(event)">
                <i class="fas fa-sign-in-alt"></i> Login / Sign Up
            </button>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Featured Section -->
        <section class="featured-banner">
            <h2>Welcome to Citas Smart Archive</h2>
            <p>Discover, share, and collaborate on academic research</p>
        </section>

        <!-- Search Section -->
        <div class="search-section">
            <h3><i class="fas fa-search me-2"></i>Search Theses</h3>
            <div class="search-input-group">
                <input type="text" id="mainSearchInput" placeholder="Search theses, authors, keywords..." />
                <button onclick="performMainSearch()">Search</button>
            </div>
        </div>

        <!-- Content Sections -->
        <section class="content-section">
            <h3>Access a Growing Collection of IT Research</h3>
            <p>Access a growing collection of IT research and stay connected to the latest innovations in technology. Our repository contains thesis works from computer science, information systems, and information technology students.</p>
        </section>

        <section class="content-section">
            <h3>Discover Quality Research</h3>
            <p>Access hundreds of publication pages and stay up to date with what's happening in your field. Browse curated thesis collections, discover groundbreaking research, and connect with fellow scholars in the academic community.</p>
        </section>
    </main>
</div>

<!-- Authentication Modal Overlay -->
<div class="auth-modal-overlay" id="authModalOverlay">
    <div class="auth-modal-content">
        <div class="auth-modal-header">
            <h2>Welcome to Citas Smart Archive</h2>
            <button type="button" class="auth-modal-close" onclick="closeAuthModal()">&times;</button>
        </div>

        <p style="text-align: center; color: var(--text-gray); margin-bottom: 1.5rem; font-size: 0.95rem;">
            To access the Thesis, please log in or create an account first.
        </p>

        <div class="auth-tabs">
            <button type="button" class="auth-tab active" onclick="switchAuthTab(event, 'login')">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
            <button type="button" class="auth-tab" onclick="switchAuthTab(event, 'signup')">
                <i class="fas fa-user-plus me-2"></i>Sign Up
            </button>
        </div>

        <!-- Login Form -->
        <div id="login-tab" class="auth-tab-content" style="display: block;">
            <div id="loginMessage" class="alert-message"></div>
            <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                <div class="auth-form-group">
                    <label for="loginStudentID">Account ID / Credential</label>
                    <input type="text" id="loginStudentID" name="student_id" placeholder="Enter Your ID or Credential" required>
                </div>
                <div class="auth-form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="auth-submit-btn">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            <p class="auth-footer-text">
                <a href="#" onclick="switchAuthTab(event, 'signup')">Don't have an account? Sign Up</a>
            </p>
        </div>

        <!-- Signup Form -->
        <div id="signup-tab" class="auth-tab-content" style="display: none;">
            <div id="signupMessage" class="alert-message"></div>
            <form id="signupForm" onsubmit="handleSignupSubmit(event)" enctype="multipart/form-data">
                <div class="row g-2">
                    <div class="col-md-12">
                        <div class="auth-form-group">
                            <label for="signupRole">Account Type</label>
                            <select id="signupRole" name="user_role" required>
                                <option value="student" selected>Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-form-group">
                            <label for="signupName">Full Name</label>
                            <input type="text" id="signupName" name="full_name" placeholder="Enter Full Name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-form-group">
                            <label for="signupEmail">Email</label>
                            <input type="email" id="signupEmail" name="email" placeholder="Enter Email" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-form-group">
                            <label for="signupStudentID" id="signupCredentialLabel">Student ID</label>
                            <input type="text" id="signupStudentID" name="student_id" placeholder="Enter Student ID" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="signupAddressGroup">
                        <div class="auth-form-group">
                            <label for="signupAddress">Address</label>
                            <input type="text" id="signupAddress" name="address" placeholder="Enter Full Address" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="signupContactGroup">
                        <div class="auth-form-group">
                            <label for="signupContact">Contact Number</label>
                            <input type="tel" id="signupContact" name="contact_number" placeholder="Enter Contact Number" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="signupCourseGroup">
                        <div class="auth-form-group">
                            <label for="signupCourse">Course</label>
                            <select id="signupCourse" name="course" required>
                                <option value="">Select Course</option>
                                <option value="BSIT">Bachelor of Science in Information Technology</option>
                                <option value="BMA">Bachelor of Multimedia Arts</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6" id="signupYearGroup">
                        <div class="auth-form-group">
                            <label for="signupYear">Year Level</label>
                            <select id="signupYear" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-form-group">
                            <label for="signupPassword">Password</label>
                            <input type="password" id="signupPassword" name="password" placeholder="Create Password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="auth-form-group">
                            <label for="signupConfirmPassword">Confirm Password</label>
                            <input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                    </div>
                    <div class="col-md-12" id="signupLoadsheetGroup">
                        <div class="auth-form-group">
                            <label for="signupLoadsheet" id="signupLoadsheetLabel">Upload Student Loadsheet (Verification)</label>
                            <input type="file" id="signupLoadsheet" name="loadsheet_file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>
                <button type="submit" class="auth-submit-btn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            <p class="auth-footer-text">
                <a href="#" onclick="switchAuthTab(event, 'login')">Already have an account? Login</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>window.USE_CUSTOM_LOGIN_HANDLER = true;</script>
<script src="script.js"></script>
<script>
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

// Login Form Handler
function handleLoginSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('loginForm'));
    const messageDiv = document.getElementById('loginMessage');
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
    
    fetch('client_includes/login.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            messageDiv.className = 'alert-message alert-success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
            messageDiv.style.display = 'block';

            // Animate modal success (pulse), then close and redirect
            const modalContent = document.querySelector('.auth-modal-content');
            if (window.animateModalSuccess) animateModalSuccess(modalContent);

            setTimeout(() => {
                const modal = document.getElementById('authModalOverlay');
                modal.classList.add('closing');
                setTimeout(() => {
                    window.location.href = data.redirect || 'index.php';
                }, 300);
            }, 900);
        } else {
            messageDiv.className = 'alert-message alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.message;
            messageDiv.style.display = 'block';
            // Animate error shake
            const modalContent = document.querySelector('.auth-modal-content');
            if (window.animateModalError) animateModalError(modalContent);
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        messageDiv.className = 'alert-message alert-danger';
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
    });
}

// Signup Form Handler
function handleSignupSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('signupForm'));
    const messageDiv = document.getElementById('signupMessage');
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
    
    console.log('Starting signup form submission...');
    
    fetch('client_includes/register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        if (!response.ok) {
            throw new Error('HTTP Error: ' + response.status + ' ' + response.statusText);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Response text length:', text.length);
        console.log('Response text (first 500 chars):', text.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(text);
            console.log('Parsed JSON:', data);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            throw new Error('Invalid server response: ' + text.substring(0, 100));
        }
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            console.log('Registration successful!');
            messageDiv.className = 'alert-message alert-success';
            messageDiv.textContent = data.message;
            messageDiv.style.display = 'block';
            
            document.getElementById('signupForm').reset();
            
            setTimeout(() => {
                switchAuthTab(new Event('click'), 'login');
                messageDiv.style.display = 'none';
            }, 3000);
        } else {
            console.error('Registration failed:', data.message);
            messageDiv.className = 'alert-message alert-danger';
            messageDiv.textContent = data.message || 'Registration failed. Please try again.';
            messageDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        console.error('Error stack:', error.stack);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        messageDiv.className = 'alert-message alert-danger';
        messageDiv.textContent = 'Error: ' + error.message;
        messageDiv.style.display = 'block';
    });
}

// Modal Functions
function openAuthModal(event) {
    event.preventDefault();
    document.getElementById('authModalOverlay').classList.add('active');
}

function closeAuthModal() {
    document.getElementById('authModalOverlay').classList.remove('active');
}

// Switch between login and signup tabs
function switchAuthTab(event, tab) {
    event.preventDefault();
    
    // Hide all tabs
    document.querySelectorAll('.auth-tab-content').forEach(el => {
        el.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.auth-tab').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected tab
    const tabElement = document.getElementById(tab + '-tab');
    if (tabElement) {
        tabElement.style.display = 'block';
            
        // Scroll into view for signup form
        if (tab === 'signup') {
            setTimeout(() => {
                tabElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
            updateSignupFormByRole();
        }
    }
    
    // Add active class to clicked button
    event.target.closest('.auth-tab').classList.add('active');
}

function toggleSignupField(groupId, shouldShow) {
    const group = document.getElementById(groupId);
    if (group) {
        group.style.display = shouldShow ? '' : 'none';
    }
}

function setSignupRequired(fieldId, required) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.required = required;
    }
}

function updateSignupFormByRole() {
    const roleSelect = document.getElementById('signupRole');
    const role = roleSelect ? roleSelect.value : 'student';
    const credentialLabel = document.getElementById('signupCredentialLabel');
    const credentialInput = document.getElementById('signupStudentID');
    const loadsheetLabel = document.getElementById('signupLoadsheetLabel');

    if (credentialLabel && credentialInput) {
        if (role === 'admin') {
            credentialLabel.textContent = 'Admin Credential / ID';
            credentialInput.placeholder = 'Enter Admin Credential / ID';
        } else if (role === 'instructor') {
            credentialLabel.textContent = 'Instructor ID';
            credentialInput.placeholder = 'Enter Instructor ID';
        } else {
            credentialLabel.textContent = 'Student ID';
            credentialInput.placeholder = 'Enter Student ID';
        }
    }

    const showProfileFields = role !== 'admin';
    toggleSignupField('signupAddressGroup', showProfileFields);
    toggleSignupField('signupContactGroup', showProfileFields);
    toggleSignupField('signupCourseGroup', showProfileFields);
    toggleSignupField('signupYearGroup', showProfileFields);
    toggleSignupField('signupLoadsheetGroup', role === 'instructor');

    setSignupRequired('signupAddress', showProfileFields);
    setSignupRequired('signupContact', showProfileFields);
    setSignupRequired('signupCourse', showProfileFields);
    setSignupRequired('signupYear', showProfileFields);
    setSignupRequired('signupLoadsheet', role === 'instructor');

    if (loadsheetLabel) {
        loadsheetLabel.textContent = role === 'instructor'
            ? 'Upload Instructor Loadsheet (Verification)'
            : 'Upload Student Loadsheet (Verification)';
    }
}

document.getElementById('signupRole')?.addEventListener('change', updateSignupFormByRole);
document.addEventListener('DOMContentLoaded', updateSignupFormByRole);

// Search Functions
function performHeaderSearch() {
    const searchTerm = document.getElementById('headerSearchInput').value;
    if (searchTerm.trim()) {
        window.location.href = 'browse.php?search=' + encodeURIComponent(searchTerm);
    }
}

function performMainSearch() {
    const searchTerm = document.getElementById('mainSearchInput').value;
    if (searchTerm.trim()) {
        window.location.href = 'browse.php?search=' + encodeURIComponent(searchTerm);
    }
}

// Close modal when clicking outside
document.getElementById('authModalOverlay')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeAuthModal();
    }
});

// Allow Enter key to submit search
document.getElementById('headerSearchInput')?.addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        performHeaderSearch();
    }
});

document.getElementById('mainSearchInput')?.addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        performMainSearch();
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

// Auto-refresh notifications every 10 seconds if user is logged in
<?php if (is_logged_in()): ?>
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
<?php endif; ?>
</script>

</body>
</html>
<?php $conn->close(); ?>