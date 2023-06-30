<?php


/**
 * Клас 'store_plg_StockPlanning' за планиране на наличностите по хоризонт
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_StockPlanning extends core_Plugin
{


    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        setIfNot($mvc->updatePlannedStockOnChangeStates, array('pending'));
        setIfNot($mvc->stockPlanningDirection, 'out');
        setIfNot($mvc->updateStocksOnShutdown, array());
        setIfNot($mvc->exStateField, $mvc->hasPlugin('doc_DocumentPlg') ? 'brState' : 'exState');
        setIfNot($mvc->filterFutureOptions, true);

        $mvc->declareInterface('store_StockPlanningIntf');
    }


    /**
     * За коя дата се заплануват наличностите, дефолтна реализация
     */
    public static function on_AfterGetPlannedQuantityDate($mvc, &$res, $rec)
    {
        if(!$res) {
            $res = !empty($rec->{$mvc->termDateFld}) ? $rec->{$mvc->termDateFld} : (!empty($rec->{$mvc->valiorFld}) ? $rec->{$mvc->valiorFld} : $rec->createdOn);
        }
    }


    /**
     * Метод по подразбиране връщащ планираните наличности
     */
    public static function on_AfterGetPlannedStocks($mvc, &$res, $rec)
    {
        if(!$res){
            $res = array();

            // За всеки случаи, се подсигуряваме, че река е пълен!
            $id = is_object($rec) ? $rec->id : $rec;
            $rec = $mvc->fetch($id, '*', false);

            if(!in_array($rec->state, $mvc->updatePlannedStockOnChangeStates) && (!($mvc instanceof deals_DealMaster) && empty($rec->{$mvc->storeFieldName})) || (($mvc instanceof store_Receipts) && $rec->isReverse == 'yes')) return;
            $date = $mvc->getPlannedQuantityDate($rec);
            if($mvc->mainDetail){

                // Ако има детайл извличат се сумарно какви количества трябва да се запазят
                $Detail = cls::get($mvc->mainDetail);
                $dQuery = $Detail->getQuery();
                $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$Detail->productFld}");
                $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey={$Detail->productFld}");
                $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey={$Detail->productFld}");
                $dQuery->XPR('totalQuantity', 'double', "SUM(#{$Detail->quantityFld})");
                $dQuery->where("#{$Detail->masterKey} = {$rec->id} AND #canStore = 'yes'");
                $dQuery->groupBy($Detail->productFld);

                // Добавяне на складируемите артикули от детайла на документа
                while($dRec = $dQuery->fetch()){
                    $quantityIn = $quantityOut = null;
                    $var = ($mvc->stockPlanningDirection == 'out') ? 'quantityOut' : 'quantityIn';
                    $var = &${$var};
                    $var = $dRec->totalQuantity;

                    $genericProductId = null;
                    if($dRec->generic == 'yes'){
                        $genericProductId = $dRec->{$Detail->productFld};
                    } elseif($dRec->canConvert == 'yes'){
                        $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->{$Detail->productFld}}", 'genericProductId');
                    }

                    $res[] = (object)array('storeId' => $rec->{$mvc->storeFieldName},
                                           'productId' => $dRec->{$Detail->productFld},
                                           'date' => $date,
                                           'quantityIn' => $quantityIn,
                                           'quantityOut' => $quantityOut,
                                           'threadId' => $rec->threadId,
                                           'genericProductId' => $genericProductId);
                }
            }
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        $mvc->updatePlannedStocks($rec);
    }


    /**
     * Рекалкулиране на плануването по основния документ, ако има такъв
     *
     * @param $mvc
     * @param $rec
     */
    private static function recalcOriginPlannedStocks($mvc, $rec)
    {
        if(isset($rec->threadId)){
            if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)) {

                if ($firstDocument->isInstanceOf('planning_Tasks')) {
                    $firstDocument = doc_Containers::getDocument($firstDocument->fetchField('originId'));
                } elseif ($mvc instanceof deals_DealMaster || $firstDocument->isInstanceOf('findeals_Deals') || $mvc instanceof planning_Jobs || $firstDocument->isInstanceOf('store_Transfers') || $firstDocument->isInstanceOf('planning_ConsumptionNotes') || $firstDocument->isInstanceOf('planning_ReturnNotes') || $firstDocument->isInstanceOf('store_ConsignmentProtocols')) {
                    $firstDocument = null;
                }

                // Ако има първи документ в треда да му се обновят запазените
                if (isset($firstDocument)) {
                    $firstDocument->getInstance()->updateStocksOnShutdown[$firstDocument->that] = $firstDocument->that;
                }
            }
        }
    }


    /**
     * След обновяване на данните за запазване
     */
    public static function on_AfterUpdatePlannedStocks($mvc, &$res, $rec)
    {
        if(!$res){
            if(in_array($rec->state, $mvc->updatePlannedStockOnChangeStates)){
                store_StockPlanning::updateByDocument($mvc, $rec->id);
                self::recalcOriginPlannedStocks($mvc, $rec);
            } elseif(!in_array($rec->state, $mvc->updatePlannedStockOnChangeStates) && in_array($rec->{$mvc->exStateField}, $mvc->updatePlannedStockOnChangeStates)){
                store_StockPlanning::remove($mvc, $rec->id);
                self::recalcOriginPlannedStocks($mvc, $rec);
            }
        }
    }


    /**
     * Контиране на счетоводен документ
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        self::recalcOriginPlannedStocks($mvc, $rec);
    }


    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterRestore($mvc, &$res, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        self::recalcOriginPlannedStocks($mvc, $rec);
    }


    /**
     * След оттегляне да се обновяват запазванията по първия документ в нишката
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        self::recalcOriginPlannedStocks($mvc, $rec);
    }


    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
       if(is_array($mvc->updateStocksOnShutdown)){

           // Обновяване на планираните количества на всички заопашени документи
           foreach ($mvc->updateStocksOnShutdown as $id) {
               store_StockPlanning::updateByDocument($mvc, $id);
           }
       }
    }


    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Хоризонти', array('store_StockPlanning', 'list', 'threadId' => $rec->threadId), null, 'ef_icon=img/16/bug.png,title=Разглеждане на хоризонтите,row=3');
        }
    }
}