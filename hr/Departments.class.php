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
    public $loadList = 'plg_RowTools, hr_Wrapper, doc_FolderPlg, plg_Printing,
                     plg_Created, WorkingCycles=hr_WorkingCycles,acc_plg_Registry';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, type, nkid, staff, locationId, employmentTotal, employmentOccupied, schedule';
    
    
    /**
     * Детайли на този мастер
     */
    public $details = 'Grafic=hr_WorkingCycles,Positions=hr_Positions';
    
    // Подготвяме видовете графики 
    static $chartTypes = array(
        'List' => 'Tаблица',
        'StructureChart' => 'Графика',
    );
    
    
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
                                 unit=Звено,
                                 brigade=Бригада,
                                 shift=Смяна,
                                 organization=Учреждение)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=НКИД, hint=Номер по НКИД');
        $this->FLD('staff', 'key(mvc=hr_Departments, select=name)', 'caption=В състава на,width=100%');
        
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
    public static function on_AfterPrepareEditForm($mvc, $data)
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
        $data->form->setDefault('staff', 'organization');
    }
    
    
    /**
     * Връща наследниците на даден запис
     */
    public static function getInheritors($id, $field, &$arr = array())
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
    public static function expandRec($rec)
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
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        self::expandRec($rec);
        
        return parent::getRecTitle($rec, $escaped);
    }
    
    
    /**
     * Изпънява се преди превръщането във вербални стойности на записа
     */
    public function on_BeforeRecToVerbal($mvc, &$row, &$rec)
    {
        self::expandRec($rec);
    }
    
    
    /**
     * Проверка за зацикляне след субмитване на формата. Разпъване на всички наследени роли
     */
    public static function on_AfterInputEditForm($mvc, $form)
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
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("#orderStr");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->orderStr = '';
        
        if($rec->staff) {
            $rec->orderStr = self::fetchField($rec->staff, 'orderStr');
        }
        $rec->orderStr .= str_pad(mb_substr($rec->name, 0, 10), 10, ' ', STR_PAD_RIGHT);
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data) {
        // Ако искаме да видим графиката на структурата
        // не ни е необходимо страницирване
        if(Request::get('Chart')  == 'Structure') {
            // Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = 1000000;
        }
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
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public function on_AfterRenderListTable($mvc, &$tpl, $data)
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
     * Създава на корен към графа за структурата. Той е "Моята фирма"
     */
    public function on_AfterSetUpMvc($mvc, &$res)
    {
        $myCompany = crm_Companies::fetchOwnCompany();
            
        if(!self::count()) {
            
            // Създаваме го
            $rec = new stdClass();
            $rec->name = $myCompany->company;
            $rec->type = 'organization';
            $rec->staff = NULL;
            
            self::save($rec);
        } else {
            $query = self::getQuery();
            $myCompanyName = trim($myCompany->company);
            $query->where("#name = '{$myCompanyName}' AND #type = 'organization'");
             
            if ($query->fetch() == FALSE) { bp();
                $rec = new stdClass();
                $rec->name = $myCompany->company;
                $rec->type = 'organization';
                $rec->staff = NULL;
                
                self::save($rec); 
            }
        }
    }
    
    
    /**
     * Изчертаване на структурата с данни от базата
     */
    public static function getChart ($data)
    {
        $myCompany = crm_Companies::fetchOwnCompany();
        
        foreach((array)$data->recs as $rec){
            // Ако имаме родител 
            if ($parent = $rec->staff) {
                if ($rec->name == $myCompany && $rec->staff == NULL) {
                    $parent = $rec->id;
                }
                // взимаме чистото име на наследника
                $name = self::fetchField($rec->id, 'name');
            } else {
                // в противен случай, го взимаме
                // както е
                $name = $rec->name;
            }
            
            $res[] = array(
                'id' => $rec->id,
                'title' => $name,
                'parent_id' => $rec->staff === NULL ? "NULL": $parent,
            );
        }
        
        $chart = orgchart_Adapter::render_($res);
        
        return $chart;
    }
}
