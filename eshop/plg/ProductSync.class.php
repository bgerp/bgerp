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
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (eshop_Products::haveRightFor('linktoeshop', (object) array('productId' => $data->rec->id))) {
            $data->toolbar->addBtn('E-маг', array('eshop_Products', 'linktoeshop', 'productId' => $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/star_2.png,title=Свързване в Е-маг');
        }
        
        if ($domainId = cms_Domains::getCurrent('id', false)) {
            if ($eshopProductId = eshop_Products::getByProductId($data->rec->id, $domainId)) {
                if (eshop_Products::haveRightFor('single', $eshopProductId)) {
                    $data->toolbar->addBtn('E-артикул', array('eshop_Products', 'single', $eshopProductId, 'ret_url' => true), 'ef_icon = img/16/domain_names_advanced.png,title=Към е-артикула');
                }
            }
        }
    }
}