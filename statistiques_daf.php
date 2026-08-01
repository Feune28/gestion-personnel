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

$anneeActuelle = (int)date('Y');

$annee = intval($_GET['annee'] ?? $anneeActuelle);

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

$totalPersonnel = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE statut = 'actif'
")->fetch();

$masseBase = $pdo->prepare("
    SELECT COALESCE(SUM(s.salaire_base),0) AS total
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    WHERE s.annee = ?
    AND p.statut = 'actif'
");
$masseBase->execute([$annee]);
$masseBase = $masseBase->fetch();

$totalSalaires = $pdo->prepare("
    SELECT COALESCE(SUM(salaire_net),0) AS total
    FROM salaires
    WHERE annee = ?
");
$totalSalaires->execute([$annee]);
$totalSalaires = $totalSalaires->fetch();

$totalPaiements = $pdo->prepare("
    SELECT COALESCE(SUM(ps.montant),0) AS total
    FROM paiements_salaires ps
    INNER JOIN salaires s ON s.id = ps.salaire_id
    WHERE s.annee = ?
");
$totalPaiements->execute([$annee]);
$totalPaiements = $totalPaiements->fetch();

$totalAvances = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM avances
    WHERE YEAR(date_avance) = ?
");
$totalAvances->execute([$annee]);
$totalAvances = $totalAvances->fetch();

$totalRetenues = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM retenues
    WHERE YEAR(date_retenue) = ?
");
$totalRetenues->execute([$annee]);
$totalRetenues = $totalRetenues->fetch();

$totalFiches = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM fiches_paie
    WHERE annee = ?
");
$totalFiches->execute([$annee]);
$totalFiches = $totalFiches->fetch();

$salairesStatut = $pdo->prepare("
    SELECT statut, COUNT(*) AS total
    FROM salaires
    WHERE annee = ?
    GROUP BY statut
");
$salairesStatut->execute([$annee]);
$salairesStatut = $salairesStatut->fetchAll();

$parFonction = $pdo->prepare("
    SELECT
        COALESCE(p.fonction, 'Non défini') AS fonction,
        COUNT(DISTINCT p.id) AS nombre,
        COALESCE(SUM(s.salaire_base),0) AS masse
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    WHERE s.annee = ?
    AND p.statut = 'actif'
    GROUP BY p.fonction
    ORDER BY masse DESC
");
$parFonction->execute([$annee]);
$parFonction = $parFonction->fetchAll();

$evolutionSalaires = $pdo->prepare("
    SELECT
        mois,
        COALESCE(SUM(salaire_net),0) AS total
    FROM salaires
    WHERE annee = ?
    GROUP BY mois
    ORDER BY mois ASC
");
$evolutionSalaires->execute([$annee]);
$evolutionSalaires = $evolutionSalaires->fetchAll();

$evolutionPaiements = $pdo->prepare("
    SELECT
        s.mois,
        COALESCE(SUM(ps.montant),0) AS total
    FROM paiements_salaires ps
    INNER JOIN salaires s ON s.id = ps.salaire_id
    WHERE s.annee = ?
    GROUP BY s.mois
    ORDER BY s.mois ASC
");
$evolutionPaiements->execute([$annee]);
$evolutionPaiements = $evolutionPaiements->fetchAll();

$chartLabels = [];
$chartSalaires = [];
$chartPaiements = [];

for($i = 1; $i <= 12; $i++){

    $chartLabels[] = $moisNoms[$i];

    $montantSalaire = 0;
    $montantPaiement = 0;

    foreach($evolutionSalaires as $row){
        if((int)$row['mois'] === $i){
            $montantSalaire = (float)$row['total'];
            break;
        }
    }

    foreach($evolutionPaiements as $row){
        if((int)$row['mois'] === $i){
            $montantPaiement = (float)$row['total'];
            break;
        }
    }

    $chartSalaires[] = $montantSalaire;
    $chartPaiements[] = $montantPaiement;
}

$fonctionLabels = [];
$fonctionData = [];

foreach($parFonction as $f){
    $fonctionLabels[] = $f['fonction'] ?: 'Non défini';
    $fonctionData[] = (float)$f['masse'];
}

$statutLabels = [];
$statutData = [];

foreach($salairesStatut as $s){
    if($s['statut'] === 'paye'){
        $libelle = 'Payé';
    }elseif($s['statut'] === 'partiellement_paye'){
        $libelle = 'Partiel';
    }else{
        $libelle = 'Non payé';
    }

    $statutLabels[] = $libelle;
    $statutData[] = (int)$s['total'];
}

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

<title>Statistiques DAF</title>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
    --bg:#f3f6fb;--card:#ffffff;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;
    --daf:#c96b58;--daf-dark:#9f4f42;--daf-soft:#fdf4f2;--daf-border:#f0c9c1;
    --success:#16a34a;--warning:#d97706;--danger:#dc2626;--shadow:0 18px 45px rgba(15,23,42,.08);
    --shadow-sm:0 8px 24px rgba(15,23,42,.06);--radius:22px;
}
body{font-family:'Inter',sans-serif;background:radial-gradient(circle at top left,rgba(37,99,235,.13),transparent 28%),radial-gradient(circle at top right,rgba(245,158,11,.10),transparent 26%),var(--bg);color:var(--text);transition:.2s;overflow-x:hidden;}
h1,h2,h3,.page-title,.stat-value,.card-title-custom{font-family:'Sora',sans-serif;}
.sidebar{position:fixed;top:10px;left:10px;bottom:10px;width:280px;height:calc(100vh - 20px);padding:16px 16px 18px;background:rgba(255,255,255,.82);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:2.5px solid rgba(15,23,42,.92);border-radius:34px;box-shadow:var(--shadow);overflow:hidden;z-index:999;}
.logo{color:var(--text);text-align:center;margin-bottom:12px;padding:6px 8px 12px;border-bottom:1px solid rgba(226,232,240,.9);}.logo img{width:58px;height:58px;border-radius:19px;object-fit:cover;margin-bottom:8px;border:3px solid #fff;box-shadow:0 12px 26px rgba(15,23,42,.10);}.logo h4{color:var(--text);font-family:'Sora',sans-serif;font-size:14px;font-weight:800;margin-top:4px;}
.menu{height:calc(100vh - 128px);min-height:430px;padding-left:8px;padding-right:4px;padding-bottom:42px;overflow-y:auto;overflow-x:hidden;direction:rtl;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;}.menu>*{direction:ltr;}.menu::-webkit-scrollbar{width:5px;}.menu::-webkit-scrollbar-track{background:transparent;}.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}.menu::-webkit-scrollbar-thumb:hover{background:var(--daf);}.menu-section{margin-bottom:10px;}.menu-title{color:var(--muted);font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;margin:0 0 5px 12px;}.menu a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#475569;padding:9px 11px;border-radius:16px;margin-bottom:3px;min-height:43px;white-space:nowrap;font-weight:700;font-size:13px;position:relative;border:1px solid transparent;background:transparent;transition:.2s;}.menu a i{width:29px;height:29px;min-width:29px;border-radius:12px;display:grid;place-items:center;background:#f1f5f9;color:var(--muted);font-size:15px;}.menu a:hover,.menu a.active{background:rgba(255,255,255,.9);border-color:rgba(226,232,240,.95);color:var(--daf);box-shadow:0 10px 24px rgba(15,23,42,.06);}.menu a.active::before{content:'';position:absolute;left:-8px;top:13px;bottom:13px;width:4px;border-radius:999px;background:linear-gradient(180deg,var(--daf),var(--daf-dark));}.menu a:hover i,.menu a.active i{color:#fff;background:linear-gradient(135deg,var(--daf),var(--daf-dark));box-shadow:0 10px 20px rgba(201,107,88,.25);}
.main{margin-left:310px;padding:32px 40px;min-height:100vh;transition:margin-left .25s ease;}.topbar,.filter-box,.card-box,.stat-card,.dashboard-footer{background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);box-shadow:var(--shadow-sm);}.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:16px;padding:20px 24px;overflow:visible;position:relative;z-index:20;}.top-left{display:flex;align-items:center;gap:13px;}.page-title{font-size:27px;font-weight:800;color:var(--text);margin:0;}.page-subtitle{color:var(--muted);font-size:13px;margin-top:4px;margin-bottom:0;}.header-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}.year-badge{background:var(--daf-soft);border:1px solid var(--daf-border);padding:8px 18px;border-radius:40px;font-size:13px;font-weight:800;color:var(--daf);}.dashboard-menu-toggle,.dark-toggle,.back-btn{width:42px;height:42px;border-radius:15px;border:1px solid var(--line);background:#fff;color:var(--daf);display:grid;place-items:center;cursor:pointer;box-shadow:0 8px 20px rgba(15,23,42,.06);transition:all .2s ease;flex:0 0 auto;text-decoration:none;}.dashboard-menu-toggle:hover,.dark-toggle:hover,.back-btn:hover{background:linear-gradient(135deg,var(--daf),var(--daf-dark));color:#fff;border-color:transparent;transform:translateY(-1px);}
.filter-box{padding:20px;margin-bottom:28px;}.form-label{font-size:13px;font-weight:800;color:var(--text);margin-bottom:7px;}.form-control,.form-select{border-radius:14px;border:1px solid var(--line);min-height:48px;font-size:14px;box-shadow:none!important;background:#fff;}.form-control:focus,.form-select:focus{border-color:var(--daf);box-shadow:0 0 0 .2rem rgba(201,107,88,.10)!important;}.btn-main{background:var(--daf);color:white;border:none;border-radius:14px;padding:12px 22px;font-weight:800;box-shadow:0 12px 22px rgba(201,107,88,.22);}.btn-main:hover{background:#111827;color:white;transform:translateY(-1px);}.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;}.stat-card{position:relative;min-height:150px;overflow:hidden;padding:20px;transition:.22s;}.stat-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:rgba(201,107,88,.28);}.stat-card::before{content:'';position:absolute;right:-34px;top:-38px;width:122px;height:122px;border-radius:50%;background:var(--daf-soft);opacity:.95;}.stat-icon{position:relative;z-index:1;width:48px;height:48px;border-radius:17px;display:grid;place-items:center;background:linear-gradient(135deg,var(--daf),var(--daf-dark));color:#fff;box-shadow:0 12px 22px rgba(201,107,88,.24);margin-bottom:14px;}.stat-icon i{font-size:21px;color:#fff;}.stat-value{position:relative;z-index:1;font-size:24px;font-weight:800;color:var(--text);letter-spacing:-1px;margin-top:5px;line-height:1.15;word-break:break-word;}.stat-label{position:relative;z-index:1;color:var(--muted);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-top:8px;}.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:28px;}.card-box{padding:24px;margin-bottom:28px;}.card-title-custom{font-size:18px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:var(--text);}.card-title-custom i{color:var(--daf);}.chart-container{height:320px;position:relative;}.table{margin:0;}.table thead th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);padding:14px 16px;border-bottom:1px solid var(--line);}.table tbody td{padding:14px 16px;font-size:13px;vertical-align:middle;color:#374151;}.table tbody tr:hover{background:#fafbfc;}.dashboard-footer{margin-top:32px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;color:var(--muted);font-size:12px;font-weight:500;}.footer-left{display:flex;align-items:center;gap:10px;}.footer-left img{width:30px;height:30px;border-radius:8px;object-fit:cover;}.footer-secure{display:flex;align-items:center;gap:6px;color:#166534;font-weight:700;}
body.sidebar-collapsed .sidebar{width:84px!important;padding-left:10px!important;padding-right:10px!important;}body.sidebar-collapsed .main{margin-left:114px!important;}body.sidebar-collapsed .logo h4,body.sidebar-collapsed .menu-title,body.sidebar-collapsed .menu a span{display:none;}body.sidebar-collapsed .logo img{width:48px;height:48px;border-radius:17px;}body.sidebar-collapsed .menu{padding-left:4px;padding-right:0;padding-bottom:42px;}body.sidebar-collapsed .menu a{padding:10px 7px;justify-content:center;}body.sidebar-collapsed .menu a i{width:34px;height:34px;min-width:34px;}
body.dark-mode{--bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#2d3340;background:#0b1120;}body.dark-mode .sidebar,body.dark-mode .topbar,body.dark-mode .filter-box,body.dark-mode .card-box,body.dark-mode .stat-card,body.dark-mode .dashboard-footer,body.dark-mode .dashboard-menu-toggle,body.dark-mode .dark-toggle,body.dark-mode .back-btn{background:#1a202c;border-color:#2d3340;color:#f0f2f5;}body.dark-mode .sidebar{border-color:rgba(255,255,255,.78)!important;}body.dark-mode .logo{border-bottom-color:#2d3340;}body.dark-mode .logo h4,body.dark-mode .page-title,body.dark-mode .card-title-custom,body.dark-mode .stat-value{color:#f0f2f5;}body.dark-mode .menu a{color:#cbd5e1;}body.dark-mode .menu a i{background:#111827;color:#94a3b8;}body.dark-mode .menu a:hover,body.dark-mode .menu a.active{background:rgba(201,107,88,.15);border-color:#2d3340;color:#e5a498;}body.dark-mode .form-control,body.dark-mode .form-select{background:#111827;border-color:#2d3340;color:#e5e7eb;}body.dark-mode .table thead th{background:#111827;border-color:#2d3340;}body.dark-mode .table tbody td{color:#e5e7eb;border-color:#2d3340;}body.dark-mode .table tbody tr:hover{background:#111827;}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr);}.chart-grid{grid-template-columns:1fr;}.main{padding:24px 28px;}}@media(max-width:900px){.sidebar{top:8px!important;left:8px!important;bottom:8px!important;width:265px!important;height:calc(100vh - 16px)!important;padding:14px 14px 16px!important;border-radius:32px!important;transform:none!important;}.menu{height:calc(100vh - 116px)!important;min-height:420px!important;padding-bottom:46px!important;}.main{margin-left:285px!important;padding:18px 12px!important;}body.sidebar-collapsed .sidebar{width:78px!important;}body.sidebar-collapsed .main{margin-left:96px!important;}.topbar{flex-direction:column;align-items:flex-start;}.header-actions{width:100%;flex-wrap:wrap;}.stats-grid{grid-template-columns:1fr;}.dashboard-footer{flex-direction:column;align-items:flex-start;}}
</style>

</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h4>ESPACE DAF</h4>
    </div>
    <div class="menu">
        <div class="menu-section">
            <div class="menu-title">Principal</div>
            <a href="dashboard_daf.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-title">Gestion financière</div>
            <a href="personnel.php"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="salaires.php"><i class="bi bi-cash-stack"></i><span>Salaires</span></a>
            <a href="paiements.php"><i class="bi bi-credit-card"></i><span>Paiements</span></a>
            <a href="avances.php"><i class="bi bi-wallet2"></i><span>Avances</span></a>
            <a href="retenues.php"><i class="bi bi-dash-circle"></i><span>Retenues</span></a>
            <a href="impayes.php"><i class="bi bi-exclamation-triangle"></i><span>Impayés</span></a>
            <a href="fiches_paie.php"><i class="bi bi-file-earmark-pdf"></i><span>Fiches de paie</span></a>
            <a href="statistiques_daf.php" class="active"><i class="bi bi-bar-chart"></i><span>Statistiques</span></a>
            <a href="notifications_daf.php"><i class="bi bi-bell"></i><span>Notifications</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-title">Compte</div>
            <a href="profil_daf.php"><i class="bi bi-person-circle"></i><span>Profil</span></a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a>
        </div>
    </div>
</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
<i class="bi bi-list"></i>
</button>

<a href="dashboard_daf.php" class="back-btn" title="Retour au dashboard DAF">
<i class="bi bi-arrow-left"></i>
</a>

<div>

<h1 class="page-title">
Statistiques financières
</h1>

<p class="page-subtitle">
Analyse annuelle des salaires, paiements, avances et retenues
</p>

</div>

</div>

<div class="header-actions">
<div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?></div>
<button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></button>
</div>

</div>

<div class="filter-box">

<form method="GET">

<div class="row g-3 align-items-end">

<div class="col-md-4">

<label class="form-label">
Année
</label>

<input
type="number"
name="annee"
class="form-control"
value="<?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>"
>

</div>

<div class="col-md-3">

<button
type="submit"
class="btn btn-main w-100"
>
<i class="bi bi-funnel"></i>
Filtrer
</button>

</div>

</div>

</form>

</div>

<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-people-fill"></i>
</div>
<div class="stat-value">
<?= number_format((int)($totalPersonnel['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Personnel actif
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-cash-stack"></i>
</div>
<div class="stat-value">
<?= argent($masseBase['total'] ?? 0) ?>
</div>
<div class="stat-label">
Masse salariale de base
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-wallet2"></i>
</div>
<div class="stat-value">
<?= argent($totalAvances['total'] ?? 0) ?>
</div>
<div class="stat-label">
Avances en <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-dash-circle-fill"></i>
</div>
<div class="stat-value">
<?= argent($totalRetenues['total'] ?? 0) ?>
</div>
<div class="stat-label">
Retenues en <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-bank"></i>
</div>
<div class="stat-value">
<?= argent($totalSalaires['total'] ?? 0) ?>
</div>
<div class="stat-label">
Salaires enregistrés
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-credit-card"></i>
</div>
<div class="stat-value">
<?= argent($totalPaiements['total'] ?? 0) ?>
</div>
<div class="stat-label">
Paiements effectués
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-file-earmark-pdf-fill"></i>
</div>
<div class="stat-value">
<?= number_format((int)($totalFiches['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Fiches de paie générées
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-calculator-fill"></i>
</div>
<div class="stat-value">
<?= argent(($totalSalaires['total'] ?? 0) - ($totalPaiements['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Reste théorique à payer
</div>
</div>

</div>

<div class="chart-grid">

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-graph-up"></i>
Évolution mensuelle
</div>

<div class="chart-container">

<canvas id="evolutionChart"></canvas>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-pie-chart-fill"></i>
Statut des salaires
</div>

<div class="chart-container">

<canvas id="statutChart"></canvas>

</div>

</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-briefcase-fill"></i>
Masse salariale de base par fonction
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Fonction</th>
<th>Nombre</th>
<th>Masse salariale</th>
</tr>

</thead>

<tbody>

<?php if(count($parFonction) > 0): ?>

<?php foreach($parFonction as $f): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars($f['fonction'] ?? 'Non défini', ENT_QUOTES, 'UTF-8') ?>
</strong>
</td>

<td>
<?= number_format((int)($f['nombre'] ?? 0)) ?>
</td>

<td>
<strong>
<?= argent($f['masse'] ?? 0) ?>
</strong>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td
colspan="3"
class="text-center text-muted py-4"
>
Aucune donnée disponible.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


<footer class="dashboard-footer">
    <div class="footer-left">
        <img src="uploads/logo/logo.jpeg" alt="Logo CMAK">
        <span>© <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?> CMAK - Statistiques financières. Tous droits réservés.</span>
    </div>
    <div class="footer-secure"><i class="bi bi-shield-check"></i> Données sécurisées</div>
</footer>

</div>

<script>
const sidebarToggle = document.getElementById('sidebarToggle');
function setSidebarCollapsed(enabled){
    document.body.classList.toggle('sidebar-collapsed', enabled);
    localStorage.setItem('sidebar-collapsed-daf', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('sidebar-collapsed-daf') === 'enabled') setSidebarCollapsed(true);
sidebarToggle?.addEventListener('click', function(){
    setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
});

const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');
function setDarkMode(enabled){
    document.body.classList.toggle('dark-mode', enabled);
    if(darkIcon) darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    localStorage.setItem('dark-mode-daf', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('dark-mode-daf') === 'enabled') setDarkMode(true);
darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});
</script>

<script>

const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');

new Chart(evolutionCtx, {
    type:'bar',
    data:{
        labels:<?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets:[
            {
                label:'Salaires enregistrés',
                data:<?= json_encode($chartSalaires) ?>,
                backgroundColor:'#c96b58',
                borderRadius:8
            },
            {
                label:'Paiements effectués',
                data:<?= json_encode($chartPaiements) ?>,
                backgroundColor:'#9f4f42',
                borderRadius:8
            }
        ]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            legend:{
                position:'bottom'
            }
        },
        scales:{
            y:{
                beginAtZero:true
            }
        }
    }
});

const statutCtx = document.getElementById('statutChart').getContext('2d');

new Chart(statutCtx, {
    type:'doughnut',
    data:{
        labels:<?= json_encode($statutLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets:[{
            data:<?= json_encode($statutData) ?>,
            backgroundColor:[
                '#16a34a',
                '#f59e0b',
                '#dc2626'
            ],
            borderWidth:0
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        cutout:'65%',
        plugins:{
            legend:{
                position:'bottom'
            }
        }
    }
});

</script>

</body>

</html>