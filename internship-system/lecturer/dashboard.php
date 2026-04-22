<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$lecturer = getLecturerProfile($_SESSION['user_id']);
$lid      = $lecturer['id'] ?? 0;

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE lecturer_id=?");
$stmt->execute([$lid]);
$totalStudents = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM logbooks lb JOIN assignments a ON a.student_id=lb.student_id
     WHERE a.lecturer_id=? AND lb.status='pending'"
);
$stmt->execute([$lid]);
$pendingReviews = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM logbooks lb JOIN assignments a ON a.student_id=lb.student_id
     WHERE a.lecturer_id=? AND lb.status='approved'"
);
$stmt->execute([$lid]);
$approvedCount = $stmt->fetchColumn();

// Recent pending logbooks
$stmt = $pdo->prepare(
    "SELECT lb.*, s.registration_number, u.name as student_name
     FROM logbooks lb
     JOIN students s ON s.id=lb.student_id
     JOIN users u ON u.id=s.user_id
     JOIN assignments a ON a.student_id=lb.student_id
     WHERE a.lecturer_id=? AND lb.status='pending'
     ORDER BY lb.submitted_at DESC LIMIT 8"
);
$stmt->execute([$lid]);
$pendingLogs = $stmt->fetchAll();

// My students
$stmt = $pdo->prepare(
    "SELECT u.name, u.email, s.registration_number, s.course,
            (SELECT COUNT(*) FROM logbooks WHERE student_id=s.id) as log_count,
            (SELECT COUNT(*) FROM internships WHERE student_id=s.id AND status='active') as has_internship
     FROM assignments a
     JOIN students s ON s.id=a.student_id
     JOIN users u ON u.id=s.user_id
     WHERE a.lecturer_id=? ORDER BY u.name"
);
$stmt->execute([$lid]);
$myStudents = $stmt->fetchAll();

startLayout('Dashboard', 'Lecturer Dashboard', 'Overview', 'dashboard');
?>

<div class="stats-grid fade-up">
  <div class="stat-card fade-up-1">
    <div class="stat-icon navy"><i class="fa fa-users"></i></div>
    <div><div class="stat-val"><?= $totalStudents ?></div><div class="stat-label">Assigned Students</div></div>
  </div>
  <div class="stat-card fade-up-2">
    <div class="stat-icon amber"><i class="fa fa-clock"></i></div>
    <div><div class="stat-val"><?= $pendingReviews ?></div><div class="stat-label">Pending Reviews</div></div>
  </div>
  <div class="stat-card fade-up-3">
    <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
    <div><div class="stat-val"><?= $approvedCount ?></div><div class="stat-label">Approved Entries</div></div>
  </div>
  <div class="stat-card fade-up-4">
    <div class="stat-icon teal"><i class="fa fa-building"></i></div>
    <div>
      <div class="stat-val"><?= array_sum(array_column($myStudents, 'has_internship')) ?></div>
      <div class="stat-label">Active Internships</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Pending logbooks -->
  <div class="card fade-up">
    <div class="card-header">
      <h3><i class="fa fa-inbox" style="color:var(--amber);margin-right:6px;"></i>Pending Reviews</h3>
      <a href="/internship-system/lecturer/logbooks.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($pendingLogs): ?>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Student</th><th>Week</th><th>Submitted</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($pendingLogs as $log): ?>
            <tr>
              <td>
                <div class="td-name"><?= htmlspecialchars($log['student_name']) ?></div>
                <div class="td-sub"><?= htmlspecialchars($log['registration_number']) ?></div>
              </td>
              <td><strong>Week <?= $log['week_number'] ?></strong></td>
              <td class="text-muted"><?= ago($log['submitted_at']) ?></td>
              <td>
                <a href="/internship-system/lecturer/review.php?id=<?= $log['id'] ?>"
                   class="btn btn-amber btn-xs">Review</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><div class="empty-icon">✅</div><p>All caught up! No pending reviews.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- My students -->
  <div class="card fade-up fade-up-2">
    <div class="card-header">
      <h3><i class="fa fa-user-graduate" style="color:var(--teal);margin-right:6px;"></i>My Students</h3>
      <a href="/internship-system/lecturer/students.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($myStudents): ?>
      <?php foreach (array_slice($myStudents, 0, 6) as $s): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);">
        <div style="width:38px;height:38px;border-radius:50%;background:var(--navy);color:var(--amber-lt);display:grid;place-items:center;font-weight:700;flex-shrink:0;">
          <?= strtoupper(substr($s['name'], 0, 1)) ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($s['name']) ?></div>
          <div class="td-sub"><?= htmlspecialchars($s['course']) ?></div>
        </div>
        <div style="text-align:right;">
          <div class="tag"><?= $s['log_count'] ?> logs</div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state"><div class="empty-icon">👥</div><p>No students assigned yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php endLayout(); ?>
