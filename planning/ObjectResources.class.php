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
    	
    	$Class = cls::get($rec->classId);
    	$pInfo = $Class->getProductInfo($rec->objectId);
    	
    	$products = array('' => '') + $Class::getByproperty('canConvert');
    	$form->setOptions('likeProductId', $products);
    	
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('selfValue', "unit={$baseCurrencyCode}");
    	
    	$title = ($rec->id) ? 'Редактиране на информацията за влагане на' : 'Добавяне на информация за влагане на';
    	$form->title = $title . "|* <b>". $Class->getTitleByid($rec->objectId) . "</b>";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		if(empty($rec->selfValue) && empty($rec->likeProductId)){
    			$form->setError('selfValue,likeProductId', 'Трябва да има поне едно попълнено поле');
    		}
    		
    		//@TODO да се добавят проверки
    	}
    }
    
    
    /**
     * Подготвя показването на ресурси
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
    	if(!(count($data->rows) || isset($pInfo->meta['canConvert']))){
    		return NULL;
    	}
    	
    	if(!isset($pInfo->meta['canConvert'])){
    		$data->notConvertableAnymore = TRUE;
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
    	$tpl = getTplFromFile('planning/tpl/ResourceObjectDetail.shtml');
    	
    	if($data->notConvertableAnymore === TRUE){
    		$title = tr('Артикула вече не е вложим');
    		$title = "<span class='red'>{$title}</span>";
    		$tpl->append($title, 'title');
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
    	$Source = cls::get($rec->classId);
    	$row->objectId = $Source->getHyperlink($rec->objectId, TRUE);
    	if($Source->fetchField($rec->objectId, 'state') == 'rejected'){
    		$row->objectId = "<span class='state-rejected-link'>{$row->objectId}</span>";
    	}
    	
    	$row->objectId = "<span style='float:left'>{$row->objectId}</span>";
    	
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
     * Връща ресурса на обекта
     * 
     * @param mixed $class - клас
     * @param int $objectId - ид
     * @return mixed - записа на ресурса или FALSE ако няма
     */
    public static function getResource($objectId, $class = 'cat_Products')
    {
    	$Class = cls::get($class);
    	
    	// Проверяваме имали такъв запис
    	if(!$rec = self::fetch("#classId = {$Class->getClassId()} AND #objectId = {$objectId}", 'likeProductId,selfValue')){
    		$rec = (object)array('likeProductId' => NULL, 'selfValue' => NULL,);
    	}
    	
    	// Ако няма твърда себестойност
    	if(!isset($rec->selfValue)){
    		
    		// Проверяваме имали зададена търговска себестойност
    		$rec->selfValue = $Class->getSelfValue($objectId);
    		
    		// Ако няма
    		if(!$rec->selfValue){
    			
    			// Кой баланс ще вземем
    			$lastBalance = acc_Balances::getLastBalance();
    			
    			// Ако има баланс
    			if($lastBalance){
    			
    				// Материала перо ли е ?
    				$objectItem = acc_Items::fetchItem($Class, $objectId);
    				
    				// Ако е перо
    				if($objectItem){
    					
    					// Опитваме се да изчислим последно притеглената му цена
    					$query = acc_BalanceDetails::getQuery();
    					acc_BalanceDetails::filterQuery($query, $lastBalance->id, '321');
    					$prodPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    					
    					$query->where("#ent{$prodPositionId}Id = {$objectItem->id}");
    					$query->XPR('totalQuantity', 'double', 'SUM(#blQuantity)');
    					$query->XPR('totalAmount', 'double', 'SUM(#blAmount)');
    					$res = $query->fetch();
    					
    					// Ако има някакво количество и суми в складовете, натрупваме ги
    					if(!is_null($res->totalQuantity) && !is_null($res->totalAmount)){
    						$totalQuantity = round($res->totalQuantity, 2);
    						$totalAmount = round($res->totalAmount, 2);
    						
    						if($totalAmount == 0){
    							$rec->selfValue = 0;
    						} else {
    							@$rec->selfValue = $totalAmount / $totalQuantity;
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Връщане резултат
    	return $rec;
    }
}