<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';

// Check if user is authenticated and has superadmin role
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}
?>

<!DOCTYPE html>
< lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem - SuperAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-gear me-2"></i>Setări Sistem</h1>
                <p class="text-muted">Configurări globale și întreținere sistem</p>
            </div>
            <div>
                <a href="/document-archive/public/superadmin-dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>În dezvoltare:</strong> Pagina Setări Sistem va fi implementată în curând.
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-database fs-1 text-primary mb-3"></i>
                        <h5>Baza de Date</h5>
                        <p class="text-muted">Optimizare și întreținere</p>
                        <button class="btn btn-outline-primary" disabled>
                            <i class="bi bi-wrench"></i> Optimizează
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                        <h5>Backup</h5>
                        <p class="text-muted">Backup și restore</p>
                        <button class="btn btn-outline-success" disabled>
                            <i class="bi bi-download"></i> Backup
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-speedometer fs-1 text-warning mb-3"></i>
                        <h5>Cache</h5>
                        <p class="text-muted">Gestionare cache sistem</p>
                        <button class="btn btn-outline-warning" disabled>
                            <i class="bi bi-arrow-clockwise"></i> Curăță
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
