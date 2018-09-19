<?php


/**
 * Клас 'store_ConsignmentProtocols'
 *
 * Мениджър на протоколи за отговорно пазене
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ConsignmentProtocols extends core_Master
{
    /**
     * Заглавие
     *
     * @var string
     */
    public $title = 'Протоколи за отговорно пазене';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Cpt';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_ConsignmentProtocol';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, store_Wrapper, doc_plg_BusinessDoc,plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                        plg_Clone, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, trans_plg_LinesPlugin, doc_plg_TplManager, plg_Search, bgerp_plg_Blank, doc_plg_HidePrices';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
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
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, contragentId=Контрагент, lineId, folderId, createdOn, createdBy';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/consignment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ConsignmentProtocolDetailsSend,store_ConsignmentProtocolDetailsReceived';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за отговорно пазене';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.7|Логистика';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,folderId,note';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ConsignmentProtocolDetailsSend, store_ConsignmentProtocolDetailsReceived';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,snapshot,lineId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden,tdClass=leftCol');
        
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'mandatory,caption=Плащане->Валута');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
        
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title, allowEmpty)', 'caption=Транспорт');
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
        $this->FLD(
            'state',
                'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка)',
                'caption=Статус, input=none'
        );
        $this->FLD('snapshot', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('responsible', 'varchar', 'caption=Получил');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, true);
            $row->title = $mvc->getLink($rec->id, 0);
        }
        
        $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
        $row = (object) ((array) $row + (array) $headerInfo);
        
        if (isset($fields['-single'])) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId);
            $row->username = core_Users::getVerbal($rec->createdBy, 'names');
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if (empty($rec->snapshot)) {
            $rec->snapshot = $mvc->prepareSnapshot($rec, dt::now());
            $mvc->save($rec, 'snapshot');
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако няма 'снимка' на моментното състояние, генерираме го в момента
        if (empty($data->rec->snapshot)) {
            $data->rec->snapshot = $mvc->prepareSnapshot($data->rec, dt::now());
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Ако потребителя няма достъп към визитката на лицето, или не може да види сч. справки то визитката, той не може да види справката
        $Contragent = cls::get($data->rec->contragentClassId);
        if (!$Contragent->haveRightFor('single', $data->rec->contragentId)) {
            
            return;
        }
        if (!haveRole($Contragent->canReports)) {
            
            return;
        }
        
        $snapshot = $data->rec->snapshot;
        
        $mvcTable = new core_Mvc;
        $mvcTable->FLD('blQuantity', 'int', 'tdClass=accCell');
        
        $table = cls::get('core_TableView', array('mvc' => $mvcTable));
        $details = $table->get($snapshot->rows, 'count=№,productId=Артикул,blQuantity=Количество');
        
        
        $tpl->replace($details, 'SNAPSHOT');
        $tpl->replace($snapshot->date, 'SNAPSHOT_DATE');
    }
    
    
    /**
     * Подготвя снапшот на моментното представяне на базата
     */
    private function prepareSnapshot($rec, $date)
    {
        $rows = array();
        
        // Кое е перото на контрагента ?
        $contragentItem = acc_Items::fetchItem($rec->contragentClassId, $rec->contragentId);
        
        // Ако контрагента не е перо, не показваме нищо
        if ($contragentItem) {
            
            // За да покажем моментното състояние на сметката на контрагента, взимаме баланса до края на текущия ден
            $to = dt::addDays(1, $date);
            $Balance = new acc_ActiveShortBalance(array('from' => $to,
                'to' => $to,
                'accs' => '323',
                'item1' => $contragentItem->id,
                'strict' => true,
                'keepUnique' => true,
                'cacheBalance' => false));
            
            // Изчлисляваме в момента, какъв би бил крания баланс по сметката в края на деня
            $Balance = $Balance->getBalanceBefore('323');
            
            $Double = cls::get('type_Double');
            $Double->params['smartRound'] = true;
            $Int = cls::get('type_Int');
            
            $accId = acc_Accounts::getRecBySystemId('323')->id;
            $count = 1;
            
            // Подготвяме записите за показване
            foreach ($Balance as $b) {
                if ($b['accountId'] != $accId) {
                    continue;
                }
                if ($b['blQuantity'] == 0) {
                    continue;
                }
                
                $row = new stdClass;
                $row->count = $Int->toVerbal($count);
                $row->productId = acc_Items::getVerbal($b['ent2Id'], 'titleLink');
                $row->blQuantity = $Double->toVerbal($b['blQuantity']);
                $row->blQuantity = ht::styleIfNegative($row->blQuantity, $b['baseQuantity']);
                
                $count++;
                $rows[] = $row;
            }
        }
        
        // Връщаме подготвените записи, и датата към която са подготвени
        return (object) array('rows' => $rows, 'date' => cls::get('type_DateTime')->toVerbal($date));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        
        if (isset($rec->id)) {
            if (store_ConsignmentProtocolDetailsSend::fetchField("#protocolId = {$rec->id}")) {
                $form->setReadOnly('currencyId');
            }
        }
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('crm_ContragentAccRegIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        return $Cover->haveInterface('crm_ContragentAccRegIntf');
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Протокол за отговорно пазене', 'content' => 'store/tpl/SingleLayoutConsignmentProtocol.shtml',
            'narrowContent' => 'store/tpl/SingleLayoutConsignmentProtocolNarrow.shtml', 'lang' => 'bg');
        
        $res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * Изчисляване на общото тегло и обем на документа
     *
     * @param stdClass $res
     *                        - weight - теглото на реда
     *                        - volume - теглото на реда
     * @param int      $id
     * @param bool     $force
     */
    public function getTotalTransportInfo($id, $force = false)
    {
        $res1 = cls::get('store_ConsignmentProtocolDetailsReceived')->getTransportInfo($id, $force);
        $res2 = cls::get('store_ConsignmentProtocolDetailsSend')->getTransportInfo($id, $force);
        
        $weight = (!is_null($res1->weight) && !is_null($res2->weight)) ? $res1->weight + $res2->weight : null;
        $volume = (!is_null($res1->volume) && !is_null($res2->volume)) ? $res1->volume + $res2->volume : null;
        
        $units = trans_Helper::getCombinedTransUnits($res1->transUnits, $res2->transUnits);
        
        return (object) array('weight' => $weight, 'volume' => $volume, 'transUnits' => $units);
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
     *
     * @return array
     *               ['baseAmount'] double|NULL - сумата за инкасиране във базова валута
     *               ['amount']     double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId'] string|NULL - валутата на документа
     *               ['notes']      string|NULL - забележки за транспортната линия
     *               ['stores']     array       - склад(ове) в документа
     *               ['weight']     double|NULL - общо тегло на стоките в документа
     *               ['volume']     double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *               [transUnitId] => quantity
     */
    public function getTransportLineInfo_($rec)
    {
        $rec = static::fetchRec($rec);
        $row = $this->recToVerbal($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'currencyId' => null, 'notes' => $rec->lineNotes);
        $res['stores'] = array($rec->storeId);
        $res['address'] = str_replace('<br>', '', $row->contragentAddress);
        
        return $res;
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
        $dQuery = store_ConsignmentProtocolDetailsSend::getQuery();
        $dQuery->where("#protocolId = {$id}");
        $dQuery->show('productId, quantity');
        
        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->storeId, $rec->state);
        
        return $warning;
    }
}
