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
                          acc_TransactionSourceIntf=sales_TransactionSourceImpl,
                          bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, doc_plg_TplManager, acc_plg_DealsChooseOperation, doc_DocumentPlg, acc_plg_Contable,
                    acc_plg_DocumentSummary, plg_Search, plg_ExportCsv, doc_plg_HidePrices, cond_plg_DefaultValues,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, doc_SharablePlg';
    
    
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
    	'dealerId'           => 'lastDocUser|lastDoc|defMethod',
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
    		'case2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '501'),
    		'bank2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '503'),
    		'caseAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '501'),
    		'bankAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '503'),
    		'debitDeals'           => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '411'),
    		'creditDeals'          => array('title' => 'Прихващане на задължение', 'debit' => '411', 'credit' => '*'),
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
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е платена
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
        
    	$this->FLD('paymentState', 'enum(pending=Чакащо,overdue=Просроченo,paid=Платенo)', 'caption=Плащане, input=none');
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
        
        price_Helper::fillRecs($recs, $rec);
        
        // ДДС-то е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amountDeal = ($rec->chargeVat == 'separate') ? $rec->_total->amount + $rec->_total->vat : $rec->_total->amount;
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
        
                $saleProduct = new sales_model_SaleProduct(NULL);
        
                $saleProduct->saleId      = $rec->id;
                $saleProduct->classId     = cls::get($product->classId)->getClassId();
                $saleProduct->productId   = $product->productId;
                $saleProduct->packagingId = $product->packagingId;
                $saleProduct->quantity    = $product->quantity;
                $saleProduct->discount    = $product->discount;
                $saleProduct->price       = $product->price;
                $saleProduct->uomId       = $product->uomId;
        
                $saleProduct->quantityInPack = $saleProduct->getQuantityInPack();
                
                $saleProduct->save();
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
        
        $priceAtDateFld = &$form->fields['pricesAtDate']->type;
        $priceAtDateFld->params['max'] = dt::addMonths($maxMonths);
        $priceAtDateFld->params['min'] = dt::addMonths(-$minMonths);
        
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    	$form->setField('sharedUsers', 'input=none');
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
     * Помощен метод за определяне на търговец по подразбиране.
     * 
     * Правило за определяне: първия, който има права за създаване на продажби от списъка:
     * 
     *  1/ Отговорника на папката на контрагента
     *  2/ Текущият потребител
     *  
     *  Ако никой от тях няма права за създаване - резултатът е NULL
     *
     * @param stdClass $rec запис на модела sales_Sales
     * @return int|NULL user(roles=sales)
     */
    public static function getDefaultDealerId($rec)
    {
        expect($rec->folderId);

        // Отговорника на папката на контрагента ...
        $inChargeUserId = doc_Folders::fetchField($rec->folderId, 'inCharge');
        if (self::haveRightFor('add', NULL, $inChargeUserId)) {
            // ... има право да създава продажби - той става дилър по подразбиране.
            return $inChargeUserId;
        }
        
        // Текущия потребител ...
        $currentUserId = core_Users::getCurrent('id');
        if (self::haveRightFor('add', NULL, $currentUserId)) {
            // ... има право да създава продажби
            return $currentUserId;
        }
        
        return NULL;
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
		$rec->amountToDeliver = round($rec->amountDeal - $rec->amountDelivered, 2);
		$rec->amountToPay = round($rec->amountDelivered - $rec->amountPaid, 2);
		$rec->amountToInvoice = round($rec->amountDelivered - $rec->amountInvoiced, 2);
		
		foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay', 'ToDeliver', 'ToInvoice') as $amnt) {
            if ($rec->{"amount{$amnt}"} == 0) {
                $row->{"amount{$amnt}"} = '<span class="quiet">0,00</span>';
            } else {
            	$value = $rec->{"amount{$amnt}"} / $rec->currencyRate;
            	$row->{"amount{$amnt}"} = $amountType->toVerbal($value);
            }
        }
        
        foreach (array('ToPay', 'ToDeliver', 'ToInvoice') as $amnt){
        	$color = ($rec->{"amount{$amnt}"} < 0) ? 'red' : 'green';
        	$row->{"amount{$amnt}"} = "<span style='color:{$color}'>{$row->{"amount{$amnt}"}}</span>";
        }
        
        if($rec->paymentState == 'overdue'){
        	$row->amountPaid = "<span style='color:red'>" . strip_tags($row->amountPaid) . "</span>";
        }
        
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    	$row->paymentState = ($rec->paymentState == 'overdue') ? "<span style='color:red'>{$row->paymentState}</span>" : $row->paymentState;
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
			
			if($rec->makeInvoice == 'no'){
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
                    $row->dealerId .= '<small style="display: block;"><span class="quiet">чрез</span> ' . $row->initiatorId;
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
        	$data->listFilter->FNC('type', 'enum(active=Активни,closed=Приключени,draft=Чернови,all=Активни и приключени,paid=Платени,overdue=Просрочени,unpaid=Неплатени,delivered=Доставени,undelivered=Недоставени)', 'caption=Тип,width=13em');
	        $data->listFilter->setDefault('type', 'active');
			$data->listFilter->showFields .= ',dealerId,type';
			$data->listFilter->setField('dealerId', 'caption=Търговец,width=13em');
			$data->listFilter->setDefault('dealerId', core_Users::getCurrent());
		}
		
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
			
			if($filter->dealerId){
				$data->query->where("#dealerId = {$filter->dealerId}");
			}
		
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
						$data->query->where("#amountPaid = #amountDeal");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'overdue':
						$data->query->where("#paymentState = 'overdue'");
						break;
					case 'delivered':
						$data->query->where("#amountDelivered = #amountDeal");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'undelivered':
						$data->query->orWhere("#amountDelivered < #amountDeal");
						$data->query->where("#state = 'active' || #state = 'closed'");
						break;
					case 'unpaid':
						$data->query->where("#amountPaid < #amountDelivered");
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
		    
		    if(cash_Pko::haveRightFor('add')){
		    	$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add')){
		    	$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ');
		    }
    	}
    	
    	if(haveRole('debug')){
            $data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг,row=2');
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
    		
    		$fromProforma = ($data->fromProforma) ? TRUE : FALSE;
    		$data->summary = price_Helper::prepareSummary($rec->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, $fromProforma, $rec->tplLang);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    		
    		if($rec->paymentMethodId) {
    			$total = $rec->_total->amount- $rec->_total->discount;
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
     * Трасира веригата от документи, породени от дадена продажба. Извлича от тях експедираните 
     * количества и платените суми.
     * 
     * @param core_Mvc $mvc
     * @param core_ObjectReference $saleRef
     * @param core_ObjectReference $descendantRef кой породен документ е инициатор на трасирането
     */
    public static function on_DescendantChanged($mvc, $saleRef, $descendantRef = NULL)
    {
        $saleRec = new sales_model_Sale($saleRef->rec());
    	$aggregatedDealInfo = $mvc->getAggregateDealInfo($saleRef->that);
		
        $saleRec->updateAggregateDealInfo($aggregatedDealInfo);
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
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = new sales_model_Sale(self::fetchRec($id));
        $actions = type_Set::toArray($rec->contoActions);
        
        // Извличаме продуктите на продажбата
        $detailRecs = $rec->getDetails('sales_SalesDetails', 'sales_model_SaleProduct');
                
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $allowedPaymentOperations = $this->allowedPaymentOperations;
       
        if(!cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
        	unset($allowedPaymentOperations['customer2caseAdvance'], $allowedPaymentOperations['customer2bankAdvance'],$allowedPaymentOperations['caseAdvance2customer'],$allowedPaymentOperations['bankAdvance2customer']);
        } else {
        	// Колко е очакваното авансово плащане
        	$downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
		}
        
        // Кои са позволените операции за последващите платежни документи
        $result->allowedPaymentOperations = $allowedPaymentOperations;
        $result->involvedContragents = array((object)array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId));
        
        $result->agreed->amount                 = $rec->amountDeal;
        $result->agreed->downpayment            = ($downPayment) ? $downPayment : NULL;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->rate               	= $rec->currencyRate;
        $result->agreed->vatType 				= $rec->chargeVat;
        $result->agreed->valior 				= $rec->valior;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->storeId      = $rec->shipmentStoreId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        if (isset($actions['pay'])) {
            $result->paid->amount   			  = $rec->amountDeal;
            $result->agreed->downpayment          = ($downPayment) ? $downPayment : NULL;
            $result->paid->currency 			  = $rec->currencyId;
            $result->paid->rate                   = $rec->currencyRate;
            $result->paid->vatType 				  = $rec->chargeVat;
            $result->paid->payment->method        = $rec->paymentMethodId;
            $result->paid->payment->bankAccountId = $rec->bankAccountId;
            $result->paid->payment->caseId        = $rec->caseId;
        }

        if (isset($actions['ship'])) {
            $result->shipped->amount             = $rec->amountDeal;
            $result->agreed->downpayment         = ($downPayment) ? $downPayment : NULL;
            $result->shipped->currency           = $rec->currencyId;
            $result->shipped->rate               = $rec->currencyRate;
            $result->shipped->vatType 			 = $rec->chargeVat;
            $result->shipped->delivery->location = $rec->deliveryLocationId;
            $result->shipped->delivery->storeId  = $rec->shipmentStoreId;
            $result->shipped->delivery->term     = $rec->deliveryTermId;
            $result->shipped->delivery->time     = $rec->deliveryTime;
        }
        
        /* @var $dRec sales_model_SaleProduct */
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $ProductMan = cls::get($p->classId);
            $p->weight  = $ProductMan->getWeight($p->productId, $p->packagingId);
            $p->volume  = $ProductMan->getVolume($p->productId, $p->packagingId);
            
            $result->agreed->products[] = $p;
            
            if (isset($actions['ship'])) {
            	
            	if($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate'){
            		
            		// Отбелязваме че има ддс за начисляване от експедирането съответно за видовете продукти
	            	$vat = $ProductMan->getVat($dRec->productId, $rec->valior);
	            	$vatAmount = $dRec->price * $dRec->quantity * $vat;
	            	$code = $dRec->classId . "|" . $dRec->productId . "|" . $dRec->packagingId;
	            	$result->invoiced->vatToCharge[$code] += $vatAmount;
            	}
            	
            	$result->shipped->products[] = clone $p;
            }
        }
        
        return $result;
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
        $saleRec = new sales_model_Sale($id);
    	
    	$saleDocuments = $this->getDescendants($saleRec->id);
        
        // Извличаме dealInfo от самата продажба
        /* @var $saleDealInfo bgerp_iface_DealResponse */
        $saleDealInfo = $this->getDealInfo($saleRec->id);
        
        // dealInfo-то на самата продажба е база, в/у която се натрупват някой от аспектите
        // на породените от нея документи (платежни, експедиционни, фактури)
        $aggregateInfo = clone $saleDealInfo;
        
        /* @var $d core_ObjectReference */
        foreach ($saleDocuments as $d) {
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
    	$tolerance = $conf->SALE_CLOSE_TOLERANCE;
    	$olderThan = $conf->SALE_CLOSE_OLDER_THAN;
    	$ClosedDeals = cls::get('sales_ClosedDeals');
    	
    	$CronHelper = cls::get('acc_CronDealsHelper', array('className' => $this->className));
    	$CronHelper->closeOldDeals($olderThan, $tolerance, $ClosedDeals);
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    private function setCron(&$res)
    {
    	// Крон метод за затваряне на остарели продажби
    	$rec = new stdClass();
        $rec->systemId = "Close sales";
        $rec->description = "Затваря приключените продажби";
        $rec->controller = "sales_Sales";
        $rec->action = "CloseOldSales";
        $rec->period = 1440;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        // Проверка по крон дали продажбата е просрочена
        $rec2 = new stdClass();
        $rec2->systemId = "IsSaleOverdue";
        $rec2->description = "Проверява дали продажбата е просрочена";
        $rec2->controller = "sales_Sales";
        $rec2->action = "CheckSalesPayments";
        $rec2->period = 60;
        $rec2->offset = 0;
        $rec2->delay = 0;
        $rec2->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
    	if($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да приключва стари продажби.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да приключва стари продажби.</li>";
        }
        
    	if($Cron->addOnce($rec2)) {
            $res .= "<li><font color='green'>Задаване на крон да проверява дали продажбата е просрочена.</font></li>";
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
    	
    	$skipped = $added = $updated = 0;
    	foreach ($tplArr as $arr){
    		$arr['docClassId'] = $this->getClassId();
    		doc_TplManager::addOnce($arr, $added, $updated, $skipped);
    	}
    	
    	$res .= "<li><font color='green'>Добавени са {$added} шаблона за продажби, обновени са {$updated}, пропуснати са {$skipped}</font></li>";
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
     * @param int $id - ид на продажба
     * @param boolean $storable - дали се търсят складируеми или нескладируеми артикули
     * @return boolean TRUE/FALSE - дали има поне един складируем/нескладируем артикул
     */
    public function hasStorableProducts($id, $storable = TRUE)
    {
    	$rec = new sales_model_Sale(self::fetchRec($id));
        $detailRecs = $rec->getDetails('sales_SalesDetails', 'sales_model_SaleProduct');
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
}