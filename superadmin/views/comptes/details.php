<?php

declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

$idCompte = (int) ($compte['id'] ?? 0);

$nom = trim((string) ($compte['nom'] ?? ''));
$prenom = trim((string) ($compte['prenom'] ?? ''));

$nomComplet = trim($nom . ' ' . $prenom);

$username = trim((string) ($compte['username'] ?? ''));
$email = trim((string) ($compte['email'] ?? ''));
$fonction = trim((string) ($compte['fonction'] ?? ''));

$role = strtoupper(
    trim((string) ($compte['role'] ?? ''))
);

$niveauAcces = trim(
    (string) ($compte['niveau_acces'] ?? '')
);

$statut = strtolower(
    trim(
        (string) (
            $compte['statut_compte']
            ?? 'actif'
        )
    )
);

/**
 * Formate une date provenant de la base de données.
 */
function formaterDateCompte(
    mixed $date,
    string $format = 'd/m/Y à H:i'
): string {
    if (empty($date)) {
        return 'Non renseignée';
    }

    try {
        return (new DateTime((string) $date))->format($format);
    } catch (Throwable $exception) {
        return htmlspecialchars((string) $date);
    }
}

$dateCreation = formaterDateCompte(
    $compte['created_at'] ?? null
);

$dateModification = formaterDateCompte(
    $compte['updated_at'] ?? null
);

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

                <h3>Détails du compte</h3>

                <p>
                    Consultation du compte administratif
                    numéro <?= $idCompte ?>
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

        <div class="row g-4">

            <div class="col-xl-4">

                <div class="card section-card">

                    <div class="card-body text-center p-4">

                        <div
                            class="profile-avatar mx-auto mb-3"
                            style="
                                width: 85px;
                                height: 85px;
                                font-size: 34px;
                            "
                        >
                            <i class="bi bi-person-fill"></i>
                        </div>

                        <h4 class="mb-1">

                            <?= htmlspecialchars(
                                $nomComplet !== ''
                                    ? $nomComplet
                                    : 'Administrateur'
                            ) ?>

                        </h4>

                        <p class="text-muted mb-3">

                            <?= htmlspecialchars(
                                $fonction !== ''
                                    ? $fonction
                                    : 'Fonction non renseignée'
                            ) ?>

                        </p>

                        <div class="d-flex justify-content-center gap-2 flex-wrap">

                            <span class="badge-role">

                                <?= htmlspecialchars(
                                    $role !== ''
                                        ? $role
                                        : 'NON DÉFINI'
                                ) ?>

                            </span>

                            <?php if ($statut === 'actif'): ?>

                                <span class="badge-actif">

                                    <i
                                        class="bi bi-check-circle-fill me-1"
                                    ></i>

                                    Actif

                                </span>

                            <?php elseif ($statut === 'suspendu'): ?>

                                <span class="badge-suspendu">

                                    <i
                                        class="bi bi-lock-fill me-1"
                                    ></i>

                                    Suspendu

                                </span>

                            <?php elseif ($statut === 'archive'): ?>

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

                        </div>

                    </div>

                </div>

                <div class="card section-card mt-4">

                    <div class="card-header">

                        <h4>Actions sur le compte</h4>

                    </div>

                    <div class="card-body">

                        <div class="d-grid gap-3">

                            <a
                                href="comptes.php?action=modifier&id=<?= $idCompte ?>"
                                class="btn btn-warning"
                            >
                                <i class="bi bi-pencil-square me-2"></i>

                                Modifier le compte
                            </a>

                            <?php if ($statut === 'actif'): ?>

                                <a
                                    href="comptes.php?action=suspendre&id=<?= $idCompte ?>"
                                    class="btn btn-danger"
                                    data-confirm="Voulez-vous vraiment suspendre ce compte ?"
                                >
                                    <i class="bi bi-lock-fill me-2"></i>

                                    Suspendre le compte
                                </a>

                            <?php elseif ($statut === 'suspendu'): ?>

                                <a
                                    href="comptes.php?action=reactiver&id=<?= $idCompte ?>"
                                    class="btn btn-success"
                                    data-confirm="Voulez-vous vraiment réactiver ce compte ?"
                                >
                                    <i class="bi bi-unlock-fill me-2"></i>

                                    Réactiver le compte
                                </a>

                            <?php endif; ?>

                            <?php if ($statut !== 'archive'): ?>

                                <a
                                    href="comptes.php?action=archiver&id=<?= $idCompte ?>"
                                    class="btn btn-secondary"
                                    data-confirm="Voulez-vous vraiment archiver ce compte ?"
                                >
                                    <i class="bi bi-archive-fill me-2"></i>

                                    Archiver le compte
                                </a>

                            <?php endif; ?>

                            <a
                                href="comptes.php?action=reinitialiser&id=<?= $idCompte ?>"
                                class="btn btn-primary"
                                data-confirm="Voulez-vous réinitialiser le mot de passe de ce compte ?"
                            >
                                <i class="bi bi-key-fill me-2"></i>

                                Réinitialiser le mot de passe
                            </a>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-xl-8">

                <div class="card section-card">

                    <div class="card-header">

                        <h4>Informations du compte</h4>

                    </div>

                    <div class="card-body p-4">

                        <div class="row g-4">

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-hash"></i>
                                    </div>

                                    <div>

                                        <small>Identifiant numérique</small>

                                        <strong>
                                            <?= $idCompte ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </div>

                                    <div>

                                        <small>Nom d’utilisateur</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $username !== ''
                                                    ? $username
                                                    : 'Non renseigné'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-person-fill"></i>
                                    </div>

                                    <div>

                                        <small>Nom</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $nom !== ''
                                                    ? $nom
                                                    : 'Non renseigné'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-person-fill"></i>
                                    </div>

                                    <div>

                                        <small>Prénom</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $prenom !== ''
                                                    ? $prenom
                                                    : 'Non renseigné'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-envelope-fill"></i>
                                    </div>

                                    <div>

                                        <small>Adresse email</small>

                                        <?php if ($email !== ''): ?>

                                            <a
                                                href="mailto:<?= htmlspecialchars(
                                                    $email
                                                ) ?>"
                                            >
                                                <strong>
                                                    <?= htmlspecialchars($email) ?>
                                                </strong>
                                            </a>

                                        <?php else: ?>

                                            <strong>
                                                Non renseignée
                                            </strong>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-briefcase-fill"></i>
                                    </div>

                                    <div>

                                        <small>Fonction</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $fonction !== ''
                                                    ? $fonction
                                                    : 'Non renseignée'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-diagram-3-fill"></i>
                                    </div>

                                    <div>

                                        <small>Rôle</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $role !== ''
                                                    ? $role
                                                    : 'Non défini'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </div>

                                    <div>

                                        <small>Niveau d’accès</small>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $niveauAcces !== ''
                                                    ? $niveauAcces
                                                    : 'Non renseigné'
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-calendar-plus-fill"></i>
                                    </div>

                                    <div>

                                        <small>Date de création</small>

                                        <strong>
                                            <?= $dateCreation ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="info-compte">

                                    <div class="info-compte-icone">
                                        <i class="bi bi-calendar-check-fill"></i>
                                    </div>

                                    <div>

                                        <small>Dernière modification</small>

                                        <strong>
                                            <?= $dateModification ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="card section-card mt-4">

                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <h4>Historique du compte</h4>

                            <small class="text-muted">
                                Dernières opérations effectuées
                            </small>

                        </div>

                        <span class="badge-role">

                            <?= count($historique) ?>
                            opération(s)

                        </span>

                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead>

                                    <tr>

                                        <th>Date</th>

                                        <th>Action</th>

                                        <th>Description</th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php if (empty($historique)): ?>

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="text-center py-5 text-muted"
                                        >

                                            <i
                                                class="bi bi-clock-history fs-1 d-block mb-3"
                                            ></i>

                                            Aucun historique disponible pour ce compte.

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($historique as $operation): ?>

                                        <?php

                                        $action = trim(
                                            (string) (
                                                $operation['action']
                                                ?? 'Opération'
                                            )
                                        );

                                        $description = trim(
                                            (string) (
                                                $operation['description']
                                                ?? ''
                                            )
                                        );

                                        $dateAction = formaterDateCompte(
                                            $operation['date_action']
                                            ?? null
                                        );

                                        ?>

                                        <tr>

                                            <td>
                                                <small>
                                                    <?= $dateAction ?>
                                                </small>
                                            </td>

                                            <td>

                                                <span class="badge-role">

                                                    <?= htmlspecialchars(
                                                        $action
                                                    ) ?>

                                                </span>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $description !== ''
                                                        ? $description
                                                        : 'Aucune description'
                                                ) ?>

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

        </div>

    </section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>