<?php

/**
 * Скрипт 'boot'
 *
 * Изпълнява се при всеки хит веднага след index.php
 * Създава обкръжението и изпълнява заявката. Съдържа
 * функции с глобална видимост
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */

/********************************************************************************************
 *                                                                                          *
 *      Дефиниции на глобални функции                                                       *
 *                                                                                          *
 ********************************************************************************************/

require EF_EF_PATH . '/core/exception/Expect.class.php';

expect(PHP_VERSION_ID >= 50300);

require EF_EF_PATH . '/core/App.class.php';

/**
 * Осигурява автоматичното зареждане на класовете
 */
function ef_autoload($className)
{
    $aliases = array('arr' => 'core_Array',
        'dt' => 'core_DateTime',
        'ht' => 'core_Html',
        'et' => 'core_ET',
        'str' => 'core_String',
        'debug' => 'core_Debug',
        'mode' => 'core_Mode',
        'redirect' => 'core_Redirect',
        'request' => 'core_Request',
        'url' => 'core_Url',
        'users' => 'core_Users',
    );
    
    if($fullName = $aliases[strtolower($className)]) {
        core_Cls::load($fullName);
        class_alias($fullName, $className);
        
        return TRUE;
    } else {
        return core_Cls::load($className, TRUE);;
    }
}

spl_autoload_register('ef_autoload', true, true);


/**
 * Изисква потребителят да има посочената роля
 * Ако я няма - генерира грешка ако е логнат, а
 * ако не е логнат подканя го да се логне
 */
function requireRole($roles)
{
    return core_Users::requireRole($roles);
}


/**
 * Проверява дали потребителя има посочената роля
 */
function haveRole($roles)
{
    return core_Users::haveRole($roles);
}


/**
 * Превод (Translation) на стринг. Използва core_Lg
 */
function tr($text, $userId = 0, $key = FALSE)
{
    $Lg = core_Cls::get('core_Lg');
    
    return $Lg->translate($text, $userId, $key);
}


// За съвместимост с версиите преди 5.3
if (!function_exists('class_alias')) {
    
    
    /**
     * @todo Чака за документация...
     */
    function class_alias($original, $alias) {
        eval('abstract class ' . $alias . ' extends ' . $original . ' {}');
    }
}


/********************************************************************************************
 *                                                                                          *
 *      Зареждане на класове с библиотечни функции                                          *
 *                                                                                          *
 ********************************************************************************************/

// Зареждаме 'CLS' класа за работа с класове
require_once(EF_EF_PATH . "/core/Cls.class.php");

/********************************************************************************************
 *                                                                                          *
 *      Определяна параметрите на конфигурацията                                            *
 *                                                                                          *
 ********************************************************************************************/


/**
 * Директорията с конфигурационните файлове
 */
defIfNot('EF_CONF_PATH', EF_ROOT_PATH . '/conf');


/**
 * По подразбиране от локалния хост се работи в режим DEBUG
 */
defIfNot('EF_DEBUG_HOSTS', 'localhost,127.0.0.1');

// Ако index.php стои в директория с име, за което съществува конфигурационен 
// файл, приема се, че това име е името на приложението
if (!defined('EF_APP_NAME') &&
    file_exists(EF_CONF_PATH . '/' . basename(EF_INDEX_PATH) . '.cfg.php')) {
    
    
    /**
     * Името на приложението. Използва се за определяне на други константи
     */
    DEFINE('EF_APP_NAME', basename(EF_INDEX_PATH));
}


/**
 * Базовото име на директорията за статичните браузърни файлове
 */
defIfNot('EF_SBF', 'sbf');


// Параметрите от виртуалното URL за зареждат в $_GET
core_App::processUrl();

// Вземаме името на приложението от параметрите на URL, ако не е дефинирано
if (!defined('EF_APP_NAME')) {
    if(!$_GET['App']) {
        halt('Error: Unable to determinate application name (EF_APP_NAME)</b>');
    }
    
    
    /**
     * Името на приложението. Използва се за определяне на други константи.
     */
    defIfNot('EF_APP_NAME', $_GET['App']);
    
    
    /**
     * Дали името на приложението е зададено фиксирано
     */
    DEFINE('EF_APP_NAME_FIXED', FALSE);
} else {
    
    
    /**
     * Дали името на приложението е зададено фиксирано
     */
    DEFINE('EF_APP_NAME_FIXED', TRUE);
}

/**
 * Пътя до директорията за статичните браузърни файлове към приложението
 */
defineIfNot('EF_SBF_PATH', EF_INDEX_PATH . "/" . EF_SBF . "/" . EF_APP_NAME);

// Зареждаме конфигурационния файл на приложението. 
// Ако липсва - показваме грешка.
// Шаблон за този файл има в директорията [_docs]
if ((@include EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php') === FALSE) {
    halt('Error in boot.php: Missing configuration file: ' .
        EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php');
}

// Зареждаме общата за всички приложения конфигурация
// Той може да липсва. Параметрите в него са с по-нисък 
// Приоритет, спрямо тези в index.cfg.php и EF_APP_NAME.cfg.php
// Шаблон за този файл има в директорията [_docs]
@include EF_CONF_PATH . '/_common.cfg.php';

// Премахваме всякакви "боклуци", които евентуално може да са се натрупали в изходния буфер
ob_clean();

// Стартира записа в буфера, като по възможност компресира съдържанието
ob_start();
ob_start('ob_gzhandler');

/**
 * Дефинира, ако не е зададено името на кода на приложението
 */
defineIfNot('EF_APP_CODE_NAME', EF_APP_NAME);

// Разрешаваме грешките, ако инсталацията е Debug
ini_set("display_errors", isDebug());
ini_set("display_startup_errors", isDebug());


/**
 * Времева зона
 */
defIfNot('EF_TIMEZONE', function_exists("date_default_timezone_get") ? : 'Europe/Sofia');

// Сетваме времевата зона
date_default_timezone_set(EF_TIMEZONE);

// Вътрешно кодиране
mb_internal_encoding("UTF-8");

// Локал за функции като basename
setlocale(LC_ALL, 'en_US.UTF8');


/**
 * Директорията с външни пакети
 */
defIfNot('EF_VENDORS_PATH', EF_ROOT_PATH . '/vendors');


/**
 * Базова директория, където се намират приложенията
 */
defIfNot('EF_APP_BASE_PATH', EF_ROOT_PATH);


/**
 * Директорията с приложението
 */
defIfNot('EF_APP_PATH', EF_APP_BASE_PATH . '/' . EF_APP_CODE_NAME);


/**
 * Базова директория, където се намират под-директориите с временни файлове
 */
defIfNot('EF_TEMP_BASE_PATH', EF_ROOT_PATH . '/temp');


/**
 * Директорията с временни файлове
 */
defIfNot('EF_TEMP_PATH', EF_TEMP_BASE_PATH . '/' . EF_APP_NAME);


/**
 * Базова директория, където се намират под-директориите с качените файлове
 */
defIfNot('EF_UPLOADS_BASE_PATH', EF_ROOT_PATH . '/uploads');


/**
 * Директорията с качените и генерираните файлове
 */
defIfNot('EF_UPLOADS_PATH', EF_UPLOADS_BASE_PATH . '/' . EF_APP_NAME);

if (!defined('EF_DONT_AUTORUN')) {
    core_App::run();
}