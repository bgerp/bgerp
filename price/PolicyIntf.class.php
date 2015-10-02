<?php



/**
 * Интерфейс за ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за ценови политики
 */
class price_PolicyIntf
{
    
    
    /**
     * Връща цената на продукта на посочения клиент
     * 
     * @param int $customerClass - ид на класа на контрагента
     * @param int $customerId - ид на клиента
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковка
     * @param double $quantity - количество
     * @param datetime $date - към коя дата искаме цената
     * @param double $rate - валутен курс
     * @param enum(yes,no,export,separate) $chargeVat - да се начислявали ДДС или не върху цената
     * 
     * @return object
     * 			$rec->price  - цена
     * 			$rec->discount - отстъпка
     */
    function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $date = NULL, $rate = 1, $chargeVat = 'no')
    {
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $packagingId, $date, $rate, $chargeVat);
    }
}