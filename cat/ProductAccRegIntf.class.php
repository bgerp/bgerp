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
        return $this->class->getProductInfo($productId, $packagingId);
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
        return $this->class->getProducts($customerClass, $customerId, $date);
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
    
    
	/**
     * Връща цената по себестойност на продукта
     * @return double
     */
    function getSelfValue($productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
        return $this->class->getSelfValue($productId, $packagingId, $quantity, $date);
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * @param mixed $properties - комбинация на горе посочените мета 
     * 							  данни или като масив или като стринг
     * @param int $limit      - лимит на показваните резултати
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    function getByProperty($properties, $limit = NULL)
    {
    	return $this->class->getByProperty($properties, $limit);
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $productId - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($productId, $sysId)
    {
    	return $this->class->getParam($productId, $sysId);
    }
    
    
    /**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
	public function getWeight($productId, $packagingId = NULL)
    {
    	return $this->class->getWeight($productId, $packagingId);
    }
    
    
    /**
     * Дали има поне един продаваем продукт за клиента
     */
    public function hasSellableProduct($contragentClassId, $contragentId, $date)
    {
    	return $this->class->hasSellableProduct($contragentClassId, $contragentId);
    }
    
    
    /**
     * Връща обема на еденица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
	public function getVolume($productId, $packagingId = NULL)
    {
    	return $this->class->getVolume($productId, $packagingId);
    }
    
    
    /**
     * Връща информация за основната опаковка на артикула
     * 
     * @param int $productId - ид на продукт
     * @return stdClass - обект с информация
     * 				->name     - име на опаковката
     * 				->quantity - к-во на продукта в опаковката
     * 				->classId  - ид на cat_Packagings или cat_UoM
     * 				->id       - на опаковката/мярката
     */
    public function getBasePackInfo($productId)
    {
    	return $this->class->getBasePackInfo($productId);
    }
}