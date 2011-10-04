<?php
/**
 *  Бюджетиране - инсталиране / деинсталиране
 *
 * @category   BGERP
 * @package    budget
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class budget_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'budget_Assets';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
  
   /**
     * Описание на модула
     */
    var $info = "Финансово бюджетиране";


    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'budget_Assets',
        	'budget_IncomeExpenses',
        	'budget_Balances',
        	'budget_Reports',
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
        
        $html .= $Menu->addItem(2, 'Финанси', 'Бюджетиране', 'budget_Assets', 'default', "{$role}, admin");
        
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