<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = sanitizeInput($_POST);
    if (!empty($d['name'])) {
        $pdo->prepare("UPDATE users SET name=? WHERE id=?")->execute([$d['name'], $_SESSION['user_id']]);
        $_SESSION['name'] = $d['name'];
    }
    if (!empty($d['new_password'])) {
        if (strlen($d['new_password']) >= 8 && $d['new_password'] === ($d['confirm_password'] ?? '')) {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$d['new_password'], $_SESSION['user_id']]);
        } else {
            setFlash('danger', 'Password must be 8+ chars and match confirmation.');
            header('Location: /internship-system/admin/settings.php'); exit;
        }
    }
    setFlash('success', 'Settings saved.');
    header('Location: /internship-system/admin/settings.php'); exit;
}

// DB info
$dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
$tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='internship_db'")->fetchColumn();

startLayout('Settings', 'System Settings', 'Settings', 'settings');
?>

<div class="page-header"><div><h1>Settings</h1></div></div>

<div class="grid-2">
  <!-- Admin profile -->
  <div class="card fade-up">
    <div class="card-header"><h3>Administrator Account</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>
        <div class="form-section-title mt-2">Change Password</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min 8 chars">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control">
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk"></i> Save Changes</button>
      </form>
    </div>
  </div>

  <!-- System info -->
  <div>
    <div class="card fade-up mb-2">
      <div class="card-header"><h3>System Information</h3></div>
      <div class="card-body">
        <table style="width:100%;font-size:.875rem;">
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:8px 0;color:var(--text-3);">PHP Version</td>
            <td style="padding:8px 0;font-weight:600;"><?= PHP_VERSION ?></td>
          </tr>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:8px 0;color:var(--text-3);">MySQL Version</td>
            <td style="padding:8px 0;font-weight:600;"><?= $dbVersion ?></td>
          </tr>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:8px 0;color:var(--text-3);">Database</td>
            <td style="padding:8px 0;font-weight:600;">internship_db</td>
          </tr>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:8px 0;color:var(--text-3);">Tables</td>
            <td style="padding:8px 0;font-weight:600;"><?= $tableCount ?></td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:var(--text-3);">Server</td>
            <td style="padding:8px 0;font-weight:600;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/WAMP' ?></td>
          </tr>
        </table>
      </div>
    </div>

    <div class="card fade-up fade-up-2">
      <div class="card-header"><h3>Danger Zone</h3></div>
      <div class="card-body">
        <p class="text-muted text-sm mb-2">These actions are irreversible. Use with caution.</p>
        <button class="btn btn-danger btn-sm"
                onclick="if(confirm('Clear ALL notifications? This cannot be undone.'))
                  fetch('/internship-system/api/notifications.php?action=clear_all').then(()=>location.reload())">
          <i class="fa fa-trash"></i> Clear All Notifications
        </button>
      </div>
    </div>
  </div>
</div>

<?php endLayout(); ?>
