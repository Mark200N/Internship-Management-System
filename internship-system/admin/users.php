<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$csrfToken = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token'] = bin2hex(random_bytes(16)));

// Handle AJAX delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['_token'] !== $csrfToken) { echo json_encode(['ok'=>false,'error'=>'CSRF']); exit; }

    $action = $_POST['action'];
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        // Prevent deleting own account
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['ok'=>false,'error'=>'Cannot delete your own account.']); exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }
    echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit;
}

// Create/Edit user
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}

// Save new or edited user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $d      = sanitizeInput($_POST);
    $errors = [];
    if (empty($d['name']))  $errors[] = 'Name required.';
    if (empty($d['email'])) $errors[] = 'Email required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (!in_array($d['role'] ?? '', ['student','lecturer','admin'])) $errors[] = 'Invalid role.';

    $userId = (int)($d['user_id'] ?? 0);

    if (empty($errors)) {
        if ($userId) {
            // Update
            $stmt = $pdo->prepare("UPDATE users SET name=?,email=?,role=? WHERE id=?");
            $stmt->execute([$d['name'],$d['email'],$d['role'],$userId]);
            if (!empty($d['password'])) {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$d['password'],$userId]);
            }
            setFlash('success', 'User updated.');
        } else {
            // Create
            if (empty($d['password'])) { $errors[] = 'Password required for new user.'; }
            else {
                $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
                $chk->execute([$d['email']]);
                if ($chk->fetch()) { $errors[] = 'Email already in use.'; }
                else {
                    $password = $d['password'];
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
                            ->execute([$d['name'],$d['email'],$password,$d['role']]);
                        $newId = $pdo->lastInsertId();
                        if ($d['role'] === 'student') {
                            $pdo->prepare("INSERT INTO students (user_id,registration_number,course,year) VALUES (?,?,?,?)")
                                ->execute([$newId, $d['registration_number'] ?? 'REG-'.str_pad($newId,4,'0',STR_PAD_LEFT), $d['course'] ?? 'Not set', 1]);
                        } elseif ($d['role'] === 'lecturer') {
                            $pdo->prepare("INSERT INTO lecturers (user_id,department) VALUES (?,?)")
                                ->execute([$newId, $d['department'] ?? 'Not set']);
                        }
                        $pdo->commit();
                        setFlash('success', 'User created successfully.');
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = 'Failed to create user: ' . $e->getMessage();
                    }
                }
            }
        }
        if (empty($errors)) { header('Location: /internship-system/admin/users.php'); exit; }
    }
    if (!empty($errors)) setFlash('danger', implode('<br>', $errors));
}

// Filters
$roleFilter = $_GET['role'] ?? 'all';
$sql  = "SELECT u.*, s.registration_number, l.department FROM users u
         LEFT JOIN students s ON s.user_id=u.id
         LEFT JOIN lecturers l ON l.user_id=u.id";
$params = [];
if ($roleFilter !== 'all') { $sql .= " WHERE u.role=?"; $params[] = $roleFilter; }
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();

$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editUser;

startLayout('Manage Users', 'User Management', 'Users', 'users');
?>

<div class="page-header">
  <div><h1>Users (<?= count($users) ?>)</h1><p class="breadcrumb">Admin / <span>Users</span></p></div>
  <div style="display:flex;gap:8px;align-items:center;">
    <a href="?role=all"      class="btn <?= $roleFilter==='all'      ?'btn-primary':'btn-outline' ?> btn-sm">All</a>
    <a href="?role=student"  class="btn <?= $roleFilter==='student'  ?'btn-amber'  :'btn-outline' ?> btn-sm">Students</a>
    <a href="?role=lecturer" class="btn <?= $roleFilter==='lecturer' ?'btn-teal'   :'btn-outline' ?> btn-sm">Lecturers</a>
    <a href="?role=admin"    class="btn <?= $roleFilter==='admin'    ?'btn-primary':'btn-outline' ?> btn-sm">Admins</a>
    <a href="?action=new" class="btn btn-amber btn-sm"><i class="fa fa-plus"></i> Add User</a>
  </div>
</div>

<?php if ($showForm): ?>
<div class="card mb-3 fade-up">
  <div class="card-header">
    <h3><?= $editUser ? 'Edit User' : 'Create New User' ?></h3>
    <a href="/internship-system/admin/users.php" class="btn btn-outline btn-sm">Cancel</a>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="save_user" value="1">
      <input type="hidden" name="user_id"   value="<?= $editUser['id'] ?? '' ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="req">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role <span class="req">*</span></label>
          <select name="role" id="adminRoleSelect" class="form-control" required>
            <option value="student"  <?= ($editUser['role']??'')  === 'student'  ? 'selected' : '' ?>>Student</option>
            <option value="lecturer" <?= ($editUser['role']??'')  === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
            <option value="admin"    <?= ($editUser['role']??'')  === 'admin'    ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password <?= $editUser ? '(leave blank to keep)' : '<span class="req">*</span>' ?></label>
          <input type="password" name="password" class="form-control" placeholder="<?= $editUser ? 'Leave blank to keep' : 'Min 8 characters' ?>">
        </div>
      </div>
      <!-- Student extras -->
      <div id="adminStudentFields">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Registration Number</label>
            <input type="text" name="registration_number" class="form-control" placeholder="e.g. 2024/CS/001">
          </div>
          <div class="form-group">
            <label class="form-label">Course</label>
            <input type="text" name="course" class="form-control" placeholder="e.g. BSc. Computer Science">
          </div>
        </div>
      </div>
      <!-- Lecturer extras -->
      <div id="adminLecturerFields" class="hidden">
        <div class="form-group" style="max-width:320px;">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control" placeholder="e.g. Computer Science">
        </div>
      </div>
      <button type="submit" class="btn btn-amber"><i class="fa fa-floppy-disk"></i> <?= $editUser ? 'Update User' : 'Create User' ?></button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>All Users</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:220px;">
  </div>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Details</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--navy);color:var(--amber-lt);display:grid;place-items:center;font-weight:700;font-size:.78rem;flex-shrink:0;">
                <?= strtoupper(substr($u['name'],0,1)) ?>
              </div>
              <strong><?= htmlspecialchars($u['name']) ?></strong>
            </div>
          </td>
          <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
          <td><?= statusBadge($u['role']) ?></td>
          <td class="text-muted">
            <?php if ($u['role'] === 'student'): ?>
              <?= htmlspecialchars($u['registration_number'] ?? '—') ?>
            <?php elseif ($u['role'] === 'lecturer'): ?>
              <?= htmlspecialchars($u['department'] ?? '—') ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="?edit=<?= $u['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
              <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
              <button class="btn btn-danger btn-xs btn-delete-user"
                      data-id="<?= $u['id'] ?>" data-token="<?= $csrfToken ?>">
                <i class="fa fa-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const adminRoleSelect    = document.getElementById('adminRoleSelect');
const adminStudentFields = document.getElementById('adminStudentFields');
const adminLecturerFields= document.getElementById('adminLecturerFields');
function toggleAdminFields() {
  const v = adminRoleSelect?.value;
  adminStudentFields?.classList.toggle('hidden', v !== 'student');
  adminLecturerFields?.classList.toggle('hidden', v !== 'lecturer');
}
adminRoleSelect?.addEventListener('change', toggleAdminFields);
toggleAdminFields();
</script>

<?php endLayout(); ?>
