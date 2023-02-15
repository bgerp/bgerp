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
     * Кой може да променя партидите?
     */
    public $canModifybatch = 'ceo,rack';


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
    public $listFields = 'productId, batch, status=Състояние,movementsHtml=@, packagingId, batch';
    
    
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
        $this->FLD('batch', 'varchar', 'caption=Партида,tdClass=rack-zone-batch,notNull');
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
        $isInline = Mode::get('inlineDetail');
        if(!Mode::is('printing')){
            $row->productId = $isInline ?  ht::createLinkRef(cat_Products::getTitleById($rec->productId), array('cat_Products', 'single', $rec->productId)) : cat_Products::getShortHyperlink($rec->productId, true);
        }

        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $movementQuantity = core_Math::roundNumber($rec->movementQuantity);
        $documentQuantity = core_Math::roundNumber($rec->documentQuantity);
        $movementQuantityVerbal = $mvc->getFieldType('movementQuantity')->toVerbal($movementQuantity);
        $documentQuantityVerbal = $mvc->getFieldType('documentQuantity')->toVerbal($documentQuantity);
        $moveStatusColor = (round($rec->movementQuantity, 4) < round($rec->documentQuantity, 4)) ? '#ff7a7a' : (($rec->movementQuantity == $rec->documentQuantity) ? '#ccc' : '#8484ff');
        
        $row->status = "<span style='color:{$moveStatusColor} !important'>{$movementQuantityVerbal}</span> / <b>{$documentQuantityVerbal}</b>";
   
        // Ако има повече нагласено от очакането добавя се бутон за връщане на количеството
        $overQuantity = round($rec->movementQuantity - $rec->documentQuantity, 7);
        if($overQuantity > 0){
            $overQuantity *= -1;
            $ZoneType = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество)');
            $zonesDefault = array('zone' => array('0' => (string)$rec->zoneId), 'quantity' => array('0' => (string)$overQuantity));
            $zonesDefault = $ZoneType->fromVerbal($zonesDefault);

            if(!Mode::is('printing')){
                $row->status = ht::createLink('', array('rack_Movements', 'add', 'movementType' => 'zone2floor', 'batch' => $rec->batch, 'productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'ret_url' => true, 'defaultZones' => $zonesDefault), false, 'class=minusImg,ef_icon=img/16/minus-white.png,title=Връщане на нагласено количество') . $row->status;
            }
        }
        
        if ($Definition = batch_Defs::getBatchDef($rec->productId)) {
            if(!empty($rec->batch)){
                $row->batch = $Definition->toVerbal($rec->batch);
                if(rack_ProductsByBatches::haveRightFor('list')){
                    $row->batch = ht::createLinkRef($row->batch, array('rack_ProductsByBatches', 'list', 'search' => $rec->batch));
                }
            } else {
                $row->batch = "<span class='quiet'>" . tr('без партида') . "</span>";
            }

            if($mvc->haveRightFor('modifybatch', $rec)){
                $row->batch .= ht::createLink('', array($mvc, 'modifybatch', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png,title=Промяна на партидата');
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
        if(Mode::is('printing')){
            $data->filter = 'notClosed';
        }

        // Допълнително обикаляне на записите
        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            $productCode = cat_Products::fetchField($rec->productId, 'code');
            $row->_code = !empty($productCode) ? $productCode : "Art{$rec->id}";

            $row->ROW_ATTR['class'] = 'row-added';
            $movementsHtml = self::getInlineMovements($rec, $data->masterData->rec, $data->filter);
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
        $newRec = self::fetch(array("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId} AND #batch = '[#1#]'", $batch));
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
                    $newRec = self::fetch(array("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId} AND #batch = '[#1#]'", $obj->batch));
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
    public static function calcProductQuantityOnZones($productId, $storeId = null, $batch = null)
    {
        $query = self::getQuery();
        $query->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $query->XPR('sum', 'double', 'sum(#movementQuantity)');
        $query->where("#productId = {$productId}");
        if(isset($storeId)){
            $query->where("#storeId = {$storeId}");
        }
        if(isset($batch)){
            $query->where(array("#batch = '[#1#]'", $batch));
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

        if(core_Packs::isInstalled('batch')){
            $bItemRec = rack_ProductsByBatches::fetch(array("#productId = {$rec->productId} AND #batch = '[#1#]' AND #storeId = {$storeId}", $rec->batch));
            if(is_object($bItemRec)){
                $bItemRec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($rec->productId, $storeId, $rec->batch);
                rack_ProductsByBatches::save($bItemRec, 'quantityOnZones');
            }
        }
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
     * @param string $additional
     * @return core_ET
     */
    public static function renderInlineDetail($masterRec, $masterMvc, $additional = null)
    {
        $tpl = new core_ET("");

        Mode::push('inlineDetail', true);
        $me = cls::get(get_called_class());
        $additional = !empty($additional) ? $additional : 'pendingAndMine';
        setIfNot($additional, 'pendingAndMine');
        $dData = (object)array('masterId' => $masterRec->id, 'masterMvc' => $masterMvc, 'masterData' => (object)array('rec' => $masterRec), 'listTableHideHeaders' => true, 'inlineDetail' => true, 'filter' => $additional);

        $dData = $me->prepareDetail($dData);
        if(!countR($dData->recs)) return $tpl;
        unset($dData->listFields['id']);
        
        $tpl = $me->renderDetail($dData);
        $tpl->removePlaces();
        $tpl->removeBlocks();
        Mode::pop('inlineDetail');

        return $tpl;
    }
    
    
    /**
     * Рендира таблицата със движения към детайла на зоната
     *
     * @param stdClass $rec
     * @return string filter
     */
    private static function getInlineMovements(&$rec, &$masterRec, $filter)
    {
        $Movements = clone cls::get('rack_Movements');
        $Movements->FLD('_rowTools', 'varchar', 'tdClass=small-field');

        $data = (object) array('recs' => array(), 'rows' => array(), 'listTableMvc' => $Movements, 'inlineMovement' => true);
        $data->listFields = arr::make('movement=Движение,leftColBtns,rightColBtns,workerId=Работник', true);
        if($masterRec->_isSingle === true){
            $data->listFields['modifiedOn'] = 'Модифициране||Modified->На||On';
            $data->listFields['modifiedBy'] = 'Модифициране||Modified->От||By';
        }

        if(Mode::is('printing')){
            unset($data->listFields['leftColBtns']);
            unset($data->listFields['rightColBtns']);
        }

        $Movements->setField('workerId', "tdClass=inline-workerId");
        $movementArr = rack_Zones::getCurrentMovementRecs($rec->zoneId, $filter);
        $allocated = &rack_ZoneDetails::$allocatedMovements[$rec->zoneId];
        $allocated = is_array($allocated) ? $allocated : array();
        
        list($productId, $packagingId, $batch) = array($rec->productId, $rec->packagingId, $rec->batch);
        $data->recs = array_filter($movementArr, function($o) use($productId, $packagingId, $batch, $allocated){
            return $o->productId == $productId && $o->packagingId == $packagingId && $o->batch == $batch && !array_key_exists($o->id, $allocated);
        });

        if(countR($data->recs)){
            $masterRec->_noMovements = true;
        }

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
            $mRec->_currentZoneId = $masterRec->id;
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


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'modifybatch' && isset($rec)){
            $zoneRec = rack_Zones::fetch($rec->zoneId);
            if(empty($zoneRec->containerId)){
                $requiredRoles = 'no_one';
            } else {
                $Document = doc_Containers::getDocument($zoneRec->containerId);
                if(!$Document->haveRightFor('edit')){
                    $requiredRoles = 'no_one';
                } else {
                    $containerId = $Document->fetchField('containerId');
                    $batchRec = batch_BatchesInDocuments::fetch(array("#containerId = {$containerId} AND #productId = {$rec->productId} AND #batch = '[#1#]' AND #storeId = {$zoneRec->storeId}", $rec->batch));
                    if(!is_object($batchRec)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }


    /**
     * Екшън за промяна на редовете от документа
     *
     * @return Redirect
     * @throws core_exception_Expect
     */
    public function act_modifybatch()
    {
        $this->requireRightFor('modifybatch');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $rId = Request::get('rId', 'int');
        $this->requireRightFor('modifybatch', $rec);
        $zoneRec = rack_Zones::fetch($rec->zoneId);
        $Document = doc_Containers::getDocument($zoneRec->containerId);
        $retUrl = getRetUrl();

        // Кои са записите от документа отговарящи на посочените артикул+партида
        $order = $dRecs = array();
        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->where("#containerId = {$zoneRec->containerId}");
        $bQuery->where(array("#productId = {$rec->productId} AND #storeId = {$zoneRec->storeId} AND #batch = '[#1#]'", $rec->batch));
        $bQuery->orderBy('id', 'ASC');
        while ($bRec = $bQuery->fetch()) {
            $dRecs[$bRec->detailRecId] = array('detailClassId' => $bRec->detailClassId, 'detailRecId' => $bRec->detailRecId, 'quantity' => $bRec->quantity, 'quantityInPack' => $bRec->quantityInPack, 'packagingId' => $bRec->packagingId);
            $order[] = $bRec->detailRecId;
        }

        // Кои са предходните и следващата
        $rId = isset($rId) ? $rId : key($dRecs);
        $index = array_search($rId, $order);
        $nextNum = $index + 1;
        $prevNum = $index - 1;
        $dRec = $dRecs[$rId];
        $recInfo = cls::get($dRec['detailClassId'])->getRowInfo($dRec['detailRecId']);

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Промяната на партидите в|* ' . $Document->getFormTitleLink();
        $Def = batch_Defs::getBatchDef($rec->productId);
        Mode::push('text', 'plain');
        $batch = $Def->toVerbal($rec->batch);
        Mode::pop('text');
        $batchCaption = str_replace(',', ' ', $batch);
        $key = md5($rec->batch);

        // Добавяне на партидата от изходния ред
        $map = array($key => $rec->batch);
        $measureName = cat_UoM::getShortName($dRec['packagingId']);
        $pCaption = cat_Products::getTitleById($rec->productId);
        $pCaption = "{$pCaption} / {$measureName}";

        $form->FLD($key, "double(min=0)", "caption={$pCaption}->{$batchCaption}");
        $form->setDefault($key, $dRec['quantity'] / $dRec['quantityInPack']);

        $round = cat_UoM::fetchField($dRec['packagingId'], 'round');
        $Double = core_Type::getByName("double(decimals={$round})");

        $quantityPack = $recInfo->quantity / $recInfo->quantityInPack;
        $form->info = tr("Общо на реда|*: <b>") . $Double->toVerbal($quantityPack) . "</b> " . str::getPlural($quantityPack, $measureName, true);

        // Показване на съществуващите налични партиди в склада
        $exBatchArr = batch_Items::getBatchQuantitiesInStore($rec->productId, $zoneRec->storeId, null, null, null, true);
        unset($exBatchArr[$rec->batch]);
        foreach ($exBatchArr as $exBatch => $exQuantity) {
            if($exQuantity <= 0) continue;
            $key = md5($exBatch);
            $map[$key] = $exBatch;

            Mode::push('text', 'plain');
            $batchCaption = $Def->toVerbal($exBatch);
            $batchCaption = str_replace(',', ' ', $batchCaption);
            Mode::pop('text');

            $form->FLD($key, "double(min=0)", "caption=Други партиди в склада->{$batchCaption}");
            Mode::push('text', 'plain');
            $info = "|* / " . $Double->toVerbal($exQuantity / $recInfo->quantityInPack) . " " . str::getPlural($exQuantity, $measureName, true);
            $form->setField($key, "unit={$info}");
            Mode::pop('text');
        }
        $form->input();

        if($form->isSubmitted()){
            $syncArr = array();
            $newArr = (array)$form->rec;
            $msg = "Редът от документа е редактиран успешно|*!";
            $noChange = true;
            $deleteId = null;

            // За всяка инпутната партида
            foreach ($newArr as $k => $v){
                $batch = $map[$k];

                // Ако има съществуващ запис
                $exRec = batch_BatchesInDocuments::fetch(array("#detailClassId = {$dRec['detailClassId']} AND #detailRecId = {$dRec['detailRecId']} AND #batch = '[#1#]'", $batch));
                if($exRec){
                    if($batch == $rec->batch){
                        if(empty($v)){

                            // Ако на изходната партида е посочено празно количество - ще се изтрива
                            $noChange = false;
                            $deleteId = $exRec->id;
                        } else {

                            // Ако изходната партида е променена - ще се обновява
                            $newQuantity = $v * $exRec->quantityInPack;
                            if(round($newQuantity, $round) != round($exRec->quantity, $round)){
                                $syncArr[$batch] = $newQuantity;
                            }
                        }
                    } else {

                        // Ако е посочена друга партида, к-то се добавя към вече съществуваното от нея
                        if(!empty($v)){
                            $syncArr[$batch] = $exRec->quantity + $v * $exRec->quantityInPack;
                        }
                    }
                } elseif(!empty($v)){
                    // Ако е изцяло нова партида ще се добавя
                    $syncArr[$batch] = $v * $dRec['quantityInPack'];
                }
            }

            $sum = array_sum($syncArr);
            if(round($sum, $round) > round($recInfo->quantity, $round)){
                $fieldsError = array();
                $mapReverse = array_flip($map);
                array_walk($syncArr, function ($a, $k) use (&$fieldsError, $mapReverse){$fieldsError[] = $mapReverse[$k];});
                $form->setError(implode(',', $fieldsError), 'Общото количество е над допустимото за реда|*!');
            }

            if(!$form->gotErrors()){
                // Ако има партиди за добавяне/обновяване
                if(countR($syncArr)){
                    $noChange = false;
                    batch_BatchesInDocuments::saveBatches($dRec['detailClassId'], $dRec['detailRecId'], $syncArr);
                }
                if(isset($deleteId)){
                    batch_BatchesInDocuments::delete($deleteId);
                }

                if($form->cmd == 'save_n_prev'){
                    $redirectNum =  $order[$prevNum];
                } elseif($form->cmd == 'save_n_next'){
                    $redirectNum =  $order[$nextNum];
                }

                if($noChange){
                    $msg = "Редът не е променен, защото няма промяна|*!";
                } else {
                    rack_Zones::forceSync($zoneRec->containerId, $zoneRec);
                }

                // Ако формата е събмитната от бутоните за следващ/предходен редирект към следващия/предходния
                if(isset($redirectNum)){
                    return new redirect(array($this, 'modifybatch', 'id' => $id, 'rId' => $redirectNum, 'ret_url' => $retUrl), $msg);
                }

                followRetUrl(null, $msg);
            }
        }

        $batchCount = countR($dRecs);

        // Бутони за напред/назад
        if($batchCount > 1){
            if (isset($order[$nextNum])) {
                $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=noicon fright,order=30, title = Следващ');
            } else {
                $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=btn-disabled noicon fright,disabled,order=30, title = Следващ');
            }
            $prevAndNextIndicator = ($index + 1) . "/{$batchCount}";
            $form->toolbar->addFnBtn($prevAndNextIndicator, '', 'class=noicon fright,order=30');
            if (isset($order[$prevNum])) {
                $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=noicon fright,order=30, title = Предишен');
            } else {
                $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=btn-disabled noicon fright,disabled,order=30, title = Предишен');
            }
        }

        $form->toolbar->addSbBtn('Промяна', 'save', 'id=btnSave,ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, 'id=back,ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        // Рендиране на формата
        return $tpl;
    }
}
