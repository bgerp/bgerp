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
 * @copyright  2006-2014 Experta OOD
 * @license    GPL 3
 * @link
 */

// Проверка за минимално изискуемата версия на PHP
if (version_compare(phpversion(), '5.5.0') < 0) {
    echo('Необходимо е php 5.5+!');
    die;
}

// Зареждаме класовете за обработка на грешки
require_once(EF_APP_PATH . '/core/exception/Break.class.php');

// Зареждаме класовете за обработка на грешки
require_once(EF_APP_PATH . '/core/exception/Expect.class.php');

// Зареждаме дебъг класа
require_once(EF_APP_PATH . '/core/Debug.class.php');

// Стартираме брояча на Debug
core_Debug::init();

// Зареждаме 'CLS' класа за работа с класове
require_once(EF_APP_PATH . '/core/Cls.class.php');

// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . '/core/App.class.php');

// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . '/core/BaseClass.class.php');

// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . '/core/Html.class.php');

// Прихващаме грешките
core_Debug::setErrorWaching();

// Подсигуряваме $_GET['virtual_url']
if(!$_GET['virtual_url']) $_GET['virtual_url'] = $_SERVER['REQUEST_URI'];

try {
    $isDefinedFatalErrPath = defined('DEBUG_FATAL_ERRORS_PATH');
    $stopDebug = false;
    
    // Ако е зададено за кои URL-та да не се записва в лога
    if ($isDefinedFatalErrPath) {
        if (!defined('DEBUG_FATAL_ERRORS_EXCLUDE')) {
            define('DEBUG_FATAL_ERRORS_EXCLUDE', 'sw.js,favicon.ico,log_Browsers/js/*,pwa_Plugin');
        }
        
        if (DEBUG_FATAL_ERRORS_EXCLUDE) {
            $errorsExlude = explode(',', DEBUG_FATAL_ERRORS_EXCLUDE);
            $vUrlStr = trim($_GET['virtual_url']);
            $vUrlStr = trim($vUrlStr, '/');
            $vUrlStr = mb_strtolower($vUrlStr);
            foreach ($errorsExlude as $eStr) {
                $eStr = trim($eStr);
                $eStr = trim($eStr, '/');
                $eStr = mb_strtolower($eStr);
                $eStr = preg_quote($eStr, '/');
                $eStr = str_replace('\*', '.*', $eStr);
                $eStrPattern = '/^' . $eStr . '$/i';
                
                if (preg_match($eStrPattern, $vUrlStr)) {
                    $stopDebug = true;
                    break;
                }
            }
        }
    }
    
    // Вземаме всички входни данни
    if ($isDefinedFatalErrPath && !$stopDebug) {
        $data = @json_encode(array('GET' => $_GET, 'POST' => $_POST, 'SERVER' => $_SERVER));
        
        if (!$data) {
            $data = json_last_error();
            $data .= ' Serilize: ' . @serialize($data);
        }
    }
    
    // Инициализиране на системата
    core_App::initSystem();
    
    // Дъмпване във файл на всички входни данни
    if ($isDefinedFatalErrPath && !$stopDebug) {
        $pathName = rtrim(DEBUG_FATAL_ERRORS_PATH, '/') . '/000' . date('_H_i_s_') . rand(1000, 9999) . '.debug';
        
        if (!defined('DEBUG_FATAL_ERRORS_FILE') && @file_put_contents($pathName, $data)) {
            define('DEBUG_FATAL_ERRORS_FILE', $pathName);
        }
    }
    
    // Параметрите от виртуалното URL за зареждат в $_GET
    core_App::processUrl();
    

    // Зарежда конфигурационните константи
    core_App::loadConfig();


    /**
     * Ще има ли криптиращ протокол?
     * NO - не
     * OPTIONAL - да, където може използвай криптиране
     * MANDATORY - да, използвай задължително
     */
    defIfNot('EF_HTTPS', 'NO');


    // Премахваме всякакви "боклуци", които евентуално може да са се натрупали в изходния буфер
    if (ob_get_contents()) ob_clean();


    // PHP5.4 bugFix
    ini_set('zlib.output_compression', 'Off');

    require_once(EF_APP_PATH . '/setup/Controller.class.php');

    // Файл за лога на сетъп процеса
    define('EF_SETUP_LOG_PATH', EF_TEMP_PATH . '/setupLog_' . md5(__FILE__) . '.html');

    // Стартира Setup, ако в заявката присъства верен SetupKey
    if (isset($_GET['SetupKey'])) {
        require_once(EF_APP_PATH . '/core/Setup.inc.php');
    }


    // Стартира записа в буфера, като по възможност компресира съдържанието
    ob_start();

    // Стартира приложението
    core_App::run();
    
    // Отключваме системата, ако е била заключена в този хит
    core_SystemLock::remove();

    // Край на работата на скрипта
    core_App::shutdown();
} catch (Exception  $e) {
 
    // Отключваме системата, ако е била заключена в този хит
    core_SystemLock::remove();

    if ($e instanceof core_exception_Db && ($link = $e->getDbLink())) {
        if (defined('EF_DB_NAME') && preg_match("/^\w{0,64}$/i", EF_DB_NAME)) {
            
            // Ако базата липсва или е абсолютно празна - отиваме направо към инициализирането
            $db = new core_Db();
            if ($e->isNotExistsDB() || ($db->getDBInfo('ROWS') == 0)) {
                redirect(array('Index', 'SetupKey' => setupKey()));
            }
            
            if ($e instanceof core_exception_Db) {
                // Опитваме се да поправим базата
                $e->repairDB($link);
                
                // Ако грешката в свързана с не-инициализиране на базата, поставяме линк, само ако потребителя е админ или е в dev бранч
                if ($e->isNotInitializedDB()) {
                    try {
                        if ((defined('BGERP_GIT_BRANCH') && BGERP_GIT_BRANCH == 'dev') || haveRole('admin')) {
                            $update = array('Index', 'SetupKey' => setupKey(), 'step' => 2);
                        }
                    } catch (Exception $e) {
                        reportException($e);
                    }
                }
            }
        }
    }
    
    reportException($e, $update, false);
    
    // Изход от скрипта
    core_App::exitScript();
} catch (Throwable  $e) {
    reportException($e, $update, false);
    
    // Изход от скрипта
    core_App::exitScript();
}


/****************************************************************************************
*                                                                                       *
*      Глобални функции-псевдоними на често използвани статични методи на core_App      *
*                                                                                       *
****************************************************************************************/


/**
 * При възникване на изключение показва грешката
 * Ако е зададено да се записва/изпраща прави съответното действие
 *
 * $param $e Exception
 * $param $update NULL|array
 * $param $supressShowing boolean
 */
function reportException($e, $update = null, $supressShowing = true)
{
    $errType = 'PHP EXCEPTION';
    $contex = $_SERVER;
    $errTitle = $e->getMessage();
    $dump = null;
    $errDetail = null;
    $breakFile = $e->getFile();
    $breakLine = $e->getLine();
    $stack = $e->getTrace();

    if (($e instanceof core_exception_Break) || ($e instanceof core_exception_Expect)) {
        $dump = $e->getDump();
        $errType = $e->getType();
    }
    
    $state = core_Debug::prepareErrorState($errType, $errTitle, $errDetail, $dump, $stack, $contex, $breakFile, $breakLine, $update);
    
    if (method_exists($e, 'getType')) {
        $type = $e->getType();
    }
    
    if ($state['httpStatusCode'] == 500) {
        switch ($type){
            
            // core_Exception_Expect
            case 'Изключение':
                $errCode = 500;
                break;
                
                // error
            case 'Грешка':
                $errCode = 501;
                break;
                
                // bp
            case 'Прекъсване':
                $errCode = 503;
                break;
                
                // expect
            case 'Несъответствие':
                $errCode = 505;
                break;
                
                // wp
            case 'Наблюдение':
                $errCode = 150;
                break;
                
                // core_exception_Db
            case 'DB Грешка':
                $errCode = 550;
                break;
                
            default:
                
                if (method_exists($e, 'getCode')) {
                    $errCode = $e->getCode();
                } else {
                    $errCode = '510';
                }
                
                break;
        }
    } else {
        $errCode = $state['httpStatusCode'];
    }
    
    $state['errCode'] = $errCode;
    
    core_Debug::renderErrorState($state, $supressShowing);
}


function getRandomString($length = 14)
{
    return  bin2hex(openssl_random_pseudo_bytes($length));
}


/**
 * Записва стейта на хита в съответния файл
 * 
 * @param string $debugCode
 * @param array $state
 * 
 * @return NULL|string
 */
function logHitState($debugCode = '200', $state = array())
{
    if (defined('DEBUG_FATAL_ERRORS_FILE') && @!Mode::is('stopLoggingDebug')) {
        
        // Максимална стойност за дебъг времената
        $maxDebugTimeCnt = 3000;
        
        $execTime = core_Debug::getExecutionTime();
        
        $data = @file_get_contents(DEBUG_FATAL_ERRORS_FILE);
        
        if ($data) {
            $dataArr = (array)@json_decode($data);
            
            if (!$dataArr) {
                $dataArr = json_last_error();
                $dataArr .= array('jsonData' => ' Unserialize: ' . $data);
            }
        }
        
        $state += (array)$dataArr;
        
        $state['update'] = FALSE;
        
        // Ако броя на дебъг времената е над допустимите оставяме тези в края и в началото
        $debugTimeArr = (array)core_Debug::$debugTime;
        $debugTimeCnt = countR(core_Debug::$debugTime);
        if ($debugTimeCnt > $maxDebugTimeCnt) {
            
            $half = (int) ($maxDebugTimeCnt/2);
            array_slice($debugTimeArr, 0, $half);
            
            $aBegin = (array) array_slice($debugTimeArr, 0, $half, TRUE);
            $aEnd = (array) array_slice($debugTimeArr, $debugTimeCnt - $half, $half, TRUE);
            
            $nDebugTime = array();
            if (!empty($aBegin) && !empty($aEnd)) {
                $moreCnt = $debugTimeCnt - (2*$half);
                
                // Вземаме времето на следващата заявка
                end($aBegin);
                $eKey = key($aBegin);
                $eKey++;
                if ($nElem = $debugTimeArr[$eKey]) {
                    $start = -1;
                    if ($nElem && $nElem->start) {
                        $start = $nElem->start;
                    }
                }
                
                $nDebugTime =  $aBegin + array('more' => (object) array('name' => "+++++ More {$moreCnt} +++++", 'start' => $start)) + $aEnd;
            }
            
            if (!empty($nDebugTime)) {
                $debugTimeArr = $nDebugTime;
            }
        }
        
        $state['_debugTime'] = $debugTimeArr;
        
        $state['_timers'] = core_Debug::$timers;
        
        $state['_executionTime'] = $execTime;
        
        $state['_Ctr'] = $_GET['Ctr'] ? $_GET['Ctr'] : 'Index';
        $state['_Act'] = $_GET['Act'] ? $_GET['Act'] : 'default';
        $state['_dbName'] = EF_DB_NAME;
        $state['_info'] = 'DB: ' . EF_DB_NAME . ' » Ctr: ' . $state['_Ctr'] . ' » Act: ' . $state['_Act'];
        $state['_debugCode'] = $debugCode;
        $state['_cookie'] = $_COOKIE;
        
        if ($state['httpStatusCode']) {
            $state['_info'] .= ' >> Code: ' . $state['httpStatusCode'];
        }
        
        // Подготовка на стека
        if (isset($state['stack'])) {
            list($state['_stack'], $state['_breakFile'], $state['_breakLine']) = core_Debug::analyzeStack($state['stack']);
        }
        
        if (isset($state['_breakFile'], $state['_breakLine'])) {
            $state['_code'] = core_Debug::getCodeAround($state['_breakFile'], $state['_breakLine']);
        }
        
        $data = @json_encode($state);
        
        // Ако възникне JSON грешка, записваме я и сериализираме данните
        if (!$data) {
            $data = json_last_error();
            try {
                $data .= ' Serilize: ' . @serialize($state);
            } catch (Exception $e) {
                $data .= ' MixedToString: ' . core_Type::mixedToString($f);
            }
        }
        
        $cnt = 0;
        
        $debugCodeOrig = $debugCode;
        
        // Ако името съвпада - създаваме нов
        $fileName = pathinfo(DEBUG_FATAL_ERRORS_FILE, PATHINFO_FILENAME);
        
        // В края добавяме и броя на символите/размера
        $fileName .= '_' . strlen($data);
        
        do {
            $pathName = log_Debug::getDebugLogFile($debugCode, $fileName);
            
            $debugCode = $debugCodeOrig . '|' . ++$cnt;
            
            if ($cnt > 100) {
                break;
            }
        } while (@file_exists($pathName));
        
        @file_put_contents($pathName, $data);
        
        return $pathName;
    }
}


/**
 * Тази функция определя пълния път до файла.
 * Като аргумент получава последната част от името на файла
 * Файла се търси в EF_APP_PATH, EF_PRIVATE_PATH
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
 *
 * @param string  $rPath    Релативен път до статичния файл
 * @param string  $qt       Символ за ограждане на резултата
 * @param boolean $absolute Дали резултатното URL да е абсолютно или релативно
 */
function sbf($rPath, $qt = '"', $absolute = false)
{
    return core_Sbf::getUrl($rPath, $qt, $absolute);
}


/**
 * Създава URL от параметрите
 *
 * @param array   $params
 * @param string  $type         Може да бъде relative|absolute|internal
 * @param boolean $protect
 * @param array   $preParamsArr - Масив с имената на параметрите, които да се добавят в pre вместо, като GET
 *
 * @return string
 */
function toUrl($params = array(), $type = 'relative', $protect = true, $preParamsArr = array())
{
    return core_App::toUrl($params, $type, $protect, $preParamsArr);
}


/**
 * Също като toUrl, но връща ескейпнат за html атрибут стринг
 */
function toUrlEsc($params = array(), $type = null, $protect = true, $preParamsArr = array())
{
    return ht::escapeAttr(toUrl($params, $type, $protect, $preParamsArr));
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
function getBoot($absolute = false, $forceHttpHost = false, $addAppName = false)
{
    return core_App::getBoot($absolute, $forceHttpHost, $addAppName);
}


/**
 * @todo Чака за документация...
 */
function getCurrentUrl()
{
    return core_App::getCurrentUrl();
}


/**
 *  Връща масив, който представлява вътрешното представяне на
 * локалното URL подадено като аргумент
 */
function parseLocalUrl($str, $unprotect = true)
{
    return core_App::parseLocalUrl($str, $unprotect);
}


/**
 * Връща масив, който представлява URL-то където трябва да
 * се използва за връщане след изпълнението на текущата задача
 */
function getRetUrl($retUrl = null)
{
    return core_App::getRetUrl($retUrl);
}


/**
 * @todo Чака за документация...
 */
function followRetUrl($url = null, $msg = null, $type = 'notice')
{
    core_App::followRetUrl($url, $msg, $type);
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 *
 *
 */
function redirect($url, $absolute = false, $msg = null, $type = 'notice')
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


/**
 * Функция за завършване на изпълнението на програмата
 *
 * @param bool $sendOutput
 */
function shutdown($sendOutput = true)
{
    core_App::shutdown($sendOutput);
}


/**
 * Дали се намираме в DEBUG режим
 */
function isDebug()
{
    return core_Debug::isDebug();
}


/**
 * Спира обработката и извежда съобщение за грешка или го записв в errorLog
 */
function halt($err)
{
    return core_App::halt($err);
}


/**
 * Точка на прекъсване
 * В продукционен режим предизвиква '500 Internal Server Error'
 * В дебъг режим показва дъмп на аргументите си и всичката останала дебъд информация
 */
function bp()
{
    $dump = func_get_args();
    
    throw new core_exception_Break('500 Прекъсване в сървъра', 'Прекъсване', $dump);
}


/**
 * Следене без прекъсване.
 * Работи по подобен начин на bp(), но без прекъсване, само репортува състоянието
 */
function wp()
{
    static $isReporting;
    
    if (!$isReporting) {
        $isReporting = true;
        core_Debug::$isErrorReporting = true;
        try {
            $dump = func_get_args();
            
            throw new core_exception_Watching('@Наблюдение', 'Наблюдение', $dump);
        } catch (core_exception_Watching $e) {
            reportException($e);
        }
        core_Debug::$isErrorReporting = false;
        $isReporting = false;
    }
}


/**
 * Показва грешка и спира изпълнението.
 */
function error($error = '500 Грешка в сървъра', $dump = null)
{
    $dump = func_get_args();
    array_shift($dump);

    throw new core_exception_Expect($error, 'Грешка', $dump);
}


/**
 * Генерира грешка, ако аргумента не е TRUE
 *
 * @var mixed   $inspect Обект, масив или скалар, който се подава за инспекция
 * @var boolean $condition
 */
function expect($cond)
{
    if (!(boolean) $cond) {
        $dump = func_get_args();
        array_shift($dump);

        throw new core_exception_Expect('500 Грешка в сървъра', 'Несъответствие', $dump);
    }
}


/**
 * Генерира грешка, ако аргумента не е TRUE
 *
 * @var mixed   $inspect Обект, масив или скалар, който се подава за инспекция
 * @var boolean $condition
 */
function expect404($cond)
{
    if (!(boolean) $cond) {
        $dump = func_get_args();
        array_shift($dump);

        throw new core_exception_Expect('404 Грешка в сървъра', 'Несъответствие', $dump);
    }
}


/**
 * Задава стойността(ите) от втория параметър на първия,
 * ако те не са установени
 * @todo: използва ли се тази функция за масиви?
 */
function setIfNot(&$p1, $p2)
{
    $args = func_get_args();
    $args[0] = &$p1;

    return call_user_func_array(array('core_App', 'setIfNot'), $args);
}


/**
 * Дефинира константа, ако преди това не е била дефинирана
 * Ако вторият и аргумент започва с '[#', то изпълнението се спира
 * с изискване за дефиниция на константата
 */
function defIfNot($name, $value = null)
{
    if (!defined($name)) {
        define($name, $value);
    }
}


/**
 * Аналогична фунция на urldecode()
 * Прави опити за конвертиране в UTF-8. Ако не успее връща оригиналното URL.
 *
 * @param string $url
 *
 * @return string
 */
function decodeUrl($url)
{
    return core_Url::decodeUrl($url);
}


/**
 * @todo Чака за документация...
 * @deprecated
 */
function defineIfNot($name, $value)
{
    return core_App::defineIfNot($name, $value);
}


/**
 * Деление на две числа, когато има опасност делителя да е 0
 */
function sDiv($x, $d)
{
    if (empty($d)) {
        if (!empty($x)) {
            // Делим на нула, различно от нула число
            $debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $msg = "Делене на нула `{$x}`/`{$d}`: File = " . $debug[0]['file'] . ' line=' . $debug[0]['line'];
            log_System::add('core_Debug', $msg, null, 'err');
        }

        return 0;
    }

    return $x / $d;
}


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
function haveRole($roles, $userId = null)
{
    return core_Users::haveRole($roles, $userId);
}


/**
 * Превод (Translation) на стринг. Използва core_Lg
 */
function tr($text, $userId = 0, $key = false)
{
    $Lg = core_Cls::get('core_Lg');
    
    return $Lg->translate($text, $userId, $key);
}


/**
 * Транслитериране на стринг. Използва core_Lg
 */
function transliterate($text)
{
    return core_Lg::transliterate($text);
}


/**
 * Връща шаблона на подадения файл през превода
 *
 * @param string $file - Пътя на файла от пакета нататък
 *
 * @return core_Et - Обект
 */
function getTplFromFile($file)
{
    return core_ET::getTplFromFile($file);
}


/**
 * Връща стойност за EF_SALT
 *
 * @return string
 */
function getEF_SALT()
{
    if (defined('EF_SALT')) {
        
        return EF_SALT;
    }
    $parse = parse_url(getSelfURL());
    $fileName = md5($parse['host']);
    if (file_exists("/tmp/". $fileName)) {
        
        return @file_get_contents("/tmp/". $fileName);
    }
    $rndStr = getRandomString();
    @file_put_contents("/tmp/". $fileName, $rndStr);
    
    return $rndStr;
}


/**
 * Връща валиден ключ за оторизация в Setup-а
 *
 * @return string
 */
function setupKey($efSalt = null, $i = 0)
{
    // Сетъп ключ, ако не е зададен
    $salt = ($efSalt)?($efSalt):(getEF_SALT());
    
    $key = md5($salt . '*9fbaknc');
    
    defIfNot('BGERP_SETUP_KEY', $key);
    
    // Валидност средно 250 сек.
    return md5($key . round($i + time() / 10000));
}


/**
 * Проверява дали аргумента е не-празен масив
 */
function countR($arr)
{
    if(!is_array($arr) && !empty($arr)) {
        
        if(defined('BGERP_GIT_BRANCH') && BGERP_GIT_BRANCH == 'dev') {
            print_r($arr);
            die(' - countR - this is not an array');
        } else {

            return 1;
        }
    }

    return empty($arr) ? 0 : count($arr);
}