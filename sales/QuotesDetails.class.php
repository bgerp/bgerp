<?php



/**
 * Мениджър за "Оферти за продажба" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_QuotesDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на офертите';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'quoteId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, price, discount, tolerance, term, amount, tools=Пулт';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quoteId', 'key(mvc=sales_Quotes)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
    	$this->FLD('quantity', 'double(decimals=4)', 'caption=К-во,width=8em;');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input,width=8em;');
        $this->FLD('discount', 'percent', 'caption=Отстъпка,width=8em;');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,width=8em;');
    	$this->FLD('term', 'int', 'caption=Срок,unit=седмици,width=8em;');
        $this->FNC('amount', 'varchar', 'caption=Сума,input=none');
    }
    
    
	/**
     * Изчислява на сумата
     */
    static function on_CalcAmount($mvc, $rec)
    {
        if($rec->quantity){
        	$rec->amount = round($rec->quantity * $rec->price, 2);
        } else {
        	$rec->amount = '???';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $form = $data->form;
       (Request::get('edit')) ? $title = tr("Редактиране") : $title = tr("Добавяне");
       $form->title = $title . " " . tr("|на запис в Оферта|* №{$form->rec->quoteId}");
    
       $masterRec = $mvc->Master->fetch($form->rec->quoteId);
       $Policy = cls::get($form->rec->policyId);
       $products = $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
       $form->setOptions('productId', $products);
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	$Policy = cls::get($rec->policyId);
	    	$masterRec = $mvc->Master->fetch($rec->quoteId);
	    	
	    	if(!$rec->price){
	    		$price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, NULL, 1, $masterRec->date);
	    		if(!$price){
	    			$form->setError('price', 'Неможе да се изчисли цената за този клиент');
	    		}
	    		
	    		$rec->price = $price->price;
	    		$rec->discount = $price->discount;
	    	}
    	}
    }
    
    
	/**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $pricePolicies = core_Classes::getOptionsByInterface('price_PolicyIntf');
           
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                
                $data->toolbar->addBtn($Policy->getPolicyTitle($data->masterData->rec->contragentClassId, $data->masterData->rec->contragentId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId},class=btn-shop");
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(!$rec->quantity){
    		$row->quantity = '???';
    	}
    	
    	// Временно докато се изесним какво се прави с productManCls
    	$uomId = cat_Products::fetchField($rec->productId, 'measureId');
    	$uomTitle = cat_UoM::recToVerbal($uomId, 'shortName')->shortName;
    	$row->quantity .= " " . $uomTitle;
    }
}