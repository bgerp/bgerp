<?php

/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category  bgerp
 * @package   opit
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class myself_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'myself_Codebase';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Code analysis";
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'myself_Codebase'

        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'powerUser';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
//    var $menuItems = array(
//            array(3.995, 'Анализ', 'Анализ', 'myself_Codebase', 'default', "powerUser"),
//        );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
              

        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}