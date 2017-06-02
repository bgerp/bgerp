<?php


/**
 * Клас 'store_Products' за наличните в склада артикули
 * Данните постоянно се опресняват от баланса
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
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
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State';
    
    
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
    public $listFields = 'productId=Наименование, measureId=Мярка,quantity,storeId';
    
    
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
        $this->FLD('quantity', 'double', 'caption=Количество,smartCenter');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        // Ако няма никакви записи - нищо не правим
        if(!count($recs)) return;
	            
	    foreach($rows as $id => &$row){
	       $rec = &$recs[$id];
	       $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
	       $row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
	       
	        try{
	        	$pInfo = cat_Products::getProductInfo($rec->productId);
	        	$row->measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
	        } catch(core_exception_Expect $e){
	        	$row->measureId = tr("???");
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
    	$orderOptions = arr::make('all=Всички,active=Активни,standard=Стандартни,private=Нестандартни,last=Последно добавени,closed=Изчерпани');
    	$data->listFilter->setOptions('order', $orderOptions);
		$data->listFilter->setDefault('order', 'active');
    	
    	$data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
    	
    	$stores = array();
    	$sQuery = store_Stores::getQuery();
    	$sQuery->where("#state != 'rejected'");
    	store_Stores::restrictAccess($sQuery);
    	while($sRec = $sQuery->fetch()){
    		$stores[$sRec->id] = store_Stores::getTitleById($sRec->id, FALSE);
    	}
    	$data->listFilter->setOptions('storeId', array('' => '') + $stores);
    	$data->listFilter->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    	
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
        		$data->title = "|Наличности в склад|* <b style='color:green'>{$selectedStoreName}</b>";
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
        			case 'active':
        				$data->query->where("#state != 'closed'");
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
    	$query->show('productId,storeId,quantity,state');
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
     * @param array $array - масив с данни за наличните артикул
     */
    private static function updateMissingProducts($array)
    {
    	// Всички записи, които са останали но не идват от баланса
    	$query = static::getQuery();
    	$query->show('productId,storeId,quantity,state');
    	
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
     * Колко е количеството на артикула в складовете
     * 
     * @param int $productId    - ид на артикул
     * @param int|NULL $storeId - конкретен склад, NULL ако е във всички
     * @return double $sum      - наличното количество
     */
    public static function getQuantity($productId, $storeId = NULL)
    {
    	$sum = 0;
    	$query = self::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->show('quantity');
    	if(isset($storeId)){
    		$query->where("#storeId = {$storeId}");
    	}
    	
    	while($r = $query->fetch()){
    		$sum += $r->quantity;
    	}
    	
    	return $sum;
    }
    
    
    /**
     * Всички налични к-ва на артикулите в склад-а
     * 
     * @param int $storeId
     * @return array $res
     */
    public static function getQuantitiesInStore($storeId)
    {
    	$res = array();
    	$query = self::getQuery();
    	$query->where("#storeId = {$storeId}");
    	$query->show('productId,quantity');
    	while($rec = $query->fetch()){
    		$res[$rec->productId] = $rec->quantity;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща всички продукти в склада
     * 
     * @param NULL|int $storeId - ид на склад, ако е NULL взима текущия активен склад
     * @return array $products
     */
    public static function getProductsInStore($storeId = NULL)
    {
    	// Ако няма склад, взима се текущия
    	if(!isset($storeId)){
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
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата, ef_icon=img/16/sport_shuttlecock.png, title=Изтриване на таблицата с продукти');
    	}
    }
    
    
    /**
     * Изчиства записите в склада
     */
    public function act_Truncate()
    {
    	requireRole('debug');
    	 
    	// Изчистваме записите от моделите
    	store_Products::truncate();
    	 
    	return new Redirect(array($this, 'list'));
    }
    
    
     
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter');
    	
    }


	/**
	 * Преди подготовката на ключовете за избор
	 */
    public static function on_BeforePrepareKeyOptions($mvc, &$options, $typeKey, $where = '')
    {
        $storeId = store_Stores::getCurrent();
        $query = self::getQuery();
        if($where) {
            $query->where($where);
        }
        while($rec = $query->fetch("#storeId = {$storeId}  AND #state = 'active'")) {
            $options[$rec->id] = self::getVerbal($rec, 'productId');
        }

        if(!count($options)) {
            $options[''] = '';
        }
    }

}