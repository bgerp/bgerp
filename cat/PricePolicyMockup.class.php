<?php



/**
* Мокъп клас-имплементация на price_PolicyIntf
*
*
* @category  bgerp
* @package   cat
* @author    Stefan Stefanov <stefan.bg@gmail.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Тест ценова политика
*/
class cat_PricePolicyMockup extends core_Manager
{
    /**
     * Заглавие
     */
    var $title = 'Тест ценова политика';


    /**
     * Интерфейс за ценова политика
     */
    var $interfaces = 'price_PolicyIntf';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        return FALSE;
    }
    
    
    /**
     * Продуктите с id <= 4
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
        /* @var $query core_Query */
        $query = cat_Products::getQuery();
        
        $query->where("#id <= 4");
        $query->orderBy("createdOn");
        
        $products = array();
        
        while ($rec = $query->fetch()) {
            $products[$rec->id] = $rec->name;
        }
        
        return $products;
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
        expect($productId <= 4);
        
        if (!is_null($customerClass)) {
            $customerClass = core_Classes::getId($customerClass);
            expect($customerId);
        } else {
            $customerId = 0;
        }
        
        $price = "{$productId}.{$customerId}";
        $discount = 0;

        if ($packagingId) {
            $discount = "0.{$packagingId}";
        }
        
        if ($quantity > 100) {
            $discount += 0.1;
        }
        
        if ($customerClass) {
            if ($discount && cls::get($customerClass) instanceof crm_Persons) {
                $discount = -$discount;
            }
        } else {
            $price *= 2; // Анонимни клиенти
        }
        
        return (object)compact('price', 'discount');
    }
}