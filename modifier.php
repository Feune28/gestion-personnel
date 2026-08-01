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

// Afficher les vraies erreurs SQL au lieu d'échouer silencieusement.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$updateError = '';

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

$annee = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1 LIMIT 1")->fetch();
$anneeFooter = $annee['libelle'] ?? date('Y');

$id = intval($_GET['id'] ?? 0);

if($id <= 0){
    die("ID invalide");
}

$sql = $pdo->prepare("SELECT * FROM personnels WHERE id=?");
$sql->execute([$id]);

$p = $sql->fetch();

if(!$p){
    die("Personnel introuvable");
}

/*
|--------------------------------------------------------------------------
| EMPLOI DU TEMPS
|--------------------------------------------------------------------------
*/

$edtReq = $pdo->prepare(
    "SELECT *
     FROM emplois_temps_professeurs
     WHERE personnel_id=?"
);

$edtReq->execute([$id]);

$edtData = [];

while($e = $edtReq->fetch()){

    $edtData[$e['jour']][$e['plage_horaire']] = [

        'matiere' => $e['matiere'],
        'classe'  => $e['classe']

    ];

}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/

if(empty($_SESSION['token'])){

    $_SESSION['token'] = bin2hex(random_bytes(32));

}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

if(isset($_POST['update'])){

    if(
        !isset($_POST['token'])
        ||
        !hash_equals($_SESSION['token'], $_POST['token'])
    ){
        die("Token invalide");
    }

    $nom                = mb_substr(trim($_POST['nom'] ?? ''), 0, 100);
    $prenom             = mb_substr(trim($_POST['prenom'] ?? ''), 0, 100);
    $date_naissance     = trim($_POST['date_naissance'] ?? '');
    $date_naissance     = $date_naissance !== '' ? $date_naissance : null;
    $lieu_naissance     = mb_substr(trim($_POST['lieu_naissance'] ?? ''), 0, 100);
    $cni                = mb_substr(trim($_POST['cni'] ?? ''), 0, 50);
    $telephone          = mb_substr(trim($_POST['telephone'] ?? ''), 0, 30);
    $telephone_urgence  = mb_substr(trim($_POST['telephone_urgence'] ?? ''), 0, 30);
    $email              = mb_substr(trim($_POST['email'] ?? ''), 0, 100);
    $nationalite        = mb_substr(trim($_POST['nationalite'] ?? ''), 0, 50);
    $fonction           = mb_substr(trim($_POST['fonction'] ?? ''), 0, 100);
    $matiere            = mb_substr(trim($_POST['matiere'] ?? ''), 0, 100);
    $niveau             = mb_substr(trim($_POST['niveau'] ?? ''), 0, 100);
    $niveau_etude       = mb_substr(trim($_POST['niveau_etude'] ?? ''), 0, 100);
    $diplome            = mb_substr(trim($_POST['diplome'] ?? ''), 0, 100);
    $prof_principal     = mb_substr(trim($_POST['prof_principal'] ?? ''), 0, 50);
    $date_embauche      = trim($_POST['date_embauche'] ?? '');
    $date_embauche      = $date_embauche !== '' ? $date_embauche : null;
    $date_debut_contrat = trim($_POST['date_debut_contrat'] ?? '');
    $date_debut_contrat = $date_debut_contrat !== '' ? $date_debut_contrat : null;
    $date_fin_contrat   = trim($_POST['date_fin_contrat'] ?? '');
    $date_fin_contrat   = $date_fin_contrat !== '' ? $date_fin_contrat : null;
    $numero_contrat     = mb_substr(trim($_POST['numero_contrat'] ?? ''), 0, 50);
    $annee_scolaire     = mb_substr(trim($_POST['annee_scolaire'] ?? ''), 0, 20);

    /*
    |--------------------------------------------------------------------------
    | PHOTO
    |--------------------------------------------------------------------------
    */

    $photo = $p['photo'];

    if(
        isset($_FILES['photo'])
        &&
        $_FILES['photo']['error'] === 0
    ){

        $maxPhoto = 2 * 1024 * 1024;

        if($_FILES['photo']['size'] <= $maxPhoto){

            $mime = mime_content_type($_FILES['photo']['tmp_name']);

            $allowedMime = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $ext = strtolower(
                pathinfo(
                    $_FILES['photo']['name'],
                    PATHINFO_EXTENSION
                )
            );

            $allowedExt = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if(
                in_array($mime, $allowedMime)
                &&
                in_array($ext, $allowedExt)
            ){

                if(!is_dir('uploads/photos')){
                    mkdir('uploads/photos', 0755, true);
                }

                $photo =
                uniqid('photo_', true).'.'.$ext;

                move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    'uploads/photos/'.$photo
                );

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS PDF
    |--------------------------------------------------------------------------
    */

    foreach(
        [
            'cv'                  => 'cv',
            'lettre_motivation'  => 'lettres',
            'contrat_pdf'        => 'contrats',
            'diplome_pdf'        => 'diplomes'
        ]

        as $field => $folder
    ){

        $$field = $p[$field];

        if(
            isset($_FILES[$field])
            &&
            $_FILES[$field]['error'] === 0
        ){

            $maxPdf = 5 * 1024 * 1024;

            if($_FILES[$field]['size'] <= $maxPdf){

                $mime = mime_content_type(
                    $_FILES[$field]['tmp_name']
                );

                if($mime === 'application/pdf'){

                    if(!is_dir('uploads/'.$folder)){
                        mkdir('uploads/'.$folder, 0755, true);
                    }

                    $$field =
                    uniqid($field.'_', true).'.pdf';

                    move_uploaded_file(
                        $_FILES[$field]['tmp_name'],
                        'uploads/'.$folder.'/'.$$field
                    );

                }

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SQL
    |--------------------------------------------------------------------------
    */

    try {

        $pdo->beginTransaction();

        $update = $pdo->prepare(

        "UPDATE personnels SET

        nom=?,
        prenom=?,
        date_naissance=?,
        lieu_naissance=?,
        cni=?,
        telephone=?,
        telephone_urgence=?,
        email=?,
        nationalite=?,
        fonction=?,
        matiere=?,
        niveau_encadrement=?,
        niveau_etude=?,
        diplome=?,
        prof_principal=?,
        date_embauche=?,
        date_debut_contrat=?,
        date_fin_contrat=?,
        numero_contrat=?,
        annee_scolaire=?,
        photo=?,
        cv=?,
        lettre_motivation=?,
        contrat_pdf=?,
        diplome_pdf=?

        WHERE id=?"

    );

    $update->execute([

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
        $photo,
        $cv,
        $lettre_motivation,
        $contrat_pdf,
        $diplome_pdf,
        $id

    ]);
    /*
|--------------------------------------------------------------------------
| SYNCHRONISATION DU COMPTE PERSONNEL
|--------------------------------------------------------------------------
*/

$updateCompte = $pdo->prepare("
    UPDATE comptes_personnels
    SET
        email = ?,
        email_verifie = 0
    WHERE personnel_id = ?
");

$updateCompte->execute([
    $email,
    $id
]);

    /*
    |--------------------------------------------------------------------------
    | EMPLOI DU TEMPS
    |--------------------------------------------------------------------------
    */

    $pdo->prepare(
        "DELETE FROM emplois_temps_professeurs
         WHERE personnel_id=?"
    )->execute([$id]);

    $matiere_emploi =
    $_POST['matiere_emploi'] ?? [];

    $classe_emploi =
    $_POST['classe_emploi'] ?? [];

    foreach($matiere_emploi as $jour => $heures){

        foreach($heures as $heure => $matiereChoisie){

            $classeChoisie =
            $classe_emploi[$jour][$heure] ?? '';

            if(
                !empty($matiereChoisie)
                &&
                !empty($classeChoisie)
            ){

                $heureParts =
                explode('-', $heure);

                $heure_debut =
                str_replace(
                    'H',
                    ':00',
                    trim($heureParts[0])
                );

                $heure_fin =
                str_replace(
                    'H',
                    ':00',
                    trim($heureParts[1])
                );

                $insert =
                $pdo->prepare(

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

                $insert->execute([

                    $id,
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

        $pdo->commit();

        $_SESSION['success_message'] = 'Les informations du personnel ont été modifiées avec succès.';
        header("Location: voir.php?id=".$id);
        exit();

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Erreur modification personnel ID '.$id.' : '.$e->getMessage());
        $updateError = 'La modification a échoué : '.$e->getMessage();
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

<title>

Modifier personnel

</title>

<link
rel="preconnect"
href="https://fonts.googleapis.com"
>

<link
rel="preconnect"
href="https://fonts.gstatic.com"
crossorigin
>

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

h1,h2,h3,.page-title,.card-title{
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

.menu::-webkit-scrollbar{width:5px}
.menu::-webkit-scrollbar-track{background:transparent}
.menu::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.menu::-webkit-scrollbar-thumb:hover{background:var(--primary)}

.menu-section{margin-bottom:10px}

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

/* MAIN */

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

.save-top{
    border:none;
    background:var(--primary);
    color:white;
    padding:12px 20px;
    border-radius:14px;
    font-size:14px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:10px;
    transition:.25s;
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.save-top:hover{
    transform:translateY(-1px);
    background:#0f172a;
}

.card-box{
    padding:26px;
    margin-bottom:25px;
}

.card-header-custom{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:22px;
}

.card-icon{
    width:46px;
    height:46px;
    border-radius:16px;
    background:var(--primary-soft);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    color:var(--primary);
}

.card-title{
    font-size:19px;
    font-weight:800;
    color:var(--text);
}

.photo-area{
    display:flex;
    align-items:center;
    gap:30px;
    flex-wrap:wrap;
}

.preview{
    width:120px;
    height:120px;
    border-radius:34px;
    object-fit:cover;
    border:5px solid #fff;
    box-shadow:0 18px 34px rgba(15,23,42,.14);
}

.photo-text h5{
    font-size:18px;
    font-weight:800;
    color:var(--text);
}

.photo-text p{
    color:var(--muted);
    margin:6px 0 14px;
    font-weight:600;
}

.form-label{
    font-size:12px;
    font-weight:800;
    color:var(--text);
    margin-bottom:7px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.form-control{
    border-radius:14px;
    border:1px solid var(--line);
    min-height:48px;
    font-size:14px;
    box-shadow:none!important;
    background:#fff;
}

.form-control:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 4px rgba(37,99,235,.10)!important;
}

.edt-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:20px;
}

.day-box{
    border:1px solid var(--line);
    border-radius:18px;
    overflow:hidden;
    background:#fff;
}

.day-title{
    background:#0f172a;
    color:white;
    padding:14px 18px;
    font-weight:800;
    font-size:15px;
}

.day-content{
    padding:15px;
}

.slot{
    background:#f8fafc;
    border:1px solid var(--line);
    border-radius:14px;
    padding:12px;
    margin-bottom:12px;
}

.slot:last-child{
    margin-bottom:0;
}

.slot-hour{
    font-size:12px;
    font-weight:800;
    color:var(--primary);
    margin-bottom:10px;
}

.doc-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
}

.doc-box{
    border:1px solid var(--line);
    border-radius:18px;
    padding:20px;
    background:#fff;
}

.doc-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
    margin-bottom:14px;
    color:var(--text);
}

.doc-title i{
    color:var(--danger);
    font-size:18px;
}

.current-file{
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    color:var(--primary);
    font-size:13px;
    margin-bottom:14px;
    font-weight:800;
}

.current-file:hover{
    text-decoration:underline;
}

.footer-save{
    position:sticky;
    bottom:0;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(14px);
    border:1px solid rgba(226,232,240,.9);
    border-radius:var(--radius);
    padding:16px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    box-shadow:var(--shadow-sm);
    z-index:40;
}

.footer-save p{
    margin:0;
    color:var(--muted);
    font-size:13px;
    font-weight:700;
}

.btn-save{
    border:none;
    background:var(--primary);
    color:white;
    padding:13px 22px;
    border-radius:14px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:10px;
    transition:.25s;
}

.btn-save:hover{
    background:#0f172a;
    transform:translateY(-1px);
}

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
body.dark-mode .back-btn,
body.dark-mode .footer-save{
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
body.dark-mode .page-title,
body.dark-mode .card-title,
body.dark-mode .photo-text h5,
body.dark-mode .form-label,
body.dark-mode .doc-title{
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

body.dark-mode .form-control,
body.dark-mode .day-box,
body.dark-mode .slot,
body.dark-mode .doc-box{
    background:#111827;
    border-color:#2d3340;
    color:#e5e7eb;
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

    .footer-save,
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
    <img src="uploads/logo/logo.jpeg" alt="Logo">
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
    <a href="personnel.php" class="active" title="Personnel">
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
    <a href="documents.php" title="Documents">
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

<form method="POST" enctype="multipart/form-data">

<?php if($updateError !== ''): ?>
<div class="alert alert-danger mb-4">
    <strong>Erreur d’enregistrement :</strong><br>
    <?= htmlspecialchars($updateError, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<input
type="hidden"
name="token"
value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>"
>

<!-- TOPBAR -->

<div class="topbar">

<div class="top-left">

<button type="button" class="dashboard-menu-toggle" id="sidebarToggle" title="Réduire / agrandir le menu">
    <i class="bi bi-list"></i>
</button>

<a
href="voir.php?id=<?= $id ?>"
class="back-btn"
title="Retour au dossier"
>

<i class="bi bi-arrow-left"></i>

</a>

<div>

<div class="page-title">

Modifier personnel

</div>

<div class="page-subtitle">

<?= htmlspecialchars($p['nom'].' '.$p['prenom'], ENT_QUOTES, 'UTF-8') ?>

·

<?= htmlspecialchars($p['fonction'], ENT_QUOTES, 'UTF-8') ?>

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

<button
type="submit"
name="update"
class="save-top"
>

<i class="bi bi-check2-circle"></i>

Enregistrer

</button>

</div>

</div>

<!-- PHOTO -->

<div class="card-box">

<div class="card-header-custom">

<div class="card-icon">

<i class="bi bi-camera"></i>

</div>

<div class="card-title">

Photo de profil

</div>

</div>

<div class="photo-area">

<img
src="uploads/photos/<?= htmlspecialchars($p['photo']) ?>"
class="preview"
id="preview"
onerror="this.src='assets/avatar.png'"
>

<div class="photo-text">

<h5>

Modifier la photo

</h5>

<p>

Formats acceptés : JPG, PNG, WEBP · Max 2 Mo

</p>

<input
type="file"
name="photo"
class="form-control"
accept=".jpg,.jpeg,.png,.webp"
onchange="loadPreview(event)"
>

</div>

</div>

</div>

<!-- INFORMATIONS PERSONNELLES -->

<div class="card-box">

<div class="card-header-custom">

<div class="card-icon">

<i class="bi bi-person-lines-fill"></i>

</div>

<div class="card-title">

Informations personnelles

</div>

</div>

<div class="row g-4">

<?php

$fields = [

    ['nom','Nom','text',$p['nom']],
    ['prenom','Prénom','text',$p['prenom']],
    ['date_naissance','Date naissance','date',$p['date_naissance']],
    ['lieu_naissance','Lieu naissance','text',$p['lieu_naissance']],
    ['cni','CNI','text',$p['cni']],
    ['telephone','Téléphone','text',$p['telephone']],
    ['telephone_urgence','Téléphone urgence','text',$p['telephone_urgence']],
    ['email','Email','email',$p['email']],
    ['nationalite','Nationalité','text',$p['nationalite']]

];

foreach($fields as [$name,$label,$type,$value]):

?>

<div class="col-md-4">

<label class="form-label">

<?= $label ?>

</label>

<input
type="<?= $type ?>"
name="<?= $name ?>"
class="form-control"
value="<?= htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') ?>"
>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- INFORMATIONS PROFESSIONNELLES -->

<div class="card-box">

<div class="card-header-custom">

<div class="card-icon">

<i class="bi bi-briefcase-fill"></i>

</div>

<div class="card-title">

Informations professionnelles

</div>

</div>

<div class="row g-4">

<?php

$proFields = [

    ['fonction','Fonction','text',$p['fonction']],
    ['matiere','Matière','text',$p['matiere']],
    ['niveau','Niveau encadrement','text',$p['niveau_encadrement']],
    ['niveau_etude','Niveau étude','text',$p['niveau_etude']],
    ['diplome','Diplôme','text',$p['diplome']],
    ['prof_principal','Prof principal','text',$p['prof_principal']],
    ['annee_scolaire','Année scolaire','text',$p['annee_scolaire']],
    ['numero_contrat','Numéro contrat','text',$p['numero_contrat']],
    ['date_embauche','Date embauche','date',$p['date_embauche']],
    ['date_debut_contrat','Début contrat','date',$p['date_debut_contrat']],
    ['date_fin_contrat','Fin contrat','date',$p['date_fin_contrat']]

];

foreach($proFields as [$name,$label,$type,$value]):

?>

<div class="col-md-4">

<label class="form-label">

<?= $label ?>

</label>

<input
type="<?= $type ?>"
name="<?= $name ?>"
class="form-control"
value="<?= htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') ?>"
>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- EMPLOI DU TEMPS -->

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

?>

<?php if($p['fonction'] === 'PROFESSEUR'): ?>

<div class="card-box">

<div class="card-header-custom">

<div class="card-icon">

<i class="bi bi-calendar-week-fill"></i>

</div>

<div class="card-title">

Emploi du temps

</div>

</div>

<div class="edt-grid">

<?php foreach($jours as $jour): ?>

<div class="day-box">

<div class="day-title">

<?= $jour ?>

</div>

<div class="day-content">

<?php foreach($heures as $heure): ?>

<?php

$current =
$edtData[$jour][$heure] ?? null;

?>

<div class="slot">

<div class="slot-hour">

<?= $heure ?>

</div>

<div class="row g-2">

<div class="col-6">

<input
type="text"
name="matiere_emploi[<?= $jour ?>][<?= $heure ?>]"
class="form-control"
placeholder="Matière"
value="<?= htmlspecialchars($current['matiere'] ?? '') ?>"
>

</div>

<div class="col-6">

<input
type="text"
name="classe_emploi[<?= $jour ?>][<?= $heure ?>]"
class="form-control"
placeholder="Classe"
value="<?= htmlspecialchars($current['classe'] ?? '') ?>"
>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>

<!-- DOCUMENTS -->

<div class="card-box">

<div class="card-header-custom">

<div class="card-icon">

<i class="bi bi-folder-fill"></i>

</div>

<div class="card-title">

Documents PDF

</div>

</div>

<div class="doc-grid">

<?php

$docs = [

    ['cv','CV','bi-file-earmark-person-fill'],
    ['lettre_motivation','Lettre motivation','bi-file-earmark-text-fill'],
    ['contrat_pdf','Contrat','bi-file-earmark-check-fill'],
    ['diplome_pdf','Diplôme','bi-patch-check-fill']

];

foreach($docs as [$name,$label,$icon]):

$current = $p[$name] ?? '';

?>

<div class="doc-box">

<div class="doc-title">

<i class="bi <?= $icon ?>"></i>

<?= $label ?>

</div>

<?php if(!empty($current)): ?>

<a
target="_blank"
class="current-file"
href="uploads/<?= $name === 'cv' ? 'cv' : ($name === 'lettre_motivation' ? 'lettres' : ($name === 'contrat_pdf' ? 'contrats' : 'diplomes')) ?>/<?= htmlspecialchars($current) ?>"
>

<i class="bi bi-eye"></i>

Voir fichier actuel

</a>

<?php endif; ?>

<input
type="file"
name="<?= $name ?>"
class="form-control"
accept=".pdf"
>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- FOOTER -->

<div class="footer-save">

<p>

<i class="bi bi-shield-check"></i>

Les modifications sont sécurisées par CSRF

</p>

<button
type="submit"
name="update"
class="btn-save"
>

<i class="bi bi-check2-circle"></i>

Enregistrer les modifications

</button>

</div>

</form>


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

<script>

function loadPreview(event){

    const file = event.target.files[0];

    if(file){

        document.getElementById('preview').src =
        URL.createObjectURL(file);

    }

}

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

</script>

</body>

</html>