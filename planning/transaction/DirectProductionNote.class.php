<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_DirectProductionNotes
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class planning_transaction_DirectProductionNote extends acc_DocumentTransactionSource
{
    /**
     * Артикули с моментни рецепти
     */
    private $instantProducts = array();


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

        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            $notAllocatedInputProductArr = array_filter($rec->_details, function($a) { return $a->type != 'allocated' && $a->type != 'subProduct';});
            $notNullQuantityProductArr = array_filter($rec->_details, function($a) { return $a->packQuantity != 0;});

            $productArr = arr::extractValuesFromArray($notNullQuantityProductArr, 'productId');
            $notAllocatedInputProductArr = arr::extractValuesFromArray($notAllocatedInputProductArr, 'productId');
            unset($notAllocatedInputProductArr[$rec->productId]);

            if($redirectError = deals_Helper::getContoRedirectError($notAllocatedInputProductArr, 'canConvert', null, 'трябва да са вложими')){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }
            
            if($redirectError = deals_Helper::getContoRedirectError($productArr, null, 'generic', 'са генерични и трябва да бъдат заменени')){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }

            $returnProductArr = array_filter($rec->_details, function($a) { return $a->type == 'pop';});
            if(countR($returnProductArr)){
                $returnProductArr = arr::extractValuesFromArray($returnProductArr, 'productId');
                if($redirectError = deals_Helper::getContoRedirectError($returnProductArr, 'canStore', null, 'трябва да са складируеми за да са отпадъци')){
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }

            $subProductArr = array_filter($rec->_details, function($a) { return $a->type == 'subProduct';});
            if(countR($subProductArr)){
                $subProductArr = arr::extractValuesFromArray($subProductArr, 'productId');
                if($redirectError = deals_Helper::getContoRedirectError($subProductArr, 'canManifacture,canStore', null, 'трябва да са производими и складируеми за да са субпродукти')){
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }


            // Ако е забранено да се изписва на минус, прави се проверка
            if(!store_Setup::canDoShippingWhenStockIsNegative()){

                // Проверка за неналичните експедирани артикули
                $shippedProductsFromStores = store_Stores::getShippedProductsByStoresFromTransactionEntries($entries, $this->instantProducts, false);
                foreach ($shippedProductsFromStores as $storeId => $arr){
                    if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($arr, $storeId, $rec->state)) {
                        acc_journal_RejectRedirect::expect(false, $warning);
                    }
                }
            }
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
        $entries = $byStores = $instantServices = $rec->_details = array();
        if (isset($rec->id)) {
            $dQuery = planning_DirectProductNoteDetails::getQuery();
            $dQuery->where("#noteId = {$rec->id}");
            $dQuery->EXT('canManifacture', 'cat_Products', 'externalName=canManifacture,externalKey=productId');
            $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $dQuery->orderBy('id,type', 'ASC');
            while($dRec = $dQuery->fetch()){
                $rec->_details[$dRec->id] = $dRec;
                if($rec->type == 'input'){
                    if(isset($dRec->storeId)){
                        $byStores[$dRec->storeId][$dRec->id] = $dRec;
                    } elseif(isset($dRec->fromAccId)){
                        $instantServices[$dRec->id] = $dRec;
                    }
                }
            }
        }

        // Кои материали ще се произвеждат преди да се вложат
        foreach ($byStores as $storeId => $dRecs){
            $clone = clone $rec;
            $clone->storeId = $storeId;
            $clone->details = $dRecs;
            $entriesProduction = sales_transaction_Sale::getProductionEntries($clone, 'planning_DirectProductionNote', 'storeId', $this->instantProducts);
            if (countR($entriesProduction)) {
                $entries = array_merge($entries, $entriesProduction);
            }
        }

        // Кои услуги ще се произвеждат ако не се влагат
        if(countR($instantServices)){
            $clone = clone $rec;
            $clone->storeId = null;
            $clone->details = $instantServices;
            $entriesProduction = sales_transaction_Sale::getProductionEntries($clone, 'planning_DirectProductionNote', 'storeId', $this->instantProducts);
            if (countR($entriesProduction)) {
                $entries = array_merge($entries, $entriesProduction);
            }
        }

        // Генериране на транзакцията за произвеждане на основния артикул
        $equalizePrimeCost = $rec->equalizePrimeCost == 'yes';
        $entries1 = self::getProductionEntries($rec->productId, $rec->quantity, $rec->storeId, $rec->debitAmount, $this->class, $rec->id, $rec->expenseItemId, $rec->valior, $rec->expenses, $rec->_details, $rec->jobQuantity, $equalizePrimeCost);
        if (countR($entries1)) {
            $entries = array_merge($entries, $entries1);
        }

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
    public static function getProductionEntries($productId, $quantity, $storeId, $debitAmount, $classId, $documentId, $expenseItemId, $valior, $expenses, $details, $jobQuantity = null, $equalizePrimeCost = null)
    {
        $entries = $array = array();
        $prodRec = cat_Products::fetch($productId, 'fixedAsset,canStore');

        $foundConvertedProducedRecs = array_filter($details, function($a) use ($productId){return $a->productId == $productId && $a->type == 'input' && isset($a->storeId);});
        $foundOtherInputedRecs = array_filter($details, function($a) use ($productId){return $a->productId != $productId && $a->type == 'input';});

        if(acc_Journal::throwErrorsIfFoundWhenTryingToPost()){
            if(countR($foundConvertedProducedRecs)){
                $convertedQuantity = arr::sumValuesArray($foundConvertedProducedRecs, 'quantity');
                $halfQuantity = $quantity / 2;
                if($convertedQuantity > $halfQuantity){
                    if(!empty($expenses) || countR($foundOtherInputedRecs)){
                        acc_journal_RejectRedirect::expect(false, "При разпределяне на режийни разходи или влагане и на други Артикули, вложеното количество от произвеждания артикул не може да бъде повече от 50% от произвежданото количество!");
                    }
                }
            }
        }

        if ($prodRec->canStore == 'yes') {
            $array = array('321', array('store_Stores', $storeId), array('cat_Products', $productId));
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

        $saleId = null;
        $Doc = cls::get($classId);
        if(isset($documentId)){
            if($Doc instanceof sales_Sales) {
                $saleId = $documentId;
            } elseif($Doc instanceof store_ShipmentOrders){
                $firstDoc = doc_Threads::getFirstDocument($Doc->fetchField($documentId, 'threadId'));
                if($firstDoc->isInstanceOf('sales_Sales')){
                    $saleId = $firstDoc->that;
                }
            } elseif($Doc instanceof planning_DirectProductionNote){
                $jobRec = planning_DirectProductionNote::getJobRec($documentId);
                $saleId = $jobRec->saleId;
            }
        }

        if (!is_array($details)) return $entries;
        $details = array_filter($details, function($a) {return !empty($a->quantity);});

        $saleRec = null;
        if(isset($saleId)){
            $saleRec = sales_Sales::fetch($saleId, 'threadId,contragentClassId,contragentId');
        }

        $outsourced = array_filter($details, function($a){ return $a->isOutsourced == 'yes';});

        // Ако има вложени получени от ПОП артикули ще им се прави отделна контировка
        $consignmentAmount = 0;
        if(is_object($saleRec) && countR($outsourced)){
            foreach ($outsourced as $key => $det1){
                if($det1->type == 'input'){
                    $creditArr = array('61101', array('cat_Products', $det1->productId), 'quantity' => $det1->quantity);

                    if(!empty($det1->storeId)){
                        $creditArr = array('61103', array($classId, $documentId), array('cat_Products', $det1->productId), 'quantity' => $det1->quantity);
                        $entry = array('debit' => array('61103',
                            array($classId, $documentId),
                            array('cat_Products', $det1->productId),
                            'quantity' => $det1->quantity),
                            'credit' => array('321',
                                array('store_Stores', $det1->storeId),
                                array('cat_Products', $det1->productId),
                                'quantity' => $det1->quantity),
                            'reason' => 'Влагане на чужди материали - производство на ишлеме');

                        $amountCheck = cat_Products::getWacAmountInStore($det1->quantity, $det1->productId, $valior, $det1->storeId);
                        if(!empty($amountCheck)){
                            $entry['amount'] = $amountCheck;
                        }

                        $entries[] = $entry;
                    }

                    $consignmentAmount += cat_Products::getWacAmountInStore($det1->quantity, $det1->productId, $valior, $det1->storeId);

                    $Cover = doc_Folders::getCover(cat_Products::fetchField($det1->productId, 'folderId'));
                    $entry = array('debit' => array('3232',
                        array($Cover->getClassId(), $Cover->that),
                        array('cat_Products', $det1->productId),
                        'quantity' => $det1->quantity),
                        'credit' => $creditArr,
                        'reason' => 'Вложен чужд материал в производството на артикул - производство на ишлеме');
                    $entries[] = $entry;

                    unset($details[$key]);
                }
            }
        }

        $costAmount = 0;
        if(isset($debitAmount)){
            $debitAmount = ($debitAmount) ? round($debitAmount, 2) : 0;
            $amount = $debitAmount;
            $costAmount = $debitAmount;
            $array['quantity'] = $quantity;

            $entry = array('amount' => $amount,
                'debit' => $array,
                'credit' => array('61102'), 'reason' => 'Бездетайлно произвеждане');
            $entries[] = $entry;
        }

        if (countR($details)) {
            arr::sortObjects($details, 'type');
            foreach ($details as $dRec) {
                    
                // Влагаме артикула, само ако е складируем, ако не е
                // се предполага ,че вече е вложен в незавършеното производство
                if ($dRec->type == 'input' || $dRec->type == 'allocated') {
                    $canStore = cat_Products::fetchField($dRec->productId, 'canStore');
                        
                    if ($canStore == 'yes') {
                        if (empty($dRec->storeId)) continue;
                            $entry = array('debit' => array('61103',
                                                      array($classId, $documentId),
                                                      array('cat_Products', $dRec->productId),
                                                     'quantity' => $dRec->quantity),
                                           'credit' => array('321',
                                                       array('store_Stores', $dRec->storeId),
                                                       array('cat_Products', $dRec->productId),
                                                     'quantity' => $dRec->quantity),
                                           'reason' => 'Влагане на материал в производството');

                            $amountCheck = cat_Products::getWacAmountInStore($dRec->quantity, $dRec->productId, $valior, $dRec->storeId);
                            if(!empty($amountCheck)){
                                $entry['amount'] = $amountCheck;
                            }
                        } else {
                            if(empty($dRec->fromAccId)) continue;
                            $item = $dRec->expenseItemId ?? acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                            $entry = array('debit' => array('61103',
                                                      array($classId, $documentId),
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

            $index = 0;
            foreach ($details as $dRec1) {
                $canStore = cat_Products::fetchField($dRec1->productId, 'canStore');
                $primeCost = null;
                if ($dRec1->type == 'input' || $dRec1->type == 'allocated') {
                    // Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
                    if ($canStore == 'yes') {
                        $primeCost = cat_Products::getWacAmountInStore($dRec1->quantity, $dRec1->productId, $valior, $dRec1->storeId);
                    } else {
                        if(empty($dRec1->fromAccId)){
                            $primeCost = planning_GenericMapper::getWacAmountInProduction($dRec1->quantity, $dRec1->productId, $valior);
                        }
                        if(empty($primeCost)){
                            $primeCost = planning_GenericMapper::getWacAmountInAllCostsAcc($dRec1->quantity, $dRec1->productId, $valior, $dRec1->expenseItemId);
                        }
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
                $quantityD = ($index == 0) ? $quantity : 0;
                    
                // Ако е материал го изписваме към произведения продукт
                $entry = array();
                if (!in_array($dRec1->type, array('pop', 'subProduct'))) {
                    $reason = ($index == 0) ? (($prodRec->canStore == 'yes') ? 'Засклаждане на произведен артикул' : 'Произвеждане на услуга') : (($canStore != 'yes' ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложен материал в производството на артикул'));
                    $array['quantity'] = $quantityD;
                    $entry['debit'] = $array;

                    if(isset($dRec1->storeId) || !empty($dRec1->fromAccId)){
                        $entry['credit'] = array('61103', array($classId, $documentId), array('cat_Products', $dRec1->productId),
                            'quantity' => $dRec1->quantity);
                    } else {
                        $entry['credit'] = array('61101', array('cat_Products', $dRec1->productId),
                            'quantity' => $dRec1->quantity);
                    }
                    $entry['reason'] = $reason;
                        
                    $entries[] = $entry;
                } else {
                    $entry['amount'] = $primeCost;
                    if($dRec1->type == 'subProduct' && !empty($dRec1->storeId)){
                        $entry['debit'] = array('321', array('store_Stores', $dRec1->storeId), array('cat_Products', $dRec1->productId), 'quantity' => $dRec1->quantity);
                        $entry['reason'] = 'Засклаждане на субпродукт';
                    } else {
                        $entry['debit'] = array('61101', array('cat_Products', $dRec1->productId), 'quantity' => $dRec1->quantity);
                        $entry['reason'] = 'Заприхождаване на отпадък в незавършеното производство';
                    }
                    $entry['credit'] = array('484');

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
        $selfAmount = $costAmount + $consignmentAmount;

        // Ако има режийни разходи, разпределяме ги
        if (isset($expenses)) {
            $costAmount = $selfAmount * $expenses;
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
            if($equalizePrimeCost){
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
                            'reason' => 'Допълване на себестойността до очакваната'
                        );

                        $entries[] = $array1;
                    }
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
                $array['quantity'] = $quantity;
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
        
        return $entries;
    }
}
