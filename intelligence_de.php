<?php

if(!isset($pdo)){
    require 'connexion.php';
}

date_default_timezone_set('Africa/Abidjan');

$today = date('Y-m-d');
$moisActuel = date('Y-m');

$intelligence = [];
$alertesAuto = [];
$anomalies = [];
$recommandations = [];

/* ===============================
   STATISTIQUES DE BASE
================================ */

$totalPersonnelIA = (int)$pdo->query("SELECT COUNT(*) FROM personnels")->fetchColumn();
$totalActifsIA = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE statut='actif'")->fetchColumn();
$totalSuspendusIA = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE statut='suspendu'")->fetchColumn();

$tauxActifs = $totalPersonnelIA > 0 ? round(($totalActifsIA / $totalPersonnelIA) * 100) : 0;

$intelligence[] = [
    'type' => 'info',
    'message' => "Le personnel actif représente {$tauxActifs}% de l’effectif total."
];

/* ===============================
   ALERTES AUTOMATIQUES
================================ */

$contratsExpirent = $pdo->query("
    SELECT nom, prenom, date_fin_contrat
    FROM personnels
    WHERE statut = 'actif'
    AND date_fin_contrat IS NOT NULL
    AND date_fin_contrat BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchAll();

foreach($contratsExpirent as $c){
    $alertesAuto[] = [
        'niveau' => 'warning',
        'message' => "Le contrat de {$c['nom']} {$c['prenom']} expire le {$c['date_fin_contrat']}."
    ];
}

$sansPhoto = (int)$pdo->query("
    SELECT COUNT(*)
    FROM personnels
    WHERE statut='actif'
    AND (photo IS NULL OR photo = '')
")->fetchColumn();

if($sansPhoto > 0){
    $alertesAuto[] = [
        'niveau' => 'warning',
        'message' => "{$sansPhoto} personnel(s) actif(s) n’ont pas de photo enregistrée."
    ];
}

$sansDiplome = (int)$pdo->query("
    SELECT COUNT(*)
    FROM personnels
    WHERE statut='actif'
    AND (diplome_pdf IS NULL OR diplome_pdf = '')
")->fetchColumn();

if($sansDiplome > 0){
    $alertesAuto[] = [
        'niveau' => 'warning',
        'message' => "{$sansDiplome} personnel(s) actif(s) n’ont pas de diplôme PDF enregistré."
    ];
}

$sansContrat = (int)$pdo->query("
    SELECT COUNT(*)
    FROM personnels
    WHERE statut='actif'
    AND (contrat_pdf IS NULL OR contrat_pdf = '')
")->fetchColumn();

if($sansContrat > 0){
    $alertesAuto[] = [
        'niveau' => 'danger',
        'message' => "{$sansContrat} personnel(s) actif(s) n’ont pas de contrat PDF enregistré."
    ];
}

/* ===============================
   DÉTECTION D’ANOMALIES
================================ */

$doublonsCNI = $pdo->query("
    SELECT cni, COUNT(*) AS total
    FROM personnels
    WHERE cni IS NOT NULL AND cni != ''
    GROUP BY cni
    HAVING total > 1
")->fetchAll();

foreach($doublonsCNI as $d){
    $anomalies[] = [
        'niveau' => 'danger',
        'message' => "CNI en doublon détectée : {$d['cni']} utilisée {$d['total']} fois."
    ];
}

$doublonsMatricule = $pdo->query("
    SELECT matricule, COUNT(*) AS total
    FROM personnels
    WHERE matricule IS NOT NULL AND matricule != ''
    GROUP BY matricule
    HAVING total > 1
")->fetchAll();

foreach($doublonsMatricule as $d){
    $anomalies[] = [
        'niveau' => 'danger',
        'message' => "Matricule en doublon détecté : {$d['matricule']} utilisé {$d['total']} fois."
    ];
}

$contratsExpiresActifs = $pdo->query("
    SELECT nom, prenom, date_fin_contrat
    FROM personnels
    WHERE statut='actif'
    AND date_fin_contrat IS NOT NULL
    AND date_fin_contrat < CURDATE()
")->fetchAll();

foreach($contratsExpiresActifs as $p){
    $anomalies[] = [
        'niveau' => 'danger',
        'message' => "{$p['nom']} {$p['prenom']} est actif alors que son contrat est expiré depuis le {$p['date_fin_contrat']}."
    ];
}

$profsSansEDT = $pdo->query("
    SELECT p.id, p.nom, p.prenom
    FROM personnels p
    LEFT JOIN emplois_temps_professeurs e ON e.personnel_id = p.id
    WHERE p.statut='actif'
    AND p.fonction='PROFESSEUR'
    GROUP BY p.id
    HAVING COUNT(e.id) = 0
")->fetchAll();

foreach($profsSansEDT as $prof){
    $anomalies[] = [
        'niveau' => 'warning',
        'message' => "Le professeur {$prof['nom']} {$prof['prenom']} n’a aucun emploi du temps enregistré."
    ];
}

/* ===============================
   RECOMMANDATIONS
================================ */

if($sansPhoto > 0){
    $recommandations[] = "Mettre à jour les photos des personnels concernés.";
}

if($sansDiplome > 0){
    $recommandations[] = "Compléter les dossiers administratifs avec les diplômes manquants.";
}

if($sansContrat > 0){
    $recommandations[] = "Numériser ou ajouter les contrats manquants.";
}

if(count($contratsExpirent) > 0){
    $recommandations[] = "Préparer le renouvellement des contrats proches de l’expiration.";
}

if(count($anomalies) === 0){
    $recommandations[] = "Aucune anomalie majeure détectée. Le système est stable.";
}

/* ===============================
   SYNTHÈSE
================================ */

$scoreIntelligence = 100;

$scoreIntelligence -= count($alertesAuto) * 5;
$scoreIntelligence -= count($anomalies) * 8;

if($scoreIntelligence < 0){
    $scoreIntelligence = 0;
}

$niveauSysteme = 'Excellent';

if($scoreIntelligence < 80){
    $niveauSysteme = 'Bon';
}

if($scoreIntelligence < 60){
    $niveauSysteme = 'À surveiller';
}

if($scoreIntelligence < 40){
    $niveauSysteme = 'Critique';
}

?>