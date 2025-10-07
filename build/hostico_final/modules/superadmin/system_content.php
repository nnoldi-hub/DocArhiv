<?php
// modules/superadmin/system_content.php

// Rezultate acțiuni
$sys_success = '';
$sys_success_html = '';
$sys_error = '';

// Pentru detecție/conversie PDF/A
require_once __DIR__ . '/../../includes/functions/archival.php';

// Helper pentru mesaje
function sa_add_success($msg) { global $sys_success; $sys_success .= ($sys_success?"\n":"") . $msg; }
function sa_add_success_html($html) { global $sys_success_html; $sys_success_html .= $html; }
function sa_add_error($msg) { global $sys_error; $sys_error .= ($sys_error?"\n":"") . $msg; }

// Procesează acțiunile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(ROLE_SUPERADMIN)) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'db_optimize':
                $pdo = getDBConnection();
                $tables = ['companies','users','departments','folders','tags','documents','document_tags','document_permissions','activity_logs','notifications','system_settings'];
                foreach ($tables as $t) {
                    // ANALYZE și OPTIMIZE (unde e permis) — ignora erorile minore
                    try { $pdo->query("ANALYZE TABLE `{$t}`"); } catch (Exception $e) {}
                    try { $pdo->query("OPTIMIZE TABLE `{$t}`"); } catch (Exception $e) {}
                }
                sa_add_success('Baza de date a fost analizată și optimizată.');
                break;

            case 'backup_now':
                $timestamp = date('Ymd_His');
                $backupDir = BACKUP_PATH;
                if (!file_exists($backupDir)) { @mkdir($backupDir, 0755, true); }

                // 1) Backup fișiere (storage/documents) într-un ZIP
                $zipPath = $backupDir . "/files_backup_{$timestamp}.zip";
                $zipOk = false;
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE)) {
                        $root = UPLOAD_PATH;
                        $rootLen = strlen($root) + 1;
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
                        foreach ($it as $file) {
                            if ($file->isDir()) continue;
                            $full = $file->getPathname();
                            $local = substr($full, $rootLen);
                            $zip->addFile($full, $local);
                        }
                        $zip->close();
                        $zipOk = file_exists($zipPath);
                    }
                }

                // 2) Export simplu DB (schema+date) – fallback minimal fără mysqldump
                $sqlPath = $backupDir . "/db_backup_{$timestamp}.sql";
                $pdo = getDBConnection();
                $pdo->query('SET NAMES utf8mb4');
                $out = "-- Document Archive backup\n-- Date: ".$timestamp."\nSET FOREIGN_KEY_CHECKS=0;\n\n";
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $t) {
                    // CREATE TABLE
                    $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
                    $createSql = $create['Create Table'] ?? '';
                    if ($createSql) {
                        $out .= "DROP TABLE IF EXISTS `{$t}`;\n{$createSql};\n\n";
                    }
                    // INSERT-uri
                    $stmt = $pdo->query("SELECT * FROM `{$t}`");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $cols = array_map(fn($c)=>"`".$c."`", array_keys($row));
                        $vals = array_map(function($v){
                            if (is_null($v)) return 'NULL';
                            return "'".str_replace(["\\","'"],["\\\\","\\'"], (string)$v)."'";
                        }, array_values($row));
                        $out .= "INSERT INTO `{$t}` (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
                    }
                    $out .= "\n";
                }
                $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
                file_put_contents($sqlPath, $out);

                sa_add_success('Backup creat cu succes.');
                // Linkuri sigure prin handler public
                $links = [];
                if (file_exists($zipPath)) {
                    $links[] = "<a href='" . APP_URL . "/superadmin-download.php?name=" . rawurlencode(basename($zipPath)) . "' target='_blank'>Descarcă fișiere</a>";
                }
                if (file_exists($sqlPath)) {
                    $links[] = "<a href='" . APP_URL . "/superadmin-download.php?name=" . rawurlencode(basename($sqlPath)) . "' target='_blank'>Descarcă SQL</a>";
                }
                if (!empty($links)) {
                    sa_add_success_html(' ' . implode(' | ', $links));
                }
                break;

            case 'cache_clear':
                $cleared = 0; $failed = 0;
                if (file_exists(CACHE_PATH)) {
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CACHE_PATH, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($it as $file) {
                        $path = $file->getPathname();
                        // păstrează eventual fișiere de rate limit curente, dacă e nevoie
                        if (strpos($path, 'rate_limit_') !== false) { continue; }
                        if ($file->isDir()) {
                            @rmdir($path) ? $cleared++ : $failed++;
                        } else {
                            @unlink($path) ? $cleared++ : $failed++;
                        }
                    }
                }
                sa_add_success("Cache curățat. Elemente: ".$cleared.( $failed?" (nereușite: $failed)":'' ));
                break;

            case 'pdfa_save':
                $pdo = getDBConnection();
                $tool = trim($_POST['pdfa_tool_path'] ?? '');
                $icc  = trim($_POST['pdfa_icc_path'] ?? '');
                $verapdf = trim($_POST['verapdf_path'] ?? '');
                $std  = in_array(($_POST['pdfa_standard'] ?? '2'), ['1','2','3'], true) ? $_POST['pdfa_standard'] : '2';
                $onUpload = !empty($_POST['convert_pdf_to_pdfa_on_upload']) ? '1' : '0';

                // upsert helper
                $upsert = function($k, $v) use ($pdo) {
                    $u = $pdo->prepare('UPDATE system_settings SET setting_value = :v WHERE setting_key = :k');
                    $u->execute([':v'=>$v, ':k'=>$k]);
                    if ($u->rowCount() === 0) {
                        $i = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (:k, :v)');
                        $i->execute([':k'=>$k, ':v'=>$v]);
                    }
                };
                $upsert('pdfa_tool_path', $tool);
                $upsert('pdfa_icc_path', $icc);
                $upsert('verapdf_path', $verapdf);
                $upsert('pdfa_standard', $std);
                $upsert('convert_pdf_to_pdfa_on_upload', $onUpload);

                sa_add_success('Setările PDF/A au fost salvate.');
                // Hints
                if ($tool && !file_exists($tool)) {
                    sa_add_error('Atenție: calea către Ghostscript nu pare validă: ' . $tool);
                }
                if ($icc && !file_exists($icc)) {
                    sa_add_error('Atenție: calea către profilul ICC nu pare validă: ' . $icc);
                }
                if ($verapdf && !file_exists($verapdf)) {
                    sa_add_error('Atenție: calea către veraPDF nu pare validă: ' . $verapdf);
                }
                break;

            case 'pdfa_test':
                // Test detecție Ghostscript și ICC
                $gs = find_gs_executable();
                $icc = find_icc_profile();
                if ($gs && $gs !== 'gswin64c' ? file_exists($gs) : true) {
                    sa_add_success('Ghostscript detectat: ' . htmlspecialchars($gs));
                } else {
                    sa_add_error('Ghostscript nu a fost detectat. Instalați-l și/sau setați calea în setări.');
                }
                if ($icc && file_exists($icc)) {
                    sa_add_success('Profil ICC detectat: ' . htmlspecialchars($icc));
                } else {
                    sa_add_error('Profil ICC nu a fost găsit. Este opțional, dar recomandat.');
                }
                // veraPDF
                $vp = find_verapdf_executable();
                if ($vp && ($vp==='verapdf' || file_exists($vp))) {
                    sa_add_success('veraPDF detectat: ' . htmlspecialchars($vp));
                } else {
                    sa_add_error('veraPDF nu a fost detectat. Este opțional, dar util pentru validare.');
                }
                break;

            default:
                sa_add_error('Acțiune invalidă.');
        }
    } catch (Exception $e) {
        sa_add_error('Eroare: ' . $e->getMessage());
    }
}
?>

<?php if (!empty($sys_success) || !empty($sys_success_html)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php if (!empty($sys_success)) echo nl2br(htmlspecialchars($sys_success)); ?>
        <?php if (!empty($sys_success_html)) echo $sys_success_html; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($sys_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= nl2br(htmlspecialchars($sys_error)) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-database fs-1 text-primary mb-3"></i>
                <h5 class="fw-bold mb-1">Baza de Date</h5>
                <p class="text-muted">Optimizare și întreținere</p>
                <form method="POST">
                    <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cache_clear">
                    <button class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise me-1"></i> Curăță cache
                    </button>
                </form>
            </div>
        </div>
    </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="bi bi-archive me-1"></i> Export Arhivistic</h5>
                    <p class="text-muted mb-3">Generează pachete pentru depunere la Arhivele Naționale (manifest + fișiere, opțional PDF/A).</p>
                    <div class="mt-auto">
                        <a href="<?= APP_URL ?>/superadmin-archival-export.php" class="btn btn-outline-primary"><i class="bi bi-arrow-right-circle me-1"></i> Deschide</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="bi bi-journal-text me-1"></i> Dosar Tehnic</h5>
                    <p class="text-muted mb-3">Rezumat arhitectură, securitate, politici și proceduri pentru conformitate arhivistică.</p>
                    <div class="mt-auto">
                        <a href="<?= APP_URL ?>/superadmin-dosar-tehnic.php" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i> Deschide</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Citește valorile actuale pentru PDF/A
    $current_tool = get_system_setting_value('pdfa_tool_path') ?: '';
    $current_icc = get_system_setting_value('pdfa_icc_path') ?: '';
    $current_std = get_system_setting_value('pdfa_standard') ?: '2';
    $current_on_upload = get_system_setting_value('convert_pdf_to_pdfa_on_upload');
    $current_on_upload = is_string($current_on_upload) ? (trim($current_on_upload) === '1' || strtolower(trim($current_on_upload)) === 'true') : false;
    $current_verapdf = get_system_setting_value('verapdf_path') ?: '';
    ?>
    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-filetype-pdf me-1"></i> PDF/A Converter (Ghostscript)</h5>
                    <p class="text-muted">Configurați calea către Ghostscript și profilul ICC. Puteți activa conversia PDF→PDF/A la upload. Opțional, setați și calea către veraPDF pentru validare.</p>
                    <form method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="pdfa_save">
                        <div class="col-12">
                            <label class="form-label">Cale Ghostscript (gswin64c.exe)</label>
                            <input type="text" name="pdfa_tool_path" class="form-control" placeholder="ex: C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe" value="<?= htmlspecialchars($current_tool) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cale profil ICC (sRGB)</label>
                            <input type="text" name="pdfa_icc_path" class="form-control" placeholder="ex: C:\\Windows\\System32\\spool\\drivers\\color\\sRGB Color Space Profile.icm" value="<?= htmlspecialchars($current_icc) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cale veraPDF (verapdf.bat)</label>
                            <input type="text" name="verapdf_path" class="form-control" placeholder="ex: C:\\Program Files\\veraPDF\\verapdf.bat" value="<?= htmlspecialchars($current_verapdf) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Standard PDF/A</label>
                            <select name="pdfa_standard" class="form-select">
                                <option value="1" <?= $current_std==='1'?'selected':'' ?>>PDF/A-1</option>
                                <option value="2" <?= $current_std==='2'?'selected':'' ?>>PDF/A-2</option>
                                <option value="3" <?= $current_std==='3'?'selected':'' ?>>PDF/A-3</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="convert_on_upload" name="convert_pdf_to_pdfa_on_upload" <?= $current_on_upload?'checked':'' ?>>
                                <label class="form-check-label" for="convert_on_upload">Activează conversia PDF→PDF/A la upload</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Salvează setări</button>
                            <button class="btn btn-outline-secondary" type="submit" formaction="" formmethod="POST" name="action" value="pdfa_test">
                                <?= csrf_field() ?>
                                <i class="bi bi-search me-1"></i> Testează detecția
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Dacă Ghostscript nu este instalat, descărcați-l de la: <a href="https://ghostscript.com/releases/gsdnld.html" target="_blank">ghostscript.com</a>. Pentru veraPDF: <a href="https://verapdf.org/downloads/" target="_blank">verapdf.org</a>. După instalare, completați căile aici.</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
