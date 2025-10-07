<?php
/**
 * SuperAdmin data export
 * public/superadmin-export.php
 */
require_once '../config/config.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    http_response_code(403);
    exit('Forbidden');
}

try { verify_csrf(); } catch (Exception $e) {
    http_response_code(400);
    exit('CSRF invalid');
}

$dataset = $_POST['dataset'] ?? '';
$format  = $_POST['format'] ?? 'csv';
$db = getDBConnection();

// Build rows
$headers = [];
$rows = [];
$filename = 'export_' . $dataset . '_' . date('Ymd_His');

switch ($dataset) {
    case 'companies_summary':
        $headers = ['Company ID','Company Name','Documents','Storage MB'];
        $stmt = $db->query("SELECT c.id, c.company_name, COUNT(d.id) docs, COALESCE(SUM(d.file_size),0) bytes FROM companies c LEFT JOIN documents d ON d.company_id=c.id GROUP BY c.id, c.company_name ORDER BY docs DESC");
        while ($r = $stmt->fetch()) {
            $rows[] = [ (int)$r['id'], (string)($r['company_name'] ?? ''), (int)$r['docs'], round(($r['bytes']??0)/1024/1024,2) ];
        }
        break;
    case 'users_per_company':
        $headers = ['Company ID','Company Name','Users'];
        $stmt = $db->query("SELECT c.id, c.company_name, COUNT(u.id) users FROM companies c LEFT JOIN users u ON u.company_id=c.id GROUP BY c.id, c.company_name ORDER BY users DESC");
        while ($r = $stmt->fetch()) { $rows[] = [ (int)$r['id'], (string)($r['company_name'] ?? ''), (int)$r['users'] ]; }
        break;
    case 'activity_last_30':
        $headers = ['Date','Events'];
        $stmt = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM activity_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d");
        while ($r = $stmt->fetch()) { $rows[] = [ (string)$r['d'], (int)$r['c'] ]; }
        break;
    default:
        http_response_code(400);
        exit('Dataset invalid');
}

if ($format === 'xlsx') {
    // Simple XLSX via CSV content served as Excel-compatible MIME
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
}

$fp = fopen('php://output', 'w');
// UTF-8 BOM for Excel
fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($fp, $headers);
foreach ($rows as $row) {
    fputcsv($fp, $row);
}

fclose($fp);
exit;
