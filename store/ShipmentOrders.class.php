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
 * @copyright 2006 - 2018 Experta OOD
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
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_ShipmentOrder, bgerp_DealIntf,trans_LogisticDataIntf,label_SequenceIntf=store_iface_ShipmentLabelImpl,deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter,deals_plg_SaveValiorOnActivation,store_Wrapper,purchase_plg_ExtractPurchasesData, sales_plg_CalcPriceDelta, plg_Sorting,store_plg_Request,acc_plg_ForceExpenceAllocation, acc_plg_Contable, cond_plg_DefaultValues,
                    plg_Clone,doc_DocumentPlg, plg_Printing, trans_plg_LinesPlugin, acc_plg_DocumentSummary, doc_plg_TplManager,deals_plg_SelectInvoice,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, doc_SharablePlg,deals_plg_SetTermDate,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, folderId, currencyId, amountDelivered, amountDeliveredVat, weight, volume,lineId, createdOn, createdBy';
    
    
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
    public $printAsClientLayaoutFile = 'store/tpl/SingleLayoutPackagingListClient.shtml';
    
    
    /**
     * Кой може да печата като клиент
     */
    public $canAsclient = 'ceo,store,sales,purchase';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ShipmentOrderDetails';
    
    
    /**
     * Шаблон за изглед при рендиране в транспортна линия
     */
    public $layoutFileInLine = 'store/tpl/ShortShipmentOrder.shtml';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('template' => 'customMethod|lastDocUser|lastDoc|lastDocSameCountry|defMethod');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDocFields($this);
        
        $this->FLD('responsible', 'varchar', 'caption=Получил,after=deliveryTime');
        $this->FLD('company', 'varchar', 'caption=Адрес за доставка->Фирма');
        $this->FLD('person', 'varchar', 'caption=Адрес за доставка->Име, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адрес за доставка->Тел., changable, class=contactData');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава, class=contactData');
        $this->FLD('pCode', 'varchar', 'caption=Адрес за доставка->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Адрес за доставка->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адрес за доставка->Адрес, changable, class=contactData');
        $this->FLD('storeReadiness', 'percent', 'input=none,caption=Готовност на склада');
        $this->setField('deliveryTime', 'caption=Натоварване');
        
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Метод по подразбиране за намиране на дефолт шаблона
     * 
     * @param stdClass $rec
     * @see cond_plg_DefaultValues
     */
    public function getCustomDefaultTemplate($rec)
    {
        if(core_Packs::isInstalled('eshop')){
            if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
                if($firstDocument->isInstanceOf('sales_Sales')){
                    if($c = eshop_Carts::fetchField("#saleId = {$firstDocument->that}")){
                        
                        $templateId = doc_TplManager::fetchField("#name = 'Експедиционно нареждане с цени (Онлайн поръчка)' AND #docClassId = {$this->getClassId()}");
                        
                        return $templateId;
                    }
                }
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if (!isset($data->form->rec->id)) {
            expect($origin = static::getOrigin($data->form->rec), $data->form->rec);
            if ($origin->isInstanceOf('sales_Sales')) {
                $data->form->FNC('importProducts', 'enum(notshipped=Неекспедирани (Всички),stocked=Неекспедирани и налични,notshippedstorable=Неекспедирани (Складируеми),notshippedservices=Неекспедирани (Услуги),services=Услуги (Всички),all=Всички)', 'caption=Вкарване от продажбата->Артикули, input,before=sharedUsers');
            }
        }
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        $tpl->append(sbf('img/16/toggle1.png', "'"), 'iconPlus');
        if ($data->rec->country) {
            $deliveryAddress = "{$data->row->country} <br/> {$data->row->pCode} {$data->row->place} <br /> {$data->row->address}";
            $inlineDeliveryAddress = "{$data->row->country},  {$data->row->pCode} {$data->row->place}, {$data->row->address}";
        } else {
            $deliveryAddress = $data->row->contragentAddress;
        }
        
        core_Lg::push($data->rec->tplLang);
        $deliveryAddress = core_Lg::transliterate($deliveryAddress);
        
        $tpl->replace($deliveryAddress, 'deliveryAddress');
        $tpl->replace($inlineDeliveryAddress, 'inlineDeliveryAddress');
        
        core_Lg::pop();
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
        core_Lg::push($rec->tplLang);
        $row->deliveryTo = '';
        if ($row->country) {
            $row->deliveryTo .= $row->country;
        }
        
        if ($row->pCode) {
            $row->deliveryTo .= (($row->deliveryTo) ? ", " : "") . $row->pCode;
        }
        
        if ($row->pCode) {
            $row->deliveryTo .= ' ' . core_Lg::transliterate($row->place);
        }
        
        foreach (array('address', 'company', 'person', 'tel') as $fld) {
            if (!empty($rec->{$fld})) {
                if ($fld == 'address') {
                    $row->{$fld} = core_Lg::transliterate($row->{$fld});
                } elseif ($fld == 'tel') {
                    if (callcenter_Talks::haveRightFor('list')) {
                        $row->{$fld} = ht::createLink($rec->{$fld}, array('callcenter_Talks', 'list', 'number' => $rec->{$fld}));
                    }
                }
                
                $row->deliveryTo .= ", {$row->{$fld}}";
            }
        }
        
        if (isset($rec->locationId)) {
            $row->locationId = crm_Locations::getHyperLink($rec->locationId);
        }
        
        // Кой е съставителя на документа
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
            
            if($rec->state != 'pending'){
                unset($row->storeReadiness);
            } else {
                $row->storeReadiness = isset($row->storeReadiness) ? $row->storeReadiness : "<b class='quiet'>N/A</b>";
            }
            
            if(Mode::isReadOnly()){
                unset($row->storeReadiness, $row->zoneReadiness);
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
            
            if ($rec->locationId) {
                foreach (array('company','person','tel','country','pCode','place','address',) as $del) {
                    if ($rec->{$del}) {
                        $form->setError("locationId,{$del}", 'Не може да има избрана локация и въведени адресни данни');
                        break;
                    }
                }
            }
            
            if ((!empty($rec->tel) || !empty($rec->country) || !empty($rec->pCode) || !empty($rec->place) || !empty($rec->address)) && (empty($rec->tel) || empty($rec->country) || empty($rec->pCode) || empty($rec->place) || empty($rec->address))) {
                $form->setError('tel,country,pCode,place,address', 'Трябва или да са попълнени всички полета за адрес или нито едно');
            }
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
    public function getDefaultEmailBody_($id, $forward = false)
    {
        $rec = $this->fetchRec($id);
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля запознайте се с нашето експедиционно нареждане') . ': #[#handle#]');
        $tpl->replace($handle, 'handle');
       
        if($rec->isReverse == 'no'){
            
            // Кои са фактурите в нишката
            $invoiceArr = deals_Helper::getInvoicesInThread($rec->threadId);
            if(countR($invoiceArr)){
                $dQuery = store_ShipmentOrderDetails::getQuery();
                $dQuery->where("#shipmentId = {$rec->id}");
                $dQuery->show('productId');
                $products = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');
                
                $invoiceArr = array_keys($invoiceArr);
                foreach ($invoiceArr as $invoiceContainerId){
                    $InvoiceRef = doc_Containers::getDocument($invoiceContainerId);
                    $InvoiceDetail = cls::get($InvoiceRef->mainDetail);
                    $iQuery = $InvoiceDetail->getQuery();
                    $iQuery->where("#{$InvoiceDetail->masterKey} = {$InvoiceRef->that}");
                    $iQuery->show('productId');
                    $invoiceProducts = arr::extractValuesFromArray($iQuery->fetchAll(), 'productId');
                    
                    // Ако има фактура със същите артикули, като ЕН-то ще се покаже към имейла
                    $diff1 = countR(array_diff_key($products, $invoiceProducts));
                    $diff2 = countR(array_diff_key($invoiceProducts, $products));
                   
                    if(empty($diff1) && empty($diff2)){
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
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Експедиционно нареждане с декларация',
            'content' => 'store/tpl/SingleLayoutShipmentOrderDec.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderDecNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Packing list with Declaration',
            'content' => 'store/tpl/SingleLayoutPackagingListDec.shtml', 'lang' => 'en', 'oldName' => 'Packaging list with Declaration', 'narrowContent' => 'store/tpl/SingleLayoutPackagingListDecNarrow.shtml',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        $tplArr[] = array('name' => 'Експедиционно нареждане с цени в евро',
            'content' => 'store/tpl/SingleLayoutShipmentOrderEuro.shtml', 'lang' => 'bg',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        
        $tplArr[] = array('name' => 'Packing list за митница',
            'content' => 'store/tpl/SingleLayoutPackagingListGrouped.shtml', 'lang' => 'en',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
        
        $tplArr[] = array('name' => 'Експедиционно нареждане с цени (Онлайн поръчка)',
            'content' => 'store/tpl/SingleLayoutShipmentOrderPricesOnline.shtml', 'lang' => 'bg',
            'toggleFields' => array('masterFld' => null, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,packPrice,discount,amount'));
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec - ид или запис на документ
     *
     * @return array $data - логистичните данни
     *
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма
     *   	string|NULL   ['toPerson']     - лице
     *      string|NULL   ['toPersonPhones'] - телефон на лицето
     *      string|NULL   ['instructions'] - инструкции
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     *		varchar|NULL  ['ourReff']      - наш реф
     * 		double|NULL   ['totalWeight']  - общо тегло
     * 		double|NULL   ['totalVolume']  - общ обем
     */
    public function getLogisticData($rec)
    {
        $rec = $this->fetchRec($rec);
        $res = parent::getLogisticData($rec);
        
        // Данните за разтоварване от ЕН-то са с приоритет
        if (!empty($rec->country) || !empty($rec->pCode) || !empty($rec->place) || !empty($rec->address)) {
            $res['toCountry'] = !empty($rec->country) ? drdata_Countries::fetchField($rec->country, 'commonName') : null;
            $res['toPCode'] = !empty($rec->pCode) ? $rec->pCode : null;
            $res['toPlace'] = !empty($rec->place) ? $rec->place : null;
            $res['toAddress'] = !empty($rec->address) ? $rec->address : null;
            
            $res['toCompany'] = !empty($rec->company) ? $rec->company : $res['toCompany'];
            $res['toPerson'] = !empty($rec->person) ? $rec->person : $res['toPerson'];
            $res['toPersonPhones'] = !empty($rec->tel) ? $rec->tel : $res['toPersonPhones'];
            
        } elseif(empty($rec->locationId) && $rec->isReverse == 'no'){
            if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
                $firstDocumentLogisticData = $firstDocument->getLogisticData();
                $res['toCountry'] = $firstDocumentLogisticData['toCountry'];
                $res['toPCode'] = $firstDocumentLogisticData['toPCode'];
                $res['toPlace'] = $firstDocumentLogisticData['toPlace'];
                $res['toAddress'] = $firstDocumentLogisticData['toAddress'];
                $res['instructions'] = $firstDocumentLogisticData['instructions'];
                $res['toCompany'] = $firstDocumentLogisticData['toCompany'];
                $res['toPerson'] = $firstDocumentLogisticData['toPerson'];
                $res['toPersonPhones'] = $firstDocumentLogisticData['toPersonPhones'];
            }
        }
        
        unset($res['deliveryTime']);
        $res['loadingTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : $rec->valior . ' ' . bgerp_Setup::get('START_OF_WORKING_DAY');
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'asclient') && $rec) {
            if (!trim($rec->company) && !trim($rec->person) && !$rec->country) {
                $requiredRoles = 'no_one';
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
            if ($rec->state == 'draft') {
                
                // Ако има проформа към протокола, правим линк към нея, иначе бутон за създаване на нова
                if ($iRec = sales_Proformas::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")) {
                    if (sales_Proformas::haveRightFor('single', $iRec)) {
                        $arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
                        $data->toolbar->addBtn("Проформа|* {$arrow}", array('sales_Proformas', 'single', $iRec->id, 'ret_url' => true), 'title=Отваряне на проформа фактура издадена към експедиционното нареждането,ef_icon=img/16/proforma.png');
                    }
                } else {
                    if (sales_Proformas::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))) {
                        $data->toolbar->addBtn('Проформа', array('sales_Proformas', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на проформа фактура към експедиционното нареждане,ef_icon=img/16/proforma.png');
                    }
                }
            }
            
            if (deals_Helper::showInvoiceBtn($rec->threadId) && in_array($rec->state, array('draft', 'active', 'pending'))) {
                if (sales_Invoices::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))) {
                    $data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на фактура към експедиционното нареждане,ef_icon=img/16/invoice.png,row=2');
                }
            }
        }
        
        // Бутони за редакция и добавяне на ЧМР-та
        if (in_array($rec->state, array('active', 'pending'))) {
            if ($cmrId = trans_Cmrs::fetchField("#originId = {$rec->containerId} AND #state != 'rejected'")) {
                if (trans_Cmrs::haveRightFor('single', $cmrId)) {
                    $data->toolbar->addBtn('ЧМР', array('trans_Cmrs', 'single', $cmrId, 'ret_url' => true), "title=Преглед на|* #CMR{$cmrId},ef_icon=img/16/lorry_go.png");
                }
            } elseif (trans_Cmrs::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
                
                // Само ако условието на доставка позволява ЧМР да се добавя към документа
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                $deliveryTermId = $firstDoc->fetchField('deliveryTermId');
                
                if ((isset($deliveryTermId) && strpos(cond_DeliveryTerms::fetchField($deliveryTermId, 'properties'), 'cmr') !== FALSE) || trans_Setup::get('CMR_SHOW_BTN') == 'yes') {
                    $data->toolbar->addBtn('ЧМР', array('trans_Cmrs', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'title=Създаване на ЧМР към експедиционното нареждане,ef_icon=img/16/lorry_add.png');
                }
            }
        }
        
        if (in_array($rec->state, array('active', 'pending')) && $rec->isReverse == 'no') {
             if(cash_Pko::haveRightFor('add', (object)array('originId' => $rec->containerId, 'threadId' => $rec->threadId))){
                 $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов документ');
             }
        }
    }
    
    
    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int    $id         - ид
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
            $data->listFields = 'deliveryTime,valior, title=Документ, currencyId, amountDelivered, amountDeliveredVat, weight, volume,lineId';
        }
    }
    
    
    /**
     * След извличане на името на документа за показване в RichText-а
     */
    protected static function on_AfterGetDocNameInRichtext($mvc, &$docName, $id)
    {
        $indicator = deals_Helper::getShipmentDocumentPendingIndicator($mvc, $id);
        if(isset($indicator)){
            if($docName instanceof core_ET){
                $docName->append($indicator);
            } else {
                $docName .= $indicator;
            }
        }
    }
    
    
    /**
     * Връща линк към документа
     */
    protected function on_AfterGetLink($mvc, &$link, $id, $maxLength = false, $attr = array())
    {
        
        $indicator = deals_Helper::getShipmentDocumentPendingIndicator($mvc, $id);
        
        if(isset($indicator)){
            if($link instanceof core_ET){
                $link->append($indicator);
                $link->removeBlocks();
                $link->removePlaces();
            } else {
                $link .= $indicator;
            }
        }
    }
}
