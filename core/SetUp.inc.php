<?php


/**
 * Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
 */
$requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
							'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
							'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
							'SPL', 'standard', 'tokenizer', 'xml', 'zlib');

/**
 * Масив съдържащ всички активни apach модули, необходими за правилна работа на ситемата
 */
$requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');

/**
 * Масив с всички активни php модули
 */
$activePhpModules = get_loaded_extensions();


foreach($requiredPhpModules as $required){
	
	if(!in_array($required, $activePhpModules)){
		
		$systemInfo['phpModules']['no'] = $required;
	}
}

/**
 * Масив с всички активни apache модули
 */
$activeApacheModules = apache_get_modules();


foreach($requiredApacheModules as $requiredA){
	
	if(!in_array($requiredA, $activeApacheModules)){
		
		$systemInfo['apacheModules']['no'] = $requiredA;
	}
}

/**
 * Път до конфигурационния файл
 */
$filename = '/var/www/ef_root/conf/bgerp.cfg.php';

/**
 * Път до конфигурационния файл на php
 */
$filePhp =  '/etc/php5/apache2/php.ini';

if(file_exists($filename)){
	
	$soap = file_get_contents($filePhp);
	
	if(strpos($soap, 'extension=php_soap.dll') === FALSE && 
	   strpos($soap, ';extension=php_soap.dll') === FALSE) {
	   	
	   	halt('Error: Missing SoapClient');
	   	
	   } else {
	   	
	   	echo "File php.ini is correct"."<br>";
	   	
	   }
	
}

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
	


if (count($systemInfo) > 0){
	
	print_r($systemInfo)."/n";
	halt('Error: Missing modules');
	
} else {
	
	echo "<button onclick=\"window.location='http://127.0.0.1/core_Users/login/'\">Начало</button>";
    halt('Everything is OK');
    
 	}
	