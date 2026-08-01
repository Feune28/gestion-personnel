<?php

require 'securite_personnel.php';

$personnel_id = (int)$personnel['id'];

$compte = $pdo->prepare("
    SELECT *
    FROM comptes_personnels
    WHERE personnel_id = ?
    LIMIT 1
");
$compte->execute([$personnel_id]);
$comptePersonnel = $compte->fetch(PDO::FETCH_ASSOC);

$totalFiches = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM fiches_paie
    WHERE personnel_id = ?
");
$totalFiches->execute([$personnel_id]);
$fiches = $totalFiches->fetch(PDO::FETCH_ASSOC);

$totalDocs = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM documents_generes
    WHERE personnel_id = ?
");
$totalDocs->execute([$personnel_id]);
$docs = $totalDocs->fetch(PDO::FETCH_ASSOC);

if(strtoupper(trim($personnel['fonction'] ?? '')) === 'PROFESSEUR'){

    $totalPresences = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM presences_professeurs
        WHERE personnel_id = ?
    ");

}else{

    $totalPresences = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM presences
        WHERE personnel_id = ?
    ");

}

$totalPresences->execute([$personnel_id]);
$presences = $totalPresences->fetch(PDO::FETCH_ASSOC);

$emploi = [];

if(strtoupper(trim($personnel['fonction'] ?? '')) === 'PROFESSEUR'){

    $reqEmploi = $pdo->prepare("
        SELECT *
        FROM emplois_temps_professeurs
        WHERE personnel_id = ?
        ORDER BY FIELD(
            jour,
            'Lundi',
            'Mardi',
            'Mercredi',
            'Jeudi',
            'Vendredi',
            'Samedi'
        ),
        heure_debut ASC
        LIMIT 5
    ");

    $reqEmploi->execute([$personnel_id]);
    $emploi = $reqEmploi->fetchAll(PDO::FETCH_ASSOC);

}

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$anneeFooter = $annee['libelle'] ?? date('Y');

$photo = 'uploads/photos/' . ($personnel['photo'] ?? '');

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Espace Personnel | Gestion Scolaire</title>

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

h1,h2,h3,.page-title,.hero-name,.stat-value,.card-title-wow{
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

.dashboard-menu-toggle{
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
}

.dashboard-menu-toggle:hover{
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

/* MAIN */

.main{
    margin-left:310px;
    transition:margin-left .25s ease;
    padding:32px 40px;
    min-height:100vh;
}

.topbar,
.hero-card,
.stat-card,
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

.dark-toggle{
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
}

.dark-toggle:hover{
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
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

/* HERO PERSONNEL */

.hero-card{
    position:relative;
    overflow:hidden;
    padding:26px;
    margin-bottom:26px;
}

.hero-card::before{
    content:'';
    position:absolute;
    right:-40px;
    top:-50px;
    width:190px;
    height:190px;
    border-radius:50%;
    background:var(--primary-soft);
    opacity:.95;
}

.hero-card::after{
    content:'';
    position:absolute;
    left:-60px;
    bottom:-80px;
    width:190px;
    height:190px;
    border-radius:50%;
    background:var(--terracotta-soft);
}

.hero-content{
    position:relative;
    z-index:1;
    display:grid;
    grid-template-columns:145px 1fr;
    gap:24px;
    align-items:center;
}

.profile-photo{
    width:130px;
    height:130px;
    border-radius:34px;
    object-fit:cover;
    border:5px solid rgba(255,255,255,.95);
    box-shadow:0 18px 34px rgba(15,23,42,.15);
}

.hero-name{
    font-size:28px;
    font-weight:800;
    color:var(--text);
    margin:0 0 6px;
}

.hero-id{
    color:var(--muted);
    font-weight:700;
    margin-bottom:14px;
}

.hero-pills{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.hero-pill{
    background:#f8fafc;
    border:1px solid var(--line);
    color:#334155;
    padding:7px 12px;
    border-radius:40px;
    font-size:12px;
    font-weight:800;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
}

.info-mini{
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:13px 15px;
}

.info-label{
    font-size:10px;
    color:var(--muted);
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.06em;
}

.info-value{
    font-size:13px;
    color:var(--text);
    font-weight:800;
    margin-top:4px;
    word-break:break-word;
}

/* STATS */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    margin-bottom:32px;
}

.stat-card{
    position:relative;
    min-height:150px;
    overflow:hidden;
    padding:20px;
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:var(--shadow);
    border-color:rgba(37,99,235,.22);
}

.stat-card::before{
    content:'';
    position:absolute;
    right:-34px;
    top:-38px;
    width:122px;
    height:122px;
    border-radius:50%;
    background:var(--primary-soft);
    opacity:.95;
}

.stat-card.warning::before{
    background:#ffedd5;
}

.stat-card.success::before{
    background:#dcfce7;
}

.stat-icon{
    position:relative;
    z-index:1;
    width:48px;
    height:48px;
    border-radius:17px;
    display:grid;
    place-items:center;
    background:var(--primary-2);
    color:#fff;
    box-shadow:0 12px 22px rgba(37,99,235,.24);
    margin-bottom:18px;
}

.stat-card.warning .stat-icon{
    background:var(--warning);
    box-shadow:0 12px 22px rgba(217,119,6,.22);
}

.stat-card.success .stat-icon{
    background:var(--success);
    box-shadow:0 12px 22px rgba(22,163,74,.22);
}

.stat-icon i{
    font-size:21px;
    color:#fff;
}

.stat-value{
    position:relative;
    z-index:1;
    font-size:34px;
    font-weight:800;
    color:var(--text);
    letter-spacing:-1px;
    line-height:1.05;
}

.stat-label{
    position:relative;
    z-index:1;
    margin-top:8px;
    color:var(--muted);
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
}

/* CONTENT CARDS */

.card-box{
    padding:24px;
    height:100%;
}

.card-title-wow{
    font-size:18px;
    font-weight:800;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--text);
}

.card-title-wow i{
    color:var(--primary);
}

.quick-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.quick-link{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 15px;
    border:1px solid var(--line);
    border-radius:16px;
    text-decoration:none;
    color:var(--text);
    transition:.2s;
    background:#fff;
    font-size:13px;
    font-weight:800;
}

.quick-link i{
    width:34px;
    height:34px;
    border-radius:13px;
    display:grid;
    place-items:center;
    color:#fff;
    background:var(--primary);
    font-size:16px;
}

.quick-link:hover{
    background:var(--primary);
    color:white;
    transform:translateY(-2px);
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.quick-link:hover i{
    background:rgba(255,255,255,.18);
}

.schedule-item{
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:14px 15px;
    margin-bottom:10px;
}

.schedule-day{
    color:var(--primary);
    font-weight:800;
}

.btn-standard{
    text-decoration:none;
    background:var(--primary);
    color:white;
    padding:8px 14px;
    border-radius:14px;
    font-size:12px;
    font-weight:800;
    transition:.2s;
    display:inline-flex;
    align-items:center;
    gap:7px;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.btn-standard:hover{
    background:#0f172a;
    color:white;
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

/* DARK MODE STANDARD */

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
body.dark-mode .hero-card,
body.dark-mode .stat-card,
body.dark-mode .card-box,
body.dark-mode .dashboard-footer,
body.dark-mode .dark-toggle,
body.dark-mode .dashboard-menu-toggle,
body.dark-mode .quick-link,
body.dark-mode .schedule-item,
body.dark-mode .info-mini,
body.dark-mode .hero-pill{
    background:#1a202c;
    border-color:#2d3340;
    color:#f0f2f5;
}

body.dark-mode .page-title,
body.dark-mode .hero-name,
body.dark-mode .stat-value,
body.dark-mode .card-title-wow,
body.dark-mode .info-value{
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

body.dark-mode .dashboard-footer{
    color:var(--muted);
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

    body.sidebar-collapsed .sidebar{width:78px!important;}
    body.sidebar-collapsed .main{margin-left:96px!important;}

    .hero-content{
        grid-template-columns:1fr;
        text-align:center;
    }

    .profile-photo{
        margin:auto;
    }

    .hero-pills{
        justify-content:center;
    }

    .info-grid,
    .stats-grid,
    .quick-grid{
        grid-template-columns:1fr;
    }

    .topbar{
        align-items:flex-start;
        flex-direction:column;
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

    <a href="espace_personnel.php" class="active">
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

</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
    <i class="bi bi-list"></i>
</button>

<div>
    <h1 class="page-title">Tableau de bord personnel</h1>
    <div class="page-subtitle">
        Bienvenue, <?= htmlspecialchars(($personnel['nom'] ?? '') . ' ' . ($personnel['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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

<div class="hero-card">

<div class="hero-content">

<div>

<?php if(!empty($personnel['photo']) && file_exists($photo)): ?>

<img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" class="profile-photo" alt="Photo">

<?php else: ?>

<img src="assets/avatar.png" class="profile-photo" alt="Photo">

<?php endif; ?>

</div>

<div>

<h2 class="hero-name">
<?= htmlspecialchars(($personnel['nom'] ?? '') . ' ' . ($personnel['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</h2>

<div class="hero-id">
Identifiant : <?= htmlspecialchars($comptePersonnel['identifiant'] ?? $personnel['identifiant'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="hero-pills">
    <span class="hero-pill"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($personnel['fonction'] ?? 'Personnel', ENT_QUOTES, 'UTF-8') ?></span>
    <span class="hero-pill"><i class="bi bi-shield-check"></i> Statut : <?= htmlspecialchars($personnel['statut'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
    <span class="hero-pill"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($personnel['annee_scolaire'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
</div>

<div class="info-grid">

<div class="info-mini">
    <div class="info-label">Téléphone</div>
    <div class="info-value"><?= htmlspecialchars($personnel['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
</div>

<div class="info-mini">
    <div class="info-label">Email</div>
    <div class="info-value"><?= htmlspecialchars($personnel['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
</div>

<div class="info-mini">
    <div class="info-label">Contrat</div>
    <div class="info-value"><?= htmlspecialchars($personnel['contrat'] ?? $personnel['numero_contrat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
</div>

</div>

</div>

</div>

</div>

<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-file-earmark-pdf"></i>
</div>
<div class="stat-value"><?= number_format((int)($docs['total'] ?? 0)) ?></div>
<div class="stat-label">Documents générés</div>
</div>

<div class="stat-card warning">
<div class="stat-icon">
<i class="bi bi-receipt"></i>
</div>
<div class="stat-value"><?= number_format((int)($fiches['total'] ?? 0)) ?></div>
<div class="stat-label">Fiches de paie</div>
</div>

<div class="stat-card success">
<div class="stat-icon">
<i class="bi bi-calendar-check"></i>
</div>
<div class="stat-value"><?= number_format((int)($presences['total'] ?? 0)) ?></div>
<div class="stat-label">Présences enregistrées</div>
</div>

</div>

<div class="row">

<div class="col-md-6 mb-4">

<div class="card-box">

<h5 class="card-title-wow">
<i class="bi bi-lightning-charge"></i>
Accès rapides
</h5>

<div class="quick-grid">

<a href="profil_personnel.php" class="quick-link">
<i class="bi bi-person-circle"></i>
Voir mon profil
</a>

<a href="documents_personnel_user.php" class="quick-link">
<i class="bi bi-file-earmark-pdf"></i>
Consulter mes documents
</a>

<a href="paiements_personnel.php" class="quick-link">
<i class="bi bi-receipt"></i>
Consulter mes paiements et fiches
</a>

<a href="demandes_personnel.php" class="quick-link">
<i class="bi bi-send"></i>
Faire une demande
</a>

</div>

</div>

</div>

<div class="col-md-6 mb-4">

<div class="card-box">

<h5 class="card-title-wow">
<i class="bi bi-calendar3"></i>
Aperçu emploi du temps
</h5>

<?php if(($personnel['fonction'] ?? '') === 'PROFESSEUR' && count($emploi) > 0): ?>

<?php foreach($emploi as $e): ?>

<div class="schedule-item">
<strong class="schedule-day"><?= htmlspecialchars($e['jour'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
<br>
<?= htmlspecialchars($e['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?>
-
<?= htmlspecialchars($e['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?>
<br>
<small class="text-muted">
Classe : <?= htmlspecialchars($e['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</small>
</div>

<?php endforeach; ?>

<a href="emploi_personnel.php" class="btn-standard mt-2">
Voir tout l’emploi du temps
</a>

<?php else: ?>

<p class="text-muted mb-0">
Aucun emploi du temps disponible pour ce compte.
</p>

<?php endif; ?>

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
document.addEventListener('DOMContentLoaded', function(){
    const sidebarToggle = document.getElementById('sidebarToggle');
    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = darkToggle ? darkToggle.querySelector('i') : null;

    function setSidebarCollapsed(enabled){
        document.body.classList.toggle('sidebar-collapsed', enabled);
        localStorage.setItem('sidebar-personnel-menu', enabled ? 'enabled' : 'disabled');
    }

    if(localStorage.getItem('sidebar-personnel-menu') === 'enabled'){
        setSidebarCollapsed(true);
    }

    if(sidebarToggle){
        sidebarToggle.addEventListener('click', function(){
            setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
        });
    }

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

    if(darkToggle){
        darkToggle.addEventListener('click', function(){
            setDarkMode(!document.body.classList.contains('dark-mode'));
        });
    }
});
</script>

</body>
</html>