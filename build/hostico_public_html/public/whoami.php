<?php
header('Content-Type: text/plain; charset=UTF-8');
echo "WHOAMI OK\n";
echo "FILE: " . __FILE__ . "\n";
echo "DOCROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "TIME: " . date('c') . "\n";
?>