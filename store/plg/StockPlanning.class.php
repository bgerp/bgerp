<?php


/**
 * Клас 'store_plg_Requests' за записване на заявените количества
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_StockPlanning extends core_Plugin
{


    /**
     * Метод по подразбиране връщащ планираните наличности
     */
    public static function on_AfterGetPlannedStocks($mvc, &$res, $rec)
    {
        if(!$res){
            $res = array();
            $id = is_object($rec) ? $rec->id : $rec;
            $rec = $mvc->fetch($id, '*', false);

            if(empty($rec->{$mvc->storeFieldName}) || $rec->isReverse == 'yes') return;

            setIfNot($mvc->stockPlanningDirection, 'out');
            $date = !empty($rec->{$mvc->termDateFld}) ? $rec->{$mvc->termDateFld} : (!empty($rec->{$mvc->valiorFld}) ? $rec->{$mvc->valiorFld} : $rec->createdOn);

            if($mvc->mainDetail){
                $Detail = cls::get($mvc->mainDetail);
                $dQuery = $Detail->getQuery();
                $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$Detail->productFieldName}");
                $dQuery->XPR('totalQuantity', 'double', "SUM(#{$Detail->quantityFld})");
                $dQuery->where("#{$Detail->masterKey} = {$rec->id} AND #canStore = 'yes'");
                $dQuery->groupBy($Detail->productFieldName);

                while($dRec = $dQuery->fetch()){
                    $quantityIn = $quantityOut = null;
                    $var = ($mvc->stockPlanningDirection == 'out') ? 'quantityOut' : 'quantityIn';
                    $var = &${$var};
                    $var = $dRec->totalQuantity;

                    $res[] = (object)array('storeId' => $rec->{$mvc->storeFieldName},
                                           'productId' => $dRec->{$Detail->productFieldName},
                                           'date' => $date,
                                           'sourceClassId' => $mvc->getClassId(),
                                           'sourceId' => $rec->id,
                                           'quantityIn' => $quantityIn,
                                           'quantityOut' => $quantityOut);
                }
            }
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        $mvc->updatePlannedStocks($rec);
    }


    public static function on_AfterUpdatePlannedStocks($mvc, &$res, $rec)
    {
        setIfNot($mvc->updatePlannedStockOnChangeState, 'pending');
        $exState = $mvc->hasPlugin('doc_DocumentPlg') ? 'brState' : 'exState';

        if(!$res){
            if($rec->state == $mvc->updatePlannedStockOnChangeState){

                store_StockPlanning::remove($mvc, $rec->id);
                $plannedStocks = $mvc->getPlannedStocks($rec);
                if(countR($plannedStocks)){
                    $now = dt::now();
                    array_walk($plannedStocks, function($a) use ($now) {$a->createdOn = $now;});
                    $Stocks = cls::get('store_StockPlanning');
                    $Stocks->saveArray($plannedStocks);
                }
            } elseif($rec->state != $mvc->updatePlannedStockOnChangeState && $rec->{$exState} == $mvc->updatePlannedStockOnChangeState){
                store_StockPlanning::remove($mvc, $rec->id);
            }
        }
    }
}