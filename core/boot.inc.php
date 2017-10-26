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
if (version_compare(phpversion(), '5.3.0') < 0) {
    echo ('Необходимо е php 5.3+!');
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
require_once(EF_APP_PATH . "/core/Cls.class.php");


// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . "/core/App.class.php");

// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . "/core/BaseClass.class.php");

// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_APP_PATH . "/core/Html.class.php");

// Прихващаме грешките
core_Debug::setErrorWaching();

try {
    // Инициализиране на системата
    core_App::initSystem();


    // Параметрите от виртуалното URL за зареждат в $_GET
    core_App::processUrl();


    // Зарежда конфигурационните константи
    core_App::loadConfig();

    if (core_App::isLocked()) {
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
        
            return array($resObj);
        } else {
            // не е ajax - връщаме културно съобщение, че системата е временно недостъпна
            // TODO: връщаме културно съобщение, че системата е временно недостъпна
        }
    }
    
    // Премахваме всякакви "боклуци", които евентуално може да са се натрупали в изходния буфер
    ob_clean();


    // PHP5.4 bugFix
    ini_set('zlib.output_compression', 'Off');

    require_once(EF_APP_PATH . "/setup/Controller.class.php");

    // Файл за лога на сетъп процеса
    define(EF_SETUP_LOG_PATH, EF_TEMP_PATH . '/setupLog_' . md5(__FILE__) . '.html');

    // Стартира Setup, ако в заявката присъства верен SetupKey
    if (isset($_GET['SetupKey'])) {
        require_once(EF_APP_PATH . "/core/Setup.inc.php");
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

    if($e instanceof core_exception_Db && ($link = $e->getDbLink())) { 
        
        if(defined('EF_DB_NAME') && preg_match("/^\w{0,64}$/i", EF_DB_NAME)) {
            
            // 1. Ако няма такава база, създаваме я и редирректваме към инсталация
            if ($e->isNotExistsDB()) {
                // Опитваме се да създадем базата и редиректваме към сетъп-а
                try {
                    mysqli_query($link, "CREATE DATABASE " . EF_DB_NAME);
                } catch(Exception $e) {
                    reportException($e);
                }
            }
            
            // Ако базата е абсолютно празна - ще се отиде направо към инициализирането
            // Ако има поне един файл, няма да се отиде към инициализиране
            if(core_Db::databaseEmpty()) {
                redirect(array('Index', 'SetupKey' => setupKey()));
            }
            
            if ($e instanceof core_exception_Db) {
                // Опитваме се да поправим базата
                $e->repairDB($link);
                
                // Ако грешката в свързана с не-инициализиране на базата, поставяме линк, само ако потребителя е админ или е в dev бранч
                if($e->isNotInitializedDB()) {
                
                    try {
                        if((defined('BGERP_GIT_BRANCH') && BGERP_GIT_BRANCH == 'dev') || haveRole('admin')) {
                            $update =  array('Index', 'SetupKey' => setupKey(), 'step' => 2);
                        }
                    } catch(Exception $e) {
                        reportException($e);
                    }
                }
            }
        }
    }
    
    reportException($e, $update, FALSE);
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
function reportException($e, $update = NULL, $supressShowing = TRUE)
{
    $errType   = 'PHP EXCEPTION';
    $contex    = $_SERVER;
    $errTitle  = $e->getMessage();
    $dump      = NULL;
    $errDetail = NULL;
    $breakFile = $e->getFile();
    $breakLine = $e->getLine();
    $stack     = $e->getTrace();

    if (($e instanceOf core_exception_Break) || ($e instanceOf core_exception_Expect)) {
        $dump = $e->getDump();
        $errType = $e->getType();
    }
    
    $state = core_Debug::prepareErrorState($errType, $errTitle, $errDetail, $dump, $stack, $contex, $breakFile, $breakLine, $update);

    core_Debug::renderErrorState($state, $supressShowing);
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
 * @param string $rPath Релативен път до статичния файл
 * @param string $qt    Символ за ограждане на резултата
 * @param boolean $absolute Дали резултатното URL да е абсолютно или релативно
 */
function sbf($rPath, $qt = '"', $absolute = FALSE)
{
    return core_Sbf::getUrl($rPath, $qt, $absolute);
}


/**
 * Създава URL от параметрите
 *
 * @param array $params
 * @param string $type Може да бъде relative|absolute|internal
 * @param boolean $protect
 * @param array $preParamsArr - Масив с имената на параметрите, които да се добавят в pre вместо, като GET
 * 
 * @return string
 */
function toUrl($params = array(), $type = 'relative', $protect = TRUE, $preParamsArr = array())
{
    return core_App::toUrl($params, $type, $protect, $preParamsArr);
}


/**
 * Също като toUrl, но връща ескейпнат за html атрибут стринг
 */
function toUrlEsc($params = array(), $type = NULL, $protect = TRUE, $preParamsArr = array())
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
function getBoot($absolute = FALSE)
{
    return core_App::getBoot($absolute);
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
function parseLocalUrl($str, $unprotect = TRUE)
{
    return core_App::parseLocalUrl($str, $unprotect);
}


/**
 * Връща масив, който представлява URL-то където трябва да
 * се използва за връщане след изпълнението на текущата задача
 */
function getRetUrl($retUrl = NULL)
{
    return core_App::getRetUrl($retUrl);
}


/**
 * @todo Чака за документация...
 */
function followRetUrl($url = NULL, $msg = NULL, $type = 'notice')
{
    core_App::followRetUrl($url, $msg, $type);
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 *
 *
 */
function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'notice')
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
function shutdown($sendOutput = TRUE)
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
    try {
        $dump = func_get_args();
    
        throw new core_exception_Watching('@Наблюдение', 'Наблюдение', $dump);
    } catch (core_exception_Watching $e) {
        reportException($e);
    }
}


/**
  * Показва грешка и спира изпълнението.
  */
function error($error = '500 Грешка в сървъра', $dump = NULL)
{   
    $dump =func_get_args(); 
    array_shift($dump);

    throw new core_exception_Expect($error, 'Грешка', $dump);
}



/**
 * Генерира грешка, ако аргумента не е TRUE
 * 
 * @var mixed $inspect Обект, масив или скалар, който се подава за инспекция
 * @var boolean $condition
 */
function expect($cond)
{   
    if (!(boolean)$cond) {

        $dump = func_get_args();
        array_shift($dump);

        throw new core_exception_Expect('500 Грешка в сървъра', 'Несъответствие', $dump);
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
function defIfNot($name, $value = NULL)
{
    if(!defined($name)) {
        define($name, $value);
    }
}


/**
 * Аналогична фунция на urldecode()
 * Прави опити за конвертиране в UTF-8. Ако не успее връща оригиналното URL.
 * 
 * @param URL $url
 * 
 * @return URL
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
    if(empty($d)) {
        if(!empty($x)) {
            // Делим на нула, различно от нула число
            $debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $msg = "Делене на нула `{$x}`/`{$d}`: File = " . $debug[0]['file'] . ' line=' . $debug[0]['line'];
            log_System::add('core_Debug', $msg, NULL, 'err');
        }

        return 0;
    }

    return $x/$d;
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
function haveRole($roles, $userId = NULL)
{
    return core_Users::haveRole($roles, $userId);
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
 * Връща валиден ключ за оторизация в Setup-а
 *
 * @return string
 */
function setupKey($efSalt = null)
{
	// Сетъп ключ, ако не е зададен
	$salt = ($efSalt)?($efSalt):(EF_SALT);
	
	$key = md5($salt . '*9fbaknc');
	
	defIfNot('BGERP_SETUP_KEY', $key);
	
	// Валидност средно 250 сек.
	return md5($key . round(time()/10000));
}
 
