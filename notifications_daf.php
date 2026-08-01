<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

$moisActuel  = (int)date('m');
$anneeActuel = (int)date('Y');

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

$salairesNonPayes = $pdo->prepare("
    SELECT
        s.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    WHERE s.mois = ?
    AND s.annee = ?
    AND s.statut IN('non_paye','partiellement_paye')
    ORDER BY s.statut ASC, p.nom ASC
");

$salairesNonPayes->execute([
    $moisActuel,
    $anneeActuel
]);

$salairesNonPayes = $salairesNonPayes->fetchAll();

$avancesEnCours = $pdo->query("
    SELECT
        a.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM avances a
    INNER JOIN personnels p ON p.id = a.personnel_id
    WHERE a.statut = 'en_cours'
    ORDER BY a.date_avance DESC
    LIMIT 100
")->fetchAll();

$contratsExpiration = $pdo->query("
    SELECT
        id,
        nom,
        prenom,
        fonction,
        matricule,
        date_fin_contrat
    FROM personnels
    WHERE date_fin_contrat IS NOT NULL
    AND date_fin_contrat != '0000-00-00'
    AND date_fin_contrat BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY date_fin_contrat ASC
")->fetchAll();

$fichesNonGenerees = $pdo->prepare("
    SELECT
        s.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    LEFT JOIN fiches_paie fp ON fp.salaire_id = s.id
    WHERE s.mois = ?
    AND s.annee = ?
    AND fp.id IS NULL
    ORDER BY p.nom ASC
");

$fichesNonGenerees->execute([
    $moisActuel,
    $anneeActuel
]);

$fichesNonGenerees = $fichesNonGenerees->fetchAll();

$paiementsRecents = $pdo->query("
    SELECT
        ps.*,
        s.mois,
        s.annee,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM paiements_salaires ps
    INNER JOIN salaires s ON s.id = ps.salaire_id
    INNER JOIN personnels p ON p.id = s.personnel_id
    ORDER BY ps.date_paiement DESC
    LIMIT 10
")->fetchAll();

$totalNotifications =
    count($salairesNonPayes)
    + count($avancesEnCours)
    + count($contratsExpiration)
    + count($fichesNonGenerees);

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function dateFr($date){
    if(empty($date) || $date === '0000-00-00'){
        return '-';
    }

    return date('d/m/Y', strtotime($date));
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

<title>Notifications DAF</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap"
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

h1,h2,h3,.page-title,.card-title-custom,.stat-value{
    font-family:'Sora',sans-serif;
}

/* SIDEBAR DAF IDENTIQUE AUX AUTRES PAGES */
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
    color:var(--daf);
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
    background:linear-gradient(180deg,var(--daf),var(--daf-dark));
}

.menu a:hover i,
.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
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
.card-box{
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
    margin-bottom:28px;
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
    letter-spacing:-.3px;
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

.dashboard-menu-toggle,
.dark-toggle,
.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--daf);
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
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

.count-badge{
    background:var(--daf-soft);
    color:var(--daf);
    border:1px solid var(--daf-border);
    padding:10px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:7px;
}

/* STATS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:28px;
}

.stat-card{
    position:relative;
    min-height:150px;
    overflow:hidden;
    background:var(--card);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    padding:20px;
    box-shadow:var(--shadow-sm);
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:var(--shadow);
    border-color:rgba(201,107,88,.25);
}

.stat-card::before{
    content:'';
    position:absolute;
    right:-34px;
    top:-38px;
    width:122px;
    height:122px;
    border-radius:50%;
    background:var(--daf-soft);
    opacity:.95;
}

.stat-icon{
    position:relative;
    z-index:1;
    width:48px;
    height:48px;
    border-radius:17px;
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
    color:#fff;
    display:grid;
    place-items:center;
    font-size:21px;
    margin-bottom:14px;
    box-shadow:0 12px 22px rgba(201,107,88,.24);
}

.stat-value{
    position:relative;
    z-index:1;
    font-size:32px;
    font-weight:800;
    color:var(--text);
    line-height:1.1;
}

.stat-label{
    position:relative;
    z-index:1;
    font-size:12px;
    color:var(--muted);
    margin-top:7px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}

/* CARDS + TABLES */
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
    color:var(--daf);
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

.badge-alert{
    border-radius:20px;
    padding:6px 10px;
    font-size:11px;
    font-weight:800;
}

.badge-danger-soft{
    background:#fee2e2;
    color:#b91c1c;
}

.badge-warning-soft{
    background:#fef3c7;
    color:#92400e;
}

.badge-success-soft{
    background:#dcfce7;
    color:#166534;
}

.empty-box{
    text-align:center;
    padding:35px;
    color:#9ca3af;
}

.action-link{
    background:var(--daf);
    color:#fff;
    border-radius:12px;
    padding:8px 12px;
    text-decoration:none;
    font-size:12px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:6px;
    box-shadow:0 10px 20px rgba(201,107,88,.18);
}

.action-link:hover{
    background:#111827;
    color:white;
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
body.dark-mode .card-title-custom,
body.dark-mode .stat-value{
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

body.dark-mode .stat-card{
    background:#1a202c;
    border-color:#2d3340;
}

body.dark-mode .table thead th{
    background:#111827;
    color:#9ca3af;
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
@media(max-width:1200px){
    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

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

    .stats-grid{
        grid-template-columns:1fr;
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

<a href="salaires.php">
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

<a href="notifications_daf.php" class="active">
<i class="bi bi-bell-fill"></i>
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

</div>

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
Notifications DAF
</h1>

<p class="page-subtitle">
Alertes financières et suivis importants
</p>

</div>

</div>

<div class="header-actions">

<div class="count-badge">
<i class="bi bi-bell-fill"></i>
<?= number_format($totalNotifications) ?> alerte(s)
</div>

<button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre">
<i class="bi bi-moon-fill"></i>
</button>

</div>

</div>


<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-exclamation-circle-fill"></i>
</div>
<div class="stat-value">
<?= count($salairesNonPayes) ?>
</div>
<div class="stat-label">
Salaires non payés / partiels
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-wallet2"></i>
</div>
<div class="stat-value">
<?= count($avancesEnCours) ?>
</div>
<div class="stat-label">
Avances en cours
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-file-earmark-text-fill"></i>
</div>
<div class="stat-value">
<?= count($contratsExpiration) ?>
</div>
<div class="stat-label">
Contrats expirant bientôt
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-file-earmark-pdf-fill"></i>
</div>
<div class="stat-value">
<?= count($fichesNonGenerees) ?>
</div>
<div class="stat-label">
Fiches non générées
</div>
</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-exclamation-triangle-fill"></i>
Salaires non payés ou partiellement payés
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Période</th>
<th>Net à payer</th>
<th>Statut</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php if(count($salairesNonPayes) > 0): ?>

<?php foreach($salairesNonPayes as $s): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(($s['nom'] ?? '').' '.($s['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($s['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($s['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($moisNoms[(int)$s['mois']] ?? $s['mois'], ENT_QUOTES, 'UTF-8') ?>
<?= htmlspecialchars($s['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<strong>
<?= argent($s['salaire_net'] ?? 0) ?>
</strong>
</td>

<td>
<?php if(($s['statut'] ?? '') === 'partiellement_paye'): ?>
<span class="badge-alert badge-warning-soft">Partiel</span>
<?php else: ?>
<span class="badge-alert badge-danger-soft">Non payé</span>
<?php endif; ?>
</td>

<td>
<a
href="paiements.php"
class="action-link"
>
Payer
</a>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="6" class="empty-box">
Aucun salaire en attente pour ce mois.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-wallet2"></i>
Avances en cours
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Montant</th>
<th>Remboursé</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php if(count($avancesEnCours) > 0): ?>

<?php foreach($avancesEnCours as $a): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(($a['nom'] ?? '').' '.($a['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($a['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($a['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<strong>
<?= argent($a['montant'] ?? 0) ?>
</strong>
</td>

<td>
<?= argent($a['montant_rembourse'] ?? 0) ?>
</td>

<td>
<?= dateFr($a['date_avance'] ?? null) ?>
</td>

<td>
<a
href="avances.php"
class="action-link"
>
Voir
</a>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="6" class="empty-box">
Aucune avance en cours.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-file-earmark-text-fill"></i>
Contrats arrivant à expiration dans 30 jours
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Matricule</th>
<th>Date fin contrat</th>
</tr>
</thead>

<tbody>

<?php if(count($contratsExpiration) > 0): ?>

<?php foreach($contratsExpiration as $c): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(($c['nom'] ?? '').' '.($c['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
</td>

<td>
<?= htmlspecialchars($c['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($c['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<span class="badge-alert badge-warning-soft">
<?= dateFr($c['date_fin_contrat'] ?? null) ?>
</span>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="4" class="empty-box">
Aucun contrat n’expire dans les 30 prochains jours.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-file-earmark-pdf-fill"></i>
Fiches de paie non générées ce mois
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Période</th>
<th>Salaire net</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php if(count($fichesNonGenerees) > 0): ?>

<?php foreach($fichesNonGenerees as $f): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(($f['nom'] ?? '').' '.($f['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($f['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($f['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($moisNoms[(int)$f['mois']] ?? $f['mois'], ENT_QUOTES, 'UTF-8') ?>
<?= htmlspecialchars($f['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<strong>
<?= argent($f['salaire_net'] ?? 0) ?>
</strong>
</td>

<td>
<a
href="fiches_paie.php"
class="action-link"
>
Générer
</a>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="5" class="empty-box">
Toutes les fiches du mois sont générées ou aucun salaire n’est enregistré.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-clock-history"></i>
Paiements récents
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Montant</th>
<th>Mode</th>
<th>Date</th>
</tr>
</thead>

<tbody>

<?php if(count($paiementsRecents) > 0): ?>

<?php foreach($paiementsRecents as $p): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($p['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($p['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<strong>
<?= argent($p['montant'] ?? 0) ?>
</strong>
</td>

<td>
<span class="badge-alert badge-success-soft">
<?= htmlspecialchars($p['mode_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</span>
</td>

<td>
<?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="5" class="empty-box">
Aucun paiement récent.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


</div>

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
</script>

</body>

</html>

