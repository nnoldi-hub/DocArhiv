<?php
// Redirect simplu către public/index.php
if (!headers_sent()) {
    header('Location: /public/index.php');
    exit;
}
?>