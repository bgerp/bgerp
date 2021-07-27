<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_InventoryNotes
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_InventoryNote extends acc_DocumentTransactionSource
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
            'reason' => "Протокол за инвентаризация №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        if ($rec->id) {
            
            // При контиране за първи път
            if (Mode::get('saveTransaction')) {
                if ($rec->state == 'draft') {
                    $this->class->sync($rec);
                }
            }
            
            $result->entries = $this->getEntries($rec, $result->totalAmount);
        }
        
        return $result;
    }
    
    
    /**
     * Връща записите на транзакцията
     *
     * Във всички случаи на констатирани излишъци (т.е. превишение на намереното над очакваното):
     *
     * Dt 321. Суровини, материали, продукция, стоки         (Складове, Артикули)
     * Ct 799. Други извънредни приходи
     *
     * В случаите на констатирани липси (т.е. превишение на очакваното над намереното), само когато НЕ Е чекнато
     * "Начет МОЛ" (т.е. решено е, че липсите ще са за сметка на фирмата/собственика):
     *
     * Dr 699. Други извънредни разходи
     * Ct 321. Суровини, материали, продукция, стоки         (Складове, Артикули)
     */
    private function getEntries($rec, &$total)
    {
        $errorArr = $productsArr = $entries = array();
        
        // Намираме тези редове, които няма да се начисляват към МОЛ
        $dQuery = store_InventoryNoteSummary::getQuery();
        $dQuery->where("#noteId = {$rec->id}");
        $dQuery->where('#charge IS NULL');
        
        core_App::setTimeLimit(600);
        
        while ($dRec = $dQuery->fetch()) {

            // Ако разликата е положителна, тоест имаме излишък
            if ($dRec->delta > 0) {
                $productsArr[$dRec->productId] = $dRec->productId;

                if($dRec->quantity == 0){

                    // Ако ще се занулява отрицателно к-во винаги ще е със складовата себестойност към момента
                    Mode::push('alwaysFeedWacStrategyWithBlQuantity', true);
                    $amount = cat_Products::getWacAmountInStore($dRec->delta, $dRec->productId, $rec->valior, $rec->storeId);
                    Mode::pop('alwaysFeedWacStrategyWithBlQuantity');

                } else {

                    // Ако не се занулява, ще се засклади с мениджърската сб-ст или със складовата, ако първата не е зададена
                    $amount = cat_Products::getPrimeCost($dRec->productId, null, $dRec->delta, $rec->valior);
                    if (!$amount) {
                        if (Mode::get('saveTransaction')) {
                            $amount = cat_Products::getWacAmountInStore($dRec->delta, $dRec->productId, $rec->valior, $rec->storeId);
                        } else {
                            $amount = 0;
                        }
                    } else {
                        $amount = $dRec->delta * $amount;
                    }
                }

                if (!isset($amount)) {
                    $errorArr[$dRec->productId] = cat_Products::getTitleById($dRec->productId);
                }
                
                $amount = round($amount, 2);
                $total += $amount;
                
                $entries[] = array(
                    'amount' => $amount,
                    'debit' => array('321', array('store_Stores', $rec->storeId),
                        array('cat_Products', $dRec->productId),
                        'quantity' => $dRec->delta),
                    'credit' => array('799'),
                    'reason' => 'Заприходени излишъци на стоково-материални запаси',
                );
            
            // Ако разликата е отрицателна, имаме липса
            } elseif ($dRec->delta < 0) {
                $productsArr[$dRec->productId] = $dRec->productId;
                $delta = abs($dRec->delta);
                
                $entries[] = array(
                    'debit' => array('699'),
                    'credit' => array('321', array('store_Stores', $rec->storeId),
                        array('cat_Products', $dRec->productId),
                        'quantity' => $delta),
                    
                    'reason' => 'Отписани липси на стоково-материални запаси',
                );
            }
        }
        
        if (Mode::get('saveTransaction')) {
            
            // Ако има грешки, при контиране прекъсваме
            if (countR($errorArr)) {
                $errorArr = implode(', ', $errorArr);
                $message = "{$errorArr} |нямат себестойност|*";
                acc_journal_RejectRedirect::expect(false, $message);
            }
            
            // Проверка на артикулите
            $productCheck = deals_Helper::checkProductForErrors($productsArr, 'canStore');
            if(countR($productCheck['notActive']) && !haveRole('accMaster,ceo')){
                 acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(',', $productCheck['notActive']) . " |не са активни|*!");
            } elseif($productCheck['metasError']){
                 acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(',', $productCheck['metasError']) . " |трябва да са складируеми и продаваеми|*!");
            }
        }
        
        return $entries;
    }
}
