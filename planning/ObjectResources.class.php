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
    public $listFields = 'tools=Пулт,likeProductId=Влагане като,selfValue=Себестойност';
    
    
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
    	$this->FLD('classId', 'class(interface=cat_ProductAccRegIntf)', 'input=hidden,silent');
    	$this->FLD('objectId', 'int', 'input=hidden,caption=Обект,silent');
    	$this->FLD('likeProductId', 'key(mvc=cat_Products,select=name)', 'caption=Влагане като');
    	
    	$this->FLD('resourceId', 'key(mvc=planning_Resources,select=title,allowEmpty,makeLink)', 'caption=Ресурс,input=none');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,input=none,silent');
    	$this->FLD('conversionRate', 'double(smartRound)', 'caption=Конверсия,silent,notNull,value=1,input=none');
    	$this->FLD('selfValue', 'double(decimals=2)', 'caption=Себестойност');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('classId,objectId');
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
    	
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('selfValue', "unit={$baseCurrencyCode}");
    	
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
    	$cQuery = planning_ConsumptionNoteDetails::getQuery();
    	$cQuery->EXT('state', 'planning_ConsumptionNotes', 'externalKey=noteId');
    	$cQuery->where("#state = 'active'");
    	$cQuery->show('productId');
    	while ($cRec = $cQuery->fetch()){
    		$consumedProducts[$cRec->productId] = $cRec->productId;
    	}
    	 
    	$dQuery = planning_DirectProductNoteDetails::getQuery();
    	$dQuery->EXT('state', 'planning_DirectProductionNote', 'externalKey=noteId');
    	$dQuery->where("#state = 'active'");
    	$dQuery->where("#type = 'input'");
    	while ($dRec = $dQuery->fetch()){
    		$consumedProducts[$dRec->productId] = $dRec->productId;
    	}
    	 
    	// Не може да се избере текущия артикул
    	unset($consumedProducts[$rec->objectId]);
    	 
    	// Намираме всички вложими артикули
    	$products = cat_Products::getByproperty('canConvert');
    	
    	if(count($products)){
    		foreach ($products as $id => $p){
    			 
    			if(!is_object($p)){
    	
    				// Ако артикула е вложим, но не е участвал в документ - махаме го
    				if(empty($consumedProducts[$id])){
    					unset($products[$id]);
    				} else {
    						
    					// Ако мярката на артикула е от друг тип - също го мяхаме
    					// Артикул може да бъде заместван само с артикул с подобна мярка
    					$mId = cat_Products::getProductInfo($id)->productRec->measureId;
    					if(empty($sameTypeMeasures[$mId])){
    						unset($products[$id]);
    					}
    				}
    			} else {
    				unset($products[$id]);
    			}
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		if(!isset($rec->selfValue) && !isset($rec->likeProductId)){
    			$form->setError('selfValue,likeProductId', 'Поне едно от полетата трябва да е попълнено');
    		}
    	}
    }
    
    
    /**
     * Подготвя показването на информацията за влагане
     */
    public function prepareResources(&$data)
    {
    	$data->rows = array();
    	$classId = $data->masterMvc->getClassId();
    	$query = $this->getQuery();
    	$query->where("#classId = {$classId} AND #objectId = {$data->masterId}");
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
    		if(self::haveRightFor('add', (object)array('classId' => $classId, 'objectId' => $data->masterId))){
    			$data->addUrl = array($this, 'add', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
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
    		
    		$Class = cls::get($rec->classId);
    		$masterRec = $Class->fetchRec($rec->objectId);
    		
    		// Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
    		if($masterRec->state != 'active' || !$Class->haveRightFor('single', $rec->objectId)){
    			$res = 'no_one';
    		} else {
    			if($pInfo = cls::get($rec->classId)->getProductInfo($rec->objectId)){
    				if(!isset($pInfo->meta['canConvert'])){
    					$res = 'no_one';
    				}
    			}
    		}
    	}
    	 
    	// За да се добави ресурс към обект, трябва самия обект да може да има ресурси
    	if($action == 'add' && isset($rec)){
    		if($mvc->fetch("#classId = {$rec->classId} AND #objectId = {$rec->objectId}")){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако обекта е използван вече в протокол за влагане, да не може да се изтрива докато протокола е активен
    		$consumptionQuery = planning_ConsumptionNoteDetails::getQuery();
    		$consumptionQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
    		if($consumptionQuery->fetch("#classId = {$rec->classId} AND #productId = {$rec->objectId} AND #state = 'active'")){
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
    	if(isset($rec->selfValue)){
    		$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    		$row->selfValue = "{$row->selfValue} <span class='cCode'>{$baseCurrencyCode}</span>";
    	}
    	
    	if(isset($rec->likeProductId)){
    		$row->likeProductId = cat_Products::getHyperlink($rec->likeProductId, TRUE);
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
     * @return double $selfValue -себестойността му
     */
    public static function getSelfValue($objectId)
    {
    	$selfValue = self::fetchField(array("#objectId = '[#1#]'", $objectId), 'selfValue');
    	
    	if(!isset($selfValue)){
    		
    		// Проверяваме имали зададена търговска себестойност
    		$selfValue = cls::get('cat_Products')->getSelfValue($objectId);
    		
    		// Ако няма търговска себестойност: проверяваме за счетоводна
    		if(!isset($selfValue)){
    			$date = dt::now();
    			$selfValue = cat_Products::getWacAmountInStore(1, $objectId, $date);
    		}
    	}
    	
    	return $selfValue;
    }
}