<?php

namespace App\Services\Hr;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco PHP Library (Standalone)
 * Adapted to use fsockopen instead of sockets extension for compatibility
 */
class ZKTecoService
{
    private $ip;
    private $port;
    private $socket;
    private $session_id = 0;
    private $reply_id = 0;

    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ACK_OK = 2000;
    const CMD_ATTLOG_RRQ = 13;
    const CMD_USERTEMP_RRQ = 9;
    const CMD_PREPARE_DATA = 1500;
    const CMD_DATA = 1501;

    public function __construct($ip, $port = 4370)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function connect()
    {
        // Try TCP first (Modern devices often use TCP on 4370)
        $this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, 3);
        
        // If TCP fails, try UDP
        if (!$this->socket) {
            $this->socket = @fsockopen("udp://{$this->ip}", $this->port, $errno, $errstr, 3);
        }

        if (!$this->socket) {
            return false;
        }

        stream_set_timeout($this->socket, 3);

        $this->session_id = 0;
        $this->reply_id = 0;

        $header = $this->createHeader(self::CMD_CONNECT, '', 0, 65535);
        fwrite($this->socket, $header);

        $response = fread($this->socket, 1024);
        if (strlen($response) >= 8) {
            $reply = unpack('vcommand/vchecksum/vsession/vreply', substr($response, 0, 8));
            if ($reply['command'] == self::CMD_ACK_OK) {
                $this->session_id = $reply['vsession'] ?? $reply['session']; // Unpack might name it session
                // Some versions of unpack use the name from string, some add index
                if (isset($reply['session'])) $this->session_id = $reply['session'];
                
                // Re-parsing carefully
                $u = unpack('v4', substr($response, 0, 8));
                $this->session_id = $u[3];
                $this->reply_id = $u[4];
                
                return true;
            }
        }

        fclose($this->socket);
        return false;
    }

    public function disconnect()
    {
        if ($this->socket) {
            $header = $this->createHeader(self::CMD_EXIT);
            fwrite($this->socket, $header);
            fclose($this->socket);
        }
    }

    private function createHeader($command, $command_string = '', $session_id = null, $reply_id = null)
    {
        $session_id = $session_id !== null ? $session_id : $this->session_id;
        $reply_id = $reply_id !== null ? $reply_id : $this->reply_id;

        $buf = pack('vvvv', $command, 0, $session_id, $reply_id) . $command_string;
        $chksum = $this->calculateChecksum($buf);
        
        $buf = pack('vvvv', $command, $chksum, $session_id, $reply_id) . $command_string;
        return $buf;
    }

    private function calculateChecksum($buf)
    {
        $size = strlen($buf);
        $chksum = 0;
        
        if ($size % 2 == 1) {
            $buf .= chr(0);
            $size++;
        }
        
        $u = unpack('v*', $buf);
        foreach ($u as $v) {
            $chksum += $v;
            while ($chksum > 65535) $chksum -= 65536;
        }

        $chksum = ~$chksum;
        while ($chksum < 0) $chksum += 65536;
        
        return $chksum;
    }

    public function getAttendance()
    {
        if (!$this->socket) return [];

        $header = $this->createHeader(self::CMD_ATTLOG_RRQ);
        fwrite($this->socket, $header);

        $response = fread($this->socket, 1024);
        if (strlen($response) < 8) return [];

        $header_data = unpack('v4', substr($response, 0, 8));
        if ($header_data[1] == self::CMD_PREPARE_DATA) {
            $size = unpack('V', substr($response, 8, 4))[1];
            $data = '';
            
            while (strlen($data) < $size) {
                $chunk = fread($this->socket, 1024);
                if (strlen($chunk) == 0) break;
                
                // Skip header of data chunks if present (ZKTeco protocol quirk)
                if (strlen($chunk) > 8 && unpack('v', substr($chunk, 0, 2))[1] == self::CMD_DATA) {
                    $data .= substr($chunk, 8);
                } else {
                    $data .= $chunk;
                }
            }
            
            return $this->parseAttendance($data);
        }

        return [];
    }

    private function parseAttendance($data)
    {
        $logs = [];
        $record_size = 40; // Modern ZK devices use 40 byte records
        
        if (strlen($data) % 40 != 0 && strlen($data) % 12 == 0) {
            $record_size = 12; // Older devices use 12 bytes
        }

        for ($i = 0; $i <= strlen($data) - $record_size; $i += $record_size) {
            $record = substr($data, $i, $record_size);
            if ($record_size == 40) {
                $u = unpack('vuid/A24id/Cstate/A13timestamp', $record);
                $logs[] = [
                    'id' => trim($u['id']),
                    'timestamp' => $this->decodeTime(unpack('V', substr($record, 26, 4))[1]),
                    'state' => $u['state']
                ];
            } else {
                $u = unpack('vuid/vstate/Vtimestamp', $record);
                $logs[] = [
                    'id' => $u['uid'],
                    'timestamp' => $this->decodeTime($u['timestamp']),
                    'state' => $u['state']
                ];
            }
        }
        return $logs;
    }

    private function decodeTime($t)
    {
        $second = $t % 60; $t /= 60;
        $minute = $t % 60; $t /= 60;
        $hour = $t % 24; $t /= 24;
        $day = $t % 31 + 1; $t /= 31;
        $month = $t % 12 + 1; $t /= 12;
        $year = floor($t + 2000);

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }
}
