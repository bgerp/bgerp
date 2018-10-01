<?php


/**
 * Наличности в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2018 Experta OOD
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
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета за листовия изглед?
     */
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantityOnPallets,quantityOnZones,quantityNotOnPallets,quantity=Количество->Общо';
    
    
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
        
        $this->FLD('quantityOnPallets', 'double(maxDecimals=2)', 'caption=Количество->На стелажи,input=hidden,smartCenter');
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
        
        if ($rec->quantityOnPallets > 0) {
            $row->_rowTools->addLink('Търсене', array('rack_Pallets', 'list', 'productId' => $rec->productId, 'ret_url' => true), 'ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт');
            $row->quantityOnPallets = ht::createLink('', array('rack_Pallets', 'list', 'productId' => $rec->productId, 'ret_url' => true), false, 'ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт') . '&nbsp;' . $row->quantityOnPallets;
        }
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
     * Връща достъпните продаваеми артикули
     */
    public static function getSellableProducts($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
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
        
        $inIds = array();
        while ($rec = $query->fetch()) {
            $inIds[$rec->productId] = $rec->productId;
        }
        
        $products = array();
        $pQuery = cat_Products::getQuery();
        
        if (is_array($inIds)) {
            if (!count($inIds)) {
                
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
        
        $xpr = "CONCAT(' ', #name, ' ', #code)";
        $pQuery->XPR('searchFieldXpr', 'text', $xpr);
        $pQuery->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
        
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
        
        $pQuery->show('id,name,code,isPublic,searchFieldXpr');
        
        while ($pRec = $pQuery->fetch()) {
            $products[$pRec->id] = cat_Products::getRecTitle($pRec, false);
        }
       
        return $products;
    }
    
    
    /**
     * Рекалкулира какво количество има по зони
     * 
     * @param int|array $productArr - ид на артикул или масив от ид-та на артикули
     * @param int $storeId          - избрания склад
     * @return void
     */
    public static function recalcQuantityOnZones($productArr, $storeId = null)
    {
        $productArr = arr::make($productArr, true);
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        
        $saveArr = array();
        $query = self::getQuery();
        $query->where("#storeId = {$storeId}");
        $query->in("productId", $productArr);
        
        while($rec = $query->fetch()){
            $rec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($rec->productId, $storeId);
            $saveArr[$rec->id] = $rec;
        }
        
        self::saveArray($saveArr, 'id,quantityOnZones');
    }
}
