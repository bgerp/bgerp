<?php



/**
 * Мениджър за "Детайли на заявките за продажба" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_SaleRequestDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на заявките за продажба';
    
    
    /**
	 * Мастър ключ към заявката
	 */
	var $masterKey = 'requestId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_AlignDecimals, plg_RowNumbering, sales_Wrapper, doc_plg_HidePrices';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, uomId=Мярка, price, discount, amount=Сума';
    
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'price,discount,amount';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('requestId', 'key(mvc=sales_SaleRequests)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('productManId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden,oldFieldName=classId');
    	$this->FLD('quantity', 'double', 'caption=К-во,width=8em');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена,width=8em');
        $this->FLD('discount', 'percent(decimals=2,min=0)', 'caption=Отстъпка,width=8em');
    }
    
    
	/**
     * Сортиране по ред на добавяне
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'ASC');
    }
        
    
    /**
     * Скриване на колоната за отстъпка ако няма отстъпки
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
        $haveDiscount = FALSE;
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
            	$haveDiscount = $haveDiscount || !empty($data->recs[$i]->discount);
            }
        }
        
    	if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal(core_Manager $mvc, &$row, $rec)
    { 
    	$productMan = cls::get($rec->productManId);
    	$masterRec = $mvc->Master->fetch($rec->requestId);
    	
    	$row->productId = $productMan->getTitleById($rec->productId);
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && $productMan->haveRightFor('read', $rec->productId)){
    		$row->productId = ht::createLinkRef($row->productId, array($productMan, 'single', $rec->productId));
    	}

    	$applyVat = $mvc->Master->fetchField($rec->requestId, 'vat');
    	if($applyVat == 'yes' || $applyVat == 'no'){
    		$vat = $productMan->getVat($rec->productId);
    		$rec->price = $rec->price * (1 + $vat);
    	}
    	
    	$rec->price /= $masterRec->rate;
    	$rec->price = currency_Currencies::round($rec->price, $masterRec->paymentCurrencyId);
    	
    	$measureId = $productMan->getProductInfo($rec->productId, NULL)->productRec->measureId;
    	$row->uomId = cat_UoM::getTitleById($measureId);
    	
    	$row->amount = $mvc->fields['price']->type->toVerbal($rec->price * $rec->quantity);
    	$row->amount = "<div style='text-align:right'>{$row->amount}</div>";
    	$row->price = $mvc->fields['price']->type->toVerbal($rec->price);
    }
}