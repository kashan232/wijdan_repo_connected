<?php
require __DIR__.'/vendor/autoload.php';

$ip = '192.168.1.201';
$port = 4370;

echo "Testing Rats ZKTeco...\n";
$zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
if ($zk->connect()) {
    echo "Rats TCP Connected!\n";
    echo "Version: " . $zk->version() . "\n";
    $zk->disconnect();
} else {
    echo "Rats TCP Failed.\n";
}

echo "Testing Omithun ZKTeco...\n";
if (class_exists(\ZKLibrary::class)) {
    $zk2 = new \ZKLibrary($ip, $port);
    if ($zk2->connect()) {
        echo "Omithun Connected!\n";
        $zk2->disconnect();
    } else {
        echo "Omithun Failed.\n";
    }
} else {
    echo "Omithun class not found (might be ZKTecoLib or something).\n";
}
