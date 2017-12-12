<?php 



/**
 * Мениджър за Центровете на дейност
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Centers extends core_Master
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_ActivityCenters';
	
	
	/**
	 * Ид на Неопределения център на дейност
	 */
	const UNDEFINED_ACTIVITY_CENTER_ID = 1;
	
	
     /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'planning_ActivityCenterIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Центрове на дейност";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Ц-р на дейност";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, planning_Wrapper, doc_FolderPlg, plg_State, plg_Rejected, plg_Created, acc_plg_Registry, doc_FolderPlg';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, planning';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, planningMaster';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo, planningMaster';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutActivityCenter.shtml';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/big_house.png';
    
    
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
    public $listFields = 'name=Център, departmentId, type, employmentOccupied=Назначени, employmentTotal=От общо, schedule=График, folderId,createdOn,createdBy';

    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'public';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'nkid,employmentTotal,employmentOccupied,startingOn';
    
    
    /**
     * Поле, в което да се постави връзка към папката в листови изглед
     */
    public $listFieldForFolderLink = 'folder';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
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
    	
    	$this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=Служители->НКИД, hint=Номер по НКИД');
    	$this->FLD('employmentTotal', 'int', "caption=Служители->Щат, input=none");
    	$this->FLD('employmentOccupied', 'int', "caption=Служители->Назначени, input=none");
    	$this->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name, allowEmpty=true)', "caption=Работен график->Цикъл,mandatory");
    	$this->FLD('startingOn', 'datetime', "caption=Работен график->От");
    	$this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', "caption=В състава на");
    	$this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=active,notNull,input=none');
		
    	$this->setDbUnique('name');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if(isset($rec->departmentId)){
    		$row->departmentId = hr_Departments::getHyperlink($rec->departmentId, TRUE);
    	}
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
    				'title' => $rec->name . " ac",
    				'num' => "Ac" . $rec->id,
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
     * След инсталирането на модела
     */
    function loadSetupData()
    {
    	if(!$this->fetchField(self::UNDEFINED_ACTIVITY_CENTER_ID, 'id')) {
    		$rec           = new stdClass();
    		$rec->id       = price_ListRules::PRICE_LIST_COST;
    		$rec->name     = 'Неопределен';
    		$rec->type     = 'workshop';
    		$rec->state    = 'active';
    
    		core_Users::forceSystemUser();
    		$this->save($rec, NULL, 'REPLACE');
    		core_Users::cancelSystemUser();
    	}
    }
    
    
    /**
     * Какви видове ресурси може да се добавят към модела
     * 
     * @param stdClass $rec
     * @return array   - празен масив ако няма позволени ресурси
     * 		['assets'] - оборудване
     * 		['hr']     - служители
     */
    public function getResourceTypeArray($rec)
    {
    	return arr::make('assets,hr', TRUE);
    }
}