<?php


/**
 * Клас 'change_Setup' - 
 *
 * @category  vendors
 * @package   chnage
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class change_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'change_Log';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'defaul';
    
    
    /**
     * Описание на модула
     */
    var $info = "Промени";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'change_Log',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
}