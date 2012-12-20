<?php
defIfNot('BANK_PO_CREDIT_ACC_DEF', '4');

// При Платежно нареждане кои сметки дебитираме и кредитираме
defIfNot('BANK_PO_CREDIT_SYSID', '503');
defIfNot('BANK_PO_DEBIT_ACC', '|96|98|105|');

// При Вносна бележка кои сметки дебитираме и кредитираме
defIfNot('BANK_VB_CREDIT_SYSID', '422');
defIfNot('BANK_VB_DEBIT_ACC', '|96|98|105|148|');


defIfNot('BANK_CASE_SYSID', '501');
defIfNot('BANK_NR_DEBIT_ACC', '|96|98|105|148|');



/**
 * class bank_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Bank
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'bank_OwnAccounts';
    
    
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
    var $info = "Банкови сметки, операции и справки";
    
    
    var $configDescription = array(
				
			'BANK_PO_DEBIT_ACC' => array ("acc_type_Accounts(root=4,maxColumns=2)"),
			
    		'BANK_NR_DEBIT_ACC' => array ("acc_type_Accounts(root=4,maxColumns=2)"),
    
    		'BANK_VB_DEBIT_ACC' => array ("acc_type_Accounts(root=4,maxColumns=2)"),
	);
	
	
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'bank_Accounts',
            'bank_OwnAccounts',
        	'bank_PaymentOrders',
            'bank_CashWithdrawOrders',
        	'bank_DepositSlips',
            'bank_PaymentMethods',
            'bank_PaymentMethodDetails'
        );
        
        // Роля за power-user на този модул
        $role = 'bank';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.2, 'Финанси', 'Банки', 'bank_OwnAccounts', 'default', "{$role}, admin");
        
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