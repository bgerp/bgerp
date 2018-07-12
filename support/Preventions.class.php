<?php


/**
 * Превантивни действия
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @deprecated
 */
class support_Preventions extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Превантивни действия';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Превантивни действия';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, support';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    public $canSingle = 'admin, support';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'support_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools2, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'body, sharedUsers';
    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'support, admin, ceo';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'support/tpl/SingleLayoutPreventions.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    //    var $singleIcon = 'img/16/xxx.png';
    
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'PRV';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'subject, body';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, subject, sharedUsers=Споделяне, createdOn, createdBy';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '10.3|Поддръжка';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно, mandatory, input=hidden');
        $this->FLD('body', 'richtext(rows=10,bucket=Support)', 'caption=Коментар,mandatory');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
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
     * Добавянето на превенция не променя състоянието на треда
     */
    public static function getThreadState($id)
    {
    }
    
    
    /**
     * Проверка дали нов документ може да бъде
     * добавен в посочената нишк-а
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
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
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        // Да не може да се добавя в папка, като начало на нишка
        return false;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Премахваме бутона за добанвяне на нов запис в листовия изглед
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     *
     *
     * @param support_Corrections $mvc
     * @param stdClass            $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        
        support_Issues::prepareBodyAndSubject($rec);
    }
}
