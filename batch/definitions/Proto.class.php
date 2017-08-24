<?php


/**
 * Базов драйвер за видове партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class batch_definitions_Proto extends core_BaseClass
{
	
	
	/**
	 * Автоматичен стринг
	 */
	const AUTO_VALUE_STRING = 'Автоматично';
	
	
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
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'batch_BatchTypeIntf';
	
	
	/**
	 * Зареден запис
	 */
	protected $rec;
	
	
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = NULL)
    {
    	return TRUE;
    }
    
    
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
    	if($this->rec->uniqueProduct != 'yes') return TRUE;
    	
    	// Ако артикула вече има партида за този артикул с тази стойност, се приема че е валидна
    	if($eProductId = batch_Items::fetchField(array("#productId != {$this->rec->productId} AND #batch = '[#1#]'", $value), 'productId')){
    		$eProductId = cat_Products::getTitleById($eProductId);
    			
    		$msg = "Въведеният партиден номер е наличен за артикул|* <b>{$eProductId}</b>";
    			
    		return FALSE;
    	}
    	
    	return TRUE;
    }
    
    
    /**
     * Добавя записа
     *
     * @param stdClass $rec
     * @return void
     */
    public function setRec($rec)
    {
    	$this->rec = $rec;
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
    	$Type = core_Type::getByName('varchar');

    	return $Type;
    }
    
    
    /**
     * Разбива партидата в масив
     * 
     * @param varchar $value - партида
     * @return array $array - масив с партидата
     */
    public function makeArray($value)
    {
    	$value = $this->denormalize($value);
    	
    	return array($value => $this->toVerbal($value));
    }
    
    
    /**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     * 
     * @param string $value
     * @return string $value
     */
    public function normalize($value)
    {
    	return trim($value);
    }
    
    
    /**
     * Денормализира партидата
     * 
     * @param text $value
     * @return text $value
     */
    public function denormalize($value)
    {
    	return $value;
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
    	return cls::get('type_Varchar')->toVerbal($value);
    }
    
    
    /**
     * Какви са свойствата на партидата
     *
     * @param varchar $value - номер на партидара
     * @return array - свойства на партидата
     * 			o name    - заглавие
     * 			o classId - клас
     * 			o value   - стойност
     */
    public function getFeatures($value)
    {
    	$res = array();
    	$res[] = (object)array('name' => core_Classes::getTitleById($classId), 'classId' => $this->getClassId(), 'value' => $value);
    	 
    	return $res;
    }
    
    
    /**
	 * Връща масив с опции за лист филтъра на партидите
	 *
	 * @return array - масив с опции
	 * 		[ключ_на_филтъра] => [име_на_филтъра]
	 */
	public function getListFilterOptions()
    {
    	return array();
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
    	$batches = array();
    	if(!isset($storeId)) return $batches;
    	$date = (isset($date)) ? $date : dt::today();
    	
    	$quantities = batch_Items::getBatchQuantitiesInStore($this->rec->productId, $storeId, $date);
    	$batches = batch_Items::allocateQuantity($quantities, $quantity);
    	
    	return $batches;
    }
    
    
    /**
     * Заглавието на полето за партида
     * 
     * @return varchar
     */
    public function getFieldCaption()
    {
    	return (!empty($this->rec->batchCaption)) ? $this->rec->batchCaption : $this->fieldCaption;
    }
    
    
    /**
     * Може ли автоматично да се разпределят к-та
     *
     * @return varchar
     */
    public function canAutoAllocate()
    {
    	return ($this->rec->autoAllocate) ? TRUE : FALSE;
    }
    
    
    /**
     * Връща името на дефиницията
     *
     * @return varchar - Името на дефиницията
     */
    public function getName()
    {
    	return (isset($this->rec->name)) ? $this->rec->name : cls::getTitle($this);
    }
    
    
    /**
     * Може ли потребителя да сменя уникалноста на партида/артикул
     * 
     * @return boolean
     */
    public function canChangeBatchUniquePerProduct()
    {
    	return TRUE;
    }
}