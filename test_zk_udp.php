<?php

$ip = '192.168.1.201';
$port = 4370;

echo "Testing UDP connection to $ip:$port...\n";
$socket = @fsockopen("udp://$ip", $port, $errno, $errstr, 5);

if (!$socket) die("UDP Failed\n");

function calculateChecksum($buf) {
    $u = unpack('v*', $buf);
    $chksum = 0;
    foreach ($u as $v) { $chksum += $v; while ($chksum > 65535) $chksum -= 65536; }
    $chksum = ~$chksum; while ($chksum < 0) $chksum += 65536;
    return $chksum;
}

$command = 1000; $session_id = 0; $reply_id = 65535;

$buf = pack('vvvv', $command, 0, $session_id, $reply_id);
$chksum = calculateChecksum($buf);
$buf = pack('vvvv', $command, $chksum, $session_id, $reply_id);

echo "Sending UDP: " . bin2hex($buf) . "\n";
fwrite($socket, $buf);
stream_set_timeout($socket, 3);
$response = fread($socket, 1024);

echo "Received UDP (" . strlen($response) . " bytes): " . bin2hex($response) . "\n";

fclose($socket);
