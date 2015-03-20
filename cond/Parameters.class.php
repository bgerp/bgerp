<?php



/**
 * Клас 'cond_Parameters' - Търговски параметри
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Parameters extends core_Master
{
    
    
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'salecond_Parameters';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cond_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, name, type, state';
    
    
    /**
     * Заглавие
     */
    var $title = 'Бизнес параметри';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Търговски параметри";
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'ceo,cond';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'ceo,cond';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,cond';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cond';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,cond';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'ceo,cond';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число,varchar=Текст,date=Дата,enum=Изброим,percent=Процент,payMethod=Начин за плащане,delCond=Условие на доставка)', 'caption=Тип');
        $this->FLD('options', 'varchar(128)', 'caption=Стойности');
        $this->FLD('default', 'varchar(64)', 'caption=Дефолт');
        $this->FLD('sysId', 'varchar(32)', 'caption=Sys Id, input=hidden');
        $this->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,default=no,maxRadio=2,value=no,hint=Използване като признак за групиране в счетоводните справки?');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	if($rec->options){
        		$vArr = explode(",", $rec->options);
        		$Type = cls::get("type_{$rec->type}");
        		foreach($vArr as $option){
        			if($rec->type != 'enum' && !$Type->fromVerbal($option)){
        				$form->setError('options', "Някоя от зададените стойности не е от типа {$rec->type}");
        			}
        		}
        	} else {
        		if($rec->type == 'enum'){
        			$form->setError('options', "За изброим тип задължително трябва да се се зададат стойностти");
        		}
        	}
        }
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cond/csv/Parameters.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "type", 
	    	2 => "sysId", 
	    	3 => "default");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	// @TODO Миграция да се махне след като се разнесе
    	$oldDelCond = $mvc->fetchField('#sysId = "deliveryTerm"', 'id');
    	$oldPayCond = $mvc->fetchField('#sysId = "paymentMethod"', 'id');
    	
    	if(empty($oldDelCond) || empty($oldPayCond)) return;
    	
    	$newDelCond = $mvc->fetchField('#sysId = "deliveryTermSale"', 'id');
    	$newPayCond = $mvc->fetchField('#sysId = "paymentMethodSale"', 'id');
    	
    	$condQuery = cond_ConditionsToCustomers::getQuery();
    	$condQuery->where("#conditionId = {$oldDelCond} || #conditionId = {$oldPayCond}");
    	while($condRec = $condQuery->fetch()){
    		if($condRec->conditionId == $oldDelCond){
    			$condRec->conditionId = $newDelCond;
    		} else {
    			$condRec->conditionId = $newPayCond;
    		}
    		cond_ConditionsToCustomers::save($condRec);
    	}
    	
    	cond_Parameters::delete($oldDelCond);
    	cond_Parameters::delete($oldPayCond);
    	
    	return $res;
    }
    
    
	/**
     * Връща стойността на дадено търговско условие за клиента
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
    	
    	//Връщаме стойността ако има директен запис за условието
    	if($value = cond_ConditionsToCustomers::fetchByCustomer($cClass, $cId, $condId)){
    		return $value;
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
    
    
    /**
     * Помощен метод за извличане на информация на параметър
     * @param int $id - ид на параметър
     * @return stdClass $res
     * 				    ->type - тип на полето
     * 					->options - масив с допустими стойности
     */
    public static function getParamInfo($id)
    {
    	$res = new stdClass();
    	$res->options = array();
    	expect($rec = static::fetch($id));
    	$res->type = $rec->type;
    	
    	if($rec->options){
    		$res->options = array('' => '') + arr::make($rec->options, TRUE);
    	}
    	return $res;
    }
}