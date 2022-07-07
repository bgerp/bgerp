<?php


/**
 * Клас 'store_StockPlanning' за хоризонти на планиране
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_StockPlanning extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = 'Хоризонти';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,productId,genericProductId,storeId,date,quantityOut,quantityIn,sourceId=Източник->Основен,reffId=Източник->Допълнителен,state=Източник->Състояние,threadId=Източник->Нишка,createdOn';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore)', 'caption=Артикул,tdClass=leftAlign');
        $this->FLD('genericProductId', 'key(mvc=cat_Products,select=name)', 'caption=Генеричен,tdClass=leftAlign');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,tdClass=storeCol leftAlign');
        $this->FLD('date', 'datetime', 'caption=Дата');
        $this->FLD('quantityIn', 'double', 'caption=Количество->Влиза');
        $this->FLD('quantityOut', 'double', 'caption=Количество->Излиза');
        $this->FLD('sourceClassId', 'class(interface=store_StockPlanningIntf,select=title,allowEmpty)', 'caption=Източник->Клас');
        $this->FLD('sourceId', 'int', 'caption=Източник->Ид,tdClass=leftCol');
        $this->FLD('reffClassId', 'class', 'caption=Втори източник->Клас,tdClass=leftCol');
        $this->FLD('reffId', 'key(mvc=doc_Containers,select=id)', 'caption=Втори източник->Ид,tdClass=leftCol');
        $this->FLD('threadId', 'int', 'caption=Източник->Нишка,silent');
        $this->FNC('state', 'enum(draft=Чернова, active=Активиран, waiting=Чакащи, pending=Заявка,rejected=Оттеглен, closed=Приключен, stopped=Спрян, wakeup=Събуден)', 'caption=Състояние, input=none');

        $this->setDbIndex('reffClassId,reffId');
        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('sourceClassId,sourceId');
        $this->setDbIndex('threadId');
        $this->setDbIndex('productId');
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
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        if(isset($rec->genericProductId)){
            $row->genericProductId = cat_Products::getHyperlink($rec->genericProductId, true);
        }

        if(isset($rec->storeId)){
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }

        $Source = cls::get($rec->sourceClassId);
        $row->sourceId = $Source->hasPlugin('doc_DocumentPlg') ? $Source->getLink($rec->sourceId, 0) : $Source->getHyperlink($rec->sourceId, true);
        $state = $Source->fetchField($rec->sourceId, 'state');
        $row->state = $mvc->getFieldType('state')->toVerbal($state);
        $row->ROW_ATTR['class'] = "state-{$state}";

        if(isset($rec->reffClassId) && isset($rec->reffId)){
            $SecondSource = cls::get($rec->reffClassId);
            $row->reffId = $SecondSource->hasPlugin('doc_DocumentPlg') ? $SecondSource->getLink($rec->reffId, 0) : $SecondSource->getHyperlink($rec->reffId, true);
        }
    }


    /**
     * Премахва всички записи от дадения документ
     *
     * @param $class
     * @param $id
     * @return void
     */
    public static function remove($class, $id)
    {
        $Class = cls::get($class);
        self::delete("#sourceClassId = {$Class->getClassId()} AND #sourceId = {$id}");
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли всички', array($mvc, 'recalcAll', 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване на запазеното по сделки');
        }
    }


    /**
     * Добавя статичните данни към масива с планираните количества
     *
     * @param array $array
     * @param mixed $class
     * @param int $id
     */
    public static function addStaticValuesToStockArr(&$array, $class, $id)
    {
        if(!countR($array)) return;

        // Добавяне на документа и датата на създаване
        $Class = cls::get($class);
        $classId = $Class->getClassId();
        $now = dt::now();

        $threadId = null;
        if(cls::haveInterface('doc_DocumentIntf', $Class)){
            $sourceRec = $Class->fetch($id, 'threadId');
            $threadId = $sourceRec->threadId;
        }

        array_walk($array, function($a) use ($now, $classId, $threadId, $id) {$a->createdOn = $now; $a->sourceClassId = $classId; $a->sourceId = $id; $a->threadId = $threadId;});
    }


    /**
     * Обновява запазените/очакваните наличности по документ
     *
     * @param mixed $classId
     * @param int|stdClass $objectId
     * @return void
     */
    public static function updateByDocument($classId, $objectId)
    {
        $Class = cls::get($classId);

        // Какви са наличните записи на документа
        $exQuery =  static::getQuery();
        $exQuery->where("#sourceClassId = {$Class->getClassId()} AND #sourceId = {$objectId}");
        $exRecs = $exQuery->fetchAll();

        // Какви ще са новите планирани количества
        $plannedStocks = $Class->getPlannedStocks($objectId);
        static::addStaticValuesToStockArr($plannedStocks, $Class, $objectId);

        // Синхронизиране на старите със новите записи
        $Stocks = cls::get('store_StockPlanning');
        $synced = arr::syncArrays($plannedStocks, $exRecs, 'genericProductId,productId,storeId,sourceClassId,sourceId', 'date,quantityIn,quantityOut,reffClassId,reffId');

        if(countR($synced['insert'])){
            $Stocks->saveArray($synced['insert']);
        }

        if(countR($synced['update'])){
            $Stocks->saveArray($synced['update'], 'id,date,quantityIn,quantityOut,reffClassId,reffId');
        }

        if(countR($synced['delete'])){
            $deleteIds = implode(',', $synced['delete']);
            $Stocks->delete("#id IN ({$deleteIds})");
        }
    }


    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->input(null, 'silent');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'date,productId,storeId,threadId,sourceClassId';
        $data->listFilter->input();
        $data->listFilter->setFieldType('date', 'date');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->productId)) {
                $data->query->where("#productId = {$rec->productId}");
            }

            if (!empty($rec->storeId)) {
                $data->query->where("#storeId = {$rec->storeId}");
            }

            if (!empty($rec->sourceClassId)) {
                $data->query->where("#sourceClassId = {$rec->sourceClassId}");
            }

            if (!empty($rec->threadId)) {
                $data->query->where("#threadId = {$rec->threadId}");
            }

            if (!empty($rec->date)) {
                $end = str_replace('00:00:00', '23:59:59', $rec->date);
                $data->query->where("#date BETWEEN '{$rec->date}' AND '{$end}'");
            }
        }

        // Сортиране на записите по num
        $data->query->orderBy('id', "DESC");
    }


    /**
     * Изчислява всички очаквани/запазени к-та от нулата
     */
    function act_recalcAll()
    {
        requireRole('debug');
        $this->recalcPlannedStocks();
        followRetUrl();
    }


    /**
     * Рекалкулира по референтен клас
     *
     * @param mixed $reffClassId - референтен клас
     * @param int $reffId        - ид на референтния обект
     * @return void
     */
    public static function recalcByReff($reffClassId, $reffId)
    {
        $ReffClass =  cls::get($reffClassId);
        $query = static::getQuery();
        $query->where("#reffClassId = {$ReffClass->getClassId()} AND #reffId = {$reffId}");
        $query->groupBy('sourceClassId,sourceId');
        $query->show('sourceClassId,sourceId');

        while($rec = $query->fetch()){
            store_StockPlanning::updateByDocument($rec->sourceClassId, $rec->sourceId);
        }
    }


    /**
     * Какви ще са планираните наличности към датата
     *
     * @param $date                  - към коя дата
     * @param null|array $productIds - ид на артикули
     * @param null|array $stores     - списък със складове, null за всички
     * @return array $res            - колко ще е запазеното и очакваното към датата
     */
    public static function getPlannedQuantities($date, $productIds = null, $stores = null)
    {
        // Ако датата е без час, ще се приеме, че е за докрая на дена
        if(strlen($date) == 10){
            $date = "{$date} 23:59:59";
        }

        $productArr = arr::make($productIds, true);
        $storesArr = isset($stores) ? arr::make($stores, true) : null;

        // Каква ще е наличността към датата
        $query = static::getQuery();
        $query->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $query->XPR('totalOut', 'double', "SUM(COALESCE(#quantityOut, 0))");
        $query->XPR('totalIn', 'double', "SUM(COALESCE(#quantityIn, 0))");
        $query->where("#date <= '{$date}' AND #generic = 'no'");
        $query->groupBy('productId,storeId');
        $query->show('productId,totalOut,totalIn,storeId');

        if(countR($productArr)){
            $query->in("productId", $productArr);
        }

        if(isset($stores) && countR($storesArr)){
            $query->in("storeId", $storesArr);
        }

        $res = array();
        while($rec = $query->fetch()){
            $res[$rec->storeId][$rec->productId] = (object)array('productId' => $rec->productId, 'storeId' => $rec->storeId, 'reserved' => round($rec->totalOut, 4), 'expected' => round($rec->totalIn, 4));
        }

        return $res;
    }


    /**
     * Връща сумарно количествата, на коя дата артикул+склад ще е с най-много запазено количество
     *
     * @return array $res
     */
    public static function getMaxReservedByProduct()
    {
        $query = static::getQuery();

        // Сумиране на всички сегашни и бъдещи запазени/очаквани количества по дата
        // Ако записа е с минала дата, приема се че е текущата
        $query->XPR("shortDate", 'date', "(CASE WHEN DATE(#date) >= CURDATE() THEN DATE(#date) ELSE CURDATE() END)");
        $query->XPR("quantityOutTotal", 'double', "ROUND(SUM(COALESCE(#quantityOut, 0)), 4)");
        $query->XPR("quantityInTotal", 'double', "ROUND(SUM(COALESCE(#quantityIn, 0)), 4)");
        $query->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $query->where("#generic = 'no' AND #storeId IS NOT NULL");
        $query->show('productId,storeId,shortDate,quantityInTotal,quantityOutTotal');
        $query->groupBy('storeId,productId,shortDate');
        $query->orderBy('shortDate', 'ASC');

        $allRecs = $res = array();
        while($rec = $query->fetch()){
            $allRecs["{$rec->storeId}|{$rec->productId}"][$rec->shortDate] = (object)array('productId' => $rec->productId, 'storeId' => $rec->storeId, 'reserved' => $rec->quantityOutTotal, 'expected' => $rec->quantityInTotal);
        }

        // За всеки запис
        foreach ($allRecs as $key => $datesObj){
            $max = null;

            // Обхождат се датите за, които има запланувани движения
            foreach ($datesObj as $date => $obj){

                // Наличността към датата е сумата от предходните дати
                $clone = clone $obj;
                array_walk($allRecs[$key], function($a, $k) use (&$clone, $date) {
                    if($k < $date){
                        $clone->reserved += $a->reserved;
                        $clone->expected += $a->expected;
                    }
                });

                // Колко ще е запазеното - очакваното (към вече сумираното за датата)
                $total = round($clone->reserved - $clone->expected, 4);

                // Намиране на датата, на която ще е максимално запазеното - очакваното
                if(is_null($max) || round($max, 4) < $total){
                    $max = $total;
                    $res[$obj->storeId][$obj->productId] = (object)array('date' => $date, 'reserved' => $clone->reserved, 'expected' => $clone->expected, 'storeId' => $clone->storeId, 'productId' => $clone->productId);
                }
            }
        }

        return $res;
    }


    /**
     * Първоначално наливане на запазените количества
     */
    private function recalcPlannedStocks()
    {
        // Ако не е имало складови движения, не се прави нищо
        if(!store_Products::count()) return;

        $Stocks = cls::get('store_StockPlanning');
        $Stocks->truncate();

        // Кои документи запазват на заявка
        $stockableClasses = array('store_ShipmentOrders',
            'store_Receipts',
            'store_Transfers',
            'store_ConsignmentProtocols',
            'planning_ConsumptionNotes',
            'planning_DirectProductionNote',
            'pos_Receipts');

        // Записват се запазените количества
        $stocksArr = array();
        foreach ($stockableClasses as $cls){
            $Source = cls::get($cls);
            $Source->setupMvc();

            $query = $Source->getQuery();
            $query->in("state", $Source->updatePlannedStockOnChangeStates);
            $count = $query->count();
            core_App::setTimeLimit(0.6 * $count, false,300);

            while($rec = $query->fetch()){
                $arr = $Source->getPlannedStocks($rec);
                store_StockPlanning::addStaticValuesToStockArr($arr, $Source, $rec->id);
                $stocksArr = array_merge($stocksArr, $arr);
            }
        }

        // Записване на запазеното на индивидуланите количества
        $Stocks->saveArray($stocksArr);

        // Преизчисляване на запазеното по сделки и запазени.
        $dealsArr = array();
        $stockableOriginClasses = array('sales_Sales', 'purchase_Purchases', 'planning_Jobs');
        foreach ($stockableOriginClasses as $cls) {
            $Source = cls::get($cls);
            $Source->setupMvc();

            $query = $Source->getQuery();
            $query->in("state", $Source->updatePlannedStockOnChangeStates);
            $count = $query->count();
            core_App::setTimeLimit(0.7 * $count, false,300);

            while ($rec = $query->fetch()) {
                $arr = $Source->getPlannedStocks($rec);
                store_StockPlanning::addStaticValuesToStockArr($arr, $Source, $rec->id);
                $dealsArr = array_merge($dealsArr, $arr);
            }
        }

        $Stocks->saveArray($dealsArr);
    }


    /**
     * Коя е най-ранната дата, на която са разполагаеми всички количества на посочените артикули
     *
     * @param int $storeId          - ид на склад
     * @param array $products       - масив от търсените наличностти ['productId' => 'quantity']
     * @param int|null $daysForward - колко дни напред да се търси
     * @return null|date            - най-ранната дата на която са налични или null ако няма
     */
    public static function getEarliestDateAllAreAvailable($storeId, $products, $daysForward = null)
    {
        $productIds = array_keys($products);
        if(!countR($products)) return;
        $daysForward = isset($daysForward) ? $daysForward : store_Setup::get('EARLIEST_SHIPMENT_READY_IN');

        // Коя е крайната дата до която най-късно ще се гледа
        $today = dt::today();
        $endDate = dt::verbal2mysql(dt::addSecs($daysForward * 24 * 3600, $today), false);

        // Извличат се еднократно всички текущи наличности на търсените артикули в търсения склад
        $inStockArr = array();
        $sQuery = store_Products::getQuery();
        $sQuery->where("#storeId = '{$storeId}'");
        $sQuery->in("productId", $productIds);
        $sQuery->show('productId,quantity');
        while($sRec = $sQuery->fetch()){
            $inStockArr[$sRec->productId] = $sRec->quantity;
        }

        // Извличат се от хоризонтите движенията за посочените артикули в посочения склад до желаната крайна дата
        $plannedArr = array();
        $query = static::getQuery();
        $query->XPR("shortDate", 'date', "(CASE WHEN DATE(#date) >= CURDATE() THEN DATE(#date) ELSE CURDATE() END)");
        $query->XPR("quantityMove", 'double', "ROUND(SUM(COALESCE(#quantityIn, 0)), 4) - ROUND(SUM(COALESCE(#quantityOut, 0)), 4)");
        $query->where("#storeId = '{$storeId}' && #shortDate >= '{$today}' AND #shortDate <= '{$endDate}'");
        $query->in("productId", $productIds);
        $query->show('quantityMove,shortDate,productId');
        $query->groupBy('storeId,productId,shortDate');

        // Групиране за всеки артикул на коя дата колко ще е общо движението: влязло - излязло
        while($rec = $query->fetch()){
            $plannedArr[$rec->productId][$rec->shortDate] = $rec->quantityMove;
        }

        // Ще се обикаля за всички дати от днеска до $daysForward дни напред
        $currentDate = $today;
        $countNeeded = countR($products);
        do {
            $ok = 0;

            // За всеки артикул с нужно количество
            foreach ($products as $productId => $neededQuantity){

                // Колко е първоначално наличното
                $finalQuantity = isset($inStockArr[$productId]) ? $inStockArr[$productId] : 0;

                // Ако има записи в хоризонтите се сумират движенията до текущата дата
                if(is_array($plannedArr[$productId])){
                    array_walk($plannedArr[$productId], function($totalMovement, $date) use ($currentDate, &$finalQuantity) {

                        // Ако датата е преди текущата добавя се към първоначалното налично
                        if($date <= $currentDate){
                            $finalQuantity += $totalMovement;
                        }
                    });
                }

                // Ако крайното разполагаемо удовлетворява нужното количество, отбелязва се че артикула е готов
                if(round($finalQuantity, 4) >= round($neededQuantity, 4)){
                    $ok++;
                }
            }

            // Ако всичките готови артикули са точно толкова колкото се търсят връща се текущата дата на обхождането
            if($ok == $countNeeded) return $currentDate;

            // Обхождането продължава докато не се стигне до крайната дата, или не се излезе от цикъла
            $currentDate = dt::addDays(1, $currentDate, false);
        } while($currentDate <= $endDate);

        // Ако се обходят всички дати и няма удовлетворени количества ще се върне null
        return null;
    }


    /**
     * Връща записите отговарящи на условията
     *
     * @param int         $productId - ид на артикул
     * @param null|array  $stores    - складове или null за всички
     * @param date        $toDate    - към коя дата
     * @param string|null $field     - кое поле или и двете
     * @return array
     */
    public static function getRecs($productId, $stores, $toDate, $field = null)
    {
        $end = (strlen($toDate) == 10) ? "{$toDate} 23:59:59" : $toDate;
        $query = static::getQuery();
        $query->where("#productId = {$productId} AND #date <= '{$end}'");
        if(isset($stores)){
            $query->in('storeId', $stores);
        }

        if($field){
            $quantityField = (strpos($field, 'reserved') !== false) ? 'quantityOut' : 'quantityIn';
            $query->where("#{$quantityField} IS NOT NULL");
        } else {
            $query->where("#quantityOut IS NOT NULL OR #quantityIn IS NOT NULL");
        }

        $query->EXT('measureId', 'cat_Products', 'externalKey=productId');
        $query->show('sourceClassId,sourceId,date,quantityOut,quantityIn,measureId,storeId');

        return $query->fetchAll();
    }
}

