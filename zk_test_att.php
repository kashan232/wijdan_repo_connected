<?php
require __DIR__.'/vendor/autoload.php';

$ip = '192.168.1.201';
$port = 4370;

$zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
if ($zk->connect()) {
    echo "Rats TCP Connected!\n";
    $attendance = $zk->getAttendance();
    echo "Attendance records: " . count($attendance) . "\n";
    if (count($attendance) > 0) {
        echo "First record: " . json_encode($attendance[0]) . "\n";
    }
    $zk->disconnect();
} else {
    echo "Rats TCP Failed.\n";
}
