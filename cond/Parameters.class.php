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
class cond_Parameters extends embed_Manager
{
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cond_ParamTypeIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cond_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, driverClass, state';
    
    
    /**
     * Заглавие
     */
    public $title = 'Търговски условия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Търговско условие";
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,cond';
    
    
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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cond';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('default', 'varchar(64)', 'caption=Дефолт');
        $this->FLD('sysId', 'varchar(32)', 'caption=Sys Id, input=hidden');
        $this->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,default=no,maxRadio=2,value=no,hint=Използване като признак за групиране в счетоводните справки?');
        
        $this->setDbUnique('name');
        $this->setDbUnique("sysId");
    }
    
    
    /**
     * Връща типа на параметъра
     *
     * @param mixed $id - ид или запис на параметър
     * @return FALSE|cond_type_Proto - инстанцирания тип или FALSE ако не може да се определи
     */
    public static function getTypeInstance($id)
    {
    	$rec = static::fetchRec($id);
    	if($Driver = static::getDriver($rec)){
    		return $Type = $Driver->getType($rec);
    	}
    	 
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	core_Classes::add($rec->driverClass);
    	$rec->driverClass = cls::get($rec->driverClass)->getClassId();
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setField('driverClass', 'caption=Тип,input');
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
    			3 => "default");
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
	/**
     * Връща стойността на дадено търговско условие за клиента
     * 
     * @param int $cId - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see cond_Parameters)
     * @return string $value - стойността на параметъра
     * Намира се в следния ред:
     * 	  1. Директен запис в cond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. Супер дефолта на параметъра дефиниран в cond_Parameters
     *    4. NULL ако нищо не е намерено
     */
    public static function getParameter($cClass, $cId, $conditionSysId, $mvc = NULL)
    {
    	expect($Class = cls::get($cClass));
    	expect($Class::fetch($cId));
    	expect($condId = static::fetchField("#sysId = '{$conditionSysId}'", 'id'));
    	
    	if($mvc){
    		if(is_string($mvc)){
    			expect($mvc = cls::get($mvc));
    		}
    	}
    	
    	// Връщаме стойността ако има директен запис за условието
    	if($value = cond_ConditionsToCustomers::fetchByCustomer($cClass, $cId, $condId)){
    		
    		return $value;
    	}
    	
    	// Търсим имали дефинирано търговско условие за държавата на контрагента
    	$contragentData = cls::get($cClass)->getContragentData($cId);
    	$countryId = $contragentData->countryId;
    	if($countryId){
    		if($value = cond_Countries::fetchField("#country = {$countryId} AND #conditionId = {$condId}", 'value')){
    		
    			return $value;
    		}
    	}
    	
    	// Търси се метод дефиниран за връщане на стойността на условието
    	$method = "get{$conditionSysId}";
    	if(method_exists($Class, $method)){
    		
    		return $Class::$method($cId);
    	}
    	
    	// Връща се супер дефолта на параметъра;
    	$default = static::fetchField($condId, 'default');
    	
    	if(isset($default)) return $default;
    	
    	return NULL;
    }
}