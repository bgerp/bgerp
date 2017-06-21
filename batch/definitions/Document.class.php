<?php


/**
 * Базов драйвер за вид партида 'хендлър на документ с дата'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Хендлър на документ с дата
 */
class batch_definitions_Document extends batch_definitions_Proto
{
	
	
	/**
	 * Плейсхолдър на полето
	 *
	 * @param string
	 */
	public $fieldPlaceholder = 'yyyymmdd-abbr№';
	
	
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
		$Class = cls::get($documentClass);
		expect($dRec = $Class->fetchRec($id));
		
		$handle = mb_strtoupper($Class->getHandle($dRec->id));
		$date = $dRec->{$Class->valiorFld};
		$date = (!empty($date)) ? $date : dt::today();
		$date = str_replace('-', '', $date);
		
		$res = "{$date}-{$handle}";
		
		return $res;
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
		if(!preg_match("/^[0-9]{8}[\-]{1}[A-Z]{3}[0-9]+/", $value, $matches)){
			$date = str_replace('-', '', dt::today());
			$msg = "Формата трябва да е във вида на|* {$date}-SAL1";
			return FALSE;
		}
		
		return parent::isValid($value, $quantity, $msg);
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
		list($date, $string) = explode('-', $value);
		 
		$varcharClassId = batch_definitions_Varchar::getClassId();
		$dateClassId = batch_definitions_ExpirationDate::getClassId();
		
		return array("{$varcharClassId}" => $string, "{$dateClassId}" => $date);
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
		ksort($batches);
	}
}