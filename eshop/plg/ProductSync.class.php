<?php


/**
 * Плъгин синхронизиращ състоянието на ешоп артикулите с артикулите
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eshop_plg_ProductSync extends core_Plugin
{
    
    
    /**
     * Обновява състоянието на детайлите на е-артикула с тези на Артикула
     *
     * @param int $productId - ид или запис на артикул
     *
     * @return void
     */
    private static function syncStatesByProductId($productId)
    {
        $productId = is_object($productId) ? $productId->id : $productId;
        $pState = cat_Products::fetchField($productId, 'state');
        
        $Details = cls::get('eshop_ProductDetails');
        $dQuery = $Details->getQuery();
        $dQuery->where("#productId = {$productId}");
        while($dRec = $dQuery->fetch()){
            if($dRec->state == 'active' && $pState != 'active'){
                $dRec->state = 'closed';
            } elseif($dRec->state == 'closed' && $pState == 'active'){
                $dRec->state = 'active';
            }
            
            $Details->save_($dRec, 'state');
        }
    }
    
    
    /**
     * След промяна на състоянието
     */
    public static function on_AfterChangeState($mvc, &$rec, $action)
    {
        self::syncStatesByProductId($rec->id);
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        self::syncStatesByProductId($id);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        self::syncStatesByProductId($id);
    }
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['eshopProductDetail'] = 'eshop_ProductDetails';
        $details = arr::fromArray($details);
    }
}