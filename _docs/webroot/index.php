<?php
/*
 * $Id: index.php,v 1.1 2008/06/30 15:16:07 milen Exp $
 */

// Ако съществува, включваме файл, който съдържа конфигурационни
// данни, който се зареждат още на най-начално ниво.
// Шаблон за този файл има в директорията [_docs]
@include(dirname(__FILE__) . '/index.cfg.php');

// Проверяваме дали има htaccess файл. Ако няма - показваме грешка
// Шаблон за този файл има в директорията [_docs]
if (!file_exists(dirname(__FILE__) . '/.htaccess')) {
    die('Error in index.php: <b>Missing .htaccess file</b>');
}

// Пътят до директорията на този файл
DEFINE('EF_INDEX_PATH', str_replace('\\', '/', realpath(dirname(__FILE__))));

// Правим опити да отркием някои пътища, ако не са дефинирани

// Общата коренна директория на APP, EF_FRAMEWORK и VENDORS
// По подразбиране е две стъпки назад от директорията на този файл
if (!defined('EF_ROOT_PATH')) {
    if (is_dir(EF_INDEX_PATH . '/ef_root')) {
        DEFINE('EF_ROOT_PATH', EF_INDEX_PATH . '/ef_root');
    } elseif (is_dir(dirname(EF_INDEX_PATH) . '/ef_root')) {
        DEFINE('EF_ROOT_PATH', dirname(EF_INDEX_PATH) . '/ef_root');
    } elseif (is_dir(dirname(dirname(EF_INDEX_PATH)) . '/ef_root')) {
        DEFINE('EF_ROOT_PATH', dirname(dirname(EF_INDEX_PATH)) . '/ef_root');
    } else {
        die('Unable to determinate <b>EF_ROOT_PATH</b>.');
    }
}

// Зареждаме началната процедура и глобалните функции
if (!defined('EF_APP_PATH')) {
    DEFINE('EF_APP_PATH', EF_ROOT_PATH . '/bgerp');
}

// Ако сме определили правилно папката с кода на фреймуърка,
// продължаваме със началния скрипт. Иначе - извеждаме грешка
if (is_dir(EF_APP_PATH)) {
    require_once(EF_APP_PATH . '/core/boot.inc.php');
} else {
    die('Error in index.php: <b>' . EF_APP_PATH . '</b> is not directory.');
}
