<?php
/**
 * DEPRECATED: Use public/admin-tags.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';
header('Location: ' . APP_URL . '/admin-tags.php', true, 301);
exit;
