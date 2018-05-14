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
	
	
	public static function getCombinedTransUnits(&$transUnits, &$transUnitsTable)
	{
		$transUnits = self::convertTableToNormalArr($transUnits);
		
		$transUnitsTable = self::convertTableToNormalArr($transUnitsTable);
		$transUnitsTable = empty($transUnitsTable) ? array() : $transUnitsTable;
		
		
		
		//
		$combined = $transUnitsTable + $transUnits;
		ksort($combined);
		
		return $combined;
	}
	
	public static function displayTransUnits($transUnits, $transUnitsTable = array())
	{
		$str = '';
		$transUnits = empty($transUnits) ? array() : $transUnits;
		$combined = self::getCombinedTransUnits($transUnits, $transUnitsTable);
		$transUnitsTable = empty($transUnitsTable) ? array() : $transUnitsTable;
		
		foreach ($combined as $unitId => $quantity){
			if(empty($quantity)) continue;
			$strPart = trans_TransportUnits::display($unitId, $quantity);
			if(array_key_exists($unitId, $transUnitsTable) && !Mode::isReadOnly()){
				$strPart = ht::createHint($strPart, 'Зададено е ръчно');
			}
			
			$str .= "{$strPart} + ";
		}
		$str = trim($str, ' + ');
		
		return $str;
	}
	
	
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
	
	
	
	public static function checkTransUnits($arr1, $arr2)
	{
		$arr1 = arr::make($arr1);
		ksort($arr1);
		
		$arr2 = arr::make($arr2);
		ksort($arr2);
		
		return (serialize($arr1) == serialize($arr2));
	}
}