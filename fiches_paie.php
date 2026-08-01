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

if(isset($_GET['generer'])){

    $salaire_id = intval($_GET['generer']);

    if($salaire_id > 0){

        $req = $pdo->prepare("
            SELECT
                s.*,
                p.id AS personnel_id,
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule
            FROM salaires s
            INNER JOIN personnels p ON p.id = s.personnel_id
            WHERE s.id = ?
            LIMIT 1
        ");

        $req->execute([$salaire_id]);
        $salaire = $req->fetch();

        if($salaire){

            $check = $pdo->prepare("
                SELECT id
                FROM fiches_paie
                WHERE salaire_id = ?
                LIMIT 1
            ");

            $check->execute([$salaire_id]);
            $existe = $check->fetch();

            if($existe){

                $message = "Cette fiche de paie existe déjà.";

            }else{

                if(!is_dir('uploads/fiches_paie')){
                    mkdir('uploads/fiches_paie', 0755, true);
                }

                $nomFichier = 'fiche_paie_'.$salaire['personnel_id'].'_'.$salaire['mois'].'_'.$salaire['annee'].'.html';

                $chemin = 'uploads/fiches_paie/'.$nomFichier;

                $contenu = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche de paie</title>

<style>
body{
    font-family:Arial, sans-serif;
    padding:35px;
    color:#111827;
}

.header-table{
    width:100%;
    margin-bottom:15px;
    border-collapse:collapse;
}

.header-table td{
    border:none;
    vertical-align:top;
    font-size:12px;
    line-height:1.5;
}

.left-head{
    text-align:left;
    width:40%;
    font-weight:bold;
}

.center-head{
    text-align:center;
    width:20%;
}

.right-head{
    text-align:center;
    width:40%;
    font-weight:bold;
}

.logo{
    width:90px;
    height:90px;
    object-fit:cover;
}

.title{
    text-align:center;
    margin-top:20px;
    margin-bottom:25px;
    border-top:2px solid #111827;
    border-bottom:2px solid #111827;
    padding:12px 0;
}

.title h1{
    margin:0;
    font-size:24px;
}

.title p{
    margin:5px 0 0;
    font-size:13px;
}

.box{
    border:1px solid #ddd;
    padding:18px;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

th,td{
    border:1px solid #ddd;
    padding:12px;
    text-align:left;
}

th{
    background:#f3f4f6;
}

.total{
    font-size:22px;
    font-weight:bold;
    text-align:right;
    margin-top:30px;
}

.footer{
    margin-top:60px;
    display:flex;
    justify-content:space-between;
    font-size:13px;
}

.signature{
    width:40%;
    text-align:center;
}
</style>

</head>

<body>

<table class="header-table">
<tr>

<td class="left-head">
MINISTERE DE L’EDUCATION NATIONALE<br>
DE L’ALPHABÉTISATION ET DE<br>
L’ENSEIGNEMENT TECHNIQUE<br>
--------------------<br>
DRENAET DUEKOUE<br>
------------
</td>

<td class="center-head">
<img src="../logo/logo.jpeg" class="logo" alt="Logo">
</td>

<td class="right-head">
REPUBLIQUE DE CÔTE D’IVOIRE<br>
-----------------------<br>
Union - Discipline - Travail<br>
----------------
</td>

</tr>
</table>

<div class="title">
<h1>FICHE DE PAIE</h1>
<p>Période : '.htmlspecialchars($moisNoms[(int)$salaire['mois']] ?? $salaire['mois'], ENT_QUOTES, 'UTF-8').' '.htmlspecialchars($salaire['annee'], ENT_QUOTES, 'UTF-8').'</p>
</div>

<div class="box">
<p><strong>Personnel :</strong> '.htmlspecialchars($salaire['nom'].' '.$salaire['prenom'], ENT_QUOTES, 'UTF-8').'</p>
<p><strong>Matricule :</strong> '.htmlspecialchars($salaire['matricule'] ?? '-', ENT_QUOTES, 'UTF-8').'</p>
<p><strong>Fonction :</strong> '.htmlspecialchars($salaire['fonction'] ?? '-', ENT_QUOTES, 'UTF-8').'</p>
</div>

<table>
<tr>
<th>Désignation</th>
<th>Montant</th>
</tr>

<tr>
<td>Salaire de base</td>
<td>'.number_format((float)$salaire['salaire_base'], 0, ',', ' ').' FCFA</td>
</tr>

<tr>
<td>Avance</td>
<td>'.number_format((float)$salaire['avance'], 0, ',', ' ').' FCFA</td>
</tr>

<tr>
<td>Retenue</td>
<td>'.number_format((float)$salaire['retenue'], 0, ',', ' ').' FCFA</td>
</tr>

<tr>
<th>Salaire net</th>
<th>'.number_format((float)$salaire['salaire_net'], 0, ',', ' ').' FCFA</th>
</tr>
</table>

<div class="total">
Net à payer : '.number_format((float)$salaire['salaire_net'], 0, ',', ' ').' FCFA
</div>

<div class="footer">
<div class="signature">
L’Agent<br><br><br>
Signature
</div>

<div class="signature">
Le DAF<br><br><br>
Signature et cachet
</div>
</div>

</body>
</html>
';

                file_put_contents($chemin, $contenu);

                $insert = $pdo->prepare("
                    INSERT INTO fiches_paie(
                        personnel_id,
                        salaire_id,
                        mois,
                        annee,
                        fichier_pdf
                    )
                    VALUES(?,?,?,?,?)
                ");

                $insert->execute([
                    $salaire['personnel_id'],
                    $salaire_id,
                    $salaire['mois'],
                    $salaire['annee'],
                    $nomFichier
                ]);

                $message = "Fiche de paie générée avec succès.";

            }

        }else{

            $error = "Salaire introuvable.";

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
        fp.id AS fiche_id,
        fp.fichier_pdf
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    LEFT JOIN fiches_paie fp ON fp.salaire_id = s.id
    ORDER BY s.annee DESC, s.mois DESC, s.id DESC
    LIMIT 100
")->fetchAll();

$fiches = $pdo->query("
    SELECT
        fp.*,
        p.nom,
        p.prenom,
        p.fonction,
        p.matricule
    FROM fiches_paie fp
    INNER JOIN personnels p ON p.id = fp.personnel_id
    ORDER BY fp.date_generation DESC
    LIMIT 100
")->fetchAll();

$totalFiches = $pdo->query("
    SELECT COUNT(*) AS total
    FROM fiches_paie
")->fetch();

$fichesMois = $pdo->query("
    SELECT COUNT(*) AS total
    FROM fiches_paie
    WHERE mois = MONTH(CURRENT_DATE())
    AND annee = YEAR(CURRENT_DATE())
")->fetch();

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fiches de paie | Espace DAF</title>

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
    --primary:#c96b58;
    --primary-dark:#9f4f42;
    --primary-soft:#fdf4f2;
    --primary-border:#f0c9c1;
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
        radial-gradient(circle at top left,rgba(37,99,235,.13),transparent 28%),
        radial-gradient(circle at top right,rgba(245,158,11,.10),transparent 26%),
        var(--bg);
    color:var(--text);
    transition:.2s;
    overflow-x:hidden;
}
h1,h2,h3,.page-title,.stat-value,.card-title-custom{font-family:'Sora',sans-serif;}

/* SIDEBAR DAF */
.sidebar{
    position:fixed;
    top:10px!important;
    left:10px!important;
    bottom:10px!important;
    width:280px!important;
    height:calc(100vh - 20px)!important;
    padding:16px 16px 18px!important;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border:2.5px solid rgba(15,23,42,.92)!important;
    border-radius:34px!important;
    box-shadow:var(--shadow);
    overflow:hidden!important;
    z-index:999;
}
.logo{
    color:var(--text);
    text-align:center;
    margin-bottom:12px!important;
    padding:6px 8px 12px!important;
    border-bottom:1px solid rgba(226,232,240,.9);
}
.logo img{
    width:58px!important;
    height:58px!important;
    border-radius:19px!important;
    object-fit:cover;
    margin-bottom:8px!important;
    border:3px solid #fff;
    box-shadow:0 12px 26px rgba(15,23,42,.10);
}
.logo h4{
    color:var(--text);
    font-family:'Sora',sans-serif;
    font-size:14px!important;
    font-weight:800;
    margin-top:4px!important;
}
.menu{
    height:calc(100vh - 128px)!important;
    min-height:430px!important;
    overflow-y:auto!important;
    overflow-x:hidden!important;
    padding-left:8px!important;
    padding-right:4px!important;
    padding-bottom:42px!important;
    direction:rtl;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
}
.menu>*{direction:ltr;}
.menu::-webkit-scrollbar{width:5px;}
.menu::-webkit-scrollbar-track{background:transparent;}
.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}
.menu::-webkit-scrollbar-thumb:hover{background:var(--primary);}
.menu-section{margin-bottom:10px!important;}
.menu-title{
    color:var(--muted);
    font-size:9.5px!important;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.14em;
    margin:0 0 5px 12px!important;
}
.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:#475569;
    padding:9px 11px!important;
    border-radius:16px;
    margin-bottom:3px!important;
    min-height:43px!important;
    white-space:nowrap!important;
    transition:.2s;
    font-size:13px;
    font-weight:700;
    position:relative;
    border:1px solid transparent;
    background:transparent;
}
.menu a i{
    width:29px!important;
    height:29px!important;
    min-width:29px!important;
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
    background:linear-gradient(180deg,#c96b58,#9f4f42);
}
.menu a:hover i,.menu a.active i{
    color:#fff;
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    box-shadow:0 10px 20px rgba(201,107,88,.25);
}

/* MAIN */
.main{
    margin-left:310px!important;
    transition:margin-left .25s ease;
    padding:32px 40px;
    min-height:100vh;
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
    justify-content:center;
    padding:10px 7px!important;
}
body.sidebar-collapsed .menu a i{
    width:34px!important;
    height:34px!important;
    min-width:34px!important;
}

.topbar{
    position:relative!important;
    z-index:9000!important;
    overflow:visible!important;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:32px;
    flex-wrap:wrap;
    gap:16px;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    padding:20px 24px;
    border-radius:var(--radius);
    box-shadow:var(--shadow-sm);
    border:1px solid rgba(226,232,240,.9);
}
.top-left{
    display:flex;
    align-items:center;
    gap:13px;
}
.page-title{
    color:var(--text);
    font-weight:800;
    font-size:27px;
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
    position:relative!important;
    z-index:9500!important;
}
.year-badge{
    background:var(--primary-soft);
    border:1px solid var(--primary-border);
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
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    color:#fff;
    border-color:transparent;
    transform:translateY(-1px);
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    margin-bottom:32px;
}
.stat-card{
    position:relative;
    min-height:150px;
    overflow:hidden;
    background:var(--card);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    padding:20px;
    box-shadow:var(--shadow-sm);
    transition:.22s;
}
.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:var(--shadow);
    border-color:rgba(201,107,88,.25);
}
.stat-card::before{
    content:'';
    position:absolute;
    right:-34px;
    top:-38px;
    width:122px;
    height:122px;
    border-radius:50%;
    background:var(--primary-soft);
    opacity:.95;
}
.stat-card.success::before{background:#dcfce7;}
.stat-top{
    position:relative;
    z-index:1;
    display:flex;
    align-items:center;
    justify-content:space-between;
}
.stat-icon{
    width:48px;
    height:48px;
    border-radius:17px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#c96b58,#9f4f42);
    color:#fff;
    box-shadow:0 12px 22px rgba(201,107,88,.25);
}
.stat-icon i{font-size:21px;color:#fff;}
.stat-card.success .stat-icon{
    background:var(--success);
    box-shadow:0 12px 22px rgba(22,163,74,.22);
}
.stat-chip{
    position:relative;
    z-index:1;
    font-size:11px;
    color:var(--muted);
    font-weight:800;
    background:#f8fafc;
    border:1px solid var(--line);
    padding:5px 9px;
    border-radius:999px;
}
.stat-label{
    position:relative;
    z-index:1;
    margin-top:18px;
    color:var(--muted);
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
}
.stat-value{
    position:relative;
    z-index:1;
    font-size:30px;
    font-weight:800;
    color:var(--text);
    letter-spacing:-1px;
    margin-top:5px;
    line-height:1.15;
}
.stat-note{
    position:relative;
    z-index:1;
    font-size:12px;
    color:var(--muted);
    margin-top:6px;
}

.card-box{
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    box-shadow:var(--shadow-sm);
    overflow:hidden;
    margin-bottom:32px;
}
.card-title-custom{
    font-size:15px;
    font-weight:800;
    margin:0;
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--text);
}
.card-title-custom i{color:var(--primary)!important;}
.section-header{
    padding:17px 20px;
    border-bottom:1px solid var(--line);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}
.badge-soft{
    background:var(--primary-soft);
    color:var(--primary);
    border-radius:999px;
    padding:6px 11px;
    font-size:11px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.alert{
    border-radius:16px;
    border:none;
    box-shadow:var(--shadow-sm);
}
.table{margin:0;}
.table thead th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:var(--muted);
    background:#f8fafc;
    border-bottom:1px solid var(--line);
    padding:14px 16px;
}
.table tbody td{
    padding:14px 16px;
    font-size:13px;
    color:#374151;
    vertical-align:middle;
}
.table tbody tr:hover{background:#fafbfc;}
.btn-small{
    border:none;
    border-radius:12px;
    padding:8px 12px;
    font-size:12px;
    font-weight:800;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.btn-generate{
    background:var(--primary);
    color:#fff;
    box-shadow:0 12px 22px rgba(201,107,88,.18);
}
.btn-generate:hover{
    background:#0f172a;
    color:#fff;
}
.btn-view{
    background:var(--primary-soft);
    color:var(--primary);
}
.btn-view:hover{
    background:var(--primary-border);
    color:var(--primary-dark);
}
.badge-ok,.badge-no{
    border-radius:999px;
    padding:7px 11px;
    font-size:11px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.badge-ok{background:#dcfce7;color:#166534;}
.badge-no{background:#fee2e2;color:#b91c1c;}
.empty-box{
    text-align:center;
    padding:38px!important;
    color:#9ca3af!important;
}

/* DARK MODE */
body.dark-mode{
    --bg:#0b1120;
    --card:#111827;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --line:#243044;
    background:#0b1120;
}
body.dark-mode .sidebar{
    background:rgba(26,32,44,.86);
    border-color:rgba(255,255,255,.78)!important;
    box-shadow:0 18px 45px rgba(0,0,0,.22);
}
body.dark-mode .logo{border-bottom-color:#2d3340;}
body.dark-mode .logo h4,
body.dark-mode .page-title,
body.dark-mode .stat-value,
body.dark-mode .card-title-custom{
    color:#f0f2f5;
}
body.dark-mode .menu a{color:#cbd5e1;}
body.dark-mode .menu a i{background:#111827;color:#94a3b8;}
body.dark-mode .menu a:hover,
body.dark-mode .menu a.active{
    background:rgba(201,107,88,.15);
    border-color:#2d3340;
    color:#e5a498;
}
body.dark-mode .topbar,
body.dark-mode .stat-card,
body.dark-mode .card-box,
body.dark-mode .year-badge,
body.dark-mode .dark-toggle,
body.dark-mode .back-btn,
body.dark-mode .dashboard-menu-toggle{
    background:#1a202c;
    border-color:#2d3340;
}
body.dark-mode .page-subtitle,
body.dark-mode .stat-label,
body.dark-mode .stat-note{
    color:#9ca3af;
}
body.dark-mode .table thead th{
    background:#111827;
    color:#9ca3af;
    border-color:#2d3340;
}
body.dark-mode .table tbody td{
    color:#e5e7eb;
    border-color:#2d3340;
}
body.dark-mode .table tbody tr:hover{background:#111827;}

/* RESPONSIVE */
@media(max-width:1200px){
    .main{padding:24px 28px;}
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
    body.sidebar-collapsed .sidebar{width:78px!important;}
    body.sidebar-collapsed .main{margin-left:96px!important;}
    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }
    .header-actions{
        width:100%;
        flex-wrap:wrap;
    }
    .stats-grid{
        grid-template-columns:1fr;
    }
}
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
            <a href="dashboard_daf.php" title="Dashboard DAF">
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

            <a href="paiements.php">
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

            <a href="fiches_paie.php" class="active">
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
    </div>
</div>

<div class="main">

    <div class="topbar">
        <div class="top-left">
            <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
                <i class="bi bi-list"></i>
            </button>

            <a href="dashboard_daf.php" class="back-btn" title="Retour au dashboard DAF">
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>
                <h1 class="page-title">Fiches de paie</h1>
                <p class="page-subtitle">Générer et consulter les fiches de paie du personnel</p>
            </div>
        </div>

        <div class="header-actions">
            <div class="year-badge">
                <i class="bi bi-calendar-check"></i>
                <?= htmlspecialchars(date('Y'), ENT_QUOTES, 'UTF-8') ?>
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

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                <span class="stat-chip">Total</span>
            </div>
            <div class="stat-label">Total fiches générées</div>
            <div class="stat-value"><?= number_format((int)($totalFiches['total'] ?? 0)) ?></div>
            <div class="stat-note">Toutes les fiches enregistrées</div>
        </div>

        <div class="stat-card success">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <span class="stat-chip">Mois</span>
            </div>
            <div class="stat-label">Fiches générées ce mois</div>
            <div class="stat-value"><?= number_format((int)($fichesMois['total'] ?? 0)) ?></div>
            <div class="stat-note">Période en cours</div>
        </div>
    </div>

    <div class="card-box">
        <div class="section-header">
            <h3 class="card-title-custom">
                <i class="bi bi-printer-fill"></i>
                Salaires disponibles pour fiche de paie
            </h3>
            <span class="badge-soft"><i class="bi bi-list-check"></i> <?= count($salaires) ?> salaire(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Fonction</th>
                        <th>Mois</th>
                        <th>Salaire net</th>
                        <th>Statut salaire</th>
                        <th>Fiche</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if(count($salaires) > 0): ?>
                        <?php foreach($salaires as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(($s['nom'] ?? '').' '.($s['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($s['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>

                                <td><?= htmlspecialchars($s['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                                <td>
                                    <?= htmlspecialchars($moisNoms[(int)$s['mois']] ?? $s['mois'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= htmlspecialchars($s['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td><strong><?= argent($s['salaire_net'] ?? 0) ?></strong></td>

                                <td><?= htmlspecialchars($s['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                                <td>
                                    <?php if(!empty($s['fiche_id'])): ?>
                                        <span class="badge-ok"><i class="bi bi-check-circle-fill"></i> Générée</span>
                                    <?php else: ?>
                                        <span class="badge-no"><i class="bi bi-x-circle-fill"></i> Non générée</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if(!empty($s['fiche_id'])): ?>
                                        <a href="uploads/fiches_paie/<?= htmlspecialchars($s['fichier_pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-small btn-view">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                    <?php else: ?>
                                        <a href="fiches_paie.php?generer=<?= (int)$s['id'] ?>" class="btn-small btn-generate" onclick="return confirm('Générer cette fiche de paie ?')">
                                            <i class="bi bi-printer"></i> Générer
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-box">
                                <i class="bi bi-inbox" style="font-size:32px;"></i>
                                <p style="margin-top:12px;">Aucun salaire enregistré pour générer une fiche.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-box">
        <div class="section-header">
            <h3 class="card-title-custom">
                <i class="bi bi-clock-history"></i>
                Historique des fiches de paie
            </h3>
            <span class="badge-soft"><i class="bi bi-file-earmark-pdf"></i> <?= count($fiches) ?> fiche(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Fonction</th>
                        <th>Mois</th>
                        <th>Fichier</th>
                        <th>Date génération</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if(count($fiches) > 0): ?>
                        <?php foreach($fiches as $f): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(($f['nom'] ?? '').' '.($f['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($f['matricule'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>

                                <td><?= htmlspecialchars($f['fonction'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                                <td>
                                    <?= htmlspecialchars($moisNoms[(int)$f['mois']] ?? $f['mois'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= htmlspecialchars($f['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <a href="uploads/fiches_paie/<?= htmlspecialchars($f['fichier_pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-small btn-view">
                                        <i class="bi bi-box-arrow-up-right"></i> Ouvrir
                                    </a>
                                </td>

                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($f['date_generation'])), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-box">
                                <i class="bi bi-inbox" style="font-size:32px;"></i>
                                <p style="margin-top:12px;">Aucune fiche de paie générée pour le moment.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

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
</script>

</body>
</html>
