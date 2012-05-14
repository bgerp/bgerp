<?php



/**
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class crm_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'crm_Companies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Визитник и управление на контактите";
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Константи за инициализиране на таблицата с контактите
            'BGERP_OWN_COMPANY_ID' => array ('int', 'mandatory'),
    
            // Име на собствената компания (тази за която ще работи bgERP)
            'BGERP_OWN_COMPANY_NAME' => array ('text', 'mandatory'),
    
            // Държавата на собствената компания (тази за която ще работи bgERP)
            'BGERP_OWN_COMPANY_COUNTRY' => array ('text', 'mandatory')
        );
    
    /**
     * Скрипт за инсталиране
     */
    function install()
    {
        $managers = array(
            'crm_Groups',
            'crm_Companies',
            'crm_Persons',
            'crm_Locations',
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
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}