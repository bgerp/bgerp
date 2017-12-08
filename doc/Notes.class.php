<?php 


/**
 * Бележки в системата
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Notes extends core_Master
{
    
    
    /**
     * Шаблон (ET) за заглавие на перо
     */
    public $recTitleTpl = '[#subject#]';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_Articles';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, colab_CreateDocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
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
    var $loadList = 'doc_Wrapper, doc_SharablePlg,doc_plg_Prototype, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
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
    var $canChangerec = 'powerUser';
    
    
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
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Notes)', 'caption=Бележка,mandatory');
        $this->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Споделяне->С партньори,input=none,before=sharedUsers,changable');
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
     * 
     * @param integer $id
     * 
     * @return NULL|string
     */
    static function getThreadState($id)
    {
	    $res = NULL;
	    
	    if (core_Packs::isInstalled('colab')) {
	        $rec = self::fetch($id);
	        if (core_Users::haveRole('partner', $rec->createdBy)) {
	            $res = 'opened';
	        } elseif (core_Users::isPowerUser($rec->createdBy) && self::isVisibleForPartners($rec)) {
	            $res = 'closed';
	        }
	    }
	    
	    return $res;
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
    
    
    /**
     * 
     * 
     * @param doc_Notes $mvc
     * @param object $res
     * @param object $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        if ($data->row->LastVersion != '0.1') {
            $data->row->currentVersionInHeader = $data->row->LastSelectedVersion ? $data->row->LastSelectedVersion : $data->row->FirstSelectedVersion;
            $data->row->currentVersionInHeader = $data->row->currentVersionInHeader ? $data->row->currentVersionInHeader : $data->row->LastVersion;
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $resArr['handle'] =  array('name' => 'Ref №', 'val' =>"[#handle#]");
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното показване
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function getHideArrForLetterHead($rec, $row)
    {
        $hideArr = array();
        
        // Ако има само една версия, тогава да не се показва във вътрешната част
        if($row->LastVersion == '0.1') {
            $hideArr['internal']['versionAndDate'] = TRUE;
            $hideArr['internal']['date'] = TRUE;
            $hideArr['internal']['version'] = TRUE;
            $hideArr['internal']['handle'] = TRUE;
        }
        
        $hideArr['internal']['ident'] = TRUE;
        $hideArr['internal']['createdBy'] = TRUE;
        $hideArr['internal']['createdOn'] = TRUE;
        
        return $hideArr;
    }
}
