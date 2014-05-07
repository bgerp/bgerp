<?php



/**
 * Модел Складови наличностти
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
    var $title = 'Складови наличностти';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Плъгини за зареждане
     */
   var $loadList = 'pos_Wrapper,plg_Sorting';
    

    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "40";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo';
 
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';
    
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, pos';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId,storeId,quantity,lastUpdated,state';
    
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'double', 'caption=Количество->Общо');
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
    	
    	// Изпразваме таблицата
    	$self = cls::get(get_called_class());
    	$self->db->query("TRUNCATE TABLE `{$self->dbTableName}`");
    	
    	// За всеки запис извлечен от счетоводството
    	foreach ($all as $index => $amount){
    		
    		// Задаване на стойности на записа
    		list($storeId, $classId, $productId) = explode('|', $index);
    		expect($storeId, $classId, $productId);
    		
    		// Ако продукта е спецификация - пропускаме го
    		if($classId != $productsClassId) continue;
    		
    		// Ако няма точка за склада - пропускаме записа
    		if(!in_array($storeId, $storesArr)) continue;
    		
    		$rec = (object)array('storeId'   => $storeId,
    							 'productId' => $productId,
    							 'quantity'  => $amount,
    		);
    		
    		
    		// Ако има съществуващ запис се обновява количеството му
	    	$exRec = static::fetch("#productId = {$productId} AND #storeId = {$storeId}");
	    	if($exRec){
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
    	
    	
    }
}