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
 * @copyright 2006 - 2014 Experta OOD
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
    public $loadList = 'pos_Wrapper,plg_Sorting,plg_StyleNumbers';
    

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
    public $listFields = 'productId,storeId,quantity,lastUpdated,state';
    
	
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име');
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
    	// Датата на синхронизацията
    	$date = dt::now();
    	$productsClassId = cat_Products::getClassId();
    	
    	// Извличаме всички складове, които са свързани към точки
    	$storesArr = array();
    	$pointQuery = pos_Points::getQuery();
    	$pointQuery->show('storeId');
    	while($pointRec = $pointQuery->fetch()){
    		$storesArr[$pointRec->storeId] = $pointRec->storeId;
    	}
    	
    	// Ако няма скалдове не правим нищо
    	if(!$storesArr) return;
    	
    	// За всеки запис извлечен от счетоводството
    	foreach ($all as $index => $amount){
    		
    		// Задаване на стойности на записа
    		list($storeId, $classId, $productId) = explode('|', $index);
    		
    		expect($storeId, $classId, $productId);
    		
    		// Ако продукта е спецификация - пропускаме го
    		if($classId != $productsClassId) continue;
    		
    		// Ако няма точка за склада - пропускаме записа
    		if(!in_array($storeId, $storesArr)) continue;
    		
    		// Променят се количествата само при нужда
    		$rec = (object)array('storeId'   => $storeId, 'productId' => $productId, 'quantity'  => $amount,);
    		$exRec = static::fetch("#productId = {$productId} AND #storeId = {$storeId}", '*', FALSE);
    		if($exRec){
    			if($exRec->quantity == $rec->quantity) continue;
    			$exRec->quantity = $rec->quantity;
    			$rec = $exRec;
    		}
    		
	    	// Обновяване на датата за ъпдейт
	    	$rec->lastUpdated = $date;
	    	
	    	// Ако количеството е 0, състоянието е затворено
	    	$rec->state = ($rec->quantity) ? 'active' : 'closed';
	    	
	    	// Обновяване на записа
	    	static::save($rec);
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
    	$receiptDetailsQuery->where("#state = 'active'");
    	$receiptDetailsQuery->where("#action LIKE '%sale%'");
    	$receiptDetailsQuery->show("state,productId,pointId,quantity,value,receiptId");
    	
    	// За всяка активирана бележка, трупаме я в масив
    	while($dRec = $receiptDetailsQuery->fetch()){
    		$dRec->storeId = pos_Points::fetchField($dRec->pointId, 'storeId');
    		if(!static::$cache[$dRec->productId][$dRec->value]){
    			static::$cache[$dRec->productId][$dRec->value] = cat_Products::getProductInfo($dRec->productId, $dRec->value);
    		}
    		$info = static::$cache[$dRec->productId][$dRec->value];
    		$dRec->quantityInPack = ($info->packagingRec) ? $info->packagingRec->quantity : 1;
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
    	$row->productId = cat_Products::getHyperLink($rec->productId, TRUE);
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
}