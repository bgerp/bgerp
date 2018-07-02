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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'bank_OwnAccounts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Банкови сметки, операции и справки';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'bank_Accounts',
        'bank_OwnAccounts',
        'bank_IncomeDocuments',
        'bank_SpendingDocuments',
        'bank_InternalMoneyTransfer',
        'bank_ExchangeDocument',
        'bank_PaymentOrders',
        'bank_CashWithdrawOrders',
        'bank_DepositSlips',
        'bank_Register',
        'migrate::repairSerchKeywords',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
            array('bank', 'seePrice'),
            array('bankMaster', 'bank'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.2, 'Финанси', 'Банки', 'bank_OwnAccounts', 'default', 'bank, ceo'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bank_reports_AccountImpl';
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция за регенериране на ключовите думи
     */
    public static function repairSerchKeywords()
    {
        $callOn = dt::addSecs(1200);
        core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', 'bank_IncomeDocuments', $callOn);
        
        $callOn = dt::addSecs(1260);
        core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', 'bank_SpendingDocuments', $callOn);
    }
}
