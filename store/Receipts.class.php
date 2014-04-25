<?php
/**
 * Клас 'store_Receipts'
 *
 * Мениджър на Складовите разписки, Само складируеми продукти могат да се заприхождават в склада
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Receipts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Складови разписки';


    /**
     * Абревиатура
     */
    public $abbr = 'Sr';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transactionIntf_Receipt, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, store_DocumentWrapper, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, doc_plg_BusinessDoc, store_plg_Document';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDelivered, weight, volume, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_ReceiptDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Складова разписка';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutReceipt.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.4|Логистика";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';


    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em,input=hidden');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=В склад, mandatory'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Обект от,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        
        // Допълнително
        $this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('isFull', 'enum(yes,no)', 'input=none,caption=Запълнен ли е,notNull,default=yes');
    }



    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    { 
        // Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$query = $this->store_ReceiptDetails->getQuery();
        $query->where("#receiptId = '{$id}'");
        
        $recs = $query->fetchAll();
        
        price_Helper::fillRecs($recs, $rec);
        $measures = $this->getMeasures($recs);
    	
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $rec->_total->amount + $rec->_total->vat : $rec->_total->amount;
        $amount -= $rec->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $rec->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $rec->_total->discount * $rec->currencyRate;
        
         // Записване в кеш полето дали има още продукти за добавяне
        $origin = $this->getOrigin($rec);
		$dealAspect = $origin->getAggregateDealInfo()->agreed;
		$invProducts = $this->getDealInfo($rec->id)->shipped;
        $rec->isFull = (!bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'storable')) ? 'yes' : 'no';
        
        $this->save($rec);
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
        if(count($mvc->updated)){
        	foreach ($mvc->updated as $id) {
	        	$mvc->updateMaster($id);
	        }
        }
    }
    
    
    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $origin = static::getOrigin($rec);
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на СР
        
        if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
            /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
            $aggregatedDealInfo = $origin->getAggregateDealInfo();
            
            $remainingToShip = clone $aggregatedDealInfo->agreed;
            $remainingToShip->pop($aggregatedDealInfo->shipped);
            
            /* @var $product bgerp_iface_DealProduct */
            foreach ($remainingToShip->products as $product) {
            	$info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
                
            	// Пропускат се експедираните и нескладируемите продукти
            	if (!isset($info->meta['canStore']) || $product->quantity <= 0) continue;
                
                $shipProduct = new stdClass();
                $shipProduct->receiptId   = $rec->id;
                $shipProduct->classId     = $product->classId;
                $shipProduct->productId   = $product->productId;
                $shipProduct->packagingId = $product->packagingId;
                $shipProduct->quantity    = $product->quantity;
                $shipProduct->price       = $product->price;
                $shipProduct->uomId       = $product->uomId;
                $shipProduct->discount    = $product->discount;
                $shipProduct->weight      = $product->weight;
                $shipProduct->volume      = $product->volume;
                $shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
                
                $mvc->store_ReceiptDetails->save($shipProduct);
            }
        }
    }


    /**
     * След оттегляне на документа
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        // Нотифициране на origin-документа, че някой от веригата му се е променил
        if ($origin = $mvc->getOrigin($id)) {
            $ref = new core_ObjectReference($mvc, $id);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
        }
    }
    
    
	/**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $Companies = cls::get('crm_Companies');
        $row->MyCompany = $Companies->getTitleById($ownCompanyData->companyId);
        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $ContragentClass = cls::get($rec->contragentClassId);
    	$row->contragentName = $ContragentClass->getTitleById($rec->contragentId);
        $row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId);
    }
    
    
    /**
     * След рендиране на сингъла
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = price_Helper::prepareSummary($rec->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    	}
    	
    	
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	$data->row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$data->row->id}</b> ({$data->row->state})";
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'DealInfo', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
    	}
    	
    	if($rec->state == 'active' && purchase_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    		$originId = doc_Threads::getFirstContainerId($rec->threadId);
	    	$data->toolbar->addBtn("Вх. фактура", array('purchase_Invoices', 'add', 'originId' => $originId, 'ret_url' => TRUE), 'ef_icon=img/16/invoice.png,title=Създаване на входяща фактура,order=9.9993');
	    }
	}
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec  = &$form->rec;
        
        $form->setDefault('valior', dt::now());
        $form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	if(!trans_Lines::count("#state = 'active'")){
        	$form->setField('lineId', 'input=none');
        }
        
        // Поле за избор на локация - само локациите на контрагента по покупката
        $form->getField('locationId')->type->options = 
            array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
            
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id)) {
        	
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getAggregateDealInfo();
                
            $form->rec->currencyId = $dealInfo->agreed->currency;
            $form->rec->currencyRate = $dealInfo->agreed->rate;
            $form->rec->locationId = $dealInfo->agreed->delivery->location;
            $form->rec->deliveryTime = $dealInfo->agreed->delivery->time;
            $form->rec->chargeVat = $dealInfo->agreed->vatType;
            $form->rec->storeId = $dealInfo->agreed->delivery->storeId;
        }
    }
    
    
	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	
        	if($rec->lineId){
        		$dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
        		
        		// Ако има избрана линия и метод на плащане, линията трябва да има подочетно лице
        		if($pMethods = $dealInfo->agreed->payment->method){
        			if(cond_PaymentMethods::isCOD($pMethods) && !trans_Lines::hasForwarderPersonId($rec->lineId)){
        				$form->setError('lineId', 'При наложен платеж, избраната линия трябва да няма материално отговорно лице !');
        			}
        		}
        	}
        	
        	if(empty($rec->isFull)){
        		
        		// Сетване на кеш полето че ЕН-то не е запълнено
        		$rec->isFull = 'no';
        	}
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	@$amountDelivered = $rec->amountDelivered / $rec->currencyRate;
    	$row->amountDelivered = $mvc->fields['amountDelivered']->type->toVerbal($amountDelivered);
    		
    	if(!$rec->weight) {
    		$row->weight = "<span class='quiet'>0</span>";
    	}
    		
    	if(!$rec->volume) {
    		$row->volume = "<span class='quiet'>0</span>";
    	}
    		
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->amountDelivered){
    			$row->amountDelivered = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDelivered}";
    		} else {
    			$row->amountDelivered = "<span class='quiet'>0.00</span>";
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		$mvc->prepareHeaderInfo($row, $rec);
    	}
    }


    /**
     * СР не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли СР да се добави в посочената нишка?
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-продажба
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Ако началото на треда е активирана покупка
    	if(($firstDoc->instance() instanceof purchase_Purchases) && $docState == 'active'){
    		
    		// Ако има поне един складируем продукт в покупката
    		return $firstDoc->hasStorableProducts();
    	}
    	
    	return FALSE;
    }
        
    
    /**
     * @param int $id key(mvc=store_Receipts)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object)array(
            'title'    => $title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в СР-то
     * @param int $id - ид на СР
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->store_ReceiptDetails->getQuery();
    	$dQuery->EXT('state', 'store_Receipts', 'externalKey=receiptId');
    	$dQuery->where("#receiptId = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
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
        $rec = new store_model_Receipt($id);
        
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
		
		$result->shipped->amount             = $rec->amountDelivered;
		$result->shipped->currency           = $rec->currencyId;
		$result->shipped->rate 				 = $rec->currencyRate;
		$result->shipped->valior 			 = $rec->valior;
        $result->shipped->vatType            = $rec->chargeVat;
        $result->shipped->delivery->location = $rec->locationId;
        $result->shipped->delivery->term     = $rec->termId;
        $result->shipped->delivery->time     = $rec->deliveryTime;
        $result->shipped->delivery->storeId  = $rec->storeId;
        
        /* @var $dRec store_model_Receipt */
        foreach ($rec->getDetails('store_ReceiptDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            $p->weight      = $dRec->weight;
            $p->volume      = $dRec->volume;
            
        	if($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate'){
            	
            	// Отбелязваме че има ддс за начисляване от експедирането
	            $ProductMan = cls::get($dRec->classId);
	            $vat = $ProductMan->getVat($dRec->productId, $rec->valior);
	            $vatAmount = $dRec->price * $dRec->quantity * $vat;
	            $code = $dRec->classId . "|" . $dRec->productId . "|" . $dRec->packagingId;
	            $result->invoiced->vatToCharge[$code] += $vatAmount;
            }
            
            $result->shipped->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
 	/**
     * Дебъг екшън показващ агрегираните бизнес данни
     */
    function act_DealInfo()
    {
    	requireRole('debug');
    	expect($id = Request::get('id', 'int'));
    	$info = $this->getDealInfo($id);
    	bp($info->shipped);
    }
    
    
    /**
     * Помощен метод за показване на документа в транспортните линии
     * @param stdClass $rec - запис на документа
     * @param stdClass $row - вербалния запис
     */
    private function prepareLineRows($rec)
    {
    	$row = new stdClass();
    	$fields = $this->selectFields();
    	$fields['-single'] = TRUE;
    	$oldRow = $this->recToVerbal($rec, $fields);
    	$amount = currency_Currencies::round($rec->amountDelivered / $rec->currencyRate, $dealInfo->currency);
    	
    	$row->weight = $oldRow->weight;
    	$row->volume = $oldRow->volume;
    	$row->collection = "<span class='cCode'>{$rec->currencyId}</span> " . $this->fields['amountDelivered']->type->toVerbal($amount);
    	$row->rowNumb = $rec->rowNumb;
    	
    	$row->address = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	$row->address .= ", " . (($rec->locationId) ? crm_Locations::getAddress($rec->locationId) : $oldRow->contragentAddress);
    	trim($row->address, ', ');
    	
    	$row->TR_CLASS = ($rec->rowNumb % 2 == 0) ? 'zebra0' : 'zebra1';
    	$row->docId = $this->getDocLink($rec->id);
    	
    	return $row;
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareReceipts($data)
    {
    	$masterRec = $data->masterData->rec;
    	$query = $this->getQuery();
    	$query->where("#lineId = {$masterRec->id}");
    	$query->where("#state = 'active'");
    	$query->orderBy("#createdOn", 'DESC');
    	
    	$i = 1;
    	while($dRec = $query->fetch()){
    		$dRec->rowNumb = $i;
    		$data->receipts[$dRec->id] = $this->prepareLineRows($dRec);
    		$i++;
    	}
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderReceipts($data)
    {
    	$table = cls::get('core_TableView');
    	$fields = "rowNumb=№,docId=Документ,weight=Тегло,volume=Обем,collection=Инкасиране,address=@Адрес";
    	
    	return $table->get($data->receipts, $fields);
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата складова разписка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = store_ReceiptDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#receiptId = '{$rec->id}'");
     	
	        while ($recDetails = $query->fetch()){
	        	// взимаме заглавията на продуктите
	        	$productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
	        	// и ги нормализираме
	        	$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	        }
	        
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
     }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Складова разписка|* №") . $rec->id;
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$mvc->setTemplates($res);
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    private function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Складова разписка', 
    					  'content' => 'store/tpl/SingleLayoutReceipt.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Складова разписка с цени', 
    					  'content' => 'store/tpl/SingleLayoutReceiptPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	
    	$skipped = $added = $updated = 0;
    	foreach ($tplArr as $arr){
    		$arr['docClassId'] = $this->getClassId();
    		doc_TplManager::addOnce($arr, $added, $updated, $skipped);
    	}
    	
    	$res .= "<li><font color='green'>Добавени са {$added} шаблона за складови разписки, обновени са {$updated}, пропуснати са {$skipped}</font></li>";
    }
}