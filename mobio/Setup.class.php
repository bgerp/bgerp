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
            'mobio_SMS',
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
        
        return '';
    }
}