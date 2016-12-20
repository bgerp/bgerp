<?php



/**
 * Интерфейс за вид партида
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
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
	 * Връща автоматичния партиден номер според класа
	 * 
	 * @param mixed $documentClass - класа за който ще връщаме партидата
	 * @param int $id - ид на документа за който ще връщаме партидата
	 * @return mixed $value - автоматичния партиден номер, ако може да се генерира
	 */
	function getAutoValue($documentClass, $id)
	{
		return $this->class->getAutoValue($documentClass, $id);
	}
	
	
	/**
     * Проверява дали стойността е невалидна
     *
     * @param string $value - стойноста, която ще проверяваме
     * @param quantity $quantity - количеството
     * @param string &$msg -текста на грешката ако има
     * @return boolean - валиден ли е кода на партидата според дефиницията или не
     */
    function isValid($value, $quantity, &$msg)
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
     * Каква е стойноста, която означава че партидата трябва да се генерира автоматично
     *
     * @return string
     */
    public function getAutoValueConst()
    {
    	return $this->class->getAutoValueConst();
    }
    
    
    /**
     * Какви са свойствата на партидата
     *
     * @param varchar $value - номер на партидара
     * @return array - свойства на партидата
     * 	масив с ключ ид на партидна дефиниция и стойност свойството
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
}