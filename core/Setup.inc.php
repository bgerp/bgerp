<?php
echo ("<h2>Първоначално установяване на основните параметри</h2>");

/**
 * Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
 */
$requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
							'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
							'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
							'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');

/**
 * Масив съдържащ всички активни apach модули, необходими за правилна работа на ситемата
 */
$requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');

/**
 * Масив с липсващите php и/или apache модули, 
 * без които системата не работи коректно
 */
$error = array();
/**
 * Масив с всички активни php модули
 */
$activePhpModules = get_loaded_extensions();

echo ("<h3>Проверка за PHP модули ...</h3>");

foreach($requiredPhpModules as $required){
	
	if(!in_array($required, $activePhpModules)){
		echo ("<li style='color: red;'> модул: $required - не е инсталиран!</li>");
		flush();
	} else {
		echo ("<li style='color: green;'> модул: $required - OK!</li>");
		flush();
	}
}

/**
 * Масив с всички активни apache модули
 */
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

/**
 * Път до конфигурационния файл
 */

$filename = EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php';


if(file_exists($filename)){
	
	echo "<li style='color: green;'> Конфигурационния файл $filename съществува.</li>";
	
	$str = file_get_contents($filename);
	
	if(strpos($str, '[#') === FALSE){
		
		echo "<li style='color: green;'> All constants are defined.</li>";
		
	} else {
		
		echo "<li style='color: red;'>Some constants are not defined.</li>";
		
	}
	
} else {
	
   		echo "<li style='color: red;'>Error: Missing configuration file $filename</li>";
    
	}
	


if (count($error) > 0){
	
	print_r($error)."\n";
	die('Error: Missing modules');
	
} else {
	$location = 'http://'.$_SERVER['SERVER_NAME'].'/core_Users/login/';
	
	echo "<button onclick=\"window.location='$location'\">Начало</button>";
    die('Everything is OK');
    
}