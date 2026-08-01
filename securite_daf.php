<?php

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

if(!isset($pdo)){
    require 'connexion.php';
}

$adminSession = $_SESSION['admin'];

$checkAdmin = $pdo->prepare("
    SELECT *
    FROM admins
    WHERE username = ?
       OR nom = ?
       OR email = ?
    LIMIT 1
");

$checkAdmin->execute([
    $adminSession,
    $adminSession,
    $adminSession
]);

$adminConnecte = $checkAdmin->fetch();

if(!$adminConnecte){
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$fonctionAdmin = strtoupper(trim($adminConnecte['fonction'] ?? ''));

if($fonctionAdmin !== 'DAF'){
    header('Location: dashboard.php');
    exit();
}
?>