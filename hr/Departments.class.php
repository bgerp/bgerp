<?php 


/**
 * Структура
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Departments extends core_Master
{
    
	
     /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf, hr_DepartmentAccRegIntf, doc_FolderIntf';
    
    
    /**
     * Детайли на този мастер
     */
    public $details = 'AccReports=acc_ReportDetails,Grafic=hr_WorkingCycles';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '611,60020';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'hr_DepartmentAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Организационна структура";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Звено";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, hr_Wrapper, doc_FolderPlg, plg_Printing, plg_State, plg_Rejected,
                        plg_Created, WorkingCycles=hr_WorkingCycles,acc_plg_Registry, plg_SaveAndNew, 
                        plg_TreeObject, plg_Modified, bgerp_plg_Blank,plg_ExpandInput';
    
    
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
     * Кой може да оттегля
     */
    public $canReject = 'ceo,hr';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo,hr';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutDepartment.shtml';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/user_group.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,name';
   
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, type, locationId, employmentOccupied=Назначени, employmentTotal=От общо, schedule=График';

    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'public';
    
    
    /**
     * Видове графики
     */
    //public static $chartTypes = array('List' => 'Tаблица', 'StructureChart' => 'Графика',);
    
    
    /**
     * Полето, което ще се разширява
     * @see plg_ExpandInput
     */
    public $expandFieldName = 'parentId';
     
    
    /**
     * Кои полета да се сумират за наследниците
     */
    public $fieldsToSumOnChildren = 'employmentTotal,employmentOccupied';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('parentId', "key(mvc=hr_Departments,allowEmpty,select=name)", 'caption=В състава на,mandatory');
        $this->FLD('type', 'enum(section=Поделение,
                                 branch=Клон,
                                 office=Офис,
                                 affiliate=Филиал,
                                 division=Дивизия,
                                 direction=Дирекция,
                                 department=Oтдел,
                                 plant=Завод,
                                 workshop=Цех,
                                 store=Склад,
				                 shop=Магазин,
                                 unit=Звено,
                                 brigade=Бригада,
                                 shift=Смяна,
                                 organization=Учреждение)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('activities', 'enum(yes=Да, no=Не)', "caption=Център на дейности,maxRadio=2,columns=2,notNull,value=no, input=none,");
        
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=Служители->НКИД, hint=Номер по НКИД');
        $this->FLD('employmentTotal', 'int', "caption=Служители->Щат, input=none");
        $this->FLD('employmentOccupied', 'int', "caption=Служители->Назначени, input=none");
        $this->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name, allowEmpty=true)', "caption=Работен график->График,mandatory");
        $this->FLD('startingOn', 'datetime', "caption=Работен график->От");
        $this->FLD('orderStr', 'varchar', "caption=Подредба,input=none,column=none");
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
        $this->FLD('systemId', 'varchar', 'input=none');
        
        $this->setDbUnique('systemId');
        $this->setDbUnique('name');
    }


    /**
     * Изпълнява се след четене на запис
     */
    protected static function on_AfterRead($mvc, &$rec)
    {
        if($rec->name == 'Моята Организация') {
            $rec->name = crm_Companies::fetchField(crm_Setup::BGERP_OWN_COMPANY_ID, 'name');
        }
    }
    
    
    /**
     * Добавя данните за записа, които зависят от неговите предшественици и от неговите детайли
     */
    public static function expandRec($rec)
    {
        $parent = $rec->parentId;
        
        while($parent && ($pRec = self::fetch($parent))) {
            setIfNot($rec->nkid, $pRec->nkid);
            setIfNot($rec->locationId, $pRec->locationId);
            $parent = $pRec->parentId;
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$fRec = &$data->form->rec;
    	$data->form->setField('parentId', 'remember');
    	self::expandRec($fRec);
    	
    	$undefinedDepId = $mvc->fetchField("#systemId = 'emptyCenter'", 'id');
    	
    	if(!$mvc->count("#id != {$undefinedDepId}") || (isset($fRec->id) && $fRec->id != $undefinedDepId && empty($fRec->parentId))){
    		$data->form->setField('parentId', 'input=none');
    	}

    	$data->form->setOptions('locationId', crm_Locations::getOwnLocations());
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("#orderStr");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->orderStr = '';
        
        if ($rec->staff) {
            $rec->orderStr = self::fetchField($rec->staff, 'orderStr');
        }
        $rec->orderStr .= str_pad(mb_substr($rec->name, 0, 10), 10, ' ', STR_PAD_RIGHT);
        
        if(!$rec->id) {
        	$rec->state = 'active';
        	
        }
    }
    
    
    /**
     * След промяна на обект от регистър
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	if($rec->activities == 'yes'){
    	    // Добавя се като перо 

    		$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", 'departments'), 'id'));
    		acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
        }
    }
    
    
 
    
    function act_Migrate()
    {
        $s = cls::get('hr_Setup');
        $s->setPositionName();
    }

    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->locationId){
    		$row->locationId = crm_Locations::getHyperlink($rec->locationId, TRUE);
    	}
    	
    	// Към неопределения център да не може да се добавя наследник
    	if($rec->systemId == 'emptyCenter'){
    		unset($row->_addBtn);
    	}
    	
    	$empTpl = new core_ET("");
    	$pQuery = crm_ext_Employees::getQuery();
    	$pQuery->like("departments", "|{$rec->id}|");
    	while($pRec = $pQuery->fetch()){
    		$codeLink = crm_ext_Employees::getCodeLink($pRec->personId);
    		$empTpl->append("{$codeLink}<br>");
    	}
    	
    	$row->employees = $empTpl;
    	
    	$aTpl = new core_ET("");
    	$aQuery = planning_AssetResources::getQuery();
    	$aQuery->like("departments", "|{$rec->id}|");
    	while($aRec = $aQuery->fetch()){
    		$fields = cls::get('planning_AssetResources')->selectFields();
    		$fields['-list'] = TRUE;
    		
    		$aRow = planning_AssetResources::recToVerbal($aRec, $fields);
    		$aTpl->append("{$aRow->code} ($aRow->quantity)<br>");
    	}
    	$row->assets = $aTpl;
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
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $rec->name . " dp",
                'num' => "Dep" . $rec->id,
                //'features' => 'foobar' // @todo!
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
     * Изчертаване на структурата с данни от базата
     */
    public static function getChart ($data)
    {
        $arrData = (array)$data->recs;
        
        foreach($arrData as $rec){
        	if($rec->systemId === 'emptyCenter' || $rec->state != 'active') continue;
        	
            // Ако имаме родител 
             if($rec->parentId == NULL && $rec->systemId !== 'myOrganisation') {
                 $parent = '0';
                 // взимаме чистото име на наследника
                 $name = self::fetchField($rec->id, 'name');
             } else {
                 // в противен случай, го взимаме както е
                 if ($rec->systemId == 'myOrganisation'){
                     $name = $rec->name;
                     $oldId = $rec->id;
                     $rec->id = '0';
                     $parent = 'NULL';
                 } elseif ($rec->parentId == $oldId) {
                 	$name = self::fetchField($rec->id, 'name');
                 	$parent = '0';
                 } else {
                     $name = self::fetchField($rec->id, 'name');
                     $parent = $rec->parentId;
                 }
             }
         
             $res[] = array(
             'id' => $rec->id,
             'title' => $name,
             'parent_id' => $parent,
             );
        }
        
        if(!static::fetchField("#systemId = 'myOrganisation'")){
        	$firstRow = array('id' => '0', 'title' => tr('Моята организация'), 'parent_id' => 'NULL');
        	if(count($res)){
        		array_unshift($res, $firstRow);
        	} else {
        		$res[] = $firstRow;
        	}
        }
        
        $chart = orgchart_Adapter::render_($res);
        
        return $chart;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	// Подготвяме пътя до файла с данните
    	$file = "hr/csv/Departments.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array(
    			0 => "name",
    			1 => "activities",
    			2 => "systemId",
    			3 => "type",
    	);
    	
    	// Импортираме данните от CSV файла.
    	// Ако той не е променян - няма да се импортират повторно
    	$cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
    	
    	// Записваме в лога вербалното представяне на резултата от импортирането
    	$res = $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща възможните опции за избор на бащи на обекта
     */
    protected static function on_AfterPrepareParentOptions($mvc, &$res, $rec)
    {
    	if(count($res)){
    		$undefinedDepId = $mvc->fetchField("#systemId = 'emptyCenter'", 'id');
    		unset($res[$undefinedDepId]);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec->parentId)){
    		if(!$mvc->haveRightFor('single', $rec->parentId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Добавя след таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $chartType = Request::get('Chart');
    
        if($chartType == 'Structure') {
    
            $tpl = static::getChart($data);
    
            $mvc->currentTab = "Структура->Графика";
        } else {
            $mvc->currentTab = "Структура->Таблица";
        }
    }
}
