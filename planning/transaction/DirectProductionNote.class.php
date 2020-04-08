<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_DirectProductionNotes
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class planning_transaction_DirectProductionNote extends acc_DocumentTransactionSource
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
            'reason' => "Протокол за производство №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        // Ако има ид, добавяме записите
        $entries = $this->getEntries($rec, $result->totalAmount);
        if (countR($entries)) {
            $result->entries = $entries;
        }
        
        return $result;
    }
    
    
    /**
     * Подготовка на записите
     *
     * 1. Етап: влагаме материалите, които ще изпишем при производството
     *
     * Всички складируеми материали в секцията за влагане ги влагаме в производството.
     * Нескладируемите се предполага, че вече са вложени там при покупката им
     *
     * Dt: 61101 - Незавършено производство                (Артикули)
     *
     * Ct: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     *   или ако артикула е услуга Ct: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     *
     * Нескладируемите артикули ги влагаме в незавършеното производство от разходната сметка
     * където се предполага че са натрупани към разходния обект 'Неразпределени разходи'
     *
     * Dt: 61101 - Незавършено производство                (Артикули)
     *
     * Ct: 60201 - Разходи за (нескладируеми) услуги и консумативи  (Разходни обекти, Артикули)
     *
     * 2. Етап: вкарваме в склада произведения продукт
     *
     * Изписваме вложените материали и вкарваме в склада продукта. Той влиза с цялото си количество
     * при изписването на първия материал/услуга, а останалите натрупват себестойността си към неговата
     * Отпадъка само намаля себестойността на проудкта съя своята себестойност
     *
     * Вкарване на материал
     *
     * Dt: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     * или ако артикула е услуга 60201 Разходи за (нескладируеми) услуги и консумативи  (Разходни обекти, Артикули)
     * по посочения разходен обект
     *
     * Ct: 61101 - Незавършено производство                (Артикули)
     *
     * Вкарване на отпадък
     *
     * Dt: с/ка 61101 - Незавършено производство
     * Ct: с/ка 484 - Операции захранващи стратегии двустранно
     * с количеството на ОТПАДЪКА и сума - според зададената му себестойност - по този начин заприхождаваме ОТПАДЪКА в незавършеното производство
     *
     * Dt: с/ка 321 - Суровини, материали, продукция, стоки
     * 		или 60201 - Разходи за (нескладируеми) услуги и консумативи  по перо 'Неразпределени разходи'
     * Ct: с/ка 484 - Операции захранващи стратегии двустранно
     * с количество 0 за ПРОИЗВЕДЕНИЯ артикул и сума = себестойността на ОТПАДЪКА но с ОТРИЦАТЕЛЕН ЗНАК, с цел - да намалим себестойността на ПРОИЗВЕДЕНИЯ артикул със стойността на генерирания ОТПАДЪК
     *
     * 3. Етап: Ако има режийни разходи за разпределение
     *
     * Dt: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     * или ако артикула е услуга Dt: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     *
     * Ct: 61102 - Други разходи (общо)
     *
     * 4. Етап: Ако разходния обект е продажба
     *
     * Dt: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     *
     * Ct: 60201 - Разходи за (нескладируеми) услуги и консумативи  по избраното перо за разпределяне
     */
    private function getEntries($rec, &$total)
    {
        $dRecs = array();
        if (isset($rec->id)) {
            $dQuery = planning_DirectProductNoteDetails::getQuery();
            $dQuery->where("#noteId = {$rec->id}");
            $dQuery->orderBy('id,type', 'ASC');
            $dRecs = $dQuery->fetchAll();
        }
        
        $entries = self::getProductionEntries($rec->productId, $rec->quantity, $rec->storeId, $rec->debitAmount, $this->class, $rec->id, $rec->expenseItemId, $rec->valior, $rec->expenses, $dRecs, $rec->jobQuantity);
        
        return $entries;
    }
    
    
    /**
     * Връща транзакцията за производството на артикул
     * 
     * @param int $productId
     * @param double $quantity
     * @param int $storeId
     * @param double|null $debitAmount
     * @param mixed $classId
     * @param int $documentId
     * @param int $expenseItemId
     * @param datetime $valior
     * @param double|null $expenses
     * @param array $details
     * 
     * @return array $entries
     */
    public static function getProductionEntries($productId, $quantity, $storeId, $debitAmount, $classId, $documentId, $expenseItemId, $valior, $expenses, $details, $jobQuantity = null)
    {
        $entries = $array = array();
        $prodRec = cat_Products::fetch($productId, 'fixedAsset,canStore');
        
        if ($prodRec->canStore == 'yes') {
            $array = array('321', array('store_Stores', $storeId),
                array('cat_Products', $productId));
        } else {
            if ($prodRec->fixedAsset == 'yes') {
                $expenseItem = array('cat_Products', $productId);
            } elseif (isset($expenseItemId)) {
                $expenseItem = $expenseItemId;
            } else {
                if (acc_Items::isItemInList($classId, $documentId, 'costObjects')) {
                    $expenseItem = array('planning_DirectProductionNote', $documentId);
                } else {
                    $expenseItem = acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                }
            }
            
            $array = array('60201', $expenseItem, array('cat_Products', $productId));
        }
        
        if (is_array($details)) {
            if (!countR($details)) {
                $debitAmount = ($debitAmount) ? $debitAmount : 0;
                
                $amount = $debitAmount;
                $costAmount = $debitAmount;
                $array['quantity'] = $quantity;
                
                $entry = array('amount' => $amount,
                    'debit' => $array,
                    'credit' => array('61102'), 'reason' => 'Бездетайлно произвеждане');
                
                $entries[] = $entry;
                
            } else {
                
                foreach ($details as $dRec) {
                    
                    // Влагаме артикула, само ако е складируем, ако не е
                    // се предполага ,че вече е вложен в незавършеното производство
                    if ($dRec->type == 'input') {
                        $canStore = cat_Products::fetchField($dRec->productId, 'canStore');
                        
                        if ($canStore == 'yes') {
                            if (empty($dRec->storeId)) {
                                continue;
                            }
                            
                            $entry = array('debit' => array('61101',
                                array('cat_Products', $dRec->productId),
                                'quantity' => $dRec->quantity),
                                'credit' => array('321',
                                    array('store_Stores', $dRec->storeId),
                                    array('cat_Products', $dRec->productId),
                                    'quantity' => $dRec->quantity),
                                'reason' => 'Влагане на материал в производството');
                        } else {
                            $item = acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                            $entry = array('debit' => array('61101',
                                array('cat_Products', $dRec->productId),
                                'quantity' => $dRec->quantity),
                                'credit' => array('60201',
                                    $item,
                                    array('cat_Products', $dRec->productId),
                                    'quantity' => $dRec->quantity),
                                'reason' => 'Влагане на нескладируема услуга или консуматив в производството');
                        }
                        
                        $entries[] = $entry;
                    }
                }
                
                arr::sortObjects($details, 'type');
                
                $costAmount = $index = 0;
                foreach ($details as $dRec1) {
                    $sign = ($dRec1->type == 'input') ? 1 : -1;
                    $canStore = cat_Products::fetchField($dRec1->productId, 'canStore');
                    
                    if ($dRec1->type == 'input') {
                        // Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
                        if ($canStore == 'yes') {
                            $primeCost = cat_Products::getWacAmountInStore($dRec1->quantity, $dRec1->productId, $valior);
                        } else {
                            $primeCost = planning_ObjectResources::getWacAmountInProduction($dRec1->quantity, $dRec1->productId, $valior);
                        }
                        
                        $sign = 1;
                    } else {
                        $primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $dRec1->productId, null, $valior);
                        $primeCost *= $dRec1->quantity;
                        $sign = -1;
                    }
                    
                    if (!$primeCost) {
                        $primeCost = 0;
                    }
                    
                    $pAmount = $sign * $primeCost;
                    $costAmount += $pAmount;
                    
                    $quantity = ($index == 0) ? $quantity : 0;
                    
                    // Ако е материал го изписваме към произведения продукт
                    if ($dRec1->type == 'input') {
                        $reason = ($index == 0) ? 'Засклаждане на произведен артикул' : (($canStore != 'yes' ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложен материал в производството на артикул'));
                        
                        $array['quantity'] = $quantity;
                        $entry['debit'] = $array;
                        
                        $entry['credit'] = array('61101', array('cat_Products', $dRec1->productId),
                            'quantity' => $dRec1->quantity);
                        $entry['reason'] = $reason;
                        
                        $entries[] = $entry;
                    } else {
                        $entry['amount'] = $primeCost;
                        $entry['debit'] = array('61101', array('cat_Products', $dRec1->productId), 'quantity' => $dRec1->quantity);
                        $entry['credit'] = array('484');
                        $entry['reason'] = 'Заприхождаване на отпадък в незавършеното производство ';
                        $entries[] = $entry;
                        
                        $entry2 = array();
                        $entry2['amount'] = -1 * $primeCost;
                        $entry2['debit'] = $array;
                        
                        if($dRec1->productId == $productId){
                            $entry2['debit']['quantity'] = -1 * $dRec1->quantity;
                        } else {
                            $entry2['debit']['quantity'] = 0;
                        }
                        
                        $entry2['credit'] = array('484');
                        
                        $entry2['reason'] = 'Приспадане себестойността на отпадък от произведен артикул';
                        $entries[] = $entry2;
                    }
                    
                    $index++;
                }
            }
            
            $selfAmount = $costAmount;
            
            // Ако има режийни разходи, разпределяме ги
            if (isset($expenses)) {
                $costAmount = $costAmount * $expenses;
                $costAmount = round($costAmount, 2);
                
                // Ако себестойността е неположителна, режийните са винаги 0
                if ($costAmount <= 0) {
                    $costAmount = 0;
                }
                
                $array['quantity'] = 0;
                
                $costArray = array(
                    'amount' => $costAmount,
                    'debit' => $array,
                    'credit' => array('61102'),
                    'reason' => 'Разпределени режийни разходи');
                
                $entries[] = $costArray;
            }
            
            if ($Driver = cat_Products::getDriver($productId)) {
                $quantityCompare = !empty($jobQuantity) ? $jobQuantity : $quantity;
                $driverCost = $Driver->getPrice($productId, $quantityCompare, 0, 0, $valior);
              
                $driverCost = is_object($driverCost) ? $driverCost->price : $driverCost;
                
                if (isset($driverCost)) {
                    $driverAmount = $driverCost * $quantity;
                    $diff = round($driverAmount - $selfAmount, 2);
                    
                    if ($diff > 0) {
                        $array['quantity'] = 0;
                        $array1 = array(
                            'amount' => $diff,
                            'debit' => $array,
                            'credit' => array('61102'),
                            'reason' => 'Изравняване на себестойността, спрямо очакваната'
                        );
                        
                        $entries[] = $array1;
                    }
                }
            }
            
            // Разпределяне към продажба ако разходния обект е продажба
            if (isset($expenseItem)) {
                if (is_array($expenseItem)) {
                    $eItem = acc_Items::fetchItem($expenseItem[0], $expenseItem[1]);
                } else {
                    $eItem = acc_Items::fetch($expenseItem);
                }
                
                if ($eItem->classId == sales_Sales::getClassId()) {
                    $saleRec = sales_Sales::fetch($eItem->objectId, 'contragentClassId, contragentId');
                    $entry4 = array('debit' => array('703',
                        array($saleRec->contragentClassId, $saleRec->contragentId),
                        array($eItem->classId, $eItem->objectId),
                        array('cat_Products', $productId),
                        'quantity' => 0),
                        'credit' => $array,
                        'reason' => 'Себестойност на услуга');
                        
                        $entries[] = $entry4;
                }
            }
        }
        
        return $entries;
    }
}
