<?php



/**
 * Клас 'cond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_DeliveryTerms extends core_Master
{
    
    /**
     * Старо име на класа
     */
	var $oldClassName = 'salecond_DeliveryTerms';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cond_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, codeName, term, state';
    
    
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
    var $canRead = 'ceo,cond';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,cond';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'ceo,cond';
    
    
    /**
     * Кой може да променя
     */
    var $canEdit = 'ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
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
    var $singleLayoutFile = 'cond/tpl/SingleDeliveryTerms.shtml';
    
    
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
    	$file = "cond/csv/DeliveryTerms.csv";
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