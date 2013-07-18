<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryIndicators extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Показатели';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper, trz_SalaryWrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,trz';
    
    //var $canDeletesysdata= 'ceo,trz';
    
        
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, date, docClass, docId, personId, departmentId, positionId, indicator, value';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('date',    'date', 'caption=Дата,mandatory,width=100%');
    	$this->FLD('docId',    'int', 'caption=Документ->№,mandatory,width=100%');
    	$this->FLD('docClass',    'key(mvc=core_Classes, select=name)', 'caption=Документ->Клас,mandatory,width=100%');
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Лице,mandatory,width=100%');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name)', 'caption=Служител->Отдел,mandatory,width=100%');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name)', 'caption=Служител->Длъжност,mandatory,width=100%');
    	$this->FLD('indicator',    'varchar', 'caption=Индикатор->Име,mandatory,width=100%');
    	$this->FLD('value',    'double', 'caption=Индикатор->Стойност,mandatory,width=100%');
    	
    	$this->setDbUnique('docId, docClass, personId, indicator');
    	
    }
    
    
    /**
     * Изпращане на данните към показателите
     */
    function cron_Indicators()
    {
        $date = dt::now(FALSE);
       
        $this->pushIndicators($date);

    }
    
    function act_Test()
    {
    	$date = '2013-07-16';
    	bp(self::pushIndicators($date));
    }
    
    
    /**
     * Събиране на информация от всички класове
     * имащи интерфейс trz_SalaryIndicatorsSourceIntf
     * 
     * @param date $date
     */
    static public function fetchIndicators($date)
    {
    	// Намираме всички класове съдържащи интерфейса
    	$docArr = core_Classes::getOptionsByInterface('trz_SalaryIndicatorsSourceIntf');
    	$indicators = array();
    
    	// Зареждаме всеки един такъв клас
    	foreach ($docArr as $doc){
    		$Class = cls::get($doc);
    		
    		// Взимаме връщания масив от интерфейсния метод
    	    $data = $Class->getSalaryIndicators($date);

    	    // По id-то на служителя, намираме от договора му
    	    // в кой отдел и на каква позиция работи
    	    for($i = 0; $i < count($data); $i ++){
    	    	$data[$i]->departmentId = hr_EmployeeContracts::fetchField("#personId = '{$data[$i]->personId}'", 'departmentId');
    	    	$data[$i]->positionId = hr_EmployeeContracts::fetchField("#personId = '{$data[$i]->personId}'", 'positionId');
    	    	
    	    }
            
    	    if(is_array($data)){
    	    	// Сливаме всичко в един масив
    			$indicators = array_merge($indicators, $data);
    	    }
    		
    	}
      
    	return $indicators;
    }
    
    
    /**
     * Пълнене на базата данни
     * 
     * @param date $date
     */
    static public function pushIndicators($date)
    {
    	$indicators = self::fetchIndicators($date);
    	
    	// За всеки един елемент от масива
    	foreach ($indicators as $indicator)
    	{
    		$rec->date = $date;
	    	$rec->docId = $indicator->docId;
	    	$rec->docClass = $indicator->docClass;
	    	$rec->personId = $indicator->personId;
	    	$rec->departmentId = $indicator->departmentId; 
	    	$rec->positionId = $indicator->positionId;
	    	$rec->indicator = $indicator->indicator;
	    	$rec->value = $indicator->value;
	    	$rec->key = $indicator->docClass . $indicator->docId;
    		
	    	$mvc = cls::get('core_Mvc');
	    	$exRec = new stdClass();
	    	
	    	// Ако имаме уникален запис го записваме
	    	// в противен слувай го ъпдейтваме
    		$res = $mvc->isUnique($rec, $fields, $exRec);

    		if($res == TRUE){
    			self::save($rec);
    		}else { 
            	$rec = $exRec;
            	self::save($rec);
            }
            
	    	
    	}
    }

    
    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "CollectIndicators";
        $rec->description = "Индикатори на заплатите";
        $rec->controller = "trz_SalaryIndicators";
        $rec->action = "Indicators";
        $rec->period = 3*60;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
        
        $res .= "<li>Напомняне  по крон</li>";
    }
}