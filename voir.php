<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

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
$notifLink     = $isDAF ? 'notifications_daf.php' : 'notifications.php';

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$id = intval($_GET['id'] ?? 0);

$sql = $pdo->prepare("SELECT * FROM personnels WHERE id=?");
$sql->execute([$id]);
$p = $sql->fetch();

if(!$p){
    die("Personnel introuvable");
}

/*
|--------------------------------------------------------------------------
| COMPTE DE CONNEXION DU PERSONNEL
|--------------------------------------------------------------------------
*/
$compteReq = $pdo->prepare("
    SELECT *
    FROM comptes_personnels
    WHERE personnel_id = ?
    LIMIT 1
");
$compteReq->execute([$id]);
$comptePersonnel = $compteReq->fetch();

$docs = $pdo->prepare("
    SELECT *
    FROM documents_generes
    WHERE personnel_id=?
    ORDER BY id DESC
");
$docs->execute([$id]);
$documents = $docs->fetchAll();

$edt = $pdo->prepare("
    SELECT *
    FROM emplois_temps_professeurs
    WHERE personnel_id=?
    ORDER BY FIELD(jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), heure_debut ASC
");
$edt->execute([$id]);
$emplois = $edt->fetchAll();

$emploi_par_jour = [];

foreach($emplois as $e){
    $emploi_par_jour[$e['jour']][] = $e;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dossier personnel | Gestion Scolaire</title>

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

h1,h2,h3,.page-title,.person-name,.section-title{
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
    background:var(--primary);
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

/* MAIN */

.main{
    margin-left:310px;
    padding:32px 40px;
    min-height:100vh;
    transition:margin-left .25s ease;
}

.topbar,
.card-box,
.day-card,
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
    background:var(--primary-soft);
    border:1px solid rgba(37,99,235,.18);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:var(--primary);
}

.dashboard-menu-toggle,
.dark-toggle,
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
.dark-toggle:hover,
.back-btn:hover{
    background:linear-gradient(135deg,#f59e0b,#d97706);
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

.card-box{
    padding:25px;
    margin-bottom:25px;
}

.profile{
    width:158px;
    height:158px;
    border-radius:42px;
    object-fit:cover;
    border:5px solid rgba(255,255,255,.95);
    box-shadow:0 18px 34px rgba(15,23,42,.15);
}

.person-name{
    font-size:22px;
    font-weight:800;
    color:var(--text);
}

.person-role{
    color:var(--muted);
    font-weight:700;
}

.badge-actif{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#dcfce7;
    color:#166534;
    padding:8px 16px;
    border-radius:30px;
    font-size:13px;
    font-weight:800;
}

.badge-suspendu{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#fee2e2;
    color:#991b1b;
    padding:8px 16px;
    border-radius:30px;
    font-size:13px;
    font-weight:800;
}

.info-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:18px;
    padding:15px 16px;
    margin-bottom:15px;
    height:100%;
    transition:.2s;
}

.info-card:hover{
    background:#f8fafc;
    transform:translateY(-1px);
}

.info-title{
    font-size:11px;
    color:var(--muted);
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
    margin-bottom:6px;
}

.info-value{
    font-size:14px;
    font-weight:800;
    color:var(--text);
    word-break:break-word;
}

.section-title{
    display:flex;
    align-items:center;
    gap:9px;
    font-size:20px;
    font-weight:800;
    margin:28px 0 18px;
    color:var(--text);
}

.section-title i{
    color:var(--primary);
}

.doc-btn{
    width:100%;
    margin-bottom:12px;
    border-radius:14px;
    padding:12px;
    font-weight:800;
}

.day-card{
    overflow:hidden;
    margin-bottom:20px;
}

.day-header{
    background:#f8fafc;
    padding:15px 18px;
    font-weight:800;
    border-bottom:1px solid var(--line);
    color:var(--text);
}

.course-box{
    border-bottom:1px solid var(--line);
    padding:15px 18px;
}

.course-box:last-child{
    border-bottom:none;
}

.course-time{
    font-size:15px;
    font-weight:800;
    color:var(--primary);
}

.course-matiere{
    font-size:15px;
    margin-top:5px;
    font-weight:800;
    color:var(--text);
}

.course-classe{
    font-size:14px;
    color:var(--muted);
    margin-top:3px;
    font-weight:600;
}

.table{
    margin-bottom:0;
}

.table thead th{
    background:#0f172a;
    color:white;
    border:none;
    padding:15px 16px;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.table thead th:first-child{
    border-radius:14px 0 0 14px;
}

.table thead th:last-child{
    border-radius:0 14px 14px 0;
}

.table tbody td{
    padding:16px;
    vertical-align:middle;
    font-size:13px;
    color:#334155;
    border-bottom:1px solid var(--line);
}

.table tbody tr:hover{
    background:#f8fafc;
}

.doc-type{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:7px 12px;
    border-radius:999px;
    background:var(--primary-soft);
    color:var(--primary);
    font-weight:800;
    font-size:12px;
}

.empty-state{
    text-align:center;
    color:#94a3b8;
    padding:20px;
}

.account-card{
    border:1px solid var(--line);
    background:#fff;
    border-radius:20px;
    padding:22px;
    box-shadow:0 8px 24px rgba(15,23,42,.05);
}
.account-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
}
.account-item{
    border:1px solid var(--line);
    background:#f8fafc;
    border-radius:16px;
    padding:14px 15px;
}
.account-label{
    font-size:11px;
    color:var(--muted);
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
    margin-bottom:7px;
}
.account-value{
    font-size:14px;
    font-weight:800;
    color:var(--text);
    word-break:break-word;
}
.password-line{
    display:flex;
    gap:8px;
    align-items:center;
}
.password-line input{
    border:1px solid var(--line);
    background:#fff;
    border-radius:12px;
    padding:9px 11px;
    font-weight:800;
    color:var(--text);
    width:100%;
}
.account-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:14px;
}
.account-btn{
    border:none;
    border-radius:12px;
    padding:9px 12px;
    font-size:12px;
    font-weight:800;
    display:inline-flex;
    gap:7px;
    align-items:center;
    background:var(--primary);
    color:white;
    transition:.2s;
}
.account-btn:hover{
    background:#0f172a;
    color:white;
}
.account-btn.secondary{
    background:#f1f5f9;
    color:#0f172a;
    border:1px solid var(--line);
}
.account-btn.secondary:hover{
    background:#e2e8f0;
}
.account-note{
    margin-top:14px;
    font-size:12px;
    color:var(--muted);
    background:#fffbeb;
    border:1px solid #fde68a;
    padding:11px 13px;
    border-radius:14px;
}
body.dark-mode .account-card,
body.dark-mode .account-item,
body.dark-mode .password-line input{
    background:#111827;
    border-color:#2d3340;
    color:#f0f2f5;
}
body.dark-mode .account-note{
    background:rgba(245,158,11,.10);
    border-color:rgba(245,158,11,.25);
    color:#fbbf24;
}
@media(max-width:900px){
    .account-grid{
        grid-template-columns:1fr;
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
    --line:#243044;
    --primary-soft:rgba(37,99,235,.18);
    background:#0b1120;
}

body.dark-mode .sidebar,
body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .day-card,
body.dark-mode .dashboard-footer,
body.dark-mode .dashboard-menu-toggle,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn{
    background:rgba(26,32,44,.86);
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
body.dark-mode .person-name,
body.dark-mode .info-value,
body.dark-mode .section-title,
body.dark-mode .course-matiere{
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
    background:rgba(255,255,255,.06);
    border-color:#2d3340;
    color:#fbbf24;
}

body.dark-mode .info-card,
body.dark-mode .day-header{
    background:#111827;
    border-color:#2d3340;
}

body.dark-mode .info-card:hover,
body.dark-mode .table tbody tr:hover{
    background:#0b1120;
}

body.dark-mode .course-box,
body.dark-mode .table tbody td{
    border-color:#2d3340;
    color:#e5e7eb;
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
    <img src="uploads/logo/logo.jpeg" alt="Logo">
    <h4><?= $isDAF ? 'ESPACE DAF' : 'GESTION ÉCOLE' ?></h4>
</div>

<div class="menu">

<?php if($isDAF): ?>

<div class="menu-section">
    <div class="menu-title">Principal</div>
    <a href="dashboard_daf.php" title="Tableau de bord DAF">
        <i class="bi bi-speedometer2"></i>
        <span>Tableau de bord</span>
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
</div>

<div class="menu-section">
    <div class="menu-title">Compte</div>
    <a href="notifications_daf.php" title="Notifications">
        <i class="bi bi-bell"></i>
        <span>Notifications</span>
    </a>
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
    <a href="documents.php" title="Documents">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Documents</span>
    </a>
    <a href="suspendus.php" title="Suspendus">
        <i class="bi bi-person-x"></i>
        <span>Suspendus</span>
    </a>
</div>

<div class="menu-section">
    <div class="menu-title">Compte</div>
    <a href="profil_admin.php" title="Profil">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
    <a href="notifications.php" title="Notifications">
        <i class="bi bi-bell"></i>
        <span>Notifications</span>
    </a>
    <a href="logout.php" title="Déconnexion">
        <i class="bi bi-box-arrow-right"></i>
        <span>Déconnexion</span>
    </a>
</div>

<?php endif; ?>

</div>
</div>

<div class="main">

<div class="topbar">
    <div class="top-left">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
            <i class="bi bi-list"></i>
        </button>

        <a href="personnel.php" class="back-btn" title="Retour à la liste du personnel">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h1 class="page-title">Dossier numérique du personnel</h1>
            <div class="page-subtitle">
                Consultation complète du dossier de <?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
    </div>
</div>

<div class="card-box mb-4">

<div class="row align-items-center g-4">

<div class="col-md-3 text-center">

<?php
$photoPath = 'uploads/photos/' . ($p['photo'] ?? '');
?>

<?php if(!empty($p['photo']) && file_exists($photoPath)): ?>

<img src="<?= htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8') ?>" class="profile mb-3" alt="Photo">

<?php else: ?>

<img src="assets/avatar.png" class="profile mb-3" alt="Avatar">

<?php endif; ?>

<h4 class="person-name mb-1">
<?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</h4>

<p class="person-role mb-3">
<?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</p>

<?php if(($p['statut'] ?? '') === 'suspendu'): ?>
<span class="badge-suspendu">
    <i class="bi bi-person-x-fill"></i>
    Suspendu
</span>
<?php else: ?>
<span class="badge-actif">
    <i class="bi bi-person-check-fill"></i>
    <?= htmlspecialchars($p['statut'] ?? 'actif', ENT_QUOTES, 'UTF-8') ?>
</span>
<?php endif; ?>

</div>

<div class="col-md-9">

<div class="row">

<?php
$infos = [
    'Identifiant' => $p['identifiant'] ?? '',
    'Téléphone' => $p['telephone'] ?? '',
    'Téléphone urgence' => $p['telephone_urgence'] ?? '',
    'Email' => $p['email'] ?? '',
    'Nationalité' => $p['nationalite'] ?? '',
    'CNI' => $p['cni'] ?? '',
    'Date naissance' => $p['date_naissance'] ?? '',
    'Lieu naissance' => $p['lieu_naissance'] ?? '',
    'Date embauche' => $p['date_embauche'] ?? '',
    'Niveau étude' => $p['niveau_etude'] ?? '',
    'Diplôme' => $p['diplome'] ?? '',
    'Année scolaire' => $p['annee_scolaire'] ?? ''
];
?>

<?php foreach($infos as $label => $valeur): ?>

<div class="col-md-4">
<div class="info-card">
<div class="info-title"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
<div class="info-value"><?= htmlspecialchars($valeur, ENT_QUOTES, 'UTF-8') ?></div>
</div>
</div>

<?php endforeach; ?>

<?php if(($p['fonction'] ?? '') === 'PROFESSEUR'): ?>

<div class="col-md-6">
<div class="info-card">
<div class="info-title">Matière</div>
<div class="info-value"><?= htmlspecialchars($p['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
</div>
</div>

<div class="col-md-6">
<div class="info-card">
<div class="info-title">Prof principal</div>
<div class="info-value"><?= htmlspecialchars($p['prof_principal'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
</div>
</div>

<?php endif; ?>

</div>
</div>
</div>
</div>

<div class="section-title">
<i class="bi bi-key-fill"></i>
Compte de connexion
</div>

<div class="account-card mb-4">

<?php if($comptePersonnel): ?>

<div class="account-grid">

<div class="account-item">
<div class="account-label">Identifiant de connexion</div>
<div class="account-value" id="loginIdentifiant">
<?= htmlspecialchars($comptePersonnel['identifiant'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</div>
</div>

<div class="account-item">
<div class="account-label">Mot de passe temporaire</div>
<div class="account-value">
<?php if($isDAF): ?>
<span class="text-muted">Réservé au DE</span>
<?php elseif((int)($comptePersonnel['premiere_connexion'] ?? 0) === 1 && !empty($comptePersonnel['password_temporaire'])): ?>
<div class="password-line">
<input type="password" id="tempPassword" value="<?= htmlspecialchars($comptePersonnel['password_temporaire'], ENT_QUOTES, 'UTF-8') ?>" readonly>
<button type="button" class="account-btn secondary" onclick="toggleTempPassword()" title="Afficher / masquer">
<i class="bi bi-eye"></i>
</button>
</div>
<?php else: ?>
<span class="text-muted">Déjà modifié par le personnel</span>
<?php endif; ?>
</div>
</div>

<div class="account-item">
<div class="account-label">Première connexion</div>
<div class="account-value">
<?php if((int)($comptePersonnel['premiere_connexion'] ?? 0) === 1): ?>
<span class="badge bg-warning text-dark">Non effectuée</span>
<?php else: ?>
<span class="badge bg-success">Effectuée</span>
<?php endif; ?>
</div>
</div>

<div class="account-item">
<div class="account-label">Compte</div>
<div class="account-value">
<?php if((int)($comptePersonnel['actif'] ?? 0) === 1): ?>
<span class="badge bg-success">Actif</span>
<?php else: ?>
<span class="badge bg-danger">Inactif</span>
<?php endif; ?>
</div>
</div>

<div class="account-item">
<div class="account-label">Email du compte</div>
<div class="account-value">
<?= htmlspecialchars($comptePersonnel['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</div>
</div>

<div class="account-item">
<div class="account-label">Email vérifié</div>
<div class="account-value">
<?php if((int)($comptePersonnel['email_verifie'] ?? 0) === 1): ?>
<span class="badge bg-success">Oui</span>
<?php else: ?>
<span class="badge bg-secondary">Non</span>
<?php endif; ?>
</div>
</div>

</div>

<?php if(!$isDAF): ?>
<div class="account-actions">
<button type="button" class="account-btn" onclick="copyText('loginIdentifiant')">
<i class="bi bi-clipboard"></i>
Copier l'identifiant
</button>

<?php if((int)($comptePersonnel['premiere_connexion'] ?? 0) === 1 && !empty($comptePersonnel['password_temporaire'])): ?>
<button type="button" class="account-btn secondary" onclick="copyPassword()">
<i class="bi bi-clipboard-check"></i>
Copier le mot de passe temporaire
</button>
<?php endif; ?>
</div>

<div class="account-note">
<i class="bi bi-shield-lock"></i>
Le mot de passe temporaire reste visible uniquement tant que le personnel n'a pas encore changé son mot de passe à la première connexion.
</div>
<?php endif; ?>

<?php else: ?>

<div class="empty-state">
<i class="bi bi-exclamation-circle" style="font-size:28px;"></i>
<p class="mt-2 mb-0">Aucun compte de connexion n'est encore associé à ce personnel.</p>
</div>

<?php endif; ?>

</div>

<?php if(count($emplois) > 0): ?>

<div class="section-title">
<i class="bi bi-calendar3"></i>
Emploi du temps
</div>

<div class="row">

<?php foreach($emploi_par_jour as $jour => $cours): ?>

<div class="col-md-6">
<div class="day-card">

<div class="day-header">
<?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php foreach($cours as $c): ?>

<div class="course-box">

<div class="course-time">
<?= htmlspecialchars($c['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="course-matiere">
<?= htmlspecialchars($c['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="course-classe">
Classe : <?= htmlspecialchars($c['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</div>

</div>

<?php endforeach; ?>

</div>
</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<div class="row mt-4">

<div class="col-md-6">

<div class="card-box">

<h5 class="section-title mt-0 mb-4">
<i class="bi bi-folder2-open"></i>
Documents personnels
</h5>

<?php if(!empty($p['contrat_pdf'])): ?>
<a href="uploads/contrats/<?= htmlspecialchars($p['contrat_pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-primary doc-btn">
<i class="bi bi-file-earmark-pdf"></i>
Voir contrat
</a>
<?php endif; ?>

<?php if(!empty($p['diplome_pdf'])): ?>
<a href="uploads/diplomes/<?= htmlspecialchars($p['diplome_pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-success doc-btn">
<i class="bi bi-file-earmark-pdf"></i>
Voir diplôme
</a>
<?php endif; ?>

<?php if(!empty($p['cv'])): ?>
<a href="uploads/cv/<?= htmlspecialchars($p['cv'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-dark doc-btn">
<i class="bi bi-file-earmark-person"></i>
Voir CV
</a>
<?php endif; ?>

<?php if(!empty($p['lettre_motivation'])): ?>
<a href="uploads/lettres/<?= htmlspecialchars($p['lettre_motivation'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-secondary doc-btn">
<i class="bi bi-file-earmark-text"></i>
Lettre motivation
</a>
<?php endif; ?>

<?php if(empty($p['contrat_pdf']) && empty($p['diplome_pdf']) && empty($p['cv']) && empty($p['lettre_motivation'])): ?>
<div class="empty-state">
    <i class="bi bi-inbox" style="font-size:28px;"></i>
    <p class="mt-2 mb-0">Aucun document personnel disponible.</p>
</div>
<?php endif; ?>

</div>
</div>

<div class="col-md-6">

<div class="card-box">

<h5 class="section-title mt-0 mb-4">
<i class="bi bi-tools"></i>
Actions administratives
</h5>

<?php if(!$isDAF): ?>

<a href="generate_absence.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-warning doc-btn">
<i class="bi bi-calendar-x"></i>
Autorisation absence
</a>

<a href="generate_stage.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-success doc-btn">
<i class="bi bi-award"></i>
Certificat stage
</a>

<a href="generate_attestation.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-dark doc-btn">
<i class="bi bi-file-earmark-text"></i>
Générer attestation
</a>

<a href="suspendre.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-danger doc-btn">
<i class="bi bi-person-x"></i>
Suspendre personnel
</a>

<?php else: ?>

<div class="alert alert-info mb-0">
<i class="bi bi-info-circle"></i>
Le DAF peut consulter le dossier du personnel, mais il ne dispose pas des actions administratives.
</div>

<?php endif; ?>

</div>
</div>
</div>

<div class="row mt-4">

<div class="col-md-12">

<div class="card-box">

<h5 class="section-title mt-0 mb-4">
<i class="bi bi-clock-history"></i>
Historique des documents générés
</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Type document</th>
<th>Date génération</th>
<th>Document</th>
</tr>
</thead>

<tbody>

<?php if(count($documents) > 0): ?>

<?php foreach($documents as $doc): ?>

<tr>

<td>
<span class="doc-type">
    <i class="bi bi-file-earmark-pdf"></i>
    <?= htmlspecialchars($doc['type_document'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</span>
</td>

<td>
<?= htmlspecialchars($doc['date_generation'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<a href="<?= htmlspecialchars($doc['fichier'] ?? '', ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
<i class="bi bi-file-earmark-pdf"></i>
Voir PDF
</a>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="3" class="text-center text-muted py-4">
Aucun document généré
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</div>
</div>
</div>

<footer class="dashboard-footer">

    <div class="footer-left">

        <img
        src="uploads/logo/logo.jpeg"
        alt="Logo CMAK"
        >

        <span>
            © <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion du personnel scolaire. Tous droits réservés.
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

function setDarkMode(enabled){
    document.body.classList.toggle('dark-mode', enabled);

    if(darkIcon){
        darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('dark-mode') === 'enabled'){
    setDarkMode(true);
}

darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});

function toggleTempPassword(){
    const input = document.getElementById('tempPassword');
    if(!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}

function copyText(elementId){
    const el = document.getElementById(elementId);
    if(!el) return;
    navigator.clipboard.writeText(el.innerText.trim());
    alert('Identifiant copié.');
}

function copyPassword(){
    const input = document.getElementById('tempPassword');
    if(!input) return;
    navigator.clipboard.writeText(input.value);
    alert('Mot de passe temporaire copié.');
}
</script>

</body>
</html>