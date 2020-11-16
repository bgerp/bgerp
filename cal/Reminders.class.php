<?php


/**
 * Клас 'cal_Reminders' - Документ - напомняне
 *
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_Reminders extends core_Master
{
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = false;
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = ' cal_Wrapper, plg_Clone,doc_DocumentPlg, plg_RowTools2, plg_Printing, doc_ActivatePlg, doc_SharablePlg, 
    				  bgerp_plg_Blank, plg_Sorting, plg_State, change_Plugin,doc_plg_Close,doc_plg_SelectFolder,plg_Search';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    public $groupByDateField = 'calcTimeStart';
    
    
    /**
     * Какви детайли има този мастер
     */
    public $details = 'cal_ReminderSnoozes';
    
    
    /**
     * Заглавие
     */
    public $title = 'Напомняния';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Напомняне';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, timeStart, timePreviously, repetition=Повторение, action, nextStartTime, sharedUsers';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'description,title';
    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'powerUser';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Кой има право да приключва?
     */
    public $canChangeTaskState = 'powerUser';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    public $canClose = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSnooz = 'powerUser';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/alarm_clock.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cal/tpl/SingleLayoutReminders.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Rem';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '1.4|Общи';
    
    
    public static $suggestions = array('', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Масив с id на напомнянията, които отварят нишки в този хит
     */
    public static $opened = array();
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cal_ReminderSnoozes';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'timePreviously,repetitionEach,repetitionType,timeStart,calcTimeStart,nextStartTime';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = '*';
    
    //24x60x60
    public static $map = array('days' => 86400,
        
        //7x24x60x60
        'weeks' => 604800,
        
        //30x24x60x60
        'months' => 2592000,
        
        //30x24x60x60
        'weekDay' => 2592000,
        
        //30x24x60x60
        'monthDay' => 2592000);
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory,width=100%, changable,silent');
        
        $this->FLD('description', 'richtext(bucket=calReminders)', 'caption=Описание,changable,silent');
        
        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители,changable,silent');
        
        // Приоритет
        $this->FLD(
            'priority',
            'enum(normal=Нормален,
                                     low=Нисък,
                                     high=Спешен,
                                     critical=Критичен)',
            'caption=Споделяне->Приоритет,maxRadio=4,columns=4,notNull,value=normal,autohide,changable'
        );
        
        // Какво ще е действието на известието?
        $this->FLD('action', 'enum(threadOpen=Отваряне на нишката,
        						   notify=Нотификация,
                                   notifyNoAns = Нотификация-ако няма отговор,
        						   replicateDraft=Чернова-копие на темата,
        						   replicate=Копие на темата)', 'caption=Действие при сработване->Избор, maxRadio=5,autohide,value=notify,notNull,changable,silent');
        
        // Начало на напомнянето
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime)', 'caption=Време->Начало, silent,changable');
        $this->FLD('calcTimeStart', 'datetime(format=smartTime)', 'caption=Време->Начало, input=none');
        
        // Предварително напомняне
        $this->FLD('timePreviously', 'time', 'caption=Време->Предварително,changable');
        
        // Колко пъти ще се повтаря напомнянето?
        $this->FLD('repetitionEach', 'int(Min=0)', 'caption=Повторение->Всеки,changable,autohide');
        
        // По какво ще се повтаря напомненето - дни, седмици, месеци, години
        $this->FLD(
            'repetitionType',
            'enum(   days=дена,
			                                  weeks=седмици,
			                                  months=месеца,
			                                  weekDay=месеца-ден от началото на седмицата,
			                                  monthDay=месеца-ден от началото на месеца)',
           'caption=Повторение->Мярка, maxRadio=5,columns=1,notNull,value=days,changable,autohide=any'
        );
        
        // За кой път се среща деня
        $this->FLD('monthsWeek', 'varchar(12)', 'caption=Срещане,notNull,input=none');
        
        // Кой ден от седмицата е
        $this->FLD('weekDayNames', 'varchar(12)', 'caption=Име на деня,notNull,input=none');
        
        // Кога е следващото стартирване на напомнянето?
        $this->FLD('nextStartTime', 'datetime(format=smartTime)', 'caption=Следващо напомняне,input=none');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $Cover = doc_Folders::getCover($data->form->rec->folderId);
        
        // Трябва да е в папка на лице или на фирма
        if (!($Cover->className == 'crm_Persons' && $Cover->className == 'crm_Companies')) {
            unset($mvc->getFieldType('repetitionType')->options['notifyNoAns']);
        }
        
        $arr = array('' => '') + static::$suggestions;
        unset($arr[0]);
        
        $data->form->setSuggestions('repetitionEach', $arr);
        
        if ($data->form->rec->threadId) {
            //Добавяме в полето Заглавие отговор на съобщението
            $title = doc_Threads::getThreadTitle($data->form->rec->threadId, false);
            $for = tr('|За|*: ');
            $title = $for . $title;
        }
        
        if (!$data->form->rec->id) {
            $cu = core_Users::getCurrent();
            $nextWorkDay = cal_Calendar::nextWorkingDay();
            
            $time = strstr($nextWorkDay, ' ', true). ' 08:00';
            
            $data->form->setDefault('timeStart', $time);
            $data->form->setDefault('title', $title);
            $data->form->setDefault('priority', 'normal');
            $data->form->setDefault('sharedUsers', '|'.$cu.'|');
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            $data->form->fields['priority']->maxRadio = 2;
        }
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    public function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $sharedUsersArr = type_UserList::toArray($form->rec->sharedUsers);
            
            if (empty($sharedUsersArr)) {
                $form->setError('sharedUsers', 'Трябва да има поне един споделен');
            }
            
            $now = dt::now();
            
            if (isset($form->rec->timeStart)) {
                if ($form->rec->timeStart < $now) {
                    // Добавяме съобщение за грешка
                    $form->setWarning('timeStart', 'Датата за напомняне трябва да е след|* ' . dt::mysql2verbal($now));
                }
                
                if (isset($form->rec->repetitionEach, $form->rec->repetitionType)) {
                    if (isset($form->rec->timePreviously)) {
                        $secRepetitionType = static::$map[$form->rec->repetitionType];
                        $repetitionSec = $form->rec->repetitionEach * $secRepetitionType;
                        
                        if ($form->rec->timePreviously >= $repetitionSec) {
                            // Добавяме съобщение за грешка
                            $form->setError('timePreviously', 'Не може да се направи напомняне с предварително време по-голямо от повторението|* ');
                        }
                    }
                }
            } else {
                if (!$form->rec->id) {
                    $form->rec->timeStart = $now;
                }
            }
            
            
            if ($form->rec->id) {
                $exState = self::fetchField($form->rec->id, 'state');
                
                if ($form->rec->timeStart < $now && ($form->rec->state != $exState && $form->rec->state != 'rejected')) {
                    // Добавяме съобщение за грешка
                    $form->setError('timeStart', 'Не може да се направи напомняне в миналото|* '. dt::mysql2verbal($now, 'smartTime'));
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        $now = dt::now();
        
        $cRec = clone $rec;
        
        $rec->nextStartTime = $mvc->getNextStartingTime2($rec);
        
        $rec->calcTimeStart = $mvc->getNextStartingTime2($cRec, false);
        if (!$rec->calcTimeStart) {
            $rec->calcTimeStart = $rec->timeStart;
        }
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#state=ASC, #calcTimeStart=DESC');
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
        $cu = core_Users::getCurrent();
        
        // Добавяме поле във формата за търсене
        
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,autoFilter');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers';
        
        $data->listFilter->input('selectedUsers', 'silent');
        
        if (!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = keylist::addKey($data->listFilter->rec->selectedUsers, $cu);
        }
        
        if ($data->listFilter->rec->selectedUsers) {
            if ($data->listFilter->rec->selectedUsers != 'all_users') {
                $data->query->likeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
            }
        }
    }
    
    
    /**
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('snooz', $data->rec)) {
            $data->toolbar->addBtn(
                 'Отлагане',
                 array(
                     'cal_ReminderSnoozes',
                     'add',
                     'remId' => $data->rec->id,
                     'ret_url' => array('cal_Reminders', 'single', $data->rec->id)
                 ),
                 array('ef_icon' => 'img/16/snooz.png',
                     'title' => 'Отлагане на напомнянето'
                 )
             );
        }
    }
    
    
    public static function on_AfterInputChanges($mvc, &$oldRec, $newRec)
    {
        // Ако не е обект, а е подаден id
        if (!is_object($newRec)) {
            
            // Опитваме се да извлечем данните
            $rec = cal_Reminders::fetch($newRec);
        }
        
        // Очакваме да има такъв запис
        expect($newRec, 'Няма такъв запис');
        
        if ($newRec->state === 'closed') {
            $newRec->state = 'active';
        }
        
        if ($newRec->notifySent === 'yes') {
            $newRec->notifySent = 'no';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        $now = dt::now();
        
        if ($rec->id) {
            $oRec = $mvc->fetch($rec->id);
            
            if ($action == 'stop') {
                if (doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) {
                    if ($rec->state !== 'active') {
                        $requiredRoles = 'no_one';
                    }
                }
            }
            
            $last7days = dt::timestamp2Mysql(dt::mysql2timestamp(dt::now()) - 7 * 24 * 60 * 60);
            if ($action == 'snooz') {
                if (!doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) {
                    $requiredRoles = 'no_one';
                }
                
                if ($rec->notifySent !== 'yes' && !($rec->nextStartTime >= $last7days && $rec->nextStartTime <= dt::now())) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     *
     * @param core_Mvc $mvc
     * @param bool     $res
     * @param string   $state
     */
    public function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        // Чернова документи не могат да се променят
        if ($res !== false && $rec->state != 'draft') {
            $res = true;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if ($data->recs) {
            foreach ((array) $data->recs as $id => $rec) {
                $row = $mvc->recToVerbal($rec);
                
                if ($rec->repetitionEach != null) {
                    if ($rec->repetitionEach == '1') {
                        switch ($rec->repetitionType) {
                            case 'days':
                                $row->repetitionType = 'ден';
                            break;
                            
                            case 'weeks':
                                $row->repetitionType = 'седмица';
                            break;
                            
                            case 'months':
                                $row->repetitionType = 'месец';
                            break;
                            
                            case 'weekDay':
                                $row->repetitionType = 'месец';
                            break;
                            
                            case 'monthDay':
                                $row->repetitionType = 'месец';
                            break;
                        }
                    }
                    
                    $data->rows[$id]->repetition = $row->repetitionEach . ' ' . $row->repetitionType;
                } else {
                    $data->rows[$id]->repetition = ' ';
                }
            }
        }
    }
    
    
    /**
     * Обновява информацията за напомнянията
     * за текущата и следващите три години
     */
    public static function updateRemindersToCalendar($rec, $fromDate, $toDate, $prefix, &$events)
    {
        // Подготвяме запис за началната дата
        if ($rec->calcTimeStart && ($rec->calcTimeStart >= $fromDate) && ($rec->calcTimeStart <= $toDate) && ($rec->state == 'active')) {
            $calRec = new stdClass();
            
            // Ключ на събитието
            $calRec->key = $prefix . '-' . $rec->id . '-Start';
            
            // TODO да се проверява за високосна година
            $calRec->time = $rec->calcTimeStart;
            
            $calRec->type = 'alarm_clock';
            
            $calRec->allDay = 'no';
            
            $calRec->state = $rec->state;
            
            $calRec->title = $rec->title;
            
            $calRec->users = $rec->sharedUsers;
            
            $calRec->url = array('cal_Reminders', 'Single', $rec->id);
            
            $calRec->priority = 90;
            
            $events[] = $calRec;
        } elseif ($rec->nextStartTime && ($rec->nextStartTime >= $fromDate) && ($rec->nextStartTime <= $toDate) && ($rec->state == 'active')) {
            $remRec = new stdClass();
            
            // Ключ на събитието
            $remRec->key = $prefix . '-' . $rec->id . '-NextStart';
            
            $remRec->time = $rec->nextStartTime;
            
            $remRec->type = 'alarm_clock';
            
            $remRec->allDay = 'no';
            
            $remRec->state = $rec->state;
            
            $remRec->title = $rec->title;
            
            $remRec->users = $rec->sharedUsers;
            
            $remRec->url = array('cal_Reminders', 'Single', $rec->id);
            
            $remRec->priority = 90;
            
            $events[] = $remRec;
        }
        
        return $events;
    }
    
    
    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    public static function getNumbPriority($rec)
    {
        if ($rec->state == 'active') {
            switch ($rec->priority) {
                case 'low':
                    $res = 100;
                    break;
                case 'normal':
                    $res = 200;
                    break;
                case 'high':
                    $res = 300;
                    break;
                case 'critical':
                    $res = 400;
                    break;
            }
        } else {
            $res = 0;
        }
        
        return $res;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     *
     * @return stdClass $row
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    public function cron_SendNotifications()
    {
        $now = dt::verbal2mysql();
        
        $this->doReminderingForActiveRecs();
    }
    
    
    /**
     * Обновяване на рожденните дни по разписание
     * (Еженощно)
     */
    public function cron_UpdateCalendarEvents()
    {
        $query = self::getQuery();
        
        $arr = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на клучовете за напомнянията в календара
        $prefix = 'REM';
        
        $events = array();
        
        while ($rec = $query->fetch("#state = 'active' && #priority != 'low'")) {
            self::updateRemindersToCalendar($rec, $fromDate, $toDate, $prefix, $events);
        }
        
        $res = cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix . '-');
        
        $status = "В календара са добавени {$res['new']}, обновени {$res['updated']} и изтрити {$res['deleted']} напомняния";
        
        return $status;
    }
    
    
    public function doReminderingForActiveRecs()
    {
        $now = dt::verbal2mysql();
        $query = self::getQuery();
        $query->where("#state = 'active' AND if(#nextStartTime, #nextStartTime, #calcTimeStart) <= '{$now}' AND (#notifySent = 'no' OR #notifySent IS NULL)");
        
        while ($rec = $query->fetch()) {
            $savedRec = clone($rec);
            
            if ($rec->repetitionEach == 0) {
                $rec->notifySent = 'yes';
                $rec->state = 'closed';
                $fields = 'state,notifySent';
            } else {
                $rec->nextStartTime = $this->getNextStartingTime2($rec);
                $fields = 'nextStartTime';
            }
            
            self::save($rec, $fields);
            
            self::doUsefullyPerformance($savedRec);
        }
        
        $query = self::getQuery();
        $query->where("#state = 'active' AND (#calcTimeStart IS NOT NULL AND #calcTimeStart < '{$now}') AND (#notifySent = 'no' OR #notifySent IS NULL)");
        
        while ($rec = $query->fetch()) {
            $rec->calcTimeStart = $this->getNextStartingTime2($rec, false);
            if ($rec->calcTimeStart) {
                $this->save($rec, 'calcTimeStart');
            }
        }
    }
    
    
    /**
     * Прилага съответния действия зададени в известието
     * 
     * @param stdClass $rec
     */
    public static function doUsefullyPerformance($rec)
    {
        $rec->message = '|Напомняне|* "' . self::getVerbal($rec, 'title') . '"';
        $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        $rec->customUrl = array('cal_Reminders', 'single',  $rec->id);
        
        $subscribedArr = keylist::toArray($rec->sharedUsers);
        if (countR($subscribedArr)) {
            foreach ($subscribedArr as $userId) {
                if ($userId > 0 && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                    switch ($rec->action) {
                        case 'notify':
                            bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
                            break;
                        
                        case 'threadOpen':
                            self::$opened[$rec->id] = true;
                            
                            // self::logNotice('Записано състояние opened ' . $rec->id, $rec->id);
                            doc_Threads::save((object) array('id' => $rec->threadId, 'state' => 'opened'), 'state');
                            doc_Threads::doUpdateThread($rec->threadId);
                            bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
                            
                            //break;
                            return;
                        
                        case 'notifyNoAns':
                            
                            // Търсим дали има пристигнало писмо
                            $emailIncomings = 'email_Incomings';
                            $idEmailIncomings = core_Classes::getId($emailIncomings);
                            
                            if (doc_Containers::fetch("#threadId = '{$rec->threadId}' AND 
													  #docClass = '{$idEmailIncomings}' AND
													  #createdOn > '{$rec->modifiedOn}'") == false) {
                                bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
                            }
                            
                            break;
                        
                        case 'replicateDraft':
                            self::replicateThread($rec, true);
                            
                            return;
                            
                            //break;
                        
                        case 'replicate':
                            self::replicateThread($rec);
                            
                            return;
                            
                            //break;
                    }
                }
            }
        }
    }
    
    
    /**
     * Функция, която репликира нишката в същата папка
     */
    public static function replicateThread($rec, $draft = false, $emulateNextTime = true)
    {
        $tRec = doc_Threads::fetch($rec->threadId);
        $fcRec = doc_Containers::fetch($tRec->firstContainerId);
        
        $fcMvc = cls::get($fcRec->docClass);
        
        $fcMvcListArr = arr::make(strtolower($fcMvc->loadList), true);
        $havePlgClone = $fcMvcListArr['plg_clone'] ? true : false;
        
        // Първият документ в нишката
        $fdRec = $fcMvc->fetch($fcRec->docId);
        
        if ($havePlgClone && $rec->createdBy) {
            core_Users::sudo($rec->createdBy);
        }
        
        $newRec = clone($fdRec);
        
        if ($havePlgClone) {
            plg_Clone::unsetFieldsNotToClone($fcMvc, $newRec, $fdRec);
        }
        
        if ($draft) {
            $newRec->state = 'draft';
        } else {
            $newRec->state = $fdRec->state;
        }
        
        if ($havePlgClone) {
            $data = new stdClass();
            $data->action = 'clone';
            $data->form = $fcMvc->getForm();
            
            $data->form->rec = &$newRec;
            $data->form->rec->clonedFromId = $fdRec->id;
            
            if ($fcMvc instanceof embed_Manager) {
                $dId = $data->form->rec->{$fcMvc->driverClassField};
                if (cls::load($dId, true)) {
                    $Driver = cls::get($dId);
                    $Driver->addFields($data->form);
                }
            }
            
            $fcMvc->invoke('AfterPrepareEditForm', array(&$data, &$data));
        }
        
        unset($newRec->id, $newRec->threadId, $newRec->containerId, $newRec->createdOn, $newRec->modifiedOn, $newRec->rejectedOn, $newRec->sharedViews);
        
        $now = dt::now();
        
        if ($emulateNextTime) {
            $now = $rec->calcTimeStart;
        }
        
        // Променяме датите, спрямо сегашните
        $secs = dt::secsBetween($now, $rec->calcTimeStart);
        
        foreach ($fcMvc->fields as $name => $field) {
            $type = $field->type;
            if (($type instanceof type_Date) || ($type instanceof type_DateTime)) {
                if (isset($newRec->{$name}) && $field->input != 'none' && $field->input != 'hidden') {
                    $newRec->{$name} = dt::addSecs($secs, $newRec->{$name});
                } else {
                    $newRec->{$name} = null;
                }
            }
        }
        
        if (isset($newRec->title)) {
            $tf = 'title';
        } elseif (isset($newRec->name)) {
            $tf = 'name';
        }
        
        if ($tf) {
            $dateFormats = array(
                ' d-m-Y ',
                ' m-Y ',
                ' M-Y ',
                ' F-Y ',
                ' Y ',
            );
            
            $trans = array();
            
            foreach ($dateFormats as $df) {
                $trans[dt::mysql2verbal($rec->calcTimeStart, $df, 'bg')] = dt::mysql2verbal($now, $df, 'bg');
                $trans[dt::mysql2verbal($rec->calcTimeStart, $df, 'en')] = dt::mysql2verbal($now, $df, 'en');
            }
            
            foreach ($trans as $from => $to) {
                $from = '/' . str_replace('-', '[ \-\.\/\\\]', $from) . '/ui';
                $to = ' ' . $to . ' ';
                $newRec->{$tf} = preg_replace($from, $to, ' '. $newRec->{$tf} . ' ');
            }
        }
        
        $newRec->__isReplicate = true;
        
        if ($havePlgClone) {
            $fcMvc->invoke('BeforeSaveCloneRec', array($fdRec, &$newRec));
        }
        
        if ($draft) {
            $newRec->state = 'draft';
        } else {
            $newRec->state = $fdRec->state;
        }
        
        if ($havePlgClone) {
            // Маркираме записа като клониран
            $newRec->_isClone = true;
        }
        
        if ($havePlgClone && $rec->createdBy) {
            core_Users::exitSudo($rec->createdBy);
        }
        
        $fcMvc->save($newRec);
        $fcMvc->logWrite("Създаване от напомняне", $newRec->id);
        $fcMvc->logNotice("Създаване от напомняне", $newRec->id);
        cal_Reminders::logNotice("Създаване от напомняне", $rec->id);
        
        if ($havePlgClone) {
            // Инвокваме фунцкцията, ако някой иска да променя нещо
            $fcMvc->invoke('AfterSaveCloneRec', array($fdRec, &$newRec));
        }
        
        // Добавяме известие за черновите
        if ($draft && $newRec->containerId) {
            $subscribedArr = keylist::toArray($rec->sharedUsers);
            if (empty($subscribedArr)) {
                $subscribedArr[$rec->createdBy] = $rec->createdBy;
            }
            $cRec = doc_Containers::fetch($newRec->containerId);
            
            doc_Containers::addNotifications($subscribedArr, $fcMvc, $cRec, 'добави');
        }
    }
    
    
    public static function getNextStartingTime2($rec, $usePreviously = true)
    {
        // При активиране, ако не е подаден целия rec
        if ($rec->_isActivatedDoc && $rec->id) {
            $oRec = self::fetch($rec->id);
            $oRecArr = (array) $oRec;
            $recArr = (array) $rec;
            foreach ($oRecArr as $k => $v) {
                if (!array_key_exists($k, $recArr)) {
                    $rec->{$k} = $v;
                }
            }
        }
        
        $rec2 = clone($rec);
        
        if (empty($rec2->repetitionEach)) {
            if (empty($rec2->timePreviously)) {
                
                return;
            }
            
            if ($usePreviously) {
                
                return dt::timestamp2Mysql(dt::mysql2timestamp($rec2->timeStart) - $rec2->timePreviously);
            } else {
                
                return $rec2->timeStart;
            }
        }
        
        if ($usePreviously) {
            $nextStartTime = dt::timestamp2Mysql(dt::mysql2timestamp($rec2->timeStart) - $rec2->timePreviously);
        } else {
            $nextStartTime = $rec2->timeStart;
        }
        
        if ($nextStartTime > dt::now()) {
            
            return $nextStartTime;
        }
        
        do {
            $exTimeStart = $rec2->timeStart;
            $rec2->timeStart = self::calcNextStartTime($rec2, $usePreviously);
        } while ($rec2->timeStart <= dt::now() && ($exTimeStart < $rec2->timeStart));
        
        return $rec2->timeStart;
    }
    
    
    /**
     *  Изчислява времето за следващото стартиране на напомнянето. Винаги е дата > от текущата
     */
    public static function calcNextStartTime($rec, $usePreviously = true)
    {
        $timeStart = $rec->__nextStartTime ? $rec->__nextStartTime : $rec->timeStart;
        
        // Секундите на началната дата
        $startTs = dt::mysql2timestamp($timeStart);
        
        // Име повторение
        if ($rec->repetitionEach !== null) {
            // от какъв тип е
            switch ($rec->repetitionType) {
                // дни
                case 'days':
                    $nextStartTime = dt::addDays(($rec->repetitionEach), $timeStart);
                break;
                
                // седмици
                case 'weeks':
                    $nextStartTime = dt::addDays(($rec->repetitionEach * 7), $timeStart);
                break;
                
                // месеци
                case 'months':
                    $nextStartTime = dt::addMonths(($rec->repetitionEach), $timeStart);
                break;
                
                // месеци, като се спазва деня от седмицата
                case 'weekDay':
                    
                    $dayOfWeekName = strtolower(date('l', $startTs));
                    
                    if (date('j', $startTs) >= 1 && date('j', $startTs) <= 7) {
                        $monthsWeek = 'first';
                    }
                    if (date('j', $startTs) >= 8 && date('j', $startTs) <= 14) {
                        $monthsWeek = 'second';
                    }
                    if (date('j', $startTs) >= 15 && date('j', $startTs) <= 21) {
                        $monthsWeek = 'third';
                    }
                    if (date('j', $startTs) >= 22 && date('j', $startTs) <= 28) {
                        $monthsWeek = 'penultimate';
                    }
                    if (date('j', $startTs) >= 29 && date('j', $startTs) <= 31) {
                        $monthsWeek = 'last';
                    }
                    
                    $wDay = $monthsWeek. '-' . $dayOfWeekName;
                    $nextDate = dt::addMonths(($rec->repetitionEach), $timeStart);
                    
                    $nextStartTime = dt::timestamp2Mysql(dt::firstDayOfMonthTms(date('m', dt::mysql2timestamp($nextDate)), date('Y', dt::mysql2timestamp($nextDate)), $wDay));
                break;
                
                // точния ден от месеца
                case 'monthDay':
                    $nextStartTime = dt::addMonths(($rec->repetitionEach), $timeStart);
                break;
            
            }
        } else {
            $nextStartTime = $timeStart;
        }
        
        $rec->__nextStartTime = $nextStartTime;
        
        // Ако имаме отбелязано време предварително
        if (($rec->timePreviously != null) && $usePreviously) {
            if ($nextStartTime) {
                $nextStartTimeTs = dt::mysql2timestamp($nextStartTime) - $rec->timePreviously;
            } else {
                $nextStartTimeTs = $startTs - $rec->timePreviously;
            }
            
            $nextStartTime = dt::timestamp2Mysql($nextStartTimeTs);
        }
        
        return $nextStartTime;
    }
    
    
    /**
     * По зададен брой пъти и тип (ден или сецмица) изчислява интервала в секунди
     *
     * @param int    $each
     * @param string $type = days/weeks
     */
    public static function getSecOfInterval($each, $type)
    {
        if ($type !== 'days' || $type !== 'weeks') {
            $intervalTs;
        }
        if ($type == 'days') {
            $intervalTs = $each * 24 * 60 * 60;
        } else {
            $intervalTs = $each * 7 * 24 * 60 * 60;
        }
        
        return $intervalTs;
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $allFieldsArr = array('priority' => 'Приоритет',
            'action' => 'Действие',
            'timePreviously' => 'Предварително',
            'calcTimeStart' => 'Начало',
            'nextStartTime' => 'Следващо напомняне',
            'rem' => 'Напомняне',
            'repetitionTypeMonth' => 'Съблюдаване на',
        );
        foreach ($allFieldsArr as $fieldName => $val) {
            if ($row->{$fieldName}) {
                $resArr[$fieldName] = array('name' => tr($val), 'val' => "[#{$fieldName}#]");
            }
        }
        
        if ($rec->calcTimeStart == $rec->nextStartTime) {
            unset($resArr['nextStartTime']);
        }
        
        if ($rec->repetitionEach == '1') {
            switch ($rec->repetitionType) {
                // дни
                case 'days':
                    $row->repetitionType = tr('ден');
                    break;
                    
                    // седмици
                case 'weeks':
                    $row->repetitionType = tr('седмица');
                    $row->each = tr('всяка');
                    break;
                    
                    // месеци
                case 'months':
                    $row->repetitionType = tr('месец');
                    break;
                    
                    // месеци, като се спазва деня от седмицата
                case 'weekDay':
                    $row->repetitionType = tr('месец');
                    $row->repetitionTypeMonth = tr($rec->monthsWeek. ' ' .$rec->weekDayNames). tr(' от месеца');
                    break;
                    
                    // точния ден от месеца
                case 'monthDay':
                    $row->repetitionType = tr('месец');
                    $row->repetitionTypeMonth = tr('точния ден от месеца');
                    break;
            
            }
            
            if (!$row->each) {
                $row->each = tr('всеки');
            }
            
            $row->repetitionEach = '';
        } else {
            $row->each = tr('на всеки');
            
            if ($rec->repetitionType == 'weekDay') {
                $row->repetitionType = tr('месеца');
            }
            
            if ($rec->repetitionType == 'monthDay') {
                $row->repetitionType = tr('месеца');
                $row->repetitionTypeMonth = tr('точния ден от месеца');
            }
        }
        
        if ($rec->repetitionEach != null) {
            $resArr['each'] = array('name' => tr('Повторение'), 'val' => '[#each#]<!--ET_BEGIN repetitionEach--> [#repetitionEach#]<!--ET_END repetitionEach--><!--ET_BEGIN repetitionType--> [#repetitionType#]<!--ET_END repetitionType-->');
        }
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     * Добавянето на сигнал отваря треда
     */
    public static function getThreadState($id)
    {
        if (self::$opened[$id]) {
            
            // self::logNotice('Върнато състояние opened ' . $id, $id);
            
            return 'opened';
        }
        
        // self::logNotice('Върнато състояние closed ' . $id, $id);
        
        return 'closed';
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        bgerp_Portal::invalidateCache(null, 'bgerp_drivers_Calendar');
    }
}
