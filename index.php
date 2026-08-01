<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion École</title>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- CSS -->

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="d-flex">

    <!-- SIDEBAR -->

    <div class="sidebar p-3">

        <h3 class="text-white text-center mb-4">
            ECOLE
        </h3>

        <a href="index.php" class="menu-link">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="personnel.php" class="menu-link">
            <i class="bi bi-people"></i>
            Personnel
        </a>

        <a href="#" class="menu-link">
            <i class="bi bi-file-earmark-pdf"></i>
            Documents
        </a>

        <a href="#" class="menu-link">
            <i class="bi bi-clock-history"></i>
            Historique
        </a>

    </div>

    <!-- CONTENU -->

    <div class="content flex-grow-1">

        <!-- TOPBAR -->

        <div class="topbar shadow-sm">

            <h4>
                Tableau de bord
            </h4>

        </div>

        <!-- DASHBOARD -->

        <div class="container-fluid mt-4">

            <div class="row">

                <div class="col-md-4">

                    <div class="card dashboard-card shadow">

                        <h5>Personnel</h5>

                        <h2>12</h2>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card dashboard-card shadow">

                        <h5>Documents</h5>

                        <h2>25</h2>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card dashboard-card shadow">

                        <h5>Suspendus</h5>

                        <h2>2</h2>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>