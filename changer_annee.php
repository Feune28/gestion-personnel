<?php

session_start();

require 'connexion.php';

if(
    !isset($_SESSION['admin'])
){
    header('Location: login.php');
    exit();
}

if(isset($_POST['annee'])){

    $id = (int) $_POST['annee'];

    $sql = $pdo->prepare(

        "SELECT * FROM annees_scolaires
         WHERE id=?"

    );

    $sql->execute([$id]);

    $annee = $sql->fetch();

    if($annee){

        $_SESSION['annee_id']
        = $annee['id'];

        $_SESSION['annee_libelle']
        = $annee['libelle'];

    }

}

header('Location: dashboard.php');

exit();