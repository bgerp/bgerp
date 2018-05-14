<?php


/**
 * Помощен клас за транспорта
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class trans_Helper
{
	
	
	/**
	 * Конвертира масив към табличен такъв
	 * 
	 * @param mixed $array
	 * @return array $res
	 */
	public static function convertToUnitTableArr($array)
	{
		$res = array('unitId' => array(), 'quantity' => array());
		$arr = arr::make($array);
		
		foreach ($arr as $unitId => $quantity){
			$res['unitId'][] = (int)$unitId;
			$res['quantity'][] = (int)$quantity;
		}
		
		return $res;
	}
	
	
	
	/**
	 * Конвертира масив с ЛЕ към такъв удобен за работа на core_Table
	 * 
	 * @param mixed $value
	 * @return array $res
	 */
	public static function convertTableUnitToTableArr($value)
	{
		$res = array('unitId' => array(), 'quantity' => array());
		$arr = core_Type::getByName('table(columns=unitId|quantity)')->toArray($value);
		foreach ($arr as $i => $obj){
			$res['unitId'][$i] = (int)$obj->unitId;
			$res['quantity'][$i] = (int)$obj->quantity;
		}
		
		return $res;
	}
	
	
	/**
	 * Конвертира таблични данни на ЛЕ към нормален масив
	 * 
	 * @param array $arr
	 * @return array|NULL $res
	 */
	public static function convertTableToNormalArr($arr)
	{
		if(is_array($arr) && !array_key_exists('unitId', $arr)) return $arr;
		$arr = is_array($arr) ? $arr : self::convertTableUnitToTableArr($arr);
		
		$res = array();
		foreach ($arr['unitId'] as $i => $unitId){
			$res[$unitId] = (int)$arr['quantity'][$i];
		}
		
		$res = (is_array($res) && count($res)) ? $res : NULL;
		
		return $res;
	}
	
	
	/**
	 * Комбинира транспортните единици
	 * 
	 * @param mixed $transUnits
	 * @param mixed $transUnitsTable
	 * @return array $combined
	 */
	public static function getCombinedTransUnits(&$transUnits, &$transUnitsTable)
	{
		$transUnits = self::convertTableToNormalArr($transUnits);
		
		$transUnitsTable = self::convertTableToNormalArr($transUnitsTable);
		$transUnitsTable = empty($transUnitsTable) ? array() : $transUnitsTable;
		
		if(!is_array($transUnitsTable) || !is_array($transUnits)){
			bp($transUnitsTable);
		}
		
		$combined = $transUnitsTable + $transUnits;
		ksort($combined);
		
		return $combined;
	}
	
	
	/**
	 * Показва транспортните единици в документа
	 * 
	 * @param mixed $transUnits
	 * @param mixed $transUnitsTable
	 * @param boolean $newLines
	 * @return string
	 */
	public static function displayTransUnits($transUnits, $transUnitsTable = array(), $newLines = FALSE)
	{
		$str = '';
		$delimeter = ($newLines) ? "<br>" : ' + ';
		$transUnits = empty($transUnits) ? array() : $transUnits;
		$transUnitsTable = empty($transUnitsTable) ? array() : $transUnitsTable;
		$combined = self::getCombinedTransUnits($transUnits, $transUnitsTable);
		
		foreach ($combined as $unitId => $quantity){
			if(empty($quantity)) continue;
			$strPart = trans_TransportUnits::display($unitId, $quantity);
			if(array_key_exists($unitId, $transUnitsTable) && !Mode::isReadOnly()){
				$strPart = ht::createHint($strPart, 'Зададено е ръчно');
			}
			
			$str .= "{$strPart} {$delimeter} ";
		}
		$str = trim($str, " {$delimeter} ");
		
		return $str;
	}
	
	
	/**
	 * Сумира транспортните единици
	 * 
	 * @param array $arr
	 * @param mixed $unitTable
	 */
	public static function sumTransUnits(&$arr, $unitTable)
	{
		if(empty($unitTable)) return;
		
		$readyLu = trans_Helper::convertTableToNormalArr($unitTable);
		foreach ($readyLu as $uId => $qId){
			if(!array_key_exists($uId, $arr)){
				$arr[$uId] = (int)0;
			}
			
			$arr[$uId] += (int)$qId;
		}
	}
	
	
	/**
	 * Проверка на транспортните единици
	 * 
	 * @param array $arr1
	 * @param array $arr2
	 * @return boolean
	 */
	public static function checkTransUnits($arr1, $arr2)
	{
		$arr1 = arr::make($arr1);
		ksort($arr1);
		
		$arr2 = arr::make($arr2);
		ksort($arr2);
		
		return (serialize($arr1) == serialize($arr2));
	}
}