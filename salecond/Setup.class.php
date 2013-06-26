<?php


/**
 * class salecond_Setup
 *
 * Инсталиране/Деинсталиране на
 * админ. мениджъри с общо предназначение
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'salecond_DeliveryTerms';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'crm=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Условия на продажба";
        
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'salecond_PaymentMethods',
        	'salecond_PaymentMethodDetails',
        	'salecond_DeliveryTerms',
        	'salecond_Parameters',
        	'salecond_ConditionsToCustomers',
        );
        
        // Роля за power-user на този модул
        $role = 'salecond';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3.9, 'Търговия', 'Терминология', 'salecond_DeliveryTerms', 'default', "{$role}, admin");
        
        $html .= $this->loadSetupData();
        
        return $html;
    }
    
    
    /**
     * Инициализране на началните данни
     */
    function loadSetupData()
    {
    	// Зареждане на други условия за продажба от csv файл
    	$html .= salecond_Parameters::setup();
    	
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