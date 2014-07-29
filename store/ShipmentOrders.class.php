<?php
/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания. Само складируеми продукти могат да се експедират
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrders extends core_Master
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Експедиционни нареждания';


    /**
     * Абревиатура
     */
    public $abbr = 'Exp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_ShipmentOrder, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, 
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, store_plg_Document';

    
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
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId, amountDelivered, amountDeliveredVat, weight, volume, createdOn, createdBy';

    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ShipmentOrderDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Експедиционно нареждане';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutShipmentOrder.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.3|Логистика";
   
    
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
    public $searchFields = 'locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden'); 
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено->Сума,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено->ДДС,input=none,summary=amount');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Обект до,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        
        // Допълнително
        $this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        
        $this->FLD('company', 'key(mvc=crm_Companies,select=name,allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Адрес за доставка->Фирма');
        $this->FLD('person', 'key(mvc=crm_Persons,select=name,allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Адрес за доставка->Лице, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адрес за доставка->Тел., changable, class=contactData');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава, class=contactData');
        $this->FLD('pCode', 'varchar', 'caption=Адрес за доставка->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Адрес за доставка->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адрес за доставка->Адрес, changable, class=contactData');
        
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    	$this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    	$this->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)','input=none,notNull,value=411');
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
    	
    	$query = $this->store_ShipmentOrderDetails->getQuery();
        $query->where("#shipmentId = '{$id}'");
        
        $recs = $query->fetchAll();
        
        deals_Helper::fillRecs($this, $recs, $rec);
        $measures = $this->getMeasures($recs);
    	
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amount -= $this->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
        
        // Записване в кеш полето дали има още продукти за добавяне
        $origin = $this->getOrigin($rec);
        
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
        // използваме го за автоматично попълване на детайлите на ЕН
        if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
            
        	// Ако документа е обратен не слагаме продукти по дефолт
        	if($rec->isReverse == 'yes') return;
            
            /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
            $aggregatedDealInfo = $origin->getAggregateDealInfo();
            $agreedProducts = $aggregatedDealInfo->get('products');
            
            if(count($agreedProducts)){
            	foreach ($agreedProducts as $product) {
            		$info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
            		 
            		// Колко остава за експедиране от продукта
            		$toShip = $product->quantity - $product->quantityDelivered;
            		 
            		// Пропускат се експедираните и нескладируемите продукти
            		if (!isset($info->meta['canStore']) || ($toShip <= 0)) continue;
            	
            		$shipProduct = new stdClass();
            		$shipProduct->shipmentId  = $rec->id;
            		$shipProduct->classId     = $product->classId;
            		$shipProduct->productId   = $product->productId;
            		$shipProduct->packagingId = $product->packagingId;
            		$shipProduct->quantity    = $toShip;
            		$shipProduct->price       = $product->price;
            		$shipProduct->uomId       = $product->uomId;
            		$shipProduct->discount    = $product->discount;
            		$shipProduct->weight      = $product->weight;
            		$shipProduct->volume      = $product->volume;
            		$shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
            	
            		$mvc->store_ShipmentOrderDetails->save($shipProduct);
            	}
            }
        }
    }
    
    
	/**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $ContragentClass = cls::get($rec->contragentClassId);
    	$cData = $ContragentClass->getContragentData($rec->contragentId);
    	$row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
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
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
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
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('locationId')->type->options = 
            array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        expect($origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId));
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        $dealInfo = $origin->getAggregateDealInfo();
        
        $form->setDefault('currencyId', $dealInfo->get('currency'));
        $form->setDefault('currencyRate', $dealInfo->get('rate'));
        $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
        $form->setDefault('deliveryTime', $dealInfo->get('deliveryTime'));
        $form->setDefault('chargeVat', $dealInfo->get('vatType'));
        $form->setDefault('storeId', $dealInfo->get('storeId'));
        
        if(!$dealInfo->get('vatType')){
        	$form->setField('chargeVat', 'input=input,important');
        }
        
        if($form->rec->id){
        	if($mvc->store_ShipmentOrderDetails->fetch("#shipmentId = {$form->rec->id}")){
        		$form->setReadOnly('chargeVat');
        	}
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	$dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
        	$operations = $dealInfo->get('allowedShipmentOperations');
        	$operation = $operations['delivery'];
        	$rec->accountId = $operation['debit'];
        	$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        	
        	if($rec->lineId){
        		
        		// Ако има избрана линия и метод на плащане, линията трябва да има подочетно лице
        		if($pMethods = $dealInfo->get('paymentMethodId')){
        			if(cond_PaymentMethods::isCOD($pMethods) && !trans_Lines::hasForwarderPersonId($rec->lineId)){
        				$form->setError('lineId', 'При наложен платеж, избраната линия трябва да има материално отговорно лице!');
        			}
        		}
        	}
        	
        	if($rec->locationId){
        		foreach (array('company','person','tel','country','pCode','place','address',) as $del){
        			 if($rec->$del){
        			 	$form->setError("locationId,{$del}", 'Не може да има избрана локация, и въведени адресни данни');
        			 	break;
        			 }
        		}
        	}
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	@$amountDelivered = $rec->amountDelivered / $rec->currencyRate;
    	$row->amountDelivered = $mvc->getFieldType('amountDelivered')->toVerbal($amountDelivered);
    		
    	if(!$rec->weight) {
    		$row->weight = "<span class='quiet'>0</span>";
    	}
    		
    	if(!$rec->volume) {
    		$row->volume = "<span class='quiet'>0</span>";
    	}
    	
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if(!$rec->amountDelivered){
    			$row->amountDelivered = "<span class='quiet'>0.00</span>";
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		$mvc->prepareHeaderInfo($row, $rec);
    	}
    }


    /**
     * ЕН не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * Допълнително, първия документ на нишка, в която е допустомо да се създаде ЕН трябва да
     * бъде от клас sales_Sales. Това се гарантира от @see store_ShipmentOrders::canAddToThread()
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли ЕН да се добави в посочената нишка?
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Може да се добавя само към активиран документ
    	if($docState == 'active'){
    		
    		if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
    			$dealInfo = $firstDoc->getAggregateDealInfo();
    			
    			return ($dealInfo->allowedShipmentOperations['delivery']) ? TRUE : FALSE;
    		}
    	}
    	
    	return FALSE;
    }
        
    
    /**
     * @param int $id key(mvc=store_ShipmentOrders)
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
     * Връща масив от използваните нестандартни артикули в ЕН-то
     * @param int $id - ид на ЕН
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->store_ShipmentOrderDetails->getQuery();
    	$dQuery->EXT('state', 'store_ShipmentOrders', 'externalKey=shipmentId');
    	$dQuery->where("#shipmentId = '{$id}'");
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
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
    	$rec = $this->fetchRec($id);
        
        // Конвертираме данъчната основа към валутата идваща от продажбата
        $aggregator->setIfNot('deliveryLocation', $rec->locationId);
        $aggregator->setIfNot('deliveryTime', $rec->deliveryTime);
        $aggregator->setIfNot('storeId', $rec->storeId);
        $aggregator->setIfNot('shippedValior', $rec->valior);
        
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$rec->id}");
        
        // Подаваме на интерфейса най-малката опаковка с която е експедиран продукта
        while ($dRec = $dQuery->fetch()) {
        	if(empty($dRec->packagingId)) continue;
        	
        	// Подаваме най-малката опаковка в която е експедиран продукта
            $push = TRUE;
            $index = $dRec->classId . "|" . $dRec->productId;
            $shipped = $aggregator->get('shippedPacks');
            if($shipped && isset($shipped[$index])){
            	if($shipped[$index]->inPack < $dRec->quantityInPack){
            		$push = FALSE;
            	} 
            } 
            
            // Ако ще обновяваме информацията за опаковката
            if($push){
            	$arr = (object)array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
            	$aggregator->push('shippedPacks', $arr, $index);
            }
        }
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
    	$row->collection = "<span class='cCode'>{$rec->currencyId}</span> " . $this->getFieldType('amountDelivered')->toVerbal($amount);
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
    public function prepareShipments($data)
    {
    	$masterRec = $data->masterData->rec;
    	$query = $this->getQuery();
    	$query->where("#lineId = {$masterRec->id}");
    	$query->where("#state = 'active'");
    	$query->orderBy("#createdOn", 'DESC');
    	
    	$i = 1;
    	while($dRec = $query->fetch()){
    		$dRec->rowNumb = $i;
    		$data->shipmentOrders[$dRec->id] = $this->prepareLineRows($dRec);
    		$i++;
    	}
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderShipments($data)
    {
    	$table = cls::get('core_TableView');
    	$fields = "rowNumb=№,docId=Документ,weight=Тегло,volume=Обем,collection=Инкасиране,address=@Адрес";
    	
    	return $table->get($data->shipmentOrders, $fields);
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашето експедиционно нареждане") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
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
    	$tplArr[] = array('name' => 'Експедиционно нареждане', 
    					  'content' => 'store/tpl/SingleLayoutShipmentOrder.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Експедиционно нареждане с цени', 
    					  'content' => 'store/tpl/SingleLayoutShipmentOrderPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	$tplArr[] = array('name' => 'Packing list', 
    					  'content' => 'store/tpl/SingleLayoutPackagingList.shtml', 'lang' => 'en', 'oldName' => 'Packaging list',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
    	
    	$skipped = $added = $updated = 0;
    	foreach ($tplArr as $arr){
    		$arr['docClassId'] = $this->getClassId();
    		doc_TplManager::addOnce($arr, $added, $updated, $skipped);
    	}
    	
    	$res .= "<li><font color='green'>Добавени са {$added} шаблона за експедиционни нареждания, обновени са {$updated}, пропуснати са {$skipped}</font></li>";
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
     	$query = store_ShipmentOrderDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#shipmentId = '{$rec->id}'");
     	
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
        return tr("|Експедиционно нареждане|* №") . $rec->id;
    }
}