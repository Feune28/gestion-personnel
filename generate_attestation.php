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

$nationalite = $p['nationalite'];

$cni = $p['cni'];

$fonction = $p['fonction'];

$matricule = $p['matricule'];

$contrat = $p['contrat'];

$date_embauche = $p['date_embauche'];

$date = date('d/m/Y');

ob_start();

include 'templates/attestation_travail.php';

$html = ob_get_clean();

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

/* GENERATION PDF */

$output = $dompdf->output();

/* NOM UNIQUE */

$file = 'documents/attestations/attestation_'.$id.'_'.time().'.pdf';

/* SAUVEGARDE PDF */

file_put_contents($file, $output);

/* ENREGISTREMENT HISTORIQUE */

$insert = $pdo->prepare(

    "INSERT INTO documents_generes(

    personnel_id,
    type_document,
    fichier

    ) VALUES(?,?,?)"

);

$insert->execute([

    $id,
    'Attestation travail',
    $file

]);

/* TELECHARGEMENT */

$dompdf->stream(

    'attestation_travail.pdf',

    array("Attachment" => false)

);

?>