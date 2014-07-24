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
    public $title = 'Детайли на заявките за продажба';
    
    
    /**
	 * Мастър ключ към заявката
	 */
	public $masterKey = 'requestId';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_AlignDecimals2, plg_RowNumbering, sales_Wrapper, doc_plg_HidePrices';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, uomId=Мярка, price, discount, amount=Сума';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,discount,amount';
    
    
    /**
     * Помощен масив (@see deals_Helper)
     */
    public static $map = array('priceFld' => 'price', 'quantityFld' => 'quantity', 'valior' => 'createdOn');
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('requestId', 'key(mvc=sales_SaleRequests)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden,oldFieldName=productManId');
    	$this->FLD('quantity', 'double', 'caption=К-во,width=8em', 'tdClass=small-field');
    	$this->FLD('price', 'double', 'caption=Ед. цена,width=8em');
        $this->FLD('discount', 'percent(decimals=2,min=0)', 'caption=Отстъпка,width=8em');
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->price) || empty($rec->quantity)) {
    		return;
    	}
    
    	$rec->amount = $rec->price * $rec->quantity;
    }
    
    
	/**
     * Сортиране по ред на добавяне
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'ASC');
    }
        
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRecs($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	$recs = &$data->recs;
    	$masterRec = $data->masterData->rec;
    	
    	deals_Helper::fillRecs($mvc->Master, $data->recs, $masterRec, static::$map);
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
    	$productMan = cls::get($rec->classId);
    	$masterRec = $mvc->Master->fetch($rec->requestId);
    	
    	$row->productId = $productMan->getTitleById($rec->productId);
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && $productMan->haveRightFor('read', $rec->productId)){
    		$row->productId = ht::createLinkRef($row->productId, array($productMan, 'single', $rec->productId));
    	}
    	
    	$measureId = $productMan->getProductInfo($rec->productId, NULL)->productRec->measureId;
    	$row->uomId = cat_UoM::getTitleById($measureId);
    }
}