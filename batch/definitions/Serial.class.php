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
	 * Проверява дали стойността е невалидна
	 *
	 * @return core_Type - инстанция на тип
	 */
	public function getBatchClassType()
	{
		$Type = core_Type::getByName('text(rows=3)');
		
		return $Type;
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
		$serials = $this->normalize($value);
		$serials = $this->makeArray($serials);
		$count = count($serials);
		
		if($count != $quantity){
			$msg = ($quantity != 1) ? "|Трябва да са въведени точно|* <b>'{$quantity}'</b> |серийни номера|*" : "Трябва да е въведен само един сериен номер";
				
			return FALSE;
		}
		
		// Ако сме стигнали до тук всичко е наред
		return TRUE;
	}
	
	
	/**
	 * Разбива партидата в масив
	 *
	 * @param varchar $value - партида
	 * @return array $array - масив с партидата
	 */
	public function makeArray($value)
	{
		$value = explode('|', $value);
    	$array = array_combine($value, $value);
		
		return $array;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $form
	 */
	public static function on_AfterInputEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$form)
	{
		$rec = &$form->rec;
		
		// Само артикули с основна мярка в брой, могат да имат серийни номера
		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
		if(cat_UoM::fetchBySysId('pcs')->id != $measureId){
			$form->setError("driverClass", "Само артикули с основна мярка 'брой' могат да имат серийни номера");
		}
	}
	
	
	/**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     * 
     * @param text $value
     * @return text $value
     */
	public function normalize($value)
	{
		$value = explode("\n", trim(str_replace("\r", '', $value)));
		$value = implode('|', $value);
		
		return ($value == '') ? NULL : $value;
	}
	
	
	/**
     * Денормализира партидата
     * 
     * @param text $value
     * @return text $value
     */
	public function denormalize($value)
	{
		$value = explode('|', $value);
		$value = implode("\n", $value);
		
		return $value;
	}
}