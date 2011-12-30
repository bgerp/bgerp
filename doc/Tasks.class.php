<?php
/**
 * Клас 'doc_Tasks' - Документ - задача
 */
class doc_Tasks extends core_Master
{   
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';	
	
    var $loadList = 'plg_Created, plg_RowTools, doc_Wrapper, plg_State, doc_DocumentPlg';

    var $title    = "Задачи";

    var $listFields = 'id, title, timeStart=Начало, responsables, timeNextRepeat';
    

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
    	                                 everyMonthy=всеки месец,
    	                                 everyThreeMonths=на всеки 3 месеца,
    	                                 everySixMonths=на всяко полугодие,
    	                                 everyYear=всяка година)', 'caption=Времена->Повторение,mandatory');
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
     * При нов запис state е draft 
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc,&$id,$rec)
    {
        if (!isset($rec->id)) {
            $rec->state = 'draft';
            
        }
    }
    
    /**
     * Calculate next repeat time
     * 
     * @param int $taskId
     * @return string $timeNextRepeat
     */
    function calcNextRepeat($taskId)
    {
        $queryTasks = doc_Tasks::getQuery();
        $where = "#id = {$taskId}";
        $queryTasks->limit(1);
        
        while($rec = $queryTasks->fetch($where)) {
            $tsTimeStart = dt::mysql2timestamp($rec->timeStart);
        }

        $tsNow = time();
        $tsTimeNextRepeat = $tsTimeStart;
        $delay = $tsTimeNextRepeat - $tsNow;
            
        if ($delay < 0) {
          	$repeat = 604800; // 1 седмица
            	
		    do {
	            $tsTimeNextRepeat += $repeat;
		    } while (($tsTimeNextRepeat - $tsNow) < 0);
        }
            
        $timeNextRepeat = date('Y-m-d H:i:s', $tsTimeNextRepeat);
        
        return $timeNextRepeat;    
    }
    
    
    /**
     * 
     */
    function act_ActivateTask()
    {
        $queryTasks = doc_Tasks::getQuery();
        $now = date('Y-m-d H:i:s', time());
        $where = "#state = 'pending' AND #timeStart<'{$now}'";
        
        while($recTasks = $queryTasks->fetch($where)) {
        	$rec->state = 'active';
        	doc_Tasks::save($rec);
        }
    }
    
    
    /**
     * Сменя state в doc_Tasks 30 мин. след като е създадена задачата
     */
    function cron_SetTasksFromDraftToPending()
    {
    	$queryTasks = doc_Tasks::getQuery();
    	$expiredOn = date('Y-m-d H:i:s', time() - 30*60);
    	$where = "#state = 'draft' AND #createdOn<'{$expiredOn}'";
    	
        while($recTasks = $queryTasks->fetch($where)) {
            $recTasks->state = 'pending';
            doc_Tasks::save($recTasks);    
        }
    }
    
    
    /**
     * Изпраща нотификации за задачите
     */
    function cron_NotifyAboutPendingTasks()
    {
    	// bgerp_Notifications::add($msg, $url, $userId, $priority);
    	
    }    
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId    = 'SetTasksFromDraftToPending';
        $rec->description = "Смяна статуса на задачите от 'draft' на 'active'";
        $rec->controller  = $mvc->className;
        $rec->action      = 'SetTasksFromDraftToPending';
        $rec->period      = 300;
        $rec->offset      = 0;
        $rec->delay       = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        // $Cron::delete(30);

        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сменя статуса на задачите от 'draft' на 'pending'.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сменя статуса на задачите от 'draft' на 'active'.</li>";
        }
        
        return $res;
    }

    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране 
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm_($mvc, $res, $data)
    {
    	// По подразбиране за нов запис
        if (!$data->form->rec->id) {
            
            $data->form->setField('timeStart', 'input=none');

        }
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
    	$row->timeNextRepeat = $mvc->calcNextRepeat($rec->id);
    }    

}