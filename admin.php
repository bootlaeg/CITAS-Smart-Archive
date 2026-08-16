<?php
/**
 * Admin Panel - Redesigned
 * CITAS Smart Archive System
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db_includes/db_connect.php';
require_login();
require_admin();

// Get user data from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics
$total_thesis_res = $conn->query("SELECT COUNT(*) as count FROM thesis");
$total_thesis = $total_thesis_res ? $total_thesis_res->fetch_assoc()['count'] : 0;

$total_users_res = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'active'");
$total_users = $total_users_res ? $total_users_res->fetch_assoc()['count'] : 0;

$pending_thesis_access_res = $conn->query("SELECT COUNT(*) as count FROM thesis_access WHERE status = 'pending'");
$pending_thesis_access = $pending_thesis_access_res ? $pending_thesis_access_res->fetch_assoc()['count'] : 0;

$pending_chatbot_access_res = $conn->query("SELECT COUNT(*) as count FROM chatbot_access_requests WHERE status = 'pending'");
$pending_chatbot_access = $pending_chatbot_access_res ? $pending_chatbot_access_res->fetch_assoc()['count'] : 0;

$pending_access = $pending_thesis_access + $pending_chatbot_access;

$pending_registrations_res = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'pending'");
$pending_registrations = $pending_registrations_res ? $pending_registrations_res->fetch_assoc()['count'] : 0;

// Get all theses
$thesis_result = $conn->query("SELECT * FROM thesis ORDER BY created_at DESC LIMIT 20");

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");

// Get pending access requests
$access_result = $conn->query("
    SELECT * FROM (
        SELECT car.id, car.user_id, car.thesis_id, car.requested_at, CAST(car.status AS CHAR) as status,
               u.full_name, u.student_id, t.title, CAST('Chatbot Access' AS CHAR) as request_type
        FROM chatbot_access_requests car
        JOIN users u ON car.user_id = u.id
        JOIN thesis t ON car.thesis_id = t.id
        WHERE car.status = 'pending'
        
        UNION ALL
        
        SELECT ta.id, ta.user_id, ta.thesis_id, ta.requested_at, CAST(ta.status AS CHAR) as status,
               u.full_name, u.student_id, t.title, CAST('Thesis Access' AS CHAR) as request_type
        FROM thesis_access ta
        JOIN users u ON ta.user_id = u.id
        JOIN thesis t ON ta.thesis_id = t.id
        WHERE ta.status = 'pending'
    ) as combined_requests
    ORDER BY requested_at DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITAS Smart Archive - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css?v=20260522a">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="img/CITAS_logo.png" alt="CITAS Logo"></div>
    <div class="brand-text">
      <div class="brand-name">CITAS Smart Archive</div>
      <div class="brand-sub">Thesis Repository</div>
    </div>
  </div>

  <div class="sidebar-admin">
    <div class="admin-avatar">
      <?php if (!empty($user['profile_picture'])): ?>
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
      <?php else: ?>
        <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
      <?php endif; ?>
    </div>
    <div class="admin-info">
      <div class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
      <div class="admin-role">Administrator</div>
    </div>
  </div>

  <div class="nav-section-label">Main</div>
  <a class="nav-item" href="index.php">
    <i class="fas fa-home"></i> Home
  </a>
  <a class="nav-item active" onclick="showPage('dashboard', this); closeSidebarMobile();" style="cursor:pointer">
    <i class="fas fa-chart-pie"></i> Dashboard
  </a>
  <a class="nav-item" onclick="showPage('theses', this); closeSidebarMobile();" style="cursor:pointer">
    <i class="fas fa-file-alt"></i> Theses
    <span class="nav-badge"><?php echo $total_thesis; ?></span>
  </a>
  <a class="nav-item" onclick="showPage('users', this); closeSidebarMobile();" style="cursor:pointer">
    <i class="fas fa-users"></i> Users
    <span class="nav-badge green"><?php echo $total_users; ?></span>
  </a>
  <a class="nav-item" onclick="showPage('requests', this); closeSidebarMobile();" style="cursor:pointer">
    <i class="fas fa-inbox"></i> Access Requests
    <span class="nav-badge"><?php echo $pending_access; ?></span>
  </a>


  


  <div class="sidebar-footer">
    <button class="logout-btn" onclick="handleLogout(event)">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </div>
</aside>

<div class="main-wrap">

  <header class="topbar">
    <button class="mobile-toggle" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>

    <div class="topbar-title">Admin <span>Dashboard</span></div>

    <div class="topbar-actions">
      <div style="position:relative">
        <button class="icon-btn" id="notifBtn" onclick="toggleNotificationPanel()">
          <i class="fas fa-bell"></i>
          <span class="notif-dot" id="notificationBadge" style="display:none"></span>
        </button>
        <div class="notif-panel" id="notificationDropdown">
          <div class="notif-ph">
            Notifications
            <button class="notif-clear" onclick="markAllAsRead()">Mark Read</button>
            <button class="notif-clear" onclick="clearAllNotifications()">Clear All</button>
          </div>
          <div id="notificationList" style="max-height:300px;overflow-y:auto">
            <p style="padding:1rem;text-align:center;color:var(--gray-500);font-size:13px">Loading notifications...</p>
          </div>
        </div>
      </div>
      <a href="my_profile.php" class="icon-btn" title="Profile" style="text-decoration:none">
        <i class="fas fa-user"></i>
      </a>
    </div>
  </header>

  <div class="content">

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â DASHBOARD PAGE Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <div class="page active" id="page-dashboard">
      <div class="stats-grid">
        <div class="stat-card orange" onclick="showPage('theses', document.querySelectorAll('.nav-item')[2])" style="cursor:pointer">
          <div class="stat-header"><div class="stat-icon"><i class="fas fa-file-alt"></i></div></div>
          <div class="stat-value"><?php echo $total_thesis; ?></div>
          <div class="stat-label">Total Theses</div>
        </div>
        <div class="stat-card green" onclick="showPage('users', document.querySelectorAll('.nav-item')[3])" style="cursor:pointer">
          <div class="stat-header"><div class="stat-icon"><i class="fas fa-user-check"></i></div></div>
          <div class="stat-value"><?php echo $total_users; ?></div>
          <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card yellow" onclick="showPage('requests', document.querySelectorAll('.nav-item')[4])" style="cursor:pointer">
          <div class="stat-header"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><span class="stat-trend trend-warn"><?php echo $pending_access; ?> new</span></div>
          <div class="stat-value"><?php echo $pending_access; ?></div>
          <div class="stat-label">Pending Requests</div>
        </div>
        <div class="stat-card blue" id="pendingRegCard" onclick="showPage('users', document.querySelectorAll('.nav-item')[3]); setTimeout(() => document.getElementById('liveMonitor').scrollIntoView({behavior:'smooth'}), 100)" style="cursor:pointer">
          <div class="stat-header"><div class="stat-icon"><i class="fas fa-user-clock"></i></div></div>
          <div class="stat-value" id="pendingRegCount"><?php echo $pending_registrations; ?></div>
          <div class="stat-label">Pending Registrations</div>
        </div>
      </div>

      <div class="two-col">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><i class="fas fa-file-alt"></i> Recent Theses</div>
            <span class="panel-action" onclick="showPage('theses', document.querySelectorAll('.nav-item')[2])">View All</span>
          </div>
          <div class="panel-scroll">
            <div class="table-wrapper">
              <table class="data-table">
                <thead><tr><th>Thesis</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="dashRecentTheses"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><i class="fas fa-user-clock"></i> Pending Instructor/Admin Requests</div>
          </div>
          <div class="panel-scroll">
            <div class="table-wrapper">
              <table class="data-table" id="pendingRegistrationsTable" style="display:none; min-width: 500px;">
                <thead>
                  <tr>
                    <th>NAME</th>
                    <th>EMAIL</th>
                    <th>ROLE REQUESTED</th>
                    <th>ACTIONS</th>
                  </tr>
                </thead>
                <tbody id="dashPendingRegistrationsBody"></tbody>
              </table>
              <div id="dashPendingRegistrationsEmpty">
                <div class="empty-msg"><i class="fas fa-check-circle"></i><p>No pending elevated role requests.</p></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="two-col">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><i class="fas fa-inbox"></i> Access Requests</div>
            <span class="panel-action" onclick="showPage('requests', document.querySelectorAll('.nav-item')[4])">View All</span>
          </div>
          <div class="request-list panel-scroll" id="dashAccessReqs"></div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><i class="fas fa-users"></i> Recent Users</div>
            <span class="panel-action" onclick="showPage('users', document.querySelectorAll('.nav-item')[3])">View All</span>
          </div>
          <div class="panel-scroll" id="dashRecentUsers"></div>
        </div>
      </div>

      <div class="panel" style="margin-top: 20px;">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-history"></i> Admin Activity Log</div>
        </div>
        <div class="panel-scroll">
          <div class="table-wrapper">
            <table class="data-table" id="activityLogTable" style="display:none; min-width: 500px;">
              <thead>
                <tr>
                  <th>OPERATOR</th>
                  <th>ACTION</th>
                  <th>TARGET ENTITY</th>
                  <th>DATE</th>
                  <th>TIME</th>
                </tr>
              </thead>
              <tbody id="dashActivityLogBody"></tbody>
            </table>
            <div id="dashActivityLogEmpty">
              <div class="empty-msg"><i class="fas fa-history"></i><p>Activity log will appear here</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â THESES PAGE Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <div class="page" id="page-theses">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-file-alt"></i> All Theses</div>
          <a href="admin_includes/admin_add_thesis_page.php" class="btn-add"><i class="fas fa-plus"></i> Add Thesis</a>
        </div>
        <div class="filter-row">
          <input type="text" id="thesisSearchInput" placeholder="Search by title, author, or course..." aria-label="Search theses">
        </div>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr>
              <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllTheses" onclick="toggleSelectAll('thesis')"></th>
              <th>ID</th><th>Title</th><th>Author</th><th>Course / Year</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
              <?php
              // Re-query for theses page since result set was consumed by dashboard
              $thesis_page = $conn->query("SELECT * FROM thesis ORDER BY created_at DESC LIMIT 20");
              if ($thesis_page && $thesis_page->num_rows > 0):
                while ($thesis = $thesis_page->fetch_assoc()): ?>
              <tr>
                <td style="text-align: center;" data-label="Select"><input type="checkbox" class="select-thesis" value="<?php echo $thesis['id']; ?>" onclick="updateBulkActionBar('thesis')"></td>
                <td data-label="ID" style="font-family:'DM Mono',monospace;font-size:11px;color:var(--gray-500)"><?php echo str_pad($thesis['id'], 3, '0', STR_PAD_LEFT); ?></td>
                <td data-label="Title"><div class="thesis-title"><?php echo htmlspecialchars(substr($thesis['title'], 0, 50)); ?></div></td>
                <td data-label="Author"><?php echo htmlspecialchars($thesis['author']); ?></td>
                <td data-label="Course/Year"><span class="course-tag"><?php echo htmlspecialchars($thesis['course']); ?></span> <span style="font-family:'DM Mono',monospace;font-size:12px;margin-left:4px"><?php echo htmlspecialchars($thesis['year']); ?></span></td>
                <td data-label="Status">
                  <?php
                    $status = $thesis['status'];
                    $badgeClass = $status === 'approved' ? 'badge-approved' : ($status === 'pending' ? 'badge-pending' : 'badge-archived');
                  ?>
                  <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                </td>
                <td data-label="Actions">
                  <div class="tbl-actions">
                    <button class="tbl-btn tbl-btn-view" onclick="viewThesis(<?php echo $thesis['id']; ?>)" title="View"><i class="fas fa-eye"></i></button>
                    <button class="tbl-btn tbl-btn-edit" onclick="editThesis(<?php echo $thesis['id']; ?>)" title="Edit"><i class="fas fa-pen"></i></button>
                    <button class="tbl-btn tbl-btn-del" onclick="deleteThesis(<?php echo $thesis['id']; ?>, '<?php echo htmlspecialchars($thesis['title'], ENT_QUOTES); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="7"><div class="empty-msg"><i class="fas fa-inbox"></i><p>No theses found</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â USERS PAGE Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <div class="page" id="page-users">
      <!-- Live Pending Monitor Panel -->
      <div class="panel monitor-panel" id="liveMonitor">
        <div class="panel-header" style="background: rgba(230, 126, 34, 0.05); border-bottom: 1px solid var(--border-light);">
          <div class="panel-title" style="display: flex; align-items: center; gap: 10px;">
            <div class="pulse-indicator"></div>
            Pending Instructor/Admin Requests
            <span class="monitor-count-badge empty" id="monitorBadge">0</span>
          </div>
        </div>
        <div class="monitor-body" id="pendingMonitorBody">
          <div class="monitor-empty"><i class="fas fa-circle-notch fa-spin"></i><p>Loading live data...</p></div>
        </div>
      </div>

      <div class="panel">
        <div class="    ">
          <div class="panel-title"><i class="fas fa-users"></i> All Users</div>
        </div>
        <div class="filter-row">
          <input type="text" id="userSearchInput" placeholder="Search by name, student ID, or email..." aria-label="Search users">
        </div>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr>
              <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllUsers" onclick="toggleSelectAll('user')"></th>
              <th>Student ID</th><th>Name</th><th>Email</th><th>Role</th><th>Course</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
              <?php
              $users_page = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");
              if ($users_page && $users_page->num_rows > 0):
                while ($u = $users_page->fetch_assoc()): ?>
              <tr>
                <td style="text-align: center;" data-label="Select">
                  <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <input type="checkbox" class="select-user" value="<?php echo $u['id']; ?>" onclick="updateBulkActionBar('user')">
                  <?php endif; ?>
                </td>
                <td data-label="Student ID" style="font-family:'DM Mono',monospace;font-size:12px"><?php echo htmlspecialchars($u['student_id']); ?></td>
                <td data-label="Name" style="font-weight:700"><?php echo htmlspecialchars($u['full_name']); ?></td>
                <td data-label="Email" style="font-size:11.5px;color:var(--gray-500)"><?php echo htmlspecialchars($u['email']); ?></td>
                <td data-label="Role"><?php echo ucfirst($u['user_role'] ?? 'student'); ?></td>
                <td data-label="Course"><span class="course-tag"><?php echo htmlspecialchars($u['course']); ?></span></td>
                <td data-label="Status">
                  <span class="badge badge-<?php echo $u['account_status'] === 'active' ? 'approved' : ($u['account_status'] === 'pending' ? 'pending' : 'archived'); ?>">
                    <?php echo ucfirst($u['account_status']); ?>
                  </span>
                </td>
                <td data-label="Actions">
                  <div class="tbl-actions">
                    <button class="tbl-btn tbl-btn-edit" onclick="openEditUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['student_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['course'], ENT_QUOTES); ?>', '<?php echo $u['account_status']; ?>', '<?php echo $u['user_role'] ?? 'student'; ?>')" title="Edit"><i class="fas fa-pen"></i></button>
                    <?php if ($u['account_status'] === 'pending'): ?>
                    <button class="tbl-btn tbl-btn-approve" onclick="verifyUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')" title="Verify"><i class="fas fa-check"></i></button>
                    <?php endif; ?>
                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <button class="tbl-btn tbl-btn-del" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="8"><div class="empty-msg"><i class="fas fa-inbox"></i><p>No users found</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â ACCESS REQUESTS PAGE Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <div class="page" id="page-requests">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-inbox"></i> All Access Requests</div>
        </div>
        <div class="filter-row">
          <input type="text" id="accessSearchInput" placeholder="Search requests by name or thesis title..." aria-label="Search access requests">
        </div>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Request ID</th><th>Student Name</th><th>Requested For</th><th>Thesis Title</th><th>Requested Date</th><th>Actions</th></tr></thead>
            <tbody>
              <?php
              $access_page = $conn->query("
                SELECT * FROM (
                    SELECT ta.id, ta.user_id, ta.thesis_id, NOW() as requested_at, CAST(ta.status AS CHAR) as status, 
                           u.full_name, u.student_id, t.title, CAST('Thesis Access' AS CHAR) as request_type 
                    FROM thesis_access ta 
                    JOIN users u ON ta.user_id = u.id 
                    JOIN thesis t ON ta.thesis_id = t.id 
                    WHERE ta.status = 'pending'
                    
                    UNION ALL
                    
                    SELECT car.id, car.user_id, car.thesis_id, car.requested_at, CAST(car.status AS CHAR) as status,
                           u.full_name, u.student_id, t.title, CAST('Chatbot Access' AS CHAR) as request_type
                    FROM chatbot_access_requests car
                    JOIN users u ON car.user_id = u.id
                    JOIN thesis t ON car.thesis_id = t.id
                    WHERE car.status = 'pending'
                ) as combined_requests
                ORDER BY requested_at DESC 
                LIMIT 20
              ");
              if ($access_page && $access_page->num_rows > 0):
                while ($request = $access_page->fetch_assoc()): ?>
              <tr>
                <td data-label="Request ID" style="font-family:'DM Mono',monospace;font-size:12px">REQ<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td data-label="Student Name" style="font-weight:700"><?php echo htmlspecialchars($request['full_name']); ?></td>
                <td data-label="Requested For"><span class="badge badge-pending"><?php echo $request['request_type']; ?></span></td>
                <td data-label="Thesis Title"><div class="thesis-title"><?php echo htmlspecialchars(substr($request['title'], 0, 40)); ?></div></td>
                <td data-label="Requested Date" style="font-family:'DM Mono',monospace;font-size:11.5px"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                <td data-label="Actions">
                  <div class="tbl-actions">
                    <button class="tbl-btn tbl-btn-approve" onclick="approveAccess(<?php echo $request['id']; ?>, <?php echo $request['user_id']; ?>, <?php echo $request['thesis_id']; ?>, '<?php echo htmlspecialchars($request['full_name'], ENT_QUOTES); ?>', '<?php echo $request['request_type']; ?>')" title="Approve"><i class="fas fa-check"></i></button>
                    <button class="tbl-btn tbl-btn-del" onclick="rejectAccess(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name'], ENT_QUOTES); ?>', '<?php echo $request['request_type']; ?>')" title="Reject"><i class="fas fa-times"></i></button>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="6"><div class="empty-msg"><i class="fas fa-inbox"></i><p>No pending access requests</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /content -->

  <!-- Bulk Action Bar -->
  <div id="bulkActionBar" class="bulk-action-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid var(--border-light); z-index: 1000; align-items: center; gap: 20px;">
    <span id="bulkActionCount" style="font-weight: 600; color: var(--text-dark);">0 items selected</span>
    <button id="bulkDeleteBtn" class="btn-danger" style="padding: 8px 16px; border-radius: 20px; border: none; background: var(--red); color: white; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500;">
      <i class="fas fa-trash"></i> Delete Selected
    </button>
  </div>

</div><!-- /main-wrap -->

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Full Name</strong></label>
                        <input type="text" class="form-control" id="editUserName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Email</strong></label>
                        <input type="email" class="form-control" id="editUserEmail">
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label"><strong>Account ID / Username</strong></label>
                        <input type="text" class="form-control" id="editUserStudentId">
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label"><strong>Course</strong></label>
                        <input type="text" class="form-control" id="editUserCourse">
                    </div>
                    <div class="col-12 mt-3 text-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="saveUserProfileFromModal()">
                            <i class="fas fa-save me-1"></i>Save Profile
                        </button>
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

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label"><strong><i class="fas fa-user-tag me-1"></i>User Role</strong></label>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select" id="editUserRole" style="max-width: 200px;">
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm" id="saveRoleBtn" onclick="changeUserRoleFromModal()">
                                <i class="fas fa-save me-1"></i>Save Role
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">Change the user's role between Student, Instructor, and Admin.</small>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3"><i class="fas fa-tools me-2"></i>Account Management Actions</h6>
                
                <div id="actionButtonsContainer" class="d-grid gap-2">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewThesisModal" tabindex="-1" aria-labelledby="viewThesisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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
                        <p id="viewThesisTitle" style="color: var(--orange); font-weight: 600;"></p>
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
                    <p id="viewThesisDescription" style="border: 1px solid var(--gray-100); padding: 1rem; border-radius: 6px; background: var(--orange-50); max-height: 300px; overflow-y: auto;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editThesisModal" tabindex="-1" aria-labelledby="editThesisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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
});

// Sidebar toggle for mobile
window.toggleSidebar = function() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
};

window.closeSidebarMobile = function() {
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
    }
};

// Page navigation (replaces old tab system)
window.showPage = function(name, navEl) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const page = document.getElementById('page-' + name);
    if (page) page.classList.add('active');

    if (navEl) {
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        navEl.classList.add('active');
    }

    const titles = { dashboard: 'Admin <span>Dashboard</span>', theses: 'Theses', users: 'Users', requests: 'Access Requests' };
    const titleEl = document.querySelector('.topbar-title');
    if (titleEl) titleEl.innerHTML = titles[name] || name;
};

// Populate dashboard panels on load
document.addEventListener('DOMContentLoaded', function() {
    // Recent Theses for dashboard
    const thesesBody = document.getElementById('dashRecentTheses');
    const fullThesesRows = document.querySelectorAll('#page-theses .data-table tbody tr');
    if (thesesBody && fullThesesRows.length) {
        const limit = Math.min(fullThesesRows.length, 5);
        for (let i = 0; i < limit; i++) {
            const row = fullThesesRows[i];
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const tr = document.createElement('tr');
                // Title+author, Course, Year (from Course/Year), Status, Actions
                const titleTd = cells[1]; // Title
                const courseYearTd = cells[3]; // Course / Year
                const courseTag = courseYearTd.querySelector('.course-tag');
                const yearSpan = courseYearTd.querySelector('span[style]');
                tr.innerHTML = `
                    <td>${titleTd.innerHTML}</td>
                    <td>${courseTag ? courseTag.outerHTML : ''}</td>
                    <td>${yearSpan ? yearSpan.textContent : ''}</td>
                    <td>${cells[4].innerHTML}</td>
                    <td>${cells[5].innerHTML}</td>`;
                thesesBody.appendChild(tr);
            }
        }
    }

    // Recent Users for dashboard
    const usersDiv = document.getElementById('dashRecentUsers');
    const fullUserRows = document.querySelectorAll('#page-users .data-table tbody tr');
    if (usersDiv && fullUserRows.length) {
        const colors = ['av-orange','av-blue','av-teal','av-purple','av-pink','av-yellow'];
        const limit = Math.min(fullUserRows.length, 5);
        for (let i = 0; i < limit; i++) {
            const cells = fullUserRows[i].querySelectorAll('td');
            if (cells.length >= 6) {
                const name = cells[1]?.textContent?.trim() || '?';
                const course = cells[4]?.textContent?.trim() || '';
                const statusEl = cells[5]?.querySelector('.badge');
                const statusHtml = statusEl ? statusEl.outerHTML : (cells[5]?.textContent?.trim() || '');
                const initial = name.charAt(0).toUpperCase();
                const div = document.createElement('div');
                div.className = 'user-row';
                div.innerHTML = '<div class="user-av ' + colors[i % colors.length] + '">' + initial + '</div><div class="user-info"><div class="user-name">' + name + '</div><div class="user-course">' + course + '</div></div><span style="flex-shrink:0">' + statusHtml + '</span>';
                usersDiv.appendChild(div);
            }
        }
    }

    // Access Requests for dashboard
    const reqsDiv = document.getElementById('dashAccessReqs');
    const fullReqRows = document.querySelectorAll('#page-requests .data-table tbody tr');
    if (reqsDiv && fullReqRows.length) {
        const colors = ['av-orange','av-blue','av-teal','av-purple','av-pink'];
        const limit = Math.min(fullReqRows.length, 4);
        for (let i = 0; i < limit; i++) {
            const cells = fullReqRows[i].querySelectorAll('td');
            if (cells.length >= 6) {
                const name = cells[1]?.textContent?.trim() || '?';
                const requestedFor = cells[2]?.textContent?.trim() || '';
                const thesis = cells[3]?.textContent?.trim() || '';
                const date = cells[4]?.textContent?.trim() || '';
                const initial = name.charAt(0).toUpperCase();
                const actions = cells[5]?.innerHTML || '';
                const item = document.createElement('div');
                item.className = 'request-item';
                item.innerHTML = `<div class="req-top"><div class="req-avatar ${colors[i % colors.length]}">${initial}</div><div><div class="req-name" style="display:flex;align-items:center;gap:6px;">${name} <span class="badge badge-pending" style="font-size:10px;padding:2px 6px;">${requestedFor}</span></div><div class="req-thesis">${thesis}</div></div><div class="req-time">${date}</div></div><div class="req-actions">${actions}</div>`;
                reqsDiv.appendChild(item);
            }
        }
        if (fullReqRows.length === 0 || (fullReqRows.length === 1 && fullReqRows[0].querySelector('.empty-msg'))) {
            reqsDiv.innerHTML = '<div class="empty-msg"><i class="fas fa-inbox"></i><p>No pending requests</p></div>';
        }
    }

    // Helper for relative time
    function timeAgo(dateParam) {
        if (!dateParam) {
            return null;
        }

        const date = typeof dateParam === 'object' ? dateParam : new Date(dateParam);
        const TODAY = new Date();
        const seconds = Math.round((TODAY - date) / 1000);
        const minutes = Math.round(seconds / 60);
        const isToday = TODAY.toDateString() === date.toDateString();

        if (seconds < 5) {
            return 'just now';
        } else if (seconds < 60) {
            return `${ seconds } sec ago`;
        } else if (seconds < 90) {
            return 'a min ago';
        } else if (minutes < 60) {
            return `${ minutes } mins ago`;
        } else if (isToday) {
            return 'today';
        }

        return date.toLocaleDateString();
    }

    // Fetch and render Admin Activity Logs
    const activityTable = document.getElementById('activityLogTable');
    const activityBody = document.getElementById('dashActivityLogBody');
    const activityEmpty = document.getElementById('dashActivityLogEmpty');
    
    if (activityTable && activityBody && activityEmpty) {
        fetch('admin_includes/admin_get_activity_logs.php?limit=5')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.count > 0) {
                    activityTable.style.display = 'table';
                    activityEmpty.style.display = 'none';
                    activityBody.innerHTML = '';
                    const colors = ['av-orange', 'av-blue', 'av-teal', 'av-purple', 'av-pink'];
                    
                    data.logs.forEach((log, index) => {
                        const adminName = log.admin_name || 'Admin';
                        const initial = adminName.charAt(0).toUpperCase();
                        const colorClass = colors[index % colors.length];
                        
                        let actionType = log.action;
                        let targetEntity = '';

                        // Parse action text to split into Action and Target Entity
                        const actionLower = log.action.toLowerCase();
                        if (actionLower.includes('deleted user')) {
                            actionType = 'Deleted User';
                            targetEntity = log.action.replace(/deleted user:?\s*/i, '').trim();
                        } else if (actionLower.includes('changed role from')) {
                            actionType = 'Change Role';
                            targetEntity = log.action;
                        } else if (actionLower.includes('suspended user')) {
                            actionType = 'Suspended User';
                            targetEntity = log.action.replace(/suspended user:?\s*/i, '').trim();
                        } else if (actionLower.includes('activated user')) {
                            actionType = 'Activated User';
                            targetEntity = log.action.replace(/activated user:?\s*/i, '').trim();
                        } else if (actionLower.includes('deleted thesis')) {
                            actionType = 'Deleted Thesis';
                            targetEntity = log.action.replace(/deleted thesis:?\s*/i, '').trim();
                        } else if (actionLower.includes('updated thesis')) {
                            actionType = 'Updated Thesis';
                            targetEntity = log.action.replace(/updated thesis:?\s*/i, '').trim();
                        } else if (actionLower.includes('approved access for')) {
                            actionType = 'Approved Access';
                            targetEntity = log.action.replace(/approved access for:?\s*/i, '').trim();
                        } else if (actionLower.includes('denied access for')) {
                            actionType = 'Denied Access';
                            targetEntity = log.action.replace(/denied access for:?\s*/i, '').trim();
                        } else {
                            targetEntity = log.action;
                        }

                        // Parse timestamp for Safari/iOS compatibility
                        let dateStr = '';
                        let timeStr = '';
                        if (log.timestamp) {
                            let timestamp = log.timestamp.replace(/-/g, "/");
                            const dateObj = new Date(timestamp);
                            if (!isNaN(dateObj.getTime())) {
                                dateStr = `${dateObj.getMonth() + 1}/${dateObj.getDate()}/${dateObj.getFullYear()}`;
                                
                                let hours = dateObj.getHours();
                                let minutes = dateObj.getMinutes();
                                hours = hours % 12;
                                hours = hours ? hours : 12; // the hour '0' should be '12'
                                minutes = minutes < 10 ? '0' + minutes : minutes;
                                timeStr = `${hours}:${minutes}`;
                            }
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="user-av ${colorClass}" style="flex-shrink:0">${initial}</div>
                                    <span style="font-weight:400; color:var(--text-dark); font-size:14px;">${adminName}</span>
                                </div>
                            </td>
                            <td><span style="font-weight:600; color:var(--text-dark)">${actionType}</span></td>
                            <td style="white-space:normal; max-width:200px;">${targetEntity}</td>
                            <td>${dateStr}</td>
                            <td>${timeStr}</td>
                        `;
                        activityBody.appendChild(tr);
                    });
                } else {
                    activityTable.style.display = 'none';
                    activityEmpty.style.display = 'block';
                    activityEmpty.innerHTML = '<div class="empty-msg"><i class="fas fa-history"></i><p>No recent activity</p></div>';
                }
            })
            .catch(error => {
                console.error('Error fetching activity logs:', error);
                activityTable.style.display = 'none';
                activityEmpty.style.display = 'block';
                activityEmpty.innerHTML = '<div class="empty-msg"><i class="fas fa-exclamation-triangle"></i><p>Failed to load activity logs</p></div>';
            });
    }
});

// ===== SEARCH & FILTER FUNCTIONALITY =====

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Filter Thesis Table
function filterThesisTable(searchTerm) {
    const rows = document.querySelectorAll('#page-theses table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        // Skip the empty state row
        if (row.querySelector('.empty-state')) {
            return;
        }

        // Get searchable cell values
        const id = row.querySelector('[data-label="ID"]')?.textContent || '';
        const title = row.querySelector('[data-label="Title"]')?.textContent || '';
        const author = row.querySelector('[data-label="Author"]')?.textContent || '';
        const courseYear = row.querySelector('[data-label="Course/Year"]')?.textContent || '';

        // Combine all searchable content
        const searchContent = (id + ' ' + title + ' ' + author + ' ' + courseYear).toLowerCase();

        // Case-insensitive partial match
        if (searchContent.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "no results" message
    showNoResultsMessage('#page-theses table tbody', visibleCount);
}

// Filter User Table
function filterUserTable(searchTerm) {
    const rows = document.querySelectorAll('#page-users table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        // Skip the empty state row
        if (row.querySelector('.empty-state')) {
            return;
        }

        // Get searchable cell values
        const studentId = row.querySelector('[data-label="Student ID"]')?.textContent || '';
        const name = row.querySelector('[data-label="Name"]')?.textContent || '';
        const email = row.querySelector('[data-label="Email"]')?.textContent || '';
        const course = row.querySelector('[data-label="Course"]')?.textContent || '';

        // Combine all searchable content
        const searchContent = (studentId + ' ' + name + ' ' + email + ' ' + course).toLowerCase();

        // Case-insensitive partial match
        if (searchContent.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "no results" message
    showNoResultsMessage('#page-users table tbody', visibleCount);
}

// Filter Access Requests Table
function filterAccessTable(searchTerm) {
    const rows = document.querySelectorAll('#page-requests table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        // Skip the empty state row
        if (row.querySelector('.empty-state')) {
            return;
        }

        // Get searchable cell values
        const requestId = row.querySelector('[data-label="Request ID"]')?.textContent || '';
        const studentName = row.querySelector('[data-label="Student Name"]')?.textContent || '';
        const thesisTitle = row.querySelector('[data-label="Thesis Title"]')?.textContent || '';

        // Combine all searchable content
        const searchContent = (requestId + ' ' + studentName + ' ' + thesisTitle).toLowerCase();

        // Case-insensitive partial match
        if (searchContent.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "no results" message
    showNoResultsMessage('#page-requests table tbody', visibleCount);
}

// Show/Hide "No Results" Message
function showNoResultsMessage(tableBodySelector, visibleCount) {
    const tableBody = document.querySelector(tableBodySelector);
    if (!tableBody) return;

    // Remove existing "no results" row if present
    const existingNoResults = tableBody.querySelector('.no-results-row');
    if (existingNoResults) {
        existingNoResults.remove();
    }

    // If no results, add a "no results" message
    if (visibleCount === 0) {
        const colCount = tableBody.parentElement.querySelector('thead tr').children.length;
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = `
            <td colspan="${colCount}" class="text-center py-4">
                <div style="color: var(--text-gray); font-size: 0.95rem;">
                    <i class="fas fa-search" style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem; display: block;"></i>
                    No matching records found
                </div>
            </td>
        `;
        tableBody.appendChild(noResultsRow);
    }
}

// Initialize search inputs with debounced event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Thesis search
    const thesisSearch = document.getElementById('thesisSearchInput');
    if (thesisSearch) {
        thesisSearch.addEventListener('input', debounce(function(e) {
            filterThesisTable(e.target.value);
        }, 300));
    }

    // User search
    const userSearch = document.getElementById('userSearchInput');
    if (userSearch) {
        userSearch.addEventListener('input', debounce(function(e) {
            filterUserTable(e.target.value);
        }, 300));
    }

    // Access request search
    const accessSearch = document.getElementById('accessSearchInput');
    if (accessSearch) {
        accessSearch.addEventListener('input', debounce(function(e) {
            filterAccessTable(e.target.value);
        }, 300));
    }
});

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
window.approveAccess = function(requestId, userId, thesisId, userName, requestType) {
    if (!confirm(`Approve ${requestType} for "${userName}"?`)) return;
    
    console.log('Approving:', { requestId, userId, thesisId, requestType });
    
    fetch('admin_includes/admin_approve_access.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'request_id=' + requestId + '&user_id=' + userId + '&thesis_id=' + thesisId + '&type=' + encodeURIComponent(requestType)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Approve response:', data);
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Approve error:', error);
        alert('Error approving access: ' + error.message);
    });
}

// Reject Access Request
window.rejectAccess = function(requestId, userName, requestType) {
    if (!confirm(`Reject ${requestType} for "${userName}"?`)) return;
    
    console.log('Rejecting:', { requestId, requestType });
    
    fetch('admin_includes/admin_deny_access.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'request_id=' + requestId + '&type=' + encodeURIComponent(requestType)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Reject response:', data);
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
window._editUserId = null;
window._editUserName = null;
window.openEditUserModal = function(userId, fullName, email, studentId, course, status, role) {
    window._editUserId = userId;
    window._editUserName = fullName;
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

    // Set role dropdown
    const roleSelect = document.getElementById('editUserRole');
    if (roleSelect) {
        roleSelect.value = role || 'student';
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

// Save User Profile Changes from modal
window.saveUserProfileFromModal = function() {
    const userId = window._editUserId;
    const fullName = document.getElementById('editUserName').value.trim();
    const email = document.getElementById('editUserEmail').value.trim();
    const studentId = document.getElementById('editUserStudentId').value.trim();
    const course = document.getElementById('editUserCourse').value.trim();

    if (!userId) {
        alert('Error: No user selected');
        return;
    }

    if (!fullName || !email || !studentId) {
        alert('Name, Email, and Account ID are required.');
        return;
    }

    const btn = document.querySelector('button[onclick="saveUserProfileFromModal()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    const formData = new URLSearchParams();
    formData.append('user_id', userId);
    formData.append('full_name', fullName);
    formData.append('email', email);
    formData.append('student_id', studentId);
    formData.append('course', course);

    fetch('admin_includes/admin_update_user_profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            alert(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload(); // Reload to reflect changes in the table
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Update profile error:', error);
        alert('Error saving user profile: ' + error.message);
    });
}

// Change user role from modal
window.changeUserRoleFromModal = function() {
    const userId = window._editUserId;
    const userName = window._editUserName;
    const newRole = document.getElementById('editUserRole').value;

    if (!userId) {
        alert('Error: No user selected');
        return;
    }

    if (!confirm(`Change role for "${userName}" to ${newRole.charAt(0).toUpperCase() + newRole.slice(1)}?`)) return;

    const btn = document.getElementById('saveRoleBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    fetch('admin_includes/admin_change_role.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId + '&new_role=' + encodeURIComponent(newRole)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
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
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Change role error:', error);
        alert('Error changing user role: ' + error.message);
    });
}

// Delete user
window.deleteUser = function(userId, userName) {
    if (!confirm(`Are you sure you want to DELETE the user "${userName}"?\n\nThis will permanently remove their account and all associated data (favorites, notifications, chatbot sessions, etc.).\n\nThis action CANNOT be undone!`)) return;
    if (!confirm(`FINAL CONFIRMATION: Delete "${userName}" permanently?`)) return;

    fetch('admin_includes/admin_delete_user.php', {
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
        console.error('Delete user error:', error);
        alert('Error deleting user: ' + error.message);
    });
}

// ========== NOTIFICATION SYSTEM ==========

// Toggle notification panel
window.toggleNotificationPanel = function() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('open');
    if (dropdown.classList.contains('open')) {
        loadNotifications();
    }
}

// Close notification panel on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notifBtn') && !e.target.closest('#notificationDropdown')) {
        const panel = document.getElementById('notificationDropdown');
        if (panel) panel.classList.remove('open');
    }
});

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
            const unreadClass = notif.is_read ? '' : ' notif-unread';
            html += `
                <div class="notif-item${unreadClass}" onclick="markNotificationRead(${notif.id})">
                    <div style="flex:1">
                        <div class="notif-text">${escapeHtml(notif.title)}</div>
                        <div class="notif-sub">${escapeHtml(notif.message)}</div>
                        <div class="notif-t">${notif.time_ago}</div>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    } else {
        list.innerHTML = '<p style="padding:20px;text-align:center;color:var(--gray-500);font-size:13px">No notifications yet</p>';
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

// Auto-refresh notifications every 5 seconds (background polling for badge count)
setInterval(() => {
    // Always fetch to update badge count in background
    fetch('client_includes/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Always update badge count
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                // Only update the full list if panel is open
                const dropdown = document.getElementById('notificationDropdown');
                if (dropdown && dropdown.classList.contains('open')) {
                    updateNotificationDisplay(data);
                }
            }
        })
        .catch(() => {});
}, 5000);

// Live Pending Registrations Monitor
window.loadPendingRegistrations = function() {
    fetch('admin_includes/admin_get_pending_users.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('dashPendingRegistrationsBody');
            const emptyDiv = document.getElementById('dashPendingRegistrationsEmpty');
            const table = document.getElementById('pendingRegistrationsTable');
            
            // Dashboard elements
            const regCount = document.getElementById('pendingRegCount');
            
            // Monitor panel elements
            const monitorBody = document.getElementById('pendingMonitorBody');
            const monitorBadge = document.getElementById('monitorBadge');
            
            let count = 0;
            
            if (data.success && data.pending && data.pending.length > 0) {
                count = data.pending.length;
                
                // Update dashboard table
                if (tbody && emptyDiv && table) {
                    table.style.display = 'table';
                    emptyDiv.style.display = 'none';
                    
                    let html = '';
                    data.pending.forEach(user => {
                        const roleBadgeClass = user.user_role === 'admin' ? 'badge-archived' : 'badge-pending';
                        html += `
                            <tr>
                                <td data-label="NAME"><strong>${escapeHtml(user.full_name)}</strong></td>
                                <td data-label="EMAIL"><small>${escapeHtml(user.email)}</small></td>
                                <td data-label="ROLE REQUESTED">
                                    <span class="badge ${roleBadgeClass}">${(user.user_role || '').toUpperCase()}</span>
                                    ${user.loadsheet_file ? `<br><a href="${escapeHtml(user.loadsheet_file)}" target="_blank" style="font-size: 11px; margin-top: 5px; display: inline-block; color: var(--primary);"><i class="fas fa-image"></i> View ID</a>` : ''}
                                </td>
                                <td data-label="ACTIONS">
                                    <div class="tbl-actions">
                                        <button class="tbl-btn tbl-btn-approve" onclick="verifyPendingUser(${user.id}, '${escapeHtml(user.full_name)}', '${user.user_role}')" title="Approve"><i class="fas fa-check"></i></button>
                                        <button class="tbl-btn tbl-btn-del" onclick="rejectPendingUser(${user.id}, '${escapeHtml(user.full_name)}')" title="Reject"><i class="fas fa-times"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
                
                // Update monitor panel
                if (monitorBody) {
                    let monitorHtml = '<div style="display: flex; flex-direction: column; gap: 10px; padding: 15px;">';
                    data.pending.forEach(user => {
                        const roleBadgeClass = user.user_role === 'admin' ? 'badge-archived' : 'badge-pending';
                        monitorHtml += `
                            <div style="background: white; border: 1px solid var(--border-light); border-radius: 6px; padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;">${escapeHtml(user.full_name)}</div>
                                    <div style="font-size: 12px; color: var(--gray-500);">${escapeHtml(user.email)}</div>
                                    <div style="margin-top: 5px;">
                                        <span class="badge ${roleBadgeClass}" style="font-size: 10px;">${(user.user_role || '').toUpperCase()}</span>
                                        ${user.loadsheet_file ? `<a href="${escapeHtml(user.loadsheet_file)}" target="_blank" style="font-size: 11px; margin-left: 8px; color: var(--primary);"><i class="fas fa-image"></i> View ID</a>` : ''}
                                    </div>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <button class="tbl-btn tbl-btn-approve" onclick="verifyPendingUser(${user.id}, '${escapeHtml(user.full_name)}', '${user.user_role}')" title="Approve" style="padding: 6px 10px;"><i class="fas fa-check"></i></button>
                                    <button class="tbl-btn tbl-btn-del" onclick="rejectPendingUser(${user.id}, '${escapeHtml(user.full_name)}')" title="Reject" style="padding: 6px 10px;"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        `;
                    });
                    monitorHtml += '</div>';
                    monitorBody.innerHTML = monitorHtml;
                }
            } else {
                // Empty state
                if (tbody && emptyDiv && table) {
                    table.style.display = 'none';
                    emptyDiv.style.display = 'block';
                    tbody.innerHTML = '';
                }
                
                if (monitorBody) {
                    monitorBody.innerHTML = '<div class="monitor-empty" style="padding: 20px; text-align: center; color: var(--gray-500);"><i class="fas fa-check-circle" style="font-size: 24px; color: var(--green); margin-bottom: 10px;"></i><p>No pending elevated role requests.</p></div>';
                }
            }
            
            // Update counts and badges
            if (regCount) {
                regCount.textContent = count;
            }
            
            if (monitorBadge) {
                monitorBadge.textContent = count;
                if (count > 0) {
                    monitorBadge.classList.remove('empty');
                } else {
                    monitorBadge.classList.add('empty');
                }
            }
        })
        .catch(error => console.error('Error loading pending registrations:', error));
};

window.verifyPendingUser = function(userId, name, role) {
    if (confirm(`Are you sure you want to approve ${name} for the ${role} role?`)) {
        fetch('admin_includes/admin_verify_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully approved ${name}.`);
                loadPendingRegistrations();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error verifying user:', error));
    }
};

window.rejectPendingUser = function(userId, name) {
    if (confirm(`Are you sure you want to reject and delete the request for ${name}?`)) {
        fetch('admin_includes/admin_delete_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully rejected ${name}.`);
                loadPendingRegistrations();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error rejecting user:', error));
    }
};

// Poll every 5 seconds
setInterval(window.loadPendingRegistrations, 5000);

// Bulk Selection Logic
let currentBulkContext = ''; // 'thesis' or 'user'

window.toggleSelectAll = function(type) {
    const isChecked = document.getElementById(type === 'thesis' ? 'selectAllTheses' : 'selectAllUsers').checked;
    const checkboxes = document.querySelectorAll('.select-' + type);
    checkboxes.forEach(cb => cb.checked = isChecked);
    updateBulkActionBar(type);
};

window.updateBulkActionBar = function(type) {
    currentBulkContext = type;
    const checkboxes = document.querySelectorAll('.select-' + type + ':checked');
    const actionBar = document.getElementById('bulkActionBar');
    const actionCount = document.getElementById('bulkActionCount');
    
    // Auto-check/uncheck the "select all" box
    const allCheckboxes = document.querySelectorAll('.select-' + type);
    const selectAllBox = document.getElementById(type === 'thesis' ? 'selectAllTheses' : 'selectAllUsers');
    if (selectAllBox && allCheckboxes.length > 0) {
        selectAllBox.checked = checkboxes.length === allCheckboxes.length;
    }
    
    if (checkboxes.length > 0) {
        actionBar.style.display = 'flex';
        actionCount.textContent = checkboxes.length + ' item' + (checkboxes.length > 1 ? 's' : '') + ' selected';
    } else {
        actionBar.style.display = 'none';
    }
};

document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
    const type = currentBulkContext;
    const checkboxes = document.querySelectorAll('.select-' + type + ':checked');
    if (checkboxes.length === 0) return;
    
    if (confirm(`Are you sure you want to permanently delete these ${checkboxes.length} selected ${type === 'thesis' ? 'theses' : 'users'}? This action cannot be undone.`)) {
        const ids = Array.from(checkboxes).map(cb => cb.value);
        
        fetch(`admin_includes/admin_bulk_delete_${type === 'thesis' ? 'theses' : 'users'}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully deleted ${data.deleted_count} items.`);
                location.reload(); // Reload to refresh tables and stats
            } else {
                alert('Error during bulk deletion: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Bulk delete error:', err);
            alert('A network error occurred during deletion.');
        });
    }
});

// Initial load when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin panel ready');
    window.loadNotifications();
    window.loadPendingRegistrations();
    
    // Hide bulk bar when changing tabs
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById('bulkActionBar').style.display = 'none';
            // Uncheck all boxes when changing tabs
            document.querySelectorAll('.select-thesis, .select-user, #selectAllTheses, #selectAllUsers').forEach(cb => cb.checked = false);
        });
    });
});
</script>

<?php include 'admin_includes/user_details_modal.html'; ?>
                            
</body>
</html>
<?php $conn->close(); ?>
