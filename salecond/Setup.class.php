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
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Setup  extends core_ProtoSetup
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
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        	'salecond_PaymentMethods',
        	'salecond_DeliveryTerms',
        	'salecond_Parameters',
        	'salecond_ConditionsToCustomers',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'salecond';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.9, 'Търговия', 'Терминология', 'salecond_DeliveryTerms', 'default', "salecond, ceo"),
        );

        
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