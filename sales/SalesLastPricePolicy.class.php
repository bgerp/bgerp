<?php



/**
* Имплементация на ценова политика "По последна продажна цена"
* Връща последната цена на която е продаден даден артикул
* на този клиент (от последната контирана продажба в папката на
* клиента)
*
* @category  bgerp
* @package   sales
* @author    Ivelin Dimov <ivelin_pdimov@abv.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Политика "По последна продажна цена"
*/
class sales_SalesLastPricePolicy extends core_Manager
{
    /**
     * Заглавие
     */
    var $title = 'Последна цена';


    /**
     * Интерфейс за ценова политика
     */
    var $interfaces = 'price_PolicyIntf';
    
    
    /**
     * Връща последната цена за посочения продукт направена в
     * продажба към контрагента
     * 
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     * 				  $rec->priority - приоритет на цената
     */
    function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $date = NULL, $rate = 1, $chargeVat = 'no')
    {
    	$lastPrices = sales_Sales::getLastProductPrices($customerClass, $customerId);
        if(!isset($lastPrices[$productId])) return NULL;
        
        $pInfo = cls::get($productManId)->getProductInfo($productId);
        $quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
        $packPrice = $lastPrices[$productId] * $quantityInPack;
    	
        $vat = cls::get($productManId)->getVat($productId);
        $packPrice = deals_Helper::getDisplayPrice($packPrice, $vat, $rate, $chargeVat);
       
        return (object)array('price' => deals_Helper::roundPrice($packPrice));
    }
}