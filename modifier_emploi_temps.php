<?php

session_start();

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

require 'connexion.php';

$id = intval($_GET['id'] ?? $_POST['professeur_id'] ?? 0);

if($id <= 0){
    header('Location: emplois_temps.php');
    exit();
}

/* Bloquer le DAF */
if(isset($_SESSION['admin_id'])){
    $reqAdmin = $pdo->prepare("SELECT fonction FROM admins WHERE id = ? LIMIT 1");
    $reqAdmin->execute([$_SESSION['admin_id']]);
    $admin = $reqAdmin->fetch();

    if($admin && strtoupper(trim($admin['fonction'] ?? '')) === 'DAF'){
        header('Location: emplois_temps.php');
        exit();
    }
}

$profReq = $pdo->prepare("
    SELECT *
    FROM personnels
    WHERE id = ?
    AND fonction = 'PROFESSEUR'
    LIMIT 1
");
$profReq->execute([$id]);
$professeur = $profReq->fetch();

if(!$professeur){
    die("Professeur introuvable.");
}

// Année scolaire active (pour badge topbar)
$annee = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1 LIMIT 1")->fetch();
$anneeFooter = $annee['libelle'] ?? date('Y');

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

$matieres = [
    'Math',
    'PC',
    'Français',
    'HG',
    'SVT',
    'Allemand',
    'Espagnol',
    'EDHC',
    'Philo',
    'Anglais',
    'Musique',
    'Art Plastique',
    'EPS'
];

$classes = [
    '6e1','6e2','6e3','6e4',
    '5e1','5e2','5e3','5e4',
    '4e1','4e2','4e3','4e4',
    '3e1','3e2','3e3','3e4',
    '2nd C1','2nd C2','2nd A1','2nd A2',
    '1ere D1','1ere D2','1ere C1','1ere C2','1ere A1','1ere A2',
    'Tle D1','Tle D2','Tle C1','Tle C2','Tle A1','Tle A2'
];

$message = "";
$error = "";

if(isset($_POST['save'])){

    $matiere_emploi = $_POST['matiere_emploi'] ?? [];
    $classe_emploi  = $_POST['classe_emploi'] ?? [];

    try{

        $pdo->beginTransaction();

        $delete = $pdo->prepare("
            DELETE FROM emplois_temps_professeurs
            WHERE personnel_id = ?
        ");
        $delete->execute([$id]);

        $insert = $pdo->prepare("
            INSERT INTO emplois_temps_professeurs(
                personnel_id,
                jour,
                heure_debut,
                heure_fin,
                plage_horaire,
                matiere,
                classe,
                annee_scolaire
            )
            VALUES(?,?,?,?,?,?,?,?)
        ");

        foreach($jours as $jour){

            foreach($heures as $heure){

                $matiereChoisie = trim($matiere_emploi[$jour][$heure] ?? '');
                $classeChoisie  = trim($classe_emploi[$jour][$heure] ?? '');

                if($matiereChoisie !== '' && $classeChoisie !== ''){

                    $parts = explode('-', $heure);

                    $heure_debut = str_replace('H', ':00', trim($parts[0] ?? ''));
                    $heure_fin   = str_replace('H', ':00', trim($parts[1] ?? ''));

                    $insert->execute([
                        $id,
                        $jour,
                        $heure_debut,
                        $heure_fin,
                        $heure,
                        $matiereChoisie,
                        $classeChoisie,
                        $professeur['annee_scolaire'] ?? ''
                    ]);
                }
            }
        }

        $pdo->commit();

        $message = "Emploi du temps modifié avec succès.";

    }catch(Exception $e){

        $pdo->rollBack();

        $error = "Erreur lors de la modification de l'emploi du temps.";
    }
}

$planning = [];

foreach($jours as $jour){
    foreach($heures as $heure){
        $planning[$jour][$heure] = [
            'matiere' => '',
            'classe' => ''
        ];
    }
}

$reqEDT = $pdo->prepare("
    SELECT *
    FROM emplois_temps_professeurs
    WHERE personnel_id = ?
");
$reqEDT->execute([$id]);
$emplois = $reqEDT->fetchAll();

foreach($emplois as $e){
    $jour = $e['jour'];
    $heure = $e['plage_horaire'];

    if(isset($planning[$jour][$heure])){
        $planning[$jour][$heure] = [
            'matiere' => $e['matiere'] ?? '',
            'classe' => $e['classe'] ?? ''
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Modifier emploi du temps | Gestion Scolaire</title>

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
}

h1,.page-title{
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
    transition:width .25s ease,padding .25s ease;
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
.card-box{
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
    margin-bottom:28px;
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
    text-decoration:none;
    flex:0 0 auto;
}

.sidebar-toggle:hover,
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

/* CARD BOX */

.card-box{
    padding:28px 30px;
    margin-bottom:25px;
}

.prof-info{
    background:var(--primary-soft);
    border:1px solid rgba(37,99,235,.18);
    border-radius:18px;
    padding:16px 20px;
    margin-bottom:22px;
    font-size:13.5px;
    line-height:1.9;
    color:var(--text);
}

.prof-info strong{
    color:var(--primary);
    font-weight:800;
}

/* ALERTS */

.alert-custom{
    border-radius:18px;
    padding:16px 20px;
    font-weight:700;
    font-size:14px;
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:20px;
}

.alert-custom.success{
    background:#dcfce7;
    border:1px solid #bbf7d0;
    color:#166534;
}

.alert-custom.danger{
    background:#fee2e2;
    border:1px solid #fecaca;
    color:#991b1b;
}

/* TABLE EDT */

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
}

.edt-table td{
    min-width:180px;
    height:130px;
    border:1px solid var(--line);
    padding:10px;
    vertical-align:top;
    background:#fff;
}

.edt-table .hour{
    background:var(--primary-soft);
    font-weight:800;
    color:var(--primary);
    width:110px;
    min-width:110px;
    text-align:center;
    vertical-align:middle;
    font-size:13px;
}

.form-select{
    border-radius:10px;
    font-size:13px;
    margin-bottom:8px;
    border-color:var(--line);
}

.form-select:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 .2rem rgba(37,99,235,.15);
}

/* BUTTONS */

.btn-main{
    background:var(--primary);
    color:#fff;
    border:none;
    border-radius:14px;
    padding:11px 20px;
    font-weight:800;
    font-size:14px;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
    transition:.2s;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn-main:hover{
    background:#0f172a;
    color:#fff;
    transform:translateY(-1px);
}

.btn-back{
    background:#fff;
    color:var(--text);
    border:1px solid var(--line);
    border-radius:14px;
    padding:11px 20px;
    text-decoration:none;
    font-weight:800;
    font-size:14px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:.2s;
}

.btn-back:hover{
    background:var(--primary-soft);
    color:var(--primary);
    border-color:rgba(37,99,235,.22);
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
        padding:22px 18px;
    }

    .table-responsive{
        overflow-x:auto;
    }
}
</style>

</head>

<body>

<div class="sidebar" id="sidebar">

<div class="logo">
    <img src="uploads/logo/logo.jpeg" alt="Logo">
    <h4>GESTION SCOLAIRE</h4>
</div>

<div class="menu">

<div class="menu-section">
    <div class="menu-title">Principal</div>

    <a href="dashboard.php">
        <i class="bi bi-speedometer2"></i>
        <span>Tableau de bord</span>
    </a>
</div>

<div class="menu-section">
    <div class="menu-title">Gestion scolaire</div>

    <a href="personnel.php">
        <i class="bi bi-people"></i>
        <span>Personnel</span>
    </a>

    <a href="Presence.php">
        <i class="bi bi-check2-square"></i>
        <span>Présence</span>
    </a>

    <a href="emplois_temps.php" class="active">
        <i class="bi bi-calendar-week"></i>
        <span>Emplois du temps</span>
    </a>

    <a href="suspendus.php">
        <i class="bi bi-person-x"></i>
        <span>Suspendus</span>
    </a>

    <a href="documents.php">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Documents</span>
    </a>
</div>

<div class="menu-section">
    <div class="menu-title">Compte</div>

    <a href="profil_admin.php">
        <i class="bi bi-person-circle"></i>
        <span>Mon profil</span>
    </a>

    <a href="notifications.php">
        <i class="bi bi-bell"></i>
        <span>Notifications</span>
    </a>

    <a href="logout.php">
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

<a href="emplois_temps.php?professeur_id=<?= (int)$id ?>" class="back-btn" title="Retour">
    <i class="bi bi-arrow-left"></i>
</a>

<div>
    <h1 class="page-title">Modifier emploi du temps</h1>
    <div class="page-subtitle">Modifier les cours hebdomadaires du professeur</div>
</div>

</div>

<div class="header-actions">

<div class="year-badge">
    <i class="bi bi-calendar-check"></i>
    <?= htmlspecialchars($annee['libelle'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?>
</div>

</div>

</div>

<?php if(!empty($message)): ?>

<div class="alert-custom success">
<i class="bi bi-check-circle-fill"></i>
<?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<?php if(!empty($error)): ?>

<div class="alert-custom danger">
<i class="bi bi-exclamation-triangle-fill"></i>
<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>

<?php endif; ?>

<div class="card-box">

<div class="prof-info">

<strong>Professeur :</strong>
<?= htmlspecialchars(($professeur['nom'] ?? '').' '.($professeur['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>

<br>

<strong>Matière principale :</strong>
<?= htmlspecialchars($professeur['matiere'] ?? '-', ENT_QUOTES, 'UTF-8') ?>

<br>

<strong>Matricule :</strong>
<?= htmlspecialchars($professeur['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>

</div>

<form method="POST">

<input
type="hidden"
name="professeur_id"
value="<?= (int)$id ?>"
>

<div class="table-responsive">

<table class="edt-table">

<thead>

<tr>
<th>Heures</th>

<?php foreach($jours as $jour): ?>

<th>
<?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?>
</th>

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

<select
name="matiere_emploi[<?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars($heure, ENT_QUOTES, 'UTF-8') ?>]"
class="form-select"
>

<option value="">Matière</option>

<?php foreach($matieres as $m): ?>

<option
value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>"
<?= ($planning[$jour][$heure]['matiere'] ?? '') === $m ? 'selected' : '' ?>
>
<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>
</option>

<?php endforeach; ?>

</select>

<select
name="classe_emploi[<?= htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars($heure, ENT_QUOTES, 'UTF-8') ?>]"
class="form-select"
>

<option value="">Classe</option>

<?php foreach($classes as $c): ?>

<option
value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"
<?= ($planning[$jour][$heure]['classe'] ?? '') === $c ? 'selected' : '' ?>
>
<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
</option>

<?php endforeach; ?>

</select>

</td>

<?php endforeach; ?>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="d-flex justify-content-between mt-4">

<a
href="emplois_temps.php?professeur_id=<?= (int)$id ?>"
class="btn-back"
>
<i class="bi bi-arrow-left"></i>
Retour
</a>

<button
type="submit"
name="save"
class="btn-main"
>
<i class="bi bi-check-circle"></i>
Enregistrer les modifications
</button>

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
</script>

</body>
</html>