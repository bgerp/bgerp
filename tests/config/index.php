<?php
/*
 * $Id: index.php,v 1.1 2008/06/30 15:16:07 milen Exp $
 */

// Ако съществува, включваме файл, който съдържа конфигурационни 
// данни, който се зареждат още на най-начално ниво. 
// Шаблон за този файл има в директорията [_docs]
@include(dirname(__FILE__) . '/index.cfg.php');

// Пътят до директорията на този файл
DEFINE('EF_INDEX_PATH', str_replace("\\", '/', dirname(__FILE__)));

// Правим опити да отркием някои пътища, ако не са дефинирани

// Общата коренна директория на APP, EF_FRAMEWORK и VENDORS
// По подразбиране е две стъпки назад от директорията на този файл
if(!defined('EF_ROOT_PATH')) {
	if(is_dir(EF_INDEX_PATH . '/ef_root')) { 
		DEFINE( 'EF_ROOT_PATH', EF_INDEX_PATH . '/ef_root');
	} else {
		DEFINE( 'EF_ROOT_PATH', dirname(EF_INDEX_PATH) . '/ef_root');
	}
}

// Зареждаме началната процедура и глобалните функции
if(!defined('EF_EF_PATH')) {
	DEFINE( 'EF_EF_PATH', EF_ROOT_PATH . '/ef');
}

require_once("boot.inc.php");