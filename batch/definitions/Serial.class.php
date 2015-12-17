<?php


/**
 * Базов драйвер за вид партида 'сериен номер'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Сериен номер
 */
class batch_definitions_Serial extends batch_definitions_Proto
{
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('from', 'int', 'caption=Обхват->От,mandatory');
		$fieldset->FLD('to', 'int', 'caption=Обхват->До,mandatory');
	}
	
	
	/**
	 * Проверява дали стойността е невалидна
	 *
	 * @param string $value - стойноста, която ще проверяваме
	 * @param string &$msg -текста на грешката ако има
	 * @return boolean - валиден ли е кода на партидата според дефиницията или не
	 */
	public function isValid($value, &$msg)
	{
		$Type = core_Type::getByName("int");
		
		// Стойноста трябва да е цяло число
		if(!$Type->fromVerbal($value)){
			$msg = 'Не е въведено цяло число';
			return FALSE;
		}
		
		// Стойноста трябва да е в допустимия интервал
		if($value < $this->rec->from || $value > $this->rec->to){
			$msg = "Стойноста не е в интервала|* <b>{$this->rec->from}</b> - <b>{$this->rec->to}</b>";
			return FALSE;
		}
		
		// Ако сме стигнали до тук всичко е наред
		return TRUE;
	}
	
	
	/**
	 * Връща автоматичния партиден номер според класа
	 *
	 * @param mixed $class - класа за който ще връщаме партидата
	 * @param int $id - ид на документа за който ще връщаме партидата
	 * @return mixed $value - автоматичния партиден номер, ако може да се генерира
	 */
	public function getAutoValue($class, $id)
	{
		$query = batch_Items::getQuery();
		$query->where("#productId = {$this->rec->productId}");
    	$query->XPR('maxNum', 'int', 'MAX(#batch)');
    	
    	if(!$maxNum = $query->fetch()->maxNum){
    		$maxNum = $this->rec->from;
    	}
    	$nextNum = $maxNum + 1;
    	
    	if($nextNum > $this->rec->to) return NULL;
    	
    	return $nextNum;
	}
}