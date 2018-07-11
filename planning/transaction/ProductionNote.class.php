<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ProductionNotes
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
class planning_transaction_ProductionNote extends acc_DocumentTransactionSource
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
        
        $result = (object) array(
            'reason' => "Протокол от производство №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        // Ако има ид, добавяме записите
        if (isset($rec->id)) {
            $entries = $this->getEntries($rec, $result->totalAmount);
            if (count($entries)) {
                $result->entries = $entries;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Връща записите на транзакцията
     *
     * Ако артикула има активно задание за производство и активна технологична карта.
     *
     * 		За всеки ресурс от картата:
     *
     * 		Dt: 321. Суровини, материали, продукция, стоки     (Складове, Артикули)
     * 		Ct: 61101. Разходи за Ресурси		(Ресурси)
     *
     * В противен случай
     *
     * 		Dt: 321. Суровини, материали, продукция, стоки   (Складове, Артикули)
     * 		Ct: 61102. Други разходи (общо)
     *
     */
    private function getEntries($rec, &$total)
    {
        $entries = array();
        
        $dQuery = planning_ProductionNoteDetails::getQuery();
        $dQuery->where("#noteId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        
        $errorArr2 = $errorArr = array();
        $expenses = 0;
        
        while ($dRec = $dQuery->fetch()) {
            unset($entry);
            
            if (isset($dRec->bomId)) {
                $quantityJob = planning_Jobs::fetchField($dRec->jobId, 'quantity');
                
                $quantityProduced = planning_Jobs::fetchField($dRec->jobId, 'quantityProduced');
                $quantityToProduce = $dRec->quantity + $quantityProduced;
                
                // Извличаме информацията за ресурсите в рецептата за двете количества
                $resourceInfoProduced = cat_Boms::getResourceInfo($dRec->bomId, $quantityProduced, $rec->valior);
                $resourceInfo = cat_Boms::getResourceInfo($dRec->bomId, $quantityToProduce, $rec->valior);
                
                $mapArr = $resourceInfo['resources'];
                if (count($mapArr)) {
                    foreach ($mapArr as $index => $res) {
                        $res->propQuantity = $res->propQuantity - $resourceInfoProduced['resources'][$index]->propQuantity;
                        
                        // Подготвяме количеството
                        $resQuantity = $dRec->quantity * ($res->propQuantity / $resourceInfo['quantity']);
                        $res->propQuantity = core_Math::roundNumber($resQuantity);
                    }
                    
                    arr::sortObjects($mapArr, 'propQuantity', 'desc');
                    arr::sortObjects($mapArr, 'type', 'asc');
                    
                    foreach ($mapArr as $index => $res) {
                        $pQuantity = ($index == 0) ? $dRec->quantity : 0;
                        
                        if ($res->type == 'input') {
                            $pInfo = cat_Products::getProductInfo($res->productId);
                            $reason = ($index == 0) ? 'Засклаждане на произведен продукт' : ((!isset($pInfo->meta['canStore'])) ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложени материали в производството на артикул');
                            
                            $entry = array(
                                'debit' => array('321', array('store_Stores', $rec->storeId),
                                    array('cat_Products', $dRec->productId),
                                    'quantity' => $pQuantity),
                                'credit' => array('61101', array('cat_Products', $res->productId),
                                    'quantity' => $res->propQuantity),
                                'reason' => $reason,
                            );
                        } else {
                            $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $res->productId, null, $rec->valior);
                            
                            // Сумата на дебита е себестойността на отпадния ресурс
                            $amount = $res->propQuantity * $selfValue;
                            
                            $entry = array(
                                'amount' => $amount,
                                'debit' => array('61101', array('cat_Products', $res->productId),
                                    'quantity' => $resQuantity),
                                'credit' => array('321', array('store_Stores', $rec->storeId),
                                    array('cat_Products', $dRec->productId),
                                    'quantity' => $pQuantity),
                                'reason' => 'Приспадане себестойността на отпадък от произведен продукт',
                            );
                            
                            $total += $amount;
                        }
                        
                        $entries[] = $entry;
                    }
                }
                
                // Ако има режийни разходи за разпределение
                if ($resourceInfo['expenses']) {
                    $primeCost1 = $resourceInfoProduced['primeCost'];
                    $primeCost2 = $resourceInfo['primeCost'];
                    $amount = $primeCost2 * $quantityToProduce - $primeCost1 * $quantityProduced;
                    
                    $costAmount = $resourceInfo['expenses'] * $amount;
                    $costAmount = round($costAmount, 2);
                    
                    if ($costAmount) {
                        $costArray = array(
                            'amount' => $costAmount,
                            'debit' => array('321', array('store_Stores', $rec->storeId),
                                array('cat_Products', $dRec->productId),
                                'quantity' => 0),
                            'credit' => array('61102'),
                            'reason' => 'Разпределени режийни разходи',
                        );
                        
                        $total += $costAmount;
                        $entries[] = $costArray;
                    }
                }
            }
            
            foreach ($resourceInfo['resources'] as $r) {
                if ($r->propQuantity == cat_BomDetails::CALC_ERROR) {
                    $errorArr2[] = cat_Products::getTitleById($dRec->productId);
                    break;
                }
            }
            
            if (!$entry) {
                $errorArr[] = cat_Products::getTitleById($dRec->productId);
            }
        }
        
        // Ако някой от артикулите не може да бдъе произведем сетваме, че ще правимр едирект със съобщението
        if (Mode::get('saveTransaction')) {
            if (count($errorArr)) {
                $errorArr = implode(', ', $errorArr);
                acc_journal_RejectRedirect::expect(false, "Артикулите: |{$errorArr}|* не могат да бъдат произведени, защото нямат задания или рецепти избрани в протокола");
            }
            
            if (count($errorArr2)) {
                $errorArr = implode(', ', $errorArr2);
                acc_journal_RejectRedirect::expect(false, "Артикулите: |{$errorArr}|* не могат да бъдат произведени, защото не може да се определят количествата на вложените материали");
            }
        }
        
        // Връщаме ентритата
        return $entries;
    }
}
