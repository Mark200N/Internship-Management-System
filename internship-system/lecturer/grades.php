<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$lecturer = getLecturerProfile($_SESSION['user_id']);
$lid      = $lecturer['id'] ?? 0;

$stmt = $pdo->prepare(
    "SELECT lb.week_number, lb.title, lb.status, lb.grade, lb.reviewed_at,
            u.name as student_name, s.registration_number, s.course
     FROM logbooks lb
     JOIN students s ON s.id=lb.student_id
     JOIN users u ON u.id=s.user_id
     JOIN assignments a ON a.student_id=lb.student_id
     WHERE a.lecturer_id=? AND lb.grade IS NOT NULL
     ORDER BY u.name, lb.week_number"
);
$stmt->execute([$lid]);
$graded = $stmt->fetchAll();

// Grade distribution
$gradeDist = array_fill_keys(['A','B+','B','C+','C','D+','D','F'], 0);
foreach ($graded as $g) {
    if (isset($gradeDist[$g['grade']])) $gradeDist[$g['grade']]++;
}

startLayout('Grades', 'Grades Overview', 'Grades', 'grades');
?>

<div class="page-header">
  <div><h1>Grades</h1><p class="breadcrumb">Lecturer / <span>Grades</span></p></div>
</div>

<div class="grid-2 mb-3">
  <div class="card">
    <div class="card-header"><h3>Grade Distribution</h3></div>
    <div class="card-body">
      <?php foreach ($gradeDist as $grade => $count): ?>
      <?php if ($count === 0) continue; ?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
        <span style="width:36px;font-weight:700;font-family:var(--font-head);"><?= $grade ?></span>
        <div class="progress-bar" style="flex:1;">
          <div class="progress-fill" data-pct="<?= $count > 0 ? min(100, $count * 20) : 0 ?>" style="width:0;"></div>
        </div>
        <span class="tag"><?= $count ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>Summary</h3></div>
    <div class="card-body">
      <div class="stats-grid" style="grid-template-columns:1fr 1fr;">
        <div class="stat-card"><div class="stat-icon green"><i class="fa fa-star"></i></div>
          <div><div class="stat-val"><?= count($graded) ?></div><div class="stat-label">Graded</div></div>
        </div>
        <div class="stat-card"><div class="stat-icon amber"><i class="fa fa-users"></i></div>
          <div>
            <div class="stat-val"><?= count(array_unique(array_column($graded, 'student_name'))) ?></div>
            <div class="stat-label">Students</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>All Graded Entries</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:200px;">
  </div>
  <?php if ($graded): ?>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Week</th><th>Title</th><th>Grade</th><th>Status</th><th>Reviewed</th></tr>
      </thead>
      <tbody>
        <?php foreach ($graded as $g): ?>
        <tr>
          <td>
            <div class="td-name"><?= htmlspecialchars($g['student_name']) ?></div>
            <div class="td-sub"><?= htmlspecialchars($g['registration_number']) ?></div>
          </td>
          <td>Week <?= $g['week_number'] ?></td>
          <td><?= htmlspecialchars($g['title'] ?: '—') ?></td>
          <td><strong style="font-size:1rem;color:var(--navy);"><?= htmlspecialchars($g['grade']) ?></strong></td>
          <td><?= statusBadge($g['status']) ?></td>
          <td class="text-muted"><?= $g['reviewed_at'] ? date('d M Y', strtotime($g['reviewed_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><div class="empty-icon">🎓</div><h3>No grades assigned yet</h3></div>
  <?php endif; ?>
</div>

<?php endLayout(); ?>
