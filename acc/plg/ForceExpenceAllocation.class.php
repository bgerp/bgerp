<?php


/**
 * Плъгин за документи при, чието контиране/оттегляне в нишки на сделки разходни обекти,
 * към които има автоматично разпределени разходи да се преразпределят
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_plg_ForceExpenceAllocation extends core_Plugin
{
    

    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        // Премахване на нотифицирането за контиране
        if ($rec->brState == 'active') {
            self::autoAllocate($mvc, $rec);
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        self::autoAllocate($mvc, $rec);
    }
    
    
    protected static function autoAllocate($mvc, $rec)
    {
        // Ще се задейства само при контиране или възстановяване на контиран документ в нишката
        if(!($rec->state == 'active' || ($rec->state == 'rejected' && $rec->brState == 'active'))){
            
            return;
        }
        
        // Кой е първия документ в нишката, ако е сделка и също така е разходен обект
        $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
        if(!$firstDocument->isInstanceOf('deals_DealMaster') || !acc_Items::isItemInList($firstDocument->getInstance(), $firstDocument->that, 'costObjects')){
            
            return;
        }
        
        // Върху кои артикули може да се разпределят разходите
        $expenseItemId = acc_Items::fetchItem($firstDocument->getInstance(), $firstDocument->that)->id;
        $correctableProducts = $firstDocument->getCorrectableProducts('acc_CostAllocations');
        
        // Кои разходи са отнесени към сделката, със зададено автоматично разпределяне
        $costQuery = acc_CostAllocations::getQuery();
        $costQuery->where("#expenseItemId = {$expenseItemId} AND #allocationBy != 'no'");
        $costQuery->show('id,quantity,allocationBy,containerId,productsData,allocationBy');
        
        // За всеки запис
        while ($costRec = $costQuery->fetch()){
            $selected = ($costRec->allocationBy == 'auto') ? $correctableProducts : $costRec->productsData;
            $copyArr = $correctableProducts;
            try{
                
                // Преразпределяне на разходите от документа, към артикулите от сделката
                $document = doc_Containers::getDocument($costRec->containerId);
                $intersected = array_intersect_key($copyArr, $selected);
                
                $error = acc_ValueCorrections::allocateAmount($intersected, $costRec->quantity, $costRec->allocationBy);
                if(empty($error)){
                    
                    // Ако успешно е преразпределено, записва се
                    $costRec->productsData = $intersected;
                    
                    // Ако вече няма артикули за преразпределяне, сменя се режима на автоматично
                    if(!countR($intersected) || is_null($intersected)){
                        $costRec->allocationBy = 'auto';
                    }
                    
                    $document->getInstance()->logWrite('Автоматично преразпределяне на разходи', $document->that);
                    acc_CostAllocations::save($costRec);
                } else {
                    $document->getInstance()->logErr($error, $document->that);
                }
            } catch(core_exception_Expect $e){
                reportException($e);
            }
        }
    }
}