<?php

declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

$erreurs = $erreurs ?? [];
$anciennesDonnees = $anciennesDonnees ?? [];

function ancienneValeur(
    array $donnees,
    string $champ,
    string $valeurParDefaut = ''
): string {
    return htmlspecialchars(
        trim(
            (string) (
                $donnees[$champ]
                ?? $valeurParDefaut
            )
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}

function classeChampErreur(
    array $erreurs,
    string $champ
): string {
    return isset($erreurs[$champ])
        ? ' is-invalid'
        : '';
}

?>

<main class="contenu">

    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="dashboard-menu-toggle"
                id="sidebarToggle"
                title="Réduire ou agrandir le menu"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <h3>Ajouter un compte</h3>

                <p>
                    Créez un nouveau compte pour le Directeur des Études ou le DAF
                </p>

            </div>

        </div>

        <div class="header-actions">

            <a
                href="comptes.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>

                Retour
            </a>

            <button
                type="button"
                class="dark-toggle"
                id="darkToggle"
                title="Activer le mode sombre"
            >
                <i class="bi bi-moon-fill"></i>
            </button>

            <div
                class="profile-menu"
                id="profileMenu"
            >

                <button
                    type="button"
                    class="profile-btn"
                    id="profileBtn"
                    aria-label="Ouvrir le menu du profil"
                >

                    <span class="profile-avatar">

                        <i class="bi bi-person-fill-gear"></i>

                    </span>

                    <i class="bi bi-chevron-down"></i>

                </button>

                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >

                    <div class="profile-dropdown-header">

                        <div class="profile-name">

                            <?= htmlspecialchars(
                                trim(
                                    ($_SESSION['superadmin_prenom'] ?? '')
                                    . ' '
                                    . ($_SESSION['superadmin_nom'] ?? '')
                                )
                            ) ?>

                        </div>

                        <div class="profile-role">
                            Super Administrateur
                        </div>

                    </div>

                    <div class="profile-dropdown-body">

                        <a href="dashboard.php">

                            <i class="bi bi-speedometer2"></i>

                            Tableau de bord

                        </a>

                        <a href="comptes.php">

                            <i class="bi bi-people-fill"></i>

                            Gestion des comptes

                        </a>

                    </div>

                    <div class="profile-dropdown-footer">

                        <a
                            href="logout.php"
                            onclick="return confirm(
                                'Voulez-vous vraiment vous déconnecter ?'
                            )"
                        >

                            <i class="bi bi-box-arrow-right"></i>

                            Se déconnecter

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </header>

    <section class="page">

        <?php if (!empty($erreurs['general'])): ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <?= htmlspecialchars($erreurs['general']) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fermer"
                ></button>

            </div>

        <?php endif; ?>

        <div class="row g-4">

            <div class="col-xl-8">

                <div class="card section-card">

                    <div class="card-header">

                        <h4>Informations du nouveau compte</h4>

                        <small class="text-muted">
                            Les champs marqués d’un astérisque sont obligatoires
                        </small>

                    </div>

                    <div class="card-body p-4">

                        <form
                            action="comptes.php?action=enregistrer"
                            method="post"
                            id="formAjouterCompte"
                            novalidate
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $_SESSION['csrf_token'] ?? ''
                                ) ?>"
                            >

                            <div class="row g-4">

                                <div class="col-md-6">

                                    <label
                                        for="nom"
                                        class="form-label"
                                    >
                                        Nom
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'nom'
                                        ) ?>"
                                        id="nom"
                                        name="nom"
                                        value="<?= ancienneValeur(
                                            $anciennesDonnees,
                                            'nom'
                                        ) ?>"
                                        maxlength="100"
                                        autocomplete="family-name"
                                        required
                                    >

                                    <?php if (isset($erreurs['nom'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['nom']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="prenom"
                                        class="form-label"
                                    >
                                        Prénom
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'prenom'
                                        ) ?>"
                                        id="prenom"
                                        name="prenom"
                                        value="<?= ancienneValeur(
                                            $anciennesDonnees,
                                            'prenom'
                                        ) ?>"
                                        maxlength="100"
                                        autocomplete="given-name"
                                        required
                                    >

                                    <?php if (isset($erreurs['prenom'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['prenom']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Adresse email
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'email'
                                        ) ?>"
                                        id="email"
                                        name="email"
                                        value="<?= ancienneValeur(
                                            $anciennesDonnees,
                                            'email'
                                        ) ?>"
                                        maxlength="190"
                                        autocomplete="email"
                                        required
                                    >

                                    <?php if (isset($erreurs['email'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['email']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="username"
                                        class="form-label"
                                    >
                                        Nom d’utilisateur
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'username'
                                        ) ?>"
                                        id="username"
                                        name="username"
                                        value="<?= ancienneValeur(
                                            $anciennesDonnees,
                                            'username'
                                        ) ?>"
                                        maxlength="80"
                                        autocomplete="username"
                                        required
                                    >

                                    <?php if (isset($erreurs['username'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['username']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <small class="form-text text-muted">
                                        Utilisez uniquement des lettres, chiffres, points, tirets et underscores.
                                    </small>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="role"
                                        class="form-label"
                                    >
                                        Rôle
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-select<?= classeChampErreur(
                                            $erreurs,
                                            'role'
                                        ) ?>"
                                        id="role"
                                        name="role"
                                        required
                                    >

                                        <option value="">
                                            Sélectionnez un rôle
                                        </option>

                                        <option
                                            value="DE"
                                            <?= ancienneValeur(
                                                $anciennesDonnees,
                                                'role'
                                            ) === 'DE'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Directeur des Études
                                        </option>

                                        <option
                                            value="DAF"
                                            <?= ancienneValeur(
                                                $anciennesDonnees,
                                                'role'
                                            ) === 'DAF'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Directeur Administratif et Financier
                                        </option>

                                    </select>

                                    <?php if (isset($erreurs['role'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['role']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="fonction"
                                        class="form-label"
                                    >
                                        Fonction
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'fonction'
                                        ) ?>"
                                        id="fonction"
                                        name="fonction"
                                        value="<?= ancienneValeur(
                                            $anciennesDonnees,
                                            'fonction'
                                        ) ?>"
                                        maxlength="150"
                                        required
                                    >

                                    <?php if (isset($erreurs['fonction'])): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['fonction']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="niveau_acces"
                                        class="form-label"
                                    >
                                        Niveau d’accès
                                    </label>

                                    <select
                                        class="form-select<?= classeChampErreur(
                                            $erreurs,
                                            'niveau_acces'
                                        ) ?>"
                                        id="niveau_acces"
                                        name="niveau_acces"
                                    >

                                        <option value="administrateur">
                                            Administrateur
                                        </option>

                                        <option
                                            value="gestionnaire"
                                            <?= ancienneValeur(
                                                $anciennesDonnees,
                                                'niveau_acces'
                                            ) === 'gestionnaire'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Gestionnaire
                                        </option>

                                        <option
                                            value="limite"
                                            <?= ancienneValeur(
                                                $anciennesDonnees,
                                                'niveau_acces'
                                            ) === 'limite'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Accès limité
                                        </option>

                                    </select>

                                    <?php if (
                                        isset($erreurs['niveau_acces'])
                                    ): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs['niveau_acces']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="statut_compte"
                                        class="form-label"
                                    >
                                        Statut initial
                                    </label>

                                    <select
                                        class="form-select<?= classeChampErreur(
                                            $erreurs,
                                            'statut_compte'
                                        ) ?>"
                                        id="statut_compte"
                                        name="statut_compte"
                                    >

                                        <option value="actif">
                                            Actif
                                        </option>

                                        <option
                                            value="suspendu"
                                            <?= ancienneValeur(
                                                $anciennesDonnees,
                                                'statut_compte'
                                            ) === 'suspendu'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Suspendu
                                        </option>

                                    </select>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="password"
                                        class="form-label"
                                    >
                                        Mot de passe temporaire
                                        <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="password"
                                            class="form-control<?= classeChampErreur(
                                                $erreurs,
                                                'password'
                                            ) ?>"
                                            id="password"
                                            name="password"
                                            minlength="8"
                                            maxlength="72"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            id="afficherMotDePasse"
                                            title="Afficher ou masquer le mot de passe"
                                        >
                                            <i class="bi bi-eye-fill"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            id="genererMotDePasse"
                                            title="Générer un mot de passe"
                                        >
                                            <i class="bi bi-magic"></i>
                                        </button>

                                    </div>

                                    <?php if (isset($erreurs['password'])): ?>

                                        <div
                                            class="invalid-feedback d-block"
                                        >

                                            <?= htmlspecialchars(
                                                $erreurs['password']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <small class="form-text text-muted">
                                        Minimum 8 caractères avec majuscule, minuscule, chiffre et caractère spécial.
                                    </small>

                                </div>

                                <div class="col-md-6">

                                    <label
                                        for="confirmation_password"
                                        class="form-label"
                                    >
                                        Confirmation du mot de passe
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="password"
                                        class="form-control<?= classeChampErreur(
                                            $erreurs,
                                            'confirmation_password'
                                        ) ?>"
                                        id="confirmation_password"
                                        name="confirmation_password"
                                        minlength="8"
                                        maxlength="72"
                                        autocomplete="new-password"
                                        required
                                    >

                                    <?php if (
                                        isset(
                                            $erreurs[
                                                'confirmation_password'
                                            ]
                                        )
                                    ): ?>

                                        <div class="invalid-feedback">

                                            <?= htmlspecialchars(
                                                $erreurs[
                                                    'confirmation_password'
                                                ]
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-12">

                                    <div class="form-check">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            value="1"
                                            id="premiere_connexion"
                                            name="premiere_connexion"
                                            checked
                                        >

                                        <label
                                            class="form-check-label"
                                            for="premiere_connexion"
                                        >
                                            Obliger l’utilisateur à modifier son mot de passe lors de sa première connexion
                                        </label>

                                    </div>

                                </div>

                                <div class="col-12">

                                    <div class="d-flex gap-3 flex-wrap">

                                        <button
                                            type="submit"
                                            class="btn btn-orange"
                                        >
                                            <i class="bi bi-person-plus-fill me-2"></i>

                                            Créer le compte
                                        </button>

                                        <button
                                            type="reset"
                                            class="btn btn-outline-secondary"
                                        >
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>

                                            Réinitialiser
                                        </button>

                                        <a
                                            href="comptes.php"
                                            class="btn btn-outline-danger"
                                        >
                                            <i class="bi bi-x-circle me-2"></i>

                                            Annuler
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

            <div class="col-xl-4">

                <div class="card section-card">

                    <div class="card-header">

                        <h4>Règles de création</h4>

                    </div>

                    <div class="card-body">

                        <div class="creation-conseil">

                            <i class="bi bi-person-badge-fill"></i>

                            <div>

                                <strong>Un compte par responsable</strong>

                                <p>
                                    Chaque DE ou DAF doit disposer de son propre compte administratif.
                                </p>

                            </div>

                        </div>

                        <div class="creation-conseil">

                            <i class="bi bi-envelope-check-fill"></i>

                            <div>

                                <strong>Email unique</strong>

                                <p>
                                    L’adresse email ne doit pas être utilisée par un autre compte.
                                </p>

                            </div>

                        </div>

                        <div class="creation-conseil">

                            <i class="bi bi-shield-lock-fill"></i>

                            <div>

                                <strong>Mot de passe sécurisé</strong>

                                <p>
                                    Le mot de passe est chiffré avant son enregistrement dans la base.
                                </p>

                            </div>

                        </div>

                        <div class="creation-conseil">

                            <i class="bi bi-arrow-repeat"></i>

                            <div>

                                <strong>Changement obligatoire</strong>

                                <p>
                                    L’utilisateur pourra être invité à remplacer le mot de passe temporaire.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="card section-card mt-4">

                    <div class="card-header">

                        <h4>Résumé du rôle</h4>

                    </div>

                    <div class="card-body">

                        <div
                            class="alert alert-primary mb-0"
                            id="resumeRole"
                        >

                            <i class="bi bi-info-circle-fill me-2"></i>

                            Sélectionnez un rôle pour afficher ses responsabilités.

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    const fonction = document.getElementById('fonction');
    const resumeRole = document.getElementById('resumeRole');

    const password = document.getElementById('password');
    const confirmationPassword = document.getElementById(
        'confirmation_password'
    );

    const afficherMotDePasse = document.getElementById(
        'afficherMotDePasse'
    );

    const genererMotDePasse = document.getElementById(
        'genererMotDePasse'
    );

    function mettreAJourRole() {
        if (!role || !resumeRole) {
            return;
        }

        if (role.value === 'DE') {
            resumeRole.innerHTML = `
                <i class="bi bi-mortarboard-fill me-2"></i>
                Le Directeur des Études gère le personnel,
                les présences, les emplois du temps et les documents.
            `;

            if (fonction && fonction.value.trim() === '') {
                fonction.value = 'Directeur des Études';
            }

            return;
        }

        if (role.value === 'DAF') {
            resumeRole.innerHTML = `
                <i class="bi bi-cash-stack me-2"></i>
                Le DAF gère les salaires, les paiements,
                les avances, les retenues et les impayés.
            `;

            if (fonction && fonction.value.trim() === '') {
                fonction.value =
                    'Directeur Administratif et Financier';
            }

            return;
        }

        resumeRole.innerHTML = `
            <i class="bi bi-info-circle-fill me-2"></i>
            Sélectionnez un rôle pour afficher ses responsabilités.
        `;
    }

    role?.addEventListener('change', mettreAJourRole);

    mettreAJourRole();

    afficherMotDePasse?.addEventListener('click', function () {
        if (!password || !confirmationPassword) {
            return;
        }

        const doitAfficher = password.type === 'password';

        password.type = doitAfficher
            ? 'text'
            : 'password';

        confirmationPassword.type = doitAfficher
            ? 'text'
            : 'password';

        const icone = afficherMotDePasse.querySelector('i');

        if (icone) {
            icone.className = doitAfficher
                ? 'bi bi-eye-slash-fill'
                : 'bi bi-eye-fill';
        }
    });

    genererMotDePasse?.addEventListener('click', function () {
        if (!password || !confirmationPassword) {
            return;
        }

        const caracteres =
            'ABCDEFGHJKLMNPQRSTUVWXYZ'
            + 'abcdefghijkmnopqrstuvwxyz'
            + '23456789'
            + '@#$%&*!?';

        let motDePasseGenere = '';

        const tableauAleatoire = new Uint32Array(12);

        window.crypto.getRandomValues(tableauAleatoire);

        tableauAleatoire.forEach(function (nombre) {
            motDePasseGenere += caracteres[
                nombre % caracteres.length
            ];
        });

        password.value = motDePasseGenere;
        confirmationPassword.value = motDePasseGenere;

        password.type = 'text';
        confirmationPassword.type = 'text';

        const icone = afficherMotDePasse?.querySelector('i');

        if (icone) {
            icone.className = 'bi bi-eye-slash-fill';
        }
    });
});
</script>