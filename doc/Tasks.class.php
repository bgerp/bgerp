<?php
/**
 * Клас 'doc_Tasks' - Документ - задача
 */
class doc_Tasks extends core_Master
{   
    /**
     * Поддържани интерфейси
     */
    var $interfaces  = 'doc_DocumentIntf';	
	
    var $loadList    = 'plg_RowTools, doc_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';

    var $title       = "Задачи";

    var $singleTitle = "Задача";

    var $listFields  = 'id, title, timeStart=Начало, repeat, responsables, timeNextRepeat';
    

    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    

    /**
     * Права
     */
    var $canRead = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,doc';    
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/sheduled-task-icon.png';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutTasks.html';    

    
    /**
     * Абривиатура
     */
    var $abbr = "TSK";

     
    function description()
    {    	
    	$this->FLD('title',        'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority',     'enum(low=нисък,
                                         normal=нормален,
                                         high=висок,
                                         critical=критичен)', 'caption=Приоритет,mandatory,value=normal,maxRadio=4,columns=4');  
    	$this->FLD('details',      'richtext',    'caption=Описание,mandatory');
    	$this->FLD('responsables', 'keylist(mvc=core_Users,select=names)', 'caption=Отговорници,mandatory');
    	
    	$this->FLD('timeStart',            'datetime',     'caption=Времена->Начало,mandatory');
    	$this->FLD('timeDuration',         'varchar(64)',  'caption=Времена->Продължителност');
    	$this->FLD('timeEnd',              'datetime',     'caption=Времена->Край');
    	
        $this->FLD('timeNextRepeat',       'datetime',     'caption=Следващо повторение,input=none,mandatory');
        $this->FLD('notificationSent',     'enum(yes,no)', 'caption=Изпратена нотификация,mandatory,input=none');
    	
    	$this->FLD('repeat',       'enum(none=няма,
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
                                         -5=5 мин. предварително,
                                         -10=10 мин. предварително,
                                         -30=30 мин. предварително,
                                         -60=1 час предварително,
                                         -120=2 час предварително,
                                         -480=8 часа предварително,
                                         -1440=1 ден предварително,
                                         -2880=2 дни предварително,
                                         -4320=3 дни предварително,
                                         -10080=7 дни предварително)', 'caption=Времена->Напомняне,mandatory');

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
		$row->author =  $this->getVerbal($rec, 'createdBy');
		
		//Състояние
        $row->state  = $rec->state;
		
        //id на създателя
        $row->authorId = $rec->createdBy;
        
		return $row;
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
        $tsNow            = time();
        $tsTimeStart      = dt::mysql2timestamp($timeStart);
        $tsRepeatInterval = doc_Tasks::repeat2timestamp($repeatInterval);
        
    	if ($rec->repeat == 'none') {
            return $rec->timeStart;
        } else {
        	$tsTimeNextRepeat = $tsTimeStart;
        	
        	// Изчисляване без добавяне на секундите на повторението, а с манипулации с календарната дата
        	$year  = substr($timeStart, 0, 4);
        	$month = (int) substr($timeStart, 5, 2);
        	$day   = (int) substr($timeStart, 8, 2);
        	$time  = substr($timeStart, 11,8);
        	   
        	switch ($repeatInterval) {
        	    case "everyDay":
        	   	case "everyTwoDays":
        	   	case "everyThreeDays":
        	   	case "everyWeek":
        	        // Изчисляване с добавяне на секундите на повторението
                    while ($tsTimeNextRepeat < $tsNow) {
                        $tsTimeNextRepeat += $tsRepeatInterval;
                    }
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

    
    /* Помощен метод за метода calcNextRepeat()
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
        	$year  += floor($monthStep / 12); 
            $month += $monthStep % 12;
            
            if ($month > 12) {
            	$year += 1;
                $month = $month - 12;
            }
                           
            $month = sprintf("%02d", $month);
            $day   = sprintf("%02d", $day);
                   
            while (checkdate($month, $day, $year) === FALSE) {
                // Минус един ден
                $day -= 1;
            }
                   
            $timeNextRepeat = $year . "-" . $month  . "-" . $day . " " . $time;
       
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
        if ($rec->repeat == 'none' XOR $rec->state == 'closed') {
           $row->timeNextRepeat = NULL;
        }   
    }    
    
    
    /**
     * Задачи по Cron
     */
    function cron_ManageTasks()
    {
    	$queryTasks = doc_Tasks::getQuery();
    	
    	// #1 - Смяна статуса от 'draft' на 'pending' 30 мин. след създаване на задачата
    	$expiredOn = date('Y-m-d H:i:s', time() - 30*60);
    	$where = "#state = 'draft' AND #createdOn < '{$expiredOn}'";
	    	
        while($recTasks = $queryTasks->fetch($where)) {
            $recTasks->state = 'pending';
            doc_Tasks::save($recTasks);    
        }
        // ENDOF #1 - Смяна статуса от 'draft' на 'pending' 30 мин. след създаване на задачата
        
        // #2 Старт на задачите
        $now = date('Y-m-d H:i:s', time());
  
        $where = "#timeNextRepeat =< '{$now}' AND #state = 'pending'";
	                
        while($recTasks = $queryTasks->fetch($where)) {
            // Смяна state на 'active'
            $recTasks->state = 'active';
                
            // Изчислява следващия 'timeNextRepeat'
            $recTasks->timeNextRepeat = doc_Tasks::calcNextRepeat($recTasks->timeNextRepeat, $recTasks->repeat);            	
                
            doc_Tasks::save($recTasks);

            // Отваря треда
            $threadId = $recTasks->threadId;
            $recThread = doc_Threads::fetch($threadId);
            $recThread->state = 'open';
            doc_Threads::save($recThread);
        }            
        // ENDOF #2 Старт на задачите 

        // #3 Нотификация на задачите
        $where = "#state = 'pending' AND #notificationSent = 'no'";
            
        while($recTasks = $queryTasks->fetch($where)) {
          	$tsNow = time();
           	$tsNotificationBefore = $this->notification2timestamp($rec->notification) * (-1);
           	$tsTimeNextRepeat = dt::mysql2timestamp($recTasks->timeNextRepeat);
            	
           	if (($tsTimeNextRepeat - $tsNow) < $tsNotificationBefore) {
           	   $msg = "Остават по-малко от " . ($tsNotification / 60) . "минути до начало на задача " . $recTasks->title;	
            	   
           	   /*
           	   $url = "";
           	   $userId = core_Users::getCurrent();
           	   $priority = $recTasks->priority;
            	   
           	   // Изпращане
           	   bgerp_Notifications::add($msg, $url, $userId, $priority);
            	   
           	   // Маркер, че нотификацията е изпратена
               $recTasks->notificationSent = 'yes';
               doc_Tasks::save($recTasks); 
           	   */
           	}
        }            
        // #3 ENDOF Нотификация на задачите
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId    = 'Tasks - change state, start, notify';
        $rec->description = "Задачи - смяна статус, стартиране, нотификация";
        $rec->controller  = $mvc->className;
        $rec->action      = 'ManageTasks';
        $rec->period      = 300;
        $rec->offset      = 0;
        $rec->delay       = 0;
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

}