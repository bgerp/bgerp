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
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed        $customerClass       - клас на контрагента
     * @param int          $customerId          - ид на контрагента
     * @param int          $productId           - ид на артикула
     * @param int          $packagingId         - ид на опаковка
     * @param float        $quantity            - количество
     * @param datetime     $datetime            - дата
     * @param float        $rate                - валутен курс
     * @param string       $chargeVat           - начин на начисляване на ддс
     * @param int|NULL     $listId              - ценова политика
     * @param bool         $quotationPriceFirst - дали първо да търси цена от последна оферта
     * @param int|null     $discountListId      - политика спрямо която да се изчислява отстъпката
     *
     * @return stdClass $rec->price  - цена
     *                  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = null, $quantity = null, $datetime = null, $rate = 1, $chargeVat = 'no', $listId = null, $quotationPriceFirst = true, $discountListId = null)
    {
        $lastPrices = sales_Sales::getLastProductPrices($customerClass, $customerId);
        if (!array_key_exists($productId, $lastPrices)) return;

        $price = is_object($lastPrices[$productId]) ? $lastPrices[$productId]->price : $lastPrices[$productId];
        $valior = is_object($lastPrices[$productId]) ? $lastPrices[$productId]->date : null;

        $pInfo = cat_Products::getProductInfo($productId);
        $quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
        $packPrice = $price * $quantityInPack;

        $packPrice = deals_Helper::getSmartBaseCurrency($packPrice, $valior, $datetime);
        $vat = cat_Products::getVat($productId);
        $packPrice = deals_Helper::getDisplayPrice($packPrice, $vat, $rate, $chargeVat);

        return (object) array('price' => deals_Helper::roundPrice($packPrice));
    }
}
