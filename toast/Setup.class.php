<?php

/**
 * Версията на toast message
 */
defIfNot('TOAST_MESSAGE_VERSION', '0.3.0f');


/**
 * Инсталиране/Деинсталиране на плъгина за показване на статъс съобщения
 *
 * @category  vendors
 * @package   toast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class toast_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Показване статус съобщенията в тост стил";
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'TOAST_MESSAGE_VERSION' => array ('enum(0.3.0f)', 'caption=Версия на `ToastMessage`->Версия'),                
    );
    
    
    /**
     * Пътища до CSS файлове
     */
    var $commonCSS = "toast/[#TOAST_MESSAGE_VERSION#]/resources/css/jquery.toastmessage.css";
    
    
    /**
     * Пътища до JS файлове
     */
    var $commonJS = "toast/[#TOAST_MESSAGE_VERSION#]/javascript/jquery.toastmessage.js";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Toast съобщения', 'toast_Toast', 'status_Messages', 'private');
        
        return $html;
    }
}

