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
 * @copyright 2006 - 2022 Experta OOD
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
     * Съобщение, което да се покаже на потребителя, ако няма намерена цена
     */
    public $notFoundPriceErrorMsg = "Артикулът не е продаван досега на клиента. Въведете цена|*!";


    /**
     * Връща цената на продукта на посочения клиент
     *
     * @param int                          $customerClass       - ид на класа на контрагента
     * @param int                          $customerId          - ид на клиента
     * @param int                          $productId           - ид на продукта
     * @param int                          $packagingId         - ид на опаковка
     * @param float                        $quantity            - количество
     * @param datetime                     $date                - към коя дата искаме цената
     * @param float                        $rate                - валутен курс
     * @param string $chargeVat           - да се начислявали ДДС или не върху цената
     * @param int|NULL                     $listId              - ценова политика
     * @param bool                         $quotationPriceFirst - Дали първо да търси цена от последна оферта
     *
     * @return object
     *                $rec->price  - цена
     *                $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = null, $quantity = null, $date = null, $rate = 1, $chargeVat = 'no', $listId = null, $quotationPriceFirst = true)
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
