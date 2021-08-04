<?php


/**
 * class cash_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Case
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cash_Cases';
    
    
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
    public $info = 'Каси, кешови операции и справки';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cash_Cases',
        'cash_Pko',
        'cash_Rko',
        'cash_InternalMoneyTransfer',
        'cash_ExchangeDocument',
        'cash_NonCashPaymentDetails',
        'migrate::recontoDocuments',
        'migrate::updateCashDocuments'
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'cash_reports_NonCashPaymentReports';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('cash', 'seePrice'),
        array('cashMaster', 'cash'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.2, 'Финанси', 'Каси', 'cash_Cases', 'default', 'cash, ceo'),
    );


    /**
     * Миграция за реконтиране на документи
     */
    public function recontoDocuments()
    {
        deals_Setup::recontoPaymentDocuments(array('cash_Pko', 'cash_Rko'));
    }


    /**
     * Миграция за обновяване на ЕН-та
     */
    public function updateCashDocuments()
    {
        foreach (array('cash_Pko', 'cash_Rko') as $mvc){
            deals_InvoicesToDocuments::migrateContainerIds($mvc);
        }
    }
}
