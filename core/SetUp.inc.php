<?php


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


foreach($requiredPhpModules as $required){
	
	if(!in_array($required, $activePhpModules)){
		
		$error['phpModules'][$required] = $required;
	}
}

/**
 * Масив с всички активни apache модули
 */
$activeApacheModules = apache_get_modules();


foreach($requiredApacheModules as $requiredA){
	
	if(!in_array($requiredA, $activeApacheModules)){
		
		$error['apacheModules'][$requiredA] = $requiredA;
	}
}

/**
 * Път до конфигурационния файл
 */
$filename = EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';


if(file_exists($filename)){
	
	echo "Configuration file $filename exists"."<br>";
	
	$str = file_get_contents($filename);
	
	if(strpos($str, '[#') === FALSE){
		
		echo "All constants are defined"."<br>";
		
	} else {
		
		echo "Some constants are not defined"."<br>";
		
	}
	
} else {
	
   		echo "Error: Missing configuration file $filename"."<br>";
    
	}
	


if (count($error) > 0){
	
	print_r($error)."/n";
	halt('Error: Missing modules');
	
} else {
	$location = 'http://'.$_SERVER['SERVER_NAME'].'/core_Users/login/';
	
	echo "<button onclick=\"window.location='$location'\">Начало</button>";
    halt('Everything is OK');
    
 	}
	
