<?php



/**
 * Интерфейс за ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стоки и продукти
 */
class price_PolicyIntf
{
    
    /**
     * Връща продуктие, които могат да се продават на посочения клиент, 
     * съгласно имплементиращата този интерфейс ценова политика
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
        return $this->class->getProducts($customerClass, $customerId, $date = NULL);
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $packagingId, $date);
    }
    
}
