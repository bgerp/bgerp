<?php


/**
 * Клас 'store_Products' за наличните в склада артикули
 * Данните постоянно се опресняват от баланса
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Products extends core_Detail
{
    /**
     * Ключ с който да се заключи ъпдейта на таблицата
     */
    const SYNC_LOCK_KEY = 'syncStoreProducts';
    
    
    /**
     * Заглавие
     */
    public $title = 'Наличности';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales,storeWorker';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantity,reservedQuantity,expectedQuantity,freeQuantity,expectedQuantityTotal,storeId';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'storeId';
    
    
    /**
     * Задължително филтър по склад
     */
    protected $mandatoryStoreFilter = false;
    
    
    /**
     * Флаг за обновяване на наличностите на шътдаун
     */
    public $updateOnShutdown = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име,tdClass=leftAlign');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,tdClass=storeCol leftAlign');
        $this->FLD('quantity', 'double(maxDecimals=3)', 'caption=Налично');
        $this->FLD('reservedQuantity', 'double(maxDecimals=3)', 'caption=Запазено');
        $this->FLD('expectedQuantity', 'double(maxDecimals=3)', 'caption=Очаквано (днес)');
        $this->FLD('expectedQuantityTotal', 'double(maxDecimals=3)', 'caption=Очаквано,tdClass=notBolded');
        $this->FNC('freeQuantity', 'double(maxDecimals=3)', 'caption=Разполаг.');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
        $this->setDbIndex('productId');
        $this->setDbIndex('storeId');
    }
    
    
    /**
     * Преди подготовката на записите
     */
    protected static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        $mvc->listItemsPerPage = (isset($data->masterMvc)) ? 100 : 20;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        // Ако няма никакви записи - нищо не правим
        if (!countR($data->recs)) {
            
            return;
        }
        $isDetail = isset($data->masterMvc);
        
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            $row->productId = cat_Products::getVerbal($rec->productId, 'name');
            $icon = cls::get('cat_Products')->getIcon($rec->productId);
            $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId), false, "ef_icon={$icon}");
            $pRec = cat_Products::fetch($rec->productId, 'code,isPublic,createdOn');
            $row->code = cat_Products::getVerbal($pRec, 'code');
            
            if ($isDetail) {
                   
                // Показване на запазеното количество
                $basePack = key(cat_Products::getPacks($rec->productId));
                if ($pRec = cat_products_Packagings::getPack($rec->productId, $basePack)) {
                    $rec->quantity /= $pRec->quantity;
                    $row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantity);
                    if (isset($rec->reservedQuantity)) {
                        $rec->reservedQuantity /= $pRec->quantity;
                    }
                }
                $rec->measureId = $basePack;
                
                // Линк към хронологията
                if (acc_BalanceDetails::haveRightFor('history')) {
                    $to = dt::today();
                    $from = dt::mysql2verbal($to, 'Y-m-1', null, false);
                    $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $from, 'toDate' => $to, 'accNum' => 321);
                    $histUrl['ent1Id'] = acc_Items::fetchItem('store_Stores', $rec->storeId)->id;
                    $histUrl['ent2Id'] = acc_Items::fetchItem('cat_Products', $rec->productId)->id;
                    $histUrl['ent3Id'] = null;
                    $row->history = ht::createLink('', $histUrl, null, 'title=Хронологична справка,ef_icon=img/16/clock_history.png');
                }
            } else {
                $rec->measureId = cat_Products::fetchField($rec->productId, 'measureId');
            }
            
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            $rec->freeQuantity = $rec->quantity - $rec->reservedQuantity + $rec->expectedQuantity;
            $row->freeQuantity = $mvc->getFieldType('freeQuantity')->toVerbal($rec->freeQuantity);
            $row->measureId = cat_UoM::getTitleById($rec->measureId);
        }
    }
    
    
    /**
     * След подготовка на филтъра
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        if($data->masterMvc instanceof cat_Products){
            $data->query->EXT('storeName', 'store_Stores', 'externalName=name,externalKey=storeId');
            $data->query->orderBy('storeName', 'ASC');
            
            return;
        }
        
        // Подготвяме формата
        cat_Products::expandFilter($data->listFilter);
        $orderOptions = arr::make('all=Всички,active=Активни,standard=Стандартни,private=Нестандартни,last=Последно добавени,closed=Изчерпани,reserved=Запазени,free=Разполагаеми');
        $data->listFilter->setOptions('order', $orderOptions);
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
        
        $stores = cls::get('store_Stores')->makeArray4Select('name', "#state != 'rejected'");
        $data->listFilter->setOptions('storeId', array('' => '') + $stores);
        $data->listFilter->setField('storeId', 'autoFilter');
        
        if ($mvc->mandatoryStoreFilter === true) {
            $storeId = store_Stores::getCurrent();
            $data->listFilter->setDefault('storeId', $storeId);
            $data->listFilter->setField('storeId', 'input=hidden');
        } else {
            if (countR($stores) == 1) {
                $data->listFilter->setDefault('storeId', key($stores));
            }
            
            if ($storeId = store_Stores::getCurrent('id', false)) {
                $data->listFilter->setDefault('storeId', $storeId);
            }
        }
        
        // Подготвяме в заявката да може да се търси по полета от друга таблица
        $data->query->EXT('keywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
        $data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $data->query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $data->query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        $data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');
        
        if (isset($data->masterMvc)) {
            $data->listFilter->setDefault('order', 'all');
            $data->listFilter->showFields = 'search,groupId';
        } else {
            $data->listFilter->setDefault('order', 'active');
            $data->listFilter->showFields = 'storeId,search,order,groupId';
        }
        
        $data->listFilter->input('storeId,order,groupId,search', 'silent');
        
        // Ако има филтър
        if ($rec = $data->listFilter->rec) {
            
            // И е избран склад, търсим склад
            if (!isset($data->masterMvc)) {
                if (isset($rec->storeId)) {
                    $selectedStoreName = store_Stores::getHyperlink($rec->storeId, true);
                    $data->title = "|Наличности в склад|* <b style='color:green'>{$selectedStoreName}</b>";
                    $data->query->where("#storeId = {$rec->storeId}");
                } elseif (countR($stores)) {
                    // Под всички складове се разбира само наличните за избор от потребителя
                    $data->query->in('storeId', array_keys($stores));
                } else {
                    // Ако няма налични складове за избор не вижда нищо
                    $data->query->where('1 = 2');
                }
            }
            
            // Ако се търси по ключови думи, търсим по тези от външното поле
            if (isset($rec->search)) {
                plg_Search::applySearch($rec->search, $data->query, 'keywords');
                
                // Ако ключовата дума е число, търсим и по ид
                if (type_Int::isInt($rec->search)) {
                    $data->query->orWhere("#productId = {$rec->search}");
                }
            }
            
            // Подредба
            if (isset($rec->order)) {
                switch ($data->listFilter->rec->order) {
                    case 'all':
                        break;
                    case 'private':
                        $data->query->where("#isPublic = 'no'");
                        break;
                    case 'last':
                          $data->query->orderBy('#createdOn=DESC');
                        break;
                    case 'closed':
                        $data->query->where("#state = 'closed'");
                        break;
                    case 'active':
                        $data->query->where("#state != 'closed'");
                        break;
                    case 'reserved':
                        $data->query->where("#reservedQuantity IS NOT NULL");
                        break;
                    case 'free':
                        $data->query->XPR('free', 'double', 'ROUND(COALESCE(#quantity, 0) - COALESCE(#reservedQuantity, 0), 2)');
                        $data->query->orderBy('free', 'ASC');
                        break;
                    default:
                        $data->query->where("#isPublic = 'yes'");
                        break;
                }
            }
            
            $data->query->orderBy('#state,#code');
            
            // Филтър по групи на артикула
            if (!empty($rec->groupId)) {
                $data->query->where("LOCATE('|{$rec->groupId}|', #groups)");
            }
        }
    }
    
    
    /**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     *
     * @param array $all - масив идващ от баланса във вида:
     *                   array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($all)
    {
        $query = self::getQuery();
        $query->show('productId,storeId,quantity,state');
        $oldRecs = $query->fetchAll();
        $self = cls::get(get_called_class());
        
        $arrRes = arr::syncArrays($all, $oldRecs, 'productId,storeId', 'quantity');
        
        if (!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
            $self->logWarning('Синхронизирането на складовите наличности е заключено от друг процес');
            
            return;
        }
        
        $self->saveArray($arrRes['insert']);
        $self->saveArray($arrRes['update'], 'id,quantity');
        
        // Ъпдейт на к-та на продуктите, имащи запис но липсващи в счетоводството
        self::updateMissingProducts($arrRes['delete']);
        
        // Поправка ако случайно е останал някой артикул с к-во в затворено състояние
        $fixQuery = self::getQuery();
        $fixQuery->where("#quantity != 0 AND #state = 'closed'");
        $fixQuery->show('id,state');
        while ($fRec = $fixQuery->fetch()) {
            $fRec->state = 'active';
            self::save($fRec, 'state');
        }
        
        core_Locks::release(self::SYNC_LOCK_KEY);
    }
    
    
    /**
     * Ф-я която ъпдейтва всички записи, които присъстват в модела,
     * но липсват в баланса
     *
     * @param array $array - масив с данни за наличните артикул
     */
    private static function updateMissingProducts($array)
    {
        // Всички записи, които са останали но не идват от баланса
        $query = static::getQuery();
        $query->show('productId,storeId,quantity,state,reservedQuantity');
        
        // Зануляваме к-та само на тези продукти, които още не са занулени
        $query->where("#state = 'active'");
        if (countR($array)) {
            
            // Маркираме като затворени, всички които не са дошли от баланса или имат количества 0
            $query->in('id', $array);
            $query->orWhere('#quantity = 0');
        }
        
        if (!countR($array)) {
            
            return;
        }
        
        // За всеки запис
        while ($rec = $query->fetch()) {
            
            // К-то им се занулява и състоянието се затваря
            if (empty($rec->reservedQuantity)) {
                $rec->state = 'closed';
            }
            
            $rec->quantity = 0;
            
            // Обновяване на записа
            static::save($rec, 'state,quantity');
        }
    }
    
    
    /**
     * Колко е количеството на артикула в складовете
     *
     * @param int      $productId    - ид на артикул
     * @param int|NULL $storeId      - конкретен склад, NULL ако е във всички
     * @param bool     $freeQuantity - FALSE за общото количество, TRUE само за разполагаемото (общо - запазено)
     *
     * @return float $sum          - сумата на количеството, общо или разполагаемо
     */
    public static function getQuantity($productId, $storeId = null, $freeQuantity = false)
    {
        $query = self::getQuery();
        $query->where("#productId = {$productId}");
        $query->show('sum');
        
        if (isset($storeId)) {
            $query->where("#storeId = {$storeId}");
        }
        
        if ($freeQuantity === true) {
            $query->XPR('sum', 'double', 'SUM(#quantity - COALESCE(#reservedQuantity, 0) + COALESCE(#expectedQuantity, 0))');
        } else {
            $query->XPR('sum', 'double', 'SUM(#quantity)');
        }
        
        $calcedSum = $query->fetch()->sum;
        $sum = (!empty($calcedSum)) ? $calcedSum : 0;
        
        return $sum;
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            if (isset($data->masterMvc)) {
                
                return;
            }
            $data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата, ef_icon=img/16/sport_shuttlecock.png, title=Изтриване на таблицата с продукти');
        }
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $data->listFields['expectedQuantity'] = "|Очаквано|*<span class='small notBolded'> (|*днес|*)</span>";
        $data->listFields['expectedQuantityTotal'] = "<span class='notBolded'>|Очаквано|*";
        if (isset($data->masterMvc)) {
            if($data->masterMvc instanceof cat_Products){
                arr::placeInAssocArray($data->listFields, array('storeId' => 'Склад|*'), null, 'code');
                unset($data->listFields['productId']);
                unset($data->listFields['code']);
            } else {
                unset($data->listFields['storeId']);
            }
            
            if (acc_BalanceDetails::haveRightFor('history')) {
                arr::placeInAssocArray($data->listFields, array('history' => ' '), 'code');
            }
        }
    }
    
    
    /**
     * Изчиства записите в склада
     */
    public function act_Truncate()
    {
        requireRole('debug');
        
        // Изчистваме записите от моделите
        store_Products::truncate();
        
        return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field');
        $data->listTableMvc->FLD('measureId', 'varchar', 'tdClass=centered');
        $data->listTableMvc->setField('expectedQuantityTotal', 'tdClass=expectedTotalCol');
        
        if (!countR($data->rows)) {
            
            return;
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            
            foreach (array('reservedQuantity', 'expectedQuantity', 'expectedQuantityTotal') as $type){
                if (!empty($rec->{$type})) {
                    
                    $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'id' => $rec->id, 'field' => $type), 'local');
                    $arrowImg = ht::createElement('img', array('src' => sbf('img/16/info-gray.png', '')));
                    $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => 'От кои документи е резервирано количеството'), $arrowImg, true);
                    $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='{$type}{$rec->id}'></span>{$arrow}</span>";
                    $row->{$type} = "<span class='fleft'>{$arrow} </span>". $row->{$type};
                }
            }
        }
    }
    
    
    /**
     * Преди подготовката на ключовете за избор
     */
    protected static function on_BeforePrepareKeyOptions($mvc, &$options, $typeKey, $where = '')
    {
        $storeId = store_Stores::getCurrent();
        $query = self::getQuery();
        if ($where) {
            $query->where($where);
        }
        while ($rec = $query->fetch("#storeId = {$storeId}  AND #state = 'active'")) {
            $options[$rec->id] = cat_Products::getTitleById($rec->productId, false);
        }
        
        if (!countR($options)) {
            $options[''] = '';
        }
    }
    
    
    /**
     * Ако е вдигнат флаг, обновява запазаните наличности на shutdown
     */
    public static function on_Shutdown($mvc)
    {
        if ($mvc->updateOnShutdown) {
            $mvc->cron_CalcReservedQuantity();
        }
    }
    
    
    /**
     * Обновяване на резервираните наличности по крон
     */
    public function cron_CalcReservedQuantity()
    {
        //@todo трябва цялото да се рефакторира
        core_Debug::$isLogging = false;
        core_App::setTimeLimit(200);
        $now = dt::now();
       
        $docArr = array('sales_Sales' => array('storeFld' => 'shipmentStoreId', 'Detail' => 'sales_SalesDetails'), 
                        'store_ShipmentOrders' => array('storeFld' => 'storeId', 'Detail' => 'store_ShipmentOrderDetails'),
                        'planning_ConsumptionNotes' => array('storeFld' => 'storeId', 'Detail' => 'planning_ConsumptionNoteDetails'),
                        'store_ConsignmentProtocols' => array('storeFld' => 'storeId', 'Detail' => 'store_ConsignmentProtocolDetailsSend'),
                        'planning_DirectProductionNote' => array('storeFld' => 'storeId', 'Detail' => 'planning_DirectProductNoteDetails'), 
        );
        
        $result = $queue = array();
        foreach ($docArr as $Doc => $arr) {
            $Doc = cls::get($Doc);
            $storeField = $arr['storeFld'];
            
            // Всички заявки
            $sQuery = $Doc->getQuery();
            $sQuery->where("#state = 'pending'");
            $sQuery->show("id,containerId,modifiedOn,{$storeField}");
            if($Doc == 'sales_Sales'){
                $sQuery->where("#{$storeField} IS NOT NULL");
            }
            
            while ($sRec = $sQuery->fetch()) {
                
                // Опит за взимане на данните от постоянния кеш
                $reserved = core_Permanent::get("reserved_{$sRec->containerId}", $sRec->modifiedOn);
                
                // Ако няма кеширани к-ва
                if (!isset($reserved)) {
                    $reserved = array();
                    $Detail = cls::get($arr['Detail']);
                    setIfNot($Detail->productFieldName, 'productId');
                    $shQuery = $Detail->getQuery();
                    $isCp = ($arr['Detail'] == 'store_ConsignmentProtocolDetailsSend');
                    
                    if ($isCp) {
                        $suMFld = 'packQuantity';
                        $shQuery->XPR('sum', 'double', "SUM(#{$suMFld} * #quantityInPack)");
                    } else {
                        $suMFld = 'quantity';
                        $shQuery->XPR('sum', 'double', "SUM(#{$suMFld})");
                    }
                    
                    $show = "{$Detail->productFieldName},{$suMFld},{$Detail->masterKey},sum,quantityInPack";
                    $shQuery->where("#{$Detail->masterKey} = {$sRec->id}");
                    $shQuery->groupBy($Detail->productFieldName);
                    
                    if($Detail instanceof planning_DirectProductNoteDetails){
                        $shQuery->where("#{$storeField} IS NOT NULL");
                        $show .= ",storeId";
                    }
                    $shQuery->show($show);
                    
                    while ($sd = $shQuery->fetch()) {
                        $storeId = ($Detail instanceof planning_DirectProductNoteDetails) ? $sd->{$storeField} : $sRec->{$storeField};
                        $key = "{$storeId}|{$sd->{$Detail->productFieldName}}";
                        $reserved[$key] = array('sId' => $storeId, 'pId' => $sd->{$Detail->productFieldName}, 'reserved' => $sd->sum, 'expected' => null, 'expectedTotal' => null);
                    }
                    
                    core_Permanent::set("reserved_{$sRec->containerId}", $reserved, 4320);
                }
                
                $queue[] = $reserved;
            }
        }
        
        
        $tQuery = store_Transfers::getQuery();
        $tQuery->where("#state = 'pending'");
        $tQuery->show('id,containerId,fromStore,toStore,modifiedOn,deliveryTime');
        while ($tRec = $tQuery->fetch()) {
            
            // Ако има срок на доставка и той е <= на сега, инвалидира се кеша, да се изчисли наново
            if(!empty($tRec->deliveryTime) && $tRec->deliveryTime <= $now){
                core_Permanent::remove("reserved_{$tRec->containerId}");
            }
            
            $reserved = core_Permanent::get("reserved_{$tRec->containerId}", $tRec->modifiedOn);
            
            // Ако няма кеширани к-ва
            if (!isset($reserved)) {
                $reserved = array();
                $tdQuery = store_TransfersDetails::getQuery();
                $tdQuery->XPR('sum', 'double', "SUM(#quantity)");
                $tdQuery->where("#transferId = {$tRec->id}");
                $tdQuery->show('newProductId,quantity,transferId,quantityInPack,sum');
                $tdQuery->groupBy('newProductId');
               
                while ($td = $tdQuery->fetch()) {
                    $key = "{$tRec->fromStore}|{$td->newProductId}";
                    $key2 = "{$tRec->toStore}|{$td->newProductId}";
                    
                    $reserved[$key] = array('sId' => $tRec->fromStore, 'pId' => $td->newProductId, 'reserved' => $td->sum, 'expected' => null, 'expectedTotal' => null);
                    $reserved[$key2] = array('sId' => $tRec->toStore, 'pId' => $td->newProductId, 'reserved' => null, 'expected' => null, 'expectedTotal' => $td->sum);
                    
                    $deliveryTime = $tRec->deliveryTime;
                    if(!empty($deliveryTime) && $deliveryTime <= $now){
                        $reserved[$key2]['expected'] = $td->sum;
                    }
                }
               
                core_Permanent::set("reserved_{$tRec->containerId}", $reserved, 4320);
            }
            
            if(is_array($reserved) && countR($reserved)){
                $queue[] = $reserved;
            }
        }
        
        $docArr = array('purchase_Purchases' => array('storeFld' => 'shipmentStoreId', 'Detail' => 'purchase_PurchasesDetails'),
                        'store_Receipts' => array('storeFld' => 'storeId', 'Detail' => 'store_ReceiptDetails'));
        
        foreach ($docArr as $Doc1 => $arr){
            $Doc = cls::get($Doc1);
            
            $srQuery = $Doc->getQuery();
            $srQuery->where("#state = 'pending' AND #{$arr['storeFld']} IS NOT NULL");
            
            if($Doc instanceof purchase_Purchases){
                $srQuery->show("id,containerId,modifiedOn,shipmentStoreId,valior");
            } else {
                $srQuery->show("id,containerId,modifiedOn,storeId,deliveryTime");
            }
            
            while ($sRec = $srQuery->fetch()) {
                $deliveryTime = isset($sRec->deliveryTime) ? $sRec->deliveryTime : (isset($sRec->valior) ? $sRec->valior : $now);
                if($deliveryTime <= $now){
                    core_Permanent::remove("reserved_{$sRec->containerId}");
                }
                
                $reserved = core_Permanent::get("reserved_{$sRec->containerId}", $sRec->modifiedOn);
                
                // Ако няма кеширани к-ва
                if (!isset($reserved)) {
                    $reserved = array();
                    $Detail = cls::get($arr['Detail']);
                    
                    $sdQuery = $Detail->getQuery();
                    $sdQuery->XPR('sum', 'double', "SUM(#quantity)");
                    $sdQuery->where("#{$Detail->masterKey} = {$sRec->id}");
                    $sdQuery->show("productId,quantity,{$Detail->masterKey},sum");
                    $sdQuery->groupBy('productId');
                    
                    while ($sd = $sdQuery->fetch()) {
                        $key = "{$sRec->{$arr['storeFld']}}|{$sd->productId}";
                        $reserved[$key] = array('sId' => $sRec->{$arr['storeFld']}, 'pId' => $sd->productId, 'reserved' => null, 'expected' => null, 'expectedTotal' => $sd->sum);
                        
                        $deliveryTime = isset($sRec->deliveryTime) ? $sRec->deliveryTime : (isset($sRec->valior) ? $sRec->valior : null);
                        
                        if(!empty($deliveryTime) && $deliveryTime <= $now){
                            $reserved[$key]['expected'] = $sd->sum;
                        }
                    }
                    
                    core_Permanent::set("reserved_{$sRec->containerId}", $reserved, 4320);
                }
                
                if(is_array($reserved) && countR($reserved)){
                    $queue[] = $reserved;
                }
            }
        }
        
        // Добавяне на произвеждания артикул от протокола за производство
        $pNoteQuery = planning_DirectProductionNote::getQuery();
        $pNoteQuery->where("#storeId IS NOT NULL AND #state = 'pending'");
        $tQuery->show('containerId,modifiedOn,deadline,storeId,quantity,productId');
        while ($nRec = $pNoteQuery->fetch()) {
            $reserved = core_Permanent::get("reserved_notes_{$nRec->containerId}", $nRec->modifiedOn);
            
            // Ако няма кеширани к-ва
            if (!isset($reserved)) {
                $reserved = array();
               
                $key = "{$nRec->storeId}|{$nRec->productId}";
                $reserved[$key] = array('sId' => $nRec->storeId, 'pId' => $nRec->productId, 'reserved' => null, 'expected' => null, 'expectedTotal' => $nRec->quantity);
                $deliveryTime = isset($nRec->deadline) ? $nRec->deadline : (isset($nRec->valior) ? $nRec->valior : null);
                if(!empty($deliveryTime) && $deliveryTime <= $now){
                    $reserved[$key]['expected'] = $nRec->quantity;
                }
                
                core_Permanent::set("reserved_notes_{$nRec->containerId}", $reserved, 4320);
            }
            
            if(countR($reserved)){
                $queue[] = $reserved;
            }
        }
        
        // Добавят се и запазените от бележки в POS-а
        if(core_Packs::isInstalled('pos')){
            $receiptQuery = pos_Receipts::getQuery();
            $receiptQuery->EXT('storeId', 'pos_Points', 'externalName=storeId,externalKey=pointId');
            $receiptQuery->where("#state = 'waiting'");
            $receiptQuery->show('storeId,modifiedOn');
            while($receiptRec = $receiptQuery->fetch()){
                $reserved = core_Permanent::get("reserved_receipts_{$receiptRec->id}", $receiptRec->modifiedOn);
                
                // Ако няма кеширани к-ва
                if (!isset($reserved)) {
                    $reserved = array();
                    $rQuery = pos_ReceiptDetails::getQuery();
                    $rQuery->where("#receiptId = {$receiptRec->id}");
                    $rQuery->where("#action LIKE '%sale%'");
                    
                    while ($rd = $rQuery->fetch()) {
                        $packRec = cat_products_Packagings::getPack($rd->productId, $rd->value);
                        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                        $quantity = $quantityInPack * $rd->quantity;
                        $key = "{$receiptRec->storeId}|{$rd->productId}";
                        $reserved[$key] = array('sId' => $receiptRec->storeId, 'pId' => $rd->productId, 'reserved' => $quantity, 'expected' => null, 'expectedTotal' => null);
                    }
                    
                    core_Permanent::set("reserved_receipts_{$receiptRec->id}", $reserved, 4320);
                }
                
                $queue[] = $reserved;
            }
        }
       
        // Сумиране на к-та
        foreach ($queue as $arr) {
            foreach ($arr as $key => $obj) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('storeId' => $obj['sId'], 'productId' => $obj['pId'], 'reservedQuantity' => $obj['reserved'], 'expectedQuantity' => $obj['expected'], 'expectedQuantityTotal' => $obj['expectedTotal'], 'state' => 'active');
                } else {
                    $result[$key]->reservedQuantity += $obj['reserved'];
                    $result[$key]->expectedQuantityTotal += $obj['expectedTotal'];
                    $result[$key]->expectedQuantity += $obj['expected'];
                }
            }
        }
        
        // Извличане на всички стари записи
        $storeQuery = static::getQuery();
        $old = $storeQuery->fetchAll();
        
        // Синхронизират се новите със старите записи
        $res = arr::syncArrays($result, $old, 'storeId,productId', 'reservedQuantity,expectedQuantity,expectedQuantityTotal');
        
        // Заклюване на процеса
        if (!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
            $this->logWarning('Синхронизирането на складовите наличности е заключено от друг процес');
            
            return;
        }
        
        // Добавяне и ъпдейт на резервираното количество на новите
        $this->saveArray($res['insert']);
        $this->saveArray($res['update'], 'id,reservedQuantity,expectedQuantity,expectedQuantityTotal');
        
        // Намиране на тези записи, от старите които са имали резервирано к-во, но вече нямат
        $unsetArr = array_filter($old, function (&$r) use ($result) {
            if (!isset($r->reservedQuantity) && !isset($r->expectedQuantity) && !isset($r->expectedQuantityTotal)) {
                
                return false;
            }
            if (array_key_exists("{$r->storeId}|{$r->productId}", $result)) {
                
                return false;
            }
            
            if(isset($r->reservedQuantity)){
                $r->_nullifyReserved = true;
            }
            
            if(isset($r->expectedQuantity)){
                $r->_nullifyExpected = true;
            }
            
            if(isset($r->expectedQuantityTotal)){
                $r->_nullifyExpectedTotal = true;
            }
            
            return true;
        });
        
        // Техните резервирани количества се изтриват
        if (countR($unsetArr)) {
            array_walk($unsetArr, function ($obj) {
                if($obj->_nullifyReserved === true){
                    $obj->reservedQuantity = null;
                }
                
                if($obj->_nullifyExpected === true){
                    $obj->expectedQuantity = null;
                }
                
                if($obj->_nullifyExpectedTotal === true){
                    $obj->expectedQuantityTotal = null;
                }
            });
            
            $this->saveArray($unsetArr, 'id,reservedQuantity,expectedQuantity,expectedQuantityTotal');
        }
        
        // Освобождаване на процеса
        core_Locks::release(self::SYNC_LOCK_KEY);
        core_Debug::$isLogging = true;
    }
    
    
    /**
     * Показва информация за резервираните количества
     */
    public function act_ShowReservedDocs()
    {
        //core_Request::setProtected('field');
        requireRole('powerUser');
        $id = Request::get('id', 'int');
        $field = Request::get('field', 'varchar');
        expect($rec = self::fetch($id));
        $now = dt::now();
        
        $arr = array();
        $arr['reservedQuantity'] = array('sales_SalesDetails' => 'shipmentStoreId', 'store_ShipmentOrderDetails' => 'storeId', 'store_TransfersDetails' => 'fromStore', 'planning_ConsumptionNoteDetails' => 'storeId', 'store_ConsignmentProtocolDetailsSend' => 'storeId', 'planning_DirectProductNoteDetails' => 'storeId');
        $arr['expectedQuantity'] = array('purchase_PurchasesDetails' => 'shipmentStoreId', 'store_TransfersDetails' => 'toStore', 'store_ReceiptDetails' => 'storeId');
        $arr['expectedQuantityTotal'] = array('purchase_PurchasesDetails' => 'shipmentStoreId', 'store_TransfersDetails' => 'toStore', 'store_ReceiptDetails' => 'storeId');
        
        // Намират се документите, запазили количества
        $docs = array();
        foreach ($arr[$field] as $Detail => $storeField) {
            
            $Detail = cls::get($Detail);
            expect($Detail->productFld, $Detail);
            
            $Master = $Detail->Master;
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('containerId', $Master->className, "externalName=containerId,externalKey={$Detail->masterKey}");
            if(!($Detail instanceof planning_DirectProductNoteDetails)){
                $dQuery->EXT('storeId', $Master->className, "externalName={$storeField},externalKey={$Detail->masterKey}");
            }
            
            $dQuery->EXT('state', $Master->className, "externalName=state,externalKey={$Detail->masterKey}");
            $dQuery->EXT('valior', $Master->className, "externalName=valior,externalKey={$Detail->masterKey}");
            if($Master->getField('deliveryTime', false)){
                $dQuery->EXT('deliveryTime', $Master->className, "externalName=deliveryTime,externalKey={$Detail->masterKey}");
            }
            
            $dQuery->where("#state = 'pending'");
            $dQuery->where("#{$Detail->productFld} = {$rec->productId}");
            $dQuery->where("#storeId = {$rec->storeId}");
            $dQuery->groupBy('containerId');
            
            while ($dRec = $dQuery->fetch()) {
                $deliveryTime = isset($dRec->deliveryTime) ? $dRec->deliveryTime : (isset($dRec->valior) ? $dRec->valior : null);
                
                if($field == 'reservedQuantity'){
                    $docs[$dRec->containerId] = doc_Containers::getDocument($dRec->containerId)->getLink(0);
                } elseif($field == 'expectedQuantityTotal'){
                    $docs[$dRec->containerId] = doc_Containers::getDocument($dRec->containerId)->getLink(0);
                } if(!empty($deliveryTime) && $deliveryTime <= $now){
                    $docs[$dRec->containerId] = doc_Containers::getDocument($dRec->containerId)->getLink(0);
                }
            }
        }
        
        // Бележките в които е участвало
        if(core_Packs::isInstalled('pos') && $field == 'reservedQuantity'){
            $receiptQuery = pos_ReceiptDetails::getQuery();
            $receiptQuery->EXT('pointId', 'pos_Receipts', "externalName=pointId,externalKey=receiptId");
            $receiptQuery->EXT('storeId', 'pos_Points', "externalName=storeId,externalKey=pointId");
            $receiptQuery->EXT('state', 'pos_Receipts', "externalName=state,externalKey=receiptId");
            $receiptQuery->where("#productId = {$rec->productId} AND #state = 'waiting' AND #action LIKE '%sale%'");
            $receiptQuery->where("#storeId = {$rec->storeId}");
            $receiptQuery->groupBy('receiptId');
            $receiptQuery->show('receiptId');
            while ($receiptRec = $receiptQuery->fetch()) {
                $docs["receipt{$receiptRec->receiptId}"] = pos_Receipts::getHyperlink($receiptRec->receiptId, true);
            }
        }
        
        // Добавяне и мастъра на протокола за производство
        if(in_array($field, array('expectedQuantity', 'expectedQuantityTotal'))){
            $pNoteQuery = planning_DirectProductionNote::getQuery();
            $pNoteQuery->where("#storeId = {$rec->storeId} AND #state = 'pending' AND #productId = {$rec->productId}");
            $pNoteQuery->show('containerId,deadline,valior');
            while($noteRec = $pNoteQuery->fetch()){
                $deliveryTime = isset($noteRec->deadline) ? $noteRec->deadline : (isset($noteRec->valior) ? $noteRec->valior : null);
                if($field == 'expectedQuantityTotal'){
                    $docs[$dRec->containerId] = doc_Containers::getDocument($noteRec->containerId)->getLink(0);
                } if(!empty($deliveryTime) && $deliveryTime <= $now){
                    $docs[$dRec->containerId] = doc_Containers::getDocument($noteRec->containerId)->getLink(0);
                }
            }
        }
        
        $links = '';
        foreach ($docs as $containerId => $link) {
            $cRec = doc_Containers::fetch($containerId, 'createdBy,folderId');
            $createdBy = crm_Profiles::createLink($cRec->createdBy);
            $folderId = doc_Folders::recToVerbal(doc_Folders::fetch($cRec->folderId))->title;
            $links .= "<div style='float:left'>{$link} | {$createdBy} | {$folderId}</div>";
        }
        $tpl = new core_ET($links);
       
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => "{$field}{$id}", 'html' => $tpl->getContent(), 'replace' => true);
            
            return array($resObj);
        }
        
        return $tpl;
    }
    
    
    /**
     * Изчисляване на готовноста на складовите документи на заявка 
     */
    public function cron_UpdateShipmentDocumentReadiness()
    {
        // За всички ЕН и МСТ
        foreach (array('store_ShipmentOrders' => 'store_ShipmentOrderDetails', 'store_Transfers' => 'store_TransfersDetails') as $Master => $Detail){
            $Master = cls::get($Master);
            $Detail = cls::get($Detail);
            $storeField = ($Master instanceof store_ShipmentOrders) ? 'storeId' : 'fromStore';
            
            // Тези които са на заявка
            $query = $Master->getQuery();
            $query->where("#state = 'pending'");
            $query->show("id,storeReadiness,{$storeField}");
            
            $toSave = array();
            while($rec = $query->fetch()){
                $products = $quantities = array();
                $isTransfer = ($Master instanceof store_Transfers);
                $totalValue = 0;
                
                // Сумира се какво е общото к-во и сумата му
                $dQuery = $Detail->getQuery();
                $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
                $dRecs = $dQuery->fetchAll();
                
                if(countR($dRecs)){
                    array_walk($dRecs, function($a) use (&$products, $Detail, &$totalValue, $isTransfer){
                        if(!array_key_exists($a->{$Detail->productFld}, $products)){
                            $products[$a->{$Detail->productFld}] = new stdClass();
                        }
                        
                        $products[$a->{$Detail->productFld}]->quantity += $a->quantity;
                        $value = ($isTransfer) ? ($a->quantity) : ($a->quantity * $a->price);
                        $products[$a->{$Detail->productFld}]->amount += $value;
                        $totalValue += $value;
                    });
                    
                    // Колко е налично в склад от артикулите на документа
                    $storeQuery = store_Products::getQuery();
                    $storeQuery->where("#storeId = {$rec->{$storeField}}");

                    $storeQuery->in('productId', array_keys($products));
                    $storeQuery->show('productId,quantity');
                    $sRecs = $storeQuery->fetchAll();
                    array_walk($sRecs, function($a) use (&$quantities){
                        $quantities[$a->productId] += $a->quantity;
                    });
                    
                    // Колко е готовноста
                    $missingAmount = 0;
                    foreach ($products as $productId => $object){
                        $singlePrice = (!empty(round($object->quantity, 4))) ? round($object->amount / $object->quantity, 6) : 0;
                        $inStore = $quantities[$productId];
                        $inStore = (empty($inStore) || $inStore < 0) ? 0 : $inStore;
                        
                        // Каква е сумата на липсващото к-во. (За МСТ си е само количеството)
                        $missingQuantity = $object->quantity - $inStore;
                        $missingQuantity = ($missingQuantity <= 0) ? 0 : $missingQuantity;
                        $missingAmount += $missingQuantity * $singlePrice;
                    }
                    
                    // Колко е готовността, тя е 1 - сумата на липсващото к-во/ общата сума на ЕН-то (За МСТ е от липсващото общо к-во)
                    $missingAmount = round($missingAmount, 6);
                    $totalValue = round($totalValue, 6);
                    $storeReadiness = !empty($totalValue) ? (1 - round($missingAmount / $totalValue, 2)) : 0;
                    $storeReadiness = ($storeReadiness < 0) ? 0 : $storeReadiness;
                    $storeReadiness = ($storeReadiness > 1) ? 1 : $storeReadiness;
                    $rec->storeReadiness = round($storeReadiness, 2);
                } else {
                    $rec->storeReadiness = null;
                }
                
                $toSave[] = $rec;
                
                if(countR($toSave)){
                    $Master->saveArray($toSave, 'id,storeReadiness');
                }
            }
        }
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        if($data->masterMvc instanceof cat_Products){
            $data->masterKey = 'productId';
            
            $data->render = true;
            $canStore = cat_Products::fetchField($data->masterId, 'canStore');
            $tabParam = $data->masterData->tabTopParam;
            $prepareTab = Request::get($tabParam);
            
            if($canStore != 'yes' || !store_Products::haveRightFor('list') || $prepareTab != 'store_Products'){
                $data->render = false;
            }
            
            if($canStore != 'yes' || !store_Products::haveRightFor('list')){
                
                return;
            }
            
            $data->TabCaption = 'Наличности';
            $data->Tab = 'top';
        }
        
        parent::prepareDetail_($data);
    }
    
    
    /**
    * Рендиране на детайла
    */
    public function renderDetail_($data)
    {
        // Не се рендира детайла, ако има само една версия или режима е само за показване
        if ($data->render === false) {
           
            return new core_ET('');
        }
        
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(parent::renderDetail_($data), 'content');
       
        return $tpl;
    }
}
