<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$student = getStudentProfile($_SESSION['user_id']);
$sid     = $student['id'] ?? 0;

$stmt = $pdo->prepare(
    "SELECT * FROM logbooks WHERE student_id=? AND (feedback IS NOT NULL OR grade IS NOT NULL)
     ORDER BY week_number ASC"
);
$stmt->execute([$sid]);
$feedbacks = $stmt->fetchAll();

startLayout('Feedback', 'Supervisor Feedback', 'Feedback', 'feedback');
?>

<div class="page-header">
  <div><h1>Feedback & Grades</h1><p class="breadcrumb">Student / <span>Feedback</span></p></div>
</div>

<?php if ($feedbacks): ?>
<?php foreach ($feedbacks as $log): ?>
<div class="card mb-2 fade-up">
  <div class="card-header">
    <h3>Week <?= $log['week_number'] ?> — <?= htmlspecialchars($log['title'] ?: "Entry") ?></h3>
    <div style="display:flex;gap:8px;align-items:center;">
      <?= statusBadge($log['status']) ?>
      <?php if ($log['grade']): ?>
      <span class="badge badge-active" style="font-size:.85rem;">Grade: <?= htmlspecialchars($log['grade']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <?php if ($log['feedback']): ?>
    <div class="feedback-box <?= $log['status'] ?>">
      <div class="fb-label"><i class="fa fa-comment"></i> Supervisor Feedback</div>
      <p style="font-size:.9rem;"><?= nl2br(htmlspecialchars($log['feedback'])) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($log['reviewed_at']): ?>
    <p class="text-muted text-sm mt-1">Reviewed: <?= date('d M Y, H:i', strtotime($log['reviewed_at'])) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="card">
  <div class="empty-state">
    <div class="empty-icon">💬</div>
    <h3>No feedback yet</h3>
    <p>Feedback from your supervisor will appear here once your logbooks are reviewed.</p>
  </div>
</div>
<?php endif; ?>

<?php endLayout(); ?>
