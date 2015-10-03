<?php 


/**
 * Бележки в системата
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Notes extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_Articles';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body';
    
    
    /**
     * Кой има право да клонира?
     */
    protected $canClone = 'powerUser';
    
    
    /**
     * Заглавие
     */
    var $title = "Бележки";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Бележка";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
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
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';


    /**
     * 
     */
    var $canSingle = 'powerUser';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutNotes.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/doc-note.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Not';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, body';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, sharedUsers=Споделяне, createdOn, createdBy';


	/**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'user';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.1|Общи"; 
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'subject, body, sharedUsers';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Notes)', 'caption=Бележка,mandatory');
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     * @see doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        
        $row->title = $subject;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    

    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     * Добавянето на бележка не променя състоянието на треда
     */
    static function getThreadState($id)
    {
        return NULL;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
}
