<?php


/**
 * Изпълнимия файл на програмата
 */
defIfNot('WEBKIT_TO_PDF_BIN', "wkhtmltopdf");


/**
 * Указва дали да се изпълни помощната програма (xvfb-run)
 */
defIfNot('WEBKIT_TO_PDF_XVFB_RUN', 'yes');


/**
 * xvfb-run - Ширина на екрана
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_WIDTH', "640");


/**
 * xvfb-run - Височина на екрана
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_HEIGHT', "480");


/**
 * xvfb-run - Дълбочина на цвета
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_BIT', "16");


/**
 * wkhtmltopdf да използва ли JS
 */
defIfNot('WEBKIT_TO_PDF_USE_JS', '');


/**
 * Колко милисекунди да изчака, докато javascript завърши
 * --javascript-delay
 */
defIfNot('WEBKIT_TO_PDF_JS_DELAY', 1000);


/**
 * Да се спира ли JS скрипта, който се зарежда бавно
 * --no-stop-slow-scripts
 */
defIfNot('WEBKIT_TO_PDF_JS_STOP_SLOW_SCRIPT', 'no');


/**
 * Да се използва PRINT медиа тип, вместо SCREEN
 * --print-media-type
 */
defIfNot('WEBKIT_TO_PDF_USE_PRINT_MEDIA_TYPE', 'no');


/**
 * Да се използва PRINT медиа тип, вместо SCREEN
 * --grayscale
 */
defIfNot('WEBKIT_TO_PDF_USE_GRAYSCALE', 'no');


/**
 * Енкодинг на входящия файл
 * --encoding
 */
defIfNot('WEBKIT_TO_PDF_INPUT_ENCODING', '');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри за конвертиране в pdf
 *
 * @category  bgerp
 * @package   webkittopdf
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class webkittopdf_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    // var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    // var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Конвертиране .html => .pdf";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
           
           'WEBKIT_TO_PDF_USE_PRINT_MEDIA_TYPE' => array ('enum(no=Не, yes=Да)', 'caption=Да се използва PRINT медиа тип, вместо SCREEN'),
           
           'WEBKIT_TO_PDF_USE_GRAYSCALE' => array ('enum(no=Не, yes=Да)', 'caption=PDF файловете да се генерират в grayscale'),
        
           'WEBKIT_TO_PDF_INPUT_ENCODING' => array ('varchar', 'caption=Енкодинг на входящия файл'),
    
           'WEBKIT_TO_PDF_USE_JS' => array ('enum( , no=Не, yes=Да)', 'caption=Работа с JS->Да се използва, allowEmpty'),
    
           'WEBKIT_TO_PDF_JS_STOP_SLOW_SCRIPT' => array ('enum(no=Не, yes=Да)', 'caption=Работа с JS->Спиране на бавен скрипт'),
           
           'WEBKIT_TO_PDF_JS_DELAY' => array ('int', 'caption=Работа с JS->Време за изчакване, unit=ms'),
    
           'WEBKIT_TO_PDF_XVFB_RUN' => array ('enum(no=Не, yes=Да)', 'caption=Работа с xvfb-run->Да се използва'),
    
           'WEBKIT_TO_PDF_SCREEN_WIDTH' => array ('int', 'caption=Работа с xvfb-run->Широчина на екрана, unit=px'),
    
           'WEBKIT_TO_PDF_SCREEN_HEIGHT' => array ('int', 'caption=Работа с xvfb-run->Височина на екрана, unit=px'),
    
           'WEBKIT_TO_PDF_SCREEN_BIT' => array ('int', 'caption=Работа с xvfb-run->Дълбочина на цвета, unit=px'),
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'webkittopdf_Converter'
        );
        
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	// Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}