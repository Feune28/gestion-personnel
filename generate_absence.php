<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

require 'connexion.php';

use Dompdf\Dompdf;

$id = $_GET['id'];

$sql = $pdo->prepare(

    "SELECT * FROM personnels WHERE id=?"

);

$sql->execute([$id]);

$p = $sql->fetch();

if(!$p){

    die("Personnel introuvable");

}

$nom = $p['nom'].' '.$p['prenom'];

$fonction = $p['fonction'];

$date = date('d/m/Y');

ob_start();

include 'templates/autorisation_absence.php';

$html = ob_get_clean();

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$output = $dompdf->output();

$file = 'documents/attestations/absence_'.$id.'_'.time().'.pdf';

file_put_contents($file, $output);

/* HISTORIQUE */

$insert = $pdo->prepare(

    "INSERT INTO documents_generes(

    personnel_id,
    type_document,
    fichier

    ) VALUES(?,?,?)"

);

$insert->execute([

    $id,
    'Autorisation absence',
    $file

]);

$dompdf->stream(

    'autorisation_absence.pdf',

    array("Attachment" => false)

);