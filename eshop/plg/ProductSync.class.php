<?php


/**
 * Плъгин синхронизиращ състоянието на ешоп артикулите с артикулите
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eshop_plg_ProductSync extends core_Plugin
{
    
    /**
     * След промяна на състоянието
     */
    public static function on_AfterChangeState($mvc, &$rec, $action)
    {
        eshop_ProductDetails::syncStatesByProductId($rec->id);
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
        eshop_ProductDetails::syncStatesByProductId($id);
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
        eshop_ProductDetails::syncStatesByProductId($id);
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