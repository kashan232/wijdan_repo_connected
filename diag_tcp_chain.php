<?php
$ip = '192.0.0.121';
$port = 4370;

$socket = @fsockopen($ip, $port, $errno, $errstr, 2);
if ($socket) {
    stream_set_timeout($socket, 2);
    
    // Connect Command
    $command = 1000;
    $command_string = '';
    $session_id = 0;
    $reply_id = 0;
    
    $buf = pack('vvvv', $command, 0, $session_id, $reply_id) . $command_string;
    $chksum = calculateChecksum($buf);
    $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
    $packet = pack('H*', '5050827d') . pack('V', strlen($buf)) . $buf;
    
    @fwrite($socket, $packet);
    $resp = @fread($socket, 1024);
    echo "TCP Connect Resp: " . bin2hex($resp) . "\n";
    
    // Get Time Command (201)
    $command = 201; // CMD_GET_TIME
    $buf = pack('vvvv', $command, 0, $session_id, $reply_id) . $command_string;
    $chksum = calculateChecksum($buf);
    $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
    $packet = pack('H*', '5050827d') . pack('V', strlen($buf)) . $buf;
    
    @fwrite($socket, $packet);
    $resp = @fread($socket, 1024);
    echo "TCP Time Resp: " . bin2hex($resp) . "\n";
    
    @fclose($socket);
}

function calculateChecksum($buf) {
    $size = strlen($buf);
    $chksum = 0;
    if ($size % 2 == 1) { $buf .= chr(0); $size++; }
    $u = unpack('v*', $buf);
    foreach ($u as $v) {
        $chksum += $v;
        while ($chksum > 65535) $chksum -= 65536;
    }
    $chksum = ~$chksum;
    while ($chksum < 0) $chksum += 65536;
    return $chksum;
}
