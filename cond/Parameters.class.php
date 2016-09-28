<?php



/**
 * Клас 'cond_Parameters' - Търговски условия
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Parameters extends bgerp_ProtoParam
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cond_Wrapper, plg_State2, plg_Search';
    
    
    /**
     * Заглавие
     */
    public $title = 'Търговски условия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Търговско условие";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,cond';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,cond';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo,cond';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cond';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	parent::setFields($this);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setField('driverClass', 'caption=Тип,input');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		$rec->name = str::mbUcfirst($rec->name);
    	}
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
    			3 => "state",
    			4 => "group",
    			5 => 'suffix',
    			6 => 'default',
    	);
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
	/**
     * Връща стойността на дадено търговско условие за клиента
     * според следните приоритети
     * 	  1. Директен запис в cond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. Супер дефолта на параметъра дефиниран в cond_Parameters
     *    4. NULL ако нищо не е намерено
     * 
     * @param int $cClass            - клас на контрагента
     * @param int $cId               - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see cond_Parameters)
     * @return string $value         - стойността на параметъра
     */
    public static function getParameter($cClass, $cId, $conditionSysId)
    {
    	expect($Class = cls::get($cClass));
    	expect($Class::fetch($cId));
    	expect($condId = self::fetchIdBySysId($conditionSysId));
    	
    	// Връщаме стойността ако има директен запис за условието
    	$value = cond_ConditionsToCustomers::fetchByCustomer($cClass, $cId, $condId);
    	if($value) return $value;
    	
    	// Търсим имали дефинирано търговско условие за държавата на контрагента
    	$contragentData = cls::get($cClass)->getContragentData($cId);
    	$countryId = $contragentData->countryId;
    	if($countryId){
    		$value = cond_Countries::fetchField("#country = {$countryId} AND #conditionId = {$condId}", 'value');
    		if($value) return $value;
    	}
    	
    	// Търси се метод дефиниран за връщане на стойността на условието
    	$method = "get{$conditionSysId}";
    	if(method_exists($Class, $method)) return $Class::$method($cId);
    	
    	// Връща се супер дефолта на параметъра;
    	$default = static::fetchField($condId, 'default');
    	if(isset($default)) return $default;
    	
    	return NULL;
    }
}