<?php
$ip = '192.168.1.201';
$port = 4370;

echo "Testing TCP to $ip:$port...\n";
$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if (!$socket) {
    echo "TCP connect failed: $errstr ($errno)\n";
} else {
    echo "TCP connected.\n";
    // Try raw packet without size prefix
    $packet = pack('vvvv', 1000, 0, 0, 65535); // connect
    $checksum = 0;
    foreach (unpack('v*', $packet) as $val) {
        $checksum += $val;
    }
    while ($checksum > 0xFFFF) {
        $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
    }
    $checksum = ~$checksum & 0xFFFF;
    
    $packet = pack('vvvv', 1000, $checksum, 0, 65535);
    
    echo "Sending TCP raw header... ";
    fwrite($socket, $packet);
    $res = fread($socket, 1024);
    echo "Response length: " . strlen($res) . "\n";
    if (strlen($res) > 0) {
        echo "Response hex: " . bin2hex($res) . "\n";
    }
    
    fclose($socket);
}

echo "Testing UDP to $ip:$port...\n";
$socket = @fsockopen("udp://$ip", $port, $errno, $errstr, 2);
if (!$socket) {
    echo "UDP connect failed: $errstr ($errno)\n";
} else {
    echo "UDP connected.\n";
    
    $packet = pack('vvvv', 1000, 0, 0, 65535); // connect
    $checksum = 0;
    foreach (unpack('v*', $packet) as $val) {
        $checksum += $val;
    }
    while ($checksum > 0xFFFF) {
        $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
    }
    $checksum = ~$checksum & 0xFFFF;
    
    $packet = pack('vvvv', 1000, $checksum, 0, 65535);
    
    echo "Sending UDP raw header... ";
    fwrite($socket, $packet);
    stream_set_timeout($socket, 2);
    $res = fread($socket, 1024);
    echo "Response length: " . strlen($res) . "\n";
    if (strlen($res) > 0) {
        echo "Response hex: " . bin2hex($res) . "\n";
    }
    fclose($socket);
}
