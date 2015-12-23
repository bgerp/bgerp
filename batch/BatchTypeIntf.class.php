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
	 * @param mixed $class - класа за който ще връщаме партидата
	 * @param int $id - ид на документа за който ще връщаме партидата
	 * @return mixed $value - автоматичния партиден номер, ако може да се генерира
	 */
	function getAutoValue($class, $id)
	{
		return $this->class->getAutoValue($class, $id);
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
    	return $this->class->isValid($value, $msg);
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
     * @param text $value
     * @return text $value
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
}