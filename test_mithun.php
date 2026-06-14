<?php
require 'vendor/autoload.php';

use Mithun\PhpZkteco\Libs\ZKTeco;

// Try UDP
echo "Testing Mithun/Zkteco (TCP)...\n";
$zk = new ZKTeco('192.0.0.64', 4370);
if ($zk->connect()) {
    echo "Connected via Mithun/Zkteco!\n";
    echo "Device name: " . $zk->deviceName() . "\n";
    $zk->disconnect();
} else {
    echo "Failed via Mithun/Zkteco!\n";
}
