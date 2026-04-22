<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$student = getStudentProfile($_SESSION['user_id']);
$sid     = $student['id'] ?? 0;

// Load existing internship
$stmt = $pdo->prepare("SELECT * FROM internships WHERE student_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$sid]);
$internship = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = sanitizeInput($_POST);
    $errors = [];
    if (empty($d['company_name'])) $errors[] = 'Company name is required.';
    if (empty($d['location']))     $errors[] = 'Location is required.';
    if (empty($d['start_date']))   $errors[] = 'Start date is required.';
    if (empty($d['end_date']))     $errors[] = 'End date is required.';
    if (!empty($d['start_date']) && !empty($d['end_date']) && $d['start_date'] >= $d['end_date'])
        $errors[] = 'End date must be after start date.';

    if (empty($errors)) {
        if ($internship) {
            $stmt = $pdo->prepare(
                "UPDATE internships SET company_name=?,location=?,supervisor_name=?,supervisor_email=?,
                 supervisor_phone=?,start_date=?,end_date=?,description=? WHERE id=?"
            );
            $stmt->execute([
                $d['company_name'], $d['location'],
                $d['supervisor_name'] ?? null, $d['supervisor_email'] ?? null,
                $d['supervisor_phone'] ?? null,
                $d['start_date'], $d['end_date'],
                $d['description'] ?? null,
                $internship['id']
            ]);
            setFlash('success', 'Internship details updated.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO internships (student_id,company_name,location,supervisor_name,supervisor_email,
                 supervisor_phone,start_date,end_date,description) VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $sid, $d['company_name'], $d['location'],
                $d['supervisor_name'] ?? null, $d['supervisor_email'] ?? null,
                $d['supervisor_phone'] ?? null,
                $d['start_date'], $d['end_date'],
                $d['description'] ?? null
            ]);
            setFlash('success', 'Internship registered successfully!');
        }
        header('Location: /internship-system/student/internship.php');
        exit;
    }
}

startLayout('My Internship', 'Internship Details', 'Internship', 'internship');
?>

<div class="page-header">
  <div>
    <h1><?= $internship ? 'Internship Details' : 'Register Internship' ?></h1>
    <p class="breadcrumb">Student / <span>Internship</span></p>
  </div>
  <?php if ($internship): ?>
  <?= statusBadge($internship['status']) ?>
  <?php endif; ?>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
  <ul style="margin:0;padding-left:18px;">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid-2">
  <div style="grid-column: 1 / -1;">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-building" style="color:var(--amber);margin-right:6px;"></i>
          <?= $internship ? 'Update Internship Information' : 'New Internship Registration' ?>
        </h3>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="form-section-title">Company Information</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="company_name">Company Name <span class="req">*</span></label>
              <input type="text" id="company_name" name="company_name" class="form-control"
                     placeholder="e.g. MTN Uganda"
                     value="<?= htmlspecialchars($internship['company_name'] ?? $_POST['company_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="location">Location <span class="req">*</span></label>
              <input type="text" id="location" name="location" class="form-control"
                     placeholder="e.g. Kampala, Uganda"
                     value="<?= htmlspecialchars($internship['location'] ?? $_POST['location'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="start_date">Start Date <span class="req">*</span></label>
              <input type="date" id="start_date" name="start_date" class="form-control"
                     value="<?= htmlspecialchars($internship['start_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="end_date">End Date <span class="req">*</span></label>
              <input type="date" id="end_date" name="end_date" class="form-control"
                     value="<?= htmlspecialchars($internship['end_date'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"
                      placeholder="Brief description of your internship role…"><?= htmlspecialchars($internship['description'] ?? '') ?></textarea>
          </div>

          <div class="form-section-title mt-2">Company Supervisor Details</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="supervisor_name">Supervisor Name</label>
              <input type="text" id="supervisor_name" name="supervisor_name" class="form-control"
                     placeholder="Mr./Ms. Full Name"
                     value="<?= htmlspecialchars($internship['supervisor_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="supervisor_email">Supervisor Email</label>
              <input type="email" id="supervisor_email" name="supervisor_email" class="form-control"
                     placeholder="supervisor@company.com"
                     value="<?= htmlspecialchars($internship['supervisor_email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group" style="max-width:240px;">
            <label class="form-label" for="supervisor_phone">Supervisor Phone</label>
            <input type="text" id="supervisor_phone" name="supervisor_phone" class="form-control"
                   placeholder="+256 700 000 000"
                   value="<?= htmlspecialchars($internship['supervisor_phone'] ?? '') ?>">
          </div>

          <div class="flex gap-1" style="margin-top:8px;">
            <button type="submit" class="btn btn-amber">
              <i class="fa fa-floppy-disk"></i>
              <?= $internship ? 'Update Details' : 'Register Internship' ?>
            </button>
            <?php if ($internship): ?>
            <a href="/internship-system/student/logbook.php" class="btn btn-primary">
              <i class="fa fa-book-open"></i> Go to Logbook
            </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php endLayout(); ?>
