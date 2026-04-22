<?php
require_once __DIR__ . '/../config/db.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ── Auth helpers ──────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /internship-system/login.php');
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    $roles = (array)$roles;
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: /internship-system/dashboard.php');
        exit;
    }
}

function currentUser(): array {
    global $pdo;
    if (!isLoggedIn()) return [];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: [];
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

// ── Sanitisation ──────────────────────────────────────────────────────────────

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function sanitizeInput(array $data): array {
    return array_map(fn($v) => is_string($v) ? trim($v) : $v, $data);
}

// ── Flash messages ────────────────────────────────────────────────────────────

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ── Notifications ─────────────────────────────────────────────────────────────

function addNotification(int $userId, string $title, string $message): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)");
    $stmt->execute([$userId, $title, $message]);
}

function unreadNotificationCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ── File upload ───────────────────────────────────────────────────────────────

function uploadFile(array $file, string $destDir): array {
    $allowed  = ['pdf', 'doc', 'docx'];
    $maxBytes = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code ' . $file['error']];
    }
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'File exceeds 5 MB limit'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return ['ok' => false, 'error' => 'Only PDF, DOC, DOCX allowed'];
    }

    // Validate MIME
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $validMimes = ['application/pdf','application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($mime, $validMimes, true)) {
        return ['ok' => false, 'error' => 'Invalid file type'];
    }

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest    = rtrim($destDir, '/') . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Could not save file'];
    }

    return ['ok' => true, 'path' => $dest, 'name' => $newName, 'original' => $file['name']];
}

// ── Misc ──────────────────────────────────────────────────────────────────────

function ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function statusBadge(string $status): string {
    $map = [
        'pending'   => 'badge-pending',
        'approved'  => 'badge-approved',
        'rejected'  => 'badge-rejected',
        'active'    => 'badge-active',
        'completed' => 'badge-completed',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return "<span class=\"badge {$cls}\">" . ucfirst($status) . "</span>";
}

function getStudentProfile(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT s.*, u.name, u.email FROM students s
         JOIN users u ON u.id = s.user_id
         WHERE s.user_id = ?"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [];
}

function getLecturerProfile(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT l.*, u.name, u.email FROM lecturers l
         JOIN users u ON u.id = l.user_id
         WHERE l.user_id = ?"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [];
}
?>
