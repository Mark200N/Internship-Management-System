<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$lecturer = getLecturerProfile($_SESSION['user_id']);
$lid      = $lecturer['id'] ?? 0;
$logId    = (int)($_GET['id'] ?? 0);

// Verify this logbook belongs to one of the lecturer's students
$stmt = $pdo->prepare(
    "SELECT lb.*, s.registration_number, s.course, u.name as student_name, u.email as student_email,
            i.company_name, i.location
     FROM logbooks lb
     JOIN students s ON s.id=lb.student_id
     JOIN users u ON u.id=s.user_id
     JOIN assignments a ON a.student_id=lb.student_id
     LEFT JOIN internships i ON i.student_id=s.id AND i.status='active'
     WHERE lb.id=? AND a.lecturer_id=?"
);
$stmt->execute([$logId, $lid]);
$log = $stmt->fetch();

if (!$log) {
    setFlash('danger', 'Logbook not found or not assigned to you.');
    header('Location: /internship-system/lecturer/logbooks.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');
    $grade    = trim($_POST['grade'] ?? '');

    if (in_array($action, ['approve','reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt   = $pdo->prepare(
            "UPDATE logbooks SET status=?, feedback=?, grade=?, reviewed_at=NOW() WHERE id=?"
        );
        $stmt->execute([$status, $feedback ?: null, $grade ?: null, $logId]);

        // Notify student
        $stmt2 = $pdo->prepare("SELECT user_id FROM students WHERE id=?");
        $stmt2->execute([$log['student_id']]);
        $stuUserId = $stmt2->fetchColumn();
        if ($stuUserId) {
            addNotification(
                $stuUserId,
                'Logbook ' . ucfirst($status),
                "Your Week {$log['week_number']} logbook has been {$status}."
                . ($feedback ? " Feedback: " . substr($feedback, 0, 80) : '')
            );
        }

        setFlash('success', "Logbook Week {$log['week_number']} has been {$status}.");
        header('Location: /internship-system/lecturer/logbooks.php');
        exit;
    }
}

startLayout('Review Entry', "Week {$log['week_number']} Review", 'Review', 'logbooks');
?>

<div class="page-header">
  <div>
    <h1>Week <?= $log['week_number'] ?> — <?= htmlspecialchars($log['student_name']) ?></h1>
    <p class="breadcrumb">Lecturer / Logbooks / <span>Review</span></p>
  </div>
  <?= statusBadge($log['status']) ?>
</div>

<div class="grid-2">
  <!-- Logbook content -->
  <div style="grid-column: 1 / -1 ;">
  <div class="grid-2">
  <div class="card fade-up">
    <div class="card-header"><h3>Student Information</h3></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem;">
        <div><span class="text-muted">Name:</span><br><strong><?= htmlspecialchars($log['student_name']) ?></strong></div>
        <div><span class="text-muted">Reg. No:</span><br><strong><?= htmlspecialchars($log['registration_number']) ?></strong></div>
        <div><span class="text-muted">Course:</span><br><?= htmlspecialchars($log['course']) ?></div>
        <div><span class="text-muted">Company:</span><br><?= htmlspecialchars($log['company_name'] ?? '—') ?></div>
      </div>
    </div>
  </div>
  <div class="card fade-up fade-up-2">
    <div class="card-header"><h3>Submission Details</h3></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem;">
        <div><span class="text-muted">Week:</span><br><strong>Week <?= $log['week_number'] ?></strong></div>
        <div><span class="text-muted">Submitted:</span><br><?= date('d M Y H:i', strtotime($log['submitted_at'])) ?></div>
        <?php if ($log['grade']): ?>
        <div><span class="text-muted">Grade:</span><br><strong><?= htmlspecialchars($log['grade']) ?></strong></div>
        <?php endif; ?>
        <?php if ($log['file_name']): ?>
        <div><span class="text-muted">Document:</span><br>
          <a href="/internship-system/uploads/logbooks/<?= $log['student_id'] ?>/<?= basename($log['file_path']) ?>"
             target="_blank" class="btn btn-outline btn-xs"><i class="fa fa-paperclip"></i> <?= htmlspecialchars($log['file_name']) ?></a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  </div>
  </div>

  <!-- Weekly report -->
  <div class="card fade-up" style="grid-column: 1 / -1;">
    <div class="card-header"><h3>Weekly Report</h3></div>
    <div class="card-body">
      <?php if ($log['title']): ?>
      <h3 style="margin-bottom:10px;"><?= htmlspecialchars($log['title']) ?></h3>
      <?php endif; ?>
      <p style="font-size:.9rem;line-height:1.8;"><?= nl2br(htmlspecialchars($log['content'])) ?></p>
      <?php if ($log['activities']): ?>
      <hr class="divider">
      <div class="form-section-title">Key Activities</div>
      <p style="font-size:.9rem;line-height:1.8;"><?= nl2br(htmlspecialchars($log['activities'])) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Existing feedback -->
  <?php if ($log['feedback'] || $log['status'] !== 'pending'): ?>
  <div class="card fade-up" style="grid-column: 1 / -1;">
    <div class="card-header"><h3>Previous Feedback</h3></div>
    <div class="card-body">
      <div class="feedback-box <?= $log['status'] ?>">
        <div class="fb-label"><?= ucfirst($log['status']) ?></div>
        <p><?= nl2br(htmlspecialchars($log['feedback'] ?? 'No feedback provided.')) ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Review form -->
  <div class="card fade-up" style="grid-column: 1 / -1;">
    <div class="card-header"><h3><i class="fa fa-pen-to-square" style="color:var(--amber);margin-right:6px;"></i>
      <?= $log['status'] === 'pending' ? 'Provide Feedback' : 'Update Feedback' ?>
    </h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-row">
          <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label" for="feedback">Feedback / Comments</label>
            <textarea id="feedback" name="feedback" class="form-control" rows="5"
                      placeholder="Write your feedback here…"><?= htmlspecialchars($log['feedback'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="form-group" style="max-width:200px;">
          <label class="form-label" for="grade">Grade (optional)</label>
          <select id="grade" name="grade" class="form-control">
            <option value="">No Grade</option>
            <?php foreach (['A','B+','B','C+','C','D+','D','F'] as $g): ?>
            <option value="<?= $g ?>" <?= ($log['grade'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex gap-1" style="margin-top:8px;">
          <button type="submit" name="action" value="approve" class="btn btn-success">
            <i class="fa fa-circle-check"></i> Approve
          </button>
          <button type="submit" name="action" value="reject" class="btn btn-danger"
                  onclick="return confirm('Reject this logbook?')">
            <i class="fa fa-circle-xmark"></i> Reject
          </button>
          <a href="/internship-system/lecturer/logbooks.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>
