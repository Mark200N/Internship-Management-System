<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$lecturer = getLecturerProfile($_SESSION['user_id']);
$lid      = $lecturer['id'] ?? 0;

$stmt = $pdo->prepare(
    "SELECT u.name, u.email, s.id as student_id, s.registration_number, s.course, s.year,
            (SELECT COUNT(*) FROM logbooks WHERE student_id=s.id) as log_count,
            (SELECT COUNT(*) FROM logbooks WHERE student_id=s.id AND status='pending') as pending_count,
            (SELECT company_name FROM internships WHERE student_id=s.id AND status='active' LIMIT 1) as company
     FROM assignments a
     JOIN students s ON s.id=a.student_id
     JOIN users u ON u.id=s.user_id
     WHERE a.lecturer_id=?
     ORDER BY u.name"
);
$stmt->execute([$lid]);
$students = $stmt->fetchAll();

startLayout('My Students', 'Assigned Students', 'Students', 'students');
?>

<div class="page-header">
  <div><h1>My Students (<?= count($students) ?>)</h1><p class="breadcrumb">Lecturer / <span>Students</span></p></div>
  <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:220px;">
</div>

<div class="card">
  <?php if ($students): ?>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Reg No.</th><th>Course</th><th>Year</th><th>Company</th><th>Logbooks</th><th>Pending</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--navy);color:var(--amber-lt);display:grid;place-items:center;font-weight:700;font-size:.8rem;flex-shrink:0;">
                <?= strtoupper(substr($s['name'],0,1)) ?>
              </div>
              <div>
                <div class="td-name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="td-sub"><?= htmlspecialchars($s['email']) ?></div>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($s['registration_number']) ?></td>
          <td><?= htmlspecialchars($s['course']) ?></td>
          <td>Year <?= $s['year'] ?></td>
          <td><?= $s['company'] ? htmlspecialchars($s['company']) : '<span class="text-muted">—</span>' ?></td>
          <td><span class="tag"><?= $s['log_count'] ?> entries</span></td>
          <td>
            <?php if ($s['pending_count'] > 0): ?>
            <span class="badge badge-pending"><?= $s['pending_count'] ?> pending</span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="/internship-system/lecturer/logbooks.php" class="btn btn-outline btn-xs">View Logbooks</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="empty-icon">👥</div>
    <h3>No students assigned</h3>
    <p>Contact the administrator to assign students to your supervision.</p>
  </div>
  <?php endif; ?>
</div>

<?php endLayout(); ?>
