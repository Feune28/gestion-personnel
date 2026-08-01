<?php

declare(strict_types=1);

$titrePage = 'Tableau de bord Super Administrateur';
$pageActive = 'dashboard';

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

                <h3>
                    Tableau de bord Super Administrateur
                </h3>

                <p>
                    Bienvenue,

                    <?= htmlspecialchars(
                        $_SESSION['superadmin_prenom'] ?? ''
                    ) ?>

                    <?= htmlspecialchars(
                        $_SESSION['superadmin_nom'] ?? ''
                    ) ?>

                    · Gestion générale de CMAK
                </p>

            </div>

        </div>

        <div class="header-actions">

            <div class="year-badge">

                <i class="bi bi-shield-check"></i>

                Administration centrale

            </div>

            <button
                type="button"
                class="dark-toggle"
                id="darkToggle"
                title="Activer ou désactiver le mode sombre"
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
                                $_SESSION['superadmin_prenom'] ?? ''
                            ) ?>

                            <?= htmlspecialchars(
                                $_SESSION['superadmin_nom'] ?? ''
                            ) ?>

                        </div>

                        <div class="profile-role">

                            <?= htmlspecialchars(
                                $_SESSION['superadmin_fonction']
                                ?? 'Super Administrateur'
                            ) ?>

                        </div>

                    </div>

                    <div class="profile-dropdown-body">

                        <a href="#">

                            <i class="bi bi-person-circle"></i>

                            Mon profil

                        </a>

                        <a href="dashboard.php">

                            <i class="bi bi-speedometer2"></i>

                            Tableau de bord

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

                            <p>DE / DAF</p>

                            <h3>
                                <?= (int) $totalDE ?>
                                /
                                <?= (int) $totalDAF ?>
                            </h3>

                        </div>

                        <div class="stat-icone icone-role">

                            <i class="bi bi-diagram-3-fill"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="row g-4">

            <div class="col-xl-9">

                <div
                    class="card section-card"
                    id="comptes"
                >

                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <h4>
                                Comptes administratifs
                            </h4>

                            <small class="text-muted">
                                Liste des comptes du DE et du DAF
                            </small>

                        </div>

                        <a
                            href="comptes.php?action=ajouter"
                            class="btn btn-orange"
                        >

                            <i class="bi bi-person-plus-fill me-1"></i>

                            Nouveau compte

                        </a>

                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead>

                                    <tr>

                                        <th>Administrateur</th>

                                        <th>Email</th>

                                        <th>Fonction</th>

                                        <th>Rôle</th>

                                        <th>Statut</th>

                                        <th>Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php if (empty($comptes)): ?>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center py-5 text-muted"
                                        >

                                            <i
                                                class="bi bi-people fs-1 d-block mb-3"
                                            ></i>

                                            Aucun compte DE ou DAF enregistré.

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($comptes as $compte): ?>

                                        <?php
                                        $idCompte = (int) (
                                            $compte['id'] ?? 0
                                        );

                                        $statutCompte = strtolower(
                                            trim(
                                                $compte['statut_compte']
                                                ?? 'actif'
                                            )
                                        );
                                        ?>

                                        <tr>

                                            <td>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        trim(
                                                            ($compte['nom'] ?? '')
                                                            . ' '
                                                            . ($compte['prenom'] ?? '')
                                                        )
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    <?= htmlspecialchars(
                                                        $compte['username']
                                                        ?? ''
                                                    ) ?>

                                                </small>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $compte['email'] ?? ''
                                                ) ?>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $compte['fonction']
                                                    ?: '-'
                                                ) ?>

                                            </td>

                                            <td>

                                                <span class="badge-role">

                                                    <?= htmlspecialchars(
                                                        $compte['role'] ?? ''
                                                    ) ?>

                                                </span>

                                            </td>

                                            <td>

                                                <?php if (
                                                    $statutCompte === 'actif'
                                                ): ?>

                                                    <span class="badge-actif">

                                                        <i
                                                            class="bi bi-check-circle-fill me-1"
                                                        ></i>

                                                        Actif

                                                    </span>

                                                <?php elseif (
                                                    $statutCompte === 'archive'
                                                ): ?>

                                                    <span class="badge-archive">

                                                        <i
                                                            class="bi bi-archive-fill me-1"
                                                        ></i>

                                                        Archivé

                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge-suspendu">

                                                        <i
                                                            class="bi bi-x-circle-fill me-1"
                                                        ></i>

                                                        Suspendu

                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <div class="actions-compte">

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
                                                        title="Modifier"
                                                    >

                                                        <i
                                                            class="bi bi-pencil-square"
                                                        ></i>

                                                    </a>

                                                    <?php if (
                                                        $statutCompte === 'actif'
                                                    ): ?>

                                                        <a
                                                            href="comptes.php?action=suspendre&id=<?= $idCompte ?>"
                                                            class="btn btn-danger btn-sm"
                                                            title="Suspendre"
                                                            onclick="return confirm(
                                                                'Voulez-vous vraiment suspendre ce compte ?'
                                                            )"
                                                        >

                                                            <i
                                                                class="bi bi-lock-fill"
                                                            ></i>

                                                        </a>

                                                    <?php else: ?>

                                                        <a
                                                            href="comptes.php?action=reactiver&id=<?= $idCompte ?>"
                                                            class="btn btn-success btn-sm"
                                                            title="Réactiver"
                                                            onclick="return confirm(
                                                                'Voulez-vous réactiver ce compte ?'
                                                            )"
                                                        >

                                                            <i
                                                                class="bi bi-unlock-fill"
                                                            ></i>

                                                        </a>

                                                    <?php endif; ?>

                                                    <a
                                                        href="comptes.php?action=reinitialiser&id=<?= $idCompte ?>"
                                                        class="btn btn-primary btn-sm"
                                                        title="Réinitialiser le mot de passe"
                                                        onclick="return confirm(
                                                            'Voulez-vous réinitialiser le mot de passe de ce compte ?'
                                                        )"
                                                    >

                                                        <i class="bi bi-key-fill"></i>

                                                    </a>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-xl-3">

                <div class="card section-card">

                    <div class="card-header">

                        <h4>Actions rapides</h4>

                    </div>

                    <div class="card-body">

                        <div class="actions-rapides">

                            <a
                                href="comptes.php"
                                class="action-box"
                            >

                                <i class="bi bi-people-fill"></i>

                                <strong>
                                    Voir les comptes
                                </strong>

                                <small>
                                    Consulter le DE et le DAF
                                </small>

                            </a>

                            <a
                                href="comptes.php?action=ajouter"
                                class="action-box"
                            >

                                <i class="bi bi-person-plus-fill"></i>

                                <strong>
                                    Ajouter
                                </strong>

                                <small>
                                    Créer un nouveau compte
                                </small>

                            </a>

                            <a
                                href="comptes.php?action=historique"
                                class="action-box"
                            >

                                <i class="bi bi-clock-history"></i>

                                <strong>
                                    Historique
                                </strong>

                                <small>
                                    Voir les opérations réalisées
                                </small>

                            </a>

                            <a
                                href="comptes.php?action=statistiques"
                                class="action-box"
                            >

                                <i class="bi bi-bar-chart-fill"></i>

                                <strong>
                                    Statistiques
                                </strong>

                                <small>
                                    Analyser les comptes
                                </small>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>