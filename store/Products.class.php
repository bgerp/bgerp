<?php


/**
 * Клас 'store_Products' за наличните в склада артикули
 * Данните постоянно се опресняват от баланса
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Products extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_Search, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State, plg_LastUsedKeys';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, tools=Пулт, name, quantity, quantityNotOnPallets, quantityOnPallets, measureId=Мярка, makePallets, state';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'storeId';
    
    
    /**
     * Полета за търсене
     */
    public $searchField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'int', 'caption=Име,remember=info');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'double', 'caption=Количество->Общо');
        $this->FNC('quantityNotOnPallets', 'double', 'caption=Количество->Непалетирано,input=hidden');
        $this->FLD('quantityOnPallets', 'double', 'caption=Количество->На палети,input=hidden');
        $this->FNC('makePallets', 'varchar(255)', 'caption=Палетиране');
        $this->FNC('name', 'varchar(255)', 'caption=Продукт');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, classId, storeId');
    }
    
    
    /**
     * Изчисляване на заглавието спрямо продуктовия мениджър
     */
    public static function on_CalcName(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->productId) || empty($rec->classId) || !cls::load($rec->classId, TRUE)){
    		return;
    	}
    	
    	try{
    	    expect(cls::load($rec->classId, TRUE));
	        $name = cls::get($rec->classId)->getTitleById($rec->productId);
    	} catch(core_exception_Expect $e){
    		$name = tr('Проблем при показването');
    	}
    	
    	return $rec->name = $name;
    }
    
    
	/**
     * Изчисляване на заглавието спрямо продуктовия мениджър
     */
    public static function on_CalcQuantityNotOnPallets(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->quantity)){
    		return;
    	}
    	
    	return $rec->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets;
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreName = store_Stores::getTitleById(store_Stores::getCurrent());
        
        $data->title = "|Продукти в СКЛАД|* \"{$selectedStoreName}\"";
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		$res = " " . plg_Search::normalizeText($rec->name);
	}
    

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        // Ако няма никакви записи - нищо не правим
        if(!count($recs)) return;
	    
	    foreach($rows as $id => &$row){
	       $rec = &$recs[$id];
	        	
	        if(cls::load($rec->classId, TRUE)){
	        	$ProductMan = cls::get($rec->classId);
	        	$row->name = $ProductMan::getHyperLink($rec->productId, TRUE);
	        } else {
	        	$row->name = tr("Проблем с показването");
	        }
	        	
	        try{
	        	$pInfo = cat_Products::getProductInfo($rec->productId);
	        	$row->measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
	        } catch(core_exception_Expect $e){
	        	$row->measureId = tr("???");
	        }
	        	 
	        if($rec->quantityNotOnPallets > 0){
	        	$row->makePallets = ht::createBtn('Палетиране', array('store_Pallets', 'add', 'productId' => $rec->id), NULL, NULL, array('title' => 'Палетиране на продукт'));
	        }
	        	
	        $row->TR_CLASS = 'active';
        }
    }
    
    
    /**
     * След подготовка на филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search';
        $data->listFilter->input();
        
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('state');
    }
    
    
    /**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     * 
     * @param array $all - масив идващ от баланса във вида:
     * 				array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($all)
    {
    	$query = static::getQuery();
    	$query->show('productId,classId,storeId,quantity,quantityOnPallets,quantityNotOnPallets,makePallets,state');
    	$oldRecs = $query->fetchAll();
    	$self = cls::get(get_called_class());
    	
    	$arrRes = arr::syncArrays($all, $oldRecs, "productId,classId,storeId", "quantity");
    	
    	$self->saveArray($arrRes['insert']);
    	$self->saveArray($arrRes['update']);
    	
    	// Ъпдейт на к-та на продуктите, имащи запис но липсващи в счетоводството
    	self::updateMissingProducts($arrRes['delete']);
    }
    
    
    /**
     * Ф-я която ъпдейтва всички записи, които присъстват в модела, 
     * но липсват в баланса
     * 
     * @param date $date - дата
     */
    private static function updateMissingProducts($array)
    {
    	// Всички записи, които са останали но не идват от баланса
    	$query = static::getQuery();
    	$query->show('productId,classId,storeId,quantity,quantityOnPallets,quantityNotOnPallets,makePallets,state');
    	
    	// Зануляваме к-та само на тези продукти, които още не са занулени
    	$query->where("#state = 'active'");
    	if(count($array)){
    		
    		// Маркираме като затворени, всички които не са дошли от баланса или имат количества 0
    		$query->in('id', $array);
    		$query->orWhere("#quantity = 0");
    	}
    	
    	if(!count($array)) return;
    	
    	// За всеки запис
    	while($rec = $query->fetch()){
    		
    		// К-то им се занулява и състоянието се затваря
    		$rec->state    = 'closed';
    		$rec->quantity = 0;
    		
    		// Обновяване на записа
    		static::save($rec);
    	}
    }
    
    
    /**
     * Връща всички продукти в склада
     * 
     * @param int $storeId - ид на склад, ако е NULL взима текущия активен склад
     * @return array $products
     */
    public static function getProductsInStore($storeId = NULL)
    {
    	// Ако няма склад, взима се текущия
    	if(!$storeId){
    		$storeId = store_Stores::getCurrent();
    	}
    	
    	$products = array();
	    $pQuery = static::getQuery();
	    $pQuery->where("#storeId = {$storeId}");
	    
	    while($pRec = $pQuery->fetch()){
	        $products[$pRec->id] = $pRec->name;
	    }
	    
	    return $products;
    }
    
    



    /**
     * След подготовка на туклбара на списъчния изглед
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
    
    
    /**
     * Изчиства записите в склада
     */
    public function act_Truncate()
    {
    	requireRole('admin,debug');
    	 
    	// Изчистваме записите от моделите
    	store_Products::truncate();
    	 
    	Redirect(array($this, 'list'));
    }
}