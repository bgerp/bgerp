<?php


/**
 * Клас 'cal_Reminders' - Документ - напомняне
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Reminders extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cal_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, doc_SharablePlg';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'timeStart';


    /**
     * Заглавие
     */
    var $title = "Напомняния";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Напомняне";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, description, timeStart, timePreviously, repetitionEach, repetitionType, repetitionAbidance, action, nextStartTime, sharedUsers';
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'description';

    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
 
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'powerUser';

    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'powerUser';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'powerUser';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/reminders.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutReminders.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Rem";
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.5|Общи"; 
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=нисък,
                                     normal=нормален,
                                     high=висок,
                                     alarm=аларма)', 
            'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal');
        
        $this->FLD('description', 'richtext(bucket=calReminders)', 'caption=Описание');

        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Споделяне,mandatory');
        
        // Какво ще е действието на известието?
        $this->FLD('action', 'enum(threadOpen=Отваряне на нишката,
        						   notify=Нотификация,
        						   notifyNoAns=Нотификация-ако няма отговор,
        						   replicateDraft=Чернова-копие на темата,
        						   replicate=Копие на темата)', 'caption=Действие, mandatory,maxRadio=5,columns=1,notNull,value=notify');
        
        // Начало на напомнянето
        $this->FLD('timeStart', 'datetime', 'caption=Време->Начало, silent');
        
        // Предварително напомняне
        $this->FLD('timePreviously', 'time', 'caption=Време->Предварително');
        
        // Колко пъти ще се повтаря напомнянето?
        $this->FLD('repetitionEach', 'int',     'caption=Повторение->Всеки');
        
        // По какво ще се повтаря напомненето - дни, седмици, месеци, години
        $this->FLD('repetitionType', 'enum(0=,
        									  days=дена,
			                                  weeks=седмици,
			                                  months=месецa,
			                                  years=години)',  
           'caption=Повторение->Мярка');
        
        // По какво ще се повтаря напомненето - ден от нач. наседмицата, от нач, намесеца и т.н
        $this->FLD('repetitionAbidance', 'enum(0=,
        									   weekDay=Ден от началото на седмицата,
        									   monthDay=Ден от началото на месеца,
        									   monthDayEnd=Ден от края на месеца)',  
           'caption=Повторение->Съблюдаване');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
        
        // Кога е следващото стартирване на напомнянето?
        $this->FLD('nextStartTime', 'datetime', 'caption=Следващо напомняне,input=none');
        
       

    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
        $data->form->setDefault('priority', 'normal');
        $data->form->setDefault('sharedUsers', "|".$cu."|");

        $rec = $data->form->rec;

        
    }


    /**
     * Подготвяне на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
      
    }



    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;

    }
    

    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {

        $rec->nextStartTime = $mvc->calcNextStartTime($rec);
    }


    /**
     *
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	
    }


    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {        
 
    }

    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {

    	if($action == 'postpone'){
	    	if ($rec->id) {
	        	if ($rec->state !== 'active' || (!$rec->timeStart) ) { 
	                $requiredRoles = 'no_one';
	            }  else {
	                if(!haveRole('ceo') || ($userId !== $rec->createdBy) &&
	                !type_Keylist::isIn($userId, $rec->sharedUsers)) {
	                	
	                	$requiredRoles = 'no_one';
	                }
	            }
    	     }
         }
    }
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	
        
    	$userId = core_Users::getCurrent();
        $data->query->orderBy("#timeStart=ASC,#state=DESC");
        
                
        if($data->listFilter->rec->selectedUsers) {
	           
	         if($data->listFilter->rec->selectedUsers != 'all_users') {
	                $data->query->likeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
	               
	           }
            	
        } 
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
  
        
        // Добавяме поле във формата за търсене
       
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
                
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers';
        
        $data->listFilter->input('selectedUsers', 'silent');
    }

   

    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    static function getNumbPriority($rec)
    {
        if($rec->state == 'active') {

            switch($rec->priority) {
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
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        //$rec = self::fetch($id);

        //return "img/16/task-" . $rec->priority . ".png";
    }



    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SendNotifications()
    {
    	
        $now = dt::verbal2mysql();
       
        self::doReminderingForActiveRecs();

    }

    static public function doReminderingForActiveRecs()
    {
    	 $now = dt::verbal2mysql();
    	 $query = self::getQuery();
    	 $query->where("#state = 'active' AND #nextStartTime <= '{$now}'");
    	 
    	 while($rec = $query->fetch()){
             
    	 	 $rec->message  = "Напомняне \"" . self::getVerbal($rec, 'title') . "\"";
    	 	 $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
    	 	 $rec->customUrl = array('cal_Reminders', 'single',  $rec->id);
    	 	 self:: doUsefullyPerformance($rec);

             self::save($rec);

    	 }

    }
    
    
    static public function doUsefullyPerformance($rec)
    {   
    	$subscribedArr = type_Keylist::toArray($rec->sharedUsers); 
		if(count($subscribedArr)) { 
			foreach($subscribedArr as $userId) {  
				if($userId > 0  && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
					switch($rec->action){
						case 'notify':
							bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
						break;
						
						case 'threadOpen':
							doc_Threads::save((object)array('id'=>$rec->threadId, 'state'=>'opened'), 'state');
							bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
						break;
						
						case 'notifyNoAns':
						break;
						
						case 'replicateDraft':
						break;
						
						case 'replicate':
						break;
					}
				}
			}
		}

    }
    
    /**
     * За тестове
     */
    static public function act_Test()
    {
    	$rec = new stdClass();
    	$rec->timeStart = '2013-03-30 18:10';
    	$rec->repetitionEach = 1;
    	$rec->repetitionType = 'months';
    	$rec->repetitionAbidance = 'weekDay';
    	
    	bp(self::calcNextStartTime($rec));
    	
    }
    
    
    /**
     *  Изчислява времето за следващото стартиране на напомнянето. Винаги е дата > от текущата
     */
    static public function calcNextStartTime($rec)
    {
    	$now = dt::verbal2mysql();
    	// Секундите на днешната дата
    	$nowTs = dt::mysql2timestamp($now);
    	
    	// Секундите на началната дата
        $startTs = dt::mysql2timestamp($rec->timeStart);
        
        if($rec->repetitionEach !== NULL ) {
	        // Типа на повторението е ден или седмица
	        if($rec->repetitionType == 'days' || $rec->repetitionType == 'weeks'){
	        	
	        	// Намираме интервала в секинди
		    	$intervalTs = self::getSecOfInterval($rec->repetitionEach, $rec->repetitionType);
		  
		    	// Изчисляваме следващата дата в секунди
		    	$nextStartTimeTs = (floor(($nowTs-$startTs)/$intervalTs) + 1)*$intervalTs;
		    	
		    	// Правим mySQL формат на новата дата
		    	$nextStartTime = date("Y-m-d H:i:s", $startTs + $nextStartTimeTs);
		    	
		    	return $nextStartTime;
	
		    	// Типа на повторението е месец
	        } elseif($rec->repetitionType == 'months'){
	        
	        	for ($i = 1; $i <= 10000; $i++){
	        		
	        	// Масив с час, сек, мин, ден, месец, год ... на Началната дата
	        	$data = getdate($startTs);
	        	
	        		// Новия месец който търсим е стария месец + ($i * повторението ни)
	        		$newMonth = $data[mon] + ($i * $rec->repetitionEach);
	        		
	        		// Секундите на новия месец
	        		$newMonthTs = mktime(0, 0, 0, $newMonth, 1, $data[year]);
	        		
	        		// Търсим съответствие по ден от месеца:
		        	if($rec->repetitionAbidance == 'monthDay'){
		        		
		        		// НАчалния ни ден
		        		$day = $data[mday];
		        		
		        		// Новия ни ден
		        		$newDay = 1 + ($day - 1);
		        		
		        		// Правим mySQL формат на датата от началните час, мин, сек и новия месец, новия ден и началната година
		        		$nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds], $newMonth, $newDay, $data[year]));
		        		
		        		// Проверяваме броя на дните в новия месец
		        		$numbMonthDay = date('t', $newMonthTs);
		        		
		        		// Ако новия ден не присъства в новия месец, то взимаме последния ден от новия месец
		        		if($newDay >= $numbMonthDay) $nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds], $newMonth, $numbMonthDay, $data[year]));
	                    
		        		if(dt::mysql2timestamp($nextStartTime) < $nowTs) continue;
		        		return $nextStartTime;
			        		
			        	} elseif($rec->repetitionAbidance == 'weekDay'){
			        		
			        		// Масив с дните от седмицата
			        		$weekDayNames = array(
					            1 => 'monday',
					            2 => 'tuesday',
					            3 => 'wednesday',
					            4 => 'thursday',
					            5 => 'friday',
					            6 => 'saturday',
					            0 => 'sunday');
					            
			        		// Броя на дните в месеца	
			        	    $numbMonthDay = date('t', $startTs);
			        	    
			        	    // Проверки за поредността на деня - 
			        	    // един ден от седмицата (напр. понеделник) може да има най-много 5 срещания
			        	    // в дадения месец
			        		if ($data[mday] - 7 >= -6 && $data[mday] - 7 <= 0) $monthsWeek = 'first';
			        		elseif($data[mday] - 14 >= -6 && $data[mday] - 14 <= 0) $monthsWeek = 'second'; 
			        		elseif($data[mday] - 21 >= -6 && $data[mday] - 21 <= 0) $monthsWeek = 'third'; 
			        		
			        		// Ако един ден е намерен за 3 път, проверяваме дали той не е и предпоследен
			        		if($data[mday] + 14 > $numbMonthDay && $monthsWeek = 'third') $monthsWeek = 'penultimate'; 
			        		
			        		// Ако един ден е намерен за предпоследен път, проверяваме дали той не е и последен
			        		if($data[mday] + 7 > $numbMonthDay && $monthsWeek == 'penultimate') $monthsWeek = 'last'; 
			        	
			        		// Вербалното име на деня, напр. first-monday, penultimate-wednesday
			        		$nextStartTimeName = $monthsWeek."-".$weekDayNames[$data[wday]];
			        		$nextStartTimeMonth = $newMonth;
			        		
			        		$nextStartTime = date("Y-m-d {$data[hours]}:{$data[minutes]}:{$data[seconds]}", dt::firstDayOfMounthTms($nextStartTimeMonth, $data[year], $nextStartTimeName));
			        		if(dt::mysql2timestamp($nextStartTime) < $nowTs) continue;
			        		
			        		return $nextStartTime;
			        		
			        	}
			        	
			        	
		        	}
	
	        }
        }
    	
    	
    }
    
    
    /**
     * По зададен брой пъти и тип (ден или сецмица) изчислява интервала в секунди
     * @param int $each
     * @param string $type = days/weeks
     */
    static public function getSecOfInterval($each, $type)
    {
    	if ($type !== 'days' || $type !== 'weeks') $intervalTs;
    	if ($type == 'days') $intervalTs = $each * 24 * 60 *60;
    	else $intervalTs = $each * 7 * 24 * 60 *60;
    	
    	return $intervalTs;
    }
    
    
    /**
     * Намира следващата дата със съответствие по ден от месеца
     * @param std Class $rec
     * @param int $each
     * @param string $type = months/years
     */
    static public function getNextDate($rec, $each, $type)
    {
    	$startTs = dt::mysql2timestamp($rec->timeStart);
    	$data = getdate($startTs);
    	
    	if ($type !== 'months' || $type !== 'years') $nextStartTime;
    	
    	if ($type == 'months') $nextStartTime = date("Y-m-d H:i:s",
    												  mktime($data[hours], $data[minutes], $data[seconds], $data[mon] + $each, $data[mday], $data[year]));
    												  
        else  $nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds], $data[mon] + ($each * 12), $data[mday], $data[year]));
    	
        return $nextStartTime;
    }
    

    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "StartReminders";
        $rec->description = "Напомняне";
        $rec->controller = "cal_Reminders";
        $rec->action = "SendNotifications";
        $rec->period = 1;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
        
        $res .= "<li>Напомняне  по крон</li>";
    }

       
}
