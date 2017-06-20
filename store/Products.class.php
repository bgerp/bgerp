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
class store_Products extends core_Detail
{
    
    
	/**
	 * Ключ с който да се заключи ъпдейта на таблицата
	 */
	const SYNC_LOCK_KEY = 'syncStoreProducts';
    
    
    /**
     * Заглавие
     */
    public $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State';
    
    
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
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantity,reservedQuantity,freeQuantity,storeId';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'storeId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'double', 'caption=Налично');
        $this->FLD('reservedQuantity', 'double', 'caption=Запазено');
        $this->FNC('freeQuantity', 'double', 'caption=Разполагаемо');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Преди подготовката на записите
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
    	if(isset($data->masterMvc)){
    		$mvc->listItemsPerPage = 100;
    	} else {
    		$mvc->listItemsPerPage = 20;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        // Ако няма никакви записи - нищо не правим
        if(!count($data->recs)) return;
       	$isDetail = isset($data->masterMvc);
        
	    foreach($data->rows as $id => &$row){
	       $rec = &$data->recs[$id];
	       $row->productId = cat_Products::getVerbal($rec->productId, 'name');
	       $icon = cls::get('cat_Products')->getIcon($rec->productId);
	       $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId), FALSE, "ef_icon={$icon}");

	       $pRec = cat_Products::fetch($rec->productId, 'code,isPublic,createdOn');
	       $row->code = cat_Products::getVerbal($pRec, 'code');
	       
	       if($isDetail){
	       		$basePack = key(cat_Products::getPacks($rec->productId));
	       		if($pRec = cat_products_Packagings::getPack($rec->productId, $basePack)){
	       			$rec->quantity /= $pRec->quantity;
	       			$row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantity);
	       			if(isset($rec->reservedQuantity)){
	       				$rec->reservedQuantity /= $pRec->quantity;
	       			}
	       		}
	       		$rec->measureId = $basePack;
	       } else {
	       		$rec->measureId = cat_Products::fetchField($rec->productId, 'measureId');
	       }
	       
	       $row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
	       if(isset($rec->reservedQuantity)){
	       		$rec->freeQuantity = $rec->quantity - $rec->reservedQuantity;
	       		$row->freeQuantity = $mvc->getFieldType('freeQuantity')->toVerbal($rec->freeQuantity);
	       }
	       
	       $row->measureId = cat_UoM::getTitleById($rec->measureId);
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
    	$data->listFilter->setField('storeId', 'autoFilter');
    	
    	// Подготвяме в заявката да може да се търси по полета от друга таблица
    	$data->query->EXT('keywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
    	$data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
    	$data->query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
    	$data->query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
    	$data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
    	$data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');
    	
    	$data->query->orderBy('code,id', ASC);
    	if(isset($data->masterMvc)){
    		$data->query->where("#state != 'closed'");
    		$data->listFilter->showFields = 'search,groupId';
    	} else {
    		$data->listFilter->showFields = 'storeId,search,order,groupId';
    	}
    	
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
        		$data->query->where("LOCATE('|{$rec->groupId}|', #groups)");
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
    	
    	if(!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
    		$this->logWarning("Синхронизирането на складовите наличности е заключено от друг процес");
    		return;
    	}
    	
    	$self->saveArray($arrRes['insert']);
    	$self->saveArray($arrRes['update'], "id,quantity");
    	
    	// Ъпдейт на к-та на продуктите, имащи запис но липсващи в счетоводството
    	self::updateMissingProducts($arrRes['delete']);
    	
    	core_Locks::release(self::SYNC_LOCK_KEY);
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
    	$query->show('productId,storeId,quantity,state,reservedQuantity');
    	
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
    		if(empty($rec->reservedQuantity)){
    			$rec->state = 'closed';
    		}
    		
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
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		if(isset($data->masterMvc)) return;
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата, ef_icon=img/16/sport_shuttlecock.png, title=Изтриване на таблицата с продукти');
    	}
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	if(isset($data->masterMvc)){
    		unset($data->listFields['storeId']);
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
    	$data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field');
    	$data->listTableMvc->FLD('measureId', 'varchar', 'tdClass=centered');
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

    
    /**
     * Обновяване на резервираните наличности по крон
     */
    function cron_CalcReservedQuantity()
    {
    	$docArr = array('store_ShipmentOrders'          => array('storeFld' => 'storeId', 'Detail' => 'store_ShipmentOrderDetails'), 
    					'store_Transfers'               => array('storeFld' => 'fromStore', 'Detail' => 'store_TransfersDetails'), 
    					'planning_ConsumptionNotes'     => array('storeFld' => 'storeId', 'Detail' => 'planning_ConsumptionNoteDetails'),
    					'planning_DirectProductionNote' => array('storeFld' => 'storeId', 'Detail' => 'planning_DirectProductNoteDetails'),
    			        'store_ConsignmentProtocols' => array('storeFld' => 'storeId', 'Detail' => 'store_ConsignmentProtocolDetailsSend'),
    	);
    	
    	$result = $queue = array();
    	foreach ($docArr as $Doc => $arr){
    		$Doc = cls::get($Doc);
    		$storeField = $arr['storeFld'];
    		
    		// Всички заявки
    		$sQuery = $Doc->getQuery();
    		$sQuery->where("#state = 'pending'");
    		$sQuery->show("id,containerId,modifiedOn,{$storeField}");
    		
    		while($sRec = $sQuery->fetch()){
    			
    			// Опит за взимане на данните от постоянния кеш
    			$reserved = core_Permanent::get("reserved_{$sRec->containerId}", $sRec->modifiedOn);
    			
    			// Ако няма кеширани к-ва
    			if(!isset($reserved)){
    				$reserved = array();
    				$Detail = cls::get($arr['Detail']);
    				setIfNot($Detail->productFieldName, 'productId');
    				
    				$shQuery = $Detail->getQuery();
    				
    				$isCp = ($arr['Detail'] == 'store_ConsignmentProtocolDetailsSend');
    				
    				if($isCp){
    					$suMFld = 'packQuantity';
    					$shQuery->XPR('sum', 'double', "SUM(#{$suMFld} * #quantityInPack)");
    				} else {
    					$suMFld = 'quantity';
    					$shQuery->XPR('sum', 'double', "SUM(#{$suMFld})");
    				}
    				
    				$shQuery->where("#{$Detail->masterKey} = {$sRec->id}");
    				$isPn = ($arr['Detail'] == 'planning_DirectProductNoteDetails');
    				
    				if($isPn) {
    					$shQuery->where("#type = 'input'");
    					$shQuery->where("#storeId IS NOT NULL");
    					$shQuery->show("productId,{$suMFld},noteId,storeId,sum,quantityInPack");
    					$shQuery->groupBy('productId,storeId');
    				} else {
    					$shQuery->show("{$Detail->productFieldName},{$suMFld},{$Detail->masterKey},sum,quantityInPack");
    					$shQuery->groupBy($Detail->productFieldName);
    				}
    				
    				while($sd = $shQuery->fetch()){
    					$storeId = ($isPn) ? $sd->storeId : $sRec->{$storeField};
    					$key = "{$storeId}|{$sd->{$Detail->productFieldName}}";
    					
    					$reserved[$key] = array('sId' => $storeId, 'pId' => $sd->{$Detail->productFieldName}, 'q' => $sd->sum);
    				}
    				
    				// Кеширане
    				core_Permanent::set("reserved_{$sRec->containerId}", $reserved, 4320);
    			}
    			
    			$queue[] = $reserved;
    		}
    	}
    	
    	// Сумиране на к-та
    	foreach ($queue as $arr){
    		foreach ($arr as $key => $obj){
    			if(!array_key_exists($key, $result)){
    				$result[$key] = (object)array('storeId' => $obj['sId'], 'productId' => $obj['pId'], 'reservedQuantity' => $obj['q'], 'state' => 'active');
    			} else {
    				$result[$key]->reservedQuantity += $obj['q'];
    			}
    		}
    	}
    	
    	// Извличане на всички стари записи
    	$storeQuery = static::getQuery();
    	$old = $storeQuery->fetchAll();
    	
    	// Синхронизират се новите със старите записи
    	$res = arr::syncArrays($result, $old, 'storeId,productId', 'reservedQuantity');
    	
    	// Заклюване на процеса
    	if(!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
    		$this->logWarning("Синхронизирането на складовите наличности е заключено от друг процес");
    		return;
    	}
    	
    	// Добавяне и ъпдейт на резервираното количество на новите
    	$this->saveArray($res['insert']);
    	$this->saveArray($res['update'], 'id,reservedQuantity');
    	
    	// Намиране на тези записи, от старите които са имали резервирано к-во, но вече нямат
    	$unsetArr = array_filter($old, function (&$r) use ($result) {
    		if(!isset($r->reservedQuantity)) return FALSE;
    		if(array_key_exists("{$r->storeId}|{$r->productId}", $result)){
    			return FALSE;
    		}
    		 
    		return TRUE;
    	});
    	
    	// Техните резервирани количества се изтриват
    	if(count($unsetArr)){
    		array_walk($unsetArr, function($obj){$obj->reservedQuantity = NULL;});
    		$this->saveArray($unsetArr, 'id,reservedQuantity');
    	}
    		 
    	// Освобождаване на процеса
    	core_Locks::release(self::SYNC_LOCK_KEY);
    }
}