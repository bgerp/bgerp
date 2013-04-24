<?php
/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
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
    var $abbr = 'exp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_RegisterIntf=sales_RegisterImpl,
                          acc_TransactionSourceIntf=store_shipmentorders_Transaction';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, bgerp_plg_Blank,
                    doc_plg_BusinessDoc, acc_plg_Registry';
    
    

    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'admin,store';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'admin,store';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'admin,store';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin,store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin,store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canConto = 'admin,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
     public $listFields = 'id, valior, contragentClassId, contragentId, amountDelivered,
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
    public $details = 'store_ShipmentOrderDetails' ;
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Експедиционно нареждане';
    
    
    /**
     * 
     */
   var $singleLayoutFile = 'store/tpl/SingleLayoutShipmentOrder.shtml';

   
    /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.2|Търговия";
   
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)',
            'caption=От склад, mandatory'); // наш склад, от където се експедира стоката
        
        /*
         * Стойности
         */
        $this->FLD('amountDelivered', 'float(decimals=2)', 'caption=Доставено,input=none'); // Сумата на доставената стока
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('termId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 'caption=Условие');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Обект до,silent'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до'); // до кога трябва да бъде доставено
        
        /*
         * Допълнително
         */
        $this->FLD('note', 'richtext', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }
    
    public static function on_AfterCreate($mvc, $rec)
    {
        $origin = static::getOrigin($rec, 'store_ShipmentIntf');
        
        if ($origin) {
            $products = $origin->getShipmentProducts();
            
            foreach ($products as $p) {
                $p->shipmentId = $rec->id;
                store_ShipmentOrderDetails::save($p);
            }
        }
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
            case 'conto':
                if (empty($rec->id) || $rec->state != 'draft') {
                    // Незаписаните ЕН не могат да се контират
                    $requiredRoles = 'no_one';
                } elseif (($transaction = $mvc->getValidatedTransaction($rec)) === FALSE) {
                    // Невъзможно е да се генерира транзакция
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
        $rec  = &$form->rec;
        
        $form->setDefault('valior', dt::mysql2verbal(dt::now(FALSE))); 
        
        // Определняне на стойности по подразбиране на базата на пораждащия документ (ако има)
        $rec = $mvc->getDefaultsByOrigin($rec);
        
        if (empty($rec->folderId)) {
            expect($rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        /*
         * Определяне на контрагента (ако още не е определен)
         */
        if (empty($rec->contragentClassId)) {
            $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        }
        if (empty($rec->contragentId)) {
            $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        /*
         * Условия за доставка по подразбиране - трябва да е след определянето на контрагента, 
         * тъй-като определянето зависи от него
         */
        if (empty($rec->termId)) {
            $rec->termId = $mvc::getDefaultDeliveryTermId($rec);
        }
        
        if (empty($rec->storeId)) {
            $rec->storeId = store_Stores::getCurrent('id', FALSE);
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('locationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        if (empty($rec->id)) {
            // Ако създаваме нов запис и стойностите по подразбиране са достатъчни за валидиране
            // на формата, не показваме форма изобщо, а направо създаваме записа с изчислените
            // ст-сти по подразбиране. За потребителя си остава възможността да промени каквото
            // е нужно в последствие.
            
            $form->validate(NULL, FALSE, (array)$form->rec);
            
            if (!$form->gotErrors()) {
                if (self::save($form->rec)) {
                    redirect(array($mvc, 'single', $form->rec->id));
                }
            } else {
                $form->errors = array();
            }
        }
    }
    
    
    /**
     * Данни за ЕН по подразбиране на базата на документа-източик (origin)
     * 
     * Ако има origin, използва метода store_ShipmentIntf::getShipmentInfo() за да определи
     * данните на ЕН по подразбиране.
     * 
     * @param stdClass $rec
     * @return stdClass
     */
    public function getDefaultsByOrigin($rec)
    {
        expect($origin = static::getOrigin($rec));
        
        if ($origin->rec('state') != 'active') {
            redirect(array('sales_Sales', 'single', $origin->rec('id')), FALSE, "Продажбата не е активна");
        }
        
        if ($origin->haveInterface('store_ShipmentIntf')) {
            $defaults = $origin->getShipmentInfo();
            $rec      = (array)$rec + (array)$defaults;
        }
        
        return (object)$rec;
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
        if ($recentRec = self::getRecentShipment($rec)) {
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
        $title = tr("Експедиционно нареждане |№" . $rec->id);
        
         
        return $title;
    }
    
    
    /**
     * Най-новата контирана продажба към същия клиент, създадена от текущия потребител, тима му или всеки
     * 
     * @param stdClass $rec запис на модела sales_Sales
     * @param string $scope 'user' | 'team' | 'any'
     * @return stdClass
     */
    protected static function getRecentShipment($rec, $scope = NULL)
    {
        if (!isset($scope)) {
            foreach (array('user', 'team', 'any') as $scope) {
                expect(!is_null($scope));
                if ($recentRec = self::getRecentShipment($rec, $scope)) {
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
                $teamMates = type_Keylist::toArray($teamMates);
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
            
            $rec->amountDelivered *= $rate; 
        }
    }
    
    
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('contragentId', TRUE)
        );
        
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
        $data->listFilter->FNC('fromDate', 'date', 'placeholder=От,caption=От,width=100px');
        $data->listFilter->FNC('toDate', 'date', 'placeholder=До,caption=До,width=100px');
    
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,clsss=btn-filter');
    
        // Показваме тези полета. Иначе и другите полета на модела ще се появят
        $data->listFilter->showFields = 'fromDate, toDate';
        
        $filter = $data->listFilter->input();
        
        /* @var $query core_Query */
        $query = $data->query;
        
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
        $query->show = arr::make('amountDelivered,valior', TRUE);
        
        $now = dt::now();
        $total = (object)array(
            'amountDelivered' => 0.0,
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
            $total->amountDelivered += (float)$rec->amountDelivered * $ratesCache[$rec->currencyId];
        }
        
        /*
         * Рендиране на съмърито 
         */
        
        // Форматиране на сумите
        foreach (array('amountDelivered') as $amountField) {
            $total->{$amountField} = sprintf("%0.02f", round($total->{$amountField}, 2));
        }
        
        $tpl = new core_ET('
            <div style="float: right; background: #eee; padding: 10px;">
                <table>
                    <tr>
                        <td class="quiet">Нареждания</td>
                        <td align="right">[#countShipments#]</td>
                    </tr>
                    <tr>
                        <td class="quiet">Доставено</td>
                        <td align="right">[#amountDelivered#] [#currencyId#]</td>
                    </tr>
                </table>
            </div>
        ');
        
        $tpl->placeObject($total);
    }
    
    
    /**
     * Връща документа, породил зададения документ
     * 
     * @param int|object $id
     * @param string $intf
     * @return NULL|core_ObjectReference
     */
    public static function getOrigin($id, $intf = NULL)
    {
        $rec = static::fetchRec($id);
        
        if (!$rec->originId) {
            return NULL;
        }
        
        return doc_Containers::getDocument($rec->originId, $intf);
    }


    /**
     * ЕН не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * Допълнително, първия документ на нишка, в която е допустомо да се създаде ЕН трябва да
     * бъде от клас sales_Sales. Това се гарантира от @see store_ShipmentOrders::canAddToThread()
     *
     * @param $folderId int ид на папката
     * @param $coverClass string класът на корицата на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId, $coverClass)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли ЕН да се добави в посочената нишка?
     *
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-продажба
     *
     * @param $folderId int ид на папката
     * @param $firstDocClass string класът първия документ в нишката
     * @return boolean
     */
    public static function canAddToThread($threadId, $firstDocClass)
    {
        if (empty($firstDocClass)) {
            $firstDoc      = doc_Threads::getFirstDocument($threadId);
            $firstDocClass = $firstDoc->className;
        } else {
            $firstDocClass = cls::getClassName($firstDocClass);
        }
        
        return 'sales_Sales' == $firstDocClass;
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
            'title'    => "Експедиционно нареждане №{$rec->id} / " . $this->getVerbal($rec, 'valior'),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $this->getRecTitle($rec)
        );
        
        return $row;
    }
}
