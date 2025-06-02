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
        'cash_InternalMoneyTransferDetails',
        'migrate::updateNonCashDetails2521'
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
        array(2.3, 'Финанси', 'Каси', 'cash_Cases', 'default', 'cash, ceo, cashAll'),
    );


    /**
     * Миграция на модела за безкасовите плащания към ПКО
     */
    public function updateNonCashDetails2521()
    {
        $NonCash = cls::get('cash_NonCashPaymentDetails');
        $NonCash->setupMvc();

        $pkoClassId = cls::get('cash_Pko')->getClassId();
        $classIdColName = str::phpToMysqlName('classId');
        $query = "UPDATE {$NonCash->dbTableName} SET {$classIdColName} = $pkoClassId  WHERE {$classIdColName} IS NULL";
        $NonCash->db->query($query);
    }
}
