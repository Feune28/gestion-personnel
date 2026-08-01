<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/AdminModel.php';

class LoginController
{
    private AdminModel $adminModel;

    public function __construct(PDO $pdo)
    {
        $this->adminModel = new AdminModel($pdo);
    }

    /**
     * Affiche et traite la page de connexion.
     */
    public function connexion(): void
    {
        if (
            isset($_SESSION['superadmin_id']) &&
            isset($_SESSION['superadmin_role']) &&
            $_SESSION['superadmin_role'] === 'SUPER_ADMIN'
        ) {
            header('Location: dashboard.php');
            exit;
        }

        $erreur = '';
        $identifiant = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifiant = trim($_POST['identifiant'] ?? '');
            $motDePasse = $_POST['password'] ?? '';

            if ($identifiant === '' || $motDePasse === '') {
                $erreur = 'Veuillez renseigner votre identifiant et votre mot de passe.';
            } else {
                $admin = $this->adminModel->trouverSuperAdmin($identifiant);

                if (!$admin) {
                    $erreur = 'Aucun compte Super Administrateur trouvé avec cet identifiant.';
                } elseif (($admin['statut_compte'] ?? '') !== 'actif') {
                    $erreur = 'Votre compte Super Administrateur est suspendu.';
                } elseif (!password_verify($motDePasse, $admin['password'])) {
                    $erreur = 'Mot de passe incorrect.';
                } else {
                    $this->creerSession($admin);

                    header('Location: dashboard.php');
                    exit;
                }
            }
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Crée la session du Super Administrateur.
     */
    private function creerSession(array $admin): void
    {
        session_regenerate_id(true);

        $_SESSION['superadmin_id'] = (int) $admin['id'];
        $_SESSION['superadmin_username'] = $admin['username'] ?? '';
        $_SESSION['superadmin_nom'] = $admin['nom'] ?? '';
        $_SESSION['superadmin_prenom'] = $admin['prenom'] ?? '';
        $_SESSION['superadmin_email'] = $admin['email'] ?? '';
        $_SESSION['superadmin_role'] = $admin['role'];
        $_SESSION['superadmin_fonction'] =
            $admin['fonction'] ?: 'Super Administrateur';

        $_SESSION['superadmin_connecte'] = true;
        $_SESSION['superadmin_derniere_activite'] = time();
    }
}