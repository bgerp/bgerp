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
class purchase_Requests extends core_Master
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
				        doc_DocumentPlg, plg_ExportCsv, cond_plg_DefaultValues,
				        doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc2, acc_plg_DocumentSummary';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,purchase';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId, amountDeal, amountDelivered, amountPaid,dealerId,createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_RequestDetails';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Покупка';


    /**
     * Лейаут на единичния изглед 
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutRequest.shtml';
    
    
    /**
     * Документа покупка може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.2|Логистика";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'deliveryTermId'     => 'lastDocUser|lastDoc|clientCondition',
    	'paymentMethodId'    => 'lastDocUser|lastDoc|clientCondition',
    	'currencyId'         => 'lastDocUser|lastDoc|defMethod',
    	'bankAccountId'      => 'lastDocUser|lastDoc',
    	'makeInvoice'        => 'lastDocUser|lastDoc|defMethod',
    	'dealerId'           => 'lastDocUser|lastDoc|defMethod',
    	'deliveryLocationId' => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 'caption=Фактуриране,maxRadio=3,columns=3');
        $this->FLD('chargeVat', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=ДДС');
        
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е фактурирана
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Доставчик');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,salecondSysId=deliveryTerm');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->От обект,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Доставка->До склад');
        
        // Плащане
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=name)', 'caption=Плащане->Начин,salecondSysId=paymentMethod');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Плащане->Банкова сметка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        
        // Наш персонал
        $this->FLD('dealerId', 'user(allowEmpty)', 'caption=Наш персонал->Закупчик');

        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
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
        
        if (empty($form->rec->folderId)) {
            expect($form->rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        if (empty($data->form->rec->makeInvoice)) {
            $form->setDefault('makeInvoice', $mvc::getDefaultMakeInvoice($data->form->rec));
        }
        
        // Поле за избор на локация - само локациите на контрагента по покупката
        $locations = array(''=>'') + crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setOptions('deliveryLocationId', $locations);
        
        // Начисляване на ДДС по подразбиране
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ? 'yes' : 'export');
        
        if ($form->rec->id) {
        	
        	// Неможе да се сменя ДДС-то ако има вече детайли
        	if($mvc->purchase_RequestDetails->fetch("#requestId = {$form->rec->id}")){
        		$data->form->setReadOnly('chargeVat');
        	}
        }
        
        $data->form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }

    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	if($form->isSubmitted()){
	    	if(!$form->rec->currencyRate){
				 $form->rec->currencyRate = round(currency_CurrencyRates::getRate($form->rec->date, $form->rec->paymentCurrencyId, NULL), 4);
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
    		
	    	if (store_Receipts::haveRightFor('add')) {
	            $data->toolbar->addBtn('Заприхождаване', array('store_Receipts', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon = img/16/star_2.png,title=Експедиране на артикулите');
	        }
	        
	    	if(sales_Invoices::haveRightFor('add')){
	    		$data->toolbar->addBtn("Фактуриране", array('sales_Invoices', 'add', 'originId' => $data->rec->containerId), 'ef_icon=img/16/invoice.png,title=Създаване на фактура,order=9.9993');
	    	}
    	}
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
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
     * Определяне на валутата по подразбиране при нова продажба.
     */
    public static function getDefaultCurrencyId($rec)
    {
        return $currencyBaseCode = acc_Periods::getBaseCurrencyCode($rec->valior);
    }
    
    
    /**
     * Определяне ст-ст по подразбиране на полето makeInvoice
     *
     * @param stdClass $rec
     * @return string ('yes' | 'no' | 'monthend')
     *
     */
    public static function getDefaultMakeInvoice($rec)
    {
        return $makeInvoice = 'yes';
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
     * @param stdClass $rec запис на модела purchase_Requests
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
     * След подготовка записите
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields,
            arr::make('currencyId,contragentId', TRUE)
        );
    
        $data->listFields['dealerId'] = 'Закупчик';
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
    	foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay') as $amnt) {
            if ($rec->{"amount{$amnt}"} == 0) {
                $row->{"amount{$amnt}"} = $row->{"amount{$amnt}"} = '<span class="quiet">0.00</span>';
            }
        }
        
    	$row->amountToPay = $mvc->getField('amountDeal')->type->toVerbal($rec->amountDeal - $rec->amountPaid);
    	if($rec->chargeVat == 'yes' || $rec->chargeVat == 'no'){
        	$vat = acc_Periods::fetchByDate($rec->valior)->vatRate;
        	$row->vat = $mvc->getField('amountDeal')->type->toVerbal($vat * 100);
        } else {
        	unset($row->chargeVat);
        }
    	
        if ($rec->chargeVat == 'freed' || $rec->chargeVat == 'export') {
            $row->chargeVat = '';
        }
        
        $row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})";
        
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    }
    }


    /**
     * След рендиране на единичния изглед
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	// Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();
    
        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }
    
        $tpl->placeArray(array(
	                'MyCompany'      => $ownCompanyData->company,
	                'MyCountry'      => $ownCompanyData->country,
	                'MyAddress'      => $address,
	                'MyCompanyVatNo' => $ownCompanyData->vatNo,
	            ), 'supplier'
        );
    
        // Данните на клиента
        $contragent = new core_ObjectReference($data->rec->contragentClassId, $data->rec->contragentId);
        $cdata      = static::normalizeContragentData($contragent->getContragentData());
    
        $tpl->placeObject($cdata, 'contragent');
    
        // Описателното (вербалното) състояние на документа
        $tpl->replace($data->row->state, 'stateText');
    
        if (!empty($data->rec->currencyRate) && $data->rec->currencyRate != 1) {
            $tpl->replace('(<span class="quiet">' . tr('курс') . "</span> {$data->row->currencyRate})", 'currencyRateText');
        }
    }
    
    
    /**
     * Нормализиране на контрагент данните
     */
    public static function normalizeContragentData($contragentData)
    {
       /*
        * Разглеждаме четири случая според данните в $contragentData
        *
        *  1. Има данни за фирма и данни за лице
        *  2. Има само данни за фирма
        *  3. Има само данни за лице
        *  4. Нито едно от горните не е вярно
        */
    
        if (empty($contragentData->company) && empty($contragentData->person)) {
            // Случай 4: нито фирма, нито лице
            return FALSE;
        }
    
        // Тук ще попълним резултата
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
    
            if (!empty($contragentData->person)) {
                // Случай 1: данни за фирма + данни за лице
    
                // TODO за сега не правим нищо допълнително
            }
        } elseif (!empty($contragentData->person)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->person;
            $rec->contragentAddress = $contragentData->pAddress;
        }
    
        return $rec;
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
        $rec = new purchase_model_Request(self::fetchRec($id));
        
        // Извличаме продуктите на продажбата
        $detailRecs = $rec->getDetails('purchase_RequestDetails', 'purchase_model_RequestProduct');
                
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_PURCHASE;
        $amount = currency_CurrencyRates::convertAmount($rec->amountDeal, $rec->valior, NULL, $rec->currencyId);
        
        $result->agreed->amount                 = $amount;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->vatType 				= $rec->chargeVat;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->storeId      = $rec->storeId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        /* @var $dRec purchase_model_RequestProduct */
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $result->agreed->products[] = $p;
            
            if ($rec->isInstantShipment == 'yes') {
                $result->shipped->products[] = clone $p;
            }
        }
        
        return $result;
    }
    
    
	/**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealAggregatorIntf::getAggregateDealInfo()
     */
    public function getAggregateDealInfo($id)
    {
        $rec = new purchase_model_Request(self::fetchRec($id));
        
        // Извличаме продуктите на продажбата
        $detailRecs = $rec->getDetails('purchase_RequestDetails', 'purchase_model_RequestProduct');
        
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_PURCHASE;
        
        $result->agreed->amount                 = $rec->amountDeal;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->vatType 				= $rec->chargeVat;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->storeId      = $rec->storeId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        $result->paid->amount                 = $rec->amountPaid;
        $result->paid->currency               = $rec->currencyId;
        $result->paid->payment->method        = $rec->paymentMethodId;
        $result->paid->payment->bankAccountId = $rec->bankAccountId;
        $result->paid->payment->caseId        = $rec->caseId;

        $result->shipped->amount             = $rec->amountDelivered;
        $result->shipped->vatType            = $rec->chargeVat;
        $result->shipped->currency           = $rec->currencyId;
        $result->shipped->delivery->storeId  = $rec->storeId;
        $result->shipped->delivery->location = $rec->deliveryLocationId;
        $result->shipped->delivery->term     = $rec->deliveryTermId;
        $result->shipped->delivery->time     = $rec->deliveryTime;
        
        /* @var $dRec purchase_model_RequestProduct */
        foreach ($detailRecs as $dRec) {
            
        	// Договорени продукти
            $aProd = new bgerp_iface_DealProduct();
            
            $aProd->classId     = $dRec->classId;
            $aProd->productId   = $dRec->productId;
            $aProd->packagingId = $dRec->packagingId;
            $aProd->discount    = $dRec->discount;
            $aProd->isOptional  = FALSE;
            $aProd->quantity    = $dRec->quantity;
            $aProd->price       = $dRec->price;
            $aProd->uomId       = $dRec->uomId;
            
            $result->agreed->products[] = $aProd;
            
            // Експедирани продукти
            $sProd = clone $aProd;
            $sProd->quantity = $dRec->quantityDelivered;
            
            $result->shipped->products[] = $sProd;
            
            // Фактурирани продукти
            $iProd = clone $aProd;
            $iProd->quantity = $dRec->quantityInvoiced;
            
            $result->invoiced->products[] = $iProd;
        }
        
        return $result;
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
        $requestRec         = new purchase_model_Request($requestRef->rec());
        $aggregatedDealInfo = $requestRec->getAggregatedDealInfo();

        $requestRec->updateAggregateDealInfo($aggregatedDealInfo);
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
        
        $rec->amountDeal = 0;
        
        while ($detailRec = $query->fetch()) {
            $vat = 1;
            
            if ($rec->chargeVat == 'yes' || $rec->chargeVat == 'no') {
                $ProductManager = cls::get($detailRec->classId);
                
                $vat += $ProductManager->getVat($detailRec->productId, $rec->valior);
            }
            
            $rec->amountDeal += $detailRec->amount * $vat;
        }
        
        $mvc->save($rec);
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
    	if($action == 'activate' && isset($rec)){
    		if(!$mvc->purchase_RequestDetails->fetch("#requestId = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    }
}