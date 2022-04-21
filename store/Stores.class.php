<?php


/**
 * Мениджър на складове
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Stores extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'store_AccRegIntf, acc_RegisterIntf, store_iface_TransferFolderCoverIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Складове';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Склад';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, acc_plg_Registry, bgerp_plg_FLB, store_Wrapper, plg_Current, plg_Rejected, doc_FolderPlg, plg_State, plg_Modified, doc_plg_Close, deals_plg_AdditionalConditions';


    /**
     * Полета за допълнителни условие към документи
     * @see deals_plg_AdditionalConditions
     */
    public $additionalConditionsToDocuments = 'sales_Sales,purchase_Purchases,store_ShipmentOrders';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,storeWorker';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canSelect = 'ceo,store,storeWorker';
    
    
    /**
     * Кой може да пише
     */
    public $canReject = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canRestore = 'ceo, admin';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'AccReports=acc_ReportDetails,store_Products';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'folder-cover';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = true;
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '302, 304, 305, 306, 309';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'store_AccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,store,acc';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,storeMaster,accMaster,accLimits';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,storeWorker';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canClose = 'ceo, admin';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, store, production';
    
    
    /**
     * Поле за избор на потребителите, които могат да активират обекта
     *
     * @see bgerp_plg_FLB
     */
    public $canActivateUserFld = 'chiefs';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Наименование,chiefs,activateRoles,selectUsers,selectRoles,workersIds=Товарачи';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * В коя номенкалтура, автоматично да влизат записите
     */
    public $autoList = 'stores';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/home-icon.png';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutStore.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory,remember=info');
        $this->FLD('comment', 'varchar(256)', 'caption=Коментар');
        $this->FLD('displayStockMeasure', 'enum(productMeasureId=От артикула,basePack=Избраната за "основна")', 'caption=Мярка,notNull,value=productMeasureId', "unit= (|за показване на наличностите|*)");
        $this->FLD('preparationBeforeShipment', 'time(suggestions=1 ден|2 дена|3 дена|1 седмица)', 'caption=Подготовка преди Експедиция->Време');

        $this->FLD('chiefs', 'userList(roles=store|ceo|production)', 'caption=Контиране на документи->Потребители,mandatory');
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Допълнително->Локация');
        $this->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Допълнително->Продуктови групи');
        $this->FLD('workersIds', 'userList(roles=storeWorker)', 'caption=Допълнително->Товарачи');
        
        $this->FLD('lastUsedOn', 'datetime', 'caption=Последено използване,input=none');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено,closed=Затворено)', 'caption=Състояние,notNull,default=active,input=none');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');

        $this->FLD('samePosPallets', 'enum(,no=Не,yes=Да)', 'caption=Различни палети на една позиция->Разрешаване,maxRadio=2,placeholder=Автоматично');
        $this->FLD('closeCombinedMovementsAtOnce', 'enum(,yes=Еднократно за цялото движение,no=Зона по зона)', 'caption=Приключване на комбинирани движения в терминала->Приключване,maxRadio=2,placeholder=Автоматично');
        $this->FLD('prioritizeRackGroups', 'enum(,yes=Да,no=Не)', 'caption=Използване на приоритетни стелажи->Разрешаване,maxRadio=2,placeholder=Автоматично');

        $this->setDbUnique('name');
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
        
        if ($rec->state != 'rejected') {
            if (store_InventoryNotes::haveRightFor('add', (object) array('folderId' => $rec->folderId))) {
                $data->toolbar->addBtn('Инвентаризация', array('store_InventoryNotes', 'add', 'folderId' => $rec->folderId, 'ret_url' => true), 'ef_icon=img/16/invertory.png,title = Създаване на протокол за инвентаризация');
            }
        }
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->id . ' st',
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * След показване на едит формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $company = crm_Companies::fetchOwnCompany();
        $locations = crm_Locations::getContragentOptions(crm_Companies::getClassId(), $company->companyId);
        $data->form->setOptions('locationId', $locations);
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            $data->form->setField('workersIds', array('maxColumns' => 2));
        }

        if(!core_Packs::isInstalled('rack')){
            $data->form->setField('samePosPallets', 'input=none');
            $data->form->setField('closeCombinedMovementsAtOnce', 'input=none');
            $data->form->setField('prioritizeRackGroups', 'input=none');
        }

        $preparationShipmentPlaceholder = $mvc->getFieldType('preparationBeforeShipment')->toVerbal(store_Setup::get('PREPARATION_BEFORE_SHIPMENT'));
        $data->form->setField('preparationBeforeShipment', "placeholder={$preparationShipmentPlaceholder}");
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'select' && $rec) {
            
            // Ако не може да избира склада, проверяваме дали е складов работник
            $cu = core_Users::getCurrent();
            if (keylist::isIn($cu, $rec->workersIds)) {
                $res = $mvc->canSelect;
            }
        }
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            if (isset($fields['-list'])) {
                $rec->name = $mvc->singleTitle . " \"{$rec->name}\"";
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-single']) {
            if ($rec->locationId) {
                $row->locationId = crm_Locations::getHyperLink($rec->locationId, true);
            }

            if(!isset($rec->preparationBeforeShipment)){
                if($defaultBeforeShipmentTime = store_Setup::get('PREPARATION_BEFORE_SHIPMENT')){
                    $row->preparationBeforeShipment = $mvc->getFieldType('preparationBeforeShipment')->toVerbal($defaultBeforeShipmentTime);
                    $row->preparationBeforeShipment = ht::createHint($row->preparationBeforeShipment, 'По подразбиране от пакета', 'notice', false);
                } else {
                    $row->preparationBeforeShipment = tr("Няма");
                }
            }

            if(core_Packs::isInstalled('rack')){
                if(empty($rec->samePosPallets)){
                    $row->samePosPallets = $mvc->getFieldType('samePosPallets')->toVerbal(rack_Setup::get('DIFF_PALLETS_IN_SAME_POS'));
                    $row->samePosPallets = ht::createHint($row->samePosPallets, 'Автоматично за системата', 'notice', false);
                }

                if(empty($rec->closeCombinedMovementsAtOnce)){
                    $row->closeCombinedMovementsAtOnce = $mvc->getFieldType('closeCombinedMovementsAtOnce')->toVerbal(rack_Setup::get('CLOSE_COMBINED_MOVEMENTS_AT_ONCE'));
                    $row->closeCombinedMovementsAtOnce = ht::createHint($row->closeCombinedMovementsAtOnce, 'Автоматично за системата', 'notice', false);
                }

                if(empty($rec->prioritizeRackGroups)){
                    $row->prioritizeRackGroups = $mvc->getFieldType('prioritizeRackGroups')->toVerbal(rack_Setup::get('ENABLE_PRIORITY_RACKS'));
                    $row->prioritizeRackGroups = ht::createHint($row->prioritizeRackGroups, 'Автоматично за системата', 'notice', false);
                }
            }

        } else if (isset($fields['-list']) && doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $row->name = "<b style='position:relative; top: 5px;'>" . $row->name . "</b>";
            $row->name .= "    <span class='fright'>" . $row->currentPlg . "</span>";
            unset($row->currentPlg);
        }
        
       
        if(isset($rec->productGroups)){
            $groupLinks = cat_Groups::getLinks($rec->productGroups);
            $row->productGroups = implode(' ', $groupLinks);
        }
    }
    
    
    /**
     * Кои документи да се показват като бързи бутони в папката на корицата
     *
     * @param int $id - ид на корицата
     *
     * @return array $res - възможните класове
     */
    public function getDocButtonsInFolder_($id)
    {
        $res = array();
        $res[] = (object)array('class' => 'planning_ConsumptionNotes', 'caption' => 'Влагане');
        $res[] = (object)array('class' => 'store_Transfers', 'caption' => 'Трансфер');
        $res[] = (object)array('class' => 'store_InventoryNotes', 'caption' => 'Инвентаризация');
        
        return $res;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            unset($data->listFields['currentPlg']);
        }
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $data->listFields['name'] = '@' . $data->listFields['name'];
            $mvc->tableRowTpl = "<tbody class='rowBlock'>[#ADD_ROWS#][#ROW#]</tbody>";
        }
    }


    /**
     * Поставя изискване да се избират за предложения само активните записи
     */
    protected static function on_BeforePrepareSuggestions($mvc, &$suggestions, core_Type $type)
    {
        $type->params['where'] .= ($type->params['where'] ? ' AND ' : '') . " (#state != 'closed' AND #state != 'rejected')";
    }


    /**
     * Колко време е нужна за подготовка на склада преди експедиция
     *
     * @param int|null $storeId - ид на склад, или null ако няма
     * @return int     $secs    - времето за подготовка в секунди
     */
    public static function getShipmentPreparationTime($storeId = null)
    {
        $secs = store_Setup::get('PREPARATION_BEFORE_SHIPMENT');
        if(isset($storeId)){
            $storeBeforeShipmentTimeSecs = store_Stores::fetchField($storeId, 'preparationBeforeShipment');
            $secs = ($storeBeforeShipmentTimeSecs) ? $storeBeforeShipmentTimeSecs : $secs;
        }

        return (int)$secs;
    }


    /**
     * Какво е времето за подговотка, при подадената дата на доставка
     *
     * @param int $storeId
     * @param datetime|null $deliveryDate
     * @return null|datetime
     */
    public static function calcLoadingDate($storeId, $deliveryDate)
    {
        // Ако няма дата нищо не се прави
        if(!isset($deliveryDate)) return null;

        // Приспада се времето за подготовка на склада
        $preparationTime = store_Stores::getShipmentPreparationTime($storeId);
        $res = dt::addSecs(-1 * $preparationTime, $deliveryDate);

        // Ако датата е в миналото, подменя се с края на работния ден на текущата дата
        if($res < dt::now()){
            $res = dt::today() . " " . trans_Setup::get('END_WORK_TIME') . ":00";
        }

        return $res;
    }
}
