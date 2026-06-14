<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BiometricDevice;
use Illuminate\Support\Facades\Crypt;
use Mithun\PhpZkteco\Zkteco; // Assuming this is the class? Or Mithun\PhpZkteco\Libs\ZKTeco

$device = BiometricDevice::where('ip_address', '192.0.0.64')->first();
if (!$device) {
    die("Device not found\n");
}

$password = '';
if (!empty($device->password)) {
    try {
        $password = Crypt::decryptString($device->password);
    } catch (\Exception $e) {
        $password = $device->password;
    }
}
echo "Found password: " . ($password ? 'YES' : 'NO') . "\n";

echo "Trying to connect using Mithun (UDP) with password...\n";
// Let's use 0mithun's lib properly
$zk = new \Mithun\PhpZkteco\Zkteco('192.0.0.64', 4370, $password);
if ($zk->connect()) {
    echo "CONNECTED!\n";
    echo "Name: " . $zk->deviceName() . "\n";
    $zk->disconnect();
} else {
    echo "FAILED.\n";
}
