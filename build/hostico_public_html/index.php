<?php
// Bootstrap index to ensure hosting pointing to repo root works
// Redirect cleanly to public/index.php preserving query string
$target = '/public/index.php';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $target, true, 302);
exit;
