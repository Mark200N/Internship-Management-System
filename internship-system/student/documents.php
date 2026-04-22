<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$student = getStudentProfile($_SESSION['user_id']);
$sid     = $student['id'] ?? 0;

// All uploaded files from logbooks
$stmt = $pdo->prepare(
    "SELECT week_number, file_path, file_name, submitted_at, status FROM logbooks
     WHERE student_id=? AND file_path IS NOT NULL ORDER BY week_number"
);
$stmt->execute([$sid]);
$documents = $stmt->fetchAll();

startLayout('Documents', 'My Documents', 'Documents', 'documents');
?>

<div class="page-header">
  <div><h1>Uploaded Documents</h1><p class="breadcrumb">Student / <span>Documents</span></p></div>
  <a href="/internship-system/student/logbook.php" class="btn btn-amber">
    <i class="fa fa-upload"></i> Upload via Logbook
  </a>
</div>

<div class="card">
  <?php if ($documents): ?>
  <div class="card-header"><h3>All Documents (<?= count($documents) ?>)</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Week</th><th>File Name</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($documents as $doc): ?>
        <tr>
          <td><strong>Week <?= $doc['week_number'] ?></strong></td>
          <td>
            <i class="fa fa-file-pdf" style="color:var(--danger);margin-right:6px;"></i>
            <?= htmlspecialchars($doc['file_name']) ?>
          </td>
          <td><?= statusBadge($doc['status']) ?></td>
          <td class="text-muted"><?= date('d M Y', strtotime($doc['submitted_at'])) ?></td>
          <td>
            <a href="/internship-system/uploads/logbooks/<?= $sid ?>/<?= basename($doc['file_path']) ?>"
               target="_blank" class="btn btn-outline btn-xs">
              <i class="fa fa-download"></i> Download
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="empty-icon">📂</div>
    <h3>No documents uploaded</h3>
    <p>Attach PDF or DOCX files when submitting logbook entries.</p>
  </div>
  <?php endif; ?>
</div>

<?php endLayout(); ?>
