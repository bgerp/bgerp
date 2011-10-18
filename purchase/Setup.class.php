<?php
/**
 *  Покупки - инсталиране / деинсталиране
 *
 * @category   BGERP
 * @package    purchase
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class purchase_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'purchase_Offers';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Покупки - доставки на стоки, материали и консумативи";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'purchase_Offers',
        	'purchase_Requests',
        	'purchase_Debt',
        );
        
        // Роля за power-user на този модул
        $role = 'purchase';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(3, 'Логистика', 'Доставки', 'purchase_Offers', 'default', "{$role}, admin");
        
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
