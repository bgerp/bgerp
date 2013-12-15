<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Продукти в онлайн магазина";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Е-Магазин";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, eshop_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,groupId,state';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name';
    
            
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Продукт";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'eshop,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'eshop,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'eshop,ceo';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';


    /**
     * Нов темплейт за показване
     */
    //var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Продукт, mandatory,width=100%');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');
        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name)', 'caption=Група, mandatory, silent');
    }


    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if($fields['-list']) {
           // $row->name = ht::createLink($row->name, array($mvc, 'Show', $rec->vid ? $rec->vid : $rec->id), NULL, 'ef_icon=img/16/monitor.png');
           $row->groupId = ht::createLink($row->groupId, array('eshop_Groups', 'Show', $rec->groupId), NULL, 'ef_icon=img/16/monitor.png');
        }
    }
}