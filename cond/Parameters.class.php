<?php



/**
 * Клас 'cond_Parameters' - Търговски условия
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Parameters extends bgerp_ProtoParam
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, cond_Wrapper, plg_State2, plg_Search';
    
    
    /**
     * Заглавие
     */
    public $title = 'Видове търговски условия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Търговско условие";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,admin';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	parent::setFields($this);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "cond/csv/Parameters.csv";
    	$fields = array(
    			0 => "name",
    			1 => "driverClass",
    			2 => "sysId",
    			3 => "group",
    			4 => 'suffix',
    			5 => 'csv_roles',
    			6 => 'options',
    	);
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res = $cntObj->html;
    	
    	return $res;
    }
    
    
	/**
     * Връща стойността на дадено търговско условие за клиента
     * според следните приоритети
     * 	  1. Директен запис в cond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. От условието за конкретната държава на контрагента
     *    4. От условието за всички държави за контрагенти
     *    5. NULL ако нищо не е намерено
     * 
     * @param int $cClass            - клас на контрагента
     * @param int $cId               - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see cond_Parameters)
     * @return string $value         - стойността на параметъра
     */
    public static function getParameter($cClass, $cId, $conditionSysId)
    {
    	// Ако няма клас и ид на документ да не връща нищо
    	if(!isset($cClass) && !isset($cId)) return;
    	
    	expect($Class = cls::get($cClass));
    	expect($cRec = $Class::fetch($cId));
    	expect($condId = self::fetchIdBySysId($conditionSysId));
    	
    	// Връщаме стойността ако има директен запис за условието
    	$value = cond_ConditionsToCustomers::fetchByCustomer($Class, $cId, $condId);
    	if($value) return $value;
    	
    	// Търси се метод дефиниран за връщане на стойността на условието
    	$method = "get{$conditionSysId}";
    	if(method_exists($Class, $method)) return $Class::$method($cId);
    	
    	// Ако има поле за държава
    	$countryFieldName = $Class->countryFieldName;
    	if ($countryFieldName) {
    		
    		// Търсим имали дефинирано търговско условие за държавата на контрагента
    		$countryId = $cRec->{$countryFieldName};
    		if($countryId){
    			$value = cond_Countries::fetchField("#country = {$countryId} AND #conditionId = {$condId}", 'value');
    			if(isset($value)) return $value;
    		}
    	}
    	
    	// От глобалния дефолт за всички държави
    	$value = cond_Countries::fetchField("#country IS NULL AND #conditionId = {$condId}", 'value');
    	
    	if(isset($value)) return $value;
    	
    	return NULL;
    }
    
    
    /**
     * Ограничаване на символите на стойноста, ако е текст
     * 
     * @param mixed $driverClass
     * @param mixed $value
     * @return mixed $value
     */
    public static function limitValue($driverClass, $value)
    {
    	$driverClass = cls::get($driverClass);
    	if(($driverClass instanceof cond_type_Text) && mb_strlen($value) > 90){
    		$bHtml = mb_strcut($value, 0, 90);
    		$cHtml = mb_strcut($value, 90);
    	
    		$value = $bHtml . "\n[hide=" . tr('Вижте още') . "]" . $value . "[/hide]";
    		$value = cls::get('type_Richtext')->toVerbal($value);
    	}
    	
    	return $value;
    }
    
    
    /**
     * Форсира параметър
     *
     * @param string $sysId       - систем ид на параметър
     * @param string $name        - име на параметъра
     * @param string $type        - тип на параметъра
     * @param NULL|text $options  - опции на параметъра само за типовете enum и set
     * @param NULL|string $suffix - наставка
     * @return number             - ид на параметъра
     */
    public static function force($sysId, $name, $type, $options = array(), $suffix = NULL)
    {
    	// Ако има параметър с това систем ид,връща се
    	$id = self::fetchIdBySysId($sysId);
    	if(!empty($id)) return $id;
    		
    	// Създаване на параметъра
    	return self::save(self::makeNewRec($sysId, $name, $type, $options, $suffix));
    }
}