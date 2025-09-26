<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Sales
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_Sale extends acc_DocumentTransactionSource
{
    /**
     *
     * @var sales_Sales
     */
    public $class;
    
    
    /**
     * Систем ид на сметката за авансово плащане
     */
    const DOWNPAYMENT_ACCOUNT_ID = '412';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Генериране на счетоводните транзакции, породени от продажба.
     *
     * Счетоводната транзакция за породена от документ-продажба може да се раздели на три
     * части:
     *
     * 1. Задължаване на с/ката на клиента
     *
     *    Dt: 411. Вземания от клиенти  (Клиент, Сделка, Валута)
     *
     *    Ct: 701. Приходи от продажби на Стоки и Продукти       (Клиент, Сделка, Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги                 (Клиент, Сделка, Услуга)
     *
     *
     * 2. Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти  (Клиент, Сделка, Стоки и Продукти)
     *
     *    Ct: 321. Суровини, материали, продукция, стоки       (Склад, Артикули)
     *
     *
     *
     * 3. Получаване на плащане (в някой случаи)
     *
     *    Dt: 501. Каси                  (Каса, Валута)
     *        503. Разпл. с/ки           (Сметка, Валута)
     *
     *    Ct: 411. Вземания от клиенти   (Клиент, Сделка, Валута)
     *
     * Такава транзакция се записва в журнала само при условие, че продабата е от текущата каса
     * и от текущия склад. В противен случай счетоводна транзакция не се прави. Вместо това,
     * първите две части се осчетоводяват при експедирането на стоката, а третата - при получа-
     * ване на плащане.
     *
     * @param int|object $id първичен ключ или запис на продажба
     *
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     *
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция
     */
    public function getTransaction($id)
    {
        $entries = array();
        $rec = $this->class->fetchRec($id);

        // Ако няма вальор е СЕГА, ако няма ръчно въведен курс - ВИНАГИ се взима този към вальора
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        $newRate = !empty($rec->currencyManualRate) ? $rec->currencyManualRate : currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null);
        if($rec->currencyRate != $newRate){
            $rec->_newCurrencyRate = $newRate;
        }

        $actions = type_Set::toArray($rec->contoActions);
        $rec = $this->fetchSaleData($rec); // Продажбата ще контира - нужни са и детайлите

        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            if(isset($rec->bankAccountId)){
                $ownBankRec = bank_OwnAccounts::fetch("#bankAccountId = {$rec->bankAccountId}", 'state');
                if(in_array($ownBankRec->state, array('closed', 'rejected'))){
                    acc_journal_RejectRedirect::expect(false, 'Банкова сметка в договора е закрита/оттеглена|*!');
                }
            }
        }

        if ($rec->doTransaction != 'no' && ($actions['ship'] || $actions['pay'])) {
            
            deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => true));
            
            if ($actions['ship']) {
                $entriesProduction = self::getProductionEntries($rec, $this->class);
                if (countR($entriesProduction)) {
                    $entries = array_merge($entries, $entriesProduction);
                }
                
                // Продажбата играе роля и на експедиционно нареждане.
                // Контирането е същото като при ЕН
                
                // Записите от тип 1 (вземане от клиент)
                $storable = array();
                $entries = array_merge($entries, $this->getTakingPart($rec, $storable));
                
                $delPart = $this->getDeliveryPart($rec, $storable);
                
                if (acc_Journal::throwErrorsIfFoundWhenTryingToPost() && countR($storable)) {
                    if($redirectError = deals_Helper::getContoRedirectError($storable, 'canStore', null, 'вече не са складируеми и не може да се изписват от склада')){
                        
                        acc_journal_RejectRedirect::expect(false, $redirectError);
                    }
                }
                
                if (is_array($delPart)) {
                    
                    // Записите от тип 2 (експедиция)
                    $entries = array_merge($entries, $delPart);
                }
            }
            
            if ($actions['pay']) {
                // Продажбата играе роля и на платежен документ (ПКО)
                // Записите от тип 3 (получаване на плащане)
                $entries = array_merge($entries, $this->getPaymentPart($rec));
            }
        }
        
        // Проверка дали артикулите отговарят на нужните свойства
        $products = arr::extractValuesFromArray($rec->details, 'productId');
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost() && countR($products)) {
            if($redirectError = deals_Helper::getContoRedirectError($products, 'canSell', 'generic', 'вече не са продаваеми или са генерични')){
                
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }
        }

        $transaction = (object) array(
            'reason' => 'Продажба #' . $rec->id,
            'valior' => $rec->valior,
            'entries' => $entries,
        );
        
        return $transaction;
    }
    
    
    /**
     * Помощен метод за извличане на данните на продажбата - мастър + детайли
     *
     * Детайлите на продажбата (продуктите) са записани в полето-масив 'details' на резултата
     *
     * @param int|object $id първичен ключ или запис на продажба
     * @param object запис на продажба (@see sales_Sales)
     */
    protected function fetchSaleData($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на продажбата
            $detailQuery = sales_SalesDetails::getQuery();
            $detailQuery->where("#saleId = '{$rec->id}'");
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     *
     *    Dt: 411. Вземания от клиенти                   (Клиент, Сделка, Валута)
     *
     *    Ct: 701. Приходи от продажби към Контрагенти   (Клиент, Сделка, Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги         (Клиент, Сделка, Услуга)
     *
     * ДДС за начисляване
     *
     *    Dt: 411. Вземания от клиенти                   (Клиент, Сделка, Валута)
     *
     *    Ct: 4530 - ДДС за начисляване
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getTakingPart($rec, &$storable)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        foreach ($rec->details as $detailRec) {
            // Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
            $canStore = cat_Products::fetchField($detailRec->productId, 'canStore', false);
            if($canStore == 'yes'){
                $storable[$detailRec->productId] = $detailRec->productId;
                $creditAccId = '701';
            } else {
                $creditAccId = '703';
            }
            
            $amount = $detailRec->amount;
            $discountVal = $detailRec->discount;
            if(!empty($detailRec->autoDiscount)){
                if(in_array($rec->state, array('draft', 'pending'))){
                    $discountVal = round((1- (1 - $discountVal)*(1 - $detailRec->autoDiscount)), 4);
                }
            }

            $amount = ($discountVal) ?  $amount * (1 - $discountVal) : $amount;
            $amount = round($amount, 2);
            
            $entries[] = array(
                'amount' => $amount * $rec->currencyRate, // В основна валута
                
                'debit' => array(
                    '411',
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                    array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $amount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    $creditAccId,
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                    array('cat_Products', $detailRec->productId), // Перо 3 - Продукт
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        if ($this->class->_total->vat) {
            $vat = $this->class->_total->vat;
            $vatAmount = $this->class->_total->vat * $rec->currencyRate;
            $entries[] = array(
                'amount' => $vatAmount, // В основна валута
                
                'debit' => array(
                    '411',
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                    array('currency_Currencies', $currencyId), // Перо 3 - Валута
                    'quantity' => $vat, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '4530',
                    array('sales_Sales', $rec->id),
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     *
     *    Dt: 501. Каси                  (Каса, Валута)
     *
     *    Ct: 411. Вземания от клиенти   (Клиент, Сделки, Валута)
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        expect($rec->caseId, 'Генериране на платежна част при липсваща каса!');
        $amountBase = $quantityAmount = 0;
        foreach ($rec->details as $detailRec) {
            $discount = $detailRec->discount;
            if(!empty($detailRec->autoDiscount)){
                if(in_array($rec->state, array('draft', 'pending'))){
                    $discount = round((1- (1 - $discount)*(1 - $detailRec->autoDiscount)), 4);
                }
            }
            $amount = ($discount) ?  $detailRec->amount * (1 - $discount) : $detailRec->amount;
            $amount = round($amount, 2);
            $amountBase += $amount;
        }
        
        if ($rec->chargeVat == 'separate' || $rec->chargeVat == 'yes') {
            $amountBase += $this->class->_total->vat;
        }
        
        $quantityAmount += $amountBase;

        // Ако има ръчно въведен курс
        if(!empty($rec->currencyManualRate) && round($rec->currencyManualRate, 4) != round($rec->rate, 4)){
            $entries[] = array(
                'amount' => $amountBase * $rec->currencyManualRate, // В основна валута към ръчно въведения курс
                'debit' => array(
                    '481', // Сметка "501. Каси"
                    array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),

                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                    array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),
            );

            // Плащането ще постъпи към курса за деня
            $actualRate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null);
            $entries[] = array(
                'amount' => $amountBase * $actualRate, // В основна валута
                'debit' => array(
                    '501', // Сметка "501. Каси"
                    array('cash_Cases', $rec->caseId),         // Перо 1 - Каса
                    array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),
                'credit' => array(
                    '481',
                    array('currency_Currencies', $currencyId),
                    'quantity' => $quantityAmount,
                ),
            );

        } else {
            $entries[] = array(
                'amount' => $amountBase * $rec->currencyRate, // В основна валута

                'debit' => array(
                    '501', // Сметка "501. Каси"
                    array('cash_Cases', $rec->caseId),         // Перо 1 - Каса
                    array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),

                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                    array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     *
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти    (Клиент, Сделки, Стоки и Продукти)
     *
     *    Ct: 321. Суровини, материали, продукция, стоки (Склад, Стоки и Продукти)
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        if (empty($rec->shipmentStoreId)) {
            
            return;
        }
        
        foreach ($rec->details as $detailRec) {
            $canStore = cat_Products::fetchField($detailRec->productId, 'canStore', false);
            if($canStore == 'yes'){
                $creditAccId = '321';
                $debitAccId = '701';
                
                $entries[] = array(
                    'debit' => array(
                        $debitAccId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                        array('cat_Products', $detailRec->productId), // Перо 3 - Продукт
                        'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
                    ),
                    
                    'credit' => array(
                        $creditAccId,
                        array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
                        'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
                    ),
                );
            }
        }
        
        return $entries;
    }
    
    
    /**
     * Връща всички експедирани продукти и техните количества по сделката
     */
    public static function getShippedProducts($jRecs, $rec, $accs = '703,706,701')
    {
        $res = array();
        
        // Извличаме тези, отнасящи се за експедиране
        $dInfo = acc_Balances::getBlAmounts($jRecs, $accs, 'credit');
        
        if (!countR($dInfo->recs)) {
            
            return $res;
        }
        
        foreach ($dInfo->recs as $p) {

             // Обикаляме всяко перо
            foreach (range(1, 3) as $i) {
                if (isset($p->{"creditItem{$i}"})) {
                    $itemRec = acc_Items::fetch($p->{"creditItem{$i}"});
                    
                    // Ако има интерфейса за артикули-пера, го добавяме
                    if (cls::haveInterface('cat_ProductAccRegIntf', $itemRec->classId)) {
                        $obj = new stdClass();
                        $obj->productId = $itemRec->objectId;
                        
                        $index = $obj->productId;
                        if (empty($res[$index])) {
                            $res[$index] = $obj;
                        }

                        $res[$index]->amount += deals_Helper::getSmartBaseCurrency($p->amount, $p->valior, $rec->valior);
                        $res[$index]->quantity += $p->creditQuantity;
                    }
                }
            }
        }
        
        foreach ($res as &$r) {
            if ($r->quantity) {
                $r->price = $r->amount / $r->quantity;
            }
        }
        
        // Връщаме масив със всички експедирани продукти по тази сделка
        return $res;
    }
    
    
    /**
     * Връща записите от журнала за това перо
     */
    public static function getEntries($id)
    {
        return acc_Journal::getEntries(array('sales_Sales', $id));
    }
    
    
    /**
     * Чисти работния кеш
     */
    public static function clearCache()
    {
        self::$cache = array();
    }
    
    
    /**
     * Колко е направеното авансово плащане досега
     */
    public static function getDownpayment($jRecs)
    {
        return acc_Balances::getBlAmounts($jRecs, static::DOWNPAYMENT_ACCOUNT_ID, 'credit')->amount;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getPaidAmount($jRecs, $rec)
    {
        // Взимаме количествата по валути
        $quantities = acc_Balances::getBlQuantities($jRecs, '411,412', 'credit', '501,503,481,482');
        $res = deals_Helper::convertJournalCurrencies($quantities, $rec->currencyId, $rec->valior);
        
        // К-то платено във валутата на сделката го обръщаме в основна валута за изравнявания
        $amount = $res->quantity;
        $amount *= $rec->currencyRate;
        
        return $amount;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getBlAmount($jRecs, $id)
    {
        $itemRec = acc_Items::fetchItem('sales_Sales', $id);
        $valior = sales_Sales::fetchField($id, 'valior');
        $paid = acc_Balances::getBlAmounts($jRecs, '411', null, null, array(null, $itemRec->id, null), array(), $valior)->amount;
        $paid += acc_Balances::getBlAmounts($jRecs, '412', null, null, array(null, $itemRec->id, null), array(), $valior)->amount;
        
        return $paid;
    }
    
    
    /**
     * Колко е доставено по сделката
     */
    public static function getDeliveryAmount($jRecs, $id)
    {
        $itemRec = acc_Items::fetchItem('sales_Sales', $id);
        $valior = sales_Sales::fetchField($id, 'valior');
        $delivered = acc_Balances::getBlAmounts($jRecs, '411', 'debit', null, array(null, $itemRec->id, null), array(), $valior)->amount;
        $delivered -= acc_Balances::getBlAmounts($jRecs, '411', 'debit', '7911', array(), array(), $valior)->amount;

        return $delivered;
    }
    
    
    /**
     * Колко е ддс-то за начисляване
     */
    public static function getAmountToInvoice($jRecs)
    {
        return -1 * acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    }
    
    
    /**
     * Връща записите за моментното производство на артикулите, ако има такива
     *
     * @param stdClass $rec
     * @throws acc_journal_RejectRedirect
     * @return array $entries
     */
    public static function getProductionEntries($rec, $class, $storeField = 'shipmentStoreId', &$instantProducts = array(), $productFieldName = 'productId', &$inputedMaterials = array())
    {
        $Class = cls::get($class);
        core_Debug::startTimer('FAST_PRODUCTION_ENTRIES');
        $entries = $bomDataCombined = array();
        if(!is_array($rec->details)) return $entries;

        $storeId = $rec->{$storeField};
        if($Class instanceof pos_Reports){
            $pointRec = pos_Points::fetch($rec->pointId);
            $storeId = !empty($storeId) ? $storeId : $pointRec->storeId;
        }

        foreach ($rec->details as $dRec1){
            // Ако имат моментна рецепта
            $instantBomRec = cat_Products::getLastActiveBom($dRec1->{$productFieldName}, 'instant');
            if(!is_object($instantBomRec)) continue;
            $quantity = $dRec1->quantity * $dRec1->quantityInPack;
            if(!array_key_exists($instantBomRec->id, $bomDataCombined)){
                $bomDataCombined[$instantBomRec->id] = (object)array('rec' => $instantBomRec, 'storeId' => $storeId, 'quantity' => 0, 'productId' => $dRec1->{$productFieldName});
                $instantProducts[$dRec1->{$productFieldName}] = $dRec1->{$productFieldName};
            }
            $bomDataCombined[$instantBomRec->id]->quantity += $quantity;
        }

        core_Debug::startTimer('FAST_PRODUCTION_BOM_DATA');

        foreach ($bomDataCombined as $bomData){

            // И тя има ресурси, произвежда се по нея
            $bomInfo = cat_Boms::getResourceInfo($bomData->rec, $bomData->quantity, $rec->valior);
            if(is_array($bomInfo['resources'])){
                foreach ($bomInfo['resources'] as &$resRec){
                    $resRec->quantity = $resRec->propQuantity;
                    $resRec->storeId = $bomData->storeId;
                    $resRec->fromAccId = '61102';

                    // Ако е инсталиран пакета за партидности
                    core_Debug::startTimer('CALC_BATCH_DATA');
                    if(core_Packs::isInstalled('batch') && $Class->allowInstantProductionBatches){
                        $canStore = cat_Products::fetchField($resRec->productId, 'canStore');
                        if($canStore == 'yes'){
                            if($Def = batch_Defs::getBatchDef($resRec->productId)){
                                if(!array_key_exists("{$resRec->storeId}|{$resRec->productId}", $inputedMaterials)){
                                    $inputedMaterials["{$resRec->storeId}|{$resRec->productId}"] = (object)array('productId' => $resRec->productId, 'quantity' => 0, 'storeId' => $resRec->storeId, 'Def' => $Def);
                                    $inputedMaterials["{$resRec->storeId}|{$resRec->productId}"]->inStock = batch_Items::getBatchQuantitiesInStore($resRec->productId, $resRec->storeId);
                                }
                                $inputedMaterials["{$resRec->storeId}|{$resRec->productId}"]->quantity += $resRec->quantity * $resRec->quantityInPack;
                            }
                        }
                    }
                    core_Debug::stopTimer('CALC_BATCH_DATA');
                }

                // Извличане на записите за производството
                $isComplete = ($bomData->rec->isComplete == 'auto') ? cat_Setup::get('DEFAULT_BOM_IS_COMPLETE') : $bomData->rec->isComplete;
                $equalizePrimeCost = ($isComplete == 'no') ? true : null;
                $prodArr = planning_transaction_DirectProductionNote::getProductionEntries($bomData->productId, $bomData->quantity, $bomData->storeId, null, $class, $rec->id, null, $rec->valior, $bomInfo['expenses'], $bomInfo['resources'], null, $equalizePrimeCost);

                if(countR($prodArr)){
                    $entries = array_merge($entries, $prodArr);
                }
            }
        }

        core_Debug::stopTimer('FAST_PRODUCTION_BOM_DATA');
        core_Debug::log("GET FAST_PRODUCTION_BOM_DATA " . round(core_Debug::$timers["FAST_PRODUCTION_BOM_DATA"]->workingTime, 6));

        // Проверка дали материалите са вложими и генерични
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            $batchesArr = $productsWithMandatoryBatches = array();

            // Ще се прави опит за автоматично разпределяне на партиди при контиране
            core_Debug::startTimer('CALC_BATCH_DATA');
            core_Debug::startTimer('ALLOCATE_BATCH_DATA');
            foreach ($inputedMaterials as $iMat){
                $batches = $iMat->Def->allocateQuantityToBatches($iMat->quantity, $iMat->storeId, $class, $rec->id, $rec->valior);
                $iMat->_leftQuantity = $iMat->quantity;

                foreach ($batches as $b => $q){
                    $bRec = (object)array('productId' => $iMat->productId, 'operation' => 'out', 'storeId' => $iMat->storeId, 'quantity' => $q, 'quantityInPack' => 1, 'packagingId' => cat_Products::fetchField($iMat->productId, 'measureId'));
                    $bRec->detailClassId = $Class->getClassId();
                    $bRec->detailRecId = $rec->id;
                    $bRec->date = $rec->valior;
                    $bRec->containerId = $rec->containerId;
                    $bRec->batch = $b;
                    $bRec->isInstant = 'yes';
                    $batchesArr[] = $bRec;
                    $iMat->_leftQuantity -= $q;
                }
            }
            core_Debug::stopTimer('ALLOCATE_BATCH_DATA');
            core_Debug::stopTimer('CALC_BATCH_DATA');

            // Проверка за намерените партиди дали отговарят на изискванията
            foreach ($inputedMaterials as $iMat1){
                $checkIfBatchIsMandatory = ($iMat1->Def->getField('alwaysRequire') == 'auto') ? batch_Templates::fetchField($iMat1->Def->getField('templateId'), 'alwaysRequire') : $iMat1->Def->getField('alwaysRequire');
                if($checkIfBatchIsMandatory == 'yes'){

                    if(round($iMat1->_leftQuantity, 5) > 0) {
                        $productsWithMandatoryBatches[$iMat1->productId] = "<b>" . cat_Products::getTitleById($iMat1->productId, false) . "</b>";
                    }
                }
            }

            if(countR($productsWithMandatoryBatches)){
                $productMsg = implode(', ', $productsWithMandatoryBatches);
                acc_journal_RejectRedirect::expect(false, "Артикулите не могат да са без партида|*(2): {$productMsg}");
            }

            // Запис
            if (countR($batchesArr)) {
                cls::get('batch_BatchesInDocuments')->saveArray($batchesArr);
            }

            $shipped = array();
            foreach ($entries as $d) {
                if (in_array($d['credit'][0], array('60201', '321'))) {
                    $shipped[$d['credit'][2][1]] = $d['credit'][2][1];
                }
            }

            if($redirectError = deals_Helper::getContoRedirectError($shipped, 'canConvert', 'generic', 'трябва да са вложими и да не са генерични')){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }
        }

        core_Debug::stopTimer('FAST_PRODUCTION_ENTRIES');
        core_Debug::log("GET FAST_PRODUCTION_ENTRIES " . round(core_Debug::$timers["FAST_PRODUCTION_ENTRIES"]->workingTime, 6));
        core_Debug::log("GET CALC_BATCH_DATA " . round(core_Debug::$timers["CALC_BATCH_DATA"]->workingTime, 6));
        core_Debug::log("GET ALLOCATE_BATCH_DATA " . round(core_Debug::$timers["ALLOCATE_BATCH_DATA"]->workingTime, 6));

        return $entries;
    }
}
