<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false, // true en production HTTPS
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';

if(
    !isset($_SESSION['admin']) ||
    empty($_SESSION['admin'])
){
    header('Location: login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    die('Méthode non autorisée');

}

if(
    !isset($_POST['token']) ||
    !hash_equals($_SESSION['token'], $_POST['token'])
){

    die('Token CSRF invalide');

}

$id = (int)$_POST['id'];

$sql = $pdo->prepare(

    "SELECT fichier
    FROM documents_generes
    WHERE id=?"

);

$sql->execute([$id]);

$doc = $sql->fetch();

if($doc){

    $fichier = $doc['fichier'];

    // Supprimer le fichier PDF du dossier
    if(file_exists($fichier)){

        unlink($fichier);

    }

    // Supprimer de la base
    $delete = $pdo->prepare(

        "DELETE FROM documents_generes
        WHERE id=?"

    );

    $delete->execute([$id]);

}

header('Location: documents.php');

exit();

?>