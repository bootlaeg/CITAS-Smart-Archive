<?php
/**
 * Browse Thesis Page - Redesigned
 * CITAS Smart Archive System
 */
require_once 'db_includes/db_connect.php';
require_login();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$count_result = $conn->query("SELECT COUNT(*) as total FROM thesis WHERE status = 'approved'");
$total_theses = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_theses / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;

$stmt = $conn->prepare("SELECT * FROM thesis WHERE status = 'approved' ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Thesis - CITAS Smart Archive</title>
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index_redesign.css">
    <link rel="stylesheet" href="css/pages_redesign.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-container">
        <a href="index.php" class="logo">
            <img src="img/CITAS_logo.png" alt="CITAS">
            <span>CITAS Smart <em>Archive</em></span>
        </a>
        <nav class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <?php if (is_logged_in() && is_admin()): ?>
            <a href="admin.php" class="nav-link"><i class="fas fa-lock"></i> Admin Dashboard</a>
            <?php endif; ?>
            <?php if (!is_logged_in() || !is_admin()): ?>
            <a href="browse.php" class="nav-link" style="background:var(--orange-50);color:var(--orange);"><i class="fas fa-compass"></i> Browse</a>
            <a href="favorites.php" class="nav-link"><i class="fas fa-heart"></i> Favorites</a>
            <?php endif; ?>
            <div class="notification-center" id="notificationCenter">
                <a href="#" class="nav-link" onclick="event.preventDefault();toggleNotificationPanel()" title="Notifications">
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
                        <i class="fas fa-user" style="color:var(--orange);"></i>
                    <?php endif; ?>
                </div>
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </a>
            <a href="#" class="nav-link logout" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <button class="hamburger-menu" id="hamburgerMenu"><span></span><span></span><span></span></button>
    </div>
</header>

<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav-menu" id="mobileNavMenu">
    <div class="mobile-user-menu">
        <a href="my_profile.php" style="text-decoration:none;color:inherit;">
            <div class="profile-info">
                <div style="width:45px;height:45px;border-radius:50%;background:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size:1.5rem;color:var(--orange);"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </a>
        <button class="logout-btn" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
        <li><a href="browse.php" class="active"><i class="fas fa-compass"></i> Browse Thesis</a></li>
        <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
        <?php if (is_admin()): ?>
        <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Main Content -->
<div class="page-wrap">
    <div class="page-grid">
        <!-- Sidebar -->
        <aside class="page-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-card-title">Navigation</div>
                <ul class="sidebar-nav">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="browse.php" class="active"><i class="fas fa-compass"></i> Browse Thesis</a></li>
                    <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                    <?php if (is_admin()): ?>
                    <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <!-- Content -->
        <main>
            <div class="page-title-bar">
                <h1><i class="fas fa-compass"></i> Browse All Theses</h1>
                <p>Explore the complete collection of research papers from the CITAS repository</p>
            </div>

            <div class="search-panel">
                <h3><i class="fas fa-search"></i> Search Theses</h3>
                <div class="search-row">
                    <input type="text" id="mainSearchInput" placeholder="Search by title, author, keywords, course..." autocomplete="off">
                    <button onclick="performMainSearch()"><i class="fas fa-search"></i> Search</button>
                </div>
                <div id="searchSuggestions" style="margin-top:0.75rem;display:none;"></div>
            </div>

            <div id="searchStatus" style="display:none;padding:0.85rem;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:8px;margin-bottom:1rem;color:var(--blue);font-size:13px;font-weight:600;">
                <i class="fas fa-info-circle me-2"></i><span id="searchStatusText"></span>
            </div>

            <section id="thesisList" class="thesis-list">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($thesis = $result->fetch_assoc()): ?>
                    <div class="thesis-card" id="thesis-<?php echo $thesis['id']; ?>">
                        <h3 class="thesis-title"><?php echo htmlspecialchars($thesis['title']); ?></h3>
                        <div class="thesis-meta">
                            <div class="thesis-meta-item"><i class="fas fa-user"></i><span><strong>Author:</strong> <?php echo htmlspecialchars($thesis['author']); ?></span></div>
                            <div class="thesis-meta-item"><i class="fas fa-book"></i><span><?php echo htmlspecialchars($thesis['course']); ?></span></div>
                            <div class="thesis-meta-item"><i class="fas fa-calendar"></i><span><?php echo htmlspecialchars($thesis['year']); ?></span></div>
                            <div class="thesis-meta-item"><i class="fas fa-eye"></i><span><?php echo $thesis['views']; ?> views</span></div>
                        </div>
                        <p class="thesis-abstract"><?php echo htmlspecialchars(substr($thesis['abstract'], 0, 300)) . (strlen($thesis['abstract']) > 300 ? '...' : ''); ?></p>
                        <div class="thesis-actions">
                            <a href="view_thesis.php?id=<?php echo $thesis['id']; ?>" class="thesis-btn thesis-btn-primary"><i class="fas fa-eye"></i> View Full Details</a>
                            <button class="thesis-btn thesis-btn-secondary" onclick="addToFavorites(<?php echo $thesis['id']; ?>, '<?php echo htmlspecialchars($thesis['title'], ENT_QUOTES); ?>')"><i class="fas fa-bookmark"></i> Add to Favorites</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Theses Found</h3>
                        <p>There are currently no approved theses in the repository.</p>
                        <a href="index.php" class="thesis-btn thesis-btn-primary"><i class="fas fa-home"></i> Back to Home</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Pagination -->
            <div class="pagination-bar">
                <?php if ($total_pages > 0): ?>
                    <a href="?page=<?php echo max(1, $current_page - 1); ?>" class="page-btn" <?php echo ($current_page <= 1) ? 'style="pointer-events:none;opacity:0.4;"' : ''; ?>><i class="fas fa-chevron-left"></i> Prev</a>
                    <?php
                    $pages_to_show = 5;
                    $start_page = max(1, $current_page - floor($pages_to_show / 2));
                    $end_page = min($total_pages, $start_page + $pages_to_show - 1);
                    if ($end_page - $start_page < $pages_to_show - 1) $start_page = max(1, $end_page - $pages_to_show + 1);
                    if ($start_page > 1): ?>
                        <a href="?page=1" class="page-btn">1</a>
                        <?php if ($start_page > 2): ?><span class="page-btn" style="border:none;background:none;cursor:default;">...</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?><span class="page-btn" style="border:none;background:none;cursor:default;">...</span><?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>" class="page-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>" class="page-btn" <?php echo ($current_page >= $total_pages) ? 'style="pointer-events:none;opacity:0.4;"' : ''; ?>>Next <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php if ($total_pages > 0): ?>
            <div class="page-info">Page <strong><?php echo $current_page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (<?php echo $total_theses; ?> total theses)</div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script src="js/index_redesign.js"></script>
<script>
// Search
let searchTimeout;
function performMainSearch() {
    const term = document.getElementById('mainSearchInput').value.trim();
    if (!term) { clearSearch(); return; }
    performSearchQuery(term);
}
function performSearchQuery(term) {
    const list = document.getElementById('thesisList');
    const status = document.getElementById('searchStatus');
    const statusText = document.getElementById('searchStatusText');
    status.style.display = 'block'; statusText.textContent = 'Searching...';
    fetch(`client_includes/search_theses.php?q=${encodeURIComponent(term)}`)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.results.length > 0) {
            displaySearchResults(data); status.style.display = 'none';
        } else {
            displayNoResults(term);
            statusText.innerHTML = `No results found for "${escapeHtml(term)}"`;
        }
    }).catch(() => { statusText.textContent = 'Error performing search.'; });
}
function displaySearchResults(data) {
    const list = document.getElementById('thesisList'); list.innerHTML = '';
    list.innerHTML = `<div style="margin-bottom:1rem;"><div style="background:rgba(34,197,94,.08);border-left:4px solid var(--green);padding:0.85rem;border-radius:6px;color:#16A34A;font-size:13px;font-weight:600;"><i class="fas fa-check-circle me-2"></i>Found <strong>${data.count}</strong> result(s) for "<strong>${escapeHtml(data.query)}</strong>"</div></div>`;
    data.results.forEach(t => list.appendChild(createThesisCard(t)));
}
function createThesisCard(t) {
    const card = document.createElement('div'); card.className = 'thesis-card'; card.id = `thesis-${t.id}`;
    let kw = '';
    if (t.keywords && t.keywords.length > 0) {
        kw = '<div style="margin:0.5rem 0;"><strong style="color:var(--gray-500);font-size:12px;"><i class="fas fa-tag me-1"></i>Keywords:</strong> <span style="display:inline-flex;flex-wrap:wrap;gap:0.3rem;margin-top:0.25rem;">';
        t.keywords.forEach(k => { const txt = k.text||k; kw += `<span class="badge ${k.highlighted?'bg-warning text-dark':'bg-secondary'}" style="font-size:10px;">${escapeHtml(txt)}</span>`; });
        kw += '</span></div>';
    }
    card.innerHTML = `<h3 class="thesis-title">${escapeHtml(t.title)}</h3>
        <div class="thesis-meta"><div class="thesis-meta-item"><i class="fas fa-user"></i><span><strong>Author:</strong> ${escapeHtml(t.author)}</span></div><div class="thesis-meta-item"><i class="fas fa-book"></i><span>${escapeHtml(t.course)}</span></div><div class="thesis-meta-item"><i class="fas fa-calendar"></i><span>${t.year}</span></div><div class="thesis-meta-item"><i class="fas fa-eye"></i><span>${t.views} views</span></div></div>
        ${kw}<p class="thesis-abstract">${escapeHtml(t.abstract)}</p>
        <div class="thesis-actions"><a href="view_thesis.php?id=${t.id}" class="thesis-btn thesis-btn-primary"><i class="fas fa-eye"></i> View Details</a><button class="thesis-btn thesis-btn-secondary" onclick="addToFavorites(${t.id},'${escapeHtml(t.title)}')"><i class="fas fa-bookmark"></i> Favorites</button></div>`;
    return card;
}
function displayNoResults(term) {
    document.getElementById('thesisList').innerHTML = `<div class="empty-state"><i class="fas fa-search"></i><h3>No Results Found</h3><p>No theses match "<strong>${escapeHtml(term)}</strong>"</p><button class="thesis-btn thesis-btn-primary" onclick="clearSearch()"><i class="fas fa-redo"></i> Clear Search</button></div>`;
}
function clearSearch() { document.getElementById('mainSearchInput').value = ''; document.getElementById('searchStatus').style.display = 'none'; location.reload(); }
document.getElementById('mainSearchInput')?.addEventListener('input', function() { clearTimeout(searchTimeout); if (this.value.trim().length < 2) return; searchTimeout = setTimeout(() => performSearchQuery(this.value.trim()), 300); });
document.getElementById('mainSearchInput')?.addEventListener('keypress', e => { if (e.key==='Enter') performMainSearch(); });
function escapeHtml(t) { if (!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
function addToFavorites(id, title) {
    fetch('client_includes/add_to_favorites.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'thesis_id='+id})
    .then(r=>r.json()).then(d=>{alert(d.success?`"${title}" added to favorites!`:'Error: '+d.message);}).catch(()=>alert('Error adding to favorites'));
}
// Notifications
function toggleNotificationPanel(){const d=document.getElementById('notificationDropdown'),h=document.querySelector('header.header');if(d&&d.style.display==='none'){d.style.display='block';if(h)d.style.top=h.offsetHeight+'px';else d.style.top='64px';loadNotifications();}else if(d){d.style.display='none';}}
function loadNotifications(){fetch('client_includes/get_notifications.php').then(r=>r.json()).then(d=>{if(d.success)updateNotificationDisplay(d);}).catch(()=>{});}
function updateNotificationDisplay(data){const b=document.getElementById('notificationBadge'),l=document.getElementById('notificationList');if(!b||!l)return;if(data.unread_count>0){b.textContent=data.unread_count;b.style.display='flex';}else{b.style.display='none';}if(data.notifications.length>0){let h='';data.notifications.forEach(n=>{const rs=n.is_read?'':' style="background:var(--orange-50);border-left:3px solid var(--orange);"';h+=`<div${rs} style="padding:0.85rem;border-bottom:1px solid var(--gray-100);cursor:pointer;" onclick="markNotificationRead(${n.id})"><div style="display:flex;justify-content:space-between;"><div style="flex:1;"><div style="font-weight:700;color:var(--dark);font-size:13px;">${escapeHtml(n.title)}</div><div style="color:var(--gray-500);font-size:12px;margin-top:0.2rem;">${escapeHtml(n.message)}</div><div style="color:var(--gray-300);font-size:11px;margin-top:0.3rem;">${n.time_ago}</div></div>${n.is_read?'':'<div style="width:8px;height:8px;background:var(--orange);border-radius:50%;margin-left:0.5rem;flex-shrink:0;margin-top:0.5rem;"></div>'}</div></div>`;});l.innerHTML=h;}else{l.innerHTML='<p style="padding:1rem;text-align:center;color:var(--gray-500);font-size:13px;">No notifications</p>';}}
function markNotificationRead(id){fetch('client_includes/mark_notifications_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'notification_ids[]='+id}).then(r=>r.json()).then(d=>{if(d.success)loadNotifications();}).catch(()=>{});}
function markAllAsRead(){fetch('client_includes/mark_notifications_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:''}).then(r=>r.json()).then(d=>{if(d.success)loadNotifications();}).catch(()=>{});}
function clearAllNotifications(){if(!confirm('Clear all notifications?'))return;fetch('client_includes/clear_all_notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:''}).then(r=>r.json()).then(d=>{if(d.success)loadNotifications();}).catch(()=>{});}
setInterval(()=>{const d=document.getElementById('notificationDropdown');if(d&&d.style.display!=='none')loadNotifications();},10000);
document.addEventListener('DOMContentLoaded',()=>loadNotifications());
</script>
</body>
</html>
<?php $conn->close(); ?>
