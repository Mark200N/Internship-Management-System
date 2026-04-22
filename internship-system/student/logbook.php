<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$student = getStudentProfile($_SESSION['user_id']);
$sid     = $student['id'] ?? 0;

// Get active internship
$stmt = $pdo->prepare("SELECT * FROM internships WHERE student_id=? AND status='active' LIMIT 1");
$stmt->execute([$sid]);
$internship = $stmt->fetch();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_logbook') {
    $weekNum  = (int)($_POST['week_number'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $activities = trim($_POST['activities'] ?? '');

    if (!$weekNum || !$content) {
        setFlash('danger', 'Week number and content are required.');
    } else {
        // Check duplicate week
        $stmt = $pdo->prepare("SELECT id FROM logbooks WHERE student_id=? AND week_number=?");
        $stmt->execute([$sid, $weekNum]);
        if ($stmt->fetch()) {
            setFlash('warning', "You already submitted a logbook for Week $weekNum.");
        } else {
            $filePath = null; $fileName = null;
            if (!empty($_FILES['document']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/logbooks/' . $sid . '/';
                $result    = uploadFile($_FILES['document'], $uploadDir);
                if (!$result['ok']) {
                    setFlash('danger', $result['error']);
                    header('Location: /internship-system/student/logbook.php');
                    exit;
                }
                $filePath = $result['path'];
                $fileName = $result['original'];
            }
            $stmt = $pdo->prepare(
                "INSERT INTO logbooks (student_id,internship_id,week_number,title,content,activities,file_path,file_name,status)
                 VALUES (?,?,?,?,?,?,?,?,'pending')"
            );
            $stmt->execute([$sid, $internship['id'] ?? null, $weekNum, $title, $content, $activities, $filePath, $fileName]);
            // Notify supervisor
            $supStmt = $pdo->prepare(
                "SELECT u.id FROM assignments a JOIN lecturers l ON l.id=a.lecturer_id JOIN users u ON u.id=l.user_id WHERE a.student_id=?"
            );
            $supStmt->execute([$sid]);
            $sup = $supStmt->fetch();
            if ($sup) addNotification($sup['id'], 'New Logbook Submission', "{$student['name']} submitted Week $weekNum logbook.");
            setFlash('success', "Week $weekNum logbook submitted successfully!");
        }
    }
    header('Location: /internship-system/student/logbook.php');
    exit;
}

// Fetch logbooks
$stmt = $pdo->prepare("SELECT * FROM logbooks WHERE student_id=? ORDER BY week_number DESC");
$stmt->execute([$sid]);
$logbooks = $stmt->fetchAll();

startLayout('Logbook', 'Weekly Logbook', 'Logbook', 'logbook');
?>

<div class="page-header">
  <div>
    <h1>Weekly Logbook</h1>
    <p class="breadcrumb">Student / <span>Logbook</span></p>
  </div>
  <?php if ($internship): ?>
  <button class="btn btn-amber" data-modal-open="submitModal">
    <i class="fa fa-plus"></i> New Entry
  </button>
  <?php endif; ?>
</div>

<?php if (!$internship): ?>
<div class="alert alert-warning">
  <i class="fa fa-triangle-exclamation"></i>
  You must have an active internship before submitting logbooks.
  <a href="/internship-system/student/internship.php" style="font-weight:600;"> Register Internship →</a>
</div>
<?php endif; ?>

<div class="card">
  <?php if ($logbooks): ?>
  <div class="card-header">
    <h3>All Entries (<?= count($logbooks) ?>)</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:200px;">
  </div>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr>
          <th>Week</th><th>Title</th><th>Status</th><th>Feedback</th><th>Grade</th><th>Submitted</th><th>Document</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logbooks as $log): ?>
        <tr>
          <td><strong>Week <?= $log['week_number'] ?></strong></td>
          <td>
            <div class="td-name"><?= htmlspecialchars($log['title'] ?: "Week {$log['week_number']}") ?></div>
            <div class="td-sub"><?= htmlspecialchars(substr($log['content'], 0, 60)) ?>…</div>
          </td>
          <td><?= statusBadge($log['status']) ?></td>
          <td>
            <?php if ($log['feedback']): ?>
            <span title="<?= htmlspecialchars($log['feedback']) ?>"
                  style="cursor:pointer;color:var(--teal);">
              <i class="fa fa-comment-dots"></i> View
            </span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= $log['grade'] ? "<strong>{$log['grade']}</strong>" : '<span class="text-muted">—</span>' ?></td>
          <td class="text-muted"><?= date('d M Y', strtotime($log['submitted_at'])) ?></td>
          <td>
            <?php if ($log['file_path']): ?>
            <a href="/internship-system/<?= htmlspecialchars(str_replace(__DIR__ . '/../', '', $log['file_path'])) ?>"
               target="_blank" class="btn btn-outline btn-xs">
              <i class="fa fa-paperclip"></i> <?= htmlspecialchars($log['file_name']) ?>
            </a>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="empty-icon">📓</div>
    <h3>No logbook entries yet</h3>
    <p>Submit your first weekly logbook entry to get started.</p>
  </div>
  <?php endif; ?>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="submitModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa fa-book-open" style="color:var(--amber);"></i> New Logbook Entry</h3>
      <button class="modal-close" data-modal-close="submitModal">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="submit_logbook">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="week_number">Week Number <span class="req">*</span></label>
            <select id="week_number" name="week_number" class="form-control" required>
              <option value="">Select week…</option>
              <?php for ($w = 1; $w <= 20; $w++): ?>
              <option value="<?= $w ?>">Week <?= $w ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="title">Entry Title</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="Brief title…">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="content">Weekly Report <span class="req">*</span></label>
          <textarea id="content" name="content" class="form-control" rows="5"
                    placeholder="Describe what you did this week…" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="activities">Key Activities</label>
          <textarea id="activities" name="activities" class="form-control" rows="3"
                    placeholder="List the main activities you performed…"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Attach Document (PDF/DOCX, max 5MB)</label>
          <div class="upload-zone">
            <input type="file" name="document" accept=".pdf,.doc,.docx">
            <div class="upload-icon">📎</div>
            <p>Click to browse or drag & drop a file</p>
            <p class="upload-file-name"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="submitModal">Cancel</button>
        <button type="submit" class="btn btn-amber"><i class="fa fa-paper-plane"></i> Submit Entry</button>
      </div>
    </form>
  </div>
</div>

<?php endLayout(); ?>
