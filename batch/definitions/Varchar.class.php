<?php


/**
 * Базов драйвер за вид партида 'varchar'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Символи(255)
 */
class batch_definitions_Varchar extends batch_definitions_Proto
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
     * @param varchar $value - номер на партидара
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