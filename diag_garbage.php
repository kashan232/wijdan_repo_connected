<?php
$ip = '192.0.0.121';
$port = 4370;

$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    @fwrite($socket, "HELLO WORLD ZKTECO TEST");
    $resp = @fread($socket, 1024);
    echo "TCP GARBAGE RESPONSE LEN: " . strlen($resp) . "\n";
    if (strlen($resp) > 0) {
        echo "TCP GARBAGE RESPONSE: " . bin2hex($resp) . "\n";
    }
    @fclose($socket);
}
