<?php



/**
 * Базов драйвер за партиден клас 'срок на годност'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Дата на доставка
 */
class batch_definitions_DeliveryDate extends batch_definitions_Date
{
	
	
	/**
	 * Име на полето за партида в документа
	 *
	 * @param string
	 */
	public $fieldCaption = 'Дт. дост.';
	
	
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
	
		if($Class instanceof purchase_Purchases || $Class instanceof store_Receipts){
			setIfNot($date, $dRec->{$Class->valiorFld}, dt::today());
			$date = dt::mysql2verbal($date, $this->rec->format);
			
			return $date;
		}
		
		return NULL;
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
		$res[] = (object)array('name' => 'Дата на доставка', 'classId' => $this->getClassId(), 'value' => $value);
	
		return $res;
	}
}