'use strict';

/* =========================================================
   SIDEBAR RÉDUITE / AGRANDIE
   ========================================================= */

const sidebarToggle = document.getElementById('sidebarToggle');

function setSidebarCollapsed(enabled) {
    document.body.classList.toggle(
        'sidebar-collapsed',
        enabled
    );

    localStorage.setItem(
        'superadmin-sidebar-collapsed',
        enabled ? 'enabled' : 'disabled'
    );
}

const sidebarState = localStorage.getItem(
    'superadmin-sidebar-collapsed'
);

if (sidebarState === 'enabled') {
    setSidebarCollapsed(true);
}

sidebarToggle?.addEventListener('click', function () {
    const isCollapsed = document.body.classList.contains(
        'sidebar-collapsed'
    );

    setSidebarCollapsed(!isCollapsed);
});


/* =========================================================
   MENU DU PROFIL
   ========================================================= */

const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById(
    'profileDropdown'
);

profileBtn?.addEventListener('click', function (event) {
    event.stopPropagation();

    profileDropdown?.classList.toggle('show');
});

profileDropdown?.addEventListener('click', function (event) {
    event.stopPropagation();
});

document.addEventListener('click', function () {
    profileDropdown?.classList.remove('show');
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        profileDropdown?.classList.remove('show');
    }
});


/* =========================================================
   MODE SOMBRE
   ========================================================= */

const darkToggle = document.getElementById('darkToggle');
const darkIcon = darkToggle?.querySelector('i');

function setDarkMode(enabled) {
    document.body.classList.toggle(
        'dark-mode',
        enabled
    );

    if (darkIcon) {
        darkIcon.className = enabled
            ? 'bi bi-sun-fill'
            : 'bi bi-moon-fill';
    }

    if (darkToggle) {
        darkToggle.title = enabled
            ? 'Activer le mode clair'
            : 'Activer le mode sombre';
    }

    localStorage.setItem(
        'superadmin-dark-mode',
        enabled ? 'enabled' : 'disabled'
    );
}

const darkModeState = localStorage.getItem(
    'superadmin-dark-mode'
);

if (darkModeState === 'enabled') {
    setDarkMode(true);
}

darkToggle?.addEventListener('click', function () {
    const darkModeEnabled = document.body.classList.contains(
        'dark-mode'
    );

    setDarkMode(!darkModeEnabled);
});


/* =========================================================
   CONFIRMATION DES ACTIONS IMPORTANTES
   ========================================================= */

document.querySelectorAll('[data-confirm]').forEach(function (element) {
    element.addEventListener('click', function (event) {
        const message = element.dataset.confirm;

        if (
            message
            && !window.confirm(message)
        ) {
            event.preventDefault();
        }
    });
});


/* =========================================================
   FERMETURE AUTOMATIQUE DES ALERTES
   ========================================================= */

const alertes = document.querySelectorAll(
    '.alert.alert-dismissible'
);

alertes.forEach(function (alerte) {
    window.setTimeout(function () {
        const bootstrapAlert = bootstrap.Alert.getOrCreateInstance(
            alerte
        );

        bootstrapAlert.close();
    }, 5000);
});