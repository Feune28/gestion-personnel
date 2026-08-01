<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/AdminModel.php';

class DashboardController
{
    private AdminModel $adminModel;

    public function __construct(PDO $pdo)
    {
        $this->adminModel = new AdminModel($pdo);
    }

    public function index(): void
    {
        $totalComptes = $this->adminModel->compterComptes();
        $totalActifs = $this->adminModel->compterActifs();
        $totalSuspendus = $this->adminModel->compterSuspendus();
        $totalDE = $this->adminModel->compterDE();
        $totalDAF = $this->adminModel->compterDAF();

        $comptes = $this->adminModel->getTousLesComptes();

        require __DIR__ . '/../views/dashboard/index.php';
    }
}