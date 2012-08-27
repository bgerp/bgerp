<?php

if ($_GET['a'] == 'blank') exit;

if(empty($_GET['a'])) {
	echo ("<h1>Задаване на основните параметри</h1>");
}

/**********************************
 * Проверка за конфигурационен файл
 **********************************/
if ($_GET['a'] == '1') {
	echo ("<h2>Проверка за конфигурационен файл ...</h2>");
}
$filename = EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php';
if(file_exists($filename)) {
	include($filename);
} else {
   	echo "<li style='color: red;'>Грешка: Липсва конфигурационен файл $filename</li>";
   	$error = TRUE;
}

if ($_GET['a'] == '1') {
	echo "<li style='color: green;'>Конфигурационен файл $filename - ОК</li>";
	exit;
}

/**********************************
 * Проверка за връзка към MySql-a
 **********************************/
if ($_GET['a'] == '2') {
	echo ("<h2>Проверка за връзка към MySql-a ...</h2>");
	if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS')) {
		if (FALSE !== mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS)) {
			echo("<li style='color: green;'>Успешна връзка с базата данни</li>");
		} else {
			echo("<li style='color: red;'>Неуспешна връзка с базата данни</li>");
		}
	} else {
		echo("<li style='color: red;'>Недефинирани константи за връзка с базата данни</li>");
	}
	exit;	
}

/**********************************
 * Проверка за необходимите модули на PHP-то
 **********************************/
if ($_GET['a'] == '3') {
	echo ("<h2>Проверка за необходимите модули на PHP ...</h2>");
	// Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
	// за сега периодично се попълва ръчно
	$requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
								'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
								'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
								'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');
	
	$activePhpModules = get_loaded_extensions();
	
	foreach($requiredPhpModules as $required) {
		if(!in_array($required, $activePhpModules)){
			echo ("<li style='color: red;'> модул: $required - не е инсталиран!</li>");
			flush();
		} else {
			echo ("<li style='color: green;'> модул: $required - OK!</li>");
			flush();
		}
	}
	exit;	
}

/**********************************
 * Проверка за необходимите модули на Apache
 **********************************/
if ($_GET['a'] == '4') {
	echo ("<h2>Проверка за необходимите модули на Apache ...</h2>");
	
	// Масив съдържащ всички активни apache модули, необходими за правилна работа на ситемата
	
	$requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');
	
	$activeApacheModules = apache_get_modules();
	
	foreach($requiredApacheModules as $requiredA){
		
		if(!in_array($requiredA, $activeApacheModules)){
			echo ("<li style='color: red;'> модул: $requiredA - не е инсталиран!</li>");
			flush();
		} else {
			echo ("<li style='color: green;'> модул: $requiredA - OK!</li>");
			flush();
		}
	}
	exit;
}
?>
<script language="javascript">

	function next() {
	    if ( typeof next.counter == 'undefined' ) {
	    	next.counter = 0;
	    }

	    alert(++next.counter);		
		document.getElementById('test').src='http://'+location.host+'/?SETUP&a='+next.counter;
	}
	
</script>

<input type="button" onclick="document.getElementById('test').src='<?php echo("http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank")?>';" value="Начало">
<input type="button" onclick="next();;" value="Следващ">
<iframe src='<?php "http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank"?>' frameborder="0" name="test" id="test" width=800 height=300></iframe>

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