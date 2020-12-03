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
    public $canList = 'ceo,rack';
    
    
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
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantity=Количество->Разполагаемо,quantityOnPallets,quantityOnZones,quantityNotOnPallets';
    
    
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
        
        if (rack_Movements::haveRightFor('add', (object) array('productId' => $rec->productId)) && $rec->quantityNotOnPallets > 0) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            
            // ОТ URL е махнато количеството, защото (1) винаги предлага с това количество палет; (2) Неща, които ги има в базата, не трябва да се предават в URL
            $row->_rowTools->addLink('Палетиране', array('rack_Movements', 'add', 'productId' => $rec->productId, 'packagingId' => $measureId, 'movementType' => 'floor2rack', 'ret_url' => true), 'ef_icon=img/16/pallet1.png,title=Палетиране на артикул');
        }
        
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
        
        return followRetUrl(null, 'Количеството по палети е преизчислено успешно|*!');
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
        
        return followRetUrl(null, 'Количеството по зони е преизчислено успешно|*!');
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
            $query->where("#storeId = {$storeId} AND #quantity > 0");
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
        
        $pQuery->XPR('searchFieldXprLower', 'text', "LOWER(CONCAT(' ', COALESCE(#name, ''), ' ', COALESCE(#code, ''), ' ', COALESCE(#nameEn, ''), ' ', 'Art', #id))");
       
        if ($q) {
            if ($q{0} == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            $qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $pQuery->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $pQuery->limit($limit);
        }
        
        $pQuery->show('id,name,code,isPublic,nameEn');
        
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
            $palletQuery->where("#batch = '{$batch}'");
            
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
        
        return $floorQuantity;
    }
}
