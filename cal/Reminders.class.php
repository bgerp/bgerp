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
    var $listFields = 'id, title, description, timeStart, timePreviously, repetitionEach, repetitionМeasure, repetitionАbidance, action, sharedUsers';
    
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
        
        $this->FLD('description', 'richtext', 'caption=Описание');

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
        
        // Повторение по 
        $this->FLD('repetitionEach', 'int',     'caption=Повторение->Всеки');
        
        // Повторение по 
        $this->FLD('repetitionМeasure', 'enum(0=,
        									  days=дена,
			                                  weeks=седмици,
			                                  months=месецa,
			                                  years=години)',  
           'caption=Повторение->Мярка');
        
        // Повторение по 
        $this->FLD('repetitionАbidance', 'enum(0=,
        									   weekDay=Ден от началото на седмицата,
        									   monthDay=Ден от началото на месеца,
        									   monthDayEnd=Ден от края на месеца)',  
           'caption=Повторение->Съблюдаване');
        
         // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
        
       

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
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
       
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
    	//bp(&$roles, $action, $rec, $userId);
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
        $query = $this->getQuery();
        $now = dt::verbal2mysql();
        $query->where("#state = 'active'  AND #notifySent = 'no' AND #timeStart <= '{$now}'");
        
        while($rec = $query->fetch()) {
            list($date, $time) = explode(' ', $rec->timeStart);  
            if($time != '00:00:00') {
                $subscribedArr = type_Keylist::toArray($rec->sharedUsers); 
                if(count($subscribedArr)) { 
                    $message = "Стартирана е задачата \"" . $this->getVerbal($rec, 'title') . "\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('cal_Tasks', 'single',  $rec->id);
                    $priority = 'normal';
                    foreach($subscribedArr as $userId) {  
                        if($userId > 0  &&  
                            doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                            bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        }
                    }
                }
            }

            $rec->notifySent = 'yes';

            $this->save($rec, 'notifySent');
        }
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
