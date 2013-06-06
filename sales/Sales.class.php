<?php
/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Sales extends core_Master
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Продажби';


    /**
     * Абревиатура
     */
    var $abbr = 'Sal';
    
    
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
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank,
                    doc_plg_BusinessDoc, acc_plg_Registry, store_plg_Shippable';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'admin,sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'admin,sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'admin,sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin,sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin,sales';
    

    /**
     * Документа продажба може да бъде само начало на нишка
     * 
     * Допълнително, папката в която могат да се създават нишки-продажби трябва да бъде с корица
     * контрагент. Това се гарантира с метода @see canAddToFolder()
     */
    var $onlyFirstInThread = TRUE;
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, contragentClassId, contragentId, currencyId, amountDeal, amountDelivered, amountPaid, 
                             dealerId, initiatorId,
                             createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     */
    public $rowToolsField;


    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'sales_SalesDetails' ;
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продажба';
    
    
    /**
     * 
     */
   var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoiceSale.shtml';
   
    /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.1|Търговия";
   
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 
            'caption=Фактуриране,maxRadio=3,columns=3');
        $this->FLD('chargeVat', 'enum(yes=с ДДС,no=без ДДС)', 'caption=ДДС');
        
        /*
         * Стойности
         */
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none'); // Сумата която е платена
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 
            'caption=Доставка->Условие');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Доставка->Обект до,silent'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 
            'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 
            'caption=Доставка->От склад'); // наш склад, от където се експедира стоката
        
        /*
         * Плащане
         */
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)',
            'caption=Плащане->Начин,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)',
            'caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Плащане->Банкова сметка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Плащане->Каса');
        
        /*
         * Наш персонал
         */
        $this->FLD('initiatorId', 'user(roles=user,allowEmpty)',
            'caption=Наш персонал->Инициатор');
        $this->FLD('dealerId', 'user(allowEmpty)',
            'caption=Наш персонал->Търговец');

        /*
         * Допълнително
         */
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към');
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    	
    	$this->fields['dealerId']->type->params['roles'] = $this->getRequiredRoles('add');
    }
    
    public static function on_AfterSave($mvc)
    {
    }


    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        switch ($action) {
            /*
             * Контират се само документи (продажби) които генерират *непразни* транзакции.
             * Документите (продажбите), които не генерират счетоводни транзакции могат да се
             * активират.
             */
            case 'conto':
            case 'activate':
                if (empty($rec->id) || $rec->state != 'draft') {
                    // Незаписаните продажби не могат нито да се контират, нито да се активират
                    $requiredRoles = 'no_one';
                    break;
                } 
                
                if (($transaction = $mvc->getValidatedTransaction($rec)) === FALSE) {
                    // Невъзможно е да се генерира транзакция
                    $requiredRoles = 'no_one';
                    break;
                }
                
                // Активиране е позволено само за продажби, които не генерират транзакции
                // Контиране е позволено само за продажби, които генерират транзакции
                $deniedAction = ($transaction->isEmpty() ? 'conto' : 'activate');
                
                if ($action == $deniedAction) {
                    $requiredRoles = 'no_one';
                }
                break;
        }
    }

    
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
        
        if ($data->rec->currencyRate != 1) {
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
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
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
        
        if (empty($data->form->rec->dealerId)) {
            $form->setDefault('dealerId', $mvc::getDefaultDealer($data->form->rec));
        }
        
        if (empty($form->rec->folderId)) {
            expect($form->rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        /*
         * Условия за доставка по подразбиране
         */
        if (empty($form->rec->deliveryTermId)) {
            $form->rec->deliveryTermId = $mvc::getDefaultDeliveryTermId($form->rec);
        }
        
        /*
         * Начин на плащане по подразбиране
         */
        if (empty($form->rec->paymentMethodId)) {
            $form->rec->paymentMethodId = $mvc::getDefaultPaymentMethodId($form->rec);
        }
        
        /*
         * Валута на продажбата по подразбиране
         */
        if (empty($data->form->rec->currencyId)) {
            $form->setDefault('currencyId', $mvc::getDefaultCurrencyCode($data->form->rec));
        }
        
        /*
         * Банкова сметка по подразбиране
         */
        if (empty($data->form->rec->bankAccountId)) {
            $form->setDefault('bankAccountId', $mvc::getDefaultBankAccountId($data->form->rec));
        }
        
        if (empty($data->form->rec->makeInvoice)) {
            $form->setDefault('makeInvoice', $mvc::getDefaultMakeInvoice($data->form->rec));
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('deliveryLocationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        
        /*
         * Начисляване на ДДС по подразбиране
         */
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ?
                'yes' : 'no'
        );
    }
    

    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=salecond_DeliveryTerms)
     */
    public static function getDefaultDeliveryTermId($rec)
    {
        $deliveryTermId = NULL;
        
        // 1. Условията на последната продажба на същия клиент
        if ($recentRec = self::getRecentSale($rec)) {
            $deliveryTermId = $recentRec->deliveryTermId;
        }
        
        // 2. Условията определени от локацията на клиента (държава, населено място)
        // @see salecond_DeliveryTermsByPlace
        if (empty($deliveryTermId)) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $deliveryTermId = salecond_DeliveryTerms::getDefault($contragent->getContragentData());
        }
        
        return $deliveryTermId;
    }
    

    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = tr("Продажба| №" . $rec->id);
        
         
        return $title;
    }


    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=salecond_DeliveryTerms)
     */
    public static function getDefaultPaymentMethodId($rec)
    {
        $paymentMethodId = NULL;    
        
        // 1. Според последната продажба на същия клиент от тек. потребител
        if ($recentRec = self::getRecentSale($rec, 'user')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }

        // 2. Ако има фиксирана каса - плащането (по подразбиране) е в брой (кеш, COD)
        if (!$paymentMethodId && $rec->caseId) {
            $paymentMethodId = salecond_PaymentMethods::fetchField("#name = 'COD'", 'id');
        }
        
        // 3. Според последната продажба към този клиент
        if (!$paymentMethodId && $recentRec = self::getRecentSale($rec, 'any')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }
        
        // 4. Според данните на клиента
        if (!$paymentMethodId) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $paymentMethodId = salecond_PaymentMethods::getDefault($contragent->getContragentData()); 
        }
        
        return $paymentMethodId;
    }


    /**
     * Определяне на валутата по подразбиране при нова продажба.
     *
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217)
     */
    public static function getDefaultCurrencyCode($rec)
    {
        if ($recentRec = self::getRecentSale($rec)) {
            $currencyBaseCode = $recentRec->currencyId;
        } else {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $currencyBaseCode = currency_Currencies::getDefault($contragent->getContragentData()); 
        }
         
        return $currencyBaseCode;
    }


    /**
     * Определяне на банковата с/ка по подразбиране при нова продажба.
     *
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217)
     */
    public static function getDefaultBankAccountId($rec)
    {
        $bankAccountId = NULL;
        
        if ($recentRec = self::getRecentSale($rec)) {
            $bankAccountId = $recentRec->bankAccountId;
        }
        
        if ($bankAccountId && !empty($rec->currencyId)) {
            // Ако валутата на продажбата не съвпада с валутата на банк. с/ка - игнорираме
            // сметката.
            $baCurrencyId = bank_Accounts::fetchField($bankAccountId, 'currencyId');
            
            if ($baCurrencyId) {
                $baCurrencyId = currency_Currencies::getCodeById($baCurrencyId);
            }
            if ($baCurrencyId && $baCurrencyId != $rec->currencyId) {
                $bankAccountId = NULL;
            }
        }
        
        if (!$bankAccountId) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $bankAccountId = bank_OwnAccounts::getDefault($contragent->getContragentData()); 
        }
         
        return $bankAccountId;
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
        $makeInvoice = NULL;
        
        if ($recentRec = self::getRecentSale($rec)) {
            $makeInvoice = $recentRec->makeInvoice;
        } else {
            $makeInvoice = 'yes';
        }
         
        return $makeInvoice;
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
    public static function getDefaultDealer($rec)
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
     * Най-новата контирана продажба към същия клиент, създадена от текущия потребител, тима му или всеки
     * 
     * @param stdClass $rec запис на модела sales_Sales
     * @param string $scope 'user' | 'team' | 'any'
     * @return stdClass
     */
    protected static function getRecentSale($rec, $scope = NULL)
    {
        if (!isset($scope)) {
            foreach (array('user', 'team', 'any') as $scope) {
                expect(!is_null($scope));
                if ($recentRec = self::getRecentSale($rec, $scope)) {
                    return $recentRec;
                }
            }
            
            return NULL;
        }
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->where("#contragentClassId = '{$rec->contragentClassId}'");
        $query->where("#contragentId = '{$rec->contragentId}'");
        $query->orderBy("createdOn", 'DESC');
        $query->limit(1);
        
        switch ($scope) {
            case 'user':
                $query->where('#createdBy = ' . core_Users::getCurrent('id'));
                break;
            case 'team':
                $teamMates = core_Users::getTeammates(core_Users::getCurrent('id'));
                $teamMates = keylist::toArray($teamMates);
                if (!empty($teamMates)) {
                    $query->where('#createdBy IN (' . implode(', ', $teamMates) . ')');
                }
                break;
        }
        
        $recentRec = $query->fetch();
        
        return $recentRec ? $recentRec : NULL;
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
        
        /*
         * Ако не е въведен валутен курс, използва се курса към датата на документа 
         */
        if (empty($form->rec->currencyRate)) {
            $form->rec->currencyRate = 
                currency_CurrencyRates::getRate($form->rec->valior, $form->rec->currencyId, NULL);
        }
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

        if ($rec->chargeVat == 'no') {
            $row->chargeVat = '';
        }
    }
    
    
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (!count($data->recs)) {
            return;
        }
        
        // Основната валута към момента
        $now            = dt::now();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($now);
        
        // Всички общи суми на продажба - в базова валута към съотв. дата
        foreach ($data->recs as &$rec) {
            $rate = currency_CurrencyRates::getRate($now, $rec->currencyId, $baseCurrencyId);
            
            $rec->amountDeal *= $rate; 
            $rec->amountDelivered *= $rate; 
            $rec->amountPaid *= $rate; 
            $rec->currencyId = NULL; // За да не се показва валутата като префикс в списъка
        }
    }
    
    
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('currencyId,initiatorId,contragentId', TRUE)
        );
        
        $data->listFields['dealerId'] = 'Търговец';
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                
                // "Изчисляване" на името на клиента
                $contragentData = NULL;
                
                if ($rec->contragentClassId && $rec->contragentId) {
    
                    $contragent = new core_ObjectReference(
                        $rec->contragentClassId, 
                        $rec->contragentId 
                    );
                    
                    $row->contragentClassId = $contragent->getHyperlink();
                }
    
                // Търговец (чрез инициатор)
                if (!empty($rec->initiatorId)) {
                    $row->dealerId .= '<small style="display: block;"><span class="quiet">чрез</span> ' . $row->initiatorId;
                }
            }
        }
            
    }

    
    /**
     * Филтър на продажбите
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        $data->listFilter = cls::get('core_Form', array('method'=>'get'));
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('filterDealerId', 'users', 'placeholder=Търговец, caption=Търговец', array('attr'=>array('onchange'=>'submit();')));
        $data->listFilter->FNC('fromDate', 'date', 'placeholder=От,caption=От,width=100px');
        $data->listFilter->FNC('toDate', 'date', 'placeholder=До,caption=До,width=100px');
    
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,clsss=btn-filter');
    
        // Показваме тези полета. Иначе и другите полета на модела ще се появят
        $data->listFilter->showFields = 'filterDealerId, fromDate, toDate';
        
        $data->listFilter->setDefault('filterDealerId', keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE)));
    
        $filter = $data->listFilter->input();
        
        /* @var $query core_Query */
        $query = $data->query;
        
        /*
         * Филтър по дилър / тийм
         */
        if ($filter->filterDealerId) {
            $query->where(
                sprintf(
                    '#dealerId IN (%s)', 
                    implode(',', keylist::toArray($filter->filterDealerId))
                )
            );
        }
        
        /*
         * Филтър по дати
         */
        $dateRange = array();
        
        if (!empty($filter->fromDate)) {
            $dateRange[0] = $filter->fromDate; 
        }
        if (!empty($filter->toDate)) {
            $dateRange[1] = $filter->toDate; 
        }
        
        if (count($dateRange) == 2) {
            sort($dateRange);
        }
        
        if (!empty($dateRange[0])) {
            $query->where(array("#valior >= '[#1#]'", $dateRange[0]));
        }
        if (!empty($dateRange[1])) {
            $query->where(array("#valior <= '[#1#]'", $dateRange[1]));
        }
    }
    
    
    public static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Използваме заглавието на списъка за заглавие на филтър-формата
        $data->listFilter->title = $data->title;
        $data->title = NULL;
    }
    
    
    public static function on_AfterRenderListSummary($mvc, $tpl, $data)
    {
        /*
         * Подготвяне на тоталите - използваме същата заявка, с която сме извлекли списъка.
         */
        
        /* @var $query core_Query */
        $query = clone $data->query;
        
        $query->limit = $query->start = NULL;
        $query->orderBy = array();
        $query->executed = FALSE;
        $query->show = arr::make('amountDeal,amountDelivered,amountPaid,currencyId,valior', TRUE);
        
        $now = dt::now();
        $total = (object)array(
            'currencyId' => acc_Periods::getBaseCurrencyCode($now),
            'countDeal' => 0,
            'amountDeal' => 0.0,
            'amountDelivered' => 0.0,
            'amountPaid' => 0.0,
        );
        
        // Кеш за вече извличаните валутни курсове
        // ключ - код на валута; стойност - курс на тази валута към основната за днес
        $ratesCache = array();
        
        while ($rec = $query->fetch()) {
            $total->countDeal       += 1;
            if (!isset($ratesCache[$rec->currencyId])) {
                $ratesCache[$rec->currencyId] = 
                    currency_CurrencyRates::getRate($now, $rec->currencyId, $total->currencyId);
                expect($ratesCache[$rec->currencyId], 
                    sprintf('Липсва курс на %s към %s за %s', $rec->currencyId, $total->currencyId, $now)
                );
            }
            $total->amountDeal      += (float)$rec->amountDeal * $ratesCache[$rec->currencyId];
            $total->amountDelivered += (float)$rec->amountDelivered * $ratesCache[$rec->currencyId];
            $total->amountPaid      += (float)$rec->amountPaid * $ratesCache[$rec->currencyId];
        }
        
        /*
         * Рендиране на съмърито 
         */
        
        // Форматиране на сумите
        foreach (array('amountDeal', 'amountDelivered', 'amountPaid') as $amountField) {
            $total->{$amountField} = sprintf("%0.02f", round($total->{$amountField}, 2));
        }
        
        $tpl = new core_ET('
            <div style="float: right; background: #eee; padding: 10px;">
                <table>
                    <tr>
                        <td class="quiet">Продажби</td>
                        <td align="right">[#countDeal#]</td>
                    </tr>
                    <tr>
                        <td class="quiet">Поръчано</td>
                        <td align="right">[#amountDeal#] [#currencyId#]</td>
                    </tr>
                    <tr>
                        <td class="quiet">Доставено</td>
                        <td align="right">[#amountDelivered#] [#currencyId#]</td>
                    </tr>
                    <tr>
                        <td class="quiet">Платено</td>
                        <td align="right">[#amountPaid#] [#currencyId#]</td>
                    </tr>
                </table>
            </div>
        ');
        
        $tpl->placeObject($total);
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
     * 
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        
        $row = (object)array(
            'title'    => "Продажба №{$rec->id} / " . $this->getVerbal($rec, 'valior'),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $this->getRecTitle($rec),
        );
        
        return $row;
    }
    
    
    /*
     * РЕАЛИЗАЦИЯ НА store_ShipmentIntf
     */
    
    
    /**
     * Данни за експедиция, записани в документа продажба
     * 
     * @param int $id key(mvc=sales_Sales)
     * @return object
     */
    public function getShipmentInfo($id)
    {
        $rec = $this->fetch($id);
        
        return (object)array(
             'contragentClassId' => $rec->contragentClassId,
             'contragentId' => $rec->contragentId,
             'termId' => $rec->deliveryTermId,
             'locationId' => $rec->deliveryLocationId,
             'deliveryTime' => $rec->deliveryTime,
             'storeId' => $rec->shipmentStoreId,
        );
    }
    
    
    /**
     * Детайли (продукти), записани в документа продажба
     * 
     * @param int $id key(mvc=sales_Sales)
     * @return array
     */
    public function getShipmentProducts($id)
    {
        $products = array();
        $saleRec  = $this->fetchRec($id);
        $query    = sales_SalesDetails::getQuery();
        
        $query->where("#saleId = {$saleRec->id}");
        
        while ($rec = $query->fetch()) {
            if ($saleRec->chargeVat == 'yes') {
                // Начисляваме ДДС
                $Policy = cls::get($rec->policyId);
                $ProductManager = $Policy->getProductMan();
                $rec->price *= 1 + $ProductManager->getVat($rec->productId, $saleRec->valior);
            } 
            $products[] = (object)array(
                'policyId'  => $rec->policyId,
                'productId'  => $rec->productId,
                'uomId'  => $rec->uomId,
                'packagingId'  => $rec->packagingId,
                'quantity'  => $rec->quantity,
                'quantityDelivered'  => $rec->quantityDelivered,
                'quantityInPack'  => $rec->quantityInPack,
                'price'  => $rec->price,
                'discount'  => $rec->discount,
            );
        }
        
        return $products;
    }
    
    
    public static function roundPrice($price)
    {
        $precision = 2 + 
            ($price <= 10) +
            ($price <= 1) +
            ($price <= 0.1);
        
        $price = round($price, $precision);
        
        return $price;
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
        // Набавяме списък на (референции към) документите, породени от $saleRef
        $descendants = $saleRef->getDescendants();
        $saleRec     = $saleRef->rec();
        $shipped     = array();
        
        // Преизчисляваме общо платената и общо експедираната сума 
        $saleRec->amountPaid      = 0;
        $saleRec->amountDelivered = 0;
        
        // Базовата валута към датата на продажбата
        $saleBaseCurrencyCode = acc_Periods::getBaseCurrencyCode($saleRec->valior);
        
        foreach ($descendants as $d) {
            $dState = $d->rec('state'); 
            if ($dState == 'draft' || $dState == 'rejected') {
                // Игнорираме черновите и оттеглените документи
                continue;
            }
            
            if ($d->haveInterface('store_ShipmentIntf')) {
                $dProducts = $d->getShipmentProducts();
                foreach ($dProducts as $p) {
                    $shipped[$p->packagingId][$p->productId]['quantity'] += $p->quantity;
                    $shipped[$p->packagingId][$p->productId]['price']     = $p->price;
                }
            } elseif ($d->haveInterface('sales_PaymentIntf')) {
                $pi = $d->getPaymentInfo();
                
                // Конвертираме платената сума към валутата на продажбата по курс към датата на
                // платежния документ
                $pi->amount = 
                    currency_CurrencyRates::convertAmount(
                        $pi->amount,         // платена сума 
                        $pi->valior,         // дата на плащане
                        $pi->currencyCode,   // валута, в която е платената сумата
                        $saleRec->currencyId // валута на продажбата 
                    );
                
                // Натрупваме в акумулатора за общо платени суми (във валутата на продажбата)
                $saleRec->amountPaid += $pi->amount;
            }
        }    
            
        $query    = sales_SalesDetails::getQuery();
        $saleId   = $saleRef->id();
        
        $saleDetailRecs = $query->fetchAll("#saleId = {$saleId}");
        
        foreach ($saleDetailRecs as $dRec) {
            $_s = $shipped[$dRec->packagingId][$dRec->productId];
            
            $R = (object)array(
                'id' => $dRec->id,
                'quantityDelivered' => $_s['quantity'], 
            );
            sales_SalesDetails::save($R);
            
            $saleRec->amountDelivered += $_s['quantity'] * $_s['price'];
        }
        
        // Записваме общо платената сума в основната валута към момента на продажбата
        $saleRec->amountPaid *= $saleRec->currencyRate;
        
        self::save($saleRec);
    }
}
