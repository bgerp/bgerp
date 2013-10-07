<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_EmployeeContracts extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,hr_ContractAccRegIntf, doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Трудови договори";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Трудов договор";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    var $filterFieldDateFrom = 'startFrom';
    var $filterFieldDateTo = 'endOn';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, hr_Wrapper, doc_ActivatePlg, plg_Printing, acc_plg_DocumentSummary,
                     acc_plg_Registry, doc_DocumentPlg, plg_Search,
                     doc_plg_BusinessDoc2,plg_AutoFilter,doc_SharablePlg';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'document';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,hr';
	
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canEdit = 'ceo,hr';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/report_user.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Td";
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'typeId, managerId, personId, specialty, 
                         departmentId, positionId, startFrom, 
                         endOn, folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.1|Човешки ресурси";
    
    
    var $listFields = 'id,typeId,personId=Имена,positionId=Позиция,startFrom,endOn';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'typeId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('typeId', 'key(mvc=hr_ContractTypes,select=name)', "caption=Тип");
        
        $this->FLD('managerId', 'key(mvc=crm_Persons,select=name,group=managers)', 'caption=Управител, mandatory');
        
        // Служител
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Имена, mandatory,width=100%');
        $this->FLD('education', 'varchar', 'caption=Служител->Образование,width=100%');
        $this->FLD('specialty', 'varchar', 'caption=Служител->Специалност,width=100%');
        $this->FLD('diplomId', 'varchar', 'caption=Служител->Диплома №,width=100%');
        $this->FLD('diplomIssuer', 'varchar', 'caption=Служител->Издадена от,width=100%');
        $this->FLD('lengthOfService', 'int', 'caption=Служител->Трудов стаж,unit=г.');
        
        // Отдел - външно поле от модела hr_Positions
        $this->EXT('departmentId', 'hr_Positions', 'externalKey=positionId,caption=Отдел');
        
        // Отдел - външно поле от модела hr_Positions
        $this->EXT('professionId', 'hr_Positions', 'externalKey=positionId,caption=Отдел');
        
        // Позиция
        $this->FLD('positionId', 'key(mvc=hr_Positions,select=name, allowEmpty)', 'caption=Работа->Позиция, mandatory,oldField=possitionId,autoFilter');
        
        // Възнаграждения
        $this->FLD('salaryBase', 'double(decimals=2)', "caption=Възнагражение->Основно");
        $this->FLD('forYearsOfService', 'percent(decimals=2)', "caption=Възнагражение->За стаж");
        $this->FLD('compersations', 'double(decimals=2)', "caption=Възнагражение->За вредности");
        $this->FLD('degreePay', 'double(decimals=2)', "caption=Възнагражение->За научна степен");

        // Срокове
        $this->FLD('startFrom', 'date(format=d.m.Y)', "caption=Време->Начало,mandatory");
        $this->FLD('endOn', 'date(format=d.m.Y)', "caption=Време->Край");
        $this->FLD('term', 'int', "caption=Време->Продължителност,unit=месеца");
        $this->FLD('annualLeave', 'int', "caption=Време->Годишен отпуск,unit=дни");
        $this->FLD('notice', 'int', "caption=Време->Предизвестие,unit=дни");
        $this->FLD('probation', 'int', "caption=Време->Изпитателен срок,unit=месеца");

        $this->FLD('descriptions', 'richtext(bucket=humanResources)', 'caption=Условия->Допълнителни');
        
        // Споделени потребители
        $this->FLD('sharedUsers', 'userList(roles=trz|ceo)', 'caption=Споделяне->Потребители');
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако нямаме права за писане в треда
    	if(doc_Threads::haveRightFor('single', $data->rec->threadId) == FALSE){
    		
    		// Премахваме бутона за коментар
	    	$data->toolbar->removeBtn('Коментар');
	    }
    }

    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->fields['departmentId']->caption = 'Отдел'; 
    	$data->listFilter->fields['professionId']->caption = 'Професия'; 
    	$data->listFilter->fields['departmentId']->mandatory = NULL; 
    	$data->listFilter->fields['positionId']->mandatory = NULL;    	
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ' ,departmentId, professionId';
        
        $data->listFilter->input();

        if($filterRec = $data->listFilter->rec){
        	if($filterRec->departmentId){
        		$data->query->where(array("#departmentId = '[#1#]'", $filterRec->departmentId));
        	}
        	
        	if($filterRec->positionId){
        		$data->query->where(array("#positionId = '[#1#]'", $filterRec->positionId));
        	}
        }
    }
    
    
	/**
     * Извиква се след изпълняването на екшън
     */
    function on_AfterAction(&$invoker, &$tpl, $act)
    {
    	if (strtolower($act) == 'single' && haveRole('hr,ceo')) {
    		
    		// Взимаме ид-то на молбата
    		$id = Request::get('id', 'int');
    		
    		// намираме, кой е текущия потребител
    		$cu =  core_Users::getCurrent();
    		
    		// взимаме записа от модела
    		$rec = self::fetch($id);
    		
    		// превръщаме кей листа на споделените потребители в масив
    		$sharedUsers = type_Keylist::toArray($rec->sahredUsers);
    		
    		// добавяме текущия потребител
    		$sharedUsers[$cu] = $cu;
    		
    		// връщаме в кей лист масива
    		$rec->sharedUsers =  keylist::fromArray($sharedUsers);
    		    		
    		self::save($rec, 'sharedUsers');

    		return  Redirect(array('doc_Containers', 'list', 'threadId'=>$rec->threadId));
    	}
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$rec = $data->form->rec;
        
        $coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        if ('crm_Persons' == $coverClass) {
        	$data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $data->form->setReadonly('personId');
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->personId = ht::createLink($row->personId, array('crm_Persons', 'Single', $rec->personId));
        
        $row->positionId = ht::createLink($row->positionId, array('hr_Departments', 'Single', $rec->departmentId, 'Tab' => 'Positions'));
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $row = $data->row;
        
        $rec = $data->rec;
        
        $row->script = hr_ContractTypes::fetchField($rec->typeId, 'script');
        
        $row->num = $data->rec->id;
        
        $row->employeeRec         = crm_Persons::fetch($rec->personId);
        $row->employeeRec->idCard = crm_ext_IdCards::fetch("#personId = {$rec->personId}");

        if(!$row->employeeRec->egn) {  
            unset($row->employeeRec->egn);
        }

        $row->employerRec = crm_Companies::fetch(crm_Setup::BGERP_OWN_COMPANY_ID);
        
        $row->managerRec = crm_Persons::fetch($rec->managerId);
        $row->managerRec->idCard = crm_ext_IdCards::fetch("#personId = {$rec->managerId}");
        $row->employersRec = crm_ext_CourtReg::fetch("#companyId = {$row->employerRec->id}");

        if(!$row->managerRec->egn) {
            unset($row->managerRec->egn);
        }

        // Взимаме данните за Длъжността
        $row->positionRec = hr_Positions::fetch($rec->positionId);

        // Извличане на данните за Структурата
        $row->departmentRec = hr_Departments::fetch($rec->departmentId);
     
        // Изчисляваме работното време
        $houresInSec = self::houresForAWeek($rec->id);
        $houres = $houresInSec / 60 / 60;
        
        $row->shiftRec = new stdClass();
                        
        if($houres % 2 !== 0){
        	$min = round(($houres - round($houres)) * 60);
        	
        	$row->shiftRec->weekhours =  round($houres) . " часа". " и " . $min . " мин.";	
        } else {
	        // да добавя и минитуте
			$row->shiftRec->weekhours =  $houres . " часа";
        }
       
        // Продължителността на договора
        $row->term = (int)$rec->term;
        
		$res = $data;
    }
  
    
    /**
     * Render single
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        $row = $data->row;
        
        $lsTpl = cls::get('legalscript_Engine', array('script' => $row->script));
        
        unset($row->script);

        $contract = $lsTpl->render($row);

        $res = new ET("[#toolbar#]
        <div class='document'>[#contract#]</div> <div style='clear:both;'></div>
        
        ");
        
        $res->replace($contract, 'contract');
        
        $res->replace($mvc->renderSingleToolbar($data), 'toolbar');
        
        return FALSE;
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $this->getVerbal($rec, 'personId') . " [" . $this->getVerbal($rec, 'startFrom') . ']',
                'num' => $rec->id,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    static function act_Test()
    {
    	$id = 2;
    	bp(self::houresForAWeek($id));
    }
    
    
    static public function getWorkingSchedule($id)
    {
    	$departmentId = self::fetchField($id, 'departmentId');
    	
    	$schedule = hr_Departments::fetchField($departmentId, 'schedule');
    	
    	return $schedule;
    }
    
    
    /**
     * 
     * Изчислява седмичното натоварване според графика в секунди
     * @param int $id
     */
    static public function houresForAWeek($id)
    {
    	// Кой е графика
    	$scheduleId = static::getWorkingSchedule($id);
    	
    	// Каква продължителност има
    	$duration = hr_WorkingCycles::fetchField($scheduleId, 'cycleDuration');
    	
    	// Извличане на данните за циклите
        $stateDetails = hr_WorkingCycleDetails::getQuery();

		// Подробности за конкретния цикъл
		$stateDetails->where("#cycleId='{$scheduleId}'");
		while ($rec = $stateDetails->fetch()){
			$cycleDetails [] = $rec;
		}
		
		if(is_array($cycleDetails)){
			foreach($cycleDetails as $cycDuration){
			
				$allHours += $cycDuration->duration;
				$break += $cycDuration->break;
			}
			
			$hoursWeekSec = ($allHours - $break) / $duration  * 7 ;
		} else {
			$hoursWeekSec = 0;
		}
		return $hoursWeekSec;
    }

    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        if (cls::haveInterface('crm_PersonAccRegIntf', $coverClass)) {
        	return TRUE;
        }
        
        /*$personId = doc_Folders::fetchCoverId($folderId);
        
        $personRec = crm_Persons::fetch($personId);
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        return keylist::isIn($emplGroupId, $personRec->groupList);*/
        
        return FALSE;
    }
 
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = tr('Трудов договор на|* ') . $this->getVerbal($rec, 'personId');
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интефейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('crm_PersonAccRegIntf');
    }
 
}
