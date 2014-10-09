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
class php_Setup extends core_ProtoSetup
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
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'php_Formater',
            'php_Const',
            'php_Interfaces',
            'php_Test',
           
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'developer';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(4, 'Разработка', 'Форматиране', 'php_Formater', 'default', "developer, admin"),
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