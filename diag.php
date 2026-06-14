<?php
$ip = '192.0.0.64';
$port = 4370;

// Test UDP
$socket = @fsockopen("udp://$ip", $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    $command = pack('vvvv', 1000, 0, 0, 0); // basic connect
    @fwrite($socket, $command);
    $resp = @fread($socket, 1024);
    echo "UDP RAW RESPONSE LEN: " . strlen($resp) . "\n";
    if (strlen($resp) > 0) {
        echo "UDP RESPONSE: " . bin2hex($resp) . "\n";
    }
    @fclose($socket);
}

// Test TCP 1: pack('V')
$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    $command = pack('vvvv', 1000, 0, 0, 0);
    $packet = pack('V', strlen($command)) . $command;
    @fwrite($socket, $packet);
    $resp = @fread($socket, 1024);
    echo "TCP PREFIX(V) LEN: " . strlen($resp) . "\n";
    if (strlen($resp) > 0) {
        echo "TCP PREFIX(V) RESPONSE: " . bin2hex($resp) . "\n";
    }
    @fclose($socket);
}

// Test TCP 2: 5050827d
$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    $command = pack('vvvv', 1000, 0, 0, 0);
    $packet = pack('H*', '5050827d') . pack('V', strlen($command)) . $command;
    @fwrite($socket, $packet);
    $resp = @fread($socket, 1024);
    echo "TCP PREFIX(5050...) LEN: " . strlen($resp) . "\n";
    if (strlen($resp) > 0) {
        echo "TCP PREFIX(5050...) RESPONSE: " . bin2hex($resp) . "\n";
    }
    @fclose($socket);
}

// Test HTTP (ISAPI or ADMS)
$client = curl_init("http://$ip:$port/");
curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
curl_setopt($client, CURLOPT_TIMEOUT, 2);
$resp = curl_exec($client);
echo "HTTP RESPONSE LEN: " . strlen((string)$resp) . "\n";
if (strlen((string)$resp) > 0) {
    echo "HTTP RESPONSE: " . substr($resp, 0, 200) . "\n";
}
curl_close($client);
