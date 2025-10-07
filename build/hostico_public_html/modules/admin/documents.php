<?php
/**
 * DEPRECATED: Use public/admin-documents.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';
header('Location: ' . APP_URL . '/admin-documents.php', true, 301);
exit;
