<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/CompteAdminController.php';

/*
|--------------------------------------------------------------------------
| Vérification du Super Administrateur
|--------------------------------------------------------------------------
*/

verifierSuperAdmin();

/*
|--------------------------------------------------------------------------
| Lancement du contrôleur
|--------------------------------------------------------------------------
*/

$controller = new CompteAdminController();

$action = isset($_GET['action'])
    ? trim((string) $_GET['action'])
    : 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'ajouter':
        $controller->ajouter();
        break;

    case 'enregistrer':
        $controller->enregistrer();
        break;

    case 'details':
        $controller->details();
        break;

    case 'modifier':
        $controller->modifier();
        break;

    case 'mettre-a-jour':
        $controller->mettreAJour();
        break;

    case 'suspendre':
        $controller->suspendre();
        break;

    case 'reactiver':
        $controller->reactiver();
        break;

    case 'archiver':
        $controller->archiver();
        break;

    case 'reinitialiser':
        $controller->reinitialiserMotDePasse();
        break;

    case 'historique':
        $controller->historique();
        break;

    case 'statistiques':
        $controller->statistiques();
        break;

    default:
        http_response_code(404);

        $titrePage = 'Page introuvable';
        $pageActive = '';

        require __DIR__ . '/views/layouts/header.php';
        require __DIR__ . '/views/layouts/sidebar.php';
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

                        <h3>Page introuvable</h3>

                        <p>
                            L’action demandée n’existe pas.
                        </p>

                    </div>

                </div>

            </header>

            <section class="page">

                <div class="card section-card">

                    <div class="card-body text-center py-5">

                        <i
                            class="bi bi-exclamation-triangle-fill text-warning"
                            style="font-size: 55px;"
                        ></i>

                        <h4 class="mt-4">
                            Erreur 404
                        </h4>

                        <p class="text-muted">
                            La page ou l’action demandée est introuvable.
                        </p>

                        <a
                            href="dashboard.php"
                            class="btn btn-orange mt-2"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Retour au tableau de bord
                        </a>

                    </div>

                </div>

            </section>

        <?php
        require __DIR__ . '/views/layouts/footer.php';
        break;
}