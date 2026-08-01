<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
|--------------------------------------------------------------------------
| Vérification de la session Super administrateur
|--------------------------------------------------------------------------
*/

if(
    !isset($_SESSION['admin_id']) ||
    !isset($_SESSION['admin_role']) ||
    $_SESSION['admin_role'] !== 'SUPER_ADMIN'
){
    header('Location: ../login.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| Expiration après 30 minutes d'inactivité
|--------------------------------------------------------------------------
*/

$dureeInactivite = 1800;

if(
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $dureeInactivite
){
    session_unset();
    session_destroy();

    header('Location: ../login.php?session=expiree');
    exit();
}

$_SESSION['last_activity'] = time();
