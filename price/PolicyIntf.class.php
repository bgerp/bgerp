<?php


/**
 * Интерфейс за ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за ценови политики
 */
class price_PolicyIntf
{

    /**
     * Съобщение, което да се покаже на потребителя, ако няма намерена цена
     */
    public $notFoundPriceErrorMsg;


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
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat, $listId, $quotationPriceFirst, $discountListId);
    }
}
