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
     * Метод връщаш информация за продукта и неговите опаковки
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res
     * 	-> productRec - записа на продукта
     *  ->isPublic - дали е публичен или частен
     * 	->meta - мета данни за продукта ако има
	 * 	     meta['canSell'] 		- дали може да се продава
	 * 	     meta['canBuy']         - дали може да се купува
	 * 	     meta['canConvert']     - дали може да се влага
	 * 	     meta['canStore']       - дали може да се съхранява
	 * 	     meta['canManifacture'] - дали може да се прозивежда
	 * 	     meta['fixedAsset']     - дали е ДМА
     * 	-> packagingRec - записа на опаковката, ако е зададена
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
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
     * Връща продуктите опции с продукти:
     * 	 Ако е зададен клиент се връщат всички публични + частните за него
     *   Ако не е зададен клиент се връщат всички активни продукти
     * 
     * @param mixed $customerClass - клас/ид на контрагента
     * @param int $customerId - ид на контрагента
     * @param string $datetime - дата към която извличаме артикулите
     * @param mixed $hasProperties - мета данни, на които да отговарят артикулите
     * @param mixed $hasnotProperties - мета данни, на които да отговарят артикулите
     * @param string $limit - колко опции да върнем
     * @return array - масив с достъпните за контрагента артикули 
     */
    function getProducts($customerClass, $customerId, $datetime = NULL, $hasProperties = NULL, $hasnotProperties = NULL, $limit = NULL)
    {
        return $this->class->getProducts($customerClass, $customerId, $datetime, $hasProperties, $hasnotProperties, $limit);
    }
    
    
	/**
     * Връща цената по себестойност на продукта
     * 
     * @return double
     */
    function getSelfValue($productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
        return $this->class->getSelfValue($productId, $packagingId, $quantity, $date);
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * 
     * @param mixed $properties       - комбинация на горе посочените мета 
     * 							        данни, на които трябва да отговарят
     * @param mixed $hasnotProperties - комбинация на горе посочените мета 
     * 							        които не трябва да имат
     */
    function getByProperty($properties, $hasnotProperties = NULL)
    {
    	return $this->class->getByProperty($properties, $hasnotProperties);
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
     * Връща параметрите на артикула
     * @param mixed $id - ид или запис на артикул
     *
     * @return array $res - параметрите на артикула
     * 					['weight']          -  Тегло
     * 					['width']           -  Широчина
     * 					['volume']          -  Обем
     * 					['thickness']       -  Дебелина
     * 					['length']          -  Дължина
     * 					['height']          -  Височина
     * 					['tolerance']       -  Толеранс
     * 					['transportWeight'] -  Транспортно тегло
     * 					['transportVolume'] -  Транспортен обем
     * 					['term']            -  Срок
     */
    public function getParams()
    {
    	return $this->class->getParams();
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
    
    
    /**
     * Връща клас имплементиращ `price_PolicyIntf`, основната ценова политика за този артикул
     */
    public function getPolicy()
    {
    	return $this->class->getPolicy();
    }
    
    
    /**
     * Връща описанието на артикула
     *
     * @param mixed $id - ид/запис
     * @param core_Mvc $documentMvc - модела
     * @param datetime $time - към кое време
     * @return mixed - описанието на артикула
     */
    public function getProductDesc($id, $documentMvc, $time = NULL)
    {
    	return $this->getProductDesc($id, $documentMvc, $time);
    }
    
    
    /**
     * Връща последното не оттеглено или чернова задание за спецификацията
     * 
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на заданието или FALSE ако няма
     */
    public function getLastJob($id)
    {
    	return $this->getLastJob($id);
    }
    
    
    /**
     * Намира последната активна технологична рецепта за артикула
     *
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public function getLastActiveBom($id)
    {
    	return $this->getLastActiveBom($id);
    }
}