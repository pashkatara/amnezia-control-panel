#!/usr/bin/env php
<?php
/**
 * Check and disable expired VPN clients
 * Run this script via cron to automatically disable clients that have expired
 * 
 * Example cron: 0 * * * * /usr/bin/php /var/www/html/bin/check_expired_clients.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../inc/Config.php';
require __DIR__ . '/../inc/DB.php';
require __DIR__ . '/../inc/VpnClient.php';
require __DIR__ . '/../inc/VpnServer.php';

// Load environment
Config::load(__DIR__ . '/../.env');

try {
    echo "[" . date('Y-m-d H:i:s') . "] Checking for expired clients...\n";
    
    // Disable all expired clients
    $count = VpnClient::disableExpiredClients();
    
    if ($count > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Disabled {$count} expired client(s)\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] No expired clients found\n";
    }
    
    // Report expiring clients (within 7 days)
    $expiring = VpnClient::getExpiringClients(7);
    
    if (count($expiring) > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] " . count($expiring) . " client(s) expiring soon:\n";
        foreach ($expiring as $client) {
            $daysLeft = (int)floor((strtotime($client['expires_at']) - time()) / 86400);
            echo "  - {$client['name']} ({$client['email']}) expires in {$daysLeft} day(s)\n";
        }
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
