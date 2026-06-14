<?php
require 'vendor/autoload.php';

use Mithun\PhpZkteco\Libs\ZKTeco;

echo "Testing Mithun/Zkteco (TCP)...\n";
// The signature is: __construct(string $host = '', int $port = 4370, bool $shouldPing = false, int $timeout = 25, $password = 0, string $protocol = 'udp', array $tcpmux = [])
$zk = new ZKTeco('192.0.0.121', 4370, false, 5, 0, 'tcp');
if ($zk->connect()) {
    echo "Connected via Mithun/Zkteco (TCP)!\n";
    echo "Device name: " . $zk->deviceName() . "\n";
    $zk->disconnect();
} else {
    echo "Failed via Mithun/Zkteco (TCP)!\n";
}

echo "Testing Mithun/Zkteco (UDP)...\n";
$zk_udp = new ZKTeco('192.0.0.121', 4370, false, 5, 0, 'udp');
if ($zk_udp->connect()) {
    echo "Connected via Mithun/Zkteco (UDP)!\n";
    echo "Device name: " . $zk_udp->deviceName() . "\n";
    $zk_udp->disconnect();
} else {
    echo "Failed via Mithun/Zkteco (UDP)!\n";
}
