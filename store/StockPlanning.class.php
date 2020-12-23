<?php


/**
 * Клас 'store_StockPlanning' за хоризонти на планиране
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
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
    public $listFields = 'productId,genericProductId,storeId,date,quantityIn,quantityOut,sourceId=Източник,threadId=Нишка,createdOn';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore)', 'caption=Артикул,tdClass=leftAlign');
        $this->FLD('genericProductId', 'key(mvc=cat_Products,select=name)', 'caption=Генеричен,tdClass=leftAlign');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,tdClass=storeCol leftAlign');
        $this->FLD('date', 'datetime', 'caption=Дата');
        $this->FLD('quantityIn', 'double(maxDecimals=3)', 'caption=Количество->Влиза');
        $this->FLD('quantityOut', 'double(maxDecimals=3)', 'caption=Количество->Излиза');
        $this->FLD('sourceClassId', 'class(interface=store_StockPlanningIntf,select=title,allowEmpty)', 'caption=Източник->Клас');
        $this->FLD('sourceId', 'int', 'caption=Източник->Ид,tdClass=leftCol');
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
        $synced = arr::syncArrays($plannedStocks, $exRecs, 'genericProductId,productId,storeId,sourceClassId,sourceId', 'date,quantityIn,quantityOut');

        if(countR($synced['insert'])){
            $Stocks->saveArray($synced['insert']);
        }

        if(countR($synced['update'])){
            $Stocks->saveArray($synced['update'], 'id,date,quantityIn,quantityOut');
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
        $data->listFilter->showFields = 'productId,threadId,sourceClassId';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->productId)) {
                $data->query->where("#productId = {$rec->productId}");
            }

            if (!empty($rec->sourceClassId)) {
                $data->query->where("#sourceClassId = {$rec->sourceClassId}");
            }

            if (!empty($rec->threadId)) {
                $data->query->where("#threadId = {$rec->threadId}");
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

function act_Test()
{
    requireRole('debug');

    $stores = array();
    $date = dt::today();
    //$productId = 27;
    $r = self::getReservedQuantity($date, $productId);
}
    public static function getReservedQuantity($date, $productIds, $stores = null)
    {
        if(strlen($date) == 10){
            $from = "{$date} 00:00:00";
            $to = "{$date} 23:59:59";
        } else {
            $from = explode(" ", $date);
            $from = "{$from[0]} 00:00:00";
            $to = $date;
        }

        $productArr = arr::make($productIds, true);
        $storesArr = isset($stores) ? arr::make($stores, true) : null;

        $query = static::getQuery();
        $query->XPR('totalOut', 'double', "ROUND(SUM(COALESCE(#quantityOut, 0)), 4)");
        $query->XPR('totalIn', 'double', "ROUND(SUM(COALESCE(#quantityIn, 0)), 4)");
        $query->where("#date BETWEEN '{$from}' AND '{$to}'");
        $query->groupBy('productId');
        $query->show('productId,totalOut,totalIn');

        if(countR($productArr)){
            $query->in("productId", $productArr);
        }

        if(isset($stores) && countR($storesArr)){
            $query->in("storeId", $storesArr);
        }

        $res = array();
        while($rec = $query->fetch()){
            $res[$rec->productId] = (object)array('reserved' => $rec->totalOut, 'expected' => $rec->totalIn);
        }

        return $res;
    }
}

