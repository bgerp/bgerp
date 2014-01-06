<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_InvoiceDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = "Детайли на фактурата";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_AlignDecimals, doc_plg_HidePrices';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantity, price, discount, amount';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'sales, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'sales, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Фактури';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount';
    
    
    /**
     * Помощен масив за мапиране на полета изпозлвани в price_Helper
     */
    public static $map = array('priceFld'    => 'price',
        			 		   'rateFld'     => 'rate', 
        			 		   'chargeVat'   => 'vatRate', 
        			 		   'quantityFld' => 'quantity', 
        			 		   'valior'      => 'date',
    						   'alwaysHideVat' => TRUE);
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт');
        $this->FLD('quantity', 'double(Min=0)', 'caption=К-во,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none');
        $this->FLD('price', 'double', 'caption=Цена, input=none');
        $this->FLD('note', 'varchar(64)', 'caption=@Пояснение');
		$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,input=none');
		$this->FLD('discount', 'percent', 'input=none,caption=Отстъпка');
		
		$this->setDbUnique('invoiceId, productId, packagingId');
	}
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
      	$form = &$data->form;
      	
      	$masterRec = $mvc->Master->fetch($form->rec->invoiceId);
      	expect($origin = $mvc->Master->getOrigin($masterRec));
      	$dealAspect = $origin->getAggregateDealInfo()->shipped;
      	$invProducts = $mvc->Master->getDealInfo($form->rec->invoiceId)->invoiced;
        $form->setOptions('productId', bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'all', $form->rec->productId, $form->rec->classId, $form->rec->packagingId));
        
        $masterTitle = $mvc->Master->getDocumentRow($form->rec->invoiceId)->title;
        (Request::get('Act') == 'add') ? $action = tr("Добавяне") : $action = tr("Редактиране");
      	$form->title = "{$action} |на запис в|* {$masterTitle}";
    }

    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	if($masterRec->type != 'invoice'){
    		
    		// При дебитни и кредитни известия поакзваме основанието
    		$data->listFields = array();
    		$data->listFields['reason'] = 'Основание';
    		$data->listFields['amount'] = 'Сума';
    		$data->rows = array();
    		$data->rows[] = (object) array('reason' => $masterRec->reason,
    									   'amount' => $masterRec->changeAmount);
    	} 
    }
    
    
    /**
     * Извиква се след изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if($form->isSubmitted()) {
            $rec = &$form->rec;
            $update = FALSE;
            list($rec->classId, $rec->productId, $rec->packagingId) = explode('|', $rec->productId);
            $rec->packagingId = ($rec->packagingId) ? $rec->packagingId : NULL;
            
            $productMan = cls::get($rec->classId);
            if(!$pInfo = $productMan::getProductInfo($rec->productId, $rec->packagingId)){
          	   $form->setError('packagingId', 'Продукта не се предлага в посочената опаковка');
          	   return;
            }
            
            $masterRec = $mvc->Master->fetch($rec->invoiceId);
          	$origin = $mvc->Master->getOrigin($masterRec);
      		$dealInfo = $origin->getAggregateDealInfo();
            
      		$aggreedProduct = $dealInfo->shipped->findProduct($rec->productId, $rec->classId, $rec->packagingId);
      		$rec->price = $aggreedProduct->price;
            $rec->uomId = $aggreedProduct->uomId;
      		$rec->quantityInPack = ($rec->packagingId) ? $pInfo->packagingRec->quantity : 1;
            
            // Изчисляваме цената
            $rec->amount = $rec->price * $rec->quantity;
        }
    }

    
	/**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $invRec = &$data->masterData->rec;
        
        if (empty($recs)) return;
        foreach ($recs as &$rec){
        	$rec->price = $rec->price * $rec->quantityInPack;
        	$haveDiscount = $haveDiscount || !empty($rec->discount);
        }
        
        price_Helper::fillRecs($recs, $invRec, static::$map);
        
    	if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$ProductMan = cls::get($rec->classId);
        $row->productId = $ProductMan::getTitleById($rec->productId);
        
    	if($rec->note){
    		$varchar = cls::get('type_Varchar');
	    	$row->note = $varchar->toVerbal($rec->note);
	    	$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
    	}
    	
    	$pInfo = $ProductMan->getProductInfo($rec->productId);
    	
    	if($rec->packagingId){
    		$measureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
    		$row->quantityInPack = $mvc->fields['price']->type->toVerbal($rec->quantityInPack);
    		$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
    	} else {
    		$row->packagingId = cat_UoM::getTitleById($pInfo->productRec->measureId);
    	}
    }
    
    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec->invoiceId)){
    		$invType = $mvc->Master->fetchField($rec->invoiceId, 'type');
    		
      		if($invType == 'invoice'){
      			$masterRec = $mvc->Master->fetch($rec->invoiceId);
		      	$origin = $mvc->Master->getOrigin($masterRec);
		      	$dealAspect = $origin->getAggregateDealInfo()->shipped;
		      	$invProducts = $mvc->Master->getDealInfo($rec->invoiceId)->invoiced;
    			if($masterRec->state != 'draft' || !bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'all')){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
	/**
     * Преди извличане на записите филтър по number
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
       $data->query->orderBy('#id', 'ASC');
    }
}