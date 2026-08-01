<?php

if(!isset($pdo)){
    require 'connexion.php';
}

date_default_timezone_set('Africa/Abidjan');

$moisActuel = (int)date('m');
$anneeActuelle = (int)date('Y');

$intelligenceDAF = [];
$alertesDAF = [];
$anomaliesDAF = [];
$recommandationsDAF = [];

/* ===============================
   SÉCURITÉ : VÉRIFIER SI TABLE EXISTE
================================ */

function tableExiste($pdo, $table){
    $req = $pdo->prepare("SHOW TABLES LIKE ?");
    $req->execute([$table]);
    return $req->rowCount() > 0;
}

/* ===============================
   VALEURS PAR DÉFAUT
================================ */

$masseSalariale = 0;
$totalAvances = 0;
$totalRetenues = 0;
$totalImpayes = 0;
$salairesEnregistres = 0;
$personnelsActifs = 0;
$nbNonPayes = 0;
$nbPartiels = 0;
$nbSalairesNegatifs = 0;
$nbDoublonsSalaires = 0;

/* ===============================
   PERSONNEL ACTIF
================================ */

if(tableExiste($pdo, 'personnels')){
    $personnelsActifs = (int)$pdo->query("
        SELECT COUNT(*)
        FROM personnels
        WHERE statut = 'actif'
    ")->fetchColumn();
}

/* ===============================
   SALAIRES
================================ */

if(tableExiste($pdo, 'salaires')){

    $req = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_salaires,
            COALESCE(SUM(salaire_net), 0) AS masse
        FROM salaires
        WHERE mois = ?
        AND annee = ?
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $dataSalaires = $req->fetch();

    $salairesEnregistres = (int)($dataSalaires['total_salaires'] ?? 0);
    $masseSalariale = (float)($dataSalaires['masse'] ?? 0);

    $intelligenceDAF[] = [
        'type' => 'info',
        'message' => "La masse salariale du mois est de " . number_format($masseSalariale, 0, ',', ' ') . " FCFA."
    ];

    if($salairesEnregistres > 0){
        $intelligenceDAF[] = [
            'type' => 'info',
            'message' => "{$salairesEnregistres} salaire(s) ont été enregistré(s) pour ce mois."
        ];
    }

}else{
    $alertesDAF[] = [
        'niveau' => 'warning',
        'message' => "La table salaires est introuvable. Les analyses salariales sont limitées."
    ];
}

/* ===============================
   AVANCES
================================ */

if(tableExiste($pdo, 'avances')){

    $req = $pdo->prepare("
        SELECT COALESCE(SUM(montant), 0)
        FROM avances
        WHERE MONTH(date_avance) = ?
        AND YEAR(date_avance) = ?
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $totalAvances = (float)$req->fetchColumn();

    if($totalAvances > 0){
        $intelligenceDAF[] = [
            'type' => 'info',
            'message' => "Les avances accordées ce mois-ci s’élèvent à " . number_format($totalAvances, 0, ',', ' ') . " FCFA."
        ];
    }

}else{
    $alertesDAF[] = [
        'niveau' => 'warning',
        'message' => "La table avances est introuvable."
    ];
}

/* ===============================
   RETENUES
================================ */

if(tableExiste($pdo, 'retenues')){

    $req = $pdo->prepare("
        SELECT COALESCE(SUM(montant), 0)
        FROM retenues
        WHERE MONTH(date_retenue) = ?
        AND YEAR(date_retenue) = ?
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $totalRetenues = (float)$req->fetchColumn();

    if($totalRetenues > 0){
        $intelligenceDAF[] = [
            'type' => 'info',
            'message' => "Les retenues du mois représentent " . number_format($totalRetenues, 0, ',', ' ') . " FCFA."
        ];
    }

}else{
    $alertesDAF[] = [
        'niveau' => 'warning',
        'message' => "La table retenues est introuvable."
    ];
}

/* ===============================
   IMPAYÉS
================================ */

if(tableExiste($pdo, 'impayes')){

    $req = $pdo->prepare("
        SELECT COUNT(*)
        FROM impayes
        WHERE mois = ?
        AND annee = ?
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $totalImpayes = (int)$req->fetchColumn();

    if($totalImpayes > 0){
        $alertesDAF[] = [
            'niveau' => 'danger',
            'message' => "{$totalImpayes} impayé(s) détecté(s) pour ce mois."
        ];
    }

}elseif(tableExiste($pdo, 'impayés')){

    $req = $pdo->prepare("
        SELECT COUNT(*)
        FROM `impayés`
        WHERE mois = ?
        AND annee = ?
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $totalImpayes = (int)$req->fetchColumn();

    if($totalImpayes > 0){
        $alertesDAF[] = [
            'niveau' => 'danger',
            'message' => "{$totalImpayes} impayé(s) détecté(s) pour ce mois."
        ];
    }

}

/* ===============================
   ANOMALIES FINANCIÈRES
================================ */

if(tableExiste($pdo, 'salaires')){

    /* Salaires nets négatifs */
    $req = $pdo->prepare("
        SELECT COUNT(*)
        FROM salaires
        WHERE mois = ?
        AND annee = ?
        AND salaire_net < 0
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $nbSalairesNegatifs = (int)$req->fetchColumn();

    if($nbSalairesNegatifs > 0){
        $anomaliesDAF[] = [
            'niveau' => 'danger',
            'message' => "{$nbSalairesNegatifs} salaire(s) net(s) négatif(s) détecté(s)."
        ];
    }

    /* Salaires enregistrés mais non payés */
    $req = $pdo->prepare("
        SELECT COUNT(*)
        FROM salaires
        WHERE mois = ?
        AND annee = ?
        AND statut = 'non_paye'
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $nbNonPayes = (int)$req->fetchColumn();

    if($nbNonPayes > 0){
        $anomaliesDAF[] = [
            'niveau' => 'danger',
            'message' => "{$nbNonPayes} salaire(s) enregistré(s) mais non payé(s) ce mois-ci."
        ];
    }

    /* Salaires partiellement payés */
    $req = $pdo->prepare("
        SELECT COUNT(*)
        FROM salaires
        WHERE mois = ?
        AND annee = ?
        AND statut = 'partiellement_paye'
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $nbPartiels = (int)$req->fetchColumn();

    if($nbPartiels > 0){
        $anomaliesDAF[] = [
            'niveau' => 'warning',
            'message' => "{$nbPartiels} salaire(s) partiellement payé(s) ce mois-ci."
        ];
    }

    /* Double salaire pour un même personnel */
    $req = $pdo->prepare("
        SELECT personnel_id, COUNT(*) AS total
        FROM salaires
        WHERE mois = ?
        AND annee = ?
        GROUP BY personnel_id
        HAVING total > 1
    ");
    $req->execute([$moisActuel, $anneeActuelle]);
    $doublonsSalaires = $req->fetchAll();
    $nbDoublonsSalaires = count($doublonsSalaires);

    foreach($doublonsSalaires as $d){
        $anomaliesDAF[] = [
            'niveau' => 'danger',
            'message' => "Double salaire détecté pour le personnel ID {$d['personnel_id']} ce mois-ci."
        ];
    }

    /* Personnel actif sans salaire enregistré */
    if($personnelsActifs > 0 && $salairesEnregistres < $personnelsActifs){
        $manquants = $personnelsActifs - $salairesEnregistres;

        $alertesDAF[] = [
            'niveau' => 'warning',
            'message' => "{$manquants} personnel(s) actif(s) n’ont pas encore de salaire enregistré ce mois-ci."
        ];
    }
}

/* ===============================
   RECOMMANDATIONS INTELLIGENTES
================================ */

if($nbNonPayes > 0){
    $recommandationsDAF[] = "Régler les {$nbNonPayes} salaire(s) non payé(s) afin d’éviter les retards de paiement.";
}

if($nbPartiels > 0){
    $recommandationsDAF[] = "Compléter le paiement des {$nbPartiels} salaire(s) partiellement réglé(s).";
}

if($totalImpayes > 0){
    $recommandationsDAF[] = "Traiter les {$totalImpayes} impayé(s) avant la clôture mensuelle.";
}

if($personnelsActifs > 0 && $salairesEnregistres < $personnelsActifs){
    $manquants = $personnelsActifs - $salairesEnregistres;
    $recommandationsDAF[] = "Créer les fiches de salaire des {$manquants} personnel(s) actif(s) qui n’ont pas encore de salaire enregistré.";
}

if($nbDoublonsSalaires > 0){
    $recommandationsDAF[] = "Vérifier et supprimer les doublons de salaires afin d’éviter un double paiement.";
}

if($nbSalairesNegatifs > 0){
    $recommandationsDAF[] = "Corriger les salaires nets négatifs avant validation des paiements.";
}

if($masseSalariale > 0 && $totalAvances > ($masseSalariale * 0.30)){
    $recommandationsDAF[] = "Les avances dépassent 30% de la masse salariale. Une vérification de la politique d’avances est conseillée.";
}

if($totalRetenues > 0 && $masseSalariale > 0 && $totalRetenues > ($masseSalariale * 0.25)){
    $recommandationsDAF[] = "Les retenues représentent plus de 25% de la masse salariale. Vérifier les motifs et les montants.";
}

if(count($anomaliesDAF) > 0){
    $recommandationsDAF[] = "Contrôler les anomalies financières avant la validation finale des paiements.";
}

if(count($alertesDAF) === 0 && count($anomaliesDAF) === 0){
    $recommandationsDAF[] = "Aucune alerte financière majeure détectée. La situation financière est stable.";
}

/* ===============================
   SCORE DAF
================================ */

$scoreDAF = 100;

$scoreDAF -= count($alertesDAF) * 5;
$scoreDAF -= count($anomaliesDAF) * 10;

if($scoreDAF < 0){
    $scoreDAF = 0;
}

$niveauDAF = 'Excellent';

if($scoreDAF < 80){
    $niveauDAF = 'Bon';
}

if($scoreDAF < 60){
    $niveauDAF = 'À surveiller';
}

if($scoreDAF < 40){
    $niveauDAF = 'Critique';
}

?>