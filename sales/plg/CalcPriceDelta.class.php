<?php


/**
 * Плъгин за кеширане на делтата при продажба при контиране на документ
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://github.com/bgerp/ef/issues/6
 */
class sales_plg_CalcPriceDelta extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->detailSellPriceFld, 'price');
        setIfNot($mvc->detailDiscountPriceFld, 'discount');
        setIfNot($mvc->detailAutoDiscountFld, 'autoDiscount');
        setIfNot($mvc->detailQuantityFld, 'quantity');
        setIfNot($mvc->detailProductFld, 'productId');
        setIfNot($mvc->detailPackagingFld, 'packagingId');
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $clone = clone $rec;
        $clone->threadId = (isset($clone->threadId)) ? $clone->threadId : $mvc->fetchField($clone->id, 'threadId');
        $clone->folderId = (isset($clone->folderId)) ? $clone->folderId : $mvc->fetchField($clone->id, 'folderId');
        $clone->activatedOn = (isset($clone->activatedOn)) ? $clone->activatedOn : $mvc->fetchField($clone->id, 'activatedOn');
        $clone->activatedOn = dt::addSecs(1, $clone->activatedOn);

        $save = $mvc->getDeltaRecs($clone);
        if(is_array($save)){
            foreach ($save as &$dRec) {
                $dRec->threadId = $clone->threadId;
                $dRec->folderId = $clone->folderId;
                $dRec->containerId = $clone->containerId;
                
                $id = sales_PrimeCostByDocument::fetchField("#detailClassId = {$dRec->detailClassId} AND #detailRecId = {$dRec->detailRecId}");
                if (!empty($id)) {
                    $dRec->id = $id;
                }
            }

            $productArr = arr::extractValuesFromArray($save, 'productId');
            sales_LastSaleByContragents::updateDates($productArr,  $clone->folderId);
        }
        
        // Запис на делтите
        cls::get('sales_PrimeCostByDocument')->saveArray($save);
    }
    
    
    /**
     * Метод по подразбиране за подготовка на записите за делта
     * 
     * @param core_Mvc $mvc
     * @param array|null $res
     * @param stdClass $rec
     * @return void
     */
    public static function on_AfterGetDeltaRecs($mvc, &$res, $rec)
    {
        if(is_array($res)) return $res;

        $onlySelfValue = false;
        $dPercent = sales_Setup::get('DELTA_MIN_PERCENT_PRIME_COST');
        
        if ($mvc instanceof sales_Sales) {
            
            // Ако е продажба и не е експедирано, не се записва нищо
            $actions = type_Set::toArray($rec->contoActions);
            if (!isset($actions['ship'])) {
                $onlySelfValue = true;
            }
        } else {
            
            // Ако не е продажба но документа НЕ е в нишка на продажба, не се записва нищо
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if (!$firstDoc->isInstanceOf('sales_Sales')) {

                return;
            }
        }
        
        // По коя политика ще се изчислява делтата
        $res = array();
        $Cover = doc_Folders::getCover($rec->folderId);
        $deltaListId = cond_Parameters::getParameter($Cover->getClassId(), $Cover->that, 'deltaList');
        
        // Намиране на детайлите
        $Detail = cls::get($mvc->mainDetail);
        $detailClassId = $Detail->getClassId();
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$rec->id}");
        $query->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$mvc->detailProductFld}");
        
        $calcLiveSoDelta = sales_Setup::get('LIVE_CALC_SO_DELTAS');
        
        $TransportShipmentArr = null;
        if($mvc instanceof store_DocumentMaster){
            if($calcLiveSoDelta == 'yes'){
                $saleRec = doc_Threads::getFirstDocument($rec->threadId)->fetch('deliveryTermId,deliveryCalcTransport,deliveryData');
                
                // Ако има калкулатор се изчислява колко е общото обемно тегло на цялото ен
                if($Calculator = cond_DeliveryTerms::getTransportCalculator($saleRec->deliveryTermId)){
                    if($saleRec->deliveryCalcTransport == 'yes'){

                        $totalWeight = $totalVolume = 0;
                        $cloneQuery = clone $query;
                        $allDetails = $cloneQuery->fetchAll();
                        array_walk($allDetails, function($a) use (&$totalWeight, &$totalVolume){$totalWeight += $a->weight; $totalVolume += $a->volume;});

                        $logisticData = $mvc->getLogisticData($rec);
                        setIfNot($logisticData['toPCode'], '');
                        $saleRec->deliveryData = is_array($saleRec->deliveryData) ? $saleRec->deliveryData : array();
                        $deliveryData = $saleRec->deliveryData + array('deliveryCountry' => drdata_Countries::getIdByName($logisticData['toCountry']), 'deliveryPCode' => $logisticData['toPCode']);

                        $totalVolumicWeight = $Calculator->getVolumicWeight($totalWeight, $totalVolume, $saleRec->deliveryTermId, $deliveryData);
                        $TransportShipmentArr = array('Calculator' => $Calculator, 'totalVolumicWeight' => $totalVolumicWeight, 'deliveryData' => $deliveryData, 'deliveryTermId' => $saleRec->deliveryTermId);
                    }
                }
            }
        }

        // да записвам вальора а да подавам активирането
        $autoDiscountAmount = null;
        $valior = $mvc->getValiorValue($rec);
        while ($dRec = $query->fetch()) {
            $sellCostWithOriginalDiscount = $dRec->{$mvc->detailSellPriceFld};
            $autoDiscountAmount = null;
            $applyDiscount = true;

            if ($mvc instanceof sales_Sales) {

                // Ако документа е продажба, изчислява се каква му е себестойноста
                $primeCost = sales_PrimeCostByDocument::getPrimeCostInSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec, $deltaListId, $dRec->notes);
            } else {

                // Ако  е документ базиран на продажба - намират се сумите от нея
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                if($firstDoc->isInstanceOf('sales_Sales')){
                    $containerId = $firstDoc->fetchField('containerId');
                    $query1 = sales_PrimeCostByDocument::getQuery();
                    $query1->where("#productId = {$dRec->{$mvc->detailProductFld}} AND #containerId = {$containerId}");
                    $cloneQuery = clone $query1;
                    $query1->where("#quantity = {$dRec->{$mvc->detailQuantityFld}}");

                    // Ако има точно за това количество - тях
                    if($fRec1 = $query1->fetch()){
                        $autoDiscountAmount = $fRec1->autoDiscountAmount;
                        $sellCostWithOriginalDiscount = $fRec1->sellCostWithOriginalDiscount;
                        $applyDiscount = false;
                    } else {

                        // Ако няма средно притеглено
                        $cloneQuery->XPR('sumDellWithOriginalDiscount', 'double', 'SUM(#quantity * COALESCE(#sellCostWithOriginalDiscount, 0))');
                        $cloneQuery->XPR('sumAutoDiscountAmount', 'double', 'SUM(#quantity * COALESCE(#autoDiscountAmount, 0))');
                        $cloneQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
                        if($cRec = $cloneQuery->fetch()){
                            if(!empty($cRec->totalQuantity)){
                                $autoDiscountAmount = round($cRec->sumAutoDiscountAmount / $cRec->totalQuantity, 4);
                                $sellCostWithOriginalDiscount = round($cRec->sumDellWithOriginalDiscount / $cRec->totalQuantity, 4);
                                $applyDiscount = false;
                            }
                        }
                    }
                }

                $primeCost = null;
                if($calcLiveSoDelta != 'yes'){
                    
                    // Ако документа е към продажба, то се взима себестойноста от продажбата
                    $primeCost = sales_PrimeCostByDocument::getPrimeCostFromSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec->containerId, $deltaListId);
                }
                
                if(!isset($primeCost)){
                    
                    // Ако артикулът няма себестойност в продажбата, то се изчислява себестоността му към момента
                    if(isset($deltaListId)){
                        $primeCost = price_ListRules::getPrice($deltaListId, $dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $valior);
                    } else {
                        $primeCost = cat_Products::getPrimeCost($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $valior, price_ListRules::PRICE_LIST_COST);
                    }
                }
                
                // Ако ще се изчислява лайв себестойноста на ен смята се какъв би бил транспорта и се добавя към себестойността
                if(isset($primeCost) && $calcLiveSoDelta == 'yes' && isset($TransportShipmentArr) && $dRec->canStore == 'yes' && isset($Calculator)){

                    $volumicWeight = $Calculator->getVolumicWeight($dRec->weight, $dRec->volume, $TransportShipmentArr['deliveryTermId'], $TransportShipmentArr['deliveryData']);
                    $fee = $TransportShipmentArr['Calculator']->getTransportFee($TransportShipmentArr['deliveryTermId'], $volumicWeight, $TransportShipmentArr['totalVolumicWeight'], $TransportShipmentArr['deliveryData']);
                    
                    if(isset($fee['fee']) && $fee['fee'] > 0){
                        $singleFee = $fee['fee'] / $dRec->quantity;
                        $primeCost += $singleFee;
                    }
                }
            }

            $sellCost = $dRec->{$mvc->detailSellPriceFld};
            $discountCalced = $dRec->{$mvc->detailDiscountPriceFld};
            if (isset($dRec->{$mvc->detailDiscountPriceFld}) && $applyDiscount) {
                $sellCostWithOriginalDiscount = $sellCost * (1 - $dRec->{$mvc->detailDiscountPriceFld});
            }

            if(isset($dRec->{$mvc->detailAutoDiscountFld})){
                $autoDiscountAmount = $sellCostWithOriginalDiscount * $dRec->{$mvc->detailAutoDiscountFld};
                if(isset($dRec->{$mvc->detailDiscountPriceFld})){
                    $discountCalced = round((1 - (1 - $dRec->{$mvc->detailDiscountPriceFld}) * (1 - $dRec->{$mvc->detailAutoDiscountFld})), 4);
                } else {
                    $discountCalced = $dRec->{$mvc->detailAutoDiscountFld};
                }
            }

            if (isset($discountCalced)) {
                $sellCost = $sellCost * (1 - $discountCalced);
            }
            
            // Ако има параметър за корекция на делти: задава се
            $correctPercent = cond_Parameters::getParameter($Cover->getClassId(), $Cover->that, 'deltaCorrect');
            if (!empty($correctPercent)) {
                $sellCost = $sellCost * (1 - $correctPercent);
            }
            
            // Ако има продажна цена, и минимален % и няма себестойност, то се записва % от продажната цена
            if(isset($sellCost) && !empty($dPercent) && empty($primeCost)){
                $primeCost = $sellCost * (1 - $dPercent);
            }
            
            // Дали да се записва само себестойността
            if ($onlySelfValue === true) {
                $sellCost = null;
            }

            // Изчисляване на цената по политика
            $r = (object) array('valior' => dt::verbal2mysql($valior),
                'detailClassId' => $detailClassId,
                'detailRecId' => $dRec->id,
                'quantity' => $dRec->{$mvc->detailQuantityFld},
                'productId' => $dRec->{$mvc->detailProductFld},
                'sellCost' => $sellCost,
                'autoDiscountAmount' => $autoDiscountAmount,
                'sellCostWithOriginalDiscount' => $sellCostWithOriginalDiscount,
                'state'    => 'active',
                'isPublic' => cat_Products::fetchField($dRec->{$mvc->detailProductFld}, 'isPublic'),
                'contragentId' => $Cover->that,
                'contragentClassId' => $Cover->getClassId(),
                'activatedOn' => $rec->activatedOn,
                'primeCost' => $primeCost);
            
            $canStore = cat_products::fetchField($dRec->{$mvc->detailProductFld}, 'canStore');
            if($canStore == 'yes'){
                if($storeId = ($mvc instanceof sales_Sales) ? $rec->shipmentStoreId : (($mvc instanceof store_DocumentMaster) ? $rec->storeId : null)){
                    $r->storeId = $storeId;
                }
            }
            
            // Ако първия документ е обединяваща продажба
            $persons = null;
            if($firstDoc = doc_Threads::getFirstDocument($rec->threadId)){
                if($firstDoc->isInstanceOf('sales_Sales')){
                    $closedDocuments = $firstDoc->fetchField('closedDocuments');
                    
                    // Търговецът и инициаторът са тези от обеднинения договор където артикула е в най-голямо количество
                    if(!empty($closedDocuments)){
                        $persons = sales_PrimeCostByDocument::getDealerAndInitiatorFromCombinedDeals($dRec->{$mvc->detailProductFld}, $closedDocuments);
                    }
                }
            }
            
            if(!is_array($persons)){
                $persons = sales_PrimeCostByDocument::getDealerAndInitiatorId($rec->containerId);
            }
            
            $r->dealerId = $persons['dealerId'];
            $r->initiatorId = $persons['initiatorId'];
            $res[] = $r;
        }
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (haveRole('admin,ceo,debug') && ($data->rec->state == 'active' || $data->rec->state == 'closed')) {
            $data->toolbar->addBtn('Делти', array('sales_PrimeCostByDocument', 'list', 'documentId' => '#' . $mvc->getHandle($data->rec->id)), 'ef_icon=img/16/bug.png,title=Делти по документа,row=2');
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	$deltaQuery = sales_PrimeCostByDocument::getQuery();
    	$deltaQuery->where("#containerId = {$rec->containerId}");
    	while($deltaRec = $deltaQuery->fetch()){
    	    $deltaRec->state = $rec->state;
    	    cls::get('sales_PrimeCostByDocument')->save($deltaRec, 'state');
    	}
    }


    /**
     * При оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        // При оттегляне на контиран/приключен документ се обновява кешираната дата
        $rec = $mvc->fetchRec($id);
        if (in_array($rec->brState, array('active', 'closed'))) {
            sales_LastSaleByContragents::updateByMvc($mvc, $rec);
        }
    }


    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    protected static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        // При възстановяване на контиран/приключен документ се обновява кешираната дата
        $rec = $mvc->fetchRec($id);
        if(in_array($rec->state, array('active', 'closed'))){
            sales_LastSaleByContragents::updateByMvc($mvc, $rec);
        }
    }
}