<?php

session_start();

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

$message = "";
$error = "";

$date = $_GET['date'] ?? date('Y-m-d');
$fonction = $_GET['fonction'] ?? '';
$type = $_GET['type'] ?? 'tous';

$joursFr = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

$jourSemaine = $joursFr[date('l', strtotime($date))] ?? '';

if(isset($_POST['modifier_presence'])){

    $source = $_POST['source'] ?? '';
    $presence_id = intval($_POST['presence_id'] ?? 0);
    $personnel_id = intval($_POST['personnel_id'] ?? 0);
    $plage_horaire = trim($_POST['plage_horaire'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    $motif = trim($_POST['motif'] ?? '');

    if(empty($statut) || empty($motif) || $personnel_id <= 0){
        $error = "Statut et motif obligatoires.";
    }else{

        if($source === 'personnel'){

            if($presence_id > 0){

                $update = $pdo->prepare("
                    UPDATE presences
                    SET statut = ?,
                        motif = ?,
                        modifie_par = ?,
                        date_modification = NOW()
                    WHERE id = ?
                ");

                $update->execute([
                    $statut,
                    $motif,
                    $_SESSION['admin'],
                    $presence_id
                ]);

            }else{

                $insert = $pdo->prepare("
                    INSERT INTO presences(
                        personnel_id,
                        date_presence,
                        statut,
                        motif,
                        modifie_par,
                        date_modification
                    )
                    VALUES(?,?,?,?,?,NOW())
                ");

                $insert->execute([
                    $personnel_id,
                    $date,
                    $statut,
                    $motif,
                    $_SESSION['admin']
                ]);
            }

            $message = "Présence enregistrée avec succès.";

        }elseif($source === 'professeur'){

            if($presence_id > 0){

                $update = $pdo->prepare("
                    UPDATE presences_professeurs
                    SET statut = ?,
                        motif = ?,
                        modifie_par = ?,
                        date_modification = NOW()
                    WHERE id = ?
                ");

                $update->execute([
                    $statut,
                    $motif,
                    $_SESSION['admin'],
                    $presence_id
                ]);

            }else{

                $insert = $pdo->prepare("
                    INSERT INTO presences_professeurs(
                        personnel_id,
                        date_presence,
                        plage_horaire,
                        statut,
                        motif,
                        modifie_par,
                        date_modification
                    )
                    VALUES(?,?,?,?,?,?,NOW())
                ");

                $insert->execute([
                    $personnel_id,
                    $date,
                    $plage_horaire,
                    $statut,
                    $motif,
                    $_SESSION['admin']
                ]);
            }

            $message = "Présence professeur enregistrée avec succès.";
        }
    }
}

$whereFonction = "";
$paramsFonction = [];

if($fonction !== ''){
    $whereFonction = " AND p.fonction = ? ";
    $paramsFonction[] = $fonction;
}

$personnels = [];

if($type === 'tous' || $type === 'personnel'){

    $sqlPersonnel = "
        SELECT
            p.id AS personnel_id,
            p.nom,
            p.prenom,
            p.fonction,
            p.matricule,
            p.photo,
            pr.id AS presence_id,
            pr.date_presence,
            pr.heure_arrivee,
            pr.heure_depart,
            pr.statut,
            pr.motif,
            pr.modifie_par,
            pr.date_modification
        FROM personnels p
        LEFT JOIN presences pr
            ON pr.personnel_id = p.id
            AND pr.date_presence = ?
        WHERE p.statut = 'actif'
        AND p.fonction <> 'PROFESSEUR'
        $whereFonction
        ORDER BY p.nom ASC, p.prenom ASC
    ";

    $reqPersonnel = $pdo->prepare($sqlPersonnel);
    $reqPersonnel->execute(array_merge([$date], $paramsFonction));
    $personnels = $reqPersonnel->fetchAll();
}

$profs = [];

if($type === 'tous' || $type === 'professeur'){

    $sqlProfs = "
        SELECT
            p.id AS personnel_id,
            p.nom,
            p.prenom,
            p.fonction,
            p.matricule,
            p.photo,
            e.plage_horaire,
            e.matiere,
            e.classe,
            pp.id AS presence_id,
            pp.heure_scan,
            pp.statut,
            pp.motif,
            pp.modifie_par,
            pp.date_modification
        FROM emplois_temps_professeurs e
        INNER JOIN personnels p ON p.id = e.personnel_id
        LEFT JOIN presences_professeurs pp
            ON pp.personnel_id = e.personnel_id
            AND pp.date_presence = ?
            AND pp.plage_horaire = e.plage_horaire
        WHERE p.statut = 'actif'
        AND p.fonction = 'PROFESSEUR'
        AND e.jour = ?
        $whereFonction
        ORDER BY e.plage_horaire ASC, p.nom ASC
    ";

    $reqProfs = $pdo->prepare($sqlProfs);
    $reqProfs->execute(array_merge([$date, $jourSemaine], $paramsFonction));
    $profs = $reqProfs->fetchAll();
}

$fonctions = $pdo->query("
    SELECT DISTINCT fonction
    FROM personnels
    WHERE fonction IS NOT NULL
    AND fonction != ''
    ORDER BY fonction ASC
")->fetchAll();

function badgeStatut($statut){
    $s = strtolower(trim($statut ?? ''));

    if($s === 'présent' || $s === 'present'){
        return '<span class="badge bg-success">Présent</span>';
    }

    if($s === 'retard'){
        return '<span class="badge bg-warning text-dark">Retard</span>';
    }

    if($s === 'justifie' || $s === 'justifié'){
        return '<span class="badge bg-info text-dark">Justifié</span>';
    }

    return '<span class="badge bg-danger">Absent</span>';
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Présences</title>
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

.card-box{
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    padding:24px;
    margin-bottom:25px;
    box-shadow:var(--shadow-sm);
}
.btn-main{
    background:var(--primary);
    color:white;
    border:none;
    border-radius:14px;
    padding:10px 18px;
    font-weight:700;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}
.btn-main:hover{background:#0f172a;color:white;}
.modal-content{border-radius:22px;border:1px solid var(--line);box-shadow:var(--shadow);overflow:hidden;}
.modal-header{border-bottom:1px solid var(--line);background:#f8fafc;}
.modal-footer{border-top:1px solid var(--line);}
.card-box h5{font-family:'Sora',sans-serif;color:var(--text);}
.alert{border-radius:18px;border:1px solid var(--line);box-shadow:var(--shadow-sm);}
body.dark-mode .card-box,
body.dark-mode .modal-content,
body.dark-mode .modal-header,
body.dark-mode .modal-footer{
    background:rgba(26,32,44,.86);
    border-color:#2d3340;
    color:#f0f2f5;
}
body.dark-mode .table tbody td{color:#cbd5e1;}

</style>
</head>

<body>
<!-- BARRE DE MENU UX -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h4>GESTION SCOLAIRE</h4>
    </div>

    <div class="menu">
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
            <a href="presences.php" class="active" title="Présence">
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
    </div>
</div>

<main class="main-content">


<div class="topbar">

    <div class="top-left page-title-with-toggle">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
            <i class="bi bi-list"></i>
        </button>

        <div>
            <h1 class="page-title">Contrôle des présences</h1>
            <div class="page-subtitle">
                Présences du <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($jourSemaine, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>

    <div class="header-actions">
        <a href="dashboard.php" class="back-btn" title="Retour au tableau de bord">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="year-badge">
            <i class="bi bi-calendar-check"></i>
            <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="dark-toggle" id="darkToggle" title="Mode sombre">
            <i class="bi bi-moon-fill"></i>
        </div>
    </div>

</div>

<?php if(!empty($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card-box shadow-sm">

<form method="GET">
<div class="row g-3">

<div class="col-md-3">
<label class="form-label">Date</label>
<input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="col-md-3">
<label class="form-label">Fonction</label>
<select name="fonction" class="form-select">
<option value="">Toutes</option>
<?php foreach($fonctions as $f): ?>
<option value="<?= htmlspecialchars($f['fonction'], ENT_QUOTES, 'UTF-8') ?>" <?= $fonction === $f['fonction'] ? 'selected' : '' ?>>
<?= htmlspecialchars($f['fonction'], ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label class="form-label">Type</label>
<select name="type" class="form-select">
<option value="tous" <?= $type === 'tous' ? 'selected' : '' ?>>Tous</option>
<option value="personnel" <?= $type === 'personnel' ? 'selected' : '' ?>>Personnel simple</option>
<option value="professeur" <?= $type === 'professeur' ? 'selected' : '' ?>>Professeurs</option>
</select>
</div>

<div class="col-md-3 d-flex align-items-end">
<button class="btn btn-main w-100">
<i class="bi bi-search"></i>
Filtrer
</button>
</div>

</div>
</form>

</div>

<?php if($type === 'tous' || $type === 'personnel'): ?>

<div class="card-box shadow-sm">
<h5 class="fw-bold mb-3">Personnel administratif / non professeur</h5>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Arrivée</th>
<th>Départ</th>
<th>Statut</th>
<th>Motif</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php if(count($personnels) > 0): ?>
<?php foreach($personnels as $p): ?>

<?php
$photo = 'uploads/photos/' . ($p['photo'] ?? '');
$statutAffiche = $p['presence_id'] ? ($p['statut'] ?? 'Présent') : 'Absent';
?>

<tr>
<td>
<div class="d-flex align-items-center gap-2">
<?php if(!empty($p['photo']) && file_exists($photo)): ?>
<img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" class="photo">
<?php else: ?>
<img src="assets/avatar.png" class="photo">
<?php endif; ?>
<div>
<strong><?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
<small class="text-muted"><?= htmlspecialchars($p['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
</div>
</div>
</td>

<td><?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($p['heure_arrivee'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($p['heure_depart'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= badgeStatut($statutAffiche) ?></td>
<td><?= htmlspecialchars($p['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>

<td>
<button
class="btn btn-sm btn-warning"
data-bs-toggle="modal"
data-bs-target="#modalModifier"
data-source="personnel"
data-id="<?= (int)($p['presence_id'] ?? 0) ?>"
data-personnel="<?= (int)$p['personnel_id'] ?>"
data-plage=""
data-statut="<?= htmlspecialchars($statutAffiche, ENT_QUOTES, 'UTF-8') ?>"
>
<i class="bi bi-pencil-square"></i>
Gérer
</button>
</td>
</tr>

<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7" class="text-center text-muted">Aucun personnel trouvé.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>

<?php endif; ?>

<?php if($type === 'tous' || $type === 'professeur'): ?>

<div class="card-box shadow-sm">
<h5 class="fw-bold mb-3">Professeurs selon emploi du temps</h5>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>Professeur</th>
<th>Plage</th>
<th>Matière</th>
<th>Classe</th>
<th>Scan</th>
<th>Statut</th>
<th>Motif</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php if(count($profs) > 0): ?>
<?php foreach($profs as $p): ?>

<?php
$photo = 'uploads/photos/' . ($p['photo'] ?? '');
$statutAffiche = $p['presence_id'] ? ($p['statut'] ?? 'present') : 'absent';
?>

<tr>
<td>
<div class="d-flex align-items-center gap-2">
<?php if(!empty($p['photo']) && file_exists($photo)): ?>
<img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" class="photo">
<?php else: ?>
<img src="assets/avatar.png" class="photo">
<?php endif; ?>
<div>
<strong><?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
<small class="text-muted"><?= htmlspecialchars($p['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
</div>
</div>
</td>

<td><?= htmlspecialchars($p['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($p['matiere'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($p['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($p['heure_scan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= badgeStatut($statutAffiche) ?></td>
<td><?= htmlspecialchars($p['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>

<td>
<button
class="btn btn-sm btn-warning"
data-bs-toggle="modal"
data-bs-target="#modalModifier"
data-source="professeur"
data-id="<?= (int)($p['presence_id'] ?? 0) ?>"
data-personnel="<?= (int)$p['personnel_id'] ?>"
data-plage="<?= htmlspecialchars($p['plage_horaire'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
data-statut="<?= htmlspecialchars($statutAffiche, ENT_QUOTES, 'UTF-8') ?>"
>
<i class="bi bi-pencil-square"></i>
Gérer
</button>
</td>
</tr>

<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="8" class="text-center text-muted">
Aucun cours prévu pour ce jour.
</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>

<?php endif; ?>




</main>

<div class="modal fade" id="modalModifier" tabindex="-1">
<div class="modal-dialog">
<form method="POST" class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Gérer une présence</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="source" id="sourceInput">
<input type="hidden" name="presence_id" id="presenceIdInput">
<input type="hidden" name="personnel_id" id="personnelIdInput">
<input type="hidden" name="plage_horaire" id="plageInput">

<div class="mb-3">
<label class="form-label">Statut</label>
<select name="statut" id="statutInput" class="form-select" required>
<option value="present">Présent</option>
<option value="retard">Retard</option>
<option value="absent">Absent</option>
<option value="justifie">Justifié</option>
</select>
</div>

<div class="mb-3">
<label class="form-label">Motif / justification</label>
<textarea name="motif" class="form-control" rows="4" required placeholder="Ex : problème réseau, activité scolaire, jour férié, erreur de pointage..."></textarea>
</div>

</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button type="submit" name="modifier_presence" class="btn btn-warning">Enregistrer</button>
</div>

</form>
</div>
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
    if(enabled){
        document.body.classList.add('dark-mode');
        if(darkIcon) darkIcon.className = 'bi bi-sun-fill';
    }else{
        document.body.classList.remove('dark-mode');
        if(darkIcon) darkIcon.className = 'bi bi-moon-fill';
    }
    localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('dark-mode') === 'enabled'){
    setDarkMode(true);
}
darkToggle?.addEventListener('click', () => {
    setDarkMode(!document.body.classList.contains('dark-mode'));
});
</script>

<script>
const modal = document.getElementById('modalModifier');

modal.addEventListener('show.bs.modal', function(event){
    const button = event.relatedTarget;

    document.getElementById('sourceInput').value = button.getAttribute('data-source');
    document.getElementById('presenceIdInput').value = button.getAttribute('data-id');
    document.getElementById('personnelIdInput').value = button.getAttribute('data-personnel');
    document.getElementById('plageInput').value = button.getAttribute('data-plage');

    const statut = button.getAttribute('data-statut') || 'absent';
    document.getElementById('statutInput').value = statut;
});
</script>


</body>
</html>
