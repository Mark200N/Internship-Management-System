<?php
require_once __DIR__ . '/config/helpers.php';
requireLogin();

switch ($_SESSION['role']) {
    case 'admin':
        require __DIR__ . '/admin/dashboard.php';
        break;
    case 'lecturer':
        require __DIR__ . '/lecturer/dashboard.php';
        break;
    case 'student':
    default:
        require __DIR__ . '/student/dashboard.php';
        break;
}
