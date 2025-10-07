<?php
/**
 * Archival utilities: conversion and export packaging
 */

require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/helpers.php';

// Contract
// - buildArchivalPackage($db, $options): returns [ 'zip_path' => string, 'manifest_path' => string ]
//   $options: company_id?:int, from?:string(YYYY-MM-DD), to?:string(YYYY-MM-DD), include_xml:bool, convert_pdfa:bool

function ensure_archival_dirs() {
    $base = STORAGE_PATH . '/exports';
    ensureDirectoryExists($base);
    return $base;
}

function get_system_setting_value($key) {
    try {
        if (function_exists('getDBConnection')) {
            $db = getDBConnection();
            $st = $db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1');
            $st->execute([':k' => $key]);
            $val = $st->fetchColumn();
            return $val !== false ? $val : null;
        }
    } catch (Exception $e) { /* ignore */ }
    return null;
}

function find_gs_executable() {
    // 1) Config override
    $cfg = get_system_setting_value('pdfa_tool_path');
    if ($cfg && file_exists($cfg)) return $cfg;
    // 2) Common Windows paths
    $candidates = [];
    $programFiles = [
        getenv('ProgramFiles') ?: 'C:\\Program Files',
        getenv('ProgramFiles(x86)') ?: 'C:\\Program Files (x86)'
    ];
    foreach ($programFiles as $pf) {
        foreach (['gswin64c.exe','gswin32c.exe'] as $exe) {
            $glob = glob($pf . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $exe);
            if ($glob) $candidates = array_merge($candidates, $glob);
        }
    }
    // Pick highest version path if found
    if (!empty($candidates)) {
        rsort($candidates, SORT_NATURAL);
        return $candidates[0];
    }
    // 3) PATH (assume available)
    return 'gswin64c'; // may resolve via PATH
}

function find_icc_profile() {
    // 1) Config override
    $cfg = get_system_setting_value('pdfa_icc_path');
    if ($cfg && file_exists($cfg)) return $cfg;
    // 2) Common Windows ICC locations
    $common = [
        'C:\\Windows\\System32\\spool\\drivers\\color\\sRGB Color Space Profile.icm',
        'C:\\Windows\\System32\\spool\\drivers\\color\\sRGB_IEC61966-2-1.icc',
        'C:\\Windows\\System32\\spool\\drivers\\color\\sRGB_IEC61966-2-1_no_black_scaling.icc'
    ];
    foreach ($common as $p) { if (file_exists($p)) return $p; }
    // 3) Inside Ghostscript Resource (if installed)
    $gs = find_gs_executable();
    if ($gs && $gs !== 'gswin64c') {
        $gsRes = dirname(dirname($gs)) . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . 'ColorSpace' . DIRECTORY_SEPARATOR . 'sRGB.icc';
        if (file_exists($gsRes)) return $gsRes;
    }
    return null; // optional
}

function find_verapdf_executable() {
    // Config override
    $cfg = get_system_setting_value('verapdf_path');
    if ($cfg && file_exists($cfg)) return $cfg;
    // Common Windows locations
    $candidates = [
        'C:\\Program Files\\veraPDF\\verapdf.bat',
        'C:\\Program Files (x86)\\veraPDF\\verapdf.bat',
    ];
    foreach ($candidates as $p) { if (file_exists($p)) return $p; }
    // PATH fallback
    return 'verapdf';
}

function validate_pdfa_with_verapdf(array $pdfPaths, string $reportPath): bool {
    $verapdf = find_verapdf_executable();
    if (!$verapdf) return false;
    $okCount = 0; $failCount = 0;
    $report = "veraPDF validation report\nGenerated: ".date('c')."\n\n";
    foreach ($pdfPaths as $pdf) {
        if (!file_exists($pdf)) { $failCount++; $report .= basename($pdf).": MISSING\n"; continue; }
        $exe = strpos($verapdf, ' ') !== false ? '"'.$verapdf.'"' : $verapdf;
        $pdfArg = '"'.$pdf.'"';
        $cmd = $exe.' --format text '.$pdfArg;
        $descriptor = [1 => ['pipe','w'], 2 => ['pipe','w']];
        $p = proc_open('cmd /C '.$cmd, $descriptor, $pipes);
        $stdout = ''; $stderr = ''; $exit = 1;
        if (is_resource($p)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            foreach ($pipes as $pp) { if (is_resource($pp)) fclose($pp); }
            $exit = proc_close($p);
        }
        // Heuristic: exit 0 and text contains PASS
        $passed = ($exit === 0) && (stripos($stdout, 'PASS') !== false || stripos($stdout, 'isCompliant=true') !== false);
        if ($passed) { $okCount++; } else { $failCount++; }
        $report .= basename($pdf).': '.($passed?'OK':'FAIL')."\n";
    }
    $report .= "\nSummary: OK=$okCount, FAIL=$failCount\n";
    @file_put_contents($reportPath, $report);
    return $failCount === 0 && $okCount > 0;
}

function to_pdfa($sourcePath, $destPath) {
    // Attempt Ghostscript PDF/A conversion on Windows
    $gs = find_gs_executable();
    if (!$gs) return false;
    $standard = get_system_setting_value('pdfa_standard'); // '1' or '2'
    if (!in_array($standard, ['1','2','3'], true)) { $standard = '2'; }
    $icc = find_icc_profile();

    $src = '"' . $sourcePath . '"';
    $dst = '"' . $destPath . '"';
    $gsExe = strpos($gs, ' ') !== false ? '"' . $gs . '"' : $gs;

    $parts = [
        $gsExe,
        "-dPDFA={$standard}",
        "-dBATCH",
        "-dNOPAUSE",
        "-sDEVICE=pdfwrite",
        "-dUseCIEColor",
        "-sProcessColorModel=DeviceRGB",
        "-sColorConversionStrategy=RGB",
        "-dPDFACompatibilityPolicy=1",
    ];
    if ($icc) { $parts[] = "-sOutputICCProfile=\"{$icc}\""; }
    $parts[] = "-sOutputFile={$dst}";
    $parts[] = $src;

    $cmd = implode(' ', $parts);
    // Use cmd /C on Windows to handle quoting
    $descriptor = [1 => ['pipe','w'], 2 => ['pipe','w']];
    $process = proc_open('cmd /C ' . $cmd, $descriptor, $pipes);
    if (is_resource($process)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) { if (is_resource($p)) fclose($p); }
        $exit = proc_close($process);
        // Consider success when exit 0 and file produced
        if ($exit === 0 && file_exists($destPath) && filesize($destPath) > 0) {
            return true;
        }
        // Fallback: try without ICC if first failed and ICC present
        if ($icc) {
            $partsNoICC = array_filter($parts, fn($a) => strpos($a, 'sOutputICCProfile') === false);
            $cmd2 = implode(' ', $partsNoICC);
            $process2 = proc_open('cmd /C ' . $cmd2, $descriptor, $pipes2);
            if (is_resource($process2)) {
                $stdout2 = stream_get_contents($pipes2[1]);
                $stderr2 = stream_get_contents($pipes2[2]);
                foreach ($pipes2 as $p) { if (is_resource($p)) fclose($p); }
                $exit2 = proc_close($process2);
                if ($exit2 === 0 && file_exists($destPath) && filesize($destPath) > 0) {
                    return true;
                }
            }
        }
    }
    return false;
}

function generate_manifest_xml(array $docs, array $meta) {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $root = $xml->createElement('ArchivalExport');
    $root->setAttribute('version', '1.0');
    $root->appendChild($xml->createElement('GeneratedAt', date('c')));
    foreach ($meta as $k => $v) {
        $root->appendChild($xml->createElement($k, (string)$v));
    }
    $items = $xml->createElement('Documents');
    foreach ($docs as $d) {
        $item = $xml->createElement('Document');
        $item->appendChild($xml->createElement('Id', (string)$d['id']));
        $item->appendChild($xml->createElement('Title', $d['title'] ?? ''));
        $item->appendChild($xml->createElement('OriginalFile', $d['file_name']));
        $item->appendChild($xml->createElement('StoredPath', $d['file_path']));
        $item->appendChild($xml->createElement('HashSHA256', $d['file_hash'] ?? ''));
        $item->appendChild($xml->createElement('Size', (string)$d['file_size']));
        $item->appendChild($xml->createElement('Mime', $d['mime_type'] ?? ''));
        $item->appendChild($xml->createElement('CreatedAt', $d['created_at'] ?? ''));
        $item->appendChild($xml->createElement('CompanyId', (string)$d['company_id']));
        if (!empty($d['metadata'])) {
            $item->appendChild($xml->createElement('Metadata', is_array($d['metadata']) ? json_encode($d['metadata']) : (string)$d['metadata']));
        }
        $items->appendChild($item);
    }
    $root->appendChild($items);
    $xml->appendChild($root);
    return $xml->saveXML();
}

function buildArchivalPackage(PDO $db, array $options) {
    $t0 = microtime(true);
    $base = ensure_archival_dirs();
    $companyId = isset($options['company_id']) ? (int)$options['company_id'] : null;
    $from = $options['from'] ?? null;
    $to = $options['to'] ?? null;
    $includeXml = !empty($options['include_xml']);
    $convertPdfa = !empty($options['convert_pdfa']);
    $perDocMetadata = !empty($options['per_doc_metadata']);

    $where = ['status = "active"'];
    $params = [];
    if ($companyId) { $where[] = 'company_id = :cid'; $params[':cid'] = $companyId; }
    if ($from) { $where[] = 'created_at >= :from'; $params[':from'] = $from . ' 00:00:00'; }
    if ($to) { $where[] = 'created_at <= :to'; $params[':to'] = $to . ' 23:59:59'; }
    $sql = 'SELECT id, company_id, title, file_name, file_path, file_size, file_hash, mime_type, created_at, metadata FROM documents WHERE ' . implode(' AND ', $where) . ' ORDER BY id';
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$docs) {
        throw new Exception('Nu s-au găsit documente pentru criteriile selectate.');
    }

    $stamp = date('Ymd_His');
    $label = 'all';
    if ($companyId) $label = 'c' . $companyId;
    if ($from || $to) $label .= '_' . ($from ?: 'start') . '_to_' . ($to ?: 'end');
    $workDir = $base . DIRECTORY_SEPARATOR . 'work_' . $label . '_' . $stamp;
    ensureDirectoryExists($workDir);
    $filesDir = $workDir . DIRECTORY_SEPARATOR . 'files';
    ensureDirectoryExists($filesDir);

    // Copy files and optionally convert PDFs to PDF/A
    foreach ($docs as &$d) {
        $src = UPLOAD_PATH . DIRECTORY_SEPARATOR . $d['file_path'];
        $dest = $filesDir . DIRECTORY_SEPARATOR . $d['file_path'];
        ensureDirectoryExists(dirname($dest));
        $ext = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
        if ($convertPdfa && $ext === 'pdf') {
            $pdfaPath = preg_replace('/\.pdf$/i', '_pdfa.pdf', $dest);
            $ok = to_pdfa($src, $pdfaPath);
            if ($ok) {
                $d['archival_file'] = str_replace($workDir . DIRECTORY_SEPARATOR, '', $pdfaPath);
            } else {
                copy($src, $dest);
                $d['archival_file'] = 'files/' . $d['file_path'];
            }
        } else {
            copy($src, $dest);
            $d['archival_file'] = 'files/' . $d['file_path'];
        }

        // Per-document metadata XML (optional)
        if ($perDocMetadata) {
            $metaDoc = new DOMDocument('1.0', 'UTF-8');
            $metaDoc->formatOutput = true;
            $root = $metaDoc->createElement('DocumentMetadata');
            $root->appendChild($metaDoc->createElement('OriginalFile', $d['file_name']));
            $root->appendChild($metaDoc->createElement('StoredPath', $d['archival_file']));
            $root->appendChild($metaDoc->createElement('Size', (string)$d['file_size']));
            $root->appendChild($metaDoc->createElement('Mime', $d['mime_type'] ?? ''));
            $hash = $d['file_hash'] ?? '';
            $hashEl = $metaDoc->createElement('Hash', $hash);
            $hashEl->setAttribute('algorithm', 'SHA-256');
            $root->appendChild($hashEl);
            $root->appendChild($metaDoc->createElement('CreatedAt', $d['created_at'] ?? ''));
            $root->appendChild($metaDoc->createElement('CompanyId', (string)$d['company_id']));
            if (!empty($d['metadata'])) {
                $root->appendChild($metaDoc->createElement('Metadata', is_array($d['metadata']) ? json_encode($d['metadata']) : (string)$d['metadata']));
            }
            $metaDoc->appendChild($root);
            $metaRelPath = preg_replace('/\.[A-Za-z0-9]+$/', '_metadata.xml', $d['archival_file']);
            $metaAbsPath = $workDir . DIRECTORY_SEPARATOR . $metaRelPath;
            ensureDirectoryExists(dirname($metaAbsPath));
            $metaDoc->save($metaAbsPath);
        }
    }

    // Manifest
    $manifestPath = $workDir . DIRECTORY_SEPARATOR . 'manifest.xml';
    if ($includeXml) {
        $xml = generate_manifest_xml($docs, [
            'CompanyFilter' => $companyId ?: 'all',
            'From' => $from ?: '',
            'To' => $to ?: '',
            'ConvertPDFA' => $convertPdfa ? 'true' : 'false',
        ]);
        file_put_contents($manifestPath, $xml);
    }

    // Create ZIP
    $zipName = 'archival_export_' . $label . '_' . $stamp . '.zip';
    $zipPath = $base . DIRECTORY_SEPARATOR . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Nu se poate crea arhiva ZIP.');
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($workDir, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $localName = substr($filePath, strlen($workDir) + 1);
        $zip->addFile($filePath, $localName);
    }
    $zip->close();

    // Copiază manifest în exports pentru download direct (opțional)
    $manifestExportPath = null;
    if ($includeXml && file_exists($manifestPath)) {
        $manifestName = 'manifest_' . $label . '_' . $stamp . '.xml';
        $manifestExportPath = $base . DIRECTORY_SEPARATOR . $manifestName;
        @copy($manifestPath, $manifestExportPath);
    }

    // Validare PDF/A cu veraPDF (opțional)
    $validationReportExportPath = null;
    if (!empty($options['validate_pdfa'])) {
        // Colectează PDF-urile din workDir
        $pdfs = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($workDir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && preg_match('/\.pdf$/i', $file->getFilename())) {
                $pdfs[] = $file->getPathname();
            }
        }
        if (!empty($pdfs)) {
            $reportLocal = $workDir . DIRECTORY_SEPARATOR . 'verapdf_report.txt';
            validate_pdfa_with_verapdf($pdfs, $reportLocal);
            // include în ZIP deja (se află în work dir)
            // copiază și în exports pentru download direct
            $validationReportExportPath = $base . DIRECTORY_SEPARATOR . 'verapdf_report_' . $label . '_' . $stamp . '.txt';
            @copy($reportLocal, $validationReportExportPath);
        }
    }

    $zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;
    $duration = round(microtime(true) - $t0, 3);

    return [
        'zip_path' => $zipPath,
        'manifest_path' => $manifestPath,
        'manifest_export_path' => $manifestExportPath,
        'validation_report_export_path' => $validationReportExportPath,
        'count' => count($docs),
        'zip_size' => $zipSize,
        'duration' => $duration,
    ];
}
