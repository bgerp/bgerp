<?php
/**
 * Документ "Заявка за продажба"
 *
 * Мениджър на документи за Заявки за продажба, от фактура
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
    public $loadList = 'sales_Wrapper, plg_Printing, doc_DocumentPlg,
    					 doc_ActivatePlg, bgerp_plg_Blank';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
	/**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заявка за продажба';
    
    
    /**
     * Работен кеш за вече извлечените продукти
     */
    protected static $cache = array();

    
    /**
     * Шаблон за еденичен изглед
     */
    var $singleLayoutFile = 'sales/tpl/SingleSaleRequest.shtml';
    
   
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
    }
    
    
 	/**
     * Преди всеки екшън на мениджъра-домакин.
     * Показва форма за уточняване на к-та
     * на оферираните продукти
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне или редакция на документ
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
        expect($originId = Request::get('originId'));
        $origin = doc_Containers::getDocument($originId);
    	expect($origin->className == 'sales_Quotations');
    	$originRec = $origin->fetch();
    	
        $form = $mvc->getFilterForm($origin->that);
        $fRec = $form->input();
        if($form->isSubmitted()){
        	$rec = (object)array('originId' => $originId,
        						 'threadId' => $originRec->threadId,
        						 'folderId' => $originRec->folderId,);
        	$rec->data = (array)$mvc->prepareData($fRec, $originRec);
        	$rec->id = static::fetchField("#originId = {$originId} AND #state = 'draft'", 'id');
        	
        	$mvc->save($rec);
        	
        	return Redirect(array($mvc, 'single', $rec->id));
        }
        
        $tpl = $mvc->renderWrapping($form->renderHtml());
        return FALSE;
    }
    
    
    /**
     * Подготвя данните получени от формата и от заявката във
     * формат на bgerp_iface_DealResponse
     * @param stdClass $rec
     * @param stdClass $quoteRec
     */
    private function prepareData($rec,  $quoteRec)
    {
    	$items = $this->prepareProducts($rec, $amount);
    	
        $result = new stdClass();
        $result->dealType = 'sale'; //bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                  = $amount;
        $result->agreed->currency                = $quoteRec->paymentCurrencyId;
        if($rec->deliveryPlaceId){
        	$result->agreed->delivery->location  = crm_Locations::fetchField("#title = '{$quoteRec->deliveryPlaceId}'", 'id');
        }
        $result->agreed->delivery->term          = $quoteRec->deliveryTermId;
    	$result->agreed->payment->method         = $quoteRec->paymentMethodId;
    	
    	$result->agreed->products = $items;
    	
    	return $result;
    }
    
    
    /**
     * Подготовка на продуктите от формата с вече уточнените
     * к-ва във подходящ вид
     * @param array $products - продуктите върнати от формата
     * @param double $amount - сума на заявката
     */
    private function prepareProducts($products, &$amount)
    {
    	$amount = 0;
    	$items = array();
    	$products = (array)$products;
    	foreach ($products as $index => $quantity){
    		list($productId, $policyId, $optional) = explode("|", $index);
    		if(empty($quantity)) continue;
    		
    		$obj = array_values(
    			array_filter(static::$cache, function ($val) use ($productId, $policyId, $quantity) {
           				if($val->productId == $productId && $val->policyId == $policyId && $val->quantity == $quantity){
            				return $val;
            			}}));
            
            $items[] = (array)new sales_model_QuotationProduct($obj[0]);
    		$amount += $quantity * ($obj[0]->price * (1 + $obj[0]->discount));
    	}
    	
    	return $items;
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * @param int $quotationId
     */
    private function getFilterForm($quotationId)
    {
    	$form = cls::get('core_Form');
    	$filteredProducts = $this->filterProducts($quotationId);
    	foreach ($filteredProducts as $index => $product){
    		if($product->optional == 'yes') {
    			$product->title = "Опционални->{$product->title}";
    			$product->options = array('' => '') + $product->options;
    			$mandatory = '';
    		} else {
	    		if(count($product->options) > 1) {
	    			$product->options = array('' => '') + $product->options;
	    			$mandatory = 'mandatory';
	    		} else {
	    			$mandatory = '';
	    		}
    		}
    		
    		$form->FNC($index, "double(decimals=2)", "width=7em,input,caption={$product->title},{$mandatory}");
    		$form->setOptions($index, $product->options);
    	}
    	
    	$form->title = tr("Уточняване на количествата към") . " #" . cls::get('sales_Quotations')->getHandle($quotationId);
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', array('sales_Quotations', 'single', $quotationId), 'ef_icon = img/16/close16.png');
    	
    	return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     * @param int $quoteId - ид на оферта
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
    			$products[$index] = (object)array('title' => $title, 'options' => array(), 'optional' => $rec->optional);
    		}
    		$products[$index]->options[$rec->quantity] = $rec->quantity;
    	}
    	
    	return $products;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	// данните на заявката са във вид 
    	// bgerp_iface_DealResponse и директно се връщат
    	return (object)$this->fetchField($id, 'data');
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
    	
    	if(($action == 'activate') && isset($rec) && $rec->state != 'active'){
    		$res = 'ceo,sales';
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
        $row->title = "Заявка за продажба №" .$this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
	/**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
        }
    }
    
    
    /**
     * Обработка на завката
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {	
    	$mvc->prepareMasterRow($data);
    	$mvc->prepareDetails($data);
    }
    
    
    /**
     * Подготвя вербалното представяне на заявката
     * @param stdClass $data
     */
    private function prepareMasterRow(&$data)
    {
    	$rec = &$data->rec->data['agreed'];
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	$data->row->amountDeal = $double->toVerbal($rec->amount);
    	$data->row->currencyId = $rec->currency;
    	$data->row->currencyRateText = $double->toVerbal(currency_CurrencyRates::getRate($data->rec->createdOn, $rec->currency, NULL));
    	$data->row->paymentMethodId = salecond_PaymentMethods::getTitleById($rec->payment->method);
    	$data->row->deliveryTermId = $varchar->toVerbal(salecond_DeliveryTerms::fetchField($rec->delivery->term, 'codeName'));
    	
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
        $cdata   = sales_Sales::normalizeContragentData($contragent->getContragentData());
        $data->row->contragentName = $cdata->contragentName;
        $data->row->contragentCountry = $cdata->contragentCountry;
        $data->row->contragentAddress = $cdata->contragentAddress;
    }
    
    
    /**
     * Подготвя детайлите
     */
    private function prepareDetails(&$data)
    {
    	$details = &$data->rec->data['agreed']->products;
    	$detailsRow = array();
    	$data->hasDiscount = FALSE;
    	$origin = doc_Containers::getDocument($data->rec->originId);
    	$originRec = $origin->fetch();
    	$applyVat = ($originRec->vat == 'yes') ? TRUE : FALSE;
    	
    	$varchar = cls::get('type_Varchar');
    	$int = cls::get('type_Int');
    	$percent = cls::get('type_Percent');
    	$double = cls::get('type_Double');
    	
    	$i = 1;
    	foreach ($details as $d){
    		$double->params['decimals'] = 2;
    		$row = new stdClass();
    		$productMan = cls::get($d['classId']);
    		$row->id = $i;
    		$row->productId = $productMan->getTitleById($d['productId']);
    		$row->productId = ht::createLinkRef($row->productId, array($productMan, 'single', $d['productId']));
    		if($applyVat){
    			$vat = $productMan->getVat($d['productId']);
    			$d['price'] = $d['price'] * (1 + $vat);
    		}
    		$price = currency_CurrencyRates::convertAmount($d['price'], $originRec->modifiedOn, NULL, $data->rec->data['agreed']->currency);
    		$row->price = $double->toverbal($price);
    		$measureId = $productMan->getProductInfo($d['productId'], NULL)->productRec->measureId;
    		$row->uomId = cat_UoM::getTitleById($measureId);
    		if($d['discount']){
    			$row->discount = $percent->toVerbal($d['discount']);
    			$data->hasDiscount = TRUE;
    		}
    		$row->amount = $double->toVerbal($price * $d['quantity']);
    		$double->params['decimals'] = strlen(substr(strrchr($d['quantity'], "."), 1));
    		$row->quantity = $double->toVerbal($d['quantity']);
    		$detailsRow[] = $row;
    		$i++;
    	}
    	
    	$data->rows = $detailsRow;
    }
    
    
    /**
     * Рендиране на детайлите
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$discountCaption = ($data->hasDiscount) ? 'discount=Отстъпка,' : '';
    	$mvc1 = cls::get('sales_SalesDetails');
    	$tInst = cls::get('core_TableView', array('mvc' => $mvc1));
        $table = $tInst->get($data->rows, "id=№,
        								   productId=Продукт, 
                                           quantity=К-во,
                                           uomId=Мярка,
                                           price=Цена,
                                           {$discountCaption}
                                           amount=Сума");
    	$tpl->replace($table, 'REQUEST_DETAILS');
    }
    
    
    /**
     * След активация се пренасочва към създаването на продажба
     */
    function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	// При първоначална активация, се пренасочва към продажба
    	if($rec->state == 'active' && empty($rec->brState)){
    		return Redirect(array('sales_Sales', 'add', 'originId' => $rec->containerId));
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->title = tr("Заявка за продажба|* №{$rec->id}");
    	$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id));
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
}