<?php

declare(strict_types=1);

/**
 * Vérifie que l’utilisateur connecté est bien un Super Administrateur.
 */
function verifierSuperAdmin(): void
{
    if (
        !isset($_SESSION['superadmin_id'])
        || !isset($_SESSION['superadmin_role'])
        || $_SESSION['superadmin_role'] !== 'SUPER_ADMIN'
    ) {
        header('Location: login.php');
        exit;
    }
}