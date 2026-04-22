<?php
// Root entry point — redirect to login or dashboard
require_once __DIR__ . '/config/helpers.php';
if (isLoggedIn()) {
    header('Location: /internship-system/dashboard.php');
} else {
    header('Location: /internship-system/login.php');
}
exit;
