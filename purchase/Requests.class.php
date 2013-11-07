<?php



/**
 * Мениджър на заявки за покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class purchase_Requests extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Покупки';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
        acc_RegisterIntf=sales_RegisterImpl,
        acc_TransactionSourceIntf=sales_TransactionSourceImpl';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
        doc_DocumentPlg, plg_ExportCsv, cond_plg_DefaultValues,
        doc_EmailCreatePlg, bgerp_plg_Blank,
        doc_plg_BusinessDoc2, acc_plg_Registry, store_plg_Shippable, acc_plg_DocumentSummary';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,purchase';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, contragentClassId, contragentId, currencyId, amountDeal, 
                            amountDelivered, amountPaid, 
                             dealerId,
                             createdOn, createdBy';


    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'purchase_RequestDetails' ;
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Покупка';


    /**
     * Лейаут на единичния изглед 
     */
    var $singleLayoutFile = 'purchase/tpl/SingleLayoutRequest.shtml';
    
    
    /**
     * Документа покупка може да бъде само начало на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.2|Логистика";
    
    
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
    	'isInstantShipment'  => 'lastDocUser|lastDoc',
    	'chargeVat'			 => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 
            'caption=Фактуриране,maxRadio=3,columns=3');
        $this->FLD('chargeVat', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=ДДС');
        
        /*
         * Стойности
         */
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Доставчик');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 
            'caption=Доставка->Условие,salecondSysId=deliveryTerm');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Доставка->От обект,silent'); // обект, от който да се приеме стоката
        $this->FLD('deliveryTime', 'datetime', 
            'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 
            'caption=Доставка->До склад'); // наш склад, до който да бъде доставена стоката
        $this->FLD('isInstantShipment', 'enum(no=По-късно,yes=На момента)', 
            'input, maxRadio=2, columns=2, caption=Получаване');
        
        /*
         * Плащане
         */
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=name)',
            'caption=Плащане->Начин,salecondSysId=paymentMethod');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)',
            'caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Плащане->Банкова сметка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Плащане->Каса');
        $this->FLD('isInstantPayment', 'enum(no=Не,yes=Да)', 'input,maxRadio=2, columns=2, caption=Плащане на момента');
        
        /*
         * Наш персонал
         */
        $this->FLD('dealerId', 'user(allowEmpty)',
            'caption=Наш персонал->Закупчик');

        /*
         * Допълнително
         */
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към');
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        
        /* @var $form core_Form */
        $form = $data->form;
       
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
        $form->getField('deliveryLocationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        
        /*
         * Начисляване на ДДС по подразбиране
         */
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ?
                'yes' : 'export'
        );
        
        /*
         * Моментни експедиция и плащане по подразбиране
         */
        if (empty($form->rec->id)) {
            $isInstantShipment = !empty($form->rec->shipmentStoreId);
            $isInstantShipment = $isInstantShipment && 
                ($form->rec->shipmentStoreId == store_Stores::getCurrent('id', FALSE));
            $isInstantShipment = $isInstantShipment && 
                store_Stores::fetchField($form->rec->shipmentStoreId, 'chiefId');
            
            $isInstantPayment = !empty($form->rec->caseId);
            $isInstantPayment = $isInstantPayment && 
                ($form->rec->caseId == store_Stores::getCurrent('id', FALSE));
            $isInstantPayment = $isInstantPayment && 
                store_Stores::fetchField($form->rec->shipmentStoreId, 'chiefId');
            
            $form->setDefault('isInstantShipment', 
                $isInstantShipment ? 'yes' : 'no');
            $form->setDefault('isInstantPayment', 
                $isInstantPayment ? 'yes' : 'no');
        }
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("Покупка| №" . $rec->id);
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
    
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
    
                // "Изчисляване" на името на доставчика
                $contragentData = NULL;
    
                if ($rec->contragentClassId && $rec->contragentId) {
    
                    $contragent = new core_ObjectReference(
                        $rec->contragentClassId,
                        $rec->contragentId
                    );
    
                    $row->contragentClassId = $contragent->getHyperlink();
                }
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (empty($row->amountDeal)) {
            $row->amountDeal = '0.00';
        }
        $row->amountDeal = $row->currencyId . ' ' . $row->amountDeal;
    
        if (!empty($rec->amountPaid)) {
            $row->amountPaid = $row->currencyId . ' ' . $row->amountPaid;
        }
    
        $amountType = $mvc->getField('amountDeal')->type;
    
        $row->amountToPay = $row->currencyId . ' '
        . $amountType->toVerbal($rec->amountDeal - $rec->amountPaid);
    
        if ($rec->chargeVat == 'freed' || $rec->chargeVat == 'export') {
            $row->chargeVat = '';
        }
    
        if ($rec->isInstantPayment == 'yes') {
            $row->caseId .= ' (на момента)';
        }
        if ($rec->isInstantShipment == 'yes') {
            $row->shipmentStoreId .= ' (на момента)';
        }
    }


    /**
     * След рендиране на единичния изглед
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        // Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();
    
        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }
    
        $tpl->placeArray(
            array(
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
     * @return array - интефейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
}