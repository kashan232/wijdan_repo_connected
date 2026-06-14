<?php
class ZKTestTCP {
    private $session_id = 0;
    private $reply_id = 0;
    
    public function connectUDP($ip, $port) {
        $socket = @fsockopen("udp://$ip", $port, $errno, $errstr, 2);
        if (!$socket) return false;
        stream_set_timeout($socket, 2);
        $header = $this->createHeader(1000, '', 0, 65535); // 1000 = CMD_CONNECT
        @fwrite($socket, $header);
        $resp = @fread($socket, 1024);
        echo "UDP Response Len: " . strlen($resp) . "\n";
        if (strlen($resp) >= 8) {
            echo "UDP Hex: " . bin2hex($resp) . "\n";
        }
        @fclose($socket);
    }
    
    private function createHeader($command, $command_string = '', $session_id = null, $reply_id = null) {
        $session_id = $session_id !== null ? $session_id : $this->session_id;
        $reply_id = $reply_id !== null ? $reply_id : $this->reply_id;
        $buf = pack('vvvv', $command, 0, $session_id, $reply_id) . $command_string;
        $chksum = $this->calculateChecksum($buf);
        $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
        return $buf;
    }
    
    private function calculateChecksum($buf) {
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
}
$test = new ZKTestTCP();
$test->connectUDP('192.0.0.121', 4370);
