<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';

header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:;"
);

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

if(isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800){
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$_SESSION['last_activity'] = time();

if(empty($_SESSION['session_init'])){
    session_regenerate_id(true);
    $_SESSION['session_init'] = true;
}

/* IDENTIFIER SI C'EST LE DAF */
$isDAF = false;

if(isset($_SESSION['admin_id'])){
    $reqAdmin = $pdo->prepare("
        SELECT fonction
        FROM admins
        WHERE id = ?
        LIMIT 1
    ");
    $reqAdmin->execute([$_SESSION['admin_id']]);
    $adminConnecte = $reqAdmin->fetch();

    if($adminConnecte){
        $isDAF = strtoupper(trim($adminConnecte['fonction'] ?? '')) === 'DAF';
    }
}

$dashboardLink = $isDAF ? 'dashboard_daf.php' : 'dashboard.php';
$profilLink    = $isDAF ? 'profil_daf.php' : 'profil_admin.php';

$fonction    = trim($_GET['fonction'] ?? '');
$statut      = trim($_GET['statut'] ?? 'actif');
$nationalite = trim($_GET['nationalite'] ?? '');
$search      = trim($_GET['search'] ?? '');

$sql = "
    SELECT *
    FROM personnels
    WHERE 1
";

$params = [];

if($statut !== ''){
    $sql .= " AND statut = ?";
    $params[] = $statut;
}

if($fonction !== ''){
    $sql .= " AND fonction = ?";
    $params[] = $fonction;
}

if($nationalite !== ''){
    $sql .= " AND nationalite = ?";
    $params[] = $nationalite;
}

if($search !== ''){
    $sql .= "
        AND (
            nom LIKE ?
            OR prenom LIKE ?
            OR matricule LIKE ?
            OR fonction LIKE ?
        )
    ";

    $searchLike = "%".$search."%";

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}

$sql .= " ORDER BY id DESC LIMIT 100";

$query = $pdo->prepare($sql);
$query->execute($params);
$personnels = $query->fetchAll();

$nations = $pdo->query("
    SELECT DISTINCT nationalite
    FROM personnels
    WHERE nationalite IS NOT NULL
    AND nationalite != ''
    ORDER BY nationalite ASC
")->fetchAll();

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personnel</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}

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
    --success:#16a34a;
    --warning:#d97706;
    --danger:#dc2626;
    --info:#0ea5e9;
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

h1,h2,h3,.page-title,.stat-value,.category-value,.presence-mini-value{font-family:'Sora',sans-serif}

/* SIDEBAR IDENTIQUE DASHBOARD */
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
    box-shadow:0 18px 45px rgba(15,23,42,.08);
    overflow:hidden;
    z-index:999;
}

.logo{
    color:var(--text);
    margin-bottom:12px;
    padding:6px 8px 12px;
    border-bottom:1px solid rgba(226,232,240,.9);
    text-align:center;
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
    margin:4px 0 0;
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

.menu > *{direction:ltr}

.menu::-webkit-scrollbar{width:5px}
.menu::-webkit-scrollbar-track{background:transparent}
.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.menu::-webkit-scrollbar-thumb:hover{background:var(--primary)}

.menu-section{margin-bottom:10px}

.menu-title{
    color:var(--muted);
    margin:0 0 5px 12px;
    font-size:9.5px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.14em;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:#475569;
    padding:9px 11px;
    min-height:43px;
    border-radius:16px;
    margin-bottom:3px;
    transition:all .2s;
    font-size:13px;
    font-weight:700;
    position:relative;
    border:1px solid transparent;
    background:transparent;
    white-space:nowrap;
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
    color:var(--primary);
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
    background:linear-gradient(180deg,#f59e0b,#d97706);
}

.menu a:hover i,
.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,#f59e0b,#d97706);
    box-shadow:0 10px 20px rgba(245,158,11,.22);
}

body.sidebar-collapsed .sidebar{
    width:84px;
    padding-left:10px;
    padding-right:10px;
}

body.sidebar-collapsed .main-content{margin-left:114px}

body.sidebar-collapsed .logo h4,
body.sidebar-collapsed .menu-title,
body.sidebar-collapsed .menu a span{display:none}

body.sidebar-collapsed .logo{
    padding-left:0;
    padding-right:0;
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
.main-content{
    margin-left:310px;
    padding:32px 40px;
    min-height:100vh;
    transition:margin-left .25s ease;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    flex-wrap:wrap;
    gap:16px;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    padding:20px 24px;
    box-shadow:var(--shadow-sm);
    position:relative;
    z-index:9000;
    overflow:visible;
}

.top-left{
    display:flex;
    align-items:center;
    gap:18px;
}

.page-title-with-toggle{
    display:flex;
    align-items:center;
    gap:13px;
}

.dashboard-menu-toggle,
.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--primary);
    display:grid;
    place-items:center;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    transition:all .2s ease;
    flex:0 0 auto;
    text-decoration:none;
}

.dashboard-menu-toggle:hover,
.back-btn:hover{
    background:linear-gradient(135deg,#f59e0b,#d97706);
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
    font-size:13px;
    color:var(--muted);
    margin-top:4px;
}

.header-actions{
    display:flex;
    gap:12px;
    align-items:center;
    position:relative;
    z-index:9500;
}

.school-year,
.year-badge{
    background:var(--primary-soft);
    border:1px solid rgba(37,99,235,.18);
    color:var(--primary);
    padding:10px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
}

.dark-toggle{
    width:40px;
    height:40px;
    border-radius:40px;
    background:#fff;
    border:1px solid var(--line);
    box-shadow:0 5px 14px rgba(15,23,42,.04);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:all .2s;
    color:#4b5563;
}

.dark-toggle:hover{
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

.btn-custom{
    border:none;
    background:var(--primary);
    color:white;
    padding:13px 22px;
    border-radius:14px;
    font-size:14px;
    font-weight:700;
    transition:.25s;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.btn-custom:hover{
    background:#0f172a;
    color:white;
    transform:translateY(-2px);
}

.filters,
.table-card,
.daf-note{
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    box-shadow:var(--shadow-sm);
}

.filters{
    padding:25px;
    margin-bottom:24px;
}

.form-label{
    font-size:13px;
    font-weight:700;
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
    border-color:var(--primary);
}

.table-card{
    padding:25px;
    overflow:hidden;
}

.table{
    margin:0;
}

.table thead{
    background:#0f172a;
    color:white;
}

.table thead th{
    border:none;
    padding:16px;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.table tbody td{
    padding:16px;
    vertical-align:middle;
    font-size:13px;
    color:#374151;
}

.photo{
    width:65px;
    height:65px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #e2e8f0;
}

.badge-fonction{
    font-size:12px;
    padding:8px 10px;
    background:var(--primary)!important;
}

.action-btn{
    width:36px;
    height:36px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.daf-note{
    color:#9a3412;
    padding:14px 18px;
    font-size:13px;
    margin-bottom:20px;
    background:rgba(255,247,237,.9);
    border-color:#fed7aa;
}

/* DARK MODE */
body.dark-mode{
    --bg:#0b1120;
    --card:#111827;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#243044;
    --primary-soft:rgba(37,99,235,.18);
    background:#0b1120;
}

body.dark-mode .sidebar,
body.dark-mode .topbar,
body.dark-mode .filters,
body.dark-mode .table-card{
    background:rgba(26,32,44,.86);
    border-color:#2d3340;
    box-shadow:0 18px 45px rgba(0,0,0,.22);
}

body.dark-mode .sidebar{
    border-color:rgba(255,255,255,.78)!important;
}

body.dark-mode .logo{
    border-bottom-color:#2d3340;
}

body.dark-mode .logo h4,
body.dark-mode .page-title,
body.dark-mode .form-label{
    color:#f0f2f5;
}

body.dark-mode .page-subtitle,
body.dark-mode .table tbody td{
    color:#cbd5e1;
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
    background:rgba(255,255,255,.06);
    border-color:#2d3340;
    color:#fbbf24;
}

body.dark-mode .dashboard-menu-toggle,
body.dark-mode .back-btn,
body.dark-mode .dark-toggle,
body.dark-mode .form-control,
body.dark-mode .form-select{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .table tbody{
    background:#1a202c;
}

body.dark-mode .table thead{
    background:#111827;
}

@media(max-width:1200px){
    .main-content{padding:24px}
}

@media(max-width:768px){
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

    .main-content{
        margin-left:285px!important;
        padding:18px 12px!important;
    }

    body.sidebar-collapsed .sidebar{
        width:78px!important;
    }

    body.sidebar-collapsed .main-content{
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
}
</style>

</head>
<body>

<!-- BARRE DE MENU UX -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h4><?= $isDAF ? 'ESPACE DAF' : 'GESTION SCOLAIRE' ?></h4>
    </div>

    <div class="menu">

        <?php if($isDAF): ?>

        <div class="menu-section">
            <div class="menu-title">Principal</div>

            <a href="dashboard_daf.php" title="Dashboard DAF">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Gestion financière</div>

            <a href="personnel.php" class="active" title="Personnel">
                <i class="bi bi-people"></i>
                <span>Personnel</span>
            </a>

            <a href="salaires.php" title="Salaires">
                <i class="bi bi-cash-stack"></i>
                <span>Salaires</span>
            </a>

            <a href="paiements.php" title="Paiements">
                <i class="bi bi-credit-card"></i>
                <span>Paiements</span>
            </a>

            <a href="avances.php" title="Avances">
                <i class="bi bi-wallet2"></i>
                <span>Avances</span>
            </a>

            <a href="retenues.php" title="Retenues">
                <i class="bi bi-dash-circle"></i>
                <span>Retenues</span>
            </a>

            <a href="impayes.php" title="Impayés">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Impayés</span>
            </a>

            <a href="fiches_paie.php" title="Fiches de paie">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>Fiches de paie</span>
            </a>

            <a href="statistiques_daf.php" title="Statistiques">
                <i class="bi bi-bar-chart"></i>
                <span>Statistiques</span>
            </a>

            <a href="notifications_daf.php" title="Notifications">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Compte</div>

            <a href="profil_daf.php" title="Profil">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
            </a>

            <a href="logout.php" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </div>

        <?php else: ?>

        <div class="menu-section">
            <div class="menu-title">Principal</div>

            <a href="dashboard.php" title="Tableau de bord">
                <i class="bi bi-speedometer2"></i>
                <span>Tableau de bord</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Gestion scolaire</div>

            <a href="personnel.php" class="active" title="Personnel">
                <i class="bi bi-people"></i>
                <span>Personnel</span>
            </a>

            <a href="presences.php" title="Présence">
                <i class="bi bi-check2-square"></i>
                <span>Présence</span>
            </a>

            <a href="emplois_temps.php" title="Emplois du temps">
                <i class="bi bi-calendar-week"></i>
                <span>Emplois du temps</span>
            </a>

            <a href="suspendus.php" title="Suspendus">
                <i class="bi bi-person-x"></i>
                <span>Suspendus</span>
            </a>

            <a href="documents.php" title="Documents">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>Documents</span>
            </a>

            <a href="notifications.php" title="Notifications">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Compte</div>

            <a href="profil_admin.php" title="Profil">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
            </a>

            <a href="logout.php" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </div>

        <?php endif; ?>

    </div>
</div>

<main class="main-content">

<div class="topbar">
    <div class="top-left page-title-with-toggle">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
            <i class="bi bi-list"></i>
        </button>

        <a href="<?= htmlspecialchars($dashboardLink, ENT_QUOTES, 'UTF-8') ?>" class="back-btn" title="Retour dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <div class="page-title">Liste du personnel</div>
            <div class="page-subtitle">
                <?= $isDAF ? 'Consultation du personnel par le DAF' : 'Gestion complète des personnels' ?>
            </div>
        </div>
    </div>

    <div class="header-actions">
        <div class="school-year">
            <i class="bi bi-calendar-check"></i>
            Année :
            <?= htmlspecialchars($annee['libelle'] ?? 'Non définie', ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="dark-toggle" id="darkToggle" title="Mode sombre">
            <i class="bi bi-moon-fill"></i>
        </div>
    </div>
</div>
<?php if($isDAF): ?>

<div class="daf-note">
<i class="bi bi-info-circle-fill"></i>
Le DAF peut consulter le personnel et accéder aux documents, mais il ne peut pas ajouter ni modifier un personnel.
</div>

<?php endif; ?>

<?php if(!$isDAF): ?>

<div class="d-flex mb-4">

<a href="ajouter.php" class="btn btn-custom">
<i class="bi bi-plus-circle"></i>
Ajouter personnel
</a>

</div>

<?php endif; ?>

<div class="filters">

<form method="GET">

<div class="row">

<div class="col-md-3 mb-3">

<label class="form-label">
Recherche
</label>

<input
type="text"
name="search"
class="form-control"
placeholder="Nom, matricule..."
value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>"
>

</div>

<div class="col-md-3 mb-3">

<label class="form-label">
Fonction
</label>

<select name="fonction" class="form-select">

<option value="">Toutes les fonctions</option>

<option value="DE" <?= $fonction === 'DE' ? 'selected' : '' ?>>DE</option>
<option value="ADE" <?= $fonction === 'ADE' ? 'selected' : '' ?>>ADE</option>
<option value="DAF" <?= $fonction === 'DAF' ? 'selected' : '' ?>>DAF</option>
<option value="SECRETAIRE" <?= $fonction === 'SECRETAIRE' ? 'selected' : '' ?>>SECRETAIRE</option>
<option value="EDUCATEUR" <?= $fonction === 'EDUCATEUR' ? 'selected' : '' ?>>EDUCATEUR</option>
<option value="PROFESSEUR" <?= $fonction === 'PROFESSEUR' ? 'selected' : '' ?>>PROFESSEUR</option>

</select>

</div>

<div class="col-md-2 mb-3">

<label class="form-label">
Statut
</label>

<select name="statut" class="form-select">

<option value="actif" <?= $statut === 'actif' ? 'selected' : '' ?>>Actif</option>
<option value="suspendu" <?= $statut === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>

</select>

</div>

<div class="col-md-2 mb-3">

<label class="form-label">
Nationalité
</label>

<select name="nationalite" class="form-select">

<option value="">Toutes</option>

<?php foreach($nations as $n): ?>

<option
value="<?= htmlspecialchars($n['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
<?= $nationalite === ($n['nationalite'] ?? '') ? 'selected' : '' ?>
>
<?= htmlspecialchars($n['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-2 mb-3 d-flex align-items-end">

<button
type="submit"
class="btn btn-dark w-100"
style="border-radius:14px;height:48px;"
>

<i class="bi bi-funnel"></i>
Filtrer

</button>

</div>

</div>

</form>

</div>

<div class="table-card mt-4">

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Photo</th>
<th>Nom complet</th>
<th>Fonction</th>
<th>Matricule</th>
<th>Téléphone</th>
<th>Nationalité</th>
<th>Statut</th>
<th>Actions</th>
</tr>

</thead>

<tbody>

<?php if(count($personnels) > 0): ?>

<?php foreach($personnels as $p): ?>

<tr>

<td>

<?php
$photo = 'uploads/photos/' . ($p['photo'] ?? '');

if(!empty($p['photo']) && file_exists($photo)):
?>

<img
src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>"
class="photo"
alt="Photo"
>

<?php else: ?>

<img
src="assets/avatar.png"
class="photo"
alt="Avatar"
>

<?php endif; ?>

</td>

<td>
<strong>
<?= htmlspecialchars(($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
</td>

<td>
<span class="badge bg-primary badge-fonction">
<?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</span>
</td>

<td>
<?= htmlspecialchars($p['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($p['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($p['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>

<?php if(($p['statut'] ?? '') === 'actif'): ?>

<span class="badge bg-success">
Actif
</span>

<?php else: ?>

<span class="badge bg-danger">
Suspendu
</span>

<?php endif; ?>

</td>

<td>

<div class="d-flex gap-2">

<a
href="voir.php?id=<?= (int)($p['id'] ?? 0) ?>"
class="btn btn-primary btn-sm action-btn"
title="Voir"
>
<i class="bi bi-eye"></i>
</a>

<?php if(!$isDAF): ?>

<a
href="modifier.php?id=<?= (int)($p['id'] ?? 0) ?>"
class="btn btn-warning btn-sm action-btn"
title="Modifier"
>
<i class="bi bi-pencil-square"></i>
</a>

<?php endif; ?>

<a
href="documents_personnel.php?id=<?= (int)($p['id'] ?? 0) ?>"
class="btn btn-success btn-sm action-btn"
title="Documents"
>
<i class="bi bi-file-earmark-pdf"></i>
</a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="8" class="text-center text-muted py-4">
Aucun personnel trouvé.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


</main>

<script>
    const sidebarToggle = document.getElementById('sidebarToggle');

    function setSidebarCollapsed(enabled) {
        document.body.classList.toggle('sidebar-collapsed', enabled);
        localStorage.setItem('sidebar-collapsed', enabled ? 'enabled' : 'disabled');
    }

    if(localStorage.getItem('sidebar-collapsed') === 'enabled'){
        setSidebarCollapsed(true);
    }

    sidebarToggle?.addEventListener('click', function(){
        setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
    });

    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = darkToggle?.querySelector('i');

    function setDarkMode(enabled) {
        if (enabled) {
            document.body.classList.add('dark-mode');
            if(darkIcon) darkIcon.className = 'bi bi-sun-fill';
        } else {
            document.body.classList.remove('dark-mode');
            if(darkIcon) darkIcon.className = 'bi bi-moon-fill';
        }
        localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');
    }

    if (localStorage.getItem('dark-mode') === 'enabled') {
        setDarkMode(true);
    }

    darkToggle?.addEventListener('click', () => {
        setDarkMode(!document.body.classList.contains('dark-mode'));
    });
</script>

</body>
</html>
