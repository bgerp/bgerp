<?php


/**
 * Мениджър на болнични
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Болнични листи
 */
class hr_Sickdays extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Болнични листи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Болничен лист';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, doc_DocumentPlg,acc_plg_DocumentSummary,doc_plg_TransferDoc, plg_Sorting, 
    				 doc_ActivatePlg, plg_Printing,doc_SharablePlg,bgerp_plg_Blank,change_Plugin, hr_Wrapper, hr_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, fitNoteNum, fitNoteDate, fitNoteFile, startDate, toDate, reason, note, icdCode';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId,startDate, toDate,fitNoteNum,fitNoteDate,reason,note, icdCode, title';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //public $searchFields = 'description';
	
	
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'startDate,toDate, fitKind,fitType,fitNoteFile,fitNoteNum,fitNoteDate,fitNoteFile,reason,icdCode,
                               treatment,paidByEmployer,paidByHI,note';

    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startDate';
    public $filterFieldDateTo = 'toDate';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'personId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, hrMaster, hrSickdays';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, hrMaster, hrSickdays';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, hrMaster, hrSickdays';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo, hrMaster, hrSickdays';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, hrMaster, hrSickdays';
    
    
    /**
     * Кой има право да променя активиран болничен
     */
    public $canChangerec = 'ceo, hrMaster, hrSickdays';
    
    
    public $canEdited = 'powerUser';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'powerUser';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutSickdays.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Skd';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '5.4|Човешки ресурси';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/sick.png';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * По кое поле ще се премества документа
     */
    public $transferFolderField = 'personId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, startDate,toDate,modifiedOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,mandatory');
        $this->FLD('startDate', 'date', 'caption=Отсъствие->От, mandatory');
        $this->FLD('toDate', 'date', 'caption=Отсъствие->До, mandatory');
        $this->FLD('fitNoteNum', 'varchar', 'caption=Болничен лист->Номер, hint=Номер/Серия/Година, changable');
        $this->FLD('fitKind', 'enum(issuing=Издаване, cancelled=Анулиране)', 'caption=Болничен лист->Тип, maxRadio=2,columns=2,notNull,value=issuing');
        $this->FLD('fitType', 'enum(first=Първичен, continued=Продължение)', 'caption=Болничен лист->Вид, maxRadio=2,columns=2,notNull,value=first');
        $this->FLD('fitNoteDate', 'date', 'caption=Болничен лист->Издаден на,after=icdCode, changable');
        $this->FLD('fitNoteFile', 'fileman_FileType(bucket=humanResources)', 'caption=Болничен лист->Файл,after=icdCode');
        $this->FLD('reason', 'enum(1=общо заболяване,
                                    2=професионална болест,
                                    4=злополука – трудова по чл. 55 ал.1 от КСО,
                                    5=злополука – трудова по чл. 55 ал.2 от КСО,
                                    6=злополука – нетрудова,
                                    7=изследване поради общо заболяване,
                                    8=изследване поради трудова злополука чл. 55 ал.1 от КСО,
                                    9=изследване поради трудова злополука чл. 55 ал.2 от КСО,
                                    10=изследване поради професионална болест,
                                    11=бацило/ паразито носителство,
                                    12=карантина,
                                    13=аборт,
                                    14=бременност,
                                    15=майчинство,
                                    16=трудоустрояване – общо заболяване,
                                    17=трудоустрояване – трудова злополука чл. 55 ал.1 от КСО,
                                    18=трудоустрояване – трудова злополука чл. 55 ал.2 от КСО,
                                    19=трудоустрояване – професионална болест,
                                    20=трудоустрояване – бременност,
                                    21=санаторно-курортно лечение поради общо заболяване,
                                    22=санаторно-курортно лечение поради трудова злополука чл. 55 ал.1 от КСО,
                                    23=санаторно-курортно лечение поради трудова злополука чл. 55 ал.2 от КСО,
                                    24=санаторно-курортно лечение поради професионална болест,
                                    25=придружаване на дете до 3-годишна възраст в болнично заведение,
                                    26=придружаване и гледане на дете до 18-годишна възраст,
                                    27=придружаване и гледане на болен над 18-годишна възраст)', 'caption=Болничен лист->Причина');
        $this->FLD('treatment', 'enum(,1=болничен,
                                        2=санаторно-курортен,
                                        3=домашен-стаен,
                                        4=домашен-амбулаторен,
                                        5=Домашен-на легло(постоянно или за определени часове от деня),
                                        6=Свободен-без право да напуска населеното място,
                                        7=Свободен-с право да напуска населеното място в границите на РБ)', 'caption=Болничен лист->Режим на лечение,placeholder=Изберете');
        $this->FLD('emoji', cls::get('type_Enum', array('options' => hr_Leaves::getEmojiesWithPrefix('s'))), 'caption=Информация->Икона за ника, maxRadio=4,columns=4,notNull,value=s2');
        $this->FLD('note', 'richtext(rows=5,bucket=Notes)', 'caption=Информация->Бележки');
        $this->FLD('icdCode', 'key2(mvc=bglocal_MKB,select=title)', 'caption=Болничен лист->MKБ код, hint=Международна класификация на болестите,placeholder=Изберете');
        $this->FLD('answerGSM', 'enum(yes=Да, no=Не, partially=Частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
        $this->FLD('answerSystem', 'enum(yes=Да, no=Не, partially=Частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
        $this->FLD('alternatePersons', 'keylist(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=По време на отсъствието->Заместник, oldFieldName=alternatePerson');
        $this->FLD('paidByEmployer', 'double(Min=0)', 'caption=Заплащане->Работодател, input=hidden, changable');
        $this->FLD('paidByHI', 'double(Min=0)', 'caption=Заплащане->НЗК, input=hidden,changable');
        $this->FNC('title', 'varchar', 'column=none');
        
        $this->FLD('sharedUsers', 'userList(roles=hrSickdays|ceo, showClosedUsers=no)', 'caption=Споделяне->Потребители');
    }
    
    
    /**
     * Изчисление на title
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = "Болничен лист №{$rec->id}";
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FLD('employeeId', 'key(mvc=crm_Persons,select=name,allowEmpty,group=employees)', 'caption=Служител,silent,before=selectPeriod');
        $data->listFilter->showFields = $data->listFilter->showFields . ',employeeId';
        $data->listFilter->input('employeeId', 'silent');
        
        if ($filterRec = $data->listFilter->rec) {
            if ($filterRec->employeeId) {
                $data->query->where(array("#personId = '[#1#]'", $filterRec->employeeId));
            }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        // Намират се всички служители
        $employees = crm_Persons::getEmployeesOptions();
        unset($employees[$rec->personId]);
        
        if (countR($employees)) {
            $form->setOptions('personId', $employees);
            $form->setSuggestions('alternatePersons', $employees);
        } else {
            redirect(array('crm_Persons', 'list'), false, '|Липсва избор за служители|*');
        }
        
        $form->setDefault('reason', 3);
        $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        if ($rec->folderId && $folderClass == 'crm_Persons') {
            $form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
            $form->setReadonly('personId');
            
            if (!haveRole('ceo,hrSickdays')) {
                $data->form->fields['sharedUsers']->mandatory = 'mandatory';
            }
        }
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $now = dt::now(false);
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
        //  if ($form->rec->startDate > $now) {
        //       
                // Добавяме съобщение за грешка  (закоментирано - за да може да се въвеждат предварително планувани болнични)
        //      $form->setError('startDate', "Началната дата трябва да е преди|* <b>{$now}</b>");
        //  }
            
            if ($form->rec->toDate < $form->rec->startDate) {
                $form->setError('toDate', "Крайната дата трябва да е след|*  <b>{$form->rec->startDate}</b>");
            }
            
            // Размяна, ако периодите са объркани
            if (isset($form->rec->startDate, $form->rec->toDate) && ($form->rec->startDate > $form->rec->toDate)) {
                $form->setError('startDate, toDate', 'Началната дата трябва да е по-малка от крайната');
            }

            $iArr = hr_Leaves::getIntersections($form->rec->personId, $form->rec->startDate, $form->rec->toDate, $form->rec->id, get_called_class());
            // за всяка една молба отговаряща на условията проверяваме
            if (!empty($iArr)) {
                // и изписваме предупреждение
                $form->setError('startDate, toDate', "|Засичане по време с: |*" . implode('<br>', $iArr));
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec->id) {
            if ($action == 'reject' && $rec && $rec->state == 'active' && $rec->startDate <= dt::now()) {
                if (!haveRole('hrSickdays, ceo')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        $mvc->updateSickdaysToCalendar($rec->id);
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     *
     * @param blast_Emails $mvc
     * @param object       $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
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
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        
        $row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->from);
        
        $row->paidByEmployer = $Double->toVerbal($rec->paidByEmployer);
        $row->paidByEmployer .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
        $row->paidByHI = $Double->toVerbal($rec->paidByHI);
        $row->paidByHI .= " <span class='cCode'>{$row->baseCurrencyId}</span>";

        $row->alternatePersons = hr_Leaves::purifyeAlternatePersons($rec->alternatePersons);
    }

    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingleLayout($mvc, $tpl, $data)
    {
        if (!isset($data->rec->paidByEmployer)) {
            $tpl->removeBlock('compensationEmployer');
        }
        
        if (!isset($data->rec->paidByHI)) {
            $tpl->removeBlock('compensationHI');
        }
    }
    
    
    /**
     * Обновява информацията за болничните в календара
     */
    public static function updateSickdaysToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на ключовете за записите в календара от тази задача
        $prefix = "SICK-{$id}-";
        
        $curDate = $rec->startDate;

        $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
        if (!$personProfile || !$personProfile->userId) {

            return ;
        }
  
        while ($curDate <= $rec->toDate) {
            // Подготвяме запис за началната дата
            if ($curDate && $curDate >= $fromDate && $curDate <= $toDate && ($rec->state == 'active' || $rec->state == 'rejected')) {
                $calRec = new stdClass();
                
                // Ключ на събитието
                $calRec->key = $prefix . "-{$curDate}";
                
                // Начало на отпуската
                $calRec->time = $curDate;
                
                // Дали е цял ден?
                $calRec->allDay = 'yes';
                
                // Икона на записа
                $calRec->type = 'sick';
                
                $personName = crm_Persons::fetchField($rec->personId, 'name');
                
                // Заглавие за записа в календара
                $calRec->title = "Болничен: {$personName}";
                
                $personId = array($personProfile->userId => 0);
                $user = keylist::fromArray($personId);
                
                // В чии календари да влезе?
                $calRec->users = $user;
                
                // Статус на задачата
                $calRec->state = $rec->state;
                
                // Url на задачата
                $calRec->url = array('hr_Sickdays', 'Single', $id);
                
                $events[] = $calRec;
            }
            $curDate = strstr(dt::addDays(1, $curDate),' ',true);
        }

        $onlyDel = $rec->state == 'rejected' ? true : false;
       
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix, $onlyDel);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената папка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        // Трябва да е в папка на лице или на проект
        if ($Cover->className != 'crm_Persons' && $Cover->className != 'doc_UnsortedFolders') {
            
            return false;
        }
        
        // Ако е в папка на лице, лицето трябва да е в група служители
        if ($Cover->className == 'crm_Persons') {
            $emplGroupId = crm_Groups::getIdFromSysId('employees');
            $personGroups = $Cover->fetchField('groupList');
            if (!keylist::isIn($emplGroupId, $personGroups)) {
                
                return false;
            }
        }
        
        if ($Cover->className == 'doc_UnsortedFolders') {
            $cu = core_Users::getCurrent();
            if (!haveRole('ceo,hrSickdays', $cu)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     *
     * @return stdClass $row
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Болничен лист №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $this->getRecTitle($rec, false);
        
        return $row;
    }

    /**
     * Ф-я, която връща дали лицето на дадената дата е в болничен
     *
     * @param string $date      Дата за проверка (формат YYYY-MM-DD или YYYY-MM-DD HH:MM:SS)
     * @param int    $personId  Ид на лице
     * @return bool             true / false
     */
    public static function getSickDay($date, $personId)
    {
        if (!$date || !$personId) {
            return false;
        }

        // Оставяме само датната част, ако е подаден и час
        if (strpos($date, ' ') !== false) {
            $date = strstr($date, ' ', true);
        }

        $q = self::getQuery();
        $q->where(array("#personId = '[#1#]'", $personId));
        $q->where("#state = 'active'");
        // Включително краищата: leaveFrom <= $date <= leaveTo
        $q->where(array("#startDate <= '[#1#]' AND #toDate >= '[#1#]'", $date));
        $q->limit(1);

        return (bool)$q->fetch();
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $me = cls::get(get_called_class());
        
        $title = tr('Болничен лист №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
        
        return $title;
    }
}
