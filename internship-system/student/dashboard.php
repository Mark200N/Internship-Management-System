<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$student = getStudentProfile($_SESSION['user_id']);
$sid     = $student['id'] ?? 0;

// Stats
$stmt = $pdo->prepare("SELECT * FROM internships WHERE student_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$sid]);
$internship = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logbooks WHERE student_id=?");
$stmt->execute([$sid]);
$totalLogs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logbooks WHERE student_id=? AND status='approved'");
$stmt->execute([$sid]);
$approvedLogs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logbooks WHERE student_id=? AND status='pending'");
$stmt->execute([$sid]);
$pendingLogs = $stmt->fetchColumn();

// Recent logbooks
$stmt = $pdo->prepare("SELECT * FROM logbooks WHERE student_id=? ORDER BY submitted_at DESC LIMIT 5");
$stmt->execute([$sid]);
$recentLogs = $stmt->fetchAll();

// Supervisor
$stmt = $pdo->prepare(
    "SELECT u.name, u.email, l.department FROM assignments a
     JOIN lecturers l ON l.id=a.lecturer_id
     JOIN users u ON u.id=l.user_id
     WHERE a.student_id=? LIMIT 1"
);
$stmt->execute([$sid]);
$supervisor = $stmt->fetch();

// Progress
$totalWeeks = 12;
$pct = $totalWeeks > 0 ? round(($approvedLogs / $totalWeeks) * 100) : 0;

startLayout('Dashboard', 'My Dashboard', 'Overview', 'dashboard');
?>

<div class="stats-grid fade-up">
  <div class="stat-card fade-up-1">
    <div class="stat-icon amber"><i class="fa fa-book-open"></i></div>
    <div>
      <div class="stat-val"><?= $totalLogs ?></div>
      <div class="stat-label">Logbooks Submitted</div>
    </div>
  </div>
  <div class="stat-card fade-up-2">
    <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
    <div>
      <div class="stat-val"><?= $approvedLogs ?></div>
      <div class="stat-label">Approved</div>
    </div>
  </div>
  <div class="stat-card fade-up-3">
    <div class="stat-icon navy"><i class="fa fa-clock"></i></div>
    <div>
      <div class="stat-val"><?= $pendingLogs ?></div>
      <div class="stat-label">Pending Review</div>
    </div>
  </div>
  <div class="stat-card fade-up-4">
    <div class="stat-icon teal"><i class="fa fa-chart-line"></i></div>
    <div>
      <div class="stat-val"><?= $pct ?>%</div>
      <div class="stat-label">Progress</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Internship Card -->
  <div class="card fade-up">
    <div class="card-header">
      <h3><i class="fa fa-building" style="color:var(--amber);margin-right:6px;"></i>Current Internship</h3>
      <?php if (!$internship): ?>
      <a href="/internship-system/student/internship.php" class="btn btn-amber btn-sm">
        <i class="fa fa-plus"></i> Register
      </a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if ($internship): ?>
        <p class="form-section-title"><?= htmlspecialchars($internship['company_name']) ?></p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.86rem;">
          <div><span class="text-muted">Location:</span><br><strong><?= htmlspecialchars($internship['location']) ?></strong></div>
          <div><span class="text-muted">Status:</span><br><?= statusBadge($internship['status']) ?></div>
          <div><span class="text-muted">Start:</span><br><strong><?= htmlspecialchars($internship['start_date']) ?></strong></div>
          <div><span class="text-muted">End:</span><br><strong><?= htmlspecialchars($internship['end_date']) ?></strong></div>
        </div>
        <div class="mt-2">
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:5px;">
            <span>Progress (<?= $approvedLogs ?>/<?= $totalWeeks ?> weeks)</span>
            <strong><?= $pct ?>%</strong>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" data-pct="<?= $pct ?>"></div>
          </div>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">🏢</div>
          <h3>No Internship Registered</h3>
          <p>Submit your internship details to get started.</p>
          <a href="/internship-system/student/internship.php" class="btn btn-amber mt-2">Register Internship</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Supervisor Card -->
  <div class="card fade-up fade-up-2">
    <div class="card-header">
      <h3><i class="fa fa-user-tie" style="color:var(--teal);margin-right:6px;"></i>My Supervisor</h3>
    </div>
    <div class="card-body">
      <?php if ($supervisor): ?>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
          <div style="width:52px;height:52px;border-radius:12px;background:var(--navy);display:grid;place-items:center;color:var(--amber-lt);font-family:var(--font-head);font-size:1.2rem;font-weight:800;">
            <?= strtoupper(substr($supervisor['name'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:700;"><?= htmlspecialchars($supervisor['name']) ?></div>
            <div class="text-muted text-sm"><?= htmlspecialchars($supervisor['department']) ?></div>
          </div>
        </div>
        <div style="font-size:.86rem;">
          <div class="flex-center gap-1 mb-1">
            <i class="fa fa-envelope" style="color:var(--teal);width:16px;"></i>
            <?= htmlspecialchars($supervisor['email']) ?>
          </div>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">👨‍🏫</div>
          <h3>No Supervisor Assigned</h3>
          <p>Your supervisor will be assigned by the admin.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Recent Logbooks -->
<div class="card mt-3 fade-up">
  <div class="card-header">
    <h3><i class="fa fa-list-check" style="color:var(--navy-3);margin-right:6px;"></i>Recent Logbook Entries</h3>
    <a href="/internship-system/student/logbook.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="card-body">
    <?php if ($recentLogs): ?>
      <?php foreach ($recentLogs as $log): ?>
      <div class="logbook-item">
        <div class="logbook-week">W<?= $log['week_number'] ?></div>
        <div style="flex:1;">
          <div style="font-weight:600;"><?= htmlspecialchars($log['title'] ?: "Week {$log['week_number']} Entry") ?></div>
          <div class="logbook-meta"><?= ago($log['submitted_at']) ?></div>
        </div>
        <div><?= statusBadge($log['status']) ?></div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state" style="padding:24px;">
        <p>No logbook entries yet. <a href="/internship-system/student/logbook.php" style="color:var(--navy-3);">Submit your first entry →</a></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php endLayout(); ?>
