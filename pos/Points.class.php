<?php



/**
 * Мениджър за "Точки на Продажба" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Points extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = "Точки на Продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_State,
                     pos_Wrapper, plg_Sorting, plg_Printing, plg_Current';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Точка на Продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, tools=Пулт, title, caseId, storeId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'admin, pos';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование, mandatory');
    	$this->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
    }
    
	
    /**
     * Създава дефолт контрагент за обекта, ако той вече няма създаден
     */
    static function on_AfterSave($mvc, &$id, $rec)
    {
    	if(!static::defaultContragent($id)) {
	    	$defaultContragent = new stdClass();
	    	$defaultContragent->name = "POS:" . $rec->id . "-Анонимен Клиент";
	    	crm_Persons::save($defaultContragent);
    	}
    }
    
    
    /**
     * Намира кой е дефолт контрагента на Точката на продажба
     * @param int $id - ид на точкта
     * @return mixed $id/FALSE - ид на контрагента или FALSE ако няма
     */
    static function defaultContragent($id = NULL)
    {
    	($id) ? $pos = $id : $pos = pos_Points::getCurrent();
    	$query = crm_Persons::getQuery();
    	$query->where("#name LIKE '%POS:{$pos}%'");
    	if($rec = $query->fetch()) {
    		
    		return $rec->id;
    	}
    	
    	return FALSE;
    }
}