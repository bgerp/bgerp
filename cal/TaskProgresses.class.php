<?php


/**
 * Клас 'cal_TaskProgresses'
 * 
 * @title Отчитане изпълнението на задачите
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_TaskProgresses extends core_Detail
{
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';

     
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Created,cal_Wrapper';


    /**
     * Заглавие
     */
    public $title = "Прогрес по задачите";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn,createdBy,message,progress,workingTime,expectationTime';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task.png';

    
    /**
     * Икона за единичния изглед
     */
    public $singleTitle = 'прогрес';
    
    
    /**
     * 
     * @var unknown
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
   
         
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // id на задачата
        $this->FLD('taskId', 'key(mvc=cal_Tasks,select=title)', 'caption=Задача,input=hidden,silent,column=none');
       
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес');

        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time(suggestions=10 мин.|30 мин.|60 мин.|2 часа|3 часа|5 часа|10 часа)',     'caption=Отработено време');
        
        // Очакван край на задачата
        $this->FLD('expectationTimeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 
            'caption=Очакван край, silent');
        
        // Статус съобщение
        $this->FLD('message',    'richtext(rows=5, bucket=calTasks)', 'caption=Съобщение');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {   
    	expect($data->form->rec->taskId);

        $masterRec = cal_Tasks::fetch($data->form->rec->taskId);
        $progressArr[''] = '';

        for($i = 0; $i <= 100; $i += 10) {
            if($masterRec->progress > ($i/100)) continue;
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        
        if ($masterRec->progress) {
        	$data->form->setDefault('progress', $masterRec->progress);
        }
        
        if ($masterRec->workingTime) {
        	$data->form->setDefault('workingTime', $masterRec->workingTime);
        }
        
        $data->form->setSuggestions('progress', $progressArr);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	expect($form->rec->taskId);
    	
    	$masterRec = cal_Tasks::fetch($form->rec->taskId);
    	
    	// ако формата е събмитната
    	if ($form->isSubmitted() && isset($masterRec->progress)){
        	if ($masterRec->progress > $form->rec->progress) {
        		$form->setWarning('progress', "|Въвели сте по-малък прогрес от предишния. Сигурни ли сте, че искате да продължите?");
        	} elseif ($masterRec->progress == $form->rec->progress) {
        		$form->setWarning('progress', "|Въвели сте прогрес равен на предишния. Сигурни ли сте, че искате да продължите?");
        	}
    	}
    }


    /**
     * Изпълнява се след опаковане на детайла от мениджъра
     * 
     * @param stdClass $data
     */
    function renderDetail($data)
    {
        if(!count($data->recs)) {
            return NULL;
        }
    	
        $tpl = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black">Прогрес</div>
                            <div class="listRows">
                            [#TABLE#]
                            </div>
	                   </div>
	                ');
	        $tpl->replace($this->renderListTable($data), 'TABLE');
		
        return $tpl;
    }
    
    
    /**
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if (count($data->recs)) {
    		foreach ($data->rows as $row) {
    			if ($row->expectationTimeEnd !== '') {
    				$row->expectationTimeEnd = '';
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
    static function on_AfterRenderListTable($mvc, &$res, $data)
	{
        if(!count($data->recs)) {
            return NULL;
        }
        
    	if(Mode::is('screenMode', 'narrow')){
			$res = new ET(' <table class="listTable progress-table"> 
									<!--ET_BEGIN COMMENT_LI-->
										<tr>
	                               			<td>
												<span class="nowrap">[#createdOn#]</span>&nbsp;	
												[#createdBy#]&nbsp; 
												[#progress#]&nbsp;
												<span class="nowrap">[#workingTime#]</span> 
												
											</td>
											<td>
												[#message#]
											</td>
										</tr>
										
									<!--ET_END COMMENT_LI-->
								</table>
                ');
			
			foreach($data->recs as $rec){
				
				$row = $mvc->recToVerbal($rec);
				
				$cTpl = $res->getBlock("COMMENT_LI");
				$cTpl->placeObject($row);
				$cTpl->removeBlocks();
				$cTpl->append2master();
			}
    	}
    }

    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $tRec = cal_Tasks::fetch($rec->taskId);
        $now = dt::now();
        // Определяне на прогреса
        if(isset($rec->progress)) {
            $tRec->progress = $rec->progress;
            
            if($rec->progress == 1) {
            	$message = "|Приключена е задачата|*" . ' "' . $tRec->title . '"';
            	$url = array('doc_Containers', 'list', 'threadId' => $tRec->threadId);
            	$customUrl = array('cal_Tasks', 'single',  $tRec->id);
            	$priority = 'normal';
            	bgerp_Notifications::add($message, $url, $tRec->createdBy, $priority, $customUrl);
                $tRec->state = 'closed';
                $tRec->timeClosed = $now;
            }
        }
        
        // Определяне на отработеното време
        if(isset($rec->workingTime)) {
            $query = self::getQuery();
            $query->where("#taskId = {$tRec->id}");
            $query->XPR('workingTimeSum', 'int', 'sum(#workingTime)');
            $rec = $query->fetch();
            $tRec->workingTime = $rec->workingTimeSum;
        }
        
        $sharedUsersArr = rtac_Plugin::getNicksArr($rec->message);
        
        // Ако има споделяния
        if ($sharedUsersArr && !empty($sharedUsersArr)) {
            
            // Добавяме id-тата на споделените потребители
            foreach ((array)$sharedUsersArr as $nick) {
                $nick = strtolower($nick);
                $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');
                $tRec->sharedUsers = type_Keylist::addKey($tRec->sharedUsers, $id);
            }
            
            doc_Containers::changeNotifications($tRec, NULL, $tRec->sharedUsers);
        }
        
        cal_Tasks::save($tRec);
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
    	if($action == 'add' && isset($rec->taskId)){
    		if($requiredRoles == 'no_one') return;
    			
    		// Проверка дали потребителя има достъп до задачата и дали е в позволено състояние за добавяне на прогрес
    		$taskState = cal_Tasks::fetchField($rec->taskId, 'state');
    		if($taskState != 'active' && $taskState != 'waiting' && $taskState != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} elseif(!cal_Tasks::haveRightFor('single', $rec->taskId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
	
	
	/**
	 * 
	 * 
	 * @param stdObject $data
	 */
	public function prepareDetail_($data)
	{
		$data->TabCaption = 'Прогрес';
		$data->Tab = 'top';
		
		$res = parent::prepareDetail_($data);
		
		if (empty($data->recs)) {
		    $data->disabled = TRUE;
		}
		
		return $res;
	}
}
