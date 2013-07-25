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
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент,fromOffer');
        $this->FLD('contragentId', 'int', 'input=hidden,fromOffer');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)','caption=Плащане->Метод,width=8em,fromOffer');
        $this->FLD('paymentCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em,fromOffer');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Плащане->Курс,width=8em,fromOffer');
        $this->FLD('vat', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)','caption=Плащане->ДДС,oldFieldName=wat,fromOffer');
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,width=8em,fromOffer');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,width=10em,fromOffer');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Общо,input=none,summary=amount');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
 	function act_CreateFromOffer()
 	{
 		$this->requireRightFor('add');
 		expect($originId = Request::get('originId'));
        $origin = doc_Containers::getDocument($originId);
    	expect($origin->className == 'sales_Quotations');
    	$originRec = $origin->fetch();
    	$id = static::fetchField("#originId = {$originRec->containerId} AND #state = 'draft'", 'id');
    	
    	// Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($origin->that);
        /*if($id){
        	 $exRec = $this->fetch($id);
        }
        
 		if ($this->haveRightFor('activate', $exRec)) {
            $form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=10.00019', 'ef_icon = img/16/lightning.png');
        }*/
        $fRec = $form->input();
        if($form->isSubmitted()){
        	$rec = (object)array('originId' => $originId,
        						 'threadId' => $originRec->threadId,
        						 'folderId' => $originRec->folderId,
        						 'id' => $id);
        	
        	if($form->cmd == 'active'){
        		$rec->state = 'active';
        	}
        	
        	// Подготовка на данните
        	$id = $this->saveData($rec, $fRec, $originRec);
        	
        	return Redirect(array($this, 'single', $id));
        }
        
        return $this->renderWrapping($form->renderHtml());
 	}
    
    
    /**
     * Записване на данните от офертата в заявката
     * формат на bgerp_iface_DealResponse
     * @param stdClass $rec
     * @param stdClass $quoteRec
     */
    private function saveData($rec, $dRec, $quoteRec)
    {
    	$fields = $this->selectFields("#fromOffer");
    	foreach($fields as $name => $fld){
    		if(isset($quoteRec->{$name})){
    			$rec->{$name} = $quoteRec->{$name};
    		}
    	}
    	
    	$items = $this->prepareProducts($dRec, $quoteRec->folderId);
    	$this->save($rec);
    	$this->sales_SaleRequestDetails->delete("#requestId = {$rec->id}");
    	
    	foreach($items as $item){
    		$item->requestId = $rec->id;
    		$this->sales_SaleRequestDetails->save($item);
    	}
    	
    	return $rec->id;
    }
    
    
    /**
     * Подготовка на продуктите от формата с вече уточнените
     * к-ва във подходящ вид
     * @param array $products - продуктите върнати от формата
     * @param double $amount - сума на заявката
     * @param int $folderId - ид на папката
     * @return array $items - масив от продукти готови за запис
     */
    private function prepareProducts($products, $folderId)
    {
    	$contragentClass = doc_Folders::fetchCoverClassId($folderId);
        $contragenId = doc_Folders::fetchCoverId($folderId);
    	
    	$items = array();
    	$products = (array)$products;
    	foreach ($products as $index => $quantity){
    		list($productId, $policyId, $optional) = explode("|", $index);
    		
    		// При опционален продукт без к-во се продължава
    		if($optional == 'yes' && empty($quantity)) continue;
    		
    		// Намира се кой детайл отговаря на този продукт
    		$obj = array_values(
    			array_filter(static::$cache, function ($val) use ($productId, $policyId, $quantity) {
           				if($val->productId == $productId && $val->policyId == $policyId && (($val->quantity == $quantity) || ($val->quantity === NULL))){
            				return $val;
            			}}));
            			
            if(!$obj[0]->quantity){
            	$obj[0]->quantity = $quantity;
            }
            			
            $items[] = (object)array('policyId'  => $obj[0]->policyId,
        					 'productId' => $obj[0]->productId,
        					 'discount'  => $obj[0]->discount,
        					 'quantity'  => $obj[0]->quantity,
        					 'price'     => $obj[0]->price);
    	}
    	
    	return $items;
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * @param int $quotationId - ид на офертата
     * @return core_Form - готовата форма
     */
    private function getFilterForm($quotationId)
    {
    	$form = cls::get('core_Form');
    	$filteredProducts = $this->filterProducts($quotationId);
    	
    	foreach ($filteredProducts as $index => $product){
    		if($product->optional == 'yes') {
    			$product->title = "Опционални->{$product->title}";
    			$product->options = $product->options;
    			$mandatory = '';
    		} else {
	    		if(count($product->options) > 1) {
	    			$product->options = array('&nbsp;' => '&nbsp;') + $product->options;
	    			$mandatory = 'mandatory';
	    		} else {
	    			$mandatory = '';
	    		}
    		}
    		
    		$form->FNC($index, "double(decimals=2)", "width=7em,input,caption={$product->title},{$mandatory}");
    		if($product->suggestions){
    			if(count($product->options) > 1){
    				$form->setSuggestions($index, $product->options);
    			}
    		} else {
    			$form->setOptions($index, $product->options);
    		}
    	}
    	
    	$form->title = tr("Заявка към Оферта") . " #" . cls::get('sales_Quotations')->getHandle($quotationId);
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', array('sales_Quotations', 'single', $quotationId), 'ef_icon = img/16/close16.png');
    	
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
    		if(!$rec->quantity && $rec->optional == 'yes'){
    			$products[$index]->suggestions = TRUE;
    		}
    		$products[$index]->options[$rec->quantity] = $rec->quantity;
    	}
    	
    	return $products;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	// @TODO
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
        $row->title = "Заявка №" .$this->abbr . $rec->id;
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
    	$row->title = tr("Заявка за продажба|* №{$rec->id}");
    	$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id));
    	
    	if($fields['-list']){
	    	if(doc_Folders::haveRightFor('single', $rec->folderId)){
	    		$img = doc_Folders::getIconImg($rec->folderId);
	    		$attr = array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . $img . ');');
	    		$link = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
            	$row->folderId = ht::createLink($row->folderId, $link, NULL, $attr);
	    	}
	    }
	    
	    if($fields['-single']){
	    	if(!$rec->amount){
	    		$row->amount = $mvc->fields['amount']->type->toVerbal($mvc->calcTotal($rec));
	    	}
	    }
    }
    
    
    /**
     * Изчислява в реално време общата сума на заявката, при активация тази
     * сума ще се запише в модела
     * @param stdClass $rec - запис от модела
     * @return double $total - общата сума на заявката
     */
    private function calcTotal($rec)
    {
    	$total = 0;
    	$applyVat = ($rec->vat == 'yes') ? TRUE : FALSE;
    	$detailQuery = $this->sales_SaleRequestDetails->getQuery();
    	$detailQuery->where("#requestId = {$rec->id}");
    	while ($d = $detailQuery->fetch()){
    		if($applyVat){
    			$productMan = ($d->productManId) ? cls::get($d->productManId) : cls::get($d->policyId)->getProductMan();
    			$d->price *= 1 + $productMan->getVat($d->productId);
    		}
    		$total += $d->price * $d->quantity;
    	}
    	
    	return $total / $rec->rate;
    }
    
    
    /**
	 * След активация се записва сумата на заявката
	 */
	public static function on_Activation($mvc, &$rec)
    {
    	$rec = $mvc->fetch($rec->id);
    	$rec->state = 'active';
    	$rec->amount = $mvc->calcTotal($rec);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if ($data->rec->state == 'active') {
    		$data->toolbar->addBtn('Продажба', array('sales_Sales', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'warning=Наистина ли искате да създадете нова продажба?', 'order=22,ef_icon = img/16/star_2.png,title=Създаване на нова продажба по заявката');
    	}
    }
}