<?php


/**
 * JS файловете
 */
defIfNot('COMPACTOR_JS_FILES', 'jquery/1.7.1/jquery.min.js, js/efCommon.js, toast/0.3.0f/javascript/jquery.toastmessage.js');


/**
 * CSS файловете
 */
defIfNot('COMPACTOR_CSS_FILES', 'css/common.css, css/Application.css, toast/0.3.0f/resources/css/jquery.toastmessage.css');


/**
 * 
 *
 * @category  compactor
 * @package   toast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class compactor_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "";
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'COMPACTOR_JS_FILES' => array ('varchar', 'caption=JS файлове->Път до файлове'),                
        'COMPACTOR_CSS_FILES' => array ('varchar', 'caption=CSS файлове->Път до файлове'),                
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Компактиране на файлове', 'compactor_Plugin', 'page_Html', 'private');
        
        return $html;
    }
}

