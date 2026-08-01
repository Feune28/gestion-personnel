<?php

declare(strict_types=1);

session_start();

if (
    isset($_SESSION['superadmin_id']) &&
    isset($_SESSION['superadmin_role']) &&
    $_SESSION['superadmin_role'] === 'SUPER_ADMIN'
) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;