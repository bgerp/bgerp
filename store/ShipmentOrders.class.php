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
    var $abbr = 'shp';
    
    
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
                    doc_plg_BusinessDoc, acc_plg_Registry';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
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
   var $newBtnGroup = "3.1|Експедиционно нареждане";
   
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        
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
        $this->FLD('deliveryTermId', 'key(mvc=trans_DeliveryTerms,select=codeName)', 
            'caption=Условие');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Обект до,silent'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 
            'caption=Срок до'); // до кога трябва да бъде доставено
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 
            'caption=От склад'); // наш склад, от където се експедира стоката
        
        /*
         * Допълнително
         */
        $this->FLD('note', 'richtext', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
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
        
        $form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
        
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
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('deliveryLocationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
    }
    

    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=trans_DeliveryTerms)
     */
    public static function getDefaultDeliveryTermId($rec)
    {
        $deliveryTermId = NULL;
        
        // 1. Условията на последната продажба на същия клиент
        if ($recentRec = self::getRecentSale($rec)) {
            $deliveryTermId = $recentRec->deliveryTermId;
        }
        
        // 2. Условията определени от локацията на клиента (държава, населено място)
        // @see trans_DeliveryTermsByPlace
        if (empty($deliveryTermId)) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $deliveryTermId = trans_DeliveryTerms::getDefault($contragent->getContragentData());
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
