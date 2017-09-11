<?php



/**
 * Базов драйвер за наследяване на партиди от тип Дата
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Тип за наследяване на партиди от тип дата
 */
abstract class batch_definitions_Date extends batch_definitions_Proto
{
	
	
	/**
	 * Позволени формати
	 */
	protected $formatSuggestions = 'm/d/y,m.d.y,d.m.Y,m/d/Y,d/m/Y,Ymd,Ydm,Y-m-d,dmY,ymd,ydm,m.d.Y';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('format', 'varchar(20)', 'caption=Формат,mandatory');
		$fieldset->setOptions('format', array('' => '') + arr::make($this->formatSuggestions, TRUE));
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
		// Ако артикула вече има партида за този артикул с тази стойност, се приема че е валидна
		if(batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))){
			return TRUE;
		}
	
		// Карта
		$map = array();
		$map['m'] = "(?'month'[0-9]{2})";
		$map['d'] = "(?'day'[0-9]{2})";
		$map['y'] = "(?'yearShort'[0-9]{2})";
		$map['Y'] = "(?'year'[0-9]{4})";
	
		// Генерираме регулярен израз спрямо картата
		$expr = $this->rec->format;
		$expr = preg_quote($expr, '/');
		$expr = strtr($expr, $map);
	
		// Проверяваме дали датата отговаря на формата
		if(!preg_match("/^{$expr}$/", $value, $matches)){
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return FALSE;
		}
	
		// Ако годината е кратка, правим я дълга
		if(isset($matches['yearShort'])){
			$matches['year'] = "20{$matches['yearShort']}";
		}
	
		// Проверяваме дали датата е възможна
		if(!checkdate($matches['month'], $matches['day'], $matches['year'])){
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return FALSE;
		}
	
		return parent::isValid($value, $quantity, $msg);
	}
	
	
	/**
	 * Добавя записа
	 *
	 * @param stdClass $rec
	 * @return void
	 */
	public function setRec($rec)
	{
		$this->fieldPlaceholder = $rec->format;
		$this->rec = $rec;
	}
}