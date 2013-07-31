<?php
/**
 * Документ "Заявка за продажба"
 *
 * Мениджър на документи за Заявки за продажба, от оферта
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SaleRequests extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Заявки за продажба';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'sales_SaleRequest';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Sr';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Printing, doc_DocumentPlg, doc_ActivatePlg, bgerp_plg_Blank, acc_plg_DocumentSummary, plg_Search, plg_Sorting';
       
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'folderId, amount';
    
    
    /**
     * Поле за търсене по дата
     */
    var $filterDateField = 'createdOn';
    
    
    /**
     * Поле за валута
     */
    var $filterCurrencyField = 'paymentCurrencyId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales,contractor';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_SaleRequestDetails' ;
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,sales';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,sales'; 


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title=Наименование, folderId, amount, state, createdOn, createdBy';
    
    
	/**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заявка за продажба';
    
    
    /**
     * Работен кеш за вече извлечените продукти
     */
    protected static $cache;

    
    /**
     * Шаблон за еденичен изглед
     */
    var $singleLayoutFile = 'sales/tpl/SingleSaleRequest.shtml';
    
   
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент,fromOffer');
        $this->FLD('contragentId', 'int', 'input=hidden,fromOffer');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)','caption=Плащане->Метод,width=8em,fromOffer');
        $this->FLD('paymentCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em,fromOffer');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Плащане->Курс,width=8em,fromOffer');
        $this->FLD('vat', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)','caption=Плащане->ДДС,oldFieldName=wat,fromOffer');
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,width=8em,fromOffer');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,width=10em,fromOffer');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Общо,input=none,summary=amount');
    	$this->FLD('discount', 'double(decimals=2)', 'caption=Общо с отстъпка,input=none');
    	$this->FLD('data', 'blob(serialize,compress)', 'input=none,caption=Данни');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
 	function act_CreateFromOffer()
 	{
 		$this->requireRightFor('add');
 		if($id = Request::get('id', 'int')){
 			expect($this->fetchField($id, 'state') == 'draft');
 		}
 		expect($originId = Request::get('originId'));
        $origin = doc_Containers::getDocument($originId);
    	expect($origin->className == 'sales_Quotations');
    	$originRec = $origin->fetch();
    	
    	// Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($origin->that, $id);
        
 		if ($this->haveRightFor('activate')) {
            $form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=9.9999', 'ef_icon = img/16/lightning.png');
        }
        
        $fRec = $form->input();
        if($form->isSubmitted()){
        	$rec = (object)array('originId' => $originId,
        						 'threadId' => $originRec->threadId,
        						 'folderId' => $originRec->folderId);
        	if(Request::get('edit')){
        		$rec->id = $id;
        	}
        	
        	// Подготовка на данните
        	$rec->data = (array)$fRec;
        	$id = $this->saveData($rec, $fRec, $originRec, $form->cmd);
        	
        	return Redirect(array($this, 'single', $id));
        }
        
        return $this->renderWrapping($form->renderHtml());
 	}
    
    
    /**
     * Записване на данните от офертата в заявката
     * @param stdClass $rec - запис на заявката
     * @param stdClass $dRec - въведените детайли
     * @param stdClass $quoteRec - офертата пораждаща заявката
     * @param string $cmd - командата от формата
     * @return int $id - ид на записа
     */
    private function saveData($rec, $dRec, $quoteRec, $cmd)
    {
    	$fields = $this->selectFields("#fromOffer");
    	foreach($fields as $name => $fld){
    		if(isset($quoteRec->{$name})){
    			$rec->{$name} = $quoteRec->{$name};
    		}
    	}
    	
    	$this->save($rec);
    	$this->sales_SaleRequestDetails->delete("#requestId = {$rec->id}");
    	
    	$items = $this->prepareProducts($dRec);
    	
    	foreach ($items as $item){
    		$item->requestId = $rec->id;
    		$this->sales_SaleRequestDetails->save($item);
    	}
    	
    	if($cmd == 'active'){
        	$this->invoke('Activation', array($rec));
        	$this->save($rec);
        }
    	
    	return $rec->id;
    }
    
    
    /**
     * Подготовка на продуктите от формата с вече уточнените
     * к-ва във подходящ вид
     * @param array $products - продуктите върнати от формата
     * @param double $amount - сума на заявката
     * @return array $items - масив от продукти готови за запис
     */
    private function prepareProducts($products)
    {
    	$items = array();
    	$products = (array)$products;
    	foreach ($products as $index => $quantity){
    		list($productId, $policyId, $optional) = explode("|", $index);
    		
    		// При опционален продукт без к-во се продължава
    		if($optional == 'yes' && empty($quantity)) continue;
    		
    		// Намира се кой детайл отговаря на този продукт
    		$obj = (object)$this->findDetail($productId, $policyId, $quantity, $optional);
            $items[] = (object)array('policyId'  => $obj->policyId,
        					         'productId' => $obj->productId,
        					 		 'discount'  => $obj->discount,
        					 		 'quantity'  => $obj->quantity,
        					 		 'price'     => $obj->price);
    	}
    	
    	return $items;
    }
    
    
    /**
     * Помощна ф-я за намиране на записа съответстващ на избраното к-во
     * @param int $productId - ид на продукт
     * @param int $policyId - политика
     * @param int $quantity - к-во
     * @param enum(yes/no) $optional - дали продукта е опционален
     * @return stdClass $val - обект съответсващ на детайл
     */
    private function findDetail($productId, $policyId, $quantity, $optional)
    {
    	// Първо се проверява имали запис за този продукт с това к-во
    	$val = array_values( array_filter(static::$cache, 
    		function ($val) use ($productId, $policyId, $quantity, $optional) {
           				if($val->optional == $optional && $val->productId == $productId && $val->policyId == $policyId && ($val->quantity == $quantity && $quantity)){
            				return $val;
            			}}));
            			
        // Ако к-то е ръчно въведено, се връща първия запис
        // съответстващ на първото срещане на продукта
        if(!$val){
        	$val = array_values( array_filter(static::$cache, 
    		function ($val) use ($productId, $policyId, $optional) {
           				if($val->optional == $optional && $val->productId == $productId && $val->policyId == $policyId){
            				return $val;
            			}}));
            			
            // Присвояване на к-то
            $val[0]->quantity = $quantity;
        }
    	
        return $val[0];
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * @param int $quotationId - ид на офертата
     * @param int $id - ид на записа ако има
     * @return core_Form - готовата форма
     */
    private function getFilterForm($quotationId, $id)
    {
    	$form = cls::get('core_Form');
    	$filteredProducts = $this->filterProducts($quotationId);
    	
    	foreach ($filteredProducts as $index => $product){
    		if($product->optional == 'yes') {
    			$product->title = "Опционални->{$product->title}";
    			$product->options = array('' => '&nbsp;') + $product->options;
    			$mandatory = '';
    		} else {
	    		if(count($product->options) > 1) {
	    			$product->options = array('' => '&nbsp;') + $product->options;
	    			$mandatory = 'mandatory';
	    		} else {
	    			$mandatory = '';
	    		}
    		}
    		
    		$form->FNC($index, "double(decimals=2)", "width=7em,input,caption={$product->title},{$mandatory}");
    		if($product->suggestions){
    			$form->setSuggestions($index, $product->options);
    		} else {
    			$form->setOptions($index, $product->options);
    		}
    	}
    	
    	if($id && Request::get('edit')){
    		if($fRec = (object)$this->fetchField($id, 'data')){
    			$form->rec = $fRec;
    		}
    		$form->title = tr("Редактиране на") . " |*&nbsp;" . $this->getLink($id);
    	} else {
    		$form->title = tr("Заявка към") . " |*&nbsp;" . cls::get('sales_Quotations')->getLink($quotationId);
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png');
    	
    	return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     * @param int $quoteId - ид на оферта
     * @return array $products - филтрираните продукти
     */
    private function filterProducts($quoteId)
    {
    	$products = array();
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$quoteId}");
    	$query->orderBy('optional', 'ASC');
    	static::$cache = $query->fetchAll();
    	while ($rec = $query->fetch()){
    		$index = "{$rec->productId}|{$rec->policyId}|{$rec->optional}";
    		if(!array_key_exists($index, $products)){
    			$title = cls::get($rec->policyId)->getProductMan()->getTitleById($rec->productId);
    			$products[$index] = (object)array('title' => $title, 'options' => array(), 'optional' => $rec->optional, 'suggestions' => FALSE);
    		}
    		if($rec->optional == 'yes'){
    			$products[$index]->suggestions = TRUE;
    		}
    		if($rec->quantity){
    			$products[$index]->options[$rec->quantity] = $rec->quantity;
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = self::fetchRec($id);
    	$query = $this->sales_SaleRequestDetails->getQuery();
    	$details = $query->where("#requestId = {$id}")->fetchAll();
    	
    	$result = new bgerp_iface_DealResponse();
    	$result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        $result->agreed->amount                = $rec->amount;
        $result->agreed->currency              = $rec->paymentCurrencyId;
        if($rec->deliveryPlaceId){
        	$placeId = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id');
        	$result->agreed->delivery->location  = $placeId;
        }
        $result->agreed->delivery->term        = $rec->deliveryTermId;
        $result->agreed->payment->method       = $rec->paymentMethodId;
    	
    	foreach ($details as $dRec) {
    		$Class = ($dRec->classId) ? cls::get($dRec->classId) : cls::get($dRec->policyId)->getProductMan();
    		$pInfo = $Class->getProductInfo($dRec->productId);

    		$p = new bgerp_iface_DealProduct();
            $p->classId     = $Class->getClassId();
            $p->productId   = $dRec->productId;
            $p->packagingId = NULL;
            $p->discount    = $dRec->discount;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $pInfo->productRec->measureId;
            $result->agreed->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if(($action == 'add') && isset($rec)){
    		if(!$rec->originId){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit') && isset($rec)){
    		$res = 'no_one';
    	}
    	
    	if(($action == 'activate') && isset($rec) && $rec->state == 'draft'){
    		$dQuery = $mvc->sales_SaleRequestDetails->getQuery();
    		$dQuery->where("#requestId = {$rec->id}");
    		if($dQuery->count()){
    			$res = 'ceo,sales';
    		}
    	}
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Заявка №" . $this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
    /**
     * Обработка на завката
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {	
    	$rec = &$data->rec;
    	
    	// Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();
		$address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }
    	$data->row->MyCompany      = $ownCompanyData->company;
        $data->row->MyCountry      = $ownCompanyData->country;
        $data->row->MyAddress      = $address;
        $data->row->MyCompanyVatNo = $ownCompanyData->vatNo;
        
        $contragentClass = doc_Folders::fetchCoverClassId($data->rec->folderId);
        $contragenId = doc_Folders::fetchCoverId($data->rec->folderId);
        $contragent = new core_ObjectReference($contragentClass, $contragenId);
        $cdata = sales_Sales::normalizeContragentData($contragent->getContragentData());
        $data->row->contragentName = $cdata->contragentName;
        $data->row->contragentCountry = $cdata->contragentCountry;
        $data->row->contragentAddress = $cdata->contragentAddress;
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->title = tr("Заявка|* №{$rec->id}");
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id));
    	
	    	if(doc_Folders::haveRightFor('single', $rec->folderId)){
	    		$img = doc_Folders::getIconImg($rec->folderId);
	    		$attr = array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . $img . ');');
	    		$link = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
            	$row->folderId = ht::createLink($row->folderId, $link, NULL, $attr);
	    	}
	    	
	    	if($rec->state == 'draft'){
	    		$img = "<img src=" . sbf('img/16/edit-icon.png') . "/>";
	    		$row->id = ht::createLink($img, array('sales_SaleRequests', 'CreateFromOffer', $rec->id, 'originId' => $rec->originId, 'ret_url' => TRUE, 'edit' => TRUE)) . " " . $row->id;
	    	}
    	}
	    
	    if($fields['-single']){
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
	    	}
	    	
	    	if($rec->state == 'draft'){
	    		list($rec->amount, $rec->discount) = $mvc->calcTotal($rec, $rec->vat);
	    	}
	    	
	    	$row->amount = $mvc->fields['amount']->type->toVerbal($rec->amount / $rec->rate);
	    	if($rec->discount){
	    		$row->discount = $mvc->fields['discount']->type->toVerbal($rec->discount / $rec->rate);
	    		$row->discountCurrencyId = $row->paymentCurrencyId;
	    	}
	    	
	    	$row->chargeVat = ($rec->vat == 'yes') ? tr('с ДДС') : tr('без ДДС');
	    	
	    	$origin = doc_Containers::getDocument($rec->originId);
	    	$row->originLink = $origin->getDocumentRow()->title;
	    }
    }
    
    
    /**
     * Изчислява в реално време общата сума на заявката, при активация тази
     * сума ще се запише в модела
     * @param stdClass $rec - запис от модела
     * @return double $total - общата сума на заявката
     */
    private function calcTotal($rec, $vat)
    {
    	$detailQuery = $this->sales_SaleRequestDetails->getQuery();
    	$detailQuery->where("#requestId = {$rec->id}");
    	
    	$discount = $total = 0;
    	while ($d = $detailQuery->fetch()){
    		if($vat == 'yes'){
    			$productMan = ($d->classId) ? cls::get($d->productManId) : cls::get($d->policyId)->getProductMan();
    			$d->price *= 1 + $productMan->getVat($d->productId);
    		}
    		
    		$amount = $d->price * $d->quantity;
    		if($d->discount){
    			$discount += $amount * $d->discount;
    		}
    		
    		$total += $amount;
    	}
    	
    	$afterDisc = ($discount != 0) ? $total - $discount : NULL;
    	return array($total, $afterDisc);
    }
    
    
    /**
	 * След активация се записва сумата на заявката
	 */
	public static function on_Activation($mvc, &$rec)
    {
    	$vat = static::fetchField($rec->id, 'vat');
    	$rec->state = 'active';
    	list($rec->amount, $rec->discount) = $mvc->calcTotal($rec, $vat);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if ($data->rec->state == 'active') {
    		$data->toolbar->addBtn('Продажба', array('sales_Sales', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'warning=Наистина ли искате да създадете нова продажба?', 'order=22,ef_icon = img/16/star_2.png,title=Създаване на нова продажба по заявката');
    	}
    	
    	if($data->rec->state == 'draft') {
	       	$data->toolbar->addBtn('Редакция', array('sales_SaleRequests', 'CreateFromOffer', $data->rec->id ,'originId' => $data->rec->originId, 'ret_url' => TRUE, 'edit' => TRUE), NULL, 'ef_icon=img/16/edit-icon.png,title=Редактиране на заявката');	
	   }
    }
}