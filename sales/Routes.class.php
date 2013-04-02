<?php



/**
 * Модел  Търговски маршрути
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Routes extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Търговски маршрути';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Търговски маршрут';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, locationId, salesmanId, dateFld, repeatWeeks, state, createdOn, createdBy';
    
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '30';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Created, plg_State2,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'sales,admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'sales';
    
    
    /**
     * Кой може да пише
     */
    var $canAdd = 'sales';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Локация,width=20em,mandatory,silent');
    	$this->FLD('salesmanId', 'user(roles=sales)', 'caption=Търговец,width=15em,mandatory');
    	$this->FLD('dateFld', 'date', 'caption=Посещения->Дата,hint=Кога е първото посещение,width=6em,mandatory');
    	$this->FLD('repeatWeeks', 'int', 'caption=Посещения->Период, unit=седмици, hint=На колко седмици се повтаря посещението,width=6em');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        
        $form->setOptions('locationId', $mvc->getLocationOptions($form->rec));
        $form->setDefault('salesmanId', $mvc->getDefaultSalesman($form->rec));
    }
    
    
    /**
     * Всяка локация я представяме като "<локация> « <име на контрагент>"
     * @param stdClass $rec - запис от модела
     * @return array $options - Масив с локациите и новото
     * им представяне
     */
    private function getLocationOptions($rec)
    {
    	$options = array();
    	$varchar = cls::get("type_Varchar");
    	$locQuery = crm_Locations::getQuery();
    	if($locId = Request::get('locationId')){
    		$locQuery->where("#id = {$locId}");
    	}	
    	
    	while($locRec = $locQuery->fetch()){
        	$locRec = crm_Locations::fetch($locRec->id);
        	$contragentCls = cls::get($locRec->contragentCls);
        	$contagentName =  $contragentCls->fetchField($locRec->contragentId, 'name');
        	$lockName = $varchar->toVerbal($locRec->title) . " « " . $varchar->toVerbal($contagentName);
        	$options[$locRec->id] = $lockName;
        }
        
        return $options;	
    }
    
    
    /**
     * Намираме кой е търговеца по подразбиране, връщаме ид-то на
     * потребителя в следния ред:
     * 1. Търговеца от последния маршрут за тази локация (ако има права)
     * 2. Отговорника на папката на контрагента на локацията (ако има права)
     * 3. Търговеца от последния маршрут създаден от текущия потребителя
     * 4. Текущия потребител ако има права 'sales'
     * 5. NULL - ако никое от горните не е изпълнено
     * @param stdClass $rec - запис от модела
     * @return int - Ид на търговеца, или NULL ако няма
     */
    private function getDefaultSalesman($rec)
    {
    	
    	// Ако имаме локация
    	if($rec->locationId){
    		$query = $this->getQuery();
    		$query->orderBy('#id', 'DESC');
    		$query->where("#locationId = {$rec->locationId}");
    		$lastRec = $query->fetch();
    		if($lastRec){
    			
    			// Ако има последен запис за тази локация
	    		if(self::haveRightFor('add', NULL, $lastRec->salesmanId)) {
		            // ... има право да създава продажби
		            return $lastRec->salesmanId;
        		}
    		}
    		
    		// Ако отговорника на папката има права 'sales'
    		$locRec = crm_Locations::fetch($rec->locationId);
    		$contragentCls = cls::get($locRec->contragentCls);
    		$folderId = $contragentCls->fetchField($locRec->contragentId, 'folderId');
    		$inChargeUserId = doc_Folders::fetchField($folderId, 'inCharge');
        	
    	 	if (self::haveRightFor('add', NULL, $inChargeUserId)) {
	            // ... има право да създава продажби - той става дилър по подразбиране.
	            return $inChargeUserId;
        	}
    	}
    	
    	$currentUserId = core_Users::getCurrent('id');
    	
    	// Ако има последен запис от този потребител
    	$query = $this->getQuery();
    	$query->orderBy('#id', 'DESC');
    	$query->where("#createdBy = {$currentUserId}");
    	$lastRoute = $query->fetch();
    	if($lastRoute){
    		return $lastRoute->salesmanId;
    	}
    	
    	// Текущия потребител ако има права
    	if(self::haveRightFor('add', NULL, $currentUserId)) {
            // ... има право да създава продажби
            return $currentUserId;
        }
        
        // NULL ако никое от горните не е изпълнено
        return NULL;
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
		$data->listFilter->FNC('user', 'user(roles=sales,allowEmpty)', 'input,caption=Търговец,width=15em,placeholder=Потребител,silent');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,width=6em,silent');
		if($mvc->haveRightFor('write')){
			$data->listFilter->setDefault('user', core_Users::getCurrent());
		}
        $data->listFilter->showFields = 'user, date';
		$data->listFilter->input();
	}
	
	
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
    	$locIcon = sbf("img/16/location_pin.png");
    	$row->locationId = ht::createLink($row->locationId, array('crm_Locations', 'single', $rec->locationId, 'ret_url' => TRUE), NULL, array('style' => "background-image:url({$locIcon})", 'class' => 'linkWithIcon'));
    	$locationState = crm_Locations::fetchField($rec->locationId, 'state');
    	if($locationState == 'rejected'){
    		$row->ROW_ATTR['class'] .= ' state-rejected';
    	}
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     */
    public static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
    	$editUrl['locationId'] = $rec->locationId;
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($date = $data->listFilter->rec->date){
    			
    		// Изчисляваме дните между датата от модела и търсената
    		$data->query->XPR("dif", 'int', "DATEDIFF (#dateFld , '{$date}')");
    			
    		// Записа отговаря ако разликата е 0 и повторението е 0
    		$data->query->orWhere("#dif = 0 && #repeatWeeks = 0");
    			
    		// Ако разликата се дели без остатък на 7 * броя повторения
    		$data->query->orWhere("MOD(#dif, (7 * #repeatWeeks)) = 0");
    	}
    	
    	if($salesmanId = $data->listFilter->rec->user){
    			
    		// Филтриране по продавач
    		$data->query->where(array("#salesmanId = [#1#]", $salesmanId));
    	}
    }
    
    
    /**
     * Подготовка на маршрутите, показвани в Single-a на локациите
     */
    function prepareRoutes($data)
    {
    	// Подготвяме маршрутите ако има налични за тази локация
    	$query = $this->getQuery();
    	$query->where(array("#locationId = [#1#]", $data->masterData->rec->id));
    	$query->where("#state != 'rejected'");
    	
    	$results = array();
    	while ($rec = $query->fetch()){
    		$row = static::recToVerbal($rec,'id,salesmanId,tools,-list');
    		$routeArr['tools'] = $row->tools;
    		$routeArr['salesmanId'] = $row->salesmanId;
    		if($data->masterData->rec->state != 'rejected'){
    			$routeArr['nextVisit'] = $this->calcNextVisit($rec);
    		} else {
    			$routeArr['nextVisit'] = tr("Маршрутите са оттеглени");
    		}
    		
    		$results[] = (object)$routeArr;
    	}
    		
    	$data->masterData->row->routes = $results;
    }
    
    
    /**
     * Изчислява кога е следващото посещение на обекта
     * @param stdClass $rec - запис от модела
     * @return string $date - вербално име на следващата дата
     */
    public function calcNextVisit($rec)
    {
    	$nowTs = dt::mysql2timestamp(dt::now());
    	$interval = 24 * 60 * 60 * 7;
    	
    	if(!$rec->dateFld) break;
    	$startTs = dt::mysql2timestamp($rec->dateFld);
    	$diff = $nowTs - $startTs;
    	if($diff < 0){
    		$nextStartTimeTs = $startTs;
    	} else {
    		if(!$rec->repeatWeeks){
    			$rec->repeatWeeks = 1;
    		}
    		$interval = $interval * $rec->repeatWeeks;
    		$nextStartTimeTs = (floor(($diff)/$interval) + 1) * $interval;
    		$nextStartTimeTs = $startTs + $nextStartTimeTs;
    	}
    	
    	$date = dt::timestamp2mysql($nextStartTimeTs);
    	$date = dt::mysql2verbal($date, "d.m.Y D");
    	
    	return  $date;
    }
    
    
	/**
     * След рендирането на Единичния изглед
     */
	function renderRoutes($data)
    {
    	$tpl = new ET(tr("|*" . getFileContent("sales/tpl/Routes.shtml")));
    		
    	// Рендираме информацията за маршрутите
    	$img = sbf('img/16/add.png');
    	$addUrl = array('sales_Routes', 'add', 'locationId' => $data->masterData->rec->id, 'ret_url' => TRUE);
    	$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon'));
    	$tpl->replace($addBtn, 'BTN');
    	if($data->masterData->row->routes){
    		$tpl->replace(' ', 'HEADER');
	    	foreach($data->masterData->row->routes as $route){
	    		$cl = $tpl->getBlock("ROW");
	    		$cl->placeObject($route);
	    		$cl->removeBlocks();
	    		$cl->append2master();
	    	}
    	} else {
    		$tpl->append("<li>" . tr('Не е включена в маршрут') . "</li>", 'ROW');
    	}
    		
    	return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'changestate' && $rec->id) {
			
			// Ако локацията е оттеглена, не позволяваме да се променя
			// състоянието на маршрутите
			$locationState = crm_Locations::fetchField($rec->locationId, 'state');
			if($locationState == 'rejected'){
				$res = 'no_one';
			}
		}
	}
	
	
	/**
	 * Променя състоянието на всички маршрути след промяна на
	 * това на локацията им
	 * @param int $locationId - id на локация
	 */
	public function changeState($locationId)
	{
		$locationState = crm_Locations::fetchField($locationId, 'state');
		$query = $this->getQuery();
		$query->where("#locationId = {$locationId}");
		while($rec = $query->fetch()){
			($locationState == 'rejected') ? $state = 'closed' : $state = 'active';
			$rec->state = $state;
			$this->save($rec);
		}
	}
}