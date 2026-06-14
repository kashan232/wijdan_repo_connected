<?php
// A simple, standalone test script to verify Biometric Connection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '192.0.0.121';
    $port = $_POST['port'] ?? 80;
    $protocol = $_POST['protocol'] ?? 'hikvision';
    $username = $_POST['username'] ?? 'admin';
    $password = $_POST['password'] ?? 'admin123';

    $result = "";

    if ($protocol === 'hikvision') {
        $url = "http://$ip:$port/ISAPI/System/deviceInfo";
        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_TIMEOUT, 5);
        curl_setopt($client, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
        curl_setopt($client, CURLOPT_USERPWD, "$username:$password");
        
        $resp = curl_exec($client);
        $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
        
        if ($status == 200) {
            $result = "<div style='color:green'><h3>✅ SUCCESS! Device Connected (Hikvision Protocol)</h3>";
            $result .= "<p>Device successfully responded. This confirms the software CAN connect if the correct settings are used.</p>";
            $result .= "<pre>" . htmlspecialchars($resp) . "</pre></div>";
        } elseif ($status == 401) {
            $result = "<div style='color:red'><h3>❌ Auth Failed (Status 401)</h3><p>Username or Password is incorrect.</p></div>";
        } else {
            $result = "<div style='color:red'><h3>❌ Failed (Status $status)</h3><p>" . curl_error($client) . "</p></div>";
        }
        curl_close($client);

    } elseif ($protocol === 'zkteco') {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 2);
        if ($socket) {
            stream_set_timeout($socket, 2);
            $command = 1000;
            $session_id = 0;
            $reply_id = 0;
            
            // Rebuild buffer with checksum
            $buf = pack('vvvv', $command, 0, $session_id, $reply_id);
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
            
            $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id);
            $packet = pack('H*', '5050827d') . pack('V', strlen($buf)) . $buf;
            
            @fwrite($socket, $packet);
            $resp = @fread($socket, 1024);
            
            if (strlen($resp) == 16 && bin2hex($resp) === "00000010000000200000002000000000") {
                $result = "<div style='color:red'><h3>❌ Connection Refused by Device</h3><p>Device received the ZKTeco packet, but responded with error code `16 32 32 0`. This means ADMS is turned on inside the device menu, OR the device does not support ZKTeco Pull on Port $port.</p></div>";
            } else {
                $result = "<div style='color:blue'><h3>ℹ️ ZKTeco Response:</h3><pre>Hex: " . bin2hex($resp) . "</pre></div>";
            }
            @fclose($socket);
        } else {
            $result = "<div style='color:red'><h3>❌ Socket Failed</h3><p>Could not reach $ip on Port $port. Error: $errstr</p></div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Biometric Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        input, select, button { display: block; width: 100%; margin: 10px 0; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
        button { background: #007bff; color: white; border: none; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #fdfdfd; }
    </style>
</head>
<body>

<div class="container">
    <h2>Biometric Connection Test</h2>
    <p>Test your connection bypassing the main software to pinpoint the exact issue.</p>
    
    <form method="POST">
        <label>IP Address</label>
        <input type="text" name="ip" value="<?= $_POST['ip'] ?? '192.0.0.121' ?>">

        <label>Port</label>
        <input type="text" name="port" value="<?= $_POST['port'] ?? '80' ?>">

        <label>Protocol</label>
        <select name="protocol">
            <option value="hikvision" <?= (($_POST['protocol'] ?? 'hikvision') == 'hikvision') ? 'selected' : '' ?>>Hikvision / HTTP</option>
            <option value="zkteco" <?= (($_POST['protocol'] ?? '') == 'zkteco') ? 'selected' : '' ?>>ZKTeco Binary / TCP</option>
        </select>

        <label>Username</label>
        <input type="text" name="username" value="<?= $_POST['username'] ?? 'admin' ?>">

        <label>Password</label>
        <input type="text" name="password" value="<?= $_POST['password'] ?? 'admin123' ?>">

        <button type="submit">Test Connection</button>
    </form>

    <?php if (isset($result)): ?>
        <div class="result">
            <?= $result ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
