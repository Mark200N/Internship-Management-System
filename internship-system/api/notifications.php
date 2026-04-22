<?php
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'read') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    echo json_encode(['ok' => true]);

} elseif ($action === 'read_all') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->execute([$userId]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/internship-system/dashboard.php'));

} elseif ($action === 'clear_all') {
    requireRole('admin');
    $pdo->query("DELETE FROM notifications");
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
