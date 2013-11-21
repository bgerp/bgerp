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
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_TransactionSourceIntf=store_transactionIntf_Receipt, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices,
                    doc_plg_BusinessDoc2, plg_LastUsedKeys, cond_plg_DefaultValues';

    
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
    public $canDelete = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'vehicleId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDeliveredVat,createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_ReceiptDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Складова разписка';
    
    
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
	* Стратегии за дефолт стойностти
	*/
    public static $defaultStrategies = array('termId' => 'lastDocUser|lastDoc|clientCondition');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em,input=hidden');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=В склад, mandatory'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Условие,mandatory,salecondSysId=deliveryTerm');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('vehicleId', 'key(mvc=trans_Vehicles,select=name,allowEmpty)', 'caption=Доставител');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }



    /**
     * След промяна в детайлите на обект от този клас
     *
     * @param core_Manager $mvc
     * @param int $id ид на мастър записа, чиито детайли са били променени
     * @param core_Manager $detailMvc мениджър на детайлите, които са били променени
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetchRec($id);
    	$query = $detailMvc->getQuery();
        $query->where("#{$detailMvc->masterKey} = '{$id}'");
    
        $rec->amountDeliveredVat = $rec->amountDelivered = 0;
    
        while ($detailRec = $query->fetch()) {
            $vat = 1;
    
            if ($rec->chargeVat == 'yes' || $rec->chargeVat == 'no') {
                $ProductManager = cls::get($detailRec->classId);
    			$vat += $ProductManager->getVat($detailRec->productId, $rec->valior);
            }
    		
            // Събиране на сумата във валутата на Ен-то за да няма разминаване
            $priceVat = ($detailRec->price * $vat) / $rec->currencyRate;
            $price = $detailRec->price / $rec->currencyRate;
    		
            $priceVat = currency_Currencies::round($priceVat, $rec->currencyCode);
            $price = currency_Currencies::round($price, $rec->currencyCode);
            
    		$rec->amountDelivered += $price * $detailRec->quantity;
            $rec->amountDeliveredVat += $priceVat * $detailRec->quantity;
        }
    	
        // Конвертиране на сумата във основна валута, за запазване в db-то
        $rec->amountDelivered *= $rec->currencyRate;
        $rec->amountDeliveredVat *= $rec->currencyRate;
    	
        $mvc->save($rec);
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
     * Подготвя вербалните данни на моята фирма
     */
    private function prepareMyCompanyInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
		$address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }  
        
        $row->MyCompany = $ownCompanyData->company;
        $row->MyCountry = $ownCompanyData->country;
        $row->MyAddress = $address;
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	 
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
        $cdata      = static::normalizeContragentData($contragent->getContragentData());
        
        foreach((array)$cdata as $name => $value){
        	$row->$name = $value;
        }
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
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
    	if($data->rec->chargeVat == 'yes' || $data->rec->chargeVat == 'no'){
    		$data->row->VAT = " " . tr('с ДДС');
    	}
    	
    	$data->row->header = $mvc->singleTitle . " №<b>{$data->row->id}</b> ({$data->row->state})";
    	
    	// Бутон за отпечатване с цени
        $data->toolbar->addBtn('Печат (с цени)', array($mvc, 'single', $data->rec->id, 'Printing' => 'yes', 'showPrices' => TRUE), 'id=btnPrintP,target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'DealInfo', $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
    	}
    	
    	$data->row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($data->rec->valior);
	}
    
    
    /**
     * Нормализиране на контрагент данните
     */
    public static function normalizeContragentData($contragentData)
    {
        $rec = new stdClass();
        
        $rec->contragentCountryId = $contragentData->countryId;
        $rec->contragentCountry   = $contragentData->country;
        
        if (!empty($contragentData->company)) {
            // Случай 1 или 2: има данни за фирма
            $rec->contragentName    = $contragentData->company;
            $rec->contragentAddress = trim(
                sprintf("%s %s\n%s",
                    $contragentData->place,
                    $contragentData->pCode,
                    $contragentData->address
                )
            );
            $rec->contragentVatNo = $contragentData->vatNo;
        } elseif (!empty($contragentData->person)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->person;
            $rec->contragentAddress = $contragentData->pAddress;
        }

        return $rec;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $rec  = &$form->rec;
        
        $form->setDefault('valior', dt::mysql2verbal(dt::now(FALSE)));
        
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
        
        if (empty($rec->storeId)) {
            $rec->storeId = store_Stores::getCurrent('id', FALSE);
        }
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id)) {
        	
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getAggregateDealInfo();
                
            $form->rec->currencyId = $dealInfo->agreed->currency;
            $form->rec->currencyRate = $dealInfo->agreed->rate;
        	if(isset($dealInfo->agreed->delivery->term)){
        		$form->rec->termId = $dealInfo->agreed->delivery->term;
                $form->setField('termId', 'input=hidden');
            }
           
            $form->rec->deliveryTime = $dealInfo->agreed->delivery->time;
            $form->rec->chargeVat = $dealInfo->agreed->vatType;
            $form->rec->storeId = $dealInfo->agreed->delivery->storeId;
            
            // ... и стойностите по подразбиране са достатъчни за валидиране
            // на формата, не показваме форма изобщо, а направо създаваме записа с изчислените
            // ст-сти по подразбиране. За потребителя си остава възможността да промени каквото
            // е нужно в последствие.
            
            if ($mvc->validate($form)) {
                if (self::save($form->rec)) {
                    redirect(array($mvc, 'single', $form->rec->id));
                }
            }
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
    			$row->amountDeliveredVat = "<span class='quiet'>0</span>";
    		}
    	}
    	
        if(isset($fields['-single'])){
			@$amountDeliveredVat = $rec->amountDeliveredVat / $rec->currencyRate;
			$row->amountDeliveredVat = $mvc->fields['amountDeliveredVat']->type->toVerbal($amountDeliveredVat);
			$mvc->prepareMyCompanyInfo($row, $rec);
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
        $title = "Складова разписка №{$rec->id} / " . $this->getVerbal($rec, 'valior');
        
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
		
		$result->shipped->amount             = $rec->amountDeliveredVat;
		$result->shipped->currency           = $rec->currencyId;
		$result->shipped->rate 				 = $rec->currencyRate;
        $result->shipped->vatType            = $rec->chargeVat;
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
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
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
}