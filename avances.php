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

$message = "";
$error = "";

$anneeActuel = (int)date('Y');
$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$personnels = $pdo->query("
    SELECT id, nom, prenom, fonction, matricule
    FROM personnels
    WHERE statut = 'actif'
    ORDER BY nom ASC, prenom ASC
")->fetchAll();

if(isset($_POST['enregistrer'])){

    $personnel_id = intval($_POST['personnel_id'] ?? 0);
    $montant = floatval($_POST['montant'] ?? 0);
    $motif = trim($_POST['motif'] ?? '');
    $date_retenue = trim($_POST['date_retenue'] ?? date('Y-m-d'));

    if($personnel_id <= 0){
        $error = "Veuillez sélectionner un personnel.";
    }elseif($montant <= 0){
        $error = "Le montant doit être supérieur à 0.";
    }elseif(empty($date_retenue)){
        $error = "Veuillez choisir une date.";
    }else{

        $insert = $pdo->prepare("
            INSERT INTO retenues(
                personnel_id,
                montant,
                motif,
                date_retenue
            )
            VALUES(?,?,?,?)
        ");

        $insert->execute([
            $personnel_id,
            $montant,
            $motif,
            $date_retenue
        ]);

        $message = "Retenue enregistrée avec succès.";

    }

}

$retenues = $pdo->query("
    SELECT
        r.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM retenues r
    INNER JOIN personnels p ON p.id = r.personnel_id
    ORDER BY r.date_retenue DESC, r.id DESC
    LIMIT 100
")->fetchAll();

$totalRetenues = $pdo->query("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM retenues
")->fetch();

$retenuesMois = $pdo->query("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM retenues
    WHERE MONTH(date_retenue) = MONTH(CURRENT_DATE())
    AND YEAR(date_retenue) = YEAR(CURRENT_DATE())
")->fetch();

$nombreRetenues = $pdo->query("
    SELECT COUNT(*) AS total
    FROM retenues
")->fetch();

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function dateFr($date){
    if(empty($date)){
        return '-';
    }
    return date('d/m/Y', strtotime($date));
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retenues | Espace DAF</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{
            --bg:#f3f6fb;--card:#ffffff;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;
            --primary:#c96b58;--primary-2:#c96b58;--primary-soft:#fdf4f2;--gold:#f59e0b;
            --success:#16a34a;--warning:#d97706;--danger:#dc2626;--shadow:0 18px 45px rgba(15,23,42,.08);
            --shadow-sm:0 8px 24px rgba(15,23,42,.06);--radius:22px;
        }
        body{font-family:'Inter',sans-serif;background:radial-gradient(circle at top left,rgba(201,107,88,.13),transparent 28%),radial-gradient(circle at top right,rgba(245,158,11,.10),transparent 26%),var(--bg);color:var(--text);transition:.2s;overflow-x:hidden;}
        h1,h2,h3,.stat-value{font-family:'Sora',sans-serif;}

        .sidebar{position:fixed;top:10px!important;left:10px!important;bottom:10px!important;width:280px!important;height:calc(100vh - 20px)!important;padding:16px 16px 18px!important;background:rgba(255,255,255,.82);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:2.5px solid rgba(15,23,42,.92)!important;border-radius:34px!important;box-shadow:var(--shadow);overflow:hidden!important;z-index:999;}
        .logo{color:var(--text);text-align:center;margin-bottom:12px!important;padding:6px 8px 12px!important;border-bottom:1px solid rgba(226,232,240,.9);}
        .logo img{width:58px!important;height:58px!important;border-radius:19px!important;object-fit:cover;margin-bottom:8px!important;border:3px solid #fff;box-shadow:0 12px 26px rgba(15,23,42,.10);}
        .logo h4{color:var(--text);font-family:'Sora',sans-serif;font-size:14px!important;font-weight:800;margin-top:4px!important;}
        .menu{height:calc(100vh - 128px)!important;min-height:430px!important;overflow-y:auto!important;overflow-x:hidden!important;padding-left:8px!important;padding-right:4px!important;padding-bottom:42px!important;direction:rtl;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;}
        .menu>*{direction:ltr;}.menu::-webkit-scrollbar{width:5px;}.menu::-webkit-scrollbar-track{background:transparent;}.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}.menu::-webkit-scrollbar-thumb:hover{background:var(--primary);}
        .menu-section{margin-bottom:10px!important;}.menu-title{color:var(--muted);font-size:9.5px!important;font-weight:800;text-transform:uppercase;letter-spacing:.14em;margin:0 0 5px 12px!important;}
        .menu a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#475569;padding:9px 11px!important;border-radius:16px;margin-bottom:3px!important;min-height:43px!important;white-space:nowrap!important;transition:.2s;font-size:13px;font-weight:700;position:relative;border:1px solid transparent;background:transparent;}
        .menu a i{width:29px!important;height:29px!important;min-width:29px!important;border-radius:12px;display:grid;place-items:center;background:#f1f5f9;color:var(--muted);font-size:15px;}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,.9);border-color:rgba(226,232,240,.95);color:var(--primary);box-shadow:0 10px 24px rgba(15,23,42,.06);}
        .menu a.active::before{content:'';position:absolute;left:-8px;top:13px;bottom:13px;width:4px;border-radius:999px;background:linear-gradient(180deg,#c96b58,#9f4f42);}
        .menu a:hover i,.menu a.active i{color:#fff;background:linear-gradient(135deg,#c96b58,#9f4f42);box-shadow:0 10px 20px rgba(201,107,88,.25);}

        .main-content{margin-left:310px!important;transition:margin-left .25s ease;padding:32px 40px;min-height:100vh;}
        body.sidebar-collapsed .sidebar{width:84px!important;padding-left:10px!important;padding-right:10px!important;}
        body.sidebar-collapsed .main-content{margin-left:114px!important;}
        body.sidebar-collapsed .logo h4,body.sidebar-collapsed .menu-title,body.sidebar-collapsed .menu a span{display:none;}
        body.sidebar-collapsed .logo img{width:48px!important;height:48px!important;border-radius:17px!important;}
        body.sidebar-collapsed .menu{padding-left:4px!important;padding-right:0!important;padding-bottom:42px!important;}
        body.sidebar-collapsed .menu a{justify-content:center;padding:10px 7px!important;}
        body.sidebar-collapsed .menu a i{width:34px!important;height:34px!important;min-width:34px!important;}

        .top-bar{position:relative!important;z-index:9000!important;overflow:visible!important;display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px;background:rgba(255,255,255,.82);backdrop-filter:blur(14px);padding:20px 24px;border-radius:var(--radius);box-shadow:var(--shadow-sm);border:1px solid rgba(226,232,240,.9);}
        .top-left{display:flex;align-items:center;gap:13px;}
        .page-title h1{font-family:'Sora',sans-serif;color:var(--text);font-weight:800;font-size:27px;margin:0 0 4px;letter-spacing:-.3px;}
        .page-title p{color:var(--muted);font-size:13px;margin:0;}
        .header-actions{display:flex;gap:12px;align-items:center;position:relative!important;z-index:9500!important;}
        .year-badge{background:var(--primary-soft);border:1px solid rgba(201,107,88,.18);padding:8px 18px;border-radius:40px;font-size:13px;font-weight:800;color:var(--primary);}
        .dashboard-menu-toggle,.dark-toggle,.back-btn{width:42px;height:42px;border-radius:15px;background:#fff;border:1px solid var(--line);box-shadow:0 8px 20px rgba(15,23,42,.06);display:grid;place-items:center;cursor:pointer;transition:.2s;color:var(--primary);text-decoration:none;flex:0 0 auto;}
        .dashboard-menu-toggle:hover,.dark-toggle:hover,.back-btn:hover{background:linear-gradient(135deg,#c96b58,#9f4f42);color:#fff;border-color:transparent;transform:translateY(-1px);}

        .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:32px;}
        .stat-card{position:relative;min-height:150px;overflow:hidden;background:var(--card);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm);transition:.22s;}
        .stat-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:rgba(201,107,88,.22);}
        .stat-card::before{content:'';position:absolute;right:-34px;top:-38px;width:122px;height:122px;border-radius:50%;background:var(--primary-soft);opacity:.95;}
        .stat-card.warning::before{background:#ffedd5;}.stat-card.success::before{background:#dcfce7;}
        .stat-top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;}
        .stat-icon{width:48px;height:48px;border-radius:17px;display:grid;place-items:center;background:var(--primary-2);color:#fff;box-shadow:0 12px 22px rgba(201,107,88,.24);}
        .stat-icon i{font-size:21px;color:#fff;}
        .stat-card.warning .stat-icon{background:var(--warning);box-shadow:0 12px 22px rgba(217,119,6,.22);}
        .stat-card.success .stat-icon{background:var(--success);box-shadow:0 12px 22px rgba(22,163,74,.22);}
        .stat-chip{position:relative;z-index:1;font-size:11px;color:var(--muted);font-weight:800;background:#f8fafc;border:1px solid var(--line);padding:5px 9px;border-radius:999px;}
        .stat-label{position:relative;z-index:1;margin-top:18px;color:var(--muted);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;}
        .stat-value{position:relative;z-index:1;font-size:26px;font-weight:800;color:var(--text);letter-spacing:-1px;margin-top:5px;line-height:1.15;}
        .stat-note{position:relative;z-index:1;font-size:12px;color:var(--muted);margin-top:6px;}

        .table-card,.form-card{background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:32px;}
        .form-card{padding:24px;overflow:visible;}
        .section-header{padding:17px 20px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
        .section-header h3{font-family:'Sora',sans-serif;font-size:15px;font-weight:800;color:var(--text);margin:0;display:flex;align-items:center;gap:8px;}
        .section-header h3 i{color:var(--primary)!important;}
        .form-card .section-header{padding:0 0 18px;margin-bottom:20px;}
        .form-label{font-size:13px;font-weight:700;color:#374151;margin-bottom:7px;}
        .form-control,.form-select{border-radius:14px;border:1px solid var(--line);min-height:46px;font-size:14px;box-shadow:none!important;background:#fff;}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 .2rem rgba(201,107,88,.10)!important;}
        .btn-main{background:var(--primary);color:#fff;border:none;border-radius:14px;padding:12px 22px;font-weight:800;box-shadow:0 12px 22px rgba(201,107,88,.18);}
        .btn-main:hover{background:#0f172a;color:#fff;}
        .badge-soft{background:var(--primary-soft);color:var(--primary);border-radius:999px;padding:6px 11px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:6px;}
        .badge-retenue{background:var(--primary-soft);color:var(--primary);border-radius:999px;padding:7px 11px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:6px;}
        .alert{border-radius:16px;border:none;box-shadow:var(--shadow-sm);}
        .table{margin:0;}
        .table thead th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);background:#f8fafc;border-bottom:1px solid var(--line);padding:14px 16px;}
        .table tbody td{padding:14px 16px;font-size:13px;color:#374151;vertical-align:middle;}
        .empty-box{text-align:center;padding:38px;color:#9ca3af;}

        .history-filter{padding:18px 20px;border-bottom:1px solid var(--line);background:rgba(248,250,252,.62);display:grid;grid-template-columns:1fr 180px auto;gap:12px;align-items:end;}
        .filter-box{position:relative;}
        .filter-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:15px;}
        .filter-box input{padding-left:40px;}
        .filter-help{grid-column:1 / -1;color:var(--muted);font-size:12px;margin-top:-4px;}
        .filter-result{font-size:12px;font-weight:800;color:var(--primary);background:var(--primary-soft);border-radius:999px;padding:7px 11px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;}
        .btn-reset-filter{height:46px;border-radius:14px;border:1px solid var(--line);background:#fff;color:var(--text);font-size:13px;font-weight:800;padding:0 15px;display:inline-flex;align-items:center;gap:7px;justify-content:center;}
        .btn-reset-filter:hover{background:#f8fafc;color:var(--primary);}
        tr.filter-hidden{display:none;}
        tr.filter-match{background:rgba(253,244,242,.70);}

        body.dark-mode{--bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#243044;--primary-soft:rgba(201,107,88,.16);background:#0b1120;}
        body.dark-mode .sidebar{background:rgba(26,32,44,.86);border-color:rgba(255,255,255,.78)!important;box-shadow:0 18px 45px rgba(0,0,0,.22);}
        body.dark-mode .logo{border-bottom-color:#2d3340;}
        body.dark-mode .logo h4,body.dark-mode .page-title h1,body.dark-mode .stat-value,body.dark-mode .section-header h3{color:#f0f2f5;}
        body.dark-mode .menu a{color:#cbd5e1;}
        body.dark-mode .menu a i{background:#111827;color:#94a3b8;}
        body.dark-mode .menu a:hover,body.dark-mode .menu a.active{background:rgba(255,255,255,.06);border-color:#2d3340;color:#e5a498;}
        body.dark-mode .top-bar,body.dark-mode .stat-card,body.dark-mode .form-card,body.dark-mode .table-card,body.dark-mode .year-badge,body.dark-mode .dark-toggle,body.dark-mode .dashboard-menu-toggle,body.dark-mode .back-btn{background:#1a202c;border-color:#2d3340;}
        body.dark-mode .page-title p,body.dark-mode .stat-label,body.dark-mode .stat-note{color:#9ca3af;}
        body.dark-mode .table tbody td,body.dark-mode .form-label{color:#e5e7eb;}
        body.dark-mode .table thead th{background:#111827;color:#9ca3af;border-color:#2d3340;}
        body.dark-mode .form-control,body.dark-mode .form-select{background:#111827;border-color:#2d3340;color:#f0f2f5;}
        body.dark-mode .form-control::placeholder{color:#64748b;}
        body.dark-mode .history-filter{background:rgba(17,24,39,.42);}
        body.dark-mode .btn-reset-filter{background:#111827;border-color:#2d3340;color:#e5e7eb;}

        @media(max-width:1200px){.stats-grid{grid-template-columns:1fr;}.main-content{padding:24px 28px;}.history-filter{grid-template-columns:1fr;}}
        @media(max-width:768px){.sidebar{top:8px!important;left:8px!important;bottom:8px!important;width:265px!important;height:calc(100vh - 16px)!important;padding:14px 14px 16px!important;border-radius:32px!important;}.menu{height:calc(100vh - 116px)!important;min-height:420px!important;padding-bottom:46px!important;}.main-content{margin-left:285px!important;padding:18px 12px!important;}body.sidebar-collapsed .sidebar{width:78px!important;}body.sidebar-collapsed .main-content{margin-left:96px!important;}.top-bar{flex-direction:column;align-items:flex-start;}.header-actions{width:100%;flex-wrap:wrap;}}
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
            <a href="dashboard_daf.php" title="Dashboard DAF"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-title">Gestion financière</div>
            <a href="personnel.php"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="salaires.php"><i class="bi bi-cash-stack"></i><span>Salaires</span></a>
            <a href="paiements.php"><i class="bi bi-credit-card"></i><span>Paiements</span></a>
            <a href="avances.php"><i class="bi bi-wallet2"></i><span>Avances</span></a>
            <a href="retenues.php" class="active"><i class="bi bi-dash-circle"></i><span>Retenues</span></a>
            <a href="impayes.php"><i class="bi bi-exclamation-triangle"></i><span>Impayés</span></a>
            <a href="fiches_paie.php"><i class="bi bi-file-earmark-pdf"></i><span>Fiches de paie</span></a>
            <a href="statistiques_daf.php"><i class="bi bi-bar-chart"></i><span>Statistiques</span></a>
            <a href="notifications_daf.php"><i class="bi bi-bell"></i><span>Notifications</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-title">Compte</div>
            <a href="profil_daf.php"><i class="bi bi-person-circle"></i><span>Profil</span></a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a>
        </div>
    </div>
</div>

<main class="main-content">
    <div class="top-bar">
        <div class="top-left">
            <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu"><i class="bi bi-list"></i></button>
            <a href="dashboard_daf.php" class="back-btn" title="Retour au dashboard DAF"><i class="bi bi-arrow-left"></i></a>
            <div class="page-title">
                <h1>Gestion des retenues</h1>
                <p>Bienvenue, <?= htmlspecialchars($_SESSION['admin'] ?? 'DAF', ENT_QUOTES, 'UTF-8') ?> · Enregistrer et consulter les retenues appliquées au personnel</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($annee['libelle'] ?? $anneeActuel, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></button>
        </div>
    </div>

    <?php if(!empty($message)): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-dash-circle-fill"></i></div><span class="stat-chip">Total</span></div>
            <div class="stat-label">Total des retenues</div>
            <div class="stat-value"><?= argent($totalRetenues['total'] ?? 0) ?></div>
            <div class="stat-note">Toutes les retenues enregistrées</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-calendar-minus"></i></div><span class="stat-chip">Mois</span></div>
            <div class="stat-label">Retenues du mois</div>
            <div class="stat-value"><?= argent($retenuesMois['total'] ?? 0) ?></div>
            <div class="stat-note">Montant retenu ce mois</div>
        </div>
        <div class="stat-card success">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-list-check"></i></div><span class="stat-chip">Nombre</span></div>
            <div class="stat-label">Nombre de retenues</div>
            <div class="stat-value"><?= number_format((int)($nombreRetenues['total'] ?? 0)) ?></div>
            <div class="stat-note">Lignes enregistrées</div>
        </div>
    </div>

    <div class="form-card">
        <div class="section-header"><h3><i class="bi bi-plus-circle-fill"></i> Nouvelle retenue</h3><span class="badge-soft"><i class="bi bi-shield-check"></i> Formulaire sécurisé</span></div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Personnel</label>
                    <select name="personnel_id" class="form-select" required>
                        <option value="">Sélectionner un personnel</option>
                        <?php foreach($personnels as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Montant</label>
                    <input type="number" step="0.01" name="montant" class="form-control" placeholder="Ex : 15000" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date de retenue</label>
                    <input type="date" name="date_retenue" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Motif</label>
                    <input type="text" name="motif" class="form-control" placeholder="Ex : absence, retard, remboursement avance...">
                </div>
                <div class="col-12">
                    <button type="submit" name="enregistrer" class="btn btn-main"><i class="bi bi-check2-circle"></i> Enregistrer la retenue</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="section-header"><h3><i class="bi bi-clock-history"></i> Historique des retenues</h3><span class="badge-soft"><i class="bi bi-list-check"></i> <?= count($retenues) ?> dernières lignes</span></div>
        <div class="history-filter">
            <div>
                <label class="form-label" for="retenueSearch">Rechercher dans l'historique</label>
                <div class="filter-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="retenueSearch" class="form-control" placeholder="Nom, matricule, fonction, montant, date, motif...">
                </div>
            </div>
            <div>
                <label class="form-label" for="retenueMonthFilter">Mois</label>
                <input type="month" id="retenueMonthFilter" class="form-control">
            </div>
            <button type="button" class="btn-reset-filter" id="resetRetenueFilter"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</button>
            <div class="filter-help">Les lignes correspondantes restent visibles et remontent automatiquement en haut du tableau. <span class="filter-result" id="retenueFilterCount"><i class="bi bi-funnel"></i> <?= count($retenues) ?> résultat(s)</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Fonction</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th>Motif</th>
                    </tr>
                </thead>
                <tbody id="retenuesTableBody">
                <?php if(count($retenues) > 0): ?>
                    <?php foreach($retenues as $r): ?>
                        <tr data-month="<?= htmlspecialchars(date('Y-m', strtotime($r['date_retenue'] ?? date('Y-m-d'))), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(trim(($r['nom'] ?? '').' '.($r['prenom'] ?? '').' '.($r['matricule'] ?? '').' '.($r['fonction'] ?? '').' '.($r['montant'] ?? '').' '.dateFr($r['date_retenue'] ?? null).' '.($r['motif'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <td><strong><?= htmlspecialchars(($r['nom'] ?? '').' '.($r['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td><?= htmlspecialchars($r['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge-retenue"><i class="bi bi-dash-circle"></i> <?= argent($r['montant'] ?? 0) ?></span></td>
                            <td><?= htmlspecialchars(dateFr($r['date_retenue'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-box"><i class="bi bi-inbox" style="font-size:32px;"></i><p style="margin-top:12px;">Aucune retenue enregistrée pour le moment.</p></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

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
        if(darkIcon){
            darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
        localStorage.setItem('dark-mode-daf', enabled ? 'enabled' : 'disabled');
    }
    if(localStorage.getItem('dark-mode-daf') === 'enabled') setDarkMode(true);
    darkToggle?.addEventListener('click', function(){
        setDarkMode(!document.body.classList.contains('dark-mode'));
    });

    const retenueSearch = document.getElementById('retenueSearch');
    const retenueMonthFilter = document.getElementById('retenueMonthFilter');
    const resetRetenueFilter = document.getElementById('resetRetenueFilter');
    const retenueFilterCount = document.getElementById('retenueFilterCount');
    const retenuesTableBody = document.getElementById('retenuesTableBody');
    const retenueRows = retenuesTableBody ? Array.from(retenuesTableBody.querySelectorAll('tr[data-search]')) : [];
    const originalRetenueRows = [...retenueRows];

    function normalizeText(value){
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyRetenueFilter(){
        const query = normalizeText(retenueSearch?.value || '');
        const month = retenueMonthFilter?.value || '';
        let matches = 0;

        const sortedRows = [...originalRetenueRows].sort(function(a, b){
            const aText = normalizeText(a.dataset.search || a.textContent);
            const bText = normalizeText(b.dataset.search || b.textContent);
            const aMatch = query === '' || aText.includes(query);
            const bMatch = query === '' || bText.includes(query);
            return Number(bMatch) - Number(aMatch);
        });

        sortedRows.forEach(function(row){
            const rowText = normalizeText(row.dataset.search || row.textContent);
            const rowMonth = row.dataset.month || '';
            const textOk = query === '' || rowText.includes(query);
            const monthOk = month === '' || rowMonth === month;
            const visible = textOk && monthOk;

            row.classList.toggle('filter-hidden', !visible);
            row.classList.toggle('filter-match', visible && (query !== '' || month !== ''));
            if(visible) matches++;
            retenuesTableBody?.appendChild(row);
        });

        if(retenueFilterCount){
            retenueFilterCount.innerHTML = '<i class="bi bi-funnel"></i> ' + matches + ' résultat(s)';
        }
    }

    retenueSearch?.addEventListener('input', applyRetenueFilter);
    retenueMonthFilter?.addEventListener('change', applyRetenueFilter);
    resetRetenueFilter?.addEventListener('click', function(){
        if(retenueSearch) retenueSearch.value = '';
        if(retenueMonthFilter) retenueMonthFilter.value = '';
        applyRetenueFilter();
    });
    applyRetenueFilter();
</script>

</body>
</html>