<?php


/**
 * Клас 'store_Products' за наличните в склада артикули
 * Данните постоянно се опресняват от баланса
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
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
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State,plg_RowTools2';
    
    
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
    public $listFields = 'productId=Наименование,quantity, quantityNotOnPallets, quantityOnPallets, measureId=Мярка, state';
    
    
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
        $this->FLD('quantity', 'double', 'caption=Количество->Общо');
        $this->FNC('quantityNotOnPallets', 'double', 'caption=Количество->Непалетирано,input=hidden');
        $this->FLD('quantityOnPallets', 'double', 'caption=Количество->На палети,input=hidden');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Изчисляване на функционално поле
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return void|number
     */
    public static function on_CalcQuantityNotOnPallets(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->quantity)){
    		return;
    	}
    	
    	return $rec->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets;
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
	    
        $makePallets = (core_Packs::isInstalled('pallet')) ? TRUE : FALSE;
        
	    foreach($rows as $id => &$row){
	       $rec = &$recs[$id];
	       $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
	        	
	        try{
	        	$pInfo = cat_Products::getProductInfo($rec->productId);
	        	$row->measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
	        } catch(core_exception_Expect $e){
	        	$row->measureId = tr("???");
	        }
	        	 
	        if($rec->quantityNotOnPallets > 0){
	        	if($makePallets){
	        		core_RowToolbar::createIfNotExists($row->_rowTools);
	        		$row->_rowTools->addLink('Палетиране', array('pallet_Pallets', 'add', 'productId' => $rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/box.png,title=Палетиране на артикул');
	        	}
	        }
        }
    }
    
    
    /**
     * След подготовка на филтъра
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Подготвяме формата
    	cat_Products::expandFilter($data->listFilter);
    	$orderOptions = arr::make('all=Всички,standard=Стандартни,private=Нестандартни,last=Последно добавени,closed=Изчерпани');
    	$data->listFilter->setOptions('order', $orderOptions);
		$data->listFilter->setDefault('order', 'standard');
    	
    	$data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
    	$data->listFilter->setDefault('storeId', store_Stores::getCurrent());
    	$data->listFilter->setField('storeId', 'autoFilter');
    	
    	// Подготвяме в заявката да може да се търси по полета от друга таблица
    	$data->query->EXT('keywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
    	$data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
    	$data->query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
    	$data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
    	$data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');
    	
        $data->listFilter->showFields = 'storeId,search,order,groupId';
        $data->listFilter->input('storeId,order,groupId,search', 'silent');
        
        // Ако има филтър
        if($rec = $data->listFilter->rec){
        	
        	// И е избран склад, търсим склад
        	if(isset($rec->storeId)){
        		$selectedStoreName = store_Stores::getHyperlink($rec->storeId, TRUE);
        		$data->title = "|Продукти в склад|* <b style='color:green'>{$selectedStoreName}</b>";
        		$data->query->where("#storeId = {$rec->storeId}");
        	}
        	
        	// Ако се търси по ключови думи, търсим по тези от външното поле
        	if(isset($rec->search)){
        	 	plg_Search::applySearch($rec->search, $data->query, 'keywords');
            
            	// Ако ключовата дума е число, търсим и по ид
            	if (type_Int::isInt($rec->search)) {
            		$data->query->orWhere("#productId = {$rec->search}");
            	}
        	}
        	
        	// Подредба
        	if(isset($rec->order)){
        		switch($data->listFilter->rec->order){
        			case 'all':
        				$data->query->orderBy('#state,#name');
						break;
		        	case 'private':
        				$data->query->where("#isPublic = 'no'");
        				$data->query->orderBy('#state,#name');
						break;
					case 'last':
			      		$data->query->orderBy('#createdOn=DESC');
		        		break;
        			case 'closed':
        				$data->query->where("#state = 'closed'");
        				break;
        			default :
        				$data->query->where("#isPublic = 'yes'");
        				$data->query->orderBy('#state,#name');
        				break;
        		}
        	}
        	
        	// Филтър по групи на артикула
        	if (!empty($rec->groupId)) {
        		$descendants = cat_Groups::getDescendantArray($rec->groupId);
        		$keylist = keylist::fromArray($descendants);
        		$data->query->likeKeylist("groups", $keylist);
        	}
        }
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
    	$query->show('productId,storeId,quantity,quantityOnPallets,quantityNotOnPallets,state');
    	$oldRecs = $query->fetchAll();
    	$self = cls::get(get_called_class());
    	
    	$arrRes = arr::syncArrays($all, $oldRecs, "productId,storeId", "quantity");
    	
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
    	$query->show('productId,storeId,quantity,quantityOnPallets,quantityNotOnPallets,state');
    	
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
	        $products[$pRec->id] = cat_Products::getTitleById($pRec->productId, FALSE);
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
    	 
    	return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * Подготвя полетата (колоните) които ще се показват
     */
    public function prepareListFields_(&$data)
    {
    	parent::prepareListFields_($data);
    	
    	if(!core_Packs::isInstalled('pallet')){
    		unset($data->listFields['quantityNotOnPallets']);
    		unset($data->listFields['quantityOnPallets']);
    		$data->listFields['quantity'] = 'Количество';
    	}
    	
    	return $data;
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter');
    	
    	// Тулбара го преместваме преди състоянието
    	if(isset($data->listFields['_rowTools'])){
    		$field = $data->listFields['_rowTools'];
    		unset($data->listFields['_rowTools']);
    		arr::placeInAssocArray($data->listFields, array('_rowTools' => $field), NULL, 'measureId');
    	}
    }
}