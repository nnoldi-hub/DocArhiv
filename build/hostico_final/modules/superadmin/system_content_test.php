<?php
// Test simplu pentru System

echo '<div class="alert alert-info">Test: Pagina System funcționează!</div>';

?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-database fs-1 text-primary mb-3"></i>
                <h5 class="fw-bold mb-1">Baza de Date</h5>
                <p class="text-muted">Optimizare și întreținere</p>
                <form method="POST">
                    <input type="hidden" name="action" value="db_optimize">
                    <button class="btn btn-primary">
                        <i class="bi bi-wrench me-1"></i> Optimizează
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                <h5 class="fw-bold mb-1">Backup</h5>
                <p class="text-muted">Backup și restore</p>
                <form method="POST">
                    <input type="hidden" name="action" value="backup_now">
                    <button class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Backup acum
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-speedometer fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold mb-1">Cache</h5>
                <p class="text-muted">Gestionare cache sistem</p>
                <form method="POST">
                    <input type="hidden" name="action" value="cache_clear">
                    <button class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise me-1"></i> Curăță cache
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><i class="bi bi-archive me-1"></i> Export Arhivistic</h5>
                <p class="text-muted mb-3">Generează pachete pentru depunere la Arhivele Naționale.</p>
                <div class="mt-auto">
                    <button class="btn btn-outline-primary" disabled>
                        <i class="bi bi-arrow-right-circle me-1"></i> În dezvoltare
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><i class="bi bi-journal-text me-1"></i> Dosar Tehnic</h5>
                <p class="text-muted mb-3">Rezumat arhitectură și conformitate arhivistică.</p>
                <div class="mt-auto">
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-file-earmark-text me-1"></i> În dezvoltare
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>