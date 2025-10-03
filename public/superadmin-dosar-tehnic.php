<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

$db = getDBConnection();

// Some quick stats for the dossier
try {
    $totals = [
        'companies' => (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
        'users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'documents' => (int)$db->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
        'storage' => (int)$db->query("SELECT COALESCE(SUM(file_size),0) FROM documents")->fetchColumn(),
    ];
} catch (Exception $e) { $totals = ['companies'=>0,'users'=>0,'documents'=>0,'storage'=>0]; }

$page_title = 'Dosar Tehnic - Conformitate Arhivistică';
$page_description = 'Rezumat arhitectură, securitate, politici și proceduri pentru validare oficială';
$current_page = 'system';

ob_start();
?>
<style>
@media print {
  .no-print { display: none !important; }
}
.section-title { border-left: 4px solid #0d6efd; padding-left: .5rem; margin-top: 1.5rem; }
code, pre { background: #f8f9fa; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <div class="btn-group">
    <a href="<?= APP_URL ?>/superadmin-system.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Înapoi</a>
    <a href="<?= APP_URL ?>/superadmin-archival-export.php" class="btn btn-outline-primary"><i class="bi bi-archive"></i> Export Arhivistic</a>
  </div>
  <div>
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
  </div>
  </div>

<div class="row g-3">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="section-title">1. Date generale</h4>
        <ul class="mb-3">
          <li>Aplicație: <?= htmlspecialchars(APP_NAME) ?> (v<?= htmlspecialchars(APP_VERSION) ?>)</li>
          <li>Companii: <?= (int)$totals['companies'] ?> | Utilizatori: <?= (int)$totals['users'] ?> | Documente: <?= (int)$totals['documents'] ?> | Stocare: <?= formatFileSize($totals['storage']) ?></li>
          <li>Timezone: <?= htmlspecialchars(APP_TIMEZONE) ?> | Locale: <?= htmlspecialchars(APP_LOCALE) ?></li>
        </ul>

        <h4 class="section-title">2. Arhitectură și stocare</h4>
        <p>Aplicația folosește arhitectură PHP + MySQL (PDO). Documentele sunt stocate pe disc în <code><?= htmlspecialchars(UPLOAD_PATH) ?></code> iar metadatele în tabelul <code>documents</code>. Exporturile și backup-urile sunt în <code><?= htmlspecialchars(STORAGE_PATH) ?></code>.</p>

        <h4 class="section-title">3. Securitate</h4>
        <ul>
          <li>Autentificare pe sesiune, roluri: superadmin, admin, manager, user</li>
          <li>CSRF pe formulare sensibile, validare extensii/MIME și dimensiune la upload</li>
          <li>Loguri: activitate (DB), erori și securitate (fișiere în <code><?= htmlspecialchars(LOGS_PATH) ?></code>)</li>
          <li>Integritate fișiere: <code>file_hash</code> (SHA-256) per document</li>
        </ul>

        <h4 class="section-title">4. Politici retenție și conversie</h4>
        <ul>
          <li>Retenție backup configurabilă: <code>BACKUP_RETENTION_DAYS</code> (= <?= (int)BACKUP_RETENTION_DAYS ?> zile)</li>
          <li>Conversie PDF→PDF/A la upload: controlată prin <code>system_settings.convert_pdf_to_pdfa_on_upload</code> (true/1 pentru activare)</li>
          <li>Conversie la export: opțiune în pagina „Export Arhivistic” (dacă e activată, fișierele PDF sunt convertite la PDF/A în pachet)</li>
        </ul>

        <h4 class="section-title">5. Proceduri export</h4>
        <ol>
          <li>SuperAdmin → Sistem → Export Arhivistic</li>
          <li>Selectează companie și interval, bifează „Include manifest XML” și opțional „Conversie PDF/A”</li>
          <li>Generează pachetul și descarcă arhiva ZIP</li>
        </ol>
        <p>Manifestul <code>manifest.xml</code> include: id, title, stored path, hash SHA-256, size, mime, created_at, company_id, metadata. Formatul poate fi adaptat conform unei scheme oficiale dacă este furnizată.</p>

        <h4 class="section-title">6. Integritate și audit</h4>
        <ul>
          <li>SHA-256 per fișier înregistrat în DB și exportat în manifest</li>
          <li>Loguri activitate (tabel <code>activity_logs</code>) pentru acțiuni user (login, upload, download, etc.)</li>
        </ul>

        <h4 class="section-title">7. Liste verificare</h4>
        <ul>
          <li>Validare PDF/A: poate fi activată la upload/în export; pentru conformitate strictă se recomandă Ghostscript</li>
          <li>Manifest conform XSD: la disponibilitatea schemei ANR, se aliniază <code>manifest.xml</code> și se adaugă validare</li>
          <li>Checksum pachet: opțional se poate adăuga <code>checksums.txt</code> și hash global ZIP</li>
        </ul>

        <div class="alert alert-info mt-3">
          <i class="bi bi-info-circle me-2"></i>
          Pentru validare oficială: atașați capturi export, un pachet ZIP exemplu și acest dosar tehnic printat.
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content_html = ob_get_clean();
$content_file = null;
require_once __DIR__ . '/../modules/superadmin/layout.php';