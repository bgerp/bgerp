<?php


/**
 * Документ 'Покупка'
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Покупки
 */
class purchase_Purchases extends deals_DealMaster
{
    /**
     * Заглавие
     */
    public $title = 'Договори за покупка';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, bgerp_DealAggregatorIntf, bgerp_DealIntf, acc_TransactionSourceIntf=purchase_transaction_Purchase, deals_DealsAccRegIntf, acc_RegisterIntf, deals_InvoiceSourceIntf,colab_CreateDocumentIntf,acc_AllowArticlesCostCorrectionDocsIntf,trans_LogisticDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, purchase_Wrapper,purchase_plg_ExtractPurchasesData, acc_plg_Registry, plg_Sorting, doc_plg_TplManager, doc_DocumentPlg, acc_plg_Contable, plg_Printing,
				        cond_plg_DefaultValues, recently_Plugin, doc_plg_HidePrices, doc_SharablePlg, plg_Clone,
				        doc_EmailCreatePlg, bgerp_plg_Blank, acc_plg_DocumentSummary, cat_plg_AddSearchKeywords, change_Plugin, plg_Search, doc_plg_Close, plg_LastUsedKeys,deals_plg_SaveValiorOnActivation';
    
    
    /**
     * При създаване на имейл, дали да се използва първият имейл от списъка
     */
    public $forceFirstEmail = true;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pur';


    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,purchase,acc';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,purchase';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,purchase,acc,purchaseAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,purchase,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,purchaseMaster,manager';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, currencyId=Валута, amountDeal, amountDelivered, amountPaid,amountInvoiced,amountInvoicedDownpayment,amountInvoicedDownpaymentToDeduct,dealerId=Закупчик,paymentState,createdOn, createdBy';
    
    
    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'purchase_PurchasesDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Покупка';


    /**
     * Клас на оферта
     */
    protected $quotationClass = 'purchase_Quotations';


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cart_put.png';
    
    
    /**
     * Икона за единичния изглед на обединяващите договори
     */
    public $singleIconFocCombinedDeals = 'img/16/shopping_carts_blue.png';
    
    
    /**
     * Лейаут на единичния изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutPurchase.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.2|Логистика';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'deliveryTermId, deliveryLocationId, deliveryTime, shipmentStoreId, paymentMethodId,
    					 currencyId, bankAccountId, caseId, dealerId, folderId, note, reff';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountDelivered,amountPaid,amountInvoiced,amountBl,amountToPay,amountInvoicedDownpaymentToDeduct,amountInvoicedDownpayment,amountToDeliver,amountToInvoice';
    
    
    /**
     * Кой може да превалутира документите в нишката
     */
    public $canChangerate = 'ceo, purchaseMaster';


    /**
     * Огледален клас за обратната операция
     */
    public $reverseClassName = 'store_ShipmentOrders';


    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see store_StockPlanning
     */
    public $stockPlanningDirection = 'in';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'dealerId,initiatorId,oneTimeDelivery';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'deliveryTermId' => 'clientCondition|lastDocUser|lastDoc',
        'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId' => 'lastDocUser|lastDoc|CoverMethod',
        'bankAccountId' => 'defMethod',
        'dealerId' => 'defMethod',
        'makeInvoice' => 'lastDocUser|lastDoc',
        'deliveryLocationId' => 'lastDocUser|lastDoc',
        'chargeVat' => 'defMethod',
        'template' => 'lastDocUser|lastDoc|defMethod',
        'shipmentStoreId' => 'clientCondition',
        'oneTimeDelivery' => 'clientCondition'
    );
    
    
    /**
     * В коя група по дефолт да влизат контрагентите, към които е направен документа
     */
    public $crmDefGroup = 'suppliers';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'purchase_PurchasesDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'purchase_PurchasesDetails';
    
    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
        'case2supplierAdvance' => array('title' => 'Авансово плащане към Доставчик', 'debit' => '402', 'credit' => '501'),
        'bank2supplierAdvance' => array('title' => 'Авансово плащане към Доставчик', 'debit' => '402', 'credit' => '503'),
        'case2supplier' => array('title' => 'Плащане към Доставчик', 'debit' => '401', 'credit' => '501'),
        'bank2supplier' => array('title' => 'Плащане към Доставчик', 'debit' => '401', 'credit' => '503'),
        'supplier2case' => array('title' => 'Прихващане на плащане', 'debit' => '501', 'credit' => '401', 'reverse' => true),
        'supplier2bank' => array('title' => 'Прихващане на плащане', 'debit' => '503', 'credit' => '401', 'reverse' => true),
        'supplier2caseRet' => array('title' => 'Връщане от Доставчик', 'debit' => '501', 'credit' => '401', 'reverse' => true),
        'supplier2bankRet' => array('title' => 'Връщане от Доставчик', 'debit' => '503', 'credit' => '401', 'reverse' => true),
        'supplierAdvance2case' => array('title' => 'Прихванат аванс от Доставчик', 'debit' => '501', 'credit' => '402', 'reverse' => true),
        'supplierAdvance2bank' => array('title' => 'Прихванат аванс от Доставчик', 'debit' => '503', 'credit' => '402', 'reverse' => true),
        'supplierAdvance2caseRet' => array('title' => 'Връщане на аванс от Доставчик', 'debit' => '501', 'credit' => '402', 'reverse' => true),
        'supplierAdvance2bankRet' => array('title' => 'Връщане на аванс от Доставчик', 'debit' => '503', 'credit' => '402', 'reverse' => true),
        'debitDeals' => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '401', 'reverse' => true),
        'creditDeals' => array('title' => 'Прихващане на задължение', 'debit' => '401', 'credit' => '*'),
    );
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'purchase_ClosedDeals';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'purchase,ceo,distributor';
    
    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('stowage' => array('title' => 'Засклаждане на стока', 'debit' => 'store', 'credit' => '401'),
        'buyServices' => array('title' => 'Покупка на услуги', 'debit' => 'service', 'credit' => '401'),
        'deliveryService' => array('title' => 'Връщане на направени услуги', 'debit' => '401', 'credit' => 'service', 'reverse' => true),
        'delivery' => array('title' => 'Връщане на доставена стока', 'debit' => '401', 'credit' => 'store', 'reverse' => true),
    );
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * Кои които трябва да имат потребителите да се изберат като дилъри
     */
    public $dealerRolesList = 'purchase,ceo';


    /**
     * Кои роли може да променят активна покупка
     */
    public $canChangerec = 'ceo,purchaseMaster';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDealFields($this);
        $this->FLD('bankAccountId', 'iban_Type(64)', 'caption=Плащане->Към банк. сметка,after=currencyRate');
        $this->setField('dealerId', 'caption=Наш персонал->Закупчик,notChangeableByContractor');
        $this->setField('shipmentStoreId', 'caption=Доставка->В склад,notChangeableByContractor,salecondSysId=defaultStorePurchase');
        $this->setField('deliveryTermId', 'salecondSysId=deliveryTermPurchase');
        $this->setField('paymentMethodId', 'salecondSysId=paymentMethodPurchase');
        $this->setField('oneTimeDelivery', 'salecondSysId=purchaseOneTimeDelivery');
        $this->setField('chargeVat', 'salecondSysId=purchaseChargeVat');
    }
    
    
    /**
     * Връща заглавието на покупката със сумата за фактуриране
     *
     * @param int  $id
     * @param bool $showAmount
     *
     * @return string
     */
    public static function getTitleWithAmount($id, $showAmount = true)
    {
        if (!$id) {
            
            return '';
        }
        $rec = self::fetch($id);
        
        if ($rec) {
            $amountToInvoice = $rec->amountDelivered - $rec->amountInvoiced;
            
            if ($amountToInvoice) {
                $amountToInvoice = round($amountToInvoice, 2);
            }
            
            if ($amountToInvoice) {
                $amountToInvoice .= ' ' . $rec->currencyId;
            }
        }
        
        $title = self::getTitleById($id);
        
        if ($amountToInvoice) {
            $title .= ' ' . $amountToInvoice;
        }
        
        return $title;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        $form->setSuggestions('bankAccountId', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        $form->setDefault('makeInvoice', 'yes');
        $form->setField('deliveryLocationId', 'caption=Доставка->Обект от');
        $form->setField('shipmentStoreId', 'caption=Доставка->До склад');
        
        $hideRate = core_Packs::getConfigValue('purchase', 'PURCHASE_USE_RATE_IN_CONTRACTS');
        if ($hideRate == 'yes' && !haveRole('partner')) {
            $form->setField('currencyRate', 'input');
        }
        
        // Търговеца по дефолт е отговорника на контрагента
        $inCharge = doc_Folders::fetchField($form->rec->folderId, 'inCharge');
        $form->setDefault('dealerId', $inCharge);
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
            $closeArr = array('purchase_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            
            if (purchase_ClosedDeals::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('Приключване', $closeArr, 'row=2,ef_icon=img/16/closeDeal.png,title=Приключване на покупката');
            } else {
                $exClosedDeal = purchase_ClosedDeals::fetchField("#threadId = {$rec->threadId} AND #state != 'rejected'", 'id');
                
                // Ако разликата е над допустимата но потребителя има права 'purchase', той вижда бутона но не може да го използва
                if (!purchase_ClosedDeals::isPurchaseDiffAllowed($rec) && haveRole('purchase') && empty($exClosedDeal)) {
                    $data->toolbar->addBtn('Приключване', $closeArr, 'row=2,ef_icon=img/16/closeDeal.png,title=Приключване на покупката,error=Нямате право да приключите покупка с разлика над допустимото|*!');
                }
            }
            
            if (store_Receipts::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $receiptUrl = array('store_Receipts', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
                $data->toolbar->addBtn('Засклаждане', $receiptUrl, 'ef_icon = img/16/store-receipt.png,title=Засклаждане на артикулите в склада,order=9.21');
            }
            
            if (purchase_Services::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $serviceUrl = array('purchase_Services', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true);
                $data->toolbar->addBtn('Приемане', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Покупка на услуги,order=9.22');
            }
            
            if (cash_Pko::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('РКО', array('cash_Rko', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер');
            }
            
            if (bank_IncomeDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('РБД', array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов разходен банков документ');
            }
            
            // Ако експедирането е на момента се добавя бутон за нова фактура
            if (deals_Helper::showInvoiceBtn($rec->threadId) && purchase_Invoices::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('Вх. фактура', array('purchase_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/invoice.png,title=Създаване на входяща фактура,order=9.9993');
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->state == 'draft') {
            
            // Ако има въведена банкова сметка, която я няма в системата я вкарваме
            if (!empty($rec->bankAccountId)) {
                if(bank_Accounts::add($rec->bankAccountId, currency_Currencies::getIdByCode($rec->currencyId), $rec->contragentClassId, $rec->contragentId)){
                    core_Statuses::newStatus('Добавена е нова сметка на контрагента|*!');
                }
            }
        }
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
                    unset($allowedPaymentOperations['case2supplierAdvance'],
                        $allowedPaymentOperations['bank2supplierAdvance'],
                        $allowedPaymentOperations['supplierAdvance2case'],
                        $allowedPaymentOperations['supplierAdvance2bank'],
                        $allowedPaymentOperations['supplierAdvance2caseRet'],
                        $allowedPaymentOperations['supplierAdvance2bankRet']
                    );
                }
            }
        }
        
        return $allowedPaymentOperations;
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
        
        // Извличаме продуктите на покупката
        $dQuery = purchase_PurchasesDetails::getQuery();
        $dQuery->where("#requestId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        $detailRecs = $dQuery->fetchAll();
        
        // Ако платежния метод няма авансова част, авансовите операции
        // не са позволени за платежните документи
        $downPayment = null;
        if (cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)) {
            
            // Колко е очакваното авансово плащане
            $downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
        }
        
        // Кои са позволените операции за последващите платежни документи
        $result->set('allowedPaymentOperations', $this->getPaymentOperations($rec));
        $result->set('allowedShipmentOperations', $this->getShipmentOperations($rec));
        $result->set('involvedContragents', array((object) array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId)));

        $deliveryTime = !empty($rec->deliveryTermTime) ? (dt::addSecs($rec->deliveryTermTime, $rec->valior, false) . " " . trans_Setup::get('END_WORK_TIME') . ":00") : $rec->deliveryTime;
        $result->setIfNot('deliveryTime', $deliveryTime);

        $result->setIfNot('amount', $rec->amountDeal);
        $result->setIfNot('currency', $rec->currencyId);
        $result->setIfNot('rate', $rec->currencyRate);
        $result->setIfNot('vatType', $rec->chargeVat);
        $result->setIfNot('agreedValior', $rec->valior);
        $result->setIfNot('deliveryLocation', $rec->deliveryLocationId);
        $result->setIfNot('deliveryTerm', $rec->deliveryTermId);
        $result->setIfNot('storeId', $rec->shipmentStoreId);
        $result->setIfNot('paymentMethodId', $rec->paymentMethodId);
        $result->setIfNot('paymentType', $rec->paymentType);
        $result->setIfNot('caseId', $rec->caseId);
        $result->setIfNot('bankAccountId', bank_Accounts::fetchField(array("#iban = '[#1#]'", $rec->bankAccountId), 'id'));
        
        purchase_transaction_Purchase::clearCache();
        $entries = purchase_transaction_Purchase::getEntries($rec->id);
        
        $deliveredAmount = purchase_transaction_Purchase::getDeliveryAmount($entries, $rec->id);
        $paidAmount = purchase_transaction_Purchase::getPaidAmount($entries, $rec);
        
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', purchase_transaction_Purchase::getDownpayment($entries));
        $result->set('amountPaid', $paidAmount);
        $result->set('deliveryAmount', $deliveredAmount);
        $result->set('blAmount', purchase_transaction_Purchase::getBlAmount($entries, $rec->id));
        
        // Опитваме се да намерим очакваното плащане
        $expectedPayment = null;
        if ($deliveredAmount > $paidAmount) {
            
            // Ако доставеното > платено това е разликата
            $expectedPayment = $deliveredAmount - $paidAmount;
        } else {
            
            // В краен случай това е очаквания аванс от метода на плащане
            $expectedPayment = $downPayment;
        }
        
        // Ако има очаквано плащане, записваме го
        if ($expectedPayment) {
            if (empty($deliveredAmount)) {
                $expectedPayment = $expectedPayment - $paidAmount;
            }
            
            if ($expectedPayment > 0) {
                $result->set('expectedPayment', $expectedPayment);
            }
        }
        
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        
        // Дефолтните платежни операции са плащания към доставчик
        $result->set('defaultCaseOperation', 'case2supplier');
        $result->set('defaultBankOperation', 'bank2supplier');
        
        // Ако се очаква авансово плащане и платения аванс е под 80% от аванса,
        // очакваме още да се плаща по аванаса
        if ($agreedDp) {
            if (empty($actualDp) || $actualDp < $agreedDp * 0.8) {
                $result->set('defaultCaseOperation', 'case2supplierAdvance');
                $result->set('defaultBankOperation', 'bank2supplierAdvance');
            }
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        $detailClassId = purchase_PurchasesDetails::getClassId();
        $agreed = array();
        $agreed2 = array();
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            foreach (array('productId', 'packagingId', 'discount', 'quantity', 'quantityInPack', 'price', 'notes', 'expenseItemId') as $fld) {
                $p->{$fld} = $dRec->{$fld};
            }

            if(Mode::is('isClosedWithDeal')){
                if(!empty($rec->reff)){
                    $p->notes = !empty($p->notes) ? ($p->notes . "\n" . "ref: {$rec->reff}") : "ref: {$rec->reff}";
                }
            }

            $p->expenseRecId = acc_CostAllocations::fetchField("#detailClassId = {$detailClassId} AND #detailRecId = {$dRec->id}");
            
            if (core_Packs::isInstalled('batch')) {
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$detailClassId}");
                $bQuery->where("#detailRecId = {$dRec->id}");
                $bQuery->where("#productId = {$dRec->productId}");
                $p->batches = $bQuery->fetchAll();
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
        $result->set('shippedProducts', purchase_transaction_Purchase::getShippedProducts($entries, $rec->id));
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate') {
            if (isset($rec)) {
                if (!$mvc->purchase_PurchasesDetails->fetch("#requestId = {$rec->id}")) {
                    $res = 'no_one';
                }
            } else {
                $res = 'no_one';
            }
        }
        
        if ($action == 'closewith' && isset($rec)) {
            if (purchase_PurchasesDetails::fetch("#requestId = {$rec->id}")) {
                $res = 'no_one';
            } elseif (!haveRole('purchase,ceo', $userId)) {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    protected function setCron(&$res)
    {
        // Крон метод за затваряне на остарели покупки
        $rec = new stdClass();
        $rec->systemId = 'Close purchases';
        $rec->description = 'Затваряне на приключените покупки';
        $rec->controller = 'purchase_Purchases';
        $rec->action = 'CloseOldPurchases';
        $rec->period = 180;
        $rec->offset = mt_rand(0, 30);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
        
        // Проверка по крон дали покупката е просрочена
        $rec2 = new stdClass();
        $rec2->systemId = 'IsPurchaseOverdue';
        $rec2->description = 'Проверяване на плащанията по покупките';
        $rec2->controller = 'purchase_Purchases';
        $rec2->action = 'CheckPurchasePayments';
        $rec2->period = 60;
        $rec2->offset = mt_rand(0, 30);
        $rec2->isRandOffset = true;
        $rec2->delay = 0;
        $rec2->timeLimit = 300;
        $res .= core_Cron::addOnce($rec2);
    }
    
    
    /**
     * Проверява дали покупки е просрочена или платени
     */
    public function cron_CheckPurchasePayments()
    {
        core_App::setTimeLimit(300);
        $overdueDelay = purchase_Setup::get('OVERDUE_CHECK_DELAY');
        $this->checkPayments($overdueDelay);

        // Изпращане на нотификации, за нефактурирани покупки
        $lateTime = purchase_Setup::get('NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS');
        $this->sendNotificationIfInvoiceIsTooLate($lateTime);
    }
    
    
    /**
     * Приключва всички приключени покупки
     */
    public function cron_CloseOldPurchases()
    {
        $conf = core_Packs::getConfig('purchase');
        $olderThan = $conf->PURCHASE_CLOSE_OLDER_THAN;
        $limit = $conf->PURCHASE_CLOSE_OLDER_NUM;
        $ClosedDeals = cls::get('purchase_ClosedDeals');
        
        $this->closeOldDeals($olderThan, $ClosedDeals, $limit);
    }
    
    
    /**
     * Зарежда шаблоните на покупката в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Договор за покупка', 'content' => 'purchase/tpl/purchases/Purchase.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/PurchaseNarrow.shtml');
        $tplArr[] = array('name' => 'Договор за покупка на услуга', 'content' => 'purchase/tpl/purchases/Service.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/ServiceNarrow.shtml');
        $tplArr[] = array('name' => 'Purchase contract', 'content' => 'purchase/tpl/purchases/PurchaseEN.shtml', 'lang' => 'en', 'narrowContent' => 'purchase/tpl/purchases/PurchaseNarrowEN.shtml');
        $tplArr[] = array('name' => 'Purchase of service contract', 'content' => 'purchase/tpl/purchases/ServiceEN.shtml', 'lang' => 'en', 'oldName' => 'Purchase of Service contract', 'narrowContent' => 'purchase/tpl/purchases/ServiceNarrowEN.shtml');
        $tplArr[] = array('name' => 'Заявка за транспорт', 'content' => 'purchase/tpl/purchases/Transport.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/purchases/TransportNarrow.shtml');
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        // Изкарваме езика на шаблона от сесията за да се рендира статистиката с езика на интерфейса
        core_Lg::pop();
        $statisticTpl = getTplFromFile('purchase/tpl/PurchaseStatisticLayout.shtml');
        $tpl->replace($statisticTpl, 'STATISTIC_BAR');
        
        // Отново вкарваме езика на шаблона в сесията
        core_Lg::push($data->rec->tplLang);
    }
    
    
    /**
     * След вербализиране на записа
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-single'])) {
            if ($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'commonConditionPur')) {
                $row->commonCondition = cls::get('type_Url')->toVerbal($cond);
            }
        } else if (isset($fields['-list']) && doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $row->title = "<b>" . $row->title . "</b>";
            $row->title .= "  «  " . $row->folderId;
        }
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
        $accounts = ($option == 'storable') ? '321' : '321,60201';

        $products = array();
        $entries = purchase_transaction_Purchase::getEntries($rec->id);
        $shipped = purchase_transaction_Purchase::getShippedProducts($entries, $rec->id, $accounts, true, true, true);
        
        $contQuery = doc_Containers::getQuery();
        $contQuery->where("#threadId = {$rec->threadId} AND #state = 'active'");
        $contQuery->show('id');
        $containersInThread = arr::extractValuesFromArray($contQuery->fetchAll(), 'id');
        
        $aQuery = acc_CostAllocations::getQuery();
        $aQuery->in("containerId", $containersInThread);
        $aQuery->show('productsData,expenseItemId');
        $allocatedRecs = $aQuery->fetchAll();
        
        if (countR($shipped)) {
            foreach ($shipped as $ship) {
                if($ship->quantity <= 0) continue;

                unset($ship->price);
                $ship->name = cat_Products::getTitleById($ship->productId, false);
                
                if(is_array($ship->expenseItems)){
                    foreach ($ship->expenseItems as $expenseId => $expenseObj){
                        
                        $allocatedArr = array();
                        array_walk($allocatedRecs, function($a) use (&$allocatedArr, $expenseId){
                            if($a->expenseItemId == $expenseId){
                                if(is_array($a->productsData)){
                                    foreach ($a->productsData as $pData){
                                        if(!array_key_exists($pData->productId, $allocatedArr)){
                                            $allocatedArr[$pData->productId] = (object)array('productId' => $pData->productId, 'amount' => 0, 'quantity' => 0, 'transportWeight' => 0, 'transportVolume' => 0, 'inStores' => array());
                                        }
                                        $allocatedArr[$pData->productId]->amount += $pData->amount;
                                        $allocatedArr[$pData->productId]->quantity += $pData->quantity;
                                        $allocatedArr[$pData->productId]->transportWeight += $pData->transportWeight;
                                        $allocatedArr[$pData->productId]->transportVolume += $pData->transportVolume;
                                        if(is_array($pData->inStores)){
                                            $allocatedArr[$pData->productId]->inStores += $pData->inStores;
                                        }
                                    }
                                }
                            }
                        });
                        
                        $ship->expenseItems[$expenseId]['allocatedToProducts'] = $allocatedArr;
                    }
                } 
                
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
            $action = type_Set::toArray($form->rec->action);
            if (isset($action['ship'])) {
                $dQuery = purchase_PurchasesDetails::getQuery();
                $dQuery->where("#requestId = {$rec->id}");
                $dQuery->show('productId');
                
                $productCheck = deals_Helper::checkProductForErrors(arr::extractValuesFromArray($dQuery->fetchAll(), 'productId'), 'canBuy');
                if($productCheck['metasError']){
                    $warning1 = "Артикулите|*: " . implode(', ', $productCheck['metasError']) . " |трябва да са продаваеми|*!";
                    $form->setError('action', $warning1);
                }
            }
        }
    }


    /**
     * Дефолтна стойност на полето за банкова сметка
     *
     * @param $rec
     * @return mixed|void|null
     */
    public function getDefaultBankAccountId($rec)
    {
        $bankAccounts = array();

        // Намиране на последните б. сметки
        foreach (array('lastDocUser', 'lastDoc') as $strat){
            $foundAccId = cond_plg_DefaultValues::getDefValueByStrategy($this, $rec, 'bankAccountId', $strat);
            if(!empty($foundAccId)){
                $bankAccounts[$foundAccId] = $foundAccId;
            }
        }

        // Връща се последната, която не е премахната
        foreach ($bankAccounts as $bankAccountId){
            $bAccId = bank_Accounts::fetchField(array("#iban = '[#1#]'", $bankAccountId), 'id');
            if($bAccId) return $bankAccountId;
        }

        return null;
    }


    /**
     * Кой е дефолтния търговец по продажбата
     *
     * @param stdClass $rec   - папка
     * @return int|NULL $dealerId - ид на търговец
     */
    public static function getDefaultDealerId($rec)
    {
        $setDefaultDealerId = purchase_Setup::get('SET_DEFAULT_DEALER_ID');
        if($setDefaultDealerId != 'yes') return null;

        $dealerId = cond_plg_DefaultValues::getFromLastDocument(cls::get(get_called_class()), $rec->folderId, 'dealerId', true);
        if (core_Users::haveRole('purchase', $dealerId)) return $dealerId;
    }
}
