<?php



/**
 * Документ 'Покупка'
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Покупки
 */
class purchase_Purchases extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Покупки';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealAggregatorIntf, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, plg_Sorting, plg_Printing, doc_ActivatePlg,
				        doc_DocumentPlg, plg_ExportCsv, cond_plg_DefaultValues, doc_plg_HidePrices,
				        doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc2, acc_plg_DocumentSummary';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'purchase_Requests';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pur';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId, amountDeal, amountDelivered, amountPaid,dealerId,createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_PurchasesDetails';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Покупка';


    /**
     * Лейаут на единичния изглед 
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutPurchase.shtml';
    
    
    /**
     * Документа покупка може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.2|Логистика";
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountDelivered,amountPaid,amountInvoiced,amountToPay';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'deliveryTermId'     => 'clientCondition|lastDocUser|lastDoc',
    	'paymentMethodId'    => 'clientCondition|lastDocUser|lastDoc',
    	'currencyId'         => 'lastDocUser|lastDoc|defMethod',
    	'bankAccountId'      => 'lastDocUser|lastDoc',
    	'makeInvoice'        => 'lastDocUser|lastDoc',
    	'dealerId'           => 'lastDocUser|lastDoc|defMethod',
    	'deliveryLocationId' => 'lastDocUser|lastDoc',
    	'chargeVat'			 => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е фактурирана
        $this->FLD('amountVat', 'double(decimals=2)', 'input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Доставчик');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTerm');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->От обект,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до');
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Доставка->До склад,oldClassName=storeId');
        
        // Плащане
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=name,allowEmpty)', 'caption=Плащане->Начин,salecondSysId=paymentMethod');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Плащане->Банкова сметка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        
        // Наш персонал
        $this->FLD('dealerId', 'user(allowEmpty)', 'caption=Наш персонал->Закупчик');

        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr' => array('rows' => 3)));
    	$this->FLD('chargeVat', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=Допълнително->ДДС');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 'caption=Допълнително->Фактуриране,maxRadio=3,columns=3');
        
    	$this->FLD('state','enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $form->setDefault('valior', dt::now());
        
        $form->setDefault('bankAccountId',bank_OwnAccounts::getCurrent('id', FALSE));
        $form->setDefault('caseId', cash_Cases::getCurrent('id', FALSE));
        $form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        if (empty($data->form->rec->makeInvoice)) {
            $form->setDefault('makeInvoice', 'yes');
        }
        
        // Поле за избор на локация - само локациите на контрагента по покупката
        $locations = array('' => '') + crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setOptions('deliveryLocationId', $locations);
        
        // Начисляване на ДДС по подразбиране
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ? 'yes' : 'export');
        
        if ($form->rec->id) {
        	
        	// Неможе да се сменя ДДС-то ако има вече детайли
        	if($mvc->purchase_PurchasesDetails->fetch("#requestId = {$form->rec->id}")){
        		$form->setReadOnly('chargeVat');
        	}
        }
        
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode($form->rec->valior));
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }

    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	if($form->isSubmitted()){
	    	if(!$form->rec->currencyRate){
				 $form->rec->currencyRate = round(currency_CurrencyRates::getRate($form->rec->date, $form->rec->currencyId, NULL), 4);
			}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	$diffAmount = $rec->amountPaid - $rec->amountDelivered;
    	if($rec->state == 'active'){
    		if($rec->amountDeal && $rec->amountPaid && $rec->amountDelivered && $diffAmount == 0){
    			$data->toolbar->addBtn('Приключи', array($mvc, 'close', $rec->id), 'warning=Сигурни ли сте че искате да приключите сделката,ef_icon=img/16/closeDeal.png,title=Приключване на продажбата');
    		}
    		
	    	if (store_Receipts::haveRightFor('add') && store_Receipts::canAddToThread($data->rec->threadId)) {
	    		$receiptUrl = array('store_Receipts', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Засклаждане', $receiptUrl, 'ef_icon = img/16/star_2.png,title=Засклаждане на артикулите в склада,order=9.21,warning=Искатели да създадете нова Складова разписка ?');
	        }
	    	
    		if(store_Receipts::haveRightFor('add') && purchase_Services::canAddToThread($data->rec->threadId)) {
    			$serviceUrl = array('purchase_Services', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
	            $data->toolbar->addBtn('Услуга', $serviceUrl, 'ef_icon = img/16/star_2.png,title=Продажба на услуги,order=9.22,warning=Искатели да създадете нов протокол за покупка на услуги ?');
	        }
    	}
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
    	}
    }
    
    
	/**
     * След подготовка на сингъла
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	
    	if(empty($data->noTotal)){
    		$data->summary = price_Helper::prepareSummary($rec->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Покупка|* №" . $rec->id);
    }
    
    
    /**
     * Помощен метод за определяне на закупчик по подразбиране.
     *
     * Правило за определяне: първия, който има права за създаване на покупки от списъка:
     *
     *  1/ Отговорника на папката на контрагента
     *  2/ Текущият потребител
     *
     *  Ако никой от тях няма права за създаване - резултатът е NULL
     *
     * @param stdClass $rec запис на модела purchase_Purchases
     * @return int|NULL user(roles=purchase)
     */
    public static function getDefaultDealerId($rec)
    {
        expect($rec->folderId);
    
        // Отговорника на папката на контрагента ...
        $inChargeUserId = doc_Folders::fetchField($rec->folderId, 'inCharge');
        if (self::haveRightFor('add', NULL, $inChargeUserId)) {
            // ... има право да създава покупки - той става закупчик по подразбиране.
            return $inChargeUserId;
        }
    
        // Текущия потребител ...
        $currentUserId = core_Users::getCurrent('id');
        if (self::haveRightFor('add', NULL, $currentUserId)) {
            // ... има право да създава покупки
            return $currentUserId;
        }
    
        return NULL;
    }
    

    /**
     * Може ли документ-продажба да се добави в посочената папка?
     * Документи-продажба могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
    
        $row = (object)array(
            'title'    => "Покупка №{$rec->id} / " . $this->getVerbal($rec, 'valior'),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $this->getRecTitle($rec),
        );
    
        return $row;
    }
    

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$amountType = $mvc->getField('amountDeal')->type;
		$rec->amountToPay = $rec->amountDelivered - $rec->amountPaid;
		
    	foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay') as $amnt) {
            if ($rec->{"amount{$amnt}"} == 0) {
                $row->{"amount{$amnt}"} = '<span class="quiet">0.00</span>';
            } else {
            	$value = $rec->{"amount{$amnt}"} / $rec->currencyRate;
				$row->{"amount{$amnt}"} = $amountType->toVerbal($value);
            }
        }
        
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    }
	    
	    if($fields['-single']){
		    $row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})";
		    
	    	$mvc->prepareHeaderInfo($row, $rec);
	    	
	    	if ($rec->currencyRate != 1) {
	            $row->currencyRateText = '(<span class="quiet">' . tr('курс') . "</span> {$row->currencyRate})";
	        }
	    }
	    
    }


	/**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $row->MyCompany = $ownCompanyData->company;
        $row->MyAddress = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId);
        
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
     * След рендиране на единичния изглед
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	if($data->summary){
    		$tpl->replace(price_Helper::renderSummary($data->summary), 'SUMMARY');
    	}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		$state = $rec->state;
    		$rec = $mvc->fetch($id);
    		$rec->state = $state;
    		
    		// Записване на покупката като отворена сделка
    		acc_OpenDeals::saveRec($rec, $mvc);
    	}
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
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = new purchase_model_Purchase(self::fetchRec($id));
        
        // Извличаме продуктите на покупката
        $detailRecs = $rec->getDetails('purchase_PurchasesDetails', 'purchase_model_PurchaseProduct');
                
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_PURCHASE;
        
        // Кои са позволените операции за последващите платежни документи
        $result->allowedPaymentOperations = array('case2supplierAdvance',
        										  'bank2supplierAdvance',
        										  'bank2supplier',
        										  'case2supplier',
        										  'supplier2bank',
        										  'supplier2case');
        
        $result->agreed->amount                 = $rec->amountDeal;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->rate                   = $rec->currencyRate;
        $result->agreed->vatType 				= $rec->chargeVat;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->storeId      = $rec->shipmentStoreId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        /* @var $dRec purchase_model_PurchaseProduct */
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $result->agreed->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
	 * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази покупка
     * 
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до 
     * момента.
     * 
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за пробдажбата е
     * сечението на следните множества:
     * 
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     * 
     * @return bgerp_iface_DealResponse
     */
    public function getAggregateDealInfo($id)
    {
        $requestRec = new purchase_model_Purchase($id);
        
    	$requestDocuments = $this->getDescendants($requestRec->id);
        
        // Извличаме dealInfo от самата покупка
        /* @var $saleDealInfo bgerp_iface_DealResponse */
        $requestDealInfo = $this->getDealInfo($requestRec->id);
        
        // dealInfo-то на самата покупка е база, в/у която се натрупват някой от аспектите
        // на породените от нея документи (платежни, експедиционни, фактури)
        $aggregateInfo = clone $requestDealInfo;
        
        /* @var $d core_ObjectReference */
        foreach ($requestDocuments as $d) {
            $dState = $d->rec('state');
            if ($dState == 'draft' || $dState == 'rejected') {
                // Игнорираме черновите и оттеглените документи
                continue;
            }
        
            if ($d->haveInterface('bgerp_DealIntf')) {
                /* @var $dealInfo bgerp_iface_DealResponse */
                $dealInfo = $d->getDealInfo();
                
                $aggregateInfo->shipped->push($dealInfo->shipped);
                $aggregateInfo->paid->push($dealInfo->paid);
                $aggregateInfo->invoiced->push($dealInfo->invoiced);
            }
        }
        
        // Aко няма експедирани/фактурирани продукти, то се копират договорените
        // но с количество 0 за експедирани/фактурирани
    	foreach(array('shipped', 'invoiced') as $type){
    		$aggregateInfo->$type->currency = $aggregateInfo->agreed->currency;
        	$aggregateInfo->$type->rate     = $aggregateInfo->agreed->rate;
        	$aggregateInfo->$type->vatType  = $aggregateInfo->agreed->vatType;
        	
        	if(!count($aggregateInfo->$type->products)){
        		foreach ($aggregateInfo->agreed->products as $aProd){
        			$cloneProd = clone $aProd;
        			$cloneProd->quantity = 0;
        			$aggregateInfo->$type->products[] = $cloneProd;
        		}
        	}
        }
        
        return $aggregateInfo;
    }
    
    
	/**
     * Трасира веригата от документи, породени от дадена покупка. Извлича от тях експедираните 
     * количества и платените суми.
     * 
     * @param core_Mvc $mvc
     * @param core_ObjectReference $requestRef
     * @param core_ObjectReference $descendantRef кой породен документ е инициатор на трасирането
     */
    public static function on_DescendantChanged($mvc, $requestRef, $descendantRef = NULL)
    {
        $requestRec = new purchase_model_Purchase($requestRef->rec());
    	$aggregatedDealInfo = $mvc->getAggregateDealInfo($requestRef->that);
		
        $requestRec->updateAggregateDealInfo($aggregatedDealInfo);
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
    	
    	$query = $this->purchase_PurchasesDetails->getQuery();
        $query->where("#requestId = '{$id}'");
        
        price_Helper::fillRecs($query->fetchAll(), $rec);
        
        // ДДС-то е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amountDeal = ($rec->chargeVat == 'no') ? $rec->_total->amount + $rec->_total->vat : $rec->_total->amount;
        $amountDeal -= $rec->_total->discount;
        $rec->amountDeal = $amountDeal * $rec->currencyRate;
        $rec->amountVat  = $rec->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $rec->_total->discount * $rec->currencyRate;
        
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
     * Дебъг екшън показващ агрегираните бизнес данни
     */
    function act_AggregateDealInfo()
    {
    	requireRole('debug');
    	expect($id = Request::get('id', 'int'));
    	$info = $this->getAggregateDealInfo($id);
    	bp($info);
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate'){
    		if(isset($rec)){
    			if(!$mvc->purchase_PurchasesDetails->fetch("#requestId = {$rec->id}")){
    				$res = 'no_one';
    			}
    		} else {
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * Помощна ф-я показваща дали в продажбата има поне един складируем/нескладируем артикул
     * @param int $id - ид на покупката
     * @param boolean $storable - дали се търсят складируеми или нескладируеми артикули
     * @return boolean TRUE/FALSE - дали има поне един складируем/нескладируем артикул
     */
    public function hasStorableProducts($id, $storable = TRUE)
    {
    	$rec = new purchase_model_Purchase(self::fetchRec($id));
        $detailRecs = $rec->getDetails('purchase_PurchasesDetails', 'purchase_model_PurchaseProduct');
        
        foreach ($detailRecs as $d){
        	$info = cls::get($d->classId)->getProductInfo($d->productId);
        	if($storable){
        		
        		// Връща се TRUE ако има поне един складируем продукт
        		if(isset($info->meta['canStore'])) return TRUE;
        	} else {
        		
        		// Връща се TRUE ако има поне един НЕ складируем продукт
        		if(!isset($info->meta['canStore']))return TRUE;
        	}
        }
        
        return FALSE;
    }
}