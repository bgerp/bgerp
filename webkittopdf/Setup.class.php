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
 * class sms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със СМС-и
 *
 *
 * @category  bgerp
 * @package   sms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class webkittopdf_Setup
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
        
           'WEBKIT_TO_PDF_BIN' => array ('varchar', 'mandatory'),
    
           'WEBKIT_TO_PDF_XVFB_RUN'   => array ('varchar'),
    
           'WEBKIT_TO_PDF_SCREEN_WIDTH'   => array ('int'),
    
           'WEBKIT_TO_PDF_SCREEN_HEIGHT'   => array ('int'),
    
           'WEBKIT_TO_PDF_SCREEN_BIT'   => array ('int'),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'webkittopdf_Converter'
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
                
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    }
}