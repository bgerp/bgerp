<?php




/**
 * Изпраща команда към ПОС терминала
 *
 * @param resource $fp файлов манипулатор за ПОС-а
 * @param array $cmd команда
 *
 * @return bool
 */
function cmdWrite ($fp, $cmd)
{
	foreach ($cmd as $b) {
		if (fputs($fp, chr($b)) === false) {
			
			return false;
		}
	}
	
	return true;
}

/**
 * Чете резултат от терминала
 *
 * @param resource $fp файлов манипулатор за ПОС-а
 *
 * @return array $res 
 */
function cmdRead($fp)
{
	$end = false;
	$res = [];
	do {
	   $res[] = ord(fgetc($fp));
	   if (end($res) == 3) {
		   $end = true;
		   // Четем кода по четност
		   $resPCheck = ord(fgetc($fp));
	   }

	} while ($end != true);
	
	return $res;
}

/**
 * Изчислява сума по четност
 *
 * @param array $res команда
 *
 * @return string $res
 */
function parityCheck(array $arr) 
{
	// Махаме първия байт 06 - ACK - Acknowledge
	if ($arr[0] == 0x6 || $arr[0] == 0x2) unset($arr[0]);
	// Махаме втория байт 02 - STX - Start of Text
	if ($arr[1] == 0x2) unset($arr[1]);

	// Изчисляваме сумата по четност
	$PCheck = 0;
	foreach ($arr as $byte) {
		$PCheck ^= $byte; 
	}
	
	return ([$PCheck]);
}


header('Access-Control-Allow-Origin: *');

if ($_SERVER["REQUEST_METHOD"] != 'GET') {
    
    exit;
}


// Опитва се да вземе входните данни от GET заявка. Ако няма такива използва дефинираните константи.
if (($data = unserialize(base64_decode(urldecode($_GET['DATA'])))) === FALSE || empty((array)$data)) {
    $res = "err: Непарсируеми или липсващи данни.";
    echo $res;
    
    exit;
}

if ($data->PORT == 'COM1' || $data->PORT == 'COM2' || $data->PORT == 'COM3') {
    define ('DEVICE', $data->PORT);
} else define ('DEVICE', 'COM1');

clearstatcache();
$fp = fopen(DEVICE,'r+');
//stream_set_blocking($fp, 0);

$STX = [0x02]; // STX - Start of Text
$ETX = [0x03]; // ETX - End of Text
$АСК = [0x06]; // ACK - Acknowledge

//// Request approval
$rApproval =  [0x02, 0x31, 0x30, 0x31, 0x03, 0x33];
$okApproval = [0x06, 0x02, 0x51, 0x48, 0x49, 0x03];
if (!cmdWrite($fp,$rApproval)) {
	fclose($fp);
	die("err:  Грешка при изпращане!");
}
$res = cmdRead($fp);

//------------ Пращаме сума 12 байта
$amount = '423.78';
$amount = intval((100*floatval($amount)));
$amount = str_pad("$amount", 12, "0", STR_PAD_LEFT);
$amountArr = unpack('C*', $amount);
    //$cmd = "06 02 34 30 31 31    30 30 30 30 30 30 30 30 30 31 35 38      39 37 35 39 39 39 39 03 30"
	//$amountCmd = array(0x06,0x02,0x34,0x30,0x31,0x31,0x30,0x30,0x30,0x30,0x30,0x30,0x30,0x30,0x30,0x31,0x35,0x38,0x39,0x37,0x35,0x39,0x39,0x39,0x39,0x03,0x30);
	
$amountCmd = array_merge([0x06,0x02,0x34,0x30,0x31,0x31], $amountArr, [0x39,0x37,0x35], [0x39,0x39,0x39,0x39], $ETX);
$amountCmd = array_merge($amountCmd, parityCheck($amountCmd));

if (!cmdWrite($fp,$amountCmd)) {
	fclose($fp);
	die("err: Грешка при изпращане!");
}

$res = cmdRead($fp);

fclose($fp);

if (count($res) == 60) {
	die("OK");
}

echo ("err: Неопределена грешка.");

// При отказ:           array(8) {[0]=>  int(6)  [1]=>  int(2)  [2]=>  int(50)  [3]=>  int(48)  [4]=>  int(49)  [5]=>  int(49)  [6]=>  int(55)  [7]=>  int(3)}
// При грешен ПИН:      array(8) {[0]=>  int(6)  [1]=>  int(2)  [2]=>  int(50)  [3]=>  int(48)  [4]=>  int(49)  [5]=>  int(48)  [6]=>  int(56)  [7]=>  int(3)}

// При валидно плащане: array(60){[0]=>  int(6)  [1]=>  int(2)  [2]=>  int(53)  [3]=>  int(48)  [4]=>  int(49)  [5]=>  int(49)  [6]=>  int(80)  [7]=>  int(53) 
//								  [8]=>  int(52) [9]=>  int(48) [10]=> int(48)  [11]=> int(52)  [12]=> int(49)  [13]=> int(51)  [14]=> int(48)  [15]=> int(48)
//								  [16]=> int(48) [17]=> int(48) [18]=> int(49)  [19]=> int(50)  [20]=> int(51)  [21]=> int(48)  [22]=> int(52)  [23]=> int(48)
//								  [24]=> int(56) [25]=> int(54) [26]=> int(48)  [27]=> int(48)  [28]=> int(48)  [29]=> int(51)  [30]=> int(51)  [31]=> int(51)
//								  [32]=> int(57) [33]=> int(48) [34]=> int(49)  [35]=> int(54)  [36]=> int(51)  [37]=> int(54)  [38]=> int(54)  [39]=> int(57)
//								  [40]=> int(56) [41]=> int(48) [42]=> int(48)  [43]=> int(48)  [44]=> int(48)  [45]=> int(48)  [46]=> int(48)  [47]=> int(48)
//								  [48]=> int(52) [49]=> int(50) [50]=> int(51)  [51]=> int(55)  [52]=> int(56)  [53]=> int(48)  [54]=> int(52)  [55]=> int(51)
//								  [56]=> int(52) [57]=> int(53) [58]=> int(48)  [59]=>  int(3)}
