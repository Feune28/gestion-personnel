<?php
session_start();
require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

if(!isset($_SESSION['personnel'])){
    header('Location: login_personnel.php');
    exit();
}

$personnel_id = (int)$_SESSION['personnel'];
$today = date('Y-m-d');
$now = date('H:i:s');

$message = "";
$error = "";

function inTimeRange($now, $start, $end){
    return strtotime($now) >= strtotime($start) && strtotime($now) <= strtotime($end);
}

function addMinutesToTime($time, $minutes){
    return date('H:i:s', strtotime($time . " +{$minutes} minutes"));
}

function toShortTime($time){
    if(empty($time)) return '-';
    return date('H:i', strtotime($time));
}

function badgePresence($statut){
    $s = strtolower(trim($statut ?? ''));

    if($s === 'present' || $s === 'présent'){
        return '<span class="presence-badge presence-present">Présent</span>';
    }

    if($s === 'retard'){
        return '<span class="presence-badge presence-retard">Retard</span>';
    }

    if($s === 'justifie' || $s === 'justifié'){
        return '<span class="presence-badge presence-justifie">Justifié</span>';
    }

    return '<span class="presence-badge presence-absent">Absent</span>';
}

function actionImpossible(){
    return "Action impossible. Veuillez voir le Directeur.";
}

function boutonDisabled($condition){
    return $condition ? 'disabled' : '';
}

function messageHoraire($debut, $fin){
    return toShortTime($debut) . " - " . toShortTime($fin);
}

$sql = $pdo->prepare("SELECT * FROM personnels WHERE id = ? LIMIT 1");
$sql->execute([$personnel_id]);
$personnel = $sql->fetch();

if(!$personnel){
    session_destroy();
    header('Location: login_personnel.php');
    exit();
}

$fonction = strtoupper(trim($personnel['fonction'] ?? ''));

$joursFr = [
    'Monday'    => 'Lundi',
    'Tuesday'   => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday'  => 'Jeudi',
    'Friday'    => 'Vendredi',
    'Saturday'  => 'Samedi',
    'Sunday'    => 'Dimanche'
];

$jour = $joursFr[date('l')] ?? '';
$isWeekend = in_array($jour, ['Samedi','Dimanche']);

$fonctionsWeekendAutorisees = [
    'GARDIEN',
    'TECHNICIEN DE SURFACE',
    'TECHNICIEN DE SURFACES',
    'TECHNICIEN SURFACE',
    'AGENT DE SURFACE',
    'AGENT DE SURFACES'
];

$weekendAutorise = in_array($fonction, $fonctionsWeekendAutorisees);
$isAdminNonPointe = in_array($fonction, ['DE','DAF']);
$isProfesseur = ($fonction === 'PROFESSEUR');
$isPersonnelSimple = !$isAdminNonPointe && !$isProfesseur;

$horaireSimple = [
    'arrivee_matin' => [
        'debut' => '07:45:00',
        'fin'   => '08:25:00',
        'retard_apres' => '08:10:00'
    ],
    'depart_matin' => [
        'debut' => '12:30:00',
        'fin'   => '13:00:00'
    ],
    'arrivee_aprem' => [
        'debut' => '13:30:00',
        'fin'   => '14:10:00',
        'retard_apres' => '13:50:00'
    ],
    'depart_aprem' => [
        'debut' => '18:00:00',
        'fin'   => '18:30:00'
    ]
];

function jourAutorise($isWeekend, $weekendAutorise){
    return !$isWeekend || $weekendAutorise;
}

if($isPersonnelSimple && isset($_POST['pointer_arrivee_matin'])){

    if(!jourAutorise($isWeekend, $weekendAutorise)){
        $error = actionImpossible();
    }elseif(!inTimeRange($now, $horaireSimple['arrivee_matin']['debut'], $horaireSimple['arrivee_matin']['fin'])){
        $error = actionImpossible();
    }else{
        $check = $pdo->prepare("SELECT * FROM presences WHERE personnel_id=? AND date_presence=? LIMIT 1");
        $check->execute([$personnel_id, $today]);
        $presence = $check->fetch();

        if($presence && !empty($presence['heure_arrivee'])){
            $error = "Vous avez déjà pointé votre arrivée du matin.";
        }else{
            $statut = ($now > $horaireSimple['arrivee_matin']['retard_apres']) ? 'retard' : 'present';

            if($presence){
                $update = $pdo->prepare("UPDATE presences SET heure_arrivee=?, statut=? WHERE id=?");
                $update->execute([$now, $statut, $presence['id']]);
            }else{
                $insert = $pdo->prepare("INSERT INTO presences(personnel_id,date_presence,heure_arrivee,statut) VALUES(?,?,?,?)");
                $insert->execute([$personnel_id, $today, $now, $statut]);
            }

            $message = "Arrivée du matin pointée avec succès.";
        }
    }
}

if($isPersonnelSimple && isset($_POST['pointer_depart_matin'])){

    if(!jourAutorise($isWeekend, $weekendAutorise)){
        $error = actionImpossible();
    }elseif(!inTimeRange($now, $horaireSimple['depart_matin']['debut'], $horaireSimple['depart_matin']['fin'])){
        $error = actionImpossible();
    }else{
        $check = $pdo->prepare("SELECT * FROM presences WHERE personnel_id=? AND date_presence=? LIMIT 1");
        $check->execute([$personnel_id, $today]);
        $presence = $check->fetch();

        if(!$presence || empty($presence['heure_arrivee'])){
            $error = "Vous devez d'abord pointer votre arrivée du matin.";
        }elseif(!empty($presence['heure_depart'])){
            $error = "Vous avez déjà pointé votre départ du matin.";
        }else{
            $update = $pdo->prepare("UPDATE presences SET heure_depart=? WHERE id=?");
            $update->execute([$now, $presence['id']]);
            $message = "Départ du matin pointé avec succès.";
        }
    }
}

if($isPersonnelSimple && isset($_POST['pointer_arrivee_aprem'])){

    if(!jourAutorise($isWeekend, $weekendAutorise)){
        $error = actionImpossible();
    }elseif(!inTimeRange($now, $horaireSimple['arrivee_aprem']['debut'], $horaireSimple['arrivee_aprem']['fin'])){
        $error = actionImpossible();
    }else{
        $check = $pdo->prepare("SELECT * FROM presences WHERE personnel_id=? AND date_presence=? LIMIT 1");
        $check->execute([$personnel_id, $today]);
        $presence = $check->fetch();

        if($presence && !empty($presence['heure_arrivee_aprem'])){
            $error = "Vous avez déjà pointé votre arrivée de l'après-midi.";
        }else{
            $statutAprem = ($now > $horaireSimple['arrivee_aprem']['retard_apres']) ? 'retard' : 'present';

            if($presence){
                $statutGlobal = strtolower($presence['statut'] ?? 'present');
                if($statutAprem === 'retard') $statutGlobal = 'retard';

                $update = $pdo->prepare("UPDATE presences SET heure_arrivee_aprem=?, statut=? WHERE id=?");
                $update->execute([$now, $statutGlobal, $presence['id']]);
            }else{
                $insert = $pdo->prepare("INSERT INTO presences(personnel_id,date_presence,heure_arrivee_aprem,statut) VALUES(?,?,?,?)");
                $insert->execute([$personnel_id, $today, $now, $statutAprem]);
            }

            $message = "Arrivée de l'après-midi pointée avec succès.";
        }
    }
}

if($isPersonnelSimple && isset($_POST['pointer_depart_aprem'])){

    if(!jourAutorise($isWeekend, $weekendAutorise)){
        $error = actionImpossible();
    }elseif(!inTimeRange($now, $horaireSimple['depart_aprem']['debut'], $horaireSimple['depart_aprem']['fin'])){
        $error = actionImpossible();
    }else{
        $check = $pdo->prepare("SELECT * FROM presences WHERE personnel_id=? AND date_presence=? LIMIT 1");
        $check->execute([$personnel_id, $today]);
        $presence = $check->fetch();

        if(!$presence || empty($presence['heure_arrivee_aprem'])){
            $error = "Vous devez d'abord pointer votre arrivée de l'après-midi.";
        }elseif(!empty($presence['heure_depart_aprem'])){
            $error = "Vous avez déjà pointé votre départ de l'après-midi.";
        }else{
            $update = $pdo->prepare("UPDATE presences SET heure_depart_aprem=? WHERE id=?");
            $update->execute([$now, $presence['id']]);
            $message = "Départ de l'après-midi pointé avec succès.";
        }
    }
}

if($isProfesseur && isset($_POST['pointer_arrivee_cours'])){

    $plage_horaire = trim($_POST['plage_horaire'] ?? '');

    $cours = $pdo->prepare("SELECT * FROM emplois_temps_professeurs WHERE personnel_id=? AND jour=? AND plage_horaire=? LIMIT 1");
    $cours->execute([$personnel_id, $jour, $plage_horaire]);
    $c = $cours->fetch();

    if(!$c){
        $error = "Cours introuvable.";
    }else{
        $heureDebut = $c['heure_debut'];
        $limiteArrivee = addMinutesToTime($heureDebut, 20);

        if(!inTimeRange($now, $heureDebut, $limiteArrivee)){
            $error = actionImpossible();
        }else{
            $check = $pdo->prepare("SELECT * FROM presences_professeurs WHERE personnel_id=? AND date_presence=? AND plage_horaire=? LIMIT 1");
            $check->execute([$personnel_id, $today, $plage_horaire]);
            $presenceCours = $check->fetch();

            if($presenceCours && !empty($presenceCours['heure_arrivee'])){
                $error = "Vous avez déjà pointé l'arrivée de ce cours.";
            }else{
                $statut = 'present';

                if($presenceCours){
                    $update = $pdo->prepare("UPDATE presences_professeurs SET heure_arrivee=?, heure_scan=?, statut=? WHERE id=?");
                    $update->execute([$now, $now, $statut, $presenceCours['id']]);
                }else{
                    $insert = $pdo->prepare("INSERT INTO presences_professeurs(personnel_id,date_presence,plage_horaire,heure_scan,heure_arrivee,statut) VALUES(?,?,?,?,?,?)");
                    $insert->execute([$personnel_id, $today, $plage_horaire, $now, $now, $statut]);
                }

                $message = "Arrivée du cours pointée avec succès.";
            }
        }
    }
}

if($isProfesseur && isset($_POST['pointer_depart_cours'])){

    $plage_horaire = trim($_POST['plage_horaire'] ?? '');

    $cours = $pdo->prepare("SELECT * FROM emplois_temps_professeurs WHERE personnel_id=? AND jour=? AND plage_horaire=? LIMIT 1");
    $cours->execute([$personnel_id, $jour, $plage_horaire]);
    $c = $cours->fetch();

    if(!$c){
        $error = "Cours introuvable.";
    }else{
        $heureFin = $c['heure_fin'];
        $limiteDepart = addMinutesToTime($heureFin, 30);

        if(!inTimeRange($now, $heureFin, $limiteDepart)){
            $error = actionImpossible();
        }else{
            $check = $pdo->prepare("SELECT * FROM presences_professeurs WHERE personnel_id=? AND date_presence=? AND plage_horaire=? LIMIT 1");
            $check->execute([$personnel_id, $today, $plage_horaire]);
            $presenceCours = $check->fetch();

            if(!$presenceCours || empty($presenceCours['heure_arrivee'])){
                $error = "Vous devez d'abord pointer l'arrivée de ce cours.";
            }elseif(!empty($presenceCours['heure_depart'])){
                $error = "Vous avez déjà pointé le départ de ce cours.";
            }else{
                $update = $pdo->prepare("UPDATE presences_professeurs SET heure_depart=? WHERE id=?");
                $update->execute([$now, $presenceCours['id']]);
                $message = "Départ du cours pointé avec succès.";
            }
        }
    }
}

$presenceJour = null;

if($isPersonnelSimple){
    $reqPresence = $pdo->prepare("SELECT * FROM presences WHERE personnel_id=? AND date_presence=? LIMIT 1");
    $reqPresence->execute([$personnel_id, $today]);
    $presenceJour = $reqPresence->fetch();
}

$coursJour = [];

if($isProfesseur){
    $reqCours = $pdo->prepare("
        SELECT e.*,
               pp.id AS presence_id,
               pp.heure_scan,
               pp.heure_arrivee,
               pp.heure_depart,
               pp.statut AS statut_presence
        FROM emplois_temps_professeurs e
        LEFT JOIN presences_professeurs pp
            ON pp.personnel_id = e.personnel_id
            AND pp.date_presence = ?
            AND pp.plage_horaire = e.plage_horaire
        WHERE e.personnel_id = ?
        AND e.jour = ?
        ORDER BY e.heure_debut ASC
    ");
    $reqCours->execute([$today, $personnel_id, $jour]);
    $coursJour = $reqCours->fetchAll();
}

$filtreJour = '';
$filtreMois = '';

$joursFiltres = [
    '1' => 'Lundi',
    '2' => 'Mardi',
    '3' => 'Mercredi',
    '4' => 'Jeudi',
    '5' => 'Vendredi',
    '6' => 'Samedi'
];

if($isProfesseur){
    $reqHist = $pdo->prepare("
        SELECT *
        FROM presences_professeurs
        WHERE personnel_id = ?
        ORDER BY date_presence DESC, id DESC
        LIMIT 100
    ");
}else{
    $reqHist = $pdo->prepare("
        SELECT *
        FROM presences
        WHERE personnel_id = ?
        ORDER BY date_presence DESC, id DESC
        LIMIT 100
    ");
}

$reqHist->execute([$personnel_id]);
$historique = $reqHist->fetchAll();

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$periodePointage = null;
$actionArriveeName = '';
$actionDepartName = '';
$arriveeDisabled = true;
$departDisabled = true;
$libellePeriode = 'Hors plage de pointage';

if($isPersonnelSimple && (!$isWeekend || $weekendAutorise)){

    if(
        inTimeRange($now, $horaireSimple['arrivee_matin']['debut'], $horaireSimple['arrivee_matin']['fin'])
        || inTimeRange($now, $horaireSimple['depart_matin']['debut'], $horaireSimple['depart_matin']['fin'])
    ){
        $periodePointage = 'matin';
        $libellePeriode = 'Session du matin';
        $actionArriveeName = 'pointer_arrivee_matin';
        $actionDepartName = 'pointer_depart_matin';

        $arriveeDisabled =
            !inTimeRange($now, $horaireSimple['arrivee_matin']['debut'], $horaireSimple['arrivee_matin']['fin'])
            || !empty($presenceJour['heure_arrivee']);

        $departDisabled =
            !inTimeRange($now, $horaireSimple['depart_matin']['debut'], $horaireSimple['depart_matin']['fin'])
            || empty($presenceJour['heure_arrivee'])
            || !empty($presenceJour['heure_depart']);

    }elseif(
        inTimeRange($now, $horaireSimple['arrivee_aprem']['debut'], $horaireSimple['arrivee_aprem']['fin'])
        || inTimeRange($now, $horaireSimple['depart_aprem']['debut'], $horaireSimple['depart_aprem']['fin'])
    ){
        $periodePointage = 'apres-midi';
        $libellePeriode = "Session de l'après-midi";
        $actionArriveeName = 'pointer_arrivee_aprem';
        $actionDepartName = 'pointer_depart_aprem';

        $arriveeDisabled =
            !inTimeRange($now, $horaireSimple['arrivee_aprem']['debut'], $horaireSimple['arrivee_aprem']['fin'])
            || !empty($presenceJour['heure_arrivee_aprem']);

        $departDisabled =
            !inTimeRange($now, $horaireSimple['depart_aprem']['debut'], $horaireSimple['depart_aprem']['fin'])
            || empty($presenceJour['heure_arrivee_aprem'])
            || !empty($presenceJour['heure_depart_aprem']);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes présences | Espace Personnel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --bg:#f3f6fb;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#e2e8f0;
    --primary:#1d4ed8;
    --primary-2:#2563eb;
    --primary-soft:#dbeafe;
    --gold:#f59e0b;
    --terracotta:#c96b58;
    --terracotta-soft:#fdf4f2;
    --terracotta-border:#f0c9c1;
    --success:#16a34a;
    --danger:#dc2626;
    --shadow:0 18px 45px rgba(15,23,42,.08);
    --shadow-sm:0 8px 24px rgba(15,23,42,.06);
    --radius:22px;
}

body{
    font-family:'Inter',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.13), transparent 28%),
        radial-gradient(circle at top right, rgba(245,158,11,.10), transparent 26%),
        var(--bg);
    color:var(--text);
    transition:all .2s ease;
    overflow-x:hidden;
}

h1,h2,h3,.page-title,.card-title-custom{
    font-family:'Sora',sans-serif;
}

/* SIDEBAR COMME DE/DAF + COULEUR PERSONNEL */

.sidebar{
    position:fixed;
    top:10px;
    left:10px;
    bottom:10px;
    width:280px;
    height:calc(100vh - 20px);
    padding:16px 16px 18px;
    background:#111827;
    border:2.5px solid rgba(15,23,42,.92);
    border-radius:34px;
    box-shadow:var(--shadow);
    overflow:hidden;
    z-index:999;
    transition:width .25s ease,padding .25s ease;
}

.logo{
    color:white;
    text-align:center;
    margin-bottom:12px;
    padding:6px 8px 12px;
    border-bottom:1px solid rgba(255,255,255,.10);
}

.logo img{
    width:58px;
    height:58px;
    border-radius:19px;
    object-fit:cover;
    margin-bottom:8px;
    border:3px solid rgba(255,255,255,.92);
    box-shadow:0 12px 26px rgba(0,0,0,.20);
}

.logo h4{
    color:white;
    font-family:'Sora',sans-serif;
    font-size:14px;
    font-weight:800;
    margin-top:4px;
}

.menu{
    height:calc(100vh - 128px);
    min-height:430px;
    padding-left:8px;
    padding-right:4px;
    padding-bottom:42px;
    overflow-y:auto;
    overflow-x:hidden;
    direction:rtl;
    scrollbar-width:thin;
    scrollbar-color:var(--terracotta) #1f2937;
}

.menu > *{
    direction:ltr;
}

.menu::-webkit-scrollbar{
    width:5px;
}

.menu::-webkit-scrollbar-track{
    background:#1f2937;
}

.menu::-webkit-scrollbar-thumb{
    background:var(--terracotta);
    border-radius:999px;
}

.menu-section{
    margin-bottom:10px;
}

.menu-title{
    color:#6b7280;
    font-size:9.5px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.14em;
    margin:0 0 5px 12px;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:#9ca3af;
    padding:9px 11px;
    border-radius:16px;
    margin-bottom:3px;
    min-height:43px;
    white-space:nowrap;
    font-weight:700;
    font-size:13px;
    position:relative;
    border:1px solid transparent;
    background:transparent;
    transition:.2s;
}

.menu a i{
    width:29px;
    height:29px;
    min-width:29px;
    border-radius:12px;
    display:grid;
    place-items:center;
    background:#1f2937;
    color:#d1d5db;
    font-size:15px;
}

.menu a:hover,
.menu a.active{
    background:rgba(201,107,88,.15);
    border-color:rgba(201,107,88,.18);
    color:#e5a498;
    box-shadow:0 10px 24px rgba(0,0,0,.10);
}

.menu a.active::before{
    content:'';
    position:absolute;
    left:-8px;
    top:13px;
    bottom:13px;
    width:4px;
    border-radius:999px;
    background:linear-gradient(180deg,#c96b58,#9f4f42);
}

.menu a:hover i,
.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    box-shadow:0 10px 20px rgba(201,107,88,.25);
}

/* COLLAPSE */

body.sidebar-collapsed .sidebar{
    width:84px!important;
    padding-left:10px!important;
    padding-right:10px!important;
}

body.sidebar-collapsed .main{
    margin-left:114px!important;
}

body.sidebar-collapsed .logo h4,
body.sidebar-collapsed .menu-title,
body.sidebar-collapsed .menu a span{
    display:none;
}

body.sidebar-collapsed .logo img{
    width:48px;
    height:48px;
    border-radius:17px;
}

body.sidebar-collapsed .menu{
    padding-left:4px;
    padding-right:0;
    padding-bottom:42px;
}

body.sidebar-collapsed .menu a{
    padding:10px 7px;
    justify-content:center;
}

body.sidebar-collapsed .menu a i{
    width:34px;
    height:34px;
    min-width:34px;
}

/* MAIN */

.main{
    margin-left:310px;
    padding:32px 40px;
    min-height:100vh;
    transition:margin-left .25s ease;
}

.topbar,
.card-box,
.dashboard-footer{
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    box-shadow:var(--shadow-sm);
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:16px;
    padding:20px 24px;
}

.top-left{
    display:flex;
    align-items:center;
    gap:13px;
}

.sidebar-toggle,
.dark-toggle,
.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--terracotta);
    display:grid;
    place-items:center;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    transition:all .2s ease;
    flex:0 0 auto;
    text-decoration:none;
}

.sidebar-toggle:hover,
.dark-toggle:hover,
.back-btn:hover{
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

.page-title{
    font-size:27px;
    font-weight:800;
    color:var(--text);
    margin:0;
}

.page-subtitle{
    color:var(--muted);
    font-size:13px;
    margin-top:4px;
}

.header-actions{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.year-badge{
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:var(--terracotta);
}

.badge-role{
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    color:var(--terracotta);
    border-radius:40px;
    padding:8px 16px;
    font-size:13px;
    font-weight:800;
}

.card-box{
    padding:24px;
    margin-bottom:28px;
}

.card-title-custom{
    font-size:18px;
    font-weight:800;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--text);
}

.card-title-custom i{
    color:var(--terracotta);
}

.personal-doc{
    border:1px solid var(--line);
    border-radius:18px;
    padding:16px;
    margin-bottom:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    background:#fff;
    transition:.2s;
}

.personal-doc:hover{
    transform:translateY(-2px);
    box-shadow:var(--shadow-sm);
}

.doc-badge{
    background:var(--terracotta-soft);
    color:var(--terracotta);
    border:1px solid var(--terracotta-border);
    border-radius:30px;
    padding:7px 12px;
    font-size:12px;
    font-weight:800;
}

.table{
    margin:0;
}

.table thead th{
    background:#111827;
    color:white;
    border:none;
    padding:14px;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.table tbody td{
    padding:14px;
    vertical-align:middle;
    font-size:13px;
}

.empty-state{
    text-align:center;
    color:#9ca3af;
    padding:50px;
}

.btn-standard{
    border-radius:13px;
    font-size:12px;
    font-weight:800;
    padding:8px 12px;
}

.dashboard-footer{
    margin-top:32px;
    padding:14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    color:var(--muted);
    font-size:12px;
    font-weight:500;
}

.footer-left{
    display:flex;
    align-items:center;
    gap:10px;
}

.footer-left img{
    width:30px;
    height:30px;
    border-radius:8px;
    object-fit:cover;
}

.footer-secure{
    display:flex;
    align-items:center;
    gap:6px;
    color:#166534;
    font-weight:700;
}

/* DARK MODE */

body.dark-mode{
    --bg:#0b1120;
    --card:#111827;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#2d3340;
    background:#0b1120;
}

body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .dashboard-footer,
body.dark-mode .personal-doc,
body.dark-mode .sidebar-toggle,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .sidebar{
    border-color:rgba(255,255,255,.78)!important;
}

body.dark-mode .page-title,
body.dark-mode .card-title-custom{
    color:#f0f2f5;
}

body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}

body.dark-mode .table thead th{
    background:#111827;
}

body.dark-mode .badge-role,
body.dark-mode .year-badge,
body.dark-mode .doc-badge{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}

/* RESPONSIVE */

@media(max-width:900px){
    .sidebar{
        top:8px!important;
        left:8px!important;
        bottom:8px!important;
        width:265px!important;
        height:calc(100vh - 16px)!important;
        padding:14px 14px 16px!important;
        border-radius:32px!important;
    }

    .menu{
        height:calc(100vh - 116px)!important;
        min-height:420px!important;
        padding-bottom:46px!important;
    }

    .main{
        margin-left:285px!important;
        padding:18px 12px!important;
    }

    body.sidebar-collapsed .sidebar{
        width:78px!important;
    }

    body.sidebar-collapsed .main{
        margin-left:96px!important;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .header-actions{
        width:100%;
        flex-wrap:wrap;
    }

    .personal-doc{
        flex-direction:column;
        align-items:flex-start;
    }

    .dashboard-footer{
        flex-direction:column;
        align-items:flex-start;
    }
}


/* PRESENCES - MEME PALETTE QUE DOCUMENTS_PERSONNEL_USER */

.notice{
    border:1px solid var(--line);
    border-left:4px solid var(--terracotta);
    border-radius:18px;
    padding:15px 16px;
    margin-bottom:20px;
    background:#fff;
    color:var(--text);
    box-shadow:var(--shadow-sm);
}

.notice i{
    color:var(--terracotta);
    margin-right:7px;
}

.course-card,
.status-box,
.pointage-actions{
    border:1px solid var(--line);
    border-radius:18px;
    padding:18px;
    margin-bottom:14px;
    background:#fff;
    transition:.2s;
}

.course-card:hover{
    transform:translateY(-2px);
    box-shadow:var(--shadow-sm);
}

.course-card.active-window{
    border-color:var(--terracotta-border);
    background:var(--terracotta-soft);
}

.course-card.out-window{
    background:#fff;
}

.time-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    border-radius:20px;
    padding:6px 10px;
    font-size:12px;
    font-weight:800;
    color:var(--terracotta);
    margin-top:5px;
}

.pointage-session{
    display:inline-flex;
    align-items:center;
    gap:7px;
    margin-bottom:14px;
    padding:7px 11px;
    border-radius:999px;
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    color:var(--terracotta);
    font-size:12px;
    font-weight:800;
}

.btn-pointage{
    min-width:170px;
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-weight:800;
    transition:.2s;
}

.btn-arrivee{
    background:#111827!important;
    color:#fff!important;
}

.btn-arrivee:hover:not(:disabled){
    background:#000!important;
    color:#fff!important;
}

.btn-depart{
    background:var(--terracotta)!important;
    color:#fff!important;
}

.btn-depart:hover:not(:disabled){
    background:#9f4f42!important;
    color:#fff!important;
}

.btn-pointage:disabled{
    background:#e5e7eb!important;
    color:#9ca3af!important;
    opacity:1;
}

.presence-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:999px;
    padding:6px 10px;
    font-size:11px;
    font-weight:800;
    border:1px solid var(--terracotta-border);
    background:var(--terracotta-soft);
    color:var(--terracotta);
}

.filter-card{
    display:flex;
    align-items:end;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:20px;
    padding:16px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:18px;
}

.filter-group{
    min-width:190px;
    flex:1 1 190px;
}

.filter-label{
    display:block;
    margin-bottom:6px;
    color:var(--text);
    font-size:12px;
    font-weight:800;
}

.filter-control{
    width:100%;
    min-height:42px;
    border:1px solid var(--line);
    border-radius:12px;
    padding:8px 12px;
    background:#fff;
    color:var(--text);
}

.btn-filter,
.btn-reset{
    min-height:42px;
    border-radius:12px;
    padding:9px 16px;
    font-weight:800;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
}

.btn-filter{
    border:none;
    background:var(--terracotta);
    color:#fff;
}

.btn-filter:hover{
    background:#9f4f42;
    color:#fff;
}

.btn-reset{
    border:1px solid var(--line);
    background:#111827;
    color:#fff;
}

.btn-reset:hover{
    background:#000;
    color:#fff;
}

.history-card{
    width:100%;
    max-width:100%;
    overflow:hidden;
}

.history-card .table-responsive{
    width:100%;
    overflow-x:auto;
}

.history-card table{
    min-width:950px;
}

body.dark-mode .notice,
body.dark-mode .course-card,
body.dark-mode .status-box,
body.dark-mode .pointage-actions,
body.dark-mode .filter-card,
body.dark-mode .filter-control{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .course-card.active-window,
body.dark-mode .time-badge,
body.dark-mode .pointage-session,
body.dark-mode .presence-badge{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}

@media(max-width:900px){
    .filter-group,
    .btn-filter,
    .btn-reset,
    .pointage-actions form,
    .btn-pointage{
        width:100%;
    }
}

/* CORRECTIONS FINALES DEMANDEES */

.sidebar{
    background:#111827!important;
    border:2.5px solid rgba(15,23,42,.92)!important;
}

.logo{
    color:#fff!important;
    border-bottom-color:rgba(255,255,255,.10)!important;
}

.logo h4{
    color:#fff!important;
}

.menu{
    scrollbar-color:var(--terracotta) #1f2937!important;
}

.menu::-webkit-scrollbar-track{
    background:#1f2937!important;
}

.menu::-webkit-scrollbar-thumb{
    background:var(--terracotta)!important;
}

.menu-title{
    color:#6b7280!important;
}

.menu a{
    color:#9ca3af!important;
}

.menu a i{
    background:#1f2937!important;
    color:#d1d5db!important;
}

.menu a:hover,
.menu a.active{
    background:rgba(201,107,88,.15)!important;
    border-color:rgba(201,107,88,.18)!important;
    color:#e5a498!important;
}

.menu a:hover i,
.menu a.active i{
    color:#fff!important;
    background:linear-gradient(135deg,#c96b58,#9f4f42)!important;
}

/* Boutons de pointage */
.btn-arrivee{
    background:#2563eb!important;
    border-color:#2563eb!important;
    color:#fff!important;
}

.btn-arrivee:hover:not(:disabled){
    background:#1d4ed8!important;
    border-color:#1d4ed8!important;
}

.btn-depart{
    background:#dc2626!important;
    border-color:#dc2626!important;
    color:#fff!important;
}

.btn-depart:hover:not(:disabled){
    background:#b91c1c!important;
    border-color:#b91c1c!important;
}

/* Statut présent en vert */
.presence-present{
    background:#dcfce7!important;
    border-color:#bbf7d0!important;
    color:#166534!important;
}

/* ALIGNEMENT DEFINITIF : aucune partie ne passe sous la barre de menu */
html,
body{
    width:100%;
    max-width:100%;
    overflow-x:hidden!important;
}

.main{
    margin-left:310px!important;
    width:calc(100vw - 310px)!important;
    max-width:calc(100vw - 310px)!important;
    min-width:0!important;
    padding:32px 40px!important;
    overflow-x:hidden!important;
}

.main > *{
    min-width:0!important;
    max-width:100%!important;
}

.card-box,
.history-card,
.topbar,
.dashboard-footer{
    display:block!important;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    margin-left:0!important;
    margin-right:0!important;
    clear:both!important;
}

.row{
    margin-left:-12px!important;
    margin-right:-12px!important;
}

.history-card{
    position:relative!important;
    margin-top:0!important;
    overflow:hidden!important;
}

.history-card .table-responsive{
    display:block!important;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    overflow-x:auto!important;
    overflow-y:hidden!important;
    -webkit-overflow-scrolling:touch;
    border-radius:16px;
}

.history-card table{
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    table-layout:fixed!important;
    margin-bottom:0!important;
}

.history-card th,
.history-card td{
    white-space:normal!important;
    overflow-wrap:anywhere!important;
    word-break:normal!important;
    text-align:center!important;
    padding:11px 7px!important;
}

.history-card th{
    font-size:10px!important;
    line-height:1.25!important;
}

.history-card td{
    font-size:12px!important;
}

.history-card th:first-child,
.history-card td:first-child{
    width:110px!important;
}

.history-card th:nth-last-child(2),
.history-card td:nth-last-child(2){
    width:95px!important;
}

.history-card th:last-child,
.history-card td:last-child{
    width:90px!important;
}

body.sidebar-collapsed .main{
    margin-left:114px!important;
    width:calc(100vw - 114px)!important;
    max-width:calc(100vw - 114px)!important;
}

@media(max-width:900px){
    .main{
        margin-left:285px!important;
        width:calc(100vw - 285px)!important;
        max-width:calc(100vw - 285px)!important;
        min-width:0!important;
        padding:18px 12px!important;
    }

    body.sidebar-collapsed .main{
        margin-left:96px!important;
        width:calc(100vw - 96px)!important;
        max-width:calc(100vw - 96px)!important;
    }

    .history-card{
        padding:16px!important;
    }

    .history-card table{
        min-width:720px!important;
        table-layout:auto!important;
    }

    .history-card th,
    .history-card td{
        white-space:nowrap!important;
    }
}


/* FILTRE HISTORIQUE */
.filter-card{
    display:grid;
    grid-template-columns:minmax(190px,1fr) minmax(190px,1fr) auto auto;
    align-items:end;
    gap:12px;
    margin-bottom:20px;
    padding:16px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:18px;
}
.filter-group{min-width:0}
.filter-label{
    display:block;
    margin-bottom:6px;
    font-size:12px;
    font-weight:800;
    color:var(--text);
}
.filter-control{
    width:100%;
    min-height:42px;
    padding:8px 12px;
    border:1px solid var(--line);
    border-radius:12px;
    background:#fff;
    color:var(--text);
}
.btn-filter,.btn-reset{
    min-height:42px;
    padding:9px 16px;
    border-radius:12px;
    font-size:13px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    white-space:nowrap;
}
.btn-filter{
    border:none;
    background:var(--terracotta);
    color:#fff;
}
.btn-filter:hover{background:#9f4f42;color:#fff}
.btn-reset{
    border:1px solid var(--line);
    background:#111827;
    color:#fff;
    text-decoration:none;
}
.btn-reset:hover{background:#000;color:#fff}
.history-card{
    width:100%;
    max-width:100%;
    min-width:0;
    overflow:hidden;
}
.history-card .table-responsive{
    width:100%;
    max-width:100%;
    min-width:0;
    overflow-x:auto;
    overflow-y:hidden;
    border-radius:16px;
}
.history-card table{
    width:100%;
    min-width:900px;
    margin-bottom:0;
}
body.dark-mode .filter-card,
body.dark-mode .filter-control{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}
@media(max-width:1100px){
    .filter-card{grid-template-columns:1fr 1fr}
}
@media(max-width:700px){
    .filter-card{grid-template-columns:1fr}
    .btn-filter,.btn-reset{width:100%}
}


/* EN-TETE IDENTIQUE AUX AUTRES PAGES PERSONNEL */
.badge-role{
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    color:var(--terracotta);
    border-radius:40px;
    padding:8px 16px;
    font-size:13px;
    font-weight:800;
}

.topbar{
    width:100%;
    max-width:100%;
}

.top-left{
    min-width:0;
}

.top-left > div{
    min-width:0;
}

.header-actions{
    margin-left:auto;
}

.filter-control{
    appearance:auto;
}


/* ALIGNEMENT PROPRE ENTETE + FOOTER */

.topbar{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) auto!important;
    align-items:center!important;
    gap:18px!important;
}

.top-left{
    display:flex!important;
    align-items:center!important;
    gap:13px!important;
    min-width:0!important;
}

.top-left > div{
    min-width:0!important;
}

.page-title,
.page-subtitle{
    white-space:nowrap!important;
    overflow:hidden!important;
    text-overflow:ellipsis!important;
}

.header-actions{
    display:flex!important;
    align-items:center!important;
    justify-content:flex-end!important;
    gap:10px!important;
    flex-wrap:nowrap!important;
    white-space:nowrap!important;
}

.dashboard-footer{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) auto!important;
    align-items:center!important;
    gap:16px!important;
}

.footer-left{
    min-width:0!important;
}

.footer-left span{
    white-space:nowrap!important;
    overflow:hidden!important;
    text-overflow:ellipsis!important;
}

.footer-secure{
    white-space:nowrap!important;
}

.filter-card{
    grid-template-columns:minmax(190px,1fr) minmax(190px,1fr) auto!important;
}


@media(max-width:900px){
    .topbar,
    .dashboard-footer{
        grid-template-columns:1fr!important;
        align-items:flex-start!important;
    }

    .header-actions{
        justify-content:flex-start!important;
        flex-wrap:wrap!important;
    }

    .page-title,
    .page-subtitle,
    .footer-left span{
        white-space:normal!important;
        overflow:visible!important;
        text-overflow:clip!important;
    }

    .filter-card{
        grid-template-columns:1fr!important;
    }
}

</style>
</head>

<body>

<div class="sidebar" id="sidebar">

<div class="logo">
    <img src="uploads/logo/logo.jpeg" alt="Logo">
    <h4>ESPACE PERSONNEL</h4>
</div>

<div class="menu">

<div class="menu-section">
    <div class="menu-title">Accueil</div>

    <a href="espace_personnel.php">
        <i class="bi bi-speedometer2"></i>
        <span>Tableau de bord</span>
    </a>
</div>

<div class="menu-section">
    <div class="menu-title">Mon espace</div>

    <a href="documents_personnel_user.php">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Mes documents</span>
    </a>

    <a href="paiements_personnel.php">
        <i class="bi bi-cash-stack"></i>
        <span>Mes paiements</span>
    </a>

    <a href="presences_personnel.php" class="active">
        <i class="bi bi-calendar-check"></i>
        <span>Mes présences</span>
    </a>

    <?php if(($personnel['fonction'] ?? '') === 'PROFESSEUR'): ?>
    <a href="emploi_personnel.php">
        <i class="bi bi-calendar3"></i>
        <span>Mon emploi du temps</span>
    </a>
    <?php endif; ?>

    <a href="demandes_personnel.php">
        <i class="bi bi-send"></i>
        <span>Mes demandes</span>
    </a>
</div>

<div class="menu-section">
    <div class="menu-title">Compte</div>
    
    <a href="profil_personnel.php">
        <i class="bi bi-person-circle"></i>
        <span>Mon profil</span>
    </a>
    <a href="logout_personnel.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Déconnexion</span>
    </a>
</div>

</div>

</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<button type="button" class="sidebar-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
    <i class="bi bi-list"></i>
</button>

<a href="espace_personnel.php" class="back-btn" title="Retour au tableau de bord">
    <i class="bi bi-arrow-left"></i>
</a>

<div>
    <h1 class="page-title">Mes présences</h1>
    <div class="page-subtitle">
        Aujourd'hui : <?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8') ?>
        · Heure actuelle : <?= htmlspecialchars(toShortTime($now), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

</div>

<div class="header-actions">

<div class="year-badge">
    <i class="bi bi-calendar-check"></i>
    <?= htmlspecialchars($annee['libelle'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?>
</div>

<button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre">
    <i class="bi bi-moon-fill"></i>
</button>

<span class="badge-role">
    <?= htmlspecialchars($personnel['fonction'] ?? 'Personnel', ENT_QUOTES, 'UTF-8') ?>
</span>

</div>

</div>

<?php if(!empty($message)): ?>
<div class="notice notice-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
<div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if($isAdminNonPointe): ?>

<div class="card-box">
    <div class="notice notice-info mb-0">
        <i class="bi bi-info-circle"></i>
        Votre fonction ne nécessite pas de pointage.
    </div>
</div>

<?php elseif($isProfesseur): ?>

<div class="card-box">
<h5 class="card-title-custom"><i class="bi bi-calendar3"></i> Cours du jour à pointer selon votre emploi du temps</h5>

<?php if(count($coursJour) > 0): ?>
<?php foreach($coursJour as $c): ?>
<?php
$heureDebut = $c['heure_debut'] ?? '00:00:00';
$heureFin = $c['heure_fin'] ?? '00:00:00';
$arriveeDebut = $heureDebut;
$arriveeFin = addMinutesToTime($heureDebut, 20);
$departDebut = $heureFin;
$departFin = addMinutesToTime($heureFin, 30);
$peutArriver = inTimeRange($now, $arriveeDebut, $arriveeFin);
$peutPartir = inTimeRange($now, $departDebut, $departFin);
$arriveeFaite = !empty($c['heure_arrivee']);
$departFait = !empty($c['heure_depart']);
?>
<div class="course-card <?= ($peutArriver || $peutPartir) ? 'active-window' : 'out-window' ?>">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <strong><?= htmlspecialchars($c['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
            <?= htmlspecialchars($c['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?> — Classe <?= htmlspecialchars($c['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <div>
                <span class="time-badge"><i class="bi bi-box-arrow-in-right"></i> Arrivée : <?= htmlspecialchars(messageHoraire($arriveeDebut, $arriveeFin), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="time-badge"><i class="bi bi-box-arrow-right"></i> Départ : <?= htmlspecialchars(messageHoraire($departDebut, $departFin), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if(!$peutArriver && !$peutPartir && !$arriveeFaite && !$departFait): ?>
                <small class="text-danger d-block mt-2">Action impossible actuellement. Voir le Directeur si besoin.</small>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <div class="mb-2"><strong>Arrivée :</strong> <?= htmlspecialchars(toShortTime($c['heure_arrivee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="mb-2"><strong>Départ :</strong> <?= htmlspecialchars(toShortTime($c['heure_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="mb-2"><?= badgePresence($c['statut_presence'] ?? 'absent') ?></div>

            <form method="POST" class="d-inline">
                <input type="hidden" name="plage_horaire" value="<?= htmlspecialchars($c['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="pointer_arrivee_cours" class="btn btn-pointage btn-arrivee" <?= boutonDisabled(!$peutArriver || $arriveeFaite) ?>>
                    <i class="bi bi-check2-circle"></i> Arrivée
                </button>
            </form>

            <form method="POST" class="d-inline">
                <input type="hidden" name="plage_horaire" value="<?= htmlspecialchars($c['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="pointer_depart_cours" class="btn btn-pointage btn-depart" <?= boutonDisabled(!$peutPartir || !$arriveeFaite || $departFait) ?>>
                    <i class="bi bi-box-arrow-right"></i> Départ
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<p class="text-muted mb-0">Aucun cours prévu aujourd'hui.</p>
<?php endif; ?>
</div>

<?php else: ?>

<div class="card-box pointage-card">
<h5 class="card-title-custom"><i class="bi bi-clock"></i> Pointage du jour</h5>

<?php if($isWeekend && !$weekendAutorise): ?>
<div class="notice notice-warning mb-0">
    <i class="bi bi-exclamation-triangle"></i>
    Action impossible. Le pointage du samedi et du dimanche est réservé aux gardiens et techniciens de surface.
</div>
<?php else: ?>

<div class="row">
    <div class="col-md-6">
        <div class="status-box">
            <h6 class="fw-bold mb-2">Matin</h6>
            <p class="mb-1"><strong>Arrivée :</strong> <?= htmlspecialchars(toShortTime($presenceJour['heure_arrivee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-1"><strong>Départ :</strong> <?= htmlspecialchars(toShortTime($presenceJour['heure_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <small class="text-muted">Arrivée autorisée : 07:45 - 08:25 · Départ autorisé : 12:30 - 13:00</small>
        </div>
    </div>

    <div class="col-md-6">
        <div class="status-box">
            <h6 class="fw-bold mb-2">Après-midi</h6>
            <p class="mb-1"><strong>Arrivée :</strong> <?= htmlspecialchars(toShortTime($presenceJour['heure_arrivee_aprem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-1"><strong>Départ :</strong> <?= htmlspecialchars(toShortTime($presenceJour['heure_depart_aprem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <small class="text-muted">Arrivée autorisée : 13:30 - 14:10 · Départ autorisé : 18:00 - 18:30</small>
        </div>
    </div>
</div>

<div class="pointage-actions">

    <div class="pointage-session">
        <i class="bi bi-clock"></i>
        <?= htmlspecialchars($libellePeriode, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="d-flex flex-wrap gap-3">

        <form method="POST">
            <?php if($actionArriveeName !== ''): ?>
                <button
                    type="submit"
                    name="<?= htmlspecialchars($actionArriveeName, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-pointage btn-arrivee"
                    <?= boutonDisabled($arriveeDisabled) ?>
                >
                    <i class="bi bi-box-arrow-in-right"></i>
                    Pointer l'arrivée
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-pointage btn-arrivee" disabled>
                    <i class="bi bi-box-arrow-in-right"></i>
                    Pointer l'arrivée
                </button>
            <?php endif; ?>
        </form>

        <form method="POST">
            <?php if($actionDepartName !== ''): ?>
                <button
                    type="submit"
                    name="<?= htmlspecialchars($actionDepartName, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-pointage btn-depart"
                    <?= boutonDisabled($departDisabled) ?>
                >
                    <i class="bi bi-box-arrow-right"></i>
                    Pointer le départ
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-pointage btn-depart" disabled>
                    <i class="bi bi-box-arrow-right"></i>
                    Pointer le départ
                </button>
            <?php endif; ?>
        </form>

    </div>

</div>
</div>

<?php if(!inTimeRange($now, $horaireSimple['arrivee_matin']['debut'], $horaireSimple['arrivee_matin']['fin']) && !inTimeRange($now, $horaireSimple['depart_matin']['debut'], $horaireSimple['depart_matin']['fin']) && !inTimeRange($now, $horaireSimple['arrivee_aprem']['debut'], $horaireSimple['arrivee_aprem']['fin']) && !inTimeRange($now, $horaireSimple['depart_aprem']['debut'], $horaireSimple['depart_aprem']['fin'])): ?>
<div class="notice notice-error mt-3 mb-0"><i class="bi bi-x-circle"></i> Action impossible actuellement. Voir le Directeur si besoin.</div>
<?php endif; ?>

<?php endif; ?>

<?php endif; ?>

<div class="card-box history-card">
<h5 class="card-title-custom"><i class="bi bi-clock-history"></i> Historique récent</h5>

<div class="filter-card" id="historiqueFilterForm">
    <div class="filter-group">
        <label for="filtreJour" class="filter-label">Voir un jour</label>

        <select id="filtreJour" name="jour" class="filter-control">
            <option value="">Tous les jours</option>

            <?php foreach($joursFiltres as $numeroJour => $nomJour): ?>
                <option
                    value="<?= htmlspecialchars($numeroJour, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $filtreJour === (string)$numeroJour ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($nomJour, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label for="filtreMois" class="filter-label">Voir un mois</label>
        <input type="month" id="filtreMois" name="mois" class="filter-control"
               value="<?= htmlspecialchars($filtreMois, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <button type="button" class="btn-reset" id="resetHistoriqueFilter">
        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
    </button>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>Date</th>
<?php if($isProfesseur): ?>
<th>Plage horaire</th><th>Arrivée cours</th><th>Départ cours</th>
<?php else: ?>
<th>Arrivée matin</th><th>Départ matin</th><th>Arrivée après-midi</th><th>Départ après-midi</th>
<?php endif; ?>
<th>Statut</th><th>Motif</th>
</tr>
</thead>
<tbody>
<?php if(count($historique) > 0): ?>
<?php foreach($historique as $h): ?>
<?php
$dateHistorique = $h['date_presence'] ?? '';
$jourHistorique = $dateHistorique !== '' ? date('N', strtotime($dateHistorique)) : '';
$moisHistorique = $dateHistorique !== '' ? date('Y-m', strtotime($dateHistorique)) : '';
?>
<tr
    class="history-row"
    data-jour="<?= htmlspecialchars($jourHistorique, ENT_QUOTES, 'UTF-8') ?>"
    data-mois="<?= htmlspecialchars($moisHistorique, ENT_QUOTES, 'UTF-8') ?>"
>
<td><?= htmlspecialchars($dateHistorique !== '' ? $dateHistorique : '-', ENT_QUOTES, 'UTF-8') ?></td>
<?php if($isProfesseur): ?>
<td><?= htmlspecialchars($h['plage_horaire'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(toShortTime($h['heure_arrivee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(toShortTime($h['heure_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<?php else: ?>
<td><?= htmlspecialchars(toShortTime($h['heure_arrivee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(toShortTime($h['heure_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(toShortTime($h['heure_arrivee_aprem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(toShortTime($h['heure_depart_aprem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<?php endif; ?>
<td><?= badgePresence($h['statut'] ?? '') ?></td>
<td><?= htmlspecialchars($h['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="<?= $isProfesseur ? 6 : 7 ?>" class="text-center text-muted py-4">Aucune présence enregistrée.</td></tr>
<?php endif; ?>
<tr id="noFilterResult" style="display:none;">
<td colspan="<?= $isProfesseur ? 6 : 7 ?>" class="text-center text-muted py-4">
    Aucun résultat pour ce filtre.
</td>
</tr>
</tbody>
</table>
</div>
</div>


<footer class="dashboard-footer">

<div class="footer-left">

<img
src="uploads/logo/logo.jpeg"
alt="Logo CMAK"
>

<span>
© <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Espace personnel. Tous droits réservés.
</span>

</div>

<div class="footer-secure">
<i class="bi bi-shield-check"></i>
Données sécurisées
</div>

</footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const sidebarToggle = document.getElementById('sidebarToggle');
const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');

function setSidebarCollapsed(enabled){
    document.body.classList.toggle('sidebar-collapsed', enabled);
    localStorage.setItem('sidebar-personnel-menu', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('sidebar-personnel-menu') === 'enabled'){
    setSidebarCollapsed(true);
}

sidebarToggle?.addEventListener('click', function(){
    setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
});

function setDarkMode(enabled){
    document.body.classList.toggle('dark-mode', enabled);

    if(darkIcon){
        darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    localStorage.setItem('dark-mode-personnel-menu', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('dark-mode-personnel-menu') === 'enabled'){
    setDarkMode(true);
}

darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});

const filtreJour = document.getElementById('filtreJour');
const filtreMois = document.getElementById('filtreMois');
const resetHistoriqueFilter = document.getElementById('resetHistoriqueFilter');
const historyRows = Array.from(document.querySelectorAll('.history-row'));
const noFilterResult = document.getElementById('noFilterResult');

function filtrerHistoriqueSansRechargement(){
    const jourChoisi = filtreJour?.value || '';
    const moisChoisi = filtreMois?.value || '';
    let visibles = 0;

    historyRows.forEach((row) => {
        const correspondJour =
            jourChoisi === '' || row.dataset.jour === jourChoisi;

        const correspondMois =
            moisChoisi === '' || row.dataset.mois === moisChoisi;

        const afficher = correspondJour && correspondMois;

        row.style.display = afficher ? '' : 'none';

        if(afficher){
            visibles++;
        }
    });

    if(noFilterResult){
        noFilterResult.style.display = visibles === 0 ? '' : 'none';
    }
}

filtreJour?.addEventListener('change', filtrerHistoriqueSansRechargement);
filtreMois?.addEventListener('change', filtrerHistoriqueSansRechargement);

resetHistoriqueFilter?.addEventListener('click', function(){
    if(filtreJour){
        filtreJour.value = '';
    }

    if(filtreMois){
        filtreMois.value = '';
    }

    filtrerHistoriqueSansRechargement();
});

</script>

</body>
</html>