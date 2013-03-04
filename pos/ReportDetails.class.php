<?php



/**
 * Модел "Репорт детайли"
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_ReportDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Репорт детайли';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'pos_Wrapper, plg_Sorting';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'reportId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'action, quantity, amount, value';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "40";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo, admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да вижда списъчния изглед?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('reportId', 'key(mvc=pos_Reports)', 'caption=Репорт');
    	$this->FLD('action', 'varchar(32)', 'caption=Действие');
    	$this->FLD('quantity', 'int', 'caption=К-во,');
        $this->FLD('amount', 'float(minDecimals=2)', 'caption=Сума');
        $this->FLD('value', 'varchar(32)', 'caption=Какво');
    }
    
    
    /**
     * Рендиране на Детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET(getFileContent('pos/tpl/ReportDetails.shtml'));
    	$paymentTpl = $tpl->getBlock("payment");
    	$saleTpl = $tpl->getBlock("sale");
    	foreach($data->rows as $k => $row) {
    		$action = $data->recs[$k]->action;
    		$typeTpl = ${"{$action}Tpl"}->getBlock("{$action}ROW");
    		$typeTpl->placeObject($row);
    		$typeTpl->removeBlocks();
    		$typeTpl->append2master();
    	}
    	
    	$tpl->append($paymentTpl);
    	$tpl->append($saleTpl);
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	//@TODO
    }
}