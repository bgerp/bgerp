<?php

/**
 * Урл за изпращане на СМС-и през Мобио
 */
defIfNot('MOBIO_URL', '');


/**
 * class mobio_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез mobio
 *
 *
 * @category  vendors
 * @package   mobio
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mobio_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез Mobio";
    

    var $configDescription = array (
        'MOBIO_URL' => array('url', 'mandatory'),
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        $managers = array(
            'mobio_SmsDlr',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
     
        // Инсталираме плъгина и за изпращане през Mobio - по подразбиране
        $status = $Plugins->forcePlugin('SMS изпращане', 'mobio_SmsPlugin', 'sms_Sender', 'private');
        
        if($status >0) {
            $html .= "<li >Закачане на Mobio като изпращач на SMS-и";
        } elseif($status == 0) {
            $html .= "<li >Mobio е бил и до сега изпращач на SMS-и";
        } else {
            $html .= "<li >Mobio не е закачен за изпращач на SMS-и, защото има друг";
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('mobio_SmsPlugin');
        $html .= "<li>Премахване на Mobio като изпращач на SMS-и";
        
        return $html;

    }
}