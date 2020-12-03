<?php


/**
 * Добавя екшън към бизнес документ за автоматично добавяне на нов артикул
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_AddProductFields extends core_Plugin
{
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('salePrice', 'double(decimals=2)', 'caption=Настройки->Продажна цена');
        $mvc->FLD('maxSaleDiscount', 'percent', 'caption=Настройки->Максимална ТО');
        $mvc->FLD('deliveryPrice', 'double(decimals=2)', 'caption=Настройки->Доставна цена');
        $mvc->FLD('storePlace', 'varchar', 'caption=Настройки->Участък от склада');
    }
}