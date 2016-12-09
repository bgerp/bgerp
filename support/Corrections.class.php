<?php



/**
 * Коригиращи действия
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Corrections extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Заглавие
     */
    var $title = "Коригиращи действия";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Коригиращи действия";
    
    
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
    var $canList = 'ceo, admin, support';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'admin, support';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools2, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'body, sharedUsers';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'support, admin, ceo';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'support/tpl/SingleLayoutCorrections.shtml';
    
    /**
     * Икона по подразбиране за единичния обект
     */
    //    var $singleIcon = 'img/16/xxx.png';
    
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'COR';
    
    
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
     * Групиране на документите
     */
    var $newBtnGroup = "10.2|Поддръжка";
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно, mandatory, input=hidden');
        $this->FLD('body', 'richtext(rows=10,bucket=Support)', 'caption=Коментар,mandatory');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
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
     * Добавянето на корекция не променя състоянието на треда
     */
    static function getThreadState($id)
    {
        
        return NULL;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде
     * добавен в посочената нишк-а
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        // Ако някой от документите в нишката, е support_Issue
        return doc_Containers::checkDocumentExistInThread($threadId, 'support_Issues');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Да не може да се добавя в папка, като начало на нишка
        return FALSE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Премахваме бутона за добанвяне на нов запис в листовия изглед
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * 
     * 
     * @param support_Corrections $mvc
     * @param stdObject $data
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        
        support_Issues::prepareBodyAndSubject($rec);
    }
}
