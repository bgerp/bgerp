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
    public $loadList = 'plg_RowTools, plg_Created, planning_Wrapper';
    
    
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
    public $listFields = 'tools=Пулт,likeProductId=Влагане като,conversionRate=Отношение';
    
    
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
    	$this->FLD('conversionRate', 'double(smartRound,Min=0)', 'caption=Отношение');
    	
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
    	$products = $mvc->getAvailableSimilarProducts($measureId);
    	
    	// Добавяме възможностите за избор на заместващи артикули за влагане
    	if(count($products)){
    		$products = array('' => '') + $products;
    		
    		$form->setOptions('likeProductId', $products);
    	} else {
    		$form->setReadOnly('likeProductId');
    	}
    	
    	$title = ($rec->id) ? 'Редактиране на информацията за влагане на' : 'Добавяне на информация за влагане на';
    	$form->title = $title . "|* <b>". cat_Products::getTitleByid($rec->objectId) . "</b>";
    }
    
    
    /**
     * Опции за избиране на всички артикули, като които може да се използва артикула за влагане
     * 
     * @param int $measureId - ид на мярка
     * @return array $products - опции за избор на артикули
     */
    private function getAvailableSimilarProducts($measureId)
    {
    	$sameTypeMeasures = cat_UoM::getSameTypeMeasures($measureId);
    	
    	// Намираме всички артикули, които са били влагане в производството от документи
    	$consumedProducts = array();
    	$consumedProducts = cat_Products::getByProperty('canConvert');
    	unset($consumedProducts[$rec->objectId]);
    	
    	return $consumedProducts;
    }
    
    
    /**
     * Подготвя показването на информацията за влагане
     */
    public function prepareResources(&$data)
    {
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
    public static function getSelfValue($objectId, $date = NULL)
    {
    	// Проверяваме имали зададена търговска себестойност
    	$selfValue = cat_Products::getSelfValue($objectId, NULL, 1, $date);
    	
    	// Ако няма търговска себестойност: проверяваме за счетоводна
    	if(!isset($selfValue)){
    		if(!$date){
    			$date = dt::now();
    		}
    			
    		$pInfo = cat_Products::getProductInfo($objectId);
    			
    		// Ако артикула е складируем взимаме среднопритеглената му цена от склада
    		if(isset($pInfo->meta['canStore'])){
    			$selfValue = cat_Products::getWacAmountInStore(1, $objectId, $date);
    		} else {
    				
    			// Ако не е складируем взимаме среднопритеглената му цена в производството
    			$item1 = acc_Items::fetchItem('cat_Products', $objectId)->id;
    			if(isset($item1)){
    				// Намираме сумата която струва к-то от артикула в склада
    				$selfValue = acc_strategy_WAC::getAmount(1, $date, '61101', $item1, NULL, NULL);
    				$selfValue = round($selfValue, 4);
    			}
    		}
    	}
    	
    	return $selfValue;
    }
    
    
    /**
     * Връща информацията даден артикул като
     * кой може да се вложи и в какво количество
     * 
     * @param int $productId - ид на артикул
     * @param sdtClass
     * 			o productId - ид на артикула, в който ще се вложи (ако няма такъв се влага в себе си)
     * 			o quantity  - количеството
     */
    public static function getConvertedInfo($productId, $quantity)
    {
    	$convertProductId = $productId;
    	$convertQuantity = $quantity;
    	
    	if($info = planning_ObjectResources::fetch("#objectId = {$productId}")){
    		$convertProductId = $info->likeProductId;
    		
    		if(empty($info->conversionRate)){
    			$mProdMeasureId = cat_Products::getProductInfo($productId)->productRec->measureId;
    			$lProdMeasureId = cat_Products::getProductInfo($info->likeProductId)->productRec->measureId;
    			if($convAmount = cat_UoM::convertValue($convertQuantity, $mProdMeasureId, $lProdMeasureId)){
    				$convertQuantity = $convAmount;
    			}
    		} else {
    			$convertQuantity = $info->conversionRate * $convertQuantity;
    		}
    	}
    	
    	return (object)array('productId' => $convertProductId, 'quantity' => $convertQuantity);
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
    	$query->where("#likeProductId = '{$productId}' AND #objectId IS NOT NULL");
    	$query->EXT('state', 'cat_Products', 'externalName=state,externalKey=objectId');
    	$query->where("#state = 'active'");
    	$query->show("objectId");
    	while($rec = $query->fetch()){
    		$res[$rec->objectId] = cat_Products::getTitleById($rec->objectId, FALSE);
    	}
    	
    	return $res;
    }
}