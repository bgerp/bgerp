<?php



/**
 * Интерфейс за вид партида
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_BatchTypeIntf extends embed_DriverIntf
{

	
	/**
	 * Инстанция на мениджъра имащ интерфейса
	 */
	public $class;
	
	
	/**
	 * Плейсхолдър на полето
	 *
	 * @param string
	 */
	public $fieldPlaceholder;
	
	
	/**
	 * Име на полето за партида в документа
	 *
	 * @param string
	 */
	public $fieldCaption;
	
	
	/**
	 * Връща автоматичния партиден номер според класа
	 * 
	 * @param mixed $documentClass - класа за който ще връщаме партидата
	 * @param int $id              - ид на документа за който ще връщаме партидата
	 * @param int $storeId         - склад
	 * @param date|NULL $date      - дата
	 * @return mixed $value        - автоматичния партиден номер, ако може да се генерира
	 */
	public function getAutoValue($documentClass, $id, $storeId, $date = NULL)
	{
		return $this->class->getAutoValue($documentClass, $id, $storeId, $date);
	}
	
	
	/**
     * Проверява дали стойността е невалидна
     *
     * @param string $value - стойноста, която ще проверяваме
     * @param quantity $quantity - количеството
     * @param string &$msg -текста на грешката ако има
     * @return boolean - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
    	return $this->class->isValid($value, $quantity, $msg);
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
    	return $this->class->getBatchClassType();
    }
    
    
    /**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     *
     * @param string $value
     * @return string $value
     */
    public function normalize($value)
    {
    	return $this->class->normalize($value);
    }
    
    
    /**
     * Денормализира партидата
     *
     * @param text $value
     * @return text $value
     */
    public function denormalize($value)
    {
    	return $this->class->denormalize($value);
    }
    
    
    /**
     * Разбива партидата в масив
     *
     * @param varchar $value - партида
     * @return array $array - масив с партидата
     */
    public function makeArray($value)
    {
    	return $this->class->makeArray($value);
    }
    
    
    /**
	 * Какви са свойствата на партидата
	 *
	 * @param varchar $value - номер на партидара
	 * @return array - свойства на партидата
	 * 			o name - заглавие
	 * 			o value  - стойност
	 */
    public function getFeatures($value)
    {
    	return $this->class->getFeatures($value);
    }
    
    
    /**
     * Връща масив с опции за лист филтъра на партидите
     *
     * @return array - масив с опции
     * 		[ключ_на_филтъра] => [име_на_филтъра]
     */
    public function getListFilterOptions()
    {
    	return $this->class->getListFilterOptions();
    }
    
    
    /**
     * Добавя филтър към заявката към  batch_Items възоснова на избраната опция (@see getListFilterOptions)
     *
     * @param core_Query $query - заявка към batch_Items
     * @param varchar $value -стойност на филтъра
     * @param string $featureCaption - Заглавие на колоната на филтъра
     * @return void
     */
    public function filterItemsQuery(core_Query &$query, $value, &$featureCaption)
    {
    	return $this->class->filterItemsQuery($query, $value, $featureCaption);
    }
    
    
    /**
     * Подрежда подадените партиди
     *
     * @param array $batches - наличните партиди
     * 		['batch_name'] => ['quantity']
     * @param date|NULL $date
     * return void
     */
    public function orderBatchesInStore(&$batches, $storeId, $date = NULL)
    {
    	return $this->class->orderBatchesInStore($batches, $storeId, $date);
    }
    
    
    /**
     * Разпределя количество към наличните партиди в даден склад към дадена дата
     *
     * @param double $quantity - к-во
     * @param int $storeId     - склад
     * @param string $date     - дата
     * @return array $batches  - от коя партида, какво количество да се изпише
     * 	[име_на_партидата] => [к_во_за_изписване]
     */
    public function allocateQuantityToBatches($quantity, $storeId, $date = NULL)
    {
    	return $this->class->allocateQuantityToBatches($quantity, $storeId, $date);
    }
    
    
    /**
     * Заглавието на полето за партида
     * 
     * @return varchar
     */
    public function getFieldCaption()
    {
    	return $this->class->getFieldCaption();
    }
    
    
    /**
     * Връща името на дефиницията
     *
     * @return varchar - Името на дефиницията
     */
    public function getName()
    {
    	return $this->class->getName();
    }
    
    
    /**
     * Може ли потребителя да сменя уникалноста на партида/артикул
     *
     * @return boolean
     */
    public function canChangeBatchUniquePerProduct()
    {
    	return $this->class->canChangeBatchUniquePerProduct();
    }
}