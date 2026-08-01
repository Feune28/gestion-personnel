<?php
// logout_personnel.php
// Déconnexion sécurisée du personnel

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conserver l'identifiant uniquement pour le journal
$personnelId = $_SESSION['personnel'] ?? null;

// Si aucune session personnel n'est active
if ($personnelId === null) {
    header('Location: login_personnel.php');
    exit;
}

// Journaliser la déconnexion
error_log(
    'Déconnexion du personnel ID : ' .
    (int) $personnelId .
    ' à ' .
    date('Y-m-d H:i:s')
);

// Supprimer toutes les données de session
$_SESSION = [];

// Supprimer le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

// Détruire définitivement la session
session_destroy();

// Rediriger vers la connexion
header('Location: login_personnel.php');
exit;