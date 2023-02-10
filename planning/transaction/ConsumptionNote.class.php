<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ConsumptionNotes
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class planning_transaction_ConsumptionNote extends acc_DocumentTransactionSource
{
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = $this->class->fetchRec($id));
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $result = (object) array(
            'reason' => "Протокол за влагане в производство №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        if (isset($rec->id)) {
            $entries = $this->getEntries($rec, $result->totalAmount);
            
            if (countR($entries)) {
                $result->entries = array_merge($result->entries, $entries);
            }
        }
        
        return $result;
    }
    
    
    /**
     * Връща записите на транзакцията
     */
    private static function getEntries($rec, &$total)
    {
        $entries = array();
        $productsArr = array();
        
        $dQuery = planning_ConsumptionNoteDetails::getQuery();
        $dQuery->where("#noteId = {$rec->id}");
        $details = $dQuery->fetchAll();
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);

        if (Mode::get('saveTransaction')) {
            if(!store_Setup::canDoShippingWhenStockIsNegative()){
                if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($details, $rec->storeId, $rec->state)) {
                    acc_journal_RejectRedirect::expect(false, $warning);
                }
            }
        }

        foreach ($details as $dRec) {
            if(empty($dRec->quantity)) continue;

            $prodRec = cat_Products::fetch($dRec->productId, 'canStore,fixedAsset');
            $productsArr[$dRec->productId] = $dRec->productId;
            $debitArr = null;
            
            if ($rec->useResourceAccounts == 'yes') {
                
                // Ако е указано да влагаме само в център на дейност и ресурси, иначе влагаме в център на дейност
                $debitArr = array('61101', array('cat_Products', $dRec->productId), 'quantity' => $dRec->quantity);
            }

            if($prodRec->canStore == 'yes'){
                $creditArr = array(321, array('store_Stores', $rec->storeId), array('cat_Products', $dRec->productId), 'quantity' => $dRec->quantity);
                $reason = 'Влагане на материал в производството';
            } else {
                $expenseItem = ($prodRec->fixedAsset == 'yes') ? array('cat_Products', $dRec->productId) : acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                $creditArr = array(60201, $expenseItem, array('cat_Products', $dRec->productId), 'quantity' => $dRec->quantity);
                $reason = 'Влагане на услуга в производството';
            }

            // Ако не е ресурс, дебитираме общата сметка за разходи '61102. Други разходи (общо)'
            if (empty($debitArr)) {
                $debitArr = array('61102');
                $type = ($prodRec->canStore == 'yes') ? 'материал' : 'услуга';
                $reason = "Бездетайлно влагане на {$type} в производството";
            }

            $entries[] = array('debit' => $debitArr, 'credit' => $creditArr, 'reason' => $reason);
        }
        
        if (Mode::get('saveTransaction')) {
            
            // Проверка на артикулите
            if (countR($productsArr)) {
                $msg = "трябва да са складируеми и/или вложими";
                if($redirectError = deals_Helper::getContoRedirectError($productsArr, 'canConvert', null, $msg)){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
                
                $msg = "са генерични и трябва да бъдат заменени";
                if($redirectError = deals_Helper::getContoRedirectError($productsArr, null, 'generic', $msg)){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        // Връщаме ентритата
        return $entries;
    }
}
