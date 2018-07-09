<?php


/**
 * Имплементация на ценова политика "По последна продажна цена"
 * Връща последната цена на която е продаден даден артикул
 * на този клиент (от последната контирана продажба в папката на
 * клиента)
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Политика "По последна продажна цена"
 */
class sales_SalesLastPricePolicy extends core_Mvc
{
    /**
     * Заглавие
     */
    public $title = 'Последна цена';
    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';
    
    
    /**
     * Връща последната цена за посочения продукт направена в
     * продажба към контрагента
     *
     * @return object $rec->price  - цена
     *                $rec->discount - отстъпка
     *                $rec->priority - приоритет на цената
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = null, $quantity = null, $date = null, $rate = 1, $chargeVat = 'no')
    {
        $lastPrices = sales_Sales::getLastProductPrices($customerClass, $customerId);
        
        if (!isset($lastPrices[$productId])) {
            
            return;
        }
        
        $pInfo = cat_Products::getProductInfo($productId);
        $quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
        $packPrice = $lastPrices[$productId] * $quantityInPack;
        
        $vat = cat_Products::getVat($productId);
        $packPrice = deals_Helper::getDisplayPrice($packPrice, $vat, $rate, $chargeVat);
        
        return (object) array('price' => deals_Helper::roundPrice($packPrice));
    }
}
