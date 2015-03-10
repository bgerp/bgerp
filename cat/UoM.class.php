<?php



/**
 * Клас 'cat_UoM' - мерни единици
 *
 * Unit of Measures
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_UoM extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_State2, plg_AlignDecimals, plg_Sorting';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'cat,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'cat,ceo';

    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,ceo';

    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,ceo';
  

    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,ceo';
 

    /**
     * Заглавие
     */
    var $title = 'Мерни единици';
    
    
    /**
     * Полета за лист изгледа
     */
    var $listFields = "id,name,shortName=Съкращение->Българско,sysId=Съкращение->Международно,state";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export');
        $this->FLD('shortName', 'varchar(12)', 'caption=Съкращение, export');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Базова мярка, export');
        $this->FLD('baseUnitRatio', 'double', 'caption=Коефициент, export');
        $this->FLD('sysId', 'varchar', 'caption=System Id,mandatory');
        $this->FLD('sinonims', 'varchar(255)', 'caption=Синоними');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
    }
    
    
    /**
     * Конвертира стойност от една мярка към основната и
     * @param double amount - стойност
     * @param int $unitId - ид на мярката
     */
    static function convertToBaseUnit($amount, $unitId)
    {
        $rec = static::fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount * $ratio;
        
        return $result;
    }
    
    
    /**
     * Конвертира стойност от основната мярка на дадена мярка
     * @param double amount - стойност
     * @param int $unitId - ид на мярката
     */
    static function convertFromBaseUnit($amount, $unitId)
    {
        $rec = static::fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount / $ratio;
        
        return $result;
    }
    
    
    /**
     * Функция връщащи масив от всички мерки които са сродни
     * на посочената мярка (примерно за грам това са : килограм, тон и др)
     * @param int $measureId - id на мярка
     * @return array $options - всички мярки от същата категория
     * като подадената
     */
    static function getSameTypeMeasures($measureId, $short = FALSE)
    {
    	expect($rec = static::fetch($measureId), "Няма такава мярка");	
    	
    	$query = static::getQuery();
    	($rec->baseUnitId) ? $baseId = $rec->baseUnitId : $baseId = $rec->id;
    	$query->where("#baseUnitId = {$baseId}");
    	$query->orWhere("#id = {$baseId}");
    	
    	$options = array("" => "");
    	while($op = $query->fetch()){
    		$cap = ($short) ? $op->shortName : $op->name;
    		$options[$op->id] = $cap;	
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
    public static function convertValue($value, $from, $to)
    {
    	expect($fromRec = static::fetch($from), 'Проблем при изчислението на първата валута');
    	expect($toRec = static::fetch($to), 'Проблем при изчислението на втората валута');
    	
    	($fromRec->baseUnitId) ? $baseFromId = $fromRec->baseUnitId : $baseFromId = $fromRec->id;
    	($toRec->baseUnitId) ? $baseToId = $toRec->baseUnitId : $baseToId = $toRec->id;
    	
    	// Очакваме двете мерки да имат една обща основна мярка
    	expect($baseFromId == $baseToId, "Не може да се конвертира от едната мярка в другата");
    	$rate = $fromRec->baseUnitRatio / $toRec->baseUnitRatio;
    	
    	// Форматираме резултата да се показва правилно числото
    	$rate = number_format($rate, 9, '.', '');
    	
    	return $value * $rate;
    }
    
    
    /**
     * Връща краткото име на мярката
     * @param int $id - ид на мярка
     * @return string - краткото име на мярката
     */
    public static function getShortName($id)
    {
    	expect($rec = static::fetch($id));
    	return static::recToVerbal($rec, 'shortName')->shortName;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	// Ако се импортира от csv файл, заместваме основната
    	// единица с ид-то и от системата
    	if(isset($rec->csv_baseUnitId) && strlen($rec->csv_baseUnitId) != 0){
    		$rec->baseUnitId = static::fetchField("#name = '{$rec->csv_baseUnitId}'", 'id');
    	}
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/UoM.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "shortName", 
	    	2 => "csv_baseUnitId", 
	    	3 => "baseUnitRatio",
	    	4 => "state",
	    	5 => "sysId",
	    	6 => "sinonims");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща мерна еденициа по систем ид
     * @param varchar $sysId - sistem Id
     * @return stdClass $rec - записа отговарящ на сис ид-то
     */
    public static function fetchBySysId($sysId)
    {
    	return static::fetch("#sysId = '{$sysId}'");
    }
    
    
    /**
     * Връща запис отговарящ на име на мерна еденица
     * (включва българско, английско или фонетично записване)
     * @param string $string - дума по която се търси
     * @return stdClass $rec - записа отговарящ на сис Ид-то
     */
    public static function fetchBySinonim($unit)
    {
    	$unitLat = strtolower(str::utf2ascii($unit));
    	
    	$query = static::getQuery();
    	$query->likeKeylist('sinonims', "|{$unitLat}|");
    	$query->orWhere(array("LOWER(#sysId) = LOWER('[#1#]')", $unitLat));
        $query->orWhere(array("LOWER(#name) = LOWER('[#1#]')", $unit));
        $query->orWhere(array("LOWER(#shortName) = LOWER('[#1#]')", $unit));

    	return $query->fetch();
    }
    
    
    /**
     * Помощна ф-я правеща умно закръгляне на сума в най-оптималната близка
     * мерна еденица от същия тип
     * @param double $val - сума за закръгляне
     * @param string $sysId - системно ид на мярка
     * @param boolean $verbal - дали да са вербални числата
     * @param boolean $asObject - да се върне обект със стойност, мярка или като стринг
     * @return string - закръглената сума с краткото име на мярката
     */
    public static function smartConvert($val, $sysId, $verbal = TRUE, $asObject = FALSE)
    {
    	$Double = cls::get('type_Double');
    	$Double->params['smartRound'] = 'smartRound';
    	
    	// Намира се коя мярка отговаря на това сис ид
    	$typeUom = cat_UoM::fetchBySysId($sysId);
    	
    	// Извличат се мерките от същия тип и се премахва празния елемент в масива
        $sameMeasures = cat_UoM::getSameTypeMeasures($typeUom->id);
        unset($sameMeasures[""]);
       
        if(count($sameMeasures) == 1){
        	
        	// Ако мярката няма сродни мерки, сумата се конвертира в нея и се връща
        	$val = cat_UoM::convertFromBaseUnit($val, $typeUom->id);
        	$val = ($verbal) ? $Double->toVerbal($val) : $val;
        	
        	return ($asObject) ? (object)(array('value' => $val, 'measure' => $typeUom->id)) : $val . " " . $typeUom->shortName;
        }
        
        // При повече от една мярка, изчисляваме, колко е конвертираната сума на всяка една
        $all = array();
        foreach ($sameMeasures as $mId => $name){
        	$all[$mId] = cat_UoM::convertFromBaseUnit($val, $mId);
        }
       
        // Сумите се пдореждат в възходящ ред
        asort($all);
        
        // Първата сума по голяма от 1 се връща
        foreach ($all as $mId => $amount){
        	
	        if($amount >= 1){
	        	$all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
	        	
	        	return ($asObject) ? (object)(array('value' => $all[$mId], 'measure' => $mId)) : $all[$mId] . " " . static::getShortName($mId); 
        	}
        }
        
        // Ако няма такава се връща последната (тази най-близо до 1)
        end($all);
        $uomId = key($all);
        
        $all[$mId] = ($verbal) ? $Double->toVerbal($all[$mId]) : $all[$mId];
        
        return ($asObject) ? (object)(array('value' => $all[$uomId], 'measure' => $mId)) : $all[$uomId] . " " . static::getShortName($mId);
    }
}
