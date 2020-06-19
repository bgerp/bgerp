<?php


/**
 * Движения в палетния склад
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
class rack_Movements extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Движения';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Движение';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper, plg_SaveAndNew, plg_State, plg_Sorting,plg_SelectPeriod,plg_Search,plg_AlignDecimals2,plg_Modified';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,rack';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,rack';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,rack';
    
    
    /**
     * Кой може да приключи движение
     */
    public $canDone = 'ceo,rack';
    
    
    /**
     * Кой може да заяви движение
     */
    public $canToggle = 'ceo,rack';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'productId,movement=Движение,startBtn=Започни,stopBtn=Приключи,workerId=Изпълнител,createdOn,createdBy,modifiedOn,modifiedBy,documents';
    
    
    /**
     * Полета по които да се търси
     */
    public $searchFields = 'palletId,position,positionTo,note';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,rack';
    
    
    /**
     * Кой има право да изтрива системните данни?
     */
    public $canDeletesysdata = 'ceo,rack';
    

    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";
    

    /**
     * Колко време след като са приключени движенията да се изтриват
     */
    const DELETE_CLOSED_MOVEMENTS_OLDER_THEN = 5184000;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,column=none');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts)', 'tdClass=productCell,caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|zones|palletId,mandatory,remember');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack,silent');
        $this->FNC('packQuantity', 'double(min=0)', 'caption=Количество,smartCenter,silent');
        $this->FNC('movementType', 'varchar', 'silent,input=hidden');
        
        // Палет, позиции и зони
        $this->FLD('palletId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->От,input=hidden,silent,placeholder=Под||Floor,removeAndRefreshForm=position|positionTo,smartCenter');
        $this->FLD('batch', 'text', 'silent,input=none,before=positionTo,removeAndRefreshForm');
        $this->FLD('position', 'rack_PositionType', 'caption=Движение->От,input=none');
        $this->FLD('positionTo', 'rack_PositionType', 'caption=Движение->Към,input=none');
        $this->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=10em|10em,validate=rack_Movements::validateZonesTable)', 'caption=Движение->Зони,smartCenter,input=hidden');
        
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=hidden');
        $this->FLD('state', 'enum(closed=Приключено, active=Активно, pending=Чакащо)', 'caption=Състояние,silent');
        $this->FLD('workerId', 'user(roles=ceo|rack)', 'caption=Движение->Товарач,tdClass=nowrap,input=none');
        
        $this->FLD('note', 'varchar(64)', 'caption=Движение->Забележка,column=none');
        $this->FLD('zoneList', 'keylist(mvc=rack_Zones, select=num)', 'caption=Зони,input=none');
        $this->FLD('fromIncomingDocument', 'enum(no,yes)', 'input=hidden,silent,notNull,value=no');
        $this->FNC('containerId', 'int', 'input=hidden,caption=Документи,silent');
        $this->FLD('documents', 'keylist(mvc=doc_Containers,select=id)', 'input=none,caption=Документи');
        
        $this->setDbIndex('storeId');
        $this->setDbIndex('palletId');
        $this->setDbIndex('productId,storeId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if(isset($rec->palletId)){
                $rec->position = rack_Pallets::fetchField($rec->palletId, 'position');
            } else {
                $rec->position = rack_PositionType::FLOOR;
                $rec->palletId = null;
            }
            
            $quantityInZones = 0;
            self::getZoneArr($rec, $quantityInZones);
            if (empty($rec->packQuantity) && empty($rec->defaultPackQuantity) && $quantityInZones >= 0) {
                $form->setError('packQuantity', 'Въведете количество');
            }
            
            if (!empty($rec->packQuantity)) {
                $warning = null;
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning, 'uom')) {
                    $form->setWarning('packQuantity', $warning);
                }
            }
            
            if (!$form->gotErrors()) {
                if (empty($rec->positionTo)) {
                    $rec->positionTo = $rec->position;
                }
                
                // Симулиране дали транзакцията е валидна
                $clone = clone $rec;
                $clone->packQuantity = isset($rec->packQuantity) ? $rec->packQuantity : $rec->defaultPackQuantity;
                
                $clone->quantity = $clone->quantityInPack * $clone->packQuantity;
                $transaction = $mvc->getTransaction($clone);
                $transaction = $mvc->validateTransaction($transaction);
               
                if($rec->state == 'pending' && $rec->fromIncomingDocument == 'yes'){
                    
                    $transaction->warningFields = array_merge($transaction->errorFields, $transaction->warningFields);
                    if (!empty($transaction->errors)) {
                        $transaction->warnings[] = $transaction->errors;
                    }
                    
                    unset($transaction->errors);
                    unset($transaction->errorFields);
                }
                
                if (!empty($transaction->errors)) {
                    $form->setError($transaction->errorFields, $transaction->errors);
                }
                
                if (!empty($transaction->warnings)) {
                    $form->setWarning($transaction->warningFields, implode(',', $transaction->warnings));
                }
                
                if (!$form->gotErrors()) {
                    $rec->packQuantity = isset($rec->packQuantity) ? $rec->packQuantity : $rec->defaultPackQuantity;
                    $rec->quantity = $rec->quantityInPack * $rec->packQuantity;
                    
                    if ($rec->state == 'closed') {
                        $rec->_isCreatedClosed = true;
                    }
                    
                    if(!empty($rec->containerId)){
                        $rec->documents = keylist::addKey($rec->documents, $rec->containerId);
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // Кеш на засегнатите зони за бързодействие
        $zonesArr = arr::extractValuesFromArray($mvc->getZoneArr($rec), 'zone');
        $rec->zoneList = (countR($zonesArr)) ? keylist::fromArray($zonesArr) : null;
        
        if ($rec->state == 'active' || $rec->_canceled === true || $rec->_isCreatedClosed === true) {
            if (empty($rec->workerId)) {
                $rec->workerId = core_Users::getCurrent('id', false);
            }
            
            // Изпълнение на транзакцията
            $reverse = ($rec->_canceled === true) ? true : false;
            $transaction = $mvc->getTransaction($rec, $reverse);
            $result = $mvc->doTransaction($transaction);
            
            // Ако има проблем при изпълнението записа се спира
            if ($result !== true) {
                core_Statuses::newStatus('Проблем при записа на движението');
                
                return false;
            }
        }
        
        if ($rec->state == 'active' || $rec->_isCreatedClosed === true){
            if(is_array($zonesArr)){
                $documents = array();
                foreach ($zonesArr as $zoneId){
                    $zoneContainerId = rack_Zones::fetchField($zoneId, 'containerId');
                    $documents[$zoneContainerId] = $zoneContainerId;
                }
                
                $documents = (countR($documents)) ? keylist::fromArray($documents) : null;
                $rec->documents = keylist::merge($rec->documents, $documents);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ако се създава запис в чернова със зони, в зоните се създава празен запис
        if($rec->state == 'pending' && $rec->_canceled !== true){
            $batch = $rec->batch;
            if(empty($batch) && isset($rec->palletId)){
                $palletBatch = rack_Pallets::fetchField($rec->palletId, 'batch');
                if(!empty($palletBatch)){
                    $batch = $palletBatch;
                }
            }
            
            $batch = empty($batch) ? '' : $batch;
            $zonesQuantityArr = self::getZoneArr($rec);
            foreach ($zonesQuantityArr as $zoneRec){
                rack_ZoneDetails::recordMovement($zoneRec->zone, $rec->productId, $rec->packagingId, 0, $batch);
            }
        }
    }
    
    
    /**
     * Изпълнява посоченото движение
     */
    private function doTransaction($transaction)
    {
        $rMvc = cls::get('rack_Racks');
        
        // Ако има начална позиция и тя не е пода обновява се палета на нея
        if (!empty($transaction->from) && $transaction->from != rack_PositionType::FLOOR) {
            try {
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->from, -1 * $transaction->quantity, $transaction->batch);
            } catch (core_exception_Expect $e) {
                reportException($e);
                
                return false;
            }
            $rMvc->updateRacks[$transaction->storeId . '-' . $transaction->from] = true;
        }
        
        // Ако има крайна позиция и тя не е пода обновява се палета на нея
        if (!empty($transaction->to) && $transaction->to != rack_PositionType::FLOOR) {
            try {
                $restQuantity = $transaction->quantity - $transaction->zonesQuantityTotal;
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->to, $restQuantity, $transaction->batch);
            } catch (core_exception_Expect $e) {
                reportException($e);
                
                // Ако има проблем ревърт на предното движение
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->from, $transaction->quantity, $transaction->batch);
                
                return false;
            }
            
            $rMvc->updateRacks[$transaction->storeId . '-' . $transaction->to] = true;
        }
        
        if (is_array($transaction->zonesQuantityArr)) {
            foreach ($transaction->zonesQuantityArr as $obj) {
                $batch = empty($transaction->batch) ? '' : $transaction->batch;
                rack_ZoneDetails::recordMovement($obj->zone, $transaction->productId, $transaction->packagingId, $obj->quantity, $batch);
            }
        }
        
        core_Cache::remove('UsedRacksPossitions', $transaction->storeId);
        
        return true;
    }
    
    
    /**
     * Помощна ф-я обръщаща зоните в подходящ вид и събира общото количество по тях
     *
     * @param stdClass $rec
     * @param float    $quantityInZones
     *
     * @return array $zoneArr
     */
    private function getZoneArr($rec, &$quantityInZones = null)
    {
        $quantityInZones = 0;
        $zoneArr = array();
        if (isset($rec->zones)) {
            $zoneArr = type_Table::toArray($rec->zones);
            if (countR($zoneArr)) {
                foreach ($zoneArr as &$obj) {
                    $obj->quantity = core_Type::getByName('double')->fromVerbal($obj->quantity);
                    $quantityInZones += $obj->quantity;
                }
            }
        }
        
        return $zoneArr;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = &$form->rec;
        
        $form->setDefault('storeId', store_Stores::getCurrent());
        $form->setDefault('fromIncomingDocument', 'no');
        $form->setField('storeId', 'input=hidden');
        $form->setField('workerId', 'input=none');
        
        $defZones = Request::get('defaultZones', 'varchar');
        if($rec->fromIncomingDocument == 'yes'){
            $form->setReadOnly('productId');
        }
        
        if (isset($rec->productId)) {
            $form->setField('packagingId', 'input');
            
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            $form->setField('palletId', 'input');
            $form->setField('positionTo', 'input');
            $form->setField('packQuantity', 'input');
            
            $zones = rack_Zones::getZones($rec->storeId);
            if (countR($zones)) {
                $form->setFieldTypeParams('zones', array('zone_opt' => array('' => '') + $zones, 'packagingId' => $rec->packagingId));
                $form->setField('zones', 'input');
                if(!empty($defZones)){
                    $form->setDefault('zones', $defZones);
                }
            } else {
                $form->setField('zones', 'input=none');
            }
            
            // Възможния избор на палети от склада
            $pallets = rack_Pallets::getPalletOptions($rec->productId, $rec->storeId);
            $form->setOptions('palletId', array('' => tr('Под||Floor')) + $pallets);
            
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            
            // Показване на допустимото количество
            $availableQuantity = rack_Pallets::getAvailableQuantity($rec->palletId, $rec->productId, $rec->storeId, $rec->batch);
            
            if (empty($rec->palletId)) {
                if ($defQuantity = rack_Pallets::getDefaultQuantity($rec->productId, $rec->storeId)) {
                    $availableQuantity = min($availableQuantity, $defQuantity);
                }
                
                $BatchClass = batch_Defs::getBatchDef($rec->productId);
                if ($BatchClass) {
                    $form->setField('batch', 'input,placeholder=Без партида');
                    $batches = batch_Items::getBatches($rec->productId, $rec->storeId, true);
                    if(!empty($rec->batch) && !array_key_exists($rec->batch, $batches)){
                        $batches[$rec->batch] = $rec->batch;
                    }
                    
                    // Ако е фиксиран артикула, фиксира се и партидата
                    if($rec->fromIncomingDocument){
                        if(Request::get('batch', 'varchar')){
                            $form->setReadOnly('batch');
                        }
                    }
                    
                    $form->setOptions('batch', array('' => '') + $batches);
                    
                    $fieldCaption = $BatchClass->getFieldCaption();
                    if (!empty($fieldCaption)) {
                        $form->setField('batch', "caption=Движение->{$fieldCaption}");
                    }
                }
            } else {
                $form->setField('batch', 'input=none');
            }
            
            if ($availableQuantity > 0) {
                $availableQuantity /= $rec->quantityInPack;
                $form->setField('packQuantity', "placeholder={$availableQuantity}");
                $form->rec->defaultPackQuantity = $availableQuantity;
            }
            
            // На коя позиция е палета?
            if (isset($rec->palletId)) {
                $form->setField('positionTo', 'placeholder=Остава');
            } else {
                $form->setField('positionTo', 'placeholder=Остава');
            }
            
            // Добавяне на предложения за нова позиция
            if ($bestPos = rack_Pallets::getBestPos($rec->productId, $rec->storeId)) {
                $form->setSuggestions('positionTo', array('' => '', tr('Под') => tr('Под'), $bestPos => $bestPos));
                if ($form->rec->positionTo == rack_PositionType::FLOOR) {
                    $form->rec->positionTo = tr('Под');
                }
            }
        } else {
            $form->setField('packagingId', 'input=none');
        }
        
        // Състоянието е последното избрано от текущия потребител
        $lQuery = self::getQuery();
        $lQuery->where('#createdBy = ' . core_Users::getCurrent());
        $lQuery->orderBy('id', 'DESC');
        if ($lastState = $lQuery->fetch()->state) {
            $form->setDefault('state', $lastState);
        }
        
        // Замаскиране на формата според избрания тип движение
        if ($movementType = Request::get('movementType')) {
            switch ($movementType) {
                case 'floor2rack':
                    $form->setField('zones', 'input=none');
                    $form->setField('palletId', 'input=none');
                    if (isset($bestPos)) {
                        $form->setDefault('positionTo', $bestPos);
                    }
                    break;
                case 'rack2floor':
                    $form->setField('zones', 'input=none');
                    $form->setReadOnly('palletId');
                    $form->setReadOnly('productId');
                    $form->setField('positionTo', 'input=hidden');
                    $form->setField('palletId', 'caption=Сваляне на пода->Палет');
                    $form->setField('note', 'caption=Сваляне на пода->Забележка');
                    $form->setDefault('positionTo', rack_PositionType::FLOOR);
                    break;
                case 'rack2rack':
                    $form->setField('zones', 'input=none');
                    $form->setReadOnly('productId');
                    $form->setReadOnly('palletId');
                    $form->setField('palletId', 'caption=Преместване на нова позиция->Палет');
                    $form->setField('positionTo', 'caption=Преместване на нова позиция->Позиция');
                    $form->setField('note', 'caption=Преместване на нова позиция->Забележка');
                    
                    if (isset($bestPos)) {
                        $form->setDefault('positionTo', $bestPos);
                    }
                    break;
            }
        }
    }
    
    
    /**
     * Проверка на таблицата със зоните
     *
     * @param mixed     $tableData
     * @param core_Type $Type
     *
     * @return array $res
     */
    public static function validateZonesTable($tableData, $Type)
    {
        $tableData = (array) $tableData;
        if (empty($tableData)) {
            
            return;
        }
        
        $res = $zones = $error = $errorFields = array();
        $packagingId = $Type->params['packagingId'];
        
        foreach ($tableData['zone'] as $key => $zone) {
            if (!empty($zone) && empty($tableData['quantity'][$key])) {
                $error[] = 'Липсва количество при избрана зона';
                $errorFields['quantity'][$key] = 'Липсва количество при избрана зона';
            }
            
            if (array_key_exists($zone, $zones)) {
                $error[] = 'Повтаряща се зона';
                $errorFields['zone'][$key] = 'Повтаряща се зона';
            } else {
                $zones[$zone] = $zone;
            }
        }
        
        foreach ($tableData['quantity'] as $key => $quantity) {
            if (!empty($quantity) && empty($tableData['zone'][$key])) {
                $error[] = 'Зададено количество без зона';
                $errorFields['zone'][$key] = 'Зададено количество без зона';
            }
            
            if (empty($quantity)) {
                $error[] = 'Количеството не може да е 0';
                $errorFields['quantity'][$key] = 'Количеството не може да е 0';
            }
            
            $Double = core_Type::getByName('double');
            $q2 = $Double->fromVerbal($quantity);
            if (!$q2) {
                $error[] = 'Невалидно количество';
                $errorFields['quantity'][$key] = 'Невалидно количество';
            } else {
                $warning = null;
                if(!deals_Helper::checkQuantity($packagingId, $q2, $warning, 'uom')){
                    $error[] = $warning;
                    $errorFields['quantity'][$key] = $warning;
                }
            }
        }
        
        if (countR($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }
        
        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }
        
        return $res;
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        
        switch ($rec->movementType) {
            case 'floor2rack':
                $title = core_Detail::getEditTitle('store_Stores', $rec->storeId, 'нов палет', $rec->id, tr('в'));
                break;
            case 'rack2floor':
                $title = 'Сваляне на палет на пода в склад|* ' . cls::get('store_Stores')->getFormTitleLink($rec->storeId);
                break;
            default:
                $title = core_Detail::getEditTitle('store_Stores', $rec->storeId, $mvc->singleTitle, $rec->id, tr('в'));
                break;
        }
        
        $data->form->title = $title;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        core_RowToolbar::createIfNotExists($row->_rowTools);
        
        if ($mvc->haveRightFor('start', $rec)) {
            $startUrl = array($mvc, 'toggle', $rec->id, 'type' => 'start', 'ret_url' => true);
            $row->_rowTools->addLink('Започване', $startUrl, "id=start{$rec->id},ef_icon=img/16/control_play.png,title=Започване на движението");
            
            if ($rec->createdBy != core_Users::getCurrent()) {
                $row->_rowTools->setWarning("start{$rec->id}", 'Сигурни ли сте, че искате да започнете движение от друг потребител');
            }
            
            if($fields['-inline'] && !isset($fields['-inline-single'])){
                $startUrl = toUrl($startUrl, 'local');
                $row->startBtn = ht::createFnBtn('Започване', '', null, array('class' => 'toggle-movement', 'data-url' => $startUrl, 'title' => 'Започване на движението', 'ef_icon' => 'img/16/control_play.png'));
            } else {
                $img = ht::createImg(array('src' => sbf('img/16/control_play.png', '')));
                $row->startBtn = ht::createLink($img, $startUrl, false, 'title=Започване на движението');
            }
        }
        
        if ($mvc->haveRightFor('done', $rec)) {
            $stopUrl = array($mvc, 'done', $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Приключване', array($mvc, 'done', $rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Приключване на движението');
            
            if($fields['-inline'] && !isset($fields['-inline-single'])){
                $stopUrl = toUrl($stopUrl, 'local');
                $row->stopBtn = ht::createFnBtn('Приключване', '', null, array('class' => 'toggle-movement', 'data-url' => $stopUrl, 'title' => 'Започване на движението', 'ef_icon' => 'img/16/gray-close.png'));
            } else {
                $img = ht::createImg(array('src' => sbf('img/16/gray-close.png', '')));
                $row->stopBtn = ht::createLink($img, $stopUrl, false, 'title=Приключване на движението');
            }
        }
        
        if ($mvc->haveRightFor('reject', $rec)) {
            $row->_rowTools->addLink('Отказване', array($mvc, 'toggle', $rec->id, 'type' => 'reject', 'ret_url' => true), 'warning=Наистина ли искате да откажете движението|*?,ef_icon=img/16/reject.png,title=Отказване на движението');
        }
        
        if (!empty($rec->note)) {
            $row->note = "<div style='font-size:0.8em;'>{$row->note}</div>";
        }
        
        $row->productId = cat_Products::getShortHyperlink($rec->productId, true);
        if (!empty($rec->note)) {
            $notes = $mvc->getFieldType('note')->toVerbal($rec->note);
            $row->productId .= "<br><span class='small'>{$notes}</span>";
        }
        
        $row->_rowTools->addLink('Палети', array('rack_Pallets', 'productId' => $rec->productId), "id=search{$rec->id},ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт");
        $row->movement = $mvc->getMovementDescription($rec);
        
        if(!empty($rec->documents)){
            $documents = array();
            $arr = keylist::toArray($rec->documents);
            foreach ($arr as $containerId){
                $documents[$containerId] = doc_Containers::getDocument($containerId)->getLink(0);
            }
            $row->documents = implode(',', $documents);
        }
    }
    
    
    /**
     * Подробно описание на движението
     *
     * @param stdClass $rec
     *
     * @return string $res
     */
    private function getMovementDescription($rec, $skipZones = false)
    {
        $packQuantity = isset($rec->_originalPackQuantity) ? $rec->_originalPackQuantity : $rec->packQuantity;
        $position = $this->getFieldType('position')->toVerbal($rec->position);
        $positionTo = $this->getFieldType('positionTo')->toVerbal($rec->positionTo);
        
        $Double = core_Type::getByName('double(smartRound)');
        $packagingRow = cat_UoM::getShortName($rec->packagingId);
        $packQuantityRow = $Double->toVerbal($packQuantity);
        
        $class = '';
        if ($palletId = cat_UoM::fetchBySinonim('pallet')->id) {
            if ($palletRec = cat_products_Packagings::getPack($rec->productId, $palletId)) {
                if ($rec->quantity == $palletRec->quantity) {
                    $class = "class = 'quiet'";
                }
            }
        }
        
        $movementArr = array();
        $packType = cat_UoM::fetchField($rec->packagingId, 'type');
        if ($packType != 'uom') {
            $packagingRow = str::getPlural($packQuantity, $packagingRow, true);
        }
        if (!empty($packQuantity)) {
            $packQuantityRow = ht::styleIfNegative($packQuantityRow, $packQuantity);
            
            $movementArr[] = "{$position} (<span {$class}>{$packQuantityRow}</span> {$packagingRow})";
        }
        
        if ($skipZones === false) {
            $quantityInZones = array();
            $zones = self::getZoneArr($rec, $quantityInZones);
            $restQuantity = round($packQuantity, 6) - round($quantityInZones, 6);
            
            Mode::push('shortZoneName', true);
            foreach ($zones as $zoneRec) {
                $class = ($rec->state == 'active') ? "class='movement-position-notice'" : "";
               
                if(rack_Zones::fetchField($zoneRec->zone)){
                    $zoneTitle = rack_Zones::getRecTitle($zoneRec->zone);
                    $zoneTitle = ht::createLink($zoneTitle, rack_Zones::getUrlArr($zoneRec->zone));
                } else {
                    $zoneTitle = ht::createHint($zoneRec->zone, 'Зоната вече не съществува', 'warning');
                }
                
                $zoneQuantity = $Double->toVerbal($zoneRec->quantity);
                $zoneQuantity = ht::styleIfNegative($zoneQuantity, $zoneRec->quantity);
                $movementArr[] = "<span {$class}>{$zoneTitle} ({$zoneQuantity})</span>";
                
                
            }
            Mode::pop('shortZoneName');
        }
        
        if (!empty($positionTo) && $restQuantity) {
            $resQuantity = $Double->toVerbal($restQuantity);
            $movementArr[] = "{$positionTo} ({$resQuantity})";
        }
      
        if($rec->state == 'pending' && isset($movementArr[0])){
            $movementArr[0] = "<span class='movement-position-notice'>{$movementArr[0]}</span>";
        }
        
        $res = implode(' » ', $movementArr);
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('start', 'reject'))) {
            $requiredRoles = $mvc->getRequiredRoles('toggle', $rec, $userId);
        }
        
        if($action == 'start' && isset($rec->state)){
            if($rec->state != 'pending'){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'reject' && isset($rec->state)){
            if($rec->state != 'active'){
                $requiredRoles = 'no_one';
            } elseif($rec->state == 'active' && isset($rec->workerId) && $rec->workerId != $userId){
                $requiredRoles = 'ceo,rackMaster';
            }
        }
        
        if ($action == 'done' && $rec && $rec->state) {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            } elseif ($rec->workerId != $userId) {
                $requiredRoles = 'ceo,rackMaster';
            }
        }
        
        if ($action == 'edit' && isset($rec->state)) {
            $oldState = $mvc->fetchField($rec->id, 'state');
            if($oldState != 'pending'){
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'delete' && isset($rec->state) && $rec->state != 'pending') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->title = 'Движения на палети в склад |*<b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        $data->query->where("#storeId = {$storeId}");
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 ELSE 3 END)");
        if ($palletId = Request::get('palletId', 'int')) {
            $data->query->where("#palletId = {$palletId}");
        }
        
        $data->listFilter->setFieldTypeParams('workerId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->setField('fromIncomingDocument', 'input=none');
        $data->listFilter->FLD('from', 'date');
        $data->listFilter->FLD('to', 'date');
        $data->listFilter->FNC('documentHnd', 'varchar', 'placeholder=Документ,caption=Документ,input,silent,recently');
        $data->listFilter->FLD('state1', 'enum(,pending=Чакащи,active=Активни,closed=Приключени)', 'placeholder=Всички');
        
        $data->listFilter->showFields = 'selectPeriod,workerId,search,documentHnd,state1';
        $data->listFilter->input();
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($filterRec = $data->listFilter->rec) {
            if (in_array($filterRec->state1, array('active', 'closed', 'pending'))) {
                $data->query->where("#state = '{$filterRec->state1}'");
            }
            
            if(!empty($filterRec->from)){
                $data->query->where("#createdOn >= '{$filterRec->from} 00:00'");
            }
            
            if(!empty($filterRec->to)){
                $data->query->where("#createdOn <= '{$filterRec->to} 59:59'");
            }
            
            if(!empty($filterRec->workerId)){
                $data->query->where("#workerId = '{$filterRec->workerId}'");
            }
            
            if(!empty($filterRec->documentHnd)){
                if($foundDocument = doc_Containers::getDocumentByHandle($filterRec->documentHnd)){
                    $data->query->where("LOCATE('|{$foundDocument->fetchField('containerId')}|', #documents)");
                }
            }
        }
        
        $data->query->orderBy('orderByState=ASC,createdOn=DESC');
    }
    
    
    /**
     * Екшън за започване на движението
     */
    public function act_Toggle()
    {
        $ajaxMode = Request::get('ajax_mode');
        $type = Request::get('type', 'varchar');
        $action = ($type == 'start') ? 'start' : 'reject';
        
        if($ajaxMode){
            if(!$this->haveRightFor($action)){
                core_Statuses::newStatus('|Нямате права|*!', 'error');
                return status_Messages::returnStatusesArray();
            }
        } else {
            $this->requireRightFor($action);
        }
       
        $id = Request::get('id', 'int');
        $rec = $this->fetch($id);
        
        // Заключване на екшъна
        if (!core_Locks::get("movement{$rec->id}", 120, 0)) {
            core_Statuses::newStatus('Друг потребител работи по движението|*!', 'warning');
            if($ajaxMode){
                return status_Messages::returnStatusesArray();
            }
            followretUrl(array($this));
        }
        
        if($ajaxMode){
            if(empty($rec)){
                core_Statuses::newStatus('|Записът вече е изтрит|*!', 'error');
                core_Locks::release("movement{$rec->id}");
                
                return status_Messages::returnStatusesArray();
            } elseif(!in_array($action, array('start', 'reject'))){
                core_Locks::release("movement{$rec->id}");
                core_Statuses::newStatus('|Невалидна операция|*!', 'error');
                
                return status_Messages::returnStatusesArray();
            } elseif(!$this->haveRightFor($action, $rec)){
                core_Locks::release("movement{$rec->id}");
                core_Statuses::newStatus('|Нямате права|*!', 'error');
                
                return status_Messages::returnStatusesArray();
            }
        } else {
            expect($rec);
            core_Locks::release("movement{$rec->id}");
            $this->requireRightFor($action, $rec);
        }
        
        if($action == 'start'){
            $rec->state = 'active';
            $reverse = false;
        } else {
            $rec->state = 'pending';
            $rec->workerId = null;
            $rec->_canceled = true;
            $reverse = true;
        }
        
        // Проверка може ли транзакцията да мине
        $transaction = $this->getTransaction($rec, $reverse);
        $transaction = $this->validateTransaction($transaction);
        
        if (!empty($transaction->errors)) {
            core_Locks::release("movement{$rec->id}");
            if($ajaxMode){
                core_Statuses::newStatus($transaction->errors, 'error');
                return status_Messages::returnStatusesArray();
            } else {
                followretUrl(null, $transaction->errors, 'error');
            }
        }
        
        // Записва се служителя и се обновява движението
        $rec->workerId = core_Users::getCurrent();
        $this->save($rec, 'state,workerId,modifiedOn,modifiedBy,documents');
        
        core_Locks::release("movement{$rec->id}");
        
        $msg = (countR($transaction->warnings)) ? implode(', ', $transaction->warnings) : null;
        $type = (countR($transaction->warnings)) ? 'warning' : 'notice';
        
        // Ако се обновява по Ajax
        if($ajaxMode){
            
            return self::forwardRefreshUrl();
        }
        
        followretUrl(null, $msg, $type);
    }
    
    
    /**
     * Форуърд на рефрешването на урл-то
     * 
     * @return array $res
     */
    private static function forwardRefreshUrl()
    {
        $refreshUrl = array('Ctr' => 'rack_Zones', 'Act' => 'default');
        $refreshUrlLocal = toUrl($refreshUrl, 'local');
        $divId = Request::get('divId');
        
        // Зануляване на ид-то за да не се обърква Forward-а
        Request::push(array('id' => false));
        
        // Форсира се обновяването на записите
        $res = Request::forward(array('Ctr' => 'rack_Zones', 'Act' => 'ajaxrefreshrows', 'divId' => $divId, 'refreshUrl' => $refreshUrlLocal));
    
        return $res;
    }
    
    
    /**
     * Екшън за приключване на движението
     */
    public function act_Done()
    {
        $ajaxMode = Request::get('ajax_mode');
        
        if($ajaxMode){
            if(!$this->haveRightFor('done')){
                core_Statuses::newStatus('|Нямате права|*!', 'error');
                return status_Messages::returnStatusesArray();
            }
        } else {
            $this->requireRightFor('done');
        }
        
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        
        // Заключване на екшъна
        if (!core_Locks::get("movement{$rec->id}", 120, 0)) {
            
            core_Statuses::newStatus('Друг потребител работи по движението|*!', 'warning');
            if($ajaxMode){
                return status_Messages::returnStatusesArray();
            }
            followretUrl(array($this));
        }
        
        if($ajaxMode){
            if(!$this->haveRightFor('done', $rec)){
                core_Locks::release("movement{$rec->id}");
                core_Statuses::newStatus('|Нямате права|*!', 'error');
                return status_Messages::returnStatusesArray();
            }
        } else {
            $this->requireRightFor('done', $rec);
        }
        
        $rec->state = 'closed';
        $this->save($rec, 'state,modifiedOn,modifiedBy');
        
        core_Locks::release("movement{$rec->id}");
        
        // Ако се обновява по Ajax
        if($ajaxMode){
            
            return self::forwardRefreshUrl();
        }
        
        followretUrl(array($this));
    }
    
    
    /**
     * Връща масив с всички използвани палети
     */
    public static function getExpected($storeId = null)
    {
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        $res = array(0 => array(), 1 => array());
        
        $floorValue = rack_PositionType::FLOOR;
        $query = self::getQuery();
        $query->where("#storeId = {$storeId} AND #state != 'closed' AND (#position != '{$floorValue}' OR #positionTo != '{$floorValue}')");
        
        while ($rec = $query->fetch()) {
            if (!empty($rec->position) && $rec->position != $floorValue) {
                $res[0][$rec->position] = $rec->productId;
            }
            
            if (!empty($rec->positionTo) && $rec->positionTo != $floorValue) {
                $res[1][$rec->positionTo] = $rec->productId;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Валидира транзакционния обект според зададените правила
     *
     * @param stdClass $transaction
     *
     * @return stdClass $res
     */
    private function validateTransaction($transaction)
    {
        $res = (object) array('transaction' => $transaction, 'errors' => array(), 'errorFields' => array(), 'warnings' => array(), 'warningFields' => array());
        
        if ($transaction->from == $transaction->to && empty($transaction->zonesQuantityTotal)) {
            $res->errors = 'Не може да се направи празно движение';
            $res->errorFields = 'positionTo,zones';
            
            return $res;
        }
        
        if (empty($transaction->from) && empty($transaction->to) && empty($transaction->zonesQuantityTotal)) {
            $res->errors = 'Не може да се направи празно движение';
            $res->errorFields = 'positionTo,zones';
            
            return $res;
        }
       
        if (countR($transaction->zonesQuantityArr) && !empty($transaction->quantity) && abs(round($transaction->quantity, 4)) < abs(round($transaction->zonesQuantityTotal, 4)) && $transaction->zonesQuantityTotal > 0) {
            $res->errors = 'Недостатъчно количество за оставяне в зоните';
            $res->errorFields = 'packQuantity,zones';
            
            return $res;
        }
        
        if (empty($transaction->quantity) && empty($transaction->zonesQuantityTotal)) {
            $res->errors = 'Не може да се направи празно движение';
            $res->errorFields = 'positionTo,zones';
            
            return $res;
        }
        
        $quantityOnPallet = rack_Pallets::getDefaultQuantity($transaction->productId, $transaction->storeId, $transaction->from);
        
        $fromPallet = $fromQuantity = $toQuantity = null;
        if (!empty($transaction->from) && $transaction->from != rack_PositionType::FLOOR) {
            $fromPallet = rack_Pallets::getByPosition($transaction->from, $transaction->storeId);
            if (empty($fromPallet)) {
                $res->errors = 'Палетът вече не е активен';
                $res->errorFields[] = 'palletId';
                
                return $res;
            }
            
            $fromQuantity = $fromPallet->quantity;
            
            if ($fromPallet->quantity - $transaction->quantity < 0) {
                $res->errors = 'Няма достатъчна наличност на изходящия палет';
                $res->errorFields[] = 'packQuantity,palletId';
                
                return $res;
            }
        }
        
        $toPallet = $toProductId = $error = null;
        if (!empty($transaction->to) && $transaction->to != rack_PositionType::FLOOR) {
            if (!rack_Racks::checkPosition($transaction->to, $transaction->productId, $transaction->storeId, $transaction->batch, $error)) {
                $res->errors = $error;
                $res->errorFields[] = 'positionTo,productId';
                
                return $res;
            }
            
            if ($toPallet = rack_Pallets::getByPosition($transaction->to, $transaction->storeId)) {
                $toProductId = $toPallet->productId;
                $toQuantity = $toPallet->quantity;
                
                if($transaction->batch != $toPallet->batch){
                    $res->errors = "На позицията артикулът е с друга партида";
                    $res->errorFields[] = 'positionTo,productId';
                }
            }
            
            // Ако има нова позиция и тя е заета от различен продукт - грешка
            if (isset($toProductId) && $toProductId != $transaction->productId) {
                $res->errors = "|* <b>{$transaction->to}</b> |е заета от артикул|*: <b>" . cat_Products::getTitleById($toProductId, false) . '</b>';
                $res->errorFields[] = 'positionTo,productId';
                
                return $res;
            }
            
            // Ако се мести от склада, и количеството е над наличното, се показва предупреждение
            if($transaction->from == rack_PositionType::FLOOR){
                $availableQuantity = rack_Products::getQuantity($transaction->productId, $transaction->storeId, true);
                if(round($transaction->quantity, 4) > round($availableQuantity, 4)){
                    $res->warnings[] = "|Въведеното количество е над наличното в склада|*!";
                    $res->warningFields[] = 'quantity';
                }
            }
            
            // Ако към новата позиция има чакащо движение
            if (self::fetchField("#positionTo = '{$transaction->to}' AND #storeId = {$transaction->storeId} AND #state = 'pending' AND #id != '{$transaction->id}'")) {
                $res->warnings[] = "Към новата позиция|* <b>{$transaction->to}</b> |има насочено друго чакащо движение|*";
                $res->warningFields[] = 'positionTo';
            }
            
            // Ако от новата позиция има чакащо движение
            if (self::fetchField("#position = '{$transaction->to}' AND #storeId = {$transaction->storeId} AND #state = 'pending' AND #id != '{$transaction->id}'")) {
                $res->warnings[] = "От новата позиция|* <b>{$transaction->to}</b> |има насочено друго чакащо движение|*";
                $res->warningFields[] = 'positionTo';
            }
            
            // Ако Към позицията е забранена за използване
            $unusableAndReserved = rack_RackDetails::getUnusableAndReserved($transaction->storeId);
            if (array_key_exists($transaction->to, $unusableAndReserved[0])) {
                $res->errors = "|*<b>{$transaction->to}</b> |е забранена за използване|*";
                $res->errorFields[] = 'positionTo';
                
                return $res;
            }
            
            // Ако Към позицията е запазена за друг артикул
            if (array_key_exists($transaction->to, $unusableAndReserved[1])) {
                if ($transaction->productId != $unusableAndReserved[1][$transaction->to]) {
                    $res->errors = "|*<b>{$transaction->to}</b> |е запазена за|*: <b>" . cat_Products::getTitleById($unusableAndReserved[1][$transaction->to], false) . '</b>';
                    $res->errorFields[] = 'positionTo';
                    
                    return $res;
                }
            }
        }
        
        if ((!empty($transaction->to) && $transaction->to != rack_PositionType::FLOOR) && $toQuantity + $transaction->quantity - $transaction->zonesQuantityTotal < 0) {
            $res->errors = 'Недостатъчно количество за изходящия палет';
            $res->errorFields[] = 'packQuantity,zones';
            
            return $res;
        }
        
        // Проверяване и на движенията по зоните
        $zoneErrors = $zoneWarnings = array();
        foreach ($transaction->zonesQuantityArr as $zone) {
            $movementQuantity = $documentQuantity = null;
            $zRec = rack_ZoneDetails::fetch("#zoneId = {$zone->zone} AND #productId = {$transaction->productId} AND #packagingId = {$transaction->packagingId}");
            $movementQuantity = is_object($zRec) ? $zRec->movementQuantity : null;
            $documentQuantity = is_object($zRec) ? $zRec->documentQuantity : null;
            $diff = round($movementQuantity, 4) + round($zone->quantity, 4);
            
            if ($diff < 0) {
                $zoneErrors[] = rack_Zones::getHyperlink($zone->zone, false);
            }
            
            if (!empty($documentQuantity) && $diff > $documentQuantity) {
                $zoneWarnings[] = rack_Zones::getHyperlink($zone->zone, false);
            }
        }
        
        if (countR($zoneErrors)) {
            $res->errors = 'В зони|*: <b>' . implode(', ', $zoneErrors) . '</b> |се получава отрицателно количество|*';
            $res->errorFields[] = 'zones';
            
            return $res;
        }
        
        if (countR($zoneWarnings)) {
            $res->warnings[] = 'В зони|*: <b>' . implode(', ', $zoneWarnings) . '</b> |се получава по-голямо количество от необходимото|*';
            $res->warningFields[] = 'zones';
        }
        
        // Предупреждение: В новия палет се получава по-голямо количество от стандартното
        if (!empty($toPallet) && $transaction->from != $transaction->to && !empty($toQuantity) && !empty($quantityOnPallet)) {
            if ($toQuantity + $transaction->quantity - $transaction->zonesQuantityTotal > $quantityOnPallet) {
                $quantityOnPalletV = core_Type::getByName('double(smartRound)')->toVerbal($quantityOnPallet);
                $res->warnings[] = "В новия палет се получава по-голямо количество от стандартното|*: <b>{$quantityOnPalletV}</b>";
                $res->warningFields[] = 'positionTo';
                $res->warningFields[] = 'packQuantity';
                $res->warningFields[] = 'zonesQuantityTotal';
            }
        }
        
        // Предупреждение: В началния палет се получава по-голямо количество от стандартното
        if (!empty($fromPallet) && $transaction->quantity < 0 && ($fromQuantity - $transaction->quantity > $quantityOnPallet)) {
            $quantityOnPalletV = core_Type::getByName('double(smartRound)')->toVerbal($quantityOnPallet);
            $res->warnings[] = "В началния палет се получава по-голямо количество от стандартното|*: <b>{$quantityOnPalletV}</b>";
            $res->warningFields[] = 'positionTo';
            $res->warningFields[] = 'packQuantity';
            $res->warningFields[] = 'zonesQuantityTotal';
        }
        
        // Ако се палетира от пода проверява се дали е налично количеството
        if($transaction->from == rack_PositionType::FLOOR && isset($transaction->batch)){
            $bMsg = isset($transaction->batch) ? 'на партидата' : 'без партида';
            $availableQuantity = rack_Products::getFloorQuantity($transaction->productId, $transaction->batch, $transaction->storeId);
            if($availableQuantity < $transaction->quantity){
                $availableQuantityV = core_Type::getByName('double(smartRound)')->toVerbal($availableQuantity);
                $res->errors = "Количеството {$bMsg} е над наличното|*: <b>{$availableQuantityV}</b>";
                $res->errorFields[] = 'batch';
                $res->errorFields[] = 'packQuantity';
            }
        }
        
        
        return $res;
    }
    
    
    /**
     * Връща транзакцията на движението
     *
     * @param stdClass $rec - запис
     *
     * @return stdClass $transaction       - обекта на транзакцията
     *                  o id                    - ид
     *                  o storeId               - ид на склад
     *                  o productId             - продукта на "От" палета/пода
     *                  o quantity              - к-во в основната опаковка за преместване
     *                  o packagingId           - ид на опаковката на движението
     *                  o from                  - от коя позиция или NULL за пода
     *                  o to                    - към коя позиция, същата, друга или NULL за пода
     *                  array $zonesQuantityArr - масив със зоните
     *                  o $zonesQuantityTotal   - всичкото оставено в зоните количество
     *
     */
    private function getTransaction($rec, $reverse = false)
    {
        $sign = ($reverse === true) ? -1 : 1;
        
        $transaction = new stdClass();
        $transaction->id = $rec->id;
        $transaction->storeId = $rec->storeId;
        $transaction->productId = $rec->productId;
        
        $BatchClass = batch_Defs::getBatchDef($rec->productId);
        if(is_object($BatchClass)){
            $transaction->batch = !empty($rec->batch) ? $rec->batch : '';
        } else {
            $transaction->batch = null;
        }
        
        if(empty($transaction->batch) && isset($rec->palletId)){
            $palletBatch = rack_Pallets::fetchField($rec->palletId, 'batch');
            if(!empty($palletBatch)){
                $transaction->batch = $palletBatch;
            }
        }
        
        $transaction->quantity = $sign * $rec->quantity;
        $transaction->packagingId = $rec->packagingId;
        $transaction->from = $rec->position;
        $transaction->to = $rec->positionTo;
        $transaction->zonesQuantityTotal = 0;
        
        $transaction->zonesQuantityArr = self::getZoneArr($rec, $transaction->zonesQuantityTotal);
        $transaction->zonesQuantityTotal *= $sign * $rec->quantityInPack;
        foreach ($transaction->zonesQuantityArr as &$zoneRec) {
            $zoneRec->quantity *= $sign * $rec->quantityInPack;
        }
        
        return $transaction;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FLD('movement', 'varchar', 'tdClass=movement-description');
        $data->listTableMvc->FLD('startBtn', 'varchar', 'tdClass=centered');
        $data->listTableMvc->FLD('stopBtn', 'varchar', 'tdClass=centered');
        if (Mode::is('screenMode', 'narrow') && array_key_exists('productId', $data->listFields)) {
            $data->listTableMvc->tableRowTpl = "[#ADD_ROWS#][#ROW#]\n";
            $data->listFields['productId'] = '@Артикул';
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $productName = ' ' . plg_Search::normalizeText(cat_Products::getTitleById($rec->productId));
        $res = ' ' . $res . ' ' . $productName;
    }
    
    
    /**
     * Затваря всички незатворени движение към зоната
     *
     * @param int $zoneId
     *
     * @return void
     */
    public static function closeByZoneId($zoneId)
    {
        $query = self::getQuery();
        $query->where("LOCATE('|{$zoneId}|', #zoneList) AND #state != 'closed'");
        while ($rec = $query->fetch()) {
            $rec->state = 'closed';
            static::save($rec, 'state');
        }
    }
    
    
    /**
     * Изтриване на минали движения
     *
     * @param datetime $olderThan
     * @return void
     */
    private function deleteOldMovements($olderThan)
    {
        if(empty($olderThan)) return;
        
        // Всички движения преди X време
        $createdBefore = dt::addSecs(-1 * $olderThan);
        
        $movementQuery = rack_Movements::getQuery();
        $movementQuery->where("#createdOn <= '{$createdBefore}'");
        
        while($mRec = $movementQuery->fetch()){
            $delete = true;
            
            // Ако началния палет е активен, не се изтрива
            if(isset($mRec->palletId)){
                $fromPalletState = rack_Pallets::fetchField($mRec->palletId, 'state');
                if($fromPalletState == 'active'){
                    $delete = false;
                }
            }
            
            // Ако има крайна дестинация и тя в момента е заета от активен палет за същия артикул, не се изтрива
            if(!empty($mRec->positionTo) && $delete === $delete){
                if($toPalletRec = rack_Pallets::getByPosition($mRec->positionTo, $mRec->storeId)){
                    if($toPalletRec->productId == $mRec->productId && $toPalletRec->state == 'active'){
                        $delete = false;
                    }
                }
            }
            
            // Движението се изтрива, ако отговаря на условията
            if($delete === true){
                rack_Movements::delete($mRec->id);
            }
        }
    }
    
    
    /**
     * Изтриване на затворени палети
     * 
     * @param datetime $olderThan
     * @return void
     */
    private function deleteClosedPallets($olderThan)
    {
        if(empty($olderThan)) return;
        
        $closedBefore = dt::addSecs(-1 * $olderThan);
        
        // Кои палети са затворени преди указаното време
        $pQuery = rack_Pallets::getQuery();
        $pQuery->where("#state = 'closed'");
        $pQuery->where("#closedOn <= '{$closedBefore}'");
        $pQuery->show('id');
        $palletsToDelete = arr::extractValuesFromArray($pQuery->fetchAll(), 'id');
        if(!countR($palletsToDelete)) return;
        
        // От тези палети, кои от тх все още участват в движения
        $query = rack_Movements::getQuery();
        $query->in('palletId', $palletsToDelete);
        $query->show('palletId');
        $palletsInMovements = arr::extractValuesFromArray($query->fetchAll(), 'palletId');
        
        // Изтриват се тези палети, към които вече няма движения
        $palletsLeftToDelete = array_diff_key($palletsToDelete, $palletsInMovements);
        foreach ($palletsLeftToDelete as $palletId) {
            rack_Pallets::delete($palletId);
        }
    }
    
    
    /**
     * Изтриване на стари движения по разписание
     */
    public function cron_DeleteOldMovementsAndPallets()
    {
        // Изтриване на старите движения
        $olderThan = self::DELETE_CLOSED_MOVEMENTS_OLDER_THEN;
        $this->deleteOldMovements($olderThan);
        
        // Изтриване на затворените палети
        $palletsOlderThan = rack_Pallets::DELETE_CLOSED_PALLETS_OLDER_THAN;
        $this->deleteClosedPallets($palletsOlderThan);
    }
}
