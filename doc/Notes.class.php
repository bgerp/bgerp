<?php 

/**
 * Бележки в системата
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
    public $oldClassName = 'doc_Articles';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, colab_CreateDocumentIntf';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Бележки';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Бележка';
    
    
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
    public $canList = 'ceo, debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, doc_SharablePlg,doc_plg_Prototype, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, change_Plugin, plg_Clone';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'doc/tpl/SingleLayoutNotes.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/doc-note.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Not';
    
    
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
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'powerUser';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '1.1|Общи';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'subject, body, sharedUsers';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Notes)', 'caption=Бележка,mandatory');
        $this->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Споделяне->С партньори,input=none,before=sharedUsers,changable');
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @see doc_DocumentIntf
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
     *
     * @param int $id
     *
     * @return NULL|string
     */
    public static function getThreadState($id)
    {
        $res = null;
        
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
     * @param stdClass     $row
     * @param stdClass     $rec
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     *
     *
     * @param doc_Notes $mvc
     * @param object    $res
     * @param object    $data
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
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $resArr['handle'] = array('name' => 'Ref №', 'val' => '[#handle#]');
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното показване
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    public static function getHideArrForLetterHead($rec, $row)
    {
        $hideArr = array();
        
        // Ако има само една версия, тогава да не се показва във вътрешната част
        if ($row->LastVersion == '0.1') {
            $hideArr['internal']['versionAndDate'] = true;
            $hideArr['internal']['date'] = true;
            $hideArr['internal']['version'] = true;
            $hideArr['internal']['handle'] = true;
        }
        
        $hideArr['internal']['ident'] = true;
        $hideArr['internal']['createdBy'] = true;
        $hideArr['internal']['createdOn'] = true;
        
        return $hideArr;
    }
}
