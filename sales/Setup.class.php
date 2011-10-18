<?php
/**
 *  Покупки - инсталиране / деинсталиране
 *
 * @category   BGERP
 * @package    sales
 * @author     Милен Георгиев
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class sales_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'sales_Deals';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Продажби на продукти и стоки";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'sales_Deals',
            'sales_Invoices',
           	'sales_InvoiceDetails',
       );
        
        // Роля за power-user на този модул
        $role = 'sales';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(2, 'Продажби', 'Сделки', 'sales_Deals', 'default', "{$role}, admin");
        
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
