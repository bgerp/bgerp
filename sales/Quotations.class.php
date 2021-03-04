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
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Quotations extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Изходящи оферти';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Q';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';
    
    
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
     * Поле за търсене по дата
     */
    public $filterDateField = 'createdOn, date, modifiedOn';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, title=Документ, folderId, state, createdOn, createdBy';
    
    
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
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'paymentMethodId, reff, company, person, email, folderId';
    
    
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
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,sales';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'validFor' => 'lastDocUser|lastDoc',
        'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId' => 'lastDocUser|lastDoc|CoverMethod',
        'chargeVat' => 'lastDocUser|lastDoc|defMethod',
        'others' => 'lastDocUser|lastDoc',
        'deliveryTermId' => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId' => 'lastDocUser|lastDoc|',
        'company' => 'clientData',
        'pCode' => 'clientData',
        'place' => 'clientData',
        'address' => 'clientData',
        'contragentCountryId' => 'clientData',
        'template' => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array(
        'email' => 'email',
        'tel' => 'tel',
        'fax' => 'fax',
        'pCode' => 'pCode',
        'place' => 'place',
        'address' => 'address',
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'reff, date, expectedTransportCost';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'deliveryTermId, paymentMethodId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('reff', 'varchar(255,nullIfEmpty)', 'caption=Ваш реф.,class=contactData');
        $this->FLD('expectedTransportCost', 'double', 'input=none,caption=Очакван транспорт');
        
        $this->FNC('row1', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row2', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row3', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Метод,salecondSysId=paymentMethodSale');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Плащане->Банкова с-ка');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащане->Валута,removeAndRefreshForm=currencyRate');
        $this->FLD('currencyRate', 'double(decimals=5)', 'caption=Плащане->Курс,input=hidden');
        $this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=Плащане->ДДС,oldFieldName=vat');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTermSale,silent,removeAndRefreshForm=deliveryData|deliveryPlaceId|deliveryAdress|deliveryCalcTransport');
        $this->FLD('deliveryCalcTransport', 'enum(yes=Скрит транспорт,no=Явен транспорт)', 'input=none,caption=Доставка->Начисляване,after=deliveryTermId');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Обект,hint=Изберете обект');
        $this->FLD('deliveryAdress', 'varchar', 'caption=Доставка->Място');
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до');
        $this->FLD('deliveryTermTime', 'time(uom=days,suggestions=1 ден|5 дни|10 дни|1 седмица|2 седмици|1 месец)', 'caption=Доставка->Срок дни');
        $this->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');
        
        $this->FLD('company', 'varchar', 'caption=Получател->Фирма, changable, class=contactData,input=hidden');
        $this->FLD('person', 'varchar', 'caption=Име, changable, class=contactData,after=reff');
        $this->FLD('email', 'varchar', 'caption=Имейл, changable, class=contactData,after=person');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Тел., changable, class=contactData,after=email');
        $this->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Факс, changable, class=contactData,after=tel');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Получател->Държава,mandatory,contactData,contragentDataField=countryId,input=hidden');
        $this->FLD('pCode', 'varchar', 'caption=Получател->П. код, changable, class=contactData,input=hidden');
        $this->FLD('place', 'varchar', 'caption=Получател->Град/с, changable, class=contactData,input=hidden');
        $this->FLD('address', 'varchar', 'caption=Получател->Адрес, changable, class=contactData,input=hidden');
        
        $this->FLD('validFor', 'time(uom=days,suggestions=10 дни|15 дни|30 дни|45 дни|60 дни|90 дни)', 'caption=Допълнително->Валидност,mandatory');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Цени,notChangeableByContractor');
        $this->FLD('others', 'text(rows=4)', 'caption=Допълнително->Условия');
    
        $this->setDbIndex('date');
        $this->setDbIndex('contragentClassId,contragentId');
    }
    
    
    /**
     * Дали да се начислява ДДС
     */
    public function getDefaultChargeVat($rec)
    {
        $cData = doc_Folders::getContragentData($rec->folderId);
        $bgId = drdata_Countries::getIdByName('Bulgaria');
        if(empty($cData->countryId) || $bgId == $cData->countryId){
            $defaultChargeVat = sales_Setup::get("QUOTATION_DEFAULT_CHARGE_VAT_BG");
            if($defaultChargeVat != 'auto'){
                
                return $defaultChargeVat;
            }
        }
        
        $defaultChargeVat = deals_Helper::getDefaultChargeVat($rec->folderId);
        
        return $defaultChargeVat;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $form->setField('deliveryAdress', array('placeholder' => '|Държава|*, |Пощенски код|*'));
        $rec = &$data->form->rec;
        
        $contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
        $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $form->setDefault('contragentClassId', $contragentClassId);
        $form->setDefault('contragentId', $contragentId);
        $form->setOptions('priceListId', array('' => '') + price_Lists::getAccessibleOptions($rec->contragentClassId, $rec->contragentId));
        
        $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, false);
        if (countR($locations)) {
            $form->setOptions('deliveryPlaceId', array('' => '') + $locations);
        }
        
        if (isset($form->rec->id)) {
            if ($mvc->sales_QuotationsDetails->fetch("#quotationId = {$form->rec->id}")) {
                foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId', 'deliveryPlaceId', 'deliveryAdress', 'deliveryCalcTransport') as $fld) {
                    $form->setReadOnly($fld);
                }
            }
        }
        
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
        
        if (!$rec->person) {
            $form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, false));
        }
        
        // Срок на валидност по подразбиране
        $form->setDefault('validFor', sales_Setup::get('DEFAULT_VALIDITY_OF_QUOTATION'));
        
        $form->input('deliveryTermId');
        if(isset($rec->deliveryTermId)){
            if(cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)){
                $calcCost = cond_DeliveryTerms::fetchField($rec->deliveryTermId, 'calcCost');
                $form->setField('deliveryCalcTransport', 'input');
                $form->setDefault('deliveryCalcTransport', $calcCost);
            }
            
            cond_DeliveryTerms::prepareDocumentForm($rec->deliveryTermId, $form, $mvc);
        }
        
        // Дефолтната ценова политика се показва като плейсхолдър
        if($listId = price_ListToCustomers::getListForCustomer($form->rec->contragentClassId, $form->rec->contragentId)){
            $form->setField("priceListId", "placeholder=" . price_Lists::getTitleById($listId));
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if ($rec->state == 'active') {
            if ($mvc->haveRightFor('salefromquotation', (object) array('folderId' => $rec->folderId, 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId))) {
                $items = $mvc->getItems($rec->id);
                
                // Ако има поне един опционален артикул или има варианти на задължителните, бутона сочи към екшън за определяне на количествата
                if (sales_QuotationsDetails::fetch("#quotationId = {$rec->id} AND #optional = 'yes'") || !$items) {
                    $data->toolbar->addBtn('Продажба', array($mvc, 'FilterProductsForSale', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
                
                // Иначе, към създаването на нова продажба
                } else {
                    $warning = '';
                    $title = 'Прехвърляне на артикулите в съществуваща продажба чернова';
                    if (!sales_Sales::count("#state = 'draft' AND #contragentId = {$rec->contragentId} AND #contragentClassId = {$rec->contragentClassId}")) {
                        $warning = 'Сигурни ли сте, че искате да създадете продажба?';
                        $title = 'Създаване на продажба от офертата';
                        $efIcon = 'img/16/star_2.png';
                    } else {
                        $efIcon = 'img/16/cart_go.png';
                    }
                    
                    $data->toolbar->addBtn('Продажба', array($mvc, 'CreateSale', $rec->id, 'ret_url' => true), array('warning' => $warning), "ef_icon={$efIcon},title={$title}");
                }
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if ($data->sales_QuotationsDetails->summary) {
            $data->row = (object) ((array) $data->row + (array) $data->sales_QuotationsDetails->summary);
        }
        
        $dData = $data->sales_QuotationsDetails;
        if ($dData->countNotOptional && $dData->notOptionalHaveOneQuantity) {
            core_Lg::push($data->rec->tplLang);
            $keys = array_keys($dData->rows);
            $firstProductRow = $dData->rows[$keys[0]][0];
            
            if ($firstProductRow->tolerance) {
                $data->row->others .= '<li>' . tr('Толеранс к-во') .": {$firstProductRow->tolerance}</li>";
            }
            
            if (isset($firstProductRow->term)) {
                $data->row->others .= '<li>' . tr('Срок за д-ка') .": {$firstProductRow->term}</li>";
            }
            
            if (isset($firstProductRow->weight)) {
                $data->row->others .= '<li>' . tr('Транспортно тегло') .": {$firstProductRow->weight}</li>";
            }
            core_Lg::pop();
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
            
            if (empty($rec->currencyRate)) {
                $rec->currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, null);
                if (!$rec->currencyRate) {
                    $form->setError('currencyRate', 'Не може да се изчисли курс');
                }
            }
            
            if (isset($rec->date, $rec->validFor)) {
                $expireOn = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), false);
                if ($expireOn < dt::today()) {
                    $form->setWarning('date,validFor', 'Валидността на офертата е преди текущата дата');
                }
            }
            
            // Проверка за валидност на адресите
            if (!empty($rec->deliveryPlaceId) && !empty($rec->deliveryAdress)) {
                $form->setError('deliveryPlaceId,deliveryAdress', 'Не може двете полета да са едновременно попълнени');
            } elseif (!empty($rec->deliveryAdress)) {
                if (!drdata_Address::parsePlace($rec->deliveryAdress)) {
                    $form->setError('deliveryAdress', 'Адресът трябва да съдържа държава и пощенски код');
                }
            }
            
            // Избрания ДДС режим съответства ли на дефолтния
            $defVat = $mvc->getDefaultChargeVat($rec);
            if ($vatWarning = deals_Helper::getVatWarning($defVat, $rec->chargeVat)) {
                $form->setWarning('chargeVat', $vatWarning);
            }
            
            // Избраната валута съответства ли на дефолтната
            $defCurrency = cls::get($rec->contragentClassId)->getDefaultCurrencyId($rec->contragentId);
            $currencyState = currency_Currencies::fetchField("#code = '{$defCurrency}'", 'state');
            if ($defCurrency != $rec->currencyId && $currencyState != 'active') {
                $form->setWarning('currencyId', "Избрана e различна валута от очакваната|* <b>{$defCurrency}</b>");
            }
            
            if (isset($rec->deliveryTermTime, $rec->deliveryTime)) {
                $form->setError('deliveryTime,deliveryTermTime', 'Трябва да е избран само един срок на доставка');
            }
            
            if(isset($rec->deliveryTermId)){
                cond_DeliveryTerms::inputDocumentForm($rec->deliveryTermId, $form, $mvc);
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
            
            if (isset($rec->validFor)) {
                // До коя дата е валидна
                $validDate = dt::addSecs($rec->validFor, $rec->date);
                $row->validDate = $mvc->getFieldType('date')->toVerbal($validDate);
                
                $date = dt::verbal2mysql($validDate, false);
                if ($date < dt::today()) {
                    if (!Mode::isReadOnly()) {
                        $row->validDate = "<span class='red'>{$row->validDate}</span>";
                        
                        if ($rec->state == 'draft') {
                            $row->validDate = ht::createHint($row->validDate, 'Валидността на офертата е преди текущата дата', 'warning');
                        } elseif ($rec->state != 'rejected') {
                            $row->validDate = ht::createHint($row->validDate, 'Офертата е изтекла', 'warning');
                        }
                    }
                }
            }

            if(!Mode::isReadOnly()){
                $folderCover = doc_Folders::getCover($rec->folderId);
                if($folderCover->that != $rec->contragentId || $folderCover->getClassId() != $rec->contragentClassId){
                    $row->company = "<span class ='red'>{$row->company}</span>";
                    $row->company = ht::createHint($row->company, 'Контрагента в офертата, се различава от този в папката', 'error', false);
                }
            }

            $row->number = $mvc->getHandle($rec->id);
            $row->username = core_Users::recToVerbal(core_Users::fetch($rec->createdBy), 'names')->names;
            $row->username = transliterate(tr($row->username));
            
            $profRec = crm_Profiles::fetchRec("#userId = {$rec->createdBy}");
            if (!empty($profRec)) {
                if ($position = crm_Persons::fetchField($profRec->personId, 'buzPosition')) {
                    $row->position = cls::get('type_Varchar')->toVerbal($position);
                }
            }
            
            $ownCompanyData = crm_Companies::fetchOwnCompany();
            
            $Varchar = cls::get('type_Varchar');
            $row->MyCompany = $Varchar->toVerbal($ownCompanyData->company);
            $row->MyCompany = transliterate(tr($row->MyCompany));
            
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $cData = $contragent->getContragentData();
            
            $fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
            $row->mycompanyCountryId = drdata_Countries::getVerbal($ownCompanyData->countryId, $fld);
            
            foreach (array('pCode', 'place', 'address') as $fld) {
                if ($cData->{$fld}) {
                    $row->{"contragent{$fld}"} = $Varchar->toVerbal($cData->{$fld});
                }
                
                if ($ownCompanyData->{$fld}) {
                    $row->{"mycompany{$fld}"} = $Varchar->toVerbal($ownCompanyData->{$fld});
                    $row->{"mycompany{$fld}"} = transliterate(tr($row->{"mycompany{$fld}"}));
                }
            }
           
            if ($rec->currencyRate == 1) {
                unset($row->currencyRate);
            }
            
            if ($rec->others) {
                $others = explode('<br>', $row->others);
                $row->others = '';
                foreach ($others as $other) {
                    $row->others .= "<li>{$other}</li>";
                }
            }
            
            if(isset($rec->deliveryTermId)){
                if ($Driver = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)) {
                    $deliveryDataArr = $Driver->getVerbalDeliveryData($rec->deliveryTermId, $rec->deliveryData, get_called_class());
                    foreach ($deliveryDataArr as $delObj){
                        $row->deliveryBlock .= "<li>{$delObj->caption}: {$delObj->value}</li>";
                    }
                }
            }

            // Показване на допълнителните условия от артикулите
            $additionalConditions = deals_Helper::getConditionsFromProducts($mvc->mainDetail, $mvc, $rec->id, $rec->tplLang);
            if (is_array($additionalConditions)) {
                foreach ($additionalConditions as $cond) {
                    $row->others .= "<li>{$cond}</li>";
                }
            }
            
            if (isset($rec->bankAccountId)) {
                $ownAccount = bank_OwnAccounts::getOwnAccountInfo($rec->bankAccountId);
                if (!Mode::isReadOnly()) {
                    $url = bank_OwnAccounts::getSingleUrlArray($rec->bankAccountId);
                }
                $row->bankAccountId = ht::createLink($ownAccount->iban, $url);
            }
            
            $deliveryAdress = '';
            if (!empty($rec->deliveryAdress)) {
                $deliveryAdress .= $mvc->getFieldType('deliveryAdress')->toVerbal($rec->deliveryAdress);
            } else {
                $placeId = ($rec->deliveryPlaceId) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;
                $deliveryAdress .= cond_DeliveryTerms::addDeliveryTermLocation($rec->deliveryTermId, $rec->contragentClassId, $rec->contragentId, null, $placeId, $rec->deliveryData, $mvc);
            }
            
            $locationId = (!empty($rec->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null; 
            
            if(isset($rec->deliveryTermId) && !Mode::isReadOnly()){
                $row->deliveryTermId = ht::createLink($row->deliveryTermId, cond_DeliveryTerms::getSingleUrlArray($rec->deliveryTermId));
            }
            
            if (!empty($deliveryAdress)) {
                if(isset($rec->deliveryTermId)){
                    $row->deliveryTermId = "{$row->deliveryTermId}, {$deliveryAdress}";
                } else {
                    $row->deliveryPlaceId = $deliveryAdress;
                }
            }
            
            if (!empty($profRec)) {
                $createdRec = crm_Persons::fetch($profRec->id);
            }
            
            $buzAddress = ($createdRec->buzAddress) ? $createdRec->buzAddress : $ownCompanyData->place;
            if ($buzAddress) {
                $row->buzPlace = cls::get('type_Varchar')->toVerbal($buzAddress);
                $row->buzPlace = core_Lg::transliterate($row->buzPlace);
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
                
                if (sales_TransportValues::getDeliveryTermError($rec->deliveryTermId, $rec->deliveryAdress, $rec->contragentClassId, $rec->contragentId, $locationId)) {
                   $row->deliveryError = tr('За транспортните разходи, моля свържете се с представител на фирмата');
                }
            }
            
            if(!empty($row->deliveryPlaceId)){
                $row->deliveryPlaceCaption = isset($rec->deliveryTermId) ? tr('Място на доставка') : tr('За адрес');
            }
            
            if (empty($rec->deliveryTime) && empty($rec->deliveryTermTime)) {
                $deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
                if ($deliveryTermTime) {
                    $deliveryTermTime = cls::get('type_Time')->toVerbal($deliveryTermTime);
                    $row->deliveryTermTime = ht::createHint($deliveryTermTime, 'Времето за доставка се изчислява динамично възоснова на най-големия срок за доставка от артикулите');
                }
            }
        }
        
        if ($fields['-list']) {
            $row->title = $mvc->getLink($rec->id, 0);
        }
        
        return $row;
    }
    
    
    /**
     * Най-големия срок на доставка
     *
     * @param int $id
     *
     * @return int|NULL
     */
    public function getMaxDeliveryTime($id)
    {
        $maxDeliveryTime = null;
        
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$id} AND #optional = 'no'");
        $query->show("productId,term,quantity,quotationId");
        
        while ($dRec = $query->fetch()) {
            $term = $dRec->term;
            if (!isset($term)) {
                $term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity);
                
                $cRec = sales_TransportValues::get($this, $dRec->quotationId, $dRec->id);
                if (isset($cRec->deliveryTime)) {
                    $term = $cRec->deliveryTime + $term;
                }
            }
            
            if (isset($term)) {
                $maxDeliveryTime = max($maxDeliveryTime, $term);
            }
        }
        
        return $maxDeliveryTime;
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        
        $row->title = self::getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
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
        if ($res == 'no_one') {
            
            return;
        }
        
        if ($action == 'activate') {
            if (!$rec->id) {
                
                // Ако документа се създава, то не може да се активира
                $res = 'no_one';
            } else {
                
                // За да се активира, трябва да има детайли
                if (!sales_QuotationsDetails::fetchField("#quotationId = {$rec->id}")) {
                    $res = 'no_one';
                }
            }
        }
        
        // Ако офертата е изтекла и е затврорена, не може да се отваря
        if ($action == 'close' && isset($rec)) {
            if ($rec->state == 'closed' && isset($rec->validFor, $rec->date)) {
                $validTill = dt::verbal2mysql(dt::addSecs($rec->validFor, $rec->date), false);
                if ($validTill < dt::today()) {
                    $res = 'no_one';
                }
            }
        }
        
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
        
        if ($action == 'salefromquotation') {
            $sRec = isset($rec->folderId) ? (object)array('folderId' => $rec->folderId) : null;
            $res = sales_Sales::getRequiredRoles('add', $sRec, $userId);

            if(isset($rec)){
                if($res != 'no_one'){

                    // Ако има разминаване между контрагента в офертата и данните от папката, забранява се създаване на продажба
                    $folderCover = doc_Folders::getCover($rec->folderId);
                    if($folderCover->that != $rec->contragentId || $folderCover->getClassId() != $rec->contragentClassId){
                        $res = 'no_one';
                    }
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
        $tpl = new core_ET(tr("Моля запознайте се с нашата оферта|* : #[#handle#]."));
        $tpl->append($handle, 'handle');
        
        if($rec->chargeVat == 'separate'){
            $tpl->append("\n\n" . tr("Обърнете внимание, че цените в тази оферта са [b]без ДДС[/b]. В договора ДДС ще е на отделен ред."));
        }
        
        return $tpl->getContent();
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
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
            $rec->deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
            if (isset($rec->deliveryTermTime)) {
                $updateFields[] = 'deliveryTermTime';
            }
        }
        
        if (countR($updateFields)) {
            $mvc->save($rec, $updateFields);
        }
        
        // Ако запитването е в папка на контрагент вкарва се в група запитвания
        $clientGroupId = crm_Groups::getIdFromSysId('customers');
        $groupRec = (object)array('name' => 'Оферти', 'sysId' => 'quotationsClients', 'parentId' => $clientGroupId);
        $groupId = crm_Groups::forceGroup($groupRec);
        
        cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, false);
    }
    
    
    /**
     * Връща масив от използваните документи в офертата
     *
     * @param int $id - ид на оферта
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - Инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и не може да се
     * изчисли общата сума ф-ята връща NULL
     *
     * @param int  $id           - ид на оферта
     * @param bool $onlyStorable - дали да са само складируемите
     *
     * @return array|NULL - продуктите
     */
    private function getItems($id, $onlyStorable = false, $groupByProduct = false)
    {
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$id} AND #optional = 'no'");
        
        if ($onlyStorable === true) {
            $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $query->where("#canStore = 'yes'");
        }
        
        $products = array();
        while ($detail = $query->fetch()) {
            $index = ($groupByProduct === true) ? $detail->productId : "{$detail->productId}|{$detail->packagingId}";
            
            if (array_key_exists($index, $products) || !$detail->quantity) {
                
                return;
            }
            $products[$index] = $detail;
        }
        
        return (countR($products)) ? array_values($products) : null;
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
        
        $res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $mvc = cls::get(get_called_class());
        
        $rec = static::fetchRec($rec);
        
        $abbr = $mvc->abbr;
        $abbr[0] = strtoupper($abbr[0]);
        
        $date = dt::mysql2verbal($rec->date, 'd.m.year');
        
        $crm = cls::get($rec->contragentClassId);
        
        $cRec = $crm->getContragentData($rec->contragentId);
        
        $contragent = str::limitLen($cRec->company ? $cRec->company : $cRec->person, 32);
        
        if ($escaped) {
            $contragent = type_Varchar::escape($contragent);
        }
        
        return "{$abbr}{$rec->id}/{$date} {$contragent}";
    }
    
    
    /**
     * Създаване на продажба от оферта
     *
     * @param stdClass $rec
     *
     * @return mixed
     */
    private function createSale($rec)
    {
        $Sales = cls::get('sales_Sales');
        $templateId = cond_plg_DefaultValues::getFromLastDocument($Sales, $rec->folderId, 'template');
        
        if (empty($templateId)) {
            $templateId = cond_plg_DefaultValues::getFromLastDocument($Sales, $rec->folderId, 'template', false);
        }
        
        if (empty($templateId)) {
            $templateId = sales_Sales::getDefaultTemplate((object) array('folderId' => $rec->folderId));
        }
        
        // Подготвяме данните на мастъра на генерираната продажба
        $fields = array('currencyId' => $rec->currencyId,
            'currencyRate' => $rec->currencyRate,
            'paymentMethodId' => $rec->paymentMethodId,
            'deliveryTermId' => $rec->deliveryTermId,
            'caseId' => cash_Cases::getCurrent('id', false),
            'chargeVat' => $rec->chargeVat,
            'note' => $rec->others,
            'originId' => $rec->containerId,
            'template' => $templateId,
            'deliveryAdress' => $rec->deliveryAdress,
            'deliveryTime' => $rec->deliveryTime,
            'deliveryTermTime' => $rec->deliveryTermTime,
            'deliveryData' => $rec->deliveryData,
            'deliveryCalcTransport' => $rec->deliveryCalcTransport,
            'deliveryLocationId' => crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id'),
        );
        
        $folderId = cls::get($rec->contragentClassId)->forceCoverAndFolder($rec->contragentId);
        $fields['dealerId'] = sales_Sales::getDefaultDealerId($folderId, $fields['deliveryLocationId']);
        
        // Създаваме нова продажба от офертата
        $saleId = sales_Sales::createNewDraft($rec->contragentClassId, $rec->contragentId, $fields);
        if (isset($saleId) && isset($rec->bankAccountId)) {
            $uRec = (object) array('id' => $saleId, 'bankAccountId' => bank_OwnAccounts::fetchField($rec->bankAccountId, 'bankAccountId'));
            cls::get('sales_Sales')->save_($uRec);
        }
        
        return $saleId;
    }
    
    
    /**
     * Екшън генериращ продажба от оферта
     */
    public function act_CreateSale()
    {
        $this->requireRightFor('salefromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        expect($rec->state = 'active');
        expect($items = $this->getItems($id));
        $this->requireRightFor('salefromquotation', $rec);
        $force = Request::get('force', 'int');
        
        // Ако не форсираме нова продажба
        if (!$force && !core_Users::isContractor()) {
            // Опитваме се да намерим съществуваща чернова продажба
            if (!Request::get('dealId', 'key(mvc=sales_Sales)') && !Request::get('stop')) {
                
                return new Redirect(array('sales_Sales', 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => true, 'quotationId' => $rec->id));
            }
        }
        
        // Ако няма създава се нова продажба
        if (!$sId = Request::get('dealId', 'key(mvc=sales_Sales)')) {
            try{
                $sId = $this->createSale($rec);
                sales_Sales::logWrite('Създаване от оферта', $sId);
            } catch(core_exception_Expect $e){
                reportException($e);
                $this->logErr($e->dump[0], $rec->id);
                followRetUrl(null, "Проблем при създаване на продажба от оферта", 'error');
            }
        }
        
        // За всеки детайл на офертата подаваме го като детайл на продажбата
        foreach ($items as $item) {
            $addedRecId = sales_Sales::addRow($sId, $item->productId, $item->packQuantity, $item->price, $item->packagingId, $item->discount, $item->tolerance, $item->term, $item->notes);
            
            // Копира се и транспорта, ако има
            $cRec = sales_TransportValues::get($this, $item->quotationId, $item->id);
            if (isset($cRec)) {
                sales_TransportValues::sync('sales_Sales', $sId, $addedRecId, $cRec->fee, $cRec->deliveryTime);
            }
        }
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logWrite('Създаване на продажба от оферта', $id);
        
        // Редирект към новата продажба
        return new Redirect(array('sales_Sales', 'single', $sId), '|Успешно е създадена продажба от офертата');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
    public function act_FilterProductsForSale()
    {
        $this->requireRightFor('salefromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        expect($rec->state == 'active');
        $this->requireRightFor('salefromquotation', $rec);
        
        // Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($rec->id, $id);
        $form->input();
        
        if ($form->isSubmitted()) {
            $products = (array) $form->rec;
            
            $setError = true;
            $errFields = array();
            foreach ($products as $index1 => $quantity1) {
                if (!empty($quantity1)) {
                    $setError = false;
                } else {
                    $errFields[] = $index1;
                }
            }
            
            if ($setError === true) {
                $form->setError(implode(',', $errFields), 'Не са зададени количества');
            }
            
            if (!$form->gotErrors()) {
                try{
                    $errorMsg = 'Проблем при създаването на оферта';
                    $sId = $this->createSale($rec);
                } catch(core_exception_Expect $e){
                    $errorMsg = $e->getMessage();
                    reportException($e);
                    $this->logErr($errorMsg, $rec->id);
                }
                
                if(empty($sId)){
                    followRetUrl(null, $errorMsg, 'error');
                }
                
                foreach ($products as $dRecId) {
                    if(empty($dRecId)) continue;
                    
                    $dRec = sales_QuotationsDetails::fetch($dRecId);
                    
                    // Копира се и транспорта, ако има
                    $addedRecId = sales_Sales::addRow($sId, $dRec->productId, $dRec->packQuantity, $dRec->price, $dRec->packagingId, $dRec->discount, $dRec->tolerance, $dRec->term, $dRec->notes);
                    $tRec = sales_TransportValues::get($this, $id, $dRecId);
                    
                    if (isset($tRec->fee)) {
                        sales_TransportValues::sync('sales_Sales', $sId, $addedRecId, $tRec->fee, $tRec->deliveryTime, $tRec->explain);
                    }
                }
                
                // Редирект към сингъла на новосъздадената продажба
                return new Redirect(array('sales_Sales', 'single', $sId));
            }
        }
        
        if (core_Users::haveRole('partner')) {
            plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
        }
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     *
     * @param int $id - ид на записа
     *
     * @return core_Form - готовата форма
     */
    private function getFilterForm($id)
    {
        $form = cls::get('core_Form');
        
        $form->title = 'Създаване на продажба от|* ' . sales_Quotations::getFormTitleLink($id);
        $form->info = tr('Моля уточнете, кои редове ще се прехвърлят в продажбата');
        $filteredProducts = $this->filterProducts($id);
        
        foreach ($filteredProducts as $index => $product) {
            if ($product->optional == 'yes') {
                $product->title = "Опционални->{$product->title}";
                $product->options = array('' => '') + $product->options;
                $mandatory = '';
            } else {
                $product->title = "Оферирани->{$product->title}";
                $mandatory = '';
                if (countR($product->options) > 1) {
                    $product->options = array('' => '') + $product->options;
                    $mandatory = 'mandatory';
                }
            }
            $form->FNC($index, 'double(decimals=2)', "input,caption={$product->title},hint={$product->hint},{$mandatory}");
            if (countR($product->options) == 1) {
                $default = key($product->options);
            }
            
            $product->options = $product->options + array('0' => '0');
            $form->setOptions($index, $product->options);
            $form->setDefault($index, $default);
        }
        
        $form->toolbar->addSbBtn('Създаване', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title = Прекратяване на действията');
        
        return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     *
     * @param int $id - ид на оферта
     *
     * @return array $products - филтрираните продукти
     */
    private function filterProducts($id)
    {
        $Detail = clone cls::get('sales_QuotationsDetails');
        
        $rec = $this->fetchRec($id);
        $products = array();
        $query = $Detail->getQuery();
        $query->where("#quotationId = {$id}");
        $query->orderBy('optional=ASC,id=ASC');
        $dRecs = $query->fetchAll();
        
        deals_Helper::fillRecs($Detail, $dRecs, $rec);
        
        foreach ($dRecs as $dRec) {
            $index = "{$dRec->productId}|{$dRec->optional}|{$dRec->packagingId}|" .md5($dRec->notes);
            
            if (!array_key_exists($index, $products)) {
                $title = cat_Products::getTitleById($dRec->productId);
                $title = str_replace(',', '.', $title);
                if (isset($dRec->packagingId)) {
                    $title .= ' / ' . cat_UoM::getShortName($dRec->packagingId);
                }
                
                $hint = null;
                if (!empty($dRec->notes)) {
                    $title .= ' / ' . str::limitLen(strip_tags(core_Type::getByName('richtext')->toVerbal($dRec->notes)), 10);
                    $hint = $dRec->notes;
                }
                $products[$index] = (object) array('title' => $title, 'options' => array(), 'optional' => $dRec->optional, 'suggestions' => false, 'hint' => $hint);
            }
            
            if ($dRec->optional == 'yes') {
                $products[$index]->suggestions = true;
            }
            
            if ($dRec->quantity) {
                core_Mode::push('text', 'plain');
                $packQuantity = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packQuantity);
                $packPrice = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packPrice);
                
                $val = "{$packQuantity} / {$packPrice} " . $rec->currencyId;
                foreach (array('discount', 'tolerance', 'term') as $fld){
                    if(!empty($dRec->{$fld})){
                        $Type = ($fld != 'term') ? core_Type::getByName('percent') : core_Type::getByName('time');
                        $val .= " / " . $Type->toVerbal($dRec->{$fld});
                    }
                }
                core_Mode::pop('text');
                
                $products[$index]->options[$dRec->id] = $val;
            }
        }
        
        return $products;
    }
    
    
    /**
     * След извличане на името на документа за показване в RichText-а
     */
    protected static function on_AfterGetDocNameInRichtext($mvc, &$docName, $id)
    {
        // Ако има реф да се показва към името му
        $reff = $mvc->getVerbal($id, 'reff');
        if (strlen($reff) != 0) {
            $docName .= "({$reff})";
        }
    }
    
    
    /**
     * Затваряне на изтекли оферти по крон
     */
    public function cron_CloseQuotations()
    {
        $today = dt::today();
        
        // Селектираме тези фактури, с изтекла валидност
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        $query->where('#validFor IS NOT NULL');
        $query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
        $query->where("#expireOn < '{$today}'");
        $query->show('id');
        
        // Затваряме ги
        while ($rec = $query->fetch()) {
            try {
                $rec->state = 'closed';
                $this->save_($rec, 'state');
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if (Request::get('Rejected', 'int')) {
            
            return;
        }
        
        $data->listFilter->FNC('sState', 'enum(all=Всички,draft=Чернова,pending=Заявка,active=Активен,closed=Приключен)', 'caption=Състояние,autoFilter');
        $data->listFilter->showFields .= ',sState';
        $data->listFilter->setDefault('sState', 'active');
        $data->listFilter->input();
        
        if ($rec = $data->listFilter->rec) {
            if (isset($rec->sState) && $rec->sState != 'all') {
                $data->query->where("#state = '{$rec->sState}'");
            }
        }
    }
    
    
    /*
     * API за генериране на оферти
    */
    
    
    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int   $contragentId    - ид на контрагента
     * @param int   $date            - дата
     * @param array $fields          - стойности на полетата на сделката
     *
     *   o $fields['originId']              - вальор (ако няма е текущата дата)
     *   o $fields['reff']                  - вашия реф на продажбата
     *   o $fields['currencyCode']          - код на валута (ако няма е основната за периода)
     * 	 o $fields['rate']                  - курс към валутата (ако няма е този към основната валута)
     * 	 o $fields['paymentMethodId']       - ид на платежен метод (Ако няма е плащане в брой, @see cond_PaymentMethods)
     * 	 o $fields['chargeVat']             - да се начислява ли ДДС - yes=Да, separate=Отделен ред за ДДС, exempt=Освободено,no=Без начисляване(ако няма, се определя според контрагента)
     * 	 o $fields['deliveryTermId']        - ид на метод на доставка (@see cond_DeliveryTerms)
     *   o $fields['deliveryCalcTransport'] - дали да се начислява скрит или явен транспорт (@see cond_DeliveryTerms)
     * 	 o $fields['validFor']              - срок на годност
     *   o $fields['company']               - фирма
     *   o $fields['person']                - лице
     *   o $fields['email']                 - имейли
     *   o $fields['tel']                   - телефон
     *   o $fields['fax']                   - факс
     *   o $fields['pCode']                 - пощенски код
     *   o $fields['place']                 - град
     *   o $fields['address']               - адрес
     *   o $fields['deliveryAdress']        - адрес за доставка
     *
     * @return mixed - ид на запис или FALSE
     */
    public static function createNewDraft($contragentClass, $contragentId, $date = null, $fields = array())
    {
        // Проверки
        $me = cls::get(get_called_class());
        expect($Cover = cls::get($contragentClass), 'Невалиден клас');
        expect(cls::haveInterface('crm_ContragentAccRegIntf', $Cover), 'Класа не е на контрагент');
        expect($Cover->fetch($contragentId), 'Няма такъв контрагент');
        expect($data = $Cover->getContragentData($contragentId), 'Няма данни за контрагента');
        
        // Подготовка на мастъра
        $newRec = new stdClass();
        $newRec->date = (isset($date)) ? $date : null;
        $newRec->reff = (isset($fields['reff'])) ? $fields['reff'] : null;
        $newRec->contragentClassId = $Cover->getClassId();
        $newRec->contragentId = $contragentId;
        $newRec->originId = (isset($fields['originId'])) ? $fields['originId'] : null;
        
        if (!empty($fields['deliveryAdress'])) {
            expect(drdata_Address::parsePlace($fields['deliveryAdress']), 'Адресът трябва да съдържа държава и пощенски код');
            $newRec->deliveryAdress = $fields['deliveryAdress'];
        }
        
        if (isset($newRec->originId)) {
            $origin = doc_Containers::getDocument($newRec->originId);
            $newRec->folderId = $origin->fetchField('folderId');
            $newRec->threadId = $origin->fetchField('threadId');
        } else {
            $newRec->folderId = $Cover->forceCoverAndFolder($contragentId);
        }
        
        $newRec->currencyId = (isset($fields['currencyCode'])) ? $fields['currencyCode'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'currencyId');
        expect(currency_Currencies::getIdByCode($newRec->currencyId), 'Невалиден код');
        
        $newRec->currencyRate = (isset($fields['rate'])) ? $fields['rate'] : currency_CurrencyRates::getRate($newRec->date, $newRec->currencyId, null);
        expect(cls::get('type_Double')->fromVerbal($newRec->currencyRate), 'Невалиден курс');
        
        $newRec->chargeVat = (isset($fields['chargeVat'])) ? $fields['chargeVat'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'chargeVat');
        expect(in_array($newRec->chargeVat, array('yes', 'no', 'exempt', 'separate')), 'Невалидно ДДС');
        
        // Намиране на метода за плащане
        $newRec->paymentMethodId = (isset($fields['paymentMethodId'])) ? $fields['paymentMethodId'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'paymentMethodId');
        if (isset($newRec->paymentMethodId)) {
            expect(cond_PaymentMethods::fetch($newRec->paymentMethodId), 'Невалиден метод за плащане');
        }
        
        // Условието на доставка
        $newRec->deliveryTermId = (isset($fields['deliveryTermId'])) ? $fields['deliveryTermId'] : cond_plg_DefaultValues::getDefaultValue($me, $newRec->folderId, 'deliveryTermId');
        if (isset($newRec->deliveryTermId)) {
            expect(cond_DeliveryTerms::fetch($newRec->deliveryTermId), 'Невалидно условие на доставка');
        }
        
        // Срока на валидност, ако не е зададен е дефолтния
        $newRec->validFor = (isset($fields['validFor'])) ? $fields['validFor'] : sales_Setup::get('DEFAULT_VALIDITY_OF_QUOTATION');
        if (isset($newRec->validFor)) {
            expect(type_Int::isInt($newRec->validFor), 'Срока на валидност трябва да е в секунди');
        }
        
        // Адресните данни
        foreach (array('company', 'person', 'email', 'tel', 'fax', 'pCode', 'place', 'address') as $fld) {
            if (isset($fields[$fld])) {
                expect($newRec->{$fld} = cls::get('type_Varchar')->fromVerbal($fields[$fld]), 'Невалидни адресни данни');
            } else {
                if (($Cover instanceof crm_Persons) && $fld == 'address') {
                    $fld = 'p'.ucfirst($fld);
                }
                if (!empty($data->{$fld})) {
                    $value = $data->{$fld};
                    if ($fld == 'email') {
                        $emails = type_Emails::toArray($data->{$fld});
                        $value = isset($emails[0]) ? $emails[0] : null;
                    } elseif ($fld == 'tel') {
                        $tels = drdata_PhoneType::toArray($data->{$fld});
                        if(is_object($tels[0])){
                            $value = '+' . $tels[0]->countryCode . $tels[0]->areaCode . $tels[0]->number;
                        } else {
                            $value = null;
                        }
                    }
                    
                    $newRec->{$fld} = $value;
                }
            }
        }
        
        // Държавата
        $newRec->contragentCountryId = (isset($fields['countryId'])) ? $fields['countryId'] : $data->countryId;
        expect(drdata_Countries::fetch($newRec->contragentCountryId), 'Невалидна държава');
        $newRec->template = self::getDefaultTemplate($newRec);
        
        if(isset($newRec->deliveryTermId)){
            if(cond_DeliveryTerms::getTransportCalculator($newRec->deliveryTermId)){
                $newRec->deliveryCalcTransport = isset($fields['deliveryCalcTransport']) ? $fields['deliveryCalcTransport'] : cond_DeliveryTerms::fetchField($newRec->deliveryTermId, 'calcCost');
            }
        }
        
        // Създаване на запис
        self::route($newRec);
        
        // Опиваме се да запишем мастъра на офертата
        if ($id = self::save($newRec)) {
            doc_ThreadUsers::addShared($newRec->threadId, $newRec->containerId, core_Users::getCurrent());
            
            return $id;
        }
        
        return false;
    }
    
    
    /**
     * Добавя нов ред в главния детайл на чернова сделка.
     * Ако има вече такъв артикул добавен към сделката, наслагва к-то, цената и отстъпката
     * на новия запис към съществуващия (цените и отстъпките стават по средно притеглени)
     *
     * @param int   $id           - ид на сделка
     * @param int   $productId    - ид на артикул
     * @param float $packQuantity - количество продадени опаковки (ако няма опаковки е цялото количество)
     * @param int   $packagingId  - ид на опаковка (не е задължителна)
     * @param float $price        - цена на единична бройка, без ДДС в основна валута
     * @param bool  $optional     - дали артикула е опционален или не
     * @param array $other        - масив с допълнителни параметри
     *                            double ['discount']       - отстъпка (опционална)
     *                            double ['tolerance']      - толеранс (опционален)
     *                            mixed  ['term']           - срок на доставка (опционален)
     *                            html   ['notes']          - забележки (опционален)
     *                            double ['quantityInPack'] - к-во в опаковка (опционален)
     *
     * @return mixed $id/FALSE     - ид на запис или FALSE
     */
    public static function addRow($id, $productId, $packQuantity, $packagingId = null, $price = null, $optional = false, $other = array())
    {
        // Проверка на параметрите
        expect($rec = self::fetch($id), 'Няма такава оферта');
        expect($rec->state == 'draft', 'Офертата трябва да е чернова');
        expect($productId, 'Трябва да е подаден артикул');
        expect($productRec = cat_Products::fetch($productId, 'id,canSell,measureId'), 'Няма такъв артикул');
        expect($productRec->canSell == 'yes', 'Артикулът не е продаваем');
        expect($packQuantity = cls::get('type_Double')->fromVerbal($packQuantity), 'Невалидно количество');
        
        // Подготовка на записа
        $newRec = new stdClass();
        $newRec->quotationId = $rec->id;
        $newRec->productId = $productId;
        $newRec->showMode = 'auto';
        $newRec->vatPercent = cat_Products::getVat($productId, $rec->date);
        $newRec->optional = ($optional === true) ? 'yes' : 'no';
        expect(in_array($newRec->optional, array('yes', 'no')));
        
        // Проверка на опаковката
        $newRec->packagingId = isset($packagingId) ? $packagingId : $productRec->measureId;
        $packs = cat_Products::getPacks($productId);
        expect(array_key_exists($newRec->packagingId, $packs), 'Артикулът няма такава опаковка');
        
        // Намиране на к-то в опаковка
        $pack = cat_products_Packagings::getPack($productId, $packagingId);
        $newRec->quantityInPack = (isset($other['quantityInPack'])) ? $other['quantityInPack'] : ((is_object($pack)) ? $pack->quantity : 1);
        expect($newRec->quantityInPack = cls::get('type_Double')->fromVerbal($newRec->quantityInPack), 'Проблем с количеството в опаковка');
        
        // Колко е общото количество
        $newRec->quantity = $newRec->quantityInPack * $packQuantity;
        
        // Дали отстъпката е между 0 и 1
        if (isset($other['discount'])) {
            expect($newRec->discount = cls::get('type_Double')->fromVerbal($other['discount']));
            expect($newRec->discount >= 0 && $newRec->discount <= 1, 'Отстъпката трябва да е между 0 и 1');
        }
        
        // Дали толеранса е между 0 и 1
        if (isset($other['tolerance'])) {
            expect($newRec->tolerance = cls::get('type_Double')->fromVerbal($other['tolerance']));
            expect($newRec->tolerance >= 0 && $newRec->tolerance <= 1);
        }
        
        if (isset($other['term'])) {
            expect($newRec->term = cls::get('type_Time')->fromVerbal($other['term']));
        }
        
        if (isset($other['notes'])) {
            $newRec->notes = cls::get('type_Richtext')->fromVerbal($other['notes']);
        }
        
        // Ако няма цена, прави се опит да се намери
        if (isset($price)) {
            $newRec->price = $price;
            expect($newRec->price = cls::get('type_Double')->fromVerbal($newRec->price), 'Невалидна цена');
        }
        
        // Изчисляване на транспортните разходи
        if (core_Packs::isInstalled('tcost')) {
            $form = sales_QuotationsDetails::getForm();
            $clone = clone $rec;
            $clone->deliveryPlaceId = (!empty($rec->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null; 
            
            sales_TransportValues::prepareFee($newRec, $form, $clone, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId', 'deliveryData' => 'deliveryData'));
        }
        
        // Проверки на записите
        if ($sameProduct = sales_QuotationsDetails::fetch("#quotationId = {$newRec->quotationId} AND #productId = {$newRec->productId}")) {
            if ($newRec->optional == 'no' && $sameProduct->optional == 'yes') {
                expect(false, 'Не може да добавите продукта като задължителен, защото фигурира вече като опционален');
            }
        }
        
        if ($sameProduct = sales_QuotationsDetails::fetch("#quotationId = {$newRec->quotationId} AND #productId = {$newRec->productId}  AND #quantity='{$newRec->quantity}'")) {
            expect(false, 'Избрания продукт вече фигурира с това количество');
        }
        
        // Запис на детайла
        return sales_QuotationsDetails::save($newRec);
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        $quotationId = $res->id;
        $rec = $mvc->fetch($quotationId);
        
        $error = array();
        $saveRecs = array();
        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where("#quotationId = {$quotationId}");
        $dQuery->where('#price IS NULL || #tolerance IS NULL || #term IS NULL || #weight IS NULL');
        while ($dRec = $dQuery->fetch()) {
            if (!isset($dRec->price)) {
                sales_QuotationsDetails::calcLivePrice($dRec, $rec, true);
                
                if (!isset($dRec->price)) {
                    $error[] = cat_Products::getTitleById($dRec->productId);
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
        
        if (countR($error)) {
            $imploded = implode(', ', $error);
            $start = (countR($error) == 1) ? 'артикулът' : 'артикулите';
            $mid = (countR($error) == 1) ? 'му' : 'им';
            $msg = "На {$start}|* <b>{$imploded}</b> |трябва да {$mid} се въведе цена|*";
            
            core_Statuses::newStatus($msg, 'error');
            
            return false;
        }
        
        // Ако има избрано условие на доставка, пзоволява ли да бъде контиран документа
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

        cls::get('sales_QuotationsDetails')->saveArray($saveRecs);
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
        
        if (!$id) {
            
            return $res;
        }
        $rec = $this->fetch($id);
        
        if (!$rec) {
            
            return $res;
        }
        
        $res = '';
        
        if ($rec->reff) {
            $res = $rec->reff . ' ';
        }
        
        
        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where(array("#quotationId = '[#1#]'", $id));
        
        // Показваме кода на продукта с най високата сума
        $maxAmount = null;
        $productId = 0;
        $pCnt = 0;
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
            $res .= cat_products::getTitleById($productId);
            
            if ($pCnt > 0) {
                $res .= ' ' . tr('и още') . '...';
            }
        }
        
        return $res;
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        
        return $this->save($rec, 'modifiedOn,modifiedBy,searchKeywords');
    }
    
    
    /**
     * Състояние на нишката
     */
    public static function getThreadState($id)
    {
        $createdBy = self::fetchField($id, 'createdBy');
        
        return ($createdBy == core_Users::SYSTEM_USER) ? 'opened' : null;
    }
    
    
    /**
     * Екшън за автоматичен редирект към създаване на детайл
     */
    function act_autoCreateInFolder()
    {
        $this->requireRightFor('add');
        expect($folderId = Request::get('folderId', 'int'));
        $this->requireRightFor('add', (object)array('folderId' => $folderId));
        expect(doc_Folders::haveRightToFolder($folderId));
        
        // Има ли избрана константа
        $constValue = sales_Setup::get('NEW_QUOTATION_AUTO_ACTION_BTN');
        if($constValue == 'form') {
            
            return Redirect(array($this, 'add', 'folderId' => $folderId, 'ret_url' => getRetUrl()));
        }
        
        // Генерира дефолтите според папката
        $Cover = doc_Folders::getCover($folderId);
        $fields = array();
        $fieldsWithStrategy = array_keys(static::$defaultStrategies);
        foreach ($fieldsWithStrategy as $field){
            $fields[$field] = cond_plg_DefaultValues::getDefaultValue($this, $folderId, $field);
        }
        
        // Създаване на мастър на документа
        try{
            $masterId = static::createNewDraft($Cover->getClassId(), $Cover->that, null, $fields);
        } catch(core_exception_Expect $e){
            reportException($e);
            
            followRetUrl(null, "Проблем при създаване на оферта");
        }
        
        $redirectUrl = array($this, 'single', $masterId);
        $Detail = cls::get($this->mainDetail);
        
        // Редирект към добавянето на детайл
        if($constValue == 'addProduct') {
            if($Detail->haveRightFor('add', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'add', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        } elseif($constValue == 'createProduct'){
            if($Detail->haveRightFor('createproduct', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'createproduct', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        }
        
        return Redirect($redirectUrl);
    }
}
