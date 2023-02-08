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
    public $searchFields = 'palletId,position,positionTo,note,batch';


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
        $mvc->FLD('workerId', 'user(roles=ceo|rack, rolesForTeams=officer|manager|ceo|storeAll, rolesForAll=ceo|storeAllGlobal,allowEmpty)', 'caption=Движение->Изпълнител,tdClass=nowrap');

        $mvc->FLD('note', 'varchar(64)', 'caption=Движение->Забележка,column=none');
        $mvc->FLD('state', 'enum(pending=Чакащо, waiting=Запазено, active=Активно, closed=Приключено)', 'caption=Движение->Състояние,silent');
        $mvc->FLD('brState', 'enum(pending=Чакащо, waiting=Запазено, active=Активно, closed=Приключено)', 'caption=Движение->Състояние,silent,input=none');
        $mvc->FLD('zoneList', 'keylist(mvc=rack_Zones, select=num)', 'caption=Зони,input=none');
        $mvc->FLD('fromIncomingDocument', 'enum(no,yes)', 'input=hidden,silent,notNull,value=no');
        $mvc->FNC('containerId', 'int', 'input=hidden,caption=Документи,silent');
        $mvc->FLD('documents', 'keylist(mvc=doc_Containers,select=id)', 'input=none,caption=Документи');
        $mvc->FNC('maxPackQuantity', 'double', 'silent,input=hidden');

        $mvc->FLD('canceledOn', 'datetime(format=smartTime)', 'caption=Върнато||Returned->На, input=none');
        $mvc->FLD('canceledBy', 'key(mvc=core_Users)', 'caption=Върнато||Returned->От||By, input=none');
        $mvc->FLD('packagings', 'blob(serialize,compress)', 'caption=Опаковки,column=none,single=none,input=none');

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

        if(isset($rec->canceledBy) && !empty($rec->canceledOn)){
            $dateVerbal = core_Type::getByName('datetime(smartTime)')->toVerbal($rec->canceledOn);
            $userIdVerbal = crm_Profiles::createLink($rec->canceledBy);

            if(isset($fields['-inline'])){
                $row->movement = ht::createHint($row->movement, "|*{$userIdVerbal} |върна движение|* №{$rec->id} |на|* {$dateVerbal}", null,true, array('src' => 'img/16/cart_go_back.png', 'style'=> 'background-color:rgba(173, 62, 42, 0.8);padding:4px;border-radius:2px;display: inline-block;', 'height' => 18, 'width' => 18));
            } else {
                $row->productId = ht::createHint($row->productId, "|*{$userIdVerbal} |върна движение|* №{$rec->id} |на|* {$dateVerbal}",  null,true, array('src' => 'img/16/cart_go_back.png', 'style'=> 'background-color:rgba(173, 62, 42, 0.8);padding:4px;border-radius:2px;display: inline-block;', 'height' => 18, 'width' => 18));
            }
        }

        if(!$fields['-inline'] && !$fields['-inline-single']){
            if($Def = batch_Defs::getBatchDef($rec->productId)){
                if(!empty($rec->batch)){
                    $row->batch = $Def->toVerbal($rec->batch);
                } else {
                    $row->batch = "<i class='quiet'>" . tr("Без партида") . "</i>";
                }
            }
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
    public function getMovementDescription($rec, $skipZones = false, $makeLinks = true)
    {
        $rec = $this->fetchRec($rec);
        $position = $this->getFieldType('position')->toVerbal($rec->position);
        $positionTo = $this->getFieldType('positionTo')->toVerbal($rec->positionTo);
        $Double = core_Type::getByName('double(smartRound)');

        $class = '';
        if ($palletId = cat_UoM::fetchBySinonim('pallet')->id) {
            if ($palletRec = cat_products_Packagings::getPack($rec->productId, $palletId)) {
                if ($rec->quantity == $palletRec->quantity) {
                    $class = "class = 'quiet'";
                }
            }
        }

        $movementArr = $quantities = array();
        $quantities['from'] = (object)array('quantity' => round($rec->quantity, 6), 'position' => $position, 'class' => $class);

        if ($skipZones === false) {
            $quantityInZones = 0;
            $zones = self::getZoneArr($rec, $quantityInZones);

            $quantityInZones *= $rec->quantityInPack;
            $restQuantity = round($rec->quantity, 9) - round($quantityInZones, 9);

            $zoneQuantities = array();
            foreach ($zones as $zoneRec) {
                $class = ($rec->state == 'active') ? "class='movement-position-notice'" : "";
                if($zRec = rack_Zones::fetch($zoneRec->zone, 'id,num')){
                    $num = $zRec->num;
                    $zoneTitle = rack_Zones::getDisplayZone($zoneRec->zone, false, false);
                    if($makeLinks){
                        $zoneTitle = ht::createLink($zoneTitle, rack_Zones::getUrlArr($zoneRec->zone));
                    }
                } else {
                    $num = $zoneRec->zone;
                    $zoneTitle = ht::createHint($zoneRec->zone, 'Зоната вече не съществува', 'warning');
                }
                $zoneQuantities[$zoneRec->zone] = (object)array('quantity' => round($zoneRec->quantity * $rec->quantityInPack, 6), 'position' => $zoneTitle, 'class' => $class, 'num' => $num);
            }

            arr::sortObjects($zoneQuantities, 'num', 'ASC');
            $quantities += $zoneQuantities;

            if (!empty($positionTo) && round($restQuantity, 6)) {
                if($rec->positionTo != $rec->position){
                    $positionTo = "<span class='differentReturnPosition'>{$positionTo}</span>";
                }

                $quantities['to'] = (object)array('quantity' => $restQuantity, 'position' => $positionTo, 'class' => $class);
            }
        }

        foreach ($quantities as $k => $a){
            if(empty($a->quantity) && $k == 'from') continue;

            if(is_array($rec->packagings)){
                $convertedQuantity = static::getSmartPackagings($rec->productId, $rec->packagings, $a->quantity, $rec->packagingId);
                if(isset($convertedQuantity)){
                    $movementArr[$k] = "{$a->position} (<span {$a->class}>{$convertedQuantity}</span>)";
                }
            }

            if(!array_key_exists($k, $movementArr)){
                $packQuantity = $a->quantity / $rec->quantityInPack;
                $packQuantity = core_Math::roundNumber($packQuantity);
                $packQuantityVerbal = $Double->toVerbal($packQuantity);
                $packQuantityVerbal = ht::styleIfNegative($packQuantityVerbal, $packQuantity);
                $packDisplay = tr(cat_UoM::getSmartName($rec->packagingId, $packQuantity));
                $packQuantityVerbal = "{$packQuantityVerbal} {$packDisplay}";
                $movementArr[$k] = "{$a->position} (<span {$a->class}>{$packQuantityVerbal}</span>)";
            }
        }

        if($rec->state == 'pending' && isset($movementArr['from'])){

            $movementArr['from'] = "<span class='movement-position-notice'>{$movementArr['from']}</span>";
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
        $data->title = 'Движения на палети в склад |*<b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';

        $data->query->where("#storeId = {$storeId}");
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'waiting' THEN 2 WHEN 'active' THEN 3 ELSE 4 END)");

        if ($palletId = Request::get('palletId', 'int')) {
            $data->query->where("#palletId = {$palletId}");
        }

        $data->listFilter->setFieldTypeParams('workerId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->setField('fromIncomingDocument', 'input=none');
        $data->listFilter->setField('workerId', 'caption=Товарач,after=to');
        $data->listFilter->FLD('from', 'date', 'caption=От');
        $data->listFilter->FLD('to', 'date', 'caption=До');
        $data->listFilter->FNC('documentHnd', 'varchar', 'placeholder=Документ,caption=Документ,input,silent,recently');
        $data->listFilter->FLD('state1', 'enum(all=Всички,pending=Чакащи,waiting=Запазени,active=Активни,closed=Приключени)', 'caption=Състояние');
        $data->listFilter->input('documentHnd', 'silent');

        $data->listFilter->showFields = 'selectPeriod, from, to, workerId,search,documentHnd,state1';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));

        $data->listFilter->input();
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

        arr::placeInAssocArray($data->listFields, array('batch' => 'Партида'), null, 'productId');
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


    /**
     * Връща умно показване на опаковките
     *
     * @param int $productId
     * @param array $packagingArr
     * @param int $quantity
     * @param int|null $preferPackagingIdIFThereAreSimilar
     * @return string|null $string
     */
    protected static function getSmartPackagings($productId, $packagingArr, $quantity, $preferPackagingIdIFThereAreSimilar = null)
    {
        $sign = ($quantity < 0) ? -1 : 1;
        $quantity = abs($quantity);

        // Кои опаковки са с по-малко количество от нужното
        $packs = array_filter($packagingArr, function($a) use ($quantity) {return $a['quantity'] <= $quantity;});
        if(!countR($packs)) return null;

        // Подобрено сортиране
        uasort($packs, function (&$a, &$b)  {
            if ($a['quantity'] == $b['quantity']) { return $a['id'] > $b['id'] ? 1 : -1;}

            return ($a['quantity'] > $b['quantity']) ? -1 : 1;
        });

        $packs = array_values($packs);
        $originalPacks = $packs;

        // Коя е най-малката опаковка
        $packsByNow = array();
        end($packs);
        $lastElementKey = key($packs);
        $lastElement = $packs[$lastElementKey];
        reset($packs);

        do {
            $first = $packs[key($packs)];
            $inPack = floor(round($quantity / $first['quantity'], 6));
            $remaining = round($quantity - ($inPack * $first['quantity']), 6);
            unset($packs[key($packs)]);
            $quantity = $remaining;
            if(empty($inPack)) continue;

            $similarArr = array();
            array_walk($originalPacks, function($a) use(&$similarArr, $first) {if($a['quantity'] == $first['quantity']) {$similarArr[$a['packagingId']] = $a['packagingId'];}});
            $packsByNow[] = array('packagingId' => $first['packagingId'], 'quantity' => $inPack, 'similarPacks' => $similarArr);
        } while($remaining >= $lastElement['quantity'] && countR($packs));

        // Ако има остатък се показва и тях в основна мярка
        if($remaining) {
            $remaining = round($remaining, 6);
            $productMeasureId = cat_Products::fetchField($productId, 'measureId');
            $remaining = cat_Uom::round($productMeasureId, $remaining);
            $packsByNow[] = array('packagingId' => $productMeasureId, 'quantity' => $remaining, 'similarPacks' => array());
        }

        // Показване на опаковките
        $string = '';
        foreach ($packsByNow as $p){
            $p['quantity'] = $sign * $p['quantity'];
            $quantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($p['quantity']);
            $quantityVerbal = ht::styleIfNegative($quantityVerbal, $p['quantity']);

            // Ако има опаковки със същото к-во ще се показват и тях освен ако не се предпочита конкретна
            $displayPackNamesArr = array($p['packagingId'] => $p['packagingId']);
            $displayPackNamesArr += $p['similarPacks'];
            if(isset($preferPackagingIdIFThereAreSimilar) && array_key_exists($preferPackagingIdIFThereAreSimilar, $displayPackNamesArr)){
                $displayPackNamesArr = array($preferPackagingIdIFThereAreSimilar => $displayPackNamesArr[$preferPackagingIdIFThereAreSimilar]);
            }

            $displayStringArr = array();
            foreach ($displayPackNamesArr as $packId){
                $displayStringArr[] = tr(cat_UoM::getSmartName($packId, $p['quantity']));
            }
            $displayString = implode('/', $displayStringArr);
            $plus = ($sign < 0) ? "&nbsp;" : "&nbsp;+&nbsp;";
            if(Mode::is('text', 'plain')){
                $plus = ($sign < 0) ? " " : " + ";
            }

            $string .= (!empty($string) ? $plus : "") . "{$quantityVerbal} {$displayString}";
        }

        return $string;
    }
}