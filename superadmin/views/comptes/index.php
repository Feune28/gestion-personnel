<?php

declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

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

                <h3>Gestion des comptes</h3>

                <p>
                    Création, modification et contrôle des comptes du DE et du DAF
                </p>

            </div>

        </div>

        <div class="header-actions">

            <div class="year-badge">

                <i class="bi bi-people-fill"></i>

                <?= (int) $totalComptes ?> compte(s)

            </div>

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

                        <a href="#">

                            <i class="bi bi-person-circle"></i>

                            Mon profil

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

        <?php if (!empty($messageSucces)): ?>

            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                <?= htmlspecialchars($messageSucces) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fermer"
                ></button>

            </div>

        <?php endif; ?>

        <?php if (!empty($messageErreur)): ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <?= htmlspecialchars($messageErreur) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fermer"
                ></button>

            </div>

        <?php endif; ?>

        <div class="row g-4 mb-4">

            <div class="col-xl-3 col-md-6">

                <div class="card carte-statistique">

                    <div class="card-body">

                        <div>

                            <p>Total des comptes</p>

                            <h3>
                                <?= (int) $totalComptes ?>
                            </h3>

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

                            <h3>
                                <?= (int) $totalActifs ?>
                            </h3>

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

                            <h3>
                                <?= (int) $totalSuspendus ?>
                            </h3>

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

                            <p>Comptes archivés</p>

                            <h3>
                                <?= (int) $totalArchives ?>
                            </h3>

                        </div>

                        <div class="stat-icone icone-role">

                            <i class="bi bi-archive-fill"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="card section-card">

            <div
                class="card-header d-flex justify-content-between align-items-center"
            >

                <div>

                    <h4>Liste des comptes administratifs</h4>

                    <small class="text-muted">
                        Comptes du Directeur des Études et du DAF
                    </small>

                </div>

                <a
                    href="comptes.php?action=ajouter"
                    class="btn btn-orange"
                >

                    <i class="bi bi-person-plus-fill me-1"></i>

                    Ajouter un compte

                </a>

            </div>

            <div class="card-body">

                <div class="row g-3 mb-4">

                    <div class="col-lg-5">

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>

                            <input
                                type="search"
                                class="form-control"
                                id="rechercheCompte"
                                placeholder="Rechercher un nom, un email ou un identifiant..."
                                autocomplete="off"
                            >

                        </div>

                    </div>

                    <div class="col-lg-3">

                        <select
                            class="form-select"
                            id="filtreRole"
                        >

                            <option value="">
                                Tous les rôles
                            </option>

                            <option value="de">
                                Directeur des Études
                            </option>

                            <option value="daf">
                                DAF
                            </option>

                        </select>

                    </div>

                    <div class="col-lg-3">

                        <select
                            class="form-select"
                            id="filtreStatut"
                        >

                            <option value="">
                                Tous les statuts
                            </option>

                            <option value="actif">
                                Actifs
                            </option>

                            <option value="suspendu">
                                Suspendus
                            </option>

                            <option value="archive">
                                Archivés
                            </option>

                        </select>

                    </div>

                    <div class="col-lg-1">

                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            id="reinitialiserFiltres"
                            title="Réinitialiser les filtres"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                        </button>

                    </div>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-hover align-middle"
                        id="tableComptes"
                    >

                        <thead>

                            <tr>

                                <th>Administrateur</th>

                                <th>Identifiant</th>

                                <th>Email</th>

                                <th>Fonction</th>

                                <th>Rôle</th>

                                <th>Statut</th>

                                <th>Date de création</th>

                                <th class="text-center">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (empty($comptes)): ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-5 text-muted"
                                >

                                    <i
                                        class="bi bi-people fs-1 d-block mb-3"
                                    ></i>

                                    Aucun compte administratif enregistré.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($comptes as $compte): ?>

                                <?php

                                $idCompte = (int) (
                                    $compte['id'] ?? 0
                                );

                                $nomComplet = trim(
                                    ($compte['nom'] ?? '')
                                    . ' '
                                    . ($compte['prenom'] ?? '')
                                );

                                $username = trim(
                                    (string) (
                                        $compte['username'] ?? ''
                                    )
                                );

                                $email = trim(
                                    (string) (
                                        $compte['email'] ?? ''
                                    )
                                );

                                $fonction = trim(
                                    (string) (
                                        $compte['fonction'] ?? ''
                                    )
                                );

                                $role = strtoupper(
                                    trim(
                                        (string) (
                                            $compte['role'] ?? ''
                                        )
                                    )
                                );

                                $statut = strtolower(
                                    trim(
                                        (string) (
                                            $compte['statut_compte']
                                            ?? 'actif'
                                        )
                                    )
                                );

                                $dateCreation = $compte['created_at']
                                    ?? null;

                                $dateCreationFormatee = '-';

                                if (!empty($dateCreation)) {
                                    try {
                                        $date = new DateTime(
                                            (string) $dateCreation
                                        );

                                        $dateCreationFormatee = $date->format(
                                            'd/m/Y à H:i'
                                        );
                                    } catch (Throwable $exception) {
                                        $dateCreationFormatee = htmlspecialchars(
                                            (string) $dateCreation
                                        );
                                    }
                                }

                                ?>

                                <tr
                                    class="ligne-compte"
                                    data-role="<?= htmlspecialchars(
                                        strtolower($role)
                                    ) ?>"
                                    data-statut="<?= htmlspecialchars(
                                        $statut
                                    ) ?>"
                                    data-recherche="<?= htmlspecialchars(
                                        strtolower(
                                            $nomComplet
                                            . ' '
                                            . $username
                                            . ' '
                                            . $email
                                            . ' '
                                            . $fonction
                                            . ' '
                                            . $role
                                        )
                                    ) ?>"
                                >

                                    <td>

                                        <div
                                            class="d-flex align-items-center gap-3"
                                        >

                                            <div class="profile-avatar">

                                                <i class="bi bi-person-fill"></i>

                                            </div>

                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $nomComplet !== ''
                                                            ? $nomComplet
                                                            : 'Administrateur'
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    Compte no <?= $idCompte ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $username !== ''
                                                    ? $username
                                                    : '-'
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php if ($email !== ''): ?>

                                            <a
                                                href="mailto:<?= htmlspecialchars(
                                                    $email
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars($email) ?>

                                            </a>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Non renseigné
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $fonction !== ''
                                                ? $fonction
                                                : '-'
                                        ) ?>

                                    </td>

                                    <td>

                                        <span class="badge-role">

                                            <?= htmlspecialchars($role) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php if ($statut === 'actif'): ?>

                                            <span class="badge-actif">

                                                <i
                                                    class="bi bi-check-circle-fill me-1"
                                                ></i>

                                                Actif

                                            </span>

                                        <?php elseif (
                                            $statut === 'suspendu'
                                        ): ?>

                                            <span class="badge-suspendu">

                                                <i
                                                    class="bi bi-lock-fill me-1"
                                                ></i>

                                                Suspendu

                                            </span>

                                        <?php elseif (
                                            $statut === 'archive'
                                        ): ?>

                                            <span class="badge-archive">

                                                <i
                                                    class="bi bi-archive-fill me-1"
                                                ></i>

                                                Archivé

                                            </span>

                                        <?php else: ?>

                                            <span class="badge-archive">

                                                <?= htmlspecialchars(
                                                    ucfirst($statut)
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <small>

                                            <?= $dateCreationFormatee ?>

                                        </small>

                                    </td>

                                    <td>

                                        <div
                                            class="actions-compte justify-content-center"
                                        >

                                            <a
                                                href="comptes.php?action=details&id=<?= $idCompte ?>"
                                                class="btn btn-info btn-sm"
                                                title="Voir les détails"
                                            >

                                                <i class="bi bi-eye-fill"></i>

                                            </a>

                                            <a
                                                href="comptes.php?action=modifier&id=<?= $idCompte ?>"
                                                class="btn btn-warning btn-sm"
                                                title="Modifier le compte"
                                            >

                                                <i
                                                    class="bi bi-pencil-square"
                                                ></i>

                                            </a>

                                            <?php if ($statut === 'actif'): ?>

                                                <a
                                                    href="comptes.php?action=suspendre&id=<?= $idCompte ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Suspendre le compte"
                                                    data-confirm="Voulez-vous vraiment suspendre le compte de <?= htmlspecialchars(
                                                        $nomComplet
                                                    ) ?> ?"
                                                >

                                                    <i class="bi bi-lock-fill"></i>

                                                </a>

                                            <?php elseif (
                                                $statut === 'suspendu'
                                            ): ?>

                                                <a
                                                    href="comptes.php?action=reactiver&id=<?= $idCompte ?>"
                                                    class="btn btn-success btn-sm"
                                                    title="Réactiver le compte"
                                                    data-confirm="Voulez-vous vraiment réactiver le compte de <?= htmlspecialchars(
                                                        $nomComplet
                                                    ) ?> ?"
                                                >

                                                    <i
                                                        class="bi bi-unlock-fill"
                                                    ></i>

                                                </a>

                                            <?php endif; ?>

                                            <?php if ($statut !== 'archive'): ?>

                                                <a
                                                    href="comptes.php?action=archiver&id=<?= $idCompte ?>"
                                                    class="btn btn-secondary btn-sm"
                                                    title="Archiver le compte"
                                                    data-confirm="Voulez-vous vraiment archiver le compte de <?= htmlspecialchars(
                                                        $nomComplet
                                                    ) ?> ?"
                                                >

                                                    <i
                                                        class="bi bi-archive-fill"
                                                    ></i>

                                                </a>

                                            <?php endif; ?>

                                            <a
                                                href="comptes.php?action=reinitialiser&id=<?= $idCompte ?>"
                                                class="btn btn-primary btn-sm"
                                                title="Réinitialiser le mot de passe"
                                                data-confirm="Voulez-vous réinitialiser le mot de passe de <?= htmlspecialchars(
                                                    $nomComplet
                                                ) ?> ?"
                                            >

                                                <i class="bi bi-key-fill"></i>

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <tr
                                id="aucunResultat"
                                style="display: none;"
                            >

                                <td
                                    colspan="8"
                                    class="text-center py-5 text-muted"
                                >

                                    <i
                                        class="bi bi-search fs-1 d-block mb-3"
                                    ></i>

                                    Aucun compte ne correspond aux critères de recherche.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const rechercheCompte = document.getElementById(
        'rechercheCompte'
    );

    const filtreRole = document.getElementById(
        'filtreRole'
    );

    const filtreStatut = document.getElementById(
        'filtreStatut'
    );

    const reinitialiserFiltres = document.getElementById(
        'reinitialiserFiltres'
    );

    const lignes = Array.from(
        document.querySelectorAll('.ligne-compte')
    );

    const aucunResultat = document.getElementById(
        'aucunResultat'
    );

    function normaliserTexte(texte) {
        return String(texte ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function filtrerComptes() {
        const recherche = normaliserTexte(
            rechercheCompte?.value
        );

        const roleSelectionne = normaliserTexte(
            filtreRole?.value
        );

        const statutSelectionne = normaliserTexte(
            filtreStatut?.value
        );

        let nombreVisible = 0;

        lignes.forEach(function (ligne) {
            const contenuRecherche = normaliserTexte(
                ligne.dataset.recherche
            );

            const role = normaliserTexte(
                ligne.dataset.role
            );

            const statut = normaliserTexte(
                ligne.dataset.statut
            );

            const correspondRecherche =
                recherche === ''
                || contenuRecherche.includes(recherche);

            const correspondRole =
                roleSelectionne === ''
                || role === roleSelectionne;

            const correspondStatut =
                statutSelectionne === ''
                || statut === statutSelectionne;

            const visible =
                correspondRecherche
                && correspondRole
                && correspondStatut;

            ligne.style.display = visible
                ? ''
                : 'none';

            if (visible) {
                nombreVisible++;
            }
        });

        if (aucunResultat) {
            aucunResultat.style.display =
                lignes.length > 0 && nombreVisible === 0
                    ? ''
                    : 'none';
        }
    }

    rechercheCompte?.addEventListener(
        'input',
        filtrerComptes
    );

    filtreRole?.addEventListener(
        'change',
        filtrerComptes
    );

    filtreStatut?.addEventListener(
        'change',
        filtrerComptes
    );

    reinitialiserFiltres?.addEventListener(
        'click',
        function () {
            if (rechercheCompte) {
                rechercheCompte.value = '';
            }

            if (filtreRole) {
                filtreRole.value = '';
            }

            if (filtreStatut) {
                filtreStatut.value = '';
            }

            filtrerComptes();

            rechercheCompte?.focus();
        }
    );
});
</script>