<?php 


/**
 * Документ с който се сигнализара някакво несъответствие
 *
 * @category  bgerp
 * @package   issue
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Issues extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Document';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Сигнали';
    
    
    /**
     * 
     */
    var $singleTitle = 'Сигнал';
    
    
    /**
     * 
     */
    var $abbr = 'Sig';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, issue';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, issue';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     *
     */
    var $canActivate = 'user';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, plg_Search, doc_SharablePlg';
    //plg_Created
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Първоначално състояние на документа
     */
//    var $firstState = 'opened';
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
//    var $defaultFolder = 'Системи';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'issue/tpl/SingleLayoutDocument.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
//    var $singleIcon = 'img/16/.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'componentId, typeId, description';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	// В description не може да се прави нищо друго, освен да се дефинират безусловно полета и техни свойства
    	//    $systemId = support_Systems::getCurrentIssueSystemId(); 
        
        //   $componentWhere = "#systemId = '{$systemId}'";
        
        //  $this->FLD('componentId', 
        //	new type_Key(array('mvc' => 'support_Components', 'select' => 'name', 'where' => $componentWhere)),
        //	'caption=Компонент, mandatory');
        
        $this->FLD('componentId', "key(mvc=support_Components,select=name)", 'caption=Компонент, mandatory');
        $this->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory');
        $this->FLD('description', 'text', "caption=Описание");
    }
    
    
	/**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
     
        $row = new stdClass();
        $row->title = $this->getVerbal($rec, 'description');
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->description;
        
        return $row;
    }
    
    
	/**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        
        return 'opened';
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $folderId = $data->form->rec->folderId;
        
        //id' то на класа, който е корица
        $coverClassRec = doc_Folders::fetch($folderId);
        
        $coverClassId = $coverClassRec->coverClass;
        
        //Името на корицата на класа
        $coverClassName = cls::getClassName($coverClassId);
        
        if ($coverClassName != 'support_Systems') {
            $systemId = support_Systems::getCurrentIssueSystemId();
            $iRec = support_Systems::fetch($systemId);
            $folderId = support_Systems::forceCoverAndFolder($iRec);
            $data->form->rec->folderId = $folderId;        
        } else {
            Mode::setPermanent('currentIssueSystemId', $coverClassRec->coverId);
            
            $query = support_Components::getQuery();
            $query->where("#systemId = '{$coverClassRec->coverId}'");
            
            while ($rec = $query->fetch()) {
                $components[$rec->id] = support_Systems::getVerbal($rec, 'name');
            }
            
            $data->form->setOptions('componentId', $components);
        }
    }
    
    
    
    
    
    
    
    
}
