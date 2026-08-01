<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$fonction = trim($_GET['fonction'] ?? '');

$sql = "
    SELECT *
    FROM personnels
    WHERE 1
";

$params = [];

if($search !== ''){
    $sql .= "
        AND (
            nom LIKE ?
            OR prenom LIKE ?
            OR matricule LIKE ?
            OR fonction LIKE ?
            OR telephone LIKE ?
            OR email LIKE ?
        )
    ";

    $like = "%".$search."%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if($fonction !== ''){
    $sql .= " AND fonction = ?";
    $params[] = $fonction;
}

$sql .= " ORDER BY nom ASC, prenom ASC LIMIT 200";

$query = $pdo->prepare($sql);
$query->execute($params);
$personnels = $query->fetchAll();

$fonctions = $pdo->query("
    SELECT DISTINCT fonction
    FROM personnels
    WHERE fonction IS NOT NULL
    AND fonction != ''
    ORDER BY fonction ASC
")->fetchAll();

$totalPersonnel = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
")->fetch();

$totalActifs = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE statut = 'actif'
")->fetch();

$totalSuspendus = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE statut = 'suspendu'
")->fetch();

$masseSalariale = $pdo->query("
    SELECT COALESCE(SUM(salaire_base),0) AS total
    FROM personnels
    WHERE statut = 'actif'
")->fetch();

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function dateFr($date){
    if(empty($date) || $date === '0000-00-00'){
        return '-';
    }

    return date('d/m/Y', strtotime($date));
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

<title>Personnel - Consultation DAF</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
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

body{
    font-family:'Inter',sans-serif;
    background:#f5f7fa;
    color:#1f2937;
}

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    background:#111827;
    padding:30px 20px;
    overflow-y:auto;
    z-index:999;
}

.logo{
    color:white;
    margin-bottom:35px;
    text-align:center;
}

.logo img{
    width:80px;
    height:80px;
    border-radius:16px;
    object-fit:cover;
    margin-bottom:12px;
}

.logo h4{
    font-size:18px;
    font-weight:700;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:#9ca3af;
    padding:12px 16px;
    border-radius:12px;
    margin-bottom:6px;
    transition:.2s;
    font-size:14px;
    font-weight:500;
}

.menu a:hover,
.menu a.active{
    background:rgba(201,107,88,.15);
    color:#e5a498;
}

.menu a i{
    font-size:18px;
    width:22px;
}

.main{
    margin-left:260px;
    padding:32px 40px;
    min-height:100vh;
}

.topbar{
    background:white;
    border:1px solid #e4e7eb;
    border-radius:16px;
    padding:20px 24px;
    margin-bottom:28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.top-left{
    display:flex;
    align-items:center;
    gap:15px;
}

.back-btn{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#f3f4f6;
    border:1px solid #e5e7eb;
    color:#111827;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    transition:.2s;
}

.back-btn:hover{
    background:#111827;
    color:white;
}

.page-title{
    font-size:24px;
    font-weight:800;
    margin:0;
}

.page-subtitle{
    font-size:13px;
    color:#6b7280;
    margin:0;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:28px;
}

.stat-card{
    background:white;
    border:1px solid #e4e7eb;
    border-radius:16px;
    padding:20px;
}

.stat-icon{
    width:45px;
    height:45px;
    border-radius:12px;
    background:#fdf4f2;
    color:#c96b58;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    margin-bottom:12px;
}

.stat-value{
    font-size:24px;
    font-weight:800;
    color:#111827;
}

.stat-label{
    font-size:13px;
    color:#6b7280;
    margin-top:4px;
}

.card-box{
    background:white;
    border:1px solid #e4e7eb;
    border-radius:16px;
    padding:24px;
    margin-bottom:28px;
}

.card-title-custom{
    font-size:17px;
    font-weight:700;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.card-title-custom i{
    color:#c96b58;
}

.form-label{
    font-size:13px;
    font-weight:600;
    color:#374151;
    margin-bottom:7px;
}

.form-control,
.form-select{
    border-radius:12px;
    border:1px solid #e5e7eb;
    min-height:46px;
    font-size:14px;
    box-shadow:none !important;
}

.form-control:focus,
.form-select:focus{
    border-color:#111827;
}

.btn-main{
    background:#111827;
    color:white;
    border:none;
    border-radius:12px;
    padding:12px 22px;
    font-weight:600;
}

.btn-main:hover{
    background:#000;
    color:white;
}

.table thead th{
    background:#f9fafb;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:#6b7280;
    padding:14px;
}

.table tbody td{
    padding:14px;
    font-size:13px;
    vertical-align:middle;
}

.photo{
    width:48px;
    height:48px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e5e7eb;
}

.badge-status{
    border-radius:20px;
    padding:6px 10px;
    font-size:11px;
    font-weight:700;
}

.badge-actif{
    background:#dcfce7;
    color:#166534;
}

.badge-suspendu{
    background:#fee2e2;
    color:#b91c1c;
}

.badge-fonction{
    background:#fdf4f2;
    color:#c96b58;
    border-radius:20px;
    padding:6px 10px;
    font-size:11px;
    font-weight:700;
}

.readonly-note{
    background:#fff7ed;
    color:#9a3412;
    border:1px solid #fed7aa;
    border-radius:14px;
    padding:14px 18px;
    font-size:13px;
    margin-bottom:20px;
}

@media(max-width:1200px){

    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:991px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .main{
        margin-left:0;
        padding:20px;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .stats-grid{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>

<div class="sidebar">

<div class="logo">

<img
src="uploads/logo/logo.jpeg"
alt="Logo"
>

<h4>ESPACE DAF</h4>

</div>

<div class="menu">

<a href="dashboard_daf.php">
<i class="bi bi-speedometer2"></i>
Dashboard
</a>

<a href="personnel_consultation.php" class="active">
<i class="bi bi-people"></i>
Personnel
</a>

<a href="salaires.php">
<i class="bi bi-cash-stack"></i>
Salaires
</a>

<a href="paiements.php">
<i class="bi bi-credit-card"></i>
Paiements
</a>

<a href="avances.php">
<i class="bi bi-wallet2"></i>
Avances
</a>

<a href="retenues.php">
<i class="bi bi-dash-circle"></i>
Retenues
</a>

<a href="fiches_paie.php">
<i class="bi bi-file-earmark-pdf"></i>
Fiches de paie
</a>

<a href="statistiques_daf.php">
<i class="bi bi-bar-chart"></i>
Statistiques
</a>

<a href="notifications_daf.php" class="active">
    <i class="bi bi-bell"></i>
    Notifications
</a>

<a href="profil_admin.php">
<i class="bi bi-person-circle"></i>
Profil
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
Déconnexion
</a>

</div>

</div>

<div class="main">

<div class="topbar">

<div class="top-left">

<a
href="dashboard_daf.php"
class="back-btn"
>
<i class="bi bi-arrow-left"></i>
</a>

<div>

<h1 class="page-title">
Consultation du personnel
</h1>

<p class="page-subtitle">
Vue financière du personnel — lecture seule
</p>

</div>

</div>

</div>

<div class="readonly-note">
<i class="bi bi-info-circle-fill"></i>
Le DAF peut consulter les informations du personnel, les contrats et les salaires, mais il ne peut pas créer, modifier ou supprimer un agent.
</div>

<div class="stats-grid">

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-people-fill"></i>
</div>
<div class="stat-value">
<?= number_format((int)($totalPersonnel['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Total personnel
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-person-check-fill"></i>
</div>
<div class="stat-value">
<?= number_format((int)($totalActifs['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Agents actifs
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-person-x-fill"></i>
</div>
<div class="stat-value">
<?= number_format((int)($totalSuspendus['total'] ?? 0)) ?>
</div>
<div class="stat-label">
Agents suspendus
</div>
</div>

<div class="stat-card">
<div class="stat-icon">
<i class="bi bi-cash-stack"></i>
</div>
<div class="stat-value">
<?= argent($masseSalariale['total'] ?? 0) ?>
</div>
<div class="stat-label">
Masse salariale de base
</div>
</div>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-funnel-fill"></i>
Recherche et filtre
</div>

<form method="GET">

<div class="row g-3">

<div class="col-md-5">

<label class="form-label">
Recherche
</label>

<input
type="text"
name="search"
class="form-control"
placeholder="Nom, matricule, téléphone, email..."
value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
>

</div>

<div class="col-md-4">

<label class="form-label">
Fonction
</label>

<select
name="fonction"
class="form-select"
>

<option value="">
Toutes les fonctions
</option>

<?php foreach($fonctions as $f): ?>

<option
value="<?= htmlspecialchars($f['fonction'], ENT_QUOTES, 'UTF-8') ?>"
<?= $fonction === $f['fonction'] ? 'selected' : '' ?>
>
<?= htmlspecialchars($f['fonction'], ENT_QUOTES, 'UTF-8') ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-md-3 d-flex align-items-end">

<button
type="submit"
class="btn btn-main w-100"
>
<i class="bi bi-search"></i>
Rechercher
</button>

</div>

</div>

</form>

</div>

<div class="card-box">

<div class="card-title-custom">
<i class="bi bi-list-ul"></i>
Liste du personnel
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Photo</th>
<th>Personnel</th>
<th>Fonction</th>
<th>Contact</th>
<th>Contrat</th>
<th>Salaire base</th>
<th>Statut</th>
</tr>

</thead>

<tbody>

<?php if(count($personnels) > 0): ?>

<?php foreach($personnels as $p): ?>

<?php
$photo = 'uploads/photos/' . ($p['photo'] ?? '');
?>

<tr>

<td>

<?php if(!empty($p['photo']) && file_exists($photo)): ?>

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
<?= htmlspecialchars(($p['nom'] ?? '').' '.($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
Matricule :
<?= htmlspecialchars($p['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<span class="badge-fonction">
<?= htmlspecialchars($p['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</span>
</td>

<td>
<?= htmlspecialchars($p['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
<br>
<small class="text-muted">
<?= htmlspecialchars($p['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</small>
</td>

<td>
<strong>
<?= htmlspecialchars($p['contrat'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</strong>
<br>
<small class="text-muted">
Début : <?= dateFr($p['date_debut_contrat'] ?? null) ?>
<br>
Fin : <?= dateFr($p['date_fin_contrat'] ?? null) ?>
</small>
</td>

<td>
<strong>
<?= argent($p['salaire_base'] ?? 0) ?>
</strong>
</td>

<td>

<?php if(($p['statut'] ?? '') === 'actif'): ?>

<span class="badge-status badge-actif">
Actif
</span>

<?php else: ?>

<span class="badge-status badge-suspendu">
Suspendu
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td
colspan="7"
class="text-center text-muted py-4"
>
Aucun personnel trouvé.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>