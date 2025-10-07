<?php
echo "Hello World - PHP works!";
echo "<br>Date: " . date('Y-m-d H:i:s');
echo "<br>Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown');
?>