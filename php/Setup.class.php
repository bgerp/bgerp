<?php



/**
 * class php_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета php
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class php_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'php_Formater';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Разхубавяване на кода; Създаване на конфигурационни файлове";
    

    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'php_Formater',
            'php_Const',
            'php_Interfaces',
            'php_Test',
           
        );
        $role = 'developer';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
               
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(4, 'Разработка', 'Форматиране', 'php_Formater', 'default', "{$role}, admin");
       
        
        return $html;
    }
    
    
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