<?php


/**
 * Модел за "Детайл на зоните"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ZoneDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайл на зоните';
    
    
    /**
     * Кой може да листва?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'zoneId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'movementsHtml';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'id, productId, batch, status=Състояние,movementsHtml=@, packagingId, batch';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    public $hashField = 'id';
    
    
    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";

    
    /**
     * Шаблон за реда в листовия изглед
     */
    public static $allocatedMovements = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=rack_Zones)', 'caption=Зона, input=hidden,silent,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,tdClass=productCell nowrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,removeAndRefreshForm=quantity|quantityInPack|displayPrice,tdClass=nowrap rack-quantity');
        $this->FLD('batch', 'varchar', 'caption=Партида,smartCenter,tdClass=rack-zone-batch,notNull');
        $this->FLD('documentQuantity', 'double(smartRound)', 'caption=Очаквано,mandatory');
        $this->FLD('movementQuantity', 'double(smartRound)', 'caption=Нагласено,mandatory');
        $this->FNC('status', 'varchar', 'tdClass=zone-product-status');
        
        $this->setDbIndex('zoneId,productId,packagingId,batch');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->movementQuantity = $rec->movementQuantity / $rec->quantityInPack;
            $rec->documentQuantity = $rec->documentQuantity / $rec->quantityInPack;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $movementQuantityVerbal = $mvc->getFieldType('movementQuantity')->toVerbal($rec->movementQuantity);
        $documentQuantityVerbal = $mvc->getFieldType('documentQuantity')->toVerbal($rec->documentQuantity);
        $moveStatusColor = (round($rec->movementQuantity, 4) < round($rec->documentQuantity, 4)) ? '#ff7a7a' : (($rec->movementQuantity == $rec->documentQuantity) ? '#ccc' : '#8484ff');
        
        $row->status = "<span style='color:{$moveStatusColor} !important'>{$movementQuantityVerbal}</span> / <b>{$documentQuantityVerbal}</b>";
   
        // Ако има повече нагласено от очакането добавя се бутон за връщане на количеството
        $overQuantity = round($rec->movementQuantity - $rec->documentQuantity, 7);
        if($overQuantity > 0){
            $overQuantity *= -1;
            $ZoneType = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество)');
            $zonesDefault = array('zone' => array('0' => (string)$rec->zoneId), 'quantity' => array('0' => (string)$overQuantity));
            $zonesDefault = $ZoneType->fromVerbal($zonesDefault);
            
            $row->status = ht::createLink('', array('rack_Movements', 'add', 'movementType' => 'zone2floor', 'productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'ret_url' => true, 'defaultZones' => $zonesDefault), false, 'class=minusImg,ef_icon=img/16/minus-white.png,title=Връщане на нагласено количество') . $row->status;
        }
        
        if ($Definition = batch_Defs::getBatchDef($rec->productId)) {
            if(!empty($rec->batch)){
                $row->batch = $Definition->toVerbal($rec->batch);
            } else {
                $row->batch = "<span class='quiet'>" . tr('без партида') . "</span>";
            }
        } else {
            $row->batch = null;
        }
    }
    
    
    /**
     * След рендиране на детайлите се скриват ценовите данни от резултатите
     * ако потребителя няма права
     */
    protected static function on_AfterPrepareDetail($mvc, $res, &$data)
    {
        if(!countR($data->rows)) return;
        setIfNot($data->inlineDetail, false);
        setIfNot($data->masterData->rec->_isSingle, !$data->inlineDetail);
        $requestedProductId = Request::get('productId', 'int');
        
        // Допълнително обикаляне на записите
        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            $productCode = cat_Products::fetchField($rec->productId, 'code');
            $row->_code = !empty($productCode) ? $productCode : "Art{$rec->id}";
            
            $row->ROW_ATTR['class'] = 'row-added';
            $movementsHtml = self::getInlineMovements($rec, $data->masterData->rec);
            if(!empty($movementsHtml)){
                $row->movementsHtml = $movementsHtml;
            }
            
            // Ако няма движения и к-та са 0, реда се маркира
            if((empty($rec->movementQuantity) && empty($rec->documentQuantity) && empty($rec->_movements)) || (isset($requestedProductId) && $rec->productId != $requestedProductId)){
                unset($data->rows[$id]);
            }
        }
        
        arr::sortObjects($data->rows, '_code', 'asc', 'str');
    }
    
    
    /**
     * Записва движение в зоната
     *
     * @param int   $zoneId      - ид на зона
     * @param int   $productId   - ид на артикул
     * @param int   $packagingId - ид на опаковка
     * @param float $quantity    - количество в основна мярка
     * @param string $batch      - ид на опаковка
     *
     * @return void
     */
    public static function recordMovement($zoneId, $productId, $packagingId, $quantity, $batch)
    {
        $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId} AND #batch = '{$batch}'");
        if (empty($newRec)) {
            $newRec = (object) array('zoneId' => $zoneId, 'productId' => $productId, 'packagingId' => $packagingId, 'movementQuantity' => 0, 'documentQuantity' => null, 'batch' => $batch);
        }
        $newRec->movementQuantity += $quantity;
        $newRec->movementQuantity = round($newRec->movementQuantity, 4);
       
        self::save($newRec);
    }
    
    
    /**
     * Синхронизиране на зоните с документа
     *
     * @param int $zoneId
     * @param int $containerId
     */
    public static function syncWithDoc($zoneId, $containerId = null)
    {
        $notIn = array();
        if (isset($containerId)) {
            $document = doc_Containers::getDocument($containerId);
            $products = $document->getProductsSummary();
            
            if (countR($products)) {
                foreach ($products as $obj) {
                    $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId} AND #batch = '{$obj->batch}'");
                    if (empty($newRec)) {
                        $newRec = (object) array('zoneId' => $zoneId, 'productId' => $obj->productId, 'packagingId' => $obj->packagingId, 'batch' => $obj->batch, 'movementQuantity' => null, 'documentQuantity' => 0);
                    }
                    $newRec->documentQuantity = $obj->quantity;
                    if(!empty($newRec->documentQuantity)){
                        $newRec->documentQuantity = round($newRec->documentQuantity, 4);
                    }
                    
                    self::save($newRec);
                    $notIn[$newRec->id] = $newRec->id;
                }
            }
        }
        
        // Зануляват се к-та от документ освен на променените записи
        self::nullifyQuantityFromDocument($zoneId, $notIn);
    }
    
    
    /**
     * Зануляване на очакваното количество по документи
     *
     * @param int   $zoneId
     * @param array $notIn
     */
    private static function nullifyQuantityFromDocument(int $zoneId, array $notIn = array())
    {
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId}");
        $query->where('#documentQuantity IS NOT NULL');
        if (countR($notIn)) {
            $query->notIn('id', $notIn);
        }
        
        while ($rec = $query->fetch()) {
            $rec->documentQuantity = null;
            self::save($rec);
        }
    }
    
    
    /**
     * Изчислява какво количество от даден продукт е налично в зоните
     * 
     * @param int $productId
     * @param int $storeId
     * @return number $res
     */
    public static function calcProductQuantityOnZones($productId, $storeId = null)
    {
        $query = self::getQuery();
        $query->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $query->XPR('sum', 'double', 'sum(#movementQuantity)');
        $query->where("#productId = {$productId}");
        if(isset($storeId)){
            $query->where("#storeId = {$storeId}");
        }
        
        $rec = $query->fetch();
        $res =  ($rec) ? $rec->sum : 0;
        
        return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $storeId = rack_Zones::fetchField($rec->zoneId, 'storeId');
        
        // Рекалкулира какво е количеството по зони на артикула в склад-а
        rack_Products::recalcQuantityOnZones($rec->productId, $storeId);
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('documentQuantity', 'DESC');
    }
    
    
    /**
     * Рендиране на детайла накуп
     * 
     * @param stdClass $masterRec
     * @param core_Mvc $masterMvc
     * @return core_ET
     */
    public static function renderInlineDetail($masterRec, $masterMvc)
    {
        $tpl = new core_ET();

        if(empty($masterRec)){
            bp();
        }

        $me = cls::get(get_called_class());
        $dData = (object)array('masterId' => $masterRec->id, 'masterMvc' => $masterMvc, 'masterData' => (object)array('rec' => $masterRec), 'listTableHideHeaders' => true, 'inlineDetail' => true);
        //bp($masterRec);

        $dData = $me->prepareDetail($dData);
        if(!countR($dData->recs)) return $tpl;
        unset($dData->listFields['id']);
        
        $tpl = $me->renderDetail($dData);
        $tpl->removePlaces();
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Рендира таблицата със движения към детайла на зоната
     *
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    private static function getInlineMovements(&$rec, $masterRec)
    {
        $Movements = clone cls::get('rack_Movements');
        $Movements->FLD('_rowTools', 'varchar', 'tdClass=small-field');
        
        $data = (object) array('recs' => array(), 'rows' => array(), 'listTableMvc' => $Movements, 'inlineMovement' => true);
        $data->listFields = arr::make('movement=Движение,startBtn=Започни,stopBtn=Приключи,workerId=Работник', true);
        if($masterRec->_isSingle === true){
            $data->listFields['modifiedOn'] = 'Модифициране||Modified->На||On';
            $data->listFields['modifiedBy'] = 'Модифициране||Modified->От||By';
        }
        
        $Movements->setField('workerId', "tdClass=inline-workerId");
        $skipClosed = ($masterRec->_isSingle === true) ? false : true;
        $movementArr = rack_Zones::getCurrentMovementRecs($rec->zoneId, $skipClosed);
        $allocated = &rack_ZoneDetails::$allocatedMovements[$rec->zoneId];
        $allocated = is_array($allocated) ? $allocated : array();
        
        list($productId, $packagingId, $batch) = array($rec->productId, $rec->packagingId, $rec->batch);
        $data->recs = array_filter($movementArr, function($o) use($productId, $packagingId, $batch, $allocated){
            return $o->productId == $productId && $o->packagingId == $packagingId && $o->batch == $batch && !array_key_exists($o->id, $allocated);
        });
        
        $rec->_movements = $data->recs;
        if(countR($rec->_movements)){
            $allocated += $rec->_movements;
        }
        
        $requestedProductId = Request::get('productId', 'int');
        
        foreach ($data->recs as $mRec) {
            if(isset($requestedProductId) && $mRec->productId != $requestedProductId) continue;
            
            $fields = $Movements->selectFields();
            $fields['-list'] = true;
            $fields['-inline'] = true;
            if($masterRec->_isSingle === true){
                $fields['-inline-single'] = true;
            }
            $data->rows[$mRec->id] = rack_Movements::recToVerbal($mRec, $fields);
        }
       
        // Рендиране на таблицата
        $tpl = new core_ET('');
        if (countR($data->rows) || $masterRec->_isSingle === true) {
            $tableClass = ($masterRec->_isSingle === true && countR($data->rows)) ? 'listTable' : 'simpleTable';
            $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => $tableClass, 'thHide' => true));
            $Movements->invoke('BeforeRenderListTable', array($tpl, &$data));
            
            $tpl->append($table->get($data->rows, $data->listFields));
            $tpl->append("style='width:100%;'", 'TABLE_ATTR');
        }
        
        $tpl->removePendings('COMMON_ROW_ATTR');
        
        return $tpl;
    }
}
