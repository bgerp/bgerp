<?php


/**
 * Наличности в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Products extends store_Products
{
    /**
     * Заглавие
     */
    public $title = 'Артикули в склада';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rackSee';
    
    
    /**
     * Кой може да преизчислява кешираните количества?
     */
    public $canRecalccachecquantity = 'admin,debug,rackMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета за листовия изглед?
     */
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantity=Количество->Налично,quantityOnPallets,quantityOnZones,quantityNotOnPallets';
    
    
    /**
     * Задължително филтър по склад
     */
    protected $mandatoryStoreFilter = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->loadList = arr::make($this->loadList, true);
        unset($this->loadList['store_Wrapper']);
        $this->loadList['rack_Wrapper'] = 'rack_Wrapper';
        $this->loadList['plg_RowTools2'] = 'plg_RowTools2';
        parent::description();
        
        $this->FLD('quantityOnPallets', 'double(maxDecimals=2)', 'caption=Количество->На палети,input=hidden,smartCenter');
        $this->FLD('quantityOnZones', 'double(maxDecimals=2)', 'caption=Количество->В зони,input=hidden,smartCenter');
        $this->XPR('quantityNotOnPallets', 'double(maxDecimals=2)', '#quantity - IFNULL(#quantityOnPallets, 0)- IFNULL(#quantityOnZones, 0)', 'caption=Количество->На пода,input=hidden,smartCenter');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        core_RowToolbar::createIfNotExists($row->_rowTools);

        // Добавяне на бутони за преизчисляване на кешираните количества
        if ($mvc->haveRightFor('recalccachecquantity', $rec->id)) {
            $row->_rowTools->addLink('К-во по зони', array('rack_Products', 'recalcquantityonzones', 'id' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Преизчисляване на количеството по зони');
            $row->_rowTools->addLink('К-во по палети', array('rack_Products', 'recalcquantityonpallets', 'id' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Преизчисляване на количеството по палети');
        }
        
        if ($rec->quantityOnPallets > 0) {
            $row->_rowTools->addLink('Търсене', array('rack_Pallets', 'list', 'productId' => $rec->productId, 'ret_url' => true), 'ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт');
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        
        if(countR($rows)){
            foreach ($rows as $id => &$row){
                $rec = $data->recs[$id];
                
                if ($rec->quantityOnPallets > 0) {
                    $row->quantityOnPallets = ht::createLink('', array('rack_Pallets', 'list', 'productId' => $rec->productId, 'ret_url' => true), false, 'ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт') . '&nbsp;' . $row->quantityOnPallets;
                }

                if (rack_Movements::haveRightFor('add', (object) array('productId' => $rec->productId)) && $rec->quantityNotOnPallets > 0) {
                    $measureId = cat_Products::fetchField($rec->productId, 'measureId');
                    $link = ht::createLink('', array('rack_Movements', 'add', 'productId' => $rec->productId, 'packagingId' => $measureId, 'movementType' => 'floor2rack', 'ret_url' => true), false, 'ef_icon=img/16/pallet1.png,title=Палетиране на артикул');
                    $row->quantityNotOnPallets = "{$link} {$row->quantityNotOnPallets}";
                }

                if(core_Packs::isInstalled('batch')){
                    $bCount = batch_Items::count("#productId = {$rec->productId} AND #storeId = {$rec->storeId}");
                    if($bCount && rack_ProductsByBatches::haveRightFor('list')){
                        $link = ht::createLink('', array('rack_ProductsByBatches', 'list', 'productId' => $rec->productId), false, 'ef_icon=img/16/google-search-icon.png,title=Показване на наличните партиди в склада');
                        $row->productId->append("&nbsp;");
                        $row->productId->append($link);
                    }
                }
            }
        }
    }
    
    
    /**
     * Екшън преизчисляващ количеството по палети
     */
    public function act_Recalcquantityonpallets()
    {
        $this->requireRightFor('recalccachecquantity');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('recalccachecquantity', $rec);
        
        // Преизчисляване на количеството по палети
        rack_Pallets::recalc($rec->productId, $rec->storeId);
        
        return followRetUrl(null, '|Количеството по палети е преизчислено успешно|*!');
    }
    
    
    /**
     * Екшън преизчисляващ количеството по зони
     */
    public function act_Recalcquantityonzones()
    {
        $this->requireRightFor('recalccachecquantity');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('recalccachecquantity', $rec);
        
        // Преизчисляване на количеството по зони
        $rec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($rec->productId, $rec->storeId);
        $this->save($rec, 'id,quantityOnZones');
        
        return followRetUrl(null, '|Количеството по зони е преизчислено успешно|*!');
    }
    
    
    /**
     * Избягване скриването на бутоните в rowTools2
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        $data->masterMvc = true;
    }
    
    
    /**
     * Връща достъпните складируеми артикули, налични в текущия склад
     */
    public static function getStorableProducts($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $query = store_Products::getQuery();
        $query->groupBy('productId');
        $query->show('productId');
        
        if ($onlyIds) {
            if (is_array($onlyIds)) {
                $onlyIds = implode(',', $onlyIds);
            }
            $onlyIds = trim($onlyIds, ',');
            $query->where("#productId IN ({$onlyIds})");
        } else {
            $storeId = store_Stores::getCurrent();
            $query->where("#storeId = {$storeId}");
        }
        
        $inIds = arr::extractValuesFromArray($query->fetchAll(), 'productId');
        
        // Подсигуряване че конкретните артикули, ще са винаги заредени в опциите
        if(ctype_digit($onlyIds) && !array_key_exists($onlyIds, $inIds)){
            $inIds[$onlyIds] = $onlyIds;
        }
        
        $products = array();
        $pQuery = cat_Products::getQuery();
        
        if (is_array($inIds)) {
            if (!countR($inIds)) {
                
                return array();
            }
            $ids = implode(',', $inIds);
            $ids = trim($ids, ',');
            expect(preg_match("/^[0-9\,]+$/", $ids), $ids, $inIds);
            $pQuery->where("#id IN (${ids})");
        } elseif (ctype_digit("{$inIds}")) {
            $pQuery->where("#id = ${onlyIds}");
        } elseif (preg_match("/^[0-9\,]+$/", $inIds)) {
            $pQuery->where("#id IN (${onlyIds})");
        }

        cat_Products::addSearchQueryToKey2SelectArr($pQuery, $q, $limit);
        while ($pRec = $pQuery->fetch()) {
            $products[$pRec->id] = cat_Products::getRecTitle($pRec, false);
        }
       
        return $products;
    }
    
    
    /**
     * Рекалкулира какво количество има по зони
     *
     * @param int|array $productArr - ид на артикул или масив от ид-та на артикули
     * @param int       $storeId    - избрания склад
     *
     * @return void
     */
    public static function recalcQuantityOnZones($productArr, $storeId = null)
    {
        $productArr = arr::make($productArr, true);
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        
        $saveArr = array();
        $query = self::getQuery();
        $query->where("#storeId = {$storeId}");
        $query->in('productId', $productArr);
        
        while ($rec = $query->fetch()) {
            $rec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($rec->productId, $storeId);
            $saveArr[$rec->id] = $rec;
        }
        
        self::saveArray($saveArr, 'id,quantityOnZones');
    }
    
    
    /**
     * Какво е разполагаемото количество от артикула на пода в склада
     * 
     * @param int $productId          - ид на артикул
     * @param int|null $batch         - номер на партида, или празно ако няма партида
     * @param int|null $storeId       - склад, ако няма, текущия
     * @return double $floorQuantity  - наличното количестто от партидата (или без партида) на пода в склада
     */
    public static function getFloorQuantity($productId, $batch = null, $storeId = null)
    {
        // Какво е количеството на пода
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        
        // Количество на пода = Общо количество - Количество на палети - Количество в зоните
        $floorQuantity = self::fetchField("#productId = {$productId} AND #storeId = {$storeId}", 'quantityNotOnPallets');
        
        $palletQuery = rack_Pallets::getQuery();
        $palletQuery->where("#productId = {$productId} AND #storeId = {$storeId}");
        $palletQuery->XPR('sum', 'double', 'SUM(#quantity)');
        
        // Ако има конкретна партида
        if(!empty($batch)){
            $palletQuery->where(array("#batch = '[#1#]'", $batch));
            
            // Очакваното к-во на пода с премахнатото палетирано
            $expectedBatchQuantity = batch_Items::getQuantity($productId, $batch, $storeId);
            $batchQuantityOnPallets = $palletQuery->fetch()->sum;
            $batchQuantityOnTheFloor = $expectedBatchQuantity - $batchQuantityOnPallets;
            $floorQuantity = min($floorQuantity, $batchQuantityOnTheFloor);
        } else {
            // Ако няма партида на пода се смята разликата от к-то на пода минус всичкото палетирано
            $palletQuery->where("#batch IS NOT NULL AND #batch != ''");
            $batchQuantityOnPallets = $palletQuery->fetch()->sum;
            
            // Очаквано количество на партиди в склада
            $batchesInStore = batch_Items::getBatchQuantitiesInStore($productId, $storeId);
            $batchQuantityInStore = array_sum($batchesInStore);
            
            // Какво количество има по партиди в зоните
            $zoneQuery = rack_ZoneDetails::getQuery();
            $zoneQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
            $zoneQuery->XPR('sum', 'double', 'sum(#movementQuantity)');
            $zoneQuery->show('sum');
            $zoneQuery->where("#productId = {$productId} AND #storeId = {$storeId} AND #batch IS NOT NULL AND #batch != ''");
            $zRec = $zoneQuery->fetch();
            $batchQuantityOnZones =  ($zRec) ? $zRec->sum : 0;
            
            // Количество партиди на пода = Количество от партидния склад - Количество партиди по палети - Количество партиди в зони
            // Количество без партиди на пода = Количество на пода - Количество партиди на пода
            $quantityBatchesOnTheFloor = $batchQuantityInStore - $batchQuantityOnPallets - $batchQuantityOnZones;
            $floorQuantity -= $quantityBatchesOnTheFloor;
        }

        $productMeasureId = cat_Products::fetchField($productId, 'measureId');
        $round = cat_UoM::fetchField($productMeasureId, 'round');
        $floorQuantity = round($floorQuantity, $round);

        return $floorQuantity;
    }

    /**
     * Подготовка на палетите
     *
     * @param stdClass $data
     */
    public function preparePallets_($data)
    {
        $canStore = $data->masterData->rec->canStore;
        if($canStore != 'yes'){
            $data->hide = true;
            return;
        }

        $rackQuery = rack_Racks::getQuery();
        $storesWithRacks = arr::extractValuesFromArray($rackQuery->fetchAll(), 'storeId');
        $count = countR($storesWithRacks);
        if(empty($count)){
            $data->hide = true;
            return;
        }

        $data->TabCaption = 'Позиции';
        $data->Tab = 'top';
        $data->recs = $data->rows = array();

        // Ако е отворен друг таб няма да се подготвят данните
        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if($prepareTab != 'pallets') {
            $data->hide = true;
            return;
        }

        // Ако складовете са повече от един да се показва филтър
        if($count > 1){
            $storeOptions = array();
            foreach ($storesWithRacks as $storeId){
                $storeOptions[$storeId] = store_Stores::getTitleById($storeId, false);
            }

            // Подготвяме формата за филтър по склад
            $form = cls::get('core_Form');
            $form->FLD("storeId{$data->masterId}", 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,silent');
            $form->setOptions("storeId{$data->masterId}", array('' => '') + $storeOptions);
            $form->view = 'horizontal';
            $form->setAction(getCurrentUrl());
            $form->toolbar->addSbBtn('', 'default', 'id=filter', 'ef_icon=img/16/funnel.png');

            // Инпутваме формата
            $form->input();
            $data->form = $form;
        }

        $storeQuery = store_Stores::getQuery();
        $storeQuery->where("#state = 'active'");
        $storeQuery->show('id');
        if(isset($form->rec->{"storeId{$data->masterId}"})){
            $storeQuery->where("#id = {$form->rec->{"storeId{$data->masterId}"}}");
        } else {
            if(countR($storesWithRacks)){
                $storeQuery->in('id', $storesWithRacks);
            } else {
                $storeQuery->where("1=2");
            }
        }

        $stores = arr::extractValuesFromArray($storeQuery->fetchAll(), 'id');
        $measureId = tr(cat_UoM::getShortName($data->masterData->rec->measureId));

        // Подготовка на позициите на складовете
        foreach ($stores as $storeId){
            $pRec = rack_Products::fetch("#storeId = {$storeId} AND #productId = {$data->masterId}");
            if(empty($pRec->quantity)) continue;

            $rQuery = rack_Pallets::getQuery();
            $rQuery->where("#storeId = {$storeId} AND #productId = {$data->masterId} AND #state = 'active'");
            while($rRec = $rQuery->fetch()){
                $data->recs[] = (object)array('rec' => $rRec,
                                              'storeId' => $rRec->storeId,
                                              'batch' => $rRec->batch,
                                              'position' => $rRec->position,
                                              'quantity' => $rRec->quantity,);
            }

            $data->recs[] = (object)array('storeId' => $storeId,
                                          'batch' => null,
                                          'position' => rack_PositionType::FLOOR,
                                          'quantity' => $pRec->quantityNotOnPallets,);

            $data->recs[] = (object)array('storeId' => $storeId,
                                          'batch' => null,
                                          'position' => null,
                                          'quantity' => $pRec->quantityOnZones,);
        }

        // Подготвяме страницирането
        $pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $pager->setPageVar($data->masterMvc->className, $data->masterId);
        $pager->itemsCount = countR($data->recs);
        $data->pager = $pager;

        $batchDef = batch_Defs::getBatchDef($data->masterId);
        $fields = $this->selectFields();
        $fields['-list'] = true;

        // Всяка позиция се вербализира
        foreach ($data->recs as $id => $rec){
            if (!$data->pager->isOnPage()) continue;

            $row = (object)array('storeId' => store_Stores::getHyperlink($rec->storeId, true), 'measureId' => $measureId);
            if(isset($rec->rec)){
                $pRow = rack_Pallets::recToVerbal($rec->rec, $fields);
                if(!Mode::isReadOnly()){
                    core_RowToolbar::createIfNotExists($pRow->_rowTools);
                    $row->tools = $pRow->_rowTools->renderHtml();
                }
            }

            $rec->quantity = empty($rec->quantity) ? 0 : $rec->quantity;
            $row->quantity = core_Type::getByName('double(smartRound)')->toVerbal($rec->quantity);
            $row->quantity = ht::styleNumber($row->quantity, $rec->quantity);
            if(is_null($rec->position)){
                $row->position = "<i>" . tr('В зони') . "</i>";
            } elseif($rec->position == rack_PositionType::FLOOR){
                $row->position = "<i>" . tr('На пода') . "</i>";
            } else {
                $row->position = core_Type::getByName('varchar')->toVerbal($rec->position);
                if(rack_Pallets::haveRightFor('forceopen', (object)array('storeId' => $rec->storeId))){
                    $row->position = ht::createLink($row->position, array('rack_Pallets', 'forceopen', 'storeId' => $rec->storeId, 'productId' => $data->masterId, 'position' => $rec->position));
                }
            }

            if(isset($batchDef)){
                if(!empty($rec->batch)){
                    $row->batch = $batchDef->toVerbal($rec->batch);
                    if (batch_Movements::haveRightFor('list')) {
                        $link = array('batch_Movements', 'list', 'batch' => $rec->batch);
                        if (isset($fields['-list'])) {
                            $link += array('productId' => $rec->productId, 'storeId' => $rec->storeId);
                        }
                        $row->batch = ht::createLink($row->batch, $link);
                    }
                }
            }

            $row->ROW_ATTR['class'] = 'state-active';
            $data->rows[$id] = $row;
        }

        $img = ht::createElement('img', array('src' => sbf('img/16/tools.png', '')));
        $data->listFields = arr::combine(array('tools' => '|*' . $img->getContent()), arr::make('tools=a,storeId=Склад,position=Позиция,batch=Партида,measureId=Мярка,quantity=Количество', true));
    }


    /**
     * Редниране на палетите
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderPallets_($data)
    {
        if($data->hide) return new core_ET("");

        $tpl = getTplFromFile('rack/tpl/PalletDetail.shtml');
        if(isset($data->form)){
            $tpl->append($data->form->renderHtml(), 'FILTER');
        }

        // Подготвяме таблицата за рендиране
        $fieldSet = new core_FieldSet();
        $fieldSet->FLD('position', 'varchar', 'smartCenter');
        $fieldSet->FLD('storeId', 'varchar', 'tdClass=leftCol');
        $fieldSet->FLD('tools', 'varchar', 'tdClass=small-field');
        $fieldSet->FLD('measureId', 'varchar', 'smartCenter');
        $fieldSet->FLD('batch', 'varchar', 'smartCenter');
        $fieldSet->FLD('quantity', 'double');
        $table = cls::get('core_TableView', array('mvc' => $fieldSet));

        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'batch,tools');

        // Ако е филтрирано по склад, скриваме колонката на склада
        if (isset($data->storeId)) {
            unset($data->listFields['storeId']);
        }
        $dTpl = $table->get($data->rows, $data->listFields);
        $tpl->append($dTpl, 'content');

        if (isset($data->pager)) {
            $tpl->append($data->pager->getHtml(), 'content');
        }

        return $tpl;
    }
}
