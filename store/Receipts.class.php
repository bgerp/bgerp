<?php


/**
 * Клас 'store_Receipts'
 *
 * Мениджър на Складовите разписки, Само складируеми продукти могат да се заприхождават в склада
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Receipts extends store_DocumentMaster
{
    /**
     * Заглавие
     */
    public $title = 'Складови разписки';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Sr';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleStoreDocument.shtml';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_Receipt, bgerp_DealIntf,trans_LogisticDataIntf,deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, store_Wrapper, sales_plg_CalcPriceDelta,store_plg_Request, plg_Sorting,purchase_plg_ExtractPurchasesData,acc_plg_ForceExpenceAllocation, acc_plg_Contable, cond_plg_DefaultValues,
                    plg_Clone,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, trans_plg_LinesPlugin, doc_plg_HidePrices, doc_SharablePlg,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning';
    
    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     *
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, store';


    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see store_StockPlanning
     */
    public $stockPlanningDirection = 'in';


    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, folderId, amountDelivered, weight, volume,lineId, createdOn, createdBy';
    
    
    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title=bottom';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId,storeId,note';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ReceiptDetails,store_DocumentPackagingDetail' ;
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Складова разписка';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/store-receipt.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.4|Логистика';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_ReceiptDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'delivery';
    
    
    /**
     * Показва броя на записите в лога за съответното действие в документа
     */
    public $showLogTimeInHead = 'Документът се връща в чернова=3';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ReceiptDetails';


    /**
     * Огледален клас за обратната операция
     */
    public $reverseClassName = 'store_ShipmentOrders';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, modifiedOn, valior, loadingOn, deliveryTime';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDocFields($this);
        $this->setField('storeId', 'caption=В склад');
        $this->FLD('loadingOn', 'datetime(requireTime)','caption=Натоварване,after=locationId');
        $this->setField('deliveryTime', 'caption=Разтоварване,after=loadingOn');
        $this->setField('prevShipment', 'caption=Адрес за натоварване->Избор');
        $this->setField('company', 'caption=Адрес за натоварване->Фирма');
        $this->setField('person', 'caption=Адрес за натоварване->Име');
        $this->setField('tel', 'caption=Адрес за натоварване->Тел');
        $this->setField('country', 'caption=Адрес за натоварване->Държава');
        $this->setField('pCode', 'caption=Адрес за натоварване->П. код');
        $this->setField('place', 'caption=Адрес за натоварване->Град/с');
        $this->setField('address', 'caption=Адрес за натоварване->Адрес');
        $this->setField('addressInfo', 'caption=Адрес за натоварване->Особености');
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            expect($origin = static::getOrigin($rec), $rec);
            $dealInfo = $origin->getAggregateDealInfo();
            
            $operations = $dealInfo->get('allowedShipmentOperations');
            $operation = $operations['stowage'];
            $rec->accountId = $operation['credit'];
            $rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $form->setField('locationId', 'caption=Обект от');
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareReceipts($data)
    {
        $data->receipts = parent::prepareLineDetail($data->masterData);
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderReceipts($data)
    {
        if (countR($data->receipts)) {
            $table = cls::get('core_TableView');
            $fields = 'rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,palletCount=Палети,collection=Инкасиране,address=@Адрес';
            $fields = core_TableView::filterEmptyColumns($data->shipmentOrders, $fields, 'collection,palletCount');
            
            return $table->get($data->receipts, $fields);
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля запознайте се с нашата складова разписка') . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Складова разписка',
            'content' => 'store/tpl/SingleLayoutReceipt.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutReceiptNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ReceiptDetails' => 'packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Складова разписка с цени',
            'content' => 'store/tpl/SingleLayoutReceiptPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutReceiptPricesNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ReceiptDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->isReverse == 'no') {
            if (deals_Helper::showInvoiceBtn($rec->threadId) && in_array($rec->state, array('active', 'pending', 'draft'))) {
                
                // Ако има фактура към протокола, правим линк към нея, иначе бутон за създаване на нова
                if ($iRec = purchase_Invoices::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")) {
                    if (purchase_Invoices::haveRightFor('single', $iRec)) {
                        $arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
                        $data->toolbar->addBtn("Вх. фактура|* {$arrow}", array('purchase_Invoices', 'single', $iRec->id, 'ret_url' => true), 'title=Отваряне на входящата фактура издадена към складова разписка,ef_icon=img/16/invoice.png');
                    }
                } else {
                    if (purchase_Invoices::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))) {
                        $data->toolbar->addBtn('Вх. фактура', array('purchase_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на входяща фактура към складова разписка,ef_icon=img/16/invoice.png,row=2');
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $data->listFields = 'deliveryTime,valior, title=Документ, amountDelivered, weight, volume,lineId';
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if(in_array($action, array('add', 'pending', 'conto', 'clonerec')) && isset($rec) && $requiredRoles != 'no_one'){

            // Ако има финална доставка и СР не е коригираща - не може да се пускат нови
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if($firstDoc->isInstanceOf('purchase_Purchases')){
                $ignoreContainerId =  ($action != 'clonerec') ? $rec->containerId : null;
                if(!deals_Helper::canHaveMoreDeliveries($rec->threadId, $ignoreContainerId)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-single'])){
            core_Lg::push($rec->tplLang);
            if(!empty($rec->deliveryTime)){
                $row->deliveryTimeCaption = ($rec->isReverse == 'no') ? tr('Разтоварване') : tr('Натоварване');
            }
            core_Lg::pop();
        }
    }


    /**
     * Kои са полетата за датите за експедирането
     *
     * @param mixed $rec     - ид или запис
     * @param boolean $cache - дали да се използват кеширани данни
     * @return array $res    - масив с резултат
     */
    public function getShipmentDateFields($rec = null, $cache = false)
    {
        $res = array('loadingOn'   => array('caption' => 'Товарене', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => false, "input" => "input"),
                     'deliveryTime' => array('caption' => 'Разтоварване', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => true, "input" => "input"),);

        return $res;
    }
}

