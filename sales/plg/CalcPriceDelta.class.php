<?php



/**
 * Плъгин за кеширане на делтата при продажба при контиране на документ
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
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
        $save = array();
        $onlySelfValue = false;
        
        if ($mvc instanceof sales_Sales) {
            
            // Ако е продажба и не е експедирано, не се записва нищо
            $actions = type_Set::toArray($rec->contoActions);
            if (!isset($actions['ship'])) {
                $onlySelfValue = true;
            }
        } else {
            
            // Ако не е продажба но документа НЕ е в нишка на продажба, не се записва нищо
            $threadId = (isset($rec->threadId)) ? $rec->threadId : $mvc->fetchField($rec->id, 'threadId');
            $firstDoc = doc_Threads::getFirstDocument($threadId);
            if (!$firstDoc->isInstanceOf('sales_Sales')) {
                return;
            }
        }
        
        $folderId = (isset($rec->folderId)) ? $rec->folderId : $mvc->fetchField($rec->id, 'folderId');
        
        // По коя политика ще се изчислява делтата
        $Cover = doc_Folders::getCover($folderId);
        $primeCostListId = cond_Parameters::getParameter($Cover->getClassId(), $Cover->that, 'deltaList');
        if (empty($primeCostListId)) {
            $primeCostListId = price_ListRules::PRICE_LIST_COST;
        }
        
        // Намиране на детайлите
        $Detail = cls::get($mvc->mainDetail);
        $detailClassId = $Detail->getClassId();
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$rec->id}");
        
        $valior = $rec->{$mvc->valiorFld};
        while ($dRec = $query->fetch()) {
            if ($mvc instanceof sales_Sales) {
                $primeCost = sales_PrimeCostByDocument::getPrimeCostInSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec, $primeCostListId);
            } else {
                $primeCost = sales_PrimeCostByDocument::getPrimeCostFromSale($dRec->{$mvc->detailProductFld}, $dRec->{$mvc->detailPackagingFld}, $dRec->{$mvc->detailQuantityFld}, $rec->containerId, $primeCostListId);
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
            
            if ($onlySelfValue === true) {
                $sellCost = null;
            }
            
            // Изчисляване на цената по политика
            $r = (object) array('valior' => $valior,
                               'detailClassId' => $detailClassId,
                               'detailRecId' => $dRec->id,
                               'containerId' => $rec->containerId,
                               'quantity' => $dRec->{$mvc->detailQuantityFld},
                               'productId' => $dRec->{$mvc->detailProductFld},
                               'sellCost' => $sellCost,
                               'primeCost' => $primeCost);
            
            $persons = sales_PrimeCostByDocument::getDealerAndInitiatorId($rec->containerId);
            
            $r->dealerId = $persons['dealerId'];
            $r->initiatorId = $persons['initiatorId'];
            
            $id = sales_PrimeCostByDocument::fetchField("#detailClassId = {$detailClassId} AND #detailRecId = {$dRec->id}");
            if (!empty($id)) {
                $r->id = $id;
            }
            
            $save[] = $r;
        }
        
        // Запис
        cls::get('sales_PrimeCostByDocument')->saveArray($save);
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (haveRole('admin,ceo,debug') && ($data->rec->state == 'active' || $data->rec->state == 'closed')) {
            $data->toolbar->addBtn('Делти', array('sales_PrimeCostByDocument', 'list', 'documentId' => '#' . $mvc->getHandle($data->rec)), 'ef_icon=img/16/bug.png,title=Делти по документа,row=2');
        }
    }
}
