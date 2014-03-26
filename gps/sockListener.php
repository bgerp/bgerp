<?php

if ($argc < 4) {
    die("Usage: <Protocol TCP/UDP> <IP> <Port>\n");
}

// $argv[0]; Url=udp://11.0.0.64:8500
$protocol = $argv[1];
$ip = $argv[2];
$port = $argv[3];

$url = $protocol . "://" . $ip . ":" . $port;


$socket = stream_socket_server($url, $errno, $errstr, STREAM_SERVER_BIND);

if (!$socket) {
    die("$errstr ($errno)");
}

//fwrite(STDOUT, getmypid());

do {
    $string = stream_socket_recvfrom($socket, 149, 0, $peer);

    $now = time();
    $res[] = $string;
    
    // Ако има външна команда
    switch ($string) {
        case "STOP!":
            stream_socket_sendto($socket, "STOP!_OK", 0, $peer);
            //stream_socket_sendto($socket, EOF, 0, $peer); 
            $string = FALSE;
        break;
        case "STARTED?":
            stream_socket_sendto($socket, "STARTED?_OK", 0, $peer);
            array_pop($res);
           // stream_socket_sendto($socket, EOF, 0, $peer);   
        break;
        case "GET!":
            foreach ($res as $data) {
                if ($data == "GET!") {
                    $data .= "_OK";
                }
                stream_socket_sendto($socket, $data, 0, $peer);
            }
            unset($res);
//            stream_socket_sendto($socket, EOF, 0, $peer);
        break;
        default : // Ако са данни различни от команда ги проверяваме дали са валидни по контролни суми
            $url = "http://bgerp.local/gps_Log/Log/?";
            $trackerData = splitData($string);
            $params = array('trackerId'=>$trackerData['ID'],
                    'data'=>$trackerData['data'],
                    'remoteIp'=>$peer);
            
            $url .= http_build_query($params);
            //die($url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT,1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            $ans = curl_exec($ch);
            curl_close($ch);
             
            //return ($ans);            
    }
} while ($string !== false);


function splitData($string)
{
    // Първите  9 след $$ трябва да се покажат в HEX код
    // махаме $$
    $start = substr($string, 0, 2);
    $L = substr($string, 2, 2);
    $ID = substr($string, 4, 6); // Последното от ID-то е винаги ff - и го прескачаме
    $CMD = substr($string, 11, 3);
    $CRC = substr($string, strlen($string) - 4, 2);
    $data = substr($string, 13, strlen($string) - 4 - 13);

    $res = array();
    $res['start'] = $start;
    $res['L'] = dechex(ord($L{0})) . " " . dechex(ord($L{1}));
    $res['ID'] = dechex(ord($ID{0})) . dechex(ord($ID{1})) . dechex(ord($ID{2})) . dechex(ord($ID{3})) . dechex(ord($ID{4})) . dechex(ord($ID{5}));
    $res['CMD'] = dechex(ord($CMD{0})) . " " . dechex(ord($CMD{1})) . " " . dechex(ord($CMD{2}));
    $res['data'] = $data;
    $res['CRC'] = toHex($CRC);

    return $res;
}

function toHex ($str) {
    $res = '';
    for ($i = 0; $i<strlen($str); $i++) {
        $input = dechex(ord($str{$i}));
        $res .= str_pad($input, 2, "0", STR_PAD_LEFT) . " ";
    }

    return $res;
}
