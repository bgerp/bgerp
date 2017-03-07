<?php


/**
 * 
 *
 * @category  bgerp
 * @package   cal
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_TaskDocuments extends core_Detail
{
    
    
    /**
     * 
     */
    public static $lastThreadsCnt = 3;
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Заглавие
     */
    public $title = "Документи към задача";
	
	
    /**
     * Заглавие
     */
    public $singleTitle = "Документ към задача";
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
	
	
    /**
     * Кой има право да оттегле?
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cal_Wrapper, plg_Created, plg_State, plg_Rejected, plg_RowTools2';
    
	
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = '-';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * 
     */
    public $listFields = 'containerId, comment';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('taskId', 'key(mvc=cal_Tasks, name=title)', 'caption=Задача, silent');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ, input=none');
        $this->FLD('comment', 'varchar', 'caption=Коментар');
        $this->FLD('state', 'enum(active=Активно, rejected=Оттеглено)', 'caption=Състояние, input=none');
        
        $this->setDbUnique('taskId, containerId');
    }
    
    
    /**
     * Добавя запис
     * 
     * @param integer $taskId
     * @param integer $cId
     * 
     * @return NULL|number
     */
    public static function add($taskId, $cId)
    {
        if (!$taskId || !$cId) return ;
        
        $rec = new stdClass();
        $rec->state = 'active';
        $rec->containerId = $cId;
        $rec->taskId = $taskId;
        
        return self::save($rec);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cal_TaskDocuments $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->FNC('documentCid', 'varchar', 'caption=Документ, input=input, formOrder=1, mandatory');
        
        $query = $mvc->getQuery();
        $query->where(array("#taskId = '[#1#]'", $data->form->rec->taskId));
        
        $existDocArr = array();
        while ($rec = $query->fetch()) {
            $existDocArr[$rec->containerId] = $rec->containerId;
        }
        
        // Документите от последните посещавани нишки от потребителя
        $threadsArr = bgerp_Recently::getLastThreadsId(self::$lastThreadsCnt);
        $docThreadIdsArr = doc_Containers::getAllDocIdFromThread($threadsArr, NULL, 'DESC');
        
        // Документа, към който ще се добавя да не се показва в списъка
        $mRec = $mvc->Master->fetch($data->form->rec->taskId);
        $existDocArr[$mRec->containerId] = $mRec->containerId;
        
        $docIdsArr = array();
        foreach ($threadsArr as $threadId => $dummy) {
            
            foreach ((array)$docThreadIdsArr[$threadId] as $cRec) {
                
                if ($existDocArr[$cRec->id]) continue;
                
                $docIdsArr[$cRec->id] = $mvc->getDocTitle($cRec->id);
            }
        }
        
        $data->form->setDefault('state', 'active');
        
        if (!$data->form->rec->id) {
            if (!empty($docIdsArr)) {
                $data->form->setOptions('documentCid', $docIdsArr);
            } else {
                $data->form->setReadonly('documentCid');
            }
        } else {
            
            // Ако редактираме записа, да се показва само избраната стойност
            
            $docTitle = $docIdsArr[$data->form->rec->containerId];
            
            if (!isset($docTitle)) {
                $title = $mvc->getDocTitle($data->form->rec->containerId);
            }
            
            $docIdsArr = array($data->form->rec->containerId => $title);
            
            $data->form->setOptions('documentCid', $docIdsArr);
        }
    }
    
    
    /**
     * Подготвя заглавието на документа, за избор в опциите
     * 
     * @param integer $cId
     * 
     * @return string
     */
    protected static function getDocTitle($cId)
    {
        $title = doc_Containers::getDocTitle($cId);
        
        $document = doc_Containers::getDocument($cId);
        $handle = $document->getHandle();
        $title = $handle . ': ' . $title;
        
        return $title;
    }
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param cal_TaskDocuments $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->containerId = $form->rec->documentCid;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param cal_TaskDocuments $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'add' && $rec && $requiredRoles != 'no_one') {
            $cRec = cal_Tasks::fetch($rec->taskId);
            if ($cRec->state == 'closed' || $cRec->state == 'rejected' || !cal_Tasks::haveRightFor('single', $cRec)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param cal_TaskDocuments $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // TODO - линка да има параметър, който да указва от къде е и да може да се отваря
        
        if ($rec->containerId) {
            $row->containerId = doc_Containers::getLinkForSingle($rec->containerId);
        }
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     * 
     * @param cal_TaskDocuments $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	$data->query->orderBy('createdOn', 'DESC');
    }
	
	
	/**
	 * 
	 * 
	 * @param stdObject $data
	 */
	public function prepareDetail_($data)
	{
	    $data->TabCaption = 'Документи';
	    $data->Tab = 'top';
		
	    $res = parent::prepareDetail_($data);
		
		if (empty($data->recs)) {
		    $data->disabled = TRUE;
		}
		
		return $res;
	}
	
	
	/**
	 * 
	 * 
	 * @param stdObject $data
	 */
	public function renderDetail_($data)
	{
	    if (empty($data->recs)) return ;
		
		return parent::renderDetail_($data);
	}
}
