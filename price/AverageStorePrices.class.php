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
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('quantity', 'double', 'caption=Количество');
        $this->FLD('price', 'double', 'caption=Цена');
        
        $this->setDbUnique('productId');
        $this->setDbUnique('itemId');
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
        
        if ($mvc->haveRightFor('saveavgprices')) {
            $data->toolbar->addBtn('От нулата', array($mvc, 'saveAvgPrices', 'truncate' => 1, 'ret_url' => true), null, 'ef_icon = img/16/bug.png,title=Изчисляване от нулата');
        }
    }
    
    
    /**
     * Изчисляване на средните складови цени
     */
    function act_saveAvgPrices()
    {
        $this->requireRightFor('saveavgprices');
        $truncate = Request::get('truncate', 'int');
        $truncate = isset($truncate) ? $truncate : false;
        static::saveAvgPrices($truncate);
        
        followRetUrl();
    }
    
    function act_Test()
    {
        $affectedTargetedProducts = array(25, 4, 39, 40, 36, 27);
        
        cls::get('price_interface_AverageCostStorePricePolicyImpl')->getCosts($affectedTargetedProducts);
    }
    
    
    
    /**
     * Запис за цените в модела
     */
    private static function saveAvgPrices($fromZero = false)
    {
        if($fromZero !== false){
            static::truncate();
        }
        
        // Има ли избрани складове за усредняване
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
        
        $query = static::getQuery();
        $exRecs = $query->fetchAll();
        $alreadyCalculatedProductIds = arr::extractValuesFromArray($exRecs, 'productId');
        core_App::setTimeLimit($count * 0.7, 900);
        
        $map = static::getProductItemMap($publicProductIds, $alreadyCalculatedProductIds);
        $dRecs = static::getLastDebitRecs(array_keys($map), $storeItems);
        
        $valiorMap = array();
        foreach ($dRecs as $jRec){
            $rec = (object)array('date' => $jRec->valior, 'itemId' => $jRec->debitItem2, 'productId' => $map[$jRec->debitItem2]->productId, 'quantity' => 0, 'price' => 0);
            $toSave[$rec->itemId] = $rec;
            
            // Записите ще се гръпурат по вальор
            $valiorMap[$jRec->valior][] = $rec->itemId;
        }
       
        foreach ($valiorMap as $valior => $pItems){
            
            // За всяка уникална дата се смятат к-та на артикулите към нея
            $Balance = new acc_ActiveShortBalance(array('from' => $valior, 'to' => $valior, 'accs' => '321', false, true, 'item1' => $storeItems, 'item2' => $pItems, 'null'));
            $bRecs = $Balance->getBalance('321');
            foreach ($pItems as $iId){
                
                // Сумира се количеството в посочените складове
                $iQuantity = 0;
                array_walk($bRecs, function ($a) use ($iId, &$iQuantity){if($a->ent2Id == $iId) {$iQuantity += $a->blQuantity;};});
                $toSave[$iId]->quantity = $iQuantity;
               
                // Изчислява се среднопритеглената цена към тази дата за общото количество във посочените складове
                $amount = cat_Products::getWacAmountInStore($toSave[$iId]->quantity, $toSave[$iId]->productId, $valior, $storeIds);
                if($toSave[$iId]->quantity){
                    $toSave[$iId]->price = round($amount / $toSave[$iId]->quantity, 6);
                }
                
                // Ако няма цена или има отрицателни количества не ни интересуват
                if($iQuantity <= 0 || empty($toSave[$iId]->price)){
                    unset($toSave[$iId]);
                }
            }
        }
        
        // Синхронизиране на записите
        $res = arr::syncArrays($toSave, $exRecs, 'itemId,productId', 'date,quantity,price');
        
        // Добавят се само новите, старите не се променят
        $me = cls::get(get_called_class());
        if (countR($res['insert'])) {
            $me->saveArray($res['insert']);
        }
    }
    
    
    /**
     * Последните дебити на артикулите
     * 
     * @param array $productItemIds - пера на артикули
     * @param array $storeItemIds   - пера на складове
     * 
     * @return array $debitRecs   
     */
    public static function getLastDebitRecs($productItemIds, $storeItemIds)
    {
        $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
        
        $debitRecs = array();
        foreach ($productItemIds as $itemId){
            $jQuery = acc_JournalDetails::getQuery();
            $jQuery->where("#debitAccId = {$storeAccId}");
            $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
            $jQuery->where("#debitItem2 = {$itemId}");
            $jQuery->in('debitItem1', $storeItemIds);
            $jQuery->limit(1);
            $jQuery->show('debitItem1,debitItem2,amount,debitQuantity,valior');
            $jQuery->orderBy('valior', 'desc');
            $jRec = $jQuery->fetch();
            
            if(is_object($jRec)){
                $debitRecs[] = $jRec;
            }
        }
        
        return $debitRecs;
        
        /*
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $jQuery->XPR('maxValior', 'datetime', 'MAX(#valior)');
        $jQuery->where("#debitAccId = {$storeAccId}");
        $jQuery->in('debitItem2', $productItemIds);
        $jQuery->in('debitItem1', $storeItemIds);
        $jQuery->show('debitItem2,journalId');
        //$jQuery->orderBy('valior,id', 'desc');
        $jQuery->groupBy('debitItem2');
        $jQuery->where('#maxValior');
        */
    }
    
    
    /**
     * Връща масив със съответствието между артикули и техните пера
     *
     * @param array $products
     *
     * @return array $map
     */
    public static function getProductItemMap($products, $excludeProducts = array())
    {
        $map = array();
        $productClassId = cat_Products::getClassId();
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#state = 'active' AND #classId = {$productClassId} AND #lastUseOn IS NOT NULL");
        $iQuery->in('objectId', $products);
        $iQuery->show('id,objectId');
        while($iRec = $iQuery->fetch()){
            if(array_key_exists($iRec->objectId, $excludeProducts)) continue;
            
            $map[$iRec->id] = (object)array('id' => $iRec->id, 'productId' => $iRec->objectId);
        }
        
        return $map;
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