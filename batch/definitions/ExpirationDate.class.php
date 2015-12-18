<?php


/**
 * Базов драйвер за вид партида 'дата на годност'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Дата на годност
 */
class batch_definitions_ExpirationDate extends batch_definitions_Proto
{
	
	
	/**
	 * Предложения за формати
	 */
	private $formatSuggestions = 'm/d/y,m.d.y,d.m.Y,m/d/Y,d/m/Y';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('format', 'varchar(20)', 'caption=Формат,mandatory');
		
		$fieldset->setSuggestions('format', array('' => '') + arr::make($this->formatSuggestions, TRUE));
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
		$check = strtotime($value);
		if(!$check) {
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return;
		}
		
		$check = dt::timestamp2Mysql($check);
		$check = dt::mysql2verbal($check, $this->rec->format);
		
		if($check !== $value){
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return;
		}
		
		return TRUE;
	}
}