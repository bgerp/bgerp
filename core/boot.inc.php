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
        cls::load($fullName);
        class_alias($fullName, $className);
        return TRUE;
    } else {
        return cls::load($className, TRUE);;
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
    return Users::requireRole($roles);
}


/**
 * Проверява дали потребителя има посочената роля
 */
function haveRole($roles)
{
    return Users::haveRole($roles);
}


/**
 * Превод (Translation) на стринг. Използва core_Lg
 */
function tr($text, $userId = 0, $key = FALSE)
{
    $Lg = cls::get('core_Lg');
    
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
    
    if (class_exists("core_Message")) {
        $text = isDebug() ? $errorInfo : $errorTitle;
        core_Message::redirect($text, 'tpl_Error');
    } else {
        // Ако грешката е възникнала, преди да се зареди core_Message се използва 
        // дирекно оптечатване чрез echo
        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html;" .
        "charset=UTF-8\" /><meta name=\"robots\" content=\"noindex,nofollow\" /></head>" .
        "<H3 style='color:red'>Error: {$errotTitle}</H3>";
        
        if (isDebug()) {
            echo "<H5 style='color:red'>Error: {$errorInfo}</H5>";
            echo "<pre>";
            print_r($debug);
            echo "</pre>";
        }
    }
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
 * Ако се извика без 2-ри аргумент - прекъсва изпълнението с изискване за дефиниция на константата
 */
function defIfNot($name, $value = NULL)
{
	if($value === NULL && !defined($name)) {
		halt("Constant '{$name}' is not defined.");
	}
    defined($name) || define($name, $value);
}


/**
 * @todo Чака за документация...
 */
function defineIfNot($name, $value)
{
    return defIfNot($name, $value);
}


/**
 * Генерира грешка, ако аргумента не е TRUE
 * Може да има още аргументи, чийто стойности се показват
 * в случай на прекъсване. Вариант на asert()
 */
function expect($expr)
{
    ($expr == TRUE) || error('Неочакван аргумент', func_get_args());
}


/**
 * Дали се намираме в DEBUG режим
 */
function isDebug()
{
    if(defined('EF_DEBUG')) return EF_DEBUG;
    
    static $noDebugIp;
    
    if(!$noDebugIp) {
        
        $hosts = arr::make(EF_DEBUG_HOSTS);
        
        if(in_array($_SERVER['HTTP_HOST'], $hosts)){
            
            
            /**
             * Включен ли е дебъга? Той ще бъде включен и когато текущия потребител има роля 'tester'
             */
            DEFINE('EF_DEBUG', TRUE);
            ini_set("display_errors", isDebug());
            ini_set("display_startup_errors", isDebug());
        } else {
            $noDebugIp = TRUE;
        }
    }
    
    return defined('EF_DEBUG') ? EF_DEBUG : FALSE;
}


/**
 * Спира обработката и извежда съобщение за грешка или го записв в errorLog
 */
function halt($err)
{
    if (isDebug()) {
        echo "<li>" . $err . " | Halt on " . date('d-m-Y H:i.s');
    } else {
        echo "On " . date('d-m-Y H:i.s') . ' a System Error has occurred';
    }
    
    error_log("HALT: " . $err);
    
    exit(-1);
}


/**
 * Точка на прекъсване. Има неограничен брой аргументи.
 * Показва съдържанието на аргументите си и текущия стек
 * Сработва само в режим на DEBUG
 */
function bp()
{
    $numargs = func_num_args();
    $stack = debug_backtrace();
    
    // Вътрешни функции, чрез които може да се генерира прекъсване
    $intFunc = array(
        'bp:debug',
        'bp:',
        'trigger:core_error',
        'error:',
        'expect:'
    );
    
    foreach ($stack as $f) {
        if (in_array(strtolower($f['function'] . ':' . $f['class']), $intFunc)) {
            $breakFile = $f['file'];
            $breakLine = $f['line'];
        }
    }
    
    // Ако сме в работен, а не тестов режим, не показваме прекъсването
    if (!isDebug()) {
        error_log("Breakpoint on line $breakLine in $breakFile");
        
        return;
    }
    
    header('Content-Type: text/html; charset=UTF-8');
    
    echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html;" .
    "charset=UTF-8\" /><meta name=\"robots\" content=\"noindex,nofollow\" /></head>" .
    "<h2>Прекъсване на линия <font color=red>$breakLine</font> в " .
    "<font color=red>$breakFile</font></h2>";
    
    if ($numargs > 0) {
        for ($i = 0; $i < $numargs; $i++) {
            echo "<hr><br><pre>";
            
            if (cls::load('core_Html', TRUE)) {
                echo core_Html::mixedToHtml(func_get_arg($i));
            } else {
                print_r(func_get_arg($i));
            }
            echo "</pre>";
        }
    }
    
    echo "<h2>Стек</h2>";
    
    foreach ($stack as $f) {
        if ((($f['file'] != $breakFile) || ($f['line'] != $breakLine)) && !$show) {
            continue;
        }
        
        $show = TRUE;
        echo "<hr><br><pre>";
        
        if (cls::load('core_Html', TRUE)) {
            echo core_Html::mixedToHtml($f);
        } else {
            print_r($f);
        }
        echo "</pre>";
    }
    
    echo Debug::getLog();
    
    exit(-1);
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
    // Не може да има връщане назад, в името на файла
    expect(strpos($shortPath, '../') === FALSE);
    
    if(defined('EF_PRIVATE_PATH')) {
        $pathsArr = array(EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH, EF_PRIVATE_PATH);
    } else {
        $pathsArr = array(EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH);
    }
    
    foreach($pathsArr as $base) {
        $fullPath = $base . '/' . $shortPath;
        
        if(is_readable($fullPath)) return $fullPath;
    }
    
    return FALSE;
}


/**
 * Връща съдържанието на файла, като стринг
 * Пътя до файла може да е указан само от пакета нататък
 */
function getFileContent($shortPath)
{
    expect($fullPath = getFullPath($shortPath));
    
    return file_get_contents($fullPath);
}


/**
 * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
 */
function sbf($rPath, $qt = '"', $absolute = FALSE)
{
    $f = getFullPath($rPath);
    
    if($f && !is_dir($f)) {
        if($dotPos = strrpos($rPath, '.')) {
            $ext = mb_substr($rPath, $dotPos);
            $time = filemtime($f);
            $newFile = mb_substr($rPath, 0, $dotPos) . "_" . date("mdHis", $time) . $ext;
            $newPath = EF_SBF_PATH . "/" . $newFile;
            
            if(!file_exists($newPath)) {
                if(!is_dir($dir = dirname($newPath))) {
                    if(!mkdir($dir, 0777, TRUE)) {
                        Debug::log("Не може да се създаде: {$dir}");
                    }
                }
                
                if(copy($f, $newPath)) {
                    $rPath = $newFile;
                }
            } else {
                $rPath = $newFile;
            }
        }
    }
    
    return $qt . getBoot($absolute) . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . $rPath . $qt;
}


/**
 * Създава URL от параметрите
 *
 * $param string $type Може да бъде relative|absolute|internal
 */
function toUrl($params = Array(), $type = 'relative')
{
    if(!$params) $params = array();
    
    // Ако параметъра е стринг - нищо не правим
    if (is_string($params)) return $params;
    
    // Очакваме, че параметъра е масив
    expect(is_array($params), $params, 'toUrl($params) Очаква  масив');
    
    $Request = & cls::get('core_Request');
    
    $Request->doProtect($params);
    
    if ($params[0]) {
        $params['Ctr'] = $params[0];
        unset($params[0]);
    }
    
    if (is_object($params['Ctr'])) {
        $params['Ctr'] = cls::getClassName($params['Ctr']);
    }
    
    if ($params[1]) {
        $params['Act'] = $params[1];
        unset($params[1]);
    }
    
    if ($params[2]) {
        $params['id'] = $params[2];
        unset($params[2]);
    }
    
    if (!$params['App']) {
        $params['App'] = $Request->get('App');
    }
    
    if(is_string($params['Ctr']) && !$params['Ctr']) {
        $params['Ctr'] = EF_DEFAULT_CTR_NAME;
    }
    
    if(is_string($params['Act']) && !$params['Act']) {
        $params['Act'] = EF_DEFAULT_ACT_NAME;
    }
    
    if (!$params['Ctr']) {
        $params['Ctr'] = $Request->get('Ctr');
        
        if (!$params['Ctr']) {
            $params['Ctr'] = 'Index';
        }
        
        if (!$params['Act']) {
            $params['Act'] = $Request->get('Act');
        }
    }
    
    // Ако има параметър ret_url - адрес за връщане, след изпълнение на текущата операция
    // И той е TRUE - това е сигнал да вземем текущото URL
    if(TRUE === $params['ret_url']) {
        $params['ret_url'] = getCurrentUrl();
    }
    
    // Ако ret_url е масив - кодирамего към локално URL
    if(is_array($params['ret_url'])) {
        $params['ret_url'] = toUrl($params['ret_url'], 'local');
    }
    
    // Ако е необходимо локално URL, то то се генерира с помощна функция
    if($type == 'local') {
        
        return toLocalUrl($params);
    }
    
    // Зпочваме подготовката на URL-то
    
    if (EF_APP_NAME_FIXED !== TRUE) {
        $pre = '/' . ($params['App'] ? $params['App'] : EF_APP_NAME);
    }
    
    // Махаме префикса на пакета по подразбиране
    $appPref = EF_APP_NAME . '_';
    
    // Очакваме името на контролера да е стринг
    expect(is_string($params['Ctr']), $appPref, $Request, $params);
    
    if (strpos($params['Ctr'], $appPref) === 0) {
        $params['Ctr'] = substr($params['Ctr'], strlen($appPref));
    }
    
    // Задължително слагаме контролера
    $pre .= '/' . $params['Ctr'] . '/';
    
    if ($params['Act'] && (strtolower($params['Act']) !== 'default' || $params['id'])) {
        $pre .= $params['Act'] . '/';
    }
    
    if ($params['id']) {
        $pre .= urlencode($params['id']) . '/';
    }
    
    unset($params['Ctr'], $params['App'], $params['Act'], $params['id']);
    
    foreach ($params as $name => $value) {
        
        if ($name == '#') continue;
        
        if ($value) {
            if (is_int($name)) {
                $name = $value;
                $value = $Request->get($name);
            }
            
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    $v = urlencode($v);
                    $url .= ($url ? '&' : '?') . "{$name}[{$key}]={$v}";
                }
            } else {
                $value = urlencode($value);
                $url .= ($url ? '&' : '?') . "{$name}={$value}";
            }
        }
    }
    
    switch($type) {
        case 'local' :
            $url1 = ltrim($pre . $url, '/');
            break;
        
        case 'relative' :
            $url1 = rtrim(getBoot(FALSE), '/') . $pre . $url;
            break;
        
        case 'absolute' :
            $url1 = rtrim(getBoot(TRUE), '/') . $pre . $url;
            break;
    }
    
    if ($params['#']) {
        $url1 .= '#' . $params['#'];
    }
    
    return $url1;
}


/**
 * @todo Чака за документация...
 */
function toLocalUrl($arr)
{
    if (is_array($arr)) {
        if (!$arr['Act'])
        $arr['Act'] = 'default';
        $url .= $arr['App'];
        $url .= "/" . $arr['Ctr'];
        $url .= "/" . $arr['Act'];
        
        if (isset($arr['id'])) {
            $url .= "/" . $arr['id'];
        }
        unset($arr['App'], $arr['Ctr'], $arr['Act'], $arr['id']);
        
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $url .= ($url ? '/' : '') . "{$key},{$k}/" . urlencode($v);
                }
            } else {
                $url .= ($url ? '/' : '') . "{$key}/" . urlencode($value);
            }
        }
    } else {
        return $arr;
    }
    
    return $url;
}


/**
 * Връща относително или пълно URL до папката на index.php
 */
function getBoot($absolute = FALSE)
{
    if ($absolute) {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $slashPos = strpos($_SERVER["SERVER_PROTOCOL"], '/');
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, $slashPos) . $s;
        
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    } else {
        
        $scriptName = $_SERVER['SCRIPT_NAME'];
        
        static $relativeWebRoot;
        
        if (!$relativeWebRoot) {
            
            $relativeWebRoot = str_replace('/index.php', '', $scriptName);
            
            if($relativeWebRoot == '/') $relativeWebRoot = '';
        }
        
        return $relativeWebRoot;
    }
}


/**
 * @todo Чака за документация...
 */
function getCurrentUrl()
{
    global $_GET;
    
    if (count($_GET)) {
        $get = $_GET;
        unset($get['virtual_url'], $get['ajax_mode']);
        
        return $get;
    }
}


/**
 * Връща масив, който представлява URL-то където трябва да
 * се използва за връщане след изпълнението на текущата задача
 */
function getRetUrl($retUrl = NULL)
{
    if (!$retUrl) {
        $retUrl = Request::get('ret_url');
    }
    
    if ($retUrl) {
        $arr = explode('/', $retUrl);
        
        $get['App'] = $arr[0];
        $get['Ctr'] = $arr[1];
        $get['Act'] = $arr[2];
        $begin = 3;
        
        $cnt = count($arr);
        
        if (count($arr) % 2 == (($begin-1) % 2)) {
            $get['id'] = $arr[$begin];
            $begin++;
        }
        
        for ($i = $begin; $i < $cnt; $i += 2) {
            $key = $arr[$i];
            $value = $arr[$i + 1];
            $value = urldecode($value);
            $key = explode(',', $key);
            
            if (count($key) == 1) {
                $get[$key[0]] = $value;
            } elseif (count($key) == 2) {
                $get[$key[0]][$key[1]] = $value;
            } else {
                error('Повече от едномерен масив в URL-то не се поддържа', $key);
            }
        }
        
        return $get;
    }
}


/**
 * @todo Чака за документация...
 */
function followRetUrl()
{
    if (!$retUrl = getRetUrl()) {
        $retUrl = array(
            EF_DEFAULT_CTR_NAME,
            EF_DEFAULT_ACT_NAME
        );
    }
    redirect($retUrl);
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 */
function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'info')
{   
    expect(ob_get_length() == 0, ob_get_length());
    $url = toUrl($url, $absolute ? 'absolute' : 'relative');
    
    if (class_exists('core_Session', FALSE)) {
        $url = core_Session::addSidToUrl($url);
    }
    
    if (isset($msg)) {
        $Nid = rand(1000000, 9999999);
        Mode::setPermanent('Notification_' . $Nid, $msg);
        Mode::setPermanent('NotificationType_' . $Nid, $type);
        $url = core_Url::addParams(toUrl($url), array('Nid' => $Nid));
    }
    
    header("Status: 302");
    header("Location: $url");
    shutdown(FALSE);
}


/**
 * Връща целия текущ URL адрес
 */
function getSelfURL()
{
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $slashPos = strpos($_SERVER["SERVER_PROTOCOL"], '/');
    $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, $slashPos) . $s;
    
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
    if(!isDebug() && $sendOutput) {
        // Изпращаме хедърите и казваме на браузъра да затвори връзката
        ob_end_flush();
        $size = ob_get_length();
        header("Content-Length: {$size}");
        header('Connection: close');
        
        // Изпращаме съдържанието на изходния буфер
        ob_end_flush();
        ob_flush();
        flush();
    }
    
    // Освобождава манипулатора на сесията. Ако трябва да се правят 
    // записи в сесията, то те трябва да се направят преди shutdown()
    if (session_id()) session_write_close();
    
    // Генерираме събитието 'suthdown' във всички сингълтон обекти
    cls::shutdown();
    
    // Излизаме със зададения статус
    exit($status);
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
processUrl();

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
defIfNot('EF_TIMEZONE', function_exists("date_default_timezone_get") ?
    date_default_timezone_get() : 'Europe/Sofia');

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

/********************************************************************************************
 *                                                                                          *
 *      Обработване на заявките за статични браузърни файлове                               *
 *                                                                                          *
 ********************************************************************************************/

// Ако имаме заявка за статичен ресурс, веднага го сервираме и
// приключване. Ако не - продъжаваме със зареждането на фреймуърка
if ($_GET[EF_SBF]) {
    _serveStaticBrowserResource($_GET[EF_SBF]);
}

/********************************************************************************************
 *                                                                                          *
 *      Зареждане ядрото на фреймуърка                                                      *
 *                                                                                          *
 ********************************************************************************************/

// Зареждаме класа регистратор на плъгините
$Plugins = & cls::get('core_Plugins');

/********************************************************************************************
 *                                                                                          *
 *      Зареждане на run-time параметри и изпълнение на заявката за динамичен ресурс        *
 *                                                                                          *
 ********************************************************************************************/

// Задаваме стойности по подразбиране на обкръжението
if (!Mode::is('screenMode')) {
    Mode::set('screenMode', core_Browser::detectMobile() ? 'narrow' : 'wide');
}

// Генерираме съдържанието
$content = Request::forward();

// Зарежда опаковката
$Wrapper = cls::get('tpl_Wrapper');

$Wrapper->renderWrapping($content);

shutdown();   // Край на работата на скрипта

/**
 * Функция, която проверява и ако се изисква, сервира
 * браузърно съдържание html, css, img ...
 */
function _serveStaticBrowserResource($name)
{
    $file = getFullPath($name);
    
    // Грешка. Файла липсва
    if (!$file) {
        error_log("EF Error: Mising file: {$name}");
        
        if (isDebug()) {
            header('Content-Type: text/html; charset=UTF-8');
            header("Content-Encoding: none");
            echo "<script type=\"text/javascript\">\n";
            echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Липсващ файл: *{$name}")) . "');\n";
            echo "</script>\n";
            exit();
        } else {
            header('HTTP/1.1 404 Not Found');
            exit();
        }
    }
    
    // Файла съществува и трябва да бъде сервиран
    // Определяне на Content-Type на файла
    $fileExt = strtolower(substr(strrchr($file, "."), 1));
    $mimeTypes = array(
        'css' => 'text/css',
        'htm' => 'text/html',
        'svg' => 'image/svg+xml',
        'html' => 'text/html',
        'xml' => 'text/xml',
        'js' => 'application/javascript',
        'swf' => 'application/x-shockwave-flash',
        'jar' => 'application/x-java-applet',
        'java' => 'application/x-java-applet',
        
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.icon'
    );
    
    $ctype = $mimeTypes[$fileExt];
    
    if (!$ctype) {
        if (isDebug()) {
            header('Content-Type: text/html; charset=UTF-8');
            header("Content-Encoding: none");
            echo "<script type=\"text/javascript\">\n";
            echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Unsuported file extention: $file ")) . "');\n";
            echo "</script>\n";
            exit();
        } else {
            header('HTTP/1.1 404 Not Found');
            exit();
        }
    }
    
    header("Content-Type: $ctype");
    
    // Хедъри за управлението на кеша в браузъра
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3153600) . " GMT");
    header("Cache-Control: max-age=3153600");
    
    if (substr($ctype, 0, 5) == 'text/' || $ctype == 'application/javascript') {
        $gzip = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));
        
        if ($gzip) {
            header("Content-Encoding: gzip");
            
            // Търсим предварително компресиран файл
            if (file_exists($file . '.gz')) {
                $file .= '.gz';
                header("Content-Length: " . filesize($file));
            } else {
                // Компресираме в движение
                ob_start("ob_gzhandler");
            }
        }
    } else {
        header("Content-Length: " . filesize($file));
    }
    
    // Изпращаме съдържанието към браузъра
    readfile($file);
    exit();
}


/**
 * Вкарва контролерните параметри от $_POST заявката
 * и виртуалното URL в $_GET заявката
 */
function processUrl()
{
    // Подготвяме виртуалното URL
    if($_GET['virtual_url']) {
        
        $dir = dirname($_SERVER['SCRIPT_NAME']);
        
        $len = ($dir == DIRECTORY_SEPARATOR) ? 1 : strlen($dir) + 1;
        
        $_GET['virtual_url'] = substr($_SERVER['REQUEST_URI'], $len);
        
        $script = '/' . basename($_SERVER['SCRIPT_NAME']);
        
        if(($pos = strpos($_GET['virtual_url'], $script)) === FALSE) {
            
            $pos = strpos($_GET['virtual_url'], '/?');
        }
        
        if($pos) {
            
            $_GET['virtual_url'] = substr($_GET['virtual_url'], 0, $pos + 1);
        }
    }
    
    // Опитваме се да извлечем името на модула
    // Ако имаме виртуално URL - изпращаме заявката към него
    if ($vUrl = $_GET['virtual_url']) {
        
        // Ако виртуалното URL не завършва на'/', редиректваме към адрес, който завършва
        $vUrl = explode('/', $vUrl);
        
        // Премахваме последният елемент
        $cnt = count($vUrl);
        
        if (empty($vUrl[$cnt - 1])) {
            unset($vUrl[$cnt - 1]);
        } else {
            if ($vUrl[0] != EF_SBF && (strpos($vUrl[$cnt - 1], '?') === FALSE)) {
                // Ако не завършва на '/' и не става дума за статичен ресурс
                // редиректваме към каноничен адрес
                redirect(getSelfURL() . '/');
            }
        }
        
        if (defined('EF_APP_NAME')) {
            $q['App'] = EF_APP_NAME;
        }
        
        if (defined('EF_CTR_NAME')) {
            $q['Ctr'] = EF_CTR_NAME;
        }
        
        if (defined('EF_ACT_NAME')) {
            $q['Act'] = EF_ACT_NAME;
        }
        
        foreach ($vUrl as $id => $prm) {
            // Определяме случая, когато заявката е за браузърен ресурс
            if ($id == 0 && $prm == EF_SBF) {
                if (!$q['App']) {
                    $q['App'] = $vUrl[1];
                }
                unset($vUrl[0], $vUrl[1]);
                $q[EF_SBF] = implode('/', $vUrl);
                break;
            }
            
            // Дали това не е името на приложението?
            if (!$q['App'] && $id == 0) {
                $q['App'] = strtolower($prm);
                continue;
            }
            
            // Дали това не е име на контролер?
            if (!$q['Ctr'] && $id < 2) {
                if (!preg_Match("/([A-Z])/", $prm)) {
                    $last = strrpos($prm, '_');
                    
                    if ($last !== FALSE && $last < strlen($prm)) {
                        $className{$last + 1} = strtoupper($prm{$last + 1});
                    } else {
                        $className{0} = strtoupper($prm{0});
                    }
                }
                $q['Ctr'] = $prm;
                continue;
            }
            
            // Дали това не е име на екшън?
            if (!$q['Act'] && $id < 3) {
                $q['Act'] = $prm;
                continue;
            }
            
            if ((count($vUrl) - $id) % 2) {
                if (!$q['id'] && !$name) {
                    $q['id'] = $prm;
                } else {
                    if ($name) {
                        $q[$name] = $prm;
                    }
                }
            } else {
                $name = $prm;
            }
        }
        
        // Вкарваме получените параметри от $_POST заявката  
        // или от виртуалното URL в $_GET заявката
        foreach ($q as $var => $value) {
            if (!$_GET[$var]) {
                if ($_POST[$var]) {
                    $_GET[$var] = $_POST[$var];
                } elseif ($q[$var]) {
                    $_GET[$var] = $q[$var];
                }
            }
        }
    }
    
    // Възможно е App да бъде получено само от POST заявка
    if (!$_GET['App'] && $_POST['App']) {
        $_GET['App'] = $_POST['App'];
    }
    
    // Абсолютен дефолт за името на приложението
    if (!$_GET['App'] && defined('EF_DEFAULT_APP_NAME')) {
        $_GET['App'] = EF_DEFAULT_APP_NAME;
    }
    
    return $q;
}