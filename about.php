<?php
/**
 * About Us Page - Citas Smart Archive
 * Citas Smart Archive System
 */

require_once 'db_includes/db_connect.php';

// Get user data if logged in
$user = null;
if (is_logged_in()) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Citas Smart Archive</title>
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
            text-align: justify;
        }

        .content-section p:last-child {
            margin-bottom: 0;
        }

        /* About Repository Section */
        .about-repository {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
        }

        .about-repository h3 {
            color: var(--primary-orange);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .about-repository h4 {
            color: var(--primary-orange);
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .about-repository p {
            color: var(--text-gray);
            line-height: 1.8;
            font-size: 1rem;
            margin-bottom: 1rem;
            text-align: justify;
        }

        .features-list {
            list-style: none;
            padding-left: 0;
            color: var(--text-gray);
        }

        .features-list li {
            padding: 0.5rem 0;
            padding-left: 2rem;
            position: relative;
            color: var(--text-gray);
        }

        .features-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary-orange);
            font-weight: bold;
            font-size: 1.5rem;
        }

        /* Vision and Mission Section */
        .vision-mission-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .vision-mission-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            border-top: 4px solid var(--primary-orange);
            transition: all 0.3s ease;
        }

        .vision-mission-card:hover {
            box-shadow: 0 4px 16px rgba(230, 126, 34, 0.15);
            transform: translateY(-2px);
        }

        .vision-mission-card h4 {
            color: var(--primary-orange);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vision-mission-card p {
            color: var(--text-gray);
            line-height: 1.8;
            text-align: justify;
        }

        /* Developers Section */
        .developers-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .developer-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .developer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .developer-card:hover::before {
            opacity: 0.05;
        }

        .developer-card:hover {
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.2);
            transform: translateY(-4px);
            border-color: var(--primary-orange);
        }

        .developer-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--light-cream);
            border: 4px solid var(--primary-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-orange);
            margin: 0 auto 1rem;
            overflow: hidden;
            position: relative;
        }

        .developer-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .developer-name {
            color: var(--primary-orange);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .developer-role {
            color: var(--text-gray);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .developer-description {
            color: var(--text-gray);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            min-height: 60px;
        }

        .developer-action {
            color: var(--primary-orange);
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .developer-card:hover .developer-action {
            transform: translateX(4px);
        }

        /* Developer Modal Styles */
        .developer-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2500;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .developer-modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .developer-modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideUp {
            from {
                transform: translateY(60px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .developer-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: white;
            border: 2px solid var(--border-light);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .developer-modal-close:hover {
            background: var(--light-cream);
            color: var(--primary-orange);
            border-color: var(--primary-orange);
        }

        .developer-modal-header {
            padding: 2rem;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            border-bottom: 1px solid var(--border-light);
        }

        .developer-modal-photo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-body-two-column {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 0;
            padding: 0;
            flex: 1;
            overflow: hidden;
        }

        .modal-body-left-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 2rem;
            background: var(--light-cream);
            border-right: 1px solid var(--border-light);
            overflow-y: auto;
            min-height: 0;
        }

        .modal-body-right-column {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding: 2rem;
            overflow-y: auto;
        }

        #developerPhotosSection {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100% !important;
            text-align: center;
        }

        #developerPhotosSection .contributor-photo {
            margin-left: auto;
            margin-right: auto;
        }

        #developerPhotosSection .contributor-name {
            width: 100%;
            text-align: center;
        }

        .modal-photo {
            width: 160px;
            height: 160px;
            border-radius: 12px;
            border: 4px solid var(--primary-orange);
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(230, 126, 34, 0.2);
        }

        .developer-modal-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }

        .developer-modal-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--primary-orange);
            line-height: 1.2;
        }

        .modal-role {
            color: var(--text-gray);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }

        .modal-badge {
            display: inline-flex;
            align-items: center;
            background: var(--light-cream);
            color: var(--primary-orange);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            width: fit-content;
            border: 1px solid var(--primary-orange);
        }

        .developer-modal-body {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
        }

        /* Contributors Photos Section */
        .contributors-photos-section {
            padding: 2rem;
            background: var(--light-cream);
            border-bottom: 1px solid var(--border-light);
        }

        .contributors-photos-section .contributors-photos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1.5rem;
            justify-items: center;
        }

        .modal-section {
            margin-bottom: 2rem;
        }

        .modal-section:last-child {
            margin-bottom: 0;
        }

        .modal-section h3 {
            color: var(--primary-orange);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .modal-section p {
            color: var(--text-gray);
            line-height: 1.8;
            font-size: 1rem;
        }

        .contributions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .contributions-list li {
            padding: 0.75rem 0;
            padding-left: 2rem;
            position: relative;
            color: var(--text-gray);
            font-size: 0.95rem;
        }

        .contributions-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary-orange);
            font-weight: bold;
            font-size: 1.2rem;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .skill-badge {
            display: inline-block;
            background: var(--light-cream);
            color: var(--primary-orange);
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid var(--primary-orange);
            transition: all 0.3s ease;
        }

        .skill-badge:hover {
            background: var(--primary-orange);
            color: white;
        }

        .developer-modal-footer {
            padding: 1.5rem 2rem;
            background: #FAFAFA;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-modal-close {
            background: white;
            color: var(--primary-orange);
            border: 2px solid var(--primary-orange);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-modal-close:hover {
            background: var(--primary-orange);
            color: white;
        }

        /* Contributors Photos Section */
        .contributors-photos-section {
            padding: 2rem;
            background: var(--light-cream);
            border-bottom: 1px solid var(--border-light);
        }

        .developer-modal-photo .contributors-photos-section {
            padding: 0;
            background: transparent;
            border-bottom: none;
        }

        .contributors-photos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
        }

        .developer-modal-photo .contributors-photos-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .contributor-photo-card {
            text-align: center;
            transition: all 0.3s ease;
        }

        .contributor-photo-card:hover {
            transform: translateY(-4px);
        }

        .contributor-photo {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            border: 3px solid var(--primary-orange);
            object-fit: cover;
            display: block;
            margin: 0 auto 0.5rem;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
            transition: all 0.3s ease;
            text-align: center;
        }

        .contributor-photo-card:hover .contributor-photo {
            box-shadow: 0 8px 20px rgba(230, 126, 34, 0.35);
            border-color: var(--primary-dark);
        }

        .contributor-name {
            color: var(--primary-orange);
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 0.5rem;
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

            .featured-banner {
                padding: 1.5rem;
            }

            .featured-banner h2 {
                font-size: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .vision-mission-container,
            .developers-container {
                grid-template-columns: 1fr;
            }

            .developer-modal-header {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .modal-photo {
                width: 140px;
                height: 140px;
            }

            .developer-modal-content {
                width: 95%;
                max-height: 85vh;
            }

            .developer-modal-header {
                padding: 1.5rem;
            }

            .modal-body-two-column {
                grid-template-columns: 1fr;
                gap: 0;
                padding: 0;
            }

            .modal-body-left-column {
                border-right: none;
                border-bottom: 1px solid var(--border-light);
                padding: 1.5rem;
            }

            .modal-body-right-column {
                padding: 1.5rem;
            }

            .developer-modal-footer {
                padding: 1rem 1.5rem;
            }

            .contributors-photos-container {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 1rem;
            }

            .contributor-photo {
                width: 100px;
                height: 100px;
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
        <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a></li>
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
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a></li>
                <?php if (is_logged_in()): ?>
                <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <?php if (is_admin()): ?>
                <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <section class="page-header">
            <h1><i class="fas fa-info-circle me-2"></i>About Us</h1>
            <p>Learn more about Citas Smart Archive and the team behind it</p>
        </section>

        <!-- About Repository Section -->
        <section class="about-repository">
            <h3><i class="fas fa-book me-2"></i>About Citas Smart Archive Web System</h3>
            <p>The Citas Smart Archive Web System makes it easy for Samar College students to access past research online. Students can quickly search and read thesis summaries, while full documents are securely available to registered users. This platform saves time, reduces reliance on printed copies, and preserves valuable research for future use.</p>
            
            <h4 style="color: var(--primary-orange); margin-top: 1.5rem; margin-bottom: 0.75rem; font-weight: 600;">Key Features:</h4>
            <ul class="features-list">
                <li>Read Thesis Summaries Online: Easily read summaries of past research anytime.</li>
                <li>Safe Sign-Up and Login: Only verified Samar College students can access the system.</li>
                <li>View Full Theses Safely: Full papers can be read online, but downloads and screenshots are blocked.</li>
                <li>All Theses in One Place: Everything is stored in one digital library for easy access.</li>
                <li>No Need for Paper Copies: Access research digitally without printed copies.</li>
                <li>Find Research Fast: Quickly search and locate past studies without wasting time.</li>
            </ul>

            <p style="margin-top: 1.5rem;">This repository serves as a valuable resource for the academic community, enabling better knowledge sharing and research collaboration across disciplines.</p>
        </section>

        <!-- School Overview Section -->
        <section class="content-section">
            <h3><i class="fas fa-school me-2"></i>School Overview</h3>
            <p>Samar College (SC) is a premier private, non-sectarian educational institution located in Catbalogan City, Samar. Founded on July 1, 1949, as Samar Junior College, it has grown from its humble beginnings to become a leading center of learning in the region. For over 75 years, the college has been dedicated to providing quality education from basic to graduate levels, fostering a community of globally competitive and values-driven individuals.</p>
        </section>

        <!-- Vision and Mission Section -->
        <div class="vision-mission-container">
            <div class="vision-mission-card">
                <h4><i class="fas fa-eye"></i> Vision</h4>
                <p>We are the leading center of learning in the island of Samar. We take pride in being the school of first choice by students where they can fully attain academic and personal achievements through affordable education, excellent instruction, and state-of-the-art facilities in a values-driven educational system.</p>
            </div>

            <div class="vision-mission-card">
                <h4><i class="fas fa-bullseye"></i> Mission</h4>
                <p>Samar College is a community-based, privately owned learning institution that provides quality basic, tertiary, and graduate education to students of Samar Island and its neighboring communities. We commit to help our students improve their quality of life by delivering affordable, values-driven, industry-relevant curricular programs that produce globally competitive, innovative, service-oriented, and God-fearing citizens who contribute to the progress of society.</p>
            </div>
        </div>

        <!-- Core Values Section -->
        <section class="content-section">
            <h3><i class="fas fa-heart me-2"></i>Core Values</h3>
            <p>The institution is guided by the following core values, which are integrated into its culture and academic programs:</p>
            
            <ul class="features-list">
                <li>Integrity</li>
                <li>Respect</li>
                <li>Concern for Others</li>
                <li>Passion for Excellence</li>
                <li>Dedication to Service</li>
                <li>God-fearing</li>
                <li>Principle-centeredness</li>
            </ul>
        </section>

        <!-- Institutional Objectives Section -->
        <section class="content-section">
            <h3><i class="fas fa-target me-2"></i>Institutional Objectives</h3>
            <p>To realize its vision and mission, Samar College intends to:</p>
            
            <div style="margin-top: 1.5rem;">
                <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange); display: block; margin-bottom: 0.5rem;">1. Adhere to the Highest Standards</strong>
                    <p style="margin: 0; color: var(--text-gray);">Adhere to the highest standards of work and personal ethics.</p>
                </div>

                <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange); display: block; margin-bottom: 0.5rem;">2. Provide Avenues for Advancement</strong>
                    <p style="margin: 0; color: var(--text-gray);">Provide avenues for advancement and give due recognition and reward for individual and collective contributions.</p>
                </div>

                <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange); display: block; margin-bottom: 0.5rem;">3. Work for the Greater Good</strong>
                    <p style="margin: 0; color: var(--text-gray);">Work for the greater good of all who belong to the community we operate in by going beyond the call of duty.</p>
                </div>

                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange); display: block; margin-bottom: 0.5rem;">4. Help Find Meaning in Life</strong>
                    <p style="margin: 0; color: var(--text-gray);">Help find meaning in life through education.</p>
                </div>
            </div>

            <p style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-light);">
                For more information about our institution, please visit the 
                <a href="https://www.samarcollege.edu.ph" target="_blank" style="color: var(--primary-orange); font-weight: 600; text-decoration: none;">
                    Samar College Official Website <i class="fas fa-external-link-alt" style="font-size: 0.85rem; margin-left: 0.5rem;"></i>
                </a>
            </p>
        </section>

        <!-- Development Team Section -->
        <section class="content-section">
            <h3><i class="fas fa-users me-2"></i>Development Team</h3>
            <p>The Citas Smart Archive was developed by a dedicated team of IT professionals committed to creating a robust and user-friendly academic platform.</p>

            <div class="developers-container">
                <!-- System Developers Card (Both Kristoffer and Glenn) -->
                <div class="developer-card" onclick="openDeveloperModal('developers')">
                    <div class="developer-avatar">
                        <i class="fas fa-laptop-code" style="font-size: 3.5rem;"></i>
                    </div>
                    <div class="developer-name">System Developers</div>
                    <div class="developer-role">System Development Team</div>
                    <div class="developer-description">
                        Developers who are responsible for building the entire system, designing the user interface (UI/UX), creating the web pages using PHP, MySQL, and JavaScript, and organizing the database structure.
                    </div>
                    <div class="developer-action">
                        <i class="fas fa-arrow-right me-2"></i>View Details
                    </div>
                </div>

                <!-- Manuscript Contributors Card -->
                <div class="developer-card" onclick="openDeveloperModal('contributors')">
                    <div class="developer-avatar">
                        <i class="fas fa-users" style="font-size: 3rem;"></i>
                    </div>
                    <div class="developer-name">Manuscript Contributors</div>
                    <div class="developer-role">Research & Content Team</div>
                    <div class="developer-description">
                        Assisted in preparing and organizing the research manuscripts included in the repository. Contributed to documentation and content organization.
                    </div>
                    <div class="developer-action">
                        <i class="fas fa-arrow-right me-2"></i>View Details
                    </div>
                </div>
            </div>
        </section>

        <!-- Developer Modal -->
        <div class="developer-modal-overlay" id="developerModalOverlay" onclick="closeDeveloperModal(event)">
            <div class="developer-modal-content" onclick="event.stopPropagation()">
                <button type="button" class="developer-modal-close" onclick="closeDeveloperModal()">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="developer-modal-header" id="modalHeader">
                    <div class="developer-modal-info">
                        <h2 id="modalDeveloperName">Developer Name</h2>
                        <p id="modalDeveloperRole" class="modal-role">Role</p>
                    </div>
                </div>

                <!-- Two Column Layout Body -->
                <div class="modal-body-two-column">
                    <!-- Left Column: Fixed (Photo and Name) -->
                    <div class="modal-body-left-column">
                        <!-- Developers Photos Section (Kristoffer and Glenn) -->
                        <div id="developersPhotosSection" style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                            <div class="contributor-photo-card developer-selector" onclick="switchDeveloper('kristoffer')" style="cursor: pointer;">
                                <img src="img/sabarre.jpg" alt="Kristoffer-son Sabarre" class="contributor-photo" style="border: 3px solid var(--primary-orange);">
                                <div class="contributor-name">Kristoffer-son Sabarre</div>
                            </div>
                            <div class="contributor-photo-card developer-selector" onclick="switchDeveloper('glenn')" style="cursor: pointer;">
                                <img src="img/1glenn.jpg" alt="Glenn" class="contributor-photo" style="border: 3px solid var(--primary-orange);">
                                <div class="contributor-name">Glenn Guarte</div>
                            </div>
                        </div>

                        <!-- Contributors Photos Section -->
                        <div id="contributorsPhotosSection" style="width: 100%; display: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                                <div class="contributor-photo-card">
                                    <img src="img/Erilla.jpg" alt="Joshua Lomentigar" class="contributor-photo">
                                    <div class="contributor-name">Joshua Lomentigar</div>
                                </div>
                                <div class="contributor-photo-card">
                                    <img src="img/ventillo.jpg" alt="Edman Tido Bacsal" class="contributor-photo">
                                    <div class="contributor-name">Edman Tido Bacsal</div>
                                </div>
                                <div class="contributor-photo-card">
                                    <img src="img/Erilla.jpg" alt="Jhon Rey Erilla" class="contributor-photo">
                                    <div class="contributor-name">Jhon Rey Erilla</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Scrollable (Description, Contributions, Skills, Badge) -->
                    <div class="modal-body-right-column">
                        <div class="modal-section">
                            <h3><i class="fas fa-briefcase me-2"></i>Responsibilities</h3>
                            <p id="modalDeveloperDescription">Loading...</p>
                        </div>

                        <div class="modal-section">
                            <h3><i class="fas fa-info-circle me-2"></i>Contributions</h3>
                            <ul id="modalDeveloperContributions" class="contributions-list">
                                <li>System Development</li>
                                <li>Code Quality</li>
                                <li>Team Collaboration</li>
                            </ul>
                        </div>

                        <div class="modal-section">
                            <h3><i class="fas fa-tools me-2"></i>Skills</h3>
                            <div id="modalDeveloperSkills" class="skills-container">
                                <span class="skill-badge">PHP</span>
                                <span class="skill-badge">MySQL</span>
                                <span class="skill-badge">JavaScript</span>
                            </div>
                        </div>

                        <div class="modal-section">
                            <div class="modal-badge" id="modalDeveloperBadge" style="width: 100%; justify-content: center;">
                                <i class="fas fa-star me-2"></i>Team Member
                            </div>
                        </div>
                    </div>
                </div>

                <div class="developer-modal-footer">
                    <button type="button" class="btn-modal-close" onclick="closeDeveloperModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>

       <section class="content-section">
            <h3><i class="fas fa-layer-group me-2"></i>Technology Used & Integrations</h3>
            <p>The Citas Smart Archive is built on a comprehensive technology stack with powerful integrations designed for reliability, security, scalability, and intelligent functionality:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Backend</strong><br>
                    <small style="color: var(--text-gray);">PHP, MySQL, Apache</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Frontend</strong><br>
                    <small style="color: var(--text-gray);">HTML5, CSS3, JavaScript, Bootstrap</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Security</strong><br>
                    <small style="color: var(--text-gray);">Password Hashing, Session Management, Input Sanitization</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Icons & UI</strong><br>
                    <small style="color: var(--text-gray);">Font Awesome, Bootstrap Components</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Ollama</strong><br>
                    <small style="color: var(--text-gray);">Local AI Model Service for intelligent thesis classification, keyword extraction, and metadata enhancement</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">PHPMailer</strong><br>
                    <small style="color: var(--text-gray);">Email Service Library for automated notifications, user communications, and system alerts</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">File Processing</strong><br>
                    <small style="color: var(--text-gray);">PDF, DOCX, and DOC file parsing for metadata extraction and document analysis</small>
                </div>
                <div style="padding: 1rem; background: var(--light-cream); border-radius: 8px; border-left: 4px solid var(--primary-orange);">
                    <strong style="color: var(--primary-orange);">Session Management</strong><br>
                    <small style="color: var(--text-gray);">Secure authentication and session handling for multi-user access control</small>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

// Developer modal data
        let currentDeveloper = 'kristoffer';
        
        const developerData = {
            kristoffer: {
                name: 'Kristoffer-son Sabarre',
                role: 'System Developer & Lead Developer',
                photo: 'img/sabarre.jpg',
                description: 'Responsible for building the entire system, designing the user interface (UI/UX), creating the web pages using PHP, MySQL, and JavaScript, and organizing the database structure.',
                contributions: [
                    'System Architecture Design',
                    'UI/UX Design & Implementation',
                    'Database Design & Optimization',
                    'Backend Development (PHP)',
                    'Frontend Development (JavaScript)',
                    'Project Leadership & Coordination'
                ],
                skills: ['PHP', 'MySQL', 'JavaScript', 'HTML5', 'CSS3', 'Bootstrap', 'Database Design', 'Web Architecture'],
                badge: 'Lead Developer'
            },
            glenn: {
                name: 'Glenn Guarte',
                role: 'System Developer',
                photo: 'img/1glenn.jpg',
                description: 'Responsible for UI/UX testing, backend functionality validation, document processing implementation using Tesseract OCR, and quality assurance throughout the development lifecycle.',
                contributions: [
                    'UI/UX Testing & Feedback',
                    'Backend Functionality Testing & Validation',
                    'Document Processing Implementation using Tesseract OCR',
                    
                ],
                skills: ['PHP', 'MySQL', 'JavaScript', 'HTML5', 'CSS3', 'Bootstrap', 'Database Design', 'Web Architecture'],
                badge: 'System Developer'
            },  
            contributors: {
                name: 'Manuscript Contributors',
                role: 'Research & Content Team',
                photo: 'placeholder-contributors.jpg',
                description: 'A dedicated team of professionals who assisted in preparing and organizing the research manuscripts included in the repository. They contributed to documentation, content organization, quality assurance, and database management.',
                contributions: [
                    'Joshua Lomentigar - Manuscript Preparation & Documentation',
                    'Edman Tido Bacsal - Manuscript Preparation & Documentation'
                    'Jhon Rey Erilla - Manuscript Preparation & Documentation',
                ],
                skills: ['Content Organization', 'Quality Assurance', 'Documentation', 'Research Support', 'Data Management', 'Content Verification', 'Team Collaboration'],
                badge: 'Research & Content Team'
            }
        };
                    
        function switchDeveloper(developerId) {
            currentDeveloper = developerId;
            const data = developerData[developerId];
            if (!data) return;
            
            document.getElementById('modalDeveloperName').textContent = data.name;
            document.getElementById('modalDeveloperRole').textContent = data.role;
            document.getElementById('modalDeveloperDescription').textContent = data.description;
            document.getElementById('modalDeveloperBadge').innerHTML = `<i class="fas fa-star me-2"></i>${data.badge}`;
            
            const contributionsList = document.getElementById('modalDeveloperContributions');
            contributionsList.innerHTML = data.contributions.map(item => `<li>${item}</li>`).join('');
            
            const skillsContainer = document.getElementById('modalDeveloperSkills');
            skillsContainer.innerHTML = data.skills.map(skill => `<span class="skill-badge">${skill}</span>`).join('');
        }

        function openDeveloperModal(developerId) {
            // Show/hide photos sections
            const developersPhotosSection = document.getElementById('developersPhotosSection');
            const contributorsPhotosSection = document.getElementById('contributorsPhotosSection');
            
            if (developerId === 'developers') {
                developersPhotosSection.style.display = 'flex';
                contributorsPhotosSection.style.display = 'none';
                currentDeveloper = 'kristoffer';
                switchDeveloper('kristoffer');
            } else if (developerId === 'contributors') {
                developersPhotosSection.style.display = 'none';
                contributorsPhotosSection.style.display = 'block';
                const data = developerData['contributors'];
                document.getElementById('modalDeveloperName').textContent = data.name;
                document.getElementById('modalDeveloperRole').textContent = data.role;
                document.getElementById('modalDeveloperDescription').textContent = data.description;
                document.getElementById('modalDeveloperBadge').innerHTML = `<i class="fas fa-star me-2"></i>${data.badge}`;
                
                const contributionsList = document.getElementById('modalDeveloperContributions');
                contributionsList.innerHTML = data.contributions.map(item => `<li>${item}</li>`).join('');
                
                const skillsContainer = document.getElementById('modalDeveloperSkills');
                skillsContainer.innerHTML = data.skills.map(skill => `<span class="skill-badge">${skill}</span>`).join('');
            }

            // Show modal
            document.getElementById('developerModalOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeveloperModal(event) {
            if (event && event.target.id !== 'developerModalOverlay') return;
            
            document.getElementById('developerModalOverlay').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when pressing Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeveloperModal();
            }
        });
</script>

</body>
</html>
<?php $conn->close(); ?>
