<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';
require 'intelligence_daf.php';

date_default_timezone_set('Africa/Abidjan');

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:;"
);

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

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

$moisActuel  = (int)date('m');
$anneeActuel = (int)date('Y');

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$totalPersonnel = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE statut = 'actif'
")->fetch();

$totalSalairesMois = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM salaires
    WHERE mois = ?
    AND annee = ?
");
$totalSalairesMois->execute([$moisActuel, $anneeActuel]);
$totalSalairesMois = $totalSalairesMois->fetch();

$masseSalarialeMois = $pdo->prepare("
    SELECT COALESCE(SUM(salaire_net),0) AS total
    FROM salaires
    WHERE mois = ?
    AND annee = ?
");
$masseSalarialeMois->execute([$moisActuel, $anneeActuel]);
$masseSalarialeMois = $masseSalarialeMois->fetch();

$salairesPayes = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM salaires
    WHERE mois = ?
    AND annee = ?
    AND statut = 'paye'
");
$salairesPayes->execute([$moisActuel, $anneeActuel]);
$salairesPayes = $salairesPayes->fetch();

$salairesPartiels = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM salaires
    WHERE mois = ?
    AND annee = ?
    AND statut = 'partiellement_paye'
");
$salairesPartiels->execute([$moisActuel, $anneeActuel]);
$salairesPartiels = $salairesPartiels->fetch();

$salairesNonPayes = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM salaires
    WHERE mois = ?
    AND annee = ?
    AND statut = 'non_paye'
");
$salairesNonPayes->execute([$moisActuel, $anneeActuel]);
$salairesNonPayes = $salairesNonPayes->fetch();

$impayesMois = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(reste),0) AS montant
    FROM (
        SELECT
            s.id,
            s.salaire_net,
            (s.salaire_net - COALESCE(SUM(ps.montant),0)) AS reste
        FROM salaires s
        LEFT JOIN paiements_salaires ps ON ps.salaire_id = s.id
        WHERE s.mois = ?
        AND s.annee = ?
        GROUP BY s.id, s.salaire_net
        HAVING reste > 0
    ) t
");
$impayesMois->execute([$moisActuel, $anneeActuel]);
$impayesMois = $impayesMois->fetch();

$totalAvancesMois = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM avances
    WHERE MONTH(date_avance) = ?
    AND YEAR(date_avance) = ?
");
$totalAvancesMois->execute([$moisActuel, $anneeActuel]);
$totalAvancesMois = $totalAvancesMois->fetch();

$totalRetenuesMois = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM retenues
    WHERE MONTH(date_retenue) = ?
    AND YEAR(date_retenue) = ?
");
$totalRetenuesMois->execute([$moisActuel, $anneeActuel]);
$totalRetenuesMois = $totalRetenuesMois->fetch();

$totalPaiementsMois = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS total
    FROM paiements_salaires
    WHERE MONTH(date_paiement) = ?
    AND YEAR(date_paiement) = ?
");
$totalPaiementsMois->execute([$moisActuel, $anneeActuel]);
$totalPaiementsMois = $totalPaiementsMois->fetch();

$totalFichesMois = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM fiches_paie
    WHERE mois = ?
    AND annee = ?
");
$totalFichesMois->execute([$moisActuel, $anneeActuel]);
$totalFichesMois = $totalFichesMois->fetch();

$contratsExpiration = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE date_fin_contrat IS NOT NULL
    AND date_fin_contrat != '0000-00-00'
    AND date_fin_contrat BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetch();

$salairesParCategorie = $pdo->prepare("
    SELECT 
        COALESCE(p.categorie, 'Non classé') AS categorie,
        COALESCE(SUM(s.salaire_net),0) AS total
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    WHERE s.mois = ?
    AND s.annee = ?
    GROUP BY p.categorie
    ORDER BY total DESC
");
$salairesParCategorie->execute([$moisActuel, $anneeActuel]);
$salairesParCategorie = $salairesParCategorie->fetchAll();

$evolutionSalaires = $pdo->prepare("
    SELECT 
        mois,
        COALESCE(SUM(salaire_net),0) AS total
    FROM salaires
    WHERE annee = ?
    GROUP BY mois
    ORDER BY mois ASC
");
$evolutionSalaires->execute([$anneeActuel]);
$evolutionSalaires = $evolutionSalaires->fetchAll();


/* ÉVOLUTION MENSUELLE DES AVANCES */
$evolutionAvances = $pdo->prepare("
    SELECT
        MONTH(date_avance) AS mois,
        COALESCE(SUM(montant),0) AS total
    FROM avances
    WHERE YEAR(date_avance) = ?
    GROUP BY MONTH(date_avance)
    ORDER BY mois ASC
");
$evolutionAvances->execute([$anneeActuel]);
$evolutionAvances = $evolutionAvances->fetchAll();

/* ÉVOLUTION MENSUELLE DES RETENUES */
$evolutionRetenues = $pdo->prepare("
    SELECT
        MONTH(date_retenue) AS mois,
        COALESCE(SUM(montant),0) AS total
    FROM retenues
    WHERE YEAR(date_retenue) = ?
    GROUP BY MONTH(date_retenue)
    ORDER BY mois ASC
");
$evolutionRetenues->execute([$anneeActuel]);
$evolutionRetenues = $evolutionRetenues->fetchAll();

/* ÉVOLUTION MENSUELLE DES IMPAYÉS */
$evolutionImpayes = $pdo->prepare("
    SELECT
        t.mois,
        COALESCE(SUM(t.reste),0) AS total
    FROM (
        SELECT
            s.id,
            s.mois,
            s.salaire_net - COALESCE(SUM(ps.montant),0) AS reste
        FROM salaires s
        LEFT JOIN paiements_salaires ps ON ps.salaire_id = s.id
        WHERE s.annee = ?
        GROUP BY s.id, s.mois, s.salaire_net
        HAVING reste > 0
    ) t
    GROUP BY t.mois
    ORDER BY t.mois ASC
");
$evolutionImpayes->execute([$anneeActuel]);
$evolutionImpayes = $evolutionImpayes->fetchAll();

$derniersPaiements = $pdo->query("
    SELECT
        ps.montant,
        ps.mode_paiement,
        ps.reference_paiement,
        ps.date_paiement,
        p.nom,
        p.prenom
    FROM paiements_salaires ps
    INNER JOIN salaires s ON s.id = ps.salaire_id
    INNER JOIN personnels p ON p.id = s.personnel_id
    ORDER BY ps.date_paiement DESC
    LIMIT 6
")->fetchAll();

$alertesImpayes = $pdo->prepare("
    SELECT
        p.nom,
        p.prenom,
        p.matricule,
        (s.salaire_net - COALESCE(SUM(ps.montant),0)) AS reste
    FROM salaires s
    INNER JOIN personnels p ON p.id = s.personnel_id
    LEFT JOIN paiements_salaires ps ON ps.salaire_id = s.id
    WHERE s.mois = ?
    AND s.annee = ?
    GROUP BY s.id, s.salaire_net, p.nom, p.prenom, p.matricule
    HAVING reste > 0
    ORDER BY reste DESC
    LIMIT 5
");
$alertesImpayes->execute([$moisActuel, $anneeActuel]);
$alertesImpayes = $alertesImpayes->fetchAll();

$dernieresAvances = $pdo->query("
    SELECT
        a.montant,
        a.date_avance,
        a.statut,
        p.nom,
        p.prenom
    FROM avances a
    INNER JOIN personnels p ON p.id = a.personnel_id
    ORDER BY a.date_avance DESC
    LIMIT 3
")->fetchAll();

$dernieresRetenues = $pdo->query("
    SELECT
        r.montant,
        r.date_retenue,
        r.motif,
        p.nom,
        p.prenom
    FROM retenues r
    INNER JOIN personnels p ON p.id = r.personnel_id
    ORDER BY r.date_retenue DESC
    LIMIT 3
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

$chartMoisLabels = [];
$chartMoisData = [];

for($i = 1; $i <= 12; $i++){
    $chartMoisLabels[] = $moisNoms[$i];
    $montant = 0;

    foreach($evolutionSalaires as $row){
        if((int)$row['mois'] === $i){
            $montant = (float)$row['total'];
            break;
        }
    }

    $chartMoisData[] = $montant;
}

$chartAvancesData = [];
$chartRetenuesData = [];
$chartImpayesData = [];

for($i = 1; $i <= 12; $i++){
    $avance = 0;
    $retenue = 0;
    $impaye = 0;

    foreach($evolutionAvances as $row){
        if((int)$row['mois'] === $i){
            $avance = (float)$row['total'];
            break;
        }
    }

    foreach($evolutionRetenues as $row){
        if((int)$row['mois'] === $i){
            $retenue = (float)$row['total'];
            break;
        }
    }

    foreach($evolutionImpayes as $row){
        if((int)$row['mois'] === $i){
            $impaye = (float)$row['total'];
            break;
        }
    }

    $chartAvancesData[] = $avance;
    $chartRetenuesData[] = $retenue;
    $chartImpayesData[] = $impaye;
}

$chartCategorieLabels = [];
$chartCategorieData = [];

foreach($salairesParCategorie as $row){
    $chartCategorieLabels[] = $row['categorie'] ?: 'Non classé';
    $chartCategorieData[] = (float)$row['total'];
}

function argent($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function dateFr($date){
    if(empty($date)){
        return '-';
    }

    return date('d/m/Y', strtotime($date));
}

function dateHeureFr($date){
    if(empty($date)){
        return '-';
    }

    return date('d/m/Y H:i', strtotime($date));
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard DAF | Gestion Scolaire</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37,99,235,.13), transparent 28%),
                radial-gradient(circle at top right, rgba(245,158,11,.10), transparent 26%),
                var(--bg);
            color: var(--text);
            transition: all 0.2s ease;
        }

        h1,h2,h3,.stat-value,.category-value,.presence-mini-value{font-family:'Sora',sans-serif;}

        /* ============================================ */
        /* BARRE DE MENU ORIGINALE                      */
        /* ============================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: #111827;
            padding: 30px 20px;
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #1f2937;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #c96b58;
            border-radius: 4px;
        }

        .logo {
            color: white;
            margin-bottom: 35px;
            text-align: center;
        }

        .logo img {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            object-fit: cover;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .logo h4 {
            font-size: 18px;
            font-weight: 700;
            margin-top: 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #9ca3af;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(201, 107, 88, 0.15);
            color: #e5a498;
        }

        .menu a i {
            font-size: 18px;
            width: 22px;
        }


        /* ============================================ */
        /* SIDEBAR UX                                  */
        /* ============================================ */
        .sidebar-toggle {
            position: absolute;
            top: 18px;
            right: -17px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid #2d3340;
            background: #111827;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all .2s;
        }

        .sidebar-toggle:hover {
            background: #c96b58;
            border-color: #c96b58;
        }

        .menu-section {
            margin-bottom: 22px;
        }

        .menu-title {
            color: #6b7280;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            margin: 0 0 8px 14px;
            transition: opacity .2s;
        }

        body.sidebar-collapsed .sidebar {
            width: 86px;
            padding-left: 14px;
            padding-right: 14px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 86px;
        }

        body.sidebar-collapsed .logo h4,
        body.sidebar-collapsed .menu-title,
        body.sidebar-collapsed .menu a span {
            display: none;
        }

        body.sidebar-collapsed .logo img {
            width: 52px;
            height: 52px;
        }

        body.sidebar-collapsed .menu a {
            justify-content: center;
            padding: 13px 10px;
        }

        body.sidebar-collapsed .menu a i {
            width: auto;
            font-size: 20px;
        }

        /* ============================================ */
        /* PROFILE DROPDOWN                            */
        /* ============================================ */
        .profile-menu {
            position: relative;
        }

        .profile-btn {
            height: 42px;
            border-radius: 40px;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
            padding: 4px 8px 4px 4px;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: all .2s;
        }

        .profile-btn:hover {
            background: #fdf4f2;
        }

        .profile-btn img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .profile-btn i {
            font-size: 12px;
            color: #6b7280;
        }

        .profile-dropdown {
            position: absolute;
            right: 0;
            top: 52px;
            width: 245px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 16px 45px rgba(0,0,0,.14);
            overflow: hidden;
            display: none;
            z-index: 2000;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown-header {
            padding: 16px 18px;
            border-bottom: 1px solid #f0f2f5;
            background: #fafbfc;
        }

        .profile-name {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
        }

        .profile-role {
            margin-top: 3px;
            font-size: 12px;
            color: #6b7280;
        }

        .profile-dropdown-body,
        .profile-dropdown-footer {
            padding: 8px;
        }

        .profile-dropdown-body {
            border-bottom: 1px solid #f0f2f5;
        }

        .profile-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            transition: all .2s;
        }

        .profile-dropdown a:hover {
            background: #fdf4f2;
            color: #c96b58;
        }

        .profile-dropdown-footer a {
            color: #b91c1c;
        }

        .profile-dropdown-footer a:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ============================================ */
        /* FOOTER                                      */
        /* ============================================ */
        .dashboard-footer {
            margin-top: 32px;
            background: white;
            border: 1px solid #e4e7eb;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-left img {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            object-fit: cover;
        }

        .footer-secure {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #166534;
            font-weight: 700;
        }

        /* ============================================ */
        /* MAIN CONTENT                                */
        /* ============================================ */
        .main-content {
            margin-left: 260px;
            transition: margin-left .25s ease;
            padding: 32px 40px;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
            background: white;
            padding: 20px 24px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e4e7eb;
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .page-title p {
            color: #6b7280;
            font-size: 13px;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .year-badge {
            background: #fdf4f2;
            border: 1px solid #f0c9c1;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            color: #c96b58;
        }

        .dark-toggle {
            width: 40px;
            height: 40px;
            border-radius: 40px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #4b5563;
        }

        .dark-toggle:hover {
            background: #fdf4f2;
            color: #c96b58;
        }

        /* Stats Grid - design pro importé */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            position: relative;
            min-height: 150px;
            overflow: hidden;
            background: var(--card);
            border: 1px solid rgba(226,232,240,.9);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: rgba(37,99,235,.22);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            right: -34px;
            top: -38px;
            width: 122px;
            height: 122px;
            border-radius: 50%;
            background: var(--primary-soft);
            opacity: .95;
        }

        .stat-card::after { display: none; }

        .stat-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            background: var(--primary-2);
            color: #fff;
            box-shadow: 0 12px 22px rgba(37,99,235,.24);
        }

        .stat-icon i {
            font-size: 21px;
            color: #fff;
        }

        .stat-chip {
            position: relative;
            z-index: 1;
            font-size: 11px;
            color: var(--muted);
            font-weight: 800;
            background: #f8fafc;
            border: 1px solid var(--line);
            padding: 5px 9px;
            border-radius: 999px;
        }

        .stat-label {
            position: relative;
            z-index: 1;
            margin-top: 18px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .stat-value {
            position: relative;
            z-index: 1;
            font-size: 34px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
            margin-top: 5px;
            line-height: 1.05;
        }

        .stat-note {
            position: relative;
            z-index: 1;
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        .stat-card.success .stat-icon { background: var(--success); box-shadow: 0 12px 22px rgba(22,163,74,.22); }
        .stat-card.warning .stat-icon { background: var(--warning); box-shadow: 0 12px 22px rgba(217,119,6,.22); }
        .stat-card.danger .stat-icon { background: var(--danger); box-shadow: 0 12px 22px rgba(220,38,38,.20); }
        .stat-card.success::before { background:#dcfce7; }
        .stat-card.warning::before { background:#ffedd5; }
        .stat-card.danger::before { background:#fee2e2; }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e4e7eb;
            overflow: hidden;
        }

        .chart-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-header h3 i {
            color: #c96b58;
        }

        .chart-body {
            padding: 20px;
            height: 280px;
            position: relative;
        }

        /* Categories */
        .categories-section {
            background: white;
            border-radius: 16px;
            border: 1px solid #e4e7eb;
            margin-bottom: 32px;
            overflow: hidden;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        .category-card {
            padding: 24px;
            text-align: center;
            border-right: 1px solid #f0f2f5;
            transition: all 0.2s;
            cursor: pointer;
        }

        .category-card:last-child {
            border-right: none;
        }

        .category-card:hover {
            background: #fdf4f2;
        }

        .category-value {
            font-size: 34px;
            font-weight: 800;
            color: #c96b58;
            line-height: 1;
            margin-bottom: 8px;
        }

        .category-name {
            font-size: 13px;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .category-percent {
            display: inline-block;
            padding: 3px 10px;
            background: #fdf4f2;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #c96b58;
        }


        /* Presence Section */
        .presence-section {
            background: white;
            border-radius: 16px;
            border: 1px solid #e4e7eb;
            margin-bottom: 32px;
            overflow: hidden;
        }

        .presence-content {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 24px;
            padding: 20px;
        }

        .presence-mini-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .presence-mini-card {
            background: #fafbfc;
            border: 1px solid #f0f2f5;
            border-radius: 14px;
            padding: 18px;
            transition: all 0.2s;
        }

        .presence-mini-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.06);
        }

        .presence-mini-icon {
            width: 38px;
            height: 38px;
            background: #fdf4f2;
            color: #c96b58;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .presence-mini-value {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
        }

        .presence-mini-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 7px;
            font-weight: 500;
        }

        .presence-chart-box {
            height: 210px;
            position: relative;
        }

        .presence-action {
            text-decoration: none;
            background: #111827;
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            transition: .2s;
        }

        .presence-action:hover {
            background: #000;
            color: white;
        }

        /* Activity Section */
        .activity-section {
            background: white;
            border-radius: 16px;
            border: 1px solid #e4e7eb;
            overflow: hidden;
        }

        .activity-list {
            padding: 8px 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid #f0f2f5;
            transition: all 0.2s;
        }

        .activity-item:hover {
            background: #fafbfc;
            padding-left: 26px;
        }

        .activity-icon {
            width: 38px;
            height: 38px;
            background: #fdf4f2;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon i {
            font-size: 16px;
            color: #c96b58;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .activity-desc {
            font-size: 11px;
            color: #6b7280;
        }

        .activity-time {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
            flex-shrink: 0;
        }

        .empty-activities {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }


        /* Style général appliqué au reste sans changer la structure */
        .top-bar,
        .chart-card,
        .categories-section,
        .presence-section,
        .activity-section,
        .dashboard-footer {
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(226,232,240,.9);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        .top-bar {
            padding: 20px 24px;
        }

        .page-title h1 {
            font-family: 'Sora', sans-serif;
            color: var(--text);
            font-weight: 800;
            font-size: 27px;
        }

        .page-title p,
        .chart-header span {
            color: var(--muted);
        }

        .year-badge {
            background: var(--primary-soft);
            border-color: rgba(37,99,235,.18);
            color: var(--primary);
            font-weight: 800;
        }

        .dark-toggle,
        .profile-btn {
            background: #fff;
            border-color: var(--line);
            box-shadow: 0 5px 14px rgba(15,23,42,.04);
        }

        .dark-toggle:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .profile-btn:hover {
            background: #fff;
            border-color: rgba(37,99,235,.22);
        }

        .chart-header {
            padding: 17px 20px;
            border-bottom: 1px solid var(--line);
        }

        .chart-header h3 {
            color: var(--text);
            font-weight: 800;
        }

        .chart-header h3 i,
        .chart-header > i,
        .category-value,
        .activity-icon i {
            color: var(--primary) !important;
        }

        .presence-action {
            background: var(--primary);
            border-radius: 14px;
            box-shadow: 0 12px 22px rgba(37,99,235,.18);
        }

        .presence-action:hover {
            background: #0f172a;
        }

        .presence-mini-card,
        .category-card {
            border-color: var(--line);
        }

        .presence-mini-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 5px 16px rgba(15,23,42,.04);
        }

        .presence-mini-icon,
        .activity-icon,
        .category-percent {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .category-card:hover,
        .activity-item:hover {
            background: #f8fafc;
        }

        .category-percent {
            color: var(--primary);
            font-weight: 800;
        }

        .dashboard-footer {
            color: var(--muted);
        }

        /* Dark Mode */
        body.dark-mode {
            --bg:#0b1120;
            --card:#111827;
            --text:#e5e7eb;
            --muted:#94a3b8;
            --line:#243044;
            --primary-soft:rgba(37,99,235,.18);
            background:#0b1120;
        }

        body.dark-mode .top-bar,
        body.dark-mode .profile-dropdown,
        body.dark-mode .profile-dropdown-header,
        body.dark-mode .dashboard-footer,
        body.dark-mode .stat-card,
        body.dark-mode .chart-card,
        body.dark-mode .categories-section,
        body.dark-mode .activity-section,
        body.dark-mode .year-badge,
        body.dark-mode .dark-toggle {
            background: #1a202c;
            border-color: #2d3340;
        }

        body.dark-mode .page-title h1,
        body.dark-mode .profile-name,
        body.dark-mode .stat-value,
        body.dark-mode .chart-header h3,
        body.dark-mode .category-name,
        body.dark-mode .activity-title {
            color: #f0f2f5;
        }

        body.dark-mode .page-title p,
        body.dark-mode .profile-role,
        body.dark-mode .stat-label,
        body.dark-mode .activity-desc {
            color: #9ca3af;
        }

        body.dark-mode .stat-icon,
        body.dark-mode .activity-icon,
        body.dark-mode .category-percent {
            background: rgba(201, 107, 88, 0.15);
        }


        body.dark-mode .presence-section,
        body.dark-mode .presence-mini-card {
            background: #1a202c;
            border-color: #2d3340;
        }

        body.dark-mode .presence-mini-value {
            color: #f0f2f5;
        }

        body.dark-mode .presence-mini-label {
            color: #9ca3af;
        }

        body.dark-mode .presence-mini-icon {
            background: rgba(201, 107, 88, 0.15);
        }

        body.dark-mode .category-card:hover {
            background: rgba(201, 107, 88, 0.08);
        }

        body.dark-mode .activity-item:hover {
            background: #1f2937;
        }

        body.dark-mode .sidebar {
            background: #0f1419;
        }

        body.dark-mode .menu a {
            color: #9ca3af;
        }

        body.dark-mode .menu a:hover,
        body.dark-mode .menu a.active {
            background: rgba(201, 107, 88, 0.15);
            color: #e5a498;
        }


        body.dark-mode .profile-dropdown a {
            color: #e5e7eb;
        }

        body.dark-mode .profile-dropdown a:hover {
            background: rgba(201, 107, 88, 0.15);
            color: #e5a498;
        }

        body.dark-mode .profile-dropdown-footer a {
            color: #fca5a5;
        }

        body.dark-mode .dashboard-footer {
            color: #9ca3af;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-section {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 1200px) {
            .presence-content {
                grid-template-columns: 1fr;
            }

            .presence-mini-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .category-card {
                border-right: none;
                border-bottom: 1px solid #f0f2f5;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .categories-grid {
                grid-template-columns: 1fr;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .presence-mini-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .chart-card, .categories-section, .activity-section {
            animation: fadeSlideUp 0.4s ease-out forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }


        /* ============================================ */
        /* SIDEBAR FINALE — claire, arrondie, pro       */
        /* ============================================ */
        .sidebar {
            top: 20px;
            left: 20px;
            bottom: 20px;
            width: 238px;
            height: calc(100vh - 40px);
            padding: 22px 14px;
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 2px solid rgba(226,232,240,.95);
            border-radius: 28px;
            box-shadow: 0 18px 45px rgba(15,23,42,.08);
            overflow: hidden;
        }

        .logo {
            color: var(--text);
            margin-bottom: 20px;
            padding: 8px 8px 18px;
            border-bottom: 1px solid rgba(226,232,240,.9);
        }

        .logo img {
            width: 68px;
            height: 68px;
            border-radius: 22px;
            border: 3px solid #fff;
            box-shadow: 0 12px 26px rgba(15,23,42,.10);
        }

        .logo h4 {
            color: var(--text);
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.2px;
        }

        .menu {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-left: 7px;
            padding-right: 2px;
            direction: rtl;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .menu > * {
            direction: ltr;
        }

        .menu::-webkit-scrollbar {
            width: 5px;
        }

        .menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .menu::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        .menu-section {
            margin-bottom: 18px;
        }

        .menu-title {
            color: var(--muted);
            margin-left: 12px;
            font-size: 10px;
            letter-spacing: .14em;
        }

        .menu a {
            color: #475569;
            padding: 11px 12px;
            border-radius: 16px;
            margin-bottom: 7px;
            font-weight: 700;
            font-size: 13px;
            position: relative;
            border: 1px solid transparent;
            background: transparent;
        }

        .menu a i {
            width: 31px;
            height: 31px;
            min-width: 31px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: var(--muted);
            font-size: 15px;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,.9);
            border-color: rgba(226,232,240,.95);
            color: var(--primary);
            box-shadow: 0 10px 24px rgba(15,23,42,.06);
        }

        .menu a.active::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 13px;
            bottom: 13px;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #f59e0b, #d97706);
        }

        .menu a:hover i,
        .menu a.active i {
            color: #fff;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 10px 20px rgba(245,158,11,.22);
        }

        .main-content {
            margin-left: 278px;
        }

        .page-title-with-toggle {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .dashboard-menu-toggle {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--primary);
            display: grid;
            place-items: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
            transition: all .2s ease;
            flex: 0 0 auto;
        }

        .dashboard-menu-toggle:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            border-color: transparent;
            transform: translateY(-1px);
        }

        .dashboard-menu-toggle i {
            font-size: 19px;
        }

        body.sidebar-collapsed .sidebar {
            width: 82px;
            padding-left: 10px;
            padding-right: 10px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 122px;
        }

        body.sidebar-collapsed .logo {
            padding-left: 0;
            padding-right: 0;
        }

        body.sidebar-collapsed .logo img {
            width: 48px;
            height: 48px;
            border-radius: 17px;
        }

        body.sidebar-collapsed .menu {
            padding-left: 4px;
            padding-right: 0;
        }

        body.sidebar-collapsed .menu a {
            padding: 10px 7px;
            justify-content: center;
        }

        body.sidebar-collapsed .menu a i {
            width: 34px;
            height: 34px;
            min-width: 34px;
        }

        body.dark-mode .sidebar {
            background: rgba(26,32,44,.86);
            border-color: #2d3340;
            box-shadow: 0 18px 45px rgba(0,0,0,.22);
        }

        body.dark-mode .logo {
            border-bottom-color: #2d3340;
        }

        body.dark-mode .logo h4 {
            color: #f0f2f5;
        }

        body.dark-mode .menu a {
            color: #cbd5e1;
        }

        body.dark-mode .menu a i {
            background: #111827;
            color: #94a3b8;
        }

        body.dark-mode .menu a:hover,
        body.dark-mode .menu a.active {
            background: rgba(255,255,255,.06);
            border-color: #2d3340;
            color: #fbbf24;
        }

        body.dark-mode .dashboard-menu-toggle {
            background: #1a202c;
            border-color: #2d3340;
            color: #fbbf24;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: 10px;
                top: 10px;
                bottom: 10px;
                width: 218px;
                height: calc(100vh - 20px);
                transform: none !important;
                border-radius: 26px;
                padding: 18px 12px;
            }

            .main-content {
                margin-left: 238px;
                padding: 18px 14px;
            }

            body.sidebar-collapsed .sidebar {
                width: 76px;
                padding-left: 8px;
                padding-right: 8px;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 96px;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .stats-grid,
            .categories-grid,
            .presence-mini-grid {
                grid-template-columns: 1fr;
            }

            .charts-section,
            .presence-content {
                grid-template-columns: 1fr;
            }
        }


        /* ============================================ */
        /* RETOUCHES DEMANDÉES — sidebar + profil       */
        /* ============================================ */
        .sidebar {
            width: 252px !important;
            border: 2.5px solid rgba(15, 23, 42, .88) !important;
            border-radius: 32px !important;
        }

        .menu {
            height: calc(100vh - 155px) !important;
            padding-bottom: 18px !important;
        }

        .main-content {
            margin-left: 292px !important;
        }

        body.sidebar-collapsed .sidebar {
            width: 84px !important;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 124px !important;
        }

        .top-bar {
            position: relative !important;
            z-index: 9000 !important;
            overflow: visible !important;
        }

        .header-actions,
        .profile-menu {
            position: relative !important;
            z-index: 9500 !important;
        }

        .profile-dropdown {
            z-index: 99999 !important;
            top: 54px !important;
        }

        .stats-grid,
        .stat-card {
            position: relative;
            z-index: 1;
        }

        body.dark-mode .sidebar {
            border-color: rgba(255,255,255,.78) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 232px !important;
                border-radius: 30px !important;
            }

            .menu {
                height: calc(100vh - 138px) !important;
                padding-bottom: 24px !important;
            }

            .main-content {
                margin-left: 252px !important;
            }

            body.sidebar-collapsed .sidebar {
                width: 78px !important;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 98px !important;
            }
        }


        /* ============================================ */
        /* CORRECTION FINALE — DÉCONNEXION VISIBLE      */
        /* ============================================ */
        .sidebar {
            top: 10px !important;
            left: 10px !important;
            bottom: 10px !important;
            width: 280px !important;
            height: calc(100vh - 20px) !important;
            padding: 16px 16px 18px !important;
            border: 2.5px solid rgba(15, 23, 42, .92) !important;
            border-radius: 34px !important;
            overflow: hidden !important;
        }

        .logo {
            margin-bottom: 12px !important;
            padding: 6px 8px 12px !important;
        }

        .logo img {
            width: 58px !important;
            height: 58px !important;
            border-radius: 19px !important;
            margin-bottom: 8px !important;
        }

        .logo h4 {
            font-size: 14px !important;
            margin-top: 4px !important;
        }

        .menu {
            height: calc(100vh - 128px) !important;
            min-height: 430px !important;
            padding-left: 8px !important;
            padding-right: 4px !important;
            padding-bottom: 42px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        .menu-section {
            margin-bottom: 10px !important;
        }

        .menu-title {
            margin: 0 0 5px 12px !important;
            font-size: 9.5px !important;
        }

        .menu a {
            padding: 9px 11px !important;
            margin-bottom: 3px !important;
            min-height: 43px !important;
            white-space: nowrap !important;
        }

        .menu a i {
            width: 29px !important;
            height: 29px !important;
            min-width: 29px !important;
        }

        .main-content {
            margin-left: 310px !important;
        }

        body.sidebar-collapsed .sidebar {
            width: 84px !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 114px !important;
        }

        body.sidebar-collapsed .menu {
            padding-bottom: 42px !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                top: 8px !important;
                left: 8px !important;
                bottom: 8px !important;
                width: 265px !important;
                height: calc(100vh - 16px) !important;
                padding: 14px 14px 16px !important;
                border-radius: 32px !important;
            }

            .menu {
                height: calc(100vh - 116px) !important;
                min-height: 420px !important;
                padding-bottom: 46px !important;
            }

            .main-content {
                margin-left: 285px !important;
                padding: 18px 12px !important;
            }

            body.sidebar-collapsed .sidebar {
                width: 78px !important;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 96px !important;
            }
        }

    

        /* ============================================ */
        /* BLOCS SPECIFIQUES DAF - même style dashboard */
        /* ============================================ */
        .quick-grid{
            display:grid;
            grid-template-columns:repeat(5,1fr);
            gap:20px;
            margin-bottom:32px;
        }
        .quick-card{
            position:relative;
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(14px);
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            padding:20px;
            box-shadow:var(--shadow-sm);
            color:var(--text);
            text-decoration:none;
            overflow:hidden;
            transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        .quick-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:rgba(37,99,235,.22);color:var(--text);}
        .quick-card i{width:48px;height:48px;border-radius:17px;display:grid;place-items:center;background:var(--primary-soft);color:var(--primary);font-size:21px;margin-bottom:14px;}
        .quick-card strong{display:block;font-family:'Sora',sans-serif;font-size:14px;font-weight:800;color:var(--text);margin-bottom:5px;}
        .quick-card span{display:block;color:var(--muted);font-size:12px;line-height:1.45;}
        .table-card{background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:32px;}
        .table{margin:0;}
        .table thead th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);background:#f8fafc;border-bottom:1px solid var(--line);padding:14px 16px;}
        .table tbody td{padding:14px 16px;font-size:13px;color:#374151;vertical-align:middle;}
        .badge-soft{background:var(--primary-soft);color:var(--primary);border-radius:999px;padding:6px 11px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:6px;}
        .activity-list{padding:8px 0;max-height:420px;overflow-y:auto;}
        .empty-box{text-align:center;padding:38px;color:#9ca3af;}
        .finance-grid .stat-card:nth-child(2) .stat-value,.finance-grid .stat-card:nth-child(7) .stat-value,.finance-grid .stat-card:nth-child(8) .stat-value{font-size:24px;line-height:1.15;}
        body.dark-mode .quick-card,body.dark-mode .table-card{background:#1a202c;border-color:#2d3340;}
        body.dark-mode .quick-card strong,body.dark-mode .quick-card,body.dark-mode .table tbody td{color:#f0f2f5;}
        body.dark-mode .quick-card span{color:#9ca3af;}
        body.dark-mode .quick-card i,body.dark-mode .badge-soft{background:rgba(37,99,235,.18);}
        body.dark-mode .table thead th{background:#111827;color:#9ca3af;border-color:#2d3340;}
        @media(max-width:1200px){.quick-grid{grid-template-columns:repeat(2,1fr);}}
        @media(max-width:768px){.quick-grid{grid-template-columns:1fr;}}


        /* ============================================ */
        /* INTELLIGENCE FINANCIÈRE DAF                  */
        /* ============================================ */
        .smart-daf-section{
            display:grid;
            grid-template-columns:1.2fr .8fr;
            gap:24px;
            margin-bottom:32px;
        }
        .smart-daf-card{
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(14px);
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            box-shadow:var(--shadow-sm);
            overflow:hidden;
        }
        .smart-daf-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:18px 20px;
            border-bottom:1px solid var(--line);
        }
        .smart-daf-header h3{
            margin:0;
            font-size:15px;
            font-weight:800;
            color:var(--text);
            display:flex;
            align-items:center;
            gap:9px;
        }
        .smart-daf-header h3 i{
            color:var(--primary);
        }
        .smart-score{
            display:flex;
            align-items:center;
            gap:10px;
            background:var(--primary-soft);
            color:var(--primary);
            border-radius:999px;
            padding:8px 13px;
            font-size:12px;
            font-weight:900;
            white-space:nowrap;
        }
        .smart-daf-body{
            padding:18px 20px;
        }
        .smart-list{
            display:grid;
            gap:10px;
        }
        .smart-item{
            display:flex;
            gap:12px;
            align-items:flex-start;
            padding:12px 13px;
            background:#f8fafc;
            border:1px solid var(--line);
            border-radius:16px;
            color:#334155;
            font-size:13px;
            font-weight:650;
            line-height:1.45;
        }
        .smart-item i{
            width:28px;
            height:28px;
            min-width:28px;
            border-radius:10px;
            display:grid;
            place-items:center;
            background:var(--primary-soft);
            color:var(--primary);
            font-size:14px;
        }
        .smart-item.warning i{background:#ffedd5;color:#d97706;}
        .smart-item.danger i{background:#fee2e2;color:#dc2626;}
        .smart-item.success i{background:#dcfce7;color:#16a34a;}
        .smart-empty{
            text-align:center;
            color:#94a3b8;
            padding:22px 10px;
            font-size:13px;
            font-weight:650;
        }
        .smart-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:15px;
        }
        .smart-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            text-decoration:none;
            border-radius:14px;
            background:var(--primary);
            color:white;
            padding:10px 14px;
            font-size:12px;
            font-weight:900;
            box-shadow:0 12px 22px rgba(37,99,235,.18);
            transition:.2s;
        }
        .smart-btn:hover{
            background:#0f172a;
            color:white;
            transform:translateY(-1px);
        }
        .smart-btn.secondary{
            background:#fff;
            color:var(--primary);
            border:1px solid var(--line);
            box-shadow:0 8px 20px rgba(15,23,42,.05);
        }
        .smart-btn.secondary:hover{
            background:var(--primary-soft);
            color:var(--primary);
        }
        .smart-mini-grid{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:12px;
        }
        .smart-mini{
            border:1px solid var(--line);
            background:#fff;
            border-radius:18px;
            padding:14px;
        }
        .smart-mini-label{
            color:var(--muted);
            font-size:11px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.05em;
            margin-bottom:6px;
        }
        .smart-mini-value{
            font-family:'Sora',sans-serif;
            font-size:20px;
            font-weight:900;
            color:var(--text);
            line-height:1.15;
        }
        body.dark-mode .smart-daf-card,
        body.dark-mode .smart-mini{
            background:#1a202c;
            border-color:#2d3340;
        }
        body.dark-mode .smart-daf-header{
            border-color:#2d3340;
        }
        body.dark-mode .smart-daf-header h3,
        body.dark-mode .smart-mini-value{
            color:#f0f2f5;
        }
        body.dark-mode .smart-item{
            background:#111827;
            border-color:#2d3340;
            color:#e5e7eb;
        }
        body.dark-mode .smart-btn.secondary{
            background:#111827;
            border-color:#2d3340;
            color:#fbbf24;
        }
        @media(max-width:1200px){
            .smart-daf-section{grid-template-columns:1fr;}
        }
        @media(max-width:768px){
            .smart-mini-grid{grid-template-columns:1fr;}
        }


        /* GRAPHIQUES FINANCIERS INTELLIGENTS */
        .intelligent-charts{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:24px;
            margin-bottom:32px;
        }
        .intelligent-chart-card{
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(14px);
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            box-shadow:var(--shadow-sm);
            overflow:hidden;
        }
        .intelligent-chart-body{
            height:320px;
            padding:20px;
            position:relative;
        }
        .chart-insight{
            display:flex;
            align-items:center;
            gap:8px;
            margin:0 20px 18px;
            padding:11px 13px;
            border-radius:14px;
            background:var(--primary-soft);
            color:var(--primary);
            font-size:12px;
            font-weight:800;
        }
        body.dark-mode .intelligent-chart-card{
            background:#1a202c;
            border-color:#2d3340;
        }
        @media(max-width:1100px){
            .intelligent-charts{grid-template-columns:1fr;}
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
        <div class="menu-section"><div class="menu-title">Principal</div><a href="dashboard_daf.php" class="active" title="Dashboard DAF"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></div>
        <div class="menu-section"><div class="menu-title">Gestion financière</div>
            <a href="personnel.php"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="salaires.php"><i class="bi bi-cash-stack"></i><span>Salaires</span></a>
            <a href="paiements.php"><i class="bi bi-credit-card"></i><span>Paiements</span></a>
            <a href="avances.php"><i class="bi bi-wallet2"></i><span>Avances</span></a>
            <a href="retenues.php"><i class="bi bi-dash-circle"></i><span>Retenues</span></a>
            <a href="impayes.php"><i class="bi bi-exclamation-triangle"></i><span>Impayés</span></a>
            <a href="fiches_paie.php"><i class="bi bi-file-earmark-pdf"></i><span>Fiches de paie</span></a>
            <a href="statistiques_daf.php"><i class="bi bi-bar-chart"></i><span>Statistiques</span></a>
            <a href="notifications_daf.php"><i class="bi bi-bell"></i><span>Notifications</span></a>
        </div>
        <div class="menu-section"><div class="menu-title">Compte</div><a href="profil_daf.php"><i class="bi bi-person-circle"></i><span>Profil</span></a><a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a></div>
    </div>
</div>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="button" class="dark-toggle" id="sidebarToggle" title="Réduire / agrandir le menu"><i class="bi bi-list"></i></button>
                <h1>Tableau de bord DAF</h1>
            </div>
            <p>Bienvenue, <?= htmlspecialchars($_SESSION['admin'] ?? 'DAF', ENT_QUOTES, 'UTF-8') ?> · Gestion financière du personnel</p>
        </div>
        <div class="header-actions">
            <div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($annee['libelle'] ?? ($anneeActuel), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></div>
            <div class="profile-menu" id="profileMenu">
                <button type="button" class="profile-btn" id="profileBtn"><img src="assets/avatar.png" alt="Photo profil"><i class="bi bi-chevron-down"></i></button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header"><div class="profile-name"><?= htmlspecialchars($_SESSION['admin'] ?? 'DAF', ENT_QUOTES, 'UTF-8') ?></div><div class="profile-role">Direction Administrative et Financière</div></div>
                    <div class="profile-dropdown-body"><a href="profil_daf.php"><i class="bi bi-person-circle"></i>Mon profil</a><a href="dashboard_daf.php"><i class="bi bi-speedometer2"></i>Tableau de bord</a></div>
                    <div class="profile-dropdown-footer"><a href="logout.php"><i class="bi bi-box-arrow-right"></i>Se déconnecter</a></div>
                </div>
            </div>
        </div>
    </div>



    <!-- Analyse intelligente DAF -->
    <div class="smart-daf-section">
        <div class="smart-daf-card">
            <div class="smart-daf-header">
                <h3><i class="bi bi-cpu-fill"></i> Analyse financière intelligente</h3>
                <span class="smart-score">
                    <i class="bi bi-speedometer2"></i>
                    Score : <?= (int)($scoreDAF ?? 0) ?>/100 · <?= htmlspecialchars($niveauDAF ?? 'Non défini', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="smart-daf-body">
                <div class="smart-list">
                    <?php if(!empty($intelligenceDAF)): ?>
                       <?php foreach($recommandationsDAF as $rec): ?>

<div class="smart-item success" style="align-items:flex-start;">
    <i class="bi bi-check-circle-fill"></i>

    <div style="line-height:1.7;">
        <?= htmlspecialchars($rec) ?>
    </div>
</div>

<?php endforeach; ?>
                    <?php else: ?>
                        <div class="smart-empty">
                            <i class="bi bi-info-circle"></i>
                            Aucune analyse financière disponible pour le moment.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="smart-actions">
                    <a href="rapport_intelligent_daf.php" class="smart-btn">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        Générer le rapport intelligent
                    </a>
                    <a href="notifications_daf.php" class="smart-btn secondary">
                        <i class="bi bi-bell-fill"></i>
                        Voir les notifications DAF
                    </a>
                </div>
            </div>
        </div>

        <div class="smart-daf-card">
            <div class="smart-daf-header">
                <h3><i class="bi bi-shield-exclamation"></i> Alertes & anomalies</h3>
                <span class="smart-score">
                    <?= number_format(count($alertesDAF ?? []) + count($anomaliesDAF ?? [])) ?> signalement(s)
                </span>
            </div>
            <div class="smart-daf-body">
                <div class="smart-mini-grid mb-3">
                    <div class="smart-mini">
                        <div class="smart-mini-label">Alertes</div>
                        <div class="smart-mini-value"><?= number_format(count($alertesDAF ?? [])) ?></div>
                    </div>
                    <div class="smart-mini">
                        <div class="smart-mini-label">Anomalies</div>
                        <div class="smart-mini-value"><?= number_format(count($anomaliesDAF ?? [])) ?></div>
                    </div>
                </div>

                <div class="smart-list">
                    <?php if(!empty($alertesDAF) || !empty($anomaliesDAF)): ?>
                        <?php foreach(array_slice($alertesDAF ?? [], 0, 4) as $alerte): ?>
                            <div class="smart-item <?= htmlspecialchars($alerte['niveau'] ?? 'warning', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div><?= htmlspecialchars($alerte['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach(array_slice($anomaliesDAF ?? [], 0, 4) as $anomalie): ?>
                            <div class="smart-item <?= htmlspecialchars($anomalie['niveau'] ?? 'danger', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-bug-fill"></i>
                                <div><?= htmlspecialchars($anomalie['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="smart-item success">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>Aucune alerte financière majeure détectée.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="smart-daf-card" style="margin-bottom:32px;">
        <div class="smart-daf-header">
            <h3><i class="bi bi-lightbulb-fill"></i> Recommandations automatiques</h3>
            <span class="smart-score"><?= number_format(count($recommandationsDAF ?? [])) ?> conseil(s)</span>
        </div>
        <div class="smart-daf-body">
            <div class="smart-list">
                <?php if(!empty($recommandationsDAF)): ?>
                    <?php foreach($recommandationsDAF as $rec): ?>
                        <div class="smart-item">
                            <i class="bi bi-check2-circle"></i>
                            <div><?= htmlspecialchars($rec, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="smart-empty">Aucune recommandation à afficher.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="stats-grid finance-grid">
        <div class="stat-card"><div class="stat-top"><div class="stat-icon"><i class="bi bi-people-fill"></i></div><span class="stat-chip">Actifs</span></div><div class="stat-label">Personnel actif</div><div class="stat-value"><?= number_format((int)($totalPersonnel['total'] ?? 0)) ?></div><div class="stat-note">Agents actifs enregistrés</div></div>
        <div class="stat-card warning"><div class="stat-top"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><span class="stat-chip">Mois</span></div><div class="stat-label">Masse salariale</div><div class="stat-value"><?= argent($masseSalarialeMois['total'] ?? 0) ?></div><div class="stat-note">Masse enregistrée ce mois</div></div>
        <div class="stat-card success"><div class="stat-top"><div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div><span class="stat-chip">Payés</span></div><div class="stat-label">Salaires payés</div><div class="stat-value"><?= number_format((int)($salairesPayes['total'] ?? 0)) ?></div><div class="stat-note">Dossiers réglés</div></div>
        <div class="stat-card danger"><div class="stat-top"><div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div><span class="stat-chip">Non payés</span></div><div class="stat-label">Salaires non payés</div><div class="stat-value"><?= number_format((int)($salairesNonPayes['total'] ?? 0)) ?></div><div class="stat-note">À suivre</div></div>
        <div class="stat-card danger"><div class="stat-top"><div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><span class="stat-chip">Impayés</span></div><div class="stat-label">Impayés du mois</div><div class="stat-value"><?= number_format((int)($impayesMois['total'] ?? 0)) ?></div><div class="stat-note"><?= argent($impayesMois['montant'] ?? 0) ?></div></div>
        <div class="stat-card warning"><div class="stat-top"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><span class="stat-chip">Partiels</span></div><div class="stat-label">Paiements partiels</div><div class="stat-value"><?= number_format((int)($salairesPartiels['total'] ?? 0)) ?></div><div class="stat-note">Paiements incomplets</div></div>
        <div class="stat-card"><div class="stat-top"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><span class="stat-chip">Avances</span></div><div class="stat-label">Avances du mois</div><div class="stat-value"><?= argent($totalAvancesMois['total'] ?? 0) ?></div><div class="stat-note">Somme des avances</div></div>
        <div class="stat-card danger"><div class="stat-top"><div class="stat-icon"><i class="bi bi-dash-circle-fill"></i></div><span class="stat-chip">Retenues</span></div><div class="stat-label">Retenues du mois</div><div class="stat-value"><?= argent($totalRetenuesMois['total'] ?? 0) ?></div><div class="stat-note">Somme des retenues</div></div>
        <div class="stat-card"><div class="stat-top"><div class="stat-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div><span class="stat-chip">PDF</span></div><div class="stat-label">Fiches générées</div><div class="stat-value"><?= number_format((int)($totalFichesMois['total'] ?? 0)) ?></div><div class="stat-note">Ce mois</div></div>
    </div>

    <div class="quick-grid">
        <a href="salaires.php" class="quick-card"><i class="bi bi-cash-stack"></i><strong>Gestion des salaires</strong><span>Enregistrer les salaires mensuels</span></a>
        <a href="paiements.php" class="quick-card"><i class="bi bi-credit-card"></i><strong>Paiements</strong><span>Effectuer ou suivre les paiements</span></a>
        <a href="avances.php" class="quick-card"><i class="bi bi-wallet2"></i><strong>Avances</strong><span>Ajouter et suivre les avances</span></a>
        <a href="retenues.php" class="quick-card"><i class="bi bi-dash-circle"></i><strong>Retenues</strong><span>Ajouter les retenues salariales</span></a>
        <a href="impayes.php" class="quick-card"><i class="bi bi-exclamation-triangle"></i><strong>Impayés</strong><span>Voir les salaires restant à payer</span></a>
    </div>

    <div class="charts-section">
        <div class="chart-card"><div class="chart-header"><h3><i class="bi bi-bar-chart-steps"></i> Évolution salariale annuelle</h3><i class="bi bi-graph-up" style="color:#2563eb;"></i></div><div class="chart-body"><canvas id="barChart"></canvas></div></div>
        <div class="chart-card"><div class="chart-header"><h3><i class="bi bi-pie-chart-fill"></i> Répartition par catégorie</h3><i class="bi bi-percent" style="color:#2563eb;"></i></div><div class="chart-body"><canvas id="pieChart"></canvas></div></div>
    </div>


    <div class="intelligent-charts">
        <div class="intelligent-chart-card">
            <div class="chart-header">
                <h3><i class="bi bi-activity"></i> Avances et retenues mensuelles</h3>
                <span class="badge-soft">Comparaison annuelle</span>
            </div>
            <div class="intelligent-chart-body">
                <canvas id="avancesRetenuesChart"></canvas>
            </div>
            <div class="chart-insight">
                <i class="bi bi-lightbulb-fill"></i>
                Compare automatiquement les sommes avancées et retenues chaque mois.
            </div>
        </div>

        <div class="intelligent-chart-card">
            <div class="chart-header">
                <h3><i class="bi bi-exclamation-diamond-fill"></i> Évolution des impayés</h3>
                <span class="badge-soft">Risque financier</span>
            </div>
            <div class="intelligent-chart-body">
                <canvas id="impayesChart"></canvas>
            </div>
            <div class="chart-insight">
                <i class="bi bi-shield-exclamation"></i>
                Une hausse de cette courbe signale une augmentation du risque d’impayés.
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="chart-header"><h3><i class="bi bi-credit-card-2-front-fill"></i> Derniers paiements</h3><span class="badge-soft"><?= argent($totalPaiementsMois['total'] ?? 0) ?> payés ce mois</span></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Personnel</th><th>Montant</th><th>Mode</th><th>Référence</th><th>Date</th></tr></thead><tbody>
        <?php if(count($derniersPaiements) > 0): ?>
            <?php foreach($derniersPaiements as $pay): ?>
            <tr><td><strong><?= htmlspecialchars(($pay['nom'] ?? '').' '.($pay['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= argent($pay['montant'] ?? 0) ?></td><td><span class="badge-soft"><?= htmlspecialchars($pay['mode_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($pay['reference_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= dateHeureFr($pay['date_paiement'] ?? null) ?></td></tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun paiement enregistré pour le moment.</td></tr>
        <?php endif; ?>
        </tbody></table></div>
    </div>

    <div class="activity-section">
        <div class="chart-header"><h3><i class="bi bi-clock-history"></i> Activités financières récentes</h3><i class="bi bi-arrow-repeat" style="color:#2563eb;cursor:pointer;" onclick="location.reload()" title="Rafraîchir"></i></div>
        <div class="activity-list">
            <?php if(count($alertesImpayes) === 0 && count($dernieresAvances) === 0 && count($dernieresRetenues) === 0 && count($derniersPaiements) === 0): ?><div class="empty-box"><i class="bi bi-inbox" style="font-size:32px;"></i><p style="margin-top:12px;">Aucune activité financière récente.</p><p style="font-size:12px;">Les avances, retenues et paiements apparaîtront ici.</p></div><?php endif; ?>
            <?php foreach($alertesImpayes as $impaye): ?><div class="activity-item"><div class="activity-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="activity-content"><div class="activity-title">Impayé : <?= htmlspecialchars(($impaye['nom'] ?? '').' '.($impaye['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div><div class="activity-desc">Reste à payer : <?= argent($impaye['reste'] ?? 0) ?></div></div><div class="activity-time">Ce mois</div></div><?php endforeach; ?>
            <?php foreach($derniersPaiements as $pay): ?><div class="activity-item"><div class="activity-icon"><i class="bi bi-credit-card-fill"></i></div><div class="activity-content"><div class="activity-title">Paiement effectué à <?= htmlspecialchars(($pay['nom'] ?? '').' '.($pay['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div><div class="activity-desc"><?= argent($pay['montant'] ?? 0) ?> · <?= htmlspecialchars($pay['mode_paiement'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div><div class="activity-time"><?= dateHeureFr($pay['date_paiement'] ?? null) ?></div></div><?php endforeach; ?>
            <?php foreach($dernieresAvances as $av): ?><div class="activity-item"><div class="activity-icon"><i class="bi bi-wallet2"></i></div><div class="activity-content"><div class="activity-title">Avance accordée à <?= htmlspecialchars(($av['nom'] ?? '').' '.($av['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div><div class="activity-desc"><?= argent($av['montant'] ?? 0) ?> · <?= htmlspecialchars($av['statut'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div><div class="activity-time"><?= dateFr($av['date_avance'] ?? null) ?></div></div><?php endforeach; ?>
            <?php foreach($dernieresRetenues as $ret): ?><div class="activity-item"><div class="activity-icon"><i class="bi bi-dash-circle-fill"></i></div><div class="activity-content"><div class="activity-title">Retenue appliquée à <?= htmlspecialchars(($ret['nom'] ?? '').' '.($ret['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div><div class="activity-desc"><?= argent($ret['montant'] ?? 0) ?> · <?= htmlspecialchars($ret['motif'] ?? 'Sans motif', ENT_QUOTES, 'UTF-8') ?></div></div><div class="activity-time"><?= dateFr($ret['date_retenue'] ?? null) ?></div></div><?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {type:'bar',data:{labels:<?= json_encode($chartMoisLabels, JSON_UNESCAPED_UNICODE) ?>,datasets:[{label:'Masse salariale',data:<?= json_encode($chartMoisData) ?>,backgroundColor:'#2563eb',hoverBackgroundColor:'#f59e0b',borderRadius:8,barPercentage:.65,categoryPercentage:.8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#1e2430',titleColor:'#fff',bodyColor:'#cbd5e1',padding:10,cornerRadius:8}},scales:{y:{beginAtZero:true,grid:{color:'#e2e8f0',drawBorder:false},ticks:{color:'#64748b',font:{size:11}}},x:{grid:{display:false},ticks:{color:'#64748b',font:{size:11,weight:'500'}}}}}});
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {type:'doughnut',data:{labels:<?= json_encode($chartCategorieLabels, JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode($chartCategorieData) ?>,backgroundColor:['#2563eb','#f59e0b','#0ea5e9','#64748b','#16a34a','#dc2626'],borderWidth:0,hoverOffset:10}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12,font:{size:11,weight:'500'},color:'#64748b'}},tooltip:{backgroundColor:'#1e2430',titleColor:'#fff',bodyColor:'#cbd5e1',padding:10,cornerRadius:8}}}});


    const avancesRetenuesCtx = document.getElementById('avancesRetenuesChart')?.getContext('2d');
    if(avancesRetenuesCtx){
        new Chart(avancesRetenuesCtx,{
            type:'line',
            data:{
                labels:<?= json_encode($chartMoisLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets:[
                    {
                        label:'Avances',
                        data:<?= json_encode($chartAvancesData) ?>,
                        borderColor:'#2563eb',
                        backgroundColor:'rgba(37,99,235,.12)',
                        fill:true,
                        tension:.35,
                        pointRadius:3,
                        pointHoverRadius:6
                    },
                    {
                        label:'Retenues',
                        data:<?= json_encode($chartRetenuesData) ?>,
                        borderColor:'#f59e0b',
                        backgroundColor:'rgba(245,158,11,.10)',
                        fill:true,
                        tension:.35,
                        pointRadius:3,
                        pointHoverRadius:6
                    }
                ]
            },
            options:{
                responsive:true,
                maintainAspectRatio:false,
                interaction:{mode:'index',intersect:false},
                plugins:{
                    legend:{position:'bottom',labels:{usePointStyle:true,padding:16}},
                    tooltip:{callbacks:{label:(ctx)=>ctx.dataset.label+' : '+Number(ctx.raw||0).toLocaleString('fr-FR')+' FCFA'}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{callback:(value)=>Number(value).toLocaleString('fr-FR')}},
                    x:{grid:{display:false}}
                }
            }
        });
    }

    const impayesCtx = document.getElementById('impayesChart')?.getContext('2d');
    if(impayesCtx){
        new Chart(impayesCtx,{
            type:'bar',
            data:{
                labels:<?= json_encode($chartMoisLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets:[{
                    label:'Montant impayé',
                    data:<?= json_encode($chartImpayesData) ?>,
                    backgroundColor:'rgba(220,38,38,.78)',
                    hoverBackgroundColor:'#b91c1c',
                    borderRadius:8,
                    barPercentage:.68
                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{callbacks:{label:(ctx)=>Number(ctx.raw||0).toLocaleString('fr-FR')+' FCFA'}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{callback:(value)=>Number(value).toLocaleString('fr-FR')}},
                    x:{grid:{display:false}}
                }
            }
        });
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    function setSidebarCollapsed(enabled){document.body.classList.toggle('sidebar-collapsed', enabled);localStorage.setItem('sidebar-collapsed', enabled ? 'enabled' : 'disabled');}
    if(localStorage.getItem('sidebar-collapsed') === 'enabled') setSidebarCollapsed(true);
    sidebarToggle?.addEventListener('click', function(){setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));});

    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    profileBtn?.addEventListener('click', function(e){e.stopPropagation(); profileDropdown.classList.toggle('show');});
    document.addEventListener('click', function(){profileDropdown?.classList.remove('show');});
    profileDropdown?.addEventListener('click', function(e){e.stopPropagation();});

    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = darkToggle.querySelector('i');
    function setDarkMode(enabled){if(enabled){document.body.classList.add('dark-mode');darkIcon.className='bi bi-sun-fill';}else{document.body.classList.remove('dark-mode');darkIcon.className='bi bi-moon-fill';}localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');}
    if(localStorage.getItem('dark-mode') === 'enabled') setDarkMode(true);
    darkToggle.addEventListener('click', function(){setDarkMode(!document.body.classList.contains('dark-mode'));});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
$assistantProfil = 'DAF';
include __DIR__ . '/assistant/assistant.php';
?>

</body>
</html>