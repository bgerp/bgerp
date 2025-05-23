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
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Products extends core_Detail
{
    
    
    /**
     * Каква да е максималната дължина на стринга за пълнотекстово търсене
     *
     * @see plg_Search
     */
    public $maxSearchKeywordLen = 13;
    
    
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
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State, plg_SelectPeriod';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales,storeWorker,storeAll';
    
    
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
    public $listFields = 'history,code=Код,productId=Артикул,measureId=Мярка,storeId,quantity,reservedQuantity,expectedQuantity,freeQuantity,reservedQuantityMin,expectedQuantityMin,freeQuantityMin,lastUpdated=<small>Промяна на||Changed on</small>';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'storeId';
    
    
    /**
     * Задължително филтър по склад
     */
    protected $mandatoryStoreFilter = false;


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'history';


    /**
     * По колко максимум документа да се показват в хинта за запазващите документи
     */
    const SHOW_DOCUMENTS_IN_PLANNED_STOCK_HINT = 15;


    /**
     * Кои полета да може да се скриват в хоризонталния лист филтър
     */
    public $toggableFieldsInVerticalListFilter = 'productId,horizon,inventory';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Артикул,tdClass=nameCell,silent');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,tdClass=storeCol leftAlign');
        $this->FLD('quantity', 'double(maxDecimals=3)', 'caption=Налично,tdClass=stockCol');
        $this->FLD('reservedQuantity', 'double(maxDecimals=3)', 'caption=Днес->Запазено,tdClass=horizonCol red');
        $this->FLD('expectedQuantity', 'double(maxDecimals=3)', 'caption=Днес->Очаквано,tdClass=horizonCol green');
        $this->FNC('freeQuantity', 'double(maxDecimals=3)', 'caption=Днес->Разполагаемо,tdClass=horizonCol');

        $this->FLD('reservedQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Запазено,tdClass=horizonCol red');
        $this->FLD('expectedQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Очаквано,tdClass=horizonCol green');
        $this->FNC('freeQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Разполагаемо,tdClass=horizonCol');
        $this->FLD('dateMin', 'date', 'caption=Минимално->Дата');
        $this->FLD('lastUpdated', 'datetime(format=smartTime)', 'caption=Промяна на');

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
        $mvc->listItemsPerPage = (isset($data->masterMvc)) ? 50 : 20;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        // Ако няма никакви записи - нищо не правим
        if (!countR($data->recs)) return;
        
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            $row->productId = cat_Products::getVerbal($rec->productId, 'name');

            $icon = cls::get('cat_Products')->getIcon($rec->productId);
            $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId), false, "ef_icon={$icon}");
            $pRec = cat_Products::fetch($rec->productId, 'code,isPublic,createdOn');
            $row->code = cat_Products::getVerbal($pRec, 'code');
            $rec->measureId = cat_Products::fetchField($rec->productId, 'measureId');

            if(isset($data->masterMvc) && $data->masterMvc instanceof cat_Products){
                $measureType = 'basePack';
            } else {
                $measureType = $data->listFilter->rec->setting;
            }

            // Ако ще се показва в основна опаковка, показва се в нея и к-та се конвертират
            if($measureType == 'basePack'){
                $basePack = key(cat_Products::getPacks($rec->productId));
                if ($pRec = cat_products_Packagings::getPack($rec->productId, $basePack)) {
                    $rec->quantity /= $pRec->quantity;
                    $row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantity);
                    foreach (array('reservedQuantity', 'expectedQuantity', 'reservedQuantityMin', 'expectedQuantityMin') as $fld){
                        if (isset($rec->{$fld})) {
                            $rec->{$fld} /= $pRec->quantity;
                        }
                    }
                }
                $rec->measureId = $basePack;
            }

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
            
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            $rec->freeQuantity = $rec->quantity - $rec->reservedQuantity + $rec->expectedQuantity;
            $row->freeQuantity = $mvc->getFieldType('freeQuantity')->toVerbal($rec->freeQuantity);

            $rec->freeQuantityMin = $rec->quantity - $rec->reservedQuantityMin + $rec->expectedQuantityMin;
            $row->freeQuantityMin = $mvc->getFieldType('freeQuantityMin')->toVerbal($rec->freeQuantityMin);
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
    {;
        if($data->masterMvc instanceof cat_Products){
            $data->query->EXT('storeName', 'store_Stores', 'externalName=name,externalKey=storeId');
            
            if($data->masterData->rec->generic == 'yes'){
                $equivalent = planning_GenericMapper::getEquivalentProducts($data->masterId);
                if(countR($equivalent) > 1){
                    $data->query->in('productId', array_keys($equivalent), false, true);
                    $data->query->orderBy('productId', 'ASC');
                }
            } else {
                $data->query->orderBy('storeName', 'ASC');
            }

            unset($data->listFilter->showFields);
            return;
        }

        // Подготвяме формата
        $data->listFilter->FNC('filters', "bgerp_type_CustomFilter(classes=store_Products)", 'caption=Филтри,input,silent,remember,autoFilter');
        $data->listFilter->FNC('groupId', 'key2(mvc=cat_Groups,select=name,allowEmpty)', 'placeholder=Група,caption=Група,input,silent,remember,autoFilter');
        $data->listFilter->FNC('folder', 'key2(mvc=doc_Folders,select=title,allowEmpty,coverInterface=cat_ProductFolderCoverIntf)', 'input,caption=Папка');
        $data->listFilter->FNC('horizon', 'time(suggestions=1 ден|1 седмица|2 седмици|1 месец|3 месеца)', 'placeholder=Хоризонт,caption=Хоризонт,input,class=w30');
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
        $data->listFilter->FNC('setting', 'enum(productMeasureId=Основна мярка,basePack=Основна опаковка)', 'caption=Настройка,input,silent,recently');
        $data->listFilter->FNC('inventory', 'enum(all=Всички,inventory=Инвентаризирани,notInventory=Неинвентаризирани)', 'caption=Инвентаризация,input,silent,autoFilter');
        $data->listFilter->FNC('from', 'date', 'caption=От');
        $data->listFilter->FNC('to', 'date', 'caption=До');

        $data->query->XPR('free', 'double', 'ROUND(COALESCE(#quantity, 0) - COALESCE(#reservedQuantity, 0), 2)');
        $data->listFilter->view = 'horizontal';

        $hKey = 'productHorizonFilter' . core_Users::getCurrent();
        if ($lastHorizon = core_Permanent::get($hKey)) {
            $data->listFilter->setDefault('horizon', $lastHorizon);
        }

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
        $data->query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $data->query->EXT('pState', 'cat_Products', 'externalName=state,externalKey=productId');
        $data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $data->query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $data->query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        $data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');

        if (isset($data->masterMvc)) {
            $showFieldsArr = arr::make('horizon,search,groupId', true);

            $data->listFilter->showFields = implode(',', $showFieldsArr);
            if($data->masterMvc instanceof store_Stores){
                $data->listFilter->setDefault('setting', $data->masterData->rec->displayStockMeasure);
            }
        } else {
            $data->listFilter->defOrder = false;
            $showFieldsArr = arr::make('search,productId,storeId,filters,groupId,horizon,setting,inventory,selectPeriod,from,to', 2);
            if($mvc instanceof rack_Products){
                unset($showFieldsArr['horizon']);
                unset($showFieldsArr['inventory']);
            }
            $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
            $data->listFilter->setDefault('filters', 'active');
            $data->listFilter->showFields = implode(',', $showFieldsArr);
            unset($data->listFilter->view);

            $sKey = "stockSettingFilter" . core_Users::getCurrent();
            if ($lastHorizon = core_Permanent::get($sKey)) {
                $data->listFilter->setDefault('setting', $lastHorizon);
            }
        }
        $data->listFilter->setDefault('inventory', 'all');
        $data->listFilter->setDefault('setting', 'productMeasureId');
        $data->listFilter->input('horizon,productId,storeId,filters,groupId,search,setting,inventory,selectPeriod,from,to', 'silent');

        // Ако има филтър
        if ($rec = $data->listFilter->rec) {

            if(isset($rec->inventory)){
                if($rec->inventory == 'all'){
                    unset($showFieldsArr['selectPeriod']);
                    unset($showFieldsArr['from']);
                    unset($showFieldsArr['to']);
                    $data->listFilter->showFields = implode(',', $showFieldsArr);
                } else {

                    // Ако ще се филтрират инвентаризираните намират се последните инвентаризации
                    $iQuery = store_InventoryNoteSummary::getQuery();
                    $iQuery->EXT('state', 'store_InventoryNotes', 'externalName=state,externalKey=noteId');
                    $iQuery->EXT('valior', 'store_InventoryNotes', 'externalName=valior,externalKey=noteId');
                    $iQuery->EXT('storeId', 'store_InventoryNotes', 'externalName=storeId,externalKey=noteId');
                    $iQuery->where("#quantity IS NOT NULL AND #state = 'active'");
                    $iQuery->show('productId,storeId,valior,noteId');
                    if(isset($rec->from)){
                        $iQuery->where("#valior >= '{$rec->from}'");
                    }
                    if(isset($rec->to)){
                        $iQuery->where("#valior <= '{$rec->to}'");
                    }
                    if(isset($rec->storeId)){
                        $iQuery->where("#storeId = {$rec->storeId}");
                    }
                    $iRecs = $inventoryProductIds = array();
                    while($iRec = $iQuery->fetch()) {
                        $inventoryProductIds[$iRec->productId] = $iRec->productId;
                        $iRecs["{$iRec->productId}|{$iRec->storeId}"][$iRec->id] = $iRec;
                    }

                    // Ако ще се показват неинвентаризираните ще се игнорират артикулите с инвентаризация
                    if($rec->inventory == 'notInventory'){
                        $data->query->notIn('productId', $inventoryProductIds);
                    } else {

                        // Ако ще са само инвентаризираните се показват само те
                        $data->inventoryRecs = $iRecs;
                        if(countR($inventoryProductIds)){
                            $data->query->XPR('key', 'varchar', "CONCAT(#productId, '|', #storeId)");
                            $data->query->in('key', array_keys($iRecs));
                        } else {
                            $data->query->where("1=2");
                        }
                    }
                }
            }

            if(isset($rec->productId)){
                $data->query->where("#productId = {$rec->productId}");
            }

            // И е избран склад, търсим склад
            if (!isset($data->masterMvc)) {
                if (isset($rec->storeId)) {
                    $selectedStoreName = store_Stores::getHyperlink($rec->storeId, true);
                    $data->title = "|Наличности в склад|* <b style='color:green'>{$selectedStoreName}</b>";
                    $data->query->where("#storeId = {$rec->storeId}");
                    unset($data->listFields['storeId']);
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

            $filtersArr = bgerp_type_CustomFilter::toArray($data->listFilter->rec->filters);
            cat_Products::applyAdditionalListFilters($filtersArr, $data->query, 'productId', 'pState');

            if(!empty($rec->horizon)){

                // Добавяне в лист изгледа
                $data->horizon = dt::addSecs($rec->horizon, null, false);
                $horizonVerbal = dt::mysql2verbal($data->horizon, 'd.m.Y');

                arr::placeInAssocArray($data->listFields, array('reservedOut' => "|*{$horizonVerbal}->|*<span class='small notBolded' title='|Запазено|*'> |Запаз.|*</span>"), null, 'freeQuantityMin');
                arr::placeInAssocArray($data->listFields, array('expectedIn' => "|*{$horizonVerbal}->|*<span class='small notBolded' title='|Очаквано|*'> |Очакв.|*</span>"), null, 'reservedOut');
                arr::placeInAssocArray($data->listFields, array('resultDiff' => "|*{$horizonVerbal}->|*<span class='small notBolded' title='|Разполагаемо|*'> |Разпол.|*</span>"), null, 'expectedIn');
                $mvc->FNC('reservedOut', 'double', ',tdClass=horizonCol red');
                $mvc->FNC('expectedIn', 'double', ',tdClass=horizonCol green');
                $mvc->FNC('resultDiff', 'double', ',tdClass=horizonCol');

                core_Permanent::set($hKey, $rec->horizon);
            } else {
                core_Permanent::remove($hKey);
            }

            if(isset($filtersArr['lastAdded'])){
                $data->query->orderBy('#productCreatedOn=DESC');
            } else {
                $data->query->orderBy('#state,#code');
            }
            
            // Филтър по групи на артикула
            if (!empty($rec->groupId)) {
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $data->query, $rec->groupId, 'productId');
            }

            $filterCaption = isset($data->masterMvc) ? '' : 'Филтрирай';
            $data->listFilter->toolbar->addSbBtn($filterCaption, 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        }
    }


    /**
     * След извличане на записите
     */
    protected static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        if(empty($data->horizon) || !countR($data->recs)) return;
        $productIds = arr::extractValuesFromArray($data->recs, 'productId');
        $storeIds = arr::extractValuesFromArray($data->recs, 'storeId');

        $reserved = store_StockPlanning::getPlannedQuantities($data->horizon, $productIds, $storeIds);

        foreach ($data->recs as &$rec){
            if(isset($reserved[$rec->storeId][$rec->productId])){
                $rec->reservedOut = $reserved[$rec->storeId][$rec->productId]->reserved;
                $rec->expectedIn = $reserved[$rec->storeId][$rec->productId]->expected;
                $rec->resultDiff = $rec->quantity - $rec->reservedOut + $rec->expectedIn;
            } else {
                $rec->resultDiff = $rec->quantity;
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
            self::logWarning('Синхронизирането на складовите наличности е заключено от друг процес');
            
            return;
        }

        $self->saveArray($arrRes['insert']);
        $self->saveArray($arrRes['update'], 'id,quantity,lastUpdated');
        
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
     * Връща запис за наличното,запазеното,очакваното и разполагаемото за даден артикул
     *
     * @param $productId          - артикул
     * @param mixed $stores       - един или няколко склада или null за всички
     * @param null|datetime $date - към коя дата
     * @return object $res
     */
    public static function getQuantities($productId, $stores = null, $date = null)
    {
        // Какви са наличностите
        $query = self::getQuery();
        $query->where("#productId = {$productId}");
        $query->XPR('quantityTotal', 'double', 'SUM(#quantity)');
        $query->XPR('reservedTotalMin', 'double', 'SUM(#reservedQuantityMin)');
        $query->XPR('expectedTotalMin', 'double', 'SUM(#expectedQuantityMin)');
        $query->show('quantityTotal,reservedTotalMin,expectedTotalMin');

        $storesArr = isset($stores) ? arr::make($stores, true) : null;
        if(isset($stores) && countR($storesArr)){
            $query->in("storeId", $storesArr);
        }

        $rec = $query->fetch();
        $quantity = isset($rec->quantityTotal) ? $rec->quantityTotal : 0;
        $res = (object)array('quantity' => $quantity);
        if(is_null($date) && is_object($rec)){
            $res->reserved = $rec->reservedTotalMin;
            $res->expected = $rec->expectedTotalMin;
        } else {
            $res->reserved = 0;
            $res->expected = 0;

            // Ако е посочена дата се взимат очакваното и запазеното към нея
            $planned = store_StockPlanning::getPlannedQuantities($date, $productId, $storesArr);
            foreach ($planned as $storeId => $storeArr){
                array_walk($storeArr, function($o) use ($res){$res->reserved += $o->reserved; $res->expected += $o->expected;});
            }
        }

        $res->free = $res->quantity - $res->reserved + $res->expected;

        return $res;
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $data->listFields['reservedQuantity'] = "Днес->Запаз.";
        $data->listFields['expectedQuantity'] = "Днес->Очакв.";
        $data->listFields['freeQuantity'] = "Днес->Разпол.";
        $data->listFields['reservedQuantityMin'] = "Минимално->Запаз.";
        $data->listFields['expectedQuantityMin'] = "Минимално->Очакв.";
        $data->listFields['freeQuantityMin'] = "Минимално->Разпол.";
        $historyBefore = 'code';
        
        if (isset($data->masterMvc)) {
            if($data->masterMvc instanceof cat_Products){
                arr::placeInAssocArray($data->listFields, array('storeId' => 'Склад|*'), null, 'code');
                if($data->masterData->rec->generic == 'yes'){
                    $data->listFields = array('code' => 'Код', 'productId' => 'Артикул') + $data->listFields;
                } else {
                    unset($data->listFields['code']);
                    unset($data->listFields['productId']);
                    $historyBefore = 'storeId';
                }
            } else {
                unset($data->listFields['storeId']);
            }
        }

        if (acc_BalanceDetails::haveRightFor('history')) {
            arr::placeInAssocArray($data->listFields, array('history' => ' '), $historyBefore);
        }
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field nowrap');
        $data->listTableMvc->FLD('measureId', 'varchar', 'tdClass=centered');
        $data->listTableMvc->setField('lastUpdated', 'tdClass=small');
        if (!countR($data->rows)) return;

        if(is_array($data->inventoryRecs)){
            $data->listFields['inventory'] = 'Инвентар.';
        }

        $today = dt::today();
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            $title = 'От кои документи е сформирано количеството';

            foreach (array('reservedQuantity', 'expectedQuantity', 'reservedQuantityMin', 'expectedQuantityMin', 'reservedOut', 'expectedIn') as $type){
                if (!empty($rec->{$type})) {
                    $date = in_array($type, array('reservedQuantity', 'expectedQuantity')) ? $today : (in_array($type, array('reservedQuantityMin', 'expectedQuantityMin')) ? $rec->dateMin : $data->horizon);

                    $tooltipUrl = toUrl(array('store_Products', 'ShowReservedDocs', 'productId' => $rec->productId, 'stores' => keylist::addKey('', $rec->storeId), 'replaceField' => "{$type}{$rec->id}", 'field' => $type, 'date' => $date), 'local');
                    $arrowImg = ht::createElement('img', array('height' => 16, 'width' => 16, 'src' => sbf('img/32/info-gray.png', '')));
                    $arrow = ht::createElement('span', array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl, 'title' => $title), $arrowImg, true);
                    $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='{$type}{$rec->id}'></span>{$arrow}</span>";
                    $row->{$type} = $arrow . $row->{$type};
                }
            }

            if(!empty($rec->freeQuantity) && !isset($rec->reservedQuantity) && !isset($rec->expectedQuantity)){
                $row->freeQuantity = "<span class='quiet'>{$row->freeQuantity}</span>";
            }

            if(!empty($rec->freeQuantityMin) && !isset($rec->reservedQuantityMin) && !isset($rec->expectedQuantityMin)){
                $row->freeQuantityMin = "<span class='quiet'>{$row->freeQuantity}</span>";
            }

            if(!empty($rec->resultDiff) && !isset($rec->reservedOut) && !isset($rec->expectedIn)){
                $row->resultDiff = "<span class='quiet'>{$row->resultDiff}</span>";
            }

            $dateMin = !empty($rec->dateMin) ? $rec->dateMin : dt::today();
            $date = dt::mysql2verbal($dateMin, 'd.m.Y');
            $row->freeQuantityMin = ht::createHint($row->freeQuantityMin, $date,'img/16/calendar_1.png', true, 'height=12px,width=12px');

            // Ако се показва колонка за последно инвентаризиране - да се покаже последния документ
            if(is_array($data->inventoryRecs)){
                $key = "{$rec->productId}|{$rec->storeId}";
                if(is_array($data->inventoryRecs[$key])){
                    krsort($data->inventoryRecs[$key]);
                    $lastInvRec = $data->inventoryRecs[$key][key($data->inventoryRecs[$key])];
                    $lastInvVerbal = dt::mysql2verbal($lastInvRec->valior, 'd.m.y');
                    $row->inventory = store_InventoryNotes::getLink($lastInvRec->noteId, 0) . " <small>[{$lastInvVerbal}]</small>";
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
     * Обновяване на резервираните наличности по крон
     */
    public function cron_CalcReservedQuantity()
    {
        $plannedCount = store_StockPlanning::count();
        core_App::setTimeLimit($plannedCount * 0.7, false,200);

        // Синхронизират се новите със старите записи
        $storeQuery = static::getQuery();
        $oldRecs = $storeQuery->fetchAll();

        // Изчисляване на максималните запазени количества
        $queue = array();
        $reservedMax = store_StockPlanning::getMaxReservedByProduct();
        $queue[] = (object)array('quantities' => $reservedMax, 'fieldReserved' => 'reservedQuantityMin', 'fieldExpected' => 'expectedQuantityMin', 'dateField' => 'dateMin');

        // Изчисляване на запазеното за днеска
        $date = dt::today();
        $reserved = store_StockPlanning::getPlannedQuantities($date);

        unset($reserved[null]);
        $queue[] = (object)array('quantities' => $reserved, 'fieldReserved' => 'reservedQuantity', 'fieldExpected' => 'expectedQuantity');

        $result = array();
        foreach ($queue as $object) {
            foreach ($object->quantities as $arr) {
                foreach ($arr as $o) {
                    $key = "{$o->storeId}|{$o->productId}";
                    if (!array_key_exists($key, $result)) {
                        $result[$key] = (object) array('storeId' => $o->storeId, 'productId' => $o->productId, 'state' => 'active');
                    }

                    $result[$key]->{$object->fieldReserved} = ($o->reserved) ? $o->reserved : null;
                    $result[$key]->{$object->fieldExpected} = ($o->expected) ? $o->expected : null;
                    if(isset($object->dateField)){
                        $result[$key]->{$object->dateField} = ($o->date) ? $o->date : null;
                    }
                }
            }
        }

        // Отново се обхождат всички изчислени записи
        $now = dt::now();
        foreach ($result as  $key => &$newObj) {
            $newObj->lastUpdated = $now;

            // Намират се старите им записи
            $exRecs = array_filter($oldRecs, function($a) use ($newObj) { return $a->storeId == $newObj->storeId && $a->productId == $newObj->productId;});
            if(is_array($exRecs)){
                $exRec = $exRecs[key($exRecs)];
                $currentFreeQuantity = $exRec->quantity - $exRec->reservedQuantity + $exRec->expectedQuantity;
                $newFreeQuantity = $exRec->quantity - $newObj->reservedQuantityMin + $newObj->expectedQuantityMin;

                // Ако текущото разполагаемо е по-малко или равно на намереното минимално разполагаемо, то текущото ще стане минимално !
                if($currentFreeQuantity <= $newFreeQuantity){
                    $newObj->reservedQuantityMin = $exRec->reservedQuantity;
                    $newObj->expectedQuantityMin = $exRec->expectedQuantity;
                    $newObj->dateMin = $date;
                }
            }
        }

        $res = arr::syncArrays($result, $oldRecs, 'storeId,productId', 'reservedQuantity,expectedQuantity,reservedQuantityMin,expectedQuantityMin,dateMin');

        // Заклюване на процеса
        if (!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
            $this->logWarning('Синхронизирането на складовите наличности е заключено от друг процес');
            
            return;
        }

        // Добавяне и ъпдейт на резервираното количество на новите
        $this->saveArray($res['insert']);
        $this->saveArray($res['update'], 'id,reservedQuantity,expectedQuantity,reservedQuantityMin,expectedQuantityMin,dateMin,lastUpdated');

        // Намиране на тези записи, от старите които са имали резервирано к-во, но вече нямат
        $unsetArr = array_filter($oldRecs, function (&$r) use ($reservedMax, $reserved, $now) {
            $unset = false;

            // Ако е имало запазено/очаквано за днеска, но вече няма ще се ънсетнат
            if(isset($r->reservedQuantity) || isset($r->expectedQuantity)){
                if(!isset($reserved[$r->storeId][$r->productId])){
                    $r->reservedQuantity = null;
                    $r->expectedQuantity = null;
                    $r->lastUpdated = $now;
                    $unset = true;
                }
            }

            // Ако е имало минимално запазено/очаквано , но вече няма ще се ънсетнат
            if(isset($r->reservedQuantityMin) || isset($r->expectedQuantityMin)){
                if(!isset($reservedMax[$r->storeId][$r->productId])){
                    $r->reservedQuantityMin = null;
                    $r->expectedQuantityMin = null;
                    $r->dateMin = null;
                    $r->lastUpdated = $now;
                    $unset = true;
                }
            }

            return $unset;
        });

        // Техните резервирани количества се изтриват
        if (countR($unsetArr)) {
            $this->saveArray($unsetArr, 'id,reservedQuantity,expectedQuantity,reservedQuantityMin,expectedQuantityMin,dateMin,lastUpdated');
        }
        
        // Освобождаване на процеса
        core_Locks::release(self::SYNC_LOCK_KEY);
    }
    
    
    /**
     * Показва информация за резервираните количества
     */
    public function act_ShowReservedDocs()
    {
        requireRole('powerUser');
        expect($productId = Request::get('productId', 'int'));
        expect($replaceField = Request::get('replaceField', 'varchar'));
        $stores = Request::get('stores', 'varchar');
        $stores = !empty($stores) ? keylist::toArray($stores) : null;

        $field = Request::get('field', 'varchar');
        $toDate = Request::get('date', 'date');
        $today = dt::today();
        $recs = store_StockPlanning::getRecs($productId, $stores, $toDate, $field);
        $recs = array_splice($recs, 0, static::SHOW_DOCUMENTS_IN_PLANNED_STOCK_HINT);

        $links = '';
        foreach($recs as $dRec){
            $Source = cls::get($dRec->sourceClassId);
            $row = (object)array('date' => dt::mysql2verbal($dRec->date));

            $uom = cat_UoM::getShortName($dRec->measureId);
            $quantity = setIfNot($dRec->quantityOut, $dRec->quantityIn);
            $quantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);

            // Ако източника е документ - показват се данните му
            if($Source->hasPlugin('doc_DocumentPlg')){
                $row->link = $Source->getLink($dRec->sourceId, 0);
                $docRec = $Source->fetch($dRec->sourceId, 'createdBy,folderId,state');
                $row->createdBy = crm_Profiles::createLink($docRec->createdBy);
                $folderId = doc_Folders::recToVerbal(doc_Folders::fetch($docRec->folderId))->title;
                $row->createdBy = " {$quantityVerbal} {$uom} | {$folderId} | {$row->createdBy}";
            } else {
                // Ако източника не е документ
                $row->link = $Source->getHyperlink($dRec->sourceId, true);
                $docRec = $Source->fetch($dRec->sourceId, 'createdBy,state');
                $row->createdBy = crm_Profiles::createLink($docRec->createdBy);
                $row->createdBy .= " | {$quantityVerbal} {$uom}";
            }

            $state = $docRec->state;

            $row->link = "<span class='state-{$state} document-handler'>{$row->link}</span>";
            if($dRec->date < $today) {
                $row->link = ht::createHint($row->link, 'Датата е в миналото', 'warning', false);
            }

            // Подготвяне на реда с информация
            $link = new core_ET("<div style='float:left;padding-bottom:2px;padding-top: 2px;'>[#link#]<!--ET_BEGIN date--> | [#date#]<!--ET_END date-->| [#createdBy#]</div>");
            $link->placeObject($row);
            $links .= $link->getContent();
        }

        $storeId = (countR($stores) == 1) ? key($stores) : null;
        Request::setProtected('hash');
        $linkToFilter = ht::createLink(tr('Още|* ...'), array('store_StockPlanning', 'Browse', 'storeId' => $storeId, 'productId' => $productId, 'hash' => md5(store_StockPlanning::LIST_CACHE_STRING)))->getContent();
        Request::removeProtected('hash');
        $links .= "<br><div style='float:left;padding-bottom:2px;padding-top: 2px;'>{$linkToFilter}</div>";

        $tpl = new core_ET($links);

        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $replaceField, 'html' => $tpl->getContent(), 'replace' => true);
            
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

                $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$Detail->productFld}");
                $dQuery->where("#{$Detail->masterKey} = {$rec->id} AND #canStore = 'yes'");
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
            $tabParam = $data->masterData->tabTopParam;
            $prepareTab = Request::get($tabParam);
            
            if($data->masterData->rec->canStore != 'yes' || !store_Products::haveRightFor('list') || $prepareTab != 'store_Products'){
                $data->render = false;
            }
            
            if($data->masterData->rec->canStore != 'yes' || !store_Products::haveRightFor('list')){
                
                return;
            }
            
            $data->TabCaption = 'Наличности';
            $data->Tab = 'top';
        }
        
        parent::prepareDetail_($data);

        if(countR($data->recs)){
            $totalField = ($data->masterData->rec->generic == 'yes') ? 'code' : 'storeId';
            $data->rows['total'] = (object)array($totalField =>  tr('Сумарно'));
            $data->rows['total']->ROW_ATTR['style'] = 'background-color:#eee;font-weight:bold;';
            $data->rows['total']->ROW_ATTR['class'] = 'totalRow';
            
            foreach (array('quantity', 'reservedQuantity', 'expectedQuantity', 'expectedQuantityMin', 'freeQuantity', 'freeQuantityMin', 'reservedQuantityMin', 'reservedOut', 'expectedOut') as $fld){
                ${$fld} = arr::sumValuesArray($data->recs, $fld, true);
                $data->rows['total']->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal(${$fld});
            }
        }
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
        $tpl->append(tr('Наличности'), 'title');

        if($data->masterData->rec->generic == 'yes'){
            $infoBlock = tr("Показани са наличностите на артикулите, които заместват|* <b class='green'>") . cat_Products::getTitleById($data->masterId) . "</b>";
            $infoBlock = "<div style='margin-bottom:5px'>{$infoBlock}</div>";
            $tpl->append($infoBlock, 'content');
        }
        
        $tpl->append(parent::renderDetail_($data), 'content');
        
        return $tpl;
    }


    /**
     * Връща наличните количества в посочените складове към датата
     *
     * @param int $productId  - ид на артикул
     * @param date|null $date - към коя дата
     * @param mixed $stores   - от кои складове
     * @return array $res     - наличните к-ва по склад
     */
    public static function getQuantitiesByStore($productId, $date = null, $stores = null)
    {
        $res = array();
        if(isset($stores)){
            $storeArr = keylist::isKeylist($stores) ? keylist::toArray($stores) : $stores;
        } else {
            $sQuery = store_Stores::getQuery();
            $sQuery->where("#state != 'rejected' AND #state != 'closed'");
            $sQuery->show('id');
            $storeArr = arr::extractValuesFromArray($sQuery->fetchAll());
        }

        foreach ($storeArr as $storeId){
            $quantity = store_Products::getQuantities($productId, $storeId, $date)->quantity;
            $res[$storeId] = $quantity;
        }

        return $res;
    }


    function remote_getStocks($authorizationId, $args)
    {
        expect(remote_Authorizations::fetchRec($authorizationId));

        // Кои са стандартните артикули в посочените складове
        $res = $measureSysIds = array();
        $query = static::getQuery();
        $query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $query->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $query->where("#isPublic = 'yes' AND (#code IS NOT NULL OR #code != '')");
        $query->in('storeId', $args['stores']);
        $recs = $query->fetchAll();
        if(!countR($recs)) return $res;

        // Еднократно извличане на сис ид-та на мерките
        $measureIds = arr::extractValuesFromArray($recs, 'measureId');
        $mQuery = cat_UoM::getQuery();
        $mQuery->in('id', $measureIds);
        $mQuery->show('id,sysId');
        while($mRec = $mQuery->fetch()){
            $measureSysIds[$mRec->id] = $mRec->sysId;
        }

        // Връщане на нужните ни данни
        foreach($recs as $rec){
            $arr = array('code' => $rec->code, 'measureSysId' => $measureSysIds[$rec->measureId], 'storeId' => $rec->storeId);
            foreach (array('quantity', 'reservedQuantity', 'expectedQuantity', 'reservedQuantityMin', 'expectedQuantityMin', 'dateMin') as $fld){
                $arr[$fld] = $rec->{$fld};
            }
            $res[] = $arr;
        }

        return $res;
    }
}
