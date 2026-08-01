<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';
require 'intelligence_de.php';

// ==============================================
// CONFIGURATION FUSEAU HORAIRE
// ==============================================
date_default_timezone_set('Africa/Abidjan'); // Changez selon votre pays
// Options: 'Europe/Paris', 'Africa/Dakar', 'Africa/Douala', 'Africa/Yaounde'

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:;");

// Session verification
if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

// Session timeout
if(isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800){
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
$_SESSION['last_activity'] = time();

// Session regeneration
if(empty($_SESSION['session_init'])){
    session_regenerate_id(true);
    $_SESSION['session_init'] = true;
}

// Current school year
$annee = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1 LIMIT 1")->fetch();

// Informations admin connecté
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
$anneeFooter = $annee['libelle'] ?? date('Y');


// Statistics
$totalPersonnel = $pdo->query("SELECT COUNT(*) as total FROM personnels")->fetch();
$actifs = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE statut='actif'")->fetch();
$suspendus = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE statut='suspendu'")->fetch();
$documents = $pdo->query("SELECT COUNT(*) as total FROM documents_generes")->fetch();

// ==============================================
// PRÉSENCES DU JOUR
// ==============================================
$datePresenceJour = date('Y-m-d');

$joursFrDashboard = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

$jourPresence = $joursFrDashboard[date('l', strtotime($datePresenceJour))] ?? '';

$presentPersonnel = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences pr
    INNER JOIN personnels p ON p.id = pr.personnel_id
    WHERE pr.date_presence = ?
    AND p.fonction <> 'PROFESSEUR'
    AND LOWER(pr.statut) IN ('present','présent')
");
$presentPersonnel->execute([$datePresenceJour]);
$presentPersonnel = $presentPersonnel->fetch();

$retardPersonnel = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences pr
    INNER JOIN personnels p ON p.id = pr.personnel_id
    WHERE pr.date_presence = ?
    AND p.fonction <> 'PROFESSEUR'
    AND LOWER(pr.statut) = 'retard'
");
$retardPersonnel->execute([$datePresenceJour]);
$retardPersonnel = $retardPersonnel->fetch();

$justifiePersonnel = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences pr
    INNER JOIN personnels p ON p.id = pr.personnel_id
    WHERE pr.date_presence = ?
    AND p.fonction <> 'PROFESSEUR'
    AND LOWER(pr.statut) IN ('justifie','justifié')
");
$justifiePersonnel->execute([$datePresenceJour]);
$justifiePersonnel = $justifiePersonnel->fetch();

$totalNonProfs = $pdo->query("
    SELECT COUNT(*) AS total
    FROM personnels
    WHERE statut = 'actif'
    AND fonction <> 'PROFESSEUR'
")->fetch();

$nonProfsRegularises = $pdo->prepare("
    SELECT COUNT(DISTINCT pr.personnel_id) AS total
    FROM presences pr
    INNER JOIN personnels p ON p.id = pr.personnel_id
    WHERE pr.date_presence = ?
    AND p.fonction <> 'PROFESSEUR'
    AND LOWER(pr.statut) IN ('present','présent','retard','justifie','justifié')
");
$nonProfsRegularises->execute([$datePresenceJour]);
$nonProfsRegularises = $nonProfsRegularises->fetch();

$presentProf = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences_professeurs
    WHERE date_presence = ?
    AND statut = 'present'
");
$presentProf->execute([$datePresenceJour]);
$presentProf = $presentProf->fetch();

$retardProf = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences_professeurs
    WHERE date_presence = ?
    AND statut = 'retard'
");
$retardProf->execute([$datePresenceJour]);
$retardProf = $retardProf->fetch();

$justifieProf = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences_professeurs
    WHERE date_presence = ?
    AND statut = 'justifie'
");
$justifieProf->execute([$datePresenceJour]);
$justifieProf = $justifieProf->fetch();

$coursPrevusProf = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM emplois_temps_professeurs e
    INNER JOIN personnels p ON p.id = e.personnel_id
    WHERE p.statut = 'actif'
    AND p.fonction = 'PROFESSEUR'
    AND e.jour = ?
");
$coursPrevusProf->execute([$jourPresence]);
$coursPrevusProf = $coursPrevusProf->fetch();

$coursRegularisesProf = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM presences_professeurs
    WHERE date_presence = ?
    AND statut IN ('present','retard','justifie')
");
$coursRegularisesProf->execute([$datePresenceJour]);
$coursRegularisesProf = $coursRegularisesProf->fetch();

$presenceJourPresent = (int)($presentPersonnel['total'] ?? 0) + (int)($presentProf['total'] ?? 0);
$presenceJourRetard = (int)($retardPersonnel['total'] ?? 0) + (int)($retardProf['total'] ?? 0);
$presenceJourJustifie = (int)($justifiePersonnel['total'] ?? 0) + (int)($justifieProf['total'] ?? 0);

$absentsNonProfs = max(0, (int)($totalNonProfs['total'] ?? 0) - (int)($nonProfsRegularises['total'] ?? 0));
$absentsProfs = max(0, (int)($coursPrevusProf['total'] ?? 0) - (int)($coursRegularisesProf['total'] ?? 0));

$presenceJourAbsent = $absentsNonProfs + $absentsProfs;


// Personnel by function
$administratif = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE fonction IN('DE', 'ADE', 'DAF', 'SECRETAIRE')")->fetch();
$pedagogique = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE fonction='PROFESSEUR'")->fetch();
$encadrement = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE fonction IN('EDUCATEUR', 'INSPECTEUR EDUCATION', 'INSPECTEUR ORIENTATION')")->fetch();
$informatique = $pdo->query("SELECT COUNT(*) as total FROM personnels WHERE fonction='INFORMATICIEN'")->fetch();

$totalCategories = ($administratif['total'] ?? 0) + ($pedagogique['total'] ?? 0) + ($encadrement['total'] ?? 0) + ($informatique['total'] ?? 0);

// ==============================================
// ACTIVITÉS RÉCENTES
// ==============================================
$recentActivities = [];

// 1. Derniers personnels ajoutés
$newPersonnel = $pdo->query("
    SELECT 
        'personnel' as type,
        CONCAT('Nouveau personnel: ', nom, ' ', prenom, ' (', fonction, ')') as action,
        'person-plus-fill' as icon,
        date_embauche as date_event
    FROM personnels 
    WHERE date_embauche IS NOT NULL AND date_embauche != '0000-00-00'
    ORDER BY date_embauche DESC 
    LIMIT 3
")->fetchAll();

// 2. Derniers documents générés
$newDocs = $pdo->query("
    SELECT 
        'document' as type,
        CONCAT('Document généré: ', type_document) as action,
        'file-earmark-pdf-fill' as icon,
        date_generation as date_event
    FROM documents_generes 
    WHERE date_generation IS NOT NULL
    ORDER BY date_generation DESC 
    LIMIT 3
")->fetchAll();

// 3. Derniers changements de statut
$statusChanges = $pdo->query("
    SELECT 
        'statut' as type,
        CONCAT('Changement statut: ', nom, ' ', prenom, ' → ', statut) as action,
        'arrow-repeat' as icon,
        date_embauche as date_event
    FROM personnels 
    WHERE statut = 'suspendu'
    ORDER BY date_embauche DESC 
    LIMIT 3
")->fetchAll();

// Fusion et tri
$allActivities = array_merge($newPersonnel, $newDocs, $statusChanges);
usort($allActivities, function($a, $b) {
    $dateA = $a['date_event'] ?? '1970-01-01';
    $dateB = $b['date_event'] ?? '1970-01-01';
    return strtotime($dateB) - strtotime($dateA);
});
$recentActivities = array_slice($allActivities, 0, 6);

// ==============================================
// FONCTION TEMPS ÉCOULÉ CORRIGÉE
// ==============================================
function timeAgo($datetime) {
    if(!$datetime || $datetime == '0000-00-00' || $datetime == '0000-00-00 00:00:00') {
        return 'Date inconnue';
    }
    
    $timestamp = strtotime($datetime);
    if($timestamp === false || $timestamp <= 0) {
        return 'Date invalide';
    }
    
    $now = time();
    $diff = $now - $timestamp;
    
    if($diff < 0) {
        return 'À venir';
    }
    if($diff < 60) {
        return 'À l\'instant';
    }
    if($diff < 3600) {
        $mins = floor($diff / 60);
        return 'Il y a ' . $mins . ' min' . ($mins > 1 ? 's' : '');
    }
    if($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Il y a ' . $hours . ' h' . ($hours > 1 ? 's' : '');
    }
    if($diff < 604800) {
        $days = floor($diff / 86400);
        return 'Il y a ' . $days . ' j' . ($days > 1 ? 's' : '');
    }
    return date('d/m/Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gestion Scolaire</title>

    <!-- Police Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js -->
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
        /* MODULE INTELLIGENT DE                       */
        /* ============================================ */
        .ai-section{
            margin-bottom:32px;
            display:grid;
            grid-template-columns:1.1fr .9fr;
            gap:22px;
        }

        .ai-card{
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(14px);
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            box-shadow:var(--shadow-sm);
            overflow:hidden;
        }

        .ai-card-header{
            padding:18px 22px;
            border-bottom:1px solid var(--line);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }

        .ai-title{
            display:flex;
            align-items:center;
            gap:10px;
            font-family:'Sora',sans-serif;
            font-size:16px;
            font-weight:800;
            color:var(--text);
            margin:0;
        }

        .ai-title i{
            color:var(--primary);
        }

        .ai-score{
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:7px 12px;
            border-radius:999px;
            background:var(--primary-soft);
            color:var(--primary);
            font-size:12px;
            font-weight:900;
        }

        .ai-body{
            padding:20px 22px;
        }

        .ai-summary{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:14px;
            margin-bottom:18px;
        }

        .ai-mini{
            border:1px solid var(--line);
            background:#fff;
            border-radius:18px;
            padding:15px;
        }

        .ai-mini-label{
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.05em;
            font-weight:900;
            color:var(--muted);
            margin-bottom:6px;
        }

        .ai-mini-value{
            font-family:'Sora',sans-serif;
            font-size:24px;
            font-weight:900;
            color:var(--text);
        }

        .ai-list{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .ai-item{
            display:flex;
            gap:11px;
            align-items:flex-start;
            padding:13px 14px;
            border-radius:16px;
            background:#f8fafc;
            border:1px solid var(--line);
            font-size:13px;
            font-weight:700;
            color:#334155;
        }

        .ai-item i{
            font-size:17px;
            margin-top:1px;
        }

        .ai-item.info i{color:var(--info)}
        .ai-item.warning i{color:var(--warning)}
        .ai-item.danger i{color:var(--danger)}
        .ai-item.success i{color:var(--success)}

        .ai-empty{
            text-align:center;
            padding:24px;
            color:var(--muted);
            font-size:13px;
            font-weight:700;
            background:#f8fafc;
            border-radius:18px;
            border:1px dashed var(--line);
        }

        .ai-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:18px;
        }

        .ai-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            text-decoration:none;
            border:none;
            border-radius:14px;
            padding:11px 15px;
            font-size:13px;
            font-weight:900;
            background:var(--primary);
            color:#fff;
            box-shadow:0 12px 22px rgba(37,99,235,.18);
            transition:.2s;
        }

        .ai-btn:hover{
            background:#0f172a;
            color:#fff;
            transform:translateY(-1px);
        }

        .ai-btn.secondary{
            background:#fff;
            color:var(--primary);
            border:1px solid var(--line);
            box-shadow:none;
        }

        .ai-btn.secondary:hover{
            background:var(--primary-soft);
            color:var(--primary);
        }

        body.dark-mode .ai-card,
        body.dark-mode .ai-mini{
            background:#1a202c;
            border-color:#2d3340;
        }

        body.dark-mode .ai-title,
        body.dark-mode .ai-mini-value{
            color:#f0f2f5;
        }

        body.dark-mode .ai-item,
        body.dark-mode .ai-empty{
            background:#111827;
            border-color:#2d3340;
            color:#cbd5e1;
        }

        body.dark-mode .ai-btn.secondary{
            background:#111827;
            border-color:#2d3340;
            color:#fbbf24;
        }

        @media(max-width:1200px){
            .ai-section{grid-template-columns:1fr;}
        }

        @media(max-width:768px){
            .ai-summary{grid-template-columns:1fr;}
        }

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

            <a href="dashboard.php" class="active" title="Tableau de bord">
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

            <a href="suspendus.php" title="Suspendus">
                <i class="bi bi-person-x"></i>
                <span>Suspendus</span>
            </a>

            <a href="documents.php" title="Documents">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>Documents</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-title">Compte</div>

            <a href="profil_admin.php" title="Mon profil">
                <i class="bi bi-person-circle"></i>
                <span>Mon profil</span>
            </a>

            <a href="logout.php" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </div>

    </div>
</div>

<!-- MAIN CONTENT -->
<main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="page-title page-title-with-toggle">
            <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h1>Tableau de bord</h1>
            <p>Bienvenue, <?= htmlspecialchars($adminNomComplet ?: 'Admin', ENT_QUOTES, 'UTF-8') ?> · Vue d'ensemble de l'établissement</p>
            </div>
        </div>

        <div class="header-actions">
            <div class="year-badge">
                <i class="bi bi-calendar-check"></i>
                <?= htmlspecialchars($annee['libelle'] ?? '2024-2025', ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="dark-toggle" id="darkToggle" title="Mode sombre">
                <i class="bi bi-moon-fill"></i>
            </div>

            <div class="profile-menu" id="profileMenu">
                <button type="button" class="profile-btn" id="profileBtn">
                    <?php if(!empty($adminPhoto) && file_exists('uploads/admins/'.$adminPhoto)): ?>
                        <img src="uploads/admins/<?= htmlspecialchars($adminPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="Photo profil">
                    <?php else: ?>
                        <img src="assets/avatar.png" alt="Photo profil">
                    <?php endif; ?>
                    <i class="bi bi-chevron-down"></i>
                </button>

                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-name">
                            <?= htmlspecialchars($adminNomComplet ?: ($_SESSION['admin'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="profile-role">
                            <?= htmlspecialchars($adminConnecte['fonction'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="profile-dropdown-body">
                        <a href="profil_admin.php">
                            <i class="bi bi-person-circle"></i>
                            Mon profil
                        </a>

                        <a href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            Tableau de bord
                        </a>
                    </div>

                    <div class="profile-dropdown-footer">
                        <a href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            Se déconnecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyse intelligente DE -->
    <section class="ai-section">
        <div class="ai-card">
            <div class="ai-card-header">
                <h3 class="ai-title"><i class="bi bi-cpu-fill"></i> Analyse intelligente</h3>
                <span class="ai-score">
                    <i class="bi bi-speedometer2"></i>
                    Score : <?= (int)($scoreIntelligence ?? 0) ?>/100 · <?= htmlspecialchars($niveauSysteme ?? 'Non évalué', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <div class="ai-body">
                <div class="ai-summary">
                    <div class="ai-mini">
                        <div class="ai-mini-label">Alertes automatiques</div>
                        <div class="ai-mini-value"><?= count($alertesAuto ?? []) ?></div>
                    </div>

                    <div class="ai-mini">
                        <div class="ai-mini-label">Anomalies détectées</div>
                        <div class="ai-mini-value"><?= count($anomalies ?? []) ?></div>
                    </div>

                    <div class="ai-mini">
                        <div class="ai-mini-label">Personnel actif</div>
                        <div class="ai-mini-value"><?= (int)($tauxActifs ?? 0) ?>%</div>
                    </div>

                    <div class="ai-mini">
                        <div class="ai-mini-label">État du système</div>
                        <div class="ai-mini-value" style="font-size:18px;"><?= htmlspecialchars($niveauSysteme ?? 'Non évalué', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="ai-list">
                    <?php if(!empty($intelligence)): ?>
                        <?php foreach(array_slice($intelligence, 0, 4) as $ia): ?>
                            <div class="ai-item <?= htmlspecialchars($ia['type'] ?? 'info', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-lightbulb-fill"></i>
                                <span><?= htmlspecialchars($ia['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ai-empty">Aucune analyse disponible pour le moment.</div>
                    <?php endif; ?>
                </div>

                <div class="ai-actions">
                    <a href="rapport_intelligent_de.php" class="ai-btn">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        Générer rapport intelligent
                    </a>

                    <a href="notifications.php" class="ai-btn secondary">
                        <i class="bi bi-bell-fill"></i>
                        Voir les notifications
                    </a>
                </div>
            </div>
        </div>

        <div class="ai-card">
            <div class="ai-card-header">
                <h3 class="ai-title"><i class="bi bi-exclamation-triangle-fill"></i> Alertes & anomalies</h3>
                <span class="ai-score"><?= count($alertesAuto ?? []) + count($anomalies ?? []) ?> signalement(s)</span>
            </div>

            <div class="ai-body">
                <div class="ai-list">
                    <?php if(!empty($alertesAuto)): ?>
                        <?php foreach(array_slice($alertesAuto, 0, 4) as $alerte): ?>
                            <div class="ai-item <?= htmlspecialchars($alerte['niveau'] ?? 'warning', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-bell-fill"></i>
                                <span><?= htmlspecialchars($alerte['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(!empty($anomalies)): ?>
                        <?php foreach(array_slice($anomalies, 0, 4) as $anomalie): ?>
                            <div class="ai-item <?= htmlspecialchars($anomalie['niveau'] ?? 'danger', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-shield-exclamation"></i>
                                <span><?= htmlspecialchars($anomalie['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(empty($alertesAuto) && empty($anomalies)): ?>
                        <div class="ai-empty">
                            <i class="bi bi-check-circle-fill"></i><br>
                            Aucune alerte critique ni anomalie détectée.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Recommandations intelligentes -->
    <section class="ai-card" style="margin-bottom:32px;">
        <div class="ai-card-header">
            <h3 class="ai-title"><i class="bi bi-stars"></i> Recommandations automatiques</h3>
        </div>
        <div class="ai-body">
            <div class="ai-list">
                <?php if(!empty($recommandations)): ?>
                    <?php foreach(array_slice($recommandations, 0, 6) as $rec): ?>
                        <div class="ai-item success">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?= htmlspecialchars($rec, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ai-empty">Aucune recommandation particulière.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <!-- Statistics Cards - design pro -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <span class="stat-chip">Global</span>
            </div>
            <div class="stat-label">Total personnel</div>
            <div class="stat-value"><?= number_format($totalPersonnel['total'] ?? 0) ?></div>
            <div class="stat-note">Tous les agents enregistrés</div>
        </div>
        <div class="stat-card success">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                <span class="stat-chip">Actifs</span>
            </div>
            <div class="stat-label">En activité</div>
            <div class="stat-value"><?= number_format($actifs['total'] ?? 0) ?></div>
            <div class="stat-note">Personnel actuellement disponible</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-person-x-fill"></i></div>
                <span class="stat-chip">Suivi</span>
            </div>
            <div class="stat-label">Suspendus</div>
            <div class="stat-value"><?= number_format($suspendus['total'] ?? 0) ?></div>
            <div class="stat-note">Dossiers à contrôler</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-top">
                <div class="stat-icon"><i class="bi bi-files"></i></div>
                <span class="stat-chip">Archives</span>
            </div>
            <div class="stat-label">Documents générés</div>
            <div class="stat-value"><?= number_format($documents['total'] ?? 0) ?></div>
            <div class="stat-note">PDF produits par le système</div>
        </div>
    </div>


    <!-- Presence Section -->
    <div class="presence-section">
        <div class="chart-header">
            <h3><i class="bi bi-check2-square"></i> Présences du jour</h3>
            <a href="presences.php" class="presence-action">
                <i class="bi bi-arrow-right-circle"></i>
                Gérer les présences
            </a>
        </div>

        <div class="presence-content">
            <div class="presence-mini-grid">
                <div class="presence-mini-card">
                    <div class="presence-mini-icon"><i class="bi bi-person-check-fill"></i></div>
                    <div class="presence-mini-value"><?= number_format($presenceJourPresent) ?></div>
                    <div class="presence-mini-label">Présents</div>
                </div>

                <div class="presence-mini-card">
                    <div class="presence-mini-icon"><i class="bi bi-alarm-fill"></i></div>
                    <div class="presence-mini-value"><?= number_format($presenceJourRetard) ?></div>
                    <div class="presence-mini-label">Retards</div>
                </div>

                <div class="presence-mini-card">
                    <div class="presence-mini-icon"><i class="bi bi-person-x-fill"></i></div>
                    <div class="presence-mini-value"><?= number_format($presenceJourAbsent) ?></div>
                    <div class="presence-mini-label">Absents / non pointés</div>
                </div>

                <div class="presence-mini-card">
                    <div class="presence-mini-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <div class="presence-mini-value"><?= number_format($presenceJourJustifie) ?></div>
                    <div class="presence-mini-label">Justifiés</div>
                </div>
            </div>

            <div class="presence-chart-box">
                <canvas id="presenceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="bi bi-bar-chart-steps"></i> Statistiques générales</h3>
                <i class="bi bi-graph-up" style="color: #c96b58;"></i>
            </div>
            <div class="chart-body">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="bi bi-pie-chart-fill"></i> Répartition par fonction</h3>
                <i class="bi bi-percent" style="color: #c96b58;"></i>
            </div>
            <div class="chart-body">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="categories-section">
        <div class="chart-header">
            <h3><i class="bi bi-briefcase-fill"></i> Personnel par service</h3>
            <span style="font-size: 12px; color: #6b7280;">Total : <?= number_format($totalCategories) ?> membres</span>
        </div>
        <div class="categories-grid">
            <div class="category-card">
                <div class="category-value"><?= number_format($administratif['total'] ?? 0) ?></div>
                <div class="category-name">Administratif</div>
                <span class="category-percent"><?= $totalCategories > 0 ? round(($administratif['total'] ?? 0) / $totalCategories * 100) : 0 ?>%</span>
            </div>
            <div class="category-card">
                <div class="category-value"><?= number_format($pedagogique['total'] ?? 0) ?></div>
                <div class="category-name">Pédagogique</div>
                <span class="category-percent"><?= $totalCategories > 0 ? round(($pedagogique['total'] ?? 0) / $totalCategories * 100) : 0 ?>%</span>
            </div>
            <div class="category-card">
                <div class="category-value"><?= number_format($encadrement['total'] ?? 0) ?></div>
                <div class="category-name">Encadrement</div>
                <span class="category-percent"><?= $totalCategories > 0 ? round(($encadrement['total'] ?? 0) / $totalCategories * 100) : 0 ?>%</span>
            </div>
            <div class="category-card">
                <div class="category-value"><?= number_format($informatique['total'] ?? 0) ?></div>
                <div class="category-name">Informatique</div>
                <span class="category-percent"><?= $totalCategories > 0 ? round(($informatique['total'] ?? 0) / $totalCategories * 100) : 0 ?>%</span>
            </div>
        </div>
    </div>

    <!-- Activity Section -->
    <div class="activity-section">
        <div class="chart-header">
            <h3><i class="bi bi-clock-history"></i> Activités récentes</h3>
            <i class="bi bi-arrow-repeat" style="color: #c96b58; cursor: pointer;" id="refreshActivities" title="Rafraîchir"></i>
        </div>
        <div class="activity-list">
            <?php if(count($recentActivities) > 0): ?>
                <?php foreach($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="bi bi-<?= htmlspecialchars($activity['icon'] ?? 'bell-fill') ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?= htmlspecialchars($activity['action'] ?? 'Activité récente') ?></div>
                        <div class="activity-desc">
                            <?= date('d/m/Y H:i:s', strtotime($activity['date_event'])) ?>
                        </div>
                    </div>
                    <div class="activity-time">
                        <?= timeAgo($activity['date_event'] ?? null) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-activities">
                    <i class="bi bi-inbox" style="font-size: 32px;"></i>
                    <p style="margin-top: 12px;">Aucune activité récente trouvée</p>
                    <p style="font-size: 11px; margin-top: 8px;">Ajoutez du personnel ou des documents pour voir les activités ici</p>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="footer-left">
            <img src="uploads/logo/logo.jpeg" alt="Logo CMAK">
            <span>
                © <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion du personnel scolaire. Tous droits réservés.
            </span>
        </div>

        <div class="footer-secure">
            <i class="bi bi-shield-check"></i>
            Données sécurisées
        </div>
    </footer>

</main>

<script>
    // Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Personnel', 'Actifs', 'Suspendus', 'Documents'],
            datasets: [{
                label: 'Effectif',
                data: [
                    <?= (int)($totalPersonnel['total'] ?? 0) ?>,
                    <?= (int)($actifs['total'] ?? 0) ?>,
                    <?= (int)($suspendus['total'] ?? 0) ?>,
                    <?= (int)($documents['total'] ?? 0) ?>
                ],
                backgroundColor: '#2563eb',
                hoverBackgroundColor: '#f59e0b',
                borderRadius: 8,
                barPercentage: 0.65,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e2430',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#e2e8f0', drawBorder: false },
                    ticks: { stepSize: 1, color: '#64748b', font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 12, weight: '500' } }
                }
            }
        }
    });

    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Administratif', 'Pédagogique', 'Encadrement', 'Informatique'],
            datasets: [{
                data: [
                    <?= (int)($administratif['total'] ?? 0) ?>,
                    <?= (int)($pedagogique['total'] ?? 0) ?>,
                    <?= (int)($encadrement['total'] ?? 0) ?>,
                    <?= (int)($informatique['total'] ?? 0) ?>
                ],
                backgroundColor: ['#2563eb', '#f59e0b', '#0ea5e9', '#64748b'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 12,
                        font: { size: 11, weight: '500' },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e2430',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                    cornerRadius: 8
                }
            }
        }
    });


    // Presence Chart
    const presenceCtx = document.getElementById('presenceChart').getContext('2d');
    new Chart(presenceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Présents', 'Retards', 'Absents', 'Justifiés'],
            datasets: [{
                data: [
                    <?= (int)$presenceJourPresent ?>,
                    <?= (int)$presenceJourRetard ?>,
                    <?= (int)$presenceJourAbsent ?>,
                    <?= (int)$presenceJourJustifie ?>
                ],
                backgroundColor: ['#16a34a', '#f59e0b', '#dc2626', '#0ea5e9'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 10,
                        font: { size: 11, weight: '500' },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e2430',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                    cornerRadius: 8
                }
            }
        }
    });


    // Sidebar collapse
    const sidebarToggle = document.getElementById('sidebarToggle');

    function setSidebarCollapsed(enabled) {
        document.body.classList.toggle('sidebar-collapsed', enabled);
        localStorage.setItem('sidebar-collapsed', enabled ? 'enabled' : 'disabled');
    }

    if(localStorage.getItem('sidebar-collapsed') === 'enabled'){
        setSidebarCollapsed(true);
    }

    sidebarToggle?.addEventListener('click', function(){
        setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
    });

    // Profile dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    profileBtn?.addEventListener('click', function(e){
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(){
        profileDropdown?.classList.remove('show');
    });

    profileDropdown?.addEventListener('click', function(e){
        e.stopPropagation();
    });

    // Dark Mode Toggle
    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = darkToggle.querySelector('i');
    
    function setDarkMode(enabled) {
        if (enabled) {
            document.body.classList.add('dark-mode');
            darkIcon.className = 'bi bi-sun-fill';
        } else {
            document.body.classList.remove('dark-mode');
            darkIcon.className = 'bi bi-moon-fill';
        }
        localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');
    }
    
    const savedMode = localStorage.getItem('dark-mode');
    if (savedMode === 'enabled') {
        setDarkMode(true);
    }
    
    darkToggle.addEventListener('click', () => {
        const isDark = !document.body.classList.contains('dark-mode');
        setDarkMode(isDark);
    });

    // Refresh activities
    document.getElementById('refreshActivities')?.addEventListener('click', function() {
        location.reload();
    }); 
    
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
$assistantProfil = 'DE';
include __DIR__ . '/assistant/assistant.php';
?>

</body>
</html>