<?php
session_start();
require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

if(!isset($_SESSION['personnel'])){
    header('Location: login_personnel.php');
    exit();
}

$personnel_id = (int) $_SESSION['personnel'];

$personnelReq = $pdo->prepare("
    SELECT *
    FROM personnels
    WHERE id = ?
    LIMIT 1
");
$personnelReq->execute([$personnel_id]);
$personnel = $personnelReq->fetch();

if(!$personnel){
    session_destroy();
    header('Location: login_personnel.php');
    exit();
}

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

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function statutPaiement($statut){
    if($statut === 'paye'){
        return '<span class="badge bg-success">Payé</span>';
    }

    if($statut === 'partiellement_paye'){
        return '<span class="badge bg-warning text-dark">Partiellement payé</span>';
    }

    return '<span class="badge bg-danger">Non payé</span>';
}

$salairesReq = $pdo->prepare("
    SELECT
        s.*,
        (
            SELECT COALESCE(SUM(ps.montant),0)
            FROM paiements_salaires ps
            WHERE ps.salaire_id = s.id
        ) AS total_paye,
        fp.fichier_pdf
    FROM salaires s
    LEFT JOIN fiches_paie fp ON fp.salaire_id = s.id
    WHERE s.personnel_id = ?
    ORDER BY s.annee DESC, s.mois DESC
");
$salairesReq->execute([$personnel_id]);
$salaires = $salairesReq->fetchAll();

$avancesReq = $pdo->prepare("
    SELECT *
    FROM avances
    WHERE personnel_id = ?
    ORDER BY date_avance DESC
");
$avancesReq->execute([$personnel_id]);
$avances = $avancesReq->fetchAll();

$retenuesReq = $pdo->prepare("
    SELECT *
    FROM retenues
    WHERE personnel_id = ?
    ORDER BY date_retenue DESC
");
$retenuesReq->execute([$personnel_id]);
$retenues = $retenuesReq->fetchAll();

$totalSalaireNet = 0;
$totalPaye = 0;
$totalAvances = 0;
$totalRetenues = 0;

foreach($salaires as $s){
    $totalSalaireNet += (float)($s['salaire_net'] ?? 0);
    $totalPaye += (float)($s['total_paye'] ?? 0);
}

foreach($avances as $a){
    $totalAvances += (float)($a['montant'] ?? 0);
}

foreach($retenues as $r){
    $totalRetenues += (float)($r['montant'] ?? 0);
}

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$photo = 'uploads/photos/' . ($personnel['photo'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes paiements | Espace Personnel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    --primary:#2563eb;
    --primary-soft:#dbeafe;
    --terracotta:#c96b58;
    --terracotta-dark:#9f4f42;
    --terracotta-soft:#fdf4f2;
    --terracotta-border:#f0c9c1;
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
        radial-gradient(circle at top left,rgba(37,99,235,.13),transparent 28%),
        radial-gradient(circle at top right,rgba(245,158,11,.10),transparent 26%),
        var(--bg);
    color:var(--text);
    overflow-x:hidden;
}

h1,h2,h3,.page-title,.card-title-custom,.stat-value{
    font-family:'Sora',sans-serif;
}

/* SIDEBAR */

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
    color:#fff;
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
    color:#fff;
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

.menu>*{direction:ltr}

.menu::-webkit-scrollbar{width:5px}
.menu::-webkit-scrollbar-track{background:#1f2937}
.menu::-webkit-scrollbar-thumb{background:var(--terracotta);border-radius:999px}

.menu-section{margin-bottom:10px}

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

.menu a:hover,.menu a.active{
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
    background:linear-gradient(180deg,var(--terracotta),var(--terracotta-dark));
}

.menu a:hover i,.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,var(--terracotta),var(--terracotta-dark));
}

/* COLLAPSE */

body.sidebar-collapsed .sidebar{
    width:84px!important;
    padding-left:10px!important;
    padding-right:10px!important;
}

body.sidebar-collapsed .main{margin-left:114px!important}

body.sidebar-collapsed .logo h4,
body.sidebar-collapsed .menu-title,
body.sidebar-collapsed .menu a span{display:none}

body.sidebar-collapsed .logo img{width:48px;height:48px;border-radius:17px}
body.sidebar-collapsed .menu{padding-left:4px;padding-right:0}
body.sidebar-collapsed .menu a{padding:10px 7px;justify-content:center}
body.sidebar-collapsed .menu a i{width:34px;height:34px;min-width:34px}

/* MAIN */

.main{
    margin-left:310px;
    padding:32px 40px;
    min-height:100vh;
    transition:margin-left .25s ease;
}

.topbar,.card-box,.stat-card,.dashboard-footer{
    background:rgba(255,255,255,.84);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    box-shadow:var(--shadow-sm);
}

.topbar{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:18px;
    padding:20px 24px;
    margin-bottom:25px;
}

.top-left{
    display:flex;
    align-items:center;
    gap:13px;
    min-width:0;
}

.top-left>div{min-width:0}

.sidebar-toggle,.dark-toggle,.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--terracotta);
    display:grid;
    place-items:center;
    cursor:pointer;
    text-decoration:none;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    transition:.2s;
    flex:0 0 auto;
}

.sidebar-toggle:hover,.dark-toggle:hover,.back-btn:hover{
    background:linear-gradient(135deg,var(--terracotta),var(--terracotta-dark));
    color:#fff;
    border-color:transparent;
}

.page-title{
    font-size:27px;
    font-weight:800;
    margin:0;
    color:var(--text);
}

.page-subtitle{
    font-size:13px;
    color:var(--muted);
    margin-top:4px;
}

.header-actions{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:nowrap;
    white-space:nowrap;
}

.year-badge,.badge-role{
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    color:var(--terracotta);
    border-radius:40px;
    padding:8px 16px;
    font-size:13px;
    font-weight:800;
}

.profile-chip{
    display:flex;
    align-items:center;
    gap:10px;
}

.profile-small{
    width:42px;
    height:42px;
    border-radius:14px;
    object-fit:cover;
    border:2px solid var(--terracotta-border);
}

/* STATS */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:18px;
    margin-bottom:25px;
}

.stat-card{
    padding:22px;
    min-width:0;
    overflow:hidden;
    position:relative;
}

.stat-card::after{
    content:'';
    position:absolute;
    right:-35px;
    top:-35px;
    width:110px;
    height:110px;
    border-radius:50%;
    background:var(--terracotta-soft);
}

.stat-icon{
    position:relative;
    z-index:1;
    width:46px;
    height:46px;
    border-radius:15px;
    background:#111827;
    color:#fff;
    display:grid;
    place-items:center;
    font-size:20px;
    margin-bottom:14px;
}

.stat-value{
    position:relative;
    z-index:1;
    font-size:22px;
    font-weight:800;
    color:var(--text);
    word-break:break-word;
}

.stat-label{
    position:relative;
    z-index:1;
    font-size:13px;
    color:var(--muted);
    margin-top:5px;
}

/* CONTENT */

.card-box{
    padding:24px;
    margin-bottom:28px;
    min-width:0;
}

.card-title-custom{
    font-size:18px;
    font-weight:800;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:10px;
}

.card-title-custom i{color:var(--terracotta)}

.table-responsive{
    width:100%;
    overflow-x:auto;
}

.table{margin:0}

.table thead th{
    background:#111827;
    color:#fff;
    border:none;
    padding:14px;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
}

.table tbody td{
    padding:14px;
    vertical-align:middle;
    font-size:13px;
}

.btn-action{
    border-radius:10px;
    font-size:12px;
    font-weight:700;
}

.badge{
    border-radius:30px;
    padding:7px 10px;
    font-weight:800;
}

.finance-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:20px;
}

.modal-content{
    border:none;
    border-radius:22px;
    overflow:hidden;
}

.modal-header,.modal-footer{
    border-color:var(--line);
}

/* FOOTER */

.dashboard-footer{
    margin-top:30px;
    padding:14px 18px;
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:16px;
    color:var(--muted);
    font-size:12px;
}

.footer-left{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
}

.footer-left img{
    width:30px;
    height:30px;
    object-fit:cover;
    border-radius:8px;
}

.footer-left span{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.footer-secure{
    white-space:nowrap;
    color:#166534;
    font-weight:700;
}

/* DARK */

body.dark-mode{
    --bg:#0b1120;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#2d3340;
    background:#0b1120;
}

body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .stat-card,
body.dark-mode .dashboard-footer,
body.dark-mode .sidebar-toggle,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn,
body.dark-mode .modal-content{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .page-title,
body.dark-mode .stat-value,
body.dark-mode .card-title-custom{
    color:#f0f2f5;
}

body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}

body.dark-mode .year-badge,
body.dark-mode .badge-role{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}

/* RESPONSIVE */

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}

@media(max-width:900px){
    .sidebar{
        top:8px!important;
        left:8px!important;
        bottom:8px!important;
        width:265px!important;
        height:calc(100vh - 16px)!important;
    }

    .main{
        margin-left:285px!important;
        padding:18px 12px!important;
    }

    body.sidebar-collapsed .sidebar{width:78px!important}
    body.sidebar-collapsed .main{margin-left:96px!important}

    .topbar,.dashboard-footer{
        grid-template-columns:1fr;
        align-items:flex-start;
    }

    .header-actions{
        justify-content:flex-start;
        flex-wrap:wrap;
    }

    .footer-left span{
        white-space:normal;
        overflow:visible;
        text-overflow:clip;
    }

    .finance-grid{grid-template-columns:1fr}
}

@media(max-width:650px){
    .stats-grid{grid-template-columns:1fr}
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

    <a href="paiements_personnel.php" class="active">
        <i class="bi bi-cash-stack"></i>
        <span>Mes paiements</span>
    </a>

    <a href="presences_personnel.php">
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

</div></div>

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
    <h1 class="page-title">Mes paiements</h1>
    <div class="page-subtitle">Salaires, avances, retenues et fiches de paie</div>
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

<div class="profile-chip">

<?php if(!empty($personnel['photo']) && file_exists($photo)): ?>
    <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" class="profile-small" alt="Photo">
<?php else: ?>
    <img src="assets/avatar.png" class="profile-small" alt="Photo">
<?php endif; ?>

<span class="badge-role">
    <?= htmlspecialchars($personnel['fonction'] ?? 'Personnel', ENT_QUOTES, 'UTF-8') ?>
</span>

</div>

</div>

</div>

<div class="stats-grid">

<div class="stat-card">
    <div class="stat-icon">
        <i class="bi bi-wallet2"></i>
    </div>
    <div class="stat-value"><?= argent($totalSalaireNet) ?></div>
    <div class="stat-label">Total salaires nets</div>
</div>

<div class="stat-card">
    <div class="stat-icon">
        <i class="bi bi-check-circle"></i>
    </div>
    <div class="stat-value"><?= argent($totalPaye) ?></div>
    <div class="stat-label">Total payé</div>
</div>

<div class="stat-card">
    <div class="stat-icon">
        <i class="bi bi-arrow-down-circle"></i>
    </div>
    <div class="stat-value"><?= argent($totalAvances) ?></div>
    <div class="stat-label">Avances reçues</div>
</div>

<div class="stat-card">
    <div class="stat-icon">
        <i class="bi bi-dash-circle"></i>
    </div>
    <div class="stat-value"><?= argent($totalRetenues) ?></div>
    <div class="stat-label">Retenues</div>
</div>

</div>

<div class="card-box">

<h5 class="card-title-custom"><i class="bi bi-list-check"></i> Historique des salaires</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Période</th>
    <th>Salaire base</th>
    <th>Prime</th>
    <th>Avance</th>
    <th>Retenue</th>
    <th>Salaire net</th>
    <th>Payé</th>
    <th>Statut</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php if(count($salaires) > 0): ?>

<?php foreach($salaires as $s): ?>

<?php
$periode = ($moisNoms[(int)$s['mois']] ?? $s['mois']) . ' ' . $s['annee'];
?>

<tr>
    <td><?= htmlspecialchars($periode, ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= argent($s['salaire_base'] ?? 0) ?></td>
    <td><?= argent($s['prime'] ?? 0) ?></td>
    <td><?= argent($s['avance'] ?? 0) ?></td>
    <td><?= argent($s['retenue'] ?? 0) ?></td>
    <td><strong><?= argent($s['salaire_net'] ?? 0) ?></strong></td>
    <td><?= argent($s['total_paye'] ?? 0) ?></td>
    <td><?= statutPaiement($s['statut'] ?? 'non_paye') ?></td>
    <td>
        <button
            type="button"
            class="btn btn-sm btn-outline-primary btn-action"
            data-bs-toggle="modal"
            data-bs-target="#detailSalaire<?= (int)$s['id'] ?>"
        >
            <i class="bi bi-eye"></i>
            Voir
        </button>

        <?php if(!empty($s['fichier_pdf'])): ?>
            <a
                href="uploads/fiches_paie/<?= htmlspecialchars($s['fichier_pdf'], ENT_QUOTES, 'UTF-8') ?>"
                target="_blank"
                class="btn btn-sm btn-dark btn-action"
            >
                <i class="bi bi-file-earmark-pdf"></i>
                Fiche
            </a>
        <?php endif; ?>
    </td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="9" class="text-center text-muted py-4">
        Aucun salaire enregistré pour votre compte.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

<div class="finance-grid">

<div>

<div class="card-box h-100">

<h5 class="card-title-custom"><i class="bi bi-arrow-down-circle"></i> Mes avances</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Date</th>
    <th>Montant</th>
    <th>Remboursé</th>
    <th>Statut</th>
</tr>
</thead>

<tbody>

<?php if(count($avances) > 0): ?>

<?php foreach($avances as $a): ?>

<tr>
    <td>
        <?= !empty($a['date_avance'])
            ? htmlspecialchars(date('d/m/Y', strtotime($a['date_avance'])), ENT_QUOTES, 'UTF-8')
            : '-' ?>
    </td>

    <td>
        <strong><?= argent($a['montant'] ?? 0) ?></strong>
    </td>

    <td>
        <?= argent($a['montant_rembourse'] ?? 0) ?>
    </td>

    <td>
        <?php if(($a['statut'] ?? '') === 'remboursee'): ?>
            <span class="badge bg-success">Remboursée</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">En cours</span>
        <?php endif; ?>
    </td>
</tr>

<?php if(!empty($a['motif'])): ?>
<tr>
    <td colspan="4" class="bg-light">
        <small class="text-muted">
            <strong>Motif :</strong>
            <?= htmlspecialchars($a['motif'], ENT_QUOTES, 'UTF-8') ?>
        </small>
    </td>
</tr>
<?php endif; ?>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="4" class="text-center text-muted py-4">
        Aucune avance enregistrée.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

<div>

<div class="card-box h-100">

<h5 class="card-title-custom"><i class="bi bi-dash-circle"></i> Mes retenues</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Date</th>
    <th>Motif</th>
    <th>Montant</th>
</tr>
</thead>

<tbody>

<?php if(count($retenues) > 0): ?>

<?php foreach($retenues as $r): ?>

<tr>
    <td>
        <?= !empty($r['date_retenue'])
            ? htmlspecialchars(date('d/m/Y', strtotime($r['date_retenue'])), ENT_QUOTES, 'UTF-8')
            : '-' ?>
    </td>

    <td>
        <?= htmlspecialchars($r['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
    </td>

    <td>
        <strong><?= argent($r['montant'] ?? 0) ?></strong>
    </td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="3" class="text-center text-muted py-4">
        Aucune retenue enregistrée.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

</div>

<?php foreach($salaires as $s): ?>

<?php
$paiementsReq = $pdo->prepare("
    SELECT *
    FROM paiements_salaires
    WHERE salaire_id = ?
    ORDER BY date_paiement DESC, id DESC
");

$paiementsReq->execute([(int)$s['id']]);
$paiementsSalaire = $paiementsReq->fetchAll();

$resteAPayer = max(
    0,
    (float)($s['salaire_net'] ?? 0) - (float)($s['total_paye'] ?? 0)
);

$periodeModal = ($moisNoms[(int)$s['mois']] ?? $s['mois']) . ' ' . $s['annee'];
?>

<div
    class="modal fade"
    id="detailSalaire<?= (int)$s['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>

<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

<div class="modal-content">

<div class="modal-header">

<div>
    <h5 class="modal-title fw-bold">
        Détail du paiement
    </h5>

    <small class="text-muted">
        <?= htmlspecialchars($periodeModal, ENT_QUOTES, 'UTF-8') ?>
    </small>
</div>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
    aria-label="Fermer"
></button>

</div>

<div class="modal-body">

<div class="row g-3 mb-4">

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Salaire de base</small>
    <div class="fw-bold mt-1">
        <?= argent($s['salaire_base'] ?? 0) ?>
    </div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Prime</small>
    <div class="fw-bold mt-1">
        <?= argent($s['prime'] ?? 0) ?>
    </div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Avance déduite</small>
    <div class="fw-bold mt-1 text-warning">
        <?= argent($s['avance'] ?? 0) ?>
    </div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Retenue</small>
    <div class="fw-bold mt-1 text-danger">
        <?= argent($s['retenue'] ?? 0) ?>
    </div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Salaire net</small>
    <div class="fw-bold mt-1 text-primary">
        <?= argent($s['salaire_net'] ?? 0) ?>
    </div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded-3 p-3 h-100">
    <small class="text-muted">Reste à payer</small>
    <div class="fw-bold mt-1">
        <?= argent($resteAPayer) ?>
    </div>
</div>
</div>

</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

<h6 class="fw-bold mb-0">
<i class="bi bi-credit-card"></i>
Paiements effectués
</h6>

<div>
    <?= statutPaiement($s['statut'] ?? 'non_paye') ?>
</div>

</div>

<div class="table-responsive">

<table class="table table-bordered align-middle">

<thead>
<tr>
    <th>Date</th>
    <th>Montant</th>
    <th>Mode</th>
    <th>Référence</th>
</tr>
</thead>

<tbody>

<?php if(count($paiementsSalaire) > 0): ?>

<?php foreach($paiementsSalaire as $ps): ?>

<tr>

<td>
    <?= !empty($ps['date_paiement'])
        ? htmlspecialchars(date('d/m/Y H:i', strtotime($ps['date_paiement'])), ENT_QUOTES, 'UTF-8')
        : '-' ?>
</td>

<td>
    <strong><?= argent($ps['montant'] ?? 0) ?></strong>
</td>

<td>
    <?= htmlspecialchars(
        ucfirst(str_replace('_', ' ', $ps['mode_paiement'] ?? '-')),
        ENT_QUOTES,
        'UTF-8'
    ) ?>
</td>

<td>
    <?= htmlspecialchars($ps['reference_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="4" class="text-center text-muted py-4">
        Aucun paiement effectué pour cette période.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</div>

<div class="modal-footer">

<?php if(!empty($s['fichier_pdf'])): ?>

<a
    href="uploads/fiches_paie/<?= htmlspecialchars($s['fichier_pdf'], ENT_QUOTES, 'UTF-8') ?>"
    target="_blank"
    class="btn btn-dark"
>
    <i class="bi bi-file-earmark-pdf"></i>
    Voir la fiche de paie
</a>

<a
    href="uploads/fiches_paie/<?= htmlspecialchars($s['fichier_pdf'], ENT_QUOTES, 'UTF-8') ?>"
    download
    class="btn btn-success"
>
    <i class="bi bi-download"></i>
    Télécharger
</a>

<?php else: ?>

<span class="text-muted me-auto">
    <i class="bi bi-info-circle"></i>
    La fiche de paie n'a pas encore été générée par le DAF.
</span>

<?php endif; ?>

<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>
    Fermer
</button>

</div>

</div>
</div>
</div>

<?php endforeach; ?>

<footer class="dashboard-footer">

<div class="footer-left">
    <img src="uploads/logo/logo.jpeg" alt="Logo CMAK">
    <span>
        © <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Espace personnel. Tous droits réservés.
    </span>
</div>

<div class="footer-secure">
    <i class="bi bi-shield-check"></i>
    Données financières personnelles et sécurisées
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
</script>

</body>
</html>
