<?php
/**
 * Funcții de Validare
 * includes/functions/validation.php
 */

/**
 * Validează adresa de email
 */
function validateEmail($email) {
    $email = trim($email);
    
    if (empty($email)) {
        return ['valid' => false, 'message' => 'Adresa de email este obligatorie'];
    }
    
    if (strlen($email) > 255) {
        return ['valid' => false, 'message' => 'Adresa de email este prea lungă'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Adresa de email nu este validă'];
    }
    
    // Verifică pentru domenii suspicioase
    $suspiciousDomains = ['tempmail.org', '10minutemail.com', 'guerrillamail.com'];
    $domain = substr(strrchr($email, '@'), 1);
    
    if (in_array(strtolower($domain), $suspiciousDomains)) {
        return ['valid' => false, 'message' => 'Vă rugăm să folosiți o adresă de email permanentă'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează parola
 */
function validatePassword($password) {
    $errors = [];
    
    if (empty($password)) {
        $errors[] = 'Parola este obligatorie';
    } else {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Parola trebuie să aibă cel puțin ' . PASSWORD_MIN_LENGTH . ' caractere';
        }
        
        if (strlen($password) > 255) { // Folosim 255 ca maxim
            $errors[] = 'Parola este prea lungă (maxim 255 caractere)';
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Parola trebuie să conțină cel puțin o literă mare';
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Parola trebuie să conțină cel puțin o literă mică';
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Parola trebuie să conțină cel puțin o cifră';
        }
        
        if (PASSWORD_REQUIRE_SYMBOLS && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Parola trebuie să conțină cel puțin un caracter special';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Verifică puterea parolei (scor 0-100)
 */
function getPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    // Lungime
    $length = strlen($password);
    if ($length >= 8) $score += 20;
    if ($length >= 12) $score += 10;
    if ($length >= 16) $score += 10;
    
    // Complexitate
    if (preg_match('/[a-z]/', $password)) $score += 10;
    if (preg_match('/[A-Z]/', $password)) $score += 10;
    if (preg_match('/[0-9]/', $password)) $score += 10;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 15;
    
    // Varietate de caractere
    $uniqueChars = strlen(count_chars($password, 3));
    if ($uniqueChars >= 5) $score += 10;
    if ($uniqueChars >= 8) $score += 5;
    
    // Feedback
    if ($length < 8) $feedback[] = 'Folosiți cel puțin 8 caractere';
    if (!preg_match('/[a-z]/', $password)) $feedback[] = 'Adăugați litere mici';
    if (!preg_match('/[A-Z]/', $password)) $feedback[] = 'Adăugați litere mari';
    if (!preg_match('/[0-9]/', $password)) $feedback[] = 'Adăugați cifre';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $feedback[] = 'Adăugați caractere speciale';
    
    return [
        'score' => min(100, $score),
        'feedback' => $feedback
    ];
}

/**
 * Validează numele companiei
 */
function validateCompanyName($name) {
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'message' => 'Numele companiei este obligatoriu'];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Numele companiei trebuie să aibă cel puțin 2 caractere'];
    }
    
    if (strlen($name) > 100) {
        return ['valid' => false, 'message' => 'Numele companiei este prea lung (maxim 100 caractere)'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9\s\-_.,&()]+$/u', $name)) {
        return ['valid' => false, 'message' => 'Numele companiei conține caractere nevalide'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează numele unei persoane
 */
function validatePersonName($name, $fieldName = 'Numele') {
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'message' => $fieldName . ' este obligatoriu'];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => $fieldName . ' trebuie să aibă cel puțin 2 caractere'];
    }
    
    if (strlen($name) > 50) {
        return ['valid' => false, 'message' => $fieldName . ' este prea lung (maxim 50 caractere)'];
    }
    
    if (!preg_match('/^[a-zA-ZăâîșțĂÂÎȘȚ\s\-\']+$/u', $name)) {
        return ['valid' => false, 'message' => $fieldName . ' conține caractere nevalide'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează numărul de telefon
 */
function validatePhone($phone) {
    $phone = trim($phone);
    
    if (empty($phone)) {
        return ['valid' => true, 'message' => '']; // Opțional
    }
    
    // Curăță numărul
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strlen($cleanPhone) < 10) {
        return ['valid' => false, 'message' => 'Numărul de telefon este prea scurt'];
    }
    
    if (strlen($cleanPhone) > 15) {
        return ['valid' => false, 'message' => 'Numărul de telefon este prea lung'];
    }
    
    // Verifică format românesc sau internațional
    if (!preg_match('/^(\+40|0040|0)[0-9]{9}$/', $cleanPhone) && 
        !preg_match('/^\+[1-9][0-9]{1,14}$/', $cleanPhone)) {
        return ['valid' => false, 'message' => 'Formatul numărului de telefon nu este valid'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează URL-ul
 */
function validateURL($url) {
    $url = trim($url);
    
    if (empty($url)) {
        return ['valid' => true, 'message' => '']; // Opțional
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'message' => 'URL-ul nu este valid'];
    }
    
    $parsedUrl = parse_url($url);
    if (!in_array($parsedUrl['scheme'], ['http', 'https'])) {
        return ['valid' => false, 'message' => 'Doar URL-uri HTTP și HTTPS sunt permise'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează data
 */
function validateDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return ['valid' => false, 'message' => 'Data este obligatorie'];
    }
    
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        return ['valid' => false, 'message' => 'Formatul datei nu este valid'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează perioada de timp
 */
function validateDateRange($startDate, $endDate, $format = 'Y-m-d') {
    $errors = [];
    
    $startValidation = validateDate($startDate, $format);
    if (!$startValidation['valid']) {
        $errors[] = 'Data început: ' . $startValidation['message'];
    }
    
    $endValidation = validateDate($endDate, $format);
    if (!$endValidation['valid']) {
        $errors[] = 'Data sfârșit: ' . $endValidation['message'];
    }
    
    if (empty($errors)) {
        $start = DateTime::createFromFormat($format, $startDate);
        $end = DateTime::createFromFormat($format, $endDate);
        
        if ($start > $end) {
            $errors[] = 'Data început nu poate fi după data sfârșit';
        }
        
        // Verifică că nu este prea în trecut (peste 10 ani)
        $tenYearsAgo = new DateTime('-10 years');
        if ($start < $tenYearsAgo) {
            $errors[] = 'Data început este prea în trecut';
        }
        
        // Verifică că nu este prea în viitor (peste 5 ani)
        $fiveYearsLater = new DateTime('+5 years');
        if ($end > $fiveYearsLater) {
            $errors[] = 'Data sfârșit este prea în viitor';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validează codul poștal
 */
function validatePostalCode($postalCode, $country = 'RO') {
    $postalCode = trim($postalCode);
    
    if (empty($postalCode)) {
        return ['valid' => true, 'message' => '']; // Opțional
    }
    
    switch (strtoupper($country)) {
        case 'RO':
            if (!preg_match('/^[0-9]{6}$/', $postalCode)) {
                return ['valid' => false, 'message' => 'Codul poștal trebuie să aibă 6 cifre'];
            }
            break;
            
        default:
            if (strlen($postalCode) < 3 || strlen($postalCode) > 10) {
                return ['valid' => false, 'message' => 'Codul poștal nu este valid'];
            }
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează CUI/CIF românesc
 */
function validateCUI($cui) {
    $cui = trim($cui);
    
    if (empty($cui)) {
        return ['valid' => true, 'message' => '']; // Opțional
    }
    
    // Curăță CUI-ul
    $cui = preg_replace('/[^0-9]/', '', $cui);
    
    if (strlen($cui) < 2 || strlen($cui) > 10) {
        return ['valid' => false, 'message' => 'CUI-ul trebuie să aibă între 2 și 10 cifre'];
    }
    
    // Algoritm de validare CUI
    $controlDigit = substr($cui, -1);
    $cuiNumber = substr($cui, 0, -1);
    
    $weights = [7, 3, 1, 7, 3, 1, 7, 3, 1];
    $sum = 0;
    
    for ($i = 0; $i < strlen($cuiNumber); $i++) {
        $sum += intval($cuiNumber[$i]) * $weights[$i];
    }
    
    $calculatedDigit = $sum % 11;
    if ($calculatedDigit == 10) {
        $calculatedDigit = 0;
    }
    
    if ($calculatedDigit != intval($controlDigit)) {
        return ['valid' => false, 'message' => 'CUI-ul nu este valid'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează titlul documentului
 */
function validateDocumentTitle($title) {
    $title = trim($title);
    
    if (empty($title)) {
        return ['valid' => false, 'message' => 'Titlul documentului este obligatoriu'];
    }
    
    if (strlen($title) < 3) {
        return ['valid' => false, 'message' => 'Titlul documentului trebuie să aibă cel puțin 3 caractere'];
    }
    
    if (strlen($title) > 255) {
        return ['valid' => false, 'message' => 'Titlul documentului este prea lung (maxim 255 caractere)'];
    }
    
    // Verifică pentru caractere nevalide
    if (preg_match('/[<>:"|*?\\\\\/]/', $title)) {
        return ['valid' => false, 'message' => 'Titlul conține caractere nevalide'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează descrierea
 */
function validateDescription($description, $required = false, $maxLength = 1000) {
    $description = trim($description);
    
    if ($required && empty($description)) {
        return ['valid' => false, 'message' => 'Descrierea este obligatorie'];
    }
    
    if (strlen($description) > $maxLength) {
        return ['valid' => false, 'message' => "Descrierea este prea lungă (maxim {$maxLength} caractere)"];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează tags-urile
 */
function validateTags($tags) {
    if (empty($tags)) {
        return ['valid' => true, 'message' => '', 'cleaned_tags' => []];
    }
    
    if (is_string($tags)) {
        $tags = explode(',', $tags);
    }
    
    $cleanedTags = [];
    $errors = [];
    
    foreach ($tags as $tag) {
        $tag = trim($tag);
        
        if (empty($tag)) {
            continue;
        }
        
        if (strlen($tag) < 2) {
            $errors[] = "Tag-ul '{$tag}' este prea scurt";
            continue;
        }
        
        if (strlen($tag) > 50) {
            $errors[] = "Tag-ul '{$tag}' este prea lung";
            continue;
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/u', $tag)) {
            $errors[] = "Tag-ul '{$tag}' conține caractere nevalide";
            continue;
        }
        
        $cleanedTags[] = $tag;
    }
    
    if (count($cleanedTags) > 10) { // Maxim 10 tag-uri per document
        $errors[] = 'Prea multe tag-uri (maxim 10)';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'cleaned_tags' => array_unique($cleanedTags)
    ];
}

/**
 * Validează ID-ul numeric
 */
function validateNumericId($id, $fieldName = 'ID') {
    if (empty($id)) {
        return ['valid' => false, 'message' => $fieldName . ' este obligatoriu'];
    }
    
    if (!is_numeric($id) || intval($id) != $id || intval($id) <= 0) {
        return ['valid' => false, 'message' => $fieldName . ' trebuie să fie un număr întreg pozitiv'];
    }
    
    if (intval($id) > 2147483647) {
        return ['valid' => false, 'message' => $fieldName . ' este prea mare'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validează sortarea
 */
function validateSortParameters($sort, $order, $allowedFields = []) {
    $errors = [];
    
    if (!empty($sort) && !empty($allowedFields) && !in_array($sort, $allowedFields)) {
        $errors[] = 'Câmpul de sortare nu este permis';
    }
    
    if (!empty($order) && !in_array(strtoupper($order), ['ASC', 'DESC'])) {
        $errors[] = 'Ordinea de sortare trebuie să fie ASC sau DESC';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validează paginarea
 */
function validatePagination($page, $limit) {
    $errors = [];
    
    if (!empty($page)) {
        if (!is_numeric($page) || intval($page) < 1) {
            $errors[] = 'Numărul paginii trebuie să fie un număr întreg pozitiv';
        }
    }
    
    if (!empty($limit)) {
        if (!is_numeric($limit) || intval($limit) < 1 || intval($limit) > 100) {
            $errors[] = 'Limita trebuie să fie între 1 și 100';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validează un formular întreg
 */
function validateForm($data, $rules) {
    $errors = [];
    $validData = [];
    
    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? '';
        $fieldErrors = [];
        
        // Required
        if (isset($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
            $fieldErrors[] = 'Acest câmp este obligatoriu';
        }
        
        // Doar dacă valoarea nu este goală sau câmpul este obligatoriu
        if (!empty($value) || (isset($fieldRules['required']) && $fieldRules['required'])) {
            
            // Email
            if (isset($fieldRules['email']) && $fieldRules['email']) {
                $validation = validateEmail($value);
                if (!$validation['valid']) {
                    $fieldErrors[] = $validation['message'];
                }
            }
            
            // Password
            if (isset($fieldRules['password']) && $fieldRules['password']) {
                $validation = validatePassword($value);
                if (!$validation['valid']) {
                    $fieldErrors = array_merge($fieldErrors, $validation['errors']);
                }
            }
            
            // Min length
            if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                $fieldErrors[] = 'Trebuie să aibă cel puțin ' . $fieldRules['min_length'] . ' caractere';
            }
            
            // Max length
            if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                $fieldErrors[] = 'Nu poate avea mai mult de ' . $fieldRules['max_length'] . ' caractere';
            }
            
            // Numeric
            if (isset($fieldRules['numeric']) && $fieldRules['numeric'] && !is_numeric($value)) {
                $fieldErrors[] = 'Trebuie să fie un număr';
            }
            
            // URL
            if (isset($fieldRules['url']) && $fieldRules['url']) {
                $validation = validateURL($value);
                if (!$validation['valid']) {
                    $fieldErrors[] = $validation['message'];
                }
            }
            
            // Date
            if (isset($fieldRules['date']) && $fieldRules['date']) {
                $validation = validateDate($value);
                if (!$validation['valid']) {
                    $fieldErrors[] = $validation['message'];
                }
            }
            
            // Custom pattern
            if (isset($fieldRules['pattern']) && !preg_match($fieldRules['pattern'], $value)) {
                $message = $fieldRules['pattern_message'] ?? 'Formatul nu este valid';
                $fieldErrors[] = $message;
            }
            
            // Custom function
            if (isset($fieldRules['custom']) && is_callable($fieldRules['custom'])) {
                $validation = call_user_func($fieldRules['custom'], $value);
                if (!$validation['valid']) {
                    $fieldErrors[] = $validation['message'];
                }
            }
        }
        
        if (!empty($fieldErrors)) {
            $errors[$field] = $fieldErrors;
        } else {
            $validData[$field] = $value;
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $validData
    ];
}

/**
 * Sanitizează și validează upload de fișier
 */
function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_EXTENSIONS;
    }
    
    if ($maxSize === null) {
        $maxSize = MAX_FILE_SIZE;
    }
    
    $errors = [];
    
    // Verifică dacă fișierul a fost încărcat
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['valid' => false, 'errors' => ['Nu a fost încărcat niciun fișier']];
    }
    
    // Verifică pentru erori de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'Fișierul este prea mare';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'Fișierul a fost încărcat doar parțial';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'Nu a fost selectat niciun fișier';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors[] = 'Eroare server: directorul temporar lipsește';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors[] = 'Eroare server: nu se poate scrie fișierul';
                break;
            default:
                $errors[] = 'Eroare necunoscută la încărcarea fișierului';
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Folosește funcțiile de securitate pentru validări suplimentare
    require_once __DIR__ . '/security.php';
    $securityErrors = validateUploadedFile($file);
    if (!empty($securityErrors)) {
        $errors = array_merge($errors, $securityErrors);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}