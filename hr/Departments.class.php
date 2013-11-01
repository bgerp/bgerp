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
    var $singleTitle = "Звено";
    
    
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
    var $listFields = 'id, name, type, nkid, staff, locationId, employmentTotal, employmentOccupied, schedule';
    

    /**
     * Детайли на този мастер
     */
    var $details = 'Grafic=hr_WorkingCycles,Positions=hr_Positions';


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('type', 'enum(section=Поделение, 
                                 division=Девизия,
                                 direction=Дирекция,
                                 department=Oтдел,
                                 plant=Завод,
                                 workshop=Цех, 
                                 unit=Звено,
                                 brigade=Бригада,
                                 shift=Смяна)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=НКИД, hint=Номер по НКИД');
        $this->FLD('staff', 'key(mvc=hr_Departments, select=name, allowEmpty)', 'caption=В състава на,width=100%');

        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('employmentTotal', 'int', "caption=Служители->Щат, input=none");
        $this->FLD('employmentOccupied', 'int', "caption=Служители->Назначени, input=none");
        $this->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name, allowEmpty=true)', "caption=Работно време->График");
        $this->FLD('startingOn', 'datetime', "caption=Работно време->Начало");
        $this->FLD('orderStr', 'varchar', "caption=Подредба,input=none,column=none");
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('locationId', array('' => '&nbsp;') + crm_Locations::getOwnLocations());

        // Да не може да се слага в звена, които са в неговия състав
        if($id = $data->form->rec->id) {
            $notAllowedCond = "#id NOT IN (" . implode(',', self::getInheritors($id, 'staff')) . ")";


        }  
        
        $query = self::getQuery();
        $query->orderBy('#orderStr');
        while($r = $query->fetch($notAllowedCond)) {
                self::expandRec($r);
                $opt[$r->id] = $r->name;
        }

        $data->form->setOptions('staff', $opt);
    }


    /**
     * Връща наследниците на даден запис
     */
    static function getInheritors($id, $field, &$arr = array())
    {
        $arr[$id] = $id;
        $query = self::getQuery();
        while($rec = $query->fetch("#{$field} = $id")) {
 
            self::getInheritors($rec->id, $field, $arr);
        }

        return $arr;
    }


    /**
     * Добавя данните за записа, които зависят от неговите предшественици и от неговите детайли
     */
    static function expandRec($rec)
    {
        $parent = $rec->staff;

        while($parent && ($pRec = self::fetch($parent))) {
            $rec->name = $pRec->name . ' » ' . $rec->name;
            setIfNot($rec->nkid, $pRec->nkid);
            setIfNot($rec->locationId, $pRec->locationId);
            $parent = $pRec->staff;
        }
    }


    /**
     * Определя заглавието на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        self::expandRec($rec);
        
        return parent::getRecTitle($rec, $escaped);
    }


    /**
     * Изпънява се преди превръщането във вербални стойности на записа
     */
    function on_BeforeRecToVerbal($mvc, &$row, &$rec)
    {
        self::expandRec($rec);
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
                
                $expandedDepartment = self::expandRec($form->rec->dependent);
                
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
        $data->query->orderBy("#orderStr");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->orderStr = '';
        if($rec->staff) {
            $rec->orderStr = self::fetchField($rec->staff, 'orderStr');
        }
        $rec->orderStr .= str_pad(mb_substr($rec->name, 0, 10), 10, ' ', STR_PAD_RIGHT);
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
	        	
	    		$haveContracts = hr_EmployeeContracts::fetch("#departmentId = '{$rec->id}'");
	    		
                $haveSubDeparments = self::fetch("#staff = '{$rec->id}'");

	    		if($haveContracts || $haveSubDeparments){
	    			$requiredRoles = 'no_one';
	    		}
    	     }
         }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterRenderListTitle($mvc, &$title, $data)
    {
    	$title = new ET('[#1#]', $title);
            
        $title->append('<div style="margin-top:5px;margin-bottom:5px;font-size:0.80em;font-family:arial;" id="chartMenu">', 'ListSummary');
        
        $chartType = Request::get('Chart');
        
        if($chartType) {
        	$url = getCurrentUrl();
        	unset($url['Chart']);
        }
        
        $urlChart = toUrl(array('hr_Departments', 'Display'));
       
        $title->append(ht::createLink(tr('Tаблица'), $url) , 'ListSummary');
        
        $title->append("&nbsp;|&nbsp;", 'ListSummary');
        
        $title->append(ht::createLink(tr('Графика'), $urlChart) , 'ListSummary');
      
        $title->append('</div>', 'ListSummary');
    }

    
    function act_Display ()
    {

    	//self::requireRightFor('day');
    	    		    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	//$data->action = 'day';
    	
    	while($rec=$data->query->fetch()){
    		$res[]=array(
    				'id' => $rec->id,
    				'title' => $rec->name,
    				'parent_id' => $rec->staff === NULL ? "NULL" : $rec->staff,
    		);
    	}
    	           
		return $this->renderWrapping(orgchart_Adapter::render_($res));

    }
}