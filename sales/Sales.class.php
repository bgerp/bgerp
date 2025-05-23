<?php


/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Sales extends deals_DealMaster
{
    /**
     * Заглавие
     */
    public $title = 'Договори за продажба';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Sal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf,
                          acc_TransactionSourceIntf=sales_transaction_Sale,
                          bgerp_DealIntf, bgerp_DealAggregatorIntf, deals_DealsAccRegIntf,sales_RatingsSourceIntf, 
                          acc_RegisterIntf,deals_InvoiceSourceIntf,label_SequenceIntf=sales_interface_SaleLabelImpl,colab_CreateDocumentIntf,acc_AllowArticlesCostCorrectionDocsIntf,trans_LogisticDataIntf,hr_IndicatorsSourceIntf,doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, sales_Wrapper, sales_plg_CalcPriceDelta, plg_Sorting, acc_plg_Registry, doc_plg_TplManager, cat_plg_NotifyProductOnDocumentStateChange, doc_DocumentPlg, acc_plg_Contable, plg_Printing,
                    acc_plg_DocumentSummary, cat_plg_AddSearchKeywords, deals_plg_SaveValiorOnActivation,price_plg_TotalDiscount, plg_Search, doc_plg_HidePrices, cond_plg_DefaultValues,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Clone, doc_SharablePlg, doc_plg_Tabs, cat_plg_UsingProductVat, doc_plg_Close,change_Plugin,plg_LastUsedKeys, bgerp_plg_Export';
    
    
    /**
     * При създаване на имейл, дали да се използва първият имейл от списъка
     */
    public $forceFirstEmail = true;
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'reff,dealerId,initiatorId,oneTimeDelivery,courierApi,detailOrderBy,makeInvoice';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';


    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';


    /**
     * Клас на оферта
     */
    protected $quotationClass = 'sales_Quotations';


    /**
     * Кой може да принтира фискална бележка
     */
    public $canPrintfiscreceipt = 'ceo,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales,acc,saleAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,sales,acc';
    
    
    /**
     * Кои външни(external) роли могат да създават/редактират документа в споделена папка
     */
    public $canWriteExternal = 'distributor';
    
    
    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,sales,acc';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'sales,ceo,distributor';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, currencyId=Вал., amountDeal, amountDelivered, amountPaid, amountInvoiced,amountInvoicedDownpayment,amountInvoicedDownpaymentToDeduct,
                             paymentState,dealerId=Търговец,
                             createdOn, createdBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'amountInvoicedDownpayment,amountInvoicedDownpaymentToDeduct,dealerId';
	
	
    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_SalesDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продажба';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.1|Търговия';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountBl,expectedTransportCost,visibleTransportCost,amountInvoicedDownpaymentToDeduct,amountInvoicedDownpayment,hiddenTransportCost,leftTransportCost,amountDelivered,amountPaid,amountInvoiced,amountToPay,amountToDeliver,amountToInvoice';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutSale.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cart_go.png';
    
    
    /**
     * Икона за единичния изглед на обединяващите договори
     */
    public $singleIconFocCombinedDeals = 'img/16/shopping_carts.png';
    
    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Кой може да превалутира документите в нишката
     */
    public $canChangerate = 'debug';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'sales,ceo';
    
    
    /**
     * Кой има право да експортва?
     */
    public $canExport = 'ceo,invoicerSale,invoicerPurchase,invoicerFindeal';
    
    
    /**
     * Кои полета да могат да се експортират в CSV формат
     *
     * @see bgerp_plg_CsvExport
     */
    public $exportableCsvFields = 'valior,id,folderId,currencyId,paymentMethodId,amountDeal,amountDelivered,amountPaid,amountInvoiced,invoices=Фактури,state';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'deliveryTermId' => 'clientCondition|lastDocUser|lastDoc',
        'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId' => 'lastDocUser|lastDoc|CoverMethod',
        'makeInvoice' => 'lastDocUser|lastDoc',
        'deliveryLocationId' => 'lastDocUser|lastDoc',
        'template' => 'lastDocUser|lastDoc|defMethod',
        'oneTimeDelivery' => 'clientCondition'
    );
    
    
    /**
     * В коя група по дефолт да влизат контрагентите, към които е направен документа
     */
    public $crmDefGroup = 'customers';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
        'customer2caseAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '501', 'credit' => '412'),
        'customer2bankAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '503', 'credit' => '412'),
        'customer2case' => array('title' => 'Плащане от Клиент', 'debit' => '501', 'credit' => '411'),
        'customer2bank' => array('title' => 'Плащане от Клиент', 'debit' => '503', 'credit' => '411'),
        'case2customerRet' => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '501', 'reverse' => true),
        'bank2customerRet' => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '503', 'reverse' => true),
        'case2customer' => array('title' => 'Прихващане на плащане', 'debit' => '411', 'credit' => '501', 'reverse' => true),
        'bank2customer' => array('title' => 'Прихващане на плащане', 'debit' => '411', 'credit' => '503', 'reverse' => true),
        'caseAdvance2customer' => array('title' => 'Прихванат аванс на Клиент', 'debit' => '412', 'credit' => '501', 'reverse' => true),
        'bankAdvance2customer' => array('title' => 'Прихванат аванс на Клиент', 'debit' => '412', 'credit' => '503', 'reverse' => true),
        'caseAdvance2customerRet' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '501', 'reverse' => true),
        'bankAdvance2customerRet' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '503', 'reverse' => true),
        'debitDeals' => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '411'),
        'creditDeals' => array('title' => 'Прихващане на задължение', 'debit' => '411', 'credit' => '*', 'reverse' => true),
    );
    
    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('delivery' => array('title' => 'Експедиране на стока', 'debit' => '411', 'credit' => 'store'),
        'deliveryService' => array('title' => 'Доставка на услуги', 'debit' => '411', 'credit' => 'service'),
        'buyServices' => array('title' => 'Връщане на услуги', 'debit' => 'service', 'credit' => '411', 'reverse' => true),
        'stowage' => array('title' => 'Връщане на стока', 'debit' => 'store', 'credit' => '411', 'reverse' => true),
    );
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'deliveryTermId, deliveryLocationId, shipmentStoreId, paymentMethodId, currencyId, bankAccountId, caseId, initiatorId, dealerId, folderId, reff, note';
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'sales_ClosedDeals';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'sales_SalesDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clzone
     */
    public $cloneDetails = 'sales_SalesDetails';
    
    
    /**
     * Кеш на уникален индекс
     */
    protected $unique = 0;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior, activatedOn, deliveryTime,modifiedOn';
    
    
    /**
     * Кои които трябва да имат потребителите да се изберат като дилъри
     */
    public $dealerRolesList = 'sales,ceo';
    
    
    /**
     * Кои роли може да променят активна продажбата
     */
    public $canChangerec = 'ceo,sales';


    /**
     * Възможност за експортиране на детайлите в csv експорта от лист изгледа
     */
    public $allowDetailCsvExportFromList = true;


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'expectedTransportCost,valior,contoActions,amountDelivered,amountBl,amountPaid,amountInvoiced,amountInvoicedDownpayment,amountInvoicedDownpaymentToDeduct,sharedViews,closedDocuments,paymentState,deliveryTime,currencyRate,currencyManualRate,contragentClassId,contragentId,state,deliveryTermTime,closedOn,visiblePricesByAllInThread,closeWith,additionalConditions,voucherId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDealFields($this);
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban,allowEmpty,maxRadio=1)', 'caption=Плащане->Банкова с-ка,after=currencyManualRate,notChangeableByContractor');
        $this->FLD('expectedTransportCost', 'double', 'input=none,caption=Очакван транспорт');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Артикули->Цени,before=detailOrderBy,notChangeableByContractor');
        $this->FLD('deliveryCalcTransport', 'enum(yes=Скрит транспорт,no=Явен транспорт)', 'input=hidden,caption=Доставка->Начисляване,after=deliveryTermId,silent');
        $this->FLD('courierApi', 'class(interface=cond_CourierApiIntf,allowEmpty,select=title)', 'input=hidden,caption=Доставка->Куриерско Api,after=deliveryCalcTransport,notChangeableIfHidden,placeholder=Автоматично');
        $this->FLD('visiblePricesByAllInThread', 'enum(no=Видими от потребители с права,yes=Видими от всички)', 'input=none');
        $this->setField('shipmentStoreId', 'salecondSysId=defaultStoreSale');
        $this->setField('deliveryTermId', 'salecondSysId=deliveryTermSale');
        $this->setField('paymentMethodId', 'salecondSysId=paymentMethodSale,silent,removeAndRefreshForm=caseId|paymentType');
        $this->setField('chargeVat', 'salecondSysId=saleChargeVat');
        $this->setField('oneTimeDelivery', 'salecondSysId=salesOneTimeDelivery');

        if (core_Packs::isInstalled('voucher')) {
            $this->FLD('voucherId', 'key(mvc=voucher_Cards,select=id,allowEmpty)', 'caption=Ваучер,input=none');
            $this->setDbIndex('voucherId');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if (empty($rec->id)) {
            $dealerId = self::getDefaultDealerId($rec->folderId, $rec->deliveryLocationId);
            $form->setDefault('dealerId', $dealerId);
        }
        
        if($form->isSubmitted()){
            
            // Ако има избрана каса
            if(isset($rec->caseId)){
                if($cu = core_Users::getCurrent()){
                    
                    // Потребителя не може да контира в нея, но може да контира в друга/и каси да му се показва предупреждение
                    if(!bgerp_plg_FLB::canUse('cash_Cases', $rec->caseId, $cu)){
                        $caseQuery = cash_Cases::getQuery();
                        bgerp_plg_FLB::addUserFilterToQuery('cash_Cases', $caseQuery, $cu, true);
                        if($caseQuery->count()){
                            $form->setWarning('caseId', 'Избрана е Каса, в която не можете да контирате!');
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Кой е дефолтния търговец по продажбата
     *
     * @param int $folderId   - папка
     * @param int $locationId - локация
     *
     * @return int|NULL $dealerId - ид на търговец
     */
    public static function getDefaultDealerId($folderId, $locationId = null)
    {
        $setDefaultDealerId = sales_Setup::get('SET_DEFAULT_DEALER_ID');
        if($setDefaultDealerId != 'yes') return null;

        if (isset($locationId)) {
            $dealerId = sales_Routes::getSalesmanId($locationId);
            if (isset($dealerId)) return $dealerId;
        }
        
        $dealerId = doc_Folders::fetchField($folderId, 'inCharge');
        if (core_Users::haveRole('sales', $dealerId)) return $dealerId;
        
        $dealerId = cond_plg_DefaultValues::getFromLastDocument(cls::get(get_called_class()), $folderId, 'dealerId', true);
        if (core_Users::haveRole('sales', $dealerId)) return $dealerId;
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        // Ако има б. сметка се нотифицират операторите и
        if ($rec->bankAccountId) {
            $operators = bank_OwnAccounts::fetchField("#bankAccountId = '{$rec->bankAccountId}'", 'operators');
            $rec->sharedUsers = keylist::merge($rec->sharedUsers, $operators);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass    $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        $myCompany = crm_Companies::fetchOwnCompany();
        $options = bank_Accounts::getContragentIbans($myCompany->companyId, 'crm_Companies', true);
        $mvc->invoke('AfterGetOwnAccountOptions', array($form, &$options));

        // Ако няма ръчно избрана БС гледа се последно избраната в папката
        $defaultBankAccountId = $rec->bankAccountId;
        if(empty($rec->bankAccountId)) {
            $lastSelectedBankAccountId = cond_plg_DefaultValues::getDefValueByStrategy($mvc, $rec, 'bankAccountId', 'lastDocUser|lastDoc');
            if(!empty($lastSelectedBankAccountId)){

                // ако все още е активна и може да я избира потребителя - попълва се
                $ownBankSelectedRec = bank_OwnAccounts::fetch("#bankAccountId = {$lastSelectedBankAccountId}");
                if(!in_array($ownBankSelectedRec->state, array('closed', 'rejected')) && $ownBankSelectedRec && bgerp_plg_FLB::canUse('bank_OwnAccounts', $ownBankSelectedRec, null, 'select')){
                    $defaultBankAccountId = $lastSelectedBankAccountId;
                }
            }
        }

        if(!array_key_exists($rec->bankAccountId, $options)){
            if($data->action != 'clone'){
                $options[$rec->bankAccountId] = $rec->bankAccountId;
            } else {
                $query = $mvc->getQuery();
                $query->where("#state != 'rejected'");
                $query->in("bankAccountId", array_keys($options));
                $query->orderBy("id", 'DESC');
                $query->limit(1);
                $defaultBankAccountId = $query->fetch()->bankAccountId;
                if(!empty($rec->bankAccountId) && $rec->bankAccountId != $defaultBankAccountId){
                    $form->setWarning('bankAccountId', "Банковата сметка е сменена, защото оригиналната не може да се използва|*: <b>" . bank_OwnAccounts::getTitleById(bank_OwnAccounts::fetchField("#bankAccountId = {$rec->bankAccountId}")) . "</b>");
                }
            }
        }

        if (countR($options)) {
            foreach ($options as $id => &$name) {
                if (is_numeric($id)) {
                    $name = bank_OwnAccounts::fetchField("#bankAccountId = {$id}", 'title');
                }
            }
        }

        $form->setOptions('bankAccountId', $options);
        $form->setDefault('bankAccountId', $defaultBankAccountId);
        $defaultOptions = $options;
        unset($defaultOptions['']);
        if(countR($defaultOptions) == 1 && $form->cmd != 'refresh') {
            $autoSelectAccount = sales_Setup::get('AUTO_SELECT_BANK_ACCOUNT_IF_ONLY_ONE_IS_AVAILABLE');
            if($autoSelectAccount == 'yes'){
                $form->setDefault('bankAccountId', key($defaultOptions));
            }
        }

        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($rec->folderId));
        
        $hideRate = core_Packs::getConfigValue('sales', 'SALES_USE_RATE_IN_CONTRACTS');
        if ($hideRate == 'yes' && !haveRole('partner')) {
            $form->setField('currencyManualRate', 'input');
        }
        
        if (empty($rec->id)) {
            $form->setField('deliveryLocationId', 'removeAndRefreshForm=dealerId');

            // Ако метода за плащане не е банков само тогава се попълва касата
            if(isset($rec->paymentMethodId)){
                $paymentType = cond_PaymentMethods::fetchField($rec->paymentMethodId, 'type');
                if($paymentType == 'cash'){

                    // Ако има дефолтна каса
                    if($caseId = cond_plg_DefaultValues::getDefValueByStrategy($mvc, $rec, 'caseId', 'sessionValue|lastDocUser|lastDoc')){
                        if(core_Packs::isInstalled('holding')){
                            if(!holding_Companies::isValueAllowed($caseId, $rec->{$mvc->ownCompanyFieldName}, 'cashes')){
                               $caseId = null;
                           }
                        }
                    }

                    $form->setDefault('caseId', $caseId);
                }
            }
        } else {
            
            // Ако има поне един детайл
            if (sales_SalesDetails::fetchField("#saleId = {$rec->id}")) {
                
                // И условието на доставка е със скрито начисляване, не може да се сменя локацията и условието на доставка
                if (isset($rec->deliveryTermId)) {
                    $deliveryCalcCost = null;
                    if (cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)) {
                        $deliveryCalcCost = cond_DeliveryTerms::fetchField($rec->deliveryTermId, 'calcCost');
                        $calcCostDefault = ($rec->deliveryCalcTransport) ? $rec->deliveryCalcTransport : $deliveryCalcCost;
                        $form->setDefault($calcCostDefault, 'deliveryCalcTransport');
                        if(empty($rec->deliveryCalcTransport)){
                            $form->setReadOnly('deliveryCalcTransport', $calcCostDefault);
                        } else {
                            $form->setReadOnly('deliveryCalcTransport');
                        }
                    } else {
                        $form->setReadOnly('deliveryCalcTransport');
                    }

                    if ($deliveryCalcCost == 'yes') {
                        $form->setReadOnly('deliveryAdress');
                        $form->setReadOnly('deliveryLocationId');
                    }
                }
            }
        }
        
        $form->setOptions('priceListId', array('' => '') + price_Lists::getAccessibleOptions($rec->contragentClassId, $rec->contragentId));
        
        // Ако е първата продажба в папката, задава банковата сметка по подразбиране за съответна държава
        if ($rec->folderId) {
            if (!doc_Containers::fetch(array("#docClass = '[#1#]' AND #folderId = '[#2#]'", $mvc->getClassId(), $rec->folderId))) {
                $cData = doc_Folders::getContragentData($rec->folderId);
                if ($cData->countryId) {
                    $defBankId = bank_OwnAccounts::getDefaultIdForCountry($cData->countryId);
                    if ($defBankId) {
                        $form->setDefault('bankAccountId', $defBankId);
                    }
                }
            }
            
            // Дефолтната ценова политика се показва като плейсхолдър
            if ($listId = price_ListToCustomers::getListForCustomer($form->rec->contragentClassId, $form->rec->contragentId)) {
                $form->setField('priceListId', 'placeholder=' . price_Lists::getTitleById($listId));
            }
        }
        
        // Възможност за ръчна смяна на режима на начисляването на скрития транспорт
        if (isset($rec->deliveryTermId)) {
            $form->setField('courierApi', 'input');
            if($courierApi = cond_DeliveryTerms::getCourierApi($rec->deliveryTermId)){
                if(empty($rec->id)){
                    $form->setDefault('courierApi', $courierApi);
                }
            }

            if (cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)) {
                $calcCost = cond_DeliveryTerms::fetchField($rec->deliveryTermId, 'calcCost');
                $form->setField('deliveryCalcTransport', 'input');
                $form->setDefault('deliveryCalcTransport', $calcCost);
            }
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        if (empty($rec->threadId)) {
            $rec->threadId = $mvc->fetchField($rec->id, 'threadId');
        }
        
        if ($rec->state == 'active') {
            $closeArr = array('sales_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            
            if (sales_ClosedDeals::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('Приключване', $closeArr, 'row=2,ef_icon=img/16/closeDeal.png,title=Приключване на продажбата');
            } else {
                $exClosedDeal = sales_ClosedDeals::fetchField("#threadId = {$rec->threadId} AND #state != 'rejected'", 'id');
                
                // Ако разликата е над допустимата но потребителя има права 'sales', той вижда бутона но не може да го използва
                if (!sales_ClosedDeals::isSaleDiffAllowed($rec) && haveRole('sales') && empty($exClosedDeal)) {
                    $data->toolbar->addBtn('Приключване', $closeArr, 'row=2,ef_icon=img/16/closeDeal.png,title=Приключване на продажбата,error=Нямате право да приключите продажба с разлика над допустимото|*!');
                }
            }
            
            // Ако протокол може да се добавя към треда и не се експедира на момента
            if (sales_Services::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $serviceUrl = array('sales_Services', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
                $data->toolbar->addBtn('Пр. услуги', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Продажба на услуги,order=9.22');
            }

            // Ако ЕН може да се добавя към треда и не се експедира на момента
            if (store_ShipmentOrders::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $shipUrl = array('store_ShipmentOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
                $data->toolbar->addBtn('Експедиране', $shipUrl, 'ef_icon = img/16/EN.png,title=Експедиране на артикулите от склада,order=9.21');
            }
            
            if (sales_Proformas::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('Проформа', array('sales_Proformas', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'row=2,ef_icon=img/16/proforma.png,title=Създаване на нова проформа фактура,order=9.9992');
            }
            
            if (deals_Helper::showInvoiceBtn($rec->threadId) && sales_Invoices::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/invoice.png,title=Създаване на нова фактура,order=9.9993');
            }

            $paymentType = $rec->paymentType ?? (isset($rec->paymentMethodId) ? cond_PaymentMethods::fetchField($rec->paymentMethodId, 'type') : null);
            if (cash_Pko::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'originId' => $rec->containerId))) {
                $btnRow = $paymentType == 'cash' || (!isset($paymentType) && ((isset($rec->caseId) && !isset($rec->bankAccountId)) || (isset($rec->caseId, $rec->bankAccountId)) || (!isset($rec->caseId) && !isset($rec->bankAccountId)))) ? 1 : 2;
                $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $rec->containerId, 'ret_url' => true), "ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер,row={$btnRow}");
            }
            
            if (bank_IncomeDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $btnRow = $paymentType == 'bank' || (!isset($paymentType) && ((!isset($rec->caseId) && isset($rec->bankAccountId)) || (isset($rec->caseId, $rec->bankAccountId)) || (!isset($rec->caseId) && !isset($rec->bankAccountId)))) ? 1 : 2;
                $data->toolbar->addBtn('ПБД', array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => true), "ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ,row={$btnRow}");
            }

            if(store_ConsignmentProtocols::canBeAddedFromDocument($rec->containerId)){
                $data->toolbar->addBtn('ПОП', array('store_ConsignmentProtocols', 'add', 'threadId' => $rec->threadId, 'ret_url' => true), "ef_icon=img/16/consignment.png,title=Създаване на нов протокол за отговорно пазене,row=1");
            }
        }
    }
    
    
    /**
     * Подготвя данните за фискалния принтер
     */
    private function prepareFiscPrinterData($rec)
    {
        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$rec->id}");
        
        $data = (object) array('products' => array(), 'payments' => array());
        while ($dRec = $dQuery->fetch()) {
            $nRec = new stdClass();
            $nRec->id = $dRec->productId;
            $nRec->managerId = cat_Products::getClassId();
            $nRec->quantity = $dRec->packQuantity;
            if ($dRec->discount) {
                $nRec->discount = $dRec->discount;
            }
            $pInfo = cat_Products::getProductInfo($dRec->productId);
            $nRec->measure = ($dRec->packagingId) ? cat_UoM::getTitleById($dRec->packagingId) : cat_UoM::getShortName($pInfo->productRec->measureId);
            $nRec->vat = cat_Products::getVat($dRec->productId, $rec->valior, $rec->vatExceptionId);
            if ($rec->chargeVat != 'yes' && $rec->chargeVat != 'separate') {
                $nRec->vat = 0;
            }
            
            $nRec->price = $dRec->packPrice;
            if ($pInfo->productRec->vatGroup) {
                $nRec->vatGroup = $pInfo->productRec->vatGroup;
            }
            
            $nRec->name = $pInfo->productRec->name;
            
            $data->products[] = $nRec;
        }
        
        $nRec = new stdClass();
        $nRec->type = 0;
        $nRec->amount = round($rec->amountPaid, 2);
        
        $data->short = true;
        $data->hasVat = ($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate') ? true : false;
        $data->payments[] = $nRec;
        $data->totalPaid = $nRec->amount;
        
        return $data;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return bgerp_iface_DealAggregator
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$result)
    {
        $rec = $this->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        $detailId = sales_SalesDetails::getClassId();
        
        // Извличаме продуктите на продажбата
        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        $detailRecs = $dQuery->fetchAll();
        
        $downPayment = null;
        if (cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)) {
            // Колко е очакваното авансово плащане
            $downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
        }
        
        // Кои са позволените операции за последващите платежни документи
        $result->set('allowedPaymentOperations', $this->getPaymentOperations($rec));
        $result->set('allowedShipmentOperations', $this->getShipmentOperations($rec));
        $result->set('involvedContragents', array((object) array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId)));
        
        $result->set('amount', $rec->amountDeal);
        $result->setIfNot('reff', $rec->reff);
        $result->setIfNot('currency', $rec->currencyId);
        $result->setIfNot('rate', $rec->currencyRate);
        $result->setIfNot('vatType', $rec->chargeVat);
        $result->setIfNot('agreedValior', $rec->valior);
        $result->setIfNot('deliveryLocation', $rec->deliveryLocationId);
        $deliveryTime = !empty($rec->deliveryTermTime) ? (dt::addSecs($rec->deliveryTermTime, $rec->valior, false) . " " . trans_Setup::get('END_WORK_TIME') . ":00") : $rec->deliveryTime;

        $result->setIfNot('detailOrderBy', $rec->detailOrderBy);
        $result->setIfNot('deliveryTime', $deliveryTime);
        $result->setIfNot('deliveryTerm', $rec->deliveryTermId);
        $result->setIfNot('storeId', $rec->shipmentStoreId);
        $result->setIfNot('paymentMethodId', $rec->paymentMethodId);
        $result->setIfNot('paymentType', $rec->paymentType);
        $result->setIfNot('caseId', $rec->caseId);
        $result->setIfNot('bankAccountId', $rec->bankAccountId);
        $result->setIfNot('priceListId', $rec->priceListId);
        
        sales_transaction_Sale::clearCache();
        $entries = sales_transaction_Sale::getEntries($rec->id);
        $deliveredAmount = sales_transaction_Sale::getDeliveryAmount($entries, $rec->id);
        $paidAmount = sales_transaction_Sale::getPaidAmount($entries, $rec);
        
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', sales_transaction_Sale::getDownpayment($entries));
        $result->set('amountPaid', $paidAmount);
        $result->set('deliveryAmount', $deliveredAmount);
        $result->set('blAmount', sales_transaction_Sale::getBlAmount($entries, $rec->id));
        
        // Опитваме се да намерим очакваното плащане
        $expectedPayment = null;
        
        // Ако доставеното > платено това е разликата
        if ($deliveredAmount > $paidAmount) {
            $expectedPayment = $deliveredAmount - $paidAmount;
        } elseif ($amountFromProforma = sales_Proformas::getExpectedDownpayment($rec)) {
            
            // Ако има авансова фактура след последния платежен документ, това е сумата от аванса и
            $expectedPayment = $amountFromProforma;
        } else {
            
            // В краен случай това е очаквания аванс от метода на плащане
            $expectedPayment = $downPayment;
        }
        
        // Ако има очаквано плащане, записваме го
        if ($expectedPayment) {
            if (empty($deliveredAmount)) {
                $expectedPayment = $expectedPayment - $paidAmount;
            }
            $expectedPayment = round($expectedPayment, 2);
            if ($expectedPayment > 0) {
                $result->set('expectedPayment', $expectedPayment);
            }
        }
        
        // Спрямо очакваното авансово плащане ако има, кои са дефолт платежните операции
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        
        // Дефолтните платежни операции са плащания към доставчик
        $result->set('defaultCaseOperation', 'customer2case');
        $result->set('defaultBankOperation', 'customer2bank');
        
        // Ако се очаква авансово плащане и платения аванс е под 80% от аванса,
        // очакваме още да се плаща по аванса
        if ($agreedDp) {
            if (empty($actualDp) || $actualDp < $agreedDp * 0.8) {
                $result->set('defaultCaseOperation', 'customer2caseAdvance');
                $result->set('defaultBankOperation', 'customer2bankAdvance');
            }
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        $agreed = array();
        $agreed2 = array();

        $showReffInThread = sales_Setup::get('SHOW_REFF_IN_SALE_THREAD');
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            foreach (array('productId', 'packagingId', 'discount', 'quantity', 'quantityInPack', 'price', 'notes', 'tolerance', 'autoDiscount', 'inputDiscount') as $fld) {
                $p->{$fld} = $dRec->{$fld};
            }

            // Записване на вашия реф в забележките само ако е избрано в настройките
            if(Mode::is('isClosedWithDeal')){
                if($showReffInThread == 'yes'){
                    if(!empty($rec->reff)){
                        $p->notes = !empty($p->notes) ? ($p->notes . "\n" . "ref: {$rec->reff}") : "ref: {$rec->reff}";
                    }
                }
            }

            if (core_Packs::isInstalled('batch')) {
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$detailId}");
                $bQuery->where("#detailRecId = {$dRec->id}");
                $bQuery->where("#productId = {$dRec->productId}");
                $p->batches = array();
                while ($bRec = $bQuery->fetch()){
                    $p->batches[$bRec->batch] = $bRec->quantity;
                }
            }
            
            if ($tRec = sales_TransportValues::get(sales_Sales::getClassId(), $rec->id, $dRec->id)) {
                if ($tRec->fee > 0) {
                    $p->fee = $tRec->fee;
                    $p->deliveryTimeFromFee = $tRec->deliveryTime;
                    $p->syncFee = true;
                }
            }
            
            $agreed[] = $p;
            
            $p1 = clone $p;
            unset($p1->notes);
            $agreed2[] = $p1;
            
            $push = true;
            $index = $p->productId;
            $shipped = $result->get('shippedPacks');
            
            $inPack = $p->quantityInPack;
            if ($shipped && isset($shipped[$index])) {
                if ($shipped[$index]->inPack < $inPack) {
                    $push = false;
                }
            }
            
            if ($push) {
                $arr = (object) array('packagingId' => $p->packagingId, 'inPack' => $inPack);
                $result->push('shippedPacks', $arr, $index);
            }
        }

        $result->set('dealProducts', $agreed);
        $agreed = deals_Helper::normalizeProducts(array($agreed2));
        $result->set('products', $agreed);
        $result->set('contoActions', $actions);
        $shippedProducts = sales_transaction_Sale::getShippedProducts($entries);

        // Ако има експедирани артикули и е инсталиран пакета за партиди
        if(core_Packs::isInstalled('batch') && countR($shippedProducts)){
            $threads = deals_Helper::getCombinedThreads($rec->threadId);

            // Извличане на движенията по ЕН и Продажби
            $batchWhere = '';
            $otherSaleQuery = sales_Sales::getQuery();
            $otherSaleQuery->where("#state IN ('active', 'closed')");
            $otherSaleQuery->in("threadId", $threads);
            $saleIds = arr::extractValuesFromArray($otherSaleQuery->fetchAll(), 'id');
            if(countR($saleIds)){
                $saleIds = implode(',', $saleIds);
                $saleClassId = sales_Sales::getClassId();
                $batchWhere = "(#docType={$saleClassId} AND #docId IN ({$saleIds}))";
            }

            $sQuery = store_ShipmentOrders::getQuery();
            $sQuery->in("threadId", $threads);
            $sQuery->where("#state = 'active'");
            $soIds = arr::extractValuesFromArray($sQuery->fetchAll(), 'id');

            if(countR($soIds)){
                $soIds = implode(',', $soIds);
                $soClassId = store_ShipmentOrders::getClassId();
                $or = empty($batchWhere) ? '' : ' OR ';
                $batchWhere .= "{$or}(#docType={$soClassId} AND #docId IN ({$soIds}))";
            }

            if(!empty($batchWhere)){
                $batches = array();
                $bQuery = batch_Movements::getQuery();
                $bQuery->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
                $bQuery->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
                $bQuery->where($batchWhere);
                $bQuery->where("#operation = 'out'");
                $bQuery->show('quantity,batch,productId');
                while ($batchRec = $bQuery->fetch()){
                    if(!array_key_exists($batchRec->productId, $batches)){
                        $batches[$batchRec->productId] = array();
                    }
                    $batches[$batchRec->productId][$batchRec->batch] += $batchRec->quantity;
                }

                // Добавя се информация за експедираните партиди към данните за експедираните артикули
                foreach ($shippedProducts as &$shipped){
                    $shipped->batches = array_key_exists($shipped->productId, $batches) ? $batches[$shipped->productId] : array();
                }
            }
        }

        $result->set('shippedProducts', $shippedProducts);
    }
    
    
    /**
     * Кои са позволените платежни операции за тази сделка
     */
    public function getPaymentOperations($id)
    {
        $rec = $this->fetchRec($id);
        
        $allowedPaymentOperations = $this->allowedPaymentOperations;
        
        if ($rec->paymentMethodId) {
            
            // Ако има метод за плащане и той няма авансова част, махаме авансовите операции
            if (!cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)) {
                if(!haveRole('accMaster,ceo')){
                    unset($allowedPaymentOperations['customer2caseAdvance'],
                         $allowedPaymentOperations['customer2bankAdvance'],
                         $allowedPaymentOperations['caseAdvance2customer'],
                         $allowedPaymentOperations['bankAdvance2customer'],
                         $allowedPaymentOperations['caseAdvance2customerRet'],
                         $allowedPaymentOperations['bankAdvance2customerRet']);
                }
            }
        }
        
        return $allowedPaymentOperations;
    }
    
    
    /**
     * Приключва всички приключени продажби
     */
    public function cron_CloseOldSales()
    {
        $conf = core_Packs::getConfig('sales');
        $olderThan = $conf->SALE_CLOSE_OLDER_THAN;
        $limit = $conf->SALE_CLOSE_OLDER_NUM;
        $ClosedDeals = cls::get('sales_ClosedDeals');
        
        $this->closeOldDeals($olderThan, $ClosedDeals, $limit);
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    protected function setCron(&$res)
    {
        // Крон метод за затваряне на остарели продажби
        $rec = new stdClass();
        $rec->systemId = 'Close sales';
        $rec->description = 'Затваряне на приключените продажби';
        $rec->controller = 'sales_Sales';
        $rec->action = 'CloseOldSales';
        $rec->period = 60;
        $rec->offset = mt_rand(0, 30);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
        
        // Проверка по крон на плащанията на продажбите
        $rec2 = new stdClass();
        $rec2->systemId = 'IsSaleOverdue';
        $rec2->description = 'Проверяване на плащанията по продажбите';
        $rec2->controller = 'sales_Sales';
        $rec2->action = 'CheckSalesPayments';
        $rec2->period = 60;
        $rec2->offset = mt_rand(0, 30);
        $rec2->isRandOffset = true;
        $rec2->delay = 0;
        $rec2->timeLimit = 300;
        $res .= core_Cron::addOnce($rec2);
    }
    
    
    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Договор за продажба',    'content' => 'sales/tpl/sales/Sale.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/sales/SaleNarrow.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Договор за изработка',   'content' => 'sales/tpl/sales/Manufacturing.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/sales/ManufacturingNarrow.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Договор за услуга',      'content' => 'sales/tpl/sales/Service.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/sales/ServiceNarrow.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Sales contract',         'content' => 'sales/tpl/sales/SaleEN.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/sales/SaleNarrowEN.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Manufacturing contract', 'content' => 'sales/tpl/sales/ManufacturingEN.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/sales/ManufacturingNarrowEN.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Service contract',       'content' => 'sales/tpl/sales/ServiceEN.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/sales/ServiceNarrowEN.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Договор за транспорт',   'content' => 'sales/tpl/sales/Transport.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/sales/TransportNarrow.shtml', 'toggleFields' => array('masterFld' => null, 'sales_SalesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Проверява дали продажбата е просрочена или платени
     */
    public function cron_CheckSalesPayments()
    {
        core_App::setTimeLimit(300);
        $overdueDelay = sales_Setup::get('OVERDUE_CHECK_DELAY');
        core_Debug::$isLogging = false;
        $this->checkPayments($overdueDelay);

        // Изпращане на нотификации, за нефактурирани продажби
        $lateTime = sales_Setup::get('NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS');
        if(!empty($lateTime)){
            $this->sendNotificationIfInvoiceIsTooLate($lateTime);
        }
        core_Debug::$isLogging = true;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'printfiscreceipt' && isset($rec)) {
            $actions = type_Set::toArray($rec->contoActions);
            
            if ($actions['ship'] && $actions['pay']) {
            } else {
                $res = 'no_one';
            }
        }
        
        if ($action == 'closewith' && isset($rec)) {
            if ($rec->state != 'active' && (sales_SalesDetails::fetch("#saleId = {$rec->id}") || price_DiscountsPerDocuments::count("#documentClassId = {$mvc->getClassId()} AND #documentId = {$rec->id}"))) {
                $res = 'no_one';
            } elseif (!haveRole('sales,ceo', $userId)) {
                $res = 'no_one';
            }
        }
        
        // Проверка на екшъна за създаване на артикул към продажба
        if ($action == 'createsaleforproduct') {
            $res = $mvc->getRequiredRoles('add', $rec, $userId);
            if (core_Users::isContractor($userId)) {
                $res = 'no_one';
            }
            
            if (isset($rec) && $res != 'no_one') {
                if (empty($rec->productId) || empty($rec->folderId)) {
                    $res = 'no_one';
                } else {
                    $pRec = cat_Products::fetch($rec->productId, 'state,canSell');
                    if ($pRec->state != 'active' || $pRec->canSell != 'yes') {
                        $res = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        $rec = $data->rec;
        
        // Изкарваме езика на шаблона от сесията за да се рендира статистиката с езика на интерфейса
        core_Lg::pop();
        $statisticTpl = getTplFromFile('sales/tpl/SaleStatisticLayout.shtml');
        $tpl->replace($statisticTpl, 'STATISTIC_BAR');
        
        // Отново вкарваме езика на шаблона в сесията
        core_Lg::push($rec->tplLang);
        
        // Скриване на секцията с транспорт, при определени условия
        if (Mode::isReadOnly() || core_Users::haveRole('partner') || empty($rec->deliveryTermId) || (!empty($rec->deliveryTermId) && !cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId))) {
            $tpl->removeBlock('TRANSPORT_BAR');
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Слагаме iframe заради касовата бележка, ако не принтираме
        if (!Mode::is('printing') && !Mode::is('text', 'xhtml')) {
            $tpl->append("<iframe name='iframe_a' style='display:none'></iframe>");
            
            if (is_array($data->jobs) === true) {
                $mvc->renderJobsInfo($tpl, $data);
            }
        }
    }
    
    
    /**
     * Показва информация за перото по Айакс
     */
    public function act_ShowInfo()
    {
        $id = Request::get('id', 'varchar');
        $unique = Request::get('unique', 'int');
        
        $tpl = new ET('[#link#]');
        $row = new stdClass();
        
        if (substr(strstr($id, 'job='), 1)) {
            $jobId = substr(strstr($id, '='), 1);
            $rec = planning_Jobs::fetchRec($jobId);
            $row = planning_Jobs::recToVerbal($rec);
            $row->link = planning_Jobs::getLink($rec->id, 0);
            
            $tpl->placeObject($row);
        } else {
            $saleId = substr(strstr($id, '='), 1);
            $rec = $this->fetchRec($saleId);
            $row = $this->recToVerbal($rec);
            $row->link = self::getLink($rec->id, 0);
            $tpl->placeObject($row);
        }
        
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => "info{$unique}", 'html' => $tpl->getContent(), 'replace' => true);
            
            return array($resObj);
        }
        
        return $tpl;
    }
    
    
    /**
     *  Намира последната продажна цена на артикулите
     */
    public static function getLastProductPrices($contragentClass, $contragentId)
    {
        $Contragent = cls::get($contragentClass);
        $ids = array();
        
        // Намираме ид-та на всички продажби, ЕН и протоколи за този контрагент
        foreach (array('sales_Sales', 'store_ShipmentOrders', 'sales_Services') as $Cls) {
            $query = $Cls::getQuery();
            $query->where("#contragentClassId = {$Contragent->getClassId()} AND #contragentId = {$contragentId}");
            $query->where("#state = 'active' OR #state = 'closed'");
            $query->show('id');
            $query->orderBy('valior', 'DESC');
            while ($rec = $query->fetch()) {
                $ids[] = $rec->id;
            }
            $key = md5(implode('', $ids));
        }
        
        if (!countR($ids)) {
            return array();
        }
        
        $cacheArr = core_Cache::get('sales_Sales', $key);
        
        // Имаме ли кеширани данни
        if (!$cacheArr) {
            
            // Ако няма инвалидираме досегашните кешове за продажбите
            core_Cache::removeByType('sales_Sales');
            $cacheArr = array();
            
            // Проверяваме на какви цени сме продавали в детайлите на продажбите, ЕН и протоколите
            foreach (array('sales_SalesDetails', 'store_ShipmentOrderDetails', 'sales_ServicesDetails') as $Detail) {
                $Detail = cls::get($Detail);
                $dQuery = $Detail->getQuery();
                $dQuery->where("#state = 'active' OR #state = 'closed'");
                $dQuery->show("productId,price,{$Detail->masterKey}");
                
                $dQuery->EXT('state', $Detail->Master->className, "externalName=state,externalKey={$Detail->masterKey}");
                $dQuery->EXT('contragentClassId', $Detail->Master->className, "externalName=contragentClassId,externalKey={$Detail->masterKey}");
                $dQuery->EXT('contragentId', $Detail->Master->className, "externalName=contragentId,externalKey={$Detail->masterKey}");
                $dQuery->where("#contragentClassId = {$Contragent->getClassId()} AND #contragentId = {$contragentId}");
                
                // Кешираме артикулите с цените
                while ($dRec = $dQuery->fetch()) {
                    $cacheArr[$dRec->productId] = $dRec->price;
                }
            }
            
            // Кешираме новите данни
            core_Cache::set('sales_Sales', $key, $cacheArr, 1440);
        }
        
        return $cacheArr;
    }
    
    
    /**
     * Метод по подразбиране за намиране на дефолт шаблона
     */
    public function getDefaultTemplate_($rec)
    {
        $cData = doc_Folders::getContragentData($rec->folderId);
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        
        $conf = core_Packs::getConfig('sales');
        $def = (empty($cData->countryId) || $bgId === $cData->countryId) ? $conf->SALE_SALE_DEF_TPL_BG : $conf->SALE_SALE_DEF_TPL_EN;
        
        return $def;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (haveRole('ceo,planning,sales,store,job')) {
            $dealTab = Request::get('dealTab');
            if (empty($dealTab) || $dealTab == 'Statistic') {
                $mvc->prepareJobsInfo($data);
            }
        }
    }
    
    
    /**
     * Подготвяме информацията за наличните задания към артикули от сделката
     *
     * @param stdClass $data
     *
     * @return void
     */
    protected function prepareJobsInfo($data)
    {
        $rec = $data->rec;
        $manifacturableProducts = static::getManifacturableProducts($data->rec);
        if (!countR($manifacturableProducts)) {
            return;
        }
        
        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('productId', array_keys($manifacturableProducts));
        $jQuery->where("#saleId = {$rec->id}");
        $jQuery->XPR('order', 'int', "(CASE #state WHEN 'draft' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'wakeup' THEN 4 WHEN 'closed' THEN 5 ELSE 3 END)");
        $jQuery->orderBy('order', 'ASC');
        
        $fields = cls::get('planning_Jobs')->selectFields();
        $fields['-list'] = true;
        
        $data->jobs = array();
        while ($jRec = $jQuery->fetch()) {
            $data->jobs[$jRec->id] = planning_Jobs::recToVerbal($jRec, $fields);
        }

        if (planning_Jobs::haveRightFor('add', (object) array('saleId' => $rec->id))) {
            $data->addJobUrl = array('planning_Jobs', 'add', 'saleId' => $rec->id, 'foreignId' => $rec->containerId, 'ret_url' => true);
            if(doc_Threads::haveRightFor('single', $rec->threadId)){
                $data->addJobUrl['threadId'] = $rec->threadId;
            }
        }
    }
    
    
    /**
     * Рендиране на информацията на заданията
     *
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    protected function renderJobsInfo(&$tpl, $data)
    {
        $table = cls::get('core_TableView', array('mvc' => cls::get('planning_Jobs')));
        
        $jobsTable = $table->get($data->jobs, 'title=Задание,dueDate=Падеж,packQuantity=Планирано,quantityFromTasks=Произведено,quantityProduced=Заскладено,packagingId=Мярка');
        $jobTpl = new core_ET("<div style='margin-top:6px'>[#table#]</div>");
        $jobTpl->replace($jobsTable, 'table');
        $tpl->replace($jobTpl, 'JOB_INFO');
        
        if (isset($data->addJobUrl)) {
            $addLink = ht::createLink('', $data->addJobUrl, false, 'ef_icon=img/16/add.png,title=Създаване на ново задание за производство от продажбата');
            $tpl->replace($addLink, 'JOB_ADD_BTN');
        }
    }
    
    
    /**
     * Връща всички производими артикули от продажбата
     *
     * @param int|stdClass $id - ид или запис
     * @param boolean $onlyActive - дали да са само активните артикули
     *
     * @return array $res - масив с производимите артикули
     */
    public static function getManifacturableProducts($id, $onlyActive = false)
    {
        // Кои са производимите, активни артикули
        $res = array();
        $rec = static::fetchRec($id);
        $saleQuery = sales_SalesDetails::getQuery();
        $saleQuery->where("#saleId = {$rec->id}");
        $saleQuery->EXT('canManifacture', 'cat_Products', 'externalName=canManifacture,externalKey=productId');
        $saleQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $saleQuery->where("#canManifacture = 'yes'");
        $saleQuery->orderBy('id', 'ASC');
        $saleQuery->XPR('codeExp', 'varchar', "LOWER(COALESCE(#code, CONCAT('Art', #id)))");
        $saleQuery->show('productId,codeExp');

        if($onlyActive){
            $saleQuery->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
            $saleQuery->where("#state = 'active'");
        }

        // Извличане на кода и рефа, за да са готови за сортиране
        $productArr = array();
        $listId = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'salesList');
        while($dRec = $saleQuery->fetch()){
            $productArr[$dRec->productId] = (object)array('productId' => $dRec->productId, 'code' => $dRec->codeExp);
            if (isset($listId)) {
                $productArr[$dRec->productId]->reff = cat_Listings::getReffByProductId($listId, $dRec->productId, $dRec->packagingId);
            }
        }

        // Сортиране на артикулите, както сa подредени в продажбата
        $detailOrderBy = $rec->detailOrderBy;

        if($detailOrderBy == 'code'){
            arr::sortObjects($productArr, 'code', 'ASC', 'natural');
        } elseif($detailOrderBy == 'reff' && isset($listId)){
            arr::sortObjects($productArr, 'reff', 'ASC', 'natural');
        }

        $productArr = array_keys($productArr);
        foreach ($productArr as $productId){
            $res[$productId] = cat_Products::getTitleById($productId, false);
        }
        
        return $res;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     *
     * @param int $id
     *
     * @return NULL|string
     */
    public static function getThreadState_($id)
    {
    }


    /**
     * Дефолтно дали да са видими цените в нишката от всички
     *
     * @param stdClass $rec
     * @return boolean
     */
    private function areThePricesInThreadVisibleByAll($rec)
    {
        $listId = isset($rec->priceListId) ? $rec->priceListId : price_ListToCustomers::getListForCustomer($rec->contragentClassId, $rec->contragentId, $rec->valior);
        $visiblePrices = price_Lists::fetchField($listId, 'visiblePricesByAnyone');

        if($visiblePrices == 'no') return false;

        // Ако продажбата или някоя от обединените е към оферта
        $documents = array($rec->id);
        if(!empty($rec->closedDocuments)) {
            $documents += keylist::toArray($rec->closedDocuments);
        }

        foreach ($documents as $docId) {
            if($docRec = $this->fetch($docId, 'originId,visiblePricesByAllInThread')){
                if(isset($docRec->originId)){
                    $origin = doc_Containers::getDocument($docRec->originId);
                    if($origin->isInstanceOf('sales_Quotations')) return false;
                }

                if($docRec->visiblePricesByAllInThread == 'no') return false;
            }
        }

        return true;
    }


    /**
     * След вербализиране на записа
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (core_Packs::isInstalled('eshop') && isset($fields['-single'])) {
            if ($cartRec = eshop_Carts::fetch("#saleId = {$rec->id}", 'id,domainId,personNames,tel,email')) {
                $cartRow = eshop_Carts::recToVerbal($cartRec, 'domainId,personNames,tel,email');
                $row->cartId = ht::createLink("№{$cartRec->id}", eshop_Carts::getSingleUrlArray($cartRec->id), false, 'ef_icon=img/16/trolley.png');
                $row->cartDomainId = $cartRow->domainId;
                $row->cartPersonnames = $cartRow->personNames;
                $row->cartTel = $cartRow->tel;
                $row->cartEmail = $cartRow->email;
            }
        }
        
        core_Lg::push($rec->tplLang);
        
        if (!empty($rec->bankAccountId)) {
            if (!Mode::isReadOnly()) {

                // Линк към нашата банкова сметка
                $ownBankRec = bank_OwnAccounts::fetch(array("#bankAccountId = '[#1#]'", $rec->bankAccountId));
                if(is_object($ownBankRec)){
                    $bankAccountRec = bank_OwnAccounts::getOwnAccountInfo($ownBankRec->id);
                    $row->bankAccountId = $bankAccountRec->iban;
                    $singleBankUrl = bank_OwnAccounts::getSingleUrlArray($ownBankRec);
                    if(countR($singleBankUrl)){
                        $attr = !empty($ownBankRec->title) ? "title={$ownBankRec->title}" : null;
                        $row->bankAccountId = ht::createLink($row->bankAccountId, $singleBankUrl, false, $attr);
                    }
                }
            }

            if ($bic = bank_Accounts::getVerbal($rec->bankAccountId, 'bic')) {
                $row->bic = $bic;
            }
            
            if ($bank = bank_Accounts::getVerbal($rec->bankAccountId, 'bank')) {
                $row->bank = tr($bank);
            }
        }
        
        if ($rec->chargeVat != 'yes' && $rec->chargeVat != 'separate') {
            if (!Mode::isReadOnly()) {
                if ($rec->contragentClassId == crm_Companies::getClassId()) {
                    $companyRec = crm_Companies::fetch($rec->contragentId);
                    $bulgariaCountryId = drdata_Countries::fetchField("#commonName = 'Bulgaria'");
                    if ($companyRec->country != $bulgariaCountryId && drdata_Countries::isEu($companyRec->country)) {
                        if (empty($companyRec->vatId)) {
                            $row->vatId = tr('Ще бъде предоставен');
                            $row->vatId = "<span class='red'>{$row->vatId}</span>";
                        }
                    }
                }
            }
        }
        
        if (isset($rec->priceListId)) {
            $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
        }
        
        if (isset($fields['-single'])) {
            if(isset($rec->voucherId)){
                $row->voucherId = voucher_Cards::getVerbal($rec->voucherId, 'number');
            }

            if(!empty($rec->courierApiPrice)){
                $row->courierApiPrice = currency_Currencies::decorate($rec->courierApiPrice);
            }

            if($receiptId = pos_Receipts::fetchField("#transferredIn = {$rec->id}")){
                $row->receiptId = pos_Receipts::getHyperlink($receiptId, true);
            }

            // Показване на дефолт дали цените ще са видими
            if(empty($rec->visiblePricesByAllInThread) && in_array($rec->state, array('draft', 'pending'))){
                $visiblePrices = ($mvc->areThePricesInThreadVisibleByAll($rec)) ? 'yes' : 'no';
                $row->visiblePricesByAllInThread =  $mvc->getFieldType('visiblePricesByAllInThread')->toVerbal($visiblePrices);
            }

            $row->visiblePricesByAllInThread = mb_strtolower($row->visiblePricesByAllInThread);
            $row->visiblePricesByAllInThread = ht::createHint("", "Цени и суми в нишката|*: |{$row->visiblePricesByAllInThread}|*");
            if ($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'commonConditionSale')) {
                $row->commonConditionQuote = cls::get('type_Url')->toVerbal($cond);
            }

            $row->detailOrderBy = mb_strtolower($row->detailOrderBy);
			$row->detailOrderBy = ht::createHint("", "Подреждане артикули по|*: |{$row->detailOrderBy}|*");
            
            core_Lg::pop();
            $row->transportCurrencyId = $row->currencyId;
            $hiddenTransportCost = sales_TransportValues::calcInDocument($mvc, $rec->id);
            $expectedTransportCost = $mvc->getExpectedTransportCost($rec);
            $visibleTransportCost = $mvc->getVisibleTransportCost($rec);
            
            $leftTransportCost = 0;
            sales_TransportValues::getVerbalTransportCost($row, $leftTransportCost, $hiddenTransportCost, $expectedTransportCost, $visibleTransportCost, $rec->currencyRate);
            
            // Ако има транспорт за начисляване
            if ($leftTransportCost > 0) {
                
                // Ако може да се добавят артикули в офертата
                if (sales_SalesDetails::haveRightFor('add', (object) array('saleId' => $rec->id))) {
                    
                    // Добавяне на линк, за добавяне на артикул 'транспорт' със цена зададената сума
                    $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                    $packPrice = $leftTransportCost * $rec->currencyRate;
                    
                    $url = array('sales_SalesDetails', 'add', 'saleId' => $rec->id,'productId' => $transportId, 'packPrice' => $packPrice, 'ret_url' => true);
                    $link = ht::createLink(tr('Добавяне'), $url, false, array('ef_icon' => 'img/16/lorry_go.png', 'style' => 'font-weight:normal;font-size: 0.8em', 'title' => 'Добавяне на допълнителен транспорт'));
                    $row->btnTransport = $link->getContent();
                }
            }

            if(empty($rec->courierApi)){
                if($courierApi = cond_DeliveryTerms::getCourierApi($rec->deliveryTermId)){
                    $courierApiVerbal = $mvc->getFieldType('courierApi')->toVerbal($courierApi);
                    $row->courierApi = ht::createHint("<span style='color:blue'>{$courierApiVerbal}</span>", 'От условието на доставка', 'notice', false);
                }
            }

            core_Lg::push($rec->tplLang);
        } elseif (isset($fields['-list']) && doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $row->title = '<b>' . $row->title . '</b>';
            $row->title .= '  «  ' . $row->folderId;
        }
        
        // Ако не е избрана сметка, от дефолтните
        if ($rec->bankAccountId && !Mode::isReadOnly() && haveRole('powerUser')) {
            $errorStr = null;

            $ownBankRec = bank_OwnAccounts::fetch(array("#bankAccountId = '[#1#]'", $rec->bankAccountId));
            if(in_array($ownBankRec->state, array('closed', 'rejected'))){
                $errorStr = 'Банковата сметка е закрита|*!';
            }

            if(in_array($rec->state, array('draft', 'pending'))){
                $cData = doc_Folders::getContragentData($rec->folderId);
                $defBankId = null;
                if (!isset($ownBankRec->countries)) {
                    $defBankId = bank_OwnAccounts::getDefaultIdForCountry($cData->countryId, false);
                } else {
                    if (!type_Keylist::isIn($cData->countryId, $ownBankRec->countries)) {
                        $defBankId = bank_OwnAccounts::getDefaultIdForCountry($cData->countryId);
                    }
                }

                if ($defBankId) {
                    $bRec = bank_OwnAccounts::fetch(array("#bankAccountId = '[#1#]'", $defBankId));
                    $errorStr = (!empty($errorStr) ? "{$errorStr} " : "") . '|Има нова банкова сметка за тази държава|*: ' . bank_OwnAccounts::getVerbal($bRec, 'title');
                }
            }

            $accountRec = bank_Accounts::fetch($rec->bankAccountId);
            if(currency_Currencies::getIdByCode($rec->currencyId) != $accountRec->currencyId){
                $errorStr = (!empty($errorStr) ? "{$errorStr} " : "") . '|Банковата сметка е в различна валута от тази на сделката|*!';
            }
            if(!empty($errorStr) && $rec->paymentType != 'cash'){
                if(core_Users::isPowerUser()){
                    $row->bankAccountId = "<span class='warning-balloon' style ='background-color:#ff9494a8'>{$row->bankAccountId}</span>";
                    $row->bankAccountId = ht::createHint($row->bankAccountId, $errorStr, 'warning');
                }
            }
        }

        if(in_array($rec->paymentType, array('postal', 'cash', 'card')) && !empty($row->bankAccountId)){
            $row->BANK_BLOCK_CLASS = 'quiet saleBankBlock';
        }

        core_Lg::pop();
    }
    
    
    /**
     * Колко е видимия транспорт начислен в сделката
     *
     * @param stdClass $rec - запис на ред
     *
     * @return float - сумата на видимия транспорт в основна валута без ДДС
     */
    private function getVisibleTransportCost($rec)
    {
        // Извличат се всички детайли и се изчислява сумата на транспорта, ако има
        $query = sales_SalesDetails::getQuery();
        $query->where("#saleId = {$rec->id}");
        
        return sales_TransportValues::getVisibleTransportCost($query);
    }
    
    
    /**
     * Колко е сумата на очаквания транспорт
     *
     * @param stdClass $rec - запис на ред
     *
     * @return float $expectedTransport - очаквания транспорт без ддс в основна валута
     */
    private function getExpectedTransportCost($rec)
    {
        if (isset($rec->expectedTransportCost)) {
            return $rec->expectedTransportCost;
        }
 
        $expectedTransport = 0;
        
        // Ако няма калкулатор в условието на доставка, не се изчислява нищо
        $TransportCalc = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId);
        if (!is_object($TransportCalc)) {
            return $expectedTransport;
        }
        
        // Подготовка на заявката, взимат се само складируеми артикули
        $query = sales_SalesDetails::getQuery();
        $query->where("#saleId = {$rec->id}");
        $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $query->where("#canStore = 'yes'");
        $products = $query->fetchAll();
        
        $codeAndCountryArr = sales_TransportValues::getCodeAndCountryId($rec->contragentClassId, $rec->contragentId, null, null, $rec->deliveryLocationId ? $rec->deliveryLocationId : $rec->deliveryAdress);
        $ourCompany = crm_Companies::fetchOurCompany('*', null, $rec->activatedOn);
        $params = array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode'], 'fromCountry' => $ourCompany->country, 'fromPostalCode' => $ourCompany->pCode);
        if ($rec->deliveryData) {
            $params += $rec->deliveryData;
        }
        
        // Изчисляване на общото тегло на офертата
        $total = sales_TransportValues::getTotalWeightAndVolume($TransportCalc, $products, $rec->deliveryTermId, $params);
        if ($total == cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT) {
            return cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT;
        }
        
        // За всеки артикул се изчислява очаквания му транспорт
        foreach ($products as $p2) {
            $fee = sales_TransportValues::getTransportCost($rec->deliveryTermId, $p2->productId, $p2->packagingId, $p2->quantity, $total, $params);
            
            // Сумира се, ако е изчислен
            if (is_array($fee) && $fee['totalFee'] > 0) {
                $expectedTransport += $fee['totalFee'];
            }
        }
        
        // Кеширане на очаквания транспорт при нужда
        if (is_null($rec->expectedTransportCost) && in_array($rec->state, array('active', 'closed'))) {
            $rec->expectedTransportCost = $expectedTransport;
            $this->save_($rec, 'expectedTransportCost');
        }
        
        // Връщане на очаквания транспорт
        return $expectedTransport;
    }
    
    
    /**
     * Списък с артикули върху, на които може да им се коригират стойностите
     *
     * @param mixed $id          - ид или запис
     * @param mixed $forMvc      - за кой мениджър
     * @param string  $option    - опции
     *
     * @return array $products         - масив с информация за артикули
     *               o productId       - ид на артикул
     *               o name            - име на артикула
     *               o quantity        - к-во
     *               o amount          - сума на артикула
     *               o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *               o transportWeight - транспортно тегло на артикула
     *               o transportVolume - транспортен обем на артикула
     */
    public function getCorrectableProducts($id, $forMvc, $option = null)
    {
        $rec = $this->fetchRec($id);
        
        // Взимаме артикулите от сметка 701
        $products = array();
        $entries = sales_transaction_Sale::getEntries($rec->id);
        $shipped = sales_transaction_Sale::getShippedProducts($entries);

        if (countR($shipped)) {
            foreach ($shipped as $ship) {
                if($option == 'storable'){
                    $canStore = cat_Products::fetchField($ship->productId, 'canStore');
                    if($canStore != 'yes') continue;
                }

                if($ship->quantity <= 0) continue;

                unset($ship->price);
                $ship->name = cat_Products::getTitleById($ship->productId, false);
                
                if ($transportWeight = cat_Products::getTransportWeight($ship->productId, 1)) {
                    $ship->transportWeight = $transportWeight;
                }
                
                if ($transportVolume = cat_Products::getTransportVolume($ship->productId, 1)) {
                    $ship->transportVolume = $transportVolume;
                }
                
                $products[$ship->productId] = $ship;
            }
        }
        
        return $products;
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
        core_Cache::remove('sales_reports_ShipmentReadiness', "c{$rec->containerId}");
    }
    
    
    /**
     * Екшън за създаване на продажба директно от нестандартен артикул
     */
    public function act_createsaleforproduct()
    {
        $this->requireRightFor('createsaleforproduct');
        expect($folderId = core_Request::get('folderId', 'int'));
        expect($productId = core_Request::get('productId', 'int'));
        expect(cat_Products::fetch($productId));
        
        $this->requireRightFor('createsaleforproduct', (object) array('folderId' => $folderId, 'productId' => $productId));
        $cover = doc_Folders::getCover($folderId);
        $fields = array('dealerId' => sales_Sales::getDefaultDealerId($folderId));

        // Създаване на продажба и редирект към добавянето на артикула
        try {
            expect($saleId = sales_Sales::createNewDraft($cover->getInstance(), $cover->that, $fields));
            redirect(array('sales_SalesDetails', 'add', 'saleId' => $saleId, 'productId' => $productId));
        } catch (core_exception_Expect $e) {
            $errorMsg = $e->getMessage();
            reportException($e);
            cat_Products::logErr($errorMsg, $productId);
            
            followRetUrl(null, $errorMsg, 'error');
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        if (empty($rec->id)) {
            if (sales_SalesDetails::haveRightFor('importlisted') && cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'salesList')) {
                $data->form->toolbar->addSbBtn('Чернова и лист', 'save_and_list', 'id=btnsaveAndList,order=9.99987', 'ef_icon = img/16/save_and_new.png');
            }
        }
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако има форма, и тя е събмитната и действието е 'запис'
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save_and_list') {
            $id = $data->form->rec->id;
            if (sales_SalesDetails::haveRightFor('importlisted', (object) array('saleId' => $id))) {
                $data->retUrl = toUrl(array('sales_SalesDetails', 'importlisted', 'saleId' => $id, 'ret_url' => toUrl(array('sales_Sales', 'single', $id), 'local')));
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @param datetime $date
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        $rec = hr_IndicatorNames::force('Активирани_продажби', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        return $result;
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        $iRec = hr_IndicatorNames::force('Активирани_продажби', __CLASS__, 1);
        
        $query = self::getQuery();
        $query->where("#state = 'active' || #state = 'closed' || (#state = 'rejected' && (#brState = 'active' || #brState = 'closed'))");
        $query->where("#modifiedOn >= '{$timeline}'");
        $query->show('valior,activatedOn,activatedBy,state,createdBy');
        
        while ($rec = $query->fetch()) {
            $activatedBy = isset($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
            if (empty($activatedBy)) {
                continue;
            }
            $personId = crm_Profiles::fetchField("#userId = {$activatedBy}", 'personId');
            if (empty($personId)) {
                continue;
            }
            
            $result[] = (object) array('date' => dt::verbal2mysql($rec->valior, false),
                'personId' => $personId,
                'docId' => $rec->id,
                'docClass' => sales_Sales::getClassId(),
                'indicatorId' => $iRec->id,
                'value' => 1,
                'isRejected' => $rec->state == 'rejected',
            );
        }
        
        return $result;
    }
    
    
    /**
     * Прихваща извикването на AfterSaveLogChange в change_Plugin
     * Добавя нотификация след промяна на документа
     *
     * @param $mvc $mvc
     * @param array $recsArr - Масив със записаните данни
     */
    protected static function on_AfterSaveLogChange($mvc, $recsArr)
    {
        if (is_array($recsArr)) {
            if ($fRec = $recsArr[0]) {
                if ($fRec->docClass && $fRec->docId) {
                    $rec = cls::get($fRec->docClass)->fetch($fRec->docId, 'threadId,containerId');
                    
                    // Кои са контейнерите в нишката
                    $tRec = doc_Containers::getQuery();
                    $tRec->where("#threadId = {$rec->threadId}");
                    $tRec->show('id');
                    $containerIds = arr::extractValuesFromArray($tRec->fetchAll(), 'id');
                    $containerIds[$fRec->containerId] = $rec->containerId;
                    
                    // Ще им се преизчисляват делтите
                    sales_PrimeCostByDocument::updatePersons($containerIds);
                }
            }
        }
    }
    
    
    /**
     * Връща разпределените разходи по сделката
     *
     * @param int $threadId
     *
     * @return array $res
     */
    public static function getCalcedTransports($threadId)
    {
        $res = array();
        $Doc = doc_Threads::getFirstDocument($threadId);
        if (empty($Doc)) {
            return $res;
        }
        if (!$Doc->isInstanceOf('sales_Sales')) {
            return $res;
        }
        
        $saleClassId = sales_Sales::getClassId();
        $tCostQuery = sales_TransportValues::getQuery();
        $tCostQuery->where("#docClassId = {$saleClassId} AND #docId = {$Doc->that}");
        $tCostQuery->where('#fee > 0');
        while ($tRec = $tCostQuery->fetch()) {
            $dRec = sales_SalesDetails::fetch($tRec->recId, 'productId,quantity');
            if (!array_key_exists($dRec->productId, $res)) {
                $res[$dRec->productId] = new stdClass();
            }
            
            $res[$dRec->productId]->fee += $tRec->fee;
            $res[$dRec->productId]->quantity += $dRec->quantity;
        }
        
        return $res;
    }
    
    
    /**
     * След инпут на формата за избор на действие
     *
     * @see deals_DealMaster::act_Chooseaction
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     * @param stdClass  $rec
     *
     * @return void
     */
    protected static function on_AfterInputSelectActionForm($mvc, &$form, $rec)
    {
        if ($form->isSubmitted()) {
            if($rec->amountDeal < 0){
                $form->setError('action', 'Общата сума на продажбата не може да е отрицателна|*!');
            } else {
                $action = type_Set::toArray($form->rec->action);
                if (isset($action['ship'])) {
                    $dQuery = sales_SalesDetails::getQuery();
                    $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                    $dQuery->where("#saleId = {$rec->id}");
                    $dQuery->show('productId,quantity,canStore');
                    $dQuery2 = clone $dQuery;

                    $detailsToCheck = array();
                    while($dRec = $dQuery->fetch()){
                        $addProductToCheck = true;
                        $instantBomRec = cat_Products::getLastActiveBom($dRec->productId, 'instant');
                        if(is_object($instantBomRec)){
                            $bomInfo = cat_Boms::getResourceInfo($instantBomRec, $dRec->quantity, $rec->valior);
                            if(is_array($bomInfo['resources'])){
                                foreach ($bomInfo['resources'] as $r){
                                    if(!array_key_exists($r->productId, $detailsToCheck)){
                                        $detailsToCheck[$r->productId] = (object)array('productId' => $r->productId, 'quantity' => 0);
                                    }
                                    $detailsToCheck[$r->productId]->quantity += $r->propQuantity;
                                    $addProductToCheck = false;
                                }
                            }
                        }
                        if($addProductToCheck){
                            if(!array_key_exists($dRec->productId, $detailsToCheck)){
                                $detailsToCheck[$dRec->productId] = (object)array('productId' => $dRec->productId, 'quantity' => 0);
                            }
                            $detailsToCheck[$dRec->productId]->quantity += $dRec->quantity;
                        }
                    }

                    if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($detailsToCheck, $rec->shipmentStoreId, $rec->state)) {
                        if(store_Setup::canDoShippingWhenStockIsNegative()){
                            $form->setWarning('action', $warning);
                        } else {
                            $form->setError('action', $warning);
                        }
                    }

                    $detailsAll = $dQuery2->fetchAll();
                    $productCheck = deals_Helper::checkProductForErrors(arr::extractValuesFromArray($detailsAll, 'productId'), 'canSell');

                    if ($productCheck['metasError']) {
                        $error1 = 'Артикулите|*: ' . implode(', ', $productCheck['metasError']) . ' |трябва да са продаваеми|*!';
                        $form->setError('action', $error1);
                    } elseif ($productCheck['notActive']) {
                        $error1 = 'Артикулите|*: ' . implode(', ', $productCheck['notActive']) . ' |трябва да са активни|*!';
                        $form->setError('action', $error1);
                    }
                }
            }
        }
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова  артикула, активираме и нея
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $clientGroupId = crm_Groups::getIdFromSysId('customers');
        $groupRec = (object) array('name' => 'Продажби', 'sysId' => 'saleClients', 'parentId' => $clientGroupId);
        $groupId = crm_Groups::forceGroup($groupRec);

        // След активиране се обновява полето за видимост на цените
        if(empty($rec->visiblePricesByAllInThread)){
            $rec->visiblePricesByAllInThread = ($mvc->areThePricesInThreadVisibleByAll($rec)) ? 'yes' : 'no';
            $mvc->save_($rec, 'visiblePricesByAllInThread');
        }

        cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, false);

        // Маркиране на ваучера че е използван
        if(core_Packs::isInstalled('voucher')){
            if(isset($rec->voucherId)){
                voucher_Cards::mark($rec->voucherId, true, $mvc->getClassId(), $rec->id, true);
            }
        }
    }
    
    
    /**
     * Преди експортиране като CSV
     */
    protected static function on_BeforeExportCsv($mvc, &$recs)
    {
        if (is_array($recs)) {
            foreach ($recs as &$rec) {
                foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced') as $amnt) {
                    if (round($rec->{"amount{$amnt}"}, 2) != 0) {
                        $rec->currencyRate = ($rec->currencyRate) ? $rec->currencyRate : 1;
                        $rec->{"amount{$amnt}"} = round($rec->{"amount{$amnt}"} / $rec->currencyRate, 2);
                    } else {
                        $rec->{"amount{$amnt}"} = 0;
                    }
                }
                
                $invoices = deals_Helper::getInvoicesInThread($rec->threadId);
                if (countR($invoices)) {
                    $rec->invoices = str_replace('#Inv', '', implode(', ', $invoices));
                }

                if(core_Packs::isInstalled('eshop')){
                    if($cartRec = eshop_Carts::fetch("#saleId = {$rec->id}")){
                        $rec->tel = $cartRec->tel;
                        $rec->email = $cartRec->email;
                        $rec->cartId = $cartRec->id;
                        $rec->instruction = $cartRec->instruction;
                    }
                }
            }
        }
    }
    
    
    /**
     * След обновяване на мастъра
     *
     * @param mixed $id - ид/запис на мастъра
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        // Ако е зададено в мода да не се рекалкулират отстъпките
        $calcAutoDiscounts = Mode::get('calcAutoDiscounts');
        if($calcAutoDiscounts === false) return;

        // Ако има твърда отстъпка за целия документ, изчислява се тя
        $rec = $mvc->fetchRec($id);
        $recalcTotalDiscount = $mvc->recalcAutoTotalDiscount($rec);
        if(!$recalcTotalDiscount){
            // Ако няма се изчислява по автоматичните отстъпки от ЦП
            static::recalcAutoDiscount($rec);
        }

        $mvc->updateMaster_($rec);
    }
    
    
    /**
     * Подготовка на рейтингите за продажба на артикулите
     *
     * @see sales_RatingsSourceIntf
     *
     * @return array $res - масив с обекти за върнатите данни
     *               o objectClassId - ид на клас на обект
     *               o objectId      - ид на обект
     *               o classId       - текущия клас
     *               o key           - ключ
     *               o value         - стойност
     */
    public function getSaleRatingsData()
    {
        $time = sales_Setup::get('STATISTIC_DATA_FOR_THE_LAST');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $time), false);
        
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->where("#valior >= '{$valiorFrom}'");
        $deltaQuery->show('productId,storeId,detailClassId');
        $receiptClassId = pos_Reports::getClassId();
        $classId = $this->getClassId();
        $objectClassId = cat_Products::getClassId();
        
        $res = array();
        $count = $deltaQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        while ($dRec = $deltaQuery->fetch()) {
            $rating = ($dRec->detailClassId == $receiptClassId) ? 1 : 10;
            
            $index = "{$dRec->productId}|{$dRec->storeId}";
            sales_ProductRatings::addRatingToObject($res, $index, $classId, $objectClassId, $dRec->productId, $dRec->storeId, $rating);
        }
        
        $res = array_values($res);
        
        return $res;
    }
    
    
    /**
     * Интерфейсен метод
     *
     * @param int $id
     * @param datetime|int $id
     * @return object
     *
     * @see doc_ContragentDataIntfstatic function getContragentData(
     */
    public static function getContragentData($id, $date = null)
    {
        if (core_Packs::isInstalled('eshop') && ($rec = self::fetchRec($id))) {
            if ($cartRec = eshop_Carts::fetch("#saleId = {$id}")) {
                $contrData = new stdClass();
                
                if ($rec->folderId) {
                    $Cover = doc_Folders::getCover($rec->folderId);
                    
                    if ($Cover->haveInterface('doc_ContragentDataIntf')) {
                        $cData = $Cover->getContragentData($date);
                        
                        if ($cData->company) {
                            $contrData->company = $cData->company;
                            $contrData->companyId = $cData->companyId;
                            if (!$cartRec->tel) {
                                $contrData->tel = $cData->tel;
                            }
                        }
                    }
                }
                
                $contrData->person = $cartRec->personNames;
                $contrData->pTel = $cartRec->tel;
                $contrData->countryId = $cartRec->country;
                
                if ($cartRec->deliveryAddress) {
                    $contrData->pCode = $cartRec->deliveryPCode;
                    $contrData->place = $cartRec->deliveryPlace;
                    $contrData->address = $cartRec->deliveryAddress;
                } else {
                    $contrData->pCode = $cartRec->invoicePCode;
                    $contrData->place = $cartRec->invoicePlace;
                    $contrData->address = $cartRec->invoiceAddress;
                }
                
                $contrData->email = $cartRec->email;
                $contrData->priority = 20;

                $contrData->_getContragentDataFromLastDoc = false;

                return $contrData;
            }
        }
    }
    
    
    /**
     * След извличане на опциите за филтър по тип
     */
    protected static function on_AfterGetListFilterTypeOptions($mvc, &$res, $data)
    {
        if (core_Packs::isInstalled('eshop')) {
            $res['onlineSale'] = 'Онлайн продажби';
        }

        if (core_Packs::isInstalled('voucher')) {
            $res['voucher'] = 'С ваучери';
        }
    }
    
    
    /**
     * Филтриране на листовия изглед по тип
     */
    protected function on_AfterFilterListFilterByOption($mvc, &$res, $option, &$query)
    {
        if ($option == 'onlineSale') {
            $query->EXT('cartId', 'eshop_Carts', 'externalName=id,remoteKey=saleId');
            $query->where('#cartId IS NOT NULL');
        }
        if ($option == 'voucher') {
            $query->where('#voucherId IS NOT NULL');
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    protected static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        $errorMsg = null;
        if (deals_Helper::hasProductsBellowMinPrice($mvc, $rec, $errorMsg)) {
            $rec->contoActions = '';
            $mvc->save_($rec, 'contoActions');
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }

        // Ако е инсталир пакета за ваучери проверка дали може да се контира
        if(core_Packs::isInstalled('voucher')){
            $dQuery = sales_SalesDetails::getQuery();
            $dQuery->where("#saleId = {$rec->id}");
            $dQuery->show('productId');
            $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');
            if($error = voucher_Cards::getContoErrors($rec->voucherId, $productIds, $mvc->getClassId(), $rec->id)){
                $rec->contoActions = '';
                $mvc->save_($rec, 'contoActions');
                core_Statuses::newStatus($error, 'error');

                return false;
            }
        }
    }


    /**
     * Каква е датата на доставка
     *
     * @param $rec
     * @return date $deliveryDate
     */
    public function getDeliveryDate($rec)
    {
        $rec = $this->fetchRec($rec);
        $deliveryDate = $rec->deliveryTime;
        if(empty($deliveryDate)){
            $deliveryDate = $rec->valior;
            if(!empty($rec->deliveryTermTime)){
                $deliveryDate = dt::addSecs($rec->deliveryTermTime, $deliveryDate);
            }
        }

        return $deliveryDate;
    }


    /**
     * Кой клас е избран за куриерско АПИ в документа
     *
     * @param stdClass $rec
     * @return null|int
     */
    public function getCourierApi4Document($rec)
    {
        // Ако има конкретно посочено куриерско API
        $courierApiDriver = null;
        $rec = $this->fetchRec($rec);
        if(isset($rec->courierApi)) {
            $courierApiDriver = $rec->courierApi;
        } elseif(isset($rec->deliveryTermId)){
            if($courierApi = cond_DeliveryTerms::getCourierApi($rec->deliveryTermId)) {
                $courierApiDriver = $courierApi;
            }
        }

        return cls::load($courierApiDriver, true) ? $courierApiDriver : null;
    }


    /**
     * Преди записване на клонирания запис
     */
    protected function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        // При репликиране от напомняне
        if(isset($nRec->__isReplicate)){
            if(isset($rec->bankAccountId)){

                // Ако банковата сметка е затворена се сменя с дефолтната такава
                $ownBankRec = bank_OwnAccounts::fetch("#bankAccountId = {$rec->bankAccountId}", 'state');
                if(in_array($ownBankRec->state, array('closed', 'rejected'))){
                    $cData = doc_Folders::getContragentData($rec->folderId);
                    $defaultCountryId = bank_OwnAccounts::getDefaultIdForCountry($cData->countryId);
                    $nRec->bankAccountId = is_numeric($defaultCountryId) ? $defaultCountryId : null;
                }
            }
        }
    }


    /**
     * След взимане на полетата за експорт в csv
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        $fieldset->FLD('tel', 'drdata_PhoneType', 'caption=Поръчител->Телефон');
        $fieldset->FLD('email', 'email', 'caption=Поръчител->Имейл');
        $fieldset->FLD('cartId', 'int', 'caption=Поръчител->Количка №');
        $fieldset->FLD('instruction', 'int', 'caption=Поръчител->Инструкции');
    }


    /**
     * Рекалкулира автоматичните отстъпки за продажбата
     *
     * @param $rec
     * @return void
     */
    public static function recalcAutoDiscount($rec)
    {
        $rec = sales_Sales::fetchRec($rec);

        // Има ли лист за автоматични отстъпки
        $basicDiscountListRec = price_Lists::getListWithBasicDiscounts(get_called_class(), $rec);
        if(!is_object($basicDiscountListRec)) return;

        // Взима всички детайли и се опитва да сметне автоматичните отстъпки
        $Detail = cls::get('sales_SalesDetails');
        $dQuery = $Detail->getQuery();
        $dQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $dQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $dQuery->where("#saleId = {$rec->id} AND #isPublic = 'yes'");
        $detailsAll = $dQuery->fetchAll();
        $discountData = price_ListBasicDiscounts::getAutoDiscountsByGroups($basicDiscountListRec, 'sales_Sales', $rec, 'sales_SalesDetails', $detailsAll);

        // За всеки артикул, ако попада в група с авотматични отстъпки - взима средния процент от нея
        $save = array();
        foreach ($detailsAll as $dRec){
            foreach ($discountData['groups'] as $groupId => $d){
                if(!keylist::isIn($groupId, $dRec->groups)) continue;
                if(empty($d['percent'])) continue;

                $dRec->autoDiscount = $d['percent'];
                $save[] = $dRec;
            }
        }

        if(countR($save)){
            $Detail->saveArray($save, 'id,autoDiscount');
        }
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if(core_Packs::isInstalled('voucher') && isset($rec->voucherId)){
            voucher_Cards::mark($rec->voucherId, false);
        }
    }

    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        // Проверка дали ваучерът е вече свободен
        if(isset($rec->voucherId) && core_Packs::isInstalled('voucher')){
            if($error = voucher_Cards::getRestoreError($rec->voucherId)){
                core_Statuses::newStatus($error, 'error');

                return false;
            }
        }
    }


    /**
     * Връща класа на обратния документ
     */
    public function getDocumentReverseClass($rec)
    {
        $class = 'store_Receipts';

        return cls::get($class);
    }
}
