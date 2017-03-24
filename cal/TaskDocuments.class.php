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
     * Кой има право да възстановява?
     */
    public $canRestore = 'powerUser';
    
    
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
        $sId = self::save($rec);
        
        if ($sId) {
            // Обновяване на мастъра
            cal_Tasks::touchRec($taskId);
        }
        
        return $sId; 
    }
    

    /**
     * Изпълнява се след създаване на нов запис
     *
     * @param cal_TaskDocuments $mvc
     * @param stdObject $rec
     */
	public static function on_AfterCreate($mvc, $rec)
	{
        if ($rec->containerId) {
            $document = doc_Containers::getDocument($rec->containerId);

            // Записваме в лога
            cal_Tasks::logWrite('Добавяне на документ', $rec->taskId);
            $document->instance->logInAct('Добавяне към задача', $document->that);
        }
    }
    
    
    /**
     * 
     * @param cal_TaskDocuments $mvc
     * @param integer $id
     * @param stdObject $rec
     * @param NULL|string $fields
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        if ($rec->taskId) {
            $cId = cal_Tasks::fetchField($rec->taskId, 'containerId');
            if ($rec->state == 'rejected') {
                doclog_Used::remove($cId, $rec->containerId);
            } else {
                doclog_Used::add($cId, $rec->containerId);
            }
        }
    }
    
    
    /**
     * Логва действието
     * 
     * @param string $msg
     * @param NULL|stdClass|integer $rec
     * @param string $type
     */
    function logInAct($msg, $rec = NULL, $type = 'write')
    {
        if ($msg == 'Създаване') return ;
        
        return parent::logInAct($msg, $rec, $type);
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
        if ($rec->containerId) {
            // Документа
            $doc = doc_Containers::getDocument($rec->containerId);
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            $url = $doc->getSingleUrlArray();
            
            if (empty($url) && $mvc->Master->haveRightFor('single', $rec->taskId) && $rec->state != 'rejected') {
                $url = $doc->getUrlWithAccess($mvc, $rec->id);
            }
            
            // Атрибутеите на линка
            $attr = array();
            $attr['ef_icon'] = $doc->getIcon($doc->that);
            $attr['title'] = 'Документ|*: ' . $docRow->title;
            
            $row->containerId = ht::createLink(str::limitLen($docRow->title, 35), $url, NULL, $attr);
        }
    }
    
    
    /**
     * Проверява дали документа се цитира в източника
     * 
     * @param integer $id
     * @param integer $cid
     * 
     * @return boolean
     */
    public static function checkDocExist($id, $cid)
    {
        if (self::fetch(array("#id = '[#1#]' AND #containerId = '[#2#]' AND #state != 'rejected'", $id, $cid))) return TRUE;
        
        return FALSE;
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
		    
		    if (!self::fetch("#state = 'rejected' && #taskId = '{$data->masterData->rec->id}'")) {
		        $data->disabled = TRUE;
		    }
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
	    if ($data->disabled) return ;
		
		return parent::renderDetail_($data);
	}
}
