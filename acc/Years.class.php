<?php



/**
 * Мениджър за счетоводни години "Години"
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Years extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, acc_YearsRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Години';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Година";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, name, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, acc_plg_Registry, acc_WrapperSettings, plg_Created, plg_Rejected';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата.
     * 
     * @see plg_RowTools
     * @var $string име на поле от този модел
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  Кой може да чете
     */
    var $canRead = 'ceo,accMaster';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,accMaster';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    var $canReports = 'ceo,accMaster';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,accMaster';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,accMaster';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     * 
     * @see acc_plg_Registry
     * @var string
     */
    var $autoList = 'year';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'int', 'caption=Година,mandatory');
        $this->setDbUnique('name');
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
    	$result = NULL;
    	if ($rec = static::fetch($objectId)) {
    		$result = (object)array(
    				'num'      => $rec->id,
    				'title'    => $rec->name,
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    	// @todo!
    }
}