<?php
class ZKTestTCP2 {
    private $session_id = 0;
    private $reply_id = 0;
    
    public function connectTCP($ip, $port) {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 2);
        if (!$socket) return false;
        stream_set_timeout($socket, 2);
        
        $command = 1000; // CMD_CONNECT
        $command_string = '';
        
        // 1. Create UDP-style buffer
        $buf = pack('vvvv', $command, 0, $this->session_id, $this->reply_id) . $command_string;
        
        // 2. Calculate Checksum
        $chksum = $this->calculateChecksum($buf);
        
        // 3. Rebuild buffer with checksum
        $buf = pack('vvvv', $command, $chksum, $this->session_id, $this->reply_id) . $command_string;
        
        // 4. Wrap for TCP: 50 50 82 7D + Length (4 bytes, little endian)
        $tcp_prefix = pack('H*', '5050827d') . pack('V', strlen($buf));
        
        $packet = $tcp_prefix . $buf;
        
        echo "Sending TCP Packet: " . bin2hex($packet) . "\n";
        @fwrite($socket, $packet);
        
        $resp = @fread($socket, 1024);
        echo "TCP Response Len: " . strlen($resp) . "\n";
        if (strlen($resp) > 0) {
            echo "TCP Hex: " . bin2hex($resp) . "\n";
        }
        @fclose($socket);
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
$test = new ZKTestTCP2();
$test->connectTCP('192.0.0.121', 4370);
