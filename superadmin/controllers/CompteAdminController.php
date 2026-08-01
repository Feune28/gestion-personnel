<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/CompteAdminModel.php';

class CompteAdminController
{
    private CompteAdminModel $compteModel;

    public function __construct()
    {
        $this->compteModel = new CompteAdminModel();
    }

    /**
     * Affiche la liste de tous les comptes administratifs.
     */
    public function index(): void
    {
        $titrePage = 'Gestion des comptes';
        $pageActive = 'comptes';

        $messageSucces = $_SESSION['message_succes'] ?? null;
        $messageErreur = $_SESSION['message_erreur'] ?? null;

        unset(
            $_SESSION['message_succes'],
            $_SESSION['message_erreur']
        );

        try {
            $comptes = $this->compteModel->obtenirTousLesComptes();

            $statistiques = $this->compteModel->obtenirStatistiques();

            $totalComptes = (int) (
                $statistiques['total_comptes'] ?? 0
            );

            $totalActifs = (int) (
                $statistiques['total_actifs'] ?? 0
            );

            $totalSuspendus = (int) (
                $statistiques['total_suspendus'] ?? 0
            );

            $totalArchives = (int) (
                $statistiques['total_archives'] ?? 0
            );
        } catch (Throwable $exception) {
            error_log(
                'Erreur CompteAdminController::index : '
                . $exception->getMessage()
            );

            $comptes = [];

            $totalComptes = 0;
            $totalActifs = 0;
            $totalSuspendus = 0;
            $totalArchives = 0;

            $messageErreur = 'Impossible de charger les comptes administratifs.';
        }

        require __DIR__ . '/../views/comptes/index.php';
    }

    /**
     * Affiche les informations détaillées d’un compte.
     */
    public function details(): void
    {
        $idCompte = $this->recupererIdDepuisUrl();

        if ($idCompte === null) {
            $this->definirMessageErreur(
                'Le compte demandé est invalide.'
            );

            $this->rediriger('comptes.php');
        }

        try {
            $compte = $this->compteModel->obtenirCompteParId(
                $idCompte
            );

            if ($compte === null) {
                $this->definirMessageErreur(
                    'Le compte demandé est introuvable.'
                );

                $this->rediriger('comptes.php');
            }

            $historique = $this->compteModel
                ->obtenirHistoriqueCompte($idCompte);
        } catch (Throwable $exception) {
            error_log(
                'Erreur CompteAdminController::details : '
                . $exception->getMessage()
            );

            $this->definirMessageErreur(
                'Impossible de charger les informations du compte.'
            );

            $this->rediriger('comptes.php');
        }

        $titrePage = 'Détails du compte';
        $pageActive = 'comptes';

        require __DIR__ . '/../views/comptes/details.php';
    }

    /**
     * Affiche le formulaire d’ajout.
     *
     * Cette méthode sera complétée à l’étape suivante.
     */
    public function ajouter(): void
    {
        $titrePage = 'Ajouter un compte';
        $pageActive = 'ajouter-compte';

        $erreurs = $_SESSION['erreurs_formulaire'] ?? [];
        $anciennesDonnees = $_SESSION['anciennes_donnees'] ?? [];

        unset(
            $_SESSION['erreurs_formulaire'],
            $_SESSION['anciennes_donnees']
        );

        require __DIR__ . '/../views/comptes/ajouter.php';
    }

    /**
     * Enregistre un nouveau compte.
     *
     * Cette méthode sera complétée à l’étape suivante.
     */
    public function enregistrer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->rediriger('comptes.php?action=ajouter');
        }

        $this->definirMessageErreur(
            'La fonction d’enregistrement est en cours de configuration.'
        );

        $this->rediriger('comptes.php?action=ajouter');
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function modifier(): void
    {
        $this->definirMessageErreur(
            'La modification sera configurée à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Met à jour un compte.
     */
    public function mettreAJour(): void
    {
        $this->definirMessageErreur(
            'La mise à jour sera configurée à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Suspend un compte.
     */
    public function suspendre(): void
    {
        $this->definirMessageErreur(
            'La suspension sera configurée à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Réactive un compte.
     */
    public function reactiver(): void
    {
        $this->definirMessageErreur(
            'La réactivation sera configurée à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Archive un compte.
     */
    public function archiver(): void
    {
        $this->definirMessageErreur(
            'L’archivage sera configuré à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Réinitialise le mot de passe.
     */
    public function reinitialiserMotDePasse(): void
    {
        $this->definirMessageErreur(
            'La réinitialisation sera configurée à l’étape suivante.'
        );

        $this->rediriger('comptes.php');
    }

    /**
     * Affiche l’historique général.
     */
    public function historique(): void
    {
        $titrePage = 'Historique des opérations';
        $pageActive = 'historique';

        try {
            $historique = $this->compteModel
                ->obtenirHistoriqueGeneral();
        } catch (Throwable $exception) {
            error_log(
                'Erreur CompteAdminController::historique : '
                . $exception->getMessage()
            );

            $historique = [];
            $messageErreur = 'Impossible de charger l’historique.';
        }

        require __DIR__ . '/../views/comptes/historique.php';
    }

    /**
     * Affiche les statistiques.
     */
    public function statistiques(): void
    {
        $titrePage = 'Statistiques des comptes';
        $pageActive = 'statistiques';

        try {
            $statistiques = $this->compteModel
                ->obtenirStatistiques();

            $comptesParRole = $this->compteModel
                ->obtenirNombreDeComptesParRole();

            $comptesParStatut = $this->compteModel
                ->obtenirNombreDeComptesParStatut();
        } catch (Throwable $exception) {
            error_log(
                'Erreur CompteAdminController::statistiques : '
                . $exception->getMessage()
            );

            $statistiques = [];
            $comptesParRole = [];
            $comptesParStatut = [];

            $messageErreur = 'Impossible de charger les statistiques.';
        }

        require __DIR__ . '/../views/comptes/statistiques.php';
    }

    /**
     * Récupère et valide l’identifiant envoyé dans l’URL.
     */
    private function recupererIdDepuisUrl(): ?int
    {
        $idCompte = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($idCompte === false || $idCompte === null) {
            return null;
        }

        return (int) $idCompte;
    }

    /**
     * Enregistre un message de succès en session.
     */
    private function definirMessageSucces(string $message): void
    {
        $_SESSION['message_succes'] = $message;
    }

    /**
     * Enregistre un message d’erreur en session.
     */
    private function definirMessageErreur(string $message): void
    {
        $_SESSION['message_erreur'] = $message;
    }

    /**
     * Effectue une redirection interne.
     */
    private function rediriger(string $destination): never
    {
        header('Location: ' . $destination);
        exit;
    }
}