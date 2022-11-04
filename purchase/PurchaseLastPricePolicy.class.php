<?php


/**
 * Имплементация на ценова политика "По последна покупна цена"
 * Връща последната цена на която е купен даден артикул
 * от този клиент (от последната контирана покупка в папката на
 * клиента)
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Политика "По последна покупна цена"
 */
class purchase_PurchaseLastPricePolicy extends core_Mvc
{
    /**
     * Заглавие
     */
    public $title = 'Последна покупна цена';
    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';


    /**
     * Съобщение, което да се покаже на потребителя, ако няма намерена цена
     */
    public $notFoundPriceErrorMsg = "Артикулът не е използван досега в оферта или покупка. Въведете цена|*!";


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
        $date =  !empty($date) ? $date : dt::today();

        // Проверява се имали последна цена по оферта
        if ($quotationPriceFirst === true) {
            $rec = purchase_QuotationDetails::getPriceInfo($customerClass, $customerId, $date, $productId, $packagingId, $quantity);
        }

        // Ако няма цена по оферта или не се изисква
        if (empty($rec->price)) {
            // Намира последната цена на която продукта е бил продаден на този контрагент
            $detailQuery = purchase_PurchasesDetails::getQuery();
            $detailQuery->EXT('contragentClassId', 'purchase_Purchases', 'externalName=contragentClassId,externalKey=requestId');
            $detailQuery->EXT('contragentId', 'purchase_Purchases', 'externalName=contragentId,externalKey=requestId');
            $detailQuery->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');
            $detailQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
            $detailQuery->where("#contragentClassId = {$customerClass}");
            $detailQuery->where("#contragentId = {$customerId}");
            $detailQuery->where("#valior <= '{$date}'");
            $detailQuery->where("#productId = '{$productId}'");
            $detailQuery->where("#state = 'active' OR #state = 'closed'");
            $detailQuery->orderBy('#valior,#id', 'DESC');
            $lastRec = $detailQuery->fetch();
            if (!$lastRec) return;

            $rec = (object)array('price' => $lastRec->price, 'discount' => $lastRec->discount);
        }

        if (!is_null($rec->price)) {
            $vat = cat_Products::getVat($productId);
            $rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat);
        }

        return $rec;
    }
}
