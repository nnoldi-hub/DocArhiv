<?php
// Legacy path redirect
require_once __DIR__ . '/../../../config/config.php';
if (!isLoggedIn()) { redirect(APP_URL . '/login.php'); exit; }
header('Location: ' . APP_URL . '/admin-dashboard.php', true, 301);
exit;