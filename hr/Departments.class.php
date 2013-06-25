<?php 


/**
 * Структура
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Departments extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Организационна структура";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Структурно звено";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
        
    
    /**
     * Плъгини за зареждане
     */
   
    var $loadList = 'plg_RowTools, hr_Wrapper, doc_FolderPlg, plg_Printing,
                     plg_Created, WorkingCycles=hr_WorkingCycles,acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'hr/tpl/SingleLayoutDepartment.shtml';
    

    /**
     * Единична икона
     */
    var $singleIcon = 'img/16/user_group.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name, type, nkid, staff, dependent, locationId, employersCntAll, employersCnt, schedule';
    
    
    var $details = 'grafic=hr_WorkingCycles';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('type', 'enum(department=отдел, link=звено, brigade=бригада)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title)', 'caption=НКИД, hint=Номер по НКИД');
        $this->FLD('staff', 'key(mvc=hr_Departments, select=name, allowEmpty)', 'caption=В състава на,width=100%');
        $this->FLD('dependent', 'keylist(mvc=hr_Departments, select=name)', "caption=Подчинен на,width=100%");
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('employersCntAll', 'int', "caption=Служители->Щат, input=none");
        $this->FLD('employersCnt', 'int', "caption=Служители->Назначени, input=none");
        $this->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name)', "caption=Работно време->График");
        $this->FLD('startingOn', 'datetime', "caption=Работно време->Начало");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('locationId', array('' => '&nbsp;') + crm_Locations::getOwnLocations());
    }
    
    
    /**
     * Проверка за зацикляне след субмитване на формата. Разпъване на всички наследени роли
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;

        // Ако формата е субмитната и редактираме запис
        if ($form->isSubmitted() && ($rec->id)) {
            
            if($rec->staff || $rec->dependent) {
                
                $expandedDepartment = self::expand($form->rec->dependent);
                
                // Ако има грешки
                if ($expandedDepartment[$rec->id]) {
                    $form->setError('dependent', "|Не може отдела да е подчинен на себе си");  
                } else {
                    $rec->dependent = keylist::fromArray($expandedDepartment);
                }

            }
         }
    }
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        
      //  $data->query->orderBy("#orderSum");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, $id, $rec)
    {
        if($rec->parentId) {
            $parentRec = $mvc->fetch($rec->parentId);
            
            if($parentRec) {
                $rec->orderId = ($parentRec->orderId + $parentRec->id) * 1000;
            }
        }
    }
    
    
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	
    	//bp($data);
    }
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	
    	if($action == 'delete'){
	    	if ($rec->id) {
	        	
	    		$inUse = hr_EmployeeContracts::fetch("#departmentId = '{$rec->id}'");
	    		
	    		if($inUse){
	    			$requiredRoles = 'no_one';
	    		}
    	     }
         }
    }
    
    static public function expand($departments, &$current = array())
    {
    	if (!is_array($departments)) {
            $departments = keylist::toArray($departments, TRUE);
        }
        
        foreach ($departments as $department) {
            if (is_object($department)) {
                $rec = $department;
            } elseif (is_numeric($department)) {
                $rec = static::fetch($department);
            } else {
                $rec = static::fetch("#dependent = '{$department}'");
            }
            
            // Прескачаме насъсществуващите роли
            if(!$rec) continue;
            
            if ($rec && !isset($current[$rec->id])) {
                $current[$rec->id] = $rec->id;
                $current += static::expand($rec->dependent, $current);
            }
        }
        
        return $current;
    }

    
}