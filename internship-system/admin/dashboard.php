<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

// Platform stats
$stats = [];
$queries = [
    'students'    => "SELECT COUNT(*) FROM students",
    'lecturers'   => "SELECT COUNT(*) FROM lecturers",
    'internships' => "SELECT COUNT(*) FROM internships",
    'logbooks'    => "SELECT COUNT(*) FROM logbooks",
    'pending'     => "SELECT COUNT(*) FROM logbooks WHERE status='pending'",
    'approved'    => "SELECT COUNT(*) FROM logbooks WHERE status='approved'",
];
foreach ($queries as $key => $sql) {
    $stats[$key] = $pdo->query($sql)->fetchColumn();
}

// Recent users
$recentUsers = $pdo->query(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

// Recent logbooks
$recentLogs = $pdo->query(
    "SELECT lb.*, u.name as student_name, s.registration_number
     FROM logbooks lb JOIN students s ON s.id=lb.student_id JOIN users u ON u.id=s.user_id
     ORDER BY lb.submitted_at DESC LIMIT 6"
)->fetchAll();

// Monthly registrations (last 6 months)
$monthlyData = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%b') as mon, COUNT(*) as cnt
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at,'%Y-%m')
     ORDER BY created_at"
)->fetchAll();

startLayout('Admin Dashboard', 'Admin Dashboard', 'Overview', 'dashboard');
?>

<div class="stats-grid fade-up">
  <div class="stat-card fade-up-1">
    <div class="stat-icon navy"><i class="fa fa-user-graduate"></i></div>
    <div><div class="stat-val"><?= $stats['students'] ?></div><div class="stat-label">Students</div></div>
  </div>
  <div class="stat-card fade-up-2">
    <div class="stat-icon teal"><i class="fa fa-chalkboard-user"></i></div>
    <div><div class="stat-val"><?= $stats['lecturers'] ?></div><div class="stat-label">Lecturers</div></div>
  </div>
  <div class="stat-card fade-up-3">
    <div class="stat-icon amber"><i class="fa fa-briefcase"></i></div>
    <div><div class="stat-val"><?= $stats['internships'] ?></div><div class="stat-label">Internships</div></div>
  </div>
  <div class="stat-card fade-up-4">
    <div class="stat-icon green"><i class="fa fa-book"></i></div>
    <div><div class="stat-val"><?= $stats['logbooks'] ?></div><div class="stat-label">Logbook Entries</div></div>
  </div>
</div>

<!-- Second row -->
<div class="stats-grid fade-up" style="grid-template-columns:repeat(3,1fr);margin-top:0;margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa fa-clock"></i></div>
    <div><div class="stat-val"><?= $stats['pending'] ?></div><div class="stat-label">Pending Reviews</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
    <div><div class="stat-val"><?= $stats['approved'] ?></div><div class="stat-label">Approved Entries</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa fa-circle-xmark"></i></div>
    <div>
      <div class="stat-val"><?= $stats['logbooks'] - $stats['approved'] - $stats['pending'] ?></div>
      <div class="stat-label">Rejected Entries</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Recent Users -->
  <div class="card fade-up">
    <div class="card-header">
      <h3><i class="fa fa-users" style="color:var(--navy-3);margin-right:6px;"></i>Recent Registrations</h3>
      <a href="/internship-system/admin/users.php" class="btn btn-outline btn-sm">Manage Users</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php foreach ($recentUsers as $u): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid var(--border);">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--navy);color:var(--amber-lt);display:grid;place-items:center;font-weight:700;font-size:.8rem;flex-shrink:0;">
          <?= strtoupper(substr($u['name'],0,1)) ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($u['name']) ?></div>
          <div class="td-sub"><?= htmlspecialchars($u['email']) ?></div>
        </div>
        <div><?= statusBadge($u['role']) ?></div>
        <div class="text-muted" style="font-size:.75rem;"><?= ago($u['created_at']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Recent Logbooks -->
  <div class="card fade-up fade-up-2">
    <div class="card-header">
      <h3><i class="fa fa-book-open" style="color:var(--amber);margin-right:6px;"></i>Recent Logbooks</h3>
      <a href="/internship-system/admin/internships.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($recentLogs): ?>
      <?php foreach ($recentLogs as $log): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid var(--border);">
        <div class="logbook-week" style="width:38px;height:38px;font-size:.72rem;">W<?= $log['week_number'] ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;"><?= htmlspecialchars($log['student_name']) ?></div>
          <div class="td-sub"><?= htmlspecialchars($log['registration_number']) ?> · Week <?= $log['week_number'] ?></div>
        </div>
        <?= statusBadge($log['status']) ?>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state" style="padding:24px;"><p>No logbooks yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Quick actions -->
<div class="card mt-3 fade-up">
  <div class="card-header"><h3>Quick Actions</h3></div>
  <div class="card-body">
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="/internship-system/admin/users.php?action=new" class="btn btn-primary">
        <i class="fa fa-user-plus"></i> Add User
      </a>
      <a href="/internship-system/admin/assignments.php" class="btn btn-amber">
        <i class="fa fa-link"></i> Assign Lecturer
      </a>
      <a href="/internship-system/admin/reports.php" class="btn btn-teal">
        <i class="fa fa-chart-bar"></i> Generate Report
      </a>
      <a href="/internship-system/admin/internships.php" class="btn btn-outline">
        <i class="fa fa-briefcase"></i> View Internships
      </a>
    </div>
  </div>
</div>

<?php endLayout(); ?>
