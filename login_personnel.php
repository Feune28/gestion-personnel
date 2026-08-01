<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

require 'connexion.php';

$error = '';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

header(
    "Content-Security-Policy: default-src 'self'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:;"
);

if(isset($_SESSION['personnel'])){
    header('Location: espace_personnel.php');
    exit();
}

if(isset($_POST['login'])){

    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';

    if($identifiant === '' || $password === ''){

        $error = 'Tous les champs sont obligatoires.';

    }else{

        $sql = $pdo->prepare("
    SELECT
        cp.id,
        cp.personnel_id,
        cp.identifiant,
        cp.password,
        cp.premiere_connexion,
        cp.actif,
        p.statut
    FROM comptes_personnels cp
    INNER JOIN personnels p
        ON p.id = cp.personnel_id
  WHERE (cp.identifiant = ? OR LOWER(cp.email) = LOWER(?))
      AND cp.actif = 1
      AND p.statut = 'actif'
    LIMIT 1
     ");

        $sql->execute([$identifiant, $identifiant]);
        $compte = $sql->fetch(PDO::FETCH_ASSOC);
        if($compte && strtolower($compte['statut']) !== 'actif'){
    $compte = false;
}

        if($compte && password_verify($password, $compte['password'])){

            session_regenerate_id(true);

            $_SESSION['personnel'] = (int)$compte['personnel_id'];
            $_SESSION['compte_personnel_id'] = (int)$compte['id'];
            $_SESSION['identifiant'] = $compte['identifiant'];
            $_SESSION['last_activity'] = time();
            $_SESSION['session_init'] = true;

            if((int)$compte['premiere_connexion'] === 1){
                header('Location: changer_mot_de_passe_personnel.php');
                exit();
            }

            header('Location: espace_personnel.php');
            exit();

        }else{

            $error = 'Identifiant ou mot de passe incorrect.';

        }

    }

}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Connexion Personnel | CMAK Facobly</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap"
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
    --bg:#f4f7fb;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#e2e8f0;
    --primary:#0f766e;
    --primary-dark:#134e4a;
    --primary-soft:#ccfbf1;
    --shadow:0 24px 70px rgba(15,23,42,.14);
}

body{
    min-height:100vh;
    padding:24px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:'Inter',sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at top left,rgba(13,148,136,.13),transparent 28%),
        radial-gradient(circle at bottom right,rgba(59,130,246,.10),transparent 26%),
        var(--bg);
}

.login-card{
    width:min(440px,100%);
    background:var(--card);
    border:1px solid rgba(226,232,240,.95);
    border-radius:26px;
    overflow:hidden;
    box-shadow:var(--shadow);
    animation:fadeUp .55s ease both;
}

.login-cover{
    position:relative;
    min-height:138px;
    padding:22px 24px;
    background:
        linear-gradient(135deg,rgba(15,23,42,.96),rgba(15,118,110,.90)),
        url('uploads/logo/logo.jpeg') center/cover;
    color:#fff;
    overflow:hidden;
}

.login-cover::before{
    content:'';
    position:absolute;
    width:155px;
    height:155px;
    border-radius:50%;
    right:-58px;
    top:-70px;
    background:rgba(255,255,255,.10);
}

.login-cover::after{
    content:'';
    position:absolute;
    width:100px;
    height:100px;
    border-radius:50%;
    left:-34px;
    bottom:-48px;
    background:rgba(45,212,191,.14);
}

.brand{
    position:relative;
    z-index:1;
    display:flex;
    align-items:center;
    gap:14px;
}

.brand-logo{
    width:58px;
    height:58px;
    border-radius:17px;
    object-fit:cover;
    border:3px solid rgba(255,255,255,.88);
    box-shadow:0 14px 30px rgba(0,0,0,.22);
}

.brand h1{
    font-family:'Sora',sans-serif;
    font-size:19px;
    font-weight:800;
    margin:0 0 4px;
}

.brand p{
    margin:0;
    font-size:12px;
    color:rgba(255,255,255,.74);
}

.login-cover-note{
    position:relative;
    z-index:1;
    margin-top:18px;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:12px;
    font-weight:700;
    color:rgba(255,255,255,.82);
}

.login-cover-note i{
    width:30px;
    height:30px;
    border-radius:10px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.12);
}

.login-body{
    padding:26px 26px 24px;
}

.login-heading{
    margin-bottom:18px;
}

.login-heading h2{
    font-family:'Sora',sans-serif;
    font-size:23px;
    font-weight:800;
    margin-bottom:7px;
}

.login-heading p{
    margin:0;
    color:var(--muted);
    font-size:13px;
    line-height:1.6;
}

.alert{
    border:none;
    border-radius:15px;
    padding:13px 15px;
    font-size:13px;
    font-weight:700;
    margin-bottom:18px;
}

.form-label{
    font-size:13px;
    font-weight:800;
    color:#334155;
    margin-bottom:8px;
}

.input-wrap{
    position:relative;
}

.input-wrap > i{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
    font-size:17px;
    pointer-events:none;
}

.form-control{
    min-height:47px;
    border-radius:14px;
    border:1px solid var(--line);
    padding:10px 44px 10px 42px;
    font-size:14px;
    box-shadow:none!important;
    transition:.2s;
}

.form-control:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 .22rem rgba(13,148,136,.10)!important;
}

.password-toggle{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    width:34px;
    height:34px;
    border:none;
    background:transparent;
    border-radius:10px;
    color:#64748b;
    display:grid;
    place-items:center;
    cursor:pointer;
}

.password-toggle:hover{
    background:#f1f5f9;
    color:var(--primary);
}

.form-options{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    margin:2px 0 18px;
}

.form-check-label{
    color:var(--muted);
    font-size:13px;
}

.forgot-link{
    color:var(--primary);
    text-decoration:none;
    font-size:13px;
    font-weight:800;
}

.forgot-link:hover{
    text-decoration:underline;
}

.btn-login{
    width:100%;
    min-height:47px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff;
    font-weight:800;
    font-size:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    box-shadow:0 14px 28px rgba(13,148,136,.22);
    transition:.22s;
}

.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 34px rgba(13,148,136,.28);
}

.login-footer{
    margin-top:18px;
    padding-top:14px;
    border-top:1px solid var(--line);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    color:var(--muted);
    font-size:11px;
}

.secure{
    display:flex;
    align-items:center;
    gap:6px;
    color:#15803d;
    font-weight:800;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(18px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

@media(max-width:520px){

    body{
        padding:12px;
    }

    .login-card{
        border-radius:21px;
    }

    .login-cover{
        padding:20px 18px;
    }

    .brand-logo{
        width:52px;
        height:52px;
        border-radius:16px;
    }

    .brand h1{
        font-size:17px;
    }

    .login-body{
        padding:22px 18px 20px;
    }

    .login-heading h2{
        font-size:21px;
    }

    .form-options,
    .login-footer{
        align-items:flex-start;
        flex-direction:column;
    }

}

</style>

</head>

<body>

<div class="login-card">

    <div class="login-cover">

        <div class="brand">

            <img
                src="uploads/logo/logo.jpeg"
                alt="Logo CMAK"
                class="brand-logo"
            >

            <div>
                <h1>CMAK Facobly</h1>
                <p>Espace personnel</p>
            </div>

        </div>

        <div class="login-cover-note">

            <i class="bi bi-person-badge-fill"></i>

            <span>
                Accès réservé aux membres du personnel
            </span>

        </div>

    </div>

    <div class="login-body">

        <div class="login-heading">

            <h2>Connexion du personnel</h2>

            <p>
                Saisissez votre identifiant et votre mot de passe pour accéder à votre espace.
            </p>

        </div>

        <?php if($error !== ''): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

            </div>

        <?php endif; ?>

        <form method="POST" autocomplete="on">

            <div class="mb-3">

               <label for="identifiant" class="form-label">
    Identifiant ou adresse e-mail
</label>
                <div class="input-wrap">

                    <i class="bi bi-person-fill"></i>

                    <input
                        type="text"
                        id="identifiant"
                        name="identifiant"
                        class="form-control"
                        placeholder="Saisissez votre identifiant ou adresse e-mail"
                        value="<?= htmlspecialchars($_POST['identifiant'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >

                </div>

            </div>

            <div class="mb-3">

                <label for="password" class="form-label">
                    Mot de passe
                </label>

                <div class="input-wrap">

                    <i class="bi bi-lock-fill"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Saisissez votre mot de passe"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        title="Afficher ou masquer le mot de passe"
                        aria-label="Afficher ou masquer le mot de passe"
                    >
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

            </div>

            <div class="form-options">

                <div class="form-check">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="rememberIdentifiant"
                    >

                    <label class="form-check-label" for="rememberIdentifiant">
                        Mémoriser mon identifiant
                    </label>

                </div>

                <a href="reset_password.php" class="forgot-link">
                    Mot de passe oublié ?
                </a>

            </div>

            <button
                type="submit"
                name="login"
                class="btn-login"
            >

                <i class="bi bi-box-arrow-in-right"></i>

                Se connecter

            </button>

        </form>

        <div class="login-footer">

            <span>
                © <?= date('Y') ?> CMAK Facobly
            </span>

            <span class="secure">
                <i class="bi bi-shield-lock-fill"></i>
                Connexion sécurisée
            </span>

        </div>

    </div>

</div>

<script>

const passwordInput = document.getElementById('password');
const passwordToggle = document.getElementById('passwordToggle');
const passwordIcon = passwordToggle?.querySelector('i');

passwordToggle?.addEventListener('click', function(){

    const isHidden = passwordInput.type === 'password';

    passwordInput.type = isHidden ? 'text' : 'password';

    if(passwordIcon){
        passwordIcon.className = isHidden
            ? 'bi bi-eye-slash'
            : 'bi bi-eye';
    }

});

const identifiantInput = document.getElementById('identifiant');
const rememberIdentifiant = document.getElementById('rememberIdentifiant');
const savedIdentifiant = localStorage.getItem('cmak-personnel-identifiant');

if(savedIdentifiant && identifiantInput){

    identifiantInput.value = savedIdentifiant;
    rememberIdentifiant.checked = true;

}

rememberIdentifiant?.addEventListener('change', function(){

    if(this.checked){

        localStorage.setItem(
            'cmak-personnel-identifiant',
            identifiantInput.value.trim()
        );

    }else{

        localStorage.removeItem('cmak-personnel-identifiant');

    }

});

identifiantInput?.addEventListener('input', function(){

    if(rememberIdentifiant?.checked){

        localStorage.setItem(
            'cmak-personnel-identifiant',
            this.value.trim()
        );

    }

});

</script>

</body>
</html>