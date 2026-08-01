<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/connexion.php';
require_once __DIR__ . '/controllers/LoginController.php';

$controller = new LoginController($pdo);

$controller->connexion();