<?php


/**
 * Изпълнимия файл на програмата
 */
defIfNot('WEBKIT_TO_PDF_BIN', "/usr/bin/wkhtmltopdf");


/**
 * Оказва дали да се изпълни помощната програма (xvfb-run)
 */
defIfNot('WEBKIT_TO_PDF_XVFB_RUN', TRUE);


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
        
           'WEBKIT_TO_PDF_BIN' => array ('varchar', 'mandatory, caption=Изпълнимия файл на програмата->Път до файла'),
    
           'WEBKIT_TO_PDF_XVFB_RUN'   => array ('varchar', 'caption=Оказва дали да се изпълни помощната програма (xvfb-run)->Да/Не'),
    
           'WEBKIT_TO_PDF_SCREEN_WIDTH'   => array ('int', 'caption=Ширина на екрана->Число'),
    
           'WEBKIT_TO_PDF_SCREEN_HEIGHT'   => array ('int', 'caption=Височина на екрана->Число'),
    
           'WEBKIT_TO_PDF_SCREEN_BIT'   => array ('int', 'caption=Дълбочина на цвета->Число'),
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