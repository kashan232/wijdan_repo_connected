<?php
$ip = '192.0.0.64';

$ports = [80, 8080, 4370, 8000, 37777];
foreach ($ports as $port) {
    echo "Testing $port...\n";
    $client = curl_init("http://$ip:$port/");
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_TIMEOUT, 2);
    curl_setopt($client, CURLOPT_HEADER, true); // Get headers
    $resp = curl_exec($client);
    if ($resp) {
        echo "Port $port HTTP Response:\n";
        echo substr($resp, 0, 500) . "\n\n";
    }
    curl_close($client);
}
