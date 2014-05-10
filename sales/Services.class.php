<?php
/**
 * Клас 'sales_Services'
 *
 * Мениджър на Протокол за извършени услуги
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Services extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за извършени услуги';


    /**
     * Абревиатура
     */
    public $abbr = 'Pss';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_TplManager, doc_plg_HidePrices,
                    doc_plg_BusinessDoc, plg_LastUsedKeys';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDeliveredVat, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'sales_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за извършени услуги';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.81|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'locationId, note';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em,input=hidden'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,summary=amount,input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Обект до,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        
        // Допълнително
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
    	
    	$query = $this->sales_ServicesDetails->getQuery();
        $query->where("#shipmentId = '{$id}'");
        
        price_Helper::fillRecs($query->fetchAll(), $rec);
        
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
		
		$rec->isFull = (!bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'services')) ? 'yes' : 'no';
		
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
        // използваме го за автоматично попълване на детайлите на протокола
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        
        /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
        $aggregatedDealInfo = $origin->getAggregateDealInfo();
            
        $remainingToShip = clone $aggregatedDealInfo->agreed;
        $remainingToShip->pop($aggregatedDealInfo->shipped);
            
        /* @var $product bgerp_iface_DealProduct */
        foreach ($remainingToShip->products as $product) {
            $info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
                
            // Пропускат се експедираните и складируемите артикули
            if (isset($info->meta['canStore']) || $product->quantity <= 0) continue;
            
            $shipProduct = new stdClass();
            $shipProduct->shipmentId  = $rec->id;
            $shipProduct->classId     = $product->classId;
            $shipProduct->productId   = $product->productId;
            $shipProduct->packagingId = $product->packagingId;
            $shipProduct->quantity    = $product->quantity;
            $shipProduct->price       = $product->price;
            $shipProduct->uomId       = $product->uomId;
            $shipProduct->discount    = $product->discount;
            $shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
                
            $mvc->sales_ServicesDetails->save($shipProduct);
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
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	$data->row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$data->row->id}</b> ({$data->row->state})";
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'DealInfo', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
    	}
	}
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $rec  = &$form->rec;
        $form->setDefault('valior', dt::now());
        
        if (empty($rec->folderId)) {
            expect($rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        // Определяне на контрагента (ако още не е определен)
        if (empty($rec->contragentClassId)) {
            $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        }
        
        if (empty($rec->contragentId)) {
            $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
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
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->amountDeliveredVat){
    			$row->amountDeliveredVat = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDeliveredVat}";
    		} else {
    			$row->amountDeliveredVat = "<span class='quiet'>0.00</span>";
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		$mvc->prepareHeaderInfo($row, $rec);
    	}
    }


    /**
     * Протокола не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли протокол да се добави в посочената нишка?
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-продажба
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Ако началото на треда е активирана продажба
    	if(($firstDoc->instance() instanceof sales_Sales) && $docState == 'active'){
    		
    		// Ако има поне един нескладируем продукт в продажбата
    		return $firstDoc->hasStorableProducts(FALSE);
    	}
    	
    	return FALSE;
    }
        
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = "Протокол за доставка на услуги №{$rec->id} / " . $this->getVerbal($rec, 'valior');
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
     * Връща масив от използваните нестандартни артикули в протоколa
     * @param int $id - ид на протоколa
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_ServicesDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Services', 'externalKey=shipmentId');
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
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = new sales_model_Service($id);
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        // Конвертираме данъчната основа към валутата идваща от продажбата
        $result->shipped->amount             = $rec->amountDelivered;
        $result->shipped->currency		 	 = $rec->currencyId;
        $result->shipped->rate		         = $rec->currencyRate;
        $result->shipped->valior 			 = $rec->valior;
        $result->shipped->vatType            = $rec->chargeVat;
        $result->shipped->delivery->location = $rec->locationId;
        $result->shipped->delivery->time     = $rec->deliveryTime;
        
        /* @var $dRec sales_model_Service */
        foreach ($rec->getDetails('sales_ServicesDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
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
    	bp($info->shipped, $this->fetch($id));
    }
    
    
    /**
     * Финализиране на транзакцията
     * @param int $id
     */
	public function finalizeTransaction($id)
    {
        $rec = $this->fetchRec($id);
        $rec->state = 'active';
        
        if ($this->save($rec)) {
            $this->invoke('AfterActivation', array($rec));
        }
        
        // Нотификация към пораждащия документ, че нещо във веригата му от породени документи се е променило.
        if ($origin = $this->getOrigin($rec)) {
            $rec = new core_ObjectReference($this, $rec);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
        }
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
    	$tplArr[] = array('name' => 'Протокол за извършени услуги', 
    					  'content' => 'sales/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Протокол за извършени услуги с цени', 
    					  'content' => 'sales/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	
    	$skipped = $added = $updated = 0;
    	foreach ($tplArr as $arr){
    		$arr['docClassId'] = $this->getClassId();
    		doc_TplManager::addOnce($arr, $added, $updated, $skipped);
    	}
    	
    	$res .= "<li><font color='green'>Добавени са {$added} шаблона за протоколи за извършени услуги, обновени са {$updated}, пропуснати са {$skipped}</font></li>";
    }
    
    
    /**
     * Транзакция за запис в журнала
     * @param int $id
     */
	public function getTransaction($id)
    {
        $entries = array();
        $rec = new sales_model_Service($id);
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        $detailsRec = $rec->getDetails('sales_ServicesDetails');
        if(count($detailsRec)){
        	price_Helper::fillRecs($detailsRec, $rec);
        	
	        foreach ($detailsRec as $dRec) {
		        if($rec->chargeVat == 'yes'){
	        		$ProductManager = cls::get($dRec->classId);
	            	$vat = $ProductManager->getVat($dRec->productId, $rec->valior);
	            	$amount = $dRec->amount - ($dRec->amount * $vat / (1 + $vat));
	        	} else {
	        		$amount = $dRec->amount;
	        	}
	        	
	        	$amount = ($dRec->discount) ?  $amount * (1 - $dRec->discount) : $amount;
	        	
	        	$entries[] = array(
	                'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
	                
	                'debit' => array(
	                    '411', // Сметка "411. Вземания от клиенти"
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	                        array('currency_Currencies', $currencyId),     		// Перо 2 - Валута
	                    'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на продажбата
	                ),
	                
	                'credit' => array(
	                    '703', // Сметка "703". Приходи от продажби на услуги
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	                    	array($dRec->classId, $dRec->productId), // Перо 2 - Артикул
	                    'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
	                ),
            	);
	        }
	        
        	if($rec->_total->vat){
	        	$vatAmount = currency_Currencies::round($rec->_total->vat * $rec->currencyRate);
	        	$entries[] = array(
	                'amount' => $vatAmount, // В основна валута
	                
	                'debit' => array(
	                    '411',
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	                        array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 2 - Валута
	                    'quantity' => $vatAmount, // "брой пари" във валутата на продажбата
	                ),
	                
	                'credit' => array(
	                    '4530', 
	                    'quantity' => $vatAmount, // Количество продукт в основната му мярка
	                ),
	            );
        	}
        }
        
        $transaction = (object)array(
            'reason'  => 'Протокол за доставка на услуги #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия протокол за продажба на услуги") . ': #[#handle#]');
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
     	$query = sales_ServicesDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#shipmentId  = '{$rec->id}'");
     	
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
        return tr("|Протокол за извършени услуги|* №") . $rec->id;
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
        	
        	if(empty($form->rec->isFull)){
        		
        		// Сетване на кеш полето че протокола е запълнен
        		$form->rec->isFull = 'no';
        	}
        }
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    	
    	// Документа не може да се контира, ако ориджина му е в състояние 'closed'
    	if($action == 'conto' && isset($rec)){
	    	$originState = $mvc->getOrigin($rec)->fetchField('state');
	        if($originState === 'closed'){
	        	$res = 'no_one';
	        }
        }
    }
}