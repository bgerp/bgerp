<?php


/**
 * Модел за кеширани изчислени транспортни цени
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Calcs extends core_Manager
{


	/**
     * Заглавие
     */
    public $title = "Изчислен транспорт";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "tcost_Wrapper";


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
    public $canList = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които се виждат
     */
    public $listFields  = "docId,recId,fee";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docClasId', 'class(interface=doc_DocumentIntf)', 'mandatory,caption=Вид на документа');
    	$this->FLD('docId', 'int', 'mandatory,caption=Ид на документа');
    	$this->FLD('recId', 'int', 'mandatory,caption=Ид на реда');
    	$this->FLD('fee', 'double', 'mandatory,caption=Сума на транспорта');
    	
    	$this->setDbUnique('docClasId,docId,recId');
    	$this->setDbIndex('docClasId,docId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->docId = cls::get($rec->docClassId)->getLink($rec->docId, 0);
    }
}