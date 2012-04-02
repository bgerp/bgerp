<?php



/**
 * Клас 'blast_LetterDetails' - Детайл на циркулярните писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_LetterDetails extends core_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper, plg_RowNumbering, plg_RowTools, plg_Select, plg_Created, plg_Sorting, plg_State';
    
    
    /**
     * Заглавие
     */
    var $title = "Детайл на писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'blast,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'blast, admin';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Контакт за масово разпращане';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'letterId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 10;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'listDetailsId, printedDate, print=Печат';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('letterId', 'key(mvc=blast_Letters, select=subject)', 'caption=Заглавие');
        $this->FLD('listDetailsId', 'keylist(mvc=blast_ListDetails, select=id)', 'caption=До:');
        $this->FLD('printedDate', 'datetime', 'caption=Отпечатано на, input=none');
    }
    
    
    /**
     * Добавя бутон на файловете, които са за клишета
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->print = HT::createBtn('Печат', array('blast_Letters', 'print', $rec->id, 'Printing' => 'yes'),
            FALSE, array('target' => '_blank'), array('class' => 'print'));
    }
    
    
    /**
     * Преди извличане на записите подрежда ги по дата на отпечатване и състояние
     */
    static function on_BeforePrepareListRecs($mvc, &$res, &$data)
    {
        $data->query->orderBy('#state', 'ASC');
        $data->query->orderBy('#printedDate', 'DESC');
        
        return ;
    }
}