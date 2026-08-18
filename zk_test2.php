<?php
$ip = '192.168.1.201';
$port = 4370;

echo "Testing TCP with size prefix to $ip:$port...\n";
$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    echo "TCP connected.\n";
    $packet = pack('vvvv', 1000, 0, 0, 65535); // connect
    $checksum = 0;
    foreach (unpack('v*', $packet) as $val) {
        $checksum += $val;
    }
    while ($checksum > 0xFFFF) {
        $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
    }
    $checksum = ~$checksum & 0xFFFF;
    
    $header = pack('vvvv', 1000, $checksum, 0, 65535);
    $tcp_header = pack('V', strlen($header)) . $header;
    
    echo "Sending TCP header with size... ";
    fwrite($socket, $tcp_header);
    $res = fread($socket, 1024);
    echo "Response length: " . strlen($res) . "\n";
    if (strlen($res) > 0) {
        echo "Response hex: " . bin2hex($res) . "\n";
    }
    fclose($socket);
}
