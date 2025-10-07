<?php
/**
 * DEPRECATED: Use public/admin-departments.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';
header('Location: ' . APP_URL . '/admin-departments.php', true, 301);
exit;
