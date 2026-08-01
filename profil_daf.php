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
require 'securite_daf.php';

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

$admin_id = $_SESSION['admin_id'] ?? 0;

$sql = $pdo->prepare("
    SELECT *
    FROM admins
    WHERE id = ?
    LIMIT 1
");

$sql->execute([$admin_id]);
$admin = $sql->fetch();

if(!$admin){
    header('Location: login.php');
    exit();
}

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil DAF | Gestion Scolaire</title>

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

h1,h2,h3,.page-title,.profile-header-left h2{
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
.profile-card,
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

/* PROFILE */

.profile-card{
    overflow:hidden;
}

.profile-banner{
    height:120px;
    background:
        radial-gradient(circle at top left, rgba(245,158,11,.55), transparent 32%),
        linear-gradient(135deg,#0f172a,#1d4ed8);
    position:relative;
}

.avatar-wrap{
    position:absolute;
    bottom:-56px;
    left:40px;
}

.avatar-wrap img{
    width:112px;
    height:112px;
    border-radius:34px;
    object-fit:cover;
    border:5px solid rgba(255,255,255,.95);
    display:block;
    background:#e5e7eb;
    box-shadow:0 18px 34px rgba(15,23,42,.18);
}

.profile-header{
    padding:72px 40px 28px 40px;
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:18px;
    border-bottom:1px solid var(--line);
}

.profile-header-left h2{
    font-size:22px;
    font-weight:800;
    margin:0 0 5px 0;
    color:var(--text);
}

.profile-header-left span{
    font-size:13px;
    color:var(--muted);
    font-weight:600;
}

.edit-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--primary);
    color:white;
    border:none;
    padding:11px 18px;
    border-radius:14px;
    font-family:'Inter',sans-serif;
    font-size:13px;
    font-weight:800;
    text-decoration:none;
    transition:.2s;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.edit-btn:hover{
    background:#0f172a;
    color:white;
    transform:translateY(-1px);
}

.profile-body{
    padding:30px 40px 36px;
}

.section-label{
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.12em;
    color:var(--muted);
    margin-bottom:16px;
}

.fields-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:0;
    border:1px solid var(--line);
    border-radius:18px;
    overflow:hidden;
    background:#fff;
}

.field-item{
    padding:15px 20px;
    border-bottom:1px solid var(--line);
    border-right:1px solid var(--line);
    transition:background .15s;
}

.field-item:hover{
    background:#f8fafc;
}

.field-item:nth-child(2n){
    border-right:none;
}

.field-item:nth-last-child(-n+2){
    border-bottom:none;
}

.field-key{
    font-size:11px;
    font-weight:800;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.06em;
    margin-bottom:4px;
}

.field-val{
    font-size:14px;
    font-weight:700;
    color:var(--text);
}

.field-val.muted{
    color:var(--muted);
    font-weight:500;
}

.divider{
    height:1px;
    background:var(--line);
    margin:28px 0;
}

.pwd-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:16px 20px;
    background:#f8fafc;
    border:1px solid var(--line);
    border-radius:18px;
}

.pwd-row-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.pwd-icon{
    width:40px;
    height:40px;
    background:linear-gradient(135deg,#f59e0b,#d97706);
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:15px;
    flex-shrink:0;
}

.pwd-label{
    font-size:11px;
    font-weight:800;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.06em;
    margin-bottom:2px;
}

.pwd-dots{
    font-size:18px;
    color:var(--text);
    letter-spacing:3px;
    line-height:1;
}

.pwd-change{
    font-size:13px;
    color:var(--primary);
    text-decoration:none;
    font-weight:800;
    transition:color .2s;
}

.pwd-change:hover{
    color:#0f172a;
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
body.dark-mode .profile-card,
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
body.dark-mode .profile-header-left h2,
body.dark-mode .field-val,
body.dark-mode .pwd-dots{
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

body.dark-mode .fields-grid,
body.dark-mode .field-item,
body.dark-mode .pwd-row{
    background:#111827;
    border-color:#2d3340;
}

body.dark-mode .field-item:hover{
    background:#0b1120;
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

    .profile-header{
        flex-direction:column;
        align-items:flex-start;
        padding-left:24px;
        padding-right:24px;
    }

    .avatar-wrap{
        left:24px;
    }

    .profile-body{
        padding:28px 24px 32px;
    }

    .fields-grid{
        grid-template-columns:1fr;
    }

    .field-item,
    .field-item:nth-child(2n){
        border-right:none;
        border-bottom:1px solid var(--line);
    }

    .field-item:last-child{
        border-bottom:none;
    }

    .pwd-row{
        flex-direction:column;
        align-items:flex-start;
    }

    .dashboard-footer{
        flex-direction:column;
        align-items:flex-start;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h4>ESPACE DAF</h4>
    </div>

    <div class="menu">

        <div class="menu-section">
            <div class="menu-title">Principal</div>

            <a href="dashboard_daf.php" title="Tableau de bord DAF">
                <i class="bi bi-speedometer2"></i>
                <span>Tableau de bord</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Gestion financière</div>

            <a href="personnel.php" title="Personnel">
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

            <a href="profil_daf.php" class="active" title="Profil">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
            </a>

            <a href="logout.php" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </div>

    </div></div>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="top-left">
            <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
                <i class="bi bi-list"></i>
            </button>

            <a href="dashboard_daf.php" class="back-btn" title="Retour au tableau de bord DAF">
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>
                <h1 class="page-title">Profil DAF</h1>
                <div class="page-subtitle">Informations du Directeur des Affaires Financières</div>
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

    <!-- CARTE PROFIL -->
    <div class="profile-card">

        <div class="profile-banner">
            <div class="avatar-wrap">
                <img
                    src="uploads/admins/<?= htmlspecialchars($admin['photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    alt="Photo DAF"
                    onerror="this.src='assets/avatar.png'"
                >
            </div>
        </div>

        <div class="profile-header">
            <div class="profile-header-left">
                <h2>
                    <?= htmlspecialchars(($admin['nom'] ?? '') . ' ' . ($admin['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <span>
                    <?= htmlspecialchars($admin['fonction'] ?? 'DAF', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <a href="modifier_admin.php?id=<?= (int)$admin['id'] ?>" class="edit-btn">
                <i class="bi bi-pencil-square"></i>
                Modifier le profil
            </a>
        </div>

        <div class="profile-body">

            <div class="section-label">Informations personnelles</div>

            <div class="fields-grid">
                <div class="field-item">
                    <div class="field-key">Nom</div>
                    <div class="field-val"><?= htmlspecialchars($admin['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Prénom</div>
                    <div class="field-val"><?= htmlspecialchars($admin['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Sexe</div>
                    <div class="field-val"><?= htmlspecialchars($admin['sexe'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Date de naissance</div>
                    <div class="field-val"><?= htmlspecialchars($admin['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Lieu de naissance</div>
                    <div class="field-val"><?= htmlspecialchars($admin['lieu_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Nationalité</div>
                    <div class="field-val"><?= htmlspecialchars($admin['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-label">Contact &amp; Informations professionnelles</div>

            <div class="fields-grid">
                <div class="field-item">
                    <div class="field-key">Email</div>
                    <div class="field-val"><?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Téléphone</div>
                    <div class="field-val"><?= htmlspecialchars($admin['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Adresse</div>
                    <div class="field-val"><?= htmlspecialchars($admin['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Fonction</div>
                    <div class="field-val"><?= htmlspecialchars($admin['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Matricule</div>
                    <div class="field-val"><?= htmlspecialchars($admin['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="field-item">
                    <div class="field-key">Niveau d'accès</div>
                    <div class="field-val"><?= htmlspecialchars($admin['niveau_acces'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-label">Sécurité</div>

            <div class="pwd-row">
                <div class="pwd-row-left">
                    <div class="pwd-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>

                    <div>
                        <div class="pwd-label">Mot de passe</div>
                        <div class="pwd-dots">••••••••••••</div>
                    </div>
                </div>

                <a href="modifier_admin.php?id=<?= (int)$admin['id'] ?>" class="pwd-change">
                    Changer <i class="bi bi-arrow-right"></i>
                </a>
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

</script>

</body>
</html>