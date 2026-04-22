<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$filter = $_GET['status'] ?? 'all';

$sql = "SELECT i.*, u.name as student_name, s.registration_number, s.course,
               u_l.name as lecturer_name
        FROM internships i
        JOIN students s ON s.id=i.student_id
        JOIN users u ON u.id=s.user_id
        LEFT JOIN assignments a ON a.student_id=s.id
        LEFT JOIN lecturers l ON l.id=a.lecturer_id
        LEFT JOIN users u_l ON u_l.id=l.user_id";
$params = [];
if ($filter !== 'all') { $sql .= " WHERE i.status=?"; $params[] = $filter; }
$sql .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$internships = $stmt->fetchAll();

startLayout('Internships', 'All Internships', 'Internships', 'internships');
?>

<div class="page-header">
  <div><h1>Internships (<?= count($internships) ?>)</h1><p class="breadcrumb">Admin / <span>Internships</span></p></div>
  <div style="display:flex;gap:8px;">
    <a href="?status=all"       class="btn <?= $filter==='all'       ?'btn-primary':'btn-outline'?> btn-sm">All</a>
    <a href="?status=active"    class="btn <?= $filter==='active'    ?'btn-teal'   :'btn-outline'?> btn-sm">Active</a>
    <a href="?status=completed" class="btn <?= $filter==='completed' ?'btn-success':'btn-outline'?> btn-sm">Completed</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Records</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:220px;">
  </div>
  <?php if ($internships): ?>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Company</th><th>Location</th><th>Duration</th><th>Supervisor</th><th>Status</th><th>Logbooks</th></tr>
      </thead>
      <tbody>
        <?php foreach ($internships as $i): ?>
        <?php
          $logStmt = $pdo->prepare("SELECT COUNT(*) FROM logbooks WHERE student_id=?");
          $logStmt->execute([$i['student_id']]);
          $logCount = $logStmt->fetchColumn();
        ?>
        <tr>
          <td>
            <div class="td-name"><?= htmlspecialchars($i['student_name']) ?></div>
            <div class="td-sub"><?= htmlspecialchars($i['registration_number']) ?> · <?= htmlspecialchars($i['course']) ?></div>
          </td>
          <td><strong><?= htmlspecialchars($i['company_name']) ?></strong></td>
          <td class="text-muted"><?= htmlspecialchars($i['location']) ?></td>
          <td class="text-muted" style="font-size:.8rem;">
            <?= date('d M Y', strtotime($i['start_date'])) ?><br>
            → <?= date('d M Y', strtotime($i['end_date'])) ?>
          </td>
          <td class="text-muted"><?= $i['lecturer_name'] ? htmlspecialchars($i['lecturer_name']) : '<span style="color:var(--danger);">Unassigned</span>' ?></td>
          <td><?= statusBadge($i['status']) ?></td>
          <td><span class="tag"><?= $logCount ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><div class="empty-icon">🏢</div><h3>No internships found</h3></div>
  <?php endif; ?>
</div>

<?php endLayout(); ?>
