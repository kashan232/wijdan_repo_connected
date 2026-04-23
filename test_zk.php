<?php

$ip = '192.168.1.201';
$port = 4370;

echo "Testing connection to $ip:$port...\n";

$fp = @fsockopen($ip, $port, $errno, $errstr, 5);

if (!$fp) {
    echo "Connection failed: $errstr ($errno)\n";
} else {
    echo "Connection successful!\n";
    fclose($fp);
}
