<?php



/**
 * Клас 'salecond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_DeliveryTerms extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, codeName, term';
    
    
    /**
     * Поле в което ще се показва тулбара
     */
    var $rowToolsSingleField = 'codeName';

    /**
     * 
     * Полетата, които ще се показват в единичния изглед
     */
    var $singleFields = 'id, term, codeName, forSeller, forBuyer, transport';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'ceo,salecond';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,salecond';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'ceo,salecond';
    
    
    /**
     * Кой може да променя
     */
    var $canEdit = 'ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'user';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'user';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'trans_DeliveryTerms';
    
    
    /**
     * Заглавие
     */
    var $title = 'Условия на доставка';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Условие на доставка";
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/delivery.png';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'salecond/tpl/SingleDeliveryTerms.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('codeName', 'varchar', 'caption=Код');
        $this->FLD('term', 'text', 'caption=Обяснение');
        $this->FLD('forSeller', 'text', 'caption=За продавача');
        $this->FLD('forBuyer', 'text', 'caption=За купувача');
        $this->FLD('transport', 'text', 'caption=Транспорт');
        
        $this->setDbUnique('codeName');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "salecond/csv/DeliveryTerms.csv";
    	$fields = array( 
	    	0 => "term", 
	    	1 => "codeName", 
	    	2 => "forSeller", 
	    	3 => "forBuyer", 
	    	4 => "transport");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}