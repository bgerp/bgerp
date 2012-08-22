<?php 
if ($_GET['a'] == 'blank') exit;

echo ("<h2>Задаване на основните параметри</h2>");

/**********************************
 * Проверка за конфигурационен файл
 **********************************/

$filename = EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php';

if(file_exists($filename)) {
	include($filename);
} else {
   	echo "<li style='color: red;'>Грешка: Липсва конфигурационен файл $filename</li>";
}

/**********************************
 * Проверка за връзка към MySql-a
 **********************************/

if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS')) {
	if (FALSE !== mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS)) {
		echo("<li style='color: green;'>Успешна връзка с базата данни</li>");
	} else {
		echo("<li style='color: red;'>Неуспешна връзка с базата данни</li>");
	}
} else {
	echo("<li style='color: red;'>Недефинирани константи за връзка с базата данни</li>");
}

/**********************************
 * Проверка за необходимите модули на PHP-то
 **********************************/

// Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
// за сега периодично се попълва ръчно
$requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
							'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
							'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
							'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');

$activePhpModules = get_loaded_extensions();

echo ("<h3>Проверка за PHP модули ...</h3>");

foreach($requiredPhpModules as $required) {
	if(!in_array($required, $activePhpModules)){
		echo ("<li style='color: red;'> модул: $required - не е инсталиран!</li>");
		flush();
	} else {
		echo ("<li style='color: green;'> модул: $required - OK!</li>");
		flush();
	}
}

/**********************************
 * Проверка за необходимите модули на PHP-то
 **********************************/

// Масив съдържащ всички активни apache модули, необходими за правилна работа на ситемата

$requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');

$activeApacheModules = apache_get_modules();

echo ("<h3>Проверка за Apache модули ...</h3>");

foreach($requiredApacheModules as $requiredA){
	
	if(!in_array($requiredA, $activeApacheModules)){
		echo ("<li style='color: red;'> модул: $requiredA - не е инсталиран!</li>");
		flush();
	} else {
		echo ("<li style='color: green;'> модул: $requiredA - OK!</li>");
		flush();
	}
}
?>
<input type="button" onclick="test.location.href='<?php "http://".$_SERVER['SERVER_NAME']."/?SETUP"?>';document.getElementById('test').frameBorder=1" value="Провери">
<input type="button" onclick="test.location.href='<?php "http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank"?>';document.getElementById('test').frameBorder=0" value="Clear">
<iframe src='<?php "http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank"?>' frameborder="1" name="test" id="test" width=800 height=400></iframe>

<?php
/*
if (count($error) > 0){
	
	print_r($error)."\n";
	die('Error: Missing modules');
	
} else {
	$location = 'http://'.$_SERVER['SERVER_NAME'].'/core_Users/login/';
	
	echo "<button onclick=\"window.location='$location'\">Начало</button>";
    die('Everything is OK');
    
}
*/