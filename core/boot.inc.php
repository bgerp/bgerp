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


/**
 * Показва грешка и спира изпълнението. Използва core_Message
 */
function error($errorInfo = NULL, $debug = NULL, $errorTitle = 'ГРЕШКА В ПРИЛОЖЕНИЕТО')
{
    if (isDebug() && isset($debug)) {
        bp($errorTitle, $errorInfo, $debug);
    }
    
    $text = isDebug() ? $errorInfo : $errorTitle;
    core_Message::redirect($text, 'tpl_Error');

    exit(-1);
}


/**
 * Задава стойността(ите) от втория параметър на първия,
 * ако те не са установени
 * @todo: използва ли се тази функция за масиви?
 */
function setIfNot(&$p1, $p2)
{
    $args = func_get_args();
    
    for ($i = 1; $i < func_num_args(); $i++) {
        $new = $args[$i];
        
        if (is_array($p1)) {
            if (!count($new))
            continue;
            
            foreach ($new as $key => $value) {
                if (!isset($p1[$key])) {
                    $p1[$key] = $value;
                }
            }
        } else {
            if (!isset($p1)) {
                $p1 = $new;
            } else {
                return $p1;
            }
        }
    }
    
    return $p1;
}


/**
 * Дефинира константа, ако преди това не е била дефинирана
 * Ако вторият и аргумент започва с '[#', то изпълнението се спира 
 * с изискване за дефиниция на константата
 */
function defIfNot($name, $value = NULL)
{
    if(!defined($name)) {
        if(substr($name, 0, 2) == '[#') {
            halt("Constant '{$name}' is not defined. Please edit: " . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php');
        } else {
            define($name, $value);
        }
    }
}


/**
 * @todo Чака за документация...
 */
function defineIfNot($name, $value)
{
    return 
    /**
     * @todo Чака за документация...
     */
    defIfNot($name, $value);
}


/**
 * Генерира грешка, ако аргумента не е TRUE
 * Може да има още аргументи, чийто стойности се показват
 * в случай на прекъсване. Вариант на asert()
 */
function expect($expr)
{
//    ($expr == TRUE) || error('Неочакван аргумент', func_get_args());
    if (!$expr) {
        throw new core_exception_Expect('Неочакван аргумент', func_get_args());
    }
    
}


/**
 * Дали се намираме в DEBUG режим
 */
function isDebug()
{
    return core_App::isDebug();
}


/**
 * Спира обработката и извежда съобщение за грешка или го записв в errorLog
 */
function halt($err)
{
    return core_App::halt($err);
}


require_once EF_EF_PATH . '/core/exception/Expect.class.php';


/**
 * Точка на прекъсване. Има неограничен брой аргументи.
 * Показва съдържанието на аргументите си и текущия стек
 * Сработва само в режим на DEBUG
 */
function bp()
{
    _bp(arrayToHtml(func_get_args()), debug_backtrace());
}

function _bp($argsHtml, $stack)
{
    $stack = prepareStack($stack, $breakFile, $breakLine);
    
    // Ако сме в работен, а не тестов режим, не показваме прекъсването
    if (!isDebug()) {
        error_log("Breakpoint on line $breakLine in $breakFile");
        
        return;
    }
    
    header('Content-Type: text/html; charset=UTF-8');
    
    echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html;" .
    "charset=UTF-8\" /><meta name=\"robots\" content=\"noindex,nofollow\" /></head>" .
    "<h1>Прекъсване на линия <font color=red>$breakLine</font> в " .
    "<font color=red>$breakFile</font></h1>";
    
    echo $argsHtml;
    
    echo "<h2>Стек</h2>";

    echo core_Exception_Expect::getTraceAsHtml($stack);
    
    echo renderStack($stack);
    
    echo Debug::getLog();
    
    exit(-1);
}

function prepareStack($stack, &$breakFile, &$breakLine)
{
    // Вътрешни функции, чрез които може да се генерира прекъсване
    $intFunc = array(
        'bp:debug',
        'bp:',
        'trigger:core_error',
        'error:',
        'expect:'
    );
    
    $breakpointPos = NULL;
    
    foreach ($stack as $i=>$f) {
        if (in_array(strtolower($f['function'] . ':' . $f['class']), $intFunc)) {
            $breakpointPos = $i;
        }
    }
    
    if (isset($breakpointPos)) {
        $breakLine = $stack[$breakpointPos]['line'];
        $breakFile = $stack[$breakpointPos]['file'];
        $stack = array_slice($stack, 0, $breakpointPos-1);
    }
    
    return $stack;
}

function renderStack($stack)
{
    $result = '';
    
    foreach ($stack as $f) {
        $result .= "<hr><br><pre id=\"{$f['file']}:{$f['line']}\">";
        $result .= core_Html::mixedToHtml($f);
        $result .= "</pre>";
    }
    
    return $result;
}

function arrayToHtml($args)
{
    $result = '';
    
    foreach ($args as $arg) {
        $result .= "<hr><br><pre>";
        $result .= core_Html::mixedToHtml($arg);
        $result .= "</pre>";
    }
    
    return $result;
}


/****************************************************************************************
 *                                                                                      *
 *           ФУНКЦИИ ЗА РАБОТА С URL                                                    *
 *                                                                                      *
 ****************************************************************************************/

/**
 * Тази функция определя пълния път до файла.
 * Като аргумент получава последната част от името на файла
 * Файла се търси в EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH
 * Ако не бъде открит, се връща FALSE
 */
function getFullPath($shortPath)
{
    return core_App::getFullPath($shortPath);
}


/**
 * Връща съдържанието на файла, като стринг
 * Пътя до файла може да е указан само от пакета нататък
 */
function getFileContent($shortPath)
{
    return core_App::getFileContent($shortPath);
}


/**
 * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
 */
function sbf($rPath, $qt = '"', $absolute = FALSE)
{
    return core_App::sbf($rPath, $qt, $absolute);
}


/**
 * Създава URL от параметрите
 *
 * $param string $type Може да бъде relative|absolute|internal
 */
function toUrl($params = array(), $type = 'relative')
{
    return core_App::toUrl($params, $type);
}


/**
 * @todo Чака за документация...
 */
function toLocalUrl($arr)
{
    return core_App::toLocalUrl($arr);
}


/**
 * Връща относително или пълно URL до папката на index.php
 * 
 * Псевдоним на @link core_App::getBoot()
 */
function getBoot($absolute = FALSE)
{
    return core_App::getBoot($absolute);
}


/**
 * @todo Чака за документация...
 */
function getCurrentUrl()
{
    core_App::getCurrentUrl();
}


/**
 * Връща масив, който представлява URL-то където трябва да
 * се използва за връщане след изпълнението на текущата задача
 */
function getRetUrl($retUrl = NULL)
{
    core_App::getRetUrl($retUrl);
}


/**
 * @todo Чака за документация...
 */
function followRetUrl()
{
    core_App::followRetUrl();
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 * 
 * 
 */
function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'info')
{
    return core_App::redirect($url, $absolute, $msg, $type);
}


/**
 * Връща целия текущ URL адрес
 */
function getSelfURL()
{
    return core_App::getSelfURL();
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


/**
 * Функция за завършване на изпълнението на програмата
 *
 * @param bool $sendOutput
 */
function shutdown($sendOutput = TRUE)
{
    core_App::shutdown();
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