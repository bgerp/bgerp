<?php


/**
 * Движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
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
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper, plg_SaveAndNew, plg_State, plg_Sorting,plg_Search,plg_AlignDecimals2';
    
    
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
    public $canDone = 'ceo,admin,rack';
    
    
    /**
     * Кой може да заяви движение
     */
    public $canToggle = 'ceo,admin,rack';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'productId,movement=Движение,workerId=Изпълнител,createdOn,createdBy';

    
    /**
     * Полета по които да се търси
     */
    public $searchFields = 'palletId,position,positionTo,note';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,column=none');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getSellableProducts)', 'tdClass=productCell,caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|zones|palletId,mandatory,remember');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack,silent');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,smartCenter,silent');
        $this->FNC('movementType', 'varchar', 'silent,input=hidden');
        
        // Палет, позиции и зони
        $this->FLD('palletId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->Палет,input=hidden,silent,placeholder=Под||Floor,removeAndRefreshForm=position|positionTo,silent,smartCenter');
        $this->FLD('position', 'rack_PositionType', 'caption=Движение->Позиция,input=none');
        $this->FLD('positionTo', 'rack_PositionType', 'caption=Движение->Нова,input=none');
        $this->FLD('palletToId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->Палет към,input=none,smartCenter');
        $this->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=10em|10em,validate=rack_Movements::validateZonesTable)', 'caption=Движение->Зони,smartCenter,input=none');
        
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none');
        $this->FLD('state', 'enum(closed=Приключено, active=Активно, pending=Чакащо)', 'caption=Състояние');
        $this->FLD('workerId', 'user', 'caption=Движение->Товарач,tdClass=nowrap,input=none');
        
        $this->FLD('note', 'varchar(64)', 'caption=Движение->Забележка,column=none');
        $this->FLD('zoneList', 'keylist(mvc=rack_Zones, select=num)', 'caption=Зони,input=none');
        
        $this->setDbIndex('storeId');
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
            if(empty($rec->position)){
                $rec->position = rack_PositionType::FLOOR;
                $rec->palletId = null;
            }
            
            $quantityInZones = 0;
            self::getZoneArr($rec, $quantityInZones);
            if (empty($rec->packQuantity) && empty($rec->defaultPackQuantity) && $quantityInZones >= 0){
                 $form->setError('packQuantity', 'Въведете количество');
            }
            
            if (!empty($rec->packQuantity)){
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setError('packQuantity', $warning);
                }
            }
            
            if (!$form->gotErrors()) {
                if(empty($rec->positionTo)){
                    $rec->positionTo = $rec->position;
                }
                
                // Симулиране дали транзакцията е валидна
                $clone = clone $rec;
                $clone->packQuantity = !empty($rec->packQuantity) ? $rec->packQuantity : $rec->defaultPackQuantity;
                
                $clone->quantity = $clone->quantityInPack * $clone->packQuantity;
                $transaction = $mvc->getTransaction($clone);
                $transaction = $mvc->validateTransaction($transaction);
                
                if(!empty($transaction->errors)){
                    $form->setError($transaction->errorFields, $transaction->errors);
                }
                
                if(!empty($transaction->warnings)){
                    $form->setWarning($transaction->warningFields, implode(',', $transaction->warnings));
                }
                
                if (!$form->gotErrors()) {
                    $rec->packQuantity = !empty($rec->packQuantity) ? $rec->packQuantity : $rec->defaultPackQuantity;
                    $rec->quantity = $rec->quantityInPack * $rec->packQuantity;
                    
                    if ($rec->state == 'closed') {
                        $rec->_isCreatedClosed = true;
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // Кеш на засегнатите зони за бързодействие
        $zonesArr = arr::extractValuesFromArray($mvc->getZoneArr($rec), 'zone');
        $rec->zoneList = (count($zonesArr)) ? keylist::fromArray($zonesArr) : null;
        
        if ($rec->state == 'active' || $rec->_canceled === true || $rec->_isCreatedClosed === true) {
            if (empty($rec->workerId)) {
                $rec->workerId = core_Users::getCurrent('id', false);
            }
            
            // Изпълнение на транзакцията
            $reverse = ($rec->_canceled === true) ? true : false;
            $transaction = $mvc->getTransaction($rec, $reverse);
            $result = $mvc->doTransaction($transaction);
            
            // Ако има проблем при изпълнението записа се спира
            if($result !== true){
                core_Statuses::newStatus('Проблем при записа на движението');
                return false;
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
        if(!empty($transaction->from) && $transaction->from != rack_PositionType::FLOOR){
            try{
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->from, -1 * $transaction->quantity);
            } catch (core_exception_Expect $e){
                reportException($e);
                return false;
            }
            $rMvc->updateRacks[$transaction->storeId . '-' . $transaction->from] = true;
        }
        
        // Ако има крайна позиция и тя не е пода обновява се палета на нея
        if(!empty($transaction->to) && $transaction->to != rack_PositionType::FLOOR){
            
            try{
                $restQuantity = $transaction->quantity - $transaction->zonesQuantityTotal;
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->to, $restQuantity);
            } catch (core_exception_Expect $e){
                reportException($e);
                
                // Ако има проблем ревърт на предното движение
                rack_Pallets::increment($transaction->productId, $transaction->storeId, $transaction->from, $transaction->quantity);
                return false;
            }
            
            $rMvc->updateRacks[$transaction->storeId . '-' . $transaction->to] = true;
        }
        
        if(is_array($transaction->zonesQuantityArr)){
            foreach ($transaction->zonesQuantityArr as $obj){
                rack_ZoneDetails::recordMovement($obj->zone, $transaction->productId, $transaction->packagingId, $obj->quantity);
            }
        }
        
        core_Cache::remove('UsedRacksPossitions', $transaction->storeId);
        
        return true;
    }
    
    
    /**
     * Помощна ф-я обръщаща зоните в подходящ вид и събира общото количество по тях
     * 
     * @param stdClass $rec
     * @param double $quantityInZones
     * 
     * @return array $zoneArr
     */
    private function getZoneArr($rec, &$quantityInZones = null)
    {
        $quantityInZones = 0;
        $zoneArr = array();
        if (isset($rec->zones)) {
            $zoneArr = type_Table::toArray($rec->zones);
            if (count($zoneArr)) {
                foreach ($zoneArr as $obj) {
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
        if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
        
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
        $form->setField('storeId', 'input=hidden');
        $form->setField('workerId', 'input=none');
        
        if (isset($rec->productId)) {
            $form->setField('packagingId', 'input');
            
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            $form->setField('palletId', 'input');
            $form->setField('positionTo', 'input');
            $form->setField('packQuantity', 'input');
            
            $zones = rack_Zones::getZones($rec->storeId);
            if (count($zones)) {
                $form->setFieldTypeParams('zones', array('zone_opt' => array('' => '') + $zones));
                $form->setField('zones', 'input');
            } else {
                $form->setField('zones', 'input=none');
            }
            
            // Възможния избор на палети от склада
            $pallets = rack_Pallets::getPalletOptions($rec->productId, $rec->storeId);
            $form->setOptions('palletId', array('' => tr('Под||Floor')) + $pallets);
            
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            
            // Показване на допустимото количество
            $availableQuantity = rack_Pallets::getAvailableQuantity($rec->palletId, $rec->productId, $rec->storeId);
            if(empty($rec->palletId)){
                if ($defQuantity = rack_Pallets::getDefaultQuantity($rec->productId, $rec->storeId)) {
                    $availableQuantity = min($availableQuantity, $defQuantity);
                }
            }
            
            if ($availableQuantity > 0) {
                $availableQuantity /= $rec->quantityInPack;
                $availableQuantityV = core_Type::getByName('double(decimals=2)')->toVerbal($availableQuantity);
                $form->setField('packQuantity', "placeholder={$availableQuantity}");
                $form->rec->defaultPackQuantity = $availableQuantity;
            }
            
            // На коя позиция е палета?
            if (isset($rec->palletId)) {
                $form->setField('position', 'input=hidden');
                if ($positionId = rack_Pallets::fetchField($rec->palletId, 'position')) {
                    $form->setDefault('position', $positionId);
                    $form->setField('positionTo', 'placeholder=Остава');
                }
            } else {
                $form->setField('positionTo', 'placeholder=Остава');
            }
            
            // Добавяне на предложения за нова позиция
            if ($bestPos = rack_Pallets::getBestPos($rec->productId, $rec->storeId)) {
                $form->setSuggestions('positionTo', array(tr('Под') => tr('Под'), $bestPos => $bestPos));
                if($form->rec->positionTo == rack_PositionType::FLOOR){
                    $form->rec->positionTo = tr('Под');
                }
            }
        } else {
            $form->setField('packagingId', 'input=none');
        }
        
        // Състоянието е последното избрано от текущия потребител
        $lQuery = self::getQuery();
        $lQuery->where("#createdBy = " . core_Users::getCurrent());
        $lQuery->orderBy('id', 'DESC');
        if($lastState = $lQuery->fetch()->state){
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
                    $form->setField('positionTo', 'input=hidden');
                    $form->setField('palletId', 'caption=Сваляне на пода->Палет');
                    $form->setField('note', 'caption=Сваляне на пода->Забележка');
                    $form->setDefault('positionTo', rack_PositionType::FLOOR);
                    break;
                case 'rack2rack':
                    $form->setField('zones', 'input=none');
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
     * @param mixed $tableData
     * @param core_Type $Type
     * @return array $res
     */
    public static function validateZonesTable($tableData, $Type)
    {
        $tableData = (array) $tableData;
        if (empty($tableData)) {
            
            return;
        }
        
        $res = $zones = $error = $errorFields = array();
        
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
            }
        }
        
        if (count($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }
        
        if (count($errorFields)) {
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
        
        if ($mvc->haveRightFor('toggle', $rec) && $rec->state != 'active') {
            
            $row->_rowTools->addLink('Започване', array($mvc, 'toggle', $rec->id, 'ret_url' => true), "id=start{$rec->id},ef_icon=img/16/control_play.png,title=Започване на движението");
            $state .= ht::createBtn('Започни', array($mvc, 'toggle', $rec->id, 'ret_url' => true), false, false, 'ef_icon=img/16/control_play.png,title=Започване на движението');
        
            if ($rec->createdBy != core_Users::getCurrent()){
                $row->_rowTools->setWarning("start{$rec->id}", 'Сигурни ли сте, че искате да започнете движение от друг потребител');
            }
        }
        
        if ($mvc->haveRightFor('done', $rec)) {
            $row->_rowTools->addLink('Приключване', array($mvc, 'done', $rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Приключване на движението');
            $state .= ht::createBtn('Приключи', array($mvc, 'done', $rec->id, 'ret_url' => true), false, false, 'ef_icon=img/16/gray-close.png,title=Приключване на движението');
        }
        
        if ($mvc->haveRightFor('toggle', $rec) && $rec->state != 'pending') {
            $row->_rowTools->addLink('Отказване', array($mvc, 'toggle', $rec->id, 'ret_url' => true), 'warning=Наистина ли искате да откажете движението|*?,ef_icon=img/16/reject.png,title=Отказ на движението');
        }
        
        if (!empty($state)) {
            $row->workerId .= ' ' . $state;
        }
        
        if (!empty($rec->note)) {
            $row->note = "<div style='font-size:0.8em;'>{$row->note}</div>";
        }
        
        $row->productId = cat_Products::getShortHyperlink($rec->productId, true);
        if(!empty($rec->note)){
            $notes = $mvc->getFieldType('note')->toVerbal($rec->note);
            $row->productId .= "<br><span class='small'>{$notes}</span>";
        }
        
        $row->_rowTools->addLink('Палети', array('rack_Pallets', 'productId' => $rec->productId), "id=search{$rec->id},ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт");
        
        $skipZones = isset($fields['-inline']) ? true : false;
        $row->movement = $mvc->getMovementDescription($rec, $skipZones);
    }
    
    
    /**
     * Подробно описание на движението
     * 
     * @param stdClass $rec
     * @return string $res
     */
    private function getMovementDescription($rec, $skipZones = false)
    {
        $position = $this->getFieldType('position')->toVerbal($rec->position);
        $positionTo = $this->getFieldType('positionTo')->toVerbal($rec->positionTo);
        
        $Double = core_Type::getByName('double(smartRound)');
        $packagingRow = cat_UoM::getShortName($rec->packagingId);
        $packQuantityRow = $Double->toVerbal($rec->packQuantity);
        
        $class = '';
        if ($palletId = cat_UoM::fetchBySinonim('pallet')->id) {
            if($palletRec = cat_products_Packagings::getPack($rec->productId, $palletId)){
                if($rec->quantity == $palletRec->quantity){
                    $class = "class = 'quiet'";
                }
            }
        }
        
        $movementArr = array();
        $packType = cat_UoM::fetchField($rec->packagingId, 'type');
        if($packType != 'uom'){
            $packagingRow = str::getPlural($rec->packQuantity, $packagingRow, true);
        }
        if(!empty($rec->packQuantity)){
            $packQuantityRow = ht::styleIfNegative($packQuantityRow, $rec->packQuantity);
            $movementArr[] = "{$position} (<span {$class}>{$packQuantityRow}</span> {$packagingRow})";
        }
        
        if($skipZones === false){
            $zones = self::getZoneArr($rec, $quantityInZones);
            $restQuantity = $rec->packQuantity - $quantityInZones;
            
            foreach ($zones as $zoneRec){
                $zoneTitle = rack_Zones::getHyperlink($zoneRec->zone);
                $zoneQuantity = $Double->toVerbal($zoneRec->quantity);
                $zoneQuantity = ht::styleIfNegative($zoneQuantity, $zoneRec->quantity);
                $movementArr[] = "<span>{$zoneTitle} ({$zoneQuantity})</span>";
            }
        }
        
        if(!empty($positionTo) && $restQuantity){
            $resQuantity = $Double->toVerbal($restQuantity);
            $movementArr[] = "{$positionTo} ({$resQuantity})";
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
        if ($action == 'toggle' && isset($rec->state)) {
            if (!in_array($rec->state, array('pending', 'active'))) {
                $requiredRoles = 'no_one';
            } elseif ($rec->state == 'active' && $rec->workerId != $userId) {
                $requiredRoles = 'ceo,rackMaster';
            }
        }
        
        if ($action == 'done' && $rec && $rec->state) {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            } elseif($rec->workerId != $userId) {
                $requiredRoles = 'ceo,rackMaster';
            }
        }
        
        if ($action == 'edit' && isset($rec) && $rec->state != 'pending') {
            $requiredRoles = 'no_one';
        }
        
        if ($action == 'delete' && isset($rec) && $rec->state != 'pending') {
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
            $data->query->where("#palletId = {$palletId} OR #palletToId = {$palletId}");
        }
        
        $data->listFilter->setFieldType('state', 'enum(all=Всички,pending=Чакащи,active=Активни,closed=Приключени)');
        $data->listFilter->setField('state', 'silent,input');
        $data->listFilter->setDefault('state', 'current');
        $data->listFilter->input();
        
        $data->listFilter->showFields = 'search,state';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    
        if($state = $data->listFilter->rec->state){
            if(in_array($state, array('active', 'closed', 'pending'))){
                $data->query->where("#state = '{$state}'");
            }
        }
        
        $data->query->orderBy('orderByState=ASC,createdOn=DESC');
    }
    
    
    /**
     * Екшън за започване на движението
     */
    public function act_Toggle()
    {
        $this->requireRightFor('toggle');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('toggle', $rec);
        $oldState = $rec->state;
        
        $reverse = false;
        $rec->state = ($oldState == 'pending') ? 'active' : 'pending';
        if ($rec->state == 'pending') {
            $rec->workerId = null;
            $rec->_canceled = true;
            $reverse = true;
        }
        
        // Проверка може ли транзакцията да мине
        $transaction = $this->getTransaction($rec, $reverse);
        $transaction = $this->validateTransaction($transaction);
       
        if(!empty($transaction->errors)){
            followretUrl(null, $transaction->errors, 'error');
        }
        
        $rec->workerId = core_Users::getCurrent();
        $this->save($rec, 'state,workerId');
        
        $msg = (count($transaction->warnings)) ? implode(', ', $transaction->warnings) : null;
        $type = (count($transaction->warnings)) ? 'warning' : 'notice';
        
        followretUrl(null, $msg, $type);
    }
    
    
    /**
     * Екшън за приключване на движението
     */
    public function act_Done()
    {
        $this->requireRightFor('done');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('done', $rec);
        
        $rec->state = 'closed';
        $this->save($rec, 'state');
        
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
        $res = (object)array('transaction' => $transaction, 'errors' => array(), 'errorFields' => array(), 'warnings' => array(), 'warningFields' => array());
        $quantityOnPallet = rack_Pallets::getDefaultQuantity($transaction->productId, $transaction->storeId);
        
        if($transaction->from == $transaction->to && empty($transaction->zonesQuantityTotal)){
            $res->errors = "Не може да се направи празно движение";
            $res->errorFields = 'positionTo,zones';
            return $res;
        }
        
        if(empty($transaction->from) && empty($transaction->to) && empty($transaction->zonesQuantityTotal)){
            $res->errors = "Не може да се направи празно движение";
            $res->errorFields = 'positionTo,zones';
            return $res;
        }
        
        if(count($transaction->zonesQuantityArr) && !empty($transaction->quantity) && abs($transaction->quantity) < abs($transaction->zonesQuantityTotal)){
            $res->errors = "Недостатъчно количество за оставяне в зоните";
            $res->errorFields = 'packQuantity,zones';
            return $res;
        }
        
        if(empty($transaction->quantity) && empty($transaction->zonesQuantityTotal)){
            $res->errors = "Не може да се направи празно движение";
            $res->errorFields = 'positionTo,zones';
            return $res;
        }
        
        $fromPallet = $fromQuantity = $toQuantity = null;
        if(!empty($transaction->from) && $transaction->from != rack_PositionType::FLOOR){
            $fromPallet = rack_Pallets::getByPosition($transaction->from, $transaction->storeId);
            if(empty($fromPallet)){
                $res->errors = "Палетът вече не е активен";
                $res->errorFields[] = 'palletId';
                return $res;
            }
            
            $fromQuantity = $fromPallet->quantity;
            if($fromPallet->quantity - $transaction->quantity < 0){
                $res->errors = "Няма достатъчна наличност на изходящия палет";
                $res->errorFields[] = 'packQuantity,palletId';
                return $res;
            }
        }
        
        $toPallet = $toProductId = null;
        if(!empty($transaction->to) && $transaction->to != rack_PositionType::FLOOR){
            
            if(!rack_Racks::checkPosition($transaction->to, $transaction->productId, $transaction->storeId, $error)){
                $res->errors = $error;
                $res->errorFields[] = 'positionTo,productId';
                return $res;
            }
            
            if($toPallet = rack_Pallets::getByPosition($transaction->to, $transaction->storeId)){
                $toProductId = $toPallet->productId;
                $toQuantity = $toPallet->quantity;
            }
            
            // Ако има нова позиция и тя е заета от различен продукт - грешка
            if(isset($toProductId) && $toProductId != $transaction->productId){
                $res->errors = "|* <b>$transaction->to</b> |е заета от артикул|*: <b>" . cat_Products::getTitleById($toProductId, false) . "</b>";
                $res->errorFields[] = 'positionTo,productId';
                return $res;
            }
            
            // Ако към новата позиция има чакащо движение
            if (self::fetchField("#positionTo = '{$transaction->to}' AND #storeId = {$transaction->storeId} AND #state = 'pending' AND #id != '{$transaction->id}'")){
                $res->warnings[] = "Към новата позиция|* <b>{$transaction->to}</b> |има насочено друго чакащо движение|*";
                $res->warningFields[] = 'positionTo';
            }
            
            // Ако от новата позиция има чакащо движение
            if (self::fetchField("#position = '{$transaction->to}' AND #storeId = {$transaction->storeId} AND #state = 'pending' AND #id != '{$transaction->id}'")){
                $res->warnings[] = "От новата позиция|* <b>{$transaction->to}</b> |има насочено друго чакащо движение|*";
                $res->warningFields[] = 'positionTo';
            }
            
            // Ако Към позицията е забранена за използване
            $unusableAndReserved = rack_RackDetails::getUnusableAndReserved($transaction->storeId);
            if (array_key_exists($transaction->to, $unusableAndReserved[0])){
                $res->errors = "|*<b>{$transaction->to}</b> |е забранена за използване|*";
                $res->errorFields[] = 'positionTo';
                return $res;
            }
            
            // Ако Към позицията е запазена за друг артикул
            if (array_key_exists($transaction->to, $unusableAndReserved[1])){
                if ($transaction->productId != $unusableAndReserved[1][$transaction->to]){
                    $res->errors = "|*<b>{$transaction->to}</b> |е запазена за|*: <b>" . cat_Products::getTitleById($unusableAndReserved[1][$transaction->to], false) . "</b>";
                    $res->errorFields[] = 'positionTo';
                    return $res;
                }
            }
        }
        
        if($toQuantity + $transaction->quantity - $transaction->zonesQuantityTotal < 0){
           $res->errors = "Недостатъчно количество за изходящия палет";
           $res->errorFields[] = 'packQuantity,zones';
           return $res;
        }
        
        // Проверяване и на движенията по зоните
        $zoneErrors = $zoneWarnings = array();
        foreach ($transaction->zonesQuantityArr as $zone){
            $movementQuantity = $documentQuantity = null;
            $zRec = rack_ZoneDetails::fetch("#zoneId = {$zone->zone} AND #productId = {$transaction->productId} AND #packagingId = {$transaction->packagingId}");
            $movementQuantity = is_object($zRec) ? $zRec->movementQuantity : null;
            $documentQuantity = is_object($zRec) ? $zRec->documentQuantity : null;
            
            if($movementQuantity + $zone->quantity < 0){
                $zoneErrors[] = rack_Zones::getVerbal($zone->zone, 'num');
            }
            
            if(!empty($documentQuantity) && $movementQuantity + $zone->quantity > $documentQuantity){
                $zoneWarnings[] = rack_Zones::getVerbal($zone->zone, 'num');
            }
        }
        
        if(count($zoneErrors)){
            $res->errors = "В зони|* <b>" . implode(', ', $zoneErrors) . "</b> |се получава отрицателно количество|*";
            $res->errorFields[] = 'zones';
            return $res;
        }
        
        if(count($zoneWarnings)){
            $res->warnings[] = "В зони|* <b>" . implode(', ', $zoneWarnings) . "</b> |се получава по-голямо количество от необходимото|*";
            $res->warningFields[] = 'zones';
        }
       
        // Предупреждение: В новия палет се получава по-голямо количество от стандартното
        if(!empty($toQuantity) && !empty($quantityOnPallet)){
            if($toQuantity + $transaction->quantity - $transaction->zonesQuantityTotal > $quantityOnPallet){
                $quantityOnPalletV = core_Type::getByName('double(smartRound)')->toVerbal($quantityOnPallet);
                $res->warnings[] = "В новия палет се получава по-голямо количество от стандартното|*: <b>{$quantityOnPalletV}";
                $res->warningFields[] = 'positionTo';
                $res->warningFields[] = 'packQuantity';
                $res->warningFields[] = 'zonesQuantityTotal';
            }
        }
        
        // Предупреждение: В началния палет се получава по-голямо количество от стандартното
        if(!empty($fromPallet) && $transaction->quantity < 0 && ($fromQuantity - $transaction->quantity > $quantityOnPallet)){
            $quantityOnPalletV = core_Type::getByName('double(smartRound)')->toVerbal($quantityOnPallet);
            $res->warnings[] = "В новия палет се получава по-голямо количество от стандартното|*: <b>{$quantityOnPalletV}</b";
            $res->warningFields[] = 'positionTo';
            $res->warningFields[] = 'packQuantity';
            $res->warningFields[] = 'zonesQuantityTotal';
        }
        
        return $res;
    }
    
    
    /**
     * Връща транзакцията на движението
     * 
     * @param stdClass $rec - запис
     * @return stdClass $transaction       - обекта на транзакцията
     *             o id                    - ид
     *             o storeId               - ид на склад
     *             o productId             - продукта на "От" палета/пода
     *             o quantity              - к-во в основната опаковка за преместване
     *             o packagingId           - ид на опаковката на движението
     *             o from                  - от коя позиция или NULL за пода
     *             o to                    - към коя позиция, същата, друга или NULL за пода
     *             array $zonesQuantityArr - масив със зоните
     *             o $zonesQuantityTotal   - всичкото оставено в зоните количество
     * 
     */
    private function getTransaction($rec, $reverse = false)
    {
        $sign = ($reverse === true) ? -1 : 1;
        
        $transaction = new stdClass();
        $transaction->id = $rec->id;
        $transaction->storeId = $rec->storeId;
        $transaction->productId = $rec->productId;
        $transaction->quantity = $sign * $rec->quantity;
        $transaction->packagingId = $rec->packagingId;
        $transaction->from = $rec->position;
        $transaction->to = $rec->positionTo;
        $transaction->zonesQuantityTotal = 0;
        
        $transaction->zonesQuantityArr = self::getZoneArr($rec, $transaction->zonesQuantityTotal);
        $transaction->zonesQuantityTotal *= $sign * $rec->quantityInPack;
        foreach ($transaction->zonesQuantityArr as &$zoneRec){
            $zoneRec->quantity *= $sign * $rec->quantityInPack;
        }
        
        return $transaction;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (Mode::is('screenMode', 'narrow')) {
            $data->listTableMvc->commonFirst = true;
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
     * @return void
     */
    public static function closeByZoneId($zoneId)
    {
        $query = self::getQuery();
        $query->where("LOCATE('|{$zoneId}|', #zoneList) AND #state != 'closed'");
        while($rec = $query->fetch()){
            $rec->state = 'closed';
            static::save($rec, 'state');
        }
    }
}
