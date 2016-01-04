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
class batch_definitions_Document extends batch_definitions_Varchar
{
	
	
	/**
	 * Връща автоматичния партиден номер според класа
	 *
	 * @param mixed $documentClass - класа за който ще връщаме партидата
	 * @param int $id - ид на документа за който ще връщаме партидата
	 * @return mixed $value - автоматичния партиден номер, ако може да се генерира
	 */
	public function getAutoValue($documentClass, $id)
	{
		$Class = cls::get($documentClass);
		expect($dRec = $Class->fetchRec($id));
		
		$handle = $Class->getHandle($dRec->id);
		$date = $dRec->{$Class->valiorFld};
		$date = dt::mysql2verbal($date, 'd.m.Y');
		
		$res = "$handle/{$date}";
		
		return $res;
	}
}