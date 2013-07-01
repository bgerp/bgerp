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
        doc_DocumentPlg, plg_ExportCsv,
        doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank,
        doc_plg_BusinessDoc, acc_plg_Registry, store_plg_Shippable, acc_plg_DocumentSummary';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,purchase';

    
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
     * Групиране на документите
     */
    var $newBtnGroup = "4.2|Логистика";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 
            'caption=Фактуриране,maxRadio=3,columns=3');
        $this->FLD('chargeVat', 'enum(yes=с ДДС,no=без ДДС)', 'caption=ДДС');
        
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
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 
            'caption=Доставка->Условие');
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
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)',
            'caption=Плащане->Начин,mandatory');
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
         * Валута на покупка по подразбиране
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
        
        // Поле за избор на локация - само локациите на контрагента по покупката
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
        
        /*
         * Моментни експедиция и плащане по подразбиране
         */
        if (empty($form->rec->id)) {
            $isInstantShipment = !empty($form->rec->shipmentStoreId);
            $isInstantShipment = $isInstantShipment && 
                ($form->rec->shipmentStoreId == store_Stores::getCurrent('id', FALSE));
            $isInstantShipment = $isInstantShipment && 
                store_Stores::fetchField('chiefId', $form->rec->shipmentStoreId);
            
            $isInstantPayment = !empty($form->rec->caseId);
            $isInstantPayment = $isInstantPayment && 
                ($form->rec->caseId == store_Stores::getCurrent('id', FALSE));
            $isInstantPayment = $isInstantPayment && 
                store_Stores::fetchField('chiefId', $form->rec->shipmentStoreId);
            
            $form->setDefault('isInstantShipment', 
                $isInstantShipment ? 'yes' : 'no');
            $form->setDefault('isInstantPayment', 
                $isInstantPayment ? 'yes' : 'no');
        }
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
    
        // 1. Условията на последната покупка от същия доставчик
        if ($recentRec = self::getRecent($rec)) {
            $deliveryTermId = $recentRec->deliveryTermId;
        }
    
        // 2. Условията определени от локацията на доставчика (държава, населено място)
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
        $title = tr("№" . $rec->id);
    
         
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
    
        // 1. Според последната покупка от същия доставчик от тек. потребител
        if ($recentRec = self::getRecent($rec, 'user')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }
    
        // 2. Ако има фиксирана каса - плащането (по подразбиране) е в брой (кеш, COD)
        if (!$paymentMethodId && $rec->caseId) {
            $paymentMethodId = salecond_PaymentMethods::fetchField("#name = 'COD'", 'id');
        }
    
        // 3. Според последната покупка от този доставчик
        if (!$paymentMethodId && $recentRec = self::getRecent($rec, 'any')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }
    
        // 4. Според данните на доставчика
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
        if ($recentRec = self::getRecent($rec)) {
            $currencyBaseCode = $recentRec->currencyId;
        } else {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $currencyBaseCode = currency_Currencies::getDefault($contragent->getContragentData());
        }
         
        return $currencyBaseCode;
    }
    
    
    /**
     * Определяне на банковата с/ка по подразбиране при нова покупка.
     *
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217)
     */
    public static function getDefaultBankAccountId($rec)
    {
        $bankAccountId = NULL;
    
        if ($recentRec = self::getRecent($rec)) {
            $bankAccountId = $recentRec->bankAccountId;
        }
    
        if ($bankAccountId && !empty($rec->currencyId)) {
            // Ако валутата на покупката не съвпада с валутата на банк. с/ка - игнорираме
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
    
        if ($recentRec = self::getRecent($rec)) {
            $makeInvoice = $recentRec->makeInvoice;
        } else {
            $makeInvoice = 'yes';
        }
         
        return $makeInvoice;
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
    public static function getDefaultDealer($rec)
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
     * Най-новата контирана покупка от същия доставчик, създадена от текущия потребител, тима му или всеки
     *
     * @param stdClass $rec запис на модела sales_Sales
     * @param string $scope 'user' | 'team' | 'any'
     * @return stdClass
     */
    protected static function getRecent($rec, $scope = NULL)
    {
        if (!isset($scope)) {
            foreach (array('user', 'team', 'any') as $scope) {
                expect(!is_null($scope));
                if ($recentRec = self::getRecent($rec, $scope)) {
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
    
    
}