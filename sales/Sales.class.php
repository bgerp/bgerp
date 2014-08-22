<?php
/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Sales extends core_Master
{
	const AGGREGATOR_TYPE = 'sale';
    
    /**
     * Заглавие
     */
    public $title = 'Продажби';


    /**
     * Абревиатура
     */
    public $abbr = 'Sal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_TransactionSourceIntf=sales_transaction_Sale,
                          bgerp_DealIntf, bgerp_DealAggregatorIntf, deals_DealsAccRegIntf, acc_RegisterIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_plg_MultiPrint, plg_Printing, doc_plg_TplManager, acc_plg_Deals, doc_DocumentPlg, acc_plg_Contable,
                    acc_plg_DocumentSummary, plg_Search, plg_ExportCsv, doc_plg_HidePrices, cond_plg_DefaultValues,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, plg_Clone, doc_SharablePlg';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да принтира фискална бележка
     */
    public $canPrintfiscreceipt = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    

    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,sales,acc';
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId=Валута, amountDeal, amountDelivered, amountPaid, 
                             dealerId, initiatorId,paymentState,
                             createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'sales_SalesDetails' ;
    

    /**
     * Кое поле да се използва за филтър по потребители
     */
    public $filterFieldUsers = 'dealerId';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продажба';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.1|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountDelivered,amountPaid,amountInvoiced,amountToPay,amountToDeliver,amountToInvoice';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutSale.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cart_go.png';

    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'deliveryTermId'     => 'clientCondition|lastDocUser|lastDoc',
    	'paymentMethodId'    => 'clientCondition|lastDocUser|lastDoc',
    	'currencyId'         => 'lastDocUser|lastDoc|CoverMethod',
    	'bankAccountId'      => 'lastDocUser|lastDoc',
    	'makeInvoice'        => 'lastDocUser|lastDoc|defMethod',
    	'deliveryLocationId' => 'lastDocUser|lastDoc',
    	'chargeVat'			 => 'lastDocUser|lastDoc|defMethod',
    	'template' 			 => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
    		'customer2caseAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '501', 'credit' => '412'),
    		'customer2bankAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '503', 'credit' => '412'),
    		'customer2case'        => array('title' => 'Плащане от Клиент', 'debit' => '501', 'credit' => '411'),
    		'customer2bank'        => array('title' => 'Плащане от Клиент', 'debit' => '503', 'credit' => '411'),
    		'case2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '501', 'reverse' => TRUE),
    		'bank2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '503', 'reverse' => TRUE),
    		'caseAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '501', 'reverse' => TRUE),
    		'bankAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '503', 'reverse' => TRUE),
    		'debitDeals'           => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '411'),
    		'creditDeals'          => array('title' => 'Прихващане на задължение', 'debit' => '411', 'credit' => '*', 'reverse' => TRUE), 
    		);

    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('delivery'        => array('title' => 'Експедиране на стока', 'debit' => '411', 'credit' => 'store'),
    										  'deliveryService' => array('title' => 'Доставка на услуги', 'debit' => '411', 'credit' => 'service'),
    										  'stowage'         => array('title' => 'Връщане на стока', 'debit' => 'store', 'credit' => '411', 'reverse' => TRUE),
    );
    		
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'deliveryTermId, deliveryLocationId, shipmentStoreId, paymentMethodId, currencyId, bankAccountId, caseId, initiatorId, dealerId, folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        
        // Стойности
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountBl', 'double(decimals=2)', 'caption=Стойности->Крайно салдо,input=none,summary=amount'); 
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountToInvoice', 'double(decimals=2)', 'input=none'); // Сумата която е платена
        
        $this->FLD('amountVat', 'double(decimals=2)', 'input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTermSale');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Обект до,silent,class=contactData'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)',  'caption=Доставка->От склад'); // наш склад, от където се експедира стоката
        
        // Плащане
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=description,allowEmpty)','caption=Плащане->Начин,salecondSysId=paymentMethodSale');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban,allowEmpty)', 'caption=Плащане->Банкова с-ка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        
        // Наш персонал
        $this->FLD('initiatorId', 'user(roles=user,allowEmpty,rolesForAll=sales|ceo)', 'caption=Наш персонал->Инициатор');
        $this->FLD('dealerId', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Наш персонал->Търговец');
        
        // Допълнително
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Допълнително->ДДС');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 'caption=Допълнително->Фактуриране,maxRadio=3,columns=3');
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към');
        $this->FLD('note', 'text(rows=4)', 'caption=Допълнително->Условия', array('attr' => array('rows' => 3)));

        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен)', 
            'caption=Статус, input=none'
        );
        
    	$this->FLD('paymentState', 'enum(pending=Чакащо,overdue=Просроченo,paid=Платенo,repaid=Издължено)', 'caption=Плащане, input=none');
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
    	
    	$query = $this->sales_SalesDetails->getQuery();
        $query->where("#saleId = '{$id}'");
        $recs = $query->fetchAll();
        
        deals_Helper::fillRecs($this, $recs, $rec);
        
        // ДДС-то е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amountDeal = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amountDeal -= $this->_total->discount;
        $rec->amountDeal = $amountDeal * $rec->currencyRate;
        $rec->amountVat  = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
        
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
     * Определяне на документа-източник (пораждащия документ)
     */
    public function getOrigin_($rec)
    {
        $rec = static::fetchRec($rec);
        
        if (!empty($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
        } else {
            $origin = FALSE;
        }
        
        return $origin;
    }


    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        if (!$origin = $mvc->getOrigin($rec)) {
            return;
        }
    
        // Ако новосъздадения документ има origin, който поддържа bgerp_DealIntf,
        // използваме го за автоматично попълване на детайлите на продажбата
    
        if ($origin->haveInterface('bgerp_DealIntf')) {
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getDealInfo();
            
            $quoted = $dealInfo->quoted;
            
            /* @var $product bgerp_iface_DealProduct */
            foreach ($quoted->products as $product) {
                $product = (object)$product;

                if ($product->quantity <= 0) {
                    continue;
                }
        
                $saleProduct = new stdClass();
        		$ProductMan = cls::get($product->classId);
                
                $saleProduct->saleId      = $rec->id;
                $saleProduct->classId     = $ProductMan->getClassId();
                $saleProduct->productId   = $product->productId;
                $saleProduct->packagingId = $product->packagingId;
                $saleProduct->quantity    = $product->quantity;
                $saleProduct->discount    = $product->discount;
                $saleProduct->price       = $product->price;
                $saleProduct->uomId       = $product->uomId;
        
                $productInfo = $ProductMan->getProductInfo($saleProduct->productId, $saleProduct->packagingId);
                $saleProduct->quantityInPack = ($saleProduct->packagingId) ? $productInfo->packagingRec->quantity : 1;
                
                sales_SalesDetails::save($saleProduct);
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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
    	// Задаване на стойности на полетата на формата по подразбиране
        self::setDefaultsFromOrigin($mvc, $form);
        self::setDefaults($mvc, $form);
        
        if ($form->rec->id){
        	
        	// Не може да се сменя ДДС-то ако има вече детайли
        	if($mvc->sales_SalesDetails->fetch("#saleId = {$form->rec->id}")){
        		foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId') as $fld){
        			$form->setReadOnly($fld);
        		}
        	}
        }
        
        $conf = core_Packs::getConfig('sales');
        $maxMonths =  $conf->SALE_MAX_FUTURE_PRICE / type_Time::SECONDS_IN_MONTH;
		$minMonths =  $conf->SALE_MAX_PAST_PRICE / type_Time::SECONDS_IN_MONTH;
        
        $priceAtDateFld = $form->getFieldType('pricesAtDate');
        $priceAtDateFld->params['max'] = dt::addMonths($maxMonths);
        $priceAtDateFld->params['min'] = dt::addMonths(-$minMonths);
        
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    	$form->setField('sharedUsers', 'input=none');
    	
    	// Текущия потребител е търговеца, щом се е стигнало до тук значи има права
    	$form->setDefault('dealerId', core_Users::getCurrent());
    }
    
    
    /**
     * Зареждане на стойности по подразбиране от документа-основание 
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function setDefaultsFromOrigin(core_Mvc $mvc, core_Form $form)
    {
        if (!($origin = $mvc->getOrigin($form->rec)) || !$origin->haveInterface('bgerp_DealIntf')) {
            // Не може да се използва `bgerp_DealIntf`
            return false;
        }
        
        /* @var $dealInfo bgerp_iface_DealResponse */
        $dealInfo = $origin->getDealInfo();
        $originRec = $origin->fetch();
        $aspect   = $dealInfo->quoted;
        
        $form->rec->note			   = $originRec->others;
        $form->rec->deliveryTermId     = $aspect->delivery->term;
        $form->rec->deliveryLocationId = $aspect->delivery->location;
        $form->rec->paymentMethodId    = $aspect->payment->method;
        $form->rec->bankAccountId      = $aspect->payment->bankAccountId;
        $form->rec->currencyId         = $aspect->currency;
        $form->rec->currencyRate       = $aspect->rate;
        $form->rec->chargeVat          = $aspect->vatType;
        $form->setReadOnly('chargeVat');
    }
    
    
    /**
     * Зареждане на стойности по подразбиране 
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function setDefaults(core_Mvc $mvc, core_Form $form)
    {
        $form->setDefault('valior', dt::now());
        $myCompany = crm_Companies::fetchOwnCompany();
        
        $form->setOptions('bankAccountId',  bank_Accounts::getContragentIbans($myCompany->companyId, 'crm_Companies', TRUE));
        
        if(empty($form->rec->id)){
        	
        	$form->setDefault('bankAccountId', bank_OwnAccounts::getCurrent('bankAccountId', FALSE));
	        $form->setDefault('caseId', cash_Cases::getCurrent('id', FALSE));
	        $form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
        }
	        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('deliveryLocationId')->type->options = 
            array('' => '') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        
        // Начисляване на ДДС по подразбиране
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ?
                'yes' : 'export'
        );
    }

    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $rec = static::fetchRec($rec);
        
        // Името на шаблона е и име на документа
    	$templateId = static::getTemplate($rec);
    	$templateName = doc_TplManager::getTitleById($templateId);
        
    	return "{$templateName} №{$rec->id}";
    }


    /**
     * Определяне на валутата по подразбиране при нова продажба
     */
    public static function getDefaultCurrencyId($rec)
    {
    	return acc_Periods::getBaseCurrencyCode($rec->valior);
    }
    
    
    /**
     * Определяне ст-ст по подразбиране на полето makeInvoice
     */
    public static function getDefaultMakeInvoice($rec)
    {
       return 'yes';
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        $rec = &$form->rec;
        
        // Ако не е въведен валутен курс, използва се курса към датата на документа 
        if (empty($rec->currencyRate)) {
            $rec->currencyRate = 
                currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
        } else {
        	if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, $rec->valior, $rec->currencyId, NULL)){
		    	$form->setWarning('currencyRate', $msg);
			}
        }
        $form->rec->paymentState = 'pending';
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		$amountType = $mvc->getField('amountDeal')->type;
		if($rec->state == 'active'){
			$rec->amountToDeliver = round($rec->amountDeal - $rec->amountDelivered, 2);
			$rec->amountToPay = round($rec->amountDelivered - $rec->amountPaid, 2);
			$rec->amountToInvoice = $rec->amountDelivered - $rec->amountInvoiced;
		}
		
		foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt) {
            if ($rec->{"amount{$amnt}"} == 0) {
                $row->{"amount{$amnt}"} = '<span class="quiet">0,00</span>';
            } else {
            	$value = round($rec->{"amount{$amnt}"} / $rec->currencyRate, 2);
            	$row->{"amount{$amnt}"} = $amountType->toVerbal($value);
            }
        }
        
        foreach (array('ToPay', 'ToDeliver', 'ToInvoice', 'Bl') as $amnt){
        	$color = (round($rec->{"amount{$amnt}"}, 2) < 0) ? 'red' : 'green';
        	$row->{"amount{$amnt}"} = "<span style='color:{$color}'>{$row->{"amount{$amnt}"}}</span>";
        }
        
        if($rec->paymentState == 'overdue' || $rec->paymentState == 'repaid'){
        	$row->amountPaid = "<span style='color:red'>" . strip_tags($row->amountPaid) . "</span>";
        }
        
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    	$row->paymentState = ($rec->paymentState == 'overdue' || $rec->paymentState == 'repaid') ? "<span style='color:red'>{$row->paymentState}</span>" : $row->paymentState;
    	}
	    
	    if($fields['-single']){
	    	$row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$row->id}</b> ({$row->state})";
	    	
		    $mvc->prepareHeaderInfo($row, $rec);
	        
	        if ($rec->currencyRate != 1) {
	            $row->currencyRateText = '(<span class="quiet">' . tr('курс') . "</span> {$row->currencyRate})";
	        }
	        
	        if($rec->deliveryLocationId){
	        	$row->deliveryLocationId = crm_Locations::getAddress($rec->deliveryLocationId);
	        }
	        
	    	if($rec->note){
				$notes = explode('<br>', $row->note);
				foreach ($notes as $note){
					$row->notes .= "<li>{$note}</li>";
				}
			}
			
			// Взависимост начислява ли се ддс-то се показва подходящия текст
			switch($rec->chargeVat){
				case 'yes':
					$fld = 'withVat';
					break;
				case 'separate':
					$fld = 'sepVat';
					break;
				default:
					$fld = 'noVat';
					break;
			}
			$row->$fld = ' ';
			
			$actions = type_Set::toArray($rec->contoActions);
			if(isset($actions['ship'])){
				$row->isDelivered .= tr('ДОСТАВЕНО');
			}
			
			if(isset($actions['pay'])){
				$row->isPaid .= tr('ПЛАТЕНО');
			}
			
			if($rec->makeInvoice == 'no' && isset($rec->amountToInvoice)){
				$row->amountToInvoice = "<span style='font-size:0.7em'>" . tr('без фактуриране') . "</span>";
			}
	    }
    }
    
    
    /**
     * След обработка на записите
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('initiatorId,contragentId', TRUE)
        );
        
        $data->listFields['dealerId'] = 'Търговец';
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
    
                // Търговец (чрез инициатор)
                if (!empty($rec->initiatorId)) {
                    $row->dealerId .= '<small><span class="quiet">чрез</span> ' . $row->initiatorId . "</small>";
                }
            }
        }
    }

    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        if(!Request::get('Rejected', 'int')){
        	$data->listFilter->FNC('type', 'enum(active=Активни,closed=Приключени,draft=Чернови,all=Активни и приключени,paid=Платени,overdue=Просрочени,unpaid=Неплатени,delivered=Доставени,undelivered=Недоставени)', 'caption=Тип');
	        $data->listFilter->setDefault('type', 'active');
			$data->listFilter->showFields .= ',type';
		}
		
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
		
			$data->query->XPR('paidRound', 'double', 'ROUND(#amountPaid, 2)');
			$data->query->XPR('dealRound', 'double', 'ROUND(#amountDeal, 2)');
			$data->query->XPR('deliveredRound', 'double', 'ROUND(#amountDelivered , 2)');
			
			if($filter->type) {
				switch($filter->type){
					case "all":
						break;
					case "draft":
						$data->query->where("#state = 'draft'");
						break;
					case "active":
						$data->query->where("#state = 'active'");
						break;
					case "closed":
						$data->query->where("#state = 'closed'");
						break;
					case 'paid':
						$data->query->where("#paidRound = #dealRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'overdue':
						$data->query->where("#paymentState = 'overdue'");
						break;
					case 'delivered':
						$data->query->where("#deliveredRound = #dealRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'undelivered':
						$data->query->where("#deliveredRound < #dealRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'unpaid':
						$data->query->where("#paidRound < #deliveredRound");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
				}
			}
		}
    }
    
    
    /**
     * След подготовка на заглавието на списъчния изглед
     */
    public static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Използваме заглавието на списъка за заглавие на филтър-формата
        $data->listFilter->title = $data->title;
        $data->title = NULL;
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	$diffAmount = $rec->amountPaid - $rec->amountDelivered;
    	if($rec->state == 'active'){
    		$closeArr = array('sales_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    		
    		if(sales_ClosedDeals::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn('Приключване', $closeArr, "row=2,ef_icon=img/16/closeDeal.png,title=Приключване на продажбата");
	    	} else {
	    		
	    		// Ако разликата е над допустимата но потребителя има права 'sales', той вижда бутона но не може да го използва
	    		if(!sales_ClosedDeals::isSaleDiffAllowed($rec) && haveRole('sales')){
	    			$data->toolbar->addBtn('Приключване', $closeArr, "ef_icon=img/16/closeDeal.png,title=Приключване на продажбата,error=Нямате право да приключите продажба с разлика над допустимото");
	    		}
	    	}
    		
    		// Ако протокол може да се добавя към треда и не се експедира на момента
    		if (sales_Services::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
    			$serviceUrl =  array('sales_Services', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
	            $data->toolbar->addBtn('Пр. услуги', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Продажба на услуги,order=9.22');
	        }
	        
	        // Ако ЕН може да се добавя към треда и не се експедира на момента
	    	if (store_ShipmentOrders::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
	    		$shipUrl = array('store_ShipmentOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
	            $data->toolbar->addBtn('Експедиране', $shipUrl, 'ef_icon = img/16/shipment.png,title=Експедиране на артикулите от склада,order=9.21');
	        }
	        
    		if(sales_Proformas::haveRightFor('add')){
	    		$data->toolbar->addBtn("Проформа", array('sales_Proformas', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'row=2,ef_icon=img/16/invoice.png,title=Създаване на проформа,order=9.9992');
		    }
		    
	        // Ако експедирането е на момента се добавя бутон за нова фактура
	        $actions = type_Set::toArray($rec->contoActions);
	    	
	        if(sales_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn("Фактура", array('sales_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/invoice.png,title=Създаване на фактура,order=9.9993');
		    }
		    
		    if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ');
		    }
		    
		    if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && $mvc->haveRightFor('printFiscReceipt', $rec)){
		    	$data->toolbar->addBtn('КБ', array($mvc, 'printReceipt', $rec->id), NULL, 'warning=Издаване на касова бележка ?', array('class' => "{$disClass} actionBtn", 'target' => 'iframe_a', 'title' => 'Издай касова бележка'));
		    }
    	}
    	
    	if(haveRole('debug')){
            $data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг,row=2');
    	}
    }
    
    
    /**
     * Принтиране на касова бележка
     */
    public function act_PrintReceipt()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetchRec($id));
    	$this->requireRightFor('printFiscReceipt', $rec);
    	
    	$conf = core_Packs::getConfig('sales');
    	$Driver = cls::get($conf->SALE_FISC_PRINTER_DRIVER);
    	$driverData = $this->prepareFiscPrinterData($rec);
    	
    	return $Driver->createFile($driverData);
    }
    
    
    /**
     * Подготвя данните за фискалния принтер
     */
    private function prepareFiscPrinterData($rec)
    {
    	$dQuery = $this->sales_SalesDetails->getQuery();
    	$dQuery->where("#saleId = {$rec->id}");
    	
    	$data = (object)array('products' => array(), 'payments' => array());
    	while($dRec = $dQuery->fetch()){
    		$nRec = new stdClass();
    		$nRec->id = $dRec->productId;
    		$nRec->managerId = $dRec->classId;
    		$nRec->quantity = $dRec->packQuantity;
    		if($dRec->discount){
    			$nRec->discount = (round($dRec->discount, 2) * 100) . "%";
    		}
    		$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
    		$nRec->measure = ($dRec->packagingId) ? cat_Packagings::getTitleById($dRec->packagingId) : cat_UoM::getShortName($pInfo->productRec->measureId);
    		$nRec->vat = cls::get($dRec->classId)->getVat($dRec->productId, $rec->valior);
    		$nRec->price = $dRec->packPrice;
    		
    		$data->products[] = $nRec;
    	}
    	
    	$nRec = new stdClass();
    	$nRec->type = 0;
    	$nRec->amount = round($rec->amountPaid, 2);
    	$data->payments[] = $nRec;
    	
    	return $data;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		
    		$fromProforma = ($data->fromProforma) ? TRUE : FALSE;
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, $fromProforma, $rec->tplLang);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    		
    		if($rec->paymentMethodId) {
    			$total = $this->_total->amount - $this->_total->discount;
    			$total = ($rec->chargeVat == 'separate') ? $total + $this->_total->vat : $total;
    			cond_PaymentMethods::preparePaymentPlan($data, $rec->paymentMethodId, $total, $rec->valior, $rec->currencyId);
    		}
    	}
    }
    
    
    /**
     * Може ли документ-продажба да се добави в посочената папка?
     * 
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
     * Връща подзаглавието на документа във вида "Дост: ХХХ(ууу), Плат ХХХ(ууу), Факт: ХХХ(ууу)"
     * @param stdClass $rec - запис от модела
     * @return string $subTitle - подзаглавието
     */
    private function getSubTitle($rec)
    {
    	$fields = $this->selectFields();
    	$fields['-single'] = TRUE;
    	$row = $this->recToVerbal($rec, $fields);
    	
        $subTitle = "Дост: " . (($row->amountDelivered) ? $row->amountDelivered : 0) . "({$row->amountToDeliver})";
		$subTitle .= ", Плат: " . (($row->amountPaid) ? $row->amountPaid : 0) . "({$row->amountToPay})";
        if($rec->makeInvoice != 'no'){
        	$subTitle .= ", Факт: " . (($row->amountInvoiced) ? $row->amountInvoiced : 0) . "({$row->amountToInvoice})";
        }
        
        return $subTitle;
    }
    
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = static::getRecTitle($rec);
        
        $row = (object)array(
            'title'    => $title,
        	'subTitle' => $this->getSubTitle($rec),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title,
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в продажбата
     * @param int $id - ид на продажба
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - Ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_SalesDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Sales', 'externalKey=saleId');
    	$dQuery->where("#saleId = '{$id}'");
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
    public function pushDealInfo($id, &$result)
    {
        $rec = $this->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        
        $result->setIfNot('dealType', self::AGGREGATOR_TYPE);
        
        // Извличаме продуктите на продажбата
        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$rec->id}");
        $detailRecs = $dQuery->fetchAll();
       
        if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
        	// Колко е очакваното авансово плащане
        	$downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
		}
        
        // Кои са позволените операции за последващите платежни документи
        $result->set('allowedPaymentOperations', $this->getPaymentOperations($rec));
        $result->set('allowedShipmentOperations', $this->getShipmentOperations($rec));
        $result->set('involvedContragents', array((object)array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId)));
        
        $result->set('amount', $rec->amountDeal);
        $result->setIfNot('currency', $rec->currencyId);
        $result->setIfNot('rate', $rec->currencyRate);
        $result->setIfNot('vatType', $rec->chargeVat);
        $result->setIfNot('agreedValior', $rec->valior);
        $result->setIfNot('deliveryLocation', $rec->deliveryLocationId);
        $result->setIfNot('deliveryTime', $rec->deliveryTime);
        $result->setIfNot('deliveryTerm', $rec->deliveryTermId);
        $result->setIfNot('storeId', $rec->shipmentStoreId);
        $result->setIfNot('paymentMethodId', $rec->paymentMethodId);
        $result->setIfNot('caseId', $rec->caseId);
        $result->setIfNot('bankAccountId', $rec->bankAccountId);
        
        sales_transaction_Sale::clearCache();
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', sales_transaction_Sale::getDownpayment($rec->id));
        $result->set('amountPaid', sales_transaction_Sale::getPaidAmount($rec->id));
        $result->set('deliveryAmount', sales_transaction_Sale::getDeliveryAmount($rec->id));
        $result->set('blAmount', sales_transaction_Sale::getBlAmount($rec->id));
        
        // Спрямо очакваното авансово плащане ако има, кои са дефолт платежните операции
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        if($agreedDp && ($actualDp < $agreedDp)){
        	$result->set('defaultCaseOperation', 'customer2caseAdvance');
        	$result->set('defaultBankOperation', 'customer2bankAdvance');
        } else {
        	$result->set('defaultCaseOperation', 'customer2case');
        	$result->set('defaultBankOperation', 'customer2bank');
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId           = $dRec->classId;
            $p->productId         = $dRec->productId;
            $p->packagingId       = $dRec->packagingId;
            $p->discount          = $dRec->discount;
            $p->quantity          = $dRec->quantity;
            $p->quantityDelivered = $dRec->quantityDelivered;
            $p->price             = $dRec->price;
            $p->uomId             = $dRec->uomId;
            
            $ProductMan = cls::get($p->classId);
            $p->weight  = $ProductMan->getWeight($p->productId, $p->packagingId);
            $p->volume  = $ProductMan->getVolume($p->productId, $p->packagingId);
            
            $result->push('products', $p);
            
            if (isset($actions['ship']) && !empty($dRec->packagingId)) {
            	$push = TRUE;
            	$index = $dRec->classId . "|" . $dRec->productId;
            	$shipped = $result->get('shippedPacks');
            	if($shipped && isset($shipped[$index])){
            		if($shipped[$index]->inPack < $dRec->quantityInPack){
            			$push = FALSE;
            		}
            	}
            	
            	if($push){
            		$arr = (object)array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
            		$result->push('shippedPacks', $arr, $index);
            	}
            }
         }
         
         $result->set('contoActions', $actions);
         $result->set('shippedProducts', sales_transaction_Sale::getShippedProducts($rec->id));
    }
    
    
    /**
     * Кои са позволените операции за експедиране
     */
    public function getShipmentOperations($id)
    {
    	return $this->allowedShipmentOperations;
    }
    
    
    /**
     * Кои са позволените платежни операции за тази сделка
     */
    public function getPaymentOperations($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$allowedPaymentOperations = $this->allowedPaymentOperations;
    	 
    	if($rec->paymentMethodId){
    
    		// Ако има метод за плащане и той няма авансова част, махаме авансовите операции
    		if(!cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
    			unset($allowedPaymentOperations['customer2caseAdvance'], 
    					$allowedPaymentOperations['customer2bankAdvance'], 
    					$allowedPaymentOperations['caseAdvance2customer'],
    					$allowedPaymentOperations['bankAdvance2customer']);
    		}
    	}
    	 
    	return $allowedPaymentOperations;
    }
    
    
	/**
	 * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази продажба
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
        $saleRec = $this->fetchRec($id);
    	
    	$saleDocuments = $this->getDescendants($saleRec->id);
        
    	$aggregateInfo = new bgerp_iface_DealAggregator;
    	
        // Извличаме dealInfo от самата продажба
        $this->pushDealInfo($saleRec->id, $aggregateInfo);
        
        foreach ($saleDocuments as $d) {
            $dState = $d->rec('state');
            if ($dState == 'draft' || $dState == 'rejected') {
                // Игнорираме черновите и оттеглените документи
                continue;
            }
        
            if ($d->haveInterface('bgerp_DealIntf')) {
                $d->instance->pushDealInfo($d->that, $aggregateInfo);
            }
        }
        
        return $aggregateInfo;
    }
    
    
    /**
     * При нова продажба, се ънсетва threadId-то, ако има
     */
    static function on_AfterPrepareDocumentLocation($mvc, $form)
    {   
    	if($form->rec->threadId && !$form->rec->id){
		     unset($form->rec->threadId);
		}
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
    	// Кои потребители ще се нотифицират
    	$rec->sharedUsers = '';
		$actions = type_Set::toArray($rec->contoActions);
    	
    	// Ако има склад, се нотифицира отговорника му
    	if(empty($actions['ship']) && $rec->shipmentStoreId){
    		$toChiefs = store_Stores::fetchField($rec->shipmentStoreId, 'chiefs');
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $toChiefs);
    	}
    		
    	// Ако има каса се нотифицира касиера
    	if(empty($actions['pay']) && $rec->caseId){
    		$toCashiers = cash_Cases::fetchField($rec->caseId, 'cashiers');
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $toCashiers);
    	}
    		
    	// Ако има б. сметка се нотифицират операторите и
    	if($rec->bankAccountId){
    		$operators = bank_OwnAccounts::fetchField("#bankAccountId = '{$rec->bankAccountId}'",'operators');
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $operators);
    	}
    		
    	// Текущия потребител се премахва от споделянето
    	$rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
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
    		
    		// Записване на продажбата като отворена сделка
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
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    		$tpl->removeBlock('STATISTIC_BAR');
    		$tpl->removeBlock('shareLog');
    	}
    	
    	if($data->paymentPlan){
    		$tpl->placeObject($data->paymentPlan);
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата продажба") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Приключва всички приключени продажби
     */
    function cron_CloseOldSales()
    {
    	$conf = core_Packs::getConfig('sales');
    	$olderThan = $conf->SALE_CLOSE_OLDER_THAN;
    	$limit = $conf->SALE_CLOSE_OLDER_NUM;
    	$ClosedDeals = cls::get('sales_ClosedDeals');
    	
    	$CronHelper = cls::get('acc_CronDealsHelper', array('className' => $this->className));
    	$CronHelper->closeOldDeals($olderThan, $ClosedDeals, $limit);
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    private function setCron(&$res)
    {
    	// Крон метод за затваряне на остарели продажби
    	$rec = new stdClass();
        $rec->systemId = "Close sales";
        $rec->description = "Затваряне на приключените продажби";
        $rec->controller = "sales_Sales";
        $rec->action = "CloseOldSales";
        $rec->period = 180;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        // Проверка по крон дали продажбата е просрочена
        $rec2 = new stdClass();
        $rec2->systemId = "IsSaleOverdue";
        $rec2->description = "Проверяване за просрочени продажби";
        $rec2->controller = "sales_Sales";
        $rec2->action = "CheckSalesPayments";
        $rec2->period = 60;
        $rec2->offset = 0;
        $rec2->delay = 0;
        $rec2->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
    	if($Cron->addOnce($rec)) {
            $res .= "<li class='green'>Задаване на крон да приключва стари продажби.</li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да приключва стари продажби.</li>";
        }
        
    	if($Cron->addOnce($rec2)) {
            $res .= "<li class='green'>Задаване на крон да проверява дали продажбата е просрочена.</li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да проверява дали продажбата е просрочена.</li>";
        }
    }
    
    
    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    private function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Договор за продажба',    'content' => 'sales/tpl/sales/Sale.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Договор за изработка',   'content' => 'sales/tpl/sales/Manufacturing.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Договор за услуга',      'content' => 'sales/tpl/sales/Service.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Sales contract',         'content' => 'sales/tpl/sales/SaleEN.shtml', 'lang' => 'en');
    	$tplArr[] = array('name' => 'Manufacturing contract', 'content' => 'sales/tpl/sales/ManufacturingEN.shtml', 'lang' => 'en');
    	$tplArr[] = array('name' => 'Service contract',       'content' => 'sales/tpl/sales/ServiceEN.shtml', 'lang' => 'en');
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$mvc->setCron($res);
    	$mvc->setTemplates($res);
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
     * Помощна ф-я показваща дали в продажбата има поне един складируем/нескладируем артикул
     * 
     * @param int $id - ид на продажба
     * @param boolean $storable - дали се търсят складируеми или нескладируеми артикули
     * @return boolean TRUE/FALSE - дали има поне един складируем/нескладируем артикул
     */
    public function hasStorableProducts($id, $storable = TRUE)
    {
    	$rec = $this->fetchRec($id);
    	$dQuery = sales_SalesDetails::getQuery();
    	$dQuery->where("#saleId = {$rec->id}");
    	$detailRecs = $dQuery->fetchAll();
    	
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
    
    
	/**
     * Проверява дали продажбата е просрочена или платени
     */
    function cron_CheckSalesPayments()
    {
    	$conf = core_Packs::getConfig('sales');
    	$overdueDelay = $conf->SALE_OVERDUE_CHECK_DELAY;
    	
    	$CronHelper = cls::get('acc_CronDealsHelper', array('className' => $this->className));
    	$CronHelper->checkPayments($overdueDelay);
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
     	$query = sales_SalesDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#saleId  = '{$rec->id}'");
     	
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
      * Перо в номенклатурите, съответстващо на този продукт
      *
      * Част от интерфейса: acc_RegisterIntf
      */
     static function getItemRec($objectId)
     {
     	$result = NULL;
     	$self = cls::get(__CLASS__);
     
     	if ($rec = self::fetch($objectId)) {
     		$contragentName = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId);
     		$result = (object)array(
     				'num' => $self->abbr . $objectId,
     				'title' => static::getRecTitle($objectId),
     				'features' => array('Контрагент' => $contragentName)
     		);
     	}
     
     	return $result;
     }
     
     
     /**
      * @see acc_RegisterIntf::itemInUse()
      * @param int $objectId
      */
     static function itemInUse($objectId)
     {
     }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
    	$aggregateDealInfo = $mvc->getAggregateDealInfo($rec->id);
    	
    	// Преизчисляваме общо платената и общо експедираната сума
    	$rec->amountPaid      = $aggregateDealInfo->get('amountPaid');
    	$rec->amountDelivered = $aggregateDealInfo->get('deliveryAmount');
    	$rec->amountInvoiced  = $aggregateDealInfo->get('invoicedAmount');
    	$rec->amountBl 		  = $aggregateDealInfo->get('blAmount');
    
    	$rec->paymentState  = $mvc->getPaymentState($aggregateDealInfo, $rec->paymentState);
    	
    	$mvc->save($rec);
    	
    	$dQuery = $mvc->sales_SalesDetails->getQuery();
    	$dQuery->where("#saleId = {$rec->id}");
    	
    	// Намираме всички експедирани продукти, и обновяваме на договорените колко са експедирани
    	$shippedProducts = $aggregateDealInfo->get('shippedProducts');
    	while($product = $dQuery->fetch()){
    		$delivered = 0;
    		if(count($shippedProducts)){
    			foreach ($shippedProducts as $key => $shipped){
    				if($product->classId == $shipped->classId && $product->productId == $shipped->productId){
    					$delivered = $shipped->quantity;
    					break;
    				}
    			}
    		}
    		
    		$product->quantityDelivered = $delivered;
    		$mvc->sales_SalesDetails->save($product);
    	}
    }
    
    
    /**
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
    	return TRUE;
    }
    
    
    /**
     * Да се показвали бърз бутон за създаване на документа в папка
     */
    public function mustShowButton($folderRec, $userId = NULL)
    {
    	$Cover = doc_Folders::getCover($folderRec->id);
    	
    	// Ако папката е на контрагент
    	if($Cover->haveInterface('doc_ContragentDataIntf')){
    		$groupList = $Cover->fetchField($Cover->instance->groupsField);
    		$clientGroupId = crm_Groups::fetchField("#sysId = 'customers'");
    		
    		// и той е в група 'клиенти'
    		if(keylist::isIn($clientGroupId, $groupList)){
    			
    			return TRUE;
    		}
    	}
    	
    	// Ако не е контрагент или не е в група 'клиенти' не слагаме бутон
    	return FALSE;
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	//Ако потребителя не е в група доставчици го включваме
    	$rec = $mvc->fetchRec($rec);
    	cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, 'customers');
    }
    
    
    /**
     * Ако с тази продажба е приключена друга продажба
     */
    public static function on_AfterClosureWithDeal($mvc, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	
    	// Намираме всички продажби които са приключени с тази
    	$details = array();
    	$closedDeals = sales_ClosedDeals::getClosedWithDeal($rec->id);
    	
    	if(count($closedDeals)){
    		
    		// За всяка от тях, включително и този документ
    		foreach ($closedDeals as $doc){
    		
    			// Взимаме договорените продукти от продажбата начало на нейната нишка
    			$firstDoc = doc_Threads::getFirstDocument($doc->threadId);
    			$dealInfo = $firstDoc->getAggregateDealInfo();
    			$products = (array)$dealInfo->get('products');
    			if(count($products)){
    				foreach ($products as $p){
    		
    					// Обединяваме множествата на договорените им продукти
    					$index = $p->classId . "|" . $p->productId;
    					$d = &$details[$index];
    					$d = (object)$d;
    		
    					$d->classId = $p->classId;
    					$d->productId = $p->productId;
    					$d->uomId = $p->uomId;
    					$d->quantity += $p->quantity;
    					$d->price = ($d->price) ? ($d->price + $p->price) / 2 : $p->price;
    					if(!empty($d->discount) || !empty($p->discount)){
    						$d->discount = ($d->discount + $p->discount) / 2;
    					}
    		
    					$info = cls::get($p->classId)->getProductInfo($p->productId, $p->packagingId);
    					$p->quantityInPack = ($p->packagingId) ? $info->packagingRec->quantity : 1;
    					if(empty($d->packagingId)){
    						$d->packagingId = $p->packagingId;
    						$d->quantityInPack = $p->quantityInPack;
    					} else {
    						if($p->quantityInPack < $d->quantityInPack){
    							$d->packagingId = $p->packagingId;
    							$d->quantityInPack = $p->quantityInPack;
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Изтриваме досегашните детайли на продажбата
    	sales_SalesDetails::delete("#saleId = {$rec->id}");
    	
    	// Записваме новите
    	if(count($details)){
    		foreach ($details as $d1){
    			$d1->saleId = $rec->id;
    			$mvc->sales_SalesDetails->save($d1);
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'printfiscreceipt' && isset($rec)){
    		
    		$actions = type_Set::toArray($rec->contoActions);
    		if ($actions['ship'] && $actions['pay']) {
    			$conf = core_Packs::getConfig('sales');
    			
    			// Ако няма избран драйвер за принтер или той е деинсталиран никой не може да издава касова бележка
    			if($conf->SALE_FISC_PRINTER_DRIVER == '' || core_Classes::fetchField($conf->SALE_FISC_PRINTER_DRIVER, 'state') == 'closed'){
    				$res = 'no_one';
    			}
    		} else {
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     *
     * @param unknown $mvc
     * @param unknown $rec
     * @param unknown $nRec
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
    	unset($nRec->contoActions, $nRec->paymentState);
    }
    
    
    /**
     * 
     * @param unknown $mvc
     * @param unknown $rec
     * @param unknown $nRec
     */
    function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
    	
    	
    	//@TODO да се премахне след като се добави тази функционалността в плъгина
    	$query = sales_SalesDetails::getQuery();
    	$query->where("#saleId = {$rec->id}");
    	while($dRec = $query->fetch()){
    		$dRec->saleId = $nRec->id;
    		unset($dRec->id);
    		sales_SalesDetails::save($dRec);
    	}
    }
}