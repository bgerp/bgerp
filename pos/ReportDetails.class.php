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
        $this->FLD('pack', 'varchar(32)', 'caption=Какво');
    }
    
    
    /**
     * Рендиране на Детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	$sales = new stdClass();
    	$payments = new stdClass();
    	$payments->listFields = "value=Плащане, amount=Сума";
    	$sales->listFields = "value=Продукт, quantity=Количество, amount=Сума";
    	if($data->rows){
	    	foreach($data->rows as $row) {
	    		($row->action == 'payment') ? $payments->rows[] = $row : $sales->rows[] = $row;
	    	}
    	}
    	
    	$tpl->append($this->renderListTable($payments));
    	$tpl->append($this->renderListTable($sales));
    	
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$varchar = cls::get("type_Varchar");
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($rec->amount); 
    	if($rec->action == 'sale') {
    		$info = cat_Products::getProductInfo($rec->value, $rec->pack);
    		$product = $info->productRec;	
    		if($rec->pack){
    			$pack = cat_Packagings::fetchField($rec->pack, 'name');
    		} else {
    			$pack = cat_UoM::fetchField($product->measureId, 'shortName');
    		}
    		$row->value = $product->code . " - " . $product->name;
    		$row->value = ht::createLink($row->value, array("cat_Products", 'single', $rec->value));
    		$row->quantity = $pack . " - " .$row->quantity;
    	} else {
    		$value = pos_Payments::fetchField($rec->value, 'title');
    		$row->value = $varchar->toVerbal($value);
    	}
    	$currencyCode = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->amount .= " <span class='cCode'>{$currencyCode}</span>";
    }
}