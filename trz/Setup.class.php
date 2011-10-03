<?php
/**
 *  ТРЗ - инсталиране / деинсталиране
 *
 * @category   BGERP
 * @package    trz
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class trz_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'trz_Salaries';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Труд и работна заплата";

    
    /**
     *  Инсталиране на пакета
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
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}