<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ObjectResources extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ресурси на обекти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'likeProductId=Влагане като';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заместващ артикул';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('objectId', 'key(mvc=cat_Products,select=name)', 'input=hidden,caption=Обект,silent');
    	$this->FLD('likeProductId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Влагане като,mandatory,silent');
    	
    	$this->FLD('resourceId', 'int', 'caption=Ресурс,input=none');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,input=none,silent');
    	$this->FLD('conversionRate', 'double(smartRound,Min=0)', 'caption=Отношение,input=none');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('objectId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Коя е мярката на артикула, и намираме всички мерки от същия тип
    	$measureId = cat_Products::getProductInfo($rec->objectId)->productRec->measureId;
    	
    	// Кои са възможните подобни артикули за избор
    	$products = $mvc->getAvailableSimilarProducts($measureId, $rec->objectId);
    	
    	// Добавяме възможностите за избор на заместващи артикули за влагане
    	if(count($products)){
    		$products = array('' => '') + $products;
    		
    		$form->setOptions('likeProductId', $products);
    	} else {
    		$form->setReadOnly('likeProductId');
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle('cat_Products', $rec->objectId, $mvc->singleTitle, $rec->id);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		$equivalentProducts = self::getEquivalentProducts($rec->likeProductId, $rec->id);
    		
    		if(array_key_exists($rec->objectId, $equivalentProducts)){
    			$form->setError('likeProductId', 'Артикулът вече е взаимозаменяем с избрания');
    		}
    	}
    }
    
    
    /**
     * Опции за избиране на всички артикули, като които може да се използва артикула за влагане
     * 
     * @param int $measureId - ид на мярка
     * @return array $products - опции за избор на артикули
     */
    private function getAvailableSimilarProducts($measureId, $productId)
    {
    	$sameTypeMeasures = cat_UoM::getSameTypeMeasures($measureId);
    	
    	// Намираме всички артикули, които са били влагане в производството от документи
    	$consumedProducts = array();
    	$consumedProducts = cat_Products::getByProperty('canConvert');
    	unset($consumedProducts[$productId]);
    	
    	return $consumedProducts;
    }
    
    
    /**
     * Подготвя показването на информацията за влагане
     */
    public function prepareResources(&$data)
    {
    	if(!haveRole('ceo,planning')){
    		$data->notConvertableAnymore = TRUE;
    		return;
    	}
    	
    	$data->rows = array();
    	$query = $this->getQuery();
    	$query->where("#objectId = {$data->masterId}");
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	$pInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(!isset($pInfo->meta['canConvert'])){
    		$data->notConvertableAnymore = TRUE;
    	}
    	
    	if(!(count($data->rows) || isset($pInfo->meta['canConvert']))){
    		return NULL;
    	}
    	
    	$data->TabCaption = 'Влагане';
    	$data->Tab = 'top';
    	$data->listFields = arr::make($this->listFields);
    	
    	if(!Mode::is('printing') && !Mode::is('inlineDocument')) {
    		if(self::haveRightFor('add', (object)array('objectId' => $data->masterId))){
    			$data->addUrl = array($this, 'add', 'objectId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
    	// Ако няма записи и вече не е вложим да не се показва
    	if(!count($data->rows) && $data->notConvertableAnymore){
    		return;
    	}
    	
    	$tpl = getTplFromFile('planning/tpl/ResourceObjectDetail.shtml');
    	
    	if($data->notConvertableAnymore === TRUE){
    		$title = tr('Артикулът вече не е вложим');
    		$title = "<small class='red'>{$title}</small>";
    		$tpl->append($title, 'title');
    		$tpl->replace('state-rejected', 'TAB_STATE');
    	} else {
    		$tpl->append(tr('Влагане'), 'title');
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$this->invoke('BeforeRenderListTable', array($tpl, &$data));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'content');
    	
    	if(isset($data->addUrl)){
    		$addLink = ht::createBtn('Добави', $data->addUrl, FALSE, FALSE, 'ef_icon=img/16/star_2.png,title=Добавяне на информация за влагане');
    		$tpl->append($addLink, 'BTNS');
    	}
    	
    	return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)){
    		
    		$masterRec = cat_Products::fetchRec($rec->objectId);
    		
    		// Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
    		if($masterRec->state != 'active' || !cat_Products::haveRightFor('single', $rec->objectId)){
    			$res = 'no_one';
    		} else {
    			if($pInfo = cat_Products::getProductInfo($rec->objectId)){
    				if(!isset($pInfo->meta['canConvert'])){
    					$res = 'no_one';
    				}
    			}
    		}
    	}
    	 
    	// За да се добави ресурс към обект, трябва самия обект да може да има ресурси
    	if($action == 'add' && isset($rec)){
    		if($mvc->fetch("#objectId = {$rec->objectId}")){
    			$res = 'no_one';
    		}
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
    	if(isset($rec->likeProductId)){
    		$row->likeProductId = cat_Products::getHyperlink($rec->likeProductId, TRUE);
    	}
    }
    
    
    /**
     * Връща себестойността на материала
     *
     * @param int $objectId - ид на артикула - материал
     * @return double $selfValue - себестойността му
     */
    public static function getSelfValue($objectId, $quantity = 1, $date = NULL)
    {
    	if(empty($objectId)) return NULL;
    	
    	// Проверяваме имали зададена търговска себестойност
    	$selfValue = cat_Products::getSelfValue($objectId, NULL, $quantity, $date);
    	
    	// Ако няма търговска себестойност: проверяваме за счетоводна
    	if(!isset($selfValue)){
    		if(!$date){
    			$date = dt::now();
    		}
    			
    		$pInfo = cat_Products::getProductInfo($objectId);
    			
    		// Ако артикула е складируем взимаме среднопритеглената му цена от склада
    		if(isset($pInfo->meta['canStore'])){
    			$selfValue = cat_Products::getWacAmountInStore($quantity, $objectId, $date);
    		} else {
    			$selfValue = static::getWacAmountInProduction($quantity, $objectId, $date);
    		}
    	}
    	
    	return $selfValue;
    }
    
    
    /**
     * Връща среднопритеглената цена на артикула в сметката на незавършеното производство
     * 
     * @param int $quantity      - к-во
     * @param int $objectId      - ид на артикул
     * @param date $date         - към коя дата
     * @return double $selfValue - среднопритеглената цена
     */
    public static function getWacAmountInProduction($quantity, $objectId, $date)
    {
    	// Ако не е складируем взимаме среднопритеглената му цена в производството
    	$item1 = acc_Items::fetchItem('cat_Products', $objectId)->id;
    	if(isset($item1)){
    		// Намираме сумата която струва к-то от артикула в склада
    		$maxTry = core_Packs::getConfigValue('cat', 'CAT_WAC_PRICE_PERIOD_LIMIT');
    		$selfValue = acc_strategy_WAC::getAmount($quantity, $date, '61101', $item1, NULL, NULL, $maxTry);
    		if($selfValue){
    			$selfValue = round($selfValue, 4);
    		}
    	}
    	
    	return $selfValue;
    }
    
    
    /**
     * Намира еквивалентите за влагане артикули на даден артикул
     * 
     * @param int $likeProductId - на кой артикул му търсим еквивалентните
     * @param int $ignoreRecId - ид на ред, който да се игнорира
     * @return array - масив за избор с еквивалентни артикули
     */
    public static function getEquivalentProducts($likeProductId, $ignoreRecId = NULL)
    {
		$array = array();
    	$query = self::getQuery();
    	$query->EXT('state', 'cat_Products', 'externalName=state,externalKey=objectId');
    	$query->where("#state = 'active'");
    	if(isset($ignoreRecId)){
    		$query->where("#id != {$ignoreRecId}");
    	}
    	
    	$query->show("objectId,likeProductId");
    	while ($dRec = $query->fetch()){
    		$array[$dRec->objectId] = $dRec->likeProductId;
    	}
    	
    	$res = array();
    	self::fetchConvertableProducts($likeProductId, $array, $res);
    	foreach ($res as $id => &$v){
    		$v = cat_Products::getTitleById($id, FALSE);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща масив със всички артикули, които могат да се влагат като друг артикул
     * 
     * @param int $productId - ид на продукта, като който ще се влагат
     * @return array - намерените артикули
     */
    private static function fetchConvertableProducts($productId, $array, &$res = array())
    {
    	if(isset($array[$productId]) && $res[$array[$productId]] !== TRUE){
    		$res[$array[$productId]] = TRUE;
    		self::fetchConvertableProducts($array[$productId], $array, $res);
    	}
    	
    	if(is_array($array)){
    		foreach($array as $key => $value){
    			if($value == $productId){
    				if($res[$key] !== TRUE){
    					$res[$key] = TRUE;
    					self::fetchConvertableProducts($key, $array, $res);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Намира средната еденична цена на всички заместващи артикули на подаден артикул
     * 
     * @param int $productId         - артикул, чиято средна цена търсим
     * @param string|NULL $date      - към коя дата
     * @return NULL|double $avgPrice - средна цена
     */
    public static function getAvgPriceEquivalentProducts($productId, $date = NULL)
    {
    	$avgPrice = NULL;
    	expect($productId);
    	
    	// Проверяваме за тази група артикули, имали кеширана средна цена
    	$cachePrice = static::$cache[current(preg_grep("|{$productId}|", array_keys(static::$cache)))];
    	if($cachePrice) return $cachePrice;
    	
    	// Ако артикула не е вложим, не търсим средна цена
    	$isConvertable = cat_Products::fetchField($productId, "canConvert");
    	if($isConvertable != 'yes') return $avgPrice;
    	
    	// Ако няма заместващи артикули, не търсим средна цена
    	$equivalentProducts = static::getEquivalentProducts($productId);
    	if(!count($equivalentProducts)) return $avgPrice;
    	
    	// Ще се опитаме да намерим средната цена на заместващите артикули
    	$priceSum = $count = 0;
    	$listId = price_ListRules::PRICE_LIST_COST;
    	price_ListToCustomers::canonizeTime($date);
    	foreach ($equivalentProducts as $pId => $pName){
    		$price = price_ListRules::getPrice($listId, $pId, NULL, $date);
    		
    		// Ако има себестойност прибавяме я към средната
    		if(isset($price)){
    			$priceSum += $price;
    			$count++;
    		}
    	}
    	
    	// Ако има намерена ненулева цена, изчисляваме средната
    	if($count !== 0){
    		$avgPrice = round($priceSum / $count, 8);
    	}
		
    	// За тази група артикули, кеширваме в паметта средната цена
    	$index = keylist::fromArray($equivalentProducts);
    	static::$cache[$index] = $avgPrice;
    	
    	// Връщаме цената ако е намерена
    	return $avgPrice;
    }
}