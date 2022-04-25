<?php


/**
 * Помощен клас за транспорта
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class trans_Helper
{
    /**
     * Конвертира масив към табличен такъв
     *
     * @param mixed $array
     *
     * @return array $res
     */
    public static function convertToUnitTableArr($array)
    {
        $res = array('unitId' => array(), 'quantity' => array());
        $arr = arr::make($array);
        
        foreach ($arr as $unitId => $quantity) {
            $res['unitId'][] = (int) $unitId;
            $res['quantity'][] = (int) $quantity;
        }
        
        return $res;
    }
    
    
    /**
     * Конвертира масив с ЛЕ към такъв удобен за работа на core_Table
     *
     * @param mixed $value
     *
     * @return array $res
     */
    public static function convertTableUnitToTableArr($value)
    {
        $res = array('unitId' => array(), 'quantity' => array());
        $arr = core_Type::getByName('table(columns=unitId|quantity)')->toArray($value);
        foreach ($arr as $i => $obj) {
            $res['unitId'][$i] = (int) $obj->unitId;
            $res['quantity'][$i] = (int) $obj->quantity;
        }
        
        return $res;
    }
    
    
    /**
     * Конвертира таблични данни на ЛЕ към нормален масив
     *
     * @param array $arr
     *
     * @return array|NULL $res
     */
    public static function convertTableToNormalArr($arr)
    {
        if (is_array($arr) && !array_key_exists('unitId', $arr)) {
            
            return $arr;
        }
        $arr = is_array($arr) ? $arr : self::convertTableUnitToTableArr($arr);
        
        $res = array();
        foreach ($arr['unitId'] as $i => $unitId) {
            $res[$unitId] = (int) $arr['quantity'][$i];
        }
        
        $res = (is_array($res) && countR($res)) ? $res : null;
        
        return $res;
    }
    
    
    /**
     * Комбинира транспортните единици
     *
     * @param mixed $transUnits
     * @param mixed $transUnitsTable
     *
     * @return array $combined
     */
    public static function getCombinedTransUnits(&$transUnits, &$transUnitsTable)
    {
        $transUnits = self::convertTableToNormalArr($transUnits);
        $transUnits = empty($transUnits) ? array() : $transUnits;
        
        $transUnitsTable = self::convertTableToNormalArr($transUnitsTable);
        $transUnitsTable = empty($transUnitsTable) ? array() : $transUnitsTable;
        
        $combined = $transUnitsTable + $transUnits;
        ksort($combined);
        
        return $combined;
    }


    /**
     * Показва транспортните единици в документа
     *
     * @param array $transUnits      - масив с логистичните еденици и техните к-ва
     * @param boolean $combineByName - дали да се групират по име или да са подробни
     * @param string $divider        - разделител
     * @return string $str           - готовия стринг за показване
     */
    public static function displayTransUnits($transUnits, $combineByName = true, $divider = ' + ')
    {
        $transUnits = empty($transUnits) ? array() : $transUnits;

        $displayArr = $combined = array();
        foreach ($transUnits as $unitId => $quantity) {
            if (empty($quantity)) continue;

            $unitId = ($unitId) ? $unitId : self::fetchIdByName('load');
            $uRec = trans_TransportUnits::fetch($unitId, 'name,pluralName');

            if($combineByName){
                $nameArr = explode(' [', $uRec->name);
                $pluralNameArr = explode(' [', $uRec->pluralName);
                $nameArr[0] = tr(mb_strtolower($nameArr[0]));
                $pluralNameArr[0] = tr(mb_strtolower($pluralNameArr[0]));
                $key = "{$nameArr[0]}|{$pluralNameArr[0]}";
            } else {
                $name = tr(mb_strtolower($uRec->name));
                $pluralName = tr(mb_strtolower($uRec->pluralName));
                $key = "{$name}|{$pluralName}";
            }

            $combined[$key] += $quantity;
        }

        // Вербализиране на к-то спрямо числото на обединената ЛЕ
        foreach ($combined as $key => $quantity) {
            $unitNameArr = explode('|', $key);
            $unitName = ($quantity == 1) ? $unitNameArr[0] : $unitNameArr[1];
            $quantity = core_Type::getByName('int')->toVerbal($quantity);
            $displayArr[] = "{$quantity} {$unitName}";
        }

        $str = implode($divider, $displayArr);
        
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
        if (empty($unitTable)) {
            
            return;
        }
        
        $readyLu = trans_Helper::convertTableToNormalArr($unitTable);
        foreach ($readyLu as $uId => $qId) {
            if (!array_key_exists($uId, $arr)) {
                $arr[$uId] = (int) 0;
            }
            
            $arr[$uId] += (int) $qId;
        }
    }
    
    
    /**
     * Проверка на транспортните единици
     *
     * @param array $arr1
     * @param array $arr2
     *
     * @return bool
     */
    public static function checkTransUnits($arr1, $arr2)
    {
        $arr1 = arr::make($arr1);
        ksort($arr1);
        foreach ($arr1 as &$v1) {
            $v1 = (int) $v1;
        }
        
        $arr2 = arr::make($arr2);
        ksort($arr2);
        foreach ($arr2 as &$v2) {
            $v2 = (int) $v2;
        }
        
        return (serialize($arr1) == serialize($arr2));
    }


    /**
     * Коя от датите ще се използва за експедиране
     *
     * @param date $valior          - вальор
     * @param int $lineId           - ид на транспортна линия (ако има)
     * @param datetime $activatedOn - дата на активиране
     * @return datetime|null        - изчислената дата за експедиране
     */
    public static function calcShippedOnDate($valior, $lineId, $activatedOn)
    {
        $shippedDate = null;
        if(!empty($valior)) {
            $startTime = trans_Setup::get('START_WORK_TIME');
            $shippedDate = "{$valior} {$startTime}:00";
        } elseif(isset($lineId)){
            $shippedDate = trans_Lines::fetchField($lineId, 'start');
        }

        $shippedDate = (!empty($shippedDate) && $shippedDate >= dt::now()) ? $shippedDate :(dt::today() . " " . trans_Setup::get('END_WORK_TIME') . ":00");

        return $shippedDate;
    }
}
