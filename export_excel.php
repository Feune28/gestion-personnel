<?php

require 'connexion.php';

header("Content-Type: application/xls");
header("Content-Disposition: attachment; filename=personnels.xls");

echo "Nom\tPrenom\tFonction\tMatricule\tStatut\n";

$sql = $pdo->query(

    "SELECT * FROM personnels"

);

while($p = $sql->fetch()){

    echo
    $p['nom']."\t".
    $p['prenom']."\t".
    $p['fonction']."\t".
    $p['matricule']."\t".
    $p['statut']."\n";

}

?>