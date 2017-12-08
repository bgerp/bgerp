<?php


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
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Setup extends core_ProtoSetup
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
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'bank_Accounts',
        'bank_OwnAccounts',
        'bank_IncomeDocuments',
        'bank_SpendingDocuments',
        'bank_InternalMoneyTransfer',
        'bank_ExchangeDocument',
        'bank_PaymentOrders',
        'bank_CashWithdrawOrders',
        'bank_DepositSlips',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('bank', 'seePrice'),
    		array('bankMaster', 'bank'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(2.2, 'Финанси', 'Банки', 'bank_OwnAccounts', 'default', "bank, ceo"),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "bank_reports_AccountImpl";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}