<?php



/**
 * Клас 'cat_UoM' - измервателни единици
 *
 * Unit of Measures
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_UoM extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State, plg_RowTools, cat_Wrapper, plg_State2, plg_AlignDecimals, plg_Sorting';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Заглавие
     */
    var $title = 'Измерителни единици';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export');
        $this->FLD('shortName', 'varchar(12)', 'caption=Кратко име, export');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Основна мярка, export');
        $this->FLD('baseUnitRatio', 'double', 'caption=Коефициент, export');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertToBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount * $ratio;
        
        return $result;
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertFromBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount / $ratio;
        
        return $result;
    }
    
    
    /**
     * Инициализиране на таблицата при инсталиране с един запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $res .= cat_setup_UoM::setup();
    }
    
    
    /**
     * Функция връщащи масив от всички мерки които са сродни
     * на посочената мярка (примерно за грам това са : килограм, тон и др)
     * @param int $measureId - id на мярка
     * @return array $options - всички мярки от същата категория
     * като подадената
     */
    static function getSameTypeMeasures($measureId)
    {
    	expect($rec = static::fetch($measureId), "Няма такава мярка");	
    	
    	$query = static::getQuery();
    	($rec->baseUnitId) ? $baseId = $rec->baseUnitId : $baseId = $rec->id;
    	$query->where("#baseUnitId = {$baseId}");
    	$query->orWhere("#id = {$baseId}");
    	
    	$options = array();
    	while($op = $query->fetch()){
    		$options[$op->id] = $op->name;	
    	}
    	
    	return $options;
    }
    
    
    /**
     * Функция която конвертира стойност от една мярка в друга
     * сродна мярка
     * @param double $value - Стойноста за конвертиране
     * @param int $from - Id на мярката от която ще обръщаме
     * @param int $to - Id на мярката към която конвертираме
     * @return double - Конвертираната стойност
     */
    public static function convertValue($value, $from, $to){
    	$fromRec = static::fetch($from);
    	$toRec = static::fetch($to);
    	expect($fromRec && $toRec, "Проблем при извличането на една от мерките");
    	
    	($fromRec->baseUnitId) ? $baseFromId = $fromRec->baseUnitId : $baseFromId = $fromRec->id;
    	($toRec->baseUnitId) ? $baseToId = $toRec->baseUnitId : $baseToId = $toRec->id;
    	
    	// Очакваме двете мерки да имат една обща основна мярка
    	expect($baseFromId == $baseToId, "Неможе да се конвертира от едната мярка в другата");
    	$rate = $fromRec->baseUnitRatio / $toRec->baseUnitRatio;
    	
    	// Форматираме резултата да се показва правилно числото
    	$rate = number_format($rate, 4, '.', '');
    	
    	return $value * $rate;
    }
}