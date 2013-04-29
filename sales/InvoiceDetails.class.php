<?php 


/**
 * Invoice (Details)
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    var $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering';
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'invoiceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId, quantity, packagingId, price, packQuantity, amount, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'sales, admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'sales, admin';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Фактури';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity', 'double(decimals=4)', 'caption=Количество,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');
        $this->FLD('quantityInPack', 'double', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input');
        $this->FLD('packQuantity', 'double', 'caption=К-во,input=none');
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
           
            $contragentItem = acc_Items::fetch($data->masterData->rec->contragentAccItemId);
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                
                $data->toolbar->addBtn($Policy->getPolicyTitle($contragentItem->classId, $contragentItem->objectId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId},class=btn-shop");
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
        $contragentItem = acc_Items::fetch($masterRec->contragentAccItemId);
        $products = $Policy->getProducts($contragentItem->classId, $contragentItem->objectId);
        $form->setOptions('productId', $products);
        
        $masterTitle = $mvc->Master->getDocumentRow($form->rec->invoiceId)->title;
        (Request::get('Act') == 'add') ? $action = tr("Добавяне") : $action = tr("Редактиране");
      	$form->title = "{$action} на запис в {$masterTitle}";
    }


    /**
     * Извиква се след изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if($form->isSubmitted()) {
            $rec = &$form->rec;
          
            if(!$pInfo = cat_Products::getProductInfo($rec->productId, $rec->packagingId)){
          	   $form->setError('packagingId', 'Продукта не се предлага в посочената опаковка');
          	   return;
            }
          
            if($rec->packagingId){
          	   $rec->quantityInPack = $pInfo->packagingRec->quantity;
            } else {
           	   $rec->quantityInPack = 1;
            }
          
            $rec->packQuantity = $rec->quantityInPack * $rec->quantity;
            if(!$form->rec->price){
          	
            // Ако не е зададена цена, извличаме я от избраната политика
          	$masterRec = sales_Invoices::fetch($rec->invoiceId);
            $contragentItem = acc_Items::fetch($masterRec->contragentAccItemId);
            $Policy = cls::get($rec->policyId);
            $rec->price = $Policy->getPriceInfo($contragentItem->classId, $contragentItem->objectId, $rec->productId, $rec->packagingId)->price;
          
	        if(!$rec->price){
	            $form->setError('price', 'Неможе да се определи цена');
	        }
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
    	$row->quantity = floatval($row->quantity);
    	$row->packQuantity = floatval($rec->packQuantity);
    	
    	if($rec->note){
    		$varchar = cls::get('type_Varchar');
	    	$row->note = $varchar->toVerbal($rec->note);
	    	$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
    	}
    	
    	$productRec = cat_Products::fetch($rec->productId);
    	if($rec->packagingId){
    		$row->quantityInPack = floatval($rec->quantityInPack);
    		$measureShort = cat_UoM::fetchField($productRec->measureId, 'shortName');
    		$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
    	} else {
    		$row->packagingId = cat_Products::getVerbal($productRec, 'measureId');
    	}
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
    		if($invoiceRec->originId || ($invoiceRec->docType && $invoiceRec->docId)){
    			$res = 'no_one';
    		}
    	}
    }
}