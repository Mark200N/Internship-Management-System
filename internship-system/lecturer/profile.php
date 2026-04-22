<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('lecturer');

$user     = currentUser();
$lecturer = getLecturerProfile($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = sanitizeInput($_POST);
    $stmt = $pdo->prepare("UPDATE users SET name=? WHERE id=?");
    $stmt->execute([$d['name'] ?? $user['name'], $_SESSION['user_id']]);
    $stmt = $pdo->prepare("UPDATE lecturers SET department=?, staff_id=?, phone=? WHERE user_id=?");
    $stmt->execute([$d['department'] ?? '', $d['staff_id'] ?? null, $d['phone'] ?? null, $_SESSION['user_id']]);
    if (!empty($d['new_password'])) {
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$d['new_password'], $_SESSION['user_id']]);
    }
    $_SESSION['name'] = $d['name'];
    setFlash('success', 'Profile updated.');
    header('Location: /internship-system/lecturer/profile.php');
    exit;
}

startLayout('Profile', 'Profile Settings', 'Profile', 'profile');
?>

<div class="page-header"><div><h1>Profile Settings</h1></div></div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><h3>Personal Information</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($lecturer['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($lecturer['department'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Staff ID</label>
            <input type="text" name="staff_id" class="form-control" value="<?= htmlspecialchars($lecturer['staff_id'] ?? '') ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk"></i> Save</button>
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
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control">
        </div>
        <button type="submit" class="btn btn-outline"><i class="fa fa-lock"></i> Update</button>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>
