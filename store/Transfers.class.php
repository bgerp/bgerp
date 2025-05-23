<?php


/**
 * Клас 'store_Transfers' - Документ за междускладови трансфери
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Transfers extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Междускладови трансфери';


    /**
     * Абревиатура
     */
    public $abbr = 'Str';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_Transfer, acc_AllowArticlesCostCorrectionDocsIntf,trans_LogisticDataIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, store_Wrapper, plg_Sorting, plg_Printing, store_plg_Request, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, trans_plg_LinesPlugin, doc_plg_BusinessDoc,plg_Clone,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning,bgerp_plg_Export, change_Plugin';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_TransfersDetails';


    /**
     * Права за плъгин-а bgerp_plg_Export
     */
    public $canExport = 'ceo, store';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'fromStore, toStore, folderId, note';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';


    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';


    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store';


    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, fromStore, toStore, weight, volume,lineId, folderId, createdOn, createdBy';


    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title=bottom';


    /**
     * Детайла, на модела
     */
    public $details = 'store_TransfersDetails';


    /**
     * Кой е главния детайл
     */
    public $mainDetail = 'store_TransfersDetails';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Междускладов трансфер';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutTransfers.shtml';


    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'store/tpl/SingleLayoutTransfersNarrow.shtml';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.5|Логистика';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * Как се казва полето в което е избран склада
     */
    public $storeFieldName = 'fromStore';


    /**
     * Дата на очакване
     */
    public $termDateFld = 'deliveryTime';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/transfers.png';


    /**
     * Полета за филтър по склад
     */
    public $filterStoreFields = 'fromStore,toStore';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'deliveryTime';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,weight,volume,weightInput,volumeInput,deliveryTime,palletCount,storeReadiness';


    /**
     * Показва броя на записите в лога за съответното действие в документа
     */
    public $showLogTimeInHead = 'Документът се връща в чернова=3';


    /**
     * Поле показващо към кой склад ще е движението
     */
    public $toStoreFieldName = 'toStore';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, modifiedOn, valior, readyOn, deliveryTime, shipmentOn, deliveryOn';


    /**
     * Кое поле ще се оказва за подредбата на детайла
     */
    public $detailOrderByField = 'detailOrderBy';
    
     
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'note, detailOrderBy';


    /**
     * Кои полета да могат да се експортират в CSV формат
     */
    public $exportableCsvFields = 'id,valior,fromStore,toStore,note,state';


    /**
     * Възможност за експортиране на детайлите в csv експорта от лист изгледа
     */
    public $allowDetailCsvExportFromList = true;


    /**
     * Да се показват ли винаги полетата за промяна на артикули при създаване
     * @var bool
     */
    public $autoAddDetailsToChange = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата');
        $this->FLD('fromStore', 'key(mvc=store_Stores,select=name)', 'caption=От склад,mandatory,silent');
        $this->FLD('toStore', 'key(mvc=store_Stores,select=name)', 'caption=До склад,mandatory');
        $this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');

        // Доставка
        $startTime = trans_Setup::get('START_WORK_TIME');
        $this->FLD('deliveryTime', "datetime(defaultTime={$startTime})", 'caption=Товарене');
        $this->FLD('deliveryOn', "datetime(defaultTime={$startTime})", 'caption=Доставка');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        $this->FLD('storeReadiness', 'percent', 'input=none,caption=Готовност на склада');

        // Допълнително
        $this->FLD('detailOrderBy', 'enum(auto=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Артикули->Подреждане по,notNull,value=auto');
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Състояние, input=none');

        $this->setDbIndex('fromStore');
        $this->setDbIndex('toStore');
        $this->setDbIndex('lineId');
        $this->setDbIndex('state');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'toStore')) {
            $requiredRoles = 'no_one';
        }

        if ($action == 'pending' && isset($rec) && $rec->id) {
            $Detail = cls::get($mvc->mainDetail);
            if (!$Detail->fetchField("#{$Detail->masterKey} = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }

        // Ако ще се създава към документ да трябва да се създава само към ПОП за получени чужди артикули
        if ($action == 'add' && isset($rec->originId)) {
            $Document = doc_Containers::getDocument($rec->originId);
            if(!$Document->isInstanceOf('store_ConsignmentProtocols')){
                $requiredRoles = 'no_one';
            } else {
                $docRec = $Document->fetch('state,productType');
                if($docRec->productType != 'other' || $docRec->state != 'active'){
                    $requiredRoles = 'no_one';
                } elseif(!store_ConsignmentProtocolDetailsReceived::count("#protocolId = {$Document->that}")){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->fromStore = store_Stores::getHyperlink($rec->fromStore, true);
        $row->toStore = store_Stores::getHyperlink($rec->toStore, true);

        if ($fields['-single']) {
            if ($rec->fromStore) {
                $fromStoreLocation = store_Stores::fetchField($rec->fromStore, 'locationId');
                if ($fromStoreLocation) {
                    $row->fromAdress = crm_Locations::getAddress($fromStoreLocation);
                }
            }

            if ($rec->toStore) {
                $toStoreLocation = store_Stores::fetchField($rec->toStore, 'locationId');
                if ($toStoreLocation) {
                    $row->toAdress = crm_Locations::getAddress($toStoreLocation);
                }
            }
        }

        if ($fields['-list']) {
            $row->title = $mvc->getLink($rec->id, 0);

            if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
                $row->title = '<b>' . $row->title . '</b>';
                $row->title .= '  ' . $row->fromStore . ' » ' . $row->toStore;
                $row->createdBy = crm_Profiles::createLink($rec->createdBy);
                $row->createdOn = $mvc->getVerbal($rec, 'createdOn');
                $row->title .= "<span class='fright'>" . $row->createdOn . ' ' . tr('от') . ' ' . $row->createdBy . '</span>';
            }
        }

        if ($rec->state != 'pending') {
            unset($row->storeReadiness);
        } else {
            $row->storeReadiness = isset($rec->storeReadiness) ? $row->storeReadiness : "<b class='quiet'>N/A</b>";
        }

        if (Mode::isReadOnly()) {
            unset($row->storeReadiness, $row->zoneReadiness);
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('fromStore', store_Stores::getCurrent('id', false));
        $Cover = doc_Folders::getCover($data->form->rec->folderId);
        if($Cover->isInstanceOf('store_Stores')){
            $data->form->setDefault('toStore', $Cover->that);
        }

        $data->form->setDefault('detailOrderBy', core_Permanent::get("{$mvc->className}_detailOrderBy"));

        if (!trans_Lines::count("#state = 'active'")) {
            $data->form->setField('lineId', 'input=none');
        }

        // При редакция, ако няма права до склада, да е избрано
        if ($data->form->rec->id) {
            foreach (array('fromStore', 'toStore') as $fName) {
                $optArr = $data->form->fields[$fName]->type->prepareOptions();
                if (!$optArr[$data->form->rec->{$fName}]) {
                    $data->form->setOptions($fName, array($data->form->rec->{$fName} => store_Stores::getVerbal($data->form->rec->{$fName}, 'name')));
                    $data->form->setDefault($fName, $data->form->rec->{$fName});
                }
            }
        }
    }


    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if ($rec->fromStore == $rec->toStore) {
                $form->setError('toStore', 'Складовете трябва да са различни');
            }

            if(empty($rec->id)){
                core_Permanent::set("{$mvc->className}_detailOrderBy", $rec->detailOrderBy, core_Permanent::FOREVER_VALUE);
            }

            $rec->folderId = store_Stores::forceCoverAndFolder($rec->toStore);
        }
    }


    /**
     * Може ли да бъде добавен документа в папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);

        return cls::haveInterface('store_iface_TransferFolderCoverIntf', $folderClass);
    }


    /**
     * Връща информацията за документа в папката
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        $subTitle = '<b>' . store_Stores::getTitleById($rec->fromStore) . '</b> » <b>' . store_Stores::getTitleById($rec->toStore) . '</b>';

        $row = (object)array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'subTitle' => $subTitle,
            'recTitle' => $title,
        );

        return $row;
    }


    /**
     * Връща масив от използваните нестандартни артикули в СР-то
     *
     * @param int $id - ид на СР
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        $res = array();
        $dQuery = store_TransfersDetails::getQuery();
        $dQuery->EXT('state', 'store_Transfers', 'externalKey=transferId');
        $dQuery->where("#transferId = '{$id}'");
        while ($dRec = $dQuery->fetch()) {
            $cid = cat_Products::fetchField($dRec->newProductId, 'containerId');
            $res[$cid] = $cid;
        }

        return $res;
    }


    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('store_iface_TransferFolderCoverIntf');
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Споделяме текущия потребител със нишката на заданието
        $cu = core_Users::getCurrent();
        doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
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
        $products = array();
        $rec = $this->fetchRec($id);
        $query = store_TransfersDetails::getQuery();
        $query->where("#transferId = {$rec->id}");
        while ($dRec = $query->fetch()) {
            if($option == 'storable'){
                $canStore = cat_Products::fetchField($dRec->newProductId, 'canStore');
                if($canStore != 'yes') continue;
            }

            if (!array_key_exists($dRec->newProductId, $products)) {
                $products[$dRec->newProductId] = (object)array('productId' => $dRec->newProductId,
                    'quantity' => 0,
                    'name' => cat_Products::getTitleById($dRec->newProductId, false),
                    'amount' => null,
                    'transportWeight' => $dRec->weight,
                    'transportVolume' => $dRec->volume,
                    'inStores' => array($rec->toStore => 0),
                );
            }

            $products[$dRec->newProductId]->quantity += $dRec->quantity;
            $products[$dRec->newProductId]->inStores[$rec->toStore] += $dRec->quantity;
        }

        return $products;
    }


    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec - ид или запис на документ
     * @return array      - логистичните данни
     *
     *		string(2)     ['fromCountry']         - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']           - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']           - град за натоварване
     * 		string|NULL   ['fromAddress']         - адрес за натоварване
     *  	string|NULL   ['fromCompany']         - фирма
     *   	string|NULL   ['fromPerson']          - лице
     *      string|NULL   ['fromPersonPhones']    - телефон на лицето
     *      string|NULL   ['fromLocationId']      - лице
     *      string|NULL   ['fromAddressInfo']     - особености
     *      string|NULL   ['fromAddressFeatures'] - особености на транспорта
     * 		datetime|NULL ['loadingTime']         - дата на натоварване
     * 		string(2)     ['toCountry']           - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']             - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']             - град за разтоварване
     *  	string|NULL   ['toAddress']           - адрес за разтоварване
     *   	string|NULL   ['toCompany']           - фирма
     *   	string|NULL   ['toPerson']            - лице
     *      string|NULL   ['toLocationId']        - лице
     *      string|NULL   ['toPersonPhones']      - телефон на лицето
     *      string|NULL   ['toAddressInfo']       - особености
     *      string|NULL   ['toAddressFeatures']   - особености на транспорта
     *      string|NULL   ['instructions']        - инструкции
     * 		datetime|NULL ['deliveryTime']        - дата на разтоварване
     * 		text|NULL 	  ['conditions']          - други условия
     *		varchar|NULL  ['ourReff']             - наш реф
     * 		double|NULL   ['totalWeight']         - общо тегло
     * 		double|NULL   ['totalVolume']         - общ обем
     */
    public function getLogisticData($rec)
    {
        $rec = $this->fetchRec($rec);
        $res = array();
        $res['ourReff'] = '#' . $this->getHandle($rec);
        $res['loadingTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : $rec->valior . ' ' . bgerp_Setup::get('START_OF_WORKING_DAY');

        foreach (array('from', 'to') as $part) {
            if ($locationId = store_Stores::fetchField($rec->{"{$part}Store"}, 'locationId')) {
                $location = crm_Locations::fetch($locationId);

                $res["{$part}Country"] = drdata_Countries::fetchField($location->countryId, 'commonName');
                $res["{$part}PCode"] = !empty($location->pCode) ? $location->pCode : null;
                $res["{$part}Place"] = !empty($location->place) ? $location->place : null;
                $res["{$part}Address"] = !empty($location->address) ? $location->address : null;
                $res["{$part}Person"] = !empty($location->mol) ? $location->mol : null;
                $res["{$part}LocationId"] = $location->id;
                $res["{$part}AddressInfo"] = $location->specifics;
                $res["{$part}AddressFeatures"] = $location->features;
            }
        }

        $res['totalWeight'] = isset($rec->weightInput) ? $rec->weightInput : $rec->weight;
        $res['totalVolume'] = isset($rec->volumeInput) ? $rec->volumeInput : $rec->volume;

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

        return $this->save($rec);
    }


    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param mixed $id
     * @param int $lineId
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['cases']          array       - каси в документа
     *               ['zoneId']         array       - ид на зона, в която е нагласен документа
     *               ['zoneReadiness']  int         - готовност в зоната в която е нагласен документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array       - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     *               ['address']        double|NULL - адрес ба диставка
     *               ['storeMovement']  string|NULL - посока на движението на склада
     *               ['locationId']     string|NULL - ид на локация на доставка (ако има)
     *               ['addressInfo']    string|NULL - информация за адреса
     *               ['countryId']      string|NULL - ид на държава
     *               ['place']          string|NULL - населено място
     *               ['features']       array       - свойства на адреса
     */
    public function getTransportLineInfo_($rec, $lineId)
    {
        $rec = static::fetchRec($rec);
        $row = $this->recToVerbal($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'amountVerbal' => null, 'currencyId' => null, 'notes' => $rec->lineNotes);

        $res['stores'] = array($rec->fromStore, $rec->toStore);
        $res['address'] = $row->toAdress;
        $res['storeMovement'] = 'out';
        $res['cases'] = array();

        if($toStoreLocationId = store_Stores::fetchField($rec->toStore, 'locationId')){
            $toStoreLocation = crm_Locations::fetch($toStoreLocationId);
            $res['locationId'] = $toStoreLocation->id;
            $res['addressInfo'] = $toStoreLocation->comment;
            $res['countryId'] = $toStoreLocation->countryId;
            $res['place'] = $toStoreLocation->place;
            if(!empty($toStoreLocation->features)){
                $res['features'] = keylist::toArray($toStoreLocation->features);
            }
        }

        return $res;
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
        $dQuery = store_TransfersDetails::getQuery();
        $dQuery->where("#transferId = {$id}");
        $dQuery->show('newProductId, quantity');

        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->fromStore, $rec->state, 'newProductId');

        return $warning;
    }


    /**
     * Извиква се преди подготовката на колоните
     */
    protected static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $data->listFields = 'deliveryTime,valior, title=Документ, folderId , weight, volume,lineId';
        }
    }


    /**
     * Връща дефолтен коментар при връзка на документи
     *
     * @param int $id
     * @param string $comment
     *
     * @return string
     */
    public function getDefaultLinkedComment($id, $comment)
    {
        $rec = $this->fetchRec($id);
        $fromStore = store_Stores::getTitleById($rec->fromStore);
        $toStore = store_Stores::getTitleById($rec->toStore);

        if (trim($comment)) {
            $comment .= '<br>';
        }

        $comment .= "{$fromStore} » {$toStore}";

        return $comment;
    }

    /**
     * Преди запис на документ
     *
     * @param core_Mvc $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Ако заданието е към сделка и е избран департамент, да се рутира към него
        if (empty($rec->id) && isset($rec->originId)) {

            // Ако МСТ е към ориджин ще се създава винаги в нова нишка
            $oldThreadId = $rec->threadId;
            $rec->folderId = store_Stores::forceCoverAndFolder($rec->toStore);
            $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn, $rec->createdBy);

            // Обновяване на информацията за контейнера и старата нишка, че документ се е преместил оттам
            $cRec = doc_Containers::fetch($rec->containerId);
            $cRec->threadId = $rec->threadId;
            doc_Containers::save($cRec, 'threadId, modifiedOn, modifiedBy');
            doc_Threads::updateThread($oldThreadId);
        }

        if(isset($rec->id)){
            $rec->_exToStoreId = $mvc->fetchField($rec->id, 'toStore', false);
        }
    }


    /**
     * След всеки запис в журнала
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if(core_Packs::isInstalled('batch')){

            // Ако е сменен дестинационния склад - да се обнови записа в черновата журнал на партидите
            if(isset($rec->_exToStoreId) && $rec->toStore != $rec->_exToStoreId){
                if(in_array($rec->state, array('draft', 'pending'))){
                    $Batches = cls::get('batch_BatchesInDocuments');
                    $bQuery = $Batches->getQuery();
                    $bQuery->where("#containerId = {$rec->containerId} AND #operation = 'in'");
                    while($bRec = $bQuery->fetch()){
                        $bRec->storeId = $rec->toStore;
                        $Batches->save_($bRec, 'storeId');
                    }
                }
            }
        }
    }


    /**
     * Кои детайли да се клонират с промяна
     *
     * @param stdClass $rec
     * @return array $res
     *          ['recs'] - записи за промяна
     *          ['detailMvc] - модел от който са
     */
    public function getDetailsToCloneAndChange_($rec)
    {
        if (isset($rec->originId) && empty($rec->id)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('store_ConsignmentProtocols')) {
                $Detail = cls::get('store_ConsignmentProtocolDetailsReceived');
                $id = $origin->that;

                $recs = array();
                $dQuery = $Detail->getQuery();
                $dQuery->where("#{$Detail->masterKey} = {$id}");
                while($dRec = $dQuery->fetch()){
                    $dRec->newProductId = $dRec->productId;

                    $inStoreQuantity = store_Products::getQuantities($dRec->productId, $rec->storeId)->quantity;
                    $quantity = min($inStoreQuantity, $dRec->quantity);
                    $dRec->quantity = $quantity;
                    $dRec->packQuantity = $dRec->quantity / $dRec->quantityInPack;
                    $recs[$dRec->id] = $dRec;
                }

                $res = array('recs' => $recs, 'detailMvc' => $Detail);

                return $res;
            }
        }
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $res = array();
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);

        $date = $this->getPlannedQuantityDate($rec);
        $Detail = cls::get('store_TransfersDetails');

        $dQuery = $Detail->getQuery();
        $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey=newProductId");
        $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey=newProductId");
        $dQuery->XPR('totalQuantity', 'double', "SUM(#{$Detail->quantityFld})");
        $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
        $dQuery->groupBy('newProductId');

        while ($dRec = $dQuery->fetch()) {
            $genericProductId = null;
            if($dRec->generic == 'yes'){
                $genericProductId = $dRec->newProductId;
            } elseif($dRec->canConvert == 'yes'){
                $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->newProductId}", 'genericProductId');
            }

            $res[] = (object)array('storeId'          => $rec->fromStore,
                                   'productId'        => $dRec->newProductId,
                                   'date'             => $date,
                                   'quantityIn'       => null,
                                   'quantityOut'      => $dRec->totalQuantity,
                                   'genericProductId' => $genericProductId);

            $res[] = (object)array('storeId'          => $rec->toStore,
                                   'productId'        => $dRec->newProductId,
                                   'date'             => $date,
                                   'quantityIn'       => $dRec->totalQuantity,
                                   'quantityOut'      => null,
                                   'genericProductId' => $genericProductId);
        }

        return $res;
    }


    /**
     * АПИ метод за добавяне на детайл към МСТ
     *
     * @param int $id
     * @param int $productId
     * @param int $packagingId
     * @param double $packQuantity
     * @param int $quantityInPack
     * @param null|string $batch
     * @return int
     * @throws core_exception_Expect
     */
    public static function addRow($id, $productId, $packagingId, $packQuantity, $quantityInPack, $batch = null)
    {
        // Проверки на параметрите
        expect($noteRec = self::fetch($id), "Няма МСТ с ид {$id}");
        expect($noteRec->state == 'draft', 'МСТ трябва да е чернова');

        expect($productRec = cat_Products::fetch($productId, 'canStore'), "Няма артикул с ид {$productId}");
        expect($productRec->canStore == 'yes', 'Артикулът трябва да е складируем');

        expect($packagingId, 'Няма мярка/опаковка');
        expect(cat_UoM::fetch($packagingId), "Няма опаковка/мярка с ид {$packagingId}");

        $packs = cat_Products::getPacks($productId);
        expect(isset($packs[$packagingId]), "Артикулът не поддържа мярка/опаковка с ид {$packagingId}");

        $Double = cls::get('type_Double');
        expect($quantityInPack = $Double->fromVerbal($quantityInPack), "Невалидно к-во {$quantityInPack}");
        expect($packQuantity = $Double->fromVerbal($packQuantity), "Невалидно к-во {$packQuantity}");
        $quantity = $quantityInPack * $packQuantity;

        $Detail = cls::get('store_TransfersDetails');
        $nRec = (object)array('transferId' => $id, 'newProductId' => $productId, 'packagingId' => $packagingId, 'quantity' => $quantity, 'quantityInPack' => $quantityInPack, 'batch' => $batch);
        $nRec->autoAllocate = !empty($row->batch);

        if(!empty($batch)) {
            expect($Def = batch_Defs::getBatchDef($productId), 'Опит за задаване на партида на артикул без партида');
            $msg = null;
            $Def->isValid($batch, $quantity, $msg);
            if ($msg) {
                expect(false, tr($msg));
            }
            $batch = $Def->normalize($batch);
            $nRec->_clonedWithBatches = true;
        }

        $Detail->save($nRec);

        if(!empty($batch)){
            batch_BatchesInDocuments::saveBatches($Detail, $nRec->id, array($batch => $nRec->quantity), true);
        }

        return $nRec->id;
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
        $rec = $this->fetchRec($rec);
        if($cache){
            $res = core_Cache::get($this->className, "earliestDateAllAvailable{$rec->containerId}");
        }

        if(!$cache || $res === false){
            $products = deals_Helper::sumProductsByQuantity('store_TransfersDetails', $rec->id, true, 'newProductId');
            $res = store_StockPlanning::getEarliestDateAllAreAvailable($rec->fromStore, $products);
            core_Cache::set($this->className, "earliestDateAllAvailable{$rec->containerId}", $res, 10);
        }

        return $res;
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
        $startTime = trans_Setup::get('START_WORK_TIME');
        $endTime = trans_Setup::get('END_WORK_TIME');
        $res = array('readyOn'      => array('caption' => 'Готовност', 'type' => 'date', 'readOnlyIfActive' => true, "input" => "input=hidden", 'autoCalcFieldName' => 'readyOnCalc', 'displayExternal' => true),
                     'deliveryTime' => array('caption' => 'Товарене', 'type' => "datetime(defaultTime={$startTime})", 'readOnlyIfActive' => true, "input" => "input", 'autoCalcFieldName' => 'deliveryTimeCalc', 'displayExternal' => true),
                     'shipmentOn'   => array('caption' => 'Експедиране на', 'type' => "datetime(defaultTime={$startTime})", 'readOnlyIfActive' => false, "input" => "input=hidden", 'autoCalcFieldName' => 'shipmentOnCalc', 'displayExternal' => true),
                     'deliveryOn'   => array('caption' => 'Доставка', 'type' => "datetime(defaultTime={$endTime})", 'readOnlyIfActive' => false, "input" => "input", 'autoCalcFieldName' => 'deliveryOnCalc', 'displayExternal' => true));

        if(isset($rec)){
            $res['deliveryTime']['placeholder'] = store_Stores::calcLoadingDate($rec->fromStore, $rec->deliveryOn);
            $res['readyOn']['placeholder'] = ($cache && !empty($rec->readyOnCalc)) ? $rec->readyOnCalc : $this->getEarliestDateAllProductsAreAvailableInStore($rec);

            $loadingOn = !empty($rec->deliveryTime) ? $rec->deliveryTime : $rec->deliveryTimeCalc;
            $res['shipmentOn']['placeholder'] = ($cache && !empty($rec->shipmentOnCalc)) ? $rec->shipmentOnCalc : trans_Helper::calcShippedOnDate($rec->valior, $rec->lineId, $rec->activatedOn, $loadingOn);
        }

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

        $preparationTime = store_Stores::getShipmentPreparationTime($rec->fromStore);

        return dt::addSecs(-1 * $preparationTime, $rec->deliveryOn);
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        if(Request::get('originId', 'int')) return true;

        $folderId = doc_Threads::fetchField($threadId, 'folderId');
        $folderClass = doc_Folders::fetchCoverClassName($folderId);

        return cls::haveInterface('store_iface_TransferFolderCoverIntf', $folderClass);
    }


    /**
     * Може ли документа да се добавя като свързан документ към оридижина си
     */
    public static function canAddDocumentToOriginAsLink_($rec)
    {
        return true;
    }
}
