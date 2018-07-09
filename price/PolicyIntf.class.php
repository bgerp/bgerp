<?php


/**
 * Интерфейс за ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за ценови политики
 */
class price_PolicyIntf
{
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
     * @param enum(yes,no,export,separate) $chargeVat           - да се начислявали ДДС или не върху цената
     * @param int|NULL                     $listId              - ценова политика
     * @param bool                         $quotationPriceFirst - Дали първо да търси цена от последна оферта
     *
     * @return object
     *                $rec->price  - цена
     *                $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = null, $quantity = null, $date = null, $rate = 1, $chargeVat = 'no', $listId = null, $quotationPriceFirst = true)
    {
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $packagingId, $quantity, $date, $rate, $chargeVat, $listId, $quotationPriceFirst);
    }
}
