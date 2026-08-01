

<?php

session_start([

    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'

]);

if(!isset($_SESSION['admin']) || empty($_SESSION['admin'])){

    header('Location: login.php');
    exit();

}

if(isset($_SESSION['last_activity']) &&
   time() - $_SESSION['last_activity'] > 1800){

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

header("X-Frame-Options: DENY");

header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: no-referrer");

header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net; " .
    "img-src 'self' data:;"
);

require 'connexion.php';

$annee = $pdo->query("
    SELECT *
    FROM annees_scolaires
    WHERE active = 1
    LIMIT 1
")->fetch();

$anneeFooter = $annee['libelle'] ?? date('Y');

/*
|--------------------------------------------------------------------------
| GENERATION USERNAME & MOT DE PASSE
|--------------------------------------------------------------------------
*/

function genererUsername($nom, $prenom){

    $nom = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $nom)
    );

    $prenom = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $prenom)
    );

    return $nom . "." . $prenom;

}

function genererMotDePasseTemporaire(){

    return "Scol@" . rand(1000,9999);

}

if(empty($_SESSION['token'])){

    $_SESSION['token'] = bin2hex(random_bytes(32));

}

$error = "";

if(isset($_POST['save'])){

    if(
        !isset($_POST['token'])
        ||
        !hash_equals($_SESSION['token'], $_POST['token'])
    ){

        die("Token CSRF invalide");

    }

    $identifiant = trim($_POST['identifiant'] ?? '');

    $nom = trim($_POST['nom'] ?? '');

    $prenom = trim($_POST['prenom'] ?? '');

    $date_naissance = trim($_POST['date_naissance'] ?? '');

    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');

    $cni = trim($_POST['cni'] ?? '');

    $telephone = trim($_POST['telephone'] ?? '');

    $telephone_urgence =
    trim($_POST['telephone_urgence'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $nationalite = trim($_POST['nationalite'] ?? '');

    $fonction = trim($_POST['fonction'] ?? '');

    $matiere = trim($_POST['matiere'] ?? '');

    $niveau = trim($_POST['niveau'] ?? '');

    $niveau_etude =
    trim($_POST['niveau_etude'] ?? '');

    $diplome = trim($_POST['diplome'] ?? '');

    $prof_principal =
    trim($_POST['prof_principal'] ?? '');

    $date_embauche =
    trim($_POST['date_embauche'] ?? '');

    $date_debut_contrat =
    trim($_POST['date_debut_contrat'] ?? '');

    $date_fin_contrat =
    trim($_POST['date_fin_contrat'] ?? '');

    $numero_contrat =
    trim($_POST['numero_contrat'] ?? '');

    $annee_scolaire =
    trim($_POST['annee_scolaire'] ?? '');

   $matiere_emploi =
$_POST['matiere_emploi'] ?? [];

$classe_emploi =
$_POST['classe_emploi'] ?? [];

    $statut = "actif";

    if(empty($nom) || empty($prenom)){

        $error = "Nom et prénom obligatoires";

    }

    if(
        !empty($email)
        &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ){

        $error = "Email invalide";

    }

    $dossiers = [

        'uploads/photos',
        'uploads/cv',
        'uploads/lettres',
        'uploads/contrats',
        'uploads/diplomes'

    ];

    foreach($dossiers as $dossier){

        if(!is_dir($dossier)){

            mkdir($dossier,0755,true);

        }

    }

    $photo = "";

    if(
        isset($_FILES['photo'])
        &&
        $_FILES['photo']['error'] === 0
    ){

        $ext = strtolower(

            pathinfo(
                $_FILES['photo']['name'],
                PATHINFO_EXTENSION
            )

        );

        $allowedImages = ['jpg','jpeg','png','webp'];

$ext = strtolower(

    pathinfo(
        $_FILES['photo']['name'],
        PATHINFO_EXTENSION
    )

);

if(!in_array($ext, $allowedImages)){

    $error = "Format image invalide";

}

if($_FILES['photo']['size'] > 5 * 1024 * 1024){

    $error = "Image trop volumineuse";

}

if(empty($error)){

    $photo = uniqid('photo_', true).'.'.$ext;

    move_uploaded_file(

        $_FILES['photo']['tmp_name'],
        'uploads/photos/'.$photo

    );

}

    }

    $cv = "";

    if(
        isset($_FILES['cv'])
        &&
        $_FILES['cv']['error'] === 0
    ){

       $ext = strtolower(

    pathinfo(
        $_FILES['cv']['name'],
        PATHINFO_EXTENSION
    )

);

if($ext !== 'pdf'){

    $error = "Le CV doit être un PDF";

}

if(empty($error)){

    $cv = uniqid('cv_', true).'.'.$ext;

    move_uploaded_file(

        $_FILES['cv']['tmp_name'],
        'uploads/cv/'.$cv

    );

}
    }

    $lettre_motivation = "";

    if(
        isset($_FILES['lettre_motivation'])
        &&
        $_FILES['lettre_motivation']['error'] === 0
    ){

        $ext = strtolower(

            pathinfo(
                $_FILES['lettre_motivation']['name'],
                PATHINFO_EXTENSION
            )

        );

        if($ext !== 'pdf'){

    $error = "La lettre doit être un PDF";

}

if(empty($error)){

    $lettre_motivation =
    uniqid('lettre_', true).'.'.$ext;

    move_uploaded_file(

        $_FILES['lettre_motivation']['tmp_name'],
        'uploads/lettres/'.$lettre_motivation

    );

}

    }

   $contrat_pdf = "";

if(
    isset($_FILES['contrat_pdf'])
    &&
    $_FILES['contrat_pdf']['error'] === 0
){

    $ext = strtolower(

        pathinfo(
            $_FILES['contrat_pdf']['name'],
            PATHINFO_EXTENSION
        )

    );

    if($ext !== 'pdf'){

        $error = "Le contrat doit être un PDF";

    }

    if(empty($error)){

        $contrat_pdf =
        uniqid('contrat_', true).'.'.$ext;

        move_uploaded_file(

            $_FILES['contrat_pdf']['tmp_name'],
            'uploads/contrats/'.$contrat_pdf

        );

    }

}

$diplome_pdf = "";

if(
    isset($_FILES['diplome_pdf'])
    &&
    $_FILES['diplome_pdf']['error'] === 0
){

    $ext = strtolower(

        pathinfo(
            $_FILES['diplome_pdf']['name'],
            PATHINFO_EXTENSION
        )

    );

    if($ext !== 'pdf'){

        $error = "Le diplôme doit être un PDF";

    }

    if(empty($error)){

        $diplome_pdf =
        uniqid('diplome_', true).'.'.$ext;

        move_uploaded_file(

            $_FILES['diplome_pdf']['tmp_name'],
            'uploads/diplomes/'.$diplome_pdf

        );

    }

}


    if(empty($error)){

        $sql = $pdo->prepare(

            "INSERT INTO personnels(

                identifiant,
                nom,
                prenom,
                date_naissance,
                lieu_naissance,
                cni,
                telephone,
                telephone_urgence,
                email,
                nationalite,
                fonction,
                matiere,
                niveau_encadrement,
                niveau_etude,
                diplome,
                prof_principal,
                date_embauche,
                date_debut_contrat,
                date_fin_contrat,
                numero_contrat,
                annee_scolaire,
                statut,
                photo,
                cv,
                lettre_motivation,
                contrat_pdf,
                diplome_pdf

            ) VALUES(

                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?

            )"

        );

       $sql->execute([

    $identifiant,
    $nom,
    $prenom,
    $date_naissance,
    $lieu_naissance,
    $cni,
    $telephone,
    $telephone_urgence,
    $email,
    $nationalite,
    $fonction,
    $matiere,
    $niveau,
    $niveau_etude,
    $diplome,
    $prof_principal,
    $date_embauche,
    $date_debut_contrat,
    $date_fin_contrat,
    $numero_contrat,
    $annee_scolaire,
    $statut,
    $photo,
    $cv,
    $lettre_motivation,
    $contrat_pdf,
    $diplome_pdf

]);

/*
|--------------------------------------------------------------------------
| CREATION COMPTE PERSONNEL
|--------------------------------------------------------------------------
*/

$personnel_id = $pdo->lastInsertId();

/*
|---------------------------------------------------------
| ENREGISTREMENT EMPLOI DU TEMPS PROF
|---------------------------------------------------------
*/

if($fonction === 'PROFESSEUR'){

    foreach($matiere_emploi as $jour => $heures){

        foreach($heures as $heure => $matiereChoisie){

            $classeChoisie =
            $classe_emploi[$jour][$heure] ?? '';

            if(
                !empty($matiereChoisie)
                &&
                !empty($classeChoisie)
            ){

                $heureParts = explode('-', $heure);

$heure_debut =
trim($heureParts[0]);

$heure_fin =
trim($heureParts[1]);

$heure_debut = str_replace('H', ':00', $heure_debut);
$heure_fin = str_replace('H', ':00', $heure_fin);

$insertEDT = $pdo->prepare(

    "INSERT INTO emplois_temps_professeurs(

        personnel_id,
        jour,
        heure_debut,
        heure_fin,
        plage_horaire,
        matiere,
        classe,
        annee_scolaire

    ) VALUES(

        ?,?,?,?,?,?,?,?

    )"

);
         $insertEDT->execute([

    $personnel_id,
    $jour,
    $heure_debut,
    $heure_fin,
    $heure,
    $matiereChoisie,
    $classeChoisie,
    $annee_scolaire

]);

            }

        }

    }

}

$username = genererUsername($nom, $prenom);

$password_temporaire =
genererMotDePasseTemporaire();

$password_hash =
password_hash(
    $password_temporaire,
    PASSWORD_DEFAULT
);

$check = $pdo->prepare(

    "SELECT id
     FROM comptes_personnels
     WHERE identifiant=?"

);

$check->execute([$username]);

if($check->rowCount() > 0){

    $username =
    $username . rand(10,99);

}

$insertCompte = $pdo->prepare(

    "INSERT INTO comptes_personnels(

        personnel_id,
        identifiant,
        password,
        password_temporaire,
        email,
        premiere_connexion,
        email_verifie,
        actif,
        role

    ) VALUES(

        ?,?,?,?,?,?,?,?,?

    )"

);

$insertCompte->execute([

    $personnel_id,
    $username,
    $password_hash,
    $password_temporaire,
    $email,
    1,
    0,
    1,
    'personnel'

]);

$_SESSION['success_compte'] = [

    'identifiant' => $username,
    'password' => $password_temporaire

];

header('Location: ajouter.php?success=1');

exit();


}

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

<title>Ajouter personnel | Gestion École</title>

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
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#f3f6fb;--card:#ffffff;--text:#0f172a;--muted:#64748b;--line:#e2e8f0;--primary:#1d4ed8;--primary-soft:#dbeafe;--gold:#f59e0b;--shadow:0 18px 45px rgba(15,23,42,.08);--shadow-sm:0 8px 24px rgba(15,23,42,.06);--radius:22px;}
body{font-family:'Inter',sans-serif;background:radial-gradient(circle at top left, rgba(37,99,235,.13), transparent 28%),radial-gradient(circle at top right, rgba(245,158,11,.10), transparent 26%),var(--bg);color:var(--text);transition:all .2s ease;overflow-x:hidden;}
h1,h2,h3,.page-title,.section-title{font-family:'Sora',sans-serif;}
.sidebar{position:fixed;top:10px;left:10px;bottom:10px;width:280px;height:calc(100vh - 20px);padding:16px 16px 18px;background:rgba(255,255,255,.82);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:2.5px solid rgba(15,23,42,.92);border-radius:34px;box-shadow:var(--shadow);overflow:hidden;z-index:999;}
.logo{color:var(--text);text-align:center;margin-bottom:12px;padding:6px 8px 12px;border-bottom:1px solid rgba(226,232,240,.9);}
.logo img{width:58px;height:58px;border-radius:19px;object-fit:cover;margin-bottom:8px;border:3px solid #fff;box-shadow:0 12px 26px rgba(15,23,42,.10);}
.logo h4{color:var(--text);font-family:'Sora',sans-serif;font-size:14px;font-weight:800;margin-top:4px;}
.menu{height:calc(100vh - 128px);min-height:430px;padding-left:8px;padding-right:4px;padding-bottom:42px;overflow-y:auto;overflow-x:hidden;direction:rtl;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;}
.menu>*{direction:ltr;}.menu::-webkit-scrollbar{width:5px}.menu::-webkit-scrollbar-track{background:transparent}.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}.menu::-webkit-scrollbar-thumb:hover{background:var(--primary)}
.menu-section{margin-bottom:10px}.menu-title{color:var(--muted);font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;margin:0 0 5px 12px;}
.menu a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#475569;padding:9px 11px;border-radius:16px;margin-bottom:3px;min-height:43px;white-space:nowrap;font-weight:700;font-size:13px;position:relative;border:1px solid transparent;background:transparent;transition:.2s;}
.menu a i{width:29px;height:29px;min-width:29px;border-radius:12px;display:grid;place-items:center;background:#f1f5f9;color:var(--muted);font-size:15px;}
.menu a:hover,.menu a.active{background:rgba(255,255,255,.9);border-color:rgba(226,232,240,.95);color:var(--primary);box-shadow:0 10px 24px rgba(15,23,42,.06);}
.menu a.active::before{content:'';position:absolute;left:-8px;top:13px;bottom:13px;width:4px;border-radius:999px;background:linear-gradient(180deg,#f59e0b,#d97706);}
.menu a:hover i,.menu a.active i{color:#fff;background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 10px 20px rgba(245,158,11,.22);}
.main{margin-left:310px;padding:32px 40px;min-height:100vh;transition:margin-left .25s ease;}
.topbar,.form-card,.dashboard-footer{background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(226,232,240,.9);border-radius:var(--radius);box-shadow:var(--shadow-sm);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;gap:16px;padding:20px 24px;position:relative;z-index:20;}
.top-left{display:flex;align-items:center;gap:13px}.page-title{font-size:27px;font-weight:800;color:var(--text);margin:0}.page-subtitle{color:var(--muted);font-size:13px;margin-top:4px}.header-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.year-badge{background:var(--primary-soft);border:1px solid rgba(37,99,235,.18);padding:8px 18px;border-radius:40px;font-size:13px;font-weight:800;color:var(--primary)}
.dashboard-menu-toggle,.dark-toggle,.back-btn{width:42px;height:42px;border-radius:15px;border:1px solid var(--line);background:#fff;color:var(--primary);display:grid;place-items:center;cursor:pointer;box-shadow:0 8px 20px rgba(15,23,42,.06);transition:all .2s ease;flex:0 0 auto;text-decoration:none;}
.dashboard-menu-toggle:hover,.dark-toggle:hover,.back-btn:hover{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-color:transparent;transform:translateY(-1px)}
.form-card{padding:28px}.alert{border-radius:16px;border:none;box-shadow:var(--shadow-sm)}.preview{width:128px;height:128px;border-radius:34px;object-fit:cover;border:5px solid rgba(255,255,255,.95);box-shadow:0 18px 34px rgba(15,23,42,.15)}
.form-control,.form-select{border-radius:14px;border:1px solid var(--line);min-height:46px;font-size:14px;box-shadow:none!important}.form-control:focus,.form-select:focus{border-color:var(--primary)}.input-group-text{border-radius:14px 0 0 14px;border-color:var(--line);background:#f8fafc;font-weight:800}label{font-size:13px;font-weight:800;color:var(--text);margin-bottom:7px}.btn-save{background:var(--primary);color:white;border:none;padding:12px 25px;border-radius:14px;font-weight:800;box-shadow:0 12px 22px rgba(37,99,235,.18)}.btn-save:hover{background:#0f172a;color:white;transform:translateY(-1px)}
.section-title{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:white;padding:12px 16px;border-radius:16px;margin-bottom:20px;font-size:16px;font-weight:800;box-shadow:0 12px 22px rgba(15,23,42,.12)}.card{border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:var(--shadow-sm)!important}.card-header{background:#0f172a!important;font-weight:800}.border.rounded{border-color:var(--line)!important;border-radius:16px!important}
.dashboard-footer{margin-top:32px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;color:var(--muted);font-size:12px;font-weight:500}.footer-left{display:flex;align-items:center;gap:10px}.footer-left img{width:30px;height:30px;border-radius:8px;object-fit:cover}.footer-secure{display:flex;align-items:center;gap:6px;color:#166534;font-weight:700}
body.sidebar-collapsed .sidebar{width:84px!important;padding-left:10px!important;padding-right:10px!important}body.sidebar-collapsed .main{margin-left:114px!important}body.sidebar-collapsed .logo h4,body.sidebar-collapsed .menu-title,body.sidebar-collapsed .menu a span{display:none}body.sidebar-collapsed .logo img{width:48px;height:48px;border-radius:17px}body.sidebar-collapsed .menu{padding-left:4px;padding-right:0;padding-bottom:42px}body.sidebar-collapsed .menu a{padding:10px 7px;justify-content:center}body.sidebar-collapsed .menu a i{width:34px;height:34px;min-width:34px}
body.dark-mode{--bg:#0b1120;--card:#111827;--text:#e5e7eb;--muted:#94a3b8;--line:#243044;--primary-soft:rgba(37,99,235,.18);background:#0b1120}body.dark-mode .sidebar,body.dark-mode .topbar,body.dark-mode .form-card,body.dark-mode .dashboard-footer,body.dark-mode .dashboard-menu-toggle,body.dark-mode .dark-toggle,body.dark-mode .back-btn{background:rgba(26,32,44,.86);border-color:#2d3340;color:#f0f2f5}body.dark-mode .sidebar{border-color:rgba(255,255,255,.78)!important}body.dark-mode .logo{border-bottom-color:#2d3340}body.dark-mode .logo h4,body.dark-mode .page-title,body.dark-mode label{color:#f0f2f5}body.dark-mode .menu a{color:#cbd5e1}body.dark-mode .menu a i{background:#111827;color:#94a3b8}body.dark-mode .menu a:hover,body.dark-mode .menu a.active{background:rgba(255,255,255,.06);border-color:#2d3340;color:#fbbf24}body.dark-mode .form-control,body.dark-mode .form-select,body.dark-mode .input-group-text,body.dark-mode .card{background:#111827;border-color:#2d3340;color:#e5e7eb}body.dark-mode .form-control::placeholder{color:#94a3b8}body.dark-mode .border.rounded{border-color:#2d3340!important}
@media(max-width:900px){.sidebar{top:8px!important;left:8px!important;bottom:8px!important;width:265px!important;height:calc(100vh - 16px)!important;padding:14px 14px 16px!important;border-radius:32px!important;transform:none!important}.menu{height:calc(100vh - 116px)!important;min-height:420px!important;padding-bottom:46px!important}.main{margin-left:285px!important;padding:18px 12px!important}body.sidebar-collapsed .sidebar{width:78px!important}body.sidebar-collapsed .main{margin-left:96px!important}.topbar{flex-direction:column;align-items:flex-start}.header-actions{width:100%;flex-wrap:wrap}.dashboard-footer{flex-direction:column;align-items:flex-start}}
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
            <a href="personnel.php" class="active" title="Personnel"><i class="bi bi-people"></i><span>Personnel</span></a>
            <a href="presences.php" title="Présence"><i class="bi bi-check2-square"></i><span>Présence</span></a>
            <a href="emplois_temps.php" title="Emplois du temps"><i class="bi bi-calendar-week"></i><span>Emplois du temps</span></a>
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

</div></div>

<div class="main">

<div class="topbar">
    <div class="top-left">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu"><i class="bi bi-list"></i></button>
        <a href="personnel.php" class="back-btn" title="Retour au personnel"><i class="bi bi-arrow-left"></i></a>
        <div><h1 class="page-title">Ajouter un personnel</h1><div class="page-subtitle">Créer un dossier personnel et un compte de connexion temporaire</div></div>
    </div>
    <div class="header-actions">
        <div class="year-badge"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($annee['libelle'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="dark-toggle" id="darkToggle" title="Mode sombre"><i class="bi bi-moon-fill"></i></button>
    </div>
</div>

<div class="form-card">


<?php if(isset($_SESSION['success_compte'])): ?>

<div class="alert alert-success shadow-sm">

<h5 class="mb-3">

<i class="bi bi-check-circle-fill"></i>
 Personnel enregistré avec succès

</h5>

<hr>

<p>

<b>Identifiant de connexion :</b>

<?= htmlspecialchars(
    $_SESSION['success_compte']['identifiant'],
    ENT_QUOTES,
    'UTF-8'
) ?>


</p>

<p>

<b>Mot de passe temporaire :</b>

<?= htmlspecialchars(
    $_SESSION['success_compte']['password'],
    ENT_QUOTES,
    'UTF-8'
) ?>

</p>

<p class="mb-0 text-danger">

⚠️ Notez ce mot de passe.
Il ne sera plus affiché après.

</p>

</div>

<?php unset($_SESSION['success_compte']); ?>

<?php endif; ?>


<?php if(!empty($error)): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<input
type="hidden"
name="token"
value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>"
>

<div class="section-title">

Informations générales

</div>

<div class="row">

<div class="col-md-3 text-center">

<img
src="assets/avatar.png"
id="preview"
class="preview shadow"
alt="Photo"
>

<br><br>

<input
type="file"
name="photo"
class="form-control"
accept="image/*"
onchange="loadPreview(event)"
required
>

</div>

<div class="col-md-9">

<div class="row">

<div class="col-md-4 mb-3">

<label>Identifiant</label>

<input
type="text"
name="identifiant"
class="form-control"
value="EMP<?= rand(1000,9999) ?>"
readonly
>

</div>

<div class="col-md-4 mb-3">

<label>Nom</label>

<input
type="text"
name="nom"
class="form-control"
required
>

</div>

<div class="col-md-4 mb-3">

<label>Prénoms</label>

<input
type="text"
name="prenom"
class="form-control"
required
>

</div>

<div class="col-md-4 mb-3">

<label>Date naissance</label>

<input
type="date"
name="date_naissance"
class="form-control"
>

</div>

<div class="col-md-4 mb-3">

<label>Lieu naissance</label>

<input
type="text"
name="lieu_naissance"
class="form-control"
>

</div>

<div class="col-md-4 mb-3">

<label>Numéro CNI</label>

<input
type="text"
name="cni"
class="form-control"
>

</div>

<div class="col-md-6 mb-3">

<label>Téléphone</label>

<div class="input-group">

<span class="input-group-text">

🇨🇮 +225

</span>

<input
type="text"
name="telephone"
class="form-control"
placeholder="0700000000"
>

</div>

</div>

<div class="col-md-6 mb-3">

<label>Téléphone urgence</label>

<div class="input-group">

<span class="input-group-text">

🇨🇮 +225

</span>

<input
type="text"
name="telephone_urgence"
class="form-control"
>

</div>

</div>

<div class="col-md-6 mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control"
>

</div>

<div class="col-md-6 mb-3">

<label>Nationalité</label>

<select
name="nationalite"
class="form-select"
>

<option value="">Choisir</option>

<option value="Côte d'Ivoire">🇨🇮 Côte d'Ivoire</option>
<option value="France">🇫🇷 France</option>
<option value="Mali">🇲🇱 Mali</option>
<option value="Burkina Faso">🇧🇫 Burkina Faso</option>
<option value="Guinée">🇬🇳 Guinée</option>
<option value="Sénégal">🇸🇳 Sénégal</option>
<option value="Togo">🇹🇬 Togo</option>
<option value="Bénin">🇧🇯 Bénin</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Année scolaire</label>

<select
name="annee_scolaire"
class="form-select"
required
>

<option value="2025-2026">2025-2026</option>
<option value="2026-2027">2026-2027</option>
<option value="2027-2028">2027-2028</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Fonction</label>

<select
name="fonction"
id="fonction"
class="form-select"
required
>

<option value="">Choisir</option>

<option value="DE">DE</option>

<option value="ADE">ADE</option>

<option value="DAF">DAF</option>

<option value="SECRETAIRE">SECRETAIRE</option>

<option value="EDUCATEUR">EDUCATEUR</option>

<option value="INSPECTEUR EDUCATION">
INSPECTEUR EDUCATION
</option>

<option value="INSPECTEUR ORIENTATION">
INSPECTEUR ORIENTATION
</option>

<option value="PROFESSEUR">
PROFESSEUR
</option>

<option value="INFORMATICIEN">
INFORMATICIEN
</option>

<option value="STAGIAIRE">
STAGIAIRE
</option>

<option value="GARDIEN">
GARDIEN
</option>

</select>

</div>

<div
class="col-md-4 mb-3"
id="matiereBox"
style="display:none;"
>

<label>Matière</label>

<select
name="matiere"
class="form-select"
>

<option value="">Choisir</option>

<option value="Math">Math</option>
<option value="PC">PC</option>
<option value="Français">Français</option>
<option value="HG">HG</option>
<option value="SVT">SVT</option>
<option value="Allemand">Allemand</option>
<option value="Espagnol">Espagnol</option>
<option value="EDHC">EDHC</option>
<option value="Philo">Philo</option>
<option value="Anglais">Anglais</option>
<option value="Musique">Musique</option>
<option value="Art Plastique">Art Plastique</option>
<option value="EPS">EPS</option>

</select>

</div>

<div
class="col-md-4 mb-3"
id="niveauBox"
style="display:none;"
>

<label>Niveau encadrement</label>

<select
name="niveau"
class="form-select"
>

<option value="6e">6e</option>
<option value="5e">5e</option>
<option value="4e">4e</option>
<option value="3e">3e</option>
<option value="2nde">2nde</option>
<option value="1ère">1ère</option>
<option value="Terminale">Terminale</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Niveau étude</label>

<select
name="niveau_etude"
class="form-select"
>

<option value="CM1">CM1</option>
<option value="CM2">CM2</option>
<option value="6e">6e</option>
<option value="5e">5e</option>
<option value="4e">4e</option>
<option value="3e">3e</option>
<option value="2nde">2nde</option>
<option value="1ère">1ère</option>
<option value="Tle">Tle</option>
<option value="BAC+1">BAC+1</option>
<option value="BAC+2">BAC+2</option>
<option value="BAC+3">BAC+3</option>
<option value="BAC+4">BAC+4</option>
<option value="BAC+5">BAC+5</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Diplôme</label>

<select
name="diplome"
class="form-select"
>

<option value="CEPE">CEPE</option>
<option value="BEPC">BEPC</option>
<option value="BAC">BAC</option>
<option value="BTS">BTS</option>
<option value="DEUG">DEUG</option>
<option value="LICENCE">LICENCE</option>
<option value="MASTER">MASTER</option>
<option value="MAITRISE">MAITRISE</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Prof principal</label>

<select
name="prof_principal"
class="form-select"
>

<option value="">Choisir</option>

<option value="6e1">6e1</option>
<option value="6e2">6e2</option>
<option value="6e3">6e3</option>
<option value="6e4">6e4</option>

<option value="5e1">5e1</option>
<option value="5e2">5e2</option>
<option value="5e3">5e3</option>
<option value="5e4">5e4</option>

<option value="4e1">4e1</option>
<option value="4e2">4e2</option>
<option value="4e3">4e3</option>
<option value="4e4">4e4</option>

<option value="3e1">3e1</option>
<option value="3e2">3e2</option>
<option value="3e3">3e3</option>
<option value="3e4">3e4</option>

<option value="2nd C1">2nd C1</option>
<option value="2nd C2">2nd C2</option>
<option value="2nd A1">2nd A1</option>
<option value="2nd A2">2nd A2</option>

<option value="1ere D1">1ere D1</option>
<option value="1ere D2">1ere D2</option>
<option value="1ere C1">1ere C1</option>
<option value="1ere C2">1ere C2</option>

<option value="Tle D1">Tle D1</option>
<option value="Tle D2">Tle D2</option>
<option value="Tle C1">Tle C1</option>
<option value="Tle C2">Tle C2</option>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Date embauche</label>

<input
type="date"
name="date_embauche"
class="form-control"
>

</div>

<div class="col-md-4 mb-3">

<label>Date début contrat</label>

<input
type="date"
name="date_debut_contrat"
class="form-control"
>

</div>

<div class="col-md-4 mb-3">

<label>Date fin contrat</label>

<input
type="date"
name="date_fin_contrat"
class="form-control"
>

</div>

<div class="col-md-4 mb-3">

<label>Numéro contrat</label>

<input
type="text"
name="numero_contrat"
class="form-control"
>

</div>

</div>

</div>

</div>
<div
class="section-title mt-4"
id="emploiSection"
style="display:none;"
>

Mini emploi du temps du professeur

</div>

<div
class="row"
id="emploiBox"
style="display:none;"
>

<div class="col-md-12 mb-3">

<div class="alert alert-info">

Le professeur peut avoir plusieurs heures de cours.
Cochez uniquement les heures où il doit être présent.

</div>

</div>

<?php

$jours = [
    "Lundi",
    "Mardi",
    "Mercredi",
    "Jeudi",
    "Vendredi",
    "Samedi"
];

$heures = [

    "08H-09H",
    "09H-10H",
    "10H-11H",
    "11H-12H",
    "14H-15H",
    "15H-16H",
    "16H-17H",
    "17H-18H"

];

foreach($jours as $jour):

?>

<div class="col-md-6 mb-4">

<div class="card shadow-sm">

<div class="card-header bg-dark text-white">

<?= $jour ?>

</div>

<div class="card-body">

<?php foreach($heures as $heure): ?>

<div class="form-check mb-2">

<div class="border rounded p-3 mb-3">

<h6 class="fw-bold">

<?= $heure ?>

</h6>

<div class="row">

<div class="col-md-6 mb-2">

<label>Matière</label>

<select
name="matiere_emploi[<?= $jour ?>][<?= $heure ?>]"
class="form-select"
>

<option value="">Choisir</option>

<option value="Math">Math</option>
<option value="PC">PC</option>
<option value="Français">Français</option>
<option value="HG">HG</option>
<option value="SVT">SVT</option>
<option value="Allemand">Allemand</option>
<option value="Espagnol">Espagnol</option>
<option value="EDHC">EDHC</option>
<option value="Philo">Philo</option>
<option value="Anglais">Anglais</option>
<option value="Musique">Musique</option>
<option value="Art Plastique">Art Plastique</option>
<option value="EPS">EPS</option>

</select>

</div>

<div class="col-md-6 mb-2">

<label>Classe</label>

<select
name="classe_emploi[<?= $jour ?>][<?= $heure ?>]"
class="form-select"
>

<option value="">Choisir</option>

<option value="6e1">6e1</option>
<option value="6e2">6e2</option>
<option value="6e3">6e3</option>
<option value="6e4">6e4</option>

<option value="5e1">5e1</option>
<option value="5e2">5e2</option>
<option value="5e3">5e3</option>
<option value="5e4">5e4</option>

<option value="4e1">4e1</option>
<option value="4e2">4e2</option>
<option value="4e3">4e3</option>
<option value="4e4">4e4</option>

<option value="3e1">3e1</option>
<option value="3e2">3e2</option>
<option value="3e3">3e3</option>
<option value="3e4">3e4</option>

<option value="2nd C1">2nd C1</option>
<option value="2nd C2">2nd C2</option>
<option value="2nd A1">2nd A1</option>
<option value="2nd A2">2nd A2</option>

<option value="1ere D1">1ere D1</option>
<option value="1ere D2">1ere D2</option>
<option value="1ere C1">1ere C1</option>
<option value="1ere C2">1ere C2</option>
<option value="1ere A1">1ere A1</option>
<option value="1ere A2">1ere A2</option>

<option value="Tle D1">Tle D1</option>
<option value="Tle D2">Tle D2</option>
<option value="Tle C1">Tle C1</option>
<option value="Tle C2">Tle C2</option>
<option value="Tle A1">Tle A1</option>
<option value="Tle A2">Tle A2</option>

</select>

</div>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<div class="section-title mt-4">

Documents

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>CV PDF</label>

<input
type="file"
name="cv"
class="form-control"
accept=".pdf"
>

</div>

<div class="col-md-6 mb-3">

<label>Lettre motivation</label>

<input
type="file"
name="lettre_motivation"
class="form-control"
accept=".pdf"
>

</div>

<div class="col-md-6 mb-3">

<label>Contrat PDF</label>

<input
type="file"
name="contrat_pdf"
class="form-control"
accept=".pdf"
>

</div>

<div class="col-md-6 mb-3">

<label>Diplôme PDF</label>

<input
type="file"
name="diplome_pdf"
class="form-control"
accept=".pdf"
>

</div>

</div>

<button
type="submit"
name="save"
class="btn-save mt-4"
>

<i class="bi bi-check-circle"></i>

Enregistrer

</button>

</form>

</div>


<footer class="dashboard-footer">
    <div class="footer-left"><img src="uploads/logo/logo.jpeg" alt="Logo CMAK"><span>© <?= htmlspecialchars($anneeFooter, ENT_QUOTES, 'UTF-8') ?> CMAK - Gestion du personnel scolaire. Tous droits réservés.</span></div>
    <div class="footer-secure"><i class="bi bi-shield-check"></i>Données sécurisées</div>
</footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

const sidebarToggle = document.getElementById('sidebarToggle');
function setSidebarCollapsed(enabled){document.body.classList.toggle('sidebar-collapsed', enabled);localStorage.setItem('sidebar-collapsed', enabled ? 'enabled' : 'disabled');}
if(localStorage.getItem('sidebar-collapsed') === 'enabled'){setSidebarCollapsed(true);}
sidebarToggle?.addEventListener('click', function(){setSidebarCollapsed(!document.body.classList.contains('sidebar-collapsed'));});
const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');
function setDarkMode(enabled){document.body.classList.toggle('dark-mode', enabled);if(darkIcon){darkIcon.className = enabled ? 'bi bi-sun-fill' : 'bi bi-moon-fill';}localStorage.setItem('dark-mode', enabled ? 'enabled' : 'disabled');}
if(localStorage.getItem('dark-mode') === 'enabled'){setDarkMode(true);}
darkToggle?.addEventListener('click', function(){setDarkMode(!document.body.classList.contains('dark-mode'));});

function loadPreview(event){

    const preview =
    document.getElementById('preview');

    preview.src =
    URL.createObjectURL(
        event.target.files[0]
    );

}

const fonction =
document.getElementById('fonction');

const matiereBox =
document.getElementById('matiereBox');

const niveauBox =
document.getElementById('niveauBox');

const emploiBox =
document.getElementById('emploiBox');

const emploiSection =
document.getElementById('emploiSection');

fonction.addEventListener('change',function(){

    matiereBox.style.display = 'none';

    niveauBox.style.display = 'none';

    emploiBox.style.display = 'none';

    emploiSection.style.display = 'none';

    if(this.value === 'PROFESSEUR'){

        matiereBox.style.display = 'block';

        emploiBox.style.display = 'flex';

        emploiSection.style.display = 'block';

    }

    if(
        this.value === 'EDUCATEUR'
        ||
        this.value === 'INSPECTEUR EDUCATION'
        ||
        this.value === 'INSPECTEUR ORIENTATION'
    ){

        niveauBox.style.display = 'block';

    }

});

</script>

</body>

</html>