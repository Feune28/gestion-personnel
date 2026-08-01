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

$moisActuel  = (int)date('m');
$anneeActuel = (int)date('Y');

$mois = intval($_GET['mois'] ?? $moisActuel);
$annee = intval($_GET['annee'] ?? $anneeActuel);
$search = trim($_GET['search'] ?? '');

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

$sql = "
    SELECT
        s.id,
        s.personnel_id,
        s.mois,
        s.annee,
        s.salaire_net,
        s.statut,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule,
        COALESCE(SUM(ps.montant),0) AS total_paye,
        (s.salaire_net - COALESCE(SUM(ps.montant),0)) AS reste
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    LEFT JOIN paiements_salaires ps ON ps.salaire_id = s.id
    WHERE s.mois = ?
    AND s.annee = ?
";

$params = [$mois, $annee];

if($search !== ''){
    $sql .= "
        AND (
            p.nom LIKE ?
            OR p.prenom LIKE ?
            OR p.matricule LIKE ?
            OR p.fonction LIKE ?
        )
    ";

    $like = "%".$search."%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= "
    GROUP BY
        s.id,
        s.personnel_id,
        s.mois,
        s.annee,
        s.salaire_net,
        s.statut,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    HAVING reste > 0
    ORDER BY reste DESC, p.nom ASC
";

$query = $pdo->prepare($sql);
$query->execute($params);
$impayes = $query->fetchAll();

$totalImpayes = 0;
$totalNet = 0;
$totalPaye = 0;
$nombreNonPayes = 0;
$nombrePartiels = 0;

foreach($impayes as $i){
    $totalImpayes += (float)$i['reste'];
    $totalNet += (float)$i['salaire_net'];
    $totalPaye += (float)$i['total_paye'];

    if((float)$i['total_paye'] <= 0){
        $nombreNonPayes++;
    }else{
        $nombrePartiels++;
    }
}

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Impayés | Espace DAF</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
    --bg:#f3f6fb;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#e2e8f0;
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
    background:radial-gradient(circle at top left,rgba(37,99,235,.13),transparent 28%),radial-gradient(circle at top right,rgba(245,158,11,.10),transparent 26%),var(--bg);
    color:var(--text);
    transition:.2s;
    overflow-x:hidden;
}
h1,h2,h3,.page-title,.stat-value,.card-title-custom{font-family:'Sora',sans-serif;}

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
.menu>*{direction:ltr;}
.menu::-webkit-scrollbar{width:5px;}
.menu::-webkit-scrollbar-track{background:transparent;}
.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}
.menu::-webkit-scrollbar-thumb:hover{background:var(--daf);}
.menu-section{margin-bottom:10px;}
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
.menu a:hover,.menu a.active{
    background:rgba(255,255,255,.9);
    border-color:rgba(226,232,240,.95);
    color:var(--daf);
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
    background:linear-gradient(180deg,var(--daf),var(--daf-dark));
}
.menu a:hover i,.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
    box-shadow:0 10px 20px rgba(201,107,88,.25);
}

.main{
    margin-left:310px;
    padding:32px 40px;
    min-height:100vh;
    transition:margin-left .25s ease;
}
.topbar,.card-box,.stat-card,.dashboard-footer{
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
    gap:16px;
    padding:20px 24px;
    overflow:visible;
    position:relative;
    z-index:20;
}
.top-left{display:flex;align-items:center;gap:13px;}
.page-title{font-size:27px;font-weight:800;color:var(--text);margin:0;}
.page-subtitle{color:var(--muted);font-size:13px;margin-top:4px;margin-bottom:0;}
.header-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.year-badge{
    background:var(--daf-soft);
    border:1px solid var(--daf-border);
    padding:8px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
    color:var(--daf);
}
.dashboard-menu-toggle,.dark-toggle,.back-btn{
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
.dashboard-menu-toggle:hover,.dark-toggle:hover,.back-btn:hover{
    background:linear-gradient(135deg,var(--daf),var(--daf-dark));
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;}
.stat-card{position:relative;min-height:150px;overflow:hidden;padding:20px;transition:.22s;}
.stat-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:rgba(201,107,88,.28);}
.stat-card::before{content:'';position:absolute;right:-34px;top:-38px;width:122px;height:122px;border-radius:50%;background:var(--daf-soft);opacity:.95;}
.stat-card.danger::before{background:#fee2e2;}
.stat-card.warning::before{background:#fef3c7;}
.stat-top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;}
.stat-icon{width:48px;height:48px;border-radius:17px;display:grid;place-items:center;background:linear-gradient(135deg,var(--daf),var(--daf-dark));color:#fff;box-shadow:0 12px 22px rgba(201,107,88,.24);}
.stat-icon i{font-size:21px;color:#fff;}
.stat-chip{position:relative;z-index:1;font-size:11px;color:var(--muted);font-weight:800;background:#f8fafc;border:1px solid var(--line);padding:5px 9px;border-radius:999px;}
.stat-label{position:relative;z-index:1;margin-top:18px;color:var(--muted);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;}
.stat-value{position:relative;z-index:1;font-size:24px;font-weight:800;color:var(--text);letter-spacing:-1px;margin-top:5px;line-height:1.15;word-break:break-word;}
.card-box{padding:24px;margin-bottom:28px;}
.card-title-custom{font-size:18px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:var(--text);}
.card-title-custom i{color:var(--daf);}
.form-label{font-size:13px;font-weight:800;color:var(--text);margin-bottom:7px;}
.form-control,.form-select{border-radius:14px;border:1px solid var(--line);min-height:48px;font-size:14px;box-shadow:none!important;background:#fff;}
.form-control:focus,.form-select:focus{border-color:var(--daf);box-shadow:0 0 0 .2rem rgba(201,107,88,.10)!important;}
.btn-main{background:var(--daf);color:white;border:none;border-radius:14px;padding:12px 22px;font-weight:800;box-shadow:0 12px 22px rgba(201,107,88,.22);}
.btn-main:hover{background:#111827;color:white;transform:translateY(-1px);}
.table{margin:0;}
.table thead th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);padding:14px 16px;border-bottom:1px solid var(--line);}
.table tbody td{padding:14px 16px;font-size:13px;vertical-align:middle;color:#374151;}
.table tbody tr:hover{background:#fafbfc;}
.badge-alert{border-radius:20px;padding:6px 10px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:6px;}
.badge-danger-soft{background:#fee2e2;color:#b91c1c;}
.badge-warning-soft{background:#fef3c7;color:#92400e;}
.action-link{background:var(--daf);color:white;border-radius:12px;padding:8px 12px;text-decoration:none;font-size:12px;font-weight:800;display:inline-flex;align-items:center;gap:6px;}
.action-link:hover{background:#111827;color:white;}
.empty-box{text-align:center;padding:35px;color:#9ca3af;}
.dashboard-footer{margin-top:32px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;color:var(--muted);font-size:12px;font-weight:500;}
.footer-left{display:flex;align-items:center;gap:10px;}.footer-left img{width:30px;height:30px;border-radius:8px;object-fit:cover;}.footer-secure{display:flex;align-items:center;gap:6px;color:#166534;font-weight:700;}
body.sidebar-collapsed .sidebar{width:84px!important;padding-left:10px!important;padding-right:10px!important;}
body.sidebar-collapsed .main{margin-left:114px!important;}
body.sidebar-collapsed .logo h4,body.sidebar-collapsed .menu-title,body.sidebar-collapsed .menu a span{display:none;}
body.sidebar-collapsed .logo img{width:48px;height:48px;border-radius:17px;}
body.sidebar-collapsed .menu{padding-left:4px;padding-right:0;padding-bottom:42px;}
body.sidebar-collapsed .menu a{padding:10px 7px;justify-content:center;}
body.sidebar-collapsed .menu a i{width:34px;height:34px;min-width:34px;}
body.dark-mode{--bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#2d3340;background:#0b1120;}
body.dark-mode .sidebar,body.dark-mode .topbar,body.dark-mode .card-box,body.dark-mode .stat-card,body.dark-mode .dashboard-footer,body.dark-mode .dashboard-menu-toggle,body.dark-mode .dark-toggle,body.dark-mode .back-btn{background:#1a202c;border-color:#2d3340;color:#f0f2f5;}
body.dark-mode .sidebar{border-color:rgba(255,255,255,.78)!important;}
body.dark-mode .logo{border-bottom-color:#2d3340;}
body.dark-mode .logo h4,body.dark-mode .page-title,body.dark-mode .card-title-custom,body.dark-mode .stat-value{color:#f0f2f5;}
body.dark-mode .menu a{color:#cbd5e1;}
body.dark-mode .menu a i{background:#111827;color:#94a3b8;}
body.dark-mode .menu a:hover,body.dark-mode .menu a.active{background:rgba(201,107,88,.15);border-color:#2d3340;color:#e5a498;}
body.dark-mode .form-control,body.dark-mode .form-select{background:#111827;border-color:#2d3340;color:#e5e7eb;}
body.dark-mode .table thead th{background:#111827;border-color:#2d3340;}
body.dark-mode .table tbody td{color:#e5e7eb;border-color:#2d3340;}
body.dark-mode .table tbody tr:hover{background:#111827;}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr);}.main{padding:24px 28px;}}
@media(max-width:900px){.sidebar{top:8px!important;left:8px!important;bottom:8px!important;width:265px!important;height:calc(100vh - 16px)!important;padding:14px 14px 16px!important;border-radius:32px!important;transform:none!important;}.menu{height:calc(100vh - 116px)!important;min-height:420px!important;padding-bottom:46px!important;}.main{margin-left:285px!important;padding:18px 12px!important;}body.sidebar-collapsed .sidebar{width:78px!important;}body.sidebar-collapsed .main{margin-left:96px!important;}.topbar{flex-direction:column;align-items:flex-start;}.header-actions{width:100%;flex-wrap:wrap;}.stats-grid{grid-template-columns:1fr;}.dashboard-footer{flex-direction:column;align-items:flex-start;}}
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
            <a href="dashboard_daf.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        </div>
        <div class="menu-section">
            <div class="menu-title">Gestion financière</div>
            <a href="personnel.php"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="salaires.php"><i class="bi bi-cash-stack"></i><span>Salaires</span></a>
            <a href="paiements.php"><i class="bi bi-credit-card"></i><span>Paiements</span></a>
            <a href="avances.php"><i class="bi bi-wallet2"></i><span>Avances</span></a>
            <a href="retenues.php"><i class="bi bi-dash-circle"></i><span>Retenues</span></a>
            <a href="impayes.php" class="active"><i class="bi bi-exclamation-triangle"></i><span>Impayés</span></a>
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

<div class="main">
    <div class="topbar">
        <div class="top-left">
            <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu"><i class="bi bi-list"></i></button>
            <a href="dashboard_daf.php" class="back-btn" title="Retour au dashboard DAF"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="page-title">Gestion des impayés</h1>
                <p class="page-subtitle">Suivi automatique des salaires non payés ou partiellement payés</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($moisNoms[$mois] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card danger">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div><span class="stat-chip">Total</span></div>
            <div class="stat-label">Nombre d’impayés</div>
            <div class="stat-value"><?= count($impayes) ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><span class="stat-chip">Reste</span></div>
            <div class="stat-label">Total restant à payer</div>
            <div class="stat-value"><?= argent($totalImpayes) ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div><span class="stat-chip">Non payés</span></div>
            <div class="stat-label">Non payés</div>
            <div class="stat-value"><?= number_format($nombreNonPayes) ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-top"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><span class="stat-chip">Partiels</span></div>
            <div class="stat-label">Paiements partiels</div>
            <div class="stat-value"><?= number_format($nombrePartiels) ?></div>
        </div>
    </div>

    <div class="card-box">
        <div class="card-title-custom"><i class="bi bi-funnel-fill"></i> Filtrer les impayés</div>
        <form method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Mois</label>
                    <select name="mois" class="form-select">
                        <?php foreach($moisNoms as $num => $nom): ?>
                            <option value="<?= $num ?>" <?= $num === $mois ? 'selected' : '' ?>><?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Année</label>
                    <input type="number" name="annee" class="form-control" value="<?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom, matricule, fonction..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-main w-100"><i class="bi bi-search"></i> Rechercher</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-box">
        <div class="card-title-custom"><i class="bi bi-list-ul"></i> Liste des impayés</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Fonction</th>
                        <th>Période</th>
                        <th>Salaire net</th>
                        <th>Total payé</th>
                        <th>Reste</th>
                        <th>Situation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(count($impayes) > 0): ?>
                    <?php foreach($impayes as $i): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(($i['nom'] ?? '').' '.($i['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br><small class="text-muted"><?= htmlspecialchars($i['matricule'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td><?= htmlspecialchars($i['fonction'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($moisNoms[(int)$i['mois']] ?? $i['mois'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($i['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= argent($i['salaire_net'] ?? 0) ?></strong></td>
                            <td><?= argent($i['total_paye'] ?? 0) ?></td>
                            <td><strong style="color:#b91c1c;"><?= argent($i['reste'] ?? 0) ?></strong></td>
                            <td>
                                <?php if((float)$i['total_paye'] <= 0): ?>
                                    <span class="badge-alert badge-danger-soft"><i class="bi bi-x-circle-fill"></i> Non payé</span>
                                <?php else: ?>
                                    <span class="badge-alert badge-warning-soft"><i class="bi bi-hourglass-split"></i> Partiel</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="paiements.php" class="action-link"><i class="bi bi-credit-card"></i> Payer</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-box"><i class="bi bi-check-circle" style="font-size:32px;"></i><p style="margin-top:12px;">Aucun impayé trouvé pour cette période.</p></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="dashboard-footer">
        <div class="footer-left"><img src="uploads/logo/logo.jpeg" alt="Logo"><span>Gestion financière du personnel scolaire.</span></div>
        <div class="footer-secure"><i class="bi bi-shield-check"></i> Données sécurisées</div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    if(darkIcon) darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    localStorage.setItem('dark-mode-daf', enabled ? 'enabled' : 'disabled');
}
if(localStorage.getItem('dark-mode-daf') === 'enabled') setDarkMode(true);
darkToggle?.addEventListener('click', function(){
    setDarkMode(!document.body.classList.contains('dark-mode'));
});
</script>
</body>
</html>
