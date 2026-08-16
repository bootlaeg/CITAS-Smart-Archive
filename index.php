<?php
/**
 * Main Homepage - Redesigned Modern Dark Theme
 * CITAS Smart Archive System
 */
require_once 'db_includes/db_connect.php';

$user = null;
if (is_logged_in()) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Get thesis count for stats
$thesisCount = 0;
$authorCount = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM thesis WHERE status='approved'");
if ($result) { $row = $result->fetch_assoc(); $thesisCount = $row['cnt']; }
$result2 = $conn->query("SELECT COUNT(DISTINCT author) as cnt FROM thesis WHERE status='approved'");
if ($result2) { $row2 = $result2->fetch_assoc(); $authorCount = $row2['cnt']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CITAS Smart Archive</title>
    <meta name="description" content="Discover, search, and access academic thesis research from CITAS students.">
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index_redesign.css">
    <!-- Cloudflare Turnstile CAPTCHA -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-container">
        <a href="index.php" class="logo">
            <img src="img/CITAS_logo.png" alt="CITAS">
            <span><em>CITAS</em> Smart Archive</span>
        </a>

        <nav class="nav-links">
            <?php if (is_logged_in()): ?>
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <?php if (is_admin()): ?>
            <a href="admin.php" class="nav-link"><i class="fas fa-lock"></i> Admin Panel</a>
            <?php endif; ?>
            <?php if (!is_admin()): ?>
            <a href="browse.php" class="nav-link"><i class="fas fa-compass"></i> Browse</a>
            <a href="favorites.php" class="nav-link"><i class="fas fa-heart"></i> Favorites</a>
            <?php endif; ?>
            <div class="notification-center" id="notificationCenter">
                <a href="#" class="nav-link" onclick="event.preventDefault(); toggleNotificationPanel()" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
                </a>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div style="padding:1rem;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center;">
                        <h4 style="margin:0;font-size:14px;font-weight:800;">Notifications</h4>
                        <button style="background:none;border:none;cursor:pointer;color:var(--gray-500);font-size:1.2rem;" onclick="toggleNotificationPanel()">&times;</button>
                    </div>
                    <div id="notificationList" style="max-height:300px;overflow-y:auto;">
                        <p style="padding:1rem;text-align:center;color:var(--gray-500);font-size:13px;">Loading...</p>
                    </div>
                    <div style="padding:0.75rem;border-top:1px solid var(--gray-100);display:flex;gap:0.5rem;">
                        <button onclick="markAllAsRead()" style="flex:1;padding:0.5rem;background:var(--gray-100);border:1px solid var(--gray-100);border-radius:6px;cursor:pointer;font-size:12px;color:var(--gray-700);font-weight:600;">Mark Read</button>
                        <button onclick="clearAllNotifications()" style="flex:1;padding:0.5rem;background:var(--gray-100);border:1px solid var(--gray-100);border-radius:6px;cursor:pointer;font-size:12px;color:var(--gray-700);font-weight:600;">Clear All</button>
                    </div>
                </div>
            </div>
            <a href="my_profile.php" class="nav-link">
                <div class="nav-profile-pic">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user" style="color:var(--primary);"></i>
                    <?php endif; ?>
                </div>
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </a>
            <a href="#" class="nav-link logout" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
            <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a>
            <a href="#" class="nav-link login-btn" onclick="openAuthModal(event)"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </nav>

        <button class="hamburger-menu" id="hamburgerMenu"><span></span><span></span><span></span></button>
    </div>
</header>

<!-- Mobile Nav Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<!-- Mobile Nav Menu -->
<nav class="mobile-nav-menu" id="mobileNavMenu">
    <?php if (is_logged_in()): ?>
    <div class="mobile-user-menu">
        <a href="my_profile.php" style="text-decoration:none;color:inherit;">
            <div class="profile-info">
                <div style="width:45px;height:45px;border-radius:50%;background:var(--bg-dark);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;border:2px solid var(--primary);">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size:1.5rem;color:var(--primary);"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
        <?php if (is_logged_in()): ?>
        <?php if (!is_admin()): ?>
        <li><a href="browse.php"><i class="fas fa-compass"></i> Browse Thesis</a></li>
        <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
        <?php endif; ?>
        <?php if (is_admin()): ?>
        <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
        <?php endif; ?>
        <?php endif; ?>
    </ul>
    <?php if (is_logged_in()): ?>
    <div class="mobile-logout-menu">
        <button class="logout-btn" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>
    <?php endif; ?>
    <?php if (!is_logged_in()): ?>
    <div class="mobile-login-menu">
        <button class="mobile-login-btn" onclick="openAuthModal(event)"><i class="fas fa-sign-in-alt"></i> Login / Sign Up</button>
    </div>
    <?php endif; ?>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge"><i class="fas fa-graduation-cap"></i> College of Information Technology and Allied Sciences</div>
        <h1>Your Gateway to<br><span class="highlight">Academic Research</span></h1>
        <p>Explore a curated collection of thesis works from IT, and Multimedia Arts students.</p>
        <div class="hero-search">
            <input type="text" id="mainSearchInput" placeholder="Search theses, authors, keywords...">
            <button onclick="performMainSearch()"><i class="fas fa-search"></i> Search</button>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><span class="number"><?php echo $thesisCount; ?>+</span><span class="label">Theses</span></div>
            <div class="hero-stat"><span class="number"><?php echo $authorCount; ?>+</span><span class="label">Authors</span></div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="features-section">
    <div class="section-header">
        <h2>Why Use <span style="color:var(--primary);">CITAS Smart Archive</span>?</h2>
        <p>Everything you need to discover and access quality academic research</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-search"></i></div>
            <h3>Smart Search</h3>
            <p>Instantly find relevant theses by title, author, keywords, or research methodology with our intelligent search system.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-robot"></i></div>
            <h3>AI-Powered Conversion</h3>
            <p>Access AI-reconstructed journal versions of theses, converting academic research into structured IMRaD format automatically.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bookmark"></i></div>
            <h3>Save Favorites</h3>
            <p>Bookmark theses you love for quick access later. Build your own personal research library effortlessly.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Verified Research</h3>
            <p>All submissions go through an admin approval process, ensuring only quality and verified research is accessible.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
            <h3>Mobile Friendly</h3>
            <p>Access the archive from any device — desktop, tablet, or mobile — with a fully responsive interface.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bell"></i></div>
            <h3>Stay Updated</h3>
            <p>Receive real-time notifications when new theses are published or your account status changes.</p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-card">
        <h2>Ready to Explore Research?</h2>
        <p>Join the community of students and educators using CITAS Smart Archive.</p>
        <?php if (is_logged_in()): ?>
            <?php if (is_admin()): ?>
            <a href="admin.php" class="cta-btn"><i class="fas fa-lock"></i> Go to Admin Panel</a>
            <?php else: ?>
            <a href="browse.php" class="cta-btn"><i class="fas fa-compass"></i> Browse Theses</a>
            <?php endif; ?>
        <?php else: ?>
        <button class="cta-btn" onclick="openAuthModal(event)"><i class="fas fa-user-plus"></i> Get Started Free</button>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">&copy; <?php echo date('Y'); ?> CITAS Smart Archive. College of Information Technology and Allied Sciences.</footer>

<!-- AUTH MODAL -->
<div class="auth-modal-overlay" id="authModalOverlay">
    <div class="auth-modal-content">
        <div class="auth-modal-header">
            <h2>Welcome to CITAS</h2>
            <button type="button" class="auth-modal-close" onclick="closeAuthModal()">&times;</button>
        </div>
        <p style="text-align:center;color:var(--text-muted);margin-bottom:1.25rem;font-size:0.9rem;">Log in or create an account to access the thesis repository.</p>
        <div class="auth-tabs">
            <button type="button" class="auth-tab active" onclick="switchAuthTab(event,'login')"><i class="fas fa-sign-in-alt me-1"></i>Login</button>
            <button type="button" class="auth-tab" onclick="switchAuthTab(event,'signup')"><i class="fas fa-user-plus me-1"></i>Sign Up</button>
        </div>
        <!-- Login -->
        <div id="login-tab" class="auth-tab-content" style="display:block;">
            <div id="loginMessage" class="alert-message"></div>
            <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                <div class="auth-form-group"><label for="loginStudentID">Account ID / Email</label><input type="text" id="loginStudentID" name="student_id" placeholder="Enter ID or Email" required></div>
                <div class="auth-form-group">
                    <label for="loginPassword">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('loginPassword', this)"></i>
                    </div>
                    <div style="text-align: right; margin-top: 5px;">
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">Forgot Password?</a>
                    </div>
                </div>
                
                <!-- Cloudflare Turnstile Widget -->
                <div class="cf-turnstile my-3 d-flex justify-content-center" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="dark"></div>
                
                <button type="submit" class="auth-submit-btn"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
            </form>
            <p class="auth-footer-text"><a href="#" onclick="switchAuthTab(event,'signup')">Don't have an account? Sign Up</a></p>
        </div>
        <!-- Signup -->
        <div id="signup-tab" class="auth-tab-content" style="display:none;">
            <div id="signupMessage" class="alert-message"></div>
            <form id="signupForm" onsubmit="handleSignupSubmit(event)" enctype="multipart/form-data">
                <div class="row g-2">
                    <div class="col-md-12"><div class="auth-form-group"><label for="signupRole">Account Type</label><select id="signupRole" name="user_role" required><option value="student" selected>Student</option><option value="instructor">Instructor</option><option value="admin">Admin</option></select></div></div>
                    <div class="col-md-6"><div class="auth-form-group"><label for="signupName">Full Name</label><input type="text" id="signupName" name="full_name" placeholder="Enter Full Name" required></div></div>
                    <div class="col-md-6"><div class="auth-form-group"><label for="signupEmail">Email</label><input type="email" id="signupEmail" name="email" placeholder="Enter Email" required></div></div>
                    <div class="col-md-6" id="signupCredentialGroup"><div class="auth-form-group"><label for="signupStudentID" id="signupCredentialLabel">Student ID</label><input type="text" id="signupStudentID" name="student_id" placeholder="Enter Student ID"></div></div>
                    <div class="col-md-6" id="signupAddressGroup"><div class="auth-form-group"><label for="signupAddress">Address</label><input type="text" id="signupAddress" name="address" placeholder="Enter Full Address"></div></div>
                    <div class="col-md-6" id="signupContactGroup"><div class="auth-form-group"><label for="signupContact">Contact Number</label><input type="tel" id="signupContact" name="contact_number" placeholder="Enter Contact Number"></div></div>
                    <div class="col-md-6" id="signupCourseGroup"><div class="auth-form-group"><label for="signupCourse">Course</label><select id="signupCourse" name="course"><option value="">Select Course</option><option value="BSIT">Bachelor of Science in Information Technology</option><option value="BMA">Bachelor of Multimedia Arts</option></select></div></div>
                    <div class="col-md-6" id="signupYearGroup"><div class="auth-form-group"><label for="signupYear">Year Level</label><select id="signupYear" name="year_level"><option value="">Select Year</option><option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option><option value="3rd Year">3rd Year</option><option value="4th Year">4th Year</option></select></div></div>
                    <div class="col-md-6"><div class="auth-form-group"><label for="signupPassword">Password</label><div class="password-wrapper"><input type="password" id="signupPassword" name="password" placeholder="Create Password" required><i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('signupPassword', this)"></i></div></div></div>
                    <div class="col-md-6"><div class="auth-form-group"><label for="signupConfirmPassword">Confirm Password</label><div class="password-wrapper"><input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Confirm Password" required><i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('signupConfirmPassword', this)"></i></div></div></div>
                    <div class="col-md-12" id="signupLoadsheetGroup"><div class="auth-form-group"><label for="signupLoadsheet" id="signupLoadsheetLabel">Upload Student Loadsheet (Verification)</label><input type="file" id="signupLoadsheet" name="loadsheet_file" accept=".pdf,.jpg,.jpeg,.png"></div></div>
                </div>
                
                <!-- Cloudflare Turnstile Widget -->
                <div class="cf-turnstile my-3 d-flex justify-content-center" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="dark"></div>
                
                <button type="submit" class="auth-submit-btn"><i class="fas fa-user-plus me-2"></i>Create Account</button>
            </form>
            <p class="auth-footer-text"><a href="#" onclick="switchAuthTab(event,'login')">Already have an account? Login</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>window.USE_CUSTOM_LOGIN_HANDLER = true;</script>
<script src="script.js"></script>
<script src="js/index_redesign.js"></script>

</body>
</html>
<?php $conn->close(); ?>