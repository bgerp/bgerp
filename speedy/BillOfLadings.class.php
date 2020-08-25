<?php 


/**
 * Модел за Товарителници към спиди
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_BillOfLadings extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Товарителници към спиди';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_Created';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
     public $listFields = "id,containerId,number,takingDate,file,createdOn,createdBy";
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ');
        $this->FLD('number', 'varchar', 'caption=Товарителница');
        $this->FLD('takingDate', 'datetime(format=smartTime)', 'caption=Дата');
        $this->FLD('file', 'fileman_FileType(bucket=billOfLadings)', 'caption=Файл');
        
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', "DESC");
    }
}