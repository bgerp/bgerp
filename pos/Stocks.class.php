<?php



/**
 * Модел "Складови наличности", Показва текущите наличности на продукта в склада на точката.
 * Синхронизира данните извлечени от счетоводството с тези на неотчетените бележки да показва приблизително
 * актуални резултати
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Stocks extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'Складови наличности';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'pos_Wrapper,plg_Sorting,plg_StyleNumbers,plg_State';
    

    /**
	 *  Брой елементи на страница 
	 */
    public $listItemsPerPage = "40";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'pos, ceo';
 
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, pos';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,productId,storeId,quantity,lastUpdated,state';
    
	
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име,remember=info');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
        $this->FLD('lastUpdated', 'datetime(format=smartTime)', 'caption=Последен ъпдейт,input=none');
        $this->FLD('state', 'enum(active=Активирано,closed=Затворено)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
    }
    
    
	/**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     * 
     * @param stdClass $all - масив идващ от баланса във вида:
     * 				array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($all)
    {
    	// Извличаме всичкис кладове групирани в ПОС-а
    	$posStoresQuery = pos_Points::getQuery();
    	$posStoresQuery->groupBy("storeId");
    	$posStoresQuery->show("storeId");
    	$usedStores = array();
    	while($pRec = $posStoresQuery->fetch()){
    		$usedStores[$pRec->storeId] = $pRec->storeId;
    	}
    	
    	$productsClsId = cat_Products::getClassId();
    	
    	// Махаме записите за складовете, които не участват в ПОС-а
    	if(is_array($all)){
    		foreach ($all as $index => $bRec){
    			if(!in_array($bRec->storeId, $usedStores) || $bRec->classId != $productsClsId){
    				unset($all[$index]);
    			}
    		}
    	}
    	
    	$stockQuery = pos_Stocks::getQuery();
    	$oldRecs = $stockQuery->fetchAll();
    	
    	$arrRes = arr::syncArrays($all, $oldRecs, "productId,storeId", "quantity");
    	
    	$self = cls::get(get_called_class());
    	$self->saveArray($arrRes['insert']);
    	$self->saveArray($arrRes['update']);
    	
    	if(count($arrRes['delete'])){
    		$closeQuery = pos_Stocks::getQuery();
    		$closeQuery->where("#state != 'closed'");
    		$closeQuery->in('id', $arrRes['delete']);
    		
    		while($rec = $closeQuery->fetch()){
    			$rec->state = 'closed';
    			$rec->quantity = 0;
    		
    			$self->save($rec);
    		}
    	}
    	
    	// Приспада количествата от не-отчетените бележки
    	self::applyPosStocks();
    }
    
    
    /**
     * След взимане на количествата от баланса, отчитаме всички не-отчетени бележки
     */
    private static function applyPosStocks()
    {
    	// Намираме всички активирани бележки
    	$activeReceipts = array();
    	
    	$receiptDetailsQuery = pos_ReceiptDetails::getQuery();
    	$receiptDetailsQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
    	$receiptDetailsQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
    	$receiptDetailsQuery->where("#state = 'waiting'");
    	$receiptDetailsQuery->where("#action LIKE '%sale%'");
    	$receiptDetailsQuery->show("state,productId,pointId,quantity,value,receiptId");
    	
    	// За всяка активирана бележка, трупаме я в масив
    	while($dRec = $receiptDetailsQuery->fetch()){
    		$dRec->storeId = pos_Points::fetchField($dRec->pointId, 'storeId');
    		if(!static::$cache[$dRec->productId]){
    			static::$cache[$dRec->productId] = cat_Products::getProductInfo($dRec->productId);
    		}
    		
    		$info = static::$cache[$dRec->productId];
    		$dRec->quantityInPack = ($info->packagings[$dRec->value]) ? $info->packagings[$dRec->value]->quantity : 1;
    		$activeReceipts[] = $dRec;
    	}
    	
    	// Ако няма не-отчетени бележки, не правим нищо
    	if(!count($activeReceipts)) return;
    	
    	// За всеки запис, форсираме го
    	foreach ($activeReceipts as $receiptRec){
    		self::forceRec($receiptRec);
    	}
    }
    
    
    /**
     * Форсира запис в модела
     */
    private static function forceRec($receiptRec) 
    {
    	// Ако има запис за този продукт и склад, обновява се ако няма се създава
    	if(!$rec = static::fetch("#storeId = '{$receiptRec->storeId}' AND #productId = '{$receiptRec->productId}'")){
    		$rec = new stdClass();
    		$rec->storeId     = $receiptRec->storeId;
    		$rec->productId   = $receiptRec->productId;
    		$rec->lastUpdated = dt::now();
    	}
    	
    	$rec->quantity -= $receiptRec->quantity * $receiptRec->quantityInPack;
    	$rec->state = ($rec->quantity) ? 'active' : 'closed';
    	
    	static::save($rec);
    }
    
    
    /**
     * Изважда к-та на продуктите от една бележка от склада, извиква се при активиране на бележка
     */
    public static function updateStocks($receiptId)
    {
    	expect($rec = pos_Receipts::fetch($receiptId));
    	$storeId = pos_Points::fetchField($rec->pointId, 'storeId');
    	$products = pos_Receipts::getProducts($receiptId);
    	
    	// Форсираме записи за всички продукти от тази бележка
    	foreach ($products as $prRec){
    		$prRec->storeId = $storeId;
    		self::forceRec($prRec);
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->storeId = store_Stores::getHyperLink($rec->storeId, TRUE);
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    }
    
    
    static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->query->orderBy('state', 'ASC');
    }
    
    
    /**
     * Връща количеството на даден продукт, на дадена точка
     * 
     * @param int $productId - ид на продукт
     * @param int $pointId - ид на точка
     * @return double - количеството на продукта в склада на точката
     */
    public static function getQuantity($productId, $pointId)
    {
    	$storeId = pos_Points::fetchField($pointId, 'storeId');
    	
    	$quantity = static::fetchField("#storeId = '{$storeId}' AND #productId = '{$productId}'", 'quantity');
    	$quantity = ($quantity) ? $quantity : 0;
    	
    	return $quantity;
    }
    
    
    /**
     * Изчиства записите в наличностите в поса
     */
    public function act_Truncate()
    {
    	requireRole('admin,debug');
    
    	// Изчистваме записите от моделите
    	pos_Stocks::truncate();
    
    	return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('admin,debug')){
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата, ef_icon=img/16/sport_shuttlecock.png, title=Изтриване на таблицата с продукти');
    	}
    }
}