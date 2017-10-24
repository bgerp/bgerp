<?php

if ($argc < 5) {
    die("Usage: <Protocol TCP/UDP> <IP> <Port> <LogUrl>\n");
}

// $argv[0]; Url=udp://11.0.0.64:8500
$protocol = $argv[1];
$ip = $argv[2];
$port = $argv[3];
$logUrl = $argv[4];

$url = $protocol . "://" . $ip . ":" . $port;

$socket = stream_socket_server($url, $errno, $errstr, STREAM_SERVER_BIND);

if (!$socket) {
    fwrite(STDOUT,"$errstr ($errno)");
    
    exit;
}

fwrite(STDOUT, "OK|" . getmypid());
        
do {
    $string = stream_socket_recvfrom($socket, 149, 0, $peer);

    // Ако са данни различни от команда ги пращаме към bgERP-a
    $url = $logUrl; // "http://{$domain}/tracking_Log/Log/?";
    $trackerData = splitData($string);
    
    if ($trackerData['CRC'] != crc16($trackerData['allData'])) {
        // Гласим параметрите за URL-то да пратят грешка
        // $trackerData['ID'] = 'CRC Error';
        // $trackerData['data'] = '';
        
        // Пропускаме грешните
        continue;
    }
    
    $params = array('trackerId'=>$trackerData['ID'],
            'data'=>$trackerData['data'],
            'remoteIp'=>$peer);
    
    $url .= http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT,1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $ans = curl_exec($ch);
    curl_close($ch);

} while ($string !== false);


function splitData($string)
{
    $res = array();

    $res['start'] = substr($string, 0, 2); // Винаги трябва да е $$ за този модел
    $res['L'] = toHex(substr($string, 2, 2)); // Дължина на целия пакет данни
    $res['ID'] = toHex(substr($string, 4, 6)); // Последното от ID-то е винаги ff - и го прескачаме
    $res['CMD'] = toHex(substr($string, 11, 3)); // Команда
    $res['data'] = substr($string, 13, strlen($string) - 4 - 13); // Основни данни - тракер + GPRMC sentense
    $res['CRC'] = toHex(substr($string, hexdec($res['L']) - 4, 2)); // Контролна сума
    $res['allData'] = substr($string, 0, hexdec($res['L']) - 4); // Данните без контролната сума

    return $res;
}

function toHex ($str)
{

    $res = '';
    
    for ($i = 0; $i<strlen($str); $i++) {
        $input = dechex(ord($str{$i}));
//      $res .= str_pad($input, 2, "0", STR_PAD_LEFT) . " ";
        $res .= str_pad($input, 2, "0", STR_PAD_LEFT);
    }

    return $res;
}

function crc16($data)
{
    $crc = 0xFFFF;
    
    for ($i = 0; $i < strlen($data); $i++)
    {
        $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
    }
    
   return dechex($crc);
}