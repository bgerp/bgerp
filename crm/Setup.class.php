<?php


/**
 * Клас 'crm_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    crm
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class crm_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'contacts_Contacts';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Скрип за инсталиране
     */
    function install()
    {
        $managers = array(
            'crm_Companies',
            'crm_Persons',
            'crm_Groups',
        );
        
        // Роля за power-user на този модул
        $role = 'crm';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Визитник', 'Контакти', 'crm_Companies', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     * Деинсталиране
     */
    function deinstall()
    {
        return "Пакета CRM е деинсталиран";
    }
}