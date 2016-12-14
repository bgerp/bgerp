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
		$fieldset->FLD('numbers', 'int', 'caption=Цифри,mandatory,unit=брой');
		$fieldset->FLD('prefix', 'varchar(10)', 'caption=Представка');
		$fieldset->FLD('suffix', 'varchar(10)', 'caption=Наставка');
	}
	
	
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
		// Ако артикула вече има партидаза този артикул с тази стойност, се приема че е валидна
		if(batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))){
			return TRUE;
		}
		
		$serials = $this->normalize($value);
		$serials = $this->makeArray($serials);
		$count = count($serials);
		$pattern = '';
		
		$errMsg = '|Всички номера трябва да отговарят на формата|*: ';
		if(!empty($this->rec->prefix)){
			$prefix = preg_quote($this->rec->prefix, '/');
			$pattern .= "{$prefix}{1}";
			$errMsg .= "|да започват с|* <b>{$this->rec->prefix}</b>, ";
		}
		$pattern .= "[0-9]{{$this->rec->numbers}}";
		$errMsg .= "|да имат точно|* <b>{$this->rec->numbers}</b> |цифри|*";
		
		if(!empty($this->rec->suffix)){
			$suffix = preg_quote($this->rec->suffix, '/');
			$pattern .= "{$suffix}{1}";
			$errMsg .= " |и да завършват на|* <b>{$this->rec->suffix}</b>";
		}
		
		foreach ($serials as $serial){
			if($serial  === FALSE){
				$msg = "Не могат да се генерират серийни номера от зададеният диапазон";
				return FALSE;
			}
			
			if(!preg_match("/^{$pattern}\z/", $serial)){
				$msg = $errMsg;
				return FALSE;
			}
		}
		
		if($count != $quantity){
			$mMsg = ($count != 1) ? 'серийни номера' : 'сериен номер';
			$msg = ($quantity != 1) ? "|Въведени са|* <b>{$count}</b> |{$mMsg}, вместо очакваните|* <b>{$quantity}</b>" : "Трябва да е въведен само един сериен номер";
		
			return FALSE;
		}
		
		// Ако сме стигнали до тук всичко е наред
		return TRUE;
	}
	
	
	/**
	 * Генерира серийни номера в интервал
	 * 
	 * @param varchar $from - начало на диапазона
	 * @param varchar $to - край на диапазона
	 * @return FALSE|array $res - генерираните номера или FALSE ако не може да се генерират
	 */
	private function getByRange($from, $to)
	{
		$oldFrom = $from;
		
		$prefix = $this->rec->prefix;
		$suffix = $this->rec->suffix;
		
		if(!empty($prefix)){
			if(strpos($from, $prefix) === FALSE || strpos($to, $prefix) === FALSE) return FALSE;
		}
		
		if(!empty($suffix)){
			if(strpos($from, $suffix) === FALSE || strpos($to, $suffix) === FALSE) return FALSE;
		}
		
		$from = str_replace($prefix, '', $from);
		$from = str_replace($suffix, '', $from);
		
		$to = str_replace($prefix, '', $to);
		$to = str_replace($suffix, '', $to);
		
		$res = array();
		$start = $from;
		while($start < $to){
			$serial = str::increment($start);
			$v = "{$prefix}{$serial}{$suffix}";
			$res[$v] = $v;
			$start = $serial;
		}
		
		if(count($res)){
			$res = array($oldFrom => $oldFrom) + $res;
			return $res;
		}
		
		return FALSE;
	}
	
	
	/**
	 * Разбива партидата в масив
	 *
	 * @param varchar $value - партида
	 * @return array $array - масив с партидата
	 */
	public function makeArray($value)
	{
		$res = array();
		
		$value = explode('|', $value);
		foreach ($value as &$v){
			$vArr = explode(':', $v);
			if(count($vArr) == 2){
				$rangeArr = $this->getByRange($vArr[0], $vArr[1]);
				if(is_array($rangeArr)){
					$res = array_merge($res, $rangeArr);
				} else {
					$res[$v] = FALSE;
				}
			} else {
				$res[$vArr[0]] = $vArr[0];
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param batch_definitions_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $form
	 */
	public static function on_AfterInputEditForm(batch_definitions_Proto $Driver, embed_Manager $Embedder, &$form)
	{
		$rec = &$form->rec;
		
		// Само артикули с основна мярка в брой, могат да имат серийни номера
		if(isset($rec->productId)){
			$measureId = cat_Products::fetchField($rec->productId, 'measureId');
			if(cat_UoM::fetchBySysId('pcs')->id != $measureId){
				$form->setError("driverClass", "Само артикули с основна мярка 'брой' могат да имат серийни номера");
			}
		}
	}
	
	
	/**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     * 
     * @param string $value
     * @return string $value
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
     * @param string $value
     * @return string $value
     */
	public function denormalize($value)
	{
		$value = explode('|', $value);
		$value = implode("\n", $value);
		
		return $value;
	}
}