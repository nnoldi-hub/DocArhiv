<?php
/**
 * Helper Functions
 * includes/functions/helpers.php
 */

/**
 * Formatează dimensiunea fișierului
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Formatează data în format românesc
 */
function formatDate($date, $format = 'd.m.Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatează data și ora
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Formatează timestamp relativ (acum 5 minute, acum 2 ore, etc)
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'acum ' . $diff . ' secunde';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'acum ' . $minutes . ' ' . ($minutes == 1 ? 'minut' : 'minute');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'acum ' . $hours . ' ' . ($hours == 1 ? 'oră' : 'ore');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'acum ' . $days . ' ' . ($days == 1 ? 'zi' : 'zile');
    } else {
        return formatDate($datetime);
    }
}

/**
 * Sanitizează input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generează token random
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validare email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generare parolă random
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

/**
 * Hash parolă
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificare forță parolă
 */
function isStrongPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    // Cel puțin o literă mare, o literă mică, un număr
    if (!preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Obține extensia fișierului
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Obține iconița pentru tipul de fișier
 */
function getFileIcon($filename) {
    $extension = getFileExtension($filename);
    
    $icons = [
        'pdf' => 'file-earmark-pdf',
        'doc' => 'file-earmark-word',
        'docx' => 'file-earmark-word',
        'xls' => 'file-earmark-excel',
        'xlsx' => 'file-earmark-excel',
        'ppt' => 'file-earmark-ppt',
        'pptx' => 'file-earmark-ppt',
        'txt' => 'file-earmark-text',
        'jpg' => 'file-earmark-image',
        'jpeg' => 'file-earmark-image',
        'png' => 'file-earmark-image',
        'gif' => 'file-earmark-image',
        'zip' => 'file-earmark-zip',
        'rar' => 'file-earmark-zip'
    ];
    
    return $icons[$extension] ?? 'file-earmark';
}

/**
 * Obține culoarea pentru tipul de fișier
 */
function getFileColor($filename) {
    $extension = getFileExtension($filename);
    
    $colors = [
        'pdf' => '#dc2626',
        'doc' => '#2563eb',
        'docx' => '#2563eb',
        'xls' => '#16a34a',
        'xlsx' => '#16a34a',
        'ppt' => '#ea580c',
        'pptx' => '#ea580c',
        'txt' => '#64748b',
        'jpg' => '#7c3aed',
        'jpeg' => '#7c3aed',
        'png' => '#7c3aed',
        'gif' => '#7c3aed',
        'zip' => '#ea580c',
        'rar' => '#ea580c'
    ];
    
    return $colors[$extension] ?? '#64748b';
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generează slug din text
 */
function slugify($text) {
    // Înlocuiește diacritice românești
    $transliterations = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ț' => 'T'
    ];
    $text = strtr($text, $transliterations);
    
    // Convertește la lowercase și înlocuiește spații cu -
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Afișează alerta Bootstrap
 */
function showAlert($message, $type = 'info') {
    $icons = [
        'success' => 'check-circle',
        'error' => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info' => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    $bootstrapType = $type === 'error' ? 'danger' : $type;
    
    return '<div class="alert alert-' . $bootstrapType . ' alert-dismissible fade show" role="alert">
                <i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Obține numele lunii în română
 */
function getMonthName($month) {
    $months = [
        1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
        5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
        9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
    ];
    return $months[$month] ?? '';
}

/**
 * Verifică dacă utilizatorul poate accesa resursa
 */
function canAccess($resourceType, $resourceId = null) {
    // SuperAdmin are acces la tot
    if (hasRole(ROLE_SUPERADMIN)) {
        return true;
    }
    
    // Admin companie are acces la tot în compania lui
    if (hasRole(ROLE_ADMIN)) {
        return true;
    }
    
    // Manager are acces la departamentul său
    if (hasRole(ROLE_MANAGER)) {
        // TODO: Verificare specifică pentru manager
        return true;
    }
    
    // User normal - verifică permisiunile
    return hasPermission($resourceType);
}

/**
 * Calculează procentul de utilizare al spațiului
 */
function getStorageUsagePercent($usedBytes, $limitGB) {
    if ($limitGB == 0) return 0;
    $limitBytes = $limitGB * 1024 * 1024 * 1024;
    return min(100, round(($usedBytes / $limitBytes) * 100, 2));
}

/**
 * Generează culoare random pentru tag/departament
 */
function generateRandomColor() {
    $colors = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
        '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e'
    ];
    return $colors[array_rand($colors)];
}

/**
 * Validare și sanitizare array de taguri
 */
function sanitizeTags($tags) {
    if (is_string($tags)) {
        $tags = explode(',', $tags);
    }
    
    if (!is_array($tags)) {
        return [];
    }
    
    $sanitized = [];
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (!empty($tag) && strlen($tag) <= 50) {
            $sanitized[] = $tag;
        }
    }
    
    return array_unique($sanitized);
}

/**
 * Obține lista cu breadcrumbs pentru navigare
 */
function getBreadcrumbs($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $lastIndex = count($items) - 1;
    foreach ($items as $index => $item) {
        if ($index === $lastIndex) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . 
                     htmlspecialchars($item['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item">';
            if (isset($item['url'])) {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . 
                         htmlspecialchars($item['label']) . '</a>';
            } else {
                $html .= htmlspecialchars($item['label']);
            }
            $html .= '</li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Verifică dacă string-ul este JSON valid
 */
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Convertește array în opțiuni HTML select
 */
function arrayToSelectOptions($array, $valueKey = 'id', $labelKey = 'name', $selectedValue = null) {
    $html = '';
    foreach ($array as $item) {
        $value = is_array($item) ? $item[$valueKey] : $item;
        $label = is_array($item) ? $item[$labelKey] : $item;
        $selected = ($value == $selectedValue) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . 
                 htmlspecialchars($label) . '</option>';
    }
    return $html;
}

/**
 * Paginator simplu
 */
function paginate($totalItems, $currentPage, $itemsPerPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . 
                 ($currentPage - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';
    }
    
    // Pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . 
                 $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . 
                 $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . 
                 ($currentPage + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Send email (placeholder - needs proper SMTP configuration)
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    // TODO: Implementare cu PHPMailer sau Swift Mailer
    // Pentru moment returnăm true
    
    $headers = [];
    if ($isHtml) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
    }
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>';
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Verifică dacă IP-ul este valid
 */
function isValidIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Obține IP-ul clientului
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (isValidIP($ip)) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/**
 * Creează director dacă nu există
 */
function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

/**
 * Șterge director recursiv
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

/**
 * Debug helper - dump and die
 */
function dd($data) {
    if (DEBUG_MODE) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

/**
 * Verifică dacă request-ul este AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Returnează response JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Wrapper simplu pentru începutul layout-ului interior (admin/user)
 */
function view_header($title = 'Aplicație') {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="ro"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">';
    echo '</head><body>';
    if (isset($_SESSION['full_name'])) {
        echo '<nav class="navbar navbar-expand-lg navbar-dark bg-dark"><div class="container-fluid">';
        echo '<a class="navbar-brand" href="#">Arhiva</a>';
        echo '<div class="navbar-text text-white ms-auto">' . htmlspecialchars($_SESSION['full_name']) . '</div>';
        echo '</div></nav>';
    }
}

/**
 * Footer simplu layout interior
 */
function view_footer() {
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</body></html>';
}

/**
 * Generează field CSRF pentru formulare
 */
function csrf_field() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verifică token CSRF din POST
 */
function verify_csrf() {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verifyCSRFToken($token)) {
        throw new Exception('Token CSRF invalid. Vă rugăm să reîncărcați pagina și să încercați din nou.');
    }
}