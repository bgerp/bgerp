<?php

defIfNot('CASH_PKO_CREDIT_ACC_DEF', '4');

defIfNot('CASH_CASE_ACCOUNT', '501');

defIfNot('CASH_PKO_CREDIT_ACC', '|95|105|');

defIfNot('CASH_RKO_DEBIT_ACC', '|96|98|105|');

/**
 * class cash_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Case
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cash_Cases';
    
    
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
    var $info = "Каси, кешови операции и справки";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
				
			'CASH_PKO_CREDIT_ACC' => array ("acc_type_Accounts(root=4,maxColumns=2)"),
	
			'CASH_RKO_DEBIT_ACC' => array ("acc_type_Accounts(root=4,maxColumns=2)"),
	);
	
	
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'cash_Cases',
            'cash_Documents',
        	'cash_Pko',
        	'cash_Rko',
        );
        
        // Роля за power-user на този модул
        $role = 'cash';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Финанси', 'Каси', 'cash_Cases', 'default', "{$role}, admin");
        
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