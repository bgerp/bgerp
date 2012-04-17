<?php 

/**
 * Зареждане на тестовата конфигурация
 */
require 'config/config.inc.php' ;

if (!defined('EF_DONT_AUTORUN')) {
    define('EF_DONT_AUTORUN', TRUE);
}

if (!defined('EF_APP_NAME')) {
    define('EF_APP_NAME', basename(dirname(dirname(__FILE__))));
}

if (!defined('EF_WEB_INDEX')) {
    die('Не е зададена входната точка на приложението' . PHP_EOL);
}

require EF_WEB_INDEX;