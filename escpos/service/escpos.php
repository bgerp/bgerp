<?php

$conf = new stdClass();
$res = '';

define("DEVICE", "/dev/usb/lp0");
define("IP_ADDRESS", "11.0.0.77");
define("PORT", "9100");
define ("OUT", "\x1B\x69\x61\x00\x1B\x40\x1B\x69\x4C\x01\x1b\x28\43\x02\x00\xFC\x02\x1B\x24\xCB\x00\x1B\x28\x56\x02\x00\xCB\x00\x1B\x68\x0B\x1B\x58\x00\x64\x00\x41\x74\x20\x79\x6F\x75\x72\x20\x73\x69\x64\x65\x0C");

header('Access-Control-Allow-Origin: *');

// Опитва се да вземе входните данни от GET заявка. Ако няма такива използва дефинираните константи. Ако има дефинирано DEVICE - се позлва с приоритет
if (($conf = unserialize(gzuncompress(base64_decode($_GET['DATA'])))) === FALSE || empty((array)$conf)) {
    $res = "err: Непарсируеми или липсващи данни.";
    echo $res;
    
    exit;
}

if (empty($conf->DEVICE) && empty($conf->IP_ADDRESS)) { // ще ги вземем от локално дефинираните константи
    $conf->DEVICE = DEVICE;
    $conf->IP_ADDRESS = IP_ADDRESS;
    $conf->PORT = PORT;
}

if (!empty($conf->OUT)) {
	// Ако има дефинирано DEVICE - се позлва с приоритет
	if (!empty($conf->DEVICE)) {
		$fp = @fopen($conf->DEVICE, "w");

		if (!$fp) {
			$res = "err: " . (error_get_last()['message']);
		} else {
			fwrite($fp, $conf->OUT);
			fclose($fp);
			$res = "Device: OK";
		}
	} elseif (!empty($conf->IP_ADDRESS) && !empty($conf->PORT)) { 	// Ако няма дефинирано DEVICE опитваме да го пратим на IP
			$fp = fsockopen($conf->IP_ADDRESS, $conf->PORT, $errno, $errstr, 10);
			if (!$fp) {
				$res = "err: $errstr ($errno)";
			} else {
				fwrite($fp, $conf->OUT);
				fclose($fp);
				$res = "Socket: OK";
			}
		} else {
		$res = "err: Недостатъчни данни за връзване по socket /IP:PORT/";
		}
} else {
		$res = "err: Празен стринг за печатане.";
}
echo $res;

exit;
