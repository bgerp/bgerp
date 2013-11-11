<?php



/**
 * Интерфейс за пера - продукти
 *
 * Този интерфейс трябва да се поддържа от всички регистри, които
 * Представляват материални ценности с които се извършват покупко-продажби
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за пера, които са стоки и продукти
 */
class cat_ProductAccRegIntf extends acc_RegisterIntf
{
    
    
    /**
     * Връща id-то на основната мярка на продукта
     *
     * @param int $productId id на записа на продукта
     * @return key(mvc=cat_UoM) ключ към записа на основната мярка на продукта
     */
    function getProductUoM($productId)
    {
        return $this->class->getProductUOM($productId);
    }
    
    
    /**
     * Връща запис, съдържащ информация за продукта
     *
     * @param int $productId id на записа на продукта
     * @param int $packagingId за коя опаковка се иска инфо. NULL - всички възможни опаковки на
     *                         продукта.
     *
     * @return stdClass обект със следните полета:
     * 
     *   ->productRec   - запис на продукта в модела му
     *   ->packagings   - масив от всички възможни опаковки на продукта (ако $packagingId == NULL)
     *   ->packagingRec - запис на опаковка от модела й (ако $packagingId != NULL)
     */
    function getProductInfo($productId, $packagingId = NULL)
    {
        return $this->class->getProductInfo($productId, $packagingId = NULL);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function isDimensional()
    {
        return TRUE;
    }
    
    
    /**
     * Връща масив с опаковките на, в които може да се слага даден продукт,
     * във вид подходящ за опции на key
     */
    function getPacks($productId)
    {
    	return $this->class->getPacks($productId);
    }
    
    

    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
        return $this->class->getProducts($customerClass, $customerId, $date = NULL);
    }
    
    
	/**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * Спрямо ценовите политики които използва
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
        return $this->class->getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId, $date);
    }
}
