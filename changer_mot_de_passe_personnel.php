<?php

session_start();

require 'connexion.php';

if (!isset($_SESSION['personnel']) || !isset($_SESSION['compte_personnel_id'])) {
    header('Location: login_personnel.php');
    exit();
}

$error = "";
$success = "";

$compte_id = (int) $_SESSION['compte_personnel_id'];

if (isset($_POST['changer'])) {

    $password1 = trim($_POST['password1'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    if (empty($password1) || empty($password2)) {

        $error = "Veuillez remplir tous les champs.";

    } elseif (strlen($password1) < 8) {

        $error = "Le mot de passe doit contenir au moins 8 caractères.";

    } elseif ($password1 !== $password2) {

        $error = "Les deux mots de passe ne correspondent pas.";

    } else {

        $hash = password_hash($password1, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE comptes_personnels
            SET password = ?,
                premiere_connexion = 0
            WHERE id = ?
        ");

        $update->execute([
            $hash,
            $compte_id
        ]);

        header('Location: espace_personnel.php');
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

    <title>Changer le mot de passe</title>

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
            --primary: #0f172a;
            --primary-light: #1e293b;
            --secondary: #334155;
            --accent: #f97316;
            --accent-dark: #ea580c;
            --background: #f1f5f9;
            --border: #dbe3ed;
            --text: #0f172a;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at top left,
                    rgba(249, 115, 22, 0.18),
                    transparent 32%
                ),
                linear-gradient(
                    135deg,
                    #0f172a 0%,
                    #172033 45%,
                    #1e293b 100%
                );
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            top: -130px;
            right: -100px;
        }

        body::after {
            content: "";
            position: fixed;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(249, 115, 22, 0.08);
            bottom: -100px;
            left: -90px;
        }

        .password-container {
            width: 100%;
            max-width: 470px;
            position: relative;
            z-index: 2;
        }

        .brand-area {
            text-align: center;
            margin-bottom: 22px;
            color: #ffffff;
        }

        .brand-icon {
            width: 74px;
            height: 74px;
            border-radius: 22px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #ffffff;
            background: linear-gradient(
                135deg,
                var(--accent),
                var(--accent-dark)
            );
            box-shadow: 0 15px 35px rgba(249, 115, 22, 0.3);
        }

        .brand-area h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-area p {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 14px;
        }

        .password-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 36px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 65px rgba(0, 0, 0, 0.28);
        }

        .card-header-custom {
            text-align: center;
            margin-bottom: 28px;
        }

        .card-header-custom h3 {
            margin: 0 0 10px;
            font-size: 25px;
            font-weight: 800;
            color: var(--primary);
        }

        .card-header-custom p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .information-box {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 15px;
            margin-bottom: 22px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            border-radius: 13px;
            font-size: 13px;
            line-height: 1.5;
        }

        .information-box i {
            font-size: 17px;
            margin-top: 1px;
        }

        .alert {
            border: none;
            border-radius: 13px;
            font-size: 14px;
            padding: 13px 15px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
        }

        .form-group-custom {
            margin-bottom: 20px;
        }

        .form-label {
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 700;
        }

        .form-label i {
            margin-right: 6px;
            color: var(--accent);
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            min-height: 52px;
            padding: 12px 48px 12px 15px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #f8fafc;
            color: var(--primary);
            font-size: 15px;
            transition: 0.25s ease;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            border: none;
            padding: 4px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            font-size: 19px;
        }

        .toggle-password:hover {
            color: var(--accent);
        }

        .password-help {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
        }

        .password-help i {
            color: var(--accent);
        }

        .btn-main {
            min-height: 53px;
            margin-top: 5px;
            border: none;
            border-radius: 13px;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(
                135deg,
                var(--primary),
                var(--primary-light)
            );
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.2);
            transition: 0.25s ease;
        }

        .btn-main:hover {
            color: #ffffff;
            background: linear-gradient(
                135deg,
                var(--accent),
                var(--accent-dark)
            );
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(249, 115, 22, 0.25);
        }

        .btn-main:active {
            transform: translateY(0);
        }

        .btn-main i {
            margin-right: 7px;
        }

        .security-footer {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #e8edf3;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .security-footer i {
            margin-right: 5px;
            color: #16a34a;
        }

        @media (max-width: 576px) {

            body {
                padding: 18px;
                align-items: flex-start;
                padding-top: 40px;
            }

            .password-card {
                padding: 27px 21px;
                border-radius: 20px;
            }

            .brand-icon {
                width: 64px;
                height: 64px;
                font-size: 27px;
                border-radius: 19px;
            }

            .brand-area h2 {
                font-size: 21px;
            }

            .card-header-custom h3 {
                font-size: 22px;
            }

        }

    </style>

</head>

<body>

    <div class="password-container">

        <div class="brand-area">

            <div class="brand-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h2>Espace Personnel</h2>

            <p>
                Sécurisation de votre compte
            </p>

        </div>

        <div class="password-card">

            <div class="card-header-custom">

                <h3>
                    Changer votre mot de passe
                </h3>

                <p>
                    Pour protéger votre compte, définissez un nouveau mot de
                    passe personnel et sécurisé.
                </p>

            </div>

            <div class="information-box">

                <i class="bi bi-info-circle-fill"></i>

                <span>
                    Il s’agit de votre première connexion. Vous devez remplacer
                    le mot de passe temporaire avant d’accéder à votre espace.
                </span>

            </div>

            <?php if (!empty($error)): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

                </div>

            <?php endif; ?>

            <form method="POST" autocomplete="off">

                <div class="form-group-custom">

                    <label
                        for="password1"
                        class="form-label"
                    >
                        <i class="bi bi-key-fill"></i>
                        Nouveau mot de passe
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="password"
                            name="password1"
                            id="password1"
                            class="form-control"
                            placeholder="Saisissez votre nouveau mot de passe"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password1"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                    <div class="password-help">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>
                            Utilisez au minimum 8 caractères.
                        </span>

                    </div>

                </div>

                <div class="form-group-custom">

                    <label
                        for="password2"
                        class="form-label"
                    >
                        <i class="bi bi-shield-check"></i>
                        Confirmer le mot de passe
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="password"
                            name="password2"
                            id="password2"
                            class="form-control"
                            placeholder="Confirmez votre nouveau mot de passe"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password2"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    name="changer"
                    class="btn btn-main w-100"
                >
                    <i class="bi bi-check-circle-fill"></i>
                    Enregistrer le nouveau mot de passe
                </button>

            </form>

            <div class="security-footer">

                <i class="bi bi-lock-fill"></i>

                Vos informations de connexion sont protégées et sécurisées.

            </div>

        </div>

    </div>

    <script>

        document.querySelectorAll('.toggle-password').forEach(function(button) {

            button.addEventListener('click', function() {

                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {

                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');

                } else {

                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');

                }

            });

        });

    </script>

</body>

</html> 