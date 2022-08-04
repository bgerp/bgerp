<?php


/**
 * Клас 'trans_Lines' - Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Lines extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Транспортни линии';


    /**
     * Абревиатура
     */
    public $abbr = 'Tl';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_Printing, plg_Clone, doc_DocumentPlg, change_Plugin, doc_ActivatePlg, doc_plg_SelectFolder, doc_plg_Close, acc_plg_DocumentSummary, plg_Search, plg_Sorting';


    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'ceo, trans';


    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'title, vehicle, forwarderId, forwarderPersonId';


    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'handler';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, trans';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';


    /**
     * Кой има право да прави документа на заявка?
     */
    public $canPending = 'ceo, trans';


    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ceo, trans';


    /**
     * Кой може да пише?
     */
    public $canClose = 'ceo, trans';


    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, trans';


    /**
     * Детайла, на модела
     */
    public $details = 'trans_LineDetails';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'start, handler=Документ,readiness=Готовност, transUnitsTotal=Лог. единици, folderId, state, createdOn, createdBy';


    /**
     * Кои полета да могат да се променят след активацията на документа
     */
    public $changableFields = 'title, start, repeat, vehicle, forwarderId, forwarderPersonId';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Транспортна линия';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutLines.shtml';


    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'trans/tpl/SingleLayoutLinesNarrow.shtml';


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/lorry_go.png';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.5|Логистика';


    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,start,repeat,countStoreDocuments,countActiveDocuments,countReadyDocuments,cases,stores, countries';


    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'start,createdOn';


    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo, trans';


    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForAll = 'ceo, trans';


    /**
     * Кеш на информацията за данните от експедиционните документи
     */
    protected $cacheLineInfo = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory');
        $this->FLD('start', 'datetime', 'caption=Начало, mandatory');
        $this->FLD('repeat', 'time(suggestions=1 ден|1 седмица|1 месец|2 дена|2 седмици|2 месеца|3 седмици)', 'caption=Повторение');
        $this->FLD('state', 'enum(draft=Чернова,,pending=Заявка,active=Активен,rejected=Оттеглен,closed=Затворен)', 'caption=Състояние,input=none');
        $this->FLD('defaultCaseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,unit=(по подразбиране)');
        $this->FLD('forwarderId', 'key2(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Превоз->Спедитор');
        $this->FLD('vehicle', 'varchar', 'caption=Превоз->МПС,oldFieldName=vehicleId');
        $this->FLD('forwarderPersonId', 'key2(mvc=crm_Persons,select=name,group=employees,allowEmpty)', 'caption=Превоз->МОЛ');
        $this->FLD('description', 'richtext(bucket=Notes,rows=4)', 'caption=Допълнително->Бележки');

        $this->FLD('stores', 'keylist(mvc=store_Stores,select=name)', 'caption=Складове,input=none');
        $this->FLD('cases', 'keylist(mvc=cash_Cases,select=name)', 'caption=Каси,input=none');
        $this->FLD('countStoreDocuments', 'int', 'input=none,notNull,value=0');
        $this->FLD('countActiveDocuments', 'int', 'input=none,notNull,value=0');
        $this->FLD('countReadyDocuments', 'int', 'input=none,notNull,value=0');
        $this->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'input=none,caption=Държави');
        $this->FLD('transUnitsTotal', 'blob(serialize, compress)', 'input=none,caption=Логистична информация');
        $this->FLD('places', 'varchar(255)', 'caption=Населени места,input=none');
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $titleArr = array();
        $titleArr[] = str_replace(' 00:00', '', dt::mysql2verbal($rec->start, 'd.m.Y H:i'));
        $ourCompany = crm_Companies::fetchOurCompany();

        if (!empty($rec->forwarderId) && $rec->forwarderId != $ourCompany->id) {
            $companyName = crm_Companies::fetchField($rec->forwarderId, 'name');
            $titleArr[] = str::limitLen($companyName, 32);
        }

        $titleArr[] = str::limitLen($rec->title, 32);
        $titleArr[] = static::getHandle($rec->id);
        $recTitle = implode(' / ', $titleArr);

        if ($escaped) {
            $recTitle = type_Varchar::escape($recTitle);
        }
        
        return $recTitle;
    }


    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setFieldTypeParams('folder', array('containingDocumentIds' => trans_Lines::getClassId()));
        $data->listFilter->FLD('lineState', 'enum(pendingAndActive=Заявка+Активни,all=Всички,draft=Чернова,pending=Заявка,active=Активен,closed=Затворен)', 'caption=Състояние');
        $data->listFilter->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
        $data->listFilter->FLD('countryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава');
        $data->listFilter->showFields .= ',lineState,storeId,countryId,search';
        $showFields = arr::make($data->listFilter->showFields, true);
        unset($showFields['filterDateField']);
        $data->listFilter->showFields = implode(',', $showFields);
        if ($selectedStore = core_Permanent::get('storeFilter' . core_Users::getCurrent())) {
            $data->listFilter->setDefault('storeId', $selectedStore);
        }

        $data->listFilter->setDefault('lineState', 'pendingAndActive');
        $data->listFilter->input();

        if ($filterRec = $data->listFilter->rec) {
            if (isset($filterRec->lineState) && $filterRec->lineState != 'all') {
                if ($filterRec->lineState == 'pendingAndActive') {
                    $data->query->where("#state = 'pending' OR #state = 'active'");
                } else {
                    $data->query->where("#state = '{$filterRec->lineState}'");
                }
            }

            if (isset($filterRec->countryId)) {
                $data->query->where("LOCATE('|{$filterRec->countryId}|', #countries)");
            }

            if (isset($filterRec->storeId)) {
                $data->query->where("LOCATE('|{$filterRec->storeId}|', #stores)");
                core_Permanent::set('storeFilter' . core_Users::getCurrent(), $filterRec->storeId, 24 * 60 * 100);
            } else {
                core_Permanent::remove('storeFilter' . core_Users::getCurrent());
            }
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if (!$data->toolbar->haveButton('btnClose')) {
            if (self::countDocumentsByState($rec->id, 'draft,pending') && $rec->state == 'active') {
                $data->toolbar->addBtn('Затваряне', array(), false, array('error' => 'Линията не може да бъде затворена докато има неактивирани документи към нея|*!', 'title' => 'Затваряне на транспортна линия'));
            }
        }

        if (!$data->toolbar->haveButton('btnActivate')) {
            if (in_array($rec->state, array('draft', 'pending')) && self::countDocumentsByState($rec->id, 'pending,draft', 'store_iface_DocumentIntf')) {
                $data->toolbar->addBtn('Активиране', array(), false, array('error' => 'В транспортната линия има заявки, чернови или оттеглени експедиционни документи|*!', 'ef_icon' => 'img/16/lightning.png', 'title' => 'Активиране на транспортната линия'));
            }
        }

        // Подмяна на бутона за принтиране с такъв да отчита натиснатия таб на детайла
        $printBtnId = plg_Printing::getPrintBtnId($mvc, $rec->id);
        if ($data->toolbar->buttons[$printBtnId]) {
            $data->toolbar->removeBtn[$printBtnId];
            $url = array($mvc, 'single', $rec->id, 'Printing' => 'yes', 'Width' => 'yes', 'lineTab' => Request::get('lineTab'));
            $data->toolbar->addBtn('Печат', $url, 'target=_blank,row=2', "id={$printBtnId},target=_blank,row=2,ef_icon = img/16/printer.png,title=Печат на документа");
        }

        if (Request::get('editTrans')) {
            bgerp_Notifications::clear(getCurrentUrl(), '*');
        }
    }


    /**
     * След подготовка на формата
     */
    protected static function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
        $form = &$data->form;
        $form->setFieldTypeParams('start', array('defaultTime' => trans_Setup::get('START_WORK_TIME')));
        $vehicleOptions = trans_Vehicles::makeArray4Select();
        if (countR($vehicleOptions) && is_array($vehicleOptions)) {
            $form->setSuggestions('vehicle', array('' => '') + arr::make($vehicleOptions, true));
        }

        $form->setOptions('forwarderPersonId', trans_Vehicles::getDriverOptions());
        if ($data->form->toolbar->haveButton('activate')) {
            $data->form->toolbar->removeBtn('activate');
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if (isset($data->form->rec->id)) {
            if (trans_LineDetails::fetchField("#lineId = {$data->form->rec->id}")) {
                $data->form->toolbar->removeBtn('save');
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if ($rec->start < dt::today()) {
                $form->setError('start', 'Не може да се създаде линия за предишен ден!');
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-single'])) {
            if (isset($rec->defaultCaseId)) {
                $row->defaultCaseId = cash_Cases::getHyperlink($rec->defaultCaseId, true);
                $allCases = keylist::toArray($rec->cases);
                if (countR($allCases) == 1 && array_key_exists($rec->defaultCaseId, $allCases)) {
                    unset($row->cases);
                }
            }

            if (!empty($rec->vehicle)) {
                if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $rec->vehicle))) {
                    $row->vehicle = trans_Vehicles::getHyperlink($vehicleRec->id, true);
                    $row->regNumber = trans_Vehicles::getVerbal($vehicleRec, 'number');
                }
            }

            $ownCompanyData = crm_Companies::fetchOwnCompany();
            $row->myCompany = ht::createLink($ownCompanyData->company, crm_Companies::getSingleUrlArray($ownCompanyData->companyId));

            $createdByUserLink = crm_Profiles::createLink($rec->createdBy);
            $row->logistic = core_Users::getVerbal($rec->createdBy, 'names');
            $row->logistic .= " ({$createdByUserLink})";

            if (isset($rec->forwarderPersonId) && !Mode::isReadOnly()) {
                $row->forwarderPersonId = ht::createLink($row->forwarderPersonId, crm_Persons::getSingleUrlArray($rec->forwarderPersonId));
            }

            if (isset($rec->forwarderId)) {
                $row->forwarderId = ht::createLink(crm_Companies::getVerbal($rec->forwarderId, 'name'), crm_Companies::getSingleUrlArray($rec->forwarderId));
            }

            // Лайв изчисление на общите ЛЕ
            $transUnitsTotal = array();
            $dQuery = trans_LineDetails::getQuery();
            $dQuery->where("#lineId = {$rec->id} AND #containerState != 'rejected' AND #status != 'removed'");
            while ($dRec = $dQuery->fetch()) {
                $Document = doc_Containers::getDocument($dRec->containerId);
                if (!array_key_exists($dRec->containerId, $mvc->cacheLineInfo)) {
                    $mvc->cacheLineInfo[$dRec->containerId] = $Document->getTransportLineInfo($rec->id);
                }
                $transportInfo = $mvc->cacheLineInfo[$dRec->containerId];
                if (is_array($transportInfo['transportUnits'])) {
                    trans_Helper::sumTransUnits($transUnitsTotal, $transportInfo['transportUnits']);
                }
            }

            $countries = keylist::toArray($rec->countries);
            if(countR($countries) != 1){
                unset($row->places);
            } else {
                $onlyCountryId = key($countries);
                if($onlyCountryId == drdata_Countries::getIdByName('Bulgaria') && !empty($rec->places)){
                    unset($row->countries);
                }
            }
        }

        // Показване на готовността
        $row->countStoreDocuments = $mvc->getVerbal($rec, 'countStoreDocuments');
        $row->countStoreDocuments = ht::createHint($row->countStoreDocuments, 'Брой складови документи', 'noicon', false);
        $row->countActiveDocuments = $mvc->getVerbal($rec, 'countActiveDocuments');
        $row->countActiveDocuments = ht::createHint($row->countActiveDocuments, 'Брой активирани складови документи', 'noicon', false);
        $row->countReadyDocuments = $mvc->getVerbal($rec, 'countReadyDocuments');
        $row->countReadyDocuments = ht::createHint($row->countReadyDocuments, 'Брой нагласени складови документи', 'noicon', false);

        $row->readiness = "{$row->countStoreDocuments} / {$row->countActiveDocuments} / {$row->countReadyDocuments}";
        if (!Mode::isReadOnly()) {
            if ($rec->countStoreDocuments != ($rec->countActiveDocuments + $rec->countReadyDocuments)) {
                $row->readiness = "<span class='red'>{$row->readiness}</span>";
            }
        }

        $row->handler = $mvc->getHyperlink($rec->id, true);
        $row->baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        if (isset($fields['-list'])) {
            $row->start = str_replace(' ', '<br>', $row->start);
            if (!empty($rec->stores)) {
                $row->stores = $mvc->getVerbal($rec, 'stores');
                $row->handler .= "<div class='small'>" . tr('Складове') . ": {$row->stores}</div>";
            }

            if (!empty($rec->cases)) {
                $row->cases = $mvc->getVerbal($rec, 'cases');
                $row->handler .= "<div class='small'> " . tr('Каси') . ": {$row->cases}</div>";
            }
            $transUnitsTotal = $rec->transUnitsTotal;
        }

        $row->transUnitsTotal = empty($transUnitsTotal) ? "<span class='quiet'>N/A</span>" : trans_Helper::displayTransUnits($transUnitsTotal, false, '<br>');
    }


    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = $data->rec;
        $row = $data->row;

        $amount = $amountExpected = $amountReturned = $weight = $volume = 0;
        $sumWeight = $sumVolume = true;

        $dQuery = trans_LineDetails::getQuery();
        $dQuery->where("#lineId = {$rec->id} AND #containerState != 'rejected' AND #status != 'removed'");

        while ($dRec = $dQuery->fetch()) {
            $Document = doc_Containers::getDocument($dRec->containerId);
            $transInfo = $Document->getTransportLineInfo($rec->id);
            $isStoreDocument = $Document->haveInterface('store_iface_DocumentIntf');

            if (!$isStoreDocument) {
                if ($transInfo['baseAmount'] < 0) {
                    if($dRec->containerState == 'active'){
                        $amountReturned += $transInfo['baseAmount'];
                    }
                } else {
                    if($dRec->containerState == 'active'){
                        $amount += $transInfo['baseAmount'];
                    }
                    $amountExpected += $transInfo['baseAmount'];
                }
            }

            // Сумиране на теглото от редовете
            if ($sumWeight === true) {
                if ($transInfo['weight']) {
                    $weight += $transInfo['weight'];
                } elseif ($isStoreDocument) {
                    unset($weight);
                    $sumWeight = false;
                }
            }

            // Сумиране на обема от редовете
            if ($sumVolume === true) {
                if ($transInfo['volume']) {
                    $volume += $transInfo['volume'];
                } elseif ($isStoreDocument) {
                    unset($volume);
                    $sumVolume = false;
                }
            }
        }

        // Показване на сумарната информация
        $row->weight = (!empty($weight)) ? cls::get('cat_type_Weight')->toVerbal($weight) : "<span class='quiet'>N/A</span>";
        $row->volume = (!empty($volume)) ? cls::get('cat_type_Volume')->toVerbal($volume) : "<span class='quiet'>N/A</span>";

        $row->totalAmountExpected = core_Type::getByName('double(decimals=2)')->toVerbal($amountExpected);
        $row->totalAmountExpected = ht::styleNumber($row->totalAmountExpected, $amount);

        $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        if($amount < $amountExpected){
            $row->totalAmount = "<b class='red'>{$row->totalAmount}</b>";
        } else {
            $row->totalAmount = "<span style='color:green'>{$row->totalAmount}</span>";
        }

        $row->totalAmountReturn = core_Type::getByName('double(decimals=2)')->toVerbal(abs($amountReturned));
        $row->totalAmountReturn = ht::styleNumber($row->totalAmountReturn, abs($amountReturned));
    }


    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $row = cls::get(get_called_class())->recToVerbal($rec, 'title,readiness');

        $row = (object)array(
            'title' => $this->getRecTitle($rec),
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $row->title,
            'subTitle' => $row->readiness,
        );

        return $row;
    }


    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->push('trans/tpl/LineStyles.css', 'CSS');
    }


    /**
     * Връща броя на документите в посочената линия
     *
     * @param int $id - ид
     * @param array $states - състояния
     * @param null|string $interface - интерфейс на документи
     * @return int                   - брой документи в линията, отговарящи на условията
     */
    private static function countDocumentsByState($id, $states, $interface = null)
    {
        $states = arr::make($states);
        $query = trans_LineDetails::getQuery();
        $query->where("#lineId = {$id} AND #status != 'removed'");
        $query->in('containerState', $states);

        if ($interface) {
            $iOptions = array_keys(core_Classes::getOptionsByInterface($interface));
            if (countR($iOptions)) {
                $query->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=containerId');
                $query->in('docClass', $iOptions);
            }
        }

        return $query->count();
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);

        // Изчисляване на готовите и не-готовите редове
        $dQuery = trans_LineDetails::getQuery();
        $dQuery->where("#lineId = {$rec->id}");
        $dQuery->where("#containerState != 'rejected' AND #status != 'removed'");
        $dQuery->show('status,containerId,containerState');

        $stores = $cases = $countries = $transUnitsTotal = $places = array();
        $rec->countStoreDocuments = $rec->countActiveDocuments = $rec->countReadyDocuments = 0;
        while ($dRec = $dQuery->fetch()) {
            $Doc = doc_Containers::getDocument($dRec->containerId);
            if (!array_key_exists($dRec->containerId, $this->cacheLineInfo)) {
                $this->cacheLineInfo[$dRec->containerId] = $Doc->getTransportLineInfo($rec->id);
            }
            $lineInfo = $this->cacheLineInfo[$dRec->containerId];
            if (!empty($lineInfo['place'])) {
                $places[$lineInfo['place']] = bglocal_Address::canonizePlace($lineInfo['place']);
            }

            if (!empty($lineInfo['countryId'])) {
                $countries[$lineInfo['countryId']] = $lineInfo['countryId'];
            }

            if ($Doc->haveInterface('store_iface_DocumentIntf')) {
                $rec->countStoreDocuments++;
                if ($dRec->containerState == 'active') {
                    $rec->countActiveDocuments++;
                }

                if(core_Packs::isInstalled('rack')){
                    if (rack_Zones::fetchField("#containerId = {$dRec->containerId} AND #readiness >= 1")) {
                        $rec->countReadyDocuments++;
                    }
                }

                if(is_array($lineInfo['transportUnits'])){
                    trans_Helper::sumTransUnits($transUnitsTotal, $lineInfo['transportUnits']);
                }
            }

            $stores = array_merge($stores, $lineInfo['stores']);
            $cases = array_merge($cases, $lineInfo['cases']);
        }

        // Сумиране на засегнатите държави
        $rec->countries = countR($countries) ? keylist::fromArray($countries) : null;

        // Запис на изчислените полета
        $rec->stores = null;
        if (countR($stores)) {
            $stores = array_combine(array_values($stores), $stores);
            $rec->stores = keylist::fromArray($stores);
        }

        $rec->cases = null;
        if (countR($cases)) {
            $cases = array_combine(array_values($cases), $cases);
            $rec->cases = keylist::fromArray($cases);
        }

        $rec->places = null;
        if(countR($places)){
            $rec->places = implode(', ', $places);
        }

        $rec->transUnitsTotal = countR($transUnitsTotal) ? $transUnitsTotal : null;
        $rec->modifiedOn = dt::now();
        $rec->modifiedBy = core_Users::getCurrent();
        $this->save($rec);
    }


    /**
     * Състояние на нишката
     */
    public static function getThreadState($id)
    {
        $rec = static::fetchRec($id);
        if ($rec->state == 'closed') return 'closed';

        return 'opened';
    }

    /**
     * Връща всички избираеми линии в посочената папка
     *
     * @param int|null $folderId - ид на папка, null за всички
     * @return array $linesArr   - масив с опции
     */
    public static function getSelectableLines($folderId = null)
    {
        $query = self::getQuery();
        $query->where("#state = 'pending' || #state = 'active'");
        $query->orderBy('id', 'DESC');
        if(isset($folderId)){
            $query->where("#folderId = {$folderId}");
        }
        $recs = $query->fetchAll();

        $res = $pendings = $active = array();

        // Подготвяне на опциите и групирането им
        array_walk($recs, function ($rec) use (&$pendings, &$active) {
            $title = trans_Lines::getRecTitle($rec, false);
            if($rec->state == 'pending'){
                $pendings[$rec->id] = $title;
            } else {
                $opt = new stdClass();
                $opt->attr = array('class' => 'state-rejected');
                $opt->title = $title;
                $active[$rec->id] = $opt;
            }
        });

        if(countR($pendings)){
            $res = array('p' => (object) array('group' => true, 'title' => tr('Чакащи'))) + $pendings;
        }

        if(countR($active)){
            $res += array('a' => (object) array('group' => true, 'title' => tr('Активирани'))) + $active;
        }

        return $res;
    }


    /**
     * Изпълнява се преди записа
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if ($rec->__isReplicate) {
            $rec->countStoreDocuments = 0;
            $rec->countActiveDocuments = 0;
            $rec->countReadyDocuments = 0;
        }
    }


    /**
     * Дефолтни данни, които да се попълват към коментар от документа
     *
     * @param mixed $rec - ид или запис
     * @param int|NULL $detId - допълнително ид, ако е нужно
     *
     * @return array $res     - дефолтните данни за коментара
     *               ['subject']     - събджект на коментара
     *               ['body']        - тяло на коментара
     *               ['sharedUsers'] - споделени потребители
     */
    public function getDefaultDataForComment($rec, $detId = null)
    {
        $res = array();
        if (empty($detId)) return $res;

        $docContainerId = trans_LineDetails::fetchField($detId, 'containerId');
        $Document = doc_Containers::getDocument($docContainerId);

        $documentRec = $Document->fetch();
        $res['body'] = 'За: #' . $Document->getHandle() . "\n";

        $users = '';
        $users = keylist::addKey($users, $documentRec->createdBy);
        $users = keylist::addKey($users, $documentRec->modifiedBy);
        $users = keylist::merge($users, $documentRec->sharedUsers);
        $res['sharedUsers'] = $users;

        return $res;
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // При промяна на състоянието да се инвалидира, кеша на документите от нея
        if (in_array($rec->state, array('active', 'closed', 'rejected'))) {
            $dQuery = trans_LineDetails::getQuery();
            $dQuery->where("#lineId = {$rec->id}");
            $dQuery->show('containerId');
            while ($dRec = $dQuery->fetch()) {
                doc_DocumentCache::cacheInvalidation($dRec->containerId);
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate' && isset($rec)) {
            if (!trans_LineDetails::fetchField("#lineId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            } elseif (self::countDocumentsByState($rec->id, 'pending,draft', 'store_iface_DocumentIntf')) {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'close' && isset($rec)) {
            if (self::countDocumentsByState($rec->id, 'draft,pending')) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Затваряне на транспортни линии по разписание
     */
    public function cron_CloseTransLines()
    {
        $activeTime = trans_Setup::get('LINES_ACTIVATED_AFTER');
        $pendingTime = trans_Setup::get('LINES_PENDING_AFTER');

        $activeFrom = dt::addSecs(-1 * $activeTime);
        $pendingFrom = dt::addSecs(-1 * $pendingTime);

        $now = dt::now();
        $query = $this->getQuery();
        $query->where("#state = 'active' || #state = 'pending'");

        while ($rec = $query->fetch()) {
            if (self::countDocumentsByState($rec->id, 'draft,pending')) continue;

            // Затварят се активните и заявките, на които им е изтекло времето
            if ($rec->state == 'active') {
                $date = !empty($rec->activatedOn) ? $rec->activatedOn : $rec->modifiedOn;
                if ($date < $activeFrom) {
                    $rec->state = 'closed';
                    $rec->brState = 'active';
                    $this->save($rec, 'state,brState,modifiedOn,modifiedBy');
                    $this->logWrite('Автоматично приключване на активна линия', $rec->id);
                }
            } else {
                $start = $rec->start;
                if (strpos($rec->start, ' 00:00:00')) {
                    $start = str_replace(' 00:00:00', ' 23:59:59', $rec->start);
                }

                // Ако началото е в миналото, и не е бутана дълго време
                if ($start < $now && $rec->modifiedOn < $pendingFrom) {
                    $rec->state = 'closed';
                    $rec->brState = 'pending';
                    $this->save($rec, 'state,brState,modifiedOn,modifiedBy');
                    $this->logWrite('Автоматично приключване на линия на заявка', $rec->id);
                }
            }
        }
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }

        // Добавяне и на номера на МПС-то в ключовите думи
        if(!empty($rec->vehicle)){
            if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $rec->vehicle))) {
                $normalizedNumber = plg_Search::normalizeText($vehicleRec->number);
                $res .= ' ' . $normalizedNumber . " " . str::removeWhiteSpace($normalizedNumber);
            } else {
                $res .= " " . str::removeWhiteSpace(plg_Search::normalizeText($rec->vehicle));
            }
        }

        if (isset($rec->id)) {
            $dQuery = trans_LineDetails::getQuery();
            $dQuery->where("#lineId = {$rec->id}");
            while ($dRec = $dQuery->fetch()) {
                $Document = doc_Containers::getDocument($dRec->containerId);
                if (!array_key_exists($dRec->containerId, $mvc->cacheLineInfo)) {
                    $mvc->cacheLineInfo[$dRec->containerId] = $Document->getTransportLineInfo($rec->id);
                }
                $tInfo = $mvc->cacheLineInfo[$dRec->containerId];

                if (isset($tInfo['countryId'])) {
                    $countryNameBg = drdata_Countries::getCountryName($tInfo['countryId'], 'bg');
                    $countryNameEn = drdata_Countries::getCountryName($tInfo['countryId'], 'en');
                    $res .= ' ' . plg_Search::normalizeText($countryNameBg) . ' ' . plg_Search::normalizeText($countryNameEn);
                }

                foreach (array('address', 'addressInfo', 'contragentName') as $fld) {
                    if (!empty($tInfo[$fld])) {
                        $res .= ' ' . plg_Search::normalizeText($tInfo[$fld]);
                    }
                }

                $res .= ' ' . plg_Search::normalizeText($Document->getVerbal('createdBy'));
            }
        }
    }


    /**
     * Коя е дефолт папката за нови записи
     */
    public function getDefaultFolder()
    {
        // Дефолтната папка е тази към която линия последно е закачан документ
        $cu = core_Users::getCurrent();
        $tQuery = trans_LineDetails::getQuery();
        $tQuery->EXT('folderId', 'trans_Lines', 'externalName=folderId,externalKey=lineId');
        $tQuery->where("#modifiedBy = '{$cu}'");
        $tQuery->show('folderId');
        $tQuery->orderBy('modifiedOn', 'DESC');

        // Ако няма е тази, в която последно е създавал линия
        $folderId = $tQuery->fetch()->folderId;
        if(empty($folderId)){
            $query = trans_Lines::getQuery();
            $query->where("#createdBy = {$cu} AND #state != 'rejected'");
            $query->orderBy("#createdOn", 'DESC');
            $query->show('folderId');
            $folderId = $query->fetch()->folderId;
        }
        if(isset($folderId)) return $folderId;

        // Ако не е намерена папка, в която последно е създаване връщане папката проект за транспортни линии
        return parent::getDefaultFolder();
    }


    /**
     * Връща наличните за избор папки на ТЛ
     *
     * @return array $options
     */
    public static function getSelectableFolderOptions()
    {
        $Type = core_Type::getByName("key2(mvc=doc_Folders,select=title)");
        $Type->params['restrictViewAccess'] = 'yes';
        $Type->params['containingDocumentIds'] = trans_Lines::getClassId();
        $options = $Type->getOptions(null);

        return $options;
    }
}
