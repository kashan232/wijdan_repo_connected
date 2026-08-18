<?php
require __DIR__.'/vendor/autoload.php';

class MyZKTeco extends \Rats\Zkteco\Lib\ZKTeco {
    public function command($command, $command_string) {
        return $this->_command($command, $command_string);
    }
}

$ip = '192.168.1.201';
$port = 4370;

$zk = new MyZKTeco($ip, $port);
if ($zk->connect()) {
    echo "Rats TCP Connected!\n";
    $uid = 1;
    $userid = "123";
    $name = "Test";
    $password = '';
    $role = 0;
    $cardno = 0;
    
    $byte1 = chr((int)($uid % 256));
    $byte2 = chr((int)($uid >> 8));
    $cardnoHex = str_pad(dechex($cardno), 8, '0', STR_PAD_LEFT);
    $cardnoBin = hex2bin(implode('', array_reverse(str_split($cardnoHex, 2))));
    
    $command_string = implode('', [
        $byte1,
        $byte2,
        chr($role),
        str_pad($password, 8, chr(0)),
        str_pad($name, 24, chr(0)),
        str_pad($cardnoBin, 4, chr(0)),
        str_pad(chr(1), 9, chr(0)),
        str_pad($userid, 9, chr(0)),
        str_repeat(chr(0), 15)
    ]);
    
    echo "Sending payload of length: " . strlen($command_string) . "\n";
    $res = $zk->command(8, $command_string); // 8 is CMD_SET_USER
    var_dump($res);
    
    $zk->disconnect();
} else {
    echo "Failed to connect.\n";
}
