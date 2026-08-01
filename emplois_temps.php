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

$professeur_id = intval($_GET['professeur_id'] ?? 0);

$annee = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1 LIMIT 1")->fetch();
$anneeFooter = $annee['libelle'] ?? date('Y');

$admin_id = $_SESSION['admin_id'] ?? 0;
$adminConnecte = null;
if($admin_id){
    $reqAdmin = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $reqAdmin->execute([$admin_id]);
    $adminConnecte = $reqAdmin->fetch();
}
$adminNomComplet = trim(($adminConnecte['nom'] ?? ($_SESSION['admin'] ?? 'Admin')) . ' ' . ($adminConnecte['prenom'] ?? ''));
$adminPhoto = $adminConnecte['photo'] ?? '';

$profs = $pdo->query("
    SELECT id, nom, prenom, matricule, matiere
    FROM personnels
    WHERE fonction = 'PROFESSEUR'
    AND statut = 'actif'
    ORDER BY nom ASC, prenom ASC
")->fetchAll();

$professeur = null;
$emplois = [];

if($professeur_id > 0){
    $reqProf = $pdo->prepare("
        SELECT *
        FROM personnels
        WHERE id = ?
        AND fonction = 'PROFESSEUR'
        LIMIT 1
    ");
    $reqProf->execute([$professeur_id]);
    $professeur = $reqProf->fetch();

    $req = $pdo->prepare("
        SELECT *
        FROM emplois_temps_professeurs
        WHERE personnel_id = ?
        ORDER BY heure_debut ASC
    ");
    $req->execute([$professeur_id]);
    $emplois = $req->fetchAll();
}

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
foreach($heures as $h){
    foreach($jours as $j){
        $planning[$h][$j] = '';
    }
}

foreach($emplois as $e){
    $plage = $e['plage_horaire'];
    $jour  = $e['jour'];

    if(isset($planning[$plage][$jour])){
        $planning[$plage][$jour] =
            '<strong>'.htmlspecialchars($e['matiere'] ?? '', ENT_QUOTES, 'UTF-8').'</strong><br>' .
            '<span class="text-muted">'.htmlspecialchars($e['classe'] ?? '', ENT_QUOTES, 'UTF-8').'</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Emplois du temps | Gestion Scolaire</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --bg:#f3f6fb;--card:#ffffff;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;
    --primary:#1d4ed8;--primary-2:#2563eb;--primary-soft:#dbeafe;--gold:#f59e0b;
    --success:#16a34a;--warning:#d97706;--danger:#dc2626;--info:#0ea5e9;
    --shadow:0 18px 45px rgba(15,23,42,.08);--shadow-sm:0 8px 24px rgba(15,23,42,.06);--radius:22px;
}
body{
    font-family:'Inter',sans-serif;
    background:radial-gradient(circle at top left, rgba(37,99,235,.13), transparent 28%),
               radial-gradient(circle at top right, rgba(245,158,11,.10), transparent 26%),var(--bg);
    color:var(--text);transition:all .2s ease;overflow-x:hidden;
}
h1,h2,h3,.page-title,.edt-table th,.prof-info strong{font-family:'Sora',sans-serif}
.sidebar{
    position:fixed;top:10px;left:10px;bottom:10px;width:280px;height:calc(100vh - 20px);
    padding:16px 16px 18px;background:rgba(255,255,255,.82);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    border:2.5px solid rgba(15,23,42,.92);border-radius:34px;box-shadow:var(--shadow);overflow:hidden;z-index:999;
}
.logo{color:var(--text);text-align:center;margin-bottom:12px;padding:6px 8px 12px;border-bottom:1px solid rgba(226,232,240,.9)}
.logo img{width:58px;height:58px;border-radius:19px;object-fit:cover;margin-bottom:8px;border:3px solid #fff;box-shadow:0 12px 26px rgba(15,23,42,.10)}
.logo h4{color:var(--text);font-family:'Sora',sans-serif;font-size:14px;font-weight:800;margin-top:4px}
.menu{height:calc(100vh - 128px);min-height:430px;padding-left:8px;padding-right:4px;padding-bottom:42px;overflow-y:auto;overflow-x:hidden;direction:rtl;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
.menu>*{direction:ltr}.menu::-webkit-scrollbar{width:5px}.menu::-webkit-scrollbar-track{background:transparent}.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}.menu::-webkit-scrollbar-thumb:hover{background:var(--primary)}
.menu-section{margin-bottom:10px}.menu-title{color:var(--muted);font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;margin:0 0 5px 12px}
.menu a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#475569;padding:9px 11px;border-radius:16px;margin-bottom:3px;min-height:43px;white-space:nowrap;font-weight:700;font-size:13px;position:relative;border:1px solid transparent;background:transparent;transition:.2s}
.menu a i{width:29px;height:29px;min-width:29px;border-radius:12px;display:grid;place-items:center;background:#f1f5f9;color:var(--muted);font-size:15px}
.menu a:hover,.menu a.active{background:rgba(255,255,255,.9);border-color:rgba(226,232,240,.95);color:var(--primary);box-shadow:0 10px 24px rgba(15,23,42,.06)}
.menu a.active::before{content:'';position:absolute;left:-8px;top:13px;bottom:13px;width:4px;border-radius:999px;background:linear-gradient(180deg,#f59e0b,#d97706)}
.menu a:hover i,.menu a.active i{color:#fff;background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 10px 20px rgba(245,158,11,.22)}
.main{margin-left:310px;padding:32px 40px;min-height:100vh;transition:margin-left .25s ease}
.topbar,.card-box,.dashboard-footer{background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);box-shadow:var(--shadow-sm)}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;gap:16px;padding:20px 24px;overflow:visible;position:relative;z-index:20}.top-left{display:flex;align-items:center;gap:13px}.page-title{font-size:27px;font-weight:800;color:var(--text);margin:0}.page-subtitle{color:var(--muted);font-size:13px;margin-top:4px}.header-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.year-badge{background:var(--primary-soft);border:1px solid rgba(37,99,235,.18);padding:8px 18px;border-radius:40px;font-size:13px;font-weight:800;color:var(--primary)}
.dashboard-menu-toggle,.dark-toggle{width:42px;height:42px;border-radius:15px;border:1px solid var(--line);background:#fff;color:var(--primary);display:grid;place-items:center;cursor:pointer;box-shadow:0 8px 20px rgba(15,23,42,.06);transition:all .2s ease;flex:0 0 auto}.dashboard-menu-toggle:hover,.dark-toggle:hover{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-color:transparent;transform:translateY(-1px)}
.btn-return-dashboard{height:42px;display:inline-flex;align-items:center;gap:8px;border-radius:15px;border:1px solid var(--line);background:#fff;color:var(--primary);padding:0 14px;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 8px 20px rgba(15,23,42,.06);transition:all .2s ease}.btn-return-dashboard:hover{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-color:transparent;transform:translateY(-1px)}.btn-print{border-radius:13px;font-weight:800;background:var(--primary);color:#fff;border:0;padding:6px 12px}.btn-print:hover{background:#0f172a;color:#fff}.print-title{display:none}

.card-box{padding:24px;margin-bottom:25px}.form-label{font-size:13px;font-weight:800;color:var(--text);margin-bottom:7px}.form-select{border-radius:14px;border:1px solid var(--line);min-height:48px;font-size:14px;box-shadow:none!important}.form-select:focus{border-color:var(--primary)}.btn-main{background:var(--primary);color:white;border:none;border-radius:14px;padding:12px 18px;font-weight:700;box-shadow:0 12px 22px rgba(37,99,235,.18)}.btn-main:hover{background:#0f172a;color:white;transform:translateY(-1px)}
.prof-info{background:#f8fafc;border:1px solid var(--line);border-radius:18px;padding:17px 20px;margin-bottom:20px;color:#334155}.edt-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border-radius:18px;border:1px solid var(--line)}.edt-table th{background:#0f172a;color:white;text-align:center;padding:15px;border-right:1px solid rgba(255,255,255,.12);font-size:13px}.edt-table th:last-child{border-right:none}.edt-table td{height:90px;text-align:center;vertical-align:middle;border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:10px;font-size:14px;background:#fff}.edt-table tr:last-child td{border-bottom:none}.edt-table td:last-child{border-right:none}.edt-table .hour{background:#f8fafc;font-weight:800;color:var(--text);width:110px}.course-cell{background:var(--primary-soft);border:1px solid rgba(37,99,235,.12);border-radius:14px;padding:10px;color:var(--text)}.empty-cell{color:#cbd5e1;font-size:12px}.btn-warning{border-radius:13px;font-weight:700}.empty-state{text-align:center;color:#94a3b8;padding:38px}.dashboard-footer{margin-top:32px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;color:var(--muted);font-size:12px;font-weight:500}.footer-left{display:flex;align-items:center;gap:10px}.footer-left img{width:30px;height:30px;border-radius:8px;object-fit:cover}.footer-secure{display:flex;align-items:center;gap:6px;color:#166534;font-weight:700}
body.sidebar-collapsed .sidebar{width:84px!important;padding-left:10px!important;padding-right:10px!important}body.sidebar-collapsed .main{margin-left:114px!important}body.sidebar-collapsed .logo h4,body.sidebar-collapsed .menu-title,body.sidebar-collapsed .menu a span{display:none}body.sidebar-collapsed .logo img{width:48px;height:48px;border-radius:17px}body.sidebar-collapsed .menu{padding-left:4px;padding-right:0;padding-bottom:42px}body.sidebar-collapsed .menu a{padding:10px 7px;justify-content:center}body.sidebar-collapsed .menu a i{width:34px;height:34px;min-width:34px}
body.dark-mode{--bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#243044;--primary-soft:rgba(37,99,235,.18);background:#0b1120}body.dark-mode .sidebar,body.dark-mode .topbar,body.dark-mode .card-box,body.dark-mode .dashboard-footer,body.dark-mode .dashboard-menu-toggle,body.dark-mode .dark-toggle{background:rgba(26,32,44,.86);border-color:#2d3340;color:#f0f2f5}body.dark-mode .sidebar{border-color:rgba(255,255,255,.78)!important}body.dark-mode .logo{border-bottom-color:#2d3340}body.dark-mode .logo h4,body.dark-mode .page-title,body.dark-mode .edt-table .hour{color:#f0f2f5}body.dark-mode .menu a{color:#cbd5e1}body.dark-mode .menu a i{background:#111827;color:#94a3b8}body.dark-mode .menu a:hover,body.dark-mode .menu a.active{background:rgba(255,255,255,.06);border-color:#2d3340;color:#fbbf24}body.dark-mode .prof-info,body.dark-mode .edt-table td,body.dark-mode .edt-table .hour{background:#111827;border-color:#2d3340;color:#e5e7eb}body.dark-mode .course-cell{background:rgba(37,99,235,.18);border-color:#243044}
@keyframes fadeSlideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}.card-box{animation:fadeSlideUp .4s ease-out forwards}
@media(max-width:900px){.sidebar{top:8px!important;left:8px!important;bottom:8px!important;width:265px!important;height:calc(100vh - 16px)!important;padding:14px 14px 16px!important;border-radius:32px!important;transform:none!important}.menu{height:calc(100vh - 116px)!important;min-height:420px!important;padding-bottom:46px!important}.main{margin-left:285px!important;padding:18px 12px!important}body.sidebar-collapsed .sidebar{width:78px!important}body.sidebar-collapsed .main{margin-left:96px!important}.topbar{flex-direction:column;align-items:flex-start}.header-actions{width:100%;flex-wrap:wrap}.table-responsive{overflow-x:auto}}

@media print{
    @page{size:A4 landscape;margin:10mm}
    body{background:#fff!important;color:#111827!important}
    .sidebar,.topbar,.card-box:first-of-type,.dashboard-footer,.no-print,.dashboard-menu-toggle,.dark-toggle,.btn-return-dashboard,.btn-print,.btn-warning{display:none!important}
    .main{margin-left:0!important;padding:0!important;width:100%!important}
    .card-box{box-shadow:none!important;border:0!important;background:#fff!important;padding:0!important;margin:0!important;animation:none!important}
    .print-title{display:block!important;text-align:center;margin-bottom:14px;border-bottom:2px solid #111827;padding-bottom:10px}
    .print-title h1{font-family:'Sora',sans-serif;font-size:22px;font-weight:800;margin:0;color:#111827!important;text-transform:uppercase}
    .print-title p{font-size:12px;margin:5px 0 0;color:#374151!important}
    .prof-info{background:#fff!important;border:1px solid #111827!important;color:#111827!important;margin-bottom:12px!important;font-size:12px!important}
    .edt-table{width:100%!important;border-collapse:collapse!important;border:1px solid #111827!important;border-radius:0!important}
    .edt-table th{background:#111827!important;color:#fff!important;border:1px solid #111827!important;padding:8px!important;font-size:11px!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .edt-table td{height:55px!important;border:1px solid #111827!important;padding:6px!important;font-size:11px!important;background:#fff!important;color:#111827!important}
    .edt-table .hour{background:#f3f4f6!important;color:#111827!important;font-weight:800!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .course-cell{background:#dbeafe!important;border:1px solid #2563eb!important;color:#111827!important;border-radius:6px!important;padding:5px!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .text-muted{color:#374151!important}
}

</style>
</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h4>GESTION ÉCOLE</h4>
    </div>

    <div class="menu">
        <div class="menu-section">
            <div class="menu-title">Principal</div>
            <a href="dashboard.php" title="Tableau de bord"><i class="bi bi-speedometer2"></i><span>Tableau de bord</span></a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Gestion scolaire</div>
            <a href="personnel.php" title="Personnel"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="presences.php" title="Présence"><i class="bi bi-check2-square"></i><span>Présence</span></a>
            <a href="emplois_temps.php" class="active" title="Emplois du temps"><i class="bi bi-calendar-week"></i><span>Emplois du temps</span></a>
            <a href="documents.php" title="Documents"><i class="bi bi-file-earmark-pdf"></i><span>Documents</span></a>
            <a href="suspendus.php" title="Suspendus"><i class="bi bi-person-x"></i><span>Suspendus</span></a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Compte</div>
            <a href="profil_admin.php" title="Profil"><i class="bi bi-person-circle"></i><span>Profil</span></a>
            <a href="notifications.php" title="Notifications"><i class="bi bi-bell"></i><span>Notifications</span></a>
            <a href="logout.php" title="Déconnexion"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a>
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
            <h1 class="page-title">Emploi du temps professeur</h1>
            <div class="page-subtitle">Sélectionnez un professeur pour afficher son emploi du temps hebdomadaire.</div>
        </div>
    </div>

    <div class="header-actions no-print">
        <a href="dashboard.php" class="btn-return-dashboard" title="Retour au tableau de bord">
            <i class="bi bi-arrow-left"></i>
            
        </a>
        <div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($annee['libelle'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></button>
    </div>
</div>

<div class="card-box">
<form method="GET">
<div class="row g-3 align-items-end">
<div class="col-md-8">
<label class="form-label">Professeur</label>
<select name="professeur_id" class="form-select" required>
<option value="0">-- Choisir un professeur --</option>
<?php foreach($profs as $prof): ?>
<option value="<?= (int)$prof['id'] ?>" <?= $professeur_id === (int)$prof['id'] ? 'selected' : '' ?>>
<?= htmlspecialchars(($prof['nom'] ?? '').' '.($prof['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
<?= !empty($prof['matiere']) ? ' - '.htmlspecialchars($prof['matiere'], ENT_QUOTES, 'UTF-8') : '' ?>
<?= !empty($prof['matricule']) ? ' - '.htmlspecialchars($prof['matricule'], ENT_QUOTES, 'UTF-8') : '' ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<button type="submit" class="btn btn-main w-100"><i class="bi bi-search"></i> Afficher l’emploi du temps</button>
</div>
</div>
</form>
</div>

<?php if($professeur): ?>
<div class="card-box print-area">
<div class="print-title">
    <h1>Emploi du temps</h1>
    <p>Imprimé le <?= date('d/m/Y H:i') ?> — Année <?= htmlspecialchars($annee['libelle'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
<div class="prof-info">
<strong>Professeur :</strong> <?= htmlspecialchars(($professeur['nom'] ?? '').' '.($professeur['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
<strong>Matière principale :</strong> <?= htmlspecialchars($professeur['matiere'] ?? '-', ENT_QUOTES, 'UTF-8') ?><br>
<strong>Matricule :</strong> <?= htmlspecialchars($professeur['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
<h5 class="fw-bold mb-0"><i class="bi bi-calendar3"></i> Tableau hebdomadaire</h5>
<div class="d-flex gap-2 flex-wrap">
    <a href="modifier_emploi_temps.php?id=<?= (int)$professeur['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Modifier</a>
    <button type="button" class="btn btn-print btn-sm" onclick="window.print()"><i class="bi bi-printer-fill"></i> Imprimer</button>
</div>
</div>

<div class="table-responsive">
<table class="edt-table">
<thead>
<tr>
<th>Heures</th>
<?php foreach($jours as $j): ?>
<th><?= htmlspecialchars($j, ENT_QUOTES, 'UTF-8') ?></th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach($heures as $h): ?>
<tr>
<td class="hour"><?= htmlspecialchars($h, ENT_QUOTES, 'UTF-8') ?></td>
<?php foreach($jours as $j): ?>
<td>
<?php if(!empty($planning[$h][$j])): ?>
<div class="course-cell"><?= $planning[$h][$j] ?></div>
<?php else: ?>
<span class="empty-cell">—</span>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php else: ?>
<div class="card-box empty-state">
<i class="bi bi-calendar-week" style="font-size:40px;"></i>
<p class="mt-3 mb-0">Veuillez choisir un professeur pour afficher son emploi du temps.</p>
</div>
<?php endif; ?>

<footer class="dashboard-footer">
    <div class="footer-left">
        <img src="uploads/logo/logo.jpeg" alt="Logo CMAK">
        <span>© <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion du personnel scolaire. Tous droits réservés.</span>
    </div>
    <div class="footer-secure"><i class="bi bi-shield-check"></i> Données sécurisées</div>
</footer>

</div>

<script>
const sidebarToggle = document.getElementById('sidebarToggle');
function setSidebarCollapsed(enabled){
    document.body.classList.toggle('sidebar-collapsed', enabled);
    localStorage.setItem('sidebar-collapsed', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('sidebar-collapsed') === 'enabled') setSidebarCollapsed(true);
sidebarToggle?.addEventListener('click', () => setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed')));

const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');
function setDarkMode(enabled){
    document.body.classList.toggle('dark-mode', enabled);
    if(darkIcon) darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('dark-mode') === 'enabled') setDarkMode(true);
darkToggle?.addEventListener('click', () => setDarkMode(!document.body.classList.contains('dark-mode')));
</script>

</body>
</html>
