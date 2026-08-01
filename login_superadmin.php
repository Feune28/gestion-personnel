<?php

session_start();

require_once __DIR__ . '/connexion.php';

if (
    isset($_SESSION['superadmin_id']) &&
    isset($_SESSION['superadmin_role']) &&
    $_SESSION['superadmin_role'] === 'SUPER_ADMIN'
) {
    header('Location: dashbord_superadmin.php');
    exit;
}

$erreur = '';
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifiant === '' || $password === '') {

        $erreur = 'Veuillez renseigner votre identifiant et votre mot de passe.';

    } else {

        $requete = $pdo->prepare("
            SELECT
                id,
                username,
                password,
                nom,
                prenom,
                email,
                fonction,
                role,
                statut_compte
            FROM admins
            WHERE (email = :identifiant OR username = :identifiant)
            AND role = 'SUPER_ADMIN'
            LIMIT 1
        ");

        $requete->execute(['identifiant' => $identifiant]);

        $admin = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {

            $erreur = 'Aucun compte Super Administrateur trouvé avec cet identifiant.';

        } elseif (($admin['statut_compte'] ?? 'actif') !== 'actif') {

            $erreur = 'Votre compte Super Administrateur est suspendu.';

        } elseif (!password_verify($password, $admin['password'])) {

            $erreur = 'Mot de passe incorrect.';

        } else {

            session_regenerate_id(true);

            $_SESSION['superadmin_id'] = (int)$admin['id'];
            $_SESSION['superadmin_nom'] = $admin['nom'];
            $_SESSION['superadmin_prenom'] = $admin['prenom'];
            $_SESSION['superadmin_email'] = $admin['email'];
            $_SESSION['superadmin_role'] = $admin['role'];
            $_SESSION['superadmin_fonction'] = $admin['fonction'] ?? 'Super Administrateur';

            header('Location: dashbord_superadmin.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion Super Administrateur | CMAK</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(
                     135deg,
        #dbe0e9 0%,
        #cedef8 45%,
        #fdffff 100%
                );
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 980px;
            min-height: 590px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #ffffff;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
        }

        .presentation {
            background:
                linear-gradient(
                    145deg,
                    rgba(15, 23, 42, 0.96),
                    rgba(30, 41, 59, 0.94)
                );
            color: white;
            padding: 60px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-box {
            width: 95px;
            height: 95px;
            border-radius: 22px;
            background: white;
            padding: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 16px;
        }

        .presentation h1 {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .presentation h5 {
            color: #f97316;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .presentation p {
            color: #cbd5e1;
            line-height: 1.8;
            font-size: 16px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            color: #e2e8f0;
        }

        .security-item i {
            width: 40px;
            height: 40px;
            background: rgba(249, 115, 22, 0.15);
            color: #f97316;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-section h2 {
            color: #0f172a;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .form-section .subtitle {
            color: #64748b;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 700;
            color: #334155;
        }

        .input-group-text {
            background: #f8fafc;
            border-right: 0;
        }

        .form-control {
            min-height: 52px;
            border-left: 0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }

        .btn-login {
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            font-weight: 800;
            width: 100%;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: white;
        }

        .footer-text {
            margin-top: 30px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 800px) {

            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .presentation {
                display: none;
            }

            .form-section {
                padding: 40px 25px;
            }
        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <section class="presentation">

        <div class="logo-box">

            <img
                src="/app_exp/uploads/logo/logo.jpeg"
                alt="Logo CMAK"
                onerror="this.style.display='none'"
            >

        </div>

        <h1>CMAK Facobly</h1>

        <h5>ESPACE SUPER ADMINISTRATEUR</h5>

        <p>
            Gérez les comptes du Directeur des Études et du Directeur
            Administratif et Financier à partir d'un espace unique et sécurisé.
        </p>

        <div class="security-item">

            <i class="bi bi-shield-lock-fill"></i>

            <span>Accès réservé au Super Administrateur</span>

        </div>

        <div class="security-item">

            <i class="bi bi-person-gear"></i>

            <span>Gestion des comptes DE et DAF</span>

        </div>

        <div class="security-item">

            <i class="bi bi-clock-history"></i>

            <span>Suivi des activités administratives</span>

        </div>

    </section>

    <section class="form-section">

        <h2>Bienvenue</h2>

        <p class="subtitle">
            Connectez-vous pour accéder à l'administration générale.
        </p>

        <?php if ($erreur !== ''): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <?= htmlspecialchars($erreur) ?>

            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="mb-4">

                <label for="identifiant" class="form-label">
                    Identifiant ou adresse email
                </label>

                <div class="input-group">

                    <span class="input-group-text">

                        <i class="bi bi-person-fill"></i>

                    </span>

                    <input
                        type="text"
                        name="identifiant"
                        id="identifiant"
                        class="form-control"
                        value="<?= htmlspecialchars($identifiant) ?>"
                        placeholder="superadmin ou superadmin@cmak.ci"
                        required
                        autofocus
                    >

                </div>

            </div>

            <div class="mb-4">

                <label for="password" class="form-label">
                    Mot de passe
                </label>

                <div class="input-group">

                    <span class="input-group-text">

                        <i class="bi bi-lock-fill"></i>

                    </span>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Saisissez votre mot de passe"
                        required
                    >

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="afficherMotDePasse"
                    >

                        <i class="bi bi-eye-fill" id="iconeMotDePasse"></i>

                    </button>

                </div>

            </div>

            <button type="submit" class="btn btn-login">

                <i class="bi bi-box-arrow-in-right me-2"></i>

                Se connecter

            </button>

        </form>

        <div class="footer-text">

            © <?= date('Y') ?> CMAK Facobly — Administration sécurisée

        </div>

    </section>

</div>

<script>

    const boutonMotDePasse = document.getElementById('afficherMotDePasse');
    const champMotDePasse = document.getElementById('password');
    const iconeMotDePasse = document.getElementById('iconeMotDePasse');

    boutonMotDePasse.addEventListener('click', function () {

        if (champMotDePasse.type === 'password') {

            champMotDePasse.type = 'text';
            iconeMotDePasse.classList.remove('bi-eye-fill');
            iconeMotDePasse.classList.add('bi-eye-slash-fill');

        } else {

            champMotDePasse.type = 'password';
            iconeMotDePasse.classList.remove('bi-eye-slash-fill');
            iconeMotDePasse.classList.add('bi-eye-fill');

        }
    });

</script>

</body>
</html>