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
        
        $res = array();
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
        $Cover = doc_Folders::getCover($rec->folderId);
        $deltaListId = cond_Parameters::getParameter($Cover->getClassId(), $Cover->that, 'deltaList');
        
        // Намиране на детайлите
        $Detail = cls::get($mvc->mainDetail);
        $detailClassId = $Detail->getClassId();
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$rec->id}");
        
        $valior = $rec->{$mvc->valiorFld};
        while ($dRec = $query->fetch()) {
            if ($mvc instanceof sales_Sales) {
                
                // Ако документа е продажба, изчислява се каква му е себестойноста
                $primeCost = sales_PrimeCostByDocument::getPrimeCostInSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec, $deltaListId);
            } else {
                
                // Ако документа е към продажба, то се взима себестойноста от продажбата
                $primeCost = sales_PrimeCostByDocument::getPrimeCostFromSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec->containerId, $deltaListId);
                if(!isset($primeCost)){
                    
                    // Ако артикулът няма себестойност в продажбата, то се изчислява себестоността му към момента
                    if(isset($deltaListId)){
                        $primeCost = price_ListRules::getPrice($deltaListId, $dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $valior);
                    } else {
                        $primeCost = cat_Products::getPrimeCost($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $valior, price_ListRules::PRICE_LIST_COST);
                    }
                }
            }
            
            $sellCost = $dRec->{$mvc->detailSellPriceFld};
            if (isset($dRec->{$mvc->detailDiscountPriceFld})) {
                $sellCost = $sellCost * (1 - $dRec->{$mvc->detailDiscountPriceFld});
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
            $r = (object) array('valior' => $valior,
                'detailClassId' => $detailClassId,
                'detailRecId' => $dRec->id,
                'quantity' => $dRec->{$mvc->detailQuantityFld},
                'productId' => $dRec->{$mvc->detailProductFld},
                'sellCost' => $sellCost,
                'state'    => 'active',
                'isPublic' => cat_Products::fetchField($dRec->{$mvc->detailProductFld}, 'isPublic'),
                'contragentId' => $Cover->that,
                'contragentClassId' => $Cover->getClassId(),
                'primeCost' => $primeCost);
            
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
}