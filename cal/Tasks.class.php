<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Tasks extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cal_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Заглавие
     */
    var $title = "Задачи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Задача";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, timeStart=Начало, repeat=Повторение, members, timeNextRepeat';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'admin,doc';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'admin,doc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,doc';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin,doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,doc';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'admin, doc';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'admin, doc';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/sheduled-task-icon.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Tsk";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=нисък,
                                    normal=нормален,
                                    high=висок,
                                    critical=критичен)', 
        'caption=Приоритет,mandatory,value=normal,maxRadio=4,columns=4');
        $this->FLD('details',      'richtext', 'caption=Описание,mandatory');
        $this->FLD('members', 'keylist(mvc=core_Users,select=names)', 'caption=Отговорници,mandatory');
        
        $this->FLD('timeStart',      'datetime',     'caption=Времена->Начало');
        $this->FLD('timeLastRepeat', 'datetime',     'caption=Времена->Начало на последното повторение, input=none');
        $this->FLD('timeNextRepeat', 'datetime',     'caption=Времена->Начало на следващото повторение, input=none');
        $this->FLD('activatedOn',    'datetime',     'caption=Времена->Активирана,input=none');
        
        // Продължителност
        $this->FLD('timeDuration',     'type_Minutes', 'caption=Времена->Продължителност');
        
        // Край на задача ю, която има продължителност
        $this->FLD('executeTimeEnd',   'datetime',           'caption=Времена->Край на изпълнение');
        
        $this->FLD('repeatTimeEnd',    'datetime',           'caption=Времена->Край на повторенията,input=none');
        $this->FLD('notificationSent', 'enum(yes,no)',       'caption=Изпратена нотификация,mandatory,input=none');
        
        $this->FLD('repeat', 'enum(none=няма,
                                   everyDay=всеки ден,
                                   everyTwoDays=на всеки 2 дена,
                                   everyThreeDays=на всеки 3 дена,
                                   everyWeek=всяка седмица,
                                   everyMonth=всеки месец,
                                   everyThreeMonths=на всеки 3 месеца,
                                   everySixMonths=на всяко полугодие,
                                   everyYear=всяка година,
                                   everyTwoYears=всеки две години,
                                   everyFiveYears=всеки пет години)', 'caption=Повторение');
        
        // notifications
        $this->FLD('notification', 'type_Minutes', 'caption=Нотификация, input=none');
        
        $this->FNC('taskType', 'enum(reminder=напомняне,
                                     event=събитие,
                                     toDoOnce=задача без повторение,
                                     toDoRepeat=задача с повторение)', 'caption=Тип на задачата, input=none');
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
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
        
        return $row;
    }
    
    
    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    static function getShared($id)
    {
        return static::fetchField($id, 'members');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // За метода 'act_ChangeTaskState' има права, само ако потребителя е сред отговорниците на задачата
        if ($rec->id && ($action == 'changetaskstate')) {
            $rec = $mvc->fetch($rec->id);
                        
            if (!type_Keylist::isIn($userId, $rec->members)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се при активиране на задача и изчислява параметри на повторенията
     *
     * Връща масив с елементи:
     * ['last'] - време на последното стартиране на задачата
     * ['next'] - време на предстоящото стартиране на задачата
     * ['last_still_active'] - връща TRUE или FALSE в зависимост дали последното стартиране е приключило
     * ['timeStartsInThePastArr'] - този елемент е масив съдържащ времената на всички стартирания на задачата преди сега
     * Смисълът от 'last_still_active' е при изпращане на нотификациите - ако последното стартиране е все още активно,
     * за него текста на нотификацията ще е различен от нотификациите за тези, които са вече минали.
     *
     * @param  stdClass $rec
     * @return array $repeatTime
     */
    function calcRepeatTimes($rec)
    {
        // Конвертира стойността за повторение към стринг, който ще бъде използван във функцията strtotime()
        $repeatArr = array('everyDay'         => '+1 day',
            'everyTwoDays'     => '+2 days',
            'everyThreeDays'   => '+3 days',
            'everyWeek'        => '+1 week',
            'everyMonth'       => '+1 month',
            'everyThreeMonths' => '+3 months',
            'everySixMonths'   => '+6 months',
            'everyYear'        => '+1 year',
            'everyTwoYears'    => '+2 years',
            'everyFiveYears'   => '+5 years');
        
        $repeatStr = $repeatArr[$rec->repeat];
        expect($repeatStr = $repeatArr[$rec->repeat]);
        
        if (!$rec->activatedOn && $rec->timeDuration) {
            // 1. Задачата има повторение, досега не е била активирана, има продължителност (край)
            // =====================================================================================  
            $counter = 0;
            
            while (TRUE) {
                if (!$counter) {
                    // Първи цикъл
                    $timeLastRepeat = $rec->timeStart;
                } else {
                    // Цикли след първия
                    if ($counter > 20) {
                        bp('Има повече от 20 цикъла (периода) при изчисляване времето на последното стартиране и следващото стартиране');
                    } else {
                        $timeLastRepeat = $timeNextRepeat;
                    }
                }
                
                $counter++;
                
                // Изчисляване на старта на следващото повторение на базата на времето в променливата $timeLastRepeat
                $tsTimeNextRepeatForTest = strtotime($repeatStr, dt::mysql2timestamp($timeLastRepeat));
                $timeNextRepeatForTest   = dt::timestamp2mysql($tsTimeNextRepeatForTest);
                
                // Проверка дали намереното начало на следващия цикъл е в миналото или в бъдещето
                if ($tsTimeNextRepeatForTest > time()) {
                    $timeNextRepeat = $timeNextRepeatForTest;
                    
                    // Проверка дали последния цикъл стартиран в миналото не е все още действащ
                    $tsTimeLastRepeatEnd = dt::mysql2timestamp($timeLastRepeat) + ($rec->timeDuration * 60);
                    
                    if ($tsTimeLastRepeatEnd > time()) {
                        $repeatTime['lastRepeatStillActive'] = TRUE;
                    } else {
                        $repeatTime['lastRepeatStillActive'] = FALSE;
                        $timeStartsInThePastArr[]      = $timeLastRepeat;
                    }
                    
                    // Край на цикъла
                    break;
                } else {
                    $timeNextRepeat = $timeNextRepeatForTest;
                    
                    $timeStartsInThePastArr[] = $timeLastRepeat;
                }
            }
        } elseif (!$rec->activatedOn && !$rec->timeDuration) {
            // 2. Задачата има повторение, досега не е била активирана, няма продължителност (край)
            $timeLastRepeat = $rec->timeStart;
            $timeNextRepeat = dt::timestamp2mysql(strtotime($repeatStr, dt::mysql2timestamp($timeLastRepeat)));
        } elseif ($rec->activatedOn) {
            // 3. Задачата има повторение, вече е била активирана, не е от значение дали има продължителност (край)
            $timeLastRepeat = $rec->timeNextRepeat;
            $timeNextRepeat = dt::timestamp2mysql(strtotime($repeatStr, dt::mysql2timestamp($timeLastRepeat)));
        }
        
        // Елементи на масива за return
        $repeatTime['last'] = $timeLastRepeat;
        $repeatTime['next'] = $timeNextRepeat;
        
        if (!empty($timeStartsInThePastArr)) {
            $repeatTime['timeStartsInThePastArr'] = $timeStartsInThePastArr;
        } else {
            $repeatTime['timeStartsInThePastArr'] = NULL;
        }
        
        if ($repeatTime['lastRepeatStillActive']) {
            $repeatTime['lastRepeatStillActive'];
        } else {
            $repeatTime['lastRepeatStillActive'] = FALSE;
        }
        
        return $repeatTime;
    }
    
    
    /**
     * Метод за тестване на doc_Tasks::timeOldStart()
     */
    function act_TestCalcRepeatTimes()
    {
        $j = new stdClass;
        
        $j->timeStart      = '2012-03-19 13:15:00';
        $j->timeDuration   = 0;
        $j->repeat         = 'everyTwoDays';
        $j->activatedOn    = '2012-03-19 13:15:00';
        $j->timeLastRepeat = '2012-03-27 13:15:00';
        $j->timeNextRepeat = '2012-03-29 13:15:00';
        
        $repeatTime = doc_Tasks::calcRepeatTimes($j);
        
        var_dump($j);
        print "<br/>=====================================================";
        print "<br/>";
        var_dump("Last repeat time: " . $repeatTime['last']);
        print "<br/>";
        var_dump("Next repeat time: " . $repeatTime['next']);
        print "<br/>";
        
        if ($repeatTime['lastRepeatStillActive'] === TRUE) {
            $lastRepeatStillActive = "YES";
        } else {
            $lastRepeatStillActive = "NO";
        }
        
        var_dump("Last repeat still active: " . $lastRepeatStillActive);
        print "<br/>=====================================================";
        print "<br/>Repeats in the past (if the last is still active will not be listed): ";
        
        if (!empty($repeatTime[timeStartsInThePastArr])) {
            $count = 0;
            
            foreach ($repeatTime[timeStartsInThePastArr] as $timeOldStart) {
                $counter++;
                print "<br/>" . $counter . ": " . $timeOldStart;
            }
        } else {
            print "No repeats in the past";
        }
        print "<br/>=====================================================";
        print "<br/>";
        
        if ($repeatTime['lastRepeatStillActive'] === TRUE) {
            var_dump("Time last repeat (still active): " . $repeatTime['last']);
        }
    }
    
    
    /**
     * Приготвяне на картинките за приоритета
     *
     * @param $mvc
     * @param $row
     * @param $rec
     * @param $fields
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        if ($fields['-single']) {
            $row->priority = 'Приоритет ';
            
            switch ($rec->priority) {
                case 'low' :
                    $row->priority .= '<img src=' . sbf('img/16/priority_low.png') . '><img src=' . sbf('img/16/priority_low.png') . '>';
                    break;
                case 'normal' :
                    $row->priority .= '<img src=' . sbf('img/16/priority_low.png') . '>';
                    break;
                case 'high' :
                    $row->priority .= '<img src=' . sbf('img/16/priority_high.png') . '>';
                    break;
                case 'critical' :
                    $row->priority .= '<img src=' . sbf('img/16/priority_high.png') . '><img src=' . sbf('img/16/priority_high.png') . '>';
                    break;
            }
        }
    }
    
    
    /**
     * Калкулира времето за повторение от string в секунди
     *
     * @param string $repeat
     * @return int $repeatSecs
     */
    static function repeat2timestamp($repeat)
    {
        switch ($repeat) {
            case "none" :
                $repeatSecs = 0;
                break;
            case "everyDay" :
                $repeatSecs = 60 * 60 * 24;
                break;
            case "everyTwoDays" :
                $repeatSecs = 60 * 60 * 24 * 2;
                break;
            case "everyThreeDays" :
                $repeatSecs = 60 * 60 * 24 * 3;
                break;
            case "everyWeek" :
                $repeatSecs = 60 * 60 * 24 * 7;
                break;
        }
        
        return $repeatSecs;
    }
    
    
    /**
     * Нотификация и стартиране на задачите по Cron
     */
    function cron_AutoTasks()
    {
        // #1 Нотификация на задачите
        $queryTasks = doc_Tasks::getQuery();
        $now = dt::verbal2mysql();
        
        $where = "#state = 'pending' AND
                  #repeat = 'none' AND  
                  #notificationSent = 'no' AND 
                  (DATE_ADD('{$now}', INTERVAL CAST(CONCAT('', #notification) AS UNSIGNED) MINUTE) > #timeStart)";
        
        /*
         $where = "#state = 'pending' AND
         #notificationSent = 'no' AND
         (DATE_ADD('{$now}', INTERVAL CAST(CONCAT('', #notification) AS UNSIGNED) MINUTE) > #timeNextRepeat)";
         */
        
        while($recTasks = $queryTasks->fetch($where)) {
            // bp(dt::verbal2mysql(), $recTasks->notification, $recTasks->timeNextRepeat);
            
            // Датата и часът на стартиране на задачата (без секундите)
            $taskDate = substr($recTasks->timeNextRepeat, 0, 10);
            $taskTime = substr($recTasks->timeNextRepeat, 11, 5);
            
            $minutesToBegin = round((dt::mysql2timestamp($recTasks->timeNextRepeat) - time()) / 60);
            
            $msg = $minutesToBegin . ' ' . tr('минути до задача') . " \"" . $recTasks->title . "\"";
            $url = array('doc_Tasks', 'single', $recTasks->id);
            $priority = 'normal';
            
            $usersArr = type_Keylist::toArray($recTasks->members);
            
            foreach($usersArr as $userId) {
                // Изпращане на нотификацията
                bgerp_Notifications::add($msg, $url, $userId, $priority);
            }
            
            // Маркер, че нотификацията е изпратена
            $recTasks->notificationSent = 'yes';
            
            doc_Tasks::save($recTasks);
        }
        
        unset($queryTasks, $where, $recTasks);
        
        // #1 ENDOF Нотификация на задачите
        
        // #2 Старт на задачите
        $queryTasks = doc_Tasks::getQuery();
        
        // $where = "#timeNextRepeat <= '{$now}' AND #state = 'pending'";
        $where = "(#timeStart <= '{$now}' AND #state = 'pending' AND #repeat = 'none') OR
                  (#timeNextRepeat <= '{$now}' AND #state = 'pending' AND #repeat != 'none')";
        
        while($recTasks =  $queryTasks->fetch($where)) {
            // Смяна state на 'active'
            $recTasks->state = 'active';
            doc_Tasks::save($recTasks);
            
            // Отваря треда
            $threadId = $recTasks->threadId;
            $recThread = doc_Threads::fetch($threadId);
            $recThread->state = 'opened';
            doc_Threads::save($recThread);
            
            // Нотификация
            
            // Датата и часът на стартиране на задачата (без секундите)
            $taskDate = substr($recTasks->timeNextRepeat, 0, 10);
            $taskTime = substr($recTasks->timeNextRepeat, 11, 5);
            
            $msg = tr("Стартирана задача") . " \"" . $recTasks->title . "\"";
            $url = array('doc_Tasks', 'single', $recTasks->id);
            $priority = 'normal';
            
            $usersArr = type_Keylist::toArray($recTasks->members);
            
            foreach($usersArr as $userId) {
                // Изпращане на нотификацията
                bgerp_Notifications::add($msg, $url, $userId, $priority);
            }
            
            // ENDOF Нотификация
        }
        
        unset($queryTasks, $where, $recTasks);
        
        // ENDOF #2 Старт на задачите
        
        // #3 Автоматично приключване или пренавиване на задачите, които имат продължителност
        $queryTasks = doc_Tasks::getQuery();
        $now = dt::verbal2mysql();
        
        $where = "#state = 'active'
                  AND #timeDuration != ''
                  AND (
                         (#timeDuration > 0 AND DATE_ADD(#timeNextRepeat, INTERVAL #timeDuration MINUTE) < '{$now}')
                       OR 
                         ((#repeatTimeEnd IS NOT NULL) AND (#repeatTimeEnd < '{$now}'))
                      )";
        
        while($recTasks = $queryTasks->fetch($where)) {
            if ($recTasks->repeat == 'none') {
                // Смяна state на 'closed' - затваряне на задачата
                $recTasks->state = 'closed';
                doc_Tasks::save($recTasks);
            } else {
                $recTasks->timeNextRepeat   = doc_Tasks::calcNextRepeatInFuture($recTasks->timeStart, $recTasks->repeat);
                $recTasks->notificationSent = 'no';
                $recTasks->state            = 'pending';
                doc_Tasks::save($recTasks);
            }
        }
        
        // ENDOF #3 Автоматично приключване или пренавиване на задачите
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec = new stdClass();
        $rec->systemId    = 'Tasks - notify and start';
        $rec->description = "Задачи - нотификация и стартиране";
        $rec->controller  = $mvc->className;
        $rec->action      = 'AutoTasks';
        $rec->period      = 5;
        $rec->offset      = 0;
        $rec->delay       = 0;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li>Модул задачи:
                         <br/>
                         1. Автоматично изпращане на нотификации за предстоящи задачи
                         <br/>
                         2. Автоматично стартиране на задачите</li>";
        } else {
            $res .= "<li>Задачи - отпреди Cron е бил нагласен за:
                         <br/>
                         1. Автоматично изпращане на нотификации за предстоящи задачи
                         <br/>
                         2. Автоматично стартиране на задачите</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Добавя бутони single view-то.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = $data->rec;
        $cu  = core_Users::getCurrent();
        
        if ($rec->state == 'active' || $rec->state == 'pending') {
            // Ако потребителя е сред отговорниците на задачата, има бутон да я приключва
            if ($mvc->haveRightFor('changeTaskState', $rec)) {
                $finalizeUrl = array('doc_Tasks', 'changeTaskState', $rec->id);
                $data->toolbar->addBtn('Настройки', $finalizeUrl, 'id=closeTask,class=btn-settings');
            }
        }
    }
    
    
    /**
     * Смяна state-а на задача
     */
    function act_ChangeTaskState()
    {
        expect($taskId  = Request::get('id', 'int'));
        $recTask = doc_Tasks::fetch($taskId);
        
        // $this->canChangeTaskState = 'no_one';
        
        // $this->requireRightFor('changeTaskState', $recTask);
        if ($this->haveRightFor('changeTaskState', $recTask)) {
            // Форма
            $form = cls::get('core_Form');
            $form->title = "Настройки на задачата \"" . str::limitLen($this->getVerbal($recTask, 'title'), 70) . "\"";
            
            // timeStart
            $form->FNC('timeStart', 'datetime', 'caption=Времена->Ново начало,mandatory');
            
            if ($recTask->repeat != 'none') {
                $form->setDefault('timeStart', $recTask->timeNextRepeat);
            }
            
            // repeat
            $form->FNC('repeat', 'enum(none=няма,
                                       everyDay=всеки ден,
                                       everyTwoDays=на всеки 2 дена,
                                       everyThreeDays=на всеки 3 дена,
                                       everyWeek=всяка седмица,
                                       everyMonth=всеки месец,
                                       everyThreeMonths=на всеки 3 месеца,
                                       everySixMonths=на всяко полугодие,
                                       everyYear=всяка година,
                                       everyTwoYears=всеки две години,
                                       everyFiveYears=всеки пет години)', 'caption=Времена->Повторение,mandatory');
            $form->setDefault('repeat', $recTask->repeat);
            
            // notification
            $form->FNC('notification', 'enum(0=на момента,
                                             5=5 мин. предварително,
                                             10=10 мин. предварително,
                                             30=30 мин. предварително,
                                             60=1 часа предварително,
                                             120=2 часа предварително,
                                             480=8 часа предварително,
                                             1440=1 ден предварително,
                                             2880=2 дни предварително,
                                             4320=3 дни предварително,
                                             10080=7 дни предварително)', 'caption=Времена->Напомняне,mandatory');
            $form->setDefault('notification', $recTask->notification);
            
            $form->view = 'vertical';
            $form->showFields = 'timeStart, repeat, notification';
            
            // Бутон 'Затваряне'
            $closeUrl = array('doc_Tasks', 'closeTask', $recTask->id);
            $form->toolbar->addBtn('Приключване', $closeUrl, 'id=closeTask,class=btn-task-close,warning=Наистина ли желаете задачата да бъде приключена?');
            
            // Бутон submit
            $form->toolbar->addSbBtn('Презареждане', 'default', 'class=btn-reload');
            
            // Бутон 'Отказ'
            $backUrl = array('doc_Tasks', 'single', $recTask->id);
            $form->toolbar->addBtn('Отказ', $backUrl, 'id=reloadTask,class=btn-cancel, order=50');
            
            // Action
            $form->setAction(array($this, 'changeTaskState', $recTask->id));
            
            // Въвеждаме съдържанието на полетата
            $form->input();
            
            // Проверка дали е предадена формата
            if ($form->isSubmitted()) {
                $rec = $form->rec;
                $rec->timeNextRepeat = doc_Tasks::calcNextRepeatInFuture($rec->timeStart, $rec->repeat);
                
                // Валидация
                $tsTimeStart = dt::mysql2timestamp($rec->timeStart);
                
                if ($tsTimeStart == FALSE) {
                    $form->setError('timeStart', 'Моля, коригирайте новото време <br/>за старт на задачата');
                } else {
                    $recTask->timeStart        = $rec->timeStart;
                    $recTask->repeat           = $rec->repeat;
                    $recTask->notification     = $rec->notification;
                    $recTask->timeNextRepeat   = $rec->timeNextRepeat;
                    $recTask->notificationSent = 'no';
                    $recTask->state            = 'pending';
                    
                    doc_Tasks::save($recTask);
                    
                    return new Redirect(array($this, 'single', $taskId));
                }
            }
            
            return $this->renderWrapping($form->renderHtml());
        }
    }
    
    
    /**
     * Затваряне на задача
     */
    function act_CloseTask()
    {
        expect($taskId = Request::get('id', 'int'));
        $recTask = doc_Tasks::fetch($taskId);
        $recTask->state = 'closed';
        
        doc_Tasks::save($recTask);
        
        return new Redirect(array($this, 'single', $taskId));
    }
    
    
    /**
     * Филтър на задачите
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Филтър';
        $data->listFilter->view  = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        $data->listFilter->FNC('user',   'type_Users', 'caption=Потребител(и),silent');
        $data->listFilter->FNC('date',   'date',    'caption=Дата, width=110px');
        $data->listFilter->FNC('strFilter', 'varchar', 'caption=Търсене');
        $data->listFilter->FNC('stateFilter',  'enum(all=Всички,
                                                     active=Активни,
                                                     pending=Чакащите, 
                                                     closed=Приключени, 
                                                     draft=Чернови)', 'caption=Статус');
        
        $data->listFilter->showFields = 'user, date, strFilter, stateFilter';
        
        // set default user
        $cu = core_Users::getCurrent();
        $data->listFilter->setDefault('user', $cu);
        
        $recFilter = &$data->listFilter->rec;
        
        $recFilter->user = '|' . core_Users::getCurrent() . '|';
        
        // Активиране на филтъра
        $data->listFilter->input();
        
        // Филтриране по потребител
        $data->query->likeKeylist('members', $recFilter->user);
        
        // date
        /*
         if ($recFilter->date) {
         $condDate = "#timeNextRepeat >= DATE_SUB('{$recFilter->date}', INTERVAL 7 DAY)
         AND
         #timeNextRepeat <= DATE_ADD('{$recFilter->date}', INTERVAL 7 DAY)";
         */
        
        // date - case #1 - Показват се само задачите с начало по-голяма или равна дата на тази дата,
        // с изключение на активните, които се показват всички, независимо от датата
        if ($recFilter->date && !$recFilter->strFilter) {
            $condDate = "(#timeNextRepeat >= NOW() AND #state != 'active')
                         OR (#state = 'active')";
        }
        
        // date - case #2 - Ако това поле не е попълнено, се показват задачите от седем дни назад
        if (!$recFilter->date && !$recFilter->strFilter) {
            $condDate = "#timeNextRepeat >= DATE_SUB(NOW(), INTERVAL 7 DAY) OR #state = 'active'";
        }
        
        // date - case #3 - Ако имаме текстово търсене се включват и задачите 1 година назад
        if (!$recFilter->date && $recFilter->strFilter) {
            $condDate = "#timeNextRepeat >= DATE_SUB(NOW(), INTERVAL 1 YEAR) OR #state = 'active'";
        }
        
        // date - case #4 - Ако имаме текстово търсене и дата във филтъра
        if ($recFilter->date && $recFilter->strFilter) {
            $condDate = "#timeNextRepeat >= '{$recFilter->date} 00:00:00' AND #timeNextRepeat <= '{$recFilter->date} 23:59:59'";
        }
        
        // ENDOF date
        
        // strFilter
        if ($recFilter->strFilter) {
            $condStrFilter = "#title LIKE '%{$recFilter->strFilter}%'";
        }
        
        // stateFilter
        if ($recFilter->stateFilter && $recFilter->stateFilter != 'all') {
            $condStateFilter = "#state = '{$recFilter->stateFilter}'";
        }
        
        // Where
        if ($condUser)        $data->query->where($condUser);
        
        if ($condDate)        $data->query->where($condDate);
        
        if ($condStrFilter)   $data->query->where($condStrFilter);
        
        if ($condStateFilter) $data->query->where($condStateFilter);
        
        // bp($data->query->buildQuery());
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if ($data->form->rec->id) {
            $data->form->rec->notification = type_Minutes::toVerbal_($data->form->rec->notification);
            $data->form->rec->timeDuration = type_Minutes::toVerbal_($data->form->rec->timeDuration);
        } else {
            // Подготвяне заглавие по подразбиране за нова задача
            if (Request::get('threadId', 'int')) {
                expect($threadId = Request::get('threadId', 'int'));
                $firstContainerId = doc_Threads::fetchField($threadId, 'firstContainerId');
                $docObj = doc_Containers::getDocument($firstContainerId, 'doc_DocumentIntf');
                $docRow = $docObj->getDocumentRow();
                
                $data->form->rec->title = $docRow->title;
            }
            
            $data->form->setDefault('priority', 'normal');
            
            $cu = core_Users::getCurrent();
            $data->form->setDefault('members', "|{$cu}|");
            
            // Duration suggestions
            $durationSuggestions = arr::make(tr(",
                                                 5 мин., 
                                                 10 мин.,
                                                 15 мин.,
                                                 30 мин.,
                                                 1 час,
                                                 2 часа,
                                                 8 часа,
                                                 1 ден,
                                                 2 дни,
                                                 3 дни,
                                                 7 дни"), TRUE);
            
            $data->form->setSuggestions('timeDuration', $durationSuggestions);
            
            // Notification suggestions
            $notificationSuggestions = arr::make(tr(",
                                                     5 мин., 
                                                     10 мин.,
                                                     15 мин.,
                                                     30 мин.,
                                                     1 час,
                                                     2 часа,
                                                     8 часа,
                                                     1 ден,
                                                     2 дни,
                                                     3 дни,
                                                     7 дни"), TRUE);
            
            $data->form->setSuggestions('notification', $notificationSuggestions);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $checkTaskDurationResult = doc_Tasks::checkTaskDuration($rec);
            
            if ($checkTaskDurationResult === FALSE) {
                $form->setError('timeDuration',   'Неправилно въведенa продължителност');
                $form->setError('executeTimeEnd', 'Неправилно въведен край на изпълнение');
            } else {
                // timeStart и timeDuration въведени, executeTimeEnd не е въведено
                if ($rec->timeStart && $rec->timeDuration && !$rec->executeTimeEnd) {
                    $tsTimeStart      = dt::mysql2timestamp($rec->timeStart);
                    $tsTimeDuration   = type_Minutes::fromVerbal_($rec->timeDuration) * 60;
                    
                    $rec->executeTimeEnd = dt::timestamp2mysql($tsTimeStart + $tsTimeDuration);
                }
                
                // timeStart и executeTimeEnd въведени, timeDuration не е въведено
                if ($rec->timeStart && $rec->executeTimeEnd && !$rec->timeDuration) {
                    $tsTimeStart      = dt::mysql2timestamp($rec->timeStart);
                    $tsExecuteTimeEnd = dt::mysql2timestamp($rec->executeTimeEnd);
                    $tsTimeDuration   = $tsExecuteTimeEnd - $tsTimeStart;
                    
                    $rec->timeDuration   = $tsTimeDuration / 60;
                }
            }
            
            if($form->cmd == 'active') {
                doc_Tasks::on_Activation($rec);
            }
        }
    }
    
    
    /**
     * Активиране на задачата
     *
     * @param stdClass $rec
     */
    static function on_Activation($rec)
    {
        $recBeforeActivation = clone $rec;
        
        // 1. При създаване на нов запис (задача) с бутона 'Активиране'
        // ============================================================
        if (!$rec->timeStart) {
            // 1.1. Ако задачата няма зададено начало (to do once)
            // ===================================================
            
            // Свойства (8) и нотификации (1)
            $rec->case = "1.1.";
            $rec->timeStart = dt::verbal2mysql();
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state     = 'active';
            $rec->activatedOn = $rec->timeStart;
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg  = "Активирана е нова задача" . " \"" . $rec->title . "\" на " . $rec->activatedOn;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif ($rec->timeStart && ($rec->timeStart > dt::verbal2mysql()) && ($rec->repeat == 'none')) {
            // 1.2. Ако задачата има зададено начало, ако началото е в бъдещето и няма повторение
            // ==================================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.2.";
            
            // $rec->timeStart
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state = 'pending';
            $rec->activatedOn = NULL;
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg = "Нова задача" . " \"" . $rec->title . "\" на " . $rec->timeStart;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "no";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart > dt::verbal2mysql()) && ($rec->repeat != 'none')) {
            // 1.3. Ако началото е в бъдещето и има повторение (без значение дали има край или не)
            // ===================================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.3.";
            
            // $rec->timeStart
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state = 'pending';
            $rec->activatedOn = NULL;
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg = "Нова задача" . " \"" . $rec->title . "\" на " . $rec->timeStart;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "no";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) && $rec->repeat == 'none' && $rec->executeTimeEnd <= dt::verbal2mysql()) {
            // 1.4. Задачата има начало в миналото, няма повторение и има край в миналото
            // ==========================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.4.";
            
            // $rec->timeStart
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state = 'closed';
            $rec->activatedOn = dt::verbal2mysql();
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg = "Активирана е задача с начало и край в миналото и е затворена. 
                                     Заглавие на задачата " . " \"" . $rec->title . "\", време за стартиране - " . $rec->timeStart;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) && $rec->repeat == 'none' && $rec->executeTimeEnd > dt::verbal2mysql()) {
            // 1.5. Задачата има начало в миналото, няма повторение и има край в бъдещето
            // ==========================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.5.";
            
            // $rec->timeStart
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state = 'active';
            $rec->activatedOn =  dt::verbal2mysql();
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg  = "Активирана е нова задача" . " \"" . $rec->title . "\" на " . $rec->activatedOn;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) && $rec->repeat == 'none' && !$rec->executeTimeEnd) {
            // 1.6. Задачата има начало в миналото, няма повторение и няма край
            // ================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.6.";
            
            // $rec->timeStart
            $rec->timeLastRepeat = NULL;
            $rec->timeNextRepeat = NULL;
            
            $rec->state = 'active';
            $rec->activatedOn = dt::verbal2mysql();
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg  = "Активирана е нова задача" . " \"" . $rec->title . "\" на " . $rec->activatedOn;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) &&
            !$rec->executeTimeEnd &&
            ($rec->repeat != 'none') &&
            !$rec->activatedOn &&
            !$rec->timeLastRepeat &&
            !$rec->timeNextRepeat) {
            // 1.7. Задачата има начало в миналото, няма край и има повторение. Активиране на първия цикъл.
            // ============================================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.7.";
            
            // $rec->timeStart
            $repeatTime = doc_Tasks::calcRepeatTimes($rec);
            $rec->timeLastRepeat = $repeatTime['last'];
            $rec->timeNextRepeat = $repeatTime['next'];
            
            $rec->state = 'active';
            $rec->activatedOn = dt::verbal2mysql();
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg = "Активирана е нова задача" . " \"" . $rec->title . "\" на " . $rec->timeLastRepeat;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) &&
            !$rec->executeTimeEnd &&
            ($rec->repeat != 'none') &&
            $rec->activatedOn &&
            $rec->timeLastRepeat &&
            $rec->timeNextRepeat) {
            // 1.8. Задачата има начало в миналото, няма край и има повторение. Активиране на цикъл след първия.
            // =================================================================================================
            
            // Свойства (7) и нотификация (1)
            $rec->case = "1.8.";
            
            // $rec->timeStart
            $repeatTime = doc_Tasks::calcRepeatTimes($rec);
            $rec->timeLastRepeat = $repeatTime['last'];
            $rec->timeNextRepeat = $repeatTime['next'];
            
            $rec->state = 'active';
            $rec->activatedOn = dt::verbal2mysql();
            
            doc_Tasks::save($rec);
            
            $rec->notificationMsg = "Активирано е повторение на задача" . " \"" . $rec->title . "\" на " . $rec->timeLastRepeat;
            doc_Tasks::sendNotification($rec);
            $rec->notificationSent = "yes";
            
            doc_Tasks::save($rec);
        } elseif (($rec->timeStart <= dt::verbal2mysql()) &&
            $rec->executeTimeEnd &&
            ($rec->repeat != 'none')) {
            // 1.9. и 1.10. Задачата има начало в миналото, има край и има повторение
            // ======================================================================
            
            $repeatTime          = doc_Tasks::calcRepeatTimes($rec);
            $tsTimeLastRepeatEnd = strtotime("+" . $rec->timeDuration . " minutes", dt::mysql2timestamp($repeatTime['last']));
            
            if ($tsTimeLastRepeatEnd > time()) {
                // 1.9. Края на последния активиран цикъл не е минал все още
                // =============================================================
                
                // Свойства (9) и нотификации (много + 1)
                $rec->case = "1.9.";
                
                // $rec->timeStart
                $rec->timeLastRepeat         = $repeatTime['last'];
                $rec->timeNextRepeat         = $repeatTime['next'];
                $rec->lastRepeatStillActive  = $repeatTime['lastRepeatStillActive'];
                
                $rec->state = 'active';
                $rec->activatedOn = dt::verbal2mysql();
                
                doc_Tasks::save($rec);
                
                // Изпращане на нотификация за стартирани и автоматично затворени задачи в миналото без последното стартиране
                $rec->timeStartsInThePastArr = $repeatTime['timeStartsInThePastArr'];
                doc_Tasks::sendNotificationForOldCycles($rec);
                
                // Нотификация за последното стартиране
                $rec->notificationMsg = "Активирано е повторение на задача" . " \"" . $rec->title . "\" на " . $rec->timeLastRepeat;
                doc_Tasks::sendNotification($rec);
                
                $rec->notificationSent = 'yes';
                
                doc_Tasks::save($rec);
            } else {
                // 1.10. Края на последния цикъл е минал, а следващото стартиране е в бъдещето
                // ===========================================================================
                
                // Свойства (8) и нотификации (много)
                $rec->case = "1.10.";
                
                // $rec->timeStart
                $rec->timeLastRepeat  = $repeatTime['last'];
                $rec->timeNextRepeat  = $repeatTime['next'];
                $rec->lastRepeatStillActive = $repeatTime['lastRepeatStillActive'];
                
                $rec->state = 'pending';
                $rec->activatedOn = dt::verbal2mysql();
                
                doc_Tasks::save($rec);
                
                // Изпращане на нотификация за стартирани и автоматично затворени цикли на задачата в миналото
                $rec->timeStartsInThePastArr = $repeatTime['timeStartsInThePastArr'];
                doc_Tasks::sendNotificationForOldCycles($rec);
                
                $rec->notificationSent = 'no';
                
                doc_Tasks::save($rec);
            }
        }
        
        bp($recBeforeActivation, $rec);
    }
    
 
    
    
    /**
     * Проверка за съотвествието между timeStart, timeDuration и executeTimeEnd
     */
    static function checkTaskDuration($rec)
    {
        if ($rec->timeStart && $rec->timeDuration && $rec->executeTimeEnd) {
            $tsTimeStart      = dt::mysql2timestamp($rec->timeStart);
            $tsTimeDuration   = type_Minutes::fromVerbal_($rec->timeDuration) * 60;
            $tsExecuteTimeEnd = dt::mysql2timestamp($rec->executeTimeEnd);
            
            if (($tsTimeStart + $tsTimeDuration) == $tsExecuteTimeEnd) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else return TRUE;
    }
    
    
    /**
     * Отваря тред на задачата
     *
     * @param stdClass $rec
     */
    function openThread($rec)
    {
        // Отваря треда
        $threadId = $rec->threadId;
        $recThread = doc_Threads::fetch($threadId);
        $recThread->state = 'opened';
        doc_Threads::save($recThread);
    }
    
    
    /**
     * Изпращане на нотификации за минали времена на стартиране на задачата
     *
     * Изпраащат се една или повече нотификации. Задейства се при първоначално активиране на задача, която:
     * - има повторения
     * - всяко повторение няма край
     * - задачата първоначално се активира след няколко преминали цикли
     * За тези преминали цикли в миналото ще бъдат изпратени нотификации.
     *
     * @param stdClass $rec
     */
    function sendNotificationForOldCycles($rec)
    {
        // Изпращане на нотификации за минали активации на задача
        if ($rec->timeStartsInThePastArr && $rec->repeat != 'none') {
            $url      = array('doc_Tasks', 'single', $rec->id);
            $priority = 'normal';
            $usersArr = type_Keylist::toArray($rec->members);
            
            $timeStartsInThePastArr = $rec->timeStartsInThePastArr;
            
            // Цикъл за всяко стартиране в миналото
            foreach ($timeStartsInThePastArr as $timeOldStart) {
                $msg = "Минало стартиране на задача \"" . $rec->title . "\" на дата " . $timeOldStart;
                
                // Цикъл за всеки потребител, който е в 'members' на задачата
                foreach($usersArr as $userId) {
                    // Изпращане на нотификацията
                    print "<br/>" . $msg . "<br/>";
                    
                    // bgerp_Notifications::add($msg, url, $userId, priority);
                }
            }
        }
    }
    
    
    /**
     * Изпращане на нотификация
     *
     * Изпраща се една нотификация до всеки отговорник на задачата.
     *
     * @param stdClass $rec
     */
    function sendNotification($rec)
    {
        $url      = array('doc_Tasks', 'single', $rec->id);
        $priority = 'normal';
        $usersArr = type_Keylist::toArray($rec->members);
        $msg      = $rec->notificationMsg;
        
        // Цикъл за всеки потребител, който е в 'members' на задачата
        foreach($usersArr as $userId) {
            // Изпращане на нотификацията
            print "<br/>" . $msg . "<br/>";
            
            // bgerp_Notifications::add($msg, url, $userId, priority);
        }
    }
}