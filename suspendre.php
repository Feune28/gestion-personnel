<?php

session_start();

require 'connexion.php';

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if($id <= 0){
    header('Location: personnel.php');
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
    | SUSPENDRE LE DOSSIER
    |--------------------------------------------------------------------------
    */

    $sql = $pdo->prepare("
        UPDATE personnels
        SET statut = 'suspendu'
        WHERE id = ?
    ");

    $sql->execute([$id]);

    /*
    |--------------------------------------------------------------------------
    | DÉSACTIVER LE COMPTE PERSONNEL
    |--------------------------------------------------------------------------
    */

    $compte = $pdo->prepare("
        UPDATE comptes_personnels
        SET actif = 0
        WHERE personnel_id = ?
    ");

    $compte->execute([$id]);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION
    |--------------------------------------------------------------------------
    */

    $message = $p['nom'] . ' ' . $p['prenom'] . ' a été suspendu';

    $notif = $pdo->prepare("
        INSERT INTO notifications(message)
        VALUES(?)
    ");

    $notif->execute([$message]);

    $pdo->commit();

    header('Location: personnel.php?success=suspension');
    exit();

} catch(Throwable $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    die("Impossible de suspendre ce personnel.");
}