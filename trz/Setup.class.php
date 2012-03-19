<?php



/**
 * ТРЗ - инсталиране / деинсталиране
 *
 *
 * @category  all
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trz_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'trz_Salaries';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Труд и работна заплата";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'trz_Salaries',
            'trz_Bonuses',
            'trz_Sickdays',
            'trz_Leaves',
            'trz_Fines',
            'trz_Payrolls',
        );
        
        // Роля за power-user на този модул
        $role = 'budget';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(2, 'Персонал', 'ТРЗ', 'trz_Salaries', 'default', "{$role}, admin");
        
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