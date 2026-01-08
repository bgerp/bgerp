<?php


/**
 * Неинкасираните плащания до колко време назад да се обират от ВКТ->Време
 */
defIfNot('CASH_COLLECT_NOT_TRANSFERRED_IN_LAST', 5);


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
        'migrate::updateNonCashDetails2521',
        'migrate::fixNonCashBlQuantities2602',
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
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CASH_COLLECT_NOT_TRANSFERRED_IN_LAST' => array('int(Min=0)', 'caption=Неинкасираните плащания до колко време назад да се обират от ВКТ->Време'),
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


    /**
     * Миграция на к-та на безналичните плащания
     */
    function fixNonCashBlQuantities2602()
    {
        $caseQuery = cash_Cases::getQuery();
        $caseQuery->where("#state != 'rejected'");

        $cases = $casePayments = $caseRecs = array();
        while($caseRec = $caseQuery->fetch()){
            $caseItemRec = acc_Items::fetchItem('cash_Cases', $caseRec->id);
            if(is_object($caseItemRec)){
                $caseRecs[$caseItemRec->id] = $caseRec;
                $cases[$caseItemRec->id] = $caseItemRec->id;
            }
        }

        $lastBalanceId = acc_Balances::getLastBalance()->id;
        $bQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($bQuery, $lastBalanceId, '502', $cases);
        while($bRec = $bQuery->fetch()){
            $casePayments[$bRec->ent1Id][$bRec->ent2Id] = (object) array('paymentItemId' => $bRec->ent2Id, 'diff' => round($bRec->baseQuantity - $bRec->baseAmount, 5));
        }

        $cancelSystemUser = false;
        $isSystemUser = core_Users::isSystemUser();
        $accountId = acc_Accounts::getRecBySystemId(502)->id;
        foreach ($casePayments as $caseItemId => $paymentData){
            if(!array_key_exists($caseItemId, $caseRecs)) continue;
            if(!$isSystemUser){
                core_Users::forceSystemUser();
                $cancelSystemUser = true;
            }

            $mRec = (object)array('valior' => '2026-01-01',
                'folderId' => $caseRecs[$caseItemId]->folderId,
                'useCloseItems' => 'yes',
                'state' => 'draft',
                'reason' => "Конвертиране на салдо в основна валута (BGN->EUR) по безналични методи при влизане в Еврозоната");

            acc_Articles::save($mRec);

            foreach ($paymentData as $p){
                $dRec = (object)array('debitAccId' => $accountId, 'creditAccId' => $accountId, 'articleId' => $mRec->id);
                $dRec->debitEnt1 = $dRec->creditEnt1 = $caseItemId;
                $dRec->debitEnt2 = $dRec->creditEnt2 = $p->paymentItemId;
                $dRec->debitQuantity = $dRec->debitPrice = 0;
                $dRec->creditQuantity = $p->diff;
                $dRec->creditPrice = 0;
                $dRec->amount = 0;
                acc_ArticleDetails::save($dRec);
            }

            cls::get('acc_Articles')->updateMaster($mRec->id);

            if($cancelSystemUser){
                core_Users::cancelSystemUser();
            }

            $mRec->isContable = 'yes';
            cls::get('acc_Articles')->save_($mRec, 'isContable');
            acc_Articles::conto($mRec);
        }
    }
}
