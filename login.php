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

if(isset($_SESSION['admin'])){

    $fonctionSession = strtoupper(trim($_SESSION['admin_fonction'] ?? ''));

    if($fonctionSession === 'DAF'){
        header('Location: dashboard_daf.php');
        exit();
    }

    header('Location: dashboard.php');
    exit();
}

if(isset($_POST['login'])){

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if($login === '' || $password === ''){

        $error = 'Veuillez remplir tous les champs.';

    }else{

        $sql = $pdo->prepare("
            SELECT
                id,
                username,
                nom,
                prenom,
                fonction,
                email,
                password
            FROM admins
            WHERE username = ?
               OR LOWER(email) = LOWER(?)
            LIMIT 1
        ");

        $sql->execute([$login, $login]);
        $admin = $sql->fetch(PDO::FETCH_ASSOC);

        if($admin && password_verify($password, $admin['password'])){

            session_regenerate_id(true);

            $_SESSION['admin'] = $admin['username'];
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_nom'] = $admin['nom'] ?? '';
            $_SESSION['admin_prenom'] = $admin['prenom'] ?? '';
            $_SESSION['admin_fonction'] = $admin['fonction'] ?? '';
            $_SESSION['last_activity'] = time();
            $_SESSION['session_init'] = true;

            $annee = $pdo->query("
                SELECT id, libelle
                FROM annees_scolaires
                WHERE active = 1
                LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);

            if($annee){
                $_SESSION['annee_id'] = (int)$annee['id'];
                $_SESSION['annee_libelle'] = $annee['libelle'];
            }

            $fonction = strtoupper(trim($admin['fonction'] ?? ''));

            if($fonction === 'DAF'){
                header('Location: dashboard_daf.php');
                exit();
            }

            header('Location: dashboard.php');
            exit();

        }else{

            $error = 'Identifiant, adresse e-mail ou mot de passe incorrect.';

        }

    }

}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Connexion | CMAK Facobly</title>

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
    --primary:#1d4ed8;
    --primary-dark:#1e3a8a;
    --primary-soft:#dbeafe;
    --danger:#dc2626;
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
        radial-gradient(circle at top left,rgba(37,99,235,.13),transparent 28%),
        radial-gradient(circle at bottom right,rgba(245,158,11,.10),transparent 26%),
        var(--bg);
}

.login-card{
    width:min(500px,100%);
    background:var(--card);
    border:1px solid rgba(226,232,240,.95);
    border-radius:28px;
    overflow:hidden;
    box-shadow:var(--shadow);
    animation:fadeUp .55s ease both;
}

.login-cover{
    position:relative;
    padding:28px 30px;
    min-height:180px;
    background:
        linear-gradient(135deg,rgba(15,23,42,.96),rgba(29,78,216,.90)),
        url('uploads/logo/logo.jpeg') center/cover;
    color:#fff;
    overflow:hidden;
}

.login-cover::before{
    content:'';
    position:absolute;
    width:180px;
    height:180px;
    border-radius:50%;
    right:-65px;
    top:-80px;
    background:rgba(255,255,255,.10);
}

.login-cover::after{
    content:'';
    position:absolute;
    width:120px;
    height:120px;
    border-radius:50%;
    left:-42px;
    bottom:-55px;
    background:rgba(245,158,11,.14);
}

.brand{
    position:relative;
    z-index:1;
    display:flex;
    align-items:center;
    gap:15px;
}

.brand-logo{
    width:70px;
    height:70px;
    border-radius:20px;
    object-fit:cover;
    border:3px solid rgba(255,255,255,.88);
    box-shadow:0 14px 30px rgba(0,0,0,.22);
}

.brand h1{
    font-family:'Sora',sans-serif;
    font-size:22px;
    font-weight:800;
    margin:0 0 4px;
}

.brand p{
    margin:0;
    font-size:13px;
    color:rgba(255,255,255,.74);
}

.login-cover-note{
    position:relative;
    z-index:1;
    margin-top:26px;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:12px;
    font-weight:700;
    color:rgba(255,255,255,.82);
}

.login-cover-note i{
    width:32px;
    height:32px;
    border-radius:11px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.12);
}

.login-body{
    padding:34px 32px 30px;
}

.login-heading{
    margin-bottom:24px;
}

.login-heading h2{
    font-family:'Sora',sans-serif;
    font-size:27px;
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
    margin-bottom:20px;
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
    min-height:52px;
    border-radius:15px;
    border:1px solid var(--line);
    padding:12px 48px 12px 44px;
    font-size:14px;
    box-shadow:none!important;
    transition:.2s;
}

.form-control:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 .22rem rgba(37,99,235,.10)!important;
}

.password-toggle{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    width:38px;
    height:38px;
    border:none;
    background:transparent;
    border-radius:11px;
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
    margin:4px 0 22px;
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
    min-height:52px;
    border:none;
    border-radius:15px;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff;
    font-weight:800;
    font-size:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    box-shadow:0 14px 28px rgba(37,99,235,.22);
    transition:.22s;
}

.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 34px rgba(37,99,235,.28);
}

.login-footer{
    margin-top:24px;
    padding-top:18px;
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

@media(max-width:540px){

    body{
        padding:14px;
    }

    .login-card{
        border-radius:23px;
    }

    .login-cover{
        padding:24px 22px;
    }

    .brand-logo{
        width:60px;
        height:60px;
        border-radius:18px;
    }

    .brand h1{
        font-size:18px;
    }

    .login-body{
        padding:28px 20px 24px;
    }

    .login-heading h2{
        font-size:23px;
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
                <p>Gestion intelligente du personnel</p>
            </div>

        </div>


    </div>

    <div class="login-body">

        <div class="login-heading">

            <h2>Bienvenue</h2>

            <p>
                Saisissez vos identifiants pour accéder à votre espace de travail.
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

                <label for="login" class="form-label">
                    Identifiant ou adresse e-mail
                </label>

                <div class="input-wrap">

                    <i class="bi bi-person-fill"></i>

                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="form-control"
                        placeholder="Saisissez votre identifiant ou votre adresse e-mail"
                        value="<?= htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                        id="rememberLogin"
                    >

                    <label class="form-check-label" for="rememberLogin">
                        Mémoriser mon identifiant ou mon e-mail
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

const loginInput = document.getElementById('login');
const rememberLogin = document.getElementById('rememberLogin');
const savedLogin = localStorage.getItem('cmak-admin-login');

if(savedLogin && loginInput && rememberLogin){

    loginInput.value = savedLogin;
    rememberLogin.checked = true;

}

rememberLogin?.addEventListener('change', function(){

    if(this.checked){

        localStorage.setItem(
            'cmak-admin-login',
            loginInput.value.trim()
        );

    }else{

        localStorage.removeItem('cmak-admin-login');
    }

});

</script>   