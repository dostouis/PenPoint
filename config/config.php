<?php
// ============================================
// config/config.php
// PenPoint - Smart Vending for Everyday School Essentials
// ============================================

// Set PHP timezone
date_default_timezone_set('Asia/Manila');

// App settings
define('APP_NAME', 'PenPoint');
define('APP_TAGLINE', 'An ESP32-Based Smart Vending Machine with QR Ph Payment & Sensor-Verified Dispensing');
define('APP_VERSION', '2.0.0');
define('GROUP_NAME', 'Group SkiDB (IV-CINS)');
define('INSTITUTION_NAME', 'University of Makati — College of Computing and Information Sciences');

// Currency & Machine configuration
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');
define('DEFAULT_MACHINE_ID', 'PEN-01');

// Team Members (Official Order from Chapter 1)
define('PROJECT_MEMBERS', [
    'Polinio, Jiyan Carl C. (Leader)',
    'Del Villar, Tracy Carmelo M.',
    'Llanes, Kurt Luis E.',
    'Palma, John Matthew S.'
]);

// Timezone constant for reference
define('APP_TIMEZONE', 'Asia/Manila');
define('APP_TIMEZONE_OFFSET', '+08:00');