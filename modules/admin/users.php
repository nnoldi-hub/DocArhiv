<?php
/**
 * DEPRECATED: Use public/admin-users.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';
header('Location: ' . APP_URL . '/admin-users.php', true, 301);
exit;
