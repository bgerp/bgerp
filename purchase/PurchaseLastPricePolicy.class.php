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
        $date =  !empty($datetime) ? $datetime : dt::today();

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
            $detailQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');
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
            $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
            $vat = cat_Products::getVat($productId, $date, $vatExceptionId);
            $rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat);
        }

        return $rec;
    }
}
