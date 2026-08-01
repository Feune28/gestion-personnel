<?php
session_start();
require 'connexion.php';

if(!isset($_SESSION['personnel'])){
    header('Location: login_personnel.php');
    exit();
}

$personnel_id = (int) $_SESSION['personnel'];

$sql = $pdo->prepare("SELECT * FROM personnels WHERE id = ? LIMIT 1");
$sql->execute([$personnel_id]);
$personnel = $sql->fetch();

if(!$personnel){
    session_destroy();
    header('Location: login_personnel.php');
    exit();
}

$fonction = strtoupper(trim($personnel['fonction'] ?? ''));

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$photo = 'uploads/photos/' . ($personnel['photo'] ?? '');

$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];

$heures = [
    '08H-09H',
    '09H-10H',
    '10H-11H',
    '11H-12H',
    '14H-15H',
    '15H-16H',
    '16H-17H',
    '17H-18H'
];

$planning = [];

foreach($heures as $heure){
    foreach($jours as $jour){
        $planning[$heure][$jour] = null;
    }
}

$emplois = [];

if($fonction === 'PROFESSEUR'){

    $req = $pdo->prepare("
        SELECT *
        FROM emplois_temps_professeurs
        WHERE personnel_id = ?
        ORDER BY FIELD(jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), heure_debut ASC
    ");

    $req->execute([$personnel_id]);
    $emplois = $req->fetchAll();

    foreach($emplois as $e){
        $planning[$e['plage_horaire']][$e['jour']] = $e;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon emploi du temps | Espace Personnel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap"
rel="stylesheet"
>

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

h1,h2,h3,.page-title,.profile-name,.section-title{
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
    transition:margin-left .25s ease;
    padding:32px 40px;
    min-height:100vh;
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
    margin-bottom:32px;
    flex-wrap:wrap;
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
    width:40px;
    height:40px;
    border-radius:40px;
    background:#fff;
    border:1px solid var(--line);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:all .2s;
    color:#4b5563;
    box-shadow:0 5px 14px rgba(15,23,42,.04);
    text-decoration:none;
}

.sidebar-toggle:hover,
.dark-toggle:hover,
.back-btn:hover{
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

.page-title{
    font-size:27px;
    font-weight:800;
    color:var(--text);
    margin:0 0 4px;
    letter-spacing:-.3px;
}

.page-subtitle{
    color:var(--muted);
    font-size:13px;
    margin:0;
}

.header-actions{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.year-badge{
    background:var(--primary-soft);
    border:1px solid rgba(37,99,235,.18);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:var(--primary);
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

/* CARD BOX */

.card-box{
    padding:28px 30px;
    margin-bottom:28px;
}

.section-title{
    font-size:18px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:18px;
    color:var(--text);
}

.section-title i{
    color:var(--primary);
}

/* EDT TABLE */

.table-responsive{
    border-radius:16px;
    overflow:hidden;
    border:1px solid var(--line);
}

.edt-table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

.edt-table th{
    background:#111827;
    color:#fff;
    text-align:center;
    padding:14px;
    border:1px solid #1f2937;
    font-family:'Sora',sans-serif;
    font-weight:700;
    font-size:13px;
    letter-spacing:.02em;
}

.edt-table td{
    height:115px;
    min-width:150px;
    border:1px solid var(--line);
    padding:10px;
    vertical-align:top;
    background:#fff;
}

.edt-table .hour{
    background:var(--primary-soft);
    font-weight:800;
    text-align:center;
    vertical-align:middle;
    color:var(--primary);
    font-size:13px;
}

.course{
    background:var(--terracotta-soft);
    border:1px solid var(--terracotta-border);
    border-radius:14px;
    padding:12px;
    height:100%;
    transition:.2s;
}

.course:hover{
    transform:translateY(-2px);
    box-shadow:var(--shadow-sm);
}

.course strong{
    color:var(--terracotta);
    font-size:13.5px;
    font-family:'Sora',sans-serif;
}

.course small{
    color:var(--muted);
    display:block;
    margin-top:4px;
    font-weight:600;
}

.empty{
    color:#cbd5e1;
    text-align:center;
    font-size:13px;
    margin-top:38px;
    font-weight:700;
}

.alert-custom{
    border-radius:18px;
    padding:18px 20px;
    font-weight:600;
    font-size:14px;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-custom.info{
    background:var(--primary-soft);
    border:1px solid rgba(37,99,235,.18);
    color:var(--primary);
}

.alert-custom.warning{
    background:#fef3c7;
    border:1px solid #fde68a;
    color:var(--warning);
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

body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .dashboard-footer,
body.dark-mode .dark-toggle,
body.dark-mode .sidebar-toggle,
body.dark-mode .back-btn{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .page-title,
body.dark-mode .section-title,
body.dark-mode .edt-table .hour{
    color:#f0f2f5;
}

body.dark-mode .badge-role{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}

body.dark-mode .year-badge{
    background:rgba(37,99,235,.18);
    border-color:#2d3340;
}

body.dark-mode .edt-table td{
    background:#1a202c;
    border-color:#2d3340;
}

body.dark-mode .edt-table th{
    background:#0b1120;
    border-color:#2d3340;
}

body.dark-mode .edt-table .hour{
    background:rgba(37,99,235,.18);
}

body.dark-mode .course{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
}

body.dark-mode .course small{
    color:#94a3b8;
}

body.dark-mode .empty{
    color:#475569;
}

body.dark-mode .alert-custom.info{
    background:rgba(37,99,235,.18);
    border-color:#2d3340;
}

body.dark-mode .alert-custom.warning{
    background:rgba(217,119,6,.15);
    border-color:#2d3340;
    color:#fbbf24;
}

body.dark-mode .sidebar{
    border-color:rgba(255,255,255,.78)!important;
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
        align-items:flex-start;
        flex-direction:column;
    }

    .card-box{
        padding:22px 20px;
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

    <a href="fiches_paie_personnel.php">
        <i class="bi bi-receipt"></i>
        <span>Mes fiches de paie</span>
    </a>

    <a href="paiements_personnel.php">
        <i class="bi bi-cash-stack"></i>
        <span>Mes paiements</span>
    </a>

    <a href="presences_personnel.php">
        <i class="bi bi-calendar-check"></i>
        <span>Mes présences</span>
    </a>

    <?php if($fonction === 'PROFESSEUR'): ?>
    <a href="emploi_personnel.php" class="active">
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
    <h1 class="page-title">Mon emploi du temps</h1>
    <div class="page-subtitle">
        <?= htmlspecialchars(($personnel['nom'] ?? '') . ' ' . ($personnel['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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

<?php if($fonction !== 'PROFESSEUR'): ?>

<div class="card-box">
    <div class="alert-custom info">
        <i class="bi bi-info-circle"></i>
        Aucun emploi du temps disponible pour votre fonction.
    </div>
</div>

<?php else: ?>

<div class="card-box">

<h5 class="section-title">
<i class="bi bi-calendar-week"></i>
Emploi du temps hebdomadaire
</h5>

<?php if(count($emplois) > 0): ?>

<div class="table-responsive">

<table class="edt-table">

<thead>
<tr>
<th>Heures</th>
<?php foreach($jours as $jour): ?>
<th><?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?></th>
<?php endforeach; ?>
</tr>
</thead>

<tbody>

<?php foreach($heures as $heure): ?>

<tr>

<td class="hour">
<?= htmlspecialchars($heure, ENT_QUOTES, 'UTF-8') ?>
</td>

<?php foreach($jours as $jour): ?>

<td>

<?php if(!empty($planning[$heure][$jour])): ?>

<div class="course">
    <strong>
        <?= htmlspecialchars($planning[$heure][$jour]['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </strong>
    <br>
    <small>
        Classe :
        <?= htmlspecialchars($planning[$heure][$jour]['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </small>
</div>

<?php else: ?>

<div class="empty">Libre</div>

<?php endif; ?>

</td>

<?php endforeach; ?>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php else: ?>

<div class="alert-custom warning">
    <i class="bi bi-exclamation-triangle"></i>
    Aucun emploi du temps enregistré pour vous.
</div>

<?php endif; ?>

</div>

<?php endif; ?>

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
</script>

</body>
</html>