<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
    $studentId  = (int)($_POST['student_id']  ?? 0);

    if ($action === 'assign' && $lecturerId && $studentId) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO assignments (lecturer_id,student_id) VALUES (?,?)");
            $stmt->execute([$lecturerId, $studentId]);

            // Notify both parties
            $lecStmt = $pdo->prepare("SELECT user_id FROM lecturers WHERE id=?");
            $lecStmt->execute([$lecturerId]);
            $lecUserId = $lecStmt->fetchColumn();

            $stuStmt = $pdo->prepare("SELECT user_id, registration_number FROM students WHERE id=?");
            $stuStmt->execute([$studentId]);
            $stuRow = $stuStmt->fetch();

            if ($lecUserId) addNotification($lecUserId, 'New Student Assigned', "A student has been assigned to your supervision.");
            if ($stuRow)    addNotification($stuRow['user_id'], 'Supervisor Assigned', "A supervisor has been assigned to you.");

            setFlash('success', 'Assignment created successfully.');
        } catch (Exception $e) {
            setFlash('danger', 'Assignment failed: ' . $e->getMessage());
        }
    } elseif ($action === 'remove') {
        $id = (int)($_POST['assignment_id'] ?? 0);
        $pdo->prepare("DELETE FROM assignments WHERE id=?")->execute([$id]);
        setFlash('success', 'Assignment removed.');
    }
    header('Location: /internship-system/admin/assignments.php');
    exit;
}

// Load data
$students  = $pdo->query(
    "SELECT s.id, s.registration_number, u.name, s.course FROM students s
     JOIN users u ON u.id=s.user_id ORDER BY u.name"
)->fetchAll();

$lecturers = $pdo->query(
    "SELECT l.id, u.name, l.department FROM lecturers l
     JOIN users u ON u.id=l.user_id ORDER BY u.name"
)->fetchAll();

$assignments = $pdo->query(
    "SELECT a.id, u_s.name as student_name, s.registration_number, s.course,
            u_l.name as lecturer_name, l.department, a.assigned_at
     FROM assignments a
     JOIN students s ON s.id=a.student_id JOIN users u_s ON u_s.id=s.user_id
     JOIN lecturers l ON l.id=a.lecturer_id JOIN users u_l ON u_l.id=l.user_id
     ORDER BY a.assigned_at DESC"
)->fetchAll();

startLayout('Assignments', 'Lecturer–Student Assignments', 'Assignments', 'assignments');
?>

<div class="page-header">
  <div><h1>Assignments</h1><p class="breadcrumb">Admin / <span>Assignments</span></p></div>
  <button class="btn btn-amber" data-modal-open="assignModal">
    <i class="fa fa-link"></i> New Assignment
  </button>
</div>

<div class="card fade-up">
  <div class="card-header">
    <h3>All Assignments (<?= count($assignments) ?>)</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:220px;">
  </div>
  <?php if ($assignments): ?>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Course</th><th>Lecturer</th><th>Department</th><th>Assigned</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
        <tr>
          <td>
            <div class="td-name"><?= htmlspecialchars($a['student_name']) ?></div>
            <div class="td-sub"><?= htmlspecialchars($a['registration_number']) ?></div>
          </td>
          <td class="text-muted"><?= htmlspecialchars($a['course']) ?></td>
          <td>
            <div class="td-name"><?= htmlspecialchars($a['lecturer_name']) ?></div>
          </td>
          <td class="text-muted"><?= htmlspecialchars($a['department']) ?></td>
          <td class="text-muted"><?= date('d M Y', strtotime($a['assigned_at'])) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action"        value="remove">
              <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
              <button type="submit" class="btn btn-danger btn-xs"
                      onclick="return confirm('Remove this assignment?')">
                <i class="fa fa-unlink"></i> Remove
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="empty-icon">🔗</div>
    <h3>No assignments yet</h3>
    <p>Assign lecturers to supervise students.</p>
  </div>
  <?php endif; ?>
</div>

<!-- Assignment Modal -->
<div class="modal-overlay" id="assignModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa fa-link" style="color:var(--amber);"></i> New Assignment</h3>
      <button class="modal-close" data-modal-close="assignModal">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="assign">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Select Lecturer <span class="req">*</span></label>
          <select name="lecturer_id" class="form-control" required>
            <option value="">— Choose Lecturer —</option>
            <?php foreach ($lecturers as $l): ?>
            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['department']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Select Student <span class="req">*</span></label>
          <select name="student_id" class="form-control" required>
            <option value="">— Choose Student —</option>
            <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['registration_number']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <p class="form-hint"><i class="fa fa-info-circle"></i> Duplicate assignments are silently ignored.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="assignModal">Cancel</button>
        <button type="submit" class="btn btn-amber"><i class="fa fa-link"></i> Assign</button>
      </div>
    </form>
  </div>
</div>

<?php endLayout(); ?>
