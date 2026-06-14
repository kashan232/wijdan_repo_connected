<?php
$ip = '192.0.0.64';
$port = 4370;

$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    echo "Connected. Waiting for initial banner/response...\n";
    $resp = @fread($socket, 1024);
    echo "Initial response len: " . strlen($resp) . "\n";
    if (strlen($resp) > 0) {
        echo "Hex: " . bin2hex($resp) . "\n";
        echo "ASCII: " . $resp . "\n";
    }
    @fclose($socket);
}
