<?php


/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания. Само складируеми продукти могат да се експедират
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ShipmentOrders extends store_DocumentMaster
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
    public $abbr = 'Exp';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, export_XmlExportIntf=store_iface_ShipmentOrderToXmlImpl,
                          acc_TransactionSourceIntf=store_transaction_ShipmentOrder, bgerp_DealIntf,trans_LogisticDataIntf,label_SequenceIntf=store_iface_ShipmentLabelImpl,deals_InvoiceSourceIntf, doc_ContragentDataIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, change_Plugin, store_plg_StoreFilter,deals_plg_SaveValiorOnActivation,store_Wrapper,purchase_plg_ExtractPurchasesData, sales_plg_CalcPriceDelta, plg_Sorting,store_plg_Request,acc_plg_ForceExpenceAllocation, acc_plg_Contable, cond_plg_DefaultValues,
                    plg_Clone,doc_DocumentPlg, plg_Printing, trans_plg_LinesPlugin, acc_plg_DocumentSummary, doc_plg_TplManager,deals_plg_SelectInvoicesToDocument,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, doc_SharablePlg,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'note';


    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     *
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, store';


    /**
     * Кой може да избира ф-ра по документа?
     */
    public $canSelectinvoice = 'cash, ceo, purchase, sales, acc, store';


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
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store,sales,purchase';


    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store,sales,purchase';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,acc';


    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';


    /**
     * Кои роли може да променят активно ЕН
     */
    public $canChangerec = 'ceo,store';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime, deliveryOn, valior, title=Документ, folderId, currencyId, amountDelivered, amountDeliveredVat, weight, volume,lineId, createdOn=Създаване, createdBy=Създал';


    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title=bottom';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId,locationId,company,person,tel,pCode,place,address,note';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/EN.png';


    /**
     * Детайла, на модела
     */
    public $details = 'store_ShipmentOrderDetails,store_DocumentPackagingDetail';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Експедиционно нареждане';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleStoreDocument.shtml';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.82|Търговия';


    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_ShipmentOrderDetails';


    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'delivery';


    /**
     * Показва броя на записите в лога за съответното действие в документа
     */
    public $showLogTimeInHead = 'Документът се връща в чернова=3';


    /**
     * Шаблон за печата като за клиент
     */
    public $printAsClientLayoutFile = 'store/tpl/SingleLayoutPackagingListClient.shtml';


    /**
     * Кой може да печата като клиент
     */
    public $canAsclient = 'ceo,store,sales,purchase';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ShipmentOrderDetails,store_DocumentPackagingDetail';


    /**
     * Шаблон за изглед при рендиране в транспортна линия
     */
    public $layoutFileInLine = 'store/tpl/ShortShipmentOrder.shtml';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('template' => 'lastDocUser|lastDoc|lastDocSameCountry|defMethod');


    /**
     * Огледален клас за обратната операция
     */
    public $reverseClassName = 'store_Receipts';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, modifiedOn, valior, readyOn, deliveryTime, shipmentOn, deliveryOn';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDocFields($this);
        $this->FLD('deliveryOn', 'datetime(requireTime)', 'input,caption=Доставка,after=deliveryTime');
        $this->FLD('responsible', 'varchar', 'caption=Получил,after=deliveryOn');
        $this->FLD('storeReadiness', 'percent', 'input=none,caption=Готовност на склада');
        $this->FLD('additionalConditions', 'blob(serialize, compress)', 'caption=Допълнително->Условия (Кеширани),input=none');
        $this->setField('deliveryTime', 'caption=Товарене');
        $this->setDbIndex('createdOn');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if (!isset($rec->id)) {
            expect($origin = static::getOrigin($rec), $rec);
            if ($origin->isInstanceOf('sales_Sales')) {
                $data->form->FNC('importProducts', 'enum(notshipped=Неекспедирани (Всички),stocked=Неекспедирани и налични,notshippedstorable=Неекспедирани (Складируеми),notshippedservices=Неекспедирани (Услуги),services=Услуги (Всички),all=Всички)', 'caption=Артикули, input,after=responsible');

            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        // Кой е съставителя на документа
        core_Lg::push($rec->tplLang);
        $row->username = transliterate(deals_Helper::getIssuer($rec->createdBy, $rec->activatedBy));

        if (isset($fields['-single'])) {
            $logisticData = $mvc->getLogisticData($rec);
            $logisticData['toCountry'] = ($rec->tplLang == 'bg') ? drdata_Countries::fetchField("#commonName = '{$logisticData['toCountry']}'", 'commonNameBg') : $logisticData['toCountry'];
            $logisticData['toPCode'] = core_Lg::transliterate($logisticData['toPCode']);
            $logisticData['toPlace'] = core_Lg::transliterate($logisticData['toPlace']);
            $logisticData['toAddress'] = core_Lg::transliterate($logisticData['toAddress']);
            $row->inlineDeliveryAddress = "{$logisticData['toCountry']}, {$logisticData['toPCode']} {$logisticData['toPlace']}, {$logisticData['toAddress']}";
            if (!Request::get('asClient')) {
                $row->inlineContragentAddress = $row->inlineDeliveryAddress;
            }
            $row->toCompany = $logisticData['toCompany'];

            if ($rec->state != 'pending') {
                unset($row->storeReadiness);
            } else {
                $row->storeReadiness = isset($row->storeReadiness) ? $row->storeReadiness : "<b class='quiet'>N/A</b>";
            }

            if (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf')) {
                unset($row->storeReadiness);
            }

            $conditions = $rec->additionalConditions;
            if (empty($conditions)) {
                if (in_array($rec->state, array('pending', 'draft'))) {
                    $condition = store_Stores::getDocumentConditionFor($rec->storeId, $mvc, $rec->tplLang);
                    if (!empty($condition)) {
                        if (!Mode::isReadOnly()) {
                            $condition = "<span style='color:blue'>{$condition}</span>";
                        }
                        $condition = ht::createHint($condition, 'Ще бъде записано при активиране');
                        $conditions = array($condition);
                    }
                }
            }

            if (is_array($conditions)) {
                foreach ($conditions as $cond) {
                    $row->note .= "\n" . $cond;
                }
            }
        }

        core_Lg::pop();
    }


    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            $dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
            $operations = $dealInfo->get('allowedShipmentOperations');
            $operation = $operations['delivery'];
            $rec->accountId = $operation['debit'];
            $rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        }
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @param int $id - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     * @see email_DocumentIntf
     *
     */
    public function getDefaultEmailBody_($id, $forward = false)
    {
        $rec = $this->fetchRec($id);
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля запознайте се с нашето експедиционно нареждане') . ': #[#handle#]');
        $tpl->replace($handle, 'handle');

        if ($rec->isReverse == 'no') {

            // Кои са фактурите в нишката
            $invoiceArr = deals_Helper::getInvoicesInThread($rec->threadId);
            if (countR($invoiceArr)) {
                $dQuery = store_ShipmentOrderDetails::getQuery();
                $dQuery->where("#shipmentId = {$rec->id}");
                $dQuery->show('productId');
                $products = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');

                $invoiceArr = array_keys($invoiceArr);
                foreach ($invoiceArr as $invoiceContainerId) {
                    $InvoiceRef = doc_Containers::getDocument($invoiceContainerId);
                    $InvoiceDetail = cls::get($InvoiceRef->mainDetail);
                    $iQuery = $InvoiceDetail->getQuery();
                    $iQuery->where("#{$InvoiceDetail->masterKey} = {$InvoiceRef->that}");
                    $iQuery->show('productId');
                    $invoiceProducts = arr::extractValuesFromArray($iQuery->fetchAll(), 'productId');

                    // Ако има фактура със същите артикули, като ЕН-то ще се покаже към имейла
                    $diff1 = countR(array_diff_key($products, $invoiceProducts));
                    $diff2 = countR(array_diff_key($invoiceProducts, $products));

                    if (empty($diff1) && empty($diff2)) {
                        $iTpl = new ET(tr("|*\n|Моля, запознайте се с приложената фактура") . ': #[#handle#]');
                        $iTpl->replace($InvoiceRef->getHandle(), 'handle');
                        $tpl->append($iTpl);
                        break;
                    }
                }
            }
        }

        return $tpl;
    }


    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Експедиционно нареждане',
            'content' => 'store/tpl/SingleLayoutShipmentOrder.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Експедиционно нареждане с цени',
            'content' => 'store/tpl/SingleLayoutShipmentOrderPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderPricesNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,packPrice,discount,amount'));
        $tplArr[] = array('name' => 'Packing list',
            'content' => 'store/tpl/SingleLayoutPackagingList.shtml', 'lang' => 'en', 'oldName' => 'Packaging list', 'narrowContent' => 'store/tpl/SingleLayoutPackagingListNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight'));
        $tplArr[] = array('name' => 'Експедиционно нареждане с декларация',
            'content' => 'store/tpl/SingleLayoutShipmentOrderDec.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderDecNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Packing list with Declaration',
            'content' => 'store/tpl/SingleLayoutPackagingListDec.shtml', 'lang' => 'en', 'oldName' => 'Packaging list with Declaration', 'narrowContent' => 'store/tpl/SingleLayoutPackagingListDecNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight'));
        $tplArr[] = array('name' => 'Експедиционно нареждане с цени в евро',
            'content' => 'store/tpl/SingleLayoutShipmentOrderEuro.shtml', 'lang' => 'bg',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));

        $tplArr[] = array('name' => 'Packing list за митница',
            'content' => 'store/tpl/SingleLayoutPackagingListGrouped.shtml', 'lang' => 'en',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight'));

        $res .= doc_TplManager::addOnce($this, $tplArr);
    }


    /**
     * Интерфейсен метод
     *
     * @param int $id
     *
     * @return object
     *
     * @see doc_ContragentDataIntf
     */
    public static function getContragentData($id)
    {
        $rec = self::fetchRec($id);

        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($firstDoc->isInstanceOf('sales_Sales')) {

            return $firstDoc->getContragentData($firstDoc->that);
        }
    }


    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec - ид или запис на документ
     * @return array      - логистичните данни
     *
     *        string(2)     ['fromCountry']         - международното име на английски на държавата за натоварване
     *        string|NULL   ['fromPCode']           - пощенски код на мястото за натоварване
     *        string|NULL   ['fromPlace']           - град за натоварване
     *        string|NULL   ['fromAddress']         - адрес за натоварване
     *    string|NULL   ['fromCompany']         - фирма
     *    string|NULL   ['fromPerson']          - лице
     *      string|NULL   ['fromLocationId']      - лице
     *      string|NULL   ['fromAddressInfo']     - особености
     *      string|NULL   ['fromAddressFeatures'] - особености на транспорта
     *        datetime|NULL ['loadingTime']         - дата на натоварване
     *        string(2)     ['toCountry']           - международното име на английски на държавата за разтоварване
     *        string|NULL   ['toPCode']             - пощенски код на мястото за разтоварване
     *        string|NULL   ['toPlace']             - град за разтоварване
     *    string|NULL   ['toAddress']           - адрес за разтоварване
     *    string|NULL   ['toCompany']           - фирма
     *    string|NULL   ['toPerson']            - лице
     *      string|NULL   ['toLocationId']        - лице
     *      string|NULL   ['toPersonPhones']      - телефон на лицето
     *      string|NULL   ['toAddressInfo']       - особености
     *      string|NULL   ['toAddressFeatures']   - особености на транспорта
     *      string|NULL   ['instructions']        - инструкции
     *        datetime|NULL ['deliveryTime']        - дата на разтоварване
     *        text|NULL      ['conditions']          - други условия
     *        varchar|NULL  ['ourReff']             - наш реф
     *        double|NULL   ['totalWeight']         - общо тегло
     *        double|NULL   ['totalVolume']         - общ обем
     */
    public function getLogisticData($rec)
    {
        $rec = $this->fetchRec($rec);
        $res = parent::getLogisticData($rec);

        unset($res['deliveryTime']);
        $res['loadingTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : ($rec->valior . ' ' . bgerp_Setup::get('START_OF_WORKING_DAY'));

        return $res;
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'asclient') && $rec) {
            if (!trim($rec->company) && !trim($rec->person) && !$rec->country) {
                $requiredRoles = 'no_one';
            }
        }

        if (in_array($action, array('add', 'pending', 'conto', 'clonerec')) && isset($rec) && $requiredRoles != 'no_one') {

            // Ако има финална доставка и ЕН не е коригираща - не може да се пускат нови
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if ($firstDoc->isInstanceOf('sales_Sales')) {
                $ignoreContainerId = ($action != 'clonerec') ? $rec->containerId : null;
                if (!deals_Helper::canHaveMoreDeliveries($rec->threadId, $ignoreContainerId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
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

            // Към чернова може да се генерират проформи, а към контиран фактури
            if (in_array($rec->state, array('draft', 'pending'))) {

                // Ако има проформа към протокола, правим линк към нея, иначе бутон за създаване на нова
                if ($iRec = sales_Proformas::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")) {
                    if (sales_Proformas::haveRightFor('single', $iRec)) {
                        $arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
                        $data->toolbar->addBtn("Проформа|* {$arrow}", array('sales_Proformas', 'single', $iRec->id, 'ret_url' => true), 'title=Отваряне на проформа фактура издадена към експедиционното нареждането,ef_icon=img/16/proforma.png');
                    }
                } else {
                    if (sales_Proformas::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))) {
                        $data->toolbar->addBtn('Проформа', array('sales_Proformas', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на проформа фактура към експедиционното нареждане,ef_icon=img/16/proforma.png');
                    }
                }
            }

            if (deals_Helper::showInvoiceBtn($rec->threadId) && in_array($rec->state, array('draft', 'active', 'pending'))) {
                if (sales_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))) {
                    $data->toolbar->addBtn('Нова фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на фактура към експедиционното нареждане,ef_icon=img/16/invoice.png,row=2');
                }

                // Ако има ф-ра на чернова показва се бутон за добавяне на артикулите към нея
                if (store_ShipmentOrderDetails::count("#shipmentId = {$rec->id}")) {
                    $iQuery = sales_Invoices::getQuery();
                    $iQuery->where("#threadId = {$rec->threadId} AND #state = 'draft' AND #type = 'invoice'");
                    $iQuery->show('id,additionalInfo');
                    while ($iRec = $iQuery->fetch()) {
                        $invWarning = '';
                        $handle = "#" . $mvc->getHandle($rec->id);
                        if (strpos($iRec->additionalInfo, $handle) !== false) {
                            $invWarning = 'Експедиционното нареждане вече е било добавено към фактурата. Наистина ли желаете да го добавите отново|*?';
                        }

                        if (sales_InvoiceDetails::haveRightFor('add', (object)array('invoiceId' => $iRec->id))) {
                            $data->toolbar->addBtn("Към|* " . sales_Invoices::getHandle($iRec->id), array('sales_InvoiceDetails', 'addFromShipmentDocument', "invoiceId" => $iRec->id, 'originId' => $rec->containerId, 'ret_url' => true), "title=Добавяне на артикулите към фактурата,ef_icon=img/16/add.png,row=2,warning={$invWarning}");
                        }
                    }
                }
            }
        }

        // Бутони за редакция и добавяне на ЧМР-та
        if (in_array($rec->state, array('active', 'pending'))) {
            $logisticData = $mvc->getLogisticData($rec->id);
            $countryId = drdata_Countries::getIdByName($logisticData['toCountry']);
            $bgId = drdata_Countries::getIdByName('Bulgaria');

            if (trans_Cmrs::haveRightFor('add', (object)array('originId' => $rec->containerId))) {

                // Само ако условието на доставка позволява ЧМР да се добавя към документа
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                $cmrRow = 2;
                if ($firstDoc->isInstanceOf('deals_DealMaster')) {
                    $deliveryTermId = $firstDoc->fetchField('deliveryTermId');
                    if ((isset($deliveryTermId) && strpos(cond_DeliveryTerms::fetchField($deliveryTermId, 'properties'), 'cmr') !== false) || trans_Setup::get('CMR_SHOW_BTN') == 'yes' || $countryId != $bgId) {
                        $cmrRow = 1;
                    }
                }

                $data->toolbar->addBtn('ЧМР', array('trans_Cmrs', 'add', 'originId' => $rec->containerId, 'ret_url' => true), "title=Създаване на ЧМР към експедиционното нареждане,ef_icon=img/16/passage.png,row={$cmrRow}");
            }

            if (trans_IntraCommunitySupplyConfirmations::haveRightFor('add', (object)array('originId' => $rec->containerId))) {
                $vodRowBtn = ($countryId == $bgId || !drdata_Countries::isEu($countryId)) ? 2 : 1;
                $data->toolbar->addBtn('ВОД', array('trans_IntraCommunitySupplyConfirmations', 'add', 'originId' => $rec->containerId, 'ret_url' => true), "ef_icon=img/16/document_accept.png,title=Създаване на ново потвърждение за ВОД,row={$vodRowBtn}");
            }
        }

        if (in_array($rec->state, array('active', 'pending')) && $rec->isReverse == 'no') {
            if (cash_Pko::haveRightFor('add', (object)array('originId' => $rec->containerId, 'threadId' => $rec->threadId))) {
                $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов документ');
            }
        }
    }


    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int $id - ид
     * @param string $isContable - какво е действието
     *
     * @return NULL|string - текста на предупреждението или NULL ако няма
     */
    public function getContoWarning_($id, $isContable)
    {
        $rec = $this->fetchRec($id);
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$id}");
        $dQuery->show('productId, quantity');

        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->storeId, $rec->state);

        return $warning;
    }


    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $data->listFields = 'deliveryTime, deliveryOn, valior, title=Документ, currencyId, amountDelivered, amountDeliveredVat, weight, volume,lineId';
        }
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    protected static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        $errorMsg = null;
        if (deals_Helper::hasProductsBellowMinPrice($mvc, $rec, $errorMsg) && $rec->isReverse !== 'yes') {
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }
    }


    /**
     * Връща информация за сумите по платежния документ
     *
     * @param mixed $id
     * @return object
     */
    public function getPaymentData($id)
    {
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $this->fetchRec($id, '*', false);
        }

        $amount = round($rec->amountDelivered / $rec->currencyRate, 2);

        return (object)array('amount' => $amount, 'currencyId' => currency_Currencies::getIdByCode($rec->currencyId));
    }


    /**
     * Дефолтна реализация на метода за връщане данните за търга
     */
    protected static function on_AfterGetAuctionData($mvc, &$res, $rec)
    {
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if (!$firstDoc->isInstanceOf('sales_Sales')) return;

        // Какви са детайлите на ЕН-то
        $tRecs = $details = array();
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            if (!array_key_exists($dRec->productId, $details)) {
                $details[$dRec->productId] = (object)array('productId' => $dRec->productId);
            }
            $details[$dRec->productId]->quantity += $dRec->quantity;
        }

        // Какъв е скрития транспорт в продажбата
        $tQuery = sales_TransportValues::getQuery();
        $tQuery->where("#docClassId = {$firstDoc->getClassId()} AND #docId = {$firstDoc->that}");
        $tQuery->EXT('productId', 'sales_SalesDetails', 'externalKey=recId');
        $tQuery->EXT('quantity', 'sales_SalesDetails', 'externalKey=recId');

        while ($tRec = $tQuery->fetch()) {
            if (!array_key_exists($tRec->productId, $tRecs)) {
                $tRecs[$tRec->productId] = (object)array('productId' => $tRec->productId);
            }
            $tRecs[$tRec->productId]->fee += $tRec->fee;
            $tRecs[$tRec->productId]->quantity += $tRec->quantity;
        }

        // Смята се колко е скрития транспорт за количествата от ЕН-то
        $hiddenTransport = 0;
        foreach ($details as $dRec1) {
            if (array_key_exists($dRec1->productId, $tRecs)) {
                $tRec = $tRecs[$dRec1->productId];
                if ($tRec->fee > 0) {
                    $singleFee = $tRec->fee / $tRec->quantity;
                    $hiddenTransport += $dRec1->quantity * $singleFee;
                }
            }
        }

        $hiddenTransport = round($hiddenTransport, 2);
        if (!empty($hiddenTransport)) {
            $res['hiddenTransport'] = $hiddenTransport;
        }
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        // Ако потребителя не е в група доставчици го включваме
        $rec = $mvc->fetchRec($rec);

        if (empty($rec->additionalConditions)) {
            $lang = isset($rec->tplLang) ? $rec->tplLang : doc_TplManager::fetchField($rec->template, 'lang');
            $condition = store_Stores::getDocumentConditionFor($rec->storeId, $mvc, $lang);
            $rec->additionalConditions = array($condition);
            $mvc->save_($rec, 'additionalConditions');
        }
    }


    /**
     * Коя е най-ранната дата на която са налични всички документи
     *
     * @param stdClass $rec
     * @param boolean $cache
     * @return date|null
     */
    public function getEarliestDateAllProductsAreAvailableInStore($rec, $cache = false)
    {
        $res = null;
        $rec = $this->fetchRec($rec);
        if ($cache) {
            $res = core_Cache::get($this->className, "earliestDateAllAvailable{$rec->containerId}");
        }

        if (!$cache || $res === false) {
            $products = deals_Helper::sumProductsByQuantity('store_ShipmentOrderDetails', $rec->id, true);
            $res = store_StockPlanning::getEarliestDateAllAreAvailable($rec->storeId, $products);
            core_Cache::set($this->className, "earliestDateAllAvailable{$rec->containerId}", $res, 10);
        }

        return $res;
    }


    /**
     * Kои са полетата за датите за експедирането
     *
     * @param mixed $rec - ид или запис
     * @param boolean $cache - дали да се използват кеширани данни
     * @return array $res    - масив с резултат
     */
    public function getShipmentDateFields($rec = null, $cache = false)
    {
        $res = array('readyOn' => array('caption' => 'Готовност', 'type' => 'date', 'readOnlyIfActive' => true, "input" => "input=hidden"),
            'deliveryTime' => array('caption' => 'Товарене', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => true, "input" => "input"),
            'shipmentOn' => array('caption' => 'Експедиране', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => false, "input" => "input=hidden"),
            'deliveryOn' => array('caption' => 'Доставка', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => false, "input" => "input"));

        if (isset($rec)) {
            $res['deliveryTime']['placeholder'] = $this->getDefaultLoadingDate($rec, $rec->deliveryOn, $cache);
            $res['readyOn']['placeholder'] = $this->getEarliestDateAllProductsAreAvailableInStore($rec, $cache);
            $res['shipmentOn']['placeholder'] = trans_Helper::calcShippedOnDate($rec->valior, $rec->lineId, $rec->activatedOn);
        }

        return $res;
    }


    /**
     * Коя е дефолтната дата за натоварване
     *
     * @param int|stdClass $id - ид или запис
     * @param datetime $deliveryDate - краен срок на доставка
     * @param boolean $cache - работен кеш
     * @return datetime              - датата на натоварване
     */
    function getDefaultLoadingDate($id, $deliveryDate = null, $cache = false)
    {
        $res = null;
        $rec = $this->fetchRec($id);
        if ($cache) {
            $res = core_Cache::get($this->className, "loadingDate{$rec->containerId}");
        }

        if (!$cache || $res === false) {
            // Кой е първия документ в нишката
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if ($firstDoc->isInstanceOf('sales_Sales')) {

                $firstRec = $firstDoc->fetch('deliveryTermId,deliveryCalcTransport,deliveryData,deliveryTermTime,valior,deliveryTime');
                if (empty($deliveryDate)) {
                    $deliveryDate = !empty($firstRec->deliveryTermTime) ? dt::addSecs($firstRec->deliveryTermTime, $firstRec->valior) : $firstRec->deliveryTime;
                }

                // Има ли калкулатор за транспорт
                if ($Calculator = cond_DeliveryTerms::getTransportCalculator($firstRec->deliveryTermId)) {

                    // Какви са логистичните данни на документа
                    $logisticData = $this->getLogisticData($rec);
                    setIfNot($logisticData['toPCode'], '');
                    $firstRec->deliveryData = is_array($firstRec->deliveryData) ? $firstRec->deliveryData : array();
                    $deliveryData = $firstRec->deliveryData + array('deliveryCountry' => drdata_Countries::getIdByName($logisticData['toCountry']), 'deliveryPCode' => $logisticData['toPCode']);

                    // Колко е най-големия срок на доставка
                    $maxDeliveryTime = $Calculator->getMaxDeliveryTime($firstRec->deliveryTermId, $deliveryData);
                    if (!empty($maxDeliveryTime)) {
                        $deliveryDate = dt::addSecs(-1 * $maxDeliveryTime, $deliveryDate);
                    }
                }
            }

            $preparationTime = store_Stores::getShipmentPreparationTime($rec->storeId);
            if(isset($deliveryDate)){
                $res = dt::addSecs(-1 * $preparationTime, $deliveryDate);
                core_Cache::set($this->className, "loadingDate{$rec->containerId}", $res, 10);
            }
        }

        // От така изчисления срок на доставка се приспадат и нужните за подготовка дни от склада
        return $res;
    }


    /**
     * За коя дата се заплануват наличностите
     *
     * @param stdClass $rec - запис
     * @return datetime     - дата, за която се заплануват наличностите
     */
    public function getPlannedQuantityDate_($rec)
    {
        // Ако има ръчно въведена дата на доставка, връща се тя
        if (!empty($rec->deliveryTime)) return $rec->deliveryTime;

        // Връща се първата намерена от: лайв изчислената, вальора, датата на активиране, датата на създаване
        $loadingDate = $this->getDefaultLoadingDate($rec, $rec->deliveryOn);
        setIfNot($loadingDate, $rec->valior, $rec->activatedOn, $rec->createdOn);

        return $loadingDate;
    }
}
