<?php
/**
 * Клас 'doc_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Tasks extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, doc_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
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
    var $listFields = 'id, title, timeStart=Начало, repeat, responsables, timeNextRepeat';
    
    
    
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
     * Кой има право да прикючава?
     */
    var $canChangeTaskState = 'admin, doc';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/sheduled-task-icon.png';
    
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutTasks.shtml';
    
    
    
    /**
     * Абривиатура
     */
    var $abbr = "TSK";
    
    
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=нисък,
                                         normal=нормален,
                                         high=висок,
                                         critical=критичен)', 'caption=Приоритет,mandatory,value=normal,maxRadio=4,columns=4');
        $this->FLD('details', 'richtext', 'caption=Описание,mandatory');
        $this->FLD('responsables', 'keylist(mvc=core_Users,select=names)', 'caption=Отговорници,mandatory');
        
        $this->FLD('timeStart', 'datetime', 'caption=Времена->Начало,mandatory');
        $this->FLD('timeDuration', 'varchar(64)', 'caption=Времена->Продължителност');
        $this->FLD('timeEnd', 'datetime', 'caption=Времена->Край');
        
        $this->FLD('timeNextRepeat', 'datetime', 'caption=Следващо повторение,input=none,mandatory');
        $this->FLD('notificationSent', 'enum(yes,no)', 'caption=Изпратена нотификация,mandatory,input=none');
        
        $this->FLD('repeat', 'enum(none=няма,
                                         everyDay=всеки ден,
                                         everyTwoDays=на всеки 2 дена,
                                         everyThreeDays=на всеки 3 дена,
                                         everyWeek=всяка седмица,
                                         everyMonth=всеки месец,
                                         everyThreeMonths=на всеки 3 месеца,
                                         everySixMonths=на всяко полугодие,
                                         everyYear=всяка година,
                                         everyTwoYears=всяки две години,
                                         everyFiveYears=всяки пет години)', 'caption=Времена->Повторение,mandatory');
        
        $this->FLD('notification', 'enum(0=на момента,
                                         5=5 мин. предварително,
                                         10=10 мин. предварително,
                                         30=30 мин. предварително,
                                         60=1 час предварително,
                                         120=2 час предварително,
                                         480=8 часа предварително,
                                         1440=1 ден предварително,
                                         2880=2 дни предварително,
                                         4320=3 дни предварително,
                                         10080=7 дни предварително)', 'caption=Времена->Напомняне,mandatory');
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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // За метода 'act_ChangeTaskState' има права, само ако потребитела е сред отговорниците на задачата 
        if ($rec->id && ($action == 'changetaskstate')) {
            $rec = $mvc->fetch($rec->id);

            $cu = core_Users::getCurrent();
            
            if (!type_Keylist::isIn($cu, $rec->responsables)) {
                $requiredRoles = 'no_one';
            }    
        }
    }    
    
    
    /**
     * Изчислява следващото време за повторение
     *
     * @param string $timeStart       MySQL datetime format
     * @param string $repeatInterval  Verbal word
     * @return string $timeNextRepeat MySQL datetime format
     */
    function calcNextRepeat($timeStart, $repeatInterval)
    {
        $tsNow = time();
        $tsTimeStart = dt::mysql2timestamp($timeStart);
        $tsRepeatInterval = doc_Tasks::repeat2timestamp($repeatInterval);
        
        if ($repeatInterval == 'none') {
            return $timeStart;
        } else {
            $tsTimeNextRepeat = $tsTimeStart;
            
            // Изчисляване без добавяне на секундите на повторението, а с манипулации с календарната дата
            $year = substr($timeStart, 0, 4);
            $month = (int) substr($timeStart, 5, 2);
            $day = (int) substr($timeStart, 8, 2);
            $time = substr($timeStart, 11,8);
            
            switch ($repeatInterval) {
                case "everyDay":
                case "everyTwoDays":
                case "everyThreeDays":
                case "everyWeek":
                    // Изчисляване с добавяне на секундите на повторението
                    while ($tsTimeNextRepeat < $tsNow) {
                        $tsTimeNextRepeat += $tsRepeatInterval;
                    }
                    
                    $timeNextRepeat = dt::timestamp2mysql($tsTimeNextRepeat);
                    break;
                
                case "everyMonth":
                    $monthStep = 1;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
                
                case "everyThreeMonths":
                    $monthStep = 3;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
                
                case "everySixMonths":
                    $monthStep = 6;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
                
                case "everyYear":
                    $monthStep = 12;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
                
                case "everyTwoYears":
                    $monthStep = 24;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
                
                case "everyFiveYears":
                    $monthStep = 60;
                    $timeNextRepeat = doc_Tasks::repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep);
                    break;
            }
            
            return $timeNextRepeat;
        }
    }


/**
 * Помощен метод за метода calcNextRepeat()
 *
 * @param int $tsTimeNextRepeat
 * @param int $tsNow
 * @param string $year
 * @param string $month
 * @param string $day
 * @param int $monthStep
 * @return string $timeNextRepeat
 */
function repeatTimeWhile($tsTimeNextRepeat, $tsNow, $year, $month, $day, $time, $monthStep)
{
    while ($tsTimeNextRepeat < $tsNow) {
        $year += floor($monthStep / 12);
        $month += $monthStep % 12;
        
        if ($month > 12) {
            $year += 1;
            $month = $month - 12;
        }
        
        $month = sprintf("%02d", $month);
        $day = sprintf("%02d", $day);
        
        while (checkdate($month, $day, $year) === FALSE) {
            // Минус един ден
            $day -= 1;
        }
        
        $timeNextRepeat = $year . "-" . $month . "-" . $day . " " . $time;
        
        return $timeNextRepeat;
    }
}



/**
 * При нов запис дава стойност на $rec->timeNextRepeat
 *
 * @param core_Mvc $mvc
 * @param int $id
 * @param stdClass $rec
 */
function on_BeforeSave($mvc,&$id,$rec)
{
    $rec->timeNextRepeat = doc_Tasks::calcNextRepeat($rec->timeStart, $rec->repeat);
    
    if ($rec->state == 'active') {
        if($rec->timeNextRepeat > dt::verbal2mysql()) {
            $rec->state = 'pending';
        }
    }
    
    if ($rec->state == 'draft') {
        $rec->notificationSent = 'no';
    }
}



/**
 * Калкулира времето за нотификация в секунди
 *
 * @param string $notification
 * @return int $notificationSecs
 */
function notification2timestamp($notification)
{
    $notificationMins = (int) $notification;
    $notificationSecs = $notificationMins * 60;
    
    return $notificationSecs;
}



/**
 * Калкулира времето за повторение от string в секунди
 *
 * @param string $repeat
 * @return int $repeatSecs
 */
function repeat2timestamp($repeat)
{
    switch ($repeat) {
        case "none":
            $repeatSecs = 0;
            break;
        case "everyDay":
            $repeatSecs = 60*60*24;
            break;
        case "everyTwoDays":
            $repeatSecs = 60*60*24*2;
            break;
        case "everyThreeDays":
            $repeatSecs = 60*60*24*3;
            break;
        case "everyWeek":
            $repeatSecs = 60*60*24*7;
            break;
    }
    
    return $repeatSecs;
}



/**
 * Визуализация на задачите
 *
 * @param core_Mvc $mvc
 * @param stdClass $row
 * @param stdClass $rec
 */
function on_AfterRecToVerbal($mvc, $row, $rec)
{
    /*
        if ($rec->repeat == 'none' OR $rec->state == 'closed') {
           $row->timeNextRepeat = NULL;
        }
        */
}


/**
 * Задачи по Cron
 * 1. Нотификация
 * 2. Старт на задачите
 */
// function cron_ManageTasks()
function act_M()
{
    // #1 Нотификация на задачите
    $queryTasks = doc_Tasks::getQuery();
    $where = "#state = 'pending' AND #notificationSent = 'no' AND DATE_ADD(NOW(), INTERVAL #notification MINUTE) > #timeNextRepeat";
    
    while($recTasks = $queryTasks->fetch($where)) {
        // Датата и часът на изпълнение на задачата (без секундите)
        $taskDate = substr($recTasks->timeNextRepeat, 0, 10);
        $taskTime = substr($recTasks->timeNextRepeat, 11, 5);
        
        $msg = "Предстояща задача '" . $recTasks->title . "' на " . $taskDate . " в " . $taskTime . " ч";
        $url = array('doc_Tasks', 'single', $recTasks->id);
        $priority = 'normal';
        
        // $userId = core_Users::getCurrent();
        $usersArr = type_Keylist::toArray($recTasks->responsables);
        
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
    $where = "#timeNextRepeat <= NOW() AND #state = 'pending'";
    
    while($recTasks = $queryTasks->fetch($where)) {
        // Смяна state на 'active'
        $recTasks->state = 'active';
        doc_Tasks::save($recTasks);
        
        // Отваря треда
        $threadId = $recTasks->threadId;
        $recThread = doc_Threads::fetch($threadId);
        $recThread->state = 'open';
        doc_Threads::save($recThread);
    }
    
    unset($queryTasks, $where, $recTasks);
    // ENDOF #2 Старт на задачите 
}



	/**
	 * Изпълнява се след създаването на модела
	 */
	function on_AfterSetupMVC($mvc, $res)
	{
	    $res .= "<p><i>Нагласяне на Cron</i></p>";
	    
	    $rec->systemId = 'Tasks - change state, start, notify';
	    $rec->description = "Задачи - смяна статус, стартиране, нотификация";
	    $rec->controller = $mvc->className;
	    $rec->action = 'ManageTasks';
	    $rec->period = 300;
	    $rec->offset = 0;
	    $rec->delay = 0;
	    // $rec->timeLimit = 200;
	    
	    $Cron = cls::get('core_Cron');
	    
	    // $Cron::delete(30);
	    
	    if ($Cron->addOnce($rec)) {
	        $res .= "<li><font color='green'>1. Задачи - смяна статуса от 'draft' на 'pending'
	                                             30 минути след създаване на нова задача
	                                             <br/>
	                                             2. Задачи - автоматично стартиране
	                                             <br/>
	                                             3. Задачи - автоматично изпращане на нотификации</font></li>";
	    } else {
	        $res .= "<li>Отпреди Cron е бил нагласен за
	                         <br/>
	                         1. Задачи - смяна статуса от 'draft' на 'pending'
	                         30 минути след създаване на нова задача
	                         <br/>
	                         2. Задачи - автоматично стартиране
	                         <br/>
	                         3. Задачи - автоматично изпращане на нотификации</li>";
	    }
	    
	    return $res;
	}
	
	
    /**
     * Добавя бутон 'Приключване' на single view-то.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	$rec = $data->rec;
    	$cu = core_Users::getCurrent();
    	
    	if ($rec->state == 'active' || $rec->state == 'pending') {
    	    // Ако потребитела е сред отговорниците на задачата, има бутон да я приключва
            if ($mvc->haveRightFor('changeTaskState', $rec)) {
                $finalizeUrl = array('doc_Tasks', 'changeTaskState', $rec->id); 
                $data->toolbar->addBtn('Приключване', $finalizeUrl, 'id=closeTask,class=btn-task-close');            
            }
        }    	
    }

    
    /**
     * Приключване на задача (затваряне/презареждане) 
     */
    function act_ChangeTaskState()
    {
        $taskId  = Request::get('id', 'int');
        $recTask = doc_Tasks::fetch($taskId);
        
        // Форма
        $form = cls::get('core_form');
        $form->title = "Приключване на задачата '" . $recTask->title . "'";

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
                                   everyTwoYears=всяки две години,
                                   everyFiveYears=всяки пет години)', 'caption=Времена->Повторение,mandatory');
        $form->setDefault('repeat', $recTask->repeat);        
        
        $form->view = 'vertical';
        $form->showFields = 'timeStart, repeat';
        
        // Бутон 'Затваряне'
        $closeUrl = array('doc_Tasks', 'closeTask', $recTask->id);
        $form->toolbar->addBtn('Затваряне', $closeUrl, 'id=closeTask,class=btn-close,warning=Наистина ли желаете задачата да бъде приключена?');
        
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
            $rec->timeNextRepeat = doc_Tasks::calcNextRepeat($rec->timeStart, $rec->repeat);
            
            // Валидация
            $tsTimeStart = dt::mysql2timestamp($rec->timeStart);
            
            if ($tsTimeStart == FALSE) {
                $form->setError('timeStart', 'Моля, коригирайте новото време <br/>за старт на задачата');
                return $this->renderWrapping($form->renderHtml());
            } else {
                $recTask->timeStart        = $rec->timeStart;
                $recTask->repeat           = $rec->repeat;
                $recTask->timeNextRepeat   = $rec->timeNextRepeat;
                $recTask->notificationSent = 'no';
                $recTask->state            = 'pending';

                doc_Tasks::save($recTask);
                
                return new Redirect(array($this, 'single', $taskId));
            }
        } else {
            return $this->renderWrapping($form->renderHtml());
        }          
        
        return $this->renderWrapping($form->renderHtml());
    }    
    
    
    /**
     * Затваряне на задачата
     */
    function act_CloseTask()
    {
        $taskId = Request::get('id', 'int');
        $recTask = doc_Tasks::fetch($taskId);
        $recTask->state = 'closed';
        
        doc_Tasks::save($recTask);
        
        return new Redirect(array($this, 'single', $taskId));
    }
    
}