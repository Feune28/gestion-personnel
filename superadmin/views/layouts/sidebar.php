<?php

declare(strict_types=1);

$pageActive = $pageActive ?? '';

?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">

        <img
            src="/app_exp/uploads/logo/logo.jpeg"
            alt="Logo CMAK"
            onerror="this.style.display='none'"
        >

        <h4>CMAK</h4>

        <small>SUPER ADMIN</small>

    </div>

    <nav>

        <div class="menu-title">
            Administration
        </div>

        <a
            href="dashboard.php"
            class="<?= $pageActive === 'dashboard' ? 'active' : '' ?>"
            title="Tableau de bord"
        >

            <i class="bi bi-grid-fill"></i>

            <span>Tableau de bord</span>

        </a>

        <a
            href="comptes.php"
            class="<?= $pageActive === 'comptes' ? 'active' : '' ?>"
            title="Comptes DE et DAF"
        >

            <i class="bi bi-people-fill"></i>

            <span>Comptes DE / DAF</span>

        </a>

        <a
            href="comptes.php?action=ajouter"
            class="<?= $pageActive === 'ajouter-compte' ? 'active' : '' ?>"
            title="Ajouter un compte"
        >

            <i class="bi bi-person-plus-fill"></i>

            <span>Ajouter un compte</span>

        </a>

        <div class="menu-title">
            Contrôle
        </div>

        <a
            href="#"
            title="Historique"
        >

            <i class="bi bi-clock-history"></i>

            <span>Historique</span>

        </a>

        <a
            href="#"
            title="Statistiques"
        >

            <i class="bi bi-bar-chart-fill"></i>

            <span>Statistiques</span>

        </a>

        <a
            href="#"
            title="Sécurité"
        >

            <i class="bi bi-shield-check"></i>

            <span>Sécurité</span>

        </a>

        <div class="menu-title">
            Compte
        </div>

        <a
            href="#"
            title="Mon profil"
        >

            <i class="bi bi-person-circle"></i>

            <span>Mon profil</span>

        </a>

        <a
            href="logout.php"
            class="deconnexion"
            title="Déconnexion"
            onclick="return confirm('Voulez-vous vraiment vous déconnecter ?')"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>Déconnexion</span>

        </a>

    </nav>

</aside>