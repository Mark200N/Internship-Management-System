<?php
require_once __DIR__ . '/config/helpers.php';
if (isLoggedIn()) { header('Location: /internship-system/dashboard.php'); exit; }

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = sanitizeInput($_POST);
    $role = $data['role'] ?? 'student';

    // Validate
    if (empty($data['name']))     $errors['name']     = 'Full name is required.';
    if (empty($data['email']))    $errors['email']    = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if (empty($data['password'])) $errors['password'] = 'Password is required.';
    elseif (strlen($data['password']) < 8) $errors['password'] = 'Minimum 8 characters.';
    if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) $errors['confirm_password'] = 'Passwords do not match.';
    if (!in_array($role, ['student','lecturer'])) $errors['role'] = 'Invalid role.';

    if ($role === 'student') {
        if (empty($data['registration_number'])) $errors['registration_number'] = 'Registration number required.';
        if (empty($data['course']))              $errors['course']              = 'Course is required.';
    } elseif ($role === 'lecturer') {
        if (empty($data['department'])) $errors['department'] = 'Department is required.';
    }

    if (empty($errors)) {
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered.';
        } else {
            $password = $data['password'];
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
                $stmt->execute([$data['name'], $data['email'], $password, $role]);
                $userId = $pdo->lastInsertId();

                if ($role === 'student') {
                    $stmt = $pdo->prepare("INSERT INTO students (user_id,registration_number,course,year) VALUES (?,?,?,?)");
                    $stmt->execute([$userId, $data['registration_number'], $data['course'], $data['year'] ?? 1]);
                } elseif ($role === 'lecturer') {
                    $stmt = $pdo->prepare("INSERT INTO lecturers (user_id,department,staff_id) VALUES (?,?,?)");
                    $stmt->execute([$userId, $data['department'], $data['staff_id'] ?? null]);
                }
                $pdo->commit();
                setFlash('success', 'Account created successfully. Please login.');
                header('Location: /internship-system/login.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors['general'] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — IMS</title>
  <link rel="stylesheet" href="/internship-system/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-art">
    <h1>Join the <em>IMS</em><br>Platform</h1>
    <p>Create your account to start managing your internship experience with ease.</p>
    <div class="features">
      <div class="feat-item"><span class="feat-dot"></span>Students: track & submit logbooks</div>
      <div class="feat-item"><span class="feat-dot"></span>Lecturers: supervise & evaluate</div>
      <div class="feat-item"><span class="feat-dot"></span>All-in-one internship tracking</div>
    </div>
  </div>
  <div class="auth-panel" style="overflow-y:auto;max-height:100vh;">
    <div class="auth-logo">
      <div class="auth-logo-icon"><i class="fa fa-graduation-cap"></i></div>
      <div class="auth-logo-text">Create Account</div>
    </div>
    <h2>Register</h2>
    <p class="auth-subtitle">Fill in your details to get started</p>

    <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST">
      <!-- Role -->
      <p class="form-label">I am a: <span class="req">*</span></p>
      <div class="role-selector mb-2">
        <div class="role-opt">
          <input type="radio" name="role" id="r_student" value="student" <?= ($data['role'] ?? 'student') === 'student' ? 'checked' : '' ?>>
          <label for="r_student"><span class="role-icon">🧑‍🎓</span>Student</label>
        </div>
        <div class="role-opt">
          <input type="radio" name="role" id="r_lecturer" value="lecturer" <?= ($data['role'] ?? '') === 'lecturer' ? 'checked' : '' ?>>
          <label for="r_lecturer"><span class="role-icon">👨‍🏫</span>Lecturer</label>
        </div>
      </div>

      <!-- Basic info -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="name">Full Name <span class="req">*</span></label>
          <input type="text" id="name" name="name" class="form-control" placeholder="John Doe"
                 value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
          <?php if (!empty($errors['name'])): ?><p class="form-error"><?= $errors['name'] ?></p><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="email">Email <span class="req">*</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="you@uni.ac.ug"
                 value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
          <?php if (!empty($errors['email'])): ?><p class="form-error"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
      </div>

      <!-- Password -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password <span class="req">*</span></label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 chars" required>
          <div class="progress-bar mt-1" style="height:4px;"><div id="pwMeter" style="height:4px;width:0%;transition:width .3s,background .3s;border-radius:4px;"></div></div>
          <?php if (!empty($errors['password'])): ?><p class="form-error"><?= $errors['password'] ?></p><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm Password <span class="req">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
          <?php if (!empty($errors['confirm_password'])): ?><p class="form-error"><?= $errors['confirm_password'] ?></p><?php endif; ?>
        </div>
      </div>

      <!-- Student fields -->
      <div id="student-fields">
        <div class="form-section-title">Student Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="registration_number">Reg. Number <span class="req">*</span></label>
            <input type="text" id="registration_number" name="registration_number" class="form-control"
                   placeholder="e.g. 2021/CS/001"
                   value="<?= htmlspecialchars($data['registration_number'] ?? '') ?>">
            <?php if (!empty($errors['registration_number'])): ?><p class="form-error"><?= $errors['registration_number'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="year">Year of Study</label>
            <select id="year" name="year" class="form-control">
              <?php for ($y = 1; $y <= 5; $y++): ?>
              <option value="<?= $y ?>" <?= ($data['year'] ?? 1) == $y ? 'selected' : '' ?>><?= "Year $y" ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="course">Course / Programme <span class="req">*</span></label>
          <input type="text" id="course" name="course" class="form-control"
                 placeholder="e.g. Bachelor of Computer Science"
                 value="<?= htmlspecialchars($data['course'] ?? '') ?>">
          <?php if (!empty($errors['course'])): ?><p class="form-error"><?= $errors['course'] ?></p><?php endif; ?>
        </div>
      </div>

      <!-- Lecturer fields -->
      <div id="lecturer-fields" class="hidden">
        <div class="form-section-title">Lecturer Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="department">Department <span class="req">*</span></label>
            <input type="text" id="department" name="department" class="form-control"
                   placeholder="e.g. Computer Science"
                   value="<?= htmlspecialchars($data['department'] ?? '') ?>">
            <?php if (!empty($errors['department'])): ?><p class="form-error"><?= $errors['department'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="staff_id">Staff ID</label>
            <input type="text" id="staff_id" name="staff_id" class="form-control"
                   placeholder="Optional"
                   value="<?= htmlspecialchars($data['staff_id'] ?? '') ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="padding:11px;margin-top:6px;">
        <i class="fa fa-user-plus"></i> Create Account
      </button>
    </form>
    <div class="auth-foot">Already have an account? <a href="/internship-system/login.php">Sign in</a></div>
  </div>
</div>

<script>
const roleInputs    = document.querySelectorAll('input[name="role"]');
const studentFields = document.getElementById('student-fields');
const lecturerFields = document.getElementById('lecturer-fields');
function toggleFields() {
  const val = document.querySelector('input[name="role"]:checked')?.value;
  studentFields.classList.toggle('hidden', val !== 'student');
  lecturerFields.classList.toggle('hidden', val !== 'lecturer');
}
roleInputs.forEach(r => r.addEventListener('change', toggleFields));
toggleFields();
</script>
<script src="/internship-system/assets/js/main.js"></script>
</body>
</html>
