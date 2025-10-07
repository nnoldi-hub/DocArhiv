<?php
// modules/superadmin/reports_content.php
$db = getDBConnection();

// KPIs
try {
    $kpi = [
        'companies' => (int)$db->query("SELECT COUNT(*) c FROM companies")->fetch()['c'],
        'users' => (int)$db->query("SELECT COUNT(*) c FROM users")->fetch()['c'],
        'documents' => (int)$db->query("SELECT COUNT(*) c FROM documents")->fetch()['c'],
        'storage_mb' => round(((int)$db->query("SELECT COALESCE(SUM(file_size),0) t FROM documents")->fetch()['t'])/1024/1024, 1),
    ];
} catch (Exception $e) {
    $kpi = ['companies'=>0,'users'=>0,'documents'=>0,'storage_mb'=>0];
}

// Last 7 days activity counts per day
$daily = [];
try {
    $stmt = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM activity_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d ORDER BY d");
    while ($r = $stmt->fetch()) { $daily[$r['d']] = (int)$r['c']; }
} catch (Exception $e) { $daily = []; }

// Top companies by documents
$topCompanies = [];
try {
    $stmt = $db->query("SELECT c.id, c.company_name, COUNT(d.id) docs, COALESCE(SUM(d.file_size),0) bytes FROM companies c LEFT JOIN documents d ON d.company_id=c.id GROUP BY c.id, c.company_name ORDER BY docs DESC LIMIT 10");
    $topCompanies = $stmt->fetchAll();
} catch (Exception $e) { $topCompanies = []; }
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary me-3"><i class="bi bi-building"></i></div>
                <div>
                    <div class="text-muted small">Companii</div>
                    <div class="h4 mb-0"><?= $kpi['companies'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info me-3"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">Utilizatori</div>
                    <div class="h4 mb-0"><?= $kpi['users'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success me-3"><i class="bi bi-file-earmark"></i></div>
                <div>
                    <div class="text-muted small">Documente</div>
                    <div class="h4 mb-0"><?= $kpi['documents'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-warning me-3"><i class="bi bi-hdd-stack"></i></div>
                <div>
                    <div class="text-muted small">Stocare</div>
                    <div class="h4 mb-0"><?= $kpi['storage_mb'] ?> MB</div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .stats-card{border:none;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,.08);}
    .stats-icon{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff}
    </style>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Activitate (7 zile)</h5>
            </div>
            <div class="card-body">
                <?php if ($daily): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Data</th><th class="text-end">Evenimente</th></tr></thead>
                            <tbody>
                                <?php foreach ($daily as $d => $c): ?>
                                    <tr><td><?= htmlspecialchars($d) ?></td><td class="text-end"><?= (int)$c ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Sugestie: putem adăuga ulterior un grafic (Chart.js) pe aceleași date.</small>
                <?php else: ?>
                    <div class="text-muted">Nu există activitate în ultimele 7 zile.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top companii după documente</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Companie</th><th class="text-end">Doc</th><th class="text-end">Stocare</th></tr></thead>
                        <tbody>
                        <?php foreach ($topCompanies as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['company_name'] ?? ('#'.$row['id'])) ?></td>
                                <td class="text-end"><?= (int)$row['docs'] ?></td>
                                <td class="text-end"><?= round(($row['bytes'] ?? 0)/1024/1024,1) ?> MB</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-download me-2"></i>Export date</h5></div>
    <div class="card-body">
        <form class="row g-3" method="POST" action="<?= APP_URL ?>/superadmin-export.php" target="_blank">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label">Set date</label>
                <select class="form-select" name="dataset" required>
                    <option value="companies_summary">Companii – sumar documente</option>
                    <option value="users_per_company">Utilizatori per companie</option>
                    <option value="activity_last_30">Activitate ultimile 30 zile</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Format</label>
                <select class="form-select" name="format" required>
                    <option value="csv">CSV</option>
                    <option value="xlsx">Excel (XLSX)</option>
                </select>
            </div>
            <div class="col-md-5 d-flex align-items-end">
                <button class="btn btn-success"><i class="bi bi-download me-1"></i>Exportă</button>
            </div>
        </form>
        <small class="text-muted">PDF se poate adăuga ulterior (de ex. cu Dompdf).</small>
    </div>
</div>
