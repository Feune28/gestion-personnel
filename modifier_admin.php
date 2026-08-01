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

$fonctionAdmin = strtoupper(trim($admin['fonction'] ?? ''));

$isDAF = ($fonctionAdmin === 'DAF');

$pageProfil = $isDAF ? 'profil_daf.php' : 'profil_admin.php';
$pageDashboard = $isDAF ? 'dashboard_daf.php' : 'dashboard.php';
$pageNotifications = $isDAF ? 'notifications_daf.php' : 'notifications.php';

$error = "";

if(isset($_POST['modifier'])){

    $nom            = mb_substr(trim($_POST['nom'] ?? ''), 0, 100);
    $prenom         = mb_substr(trim($_POST['prenom'] ?? ''), 0, 100);
    $sexe           = mb_substr(trim($_POST['sexe'] ?? ''), 0, 20);
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $lieu_naissance = mb_substr(trim($_POST['lieu_naissance'] ?? ''), 0, 100);
    $nationalite    = mb_substr(trim($_POST['nationalite'] ?? ''), 0, 50);
    $email          = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telephone      = mb_substr(trim($_POST['telephone'] ?? ''), 0, 30);
    $adresse        = mb_substr(trim($_POST['adresse'] ?? ''), 0, 200);
    $fonction       = mb_substr(trim($_POST['fonction'] ?? ''), 0, 100);
    $matricule      = mb_substr(trim($_POST['matricule'] ?? ''), 0, 50);
    $niveau_acces   = mb_substr(trim($_POST['niveau_acces'] ?? ''), 0, 50);
    $password       = trim($_POST['password'] ?? '');

    if(empty($nom) || empty($prenom) || !$email){
        $error = "Nom, prénom et email sont obligatoires.";
    }else{

        $photo = $admin['photo'];

        if(!empty($_FILES['photo']['name'])){

            $maxPhoto = 2 * 1024 * 1024;
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            $allowedMime = ['image/jpeg','image/png'];
            $allowedExt  = ['jpg','jpeg','png'];

            if($_FILES['photo']['size'] > $maxPhoto){
                $error = "Photo trop lourde (max 2 Mo).";
            }elseif(!in_array($mime, $allowedMime) || !in_array($ext, $allowedExt)){
                $error = "Format image invalide (JPG ou PNG uniquement).";
            }else{

                if(!is_dir('uploads/admins')){
                    mkdir('uploads/admins', 0755, true);
                }

                $photo = uniqid('admin_', true) . '.' . $ext;

                move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    'uploads/admins/' . $photo
                );
            }
        }

        if(!empty($password)){
            if(strlen($password) < 8){
                $error = "Mot de passe trop court (8 caractères minimum).";
            }else{
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }
        }else{
            $passwordHash = $admin['password'];
        }

        if(empty($error)){

            $update = $pdo->prepare("
                UPDATE admins SET
                    nom = ?,
                    prenom = ?,
                    sexe = ?,
                    date_naissance = ?,
                    lieu_naissance = ?,
                    nationalite = ?,
                    email = ?,
                    telephone = ?,
                    adresse = ?,
                    fonction = ?,
                    matricule = ?,
                    niveau_acces = ?,
                    password = ?,
                    photo = ?
                WHERE id = ?
            ");

            $update->execute([
                $nom,
                $prenom,
                $sexe,
                $date_naissance,
                $lieu_naissance,
                $nationalite,
                $email,
                $telephone,
                $adresse,
                $fonction,
                $matricule,
                $niveau_acces,
                $passwordHash,
                $photo,
                $admin['id']
            ]);

            $_SESSION['admin'] = $admin['username'];
            $_SESSION['admin_nom'] = $nom;
            $_SESSION['admin_prenom'] = $prenom;
            $_SESSION['admin_fonction'] = $fonction;

            $fonctionApres = strtoupper(trim($fonction));

            if($fonctionApres === 'DAF'){
                header('Location: profil_daf.php');
                exit();
            }

            header('Location: profil_admin.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Modifier Admin | Gestion Scolaire</title>

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

h1,h2,h3,.page-title,.form-title{
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
.form-card,
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

/* FORM */

.form-card{
    overflow:hidden;
    max-width:980px;
    margin:0 auto;
}

.form-banner{
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

.avatar-img{
    width:112px;
    height:112px;
    border-radius:34px;
    object-fit:cover;
    border:5px solid rgba(255,255,255,.95);
    background:#e5e7eb;
    display:block;
    box-shadow:0 18px 34px rgba(15,23,42,.18);
}

.avatar-badge{
    position:absolute;
    bottom:3px;
    right:3px;
    width:31px;
    height:31px;
    background:linear-gradient(135deg,#f59e0b,#d97706);
    border-radius:12px;
    border:2px solid white;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:12px;
}

.form-body{
    padding:80px 45px 45px;
}

.form-title{
    font-size:20px;
    font-weight:800;
    color:var(--text);
    margin-bottom:4px;
}

.form-subtitle{
    font-size:13px;
    color:var(--muted);
    margin-bottom:28px;
    font-weight:600;
}

.section-label{
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.12em;
    color:var(--muted);
    margin-bottom:14px;
    margin-top:28px;
    display:flex;
    align-items:center;
    gap:8px;
}

.section-label::after{
    content:'';
    flex:1;
    height:1px;
    background:var(--line);
}

.section-label:first-of-type{
    margin-top:0;
}

.field-group{
    margin-bottom:16px;
}

.field-label{
    display:block;
    font-size:11px;
    font-weight:800;
    color:var(--text);
    margin-bottom:6px;
    text-transform:uppercase;
    letter-spacing:.05em;
}

.field-input{
    width:100%;
    padding:12px 15px;
    border:1.5px solid var(--line);
    border-radius:14px;
    font-family:'Inter',sans-serif;
    font-size:14px;
    color:var(--text);
    background:#fff;
    transition:border-color .2s, box-shadow .2s;
    outline:none;
    appearance:none;
}

.field-input:focus{
    border-color:var(--primary);
    background:white;
    box-shadow:0 0 0 4px rgba(37,99,235,.10);
}

.field-input:hover:not(:focus){
    border-color:#cbd5e1;
}

select.field-input{
    cursor:pointer;
}

.field-hint{
    font-size:11px;
    color:var(--muted);
    margin-top:6px;
}

.alert-custom{
    background:#fef2f2;
    border:1px solid #fecaca;
    border-radius:14px;
    padding:13px 16px;
    font-size:13px;
    color:#dc2626;
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:24px;
    font-weight:700;
}

#photoZone{
    display:flex;
    align-items:center;
    gap:14px;
    padding:15px 16px;
    background:white;
    transition:.25s;
    border:1.5px dashed #cbd5e1;
    border-radius:16px;
    cursor:pointer;
}

#photoZone:hover{
    border-color:var(--primary);
    background:#f8fafc;
}

#photoZone img{
    width:46px;
    height:46px;
    border-radius:15px;
    object-fit:cover;
    border:2px solid #fff;
    box-shadow:0 8px 18px rgba(15,23,42,.08);
    flex-shrink:0;
}

#photoZone .zone-text label{
    font-size:13px;
    font-weight:800;
    color:var(--text);
    cursor:pointer;
    margin:0;
    display:block;
}

#photoZone .zone-text small{
    font-size:11px;
    color:var(--muted);
    margin-top:2px;
    display:block;
}

#photoInput{
    display:none;
}

.form-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    padding-top:24px;
    border-top:1px solid var(--line);
    margin-top:12px;
}

.btn-cancel,
.btn-save{
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:none;
    padding:11px 20px;
    border-radius:14px;
    font-family:'Inter',sans-serif;
    font-size:13px;
    font-weight:800;
    text-decoration:none;
    transition:.2s;
}

.btn-cancel{
    background:#f8fafc;
    color:#334155;
    border:1px solid var(--line);
}

.btn-cancel:hover{
    background:#e2e8f0;
    color:#0f172a;
}

.btn-save{
    background:var(--primary);
    color:white;
    cursor:pointer;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.btn-save:hover{
    background:#0f172a;
    transform:translateY(-1px);
    color:white;
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
body.dark-mode .form-card,
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
body.dark-mode .form-title,
body.dark-mode .field-label,
body.dark-mode #photoZone .zone-text label{
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

body.dark-mode .field-input,
body.dark-mode #photoZone{
    background:#111827;
    border-color:#2d3340;
    color:#e5e7eb;
}

body.dark-mode #photoZone:hover{
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

    .form-body{
        padding:80px 24px 32px;
    }

    .avatar-wrap{
        left:24px;
    }

    .form-footer{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-cancel,
    .btn-save{
        justify-content:center;
        width:100%;
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

    <a href="personnel.php" title="Personnel">
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

    <a href="profil_admin.php" class="active" title="Profil">
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

        <a href="<?= htmlspecialchars($pageProfil, ENT_QUOTES, 'UTF-8') ?>" class="back-btn" title="Retour au profil">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h1 class="page-title">
                Modifier le profil
            </h1>

            <div class="page-subtitle">
                Mettez à jour vos informations personnelles
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

<div class="form-card">

<div class="form-banner">

<div class="avatar-wrap">

<img
src="uploads/admins/<?= htmlspecialchars($admin['photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
class="avatar-img"
id="avatarTop"
onerror="this.src='assets/avatar.png'"
alt="Photo"
>

<div class="avatar-badge">
<i class="bi bi-camera-fill"></i>
</div>

</div>

</div>

<div class="form-body">

<div class="form-title">
<?= htmlspecialchars(($admin['nom'] ?? '') . ' ' . ($admin['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="form-subtitle">
<?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
&nbsp;·&nbsp;
<?= htmlspecialchars($admin['fonction'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8') ?>
</div>

<?php if(!empty($error)): ?>

<div class="alert-custom">
<i class="bi bi-exclamation-circle-fill"></i>
<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="section-label">
Informations personnelles
</div>

<div class="row g-3">

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Nom</label>
<input
type="text"
name="nom"
class="field-input"
required
value="<?= htmlspecialchars($admin['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Prénom</label>
<input
type="text"
name="prenom"
class="field-input"
required
value="<?= htmlspecialchars($admin['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Sexe</label>
<select name="sexe" class="field-input">
<option value="">-- Choisir --</option>
<option value="Masculin" <?= ($admin['sexe'] ?? '') === 'Masculin' ? 'selected' : '' ?>>Masculin</option>
<option value="Féminin" <?= ($admin['sexe'] ?? '') === 'Féminin' ? 'selected' : '' ?>>Féminin</option>
</select>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Date de naissance</label>
<input
type="date"
name="date_naissance"
class="field-input"
value="<?= htmlspecialchars($admin['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Lieu de naissance</label>
<input
type="text"
name="lieu_naissance"
class="field-input"
value="<?= htmlspecialchars($admin['lieu_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Nationalité</label>
<input
type="text"
name="nationalite"
class="field-input"
value="<?= htmlspecialchars($admin['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Adresse</label>
<input
type="text"
name="adresse"
class="field-input"
value="<?= htmlspecialchars($admin['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

</div>

<div class="section-label">
Contact &amp; Informations professionnelles
</div>

<div class="row g-3">

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Email</label>
<input
type="email"
name="email"
class="field-input"
required
value="<?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-6">
<div class="field-group">
<label class="field-label">Téléphone</label>
<input
type="text"
name="telephone"
class="field-input"
value="<?= htmlspecialchars($admin['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Fonction</label>
<input
type="text"
name="fonction"
class="field-input"
value="<?= htmlspecialchars($admin['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Matricule</label>
<input
type="text"
name="matricule"
class="field-input"
value="<?= htmlspecialchars($admin['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

<div class="col-md-4">
<div class="field-group">
<label class="field-label">Niveau d'accès</label>
<input
type="text"
name="niveau_acces"
class="field-input"
value="<?= htmlspecialchars($admin['niveau_acces'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
</div>
</div>

</div>

<div class="section-label">
Sécurité
</div>

<div class="row g-3">

<div class="col-md-8">
<div class="field-group">
<label class="field-label">Nouveau mot de passe</label>
<input
type="password"
name="password"
class="field-input"
placeholder="Laisser vide pour conserver l'actuel"
>
<div class="field-hint">
<i class="bi bi-shield-check"></i>
Minimum 8 caractères
</div>
</div>
</div>

</div>

<div class="section-label">
Photo de profil
</div>

<div id="photoZone" onclick="document.getElementById('photoInput').click()">

<img
src="uploads/admins/<?= htmlspecialchars($admin['photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
id="previewImg"
onerror="this.src='assets/avatar.png'"
alt="Aperçu"
>

<div class="zone-text">
<label>Changer la photo</label>
<small>JPG ou PNG — max 2 Mo. Cliquez pour parcourir.</small>
</div>

<i class="bi bi-upload ms-auto" style="color:#9ca3af;font-size:16px;"></i>

</div>

<input
type="file"
name="photo"
id="photoInput"
accept=".jpg,.jpeg,.png"
onchange="handlePhoto(event)"
>

<div class="form-footer">

<a href="<?= htmlspecialchars($pageProfil, ENT_QUOTES, 'UTF-8') ?>" class="btn-cancel">
<i class="bi bi-x"></i>
Annuler
</a>

<button
type="submit"
name="modifier"
class="btn-save"
>
<i class="bi bi-check2"></i>
Enregistrer les modifications
</button>

</div>

</form>

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
function handlePhoto(e){
    const f = e.target.files[0];
    if(!f) return;

    const url = URL.createObjectURL(f);

    document.getElementById('previewImg').src = url;
    document.getElementById('avatarTop').src  = url;
}

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