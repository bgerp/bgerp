<?php


/**
 * Клас за наследяване от моделите за движения
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class rack_MovementAbstract extends core_Manager
{

    /**
     * Полета по които да се търси
     */
    public $searchFields = 'palletId,position,positionTo,note';


    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";


    /**
     * Добавяне на задължителните полета в наследниците
     */
    protected static function setFields($mvc)
    {
        $mvc->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,column=none');
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts)', 'tdClass=productCell,caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|zones|palletId,mandatory,remember');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack,silent');
        $mvc->FNC('packQuantity', 'double(min=0)', 'caption=Количество,smartCenter,silent');
        $mvc->FNC('movementType', 'varchar', 'silent,input=hidden');

        // Палет, позиции и зони
        $mvc->FLD('palletId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->От,input=hidden,silent,placeholder=Под||Floor,removeAndRefreshForm=position|positionTo,smartCenter');
        $mvc->FLD('batch', 'text', 'silent,input=none,before=positionTo,removeAndRefreshForm');
        $mvc->FLD('position', 'rack_PositionType', 'caption=Движение->От,input=none');
        $mvc->FLD('positionTo', 'rack_PositionType', 'caption=Движение->Към,input=none');
        $mvc->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=10em|10em,validate=rack_Movements::validateZonesTable)', 'caption=Движение->Зони,smartCenter,input=hidden');

        $mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
        $mvc->FLD('quantityInPack', 'double', 'input=hidden');
        $mvc->FLD('workerId', 'user(roles=ceo|rack)', 'caption=Движение->Товарач,tdClass=nowrap,input=none');

        $mvc->FLD('note', 'varchar(64)', 'caption=Движение->Забележка,column=none');
        $mvc->FLD('state', 'enum(pending=Чакащо, waiting=Запазено, active=Активно, closed=Приключено)', 'caption=Движение->Състояние,silent');
        $mvc->FLD('brState', 'enum(pending=Чакащо, waiting=Запазено, active=Активно, closed=Приключено)', 'caption=Движение->Състояние,silent,input=none');
        $mvc->FLD('zoneList', 'keylist(mvc=rack_Zones, select=num)', 'caption=Зони,input=none');
        $mvc->FLD('fromIncomingDocument', 'enum(no,yes)', 'input=hidden,silent,notNull,value=no');
        $mvc->FNC('containerId', 'int', 'input=hidden,caption=Документи,silent');
        $mvc->FLD('documents', 'keylist(mvc=doc_Containers,select=id)', 'input=none,caption=Документи');

        $mvc->setDbIndex('storeId');
        $mvc->setDbIndex('palletId');
        $mvc->setDbIndex('productId,storeId');
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $makeLinks = !($fields['-inline'] && !isset($fields['-inline-single']));
        if (!empty($rec->note)) {
            $row->note = "<div style='font-size:0.8em;'>{$row->note}</div>";
        }

        $row->productId = cat_Products::getShortHyperlink($rec->productId, true);
        if (!empty($rec->note)) {
            $notes = $mvc->getFieldType('note')->toVerbal($rec->note);
            $row->productId .= "<br><span class='small'>{$notes}</span>";
        }

        $row->_rowTools->addLink('Палети', array('rack_Pallets', 'productId' => $rec->productId), "id=search{$rec->id},ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт");
        $row->movement = $mvc->getMovementDescription($rec, false, $makeLinks);

        if($fields['-inline'] && isset($rec->workerId)){
            $row->workerId = core_Users::getVerbal($rec->workerId, 'nick');
        }

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
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $productName = ' ' . plg_Search::normalizeText(cat_Products::getTitleById($rec->productId));
        $productName .= " {$rec->productId}";

        $res = ' ' . $res . ' ' . $productName;
    }


    /**
     * Подробно описание на движението
     *
     * @param stdClass $rec
     * @param stdClass $skipZones
     * @param stdClass $makeLinks
     *
     * @return string $res
     */
    protected function getMovementDescription($rec, $skipZones = false, $makeLinks = true)
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
                    $zoneTitle = rack_Zones::styleZone($zoneRec->zone, $zoneTitle, 'zoneMovement');
                    if($makeLinks){
                        $zoneTitle = ht::createLink($zoneTitle, rack_Zones::getUrlArr($zoneRec->zone));
                    }
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
     * Помощна ф-я обръщаща зоните в подходящ вид и събира общото количество по тях
     *
     * @param stdClass $rec
     * @param float    $quantityInZones
     *
     * @return array $zoneArr
     */
    public static function getZoneArr($rec, &$quantityInZones = null)
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
     * След обработка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'waiting' THEN 2 WHEN 'active' THEN 3 ELSE 4 END)");
        if ($palletId = Request::get('palletId', 'int')) {
            $data->query->where("#palletId = {$palletId}");
        }

        $data->listFilter->setFieldTypeParams('workerId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->setField('fromIncomingDocument', 'input=none');
        $data->listFilter->FLD('from', 'date');
        $data->listFilter->FLD('to', 'date');
        $data->listFilter->FNC('documentHnd', 'varchar', 'placeholder=Документ,caption=Документ,input,silent,recently');
        $data->listFilter->FLD('state1', 'enum(,pending=Чакащи,waiting=Запазени,active=Активни,closed=Приключени)', 'placeholder=Всички');

        $data->listFilter->showFields = 'selectPeriod,workerId,search,documentHnd,state1';
        $data->listFilter->input();
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if ($filterRec = $data->listFilter->rec) {
            if (in_array($filterRec->state1, array('active', 'closed', 'pending', 'waiting'))) {
                $data->query->where("#state = '{$filterRec->state1}'");
            }

            if(!empty($filterRec->from)){
                $data->query->where("#createdOn >= '{$filterRec->from} 00:00:00'");
            }

            if(!empty($filterRec->to)){
                $data->query->where("#createdOn <= '{$filterRec->to} 23:59:59'");
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
        if (in_array($action, array('start', 'reject', 'load', 'unload'))) {
            $requiredRoles = $mvc->getRequiredRoles('toggle', $rec, $userId);
        }

        if($action == 'start' && isset($rec->state)){
            if(!in_array($rec->state, array('pending', 'waiting'))){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'load' && isset($rec->state)){
            if($rec->state != 'pending'){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'unload' && isset($rec->state)){
            if($rec->state != 'waiting'){
                $requiredRoles = 'no_one';
            }

            if($rec->workerId != $userId){
                if(!haveRole('rackMaster')){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'reject' && isset($rec->state)){
            if($rec->state != 'active'){
                $requiredRoles = 'no_one';
            } elseif(isset($rec->workerId) && $rec->workerId != $userId){
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
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FLD('movement', 'varchar', 'tdClass=movement-description');
        if(!$data->inlineMovement){
            $data->listTableMvc->FLD('leftColBtns', 'varchar', 'tdClass=centered');
            $data->listTableMvc->FLD('rightColBtns', 'varchar', 'tdClass=centered');
            $data->listTableMvc->setField('workerId', 'tdClass=centered');
        } else {
            $data->listTableMvc->FLD('leftColBtns', 'varchar', 'tdClass=terminalLeftBtnsCol');
            $data->listTableMvc->FLD('rightColBtns', 'varchar', 'tdClass=terminalRightBtnsCol');
            $data->listTableMvc->setField('workerId', 'tdClass=terminalWorkerCol');
        }

        if (Mode::is('screenMode', 'narrow') && array_key_exists('productId', $data->listFields)) {
            $data->listTableMvc->tableRowTpl = "[#ADD_ROWS#][#ROW#]\n";
            $data->listFields['productId'] = '@Артикул';
        }
    }
}