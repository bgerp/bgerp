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
 * @title     Интерфейс за ценови политики
 */
class price_PolicyIntf
{
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     * $rec->priority - приоритет на цената (0, 1 или 2)
     */
    function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $date = NULL, $roundForDocument = FALSE)
    {
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId, $date, $roundForDocument);
    }
    
    
    /**
     * Заглавие на ценоразписа за конкретен клиент 
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->class->getPolicyTitle($customerClass, $customerId);
    }
}