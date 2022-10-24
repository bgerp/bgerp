<?php


/**
 * Документ "Изходяща оферта"
 *
 * Мениджър на документи за Изходящи оферти
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Quotations extends deals_QuotationMaster
{


    /**
     * Дали да взема контрагент данните от последния документ в папката
     */
    public $getContragentDataFromLastDoc = false;


    /**
     * Заглавие
     */
    public $title = 'Изходящи оферти';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Q';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Sorting, sales_Wrapper, doc_plg_Close, doc_EmailCreatePlg, acc_plg_DocumentSummary, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, plg_Printing, doc_ActivatePlg, plg_Clone, bgerp_plg_Blank, cond_plg_DefaultValues,doc_plg_SelectFolder,plg_LastUsedKeys,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,sales';

    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/quotation.png';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canWrite = 'ceo,sales';

    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'sales_QuotationsDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Изходяща оферта';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.7|Търговия';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_QuotationsDetails';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, sales';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo, sales';
    
    
    /**
     * Кой  може да клонира системни записи
     */
    public $canClonesysdata = 'ceo, sales';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,sales';


    /**
     * Клас за сделка, който последва офертата
     */
    protected $dealClass = 'sales_Sales';


    /**
     * Кои полета да са нередактируеми, ако има вече детайли
     */
    protected $readOnlyFieldsIfHaveDetail = 'chargeVat,currencyRate,currencyId,deliveryTermId,deliveryPlaceId,deliveryAdress,deliveryCalcTransport';


    /**
     * Полета свързани с цени
     */
    public $priceFields = 'expectedTransportCost,visibleTransportCost,hiddenTransportCost,leftTransportCost';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setQuotationFields($this);
        $this->FLD('expectedTransportCost', 'double', 'input=none,caption=Очакван транспорт');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Плащане->Банкова с-ка,after=paymentMethodId');

        $this->FNC('row1', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row2', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row3', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->setField('paymentMethodId', 'salecondSysId=paymentMethodSale');
        $this->setField('chargeVat', 'salecondSysId=quotationChargeVat');
        $this->setField('deliveryTermId', 'salecondSysId=deliveryTermSale');
        $this->FLD('deliveryCalcTransport', 'enum(yes=Скрит транспорт,no=Явен транспорт)', 'input=none,caption=Доставка->Начисляване,after=deliveryTermId');

        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'after=validFor,caption=Допълнително->Цени,notChangeableByContractor');
        $this->FLD('others', 'text(rows=4)', 'caption=Допълнително->Условия');
    
        $this->setDbIndex('date');
        $this->setDbIndex('contragentClassId,contragentId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setOptions('priceListId', array('' => '') + price_Lists::getAccessibleOptions($rec->contragentClassId, $rec->contragentId));

        if (isset($rec->originId) && $data->action != 'clone' && empty($form->rec->id)) {
            
            // Ако офертата има ориджин
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->haveInterface('cat_ProductAccRegIntf')) {
                $form->setField('row1,row2,row3', 'input');
                $rec->productId = $origin->that;
                
                if($Driver = $origin->getDriver()){
                    $quantitiesArr = $Driver->getQuantitiesForQuotation($origin->getInstance(), $origin->fetch());
                    $form->setDefault('row1', $quantitiesArr[0]);
                    $form->setDefault('row2', $quantitiesArr[1]);
                    $form->setDefault('row3', $quantitiesArr[2]);
                }
            }
        }
        
        // Срок на валидност по подразбиране
        $form->setDefault('validFor', sales_Setup::get('DEFAULT_VALIDITY_OF_QUOTATION'));

        // Дефолтната ценова политика се показва като плейсхолдър
        if($listId = price_ListToCustomers::getListForCustomer($form->rec->contragentClassId, $form->rec->contragentId)){
            $form->setField("priceListId", "placeholder=" . price_Lists::getTitleById($listId));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Ако има проверка на к-та от запитването
            $errorFields2 = $errorFields = $allQuantities = array();
            $checArr = array('1' => $rec->row1, '2' => $rec->row2, '3' => $rec->row3);
            foreach ($checArr as $k => $v) {
                if (!empty($v)) {
                    $parts = type_ComplexType::getParts($v);
                    $rec->{"quantity{$k}"} = $parts['left'];
                    $rec->{"price{$k}"} = ($parts['right'] === '') ? null : $parts['right'];
                    
                    if ($moq = cat_Products::getMoq($rec->productId)) {
                        if (!empty($rec->{"quantity{$k}"}) && $rec->{"quantity{$k}"} < $moq) {
                            $errorFields2[] = "row{$k}";
                        }
                    }
                    
                    if (in_array($parts['left'], $allQuantities)) {
                        $errorFields[] = "row{$k}";
                    } else {
                        $allQuantities[] = $parts['left'];
                    }
                }
            }
            
            // Ако има повтарящи се полета
            if (countR($errorFields)) {
                $form->setError($errorFields, 'Количествата трябва да са различни');
            } elseif (countR($errorFields2)) {
                $moq = core_Type::getByName('double(smartRound)')->toVerbal($moq);
                $form->setError($errorFields2, "Минимално количество за поръчка|* <b>{$moq}</b>");
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->originId)) {
            
            // Намиране на ориджина
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin && cls::haveInterface('cat_ProductAccRegIntf', $origin->instance)) {
                $originRec = $origin->fetch('id,measureId');
                $vat = cat_Products::getVat($origin->that, $rec->date);
                
                // Ако в река има 1 от 3 к-ва
                foreach (range(1, 3) as $i) {
                    
                    // Ако има дефолтно количество
                    $quantity = $rec->{"quantity{$i}"};
                    $price = $rec->{"price{$i}"};
                    if (!$quantity) {
                        continue;
                    }
                    
                    // Прави се опит за добавянето на артикула към реда
                    try {
                        if (!empty($price)) {
                            $price = deals_Helper::getPurePrice($price, $vat, $rec->currencyRate, $rec->chargeVat);
                        }
                        sales_Quotations::addRow($rec->id, $originRec->id, $quantity, $originRec->measureId, $price);
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        
                        if (haveRole('debug')) {
                            $dump = $e->getDump();
                            core_Statuses::newStatus($dump[0], 'warning');
                        }
                    }
                }
                
                // Споделяме текущия потребител със нишката на заданието
                $cu = core_Users::getCurrent();
                doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
            }
        }
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());
        
        if (empty($rec->date)) {
            $row->date = ht::createHint('', 'Датата ще бъде записана при активиране');
        }
        
        if ($fields['-single']) {

            // Линк към от коя оферта е клонирано
            if(isset($rec->clonedFromId)){
                $row->clonedFromId = "#" . self::getHandle($rec->clonedFromId);
                if(!Mode::isReadOnly()){
                    $row->clonedFromId = ht::createLink($row->clonedFromId, self::getSingleUrlArray($rec->clonedFromId));
                }
            }

            if ($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'commonConditionSale')) {
                $row->commonConditionQuote = cls::get('type_Url')->toVerbal($cond);
            }
            
            $items = $mvc->getItems($rec->id, true, true);
            
            if (is_array($items)) {
                $row->transportCurrencyId = $row->currencyId;
                
                $hiddenTransportCost = sales_TransportValues::calcInDocument($mvc, $rec->id);
                $expectedTransportCost = $mvc->getExpectedTransportCost($rec);
                $visibleTransportCost = $mvc->getVisibleTransportCost($rec);
                
                $leftTransportCost = 0;
                sales_TransportValues::getVerbalTransportCost($row, $leftTransportCost, $hiddenTransportCost, $expectedTransportCost, $visibleTransportCost, $rec->currencyRate);
                
                // Ако има транспорт за начисляване
                if ($leftTransportCost > 0) {
                    
                    // Ако може да се добавят артикули в офертата
                    if (sales_QuotationsDetails::haveRightFor('add', (object) array('quotationId' => $rec->id))) {
                        
                        // Добавяне на линк, за добавяне на артикул 'транспорт' със цена зададената сума
                        $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                        $packPrice = $leftTransportCost * $rec->currencyRate;
                        
                        $url = array('sales_QuotationsDetails', 'add', 'quotationId' => $rec->id, 'productId' => $transportId, 'packPrice' => $packPrice, 'optional' => 'no','ret_url' => true);
                        $link = ht::createLink('Добавяне', $url, false, array('ef_icon' => 'img/16/lorry_go.png', 'style' => 'font-weight:normal;font-size: 0.8em', 'title' => 'Добавяне на допълнителен транспорт'));
                        $row->btnTransport = $link->getContent();
                    }
                }
            }
            
            if (isset($rec->deliveryTermId)) {
                $locationId = (!empty($rec->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;
                if (sales_TransportValues::getDeliveryTermError($rec->deliveryTermId, $rec->deliveryAdress, $rec->contragentClassId, $rec->contragentId, $locationId)) {
                   $row->deliveryError = tr('За транспортните разходи, моля свържете се с представител на фирмата');
                }
            }

            if (empty($rec->deliveryTime) && empty($rec->deliveryTermTime)) {
                $deliveryTermTime = $mvc->calcDeliveryTime($rec);
                if (isset($deliveryTermTime)) {
                    $deliveryTermTime = cls::get('type_Time')->toVerbal($deliveryTermTime);
                    $deliveryTermTime = "<span style='color:blue'>{$deliveryTermTime}</span>";
                    $row->deliveryTermTime = ht::createHint($deliveryTermTime, 'Времето за доставка се изчислява динамично възоснова мястото за доставка, артикулите в договора и нужното време за подготовка|*!');
                }
            }

            if (isset($rec->bankAccountId)) {
                $ownAccount = bank_OwnAccounts::getOwnAccountInfo($rec->bankAccountId);
                $row->bankAccountId = $ownAccount->iban;
                if(!Mode::isReadOnly()){
                    $row->bankAccountId = ht::createLink($ownAccount->iban, bank_OwnAccounts::getSingleUrlArray($rec->bankAccountId));
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Колко е сумата на очаквания транспорт.
     * Изчислява се само ако няма вариации в задължителните артикули
     *
     * @param stdClass $rec - запис на ред
     *
     * @return float $expectedTransport - очаквания транспорт без ддс в основна валута
     */
    private function getExpectedTransportCost($rec)
    {
        if(isset($rec->expectedTransportCost)) return $rec->expectedTransportCost;
        
        $expectedTransport = 0;
        
        // Ако няма калкулатор в условието на доставка, не се изчислява нищо
        $TransportCalc = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId);
        if (!is_object($TransportCalc)) {
            
            return $expectedTransport;
        }
        
        // Подготовка на заявката, взимат се само задължителните складируеми артикули
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$rec->id}");
        $query->where("#optional = 'no'");
        $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $query->where("#canStore = 'yes'");
        
        $products = $query->fetchAll();
        
        $locationId = null;
        if (isset($rec->deliveryPlaceId)) {
            $locationId = crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id');
        }
        $codeAndCountryArr = sales_TransportValues::getCodeAndCountryId($rec->contragentClassId, $rec->contragentId, $rec->pCode, $rec->contragentCountryId, $locationId ? $locationId : $rec->deliveryAdress);
        
        $ourCompany = crm_Companies::fetchOurCompany();
        $params = array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode'], 'fromCountry' => $ourCompany->country, 'fromPostalCode' => $ourCompany->pCode);
        
        // Изчисляване на общото тегло на офертата
        $total = sales_TransportValues::getTotalWeightAndVolume($TransportCalc, $products, $rec->deliveryTermId, $params);
        if($total == cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT) return cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT;
        
        // За всеки артикул се изчислява очаквания му транспорт
        foreach ($products as $p2) {
            $fee = sales_TransportValues::getTransportCost($rec->deliveryTermId, $p2->productId, $p2->packagingId, $p2->quantity, $total, $params);
            
            // Сумира се, ако е изчислен
            if (is_array($fee) && $fee['totalFee'] > 0) {
                $expectedTransport += $fee['totalFee'];
            }
        }
        
        // Кеширане на очаквания транспорт при нужда
        if(is_null($rec->expectedTransportCost) && in_array($rec->state, array('active', 'closed'))){
            $rec->expectedTransportCost = $expectedTransport;
            $this->save_($rec, 'expectedTransportCost');
        }
        
        // Връщане на очаквания транспорт
        return $expectedTransport;
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
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$rec->id}");
        $query->where("#optional = 'no'");
        
        return sales_TransportValues::getVisibleTransportCost($query);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $hasTransport = !empty($data->row->hiddenTransportCost) || !empty($data->row->expectedTransportCost) || !empty($data->row->visibleTransportCost);
        
        $isReadOnlyMode = Mode::isReadOnly();
        
        if ($isReadOnlyMode) {
            $tpl->removeBlock('header');
        }
        
        if ($hasTransport === false || $isReadOnlyMode || core_Users::haveRole('partner')) {
            $tpl->removeBlock('TRANSPORT_BAR');
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
         // Може да се създава към артикул само ако артикула е продаваем
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('cat_Products')) {
                $canSell = $origin->fetchField('canSell');
                if ($canSell == 'no') {
                    $res = 'no_one';
                }
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
    public function getDefaultEmailBody($id, $forward = false)
    {
        $rec = $this->fetchRec($id);
        $handle = $this->getHandle($id);
        $tpl = new core_ET(tr("Моля, запознайте се с нашата оферта|*: #[#handle#]."));
        $tpl->append($handle, 'handle');
        
        if($rec->chargeVat == 'separate'){
            $tpl->append("\n\n" . tr("Обърнете внимание, че цените в тази оферта са [b]без ДДС[/b]. В договора ДДС ще е на отделен ред."));
        }
        
        return $tpl->getContent();
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова  артикула, активираме и нея
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $updateFields = array();
        
        if (!isset($rec->contragentId)) {
            $rec = self::fetch($rec->id);
        }
        
        // Ако няма дата попълваме текущата след активиране
        if (empty($rec->date)) {
            $updateFields[] = 'date';
            $rec->date = dt::today();
        }
        
        if (empty($rec->deliveryTime) && empty($rec->deliveryTermTime)) {
            $rec->deliveryTermTime = $mvc->calcDeliveryTime($rec);
            if (isset($rec->deliveryTermTime)) {
                $updateFields[] = 'deliveryTermTime';
            }
        }
        
        if (countR($updateFields)) {
            $mvc->save($rec, $updateFields);
        }
        
        // Ако офертата е в папка на контрагент вкарва се в група Клиенти->Оферти
        $clientGroupId = crm_Groups::getIdFromSysId('customers');
        $groupRec = (object)array('name' => 'Оферти', 'sysId' => 'quotationsClients', 'parentId' => $clientGroupId);
        $groupId = crm_Groups::forceGroup($groupRec);
        
        cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, false);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Оферта нормален изглед', 'content' => 'sales/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/QuotationHeaderNormalNarrow.shtml');
        $tplArr[] = array('name' => 'Оферта изглед за писмо', 'content' => 'sales/tpl/QuotationHeaderLetter.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Quotation', 'content' => 'sales/tpl/QuotationHeaderNormalEng.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/QuotationHeaderNormalEngNarrow.shtml');
        $res = doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        // Ако има избрано условие на доставка, позволява ли да бъде контиран документа
        $rec = $mvc->fetch($res->id);
        if(isset($rec->deliveryTermId)){
            $error = null;
            if(!cond_DeliveryTerms::checkDeliveryDataOnActivation($rec->deliveryTermId, $rec, $rec->deliveryData, $mvc, $error)){
                core_Statuses::newStatus($error, 'error');

                return false;
            }
        }

        $errorMsg = null;
        if(deals_Helper::hasProductsBellowMinPrice($mvc, $rec, $errorMsg)){
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }


        $saveRecs = $productsWithoutPrices = $productIds = array();
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where("#quotationId = {$rec->id}");

        while ($dRec = $dQuery->fetch()) {
            $productIds[$dRec->productId] = $dRec->productId;
            if(!isset($dRec->price) || !isset($dRec->tolerance) || !isset($dRec->term) || !isset($dRec->weight)){
                if (!isset($dRec->price)) {
                    $Detail::calcLivePrice($dRec, $rec, true);
                    if (!isset($dRec->price)) {
                        $productsWithoutPrices[] = cat_Products::getTitleById($dRec->productId);
                    }
                }

                if (!isset($dRec->term)) {
                    if ($term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity)) {
                        if ($deliveryTime = sales_TransportValues::get('sales_Quotations', $dRec->quotationId, $dRec->id)->deliveryTime) {
                            $term += $deliveryTime;
                        }
                        $dRec->term = $term;
                    }
                }

                if (!isset($dRec->tolerance)) {
                    if ($tolerance = cat_Products::getTolerance($dRec->productId, $dRec->quantity)) {
                        $dRec->tolerance = $tolerance;
                    }
                }

                if (!isset($dRec->weight)) {
                    $dRec->weight = cat_Products::getTransportWeight($dRec->productId, $dRec->quantity);
                }

                $saveRecs[] = $dRec;
            }
        }

        if($redirectError = deals_Helper::getContoRedirectError($productIds, 'canSell', 'generic', 'вече не са продаваеми или са генерични')){
            core_Statuses::newStatus($redirectError, 'error');

            return false;
        }

        $count = countR($productsWithoutPrices);
        if ($count) {
            $imploded = implode(', ', $productsWithoutPrices);
            $start = ($count == 1) ? 'артикула' : 'артикулите';
            $mid = ($count == 1) ? 'му' : 'им';
            $error = "На {$start}|* <b>{$imploded}</b> |трябва да {$mid} се въведе цена|*!";
            core_Statuses::newStatus($error, 'error');

            return false;
        }

        if(countR($saveRecs)){
            cls::get('sales_QuotationsDetails')->saveArray($saveRecs);
        }
    }
    
    
    /**
     * Връща заглавието на имейла
     *
     * @param int  $id
     * @param bool $isForwarding
     *
     * @return string
     *
     * @see email_DocumentIntf
     */
    public function getDefaultEmailSubject($id, $isForwarding = false)
    {
        $res = '';
        $rec = $this->fetchRec($id);
        
        if ($rec->reff) {
            $res = $rec->reff . ' ';
        }

        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where(array("#quotationId = '[#1#]'", $id));
        
        // Показваме кода на продукта с най високата сума
        $maxAmount = null;
        $pCnt = $productId = 0;
        while ($dRec = $dQuery->fetch()) {
            $amount = $dRec->price * $dRec->quantity;
            
            if ($dRec->discount) {
                $amount = $amount * (1 - $dRec->discount);
            }
            
            if (!isset($maxAmount) || ($amount > $maxAmount)) {
                $maxAmount = $amount;
                $productId = $dRec->productId;
            }
            
            $pCnt++;
        }
        
        $pCnt--;
        if ($productId) {
            $res .= cat_Products::getTitleById($productId);
            if ($pCnt > 0) {
                $res .= ' ' . tr('и още') . '...';
            }
        }
        
        return $res;
    }


    /**
     * Колко е максималния срок на доставка
     *
     * @param int|stdClass $id
     * @return int|NULL
     */
    protected function calcDeliveryTime($id)
    {
        $rec = $this->fetchRec($id);

        $defaultDeliveryTime = null;

        // Ако доставката е с явен транспорт, намира се максималния срок на доставка до мястото
        if($rec->deliveryCalcTransport == 'no'){
            $Calculator = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId);
            if(is_object($Calculator)){
                $locationId = isset($rec->deliveryPlaceId) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;
                $codeAndCountryArr = sales_TransportValues::getCodeAndCountryId($rec->contragentClassId, $rec->contragentId, $rec->pCode, $rec->contragentCountryId, $locationId ? $locationId : $rec->deliveryAdress);
                $deliveryParams = array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode']);
                $defaultDeliveryTime = $Calculator->getMaxDeliveryTime($rec->deliveryTermId, $deliveryParams);
            }
        }

        // Колко е максималният срок за доставка от детайлите
        $Detail = cls::get($this->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$rec->id} AND #optional = 'no'");
        $dQuery->show("productId,term,quantity,quotationId");
        $maxDeliveryTime = deals_Helper::calcMaxDeliveryTime($this, $rec, $Detail, $dQuery, $defaultDeliveryTime);

        return $maxDeliveryTime;
    }
}
