<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$lecturer = getLecturerProfile($_SESSION['user_id']);
$lid      = $lecturer['id'] ?? 0;

$filter = $_GET['status'] ?? 'all';

$sql = "SELECT lb.*, s.registration_number, u.name as student_name, i.company_name
        FROM logbooks lb
        JOIN students s ON s.id=lb.student_id
        JOIN users u ON u.id=s.user_id
        LEFT JOIN internships i ON i.student_id=s.id AND i.status='active'
        JOIN assignments a ON a.student_id=lb.student_id
        WHERE a.lecturer_id=?";
$params = [$lid];
if ($filter !== 'all') { $sql .= " AND lb.status=?"; $params[] = $filter; }
$sql .= " ORDER BY lb.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbooks = $stmt->fetchAll();

startLayout('Review Logbooks', 'Review Logbooks', 'Logbooks', 'logbooks');
?>

<div class="page-header">
  <div><h1>Review Logbooks</h1><p class="breadcrumb">Lecturer / <span>Logbooks</span></p></div>
  <div style="display:flex;gap:8px;">
    <a href="?status=all"     class="btn <?= $filter === 'all'      ? 'btn-primary' : 'btn-outline' ?> btn-sm">All</a>
    <a href="?status=pending" class="btn <?= $filter === 'pending'  ? 'btn-amber'   : 'btn-outline' ?> btn-sm">Pending</a>
    <a href="?status=approved"class="btn <?= $filter === 'approved' ? 'btn-success' : 'btn-outline' ?> btn-sm">Approved</a>
    <a href="?status=rejected"class="btn <?= $filter === 'rejected' ? 'btn-danger'  : 'btn-outline' ?> btn-sm">Rejected</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Logbook Entries (<?= count($logbooks) ?>)</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:220px;">
  </div>
  <?php if ($logbooks): ?>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Company</th><th>Week</th><th>Title</th><th>Status</th><th>Submitted</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logbooks as $log): ?>
        <tr>
          <td>
            <div class="td-name"><?= htmlspecialchars($log['student_name']) ?></div>
            <div class="td-sub"><?= htmlspecialchars($log['registration_number']) ?></div>
          </td>
          <td class="text-muted"><?= htmlspecialchars($log['company_name'] ?? '—') ?></td>
          <td><strong>Week <?= $log['week_number'] ?></strong></td>
          <td><?= htmlspecialchars($log['title'] ?: 'Untitled') ?></td>
          <td><?= statusBadge($log['status']) ?></td>
          <td class="text-muted"><?= date('d M Y', strtotime($log['submitted_at'])) ?></td>
          <td>
            <a href="/internship-system/lecturer/review.php?id=<?= $log['id'] ?>"
               class="btn btn-amber btn-xs">
              <?= $log['status'] === 'pending' ? 'Review' : 'View' ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><div class="empty-icon">📚</div><h3>No logbooks found</h3></div>
  <?php endif; ?>
</div>

<?php endLayout(); ?>
