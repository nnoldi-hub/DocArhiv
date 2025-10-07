<?php
/**
 * DEPRECATED: Use public/admin-settings.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';
header('Location: ' . APP_URL . '/admin-settings.php', true, 301);
exit;
