<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_EmployeeContracts extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,hr_ContractAccRegIntf, doc_DocumentIntf, bgerp_plg_Blank';
    
    
    /**
     * Заглавие
     */
    public $title = "Трудови договори";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Трудов договор";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startFrom';
    
    /**
     * @todo Чака за документация...
     */
    public $filterFieldDateTo = 'endOn';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, hr_Wrapper, doc_ActivatePlg, bgerp_plg_Blank, plg_Printing, acc_plg_DocumentSummary,
                     acc_plg_Registry, doc_DocumentPlg, plg_Search,
                     doc_plg_BusinessDoc,plg_AutoFilter,doc_SharablePlg';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'document';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hr';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/report_user.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Td";
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'typeId,dateId, managerId, personId, specialty, 
                         departmentId, positionId, startFrom, 
                         endOn, folderId, threadId, containerId';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.1|Човешки ресурси";
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,dateId,title=Документ,personId=Имена,typeId,numId,positionId=Позиция,startFrom,endOn';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * В коя номенклатура да се добави при активиране
     */
    public $addToListOnActivation = 'workContracts';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('typeId', 'key(mvc=hr_ContractTypes,select=name)', "caption=Договор->Тип");
        
        $this->FLD('managerId', 'key(mvc=crm_Persons, select=name,group=managers)', 'caption=Договор->Управител, mandatory');
        
        $this->FLD('numId', 'int', 'caption=Договор->Номер');
        $this->FLD('dateId', 'date(format=d.m.Y)', 'caption=Договор->Дата, mandatory');
        
        // Служител
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Имена, mandatory,width=100%');
        $this->FLD('education', 'varchar', 'caption=Служител->Образование,width=100%');
        $this->FLD('specialty', 'varchar', 'caption=Служител->Специалност,width=100%');
        $this->FLD('diplomId', 'varchar', 'caption=Служител->Диплома №,width=100%');
        $this->FLD('diplomIssuer', 'varchar', 'caption=Служител->Издадена от,width=100%');
        $this->FLD('lengthOfService', 'time(suggestions=1 мес|2 мес|3 мес|4 мес|5 мес|6 мес|7 мес|8 мес|9 мес|10 мес|11 мес|12 мес|2 год|3 год|5 год,uom=months,allowEmpty)', 'caption=Служител->Трудов стаж, unit=мес');
        
        // Отдел - външно поле от модела hr_Positions
        $this->EXT('departmentId', 'hr_Positions', 'externalKey=positionId,caption=Отдел');
        
        // Отдел - външно поле от модела hr_Positions
        $this->EXT('professionId', 'hr_Positions', 'externalKey=positionId,caption=Отдел');
        
        // Позиция
        $this->FLD('positionId', 'key(mvc=hr_Positions,select=name)', 'caption=Работа->Позиция, mandatory,oldField=possitionId,autoFilter');
        
        // Възнаграждения
        $this->FLD('salaryBase', 'double(decimals=2)', "caption=Възнагражение->Основно");
        $this->FLD('forYearsOfService', 'percent(decimals=2)', "caption=Възнагражение->За стаж");
        $this->FLD('compersations', 'double(decimals=2)', "caption=Възнагражение->За вредности");
        $this->FLD('degreePay', 'double(decimals=2)', "caption=Възнагражение->За научна степен");
        
        // Срокове
        $this->FLD('startFrom', 'date(format=d.m.Y)', "caption=Време->Начало,mandatory");
        $this->FLD('endOn', 'date(format=d.m.Y)', "caption=Време->Край");
        $this->FLD('term', 'time(suggestions=3 мес|6 мес|9 мес|12 мес|24 мес,uom=months,allowEmpty)', "caption=Време->Продължителност, unit=мес");
        $this->FLD('annualLeave', 'time(suggestions=10 дни|15 дни|20 дни|22 дни|25 дни,uom=days,allowEmpty)', "caption=Време->Годишен отпуск,unit=дни");
        $this->FLD('notice', 'time(suggestions=10 дни|15 дни|20 дни|30 дни,uom=days,allowEmpty)', "caption=Време->Предизвестие,unit=дни");
        $this->FLD('probation', 'time(suggestions=1 мес|2 мес|3 мес|6 мес|9 мес|12 мес,uom=month,allowEmpty)', "caption=Време->Изпитателен срок, unit=мес");
        
        $this->FLD('descriptions', 'richtext(bucket=humanResources, shareUsersRoles=trz|ceo)', 'caption=Условия->Допълнителни');
        
        // Споделени потребители
        $this->FLD('sharedUsers', 'userList(roles=trz|ceo)', 'caption=Споделяне->Потребители');
        
        $this->setDbUnique('numId');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
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
    public static function on_AfterAction(&$invoker, &$tpl, $act)
    {
        if (strtolower($act) == 'single' && haveRole('hr,ceo') && !Mode::is('printing')) {
            
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
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        // Скриваме опцията за номеклатурата
        //$data->form->fields['lists']->input = "none";
        
        $coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        //Полето Служител->Име не може да се променя
        if ('crm_Persons' == $coverClass) {
            $data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
            $data->form->setReadonly('personId');
        }
        // по дефолт слагаме днешна дата
        $data->form->setDefault('dateId', dt::verbal2mysql());
        
        // сладаме Управители
        $managers = $mvc->getManagers();
        $data->form->setOptions('managerId', $managers);
        
    	if(!haveRole('ceo,hr')){
    		$data->form->setField('numId', 'input=none');
    	}
    	
    	$data->form->setDefault('numId', self::getNexNumber());
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->personId = crm_Persons::getHyperLink($rec->personId, TRUE);
        $row->positionId = ht::createLink($row->positionId, array('hr_Departments', 'Single', $rec->departmentId, 'Tab' => 'Positions'));
    
        if(isset($fields['-list'])){
        	$row->title = $mvc->getLink($rec->id, 0);
        }
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	// трудовият договор, не може да се създаде без да е обявено работното време в него
    	// в системата, работното време се определя от различните графици
    	// те от своя страна се добавят към отделите (структура)
    	$queryWorkingCycle = hr_Departments::getQuery();
    	
    	if($queryWorkingCycle->fetch("#schedule") == FALSE){
    	
    		// Ако няма, изискваме от потребителя да въведе
    		return  Redirect(array('hr_Departments', 'list'), NULL,  "Не сте въвели работни графици");
    	}
    	
        $row = $data->row;
        
        $rec = $data->rec;
        
        $row->script = hr_ContractTypes::fetchField($rec->typeId, 'script');
        
        //tuk
        //$row->num = $data->rec->id;
        
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
        $position = hr_Positions::recToVerbal(hr_Positions::fetch($rec->positionId, 'name, salaryBase, forYearsOfService, compensations,
                                                    annualLeave, notice, probation'));
        
        // Вземаме данните за Структурата
        $department = hr_Departments::recToVerbal(hr_Departments::fetch($rec->departmentId, 'nkid, type'));
        
        if((!$rec->salaryBase || !$rec->forYearsOfService || !$rec->compensations) &&
            (!$rec->annualLeave || !$rec->notice || !$rec->probation)) { ;
            
            // Професията
            $row->positionsId = $position->professionId;
            
            // Заплатата
            $row->salaryBase = $position->salaryBase;
            
            // Процент прослужено време
            $row->forYearsOfService = $position->forYearsOfService;
            
            // Заплащане за вредност
            $row->compensations = $position->compensations;
            
            // Годишен отпуск
            $row->annualLeave = $position->annualLeave;
            
            // Предизвестие
            $row->notice = $position->notice;
            
            // Изпитателен срок
            $row->probation = $position->probation;
        }
        
        // Професията
        $row->positionsId = $position->professionId;
        
        // Период на изплащане на възнаграждението
        $row->frequensity =  $position->frequensity;
        
        // Извличане на данните за професията
        $nkpd = hr_Professions::fetchField($rec->professionId, 'nkpd');
        
        // Национална класификация на професиите и длъжностите
        $row->professionsRec = new stdClass();
        $row->professionsRec->nkpd = bglocal_NKPD::getTitleById($nkpd);
        
        // Национална класификация на икономическите дейности 
        $row->departmentRec = new stdClass();
        $row->departmentRec->nkid = $department->nkid;
        
        // Вид на структурата
        $row->departmentRec->type = $department->type;
        
        // Изчисляваме работното време
        $houresInSec = self::houresForAWeek($rec->id);
        $houres = $houresInSec / 60 / 60;
        
        $row->shiftRec = new stdClass();
        
        if($houres % 2 !== 0){
            $min = round(($houres - round($houres)) * 60);
            
            $row->shiftRec->weekhours =  round($houres) . " часа" . " и " . $min . " мин.";
        } else {
            // да добавя и минитуте
            $row->shiftRec->weekhours =  $houres . " часа";
        }
        
        // Продължителността на договора
        $row->term = (int)$rec->term;
        
        $res = $data;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        // След като се записали/активирали формата
        if($rec->typeId){
            
            // Вземаме шаблона на труговия договор
            $tpl = hr_ContractTypes::fetchField($rec->typeId, 'script');
            
            // и намираме всички плейсхолдери в него
            preg_match_all('/\[#([a-zA-Z0-9_:]{1,})#\]/', $tpl, $matches);
      
            // помощен масив, тези полете от формата на модела не са от значение за шаблона
            $sysArray = array("id", "ret_url", "typeId", "managerId", "personId", "departmentId",
                "descriptions", "sharedUsers", "sharedViews", "searchKeywords", "professionId",
                "folderId", "threadId", "containerId", "originId", "state", "brState",
                "lastUsedOn", "createdOn", "createdBy", "modifiedOn", "modifiedBy", "lists");
            $sysArrayCnt = count($sysArray);
            // От всички полета на модела
            foreach($rec as $name=>$value){
                $formField[$name] = $name;
                
                for($i = 0; $i <= $sysArrayCnt; $i++){
                    // махаме тези от помощния масив
                    unset($formField[$sysArray[$i]]);
                }
            }
            
            // намираме сечението на останалите полета и полетата от шаблона
            $mandatoryFields = array_intersect($formField, $matches[1]);
            
            foreach($mandatoryFields as $field){
                // Ако имаме непопълнено поле от гореполучения масив
                if($rec->$field == NULL){
                    // Предупреждамае потребителя
                    $form->setWarning($field, "Непопълнено поле" . "\n" . "|* <b>|" . $form->fields[$field]->caption . "!" . "|*</b> |");
                }
            }
        }
        
    	if ($form->isSubmitted()) {

	        if($rec->numId){
		        if(!$mvc->isNumberInRange($rec->numId)){
					$form->setError('numId', "Номер '{$rec->numId}' е извън позволения интервал");
				}
	        }
        }
    }
    
    
    /**
     * След промяна на обект от регистър
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	$position = self::fetchField($id, "positionId");

    	if($rec->state == 'active'){
            
            // Взимаме запълването до сега
            $employmentOccupied = hr_Positions::fetchField($position, 'employmentOccupied');
            
            // Изчисляваме работното време
            $houresInSec = self::houresForAWeek($rec->id);
            $houres = $houresInSec / 60 / 60;
            
            $recPosition = new stdClass();
            $recPosition->id = $position;
            
            // Ако работната седмица е над 35ч е един щат
            if($houres >= 35){
                $recPosition->employmentOccupied = $employmentOccupied + 1;
            } else {
                
                // в противен случай е половин щат
                $recPosition->employmentOccupied = $employmentOccupied + 0.5;
            }
            
            // записваме новата стойност
            hr_Positions::save($recPosition, 'employmentOccupied');
        }
    }
    
    
    /**
     * Render single
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    public static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        $row = $data->row;
        
        $lsTpl = cls::get('legalscript_Engine', array('script' => $row->script));
        
        unset($row->script);
        
        $contract = $lsTpl->render($row);
        
        $res = new ET("[#toolbar#]
        <div class='document'>
        [#blank#]<br>
        [#contract#]</div> <div style='clear:both;'></div>
        
        ");
        
        $res->replace($mvc->renderSingleLayout($data), 'blank');
        
        $res->replace($contract, 'contract');
        
        $res->replace($mvc->renderSingleToolbar($data), 'toolbar');
        
        return FALSE;
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        // Проверяваме дали имаме въведени позиции
        $query = hr_Positions::getQuery();
        
        if($query->fetchAll() == FALSE){
            
            // Ако няма, изискваме от потребителя да въведе
            return  Redirect(array('hr_Departments', 'list'), NULL,  "Не сте въвели позиция");
        }
        
        // трудовият договор, не може да се създаде без да е обявено работното време в него
        // в системата, работното време се определя от различните графици
        // те от своя страна се добавят към отделите (структура)
        $queryWorkingCycle = hr_WorkingCycles::getQuery();
        
        if($query->fetchAll() == FALSE){
        
        	// Ако няма, изискваме от потребителя да въведе
        	return  Redirect(array('hr_WorkingCycles', 'list'), NULL,  "Не сте въвели работни графици");
        }
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        if($rec->state == 'draft'){
        	if(empty($rec->numId) && $rec->numId == NULL){
        		$rec->numId = self::getNexNumber();
        		$rec->searchKeywords .= " " . plg_Search::normalizeText($rec->numId);
        	}
        }
        
        // трудовият договор, не може да се създаде без да е обявено работното време в него
        // в системата, работното време се определя от различните графици
        // те от своя страна се добавят към отделите (структура)
        $queryWorkingCycle = hr_Departments::getQuery();
    
        if($queryWorkingCycle->fetch("#schedule") == FALSE){

        	// Ако няма, изискваме от потребителя да въведе
        	return  Redirect(array('hr_Departments', 'list'), NULL,  "Не сте въвели работни графици");
        }
    }
    
    
    /**
     * Валидиране на полето 'number' - номер на фактурата
     * 
     * Предупреждение при липса на ф-ра с номер едно по-малко от въведения.
     */
    public function on_ValidateNumber(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->numId)) {
            return;
        }
        
        $prevNumber = intval($rec->numId)-1;
        if (!$mvc->fetchField("#numId = {$prevNumber}")) {
            $form->setWarning('numId', 'Липсва договор с предходния номер!');
        }
    }
    
    
	/**
     * Валидиране на полето 'dateId' - дата на трудовия договор
     * Предупреждение ако има трудов договор с по-нова дата (само при update!)
     */
    public static function on_ValidateDate(core_Mvc $mvc, $rec, core_Form $form)
    {
    	$newDate = $mvc->getNewestContractDate();
    	if($newDate > $rec->dateId) {
    		
    		// Най-новият валиден трудов договор в БД е по-нов от настоящият.
    		$form->setError('dateId',
    				'Не може да се запише трудов договор с дата по-малка от последния активен трудов договор (' .
    				dt::mysql2verbal($getNewestContractRec->date, 'd.m.y') .
    				')'
    		);
    	}
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function act_Test()
    {
        $id = 2;
    }
    
    
	/**
     * Връща датата на последната ф-ра
     */
    protected function getNewestContractDate()
    {
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	$query->orderBy('dateId', 'DESC');
    	$query->limit(1);
    	$lastRec = $query->fetch();
    	
    	return $lastRec->dateId;
    }
    
    
    /**
     * Връща всички Всички лица, които могат да бъдат титуляри на сметка
     * тези включени в група "Управители"
     */
    public function getManagers()
    {
        $options = array();
        $groupId = crm_Groups::fetchField("#sysId = 'managers'", 'id');
        $personQuery = crm_Persons::getQuery();
        $personQuery->where("#groupList LIKE '%|{$groupId}|%'");
        
        while($personRec = $personQuery->fetch()) {
            $options[$personRec->id] = crm_Persons::getVerbal($personRec, 'name');
        }
        
        if(count($options) == 0) {
            return Redirect(array('crm_Persons', 'list'), NULL, 'Няма лица в група "Управители" за управител. Моля добавете !');
        }
        
        return $options;
    }

    
    /**
     * @todo Чака за документация...
     */
    public static function getWorkingSchedule($id)
    {
        $departmentId = self::fetchField($id, 'departmentId');
        
        $schedule = hr_Departments::fetchField($departmentId, 'schedule');
        
        return $schedule;
    }
    
    
    /**
     * Изчислява седмичното натоварване според графика в секунди
     * @param int $id
     */
    public static function houresForAWeek($id)
    {
        // Кой е графика
        $scheduleId = static::getWorkingSchedule($id);
        
         // Каква продължителност има
        $duration = hr_WorkingCycles::fetchField($scheduleId, 'cycleDuration');
        
        if (!$duration) {
        	return Redirect(array('hr_WorkingCycles', 'list'), NULL, 'Не сте въвели продължителност на графика!');
        }
        
       
        
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
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    public function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $this->getVerbal($rec, 'personId') . " [" . $this->getVerbal($rec, 'startFrom') . ']',
                'num' => $rec->id . " ec",
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
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
        
        return FALSE;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
        return array('crm_PersonAccRegIntf');
    }
    
    
    /**
     * Дали подадения номер е в позволения диапазон за номера на фактури
     * @param $number - номера на фактурата
     */
    private function isNumberInRange($numId)
    {
    	expect($numId);
    	$conf = core_Packs::getConfig('sales');
    	
    	return ($conf->HR_EC_MIN <= $numId && $numId <= $conf->HR_EC_MAX);
    }
    
    
    /**
     * Ф-я връщаща следващия номер на фактурата, ако той е в границите
     * @return int - следващия номер на фактура
     */
    protected static function getNexNumber()
    {
    	$conf = core_Packs::getConfig('hr');
    	
    	$query = static::getQuery();
    	$query->XPR('maxNum', 'int', 'MAX(#numId)');
    	if(!$maxNum = $query->fetch()->maxNum){
    		$maxNum = $conf->HR_EC_MIN;
    	}
    	$nextNum = $maxNum + 1;
    	
    	if($nextNum > $conf->HR_EC_MAX) return NULL;
    	
    	return $nextNum;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	$title = tr('Трудов договор на|* ') . $me->getVerbal($rec, 'personId');
    	
    	return $title;
    }
}
