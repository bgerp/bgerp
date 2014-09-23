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

require_once(EF_EF_PATH . '/core/exception/Expect.class.php');


require_once(EF_EF_PATH . '/core/Debug.class.php');


// core_Debug::setErrorWaching();


// Зареждаме 'CLS' класа за работа с класове
require_once(EF_EF_PATH . "/core/Cls.class.php");


// Зареждаме 'APP' класа с помощни функции за приложението
require_once(EF_EF_PATH . "/core/App.class.php");


try {
    // Инициализиране на системата
    core_App::initSystem();


    // Параметрите от виртуалното URL за зареждат в $_GET
    core_App::processUrl();


    // Зарежда конфигурационните константи
    core_App::loadConfig();


    // Премахваме всякакви "боклуци", които евентуално може да са се натрупали в изходния буфер
    ob_clean();


    // PHP5.4 bugFix
    ini_set('zlib.output_compression', 'Off');


    /**
     * Стартира Setup, ако в заявката присъства верен SetupKey
     */
    if (isset($_GET['SetupKey'])) {
        require_once(EF_EF_PATH . "/core/Setup.inc.php");
    }


    // Стартира записа в буфера, като по възможност компресира съдържанието
    ob_start();


    // Стартира приложението
    core_App::run();


    // Край на работата на скрипта
    core_App::shutdown();

} catch (core_exception_Expect $e) {
 
    if(is_array($e->debug) && isset($e->debug['mysqlErrCode']) && $e->debug['mysqlErrCode'] == 1146 && core_Db::databaseEmpty()) {

        // При празна база редиректваме безусловно към сетъп-а
        redirect(array('Index', 'SetupKey' => setupKey()));

    } elseif(is_array($e->debug) && isset($e->debug['mysqlErrCode']) && $e->debug['mysqlErrCode'] == 1049) {

        // Създаваме и редиректваме
        mysql_query("CREATE DATABASE " . EF_DB_NAME);
        redirect(array('Index', 'SetupKey' => setupKey()));

    } else { 
        $e->showMessage(); 
    }

}



/****************************************************************************************
*                                                                                       *
*      Глобални функции-псевдоними на често използвани статични методи на core_App      *
*                                                                                       *
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
    core_App::shutdown();
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


/**
 * Точка на прекъсване. Има неограничен брой аргументи.
 * Показва съдържанието на аргументите си и текущия стек
 * Сработва само в режим на DEBUG
 */
function bp()
{
    call_user_func_array(array('core_App', 'bp'), func_get_args());
}


/**
  * Показва грешка и спира изпълнението.
  */
function error($error = NULL, $dump = NULL)
{   
    throw new core_exception_Expect($error, $dump, 'Грешка');
}



/**
 * Генерира грешка, ако аргумента не е TRUE
 * 
 * @var mixed $inspect Обект, масив или скалар, който се подава за инспекция
 * @var boolean $condition
 */
function expect($cond, $error = NULL, $dump = NULL)
{   
    if (!(boolean)$cond) {

        $args = func_get_args();
        unset($args[0]);

        if(!is_string($error)) {
            $msg = "Exception";
        } else {
            $msg = $error;
            unset($args[1]);
        }

    	throw new core_exception_Expect($msg, $args, 'Несъответствие');
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
    return core_App::defIfNot($name, $value);
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
function setupKey()
{
	// Сетъп ключ, ако не е зададен
	defIfNot('BGERP_SETUP_KEY', md5(EF_SALT . '*9fbaknc'));

	// Валидност средно 50 сек.
	return md5(BGERP_SETUP_KEY . round(time()/100));
}


