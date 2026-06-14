<?php
require 'vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

$zk = new ZKTeco('192.0.0.121', 4370);
if ($zk->connect()) {
    echo "Connected successfully via rats/zkteco (UDP)!\n";
    echo "Device name: " . $zk->deviceName() . "\n";
    $zk->disconnect();
} else {
    echo "Failed to connect via rats/zkteco (UDP)!\n";
}
