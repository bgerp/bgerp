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
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Показване статус съобщенията в тост стил';
    

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TOAST_MESSAGE_VERSION' => array('enum(0.3.0f)', 'caption=Версия на `ToastMessage`->Версия'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Toast съобщения', 'toast_Toast', 'status_Messages', 'private');
        
        return $html;
    }


    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('toast');
        
        return 'toast/' . $conf->TOAST_MESSAGE_VERSION . '/javascript/jquery.toastmessage.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('toast');
        
        return 'toast/' . $conf->TOAST_MESSAGE_VERSION . '/resources/css/jquery.toastmessage.css';
    }
}
