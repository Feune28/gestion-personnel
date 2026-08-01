<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    !isset($_SESSION['superadmin_id']) ||
    !isset($_SESSION['superadmin_role']) ||
    $_SESSION['superadmin_role'] !== 'SUPER_ADMIN'
) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Expiration de session après 2 heures d’inactivité
|--------------------------------------------------------------------------
*/

$dureeSession = 7200;

$derniereActivite =
    $_SESSION['superadmin_derniere_activite'] ?? time();

if ((time() - $derniereActivite) > $dureeSession) {
    session_unset();
    session_destroy();

    header('Location: login.php?session=expiree');
    exit;
}

$_SESSION['superadmin_derniere_activite'] = time();