<?php


/**
 * Клас 'price_AverageStorePrices' - за кеширани изчислени средни складови цени
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_AverageStorePrices extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'price_Wrapper,plg_AlignDecimals2,plg_Sorting';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,debug';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може ръчно да обнови модела
     */
    public $canSaveavgprices = 'ceo,debug';
    
    
    /**
     * Заглавие
     */
    public $title = 'Средни складови цени';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Средна складова цена';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,date,quantity,price';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('itemId', 'key(mvc=acc_Items,select=titleLink)', 'caption=Перо');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('date', 'datetime(format=smartTime)', 'caption=Дата');
        $this->FLD('quantity', 'double', 'caption=Количество');
        $this->FLD('price', 'double', 'caption=Цена');
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $stores = keylist::toArray(price_Setup::get('STORE_AVERAGE_PRICES'));
        $titleArr = array();
        foreach ($stores as $storeId){
            $titleArr[] = store_Stores::getTitleById($storeId);
        }
        
        if(countR($titleArr)){
            $data->title = 'Средни складови цени в|*: <b style="color:green">' . implode(', ', $titleArr) . "</b>";
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(isset($rec->productId)){
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('saveavgprices')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'saveAvgPrices', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на стойностите');
        }
    }
    
    
    /**
     * Изчисляване на средните складови цени
     */
    function act_saveAvgPrices()
    {
        $this->requireRightFor('saveavgprices');
        static::saveAvgPrices();
        
        followRetUrl();
    }
    
    
    /**
     * Запис за цените в модела
     */
    private static function saveAvgPrices()
    {
        // Има ли избрани складове за осредняване
        $storesKeylist = price_Setup::get('STORE_AVERAGE_PRICES');
        $storeIds = keylist::toArray($storesKeylist);
        if(!countR($storeIds)){
            
            static::truncate();
            return;
        }
        
        // Ако има, кои са техните пера
        $storeItems = array();
        foreach ($storeIds as $storeId){
            $storeItemId = acc_Items::fetchItem('store_Stores', $storeId)->id;
            $storeItems[$storeItemId] = $storeItemId;
        }
        
        // Има ли стандартни артикули
        $toSave = array();
        $query = cat_Products::getQuery();
        $query->where("#state = 'active'  AND #canStore = 'yes' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
        $query->show('id');
        $publicProductIds = arr::extractValuesFromArray($query->fetchAll(), 'id');
        $count = countR($publicProductIds);
        if(!$count){
            
            return;
        }
        
        core_App::setTimeLimit($count * 0.6, 900);
        
        $map = array();
        $productClassId = cat_Products::getClassId();
        $publicProductIdString = implode(',', $publicProductIds);
        
        // Кои са техните пера?
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#state = 'active' AND #objectId IN ({$publicProductIdString}) AND #classId = {$productClassId} AND #lastUseOn IS NOT NULL");
        $iQuery->show('id,objectId');
        while($iRec = $iQuery->fetch()){
            $map[$iRec->id] = (object)array('id' => $iRec->id, 'productId' => $iRec->objectId);
        }
        
        // Намира се датата на последния им дебит
        $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $jQuery->XPR('maxValior', 'double', 'MAX(#valior)');
        $jQuery->where("#debitAccId = {$storeAccId}");
        $jQuery->show('debitItem2,debitItem1, maxValior');
        $jQuery->in('debitItem2', array_keys($map));
        $jQuery->in('debitItem1', $storeItems);
        $jQuery->groupBy('debitItem2');
        
        while($jRec = $jQuery->fetch()){
            $rec = (object)array('date' => $jRec->maxValior, 'itemId' => $jRec->debitItem2, 'productId' => $map[$jRec->debitItem2]->productId, 'quantity' => 0, 'price' => 0);
            $toSave[$rec->productId] = $rec;
        }
        
        // Сумарното им количество по складовете
        $pQuery = store_Products::getQuery();
        $pQuery->where("#quantity >= 0");
        $pQuery->XPR('sum', 'double', 'SUM(#quantity)');
        $pQuery->in("productId", $publicProductIds);
        $pQuery->in("storeId", $storeIds);
        $pQuery->groupBy('productId,storeId');
        $pQuery->show('sum,productId,storeId');
        
        while($pRec = $pQuery->fetch()){
            if(array_key_exists($pRec->productId, $toSave)){
                $toSave[$pRec->productId]->quantity += $pRec->sum;
            }
        }
        
        // Колко им е средната складова цена
        $date = dt::today();
        foreach ($toSave as $obj){
            $amount = cat_Products::getWacAmountInStore($obj->quantity, $obj->productId, $date, $storeIds);
            if($obj->quantity){
                $obj->price = round($amount / $obj->quantity, 6);
            }
        }
        
        // Синхронизиране на записите
        $query = static::getQuery();
        $exRecs = $query->fetchAll();
        $res = arr::syncArrays($toSave, $exRecs, 'itemId,productId', 'date,quantity,price');
        
        $me = cls::get(get_called_class());
        if (countR($res['insert'])) {
            $me->saveArray($res['insert']);
        }
        
        if (countR($res['update'])) {
            $me->saveArray($res['update'], 'id,date,quantity,price');
        }
        
        if (countR($res['delete'])) {
            $delete = implode(',', $res['delete']);
            $me->delete("#id IN ({$delete})");
        }
    }
    
    
    /**
     * Преизчисляване на всички усреднени цени
     * 
     * @param core_Type $Type
     * @param mixed $oldValue
     * @param mixed $newValue
     * 
     * @return void
     */
    public static function updateAvgPrices($Type, $oldValue, $newValue)
    {
        static::saveAvgPrices();
    }
}