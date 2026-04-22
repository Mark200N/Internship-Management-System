<?php
// includes/layout.php — shared layout helpers
require_once __DIR__ . '/../config/helpers.php';

$_currentUser = currentUser();
$_notifCount  = isLoggedIn() ? unreadNotificationCount($_SESSION['user_id']) : 0;

function startLayout(string $title, string $pageTitle = '', string $breadcrumb = '', string $activeNav = ''): void {
    global $_currentUser, $_notifCount;
    $role     = $_currentUser['role'] ?? '';
    $initials = strtoupper(substr($_currentUser['name'] ?? 'U', 0, 1));
    $flash    = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> — IMS</title>
  <link rel="stylesheet" href="/internship-system/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"></div>
<div class="app-shell">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fa fa-graduation-cap"></i></div>
      <div class="brand-name">Internship<br><span>Management</span></div>
    </div>

    <div class="sidebar-role-badge">
      <i class="fa fa-circle" style="font-size:.4rem;vertical-align:middle;margin-right:5px;"></i>
      <?= ucfirst($role) ?> Portal
    </div>

    <nav class="sidebar-nav">
      <?php if ($role === 'student'): ?>
        <div class="nav-section-label">Main</div>
        <div class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
          <a href="/internship-system/dashboard.php"><span class="nav-icon"><i class="fa fa-gauge"></i></span>Dashboard</a>
        </div>
        <div class="nav-item <?= $activeNav === 'internship' ? 'active' : '' ?>">
          <a href="/internship-system/student/internship.php"><span class="nav-icon"><i class="fa fa-building"></i></span>My Internship</a>
        </div>
        <div class="nav-item <?= $activeNav === 'logbook' ? 'active' : '' ?>">
          <a href="/internship-system/student/logbook.php"><span class="nav-icon"><i class="fa fa-book-open"></i></span>Logbook</a>
        </div>
        <div class="nav-item <?= $activeNav === 'documents' ? 'active' : '' ?>">
          <a href="/internship-system/student/documents.php"><span class="nav-icon"><i class="fa fa-file-arrow-up"></i></span>Documents</a>
        </div>
        <div class="nav-item <?= $activeNav === 'feedback' ? 'active' : '' ?>">
          <a href="/internship-system/student/feedback.php"><span class="nav-icon"><i class="fa fa-comments"></i></span>Feedback</a>
        </div>
        <div class="nav-section-label">Account</div>
        <div class="nav-item <?= $activeNav === 'profile' ? 'active' : '' ?>">
          <a href="/internship-system/student/profile.php"><span class="nav-icon"><i class="fa fa-user-pen"></i></span>Profile</a>
        </div>

      <?php elseif ($role === 'lecturer'): ?>
        <div class="nav-section-label">Main</div>
        <div class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
          <a href="/internship-system/dashboard.php"><span class="nav-icon"><i class="fa fa-gauge"></i></span>Dashboard</a>
        </div>
        <div class="nav-item <?= $activeNav === 'students' ? 'active' : '' ?>">
          <a href="/internship-system/lecturer/students.php"><span class="nav-icon"><i class="fa fa-users"></i></span>My Students</a>
        </div>
        <div class="nav-item <?= $activeNav === 'logbooks' ? 'active' : '' ?>">
          <a href="/internship-system/lecturer/logbooks.php"><span class="nav-icon"><i class="fa fa-book"></i></span>Review Logbooks</a>
        </div>
        <div class="nav-item <?= $activeNav === 'grades' ? 'active' : '' ?>">
          <a href="/internship-system/lecturer/grades.php"><span class="nav-icon"><i class="fa fa-star"></i></span>Grades</a>
        </div>
        <div class="nav-section-label">Account</div>
        <div class="nav-item <?= $activeNav === 'profile' ? 'active' : '' ?>">
          <a href="/internship-system/lecturer/profile.php"><span class="nav-icon"><i class="fa fa-user-pen"></i></span>Profile</a>
        </div>

      <?php elseif ($role === 'admin'): ?>
        <div class="nav-section-label">Overview</div>
        <div class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
          <a href="/internship-system/dashboard.php"><span class="nav-icon"><i class="fa fa-gauge"></i></span>Dashboard</a>
        </div>
        <div class="nav-section-label">Management</div>
        <div class="nav-item <?= $activeNav === 'users' ? 'active' : '' ?>">
          <a href="/internship-system/admin/users.php"><span class="nav-icon"><i class="fa fa-users-gear"></i></span>Users</a>
        </div>
        <div class="nav-item <?= $activeNav === 'assignments' ? 'active' : '' ?>">
          <a href="/internship-system/admin/assignments.php"><span class="nav-icon"><i class="fa fa-link"></i></span>Assignments</a>
        </div>
        <div class="nav-item <?= $activeNav === 'internships' ? 'active' : '' ?>">
          <a href="/internship-system/admin/internships.php"><span class="nav-icon"><i class="fa fa-briefcase"></i></span>Internships</a>
        </div>
        <div class="nav-item <?= $activeNav === 'reports' ? 'active' : '' ?>">
          <a href="/internship-system/admin/reports.php"><span class="nav-icon"><i class="fa fa-chart-bar"></i></span>Reports</a>
        </div>
        <div class="nav-section-label">System</div>
        <div class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
          <a href="/internship-system/admin/settings.php"><span class="nav-icon"><i class="fa fa-sliders"></i></span>Settings</a>
        </div>
      <?php endif; ?>

      <div class="nav-section-label">Session</div>
      <div class="nav-item">
        <a href="/internship-system/logout.php" data-confirm="Log out?">
          <span class="nav-icon"><i class="fa fa-arrow-right-from-bracket"></i></span>Logout
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-name"><?= htmlspecialchars($_currentUser['name'] ?? '') ?></div>
      <div><?= htmlspecialchars($_currentUser['email'] ?? '') ?></div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button id="sidebarToggle" class="btn-outline btn btn-sm" style="display:none;">☰</button>
        <h2><?= htmlspecialchars($pageTitle ?: $title) ?></h2>
        <?php if ($breadcrumb): ?>
        <p class="breadcrumb">Home / <span><?= htmlspecialchars($breadcrumb) ?></span></p>
        <?php endif; ?>
      </div>
      <div class="topbar-right">
        <!-- Notifications -->
        <div style="position:relative;">
          <button class="topbar-icon-btn" id="notifBtn" title="Notifications">
            <i class="fa fa-bell"></i>
            <?php if ($_notifCount > 0): ?>
            <span class="notif-dot"></span>
            <?php endif; ?>
          </button>
          <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-header">
              Notifications
              <a href="/internship-system/api/notifications.php?action=read_all" class="text-sm" style="color:var(--navy-3)">Mark all read</a>
            </div>
            <?php
              global $pdo;
              $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 8");
              $stmt->execute([$_SESSION['user_id']]);
              $notifs = $stmt->fetchAll();
              if ($notifs): foreach ($notifs as $n):
            ?>
            <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" data-id="<?= $n['id'] ?>">
              <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
              <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
              <div class="notif-time"><?= ago($n['created_at']) ?></div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state" style="padding:20px"><p>No notifications</p></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="topbar-avatar"><?= $initials ?></div>
      </div>
    </header>

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div style="padding:14px 28px 0;">
      <div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss>
        <?= htmlspecialchars($flash['msg']) ?>
        <button class="alert-close">✕</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Page content starts -->
    <main class="page-content">
<?php
}

function endLayout(): void {
?>
    </main>
  </div><!-- .main-content -->
</div><!-- .app-shell -->

<script src="/internship-system/assets/js/main.js"></script>
<style>
@media(max-width:900px){
  #sidebarToggle{display:flex!important;}
  #sidebarOverlay{display:block!important;}
}
</style>
</body>
</html>
<?php
}
?>
