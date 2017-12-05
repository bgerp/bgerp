<?php 



/**
 * Мениджър за департаменти
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Departments extends core_Master
{
    
    
	/**
	 * Ид на основния департамент
	 */
	const ROOT_DEPARTMENT_ID = 1;
	
	
    /**
     * Заглавие
     */
    public $title = "Департаменти";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Департамент";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, hr_Wrapper, plg_State, plg_Rejected,plg_Created,plg_SaveAndNew,plg_TreeObject, plg_Modified';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Структура->Таблица';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hrMaster';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hrMaster';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,hrMaster';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo,hrMaster';
    
    
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
    public $listFields = 'name=Департамент, locationId, state, createdOn,createdBy';

    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'public';
     
    
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
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('orderStr', 'varchar', "caption=Подредба,input=none,column=none");
        $this->FLD('state', 'enum(active=Активно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
       
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
    	$mvc->setField('makeDescendantsFeatures', 'input=none');
    			
    	self::expandRec($fRec);
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
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($rec->locationId)){
    		$row->locationId = crm_Locations::getHyperlink($rec->locationId, TRUE);
    	}
    	
    	if(isset($rec->parentId)){
    		$row->parentId = $mvc->getHyperlink($rec->parentId, TRUE);
    	}
    	
    	$row->STATE_CLASS = "state-{$rec->state}";
    }

    
    /**
     * Изчертаване на структурата с данни от базата
     */
    public static function getChart($data)
    {
        $arrData = (array)$data->recs;
        
        $first = $arrData[self::ROOT_DEPARTMENT_ID];
        unset($arrData[self::ROOT_DEPARTMENT_ID]);
        
        foreach($arrData as $rec){
        	if($rec->state != 'active') continue;
        	
            // Ако имаме родител 
             if($rec->parentId == NULL) {
                 $parent = '0';
                 // взимаме чистото име на наследника
                 $name = self::fetchField($rec->id, 'name');
             } else {
             	if ($rec->parentId == $oldId) {
                 	$name = self::fetchField($rec->id, 'name');
                 	$parent = '0';
                 } else {
                     $name = self::fetchField($rec->id, 'name');
                     $parent = $rec->parentId;
                 }
             }
         
             $res[] = array('id' => $rec->id, 'title' => $name, 'parent_id' => $parent);
        }
        
        $firstRow = array('id' => '1', 'title' => $first->name, 'parent_id' => 'NULL');
        if(count($res)){
        	array_unshift($res, $firstRow);
        } else {
        	$res[] = $firstRow;
        }
        
        $chart = orgchart_Adapter::render_($res);
        
        return $chart;
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
    	
    	// Основния департамент не може да бъде променян
    	if(($action == 'edit' || $action == 'restore' || $action == 'delete' || $action == 'reject') && isset($rec)){
    		if($rec->id == self::ROOT_DEPARTMENT_ID){
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
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	return $me->getVerbal($rec, 'name');
    }
    
    
    /**
     * Синхронизиране на името на първия департамент с това на 'Моята фирма'
     * 
     * @param string $myCompanyName - името на моята фирма
     * @return int
     */
    public static function forceFirstDepartment($myCompanyName)
    {
    	$rec = self::fetch(self::ROOT_DEPARTMENT_ID);
    	
    	if(empty($rec)){
    		$rec = new stdClass();
    		$rec->id = self::ROOT_DEPARTMENT_ID;
    		$rec->state = 'active';
    	}
    	
    	if($rec->name != $myCompanyName){
    		$rec->name = $myCompanyName;
    	
    		core_Users::forceSystemUser();
    		self::save($rec, NULL, 'REPLACE');
    		core_Users::cancelSystemUser();
    	}
    	
    	return $rec->id;
    }
}
