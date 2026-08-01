<?php

declare(strict_types=1);

class AdminModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Recherche le Super Admin par username ou email.
     */
    public function trouverSuperAdmin(string $identifiant): array|false
    {
        $sql = "
            SELECT
                id,
                username,
                password,
                nom,
                prenom,
                email,
                fonction,
                role,
                statut_compte,
                doit_changer_mot_de_passe,
                date_creation
            FROM admins
            WHERE (
                username = :identifiant
                OR email = :identifiant
            )
            AND role = 'SUPER_ADMIN'
            LIMIT 1
        ";

        $requete = $this->pdo->prepare($sql);

        $requete->execute([
            'identifiant' => $identifiant
        ]);

        return $requete->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retourne tous les comptes DE et DAF.
     */
    public function getTousLesComptes(): array
    {
        $sql = "
            SELECT
                id,
                username,
                nom,
                prenom,
                email,
                fonction,
                role,
                statut_compte,
                date_creation,
                date_modification,
                date_suspension
            FROM admins
            WHERE role IN ('DE', 'DAF')
            ORDER BY id DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte tous les comptes DE et DAF.
     */
    public function compterComptes(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM admins
            WHERE role IN ('DE', 'DAF')
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte les comptes actifs.
     */
    public function compterActifs(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM admins
            WHERE role IN ('DE', 'DAF')
            AND statut_compte = 'actif'
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte les comptes suspendus.
     */
    public function compterSuspendus(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM admins
            WHERE role IN ('DE', 'DAF')
            AND statut_compte = 'suspendu'
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte les Directeurs des Études.
     */
    public function compterDE(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM admins
            WHERE role = 'DE'
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte les Directeurs Administratifs et Financiers.
     */
    public function compterDAF(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM admins
            WHERE role = 'DAF'
        ";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }
}