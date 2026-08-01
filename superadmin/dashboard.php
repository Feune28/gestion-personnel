<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/connexion.php';
require_once __DIR__ . '/includes/verifier_superadmin.php';
require_once __DIR__ . '/controllers/DashboardController.php';

$controller = new DashboardController($pdo);

$controller->index();