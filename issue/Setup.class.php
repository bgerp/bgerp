<?php


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с issue модула
 *
 * @category  bgerp
 * @package   issue
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class issue_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'issue_Document';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Сигнали";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Инсталиране на мениджърите
        $managers = array(
            'issue_Document',
            'issue_Components',
            'issue_Systems',
            'issue_Types',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.14, 'Обслужване', 'Поддръжка', 'issue_Document', 'default', "user");
        
        $html .= issue_Types::loadData();
        
        $role = 'issue';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
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