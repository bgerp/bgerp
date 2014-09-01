<?php 

define('EF_DONT_AUTORUN', TRUE);
define('EF_DEBUG', TRUE);
define('EF_ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('EF_EF_PATH', dirname(dirname(__FILE__)));
define('EF_APP_NAME', 'testapp');
define('EF_APP_PATH', dirname(__FILE__) . '/' . EF_APP_NAME);
define('EF_CONF_PATH', EF_APP_PATH . '/config');
define('EF_INDEX_PATH', dirname(__FILE__));

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_DEPRECATED);

require_once( EF_EF_PATH . "/core/boot.inc.php");
