<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа findeals_ClosedDeals
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see acc_TransactionSourceIntf
 *
 */
class findeals_transaction_CloseDeal extends deals_ClosedDealTransaction
{
    
    
    /**
     *
     * @var findeals_ClosedDeals
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Разчетната сметка РС има Дебитно (Dt) салдо
     *
     *  	Намаляваме вземанията си от Контрагента с извънреден разход за съответната сума,
     *      със сумата на дебитното салдо на РС
     *
     *  		Dt: 6913 - Отписани вземания по Финансови сделки
     *  		Ct: Разчетната сметка
     *
     *  Разчетната сметка РС има Кредитно (Ct) салдо
     *
     *  	Намаляваме задължението си към Контрагента за сметка на извънреден приход със сумата на неплатеното задължение,
     *  	със сумата на кредитното салдо на РС
     *
     *  		Dt: Разчетната сметка
     *  		Ct: 7913 - Отписани задължения по Финансови сделки
     *
     *
     *
     */
    public function getTransaction($id)
    {
        expect($rec = $this->class->fetchRec($id));
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $info = $this->class->getDealInfo($rec->threadId);
        $docRec = $firstDoc->fetch();
        $accRec = acc_Accounts::fetch($docRec->accountId);
         
        $amount = $info->get('blAmount');
        
        // Създаване на обекта за транзакция
        $result = (object) array(
                'reason' => $rec->notes,
                'valior' => ($rec->valior) ? $rec->valior : $this->class->getValiorDate($rec),
                'totalAmount' => 0,
                'entries' => array(),
        );
        
        if ($amount == 0) {
            
            return $result;
        }
        
        if ($rec->closeWith) {
            $dealItem = acc_Items::fetch("#classId = {$firstDoc->getInstance()->getClassId()} AND #objectId = '{$firstDoc->that}' ");
            $closeDealItem = array($firstDoc->className, $rec->closeWith);
            $closeEntries = $this->class->getTransferEntries($dealItem, $result->totalAmount, $closeDealItem, $rec);
            $result->entries = array_merge($result->entries, $closeEntries);
        } else {
            $jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
            
            // За всеки случай махат се от записите, тези които са на приключването на покупка
            if (isset($rec->id)) {
                if ($thisRec = acc_Journal::fetchByDoc($this->class, $rec->id)) {
                    $nQuery = acc_JournalDetails::getQuery();
                    $nQuery->where("#journalId = {$thisRec->id}");
                    $thisIds = arr::extractValuesFromArray($nQuery->fetchAll(), 'id');
                    $jRecs = array_diff_key($jRecs, $thisIds);
                }
            }
            
            $sysId = acc_Accounts::fetchField($docRec->accountId, 'systemId');
            $quantities = acc_Balances::getBlQuantities($jRecs, $sysId);
            
            if (is_array($quantities)) {
                foreach ($quantities as $index => $obj) {
                    $entry = $this->getCloseEntry($obj->amount, $obj->quantity, $index, $result->totalAmount, $docRec, $firstDoc);
                    if (count($entry)) {
                        $result->entries = array_merge($result->entries, $entry);
                    }
                }
            }
        }
        
        return $result;
    }
    
    
    /**
     * Отчитане на извънредните приходи/разходи от сделката
     *
     */
    private function getCloseEntry($amount, $quantity, $index, &$totalAmount, $docRec, $firstDoc)
    {
        $entry = array();
    
        $dealArr = array(acc_Accounts::fetchField($docRec->accountId, 'systemId'),
                         array($docRec->contragentClassId, $docRec->contragentId),
                         array($firstDoc->className, $docRec->id),
                         $index,
                        'quantity' => abs($quantity));
        
        if ($amount > 0) {
            
            // Ако РС има дебитно салдо
            $entry = array('amount' => $amount,
                    'debit' => array('6913',
                               array($docRec->contragentClassId, $docRec->contragentId),
                               array($firstDoc->className, $firstDoc->that)),
                    'credit' => $dealArr);
        } else {
            
            // Ако РС има кредитно салдо
            $entry = array('amount' => abs($amount),
                    'debit' => $dealArr,
                    'credit' => array('7913',
                                array($docRec->contragentClassId, $docRec->contragentId),
                                array($firstDoc->className, $firstDoc->that))
            );
        }
            
        $totalAmount += abs($amount);
        
        // Връщане на записа
        return array($entry);
    }
}
