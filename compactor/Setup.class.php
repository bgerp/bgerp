<?php


/**
 * JS файловете
 */
defIfNot('COMPACTOR_JS_FILES', 'js/efCommon.js, jquery/1.7.1/jquery.min.js, toast/0.3.0f/javascript/jquery.toastmessage.js');


/**
 * CSS файловете
 */
defIfNot('COMPACTOR_CSS_FILES', 'css/common.css, css/Application.css, toast/0.3.0f/resources/css/jquery.toastmessage.css');


/**
 * Временната папка
 */
defIfNot('COMPACTOR_TEMP_PATH', EF_TEMP_PATH . "/compactor");


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
        'COMPACTOR_JS_FILES' => array ('varchar', 'caption=CSS файлове->Път до файлове'),                
        'COMPACTOR_CSS_FILES' => array ('varchar', 'caption=JS файлове->Път до файлове'),                
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
        
    	$conf = core_Packs::getConfig('compactor');
    	
        //Създаваме рекурсивно папката
        $d = $conf->COMPACTOR_TEMP_PATH;
        
        $caption = 'За временни файлове на compactor';
        
        // Ако директорията липсва, правим опит да я създадем
        if(!is_dir($d)) {
            if(mkdir($d, 0777, TRUE)) {
                $html .= "<li style='color:green;'> Директорията <b>{$d}</b> е създадена ({$caption})</li>";
            } else {
                $html .= "<li style='color:red;'> Директорията <b>{$d}</b> не може да бъде създадена ({$caption})</li>";
            }
        } else {
            $html .= "<li> Директорията <b>{$d}</b> съществува от преди ({$caption})</li>";
        }
        
        return $html;
    }
}

