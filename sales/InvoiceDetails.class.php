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
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
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
		if (!empty ($data->toolbar->buttons ['btnAdd'])) {
			$productManagers = core_Classes::getOptionsByInterface ('cat_ProductAccRegIntf');
			$masterRec = $data->masterData->rec;
			$addUrl = $data->toolbar->buttons ['btnAdd']->url;
			
			foreach ($productManagers as $manId => $manName) {
				$productMan = cls::get ($manId);
				$products = $productMan->getProducts ($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date);
				if (!count ($products)) {
					$error = "error=Няма продаваеми {$productMan->title}";
				}
				
				$data->toolbar->addBtn ($productMan->singleTitle, $addUrl + array ('classId' => $manId), "id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
	            	unset($error);
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
        $ProductMan = cls::get($form->rec->classId);
        
        // Поакзваме само продуктите спрямо ценовата политиказа контрагента
        $masterRec = $mvc->Master->fetch($form->rec->invoiceId);
        $products = $ProductMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
       
        $data->form->fields['price']->unit = ($masterRec->vatRate == 'yes') ? 'с ДДС' : 'без ДДС';
        
        
        if($form->rec->id){
        	$form->setOptions('productId', array($form->rec->productId => $products[$form->rec->productId]));
        } else {
        	$form->setOptions('productId', $products);
        }
        
        $masterTitle = $mvc->Master->getDocumentRow($form->rec->invoiceId)->title;
        (Request::get('Act') == 'add') ? $action = tr("Добавяне") : $action = tr("Редактиране");
      	$form->title = "{$action} |на запис в|* {$masterTitle}";
      	
   		if($form->rec->price && $masterRec->rate){
   		 	if($masterRec->chargeVat == 'yes'){
   		 		$form->rec->price *= 1 + $ProductMan->getVat($form->rec->productId, $masterRec->valior);
   		 	}
   		 	
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
            $update = FALSE;
            
        	if(empty($rec->id) && $id = $mvc->fetchField("#invoiceId = {$rec->invoiceId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}", 'id')){
            	$form->setWarning("productId", "Има вече такъв продукт! Искатели да го обновите ?");
            	$rec->id = $id;
            	$update = TRUE;
            }
            
            $productMan = cls::get($rec->classId);
            if(!$pInfo = $productMan::getProductInfo($rec->productId, $rec->packagingId)){
          	   $form->setError('packagingId', 'Продукта не се предлага в посочената опаковка');
          	   return;
            }
          
            $rec->quantityInPack = ($rec->packagingId) ? $pInfo->packagingRec->quantity : 1;
            $masterRec = $mvc->Master->fetch($rec->invoiceId);
            
            if(!$rec->price){
          	
	            // Ако не е зададена цена, извличаме я от избраната политика
	          	$rec->price = $productMan->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->quantity)->price;
	          	if(!$rec->price){
		            $form->setError('price', 'Неможе да се определи цена');
		        }
          	} else {
          		if ($masterRec->vatRate == 'yes') {
                	if(!$update || ($update && Request::get('Ignore'))){
                		
                		// Потребителя въвежда цените с ДДС
                    	$rec->price /= 1 + $productMan->getVat($rec->productId, $masterRec->valior);
                	} 
                }
          		$rec->price = $rec->price * $masterRec->rate;
          	}
          
           // Изчисляваме цената
           $rec->amount = $rec->price * $rec->quantity;
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
}