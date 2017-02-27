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
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */			
    function getProductInfo($productId)
    {
        return $this->class->getProductInfo($productId);
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
     * Връща теглото на единица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на единица от продукта
     */
	public function getWeight($productId, $packagingId = NULL)
    {
    	return $this->class->getWeight($productId, $packagingId);
    }
    
    
    /**
	 * Връща стойността на параметъра с това име, или
	 * всички параметри с техните стойностти
	 * 
	 * @param string $id     - ид на записа
	 * @param string $name   - име на параметъра, или NULL ако искаме всички
	 * @param boolean $verbal - дали да са вербални стойностите
	 * @return mixed - стойност или FALSE ако няма
	 */
    public function getParams($id, $name = NULL, $verbal = FALSE)
    {
    	return $this->class->getParams($id, $name, $verbal);
    }
    
    
    /**
     * Връща обема на единица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на единица от продукта
     */
	public function getVolume($productId, $packagingId = NULL)
    {
    	return $this->class->getVolume($productId, $packagingId);
    }
    
    
    /**
     * Връща последното не оттеглено или чернова задание за артикула
     * 
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на заданието или FALSE ако няма
     */
    public function getLastJob($id)
    {
    	return $this->class->getLastJob($id);
    }
    
    
    /**
     * Връща последната активна рецепта на артикула
     *
     * @param mixed $id - ид или запис
     * @param sales|production $type - вид работна или търговска
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public function getLastActiveBom($id, $type = NULL)
    {
    	return $this->class->getLastActiveBom($id, $type);
    }
    
    
    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @param mixed $id - ид или запис на артикул
     * @param double $quantity - к-во за произвеждане
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     * 				    -> title        - дефолт име на задачата
     * 					-> driverClass  - драйвър на задача
     * 					-> products     - масив от масиви с продуктите за влагане/произвеждане/отпадане
     * 						 - array input      - материали за влагане
     * 						 - array production - артикули за произвеждане
     * 						 - array waste      - отпадъци
     */
    public function getDefaultProductionTasks($id, $quantity = 1)
    {
    	return $this->class->getDefaultProductionTasks($id, $quantity);
    }
    
    
    /**
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     *
     * @param int $id - ид на артикул
     * @param core_RowToolbar $toolbar - тулбара
     * @param mixed $docClass - класа документа
     * @param int $docId - ид на документа
     * @return void
     */
    public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $docClass, $docId)
    {
    	return $this->class->addButtonsToDocToolbar($id, $toolbar, $docClass, $docId);
    }
    
    
    /**
     * Колко е толеранса
     *
     * @param int $id          - ид на артикул
     * @param double $quantity - к-во
     * @return double|NULL     - толеранс или NULL, ако няма
     */
    public function getTolerance($id, $quantity)
    {
    	return $this->class->getTolerance($id, $quantity);
    }
    
    
    /**
     * Колко е срока на доставка
     *
     * @param int $id          - ид на артикул
     * @param double $quantity - к-во
     * @return double|NULL     - срока на доставка в секунди или NULL, ако няма
     */
    public function getDeliveryTime($id, $quantity)
    {
    	return $this->class->getDeliveryTime($id, $quantity);
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id - ид на артикул
     * @return double|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public static function getMoq($id)
    {
    	return $this->class->getMoq($id);
    }
}