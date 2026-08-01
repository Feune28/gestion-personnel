<?php

declare(strict_types=1);

if (!isset($erreur)) {
    $erreur = '';
}

if (!isset($identifiant)) {
    $identifiant = '';
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

    <title>Connexion Super Administrateur | CMAK</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/superadmin.css"
    >

</head>

<body class="login-body">

<div class="login-wrapper">

    <section class="login-presentation">

        <div class="login-logo">

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
            Administratif et Financier depuis un espace sécurisé.
        </p>

        <div class="login-avantage">

            <i class="bi bi-shield-lock-fill"></i>

            <span>
                Accès exclusivement réservé au Super Administrateur
            </span>

        </div>

        <div class="login-avantage">

            <i class="bi bi-person-gear"></i>

            <span>
                Création et administration des comptes DE et DAF
            </span>

        </div>

        <div class="login-avantage">

            <i class="bi bi-clock-history"></i>

            <span>
                Contrôle et suivi des activités administratives
            </span>

        </div>

    </section>

    <section class="login-form-section">

        <div class="login-mobile-logo">

            <i class="bi bi-shield-lock-fill"></i>

        </div>

        <h2>Bienvenue</h2>

        <p class="login-subtitle">
            Connectez-vous pour accéder à l’administration générale.
        </p>

        <?php if ($erreur !== ''): ?>

            <div
                class="alert alert-danger d-flex align-items-center"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <div>
                    <?= htmlspecialchars($erreur) ?>
                </div>

            </div>

        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="mb-4">

                <label
                    for="identifiant"
                    class="form-label"
                >
                    Identifiant ou adresse email
                </label>

                <div class="input-group login-input-group">

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
                        autocomplete="username"
                        required
                        autofocus
                    >

                </div>

            </div>

            <div class="mb-4">

                <label
                    for="password"
                    class="form-label"
                >
                    Mot de passe
                </label>

                <div class="input-group login-input-group">

                    <span class="input-group-text">

                        <i class="bi bi-lock-fill"></i>

                    </span>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Saisissez votre mot de passe"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="afficherMotDePasse"
                        title="Afficher le mot de passe"
                    >

                        <i
                            class="bi bi-eye-fill"
                            id="iconeMotDePasse"
                        ></i>

                    </button>

                </div>

            </div>

            <button
                type="submit"
                class="btn btn-login"
            >

                <i class="bi bi-box-arrow-in-right me-2"></i>

                Se connecter

            </button>

        </form>

        <div class="login-footer">

            © <?= date('Y') ?> CMAK Facobly —
            Administration sécurisée

        </div>

    </section>

</div>

<script src="assets/js/superadmin.js"></script>

</body>
</html>