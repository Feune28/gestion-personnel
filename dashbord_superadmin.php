<?php

session_start();

require_once __DIR__ . '/connexion.php';

if (
    !isset($_SESSION['superadmin_id']) ||
    !isset($_SESSION['superadmin_role']) ||
    $_SESSION['superadmin_role'] !== 'SUPER_ADMIN'
) {
    header('Location: login_superadmin.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Déconnexion
|--------------------------------------------------------------------------
*/

if (isset($_GET['action']) && $_GET['action'] === 'logout') {

    session_unset();
    session_destroy();

    header('Location: login_superadmin.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$totalComptes = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admins
    WHERE role IN ('DE', 'DAF')
")->fetchColumn();

$totalActifs = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admins
    WHERE role IN ('DE', 'DAF')
    AND statut_compte = 'actif'
")->fetchColumn();

$totalSuspendus = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admins
    WHERE role IN ('DE', 'DAF')
    AND statut_compte = 'suspendu'
")->fetchColumn();

$totalDE = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admins
    WHERE role = 'DE'
")->fetchColumn();

$totalDAF = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admins
    WHERE role = 'DAF'
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| Liste des comptes DE et DAF
|--------------------------------------------------------------------------
*/

$requeteComptes = $pdo->query("
    SELECT
        id,
        username,
        nom,
        prenom,
        email,
        fonction,
        role,
        statut_compte,
        date_creation
    FROM admins
    WHERE role IN ('DE', 'DAF')
    ORDER BY id DESC
");

$comptes = $requeteComptes->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tableau de bord Super Administrateur | CMAK</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --bleu-sombre: #0f172a;
            --bleu-secondaire: #1e293b;
            --orange: #f97316;
            --orange-fonce: #ea580c;
            --fond: #f1f5f9;
            --texte: #334155;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--fond);
            color: var(--texte);
            font-family: Arial, Helvetica, sans-serif;
        }

        .application {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background:
                linear-gradient(
                    180deg,
                    var(--bleu-sombre),
                    var(--bleu-secondaire)
                );
            color: white;
            padding: 25px 18px;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            text-align: center;
            padding-bottom: 24px;
            margin-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-header img {
            width: 82px;
            height: 82px;
            object-fit: cover;
            background: white;
            padding: 5px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .sidebar-header h4 {
            margin: 0;
            font-weight: 800;
        }

        .sidebar-header small {
            color: var(--orange);
            font-weight: 700;
        }

        .menu-title {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 22px 12px 8px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #e2e8f0;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 7px;
            border-radius: 12px;
            transition: 0.2s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: var(--orange);
            color: white;
        }

        .sidebar nav a i {
            font-size: 19px;
        }

        .deconnexion {
            margin-top: 25px;
            background: rgba(239, 68, 68, 0.15);
            color: #fecaca !important;
        }

        .deconnexion:hover {
            background: #dc2626 !important;
            color: white !important;
        }

        .contenu {
            width: calc(100% - 280px);
            margin-left: 280px;
            min-height: 100vh;
        }

        .topbar {
            min-height: 88px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 30px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .topbar h3 {
            margin: 0;
            color: var(--bleu-sombre);
            font-weight: 800;
        }

        .topbar p {
            margin: 5px 0 0;
            color: #64748b;
        }

        .profil-admin {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profil-admin .icone {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--orange), var(--orange-fonce));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .profil-admin strong {
            color: var(--bleu-sombre);
        }

        .page {
            padding: 30px;
        }

        .carte-statistique {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            height: 100%;
            overflow: hidden;
        }

        .carte-statistique .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px;
        }

        .carte-statistique h3 {
            color: var(--bleu-sombre);
            font-weight: 800;
            font-size: 31px;
            margin: 8px 0 0;
        }

        .carte-statistique p {
            color: #64748b;
            margin: 0;
            font-weight: 700;
        }

        .stat-icone {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
        }

        .icone-total {
            color: #2563eb;
            background: #dbeafe;
        }

        .icone-actif {
            color: #16a34a;
            background: #dcfce7;
        }

        .icone-suspendu {
            color: #dc2626;
            background: #fee2e2;
        }

        .icone-role {
            color: #ea580c;
            background: #ffedd5;
        }

        .section-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .section-card .card-header {
            background: white;
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-card .card-header h4 {
            margin: 0;
            color: var(--bleu-sombre);
            font-weight: 800;
        }

        .btn-orange {
            background: var(--orange);
            color: white;
            border: 0;
            border-radius: 10px;
            padding: 10px 17px;
            font-weight: 700;
        }

        .btn-orange:hover {
            background: var(--orange-fonce);
            color: white;
        }

        .table thead th {
            background: var(--bleu-sombre);
            color: white;
            border: 0;
            padding: 15px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge-role {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 8px 11px;
            border-radius: 9px;
        }

        .badge-actif {
            background: #dcfce7;
            color: #15803d;
            padding: 8px 11px;
            border-radius: 9px;
        }

        .badge-suspendu {
            background: #fee2e2;
            color: #b91c1c;
            padding: 8px 11px;
            border-radius: 9px;
        }

        .actions-rapides {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .action-box {
            text-decoration: none;
            color: var(--texte);
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            padding: 18px;
            transition: 0.2s;
        }

        .action-box:hover {
            transform: translateY(-3px);
            border-color: var(--orange);
            color: var(--orange-fonce);
        }

        .action-box i {
            font-size: 25px;
            color: var(--orange);
            margin-bottom: 10px;
            display: block;
        }

        .action-box strong {
            display: block;
            color: var(--bleu-sombre);
            margin-bottom: 5px;
        }

        @media (max-width: 1000px) {

            .sidebar {
                width: 230px;
            }

            .contenu {
                width: calc(100% - 230px);
                margin-left: 230px;
            }
        }

        @media (max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .application {
                display: block;
            }

            .contenu {
                width: 100%;
                margin-left: 0;
            }

            .topbar {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }

            .page {
                padding: 18px;
            }

            .actions-rapides {
                grid-template-columns: 1fr;
            }
        }

    
        /* =========================================================
           DESIGN DE RÉFÉRENCE DAF APPLIQUÉ AU SUPER ADMIN
           ========================================================= */
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
            transition:background .25s ease,color .25s ease;
        }

        h1,h2,h3,h4,.carte-statistique h3{
            font-family:'Sora',sans-serif;
        }

        .application{display:block;}

        .sidebar{
            position:fixed;
            top:20px;
            left:20px;
            bottom:20px;
            width:252px;
            height:calc(100vh - 40px);
            min-height:0;
            padding:22px 14px;
            background:rgba(255,255,255,.84);
            backdrop-filter:blur(16px);
            -webkit-backdrop-filter:blur(16px);
            border:2.5px solid rgba(15,23,42,.88);
            border-radius:32px;
            box-shadow:0 18px 45px rgba(15,23,42,.08);
            overflow:hidden;
            color:var(--text);
            transition:width .25s ease,padding .25s ease;
        }

        .sidebar-header{
            color:var(--text);
            margin-bottom:18px;
            padding:8px 8px 18px;
            border-bottom:1px solid rgba(226,232,240,.95);
        }

        .sidebar-header img{
            width:68px;
            height:68px;
            border-radius:22px;
            padding:0;
            border:3px solid #fff;
            box-shadow:0 12px 26px rgba(15,23,42,.10);
        }

        .sidebar-header h4{
            color:var(--text);
            font-size:16px;
            font-weight:800;
        }

        .sidebar-header small{
            display:block;
            margin-top:4px;
            color:var(--primary);
            letter-spacing:.08em;
            font-size:10px;
            font-weight:800;
        }

        .sidebar nav{
            height:calc(100vh - 190px);
            overflow-y:auto;
            overflow-x:hidden;
            padding-left:7px;
            padding-right:2px;
            direction:rtl;
            scrollbar-width:thin;
            scrollbar-color:#cbd5e1 transparent;
        }

        .sidebar nav > *{direction:ltr;}
        .sidebar nav::-webkit-scrollbar{width:5px;}
        .sidebar nav::-webkit-scrollbar-track{background:transparent;}
        .sidebar nav::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}
        .sidebar nav::-webkit-scrollbar-thumb:hover{background:var(--primary);}

        .menu-title{
            color:var(--muted);
            margin:18px 0 8px 12px;
            font-size:10px;
            font-weight:800;
            letter-spacing:.14em;
        }

        .sidebar nav a{
            color:#475569;
            padding:11px 12px;
            border-radius:16px;
            margin-bottom:7px;
            font-weight:700;
            font-size:13px;
            position:relative;
            border:1px solid transparent;
            background:transparent;
            gap:11px;
        }

        .sidebar nav a i{
            width:31px;
            height:31px;
            min-width:31px;
            border-radius:12px;
            display:grid;
            place-items:center;
            background:#f1f5f9;
            color:var(--muted);
            font-size:15px;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active{
            background:rgba(255,255,255,.92);
            border-color:rgba(226,232,240,.95);
            color:var(--primary);
            box-shadow:0 10px 24px rgba(15,23,42,.06);
        }

        .sidebar nav a.active::before{
            content:'';
            position:absolute;
            left:-8px;
            top:13px;
            bottom:13px;
            width:4px;
            border-radius:999px;
            background:linear-gradient(180deg,#f59e0b,#d97706);
        }

        .sidebar nav a:hover i,
        .sidebar nav a.active i{
            color:#fff;
            background:linear-gradient(135deg,#f59e0b,#d97706);
            box-shadow:0 10px 20px rgba(245,158,11,.22);
        }

        .deconnexion{
            margin-top:0;
            background:transparent;
            color:#475569!important;
        }
        .deconnexion:hover{background:rgba(220,38,38,.08)!important;color:#dc2626!important;}
        .deconnexion:hover i{background:linear-gradient(135deg,#ef4444,#b91c1c)!important;}

        .contenu{
            width:auto;
            margin-left:292px;
            padding:32px 40px;
            min-height:100vh;
            transition:margin-left .25s ease;
        }

        .topbar{
            min-height:auto;
            position:relative;
            top:auto;
            z-index:9000;
            overflow:visible;
            padding:20px 24px;
            margin-bottom:28px;
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            background:rgba(255,255,255,.84);
            backdrop-filter:blur(14px);
            box-shadow:var(--shadow-sm);
        }

        .topbar-left{
            display:flex;
            align-items:center;
            gap:13px;
        }

        .topbar h3{
            font-family:'Sora',sans-serif;
            font-size:27px;
            color:var(--text);
            font-weight:800;
            letter-spacing:-.4px;
        }

        .topbar p{color:var(--muted);font-size:13px;}

        .dashboard-menu-toggle,
        .dark-toggle{
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
        }

        .dashboard-menu-toggle:hover,
        .dark-toggle:hover{
            background:linear-gradient(135deg,#f59e0b,#d97706);
            color:#fff;
            border-color:transparent;
            transform:translateY(-1px);
        }

        .header-actions{display:flex;align-items:center;gap:12px;}

        .year-badge{
            background:var(--primary-soft);
            border:1px solid rgba(37,99,235,.18);
            color:var(--primary);
            font-weight:800;
            padding:8px 16px;
            border-radius:40px;
            font-size:13px;
        }

        .profile-menu{position:relative;z-index:9500;}
        .profile-btn{
            height:42px;
            border-radius:40px;
            border:1px solid var(--line);
            background:#fff;
            padding:4px 9px 4px 4px;
            display:flex;
            align-items:center;
            gap:8px;
            cursor:pointer;
            box-shadow:0 5px 14px rgba(15,23,42,.04);
        }

        .profile-avatar{
            width:34px;height:34px;border-radius:50%;
            display:grid;place-items:center;
            background:linear-gradient(135deg,var(--primary-2),#0f172a);
            color:#fff;
        }

        .profile-btn i.bi-chevron-down{font-size:12px;color:var(--muted);}

        .profile-dropdown{
            position:absolute;
            right:0;
            top:54px;
            width:265px;
            background:#fff;
            border:1px solid var(--line);
            border-radius:16px;
            box-shadow:0 16px 45px rgba(0,0,0,.14);
            overflow:hidden;
            display:none;
            z-index:99999;
        }

        .profile-dropdown.show{display:block;}
        .profile-dropdown-header{padding:16px 18px;border-bottom:1px solid #f0f2f5;background:#fafbfc;}
        .profile-name{font-size:14px;font-weight:800;color:#111827;}
        .profile-role{margin-top:3px;font-size:12px;color:#6b7280;}
        .profile-dropdown-body,.profile-dropdown-footer{padding:8px;}
        .profile-dropdown-body{border-bottom:1px solid #f0f2f5;}
        .profile-dropdown a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:10px;text-decoration:none;color:#374151;font-size:13px;font-weight:600;}
        .profile-dropdown a:hover{background:#eff6ff;color:var(--primary);}
        .profile-dropdown-footer a{color:#b91c1c;}
        .profile-dropdown-footer a:hover{background:#fee2e2;color:#991b1b;}

        .page{padding:0;}

        .carte-statistique,
        .section-card{
            border:1px solid rgba(226,232,240,.9);
            border-radius:var(--radius);
            box-shadow:var(--shadow-sm);
            background:rgba(255,255,255,.86);
            backdrop-filter:blur(14px);
        }

        .carte-statistique{
            position:relative;
            min-height:150px;
            overflow:hidden;
            transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;
        }

        .carte-statistique:hover{
            transform:translateY(-5px);
            box-shadow:var(--shadow);
            border-color:rgba(37,99,235,.22);
        }

        .carte-statistique::before{
            content:'';
            position:absolute;
            right:-34px;
            top:-38px;
            width:122px;
            height:122px;
            border-radius:50%;
            background:var(--primary-soft);
        }

        .carte-statistique .card-body{position:relative;z-index:1;padding:20px;}
        .carte-statistique p{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em;}
        .carte-statistique h3{color:var(--text);font-size:34px;margin-top:8px;}
        .stat-icone{width:48px;height:48px;border-radius:17px;color:#fff!important;background:var(--primary-2)!important;box-shadow:0 12px 22px rgba(37,99,235,.24);}
        .icone-actif{background:var(--success)!important;box-shadow:0 12px 22px rgba(22,163,74,.22);}
        .icone-suspendu{background:var(--danger)!important;box-shadow:0 12px 22px rgba(220,38,38,.20);}
        .icone-role{background:var(--warning)!important;box-shadow:0 12px 22px rgba(217,119,6,.22);}

        .section-card .card-header{
            background:transparent;
            padding:20px 22px;
            border-bottom:1px solid var(--line);
        }
        .section-card .card-header h4{color:var(--text);font-size:17px;}

        .btn-orange{
            background:var(--primary);
            border-radius:14px;
            box-shadow:0 12px 22px rgba(37,99,235,.18);
        }
        .btn-orange:hover{background:#0f172a;}

        .table thead th{background:#0f172a;padding:15px;}
        .table tbody td{padding:15px;border-color:var(--line);}
        .badge-role,.badge-actif,.badge-suspendu{display:inline-flex;align-items:center;font-weight:700;border-radius:999px;}

        .action-box{
            border:1px solid var(--line);
            border-radius:18px;
            box-shadow:0 5px 16px rgba(15,23,42,.04);
        }
        .action-box:hover{border-color:rgba(37,99,235,.25);color:var(--primary);}
        .action-box i{color:var(--primary);}
        .action-box strong{color:var(--text);}

        .dashboard-footer{
            margin-top:32px;
            background:rgba(255,255,255,.84);
            border:1px solid var(--line);
            border-radius:16px;
            box-shadow:var(--shadow-sm);
            padding:14px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            color:var(--muted);
            font-size:12px;
            font-weight:500;
        }
        .footer-left{display:flex;align-items:center;gap:10px;}
        .footer-left img{width:30px;height:30px;border-radius:8px;object-fit:cover;}
        .footer-secure{display:flex;align-items:center;gap:6px;color:#166534;font-weight:700;}

        body.sidebar-collapsed .sidebar{width:84px;padding-left:10px;padding-right:10px;}
        body.sidebar-collapsed .contenu{margin-left:124px;}
        body.sidebar-collapsed .sidebar-header h4,
        body.sidebar-collapsed .sidebar-header small,
        body.sidebar-collapsed .menu-title,
        body.sidebar-collapsed .sidebar nav a span{display:none;}
        body.sidebar-collapsed .sidebar-header img{width:48px;height:48px;border-radius:17px;}
        body.sidebar-collapsed .sidebar nav{padding-left:4px;padding-right:0;}
        body.sidebar-collapsed .sidebar nav a{padding:10px 7px;justify-content:center;}
        body.sidebar-collapsed .sidebar nav a i{width:34px;height:34px;min-width:34px;}

        body.dark-mode{
            --bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#243044;
            --primary-soft:rgba(37,99,235,.18);
            background:#0b1120;
        }
        body.dark-mode .sidebar,
        body.dark-mode .topbar,
        body.dark-mode .carte-statistique,
        body.dark-mode .section-card,
        body.dark-mode .dashboard-footer,
        body.dark-mode .profile-dropdown,
        body.dark-mode .profile-dropdown-header{
            background:rgba(26,32,44,.90);
            border-color:#2d3340;
        }
        body.dark-mode .sidebar{box-shadow:0 18px 45px rgba(0,0,0,.22);}
        body.dark-mode .sidebar-header{border-bottom-color:#2d3340;}
        body.dark-mode .sidebar-header h4,
        body.dark-mode .topbar h3,
        body.dark-mode .carte-statistique h3,
        body.dark-mode .section-card h4,
        body.dark-mode .profile-name,
        body.dark-mode .action-box strong{color:#f0f2f5;}
        body.dark-mode .sidebar nav a{color:#cbd5e1;}
        body.dark-mode .sidebar nav a i{background:#111827;color:#94a3b8;}
        body.dark-mode .sidebar nav a:hover,
        body.dark-mode .sidebar nav a.active{background:rgba(255,255,255,.06);border-color:#2d3340;color:#fbbf24;}
        body.dark-mode .dashboard-menu-toggle,
        body.dark-mode .dark-toggle,
        body.dark-mode .profile-btn,
        body.dark-mode .action-box{background:#1a202c;border-color:#2d3340;color:#fbbf24;}
        body.dark-mode .profile-dropdown a{color:#e5e7eb;}
        body.dark-mode .profile-dropdown a:hover{background:rgba(37,99,235,.15);color:#93c5fd;}
        body.dark-mode .table{--bs-table-bg:transparent;--bs-table-color:#e5e7eb;--bs-table-border-color:#2d3340;}
        body.dark-mode .text-muted{color:#94a3b8!important;}

        @media(max-width:768px){
            .sidebar{
                left:10px;top:10px;bottom:10px;width:218px;height:calc(100vh - 20px);
                position:fixed;min-height:0;border-radius:26px;padding:18px 12px;
            }
            .contenu{width:auto;margin-left:238px;padding:18px 14px;}
            body.sidebar-collapsed .sidebar{width:76px;padding-left:8px;padding-right:8px;}
            body.sidebar-collapsed .contenu{margin-left:96px;}
            .topbar{flex-direction:column;align-items:flex-start;}
            .header-actions{width:100%;flex-wrap:wrap;}
            .actions-rapides{grid-template-columns:1fr;}
        }

    </style>

</head>

<body>

<div class="application">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="/app_exp/uploads/logo/logo.jpeg" alt="Logo CMAK" onerror="this.style.display='none'">
            <h4>CMAK</h4>
            <small>SUPER ADMIN</small>
        </div>

        <nav>
            <div class="menu-title">Administration</div>

            <a href="dashbord_superadmin.php" class="active" title="Tableau de bord">
                <i class="bi bi-grid-fill"></i>
                <span>Tableau de bord</span>
            </a>

            <a href="#comptes" title="Comptes DE et DAF">
                <i class="bi bi-people-fill"></i>
                <span>Comptes DE / DAF</span>
            </a>

            <a href="#" title="Ajouter un compte">
                <i class="bi bi-person-plus-fill"></i>
                <span>Ajouter un compte</span>
            </a>

            <div class="menu-title">Contrôle</div>

            <a href="#" title="Historique">
                <i class="bi bi-clock-history"></i>
                <span>Historique</span>
            </a>

            <a href="#" title="Statistiques">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Statistiques</span>
            </a>

            <a href="#" title="Sécurité">
                <i class="bi bi-shield-check"></i>
                <span>Sécurité</span>
            </a>

            <div class="menu-title">Compte</div>

            <a href="#" title="Mon profil">
                <i class="bi bi-person-circle"></i>
                <span>Mon profil</span>
            </a>

            <a href="dashbord_superadmin.php?action=logout"
               class="deconnexion"
               title="Déconnexion"
               onclick="return confirm('Voulez-vous vraiment vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </nav>
    </aside>

    <main class="contenu">

        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="dashboard-menu-toggle" id="sidebarToggle"
                        title="Réduire / agrandir le menu">
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <h3>
                        Tableau de bord Super Administrateur
                    </h3>
                    <p>
                        Bienvenue,
                        <?= htmlspecialchars($_SESSION['superadmin_prenom']) ?>
                        <?= htmlspecialchars($_SESSION['superadmin_nom']) ?>
                        · Gestion générale de CMAK
                    </p>
                </div>
            </div>

            <div class="header-actions">
                <div class="year-badge">
                    <i class="bi bi-shield-check"></i>
                    Administration centrale
                </div>

                <button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre">
                    <i class="bi bi-moon-fill"></i>
                </button>

                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-btn" id="profileBtn">
                        <span class="profile-avatar">
                            <i class="bi bi-person-fill-gear"></i>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-dropdown-header">
                            <div class="profile-name">
                                <?= htmlspecialchars($_SESSION['superadmin_prenom']) ?>
                                <?= htmlspecialchars($_SESSION['superadmin_nom']) ?>
                            </div>
                            <div class="profile-role">
                                <?= htmlspecialchars($_SESSION['superadmin_fonction']) ?>
                            </div>
                        </div>

                        <div class="profile-dropdown-body">
                            <a href="#">
                                <i class="bi bi-person-circle"></i>
                                Mon profil
                            </a>
                            <a href="dashbord_superadmin.php">
                                <i class="bi bi-speedometer2"></i>
                                Tableau de bord
                            </a>
                        </div>

                        <div class="profile-dropdown-footer">
                            <a href="dashbord_superadmin.php?action=logout"
                               onclick="return confirm('Voulez-vous vraiment vous déconnecter ?')">
                                <i class="bi bi-box-arrow-right"></i>
                                Se déconnecter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="page">

            <div class="row g-4 mb-4">

                <div class="col-xl-3 col-md-6">

                    <div class="card carte-statistique">

                        <div class="card-body">

                            <div>

                                <p>Total des comptes</p>

                                <h3><?= $totalComptes ?></h3>

                            </div>

                            <div class="stat-icone icone-total">

                                <i class="bi bi-people-fill"></i>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card carte-statistique">

                        <div class="card-body">

                            <div>

                                <p>Comptes actifs</p>

                                <h3><?= $totalActifs ?></h3>

                            </div>

                            <div class="stat-icone icone-actif">

                                <i class="bi bi-person-check-fill"></i>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card carte-statistique">

                        <div class="card-body">

                            <div>

                                <p>Comptes suspendus</p>

                                <h3><?= $totalSuspendus ?></h3>

                            </div>

                            <div class="stat-icone icone-suspendu">

                                <i class="bi bi-person-x-fill"></i>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card carte-statistique">

                        <div class="card-body">

                            <div>

                                <p>DE / DAF</p>

                                <h3><?= $totalDE ?> / <?= $totalDAF ?></h3>

                            </div>

                            <div class="stat-icone icone-role">

                                <i class="bi bi-diagram-3-fill"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row g-4">

                <div class="col-xl-9">

                    <div class="card section-card" id="comptes">

                        <div class="card-header d-flex justify-content-between align-items-center">

                            <div>

                                <h4>Comptes administratifs</h4>

                                <small class="text-muted">
                                    Liste des comptes du DE et du DAF
                                </small>

                            </div>

                            <button
                                type="button"
                                class="btn btn-orange"
                                onclick="alert('Le formulaire complet sera ajouté après le dépôt.')"
                            >

                                <i class="bi bi-person-plus-fill me-1"></i>

                                Nouveau compte

                            </button>

                        </div>

                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table class="table table-hover mb-0">

                                    <thead>

                                        <tr>

                                            <th>Administrateur</th>

                                            <th>Email</th>

                                            <th>Fonction</th>

                                            <th>Rôle</th>

                                            <th>Statut</th>

                                            <th>Actions</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                    <?php if (empty($comptes)): ?>

                                        <tr>

                                            <td
                                                colspan="6"
                                                class="text-center py-5 text-muted"
                                            >

                                                Aucun compte DE ou DAF enregistré.

                                            </td>

                                        </tr>

                                    <?php else: ?>

                                        <?php foreach ($comptes as $compte): ?>

                                            <tr>

                                                <td>

                                                    <strong>

                                                        <?= htmlspecialchars($compte['nom']) ?>

                                                        <?= htmlspecialchars($compte['prenom']) ?>

                                                    </strong>

                                                    <br>

                                                    <small class="text-muted">

                                                        <?= htmlspecialchars($compte['username'] ?? '') ?>

                                                    </small>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars($compte['email']) ?>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars($compte['fonction'] ?? '-') ?>

                                                </td>

                                                <td>

                                                    <span class="badge-role">

                                                        <?= htmlspecialchars($compte['role']) ?>

                                                    </span>

                                                </td>

                                                <td>

                                                    <?php if (($compte['statut_compte'] ?? 'actif') === 'actif'): ?>

                                                        <span class="badge-actif">

                                                            <i class="bi bi-check-circle-fill me-1"></i>

                                                            Actif

                                                        </span>

                                                    <?php else: ?>

                                                        <span class="badge-suspendu">

                                                            <i class="bi bi-x-circle-fill me-1"></i>

                                                            Suspendu

                                                        </span>

                                                    <?php endif; ?>

                                                </td>

                                                <td>

                                                    <button
                                                        type="button"
                                                        class="btn btn-warning btn-sm"
                                                        title="Modifier"
                                                        onclick="alert('Modification du compte après le dépôt.')"
                                                    >

                                                        <i class="bi bi-pencil-square"></i>

                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-danger btn-sm"
                                                        title="Suspendre"
                                                        onclick="alert('Suspension du compte après le dépôt.')"
                                                    >

                                                        <i class="bi bi-lock-fill"></i>

                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-info btn-sm"
                                                        title="Réinitialiser le mot de passe"
                                                        onclick="alert('Réinitialisation après le dépôt.')"
                                                    >

                                                        <i class="bi bi-key-fill"></i>

                                                    </button>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-xl-3">

                    <div class="card section-card">

                        <div class="card-header">

                            <h4>Actions rapides</h4>

                        </div>

                        <div class="card-body">

                            <div class="actions-rapides">

                                <a href="#comptes" class="action-box">

                                    <i class="bi bi-people-fill"></i>

                                    <strong>Voir les comptes</strong>

                                    <small>Consulter le DE et le DAF</small>

                                </a>

                                <a
                                    href="#"
                                    class="action-box"
                                    onclick="alert('Fonction disponible prochainement.')"
                                >

                                    <i class="bi bi-person-plus-fill"></i>

                                    <strong>Ajouter</strong>

                                    <small>Créer un nouveau compte</small>

                                </a>

                                <a
                                    href="#"
                                    class="action-box"
                                    onclick="alert('Historique disponible prochainement.')"
                                >

                                    <i class="bi bi-clock-history"></i>

                                    <strong>Historique</strong>

                                    <small>Voir les opérations réalisées</small>

                                </a>

                                <a
                                    href="#"
                                    class="action-box"
                                    onclick="alert('Statistiques disponibles prochainement.')"
                                >

                                    <i class="bi bi-bar-chart-fill"></i>

                                    <strong>Statistiques</strong>

                                    <small>Analyser les comptes</small>

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

            <footer class="dashboard-footer">
                <div class="footer-left">
                    <img src="/app_exp/uploads/logo/logo.jpeg" alt="Logo CMAK" onerror="this.style.display='none'">
                    <span>© <?= date('Y') ?> CMAK · Espace Super Administrateur</span>
                </div>
                <div class="footer-secure">
                    <i class="bi bi-shield-lock-fill"></i>
                    Session sécurisée
                </div>
            </footer>

    </main>

</div>


<script>
    const sidebarToggle = document.getElementById('sidebarToggle');

    function setSidebarCollapsed(enabled) {
        document.body.classList.toggle('sidebar-collapsed', enabled);
        localStorage.setItem('superadmin-sidebar-collapsed', enabled ? 'enabled' : 'disabled');
    }

    if (localStorage.getItem('superadmin-sidebar-collapsed') === 'enabled') {
        setSidebarCollapsed(true);
    }

    sidebarToggle?.addEventListener('click', function () {
        setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));
    });

    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    profileBtn?.addEventListener('click', function (event) {
        event.stopPropagation();
        profileDropdown?.classList.toggle('show');
    });

    profileDropdown?.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        profileDropdown?.classList.remove('show');
    });

    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = darkToggle?.querySelector('i');

    function setDarkMode(enabled) {
        document.body.classList.toggle('dark-mode', enabled);

        if (darkIcon) {
            darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }

        localStorage.setItem('superadmin-dark-mode', enabled ? 'enabled' : 'disabled');
    }

    if (localStorage.getItem('superadmin-dark-mode') === 'enabled') {
        setDarkMode(true);
    }

    darkToggle?.addEventListener('click', function () {
        setDarkMode(!document.body.classList.contains('dark-mode'));
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>