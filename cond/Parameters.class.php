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
    	expect($Class::fetch($cId));
    	expect($condId = self::fetchIdBySysId($conditionSysId));
    	
    	// Връщаме стойността ако има директен запис за условието
    	$value = cond_ConditionsToCustomers::fetchByCustomer($cClass, $cId, $condId);
    	if($value) return $value;
    	
    	// Търси се метод дефиниран за връщане на стойността на условието
    	$method = "get{$conditionSysId}";
    	if(method_exists($Class, $method)) return $Class::$method($cId);
    	
    	// Търсим имали дефинирано търговско условие за държавата на контрагента
    	$contragentData = cls::get($cClass)->getContragentData($cId);
    	$countryId = $contragentData->countryId;
    	if($countryId){
    		$value = cond_Countries::fetchField("#country = {$countryId} AND #conditionId = {$condId}", 'value');
    		if(isset($value)) return $value;
    	}
    	
    	// От глобалния дефолт за всички държави
    	$value = cond_Countries::fetchField("#country IS NULL AND #conditionId = {$condId}", 'value');
    	
    	if(isset($value)) return $value;
    	
    	return NULL;
    }
}