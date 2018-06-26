<?php


/**
 * Базов драйвер за вид партида 'Цифри'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Цифри(255)
 */
class batch_definitions_Digits extends batch_definitions_Proto
{
	
	
	/**
	 * Име на полето за партида в документа
	 *
	 * @param string
	 */
	public $fieldCaption = 'lot';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('length', 'int', 'caption=Дължина,placeholder=255');
	}
	
	
	/**
	 * Проверява дали стойността е невалидна
	 *
	 * @param string $value - стойноста, която ще проверяваме
	 * @param quantity $quantity - количеството
	 * @param string &$msg - текста на грешката ако има
	 * @return boolean - валиден ли е кода на партидата според дефиницията или не
	 */
	public function isValid($value, $quantity, &$msg)
	{
		if(!ctype_digit($value)){
			$msg = "Не всички символи са цифри";
			return FALSE;
		}
		
		if(strlen($value) > $this->rec->length){
			$msg = "Над допустимите|* <b>{$this->rec->length}</b> |цифри|*";
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Проверява дали стойността е невалидна
	 *
	 * @return core_Type - инстанция на тип
	 */
	public function getBatchClassType()
	{
		$string = !isset($this->rec->length) ? 'varchar' : "varchar({$this->rec->length})";
		
		$Type = core_Type::getByName($string);
		
		return $Type;
	}
	
	
	/**
     * Какви са свойствата на партидата
     *
     * @param string $value - номер на партидара
     * @return array - свойства на партидата
     * 			o name    - заглавие
     * 			o classId - клас
     * 			o value   - стойност
     */
	public function getFeatures($value)
	{
		$res = array();
		$res[] = (object)array('name' => 'Партида', 'classId' => $this->getClassId(), 'value' => $value);
	
		return $res;
	}
}