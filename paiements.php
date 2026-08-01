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

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$message = "";
$error = "";

if(isset($_POST['payer'])){

    $salaire_id = intval($_POST['salaire_id'] ?? 0);
    $montant = floatval($_POST['montant'] ?? 0);
    $mode_paiement = trim($_POST['mode_paiement'] ?? 'especes');
    $reference_paiement = trim($_POST['reference_paiement'] ?? '');

    if($salaire_id <= 0){
        $error = "Veuillez sélectionner un salaire.";
    }elseif($montant <= 0){
        $error = "Le montant payé doit être supérieur à 0.";
    }else{

        $salaireReq = $pdo->prepare("
            SELECT *
            FROM salaires
            WHERE id = ?
            LIMIT 1
        ");
        $salaireReq->execute([$salaire_id]);
        $salaire = $salaireReq->fetch();

        if(!$salaire){
            $error = "Salaire introuvable.";
        }else{

            $insert = $pdo->prepare("
                INSERT INTO paiements_salaires(
                    salaire_id,
                    montant,
                    mode_paiement,
                    reference_paiement
                )
                VALUES(?,?,?,?)
            ");

            $insert->execute([
                $salaire_id,
                $montant,
                $mode_paiement,
                $reference_paiement
            ]);

            $totalPayeReq = $pdo->prepare("
                SELECT COALESCE(SUM(montant),0) AS total
                FROM paiements_salaires
                WHERE salaire_id = ?
            ");
            $totalPayeReq->execute([$salaire_id]);
            $totalPaye = $totalPayeReq->fetch();

            if((float)$totalPaye['total'] >= (float)$salaire['salaire_net']){
                $statut = 'paye';
            }else{
                $statut = 'partiellement_paye';
            }

            $update = $pdo->prepare("
                UPDATE salaires
                SET statut = ?
                WHERE id = ?
            ");

            $update->execute([
                $statut,
                $salaire_id
            ]);

            $message = "Paiement enregistré avec succès.";

        }

    }

}

$salaires = $pdo->query("
    SELECT
        s.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule,
        (
            SELECT COALESCE(SUM(ps.montant),0)
            FROM paiements_salaires ps
            WHERE ps.salaire_id = s.id
        ) AS total_paye
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    WHERE s.statut != 'paye'
    ORDER BY s.annee DESC, s.mois DESC, p.nom ASC
")->fetchAll();

$paiements = $pdo->query("
    SELECT
        ps.*,
        s.mois,
        s.annee,
        s.salaire_net,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM paiements_salaires ps
    INNER JOIN salaires s ON s.id = ps.salaire_id
    INNER JOIN personnels p ON p.id = s.personnel_id
    ORDER BY ps.date_paiement DESC
    LIMIT 100
")->fetchAll();

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

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Paiements des salaires | Espace DAF</title>

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
    --primary-soft:#dbeafe;
    --daf:#c96b58;
    --daf-dark:#9f4f42;
    --daf-soft:#fdf4f2;
    --daf-border:#f0c9c1;
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

h1,h2,h3,.page-title,.card-title-custom,.info-box h3{
    font-family:'Sora',sans-serif;
}

/* SIDEBAR DAF IDENTIQUE DASHBOARD_DAF */

.sidebar{
    position:fixed!important;
    top:10px!important;
    left:10px!important;
    bottom:10px!important;
    width:280px!important;
    height:calc(100vh - 20px)!important;
    padding:16px 16px 18px!important;
    background:rgba(255,255,255,.82)!important;
    backdrop-filter:blur(16px)!important;
    -webkit-backdrop-filter:blur(16px)!important;
    border:2.5px solid rgba(15,23,42,.92)!important;
    border-radius:34px!important;
    box-shadow:var(--shadow)!important;
    overflow:hidden!important;
    z-index:999!important;
}

.logo{
    color:var(--text)!important;
    text-align:center!important;
    margin-bottom:12px!important;
    padding:6px 8px 12px!important;
    border-bottom:1px solid rgba(226,232,240,.9)!important;
}

.logo img{
    width:58px!important;
    height:58px!important;
    border-radius:19px!important;
    object-fit:cover!important;
    margin-bottom:8px!important;
    border:3px solid #fff!important;
    box-shadow:0 12px 26px rgba(15,23,42,.10)!important;
}

.logo h4{
    color:var(--text)!important;
    font-family:'Sora',sans-serif!important;
    font-size:14px!important;
    font-weight:800!important;
    margin-top:4px!important;
}

.menu{
    height:calc(100vh - 128px)!important;
    min-height:430px!important;
    padding-left:8px!important;
    padding-right:4px!important;
    padding-bottom:42px!important;
    overflow-y:auto!important;
    overflow-x:hidden!important;
    direction:rtl!important;
    scrollbar-width:thin!important;
    scrollbar-color:#cbd5e1 transparent!important;
}

.menu > *{
    direction:ltr!important;
}

.menu::-webkit-scrollbar{
    width:5px!important;
}

.menu::-webkit-scrollbar-track{
    background:transparent!important;
}

.menu::-webkit-scrollbar-thumb{
    background:#cbd5e1!important;
    border-radius:999px!important;
}

.menu::-webkit-scrollbar-thumb:hover{
    background:var(--daf)!important;
}

.menu-section{
    margin-bottom:10px!important;
}

.menu-title{
    color:var(--muted)!important;
    font-size:9.5px!important;
    font-weight:800!important;
    text-transform:uppercase!important;
    letter-spacing:.14em!important;
    margin:0 0 5px 12px!important;
}

.menu a{
    display:flex!important;
    align-items:center!important;
    gap:12px!important;
    text-decoration:none!important;
    color:#475569!important;
    padding:9px 11px!important;
    border-radius:16px!important;
    margin-bottom:3px!important;
    min-height:43px!important;
    white-space:nowrap!important;
    font-weight:700!important;
    font-size:13px!important;
    position:relative!important;
    border:1px solid transparent!important;
    background:transparent!important;
    transition:.2s!important;
}

.menu a i{
    width:29px!important;
    height:29px!important;
    min-width:29px!important;
    border-radius:12px!important;
    display:grid!important;
    place-items:center!important;
    background:#f1f5f9!important;
    color:var(--muted)!important;
    font-size:15px!important;
}

.menu a:hover,
.menu a.active{
    background:rgba(255,255,255,.9)!important;
    border-color:rgba(226,232,240,.95)!important;
    color:var(--daf)!important;
    box-shadow:0 10px 24px rgba(15,23,42,.06)!important;
}

.menu a.active::before{
    content:''!important;
    position:absolute!important;
    left:-8px!important;
    top:13px!important;
    bottom:13px!important;
    width:4px!important;
    border-radius:999px!important;
    background:linear-gradient(180deg,var(--daf),var(--daf-dark))!important;
}

.menu a:hover i,
.menu a.active i{
    color:#fff!important;
    background:linear-gradient(135deg,var(--daf),var(--daf-dark))!important;
    box-shadow:0 10px 20px rgba(201,107,88,.25)!important;
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
    background:var(--daf-soft);
    border:1px solid var(--daf-border);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:var(--daf);
}

.dashboard-menu-toggle,
.dark-toggle,
.back-btn{
    width:42px;
    height:42px;
    border-radius:15px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--daf);
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
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

.card-box{
    padding:24px;
    margin-bottom:28px;
}

.card-title-custom{
    font-size:18px;
    font-weight:800;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--text);
}

.card-title-custom i{
    color:var(--daf);
}

.form-label{
    font-size:13px;
    font-weight:800;
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
    border-color:var(--daf);
}

.btn-main{
    background:var(--daf);
    color:white;
    border:none;
    border-radius:14px;
    padding:12px 22px;
    font-weight:800;
    box-shadow:0 12px 22px rgba(201,107,88,.22);
}

.btn-main:hover{
    background:#111827;
    color:white;
    transform:translateY(-1px);
}

.alert{
    border-radius:16px;
    border:none;
    box-shadow:var(--shadow-sm);
}

.table{
    margin:0;
}

.table thead th{
    background:#f8fafc;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:var(--muted);
    padding:14px;
    border-bottom:1px solid var(--line);
}

.table tbody td{
    padding:14px;
    font-size:13px;
    vertical-align:middle;
    color:#374151;
}

.table tbody tr:hover{
    background:#fafbfc;
}

.info-box{
    background:var(--daf-soft);
    border:1px solid var(--daf-border);
    border-radius:18px;
    padding:16px;
    height:100%;
}

.info-box small{
    color:var(--muted);
    font-weight:800;
}

.info-box h3{
    color:var(--daf);
    font-size:24px;
    font-weight:800;
    margin:6px 0 0;
}

.badge-mode{
    background:var(--daf-soft);
    color:var(--daf);
    border-radius:20px;
    padding:6px 10px;
    font-size:11px;
    font-weight:800;
}

.filter-zone{
    flex:1;
    max-width:820px;
}

.filter-zone .form-control,
.filter-zone .form-select{
    min-height:42px;
    font-size:13px;
}

.no-result-row{
    display:none;
}

.no-result-row td{
    text-align:center;
    color:var(--muted)!important;
    padding:28px!important;
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
    display:none!important;
}

body.sidebar-collapsed .logo img{
    width:48px!important;
    height:48px!important;
    border-radius:17px!important;
}

body.sidebar-collapsed .menu{
    padding-left:4px!important;
    padding-right:0!important;
    padding-bottom:42px!important;
}

body.sidebar-collapsed .menu a{
    padding:10px 7px!important;
    justify-content:center!important;
}

body.sidebar-collapsed .menu a i{
    width:34px!important;
    height:34px!important;
    min-width:34px!important;
}

/* DARK MODE */

body.dark-mode{
    --bg:#0b1120;
    --card:#111827;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#2d3340;
    background:#0b1120;
}

body.dark-mode .sidebar,
body.dark-mode .topbar,
body.dark-mode .card-box,
body.dark-mode .dashboard-footer,
body.dark-mode .dashboard-menu-toggle,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn{
    background:#1a202c!important;
    border-color:#2d3340!important;
    color:#f0f2f5;
}

body.dark-mode .sidebar{
    border-color:rgba(255,255,255,.78)!important;
}

body.dark-mode .logo{
    border-bottom-color:#2d3340!important;
}

body.dark-mode .logo h4,
body.dark-mode .page-title,
body.dark-mode .card-title-custom{
    color:#f0f2f5!important;
}

body.dark-mode .menu a{
    color:#cbd5e1!important;
}

body.dark-mode .menu a i{
    background:#111827!important;
    color:#94a3b8!important;
}

body.dark-mode .menu a:hover,
body.dark-mode .menu a.active{
    background:rgba(201,107,88,.15)!important;
    border-color:#2d3340!important;
    color:#e5a498!important;
}

body.dark-mode .form-control,
body.dark-mode .form-select,
body.dark-mode .info-box{
    background:#111827;
    border-color:#2d3340;
    color:#e5e7eb;
}

body.dark-mode .table thead th{
    background:#111827;
    border-color:#2d3340;
}

body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}

body.dark-mode .table tbody tr:hover{
    background:#111827;
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

    .filter-zone{
        max-width:100%;
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

<img
src="uploads/logo/logo.jpeg"
alt="Logo"
>

<h4>ESPACE DAF</h4>

</div>

<div class="menu">

<div class="menu-section">
<div class="menu-title">Principal</div>

<a href="dashboard_daf.php">
<i class="bi bi-speedometer2"></i>
<span>Dashboard</span>
</a>
</div>

<div class="menu-section">
<div class="menu-title">Gestion financière</div>

<a href="personnel.php">
<i class="bi bi-people"></i>
<span>Personnel</span>
</a>

<a href="salaires.php">
<i class="bi bi-cash-stack"></i>
<span>Salaires</span>
</a>

<a href="paiements.php" class="active">
<i class="bi bi-credit-card"></i>
<span>Paiements</span>
</a>

<a href="avances.php">
<i class="bi bi-wallet2"></i>
<span>Avances</span>
</a>

<a href="retenues.php">
<i class="bi bi-dash-circle"></i>
<span>Retenues</span>
</a>

<a href="impayes.php">
<i class="bi bi-exclamation-triangle"></i>
<span>Impayés</span>
</a>

<a href="fiches_paie.php">
<i class="bi bi-file-earmark-pdf"></i>
<span>Fiches de paie</span>
</a>

<a href="statistiques_daf.php">
<i class="bi bi-bar-chart"></i>
<span>Statistiques</span>
</a>

<a href="notifications_daf.php">
<i class="bi bi-bell"></i>
<span>Notifications</span>
</a>
</div>

<div class="menu-section">
<div class="menu-title">Compte</div>

<a href="profil_daf.php">
<i class="bi bi-person-circle"></i>
<span>Profil</span>
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
<span>Déconnexion</span>
</a>
</div>

</div></div>

</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
<i class="bi bi-list"></i>
</button>

<a
href="dashboard_daf.php"
class="back-btn"
title="Retour au dashboard DAF"
>
<i class="bi bi-arrow-left"></i>
</a>

<div>

<h1 class="page-title">
Paiements des salaires
</h1>

<p class="page-subtitle">
Enregistrer les paiements effectués aux agents
</p>

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

<?php if(!empty($message)): ?>

<div class="alert alert-success">
<i class="bi bi-check-circle-fill"></i>
<?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<?php if(!empty($error)): ?>

<div class="alert alert-danger">
<i class="bi bi-exclamation-triangle-fill"></i>
<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-credit-card-fill"></i>
Enregistrer un paiement
</div>

<form method="POST">

<div class="row g-3">

<div class="col-md-6">

<label class="form-label">
Salaire à payer
</label>

<select
name="salaire_id"
id="salaire_id"
class="form-select"
required
>

<option value="">
Sélectionner un salaire
</option>

<?php foreach($salaires as $s): ?>

<?php
$reste = (float)$s['salaire_net'] - (float)$s['total_paye'];
if($reste < 0){ $reste = 0; }
?>

<option
value="<?= (int)$s['id'] ?>"
data-net="<?= htmlspecialchars($s['salaire_net'], ENT_QUOTES, 'UTF-8') ?>"
data-paye="<?= htmlspecialchars($s['total_paye'], ENT_QUOTES, 'UTF-8') ?>"
data-reste="<?= htmlspecialchars($reste, ENT_QUOTES, 'UTF-8') ?>"
>
<?= htmlspecialchars(($s['nom'] ?? '').' '.($s['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
-
<?= htmlspecialchars($moisNoms[(int)$s['mois']] ?? $s['mois'], ENT_QUOTES, 'UTF-8') ?>
<?= htmlspecialchars($s['annee'], ENT_QUOTES, 'UTF-8') ?>
-
Reste : <?= argent($reste) ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-3">

<div class="info-box">

<small>Reste à payer</small>

<h3 id="restePreview">
0 FCFA
</h3>

</div>

</div>

<div class="col-md-3">

<label class="form-label">
Montant payé
</label>

<input
type="number"
step="0.01"
name="montant"
id="montant"
class="form-control"
required
>

</div>

<div class="col-md-4">

<label class="form-label">
Mode de paiement
</label>

<select
name="mode_paiement"
class="form-select"
required
>

<option value="especes">Espèces</option>
<option value="mobile_money">Mobile Money</option>
<option value="virement">Virement</option>
<option value="cheque">Chèque</option>

</select>

</div>

<div class="col-md-8">

<label class="form-label">
Référence du paiement
</label>

<input
type="text"
name="reference_paiement"
class="form-control"
placeholder="Ex : Reçu, numéro transaction, référence virement..."
>

</div>

<div class="col-12">

<button
type="submit"
name="payer"
class="btn btn-main"
>

<i class="bi bi-check2-circle"></i>
Enregistrer le paiement

</button>

</div>

</div>

</form>

</div>

<div class="card-box">

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">

<div class="card-title-custom mb-0">
<i class="bi bi-clock-history"></i>
Historique des paiements
</div>

<div class="row g-2 filter-zone">

<div class="col-md-5">
<input
type="text"
id="searchPaiement"
class="form-control"
placeholder="Rechercher nom, matricule, référence..."
autocomplete="off"
>
</div>

<div class="col-md-3">
<select id="filterMode" class="form-select">
<option value="">Tous modes</option>
<option value="especes">Espèces</option>
<option value="mobile_money">Mobile Money</option>
<option value="virement">Virement</option>
<option value="cheque">Chèque</option>
</select>
</div>

<div class="col-md-2">
<select id="filterMois" class="form-select">
<option value="">Tous mois</option>
<?php foreach($moisNoms as $num => $nom): ?>
<option value="<?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>">
<?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<input
type="number"
id="filterAnnee"
class="form-control"
placeholder="Année"
>
</div>

</div>

</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Personnel</th>
<th>Fonction</th>
<th>Mois</th>
<th>Montant payé</th>
<th>Mode</th>
<th>Référence</th>
<th>Date</th>
</tr>

</thead>

<tbody id="paiementsTableBody">

<?php if(count($paiements) > 0): ?>

<?php foreach($paiements as $p): ?>

<tr
data-mode="<?= htmlspecialchars($p['mode_paiement'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
data-mois="<?= htmlspecialchars($moisNoms[(int)$p['mois']] ?? $p['mois'], ENT_QUOTES, 'UTF-8') ?>"
data-annee="<?= htmlspecialchars($p['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>

<td>
<strong>
<?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
<?= htmlspecialchars($p['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<?= htmlspecialchars($p['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars($moisNoms[(int)$p['mois']] ?? $p['mois'], ENT_QUOTES, 'UTF-8') ?>
<?= htmlspecialchars($p['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<strong>
<?= argent($p['montant'] ?? 0) ?>
</strong>
</td>

<td>
<span class="badge-mode">
<?= htmlspecialchars($p['mode_paiement'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</span>
</td>

<td>
<?= htmlspecialchars($p['reference_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= htmlspecialchars(date('d/m/Y H:i', strtotime($p['date_paiement'])), ENT_QUOTES, 'UTF-8') ?>
</td>

</tr>

<?php endforeach; ?>

<tr class="no-result-row" id="noResultRow">
<td colspan="7">
<i class="bi bi-search"></i>
Aucun paiement ne correspond au filtre.
</td>
</tr>

<?php else: ?>

<tr>
<td
colspan="7"
class="text-center text-muted py-4"
>
Aucun paiement enregistré pour le moment.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


<footer class="dashboard-footer">

<div class="footer-left">

<img
src="uploads/logo/logo.jpeg"
alt="Logo CMAK"
>

<span>
© <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion financière du personnel scolaire. Tous droits réservés.
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
    localStorage.setItem('sidebar-collapsed-daf', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('sidebar-collapsed-daf') === 'enabled'){
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

    localStorage.setItem('dark-mode-daf', enabled ? 'enabled' : 'disabled');
}

if(localStorage.getItem('dark-mode-daf') === 'enabled'){
    setDarkMode(true);
}

darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});


const salaireSelect = document.getElementById('salaire_id');
const restePreview = document.getElementById('restePreview');
const montantInput = document.getElementById('montant');

function formatMoney(value){
    return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
}

salaireSelect.addEventListener('change', function(){

    const selected = this.options[this.selectedIndex];
    const reste = parseFloat(selected.getAttribute('data-reste')) || 0;

    restePreview.textContent = formatMoney(reste);
    montantInput.value = reste > 0 ? reste : '';

});


const searchPaiement = document.getElementById('searchPaiement');
const filterMode = document.getElementById('filterMode');
const filterMois = document.getElementById('filterMois');
const filterAnnee = document.getElementById('filterAnnee');
const paiementsTableBody = document.getElementById('paiementsTableBody');
const noResultRow = document.getElementById('noResultRow');

function filtrerPaiements(){
    if(!paiementsTableBody) return;

    const q = (searchPaiement?.value || '').toLowerCase().trim();
    const mode = filterMode?.value || '';
    const mois = filterMois?.value || '';
    const annee = (filterAnnee?.value || '').trim();

    let visibleCount = 0;

    const rows = Array.from(paiementsTableBody.querySelectorAll('tr'))
        .filter(row => row.id !== 'noResultRow' && !row.querySelector('td[colspan="7"]'));

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowMode = row.dataset.mode || '';
        const rowMois = row.dataset.mois || '';
        const rowAnnee = row.dataset.annee || '';

        const okSearch = q === '' || text.includes(q);
        const okMode = mode === '' || rowMode === mode;
        const okMois = mois === '' || rowMois === mois;
        const okAnnee = annee === '' || rowAnnee === annee;

        const show = okSearch && okMode && okMois && okAnnee;

        row.style.display = show ? '' : 'none';

        if(show) visibleCount++;
    });

    if(noResultRow){
        noResultRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
    }
}

[searchPaiement, filterMode, filterMois, filterAnnee].forEach(el => {
    el?.addEventListener('input', filtrerPaiements);
    el?.addEventListener('change', filtrerPaiements);
});

</script>

</body>

</html>