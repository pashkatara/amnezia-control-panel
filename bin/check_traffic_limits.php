#!/usr/bin/env php
<?php
/**
 * Check and disable clients that exceeded their traffic limit
 * Run this script via cron every hour
 */

require_once __DIR__ . '/../inc/Config.php';
require_once __DIR__ . '/../inc/DB.php';
require_once __DIR__ . '/../inc/VpnClient.php';
require_once __DIR__ . '/../inc/VpnServer.php';

// Load config
Config::load(__DIR__ . '/../.env');

echo '[' . date('Y-m-d H:i:s') . '] Checking for clients over traffic limit...' . PHP_EOL;

try {
    $disabled = VpnClient::disableClientsOverLimit();
    
    if ($disabled > 0) {
        echo '[' . date('Y-m-d H:i:s') . '] Disabled ' . $disabled . ' client(s) that exceeded traffic limit' . PHP_EOL;
    } else {
        echo '[' . date('Y-m-d H:i:s') . '] No clients over traffic limit' . PHP_EOL;
    }
} catch (Exception $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
