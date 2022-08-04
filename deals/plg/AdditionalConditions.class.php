<?php


/**
 * Плъгин позволяващ добавяне на допълнителни условия на обекти към документи
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_AdditionalConditions extends core_Plugin
{

    protected static $map = array('sales_Sales' => array('bg' => 'conditionSaleBg', 'en' => 'conditionSaleEn'),
                                  'purchase_Purchases' => array('bg' => 'conditionPurchaseBg', 'en' => 'conditionPurchaseEn'),
                                  'store_ShipmentOrders' => array('bg' => 'conditionExpBg', 'en' => 'conditionExpEn'),);


    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $addDocumentFields = arr::make($mvc->additionalConditionsToDocuments, true);

        if(isset($addDocumentFields['sales_Sales'])){
            $mvc->FLD('conditionSaleBg', 'text(rows=2)', 'caption=Допълнителни условия към Продажба->BG,autohide');
            $mvc->FLD('conditionSaleEn', 'text(rows=2)', 'caption=Допълнителни условия към Продажба->EN,autohide');
        }

        if(isset($addDocumentFields['sales_Sales'])){
            $mvc->FLD('conditionPurchaseBg', 'text(rows=2)', 'caption=Допълнителни условия към Покупка->BG,autohide');
            $mvc->FLD('conditionPurchaseEn', 'text(rows=2)', 'caption=Допълнителни условия към Покупка->EN,autohide');
        }

        if(isset($addDocumentFields['store_ShipmentOrders'])){
            $mvc->FLD('conditionExpBg', 'text(rows=2)', 'caption=Допълнителни условия към ЕН->BG,autohide');
            $mvc->FLD('conditionExpEn', 'text(rows=2)', 'caption=Допълнителни условия към ЕН->EN,autohide');
        }
    }


    /**
     * Помощен метод връщащ допълнителните условия на обекта към документ
     *
     * @param $mvc
     * @param $res
     * @param $objectOd
     * @param $class
     * @param $lg
     * @return void
     */
    public static function on_AfterGetDocumentConditionFor($mvc, &$res, $objectOd, $class, $lg = null)
    {
        if(!$res){
            $lg = isset($lg) ? $lg : core_Lg::getCurrent();
            $Class = cls::get($class);
            $field = static::$map[$Class->className][$lg];

            if($field){
                if($mvc->getField($field, false)){
                    $value = $mvc->fetchField($objectOd, $field);
                    if(!empty($value)){
                        $res = $value;
                    }
                }
            }
        }
    }
}