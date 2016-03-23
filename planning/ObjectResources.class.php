<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ObjectResources extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ObjectResources';
	
	
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
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Информация за влагане';
    
    
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
    	$url = cat_Products::getSingleUrlArray($data->form->rec->objectId);
    	$objectName = cat_Products::getTitleById($data->form->rec->objectId);
    	$objectName = ht::createLink($objectName, $url, NULL, array('ef_icon' => cls::get('cat_Products')->singleIcon, 'class' => 'linkInTitle'));
    	
    	$title = ($data->form->rec->id) ? 'Редактиране на информацията за влагане на' : 'Добавяне на информация за влагане на';
    	$data->form->title = $title . "|* <b style='color:#ffffcc;'>". $objectName . "</b>";
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
    	
    	if(!Mode::is('printing')) {
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
    		$title = tr('Артикула вече не е вложим');
    		$title = "<small class='red'>{$title}</small>";
    		$tpl->append($title, 'title');
    		$tpl->replace('state-rejected', 'TAB_STATE');
    	} else {
    		$tpl->append(tr('Влагане'), 'title');
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	if(!count($data->rows)){
    		unset($fields['tools']);
    	}
    	
    	$tpl->append($table->get($data->rows, $this->listFields), 'content');
    	
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
    	
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако обекта е използван вече в протокол за влагане, да не може да се изтрива докато протокола е активен
    		$consumptionQuery = planning_ConsumptionNoteDetails::getQuery();
    		$consumptionQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
    		if($consumptionQuery->fetch("#productId = {$rec->objectId} AND #state = 'active'")){
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
    	
    	if(!$rec->conversionRate){
    		$row->conversionRate = 1;
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Връща себестойността на материала
     *
     * @param int $objectId - ид на артикула - материал
     * @return double $selfValue - себестойността му
     */
    public static function getSelfValue($objectId, $quantity = 1, $date = NULL)
    {
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
    		$selfValue = acc_strategy_WAC::getAmount($quantity, $date, '61101', $item1, NULL, NULL);
    		if($selfValue){
    			$selfValue = round($selfValue, 4);
    		}
    	}
    	
    	return $selfValue;
    }
    
    
    /**
     * Връща масив със всички артикули, които могат да се влагат като друг артикул
     * 
     * @param int $productId - ид на продукта, като който ще се влагат
     * @return array - намерените артикули
     */
    public static function fetchConvertableProducts($productId)
    {
    	$res = array();
    	
    	$query = self::getQuery();
    	$query->EXT('state', 'cat_Products', 'externalName=state,externalKey=objectId');
    	$query->where("#state = 'active'");
    	$query->show("objectId,likeProductId");
    	
    	$query2 = clone $query;
    	$query->where("#likeProductId = '{$productId}' AND #objectId IS NOT NULL AND #objectId != '{$productId}'");
    	while($rec = $query->fetch()){
    		$res[$rec->objectId] = cat_Products::getTitleById($rec->objectId, FALSE);
    	}
    	
    	$query2->where("#objectId = {$productId} AND #likeProductId != {$productId}");
    	while($rec = $query2->fetch()){
    		if($rec->likeProductId){
    			$res[$rec->likeProductId] = cat_Products::getTitleById($rec->likeProductId, FALSE);
    			$replaceable = self::fetchConvertableProducts($rec->likeProductId);
    			$res += $replaceable;
    		}
    	}
    	
    	return $res;
    }
}