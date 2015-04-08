<?php


/**
 * Документ за създаване на HTML, който може да се използва за миграции
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Html extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "HTML";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "HTML";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'debug';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Стойност по подразбиране на състоянието
     * @see plg_State
     */
    public $defaultState = 'active';
    
    
    /**
     * Стойност по подразбиране на състоянието
     * @see plg_State
     */
    public $firstState = 'active';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_DocumentPlg, plg_Printing, bgerp_plg_Blank, plg_RowTools';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutHtml.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/doc.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Dh';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, content, email, type';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.1|Общи";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Заглавие,mandatory');
        $this->FLD('content', 'html(1000000)', 'caption=Текст,mandatory');
        $this->FLD('email', 'email', 'caption=Имейл');
        $this->FLD('type', 'varchar(16)', 'caption=Тип');
    }

    
    /**
     * Добавя подадения запис в модела
     * 
     * @param object $rec
     * $rec->subject
     * $rec->content
     * $rec->email
     * $rec->type
     * $rec->state
     * $rec->createdBy
     * $rec->createdOn
     * 
     * @return integer - id на записа
     */
    public static function add($rec)
    {
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $rec->content, $matches);
        
        if ($matches[1]) {
            $rec->content = $matches[1];
        }

        $id = self::save($rec, NULL, 'IGNORE');
        
        return $id;
    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     * 
     * @param integer $id
     * 
     * @return object
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        
        $row->title = $subject;
        
        // Ако няма потребител и има имейл да се използва то
        if ($rec->createdBy > 0) {
            $row->author = $this->getVerbal($rec, 'createdBy');
        } elseif (isset($rec->email)) {
            $row->authorEmail = $rec->email;
            $row->author = "<small>{$rec->email}</small>";
        } else {
            $row->author = $this->getVerbal($rec, 'modifiedBy');
        }
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     * Добавянето на документ да не променя състоянието на треда
     * 
     * @param integer $id
     * 
     * @return NULL
     */
    public static function getThreadState($id)
    {
        
        return NULL;
    }
}
