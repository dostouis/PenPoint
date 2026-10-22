<?php
date_default_timezone_set('Asia/Manila');
echo "Timezone: " . date_default_timezone_get() . "\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Formatted: " . date('M j, g:i A') . "\n";
?>