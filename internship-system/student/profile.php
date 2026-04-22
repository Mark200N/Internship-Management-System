<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$user    = currentUser();
$student = getStudentProfile($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = sanitizeInput($_POST);
    $errors = [];
    if (empty($d['name'])) $errors[] = 'Name is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->execute([$d['name'], $_SESSION['user_id']]);

        $stmt = $pdo->prepare("UPDATE students SET course=?, year=?, phone=? WHERE user_id=?");
        $stmt->execute([$d['course'] ?? '', $d['year'] ?? 1, $d['phone'] ?? null, $_SESSION['user_id']]);

        if (!empty($d['new_password'])) {
            if (strlen($d['new_password']) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($d['new_password'] !== ($d['confirm_password'] ?? '')) {
                $errors[] = 'Passwords do not match.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
                $stmt->execute([$d['new_password'], $_SESSION['user_id']]);
            }
        }

        if (empty($errors)) {
            $_SESSION['name'] = $d['name'];
            setFlash('success', 'Profile updated successfully.');
            header('Location: /internship-system/student/profile.php');
            exit;
        }
    }
}

startLayout('My Profile', 'Profile Settings', 'Profile', 'profile');
?>

<div class="page-header">
  <div><h1>Profile Settings</h1><p class="breadcrumb">Student / <span>Profile</span></p></div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><h3>Personal Information</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
          <p class="form-hint">Email cannot be changed. Contact admin if needed.</p>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="+256 700 000 000"
                 value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
        </div>

        <div class="form-section-title mt-2">Academic Details</div>
        <div class="form-group">
          <label class="form-label">Registration Number</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($student['registration_number'] ?? '') ?>" disabled>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Course</label>
            <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($student['course'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <select name="year" class="form-control">
              <?php for ($y = 1; $y <= 5; $y++): ?>
              <option value="<?= $y ?>" <?= ($student['year'] ?? 1) == $y ? 'selected' : '' ?>>Year <?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk"></i> Save Changes</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Change Password</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="name" value="<?= htmlspecialchars($user['name']) ?>">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
        </div>
        <button type="submit" class="btn btn-outline"><i class="fa fa-lock"></i> Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>
