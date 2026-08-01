<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';
require 'securite_daf.php';

date_default_timezone_set('Africa/Abidjan');

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

$message = "";
$error = "";

$moisActuel  = (int)date('m');
$anneeActuel = (int)date('Y');

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$moisNoms = [
    1 => 'Janvier',
    2 => 'Février',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Août',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre'
];

$personnels = $pdo->query("
    SELECT id, nom, prenom, fonction, matricule, salaire_base
    FROM personnels
    WHERE statut = 'actif'
    ORDER BY nom ASC, prenom ASC
")->fetchAll();

if(isset($_POST['enregistrer'])){

    $personnel_id = intval($_POST['personnel_id'] ?? 0);
    $mois         = intval($_POST['mois'] ?? 0);
    $annee        = intval($_POST['annee'] ?? 0);

    $salaire_base = floatval($_POST['salaire_base'] ?? 0);
    $avance       = floatval($_POST['avance'] ?? 0);
    $retenue      = floatval($_POST['retenue'] ?? 0);

    $salaire_net = $salaire_base - $avance - $retenue;

    if($personnel_id <= 0){
        $error = "Veuillez sélectionner un personnel.";
    }elseif($mois < 1 || $mois > 12){
        $error = "Mois invalide.";
    }elseif($annee < 2000){
        $error = "Année invalide.";
    }elseif($salaire_base <= 0){
        $error = "Le salaire doit être supérieur à 0.";
    }elseif($salaire_net < 0){
        $error = "Le salaire net ne peut pas être négatif.";
    }else{

        $check = $pdo->prepare("
            SELECT id
            FROM salaires
            WHERE personnel_id = ?
            AND mois = ?
            AND annee = ?
            LIMIT 1
        ");

        $check->execute([
            $personnel_id,
            $mois,
            $annee
        ]);

        $existant = $check->fetch();

        if($existant){

            $update = $pdo->prepare("
                UPDATE salaires SET
                salaire_base = ?,
                avance = ?,
                retenue = ?,
                salaire_net = ?
                WHERE id = ?
            ");

            $update->execute([
                $salaire_base,
                $avance,
                $retenue,
                $salaire_net,
                $existant['id']
            ]);

            $message = "Salaire mis à jour avec succès.";

        }else{

            $insert = $pdo->prepare("
                INSERT INTO salaires(
                    personnel_id,
                    mois,
                    annee,
                    salaire_base,
                    prime,
                    avance,
                    retenue,
                    salaire_net,
                    statut
                )
                VALUES(?,?,?,?,?,?,?,?,?)
            ");

            $insert->execute([
                $personnel_id,
                $mois,
                $annee,
                $salaire_base,
                0,
                $avance,
                $retenue,
                $salaire_net,
                'non_paye'
            ]);

            $message = "Salaire enregistré avec succès.";

        }

    }

}

$salaires = $pdo->query("
    SELECT
        s.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    ORDER BY s.annee DESC, s.mois DESC, s.id DESC
    LIMIT 100
")->fetchAll();

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Gestion des salaires | Espace DAF</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
rel="stylesheet"
>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

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
    --primary-soft:#dbeafe;
    --daf:#c96b58;
    --daf-dark:#9f4f42;
    --daf-soft:#fdf4f2;
    --daf-border:#f0c9c1;
    --success:#16a34a;
    --warning:#d97706;
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

h1,h2,h3,.page-title,.card-title-custom,.net-box h3{
    font-family:'Sora',sans-serif;
}

/* SIDEBAR DAF */

.sidebar{
    position:fixed;
    top:10px;
    left:10px;
    bottom:10px;
    width:280px;
    height:calc(100vh - 20px);
    padding:16px 16px 18px;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border:2.5px solid rgba(15,23,42,.92);
    border-radius:34px;
    box-shadow:var(--shadow);
    overflow:hidden;
    z-index:999;
}

.logo{
    color:var(--text);
    text-align:center;
    margin-bottom:12px;
    padding:6px 8px 12px;
    border-bottom:1px solid rgba(226,232,240,.9);
}

.logo img{
    width:58px;
    height:58px;
    border-radius:19px;
    object-fit:cover;
    margin-bottom:8px;
    border:3px solid #fff;
    box-shadow:0 12px 26px rgba(15,23,42,.10);
}

.logo h4{
    color:var(--text);
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
    scrollbar-color:#cbd5e1 transparent;
}

.menu > *{
    direction:ltr;
}

.menu::-webkit-scrollbar{
    width:5px;
}

.menu::-webkit-scrollbar-track{
    background:transparent;
}

.menu::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
}

.menu::-webkit-scrollbar-thumb:hover{
    background:var(--daf);
}

.menu-section{
    margin-bottom:10px;
}

.menu-title{
    color:var(--muted);
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
    color:#475569;
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
    background:#f1f5f9;
    color:var(--muted);
    font-size:15px;
}

.menu a:hover,
.menu a.active{
    background:rgba(255,255,255,.9);
    border-color:rgba(226,232,240,.95);
    color:#c96b58;
    box-shadow:0 10px 24px rgba(15,23,42,.06);
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
    overflow:visible;
    position:relative;
    z-index:20;
}

.top-left{
    display:flex;
    align-items:center;
    gap:13px;
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
    background:var(--daf-soft);
    border:1px solid var(--daf-border);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:#c96b58;
}

.dashboard-menu-toggle,
.dark-toggle,
.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:#c96b58;
    display:grid;
    place-items:center;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    transition:all .2s ease;
    flex:0 0 auto;
    text-decoration:none;
}

.dashboard-menu-toggle:hover,
.dark-toggle:hover,
.back-btn:hover{
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
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
    color:#c96b58;
}

.form-label{
    font-size:13px;
    font-weight:800;
    color:var(--text);
    margin-bottom:7px;
}

.form-control,
.form-select{
    border-radius:14px;
    border:1px solid var(--line);
    min-height:48px;
    font-size:14px;
    box-shadow:none!important;
}

.form-control:focus,
.form-select:focus{
    border-color:#c96b58;
}

.btn-main{
    background:var(--daf);
    color:white;
    border:none;
    border-radius:14px;
    padding:12px 22px;
    font-weight:800;
    box-shadow:0 12px 22px rgba(201,107,88,.22);
}

.btn-main:hover{
    background:#111827;
    color:white;
    transform:translateY(-1px);
}

.alert{
    border-radius:16px;
    border:none;
    box-shadow:var(--shadow-sm);
}

.table{
    margin:0;
}

.table thead th{
    background:#f8fafc;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:var(--muted);
    padding:14px;
    border-bottom:1px solid var(--line);
}

.table tbody td{
    padding:14px;
    font-size:13px;
    vertical-align:middle;
    color:#374151;
}

.table tbody tr:hover{
    background:#fafbfc;
}

.badge-status{
    border-radius:20px;
    padding:6px 10px;
    font-size:11px;
    font-weight:800;
}

.badge-non{
    background:#fee2e2;
    color:#b91c1c;
}

.badge-partiel{
    background:#fef3c7;
    color:#92400e;
}

.badge-paye{
    background:#dcfce7;
    color:#166534;
}

.net-box{
    background:var(--daf-soft);
    border:1px solid var(--daf-border);
    border-radius:18px;
    padding:16px;
    height:100%;
}

.net-box small{
    color:var(--muted);
    font-weight:800;
}

.net-box h3{
    color:#c96b58;
    font-size:24px;
    font-weight:800;
    margin:6px 0 0;
}


.filter-zone{
    flex:1;
    max-width:820px;
}

.filter-zone .form-control,
.filter-zone .form-select{
    min-height:42px;
    font-size:13px;
}

.no-result-row{
    display:none;
}

.no-result-row td{
    text-align:center;
    color:var(--muted)!important;
    padding:28px!important;
}

@media(max-width:900px){
    .filter-zone{
        max-width:100%;
        width:100%;
    }
}

/* FOOTER */

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

/* DARK MODE */

body.dark-mode{
    --bg:#0b1120;
    --card:#111827;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#2d3340;
    background:#0b1120;
}

body.dark-mode .sidebar,
body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .dashboard-footer,
body.dark-mode .dashboard-menu-toggle,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .sidebar{
    border-color:rgba(255,255,255,.78)!important;
}

body.dark-mode .logo{
    border-bottom-color:#2d3340;
}

body.dark-mode .logo h4,
body.dark-mode .page-title,
body.dark-mode .card-title-custom{
    color:#f0f2f5;
}

body.dark-mode .menu a{
    color:#cbd5e1;
}

body.dark-mode .menu a i{
    background:#111827;
    color:#94a3b8;
}

body.dark-mode .menu a:hover,
body.dark-mode .menu a.active{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}

body.dark-mode .form-control,
body.dark-mode .form-select,
body.dark-mode .net-box{
    background:#111827;
    border-color:#2d3340;
    color:#e5e7eb;
}

body.dark-mode .table thead th{
    background:#111827;
    border-color:#2d3340;
}

body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}

body.dark-mode .table tbody tr:hover{
    background:#111827;
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
        transform:none!important;
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

    .dashboard-footer{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

</head>

<body>

<div class="sidebar" id="sidebar">

<div class="logo">

<img
src="uploads/logo/logo.jpeg"
alt="Logo"
>

<h4>ESPACE DAF</h4>

</div>

<div class="menu">

<div class="menu-section">
<div class="menu-title">Principal</div>

<a href="dashboard_daf.php">
<i class="bi bi-speedometer2"></i>
<span>Dashboard</span>
</a>
</div>

<div class="menu-section">
<div class="menu-title">Gestion financière</div>

<a href="personnel.php">
<i class="bi bi-people"></i>
<span>Personnel</span>
</a>

<a href="salaires.php" class="active">
<i class="bi bi-cash-stack"></i>
<span>Salaires</span>
</a>

<a href="paiements.php">
<i class="bi bi-credit-card"></i>
<span>Paiements</span>
</a>

<a href="avances.php">
<i class="bi bi-wallet2"></i>
<span>Avances</span>
</a>

<a href="retenues.php">
<i class="bi bi-dash-circle"></i>
<span>Retenues</span>
</a>

<a href="impayes.php">
<i class="bi bi-exclamation-triangle"></i>
<span>Impayés</span>
</a>

<a href="fiches_paie.php">
<i class="bi bi-file-earmark-pdf"></i>
<span>Fiches de paie</span>
</a>

<a href="statistiques_daf.php">
<i class="bi bi-bar-chart"></i>
<span>Statistiques</span>
</a>

<a href="notifications_daf.php">
<i class="bi bi-bell"></i>
<span>Notifications</span>
</a>
</div>

<div class="menu-section">
<div class="menu-title">Compte</div>

<a href="profil_daf.php">
<i class="bi bi-person-circle"></i>
<span>Profil</span>
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
<span>Déconnexion</span>
</a>
</div>

</div></div>

</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
<i class="bi bi-list"></i>
</button>

<a
href="dashboard_daf.php"
class="back-btn"
title="Retour au dashboard DAF"
>
<i class="bi bi-arrow-left"></i>
</a>

<div>

<h1 class="page-title">
Gestion des salaires
</h1>

<p class="page-subtitle">
Enregistrement mensuel des salaires du personnel
</p>

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

</div>

</div>

<?php if(!empty($message)): ?>

<div class="alert alert-success">
<i class="bi bi-check-circle-fill"></i>
<?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<?php if(!empty($error)): ?>

<div class="alert alert-danger">
<i class="bi bi-exclamation-triangle-fill"></i>
<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-plus-circle-fill"></i>
Enregistrer un salaire
</div>

<form method="POST">

<div class="row g-3">

<div class="col-md-4">

<label class="form-label">
Personnel
</label>

<select
name="personnel_id"
id="personnel_id"
class="form-select"
required
>

<option value="">
Sélectionner un personnel
</option>

<?php foreach($personnels as $p): ?>

<option
value="<?= (int)$p['id'] ?>"
data-salaire="<?= htmlspecialchars($p['salaire_base'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"
>
<?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
-
<?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-2">

<label class="form-label">
Mois
</label>

<select
name="mois"
class="form-select"
required
>

<?php foreach($moisNoms as $num => $nom): ?>

<option
value="<?= $num ?>"
<?= $num === $moisActuel ? 'selected' : '' ?>
>
<?= $nom ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-2">

<label class="form-label">
Année
</label>

<input
type="number"
name="annee"
class="form-control"
value="<?= $anneeActuel ?>"
required
>

</div>

<div class="col-md-4">

<div class="net-box">

<small>Salaire net calculé</small>

<h3 id="netPreview">
0 FCFA
</h3>

</div>

</div>

<div class="col-md-4">

<label class="form-label">
Salaire
</label>

<input
type="number"
step="0.01"
name="salaire_base"
id="salaire_base"
class="form-control"
required
>

</div>

<div class="col-md-4">

<label class="form-label">
Avance
</label>

<input
type="number"
step="0.01"
name="avance"
id="avance"
class="form-control"
value="0"
>

</div>

<div class="col-md-4">

<label class="form-label">
Retenue
</label>

<input
type="number"
step="0.01"
name="retenue"
id="retenue"
class="form-control"
value="0"
>

</div>

<div class="col-12">

<button
type="submit"
name="enregistrer"
class="btn btn-main"
>

<i class="bi bi-check2-circle"></i>
Enregistrer le salaire

</button>

</div>

</div>

</form>

</div>

<div class="card-box">

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">

<div class="card-title-custom mb-0">
<i class="bi bi-list-check"></i>
Derniers salaires enregistrés
</div>

<div class="row g-2 filter-zone">

<div class="col-md-5">
<input
type="text"
id="searchSalaire"
class="form-control"
placeholder="Rechercher nom, matricule, fonction..."
autocomplete="off"
>
</div>

<div class="col-md-3">
<select id="filterMois" class="form-select">
<option value="">Tous les mois</option>
<?php foreach($moisNoms as $num => $nom): ?>
<option value="<?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>">
<?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<input
type="number"
id="filterAnnee"
class="form-control"
placeholder="Année"
>
</div>

<div class="col-md-2">
<select id="filterStatut" class="form-select">
<option value="">Tous statuts</option>
<option value="Payé">Payé</option>
<option value="Partiel">Partiel</option>
<option value="Non payé">Non payé</option>
</select>
</div>

</div>

</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Mois</th>
<th>Salaire</th>
<th>Avance</th>
<th>Retenue</th>
<th>Net</th>
<th>Statut</th>
</tr>

</thead>

<tbody id="salairesTableBody">

<?php if(count($salaires) > 0): ?>

<?php foreach($salaires as $s): ?>

<tr
data-mois="<?= htmlspecialchars($moisNoms[(int)$s['mois']] ?? $s['mois'], ENT_QUOTES, 'UTF-8') ?>"
data-annee="<?= htmlspecialchars($s['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
data-statut="<?= ($s['statut'] ?? '') === 'paye' ? 'Payé' : (($s['statut'] ?? '') === 'partiellement_paye' ? 'Partiel' : 'Non payé') ?>"
>

<td>
<strong>
<?= htmlspecialchars(($s['nom'] ?? '').' '.($s['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($s['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($s['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($moisNoms[(int)$s['mois']] ?? $s['mois'], ENT_QUOTES, 'UTF-8') ?>
<?= htmlspecialchars($s['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= argent($s['salaire_base'] ?? 0) ?>
</td>

<td>
<?= argent($s['avance'] ?? 0) ?>
</td>

<td>
<?= argent($s['retenue'] ?? 0) ?>
</td>

<td>
<strong>
<?= argent($s['salaire_net'] ?? 0) ?>
</strong>
</td>

<td>

<?php if(($s['statut'] ?? '') === 'paye'): ?>

<span class="badge-status badge-paye">
Payé
</span>

<?php elseif(($s['statut'] ?? '') === 'partiellement_paye'): ?>

<span class="badge-status badge-partiel">
Partiel
</span>

<?php else: ?>

<span class="badge-status badge-non">
Non payé
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

<tr class="no-result-row" id="noResultRow">
<td colspan="8">
<i class="bi bi-search"></i>
Aucun salaire ne correspond au filtre.
</td>
</tr>

<?php else: ?>

<tr>
<td
colspan="8"
class="text-center text-muted py-4"
>
Aucun salaire enregistré pour le moment.
</td>
</tr>

<?php endif; ?>

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
© <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion financière du personnel scolaire. Tous droits réservés.
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

function setSidebarCollapsed(enabled){
    document.body.classList.toggle('sidebar-collapsed', enabled);
    localStorage.setItem('sidebar-collapsed-daf', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('sidebar-collapsed-daf') === 'enabled'){
    setSidebarCollapsed(true);
}

sidebarToggle?.addEventListener('click', function(){
    setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
});

const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');

function setDarkMode(enabled){
    document.body.classList.toggle('dark-mode', enabled);

    if(darkIcon){
        darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    localStorage.setItem('dark-mode-daf', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('dark-mode-daf') === 'enabled'){
    setDarkMode(true);
}

darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});


const personnelSelect = document.getElementById('personnel_id');
const salaireInput = document.getElementById('salaire_base');
const avanceInput = document.getElementById('avance');
const retenueInput = document.getElementById('retenue');
const netPreview = document.getElementById('netPreview');

function formatMoney(value){
    return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
}

function calculerNet(){
    const salaire = parseFloat(salaireInput.value) || 0;
    const avance = parseFloat(avanceInput.value) || 0;
    const retenue = parseFloat(retenueInput.value) || 0;

    let net = salaire - avance - retenue;

    if(net < 0){
        net = 0;
    }

    netPreview.textContent = formatMoney(net);
}

personnelSelect.addEventListener('change', function(){

    const selected = this.options[this.selectedIndex];
    const salaire = selected.getAttribute('data-salaire');

    if(salaire !== null && salaire !== ''){
        salaireInput.value = salaire;
    }

    calculerNet();


const searchSalaire = document.getElementById('searchSalaire');
const filterMois = document.getElementById('filterMois');
const filterAnnee = document.getElementById('filterAnnee');
const filterStatut = document.getElementById('filterStatut');
const salairesTableBody = document.getElementById('salairesTableBody');
const noResultRow = document.getElementById('noResultRow');

function filtrerSalaires(){
    if(!salairesTableBody) return;

    const q = (searchSalaire?.value || '').toLowerCase().trim();
    const mois = filterMois?.value || '';
    const annee = (filterAnnee?.value || '').trim();
    const statut = filterStatut?.value || '';

    let visibleCount = 0;

    const rows = Array.from(salairesTableBody.querySelectorAll('tr'))
        .filter(row => row.id !== 'noResultRow' && !row.querySelector('td[colspan="8"]'));

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowMois = row.dataset.mois || '';
        const rowAnnee = row.dataset.annee || '';
        const rowStatut = row.dataset.statut || '';

        const okSearch = q === '' || text.includes(q);
        const okMois = mois === '' || rowMois === mois;
        const okAnnee = annee === '' || rowAnnee === annee;
        const okStatut = statut === '' || rowStatut === statut;

        const show = okSearch && okMois && okAnnee && okStatut;

        row.style.display = show ? '' : 'none';

        if(show) visibleCount++;
    });

    if(noResultRow){
        noResultRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
    }
}

[searchSalaire, filterMois, filterAnnee, filterStatut].forEach(el => {
    el?.addEventListener('input', filtrerSalaires);
    el?.addEventListener('change', filtrerSalaires);
});


});

salaireInput.addEventListener('input', calculerNet);
avanceInput.addEventListener('input', calculerNet);
retenueInput.addEventListener('input', calculerNet);

calculerNet();


const searchSalaire = document.getElementById('searchSalaire');
const filterMois = document.getElementById('filterMois');
const filterAnnee = document.getElementById('filterAnnee');
const filterStatut = document.getElementById('filterStatut');
const salairesTableBody = document.getElementById('salairesTableBody');
const noResultRow = document.getElementById('noResultRow');

function filtrerSalaires(){
    if(!salairesTableBody) return;

    const q = (searchSalaire?.value || '').toLowerCase().trim();
    const mois = filterMois?.value || '';
    const annee = (filterAnnee?.value || '').trim();
    const statut = filterStatut?.value || '';

    let visibleCount = 0;

    const rows = Array.from(salairesTableBody.querySelectorAll('tr'))
        .filter(row => row.id !== 'noResultRow' && !row.querySelector('td[colspan="8"]'));

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowMois = row.dataset.mois || '';
        const rowAnnee = row.dataset.annee || '';
        const rowStatut = row.dataset.statut || '';

        const okSearch = q === '' || text.includes(q);
        const okMois = mois === '' || rowMois === mois;
        const okAnnee = annee === '' || rowAnnee === annee;
        const okStatut = statut === '' || rowStatut === statut;

        const show = okSearch && okMois && okAnnee && okStatut;

        row.style.display = show ? '' : 'none';

        if(show) visibleCount++;
    });

    if(noResultRow){
        noResultRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
    }
}

[searchSalaire, filterMois, filterAnnee, filterStatut].forEach(el => {
    el?.addEventListener('input', filtrerSalaires);
    el?.addEventListener('change', filtrerSalaires);
});


</script>

</body>

</html>