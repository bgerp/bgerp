<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_InvoiceDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на фактурата";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_AlignDecimals, doc_plg_HidePrices';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кое е активното меню
     */
    var $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'invoiceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId, packagingId, quantity, price, amount';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'sales, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'sales, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Фактури';
    
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'price,amount';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт');
        $this->FLD('quantity', 'double', 'caption=К-во,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');
        $this->FLD('quantityInPack', 'double', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена, input');
        $this->FLD('note', 'varchar(64)', 'caption=@Пояснение');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,input=none');
        
        $this->setDbUnique('invoiceId, productId, packagingId');
    }
    
    
    /**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $pricePolicies = core_Classes::getOptionsByInterface('price_PolicyIntf');
           
            $customerClass = $data->masterData->rec->contragentClassId;
            $customerId    = $data->masterData->rec->contragentId;
            
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                
                $data->toolbar->addBtn($Policy->getPolicyTitle($customerClass, $customerId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId}", 'ef_icon = img/16/shopping.png');
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
      	$form = $data->form;
        $Policy = cls::get($form->rec->policyId);
        
        // Поакзваме само продуктите спрямо ценовата политиказа контрагента
        $masterRec = $mvc->Master->fetch($form->rec->invoiceId);
       
        $products = $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
        $form->setOptions('productId', $products);
        
        $masterTitle = $mvc->Master->getDocumentRow($form->rec->invoiceId)->title;
        (Request::get('Act') == 'add') ? $action = tr("Добавяне") : $action = tr("Редактиране");
      	$form->title = "{$action} |на запис в|* {$masterTitle}";
      	
   		 if($form->rec->price && $masterRec->rate){
       	 	$form->rec->price = round($form->rec->price / $masterRec->rate, 2);
         }
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
            $Policy = cls::get($rec->policyId);
            $productMan = $Policy->getProductMan();
            if(!$pInfo = $productMan::getProductInfo($rec->productId, $rec->packagingId)){
          	   $form->setError('packagingId', 'Продукта не се предлага в посочената опаковка');
          	   return;
            }
          
            $rec->quantityInPack = ($rec->packagingId) ? $pInfo->packagingRec->quantity : 1;
            $masterRec = $mvc->Master->fetch($rec->invoiceId);
            
            if(!$form->rec->price){
          	
	            // Ако не е зададена цена, извличаме я от избраната политика
	          	$rec->price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity)->price;
	          	if(!$rec->price){
		            $form->setError('price', 'Неможе да се определи цена');
		        }
          	} else {
          		$rec->price = round($rec->price * $masterRec->rate, 2);
          	}
          
           // Изчисляваме цената
           $form->rec->amount = round($form->rec->price * $form->rec->quantity, 2);
        }
    }

    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$Policy = cls::get($rec->policyId);
        $productMan = $Policy->getProductMan();
        $row->productId = $productMan::getTitleById($rec->productId);
        
    	if($rec->note){
    		$varchar = cls::get('type_Varchar');
	    	$row->note = $varchar->toVerbal($rec->note);
	    	$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
    	}
    	
    	$pInfo = $productMan->getProductInfo($rec->productId);
    	
    	if($rec->packagingId){
    		$measureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
    		$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
    	} else {
    		$row->packagingId = cat_UoM::getTitleById($pInfo->productRec->measureId);
    	}
    	
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$masterRec = $mvc->Master->fetch($rec->invoiceId);
    	
    	$price = round($rec->price / $masterRec->rate, 2);
    	$row->price = $double->toVerbal($price);
    	
    	$amount = round($rec->amount / $masterRec->rate, 2);
    	$row->amount = $double->toVerbal($amount);
    }
    
    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'write' && isset($rec->invoiceId)){
    		
    		// Ако фактурата е генерирана от вече контирана продажба
    		// неможе да се добавят нови продукти
    		$invoiceRec = $mvc->Master->fetch($rec->invoiceId);
    		if(($invoiceRec->originId && $invoiceRec->type == 'invoice') || ($invoiceRec->docType && $invoiceRec->docId)){
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * След запис, обновяваме информацията в мастъра
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        $mvc->Master->updateInvoice($rec->invoiceId);
    }
}