<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryRules extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Правила';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Правило';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, departmentId, positionId, conditionExpr, amountExpr';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=Лице,width=100%');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name, allowEmpty=true)', 'caption=Отдел,width=100%');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name, allowEmpty=true)', 'caption=Длъжност,width=100%');
    	$this->FLD('conditionExpr',    'text', 'caption=Условие,mandatory,width=100%');
    	$this->FLD('amountExpr',    'text', 'caption=Сума,mandatory,width=100%');
    	
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        
        if ($form->isSubmitted()) {
                        
            // Ако не е цяло число
            if ($form->rec->personId && $form->rec->departmentId && $form->rec->positionId) {
                
            	$departmentId = hr_EmployeeContracts::fetchField("#personId = '{$form->rec->personId}'", 'departmentId');
    	    	$positionId = hr_EmployeeContracts::fetchField("#personId = '{$form->rec->personId}'", 'positionId');
    	    	
    	    	if($form->rec->departmentId != $departmentId || $form->rec->positionId != $positionId){
	                // Сетваме грешката
	                $form->setError('departmentId, positionId', 'Лицето не е в този отдел или не е на тази длъжност');
    	    	}
            }
            
            if(!$form->gotErrors()){
	            // Ако са въведени повече от допустимите полета полета: Лице, Отдел, Длъжност
	            if ($form->rec->personId && ($form->rec->departmentId || $form->rec->positionId)) {
	                
	                // Сетваме предупреждение
	                $form->setWarning('personId, departmentId, positionId', 'Въведени са повече от допустимите полета: Лице, Отдел или Длъжност');
	            }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	// Ако имаме права да видим визитката
    	if(crm_Persons::haveRightFor('single', $rec->personId)){
    		$name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
    		$row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
    	}
    }
    
    
    /**
     * 
     */
    public static function calculateConditionExpr()
    {
    	// Заявка по договорите
        $query = hr_EmployeeContracts::getQuery();
    	     	 
    	while($rec = $query->fetch()){
    	
    		$contracts[] = $rec;
    	}
    	
    	// Заявка по правилата
    	$querySelf = self::getQuery();
    	    	 
    	while($recSelf = $querySelf->fetch()){
    	
    		$rules[] = $recSelf;
    	}
    	
    	// тримерен масив [договор][лице][правило]
    	$result = array();
    	
    	foreach($contracts as $contract){
    		$person = $contract->personId;
    		$department = $contract->departmentId;
    		$position = $contract->positionId;
    		
    		foreach($rules as $rule){
    			$personRule = $rule->personId;
    			$departmentRule = $rule->departmentId;
    			$positionRule = $rule->positionId;
    			
    			if(($person == $personRule || $personRule == NULL) &&  
    			    ($department == $departmentRule || $departmentRule == NULL) && 
    			    ($position == $positionRule || $positionRule == NULL)){
    				$result[$contract->id][$person][$rule->id] = "Правилото се изпълнява";
    			} else {
    				$result[$contract->id][$person][$rule->id] = "Правилото не се изпълнява";
    			}
    		}
    	}
    	
    	return $result;
    }

}
