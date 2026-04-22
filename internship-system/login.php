<?php
require_once __DIR__ . '/config/helpers.php';
if (isLoggedIn()) { header('Location: /internship-system/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $storedPassword = $user['password'];
            $isValid = false;

            if ($password === $storedPassword) {
                $isValid = true;
            } elseif (password_verify($password, $storedPassword)) {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$password, $user['id']]);
                $isValid = true;
            }

            if ($isValid) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['name'];
                header('Location: /internship-system/dashboard.php');
                exit;
            }
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Internship Management System</title>
  <link rel="stylesheet" href="/internship-system/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div class="auth-page">

  <!-- Left art panel -->
  <div class="auth-art">
    <h1>Track Your <em>Internship</em><br>Journey</h1>
    <p>A centralized platform for students, lecturers, and administrators to manage the entire internship lifecycle.</p>
    <div class="features">
      <div class="feat-item"><span class="feat-dot"></span>Submit weekly logbooks & documents</div>
      <div class="feat-item"><span class="feat-dot"></span>Real-time supervisor feedback</div>
      <div class="feat-item"><span class="feat-dot"></span>Progress tracking & evaluation</div>
      <div class="feat-item"><span class="feat-dot"></span>Secure role-based access</div>
    </div>
  </div>

  <!-- Right auth panel -->
  <div class="auth-panel">
    <div class="auth-logo">
      <div class="auth-logo-icon"><i class="fa fa-graduation-cap"></i></div>
      <div class="auth-logo-text">Internship<br>Management System</div>
    </div>

    <h2>Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Email address <span class="req">*</span></label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="you@university.ac.ug"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">
          Password <span class="req">*</span>
          <a href="#" style="float:right;font-size:.78rem;color:var(--navy-3);font-weight:400;">Forgot password?</a>
        </label>
        <div style="position:relative;">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••" required>
          <button type="button" id="togglePw"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-3);cursor:pointer;">
            <i class="fa fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="form-group" style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" id="remember" name="remember" style="width:auto;">
        <label for="remember" class="form-label" style="margin:0;cursor:pointer;">Keep me signed in</label>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="padding:11px;">
        <i class="fa fa-arrow-right-to-bracket"></i> Sign In
      </button>
    </form>

    <div class="auth-foot">
      Don't have an account?
      <a href="/internship-system/register.php">Create one</a>
    </div>

    <div class="auth-foot" style="margin-top:12px;font-size:.75rem;">
      <strong>Demo:</strong> admin@internship.ac.ug / Admin@1234
    </div>
  </div>
</div>

<script>
const togglePw = document.getElementById('togglePw');
const pwInput  = document.getElementById('password');
const eyeIcon  = document.getElementById('eyeIcon');
togglePw?.addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  eyeIcon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
});
</script>
</body>
</html>
