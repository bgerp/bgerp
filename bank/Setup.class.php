<?php


/**
 * Наши банкови сметки за плащания с пощенски паричен превод
 */
defIfNot('BANK_POSTAL_TRANSFER_ACCOUNTS', '');


/**
 * class bank_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Bank
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bank_interface_PosDummy';


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
        'migrate::recontoExchangeDocuments2601',
        'migrate::recontoDocs2620',
        'migrate::fixPaymentUntil2623',
        'migrate::setPostalTransferAccounts2645',
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('bank', 'seePrice'),
        array('bankMaster', 'bank'),
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BANK_POSTAL_TRANSFER_ACCOUNTS' => array('keylist(mvc=bank_OwnAccounts,select=title)', 'caption=Пощенски паричен превод->Банкови сметки'),
    );


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.2, 'Финанси', 'Банки', 'bank_OwnAccounts', 'default', 'bank, ceo, bankAll'),
    );


    /**
     * Реконтиране на банковите документи
     */
    public function recontoExchangeDocuments2601()
    {
        $eurozoneDate = acc_Setup::getEurozoneDate();
        $bQuery = bank_ExchangeDocument::getQuery();
        $bQuery->where("#state = 'active' AND #createdOn >= '{$eurozoneDate}' AND #createdBy = -1");
        while ($bRec = $bQuery->fetch()) {
            acc_Journal::reconto($bRec->containerId);
        }
    }


    /**
     * Реконтиране на ПКО и ВКТ
     */
    public function recontoDocs2620()
    {
        $callOn = dt::addSecs(120);
        $data = (object)array('class' => 'bank_InternalMoneyTransfer', 'from' => '2026-01-01', 'fields' => array(), 'lastId' => null);
        core_CallOnTime::setCall('acc_Journal', 'recontoActiveDocuments', $data, $callOn);
    }


    /**
     * Миграция на замърсените РБД-та
     */
    public function fixPaymentUntil2623()
    {
        $Spending = cls::get('bank_SpendingDocuments');
        $earlyPaymentUntilColName = str::phpToMysqlName('earlyPaymentUntil');
        $query = "UPDATE {$Spending->dbTableName} SET {$earlyPaymentUntilColName} = NULL WHERE {$earlyPaymentUntilColName} = '0000-00-00'";
        $Spending->db->query($query);
    }


    /**
     * Сметките с "ППП" в името стават сметки за пощенски паричен превод, ако не са зададени
     */
    public function setPostalTransferAccounts2645()
    {
        if (!empty(core_Packs::getConfigValue('bank', 'BANK_POSTAL_TRANSFER_ACCOUNTS'))) return;

        $accounts = array();
        $query = bank_OwnAccounts::getQuery();
        $query->like('title', 'ППП');
        $query->show('id');
        while ($rec = $query->fetch()) {
            $accounts[$rec->id] = $rec->id;
        }

        if (!countR($accounts)) return;

        core_Packs::setConfig('bank', array('BANK_POSTAL_TRANSFER_ACCOUNTS' => keylist::fromArray($accounts)));
    }
}
