<?php


/**
 * Мениджър на работа от вкъщи
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Заявка за работа от вкъщи
 */
class hr_HomeOffice extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Заявка за работа от вкъщи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заявка за работа от вкъщи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, doc_DocumentPlg,doc_plg_TransferDoc, acc_plg_DocumentSummary,plg_Sorting, 
    				 doc_ActivatePlg, plg_Printing,doc_SharablePlg,bgerp_plg_Blank,change_Plugin, hr_Wrapper, hr_EmailCreatePlg';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, hrMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, hrMaster';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, hrMaster';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го активира?
     */
    public $canActivate = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, hrMaster';
    
    
    /**
     * Кой има право да прави начисления
     */
    public $canChangerec = 'ceo, hrMaster';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'powerUser';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '5.4|Човешки ресурси';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, personId, startDate, toDate';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId,startDate, toDate,title';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'personId';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutHomeOffice.shtml';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startDate';
    public $filterFieldDateTo = 'toDate';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Hmo';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/house.png';
    
    
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
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител, mandatory');
        $this->FLD('startDate', 'datetime(defaultTime=00:00:00)', 'caption=Считано->От, mandatory');
        $this->FLD('toDate', 'datetime(defaultTime=23:59:59)', 'caption=Считано->До, mandatory');
        $this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
        $this->FLD('emoji', cls::get('type_Enum', array('options' => hr_Leaves::getEmojiesWithPrefix('h'))), 'caption=Информация->Икона за ника, maxRadio=10,columns=10,notNull,value=h2');
        $this->FLD('note', 'richtext(rows=5, bucket=Notes)', 'caption=Информация->Бележки');
        $this->FLD('answerGSM', 'enum(yes=Да, no=Не, partially=Частично)', 'caption=По време на работата от вкъщи->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
        $this->FLD('answerSystem', 'enum(yes=Да, no=Не, partially=Частично)', 'caption=По време на работата от вкъщи->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
        $this->FLD('alternatePersons', 'keylist(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=По време на работата от вкъщи->Заместник, oldFieldName=alternatePerson');
        $this->FNC('title', 'varchar', 'column=none');
        
        $this->FLD('sharedUsers', 'userList(roles=hrHomeOffice|ceo, showClosedUsers=no)', 'caption=Споделяне->Потребители');
    }
    
    
    /**
     * Изчисление на title
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = "Заявка за работа от вкъщи  №{$rec->id}";
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        $mvc->updateHomeOfficeToCalendar($rec->id);
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

        $employees = crm_Persons::getEmployeesOptions(false, null, false, 'active');
        unset($employees[$rec->personId]);
        $form->setSuggestions('alternatePersons', $employees);
        $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        if ($rec->folderId && $folderClass == 'crm_Persons') {
            $form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
            $form->setReadonly('personId');
            
            if (!haveRole('ceo,hrHomeOffice')) {
                $form->setField('sharedUsers', 'mandatory');
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (haveRole('ceo,hrHomeOffice,admin')) {
            $ignorable = true;
        } else {
            $ignorable = false;
        }
        
        $now = dt::now();
        
        // един месец назад
        $before30Days = dt::addMonths(-1, $now);
        $before30DaysVerbal = dt::mysql2verbal($before30Days, 'd.m.Y');
        
        // една година напред
        $after1year = dt::addMonths(12, $now);
        $after1yearVerbal = dt::mysql2verbal($after1year, 'd.m.Y');
        
        if ($form->isSubmitted()) {
            // Размяна, ако периодите са объркани
            if (isset($form->rec->startDate, $form->rec->toDate) && ($form->rec->startDate > $form->rec->toDate)) {
                $form->setError('startDate, toDate', 'Началната дата трябва да е по-малка от крайната');
            }
            
            if (isset($form->rec->startDate) && ($form->rec->startDate < $before30Days)) {
                $form->setError('startDate', "Началната дата трябва да е след {$before30DaysVerbal}г.", $ignorable);
            }
            
            if (isset($form->rec->startDate) && ($form->rec->startDate > $after1year)) {
                $form->setError('startDate', "Началната дата трябва да е преди {$after1yearVerbal}г.", $ignorable);
            }
            
            if (isset($form->rec->toDate) && ($form->rec->toDate > $after1year)) {
                $form->setError('toDate', "Крайната дата трябва да е преди {$after1yearVerbal}г.", $ignorable);
            }
            
            // Изисляване на брой дни хоум офис
            if ($form->rec->startDate && $form->rec->toDate) {
                $scheduleId = planning_Hr::getSchedule($form->rec->personId);
                $days = hr_Schedules::calcLeaveDaysBySchedule($scheduleId, $form->rec->startDate, $form->rec->toDate);
                $form->rec->leaveDays = $days->workDays;
            }
            
            // ако не са изчислени дните за отпуска или са по-малко от 1, даваме грешка
            if (!$form->rec->leaveDays || isset($form->rec->leaveDays) < 1) {
                $form->setError('leaveDays', 'Броят неприсъствени дни е 0');
            }
            
            // правим заявка към базата
            $query = self::getQuery();
            
            // търсим всички молби, които са за текущия потребител
            $query->where("#personId='{$form->rec->personId}'");
            
            if ($form->rec->id) {
                $query->where("#id != {$form->rec->id}");
            }
            
            // търсим времево засичане
            $query->where("(#startDate <= '{$form->rec->startDate}' AND #toDate >= '{$form->rec->startDate}')
            OR
            (#startDate <= '{$form->rec->toDate}' AND #toDate >= '{$form->rec->toDate}')");
            
            $query->where("#state = 'active'");
            
            // за всяка една молба отговаряща на условията проверяваме
            if ($recReq = $query->fetch()) {
                $link = ht::createLink("Заявка за работа от вкъщи №{$recReq->id}", array($mvc, 'single', $recReq->id, 'ret_url' => true, ''), null, 'ef_icon=img/16/house.png');
                
                // и изписваме предупреждение
                $form->setError('startDate, toDate', "|Засичане по време с |*{$link}");
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
      
        $row->alternatePersons = hr_Leaves::purifyeAlternatePersons($rec->alternatePersons);
        
        $DateTime = cls::get('core_DateTime');
        
        if (isset($rec->activatedOn)) {
            $row->activatedOn = dt::mysql2verbal($rec->activatedOn, 'd.m.Y');
        }
        
        if (isset($rec->activatedBy)) {
            $row->activatedBy = core_Users::getVerbal($rec->activatedBy, 'names');
            if (!Mode::isReadOnly()) {
                $row->activatedBy = crm_Profiles::createLink($rec->activatedBy, $row->activatedBy);
            }
        }
        
        
        if ($rec->startDate) {
            $tLeaveFrom = dt::mysql2timestamp($rec->startDate);
            $dayOfWeekFrom = date('l', $tLeaveFrom);

            $row->startDate = $DateTime->mysql2verbal($rec->startDate, 'd.m.Y');
            
        }
        
        if ($rec->toDate) {
            $tLeaveTo = dt::mysql2timestamp($rec->toDate);
            $dayOfWeekTo = date('l', $tLeaveTo);
            
            $row->toDate = $DateTime->mysql2verbal($rec->toDate, 'd.m.Y');

        }
        
        $myCompany = crm_Companies::fetchOurCompany();
        $row->myCompany = $myCompany->name;
        
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
        $homeDays = hr_Setup::get('DAYS_IN_HOMEOFFICE');
        $lDays = self::getHomeDayForMonth();
        
        
        if ($rec->id) {
            
            
            if ($action == 'order') {
                // и нямаме нужните права
                if (!Users::haveRole('ceo') || !Users::haveRole('hrHomeOffice')) {
                    // то не може да я направим
                    $requiredRoles = 'no_one';
                }
            }
            
            if($action == 'activate') {
               
                if($lDays >= $homeDays ){ 

                    $canActivate = $mvc->canActivate($rec); 
                    if ($canActivate == TRUE) {
                        // то не може да я направим
                        $requiredRoles = 'no_one';
                    }
                }   
            }  
        }

        if ($action == 'add' || $action == 'reject' || $action == 'decline') {
            if ($rec->folderId) {
                $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);
                
                if ($rec->folderId && $folderClass == 'crm_Persons') {
                    $personId = doc_Folders::fetchCoverId($rec->folderId);
                    $inCharge = crm_Profiles::fetchField("#personId = '{$personId}'", 'userId');
                    
                    //$inCharge = doc_Folders::fetchField($rec->folderId, 'inCharge');
                    
                    if ($inCharge != $userId) {
                        if (!Users::haveRole('ceo') && !Users::haveRole('hrHomeOffice')) {
                            // то не може да я направим
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
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
        if (doc_Threads::haveRightFor('single', $data->rec->threadId) == false) {
            
            // Премахваме бутона за коментар
            $data->toolbar->removeBtn('Коментар');
        }

        if ($mvc->haveRightFor('decline', $data->rec) && $data->rec->state != 'closed') {
            $data->toolbar->addBtn(
                'Отказ',
                array(
                    $mvc,
                    'Decline',
                    'id' => $data->rec->id,
                    'ret_url' => array('hr_HomeOffice', 'single', $data->rec->id)
                ),
                array('ef_icon' => 'img/16/cancel16.png',
                    'title' => 'Отказ на заявка за работа от вкъщи'
                )
                );
        }
        
        // Ако нямаме права за писане в треда
        if (doc_Threads::haveRightFor('single', $data->rec->threadId) && ($data->rec->state != 'draft' && $data->rec->state != 'pending')) {
            
            // Премахваме бутона за коментар
            $data->toolbar->removeBtn('activate');
            $data->toolbar->removeBtn('Отказ');
        }
    }

    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        //
        $rec = $mvc->fetchRec($rec);
        $subscribedArr = keylist::toArray($rec->sharedUsers);
        $subscribedArr[$rec->createdBy] = $rec->createdBy;
        
        if (isset($rec->alternatePersons)) {
            foreach (type_Keylist::toArray($rec->alternatePersons) as $aPerson) {
                $alternatePersonId = crm_Profiles::fetchField(array("#personId = '[#1#]'", $aPerson), 'userId');
                if ($alternatePersonId) {
                    $subscribedArr[$alternatePersonId] = $alternatePersonId;
                }
            }
        }
        
        if (countR($subscribedArr)) {
            foreach ($subscribedArr as $userId) {
                if ($userId > 0 && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                    $rec->message = '|Активирана е |* "' . self::getRecTitle($rec) . '"';
                    $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $rec->customUrl = array($mvc, 'single',  $rec->id);
                    $rec->priority = 0;
                    
                    bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
                }
            }
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $title = $mvc->getRecTitle($rec, false);
        $res .= ' ' . plg_Search::normalizeText($title);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (isset($data->rec->startDate, $data->rec->toDate)) {
            $leaveFrom = strstr($data->rec->startDate, ' ', true);
            $leaveTo = strstr($data->rec->toDate, ' ', true);
        }
        
        if (trim($leaveFrom) == trim($leaveTo)) {
            $tpl->removeBlock('startDate');
            $tpl->removeBlock('fromHour');
            $tpl->removeBlock('dayFrom');
            $tpl->removeBlock('to');
        } else {
            $tpl->removeBlock('on');
        }
        
        if ($data->rec->state == 'closed') {
            $row = new stdClass();
            $rowTpl = $tpl->getBlock('decline');
            
            if (isset($data->rec->modifiedOn)) {
                $row->modifiedOn = dt::mysql2verbal($data->rec->modifiedOn, 'd.m.Y');
            }
            
            if (isset($data->rec->modifiedBy)) {
                $row->modifiedBy = core_Users::getVerbal($data->rec->modifiedBy, 'names');
                if (!Mode::isReadOnly()) {
                    $row->modifiedBy = crm_Profiles::createLink($data->rec->modifiedBy, $row->modifiedBy);
                }
            }
            
            if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
                $row->modifiedOn = dt::mysql2verbal(dt::addDays(-1, $data->rec->startDate), 'd.m.Y');
            }
            
            $rowTpl->placeObject($row);
            $rowTpl->removeBlocks();
            $rowTpl->append2master();
            
            $tpl->removeBlock('activatedBy');
        } else {
            $tpl->removeBlock('decline');
            
        }
        
        $leaveFromTs = dt::mysql2timestamp($data->rec->startDate);
        $activatedOnTs = dt::mysql2timestamp($data->rec->activatedOn);
        $modifiedOnTs = dt::mysql2timestamp($data->rec->modifiedOn);
        $createdOnTs = dt::mysql2timestamp($data->rec->createdOn);
        
        // Ако ще разпечатваме или ще отворим сингъла от qr-код
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            // ако началната дата на отпуската е по-малка от дата на създаване на документа
            // или датата на одобрение е по-голяма от  начаната дата на отпуската
            // искаме датите на създаване и одобряване да са преди началната дата
            if($leaveFromTs <= $createdOnTs || $activatedOnTs >= $leaveFromTs ) {
                
                if($data->rec->state == 'active'){
                    
                    // заменяме датат на одобрено
                    $row = new stdClass();
                    $rowTpl = $tpl->getBlock('activatedBy');
                    $row->activatedOn = dt::mysql2verbal(dt::addDays(-1, $data->rec->startDate), 'd.m.Y');
                    
                    // кой е одобрил
                    if (isset($data->rec->activatedBy)) {
                        $row->activatedBy = core_Users::getVerbal($data->rec->activatedBy, 'names');
                        if (!Mode::isReadOnly()) {
                            $row->activatedBy = crm_Profiles::createLink($data->rec->activatedBy, $row->activatedBy);
                        }
                    }
                    
                    $rowTpl->placeObject($row);
                    $rowTpl->removeBlocks();
                    $rowTpl->append2master();
                }
                
                // заменяме датат на молбата
                $row1 = new stdClass();
                $rowTpl1 = $tpl->getBlock('createdDate');
                $row1->createdDate =  dt::mysql2verbal(dt::addDays(-2, $data->rec->startDate), 'd.m.Y');
                $rowTpl1->placeObject($row1);
                $rowTpl1->removeBlocks();
                $rowTpl1->append2master();
                
                // заменяме датат на документа
                $row2 = new stdClass();
                $rowTpl2 = $tpl->getBlock('createdDateFooter');
                $row2->createdDate = dt::mysql2verbal(dt::addDays(-2, $data->rec->startDate), 'd.m.Y');
                $rowTpl2->placeObject($row1);
                $rowTpl2->removeBlocks();
                $rowTpl2->append2master();
            }
        }
    }
    
    
    /**
     * Метод по подразбиране на canActivate
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
        if (!$res) {
            if (empty($rec->id)) {
                $res = false;
            } else {
                $cUser = core_Users::getCurrent();

                if (haveRole('ceo', $cUser) || haveRole('hrHomeOffice',$cUser)) {
                    // то не може да я направим
                    $res = false;
                } else { 
                    $res = true;
                }
            }
        } 
    }
    
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    public static function updateHomeOfficeToCalendar($id)
    {
        if($id){
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на ключовете за записите в календара от тази задача
        $prefix = "HMOFFICE-{$id}-";
        
        $curDate = $rec->startDate;

        $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
        if (!$personProfile || !$personProfile->userId) {

            return ;
        }

        while ($curDate < $rec->toDate) {
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
                $calRec->type = 'house';
                
                $personName = crm_Persons::fetchField($rec->personId, 'name');
                
                // Заглавие за записа в календара
                $calRec->title = "Работи от вкъщи: {$personName}";
                
                $personId = array($personProfile->userId => 0);
                $user = keylist::fromArray($personId);

                // В чии календари да влезе?
                $calRec->users = $user;

                // Статус на задачата
                $calRec->state = $rec->state;

                // Url на задачата
                $calRec->url = array('hr_HomeOffice', 'Single', $id);

                $events[] = $calRec;
            }
            $curDate = dt::addDays(1, $curDate);
        }

        $onlyDel = $rec->state == 'rejected' ? true : false;
        
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix, $onlyDel);
        }
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
        $row->title = "Заявка за работа от вкъщи  №{$rec->id}";
        
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
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка
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
            if (!haveRole('ceo,hrHomeOffice', $cu)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $me = cls::get(get_called_class());
        
        $title = tr('Заявка за работа от вкъщи  №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
        
        return $title;
    }
    
    
    /**
     * Връща броя дни използвани за хоум офис
     * @return number
     */
    public static function getHomeDayForMonth()
    {
        $map = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
        
        foreach ($map as $id => $month) {
            $year = date('Y');
           
            // Таймстамп на първия ден на текущия месеца
            $firstDayTms = mktime(0, 0, 0, $month, 1, $year);
            
            // Броя на дните в текущия месеца
            $lastDay = date('t', $firstDayTms);
            
            //календарна дата на първи и последен ден от избрания месец
            $fromDate = "$year-$month-01";
            $toDate = "$year-$month-$lastDay";
            
            // Предишния месец
            $pm = $month-1;
            if($pm == 0) {
                $pm = 12;
                $py = $year-1;
            } else {
                $py = $year;
            }
            
            $firstDayTmsPrevMonth = mktime(0, 0, 0, $pm, 1, $py);
            $lastDayPrevMonth = date('t', $firstDayTmsPrevMonth);
            //календарна дата на последния ден от предишния месец
            $prevMonth = "$py-$pm-$lastDayPrevMonth";
            
            // Следващият месец
            $nm = $month+1;
            if($nm == 13) {
                $nm = 1;
                $ny = $year+1;
            } else {
                $ny = $year;
            }
            //календарна датана първият ден на следващия месец
            $nextMonth = "$ny-$nm-01";
            
            $cUser = core_Users::getCurrent();
            
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = self::getQuery();
            
            // Искаме само активираните документи
            $data->query->where("#state = 'active' AND ((#startDate >= '{$fromDate}' AND #toDate <= '{$toDate}')
                                               OR (#startDate <= '{$prevMonth}') OR (#toDate >= '{$nextMonth}'))");
            // търсим всички заявки за хоум офис, които са за текущия потребител
            $data->query->where("#personId='{$cUser}'");
            
            $data->recs = $data->query->fetchAll();
            
            $lDay = 0;
            
            foreach($data->recs as $id=>$rec){ 

                    
                $lDay += $rec->leaveDays;

            }
            return $lDay;
          
        }     
    }
    
    
    
    /**
     * Метод за отказване на заявка за работа от вкъщи
     */
    public static function act_Decline()
    {
        //Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = hr_HomeOffice::fetch($id));
        
        // Очакваме да има права за записа
        hr_HomeOffice::requireRightFor('decline', $rec);
        
        //Очакваме потребителя да има права за спиране
        hr_HomeOffice::haveRightFor('decline', $rec);
        
        $link = array('hr_HomeOffice', 'single', $rec->id);
        
        //Променяме статуса на затворено
        $rec->brState = $rec->state;
        $rec->state = 'closed';
        hr_HomeOffice::save($rec);
        
        $subscribedArr = keylist::toArray($rec->sharedUsers);
        $subscribedArr[$rec->createdBy] = $rec->createdBy;
        
        if (isset($rec->alternatePersons)) {
            foreach (type_Keylist::toArray($rec->alternatePersons) as $aPerson) {
                $alternatePersonId = crm_Profiles::fetchField(array("#personId = '[#1#]'", $aPerson), 'userId');
                if ($alternatePersonId) {
                    $subscribedArr[$alternatePersonId] = $alternatePersonId;
                }
            }
        }
        
        if (countR($subscribedArr)) {
            foreach ($subscribedArr as $userId) {
                if ($userId > 0 && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                    $rec->message = '|Отказана е |* "' . self::getRecTitle($rec) . '"';
                    $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $rec->customUrl = array(get_called_class(), 'single',  $rec->id);
                    $rec->priority = 0;
                    
                    bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
                }
            }
        }
        
        // Редиректваме
        return new Redirect($link, '|Успешно отказахте заявка за работа от вкъщи');
    }
}
