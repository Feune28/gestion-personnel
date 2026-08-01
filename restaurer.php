<?php

session_start();

require 'connexion.php';

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if($id <= 0){
    header('Location: suspendus.php');
    exit();
}

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | RÉCUPÉRER LE PERSONNEL
    |--------------------------------------------------------------------------
    */

    $get = $pdo->prepare("
        SELECT nom, prenom
        FROM personnels
        WHERE id = ?
        LIMIT 1
    ");

    $get->execute([$id]);

    $p = $get->fetch();

    if(!$p){
        throw new Exception("Personnel introuvable.");
    }

    /*
    |--------------------------------------------------------------------------
    | RÉACTIVER LE DOSSIER
    |--------------------------------------------------------------------------
    */

    $sql = $pdo->prepare("
        UPDATE personnels
        SET statut = 'actif'
        WHERE id = ?
    ");

    $sql->execute([$id]);

    /*
    |--------------------------------------------------------------------------
    | RÉACTIVER LE COMPTE DE CONNEXION
    |--------------------------------------------------------------------------
    */

    $compte = $pdo->prepare("
        UPDATE comptes_personnels
        SET actif = 1
        WHERE personnel_id = ?
    ");

    $compte->execute([$id]);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION
    |--------------------------------------------------------------------------
    */

    $message = $p['nom'] . ' ' . $p['prenom'] . ' a été restauré';

    $notif = $pdo->prepare("
        INSERT INTO notifications(message)
        VALUES(?)
    ");

    $notif->execute([$message]);

    $pdo->commit();

    header('Location: suspendus.php?success=restauration');
    exit();

} catch(Throwable $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    die("Impossible de restaurer ce personnel.");
}