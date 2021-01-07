<?php


/**
 * Клас 'store_StockPlanning' за хоризонти на планиране
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    public $listFields = 'id,productId,genericProductId,storeId,date,quantityIn,quantityOut,sourceId=Източник->Основен,reffId=Източник->Допълнителен,threadId=Нишка,createdOn';


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
        $this->FLD('threadId', 'int', 'caption=Източник->Нишка');

        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('sourceClassId,sourceId');
        $this->setDbIndex('threadId');
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
        $threadId = (cls::haveInterface('doc_DocumentIntf', $Class)) ? $Class->fetchField($id, 'threadId') : null;

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

        cls::get('store_Setup')->migratePendings();

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
        $today = dt::today();
        $today = "{$today} 00:00:00";

        // Каква ще е наличността към датата
        $query = static::getQuery();
        $query->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $query->XPR('totalOut', 'double', "ROUND(SUM(COALESCE(#quantityOut, 0)), 4)");
        $query->XPR('totalIn', 'double', "ROUND(SUM(COALESCE(#quantityIn, 0)), 4)");
        $query->where("#date >= '{$today}' AND #date <= '{$date}' AND #generic = 'no'");
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
            $res[$rec->storeId][$rec->productId] = (object)array('productId' => $rec->productId, 'storeId' => $rec->storeId, 'reserved' => $rec->totalOut, 'expected' => $rec->totalIn);
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
        $today = dt::today();

        // Сумиране на всички сегашни и бъдещи запазени/очаквани количества по дата
        $query->XPR("shortDate", 'date', "DATE(#date)");
        $query->XPR("quantityOutTotal", 'double', "ROUND(SUM(COALESCE(#quantityOut, 0)), 4)");
        $query->XPR("quantityInTotal", 'double', "ROUND(SUM(COALESCE(#quantityIn, 0)), 4)");
        $query->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $query->where("#generic = 'no' AND #storeId IS NOT NULL AND #shortDate >= CURDATE()");
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

            // Обхождат се датите за, които има запланувани движение
            foreach ($datesObj as $date => $obj){
                $sumReserved = $sumExpected = 0;

                // Наличността към датата е сумата от предходните дати
                $clone = clone $obj;
                array_walk($allRecs[$key], function($a, $k) use (&$clone, $date) {
                    if($k < $date){
                        $clone->reserved += $a->reserved;
                        $clone->expected += $a->expected;
                    }
                });

                // Колко ще е запазеното - очакваното
                $total = round($obj->reserved - $obj->expected, 4);

                // Намиране на датата, на която ще е максимално запазеното - очакваното
                if(is_null($max) || round($max, 4) < $total){
                    $max = $total;
                    $res[$obj->storeId][$obj->productId] = (object)array('date' => $date, 'reserved' => $clone->reserved, 'expected' => $clone->expected, 'storeId' => $clone->storeId, 'productId' => $clone->productId);
                }
            }
        }

        return $res;
    }


    function act_Test()
    {
        $storeId = 21;
        $date = null;
        $productId = 4328;//1330
        $date = '2021-02-06';

        $r = store_Products::getRec($productId, $storeId, $date);

        bp($r);
    }
}

