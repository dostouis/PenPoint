<?php
// api/ping.php
// Simple health check endpoint for ESP/Arduino

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

echo "OK";
?>