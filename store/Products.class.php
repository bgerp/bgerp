<?php



/**
 * Продукти
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Products extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_Search, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals, plg_State, plg_LastUsedKeys';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, tools=Пулт, name, quantity, quantityNotOnPallets, quantityOnPallets, makePallets, state';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'storeId';
    
    
    /**
     * Полета за търсене
     */
    var $searchField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
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
        $this->FLD('state', 'enum(active=Активирано,closed=Затворено)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, classId, storeId');
    }
    
    
    /**
     * Изчисляване на заглавието спрямо продуктовия мениджър
     */
    public function on_CalcName(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->productId) || empty($rec->classId)){
    		return;
    	}
    	
    	try{
    		$name = $rec->name = cls::get($rec->classId)->getTitleById($rec->productId);
    	} catch(Exception $e){
    		$name = tr('Проблем при показването');
    	}
    	
    	return $rec->name = $name;
    }
    
    
	/**
     * Изчисляване на заглавието спрямо продуктовия мениджър
     */
    public function on_CalcQuantityNotOnPallets(core_Mvc $mvc, $rec)
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
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreName = store_Stores::getTitleById(store_Stores::getCurrent());
        
        $data->title = "|Продукти в СКЛАД|* \"{$selectedStoreName}\"";
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		$res = " " . plg_Search::normalizeText($rec->name);
	}
     
     
    /**
     * При добавяне/редакция на палетите - данни по подразбиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        expect($ProductManager = cls::get($rec->classId));
        
    	if (empty($rec->id)) {
            $form->setOptions('productId', $ProductManager::getByProperty('canStore'));
        } else {
            $form->setOptions('productId', array($rec->productId => $ProductManager->getTitleById($rec->productId)));
        }
        
        $form->setReadOnly('storeId', store_Stores::getCurrent());
    }
    

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        // Ако няма никакви записи - нищо не правим
        if(!count($recs)) return;
	        foreach($rows as $id => &$row){
	        	$rec = &$recs[$id];
	        	
	        	$ProductMan = cls::get($rec->classId);
	        	try{
	        		$pInfo = $ProductMan->getProductInfo($rec->productId);
	        		$measureShortName = cat_UoM::getShortName($pInfo->productRec->measureId);
	        	} catch(Exception $e){
	        		$measureShortName = tr("???");
	        	}
	        	
		        if($rec->quantityNotOnPallets > 0){
		        	$row->makePallets = ht::createBtn('Палетиране', array('store_Pallets', 'add', 'productId' => $rec->id), NULL, NULL, array('title' => 'Палетиране на продукт'));
		        }
		        
		        $row->name = $ProductMan::getHyperLink($rec->productId, TRUE);
		        
		        $row->quantity .= ' ' . $measureShortName;
		        if($rec->quantityOnPallets){
		        	 $row->quantityOnPallets .= ' ' . $measureShortName;
		        }
	       
	        	$row->quantityNotOnPallets .= ' ' . $measureShortName;
	        	
	        	$row->TR_CLASS = 'active';
        }
    }
    
    
    /**
     * Филтър
     */
    static function on_AfterPrepareListFilter($mvc, $data)
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
     * @param stdClass $all - масив идващ от баланса във вида:
     * 				array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($all)
    {
    	// Датата на синхронизацията
    	$date = dt::now();
    	
    	// За всеки запис извлечен от счетоводството
    	foreach ($all as $index => $amount){
    		
    		// Задаване на стойности на записа
    		list($storeId, $classId, $productId) = explode('|', $index);
    		expect($storeId, $classId, $productId);
    		
    		$rec = (object)array('storeId'   => $storeId,
    							 'classId'   => $classId,
    							 'productId' => $productId,
    							 'quantity'  => $amount,
    		);
    		
    		// Ако има съществуващ запис се обновява количеството му
	    	$exRec = static::fetch("#productId = {$productId} AND #classId = {$classId} AND #storeId = {$storeId}");
	    	if($exRec){
	    		// Ъпдейтваме количеството само ако има промяна
	    		if($exRec->quantity == $rec->quantity) {
	    			
	    			// Записваме в кеша ид-то на съществуващия запис и продължаваме напред
	    			static::$cache[$exRec->id] = $exRec->id;
	    			continue;
	    		}
	    		$exRec->quantity = $rec->quantity;
	    		$rec = $exRec;
	    	}
	    	
	    	// Ако количеството е 0, състоянието е затворено
	    	$rec->state = ($rec->quantity) ? 'active' : 'closed';
	    	
	    	// Обновяване на записа
	    	static::save($rec);
	    	
	    	// Записваме в кеша ид-то добавения запис
	    	static::$cache[$rec->id] = $rec->id;
    	}
    	
    	// Ъпдейт на к-та на продуктите, имащи запис но липсващи в счетоводството
    	static::updateMissingProducts($date);
    }
    
    
    /**
     * Ф-я която ъпдейтва всички записи, които присъстват в модела, 
     * но липсват в баланса
     * 
     * @param date $date - дата
     */
    private static function updateMissingProducts($date)
    {
    	// Всички записи, които са останали но не идват от баланса
    	$query = static::getQuery();
    	
    	// Изключваме продуктите, които са дошли от счетоводството, ако има такива
    	if(count(static::$cache)){
    		$query->notIn('id', static::$cache);
    	}
    	
    	// За всеки запис
    	while($rec = $query->fetch()){
    		
    		// К-то им се занулява и състоянието се затваря
    		$rec->state       = 'closed';
    		$rec->quantity    = 0;
    		
    		// Обновяване на записа
    		static::save($rec);
    	}
    }
    
    
    /**
     * Връща всички налични продукти в склада
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
    	
    	// Извличане на всички активни продукти с к-во по-голямо от нула
    	$products = array();
	    $pQuery = static::getQuery();
	    $pQuery->where("#storeId = {$storeId}");
	    $pQuery->where("#quantity > 0");
	    $pQuery->where("#state = 'active'");
	    while($pRec = $pQuery->fetch()){
	        $products[$pRec->id] = $pRec->name;
	    }
	    
	    return $products;
    }
}