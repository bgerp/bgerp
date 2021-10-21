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
        'migrate::recontoDocuments1',
        'migrate::updateCashDocuments',
        'migrate::updateNonCashPayments',
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
    public function recontoDocuments1()
    {
        deals_Setup::fixDocumentsWithMoreThanNDigits(array('cash_Pko', 'cash_Rko'));
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


    /**
     * Миграция на инкасиранията на касовите плащания
     */
    function updateNonCashPayments()
    {
        $sources = array();
        $CashTransfers = cls::get('cash_InternalMoneyTransfer');
        $pkoClassId = cash_Pko::getClassId();
        $query = cash_InternalMoneyTransfer::getQuery();
        $query->where("#operationSysId = 'nonecash2case' AND #sourceId IS NULL");
        $allTransfers = $query->fetchAll();
        $idArr = arr::extractValuesFromArray($allTransfers, 'containerId');

        if(!countR($idArr)) return;
        $ids = implode(',', $idArr);
        core_App::setTimeLimit(400);

        $lQuery = doc_Linked::getQuery();
        $lQuery->where("(#outVal IN ({$ids}) AND #outType = 'doc') OR (#inVal  IN ({$ids}) AND #inType = 'doc')");

        while($lRec = $lQuery->fetch()){
            foreach (array('outVal', 'inVal') as $fld){
                $otherVal = ($fld == 'outVal') ? 'inVal' : 'outVal';
                if(!array_key_exists($lRec->{$fld}, $idArr)){
                    $cRec = doc_Containers::fetch($lRec->{$fld}, 'docClass,id');
                    if($cRec->docClass == $pkoClassId){
                        $sources[$lRec->{$otherVal}] = $cRec->id;
                    }
                }
            }
        }

        if(!countR($sources)) return;

        $saveArr = array();
        foreach ($allTransfers as $tRec){
            if(array_key_exists($tRec->containerId, $sources)){
                $tRec->sourceId = $sources[$tRec->containerId];
                $saveArr[$tRec->id] = $tRec;
            }
        }

        if(countR($saveArr)){
            $CashTransfers->saveArray($saveArr, 'id,sourceId');
        }
    }
}
