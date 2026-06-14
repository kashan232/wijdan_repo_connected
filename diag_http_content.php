<?php
$ip = '192.0.0.121';
$url = "http://$ip:80/";
$client = curl_init($url);
curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
curl_setopt($client, CURLOPT_TIMEOUT, 5);
$resp = curl_exec($client);
if ($resp) {
    echo "Port 80 response (first 1000 chars):\n";
    echo substr($resp, 0, 1000) . "\n";
} else {
    echo "Port 80 error: " . curl_error($client) . "\n";
}
curl_close($client);
