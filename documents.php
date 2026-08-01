<?php

session_start([

    'cookie_httponly' => true,
    'cookie_secure' => false,
    'cookie_samesite' => 'Strict'

]);

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
    "img-src 'self' data:; " .
    "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com;"
);

if(
    !isset($_SESSION['admin']) ||
    empty($_SESSION['admin'])
){

    header('Location: login.php');
    exit();

}

if(
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 1800)
){

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

if(empty($_SESSION['token'])){

    $_SESSION['token'] = bin2hex(random_bytes(32));

}

require 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

$admin_id = $_SESSION['admin_id'] ?? 0;
$adminConnecte = null;

if($admin_id){
    $reqAdmin = $pdo->prepare("
        SELECT *
        FROM admins
        WHERE id = ?
        LIMIT 1
    ");
    $reqAdmin->execute([$admin_id]);
    $adminConnecte = $reqAdmin->fetch();
}

$adminNomComplet = trim(($adminConnecte['nom'] ?? ($_SESSION['admin'] ?? 'Admin')) . ' ' . ($adminConnecte['prenom'] ?? ''));
$adminPhoto = $adminConnecte['photo'] ?? '';

$sql = $pdo->query(

    "SELECT documents_generes.*, personnels.nom, personnels.prenom

    FROM documents_generes

    INNER JOIN personnels

    ON documents_generes.personnel_id = personnels.id

    ORDER BY documents_generes.id DESC

    LIMIT 50"

);

$documents = $sql->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Documents générés | Gestion Scolaire</title>

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

h1,h2,h3,.page-title{
    font-family:'Sora',sans-serif;
}

/* ============================================ */
/* SIDEBAR IDENTIQUE DASHBOARD                  */
/* ============================================ */

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

/* ============================================ */
/* MAIN                                         */
/* ============================================ */

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
    padding:24px;
    margin-bottom:25px;
}

.search-card{
    padding:18px 20px;
}

.form-control{
    border-radius:14px;
    border:1px solid var(--line);
    min-height:48px;
    font-size:14px;
    box-shadow:none!important;
}

.form-control:focus{
    border-color:var(--primary);
}

.search-wrapper{
    position:relative;
}

.search-wrapper i{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:var(--muted);
}

.search-wrapper input{
    padding-left:44px;
}

.table{
    margin:0;
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

.table tbody tr:last-child td{
    border-bottom:none;
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

.action-group{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}

.delete-form{
    display:inline-flex;
    margin:0;
}

.btn-doc{
    border-radius:13px;
    font-weight:700;
    min-height:34px;
}

.btn-primary{
    background:var(--primary);
    border-color:var(--primary);
}

.btn-primary:hover{
    background:#0f172a;
    border-color:#0f172a;
}

.btn-danger{
    background:var(--danger);
    border-color:var(--danger);
}

.empty-state{
    text-align:center;
    color:#94a3b8;
    padding:38px;
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
body.dark-mode .page-title{
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

body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}

body.dark-mode .table tbody tr:hover{
    background:#111827;
}

body.dark-mode .form-control{
    background:#111827;
    border-color:#2d3340;
    color:#e5e7eb;
}

body.dark-mode .form-control::placeholder{
    color:#94a3b8;
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

    .table-responsive{
        overflow-x:auto;
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

        <img
        src="uploads/logo/logo.jpeg"
        alt="Logo"
        >

        <h4>GESTION ÉCOLE</h4>

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

            <a href="presences.php" title="Présence">
                <i class="bi bi-check2-square"></i>
                <span>Présence</span>
            </a>

            <a href="emplois_temps.php" title="Emplois du temps">
                <i class="bi bi-calendar-week"></i>
                <span>Emplois du temps</span>
            </a>

            <a href="documents.php" class="active" title="Documents">
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

<!-- MAIN -->

<div class="main">

<div class="topbar">

    <div class="top-left">

        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
            <i class="bi bi-list"></i>
        </button>

        <a href="dashboard.php" class="back-btn" title="Retour au tableau de bord">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>

            <h1 class="page-title">
                Documents générés
            </h1>

            <div class="page-subtitle">
                Historique des documents PDF générés
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

<!-- RECHERCHE -->

<div class="card-box search-card">

    <div class="row">

        <div class="col-md-5">

            <div class="search-wrapper">

                <i class="bi bi-search"></i>

                <input
                type="text"
                id="searchInput"
                class="form-control"
                placeholder="Rechercher document, personnel, type..."
                autocomplete="off"
                >

            </div>

        </div>

    </div>

</div>

<!-- TABLEAU -->

<div class="card-box">

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>

<th>Personnel</th>

<th>Type document</th>

<th>Date</th>

<th>Actions</th>

</tr>

</thead>

<tbody id="tableBody">

<?php if(count($documents) > 0): ?>

<?php foreach($documents as $doc): ?>

<tr>

<td>

<strong>
<?= htmlspecialchars(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</strong>

</td>

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

<div class="action-group">

<a
href="uploads/documents/<?= urlencode(basename($doc['fichier'] ?? '')) ?>"
target="_blank"
class="btn btn-primary btn-sm btn-doc"
>

<i class="bi bi-eye"></i>

Voir PDF

</a>

<form
method="POST"
action="supprimer_document.php"
class="delete-form"
onsubmit="return confirm('Supprimer ce document ?')"
>

<input
type="hidden"
name="id"
value="<?= (int)($doc['id'] ?? 0) ?>"
>

<input
type="hidden"
name="token"
value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>"
>

<button
type="submit"
class="btn btn-danger btn-sm btn-doc"
title="Supprimer"
>

<i class="bi bi-trash"></i>

</button>

</form>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="4" class="empty-state">
    <i class="bi bi-inbox" style="font-size:32px;"></i>
    <p class="mt-3 mb-0">Aucun document généré pour le moment.</p>
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
            © <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion du personnel scolaire. Tous droits réservés.
        </span>

    </div>

    <div class="footer-secure">
        <i class="bi bi-shield-check"></i>
        Données sécurisées
    </div>

</footer>

</div>

<!-- SCRIPT RECHERCHE -->

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

const input = document.getElementById('searchInput');

const tableBody = document.getElementById('tableBody');

const rows = Array.from(tableBody.querySelectorAll('tr'));

if(input && tableBody){

    input.addEventListener('keyup', function(){

        let value = this.value.toLowerCase();

        rows.sort((a, b) => {

            let textA = a.innerText.toLowerCase();

            let textB = b.innerText.toLowerCase();

            let matchA = textA.includes(value) ? 1 : 0;

            let matchB = textB.includes(value) ? 1 : 0;

            return matchB - matchA;

        });

        rows.forEach(row => {

            let text = row.innerText.toLowerCase();

            if(text.includes(value) || value === ''){

                row.style.display = '';

            }else{

                row.style.display = 'none';

            }

            tableBody.appendChild(row);

        });

    });

}

</script>

</body>

</html>