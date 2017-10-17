<?php



/**
 * Модел  за търговски маршрути
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Routes extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'Търговски маршрути';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Търговски маршрут';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragent=Клиент,locationId,nextVisit=Посещения->Следващо,repeat=Посещения->Период,dateFld=Посещения->Начало,salesmanId,state,createdOn,createdBy';
    
    
	/**
	 * Брой рецепти на страница
	 */
	public $listItemsPerPage = '30';
	
	
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, plg_Created, plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, plg_Rejected, plg_State2';

    
    /**
     * Кой може да пише
     */
    public $canWrite = 'sales,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да пише
     */
    public $canAdd = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да пише
     */
    public $canReject = 'sales,ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'locationId,salesmanId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Локация,mandatory,silent');
    	$this->FLD('salesmanId', 'user(roles=sales|ceo,select=nick)', 'caption=Търговец,mandatory');
    	$this->FLD('dateFld', 'date', 'caption=Посещения->Дата,hint=Кога е първото посещение,mandatory');
    	$this->FLD('repeat', 'time(suggestions=|1 седмица|2 седмици|3 седмици|1 месец)', 'caption=Посещения->Период, hint=на какъв период да е повторението на маршрута');
    	
    	// Изчислимо поле за кога е следващото посещение
    	$this->FLD('nextVisit', 'date(format=d.m.Y D)', 'caption=Посещения->Следващо,input=none');

        $this->setDbIndex('locationId,dateFld');
        $this->setDbIndex('locationId');
        $this->setDbIndex('salesmanId');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	// Добавяме името на контрагента към ключовите дум
    	$locRec = crm_Locations::fetch($rec->locationId);
    	$res .= " " . plg_Search::normalizeText(cls::get($locRec->contragentCls)->getVerbal($locRec->contragentId, 'name'));
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->setDefault('dateFld', dt::today());
        $form->setOptions('locationId', $mvc->getLocationOptions($form->rec));
        $form->setDefault('salesmanId', $mvc->getDefaultSalesman($form->rec));
    }
    
    
    /**
     * Всяка локация я представяме като "<локация> « <име на контрагент>"
     * 
     * @param stdClass $rec - запис от модела
     * @return array $options - Масив с локациите и новото им представяне
     */
    private function getLocationOptions($rec)
    {
    	$options = array();
    	$varchar = cls::get("type_Varchar");
    	$locQuery = crm_Locations::getQuery();
    	$locQuery->where("#state != 'rejected'");
    	if ($locId = Request::get('locationId', 'int')) {
    		$locQuery->where("#id = {$locId}");
    	}	
    	
    	while ($locRec = $locQuery->fetch()) {
        	$locRec = crm_Locations::fetch($locRec->id);
        	if(cls::load($locRec->contragentCls, TRUE)){
        		$contragentCls = cls::get($locRec->contragentCls);
        		$contagentName =  $contragentCls->fetchField($locRec->contragentId, 'name');
        		$lockName = $varchar->toVerbal($locRec->title) . " « " . $varchar->toVerbal($contagentName);
        		$options[$locRec->id] = $lockName;
        	}
        }
        
        return $options;	
    }
    
    
    /**
     * Намираме кой е търговеца по подразбиране, връщаме ид-то на
     * потребителя в следния ред:
     * 
     * 1. Търговеца от последния маршрут за тази локация (ако има права)
     * 2. Отговорника на папката на контрагента на локацията (ако има права)
     * 3. Търговеца от последния маршрут създаден от текущия потребителя
     * 4. Текущия потребител ако има права 'sales'
     * 5. NULL - ако никое от горните не е изпълнено
     * 
     * @param stdClass $rec - запис от модела
     * @return int - Ид на търговеца, или NULL ако няма
     */
    private function getDefaultSalesman($rec)
    {
    	// Ако имаме локация
    	if ($rec->locationId) {
    		$query = $this->getQuery();
    		$query->orderBy('#id', 'DESC');
    		$query->where("#locationId = {$rec->locationId}");
    		$lastRec = $query->fetch();
    		
    		if ($lastRec) {
    			
    			// Ако има последен запис за тази локация
	    		if (self::haveRightFor('add', NULL, $lastRec->salesmanId)) {
		            // ... има право да създава продажби
		            return $lastRec->salesmanId;
        		}
    		}
    		
    		// Ако отговорника на папката има права 'sales'
    		$locRec = crm_Locations::fetch($rec->locationId);
    		$contragentCls = cls::get($locRec->contragentCls);
    		$inCharge = $contragentCls->fetchField($locRec->contragentId, 'inCharge');
    		
    		if (self::haveRightFor('add', NULL, $inCharge)) {
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
    	if($lastRoute) return $lastRoute->salesmanId;
    	
    	// Текущия потребител ако има права
    	if(self::haveRightFor('add', NULL, $currentUserId)) return $currentUserId;
        
        // NULL ако никое от горните не е изпълнено
        return NULL;
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->FNC('user', 'user(roles=sales|ceo,allowEmpty)', 'input,caption=Търговец,placeholder=Търговец,silent,autoFilter');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,silent');

        $data->listFilter->showFields = 'search,user,date';
        $data->listFilter->input();
        $data->query->orderBy("#nextVisit", 'ASC');
        
		// Филтриране по дата
    	if ($data->listFilter->rec->date) {
    		$data->query->where("#nextVisit = '{$data->listFilter->rec->date}'");
    		$data->query->XPR("dif", 'int', "DATEDIFF (#dateFld , '{$data->listFilter->rec->date}')");
    		$data->query->orWhere("MOD(#dif, round(#repeat / 86400 )) = 0");
    	}
    	
    	// Филтриране по продавач
    	if ($data->listFilter->rec->user) {
    		$data->query->where(array("#salesmanId = [#1#]", $data->listFilter->rec->user));
    	}
    	
    	if(Mode::isReadOnly()){
    		unset($data->listFields['state']);
    		unset($data->listFields['createdOn']);
    		unset($data->listFields['createdBy']);
    	}
	}
	
	
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {   
     	$row->locationId = crm_Locations::getHyperLink($rec->locationId, TRUE);
    	$locationState = crm_Locations::fetchField($rec->locationId, 'state');
    	
    	if(!$rec->repeat){
    		$row->repeat = "<span class='quiet'>" . tr('еднократно') . "</span>";
    	}
    	
    	$locationRec = crm_Locations::fetch($rec->locationId);
    	$row->contragent = cls::get($locationRec->contragentCls)->getHyperLink($locationRec->contragentId, TRUE); 
    	
    	if($rec->state == 'active'){
    		if(!Mode::isReadOnly()){
    			if(crm_Locations::haveRightFor('createsale', $rec->locationId)){
    				core_RowToolbar::createIfNotExists($row->_rowTools);
    				$row->_rowTools->addLink('Продажба', array('crm_Locations', 'createSale', $rec->locationId, 'ret_url' => TRUE), 'ef_icon=img/16/cart_go.png,title=Създаване на нова продажба към локацията');
    			}
    		}
    	} else {
    		unset($row->nextVisit);
    	}
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     */
    protected static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
    	$editUrl['locationId'] = $rec->locationId;
    }

    
    /**
     * Подготовка на маршрутите, показвани в Single-a на локациите
     */
    public function prepareRoutes($data)
    {
    	// Подготвяме маршрутите ако има налични за тази локация
    	$query = $this->getQuery();
    	$query->where(array("#locationId = [#1#]", $data->masterData->rec->id));
    	$query->where("#state = 'active'");
    	
    	$results = array();
     	while ($rec = $query->fetch()) {
			$data->rows[$rec->id] = static::recToVerbal($rec);
    	}
    		
    	if ($this->haveRightFor('add', (object)(array('locationId' => $data->masterData->rec->id)))) {
	    	$data->addUrl = array('sales_Routes', 'add', 'locationId' => $data->masterData->rec->id, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Изчислява кога е следващото посещение на обекта
     * @param stdClass $rec - запис от модела
     * @return string $date - вербално име на следващата дата
     */
    public function getNextVisit($rec)
    {
    	$nowTs = dt::mysql2timestamp(dt::now());
    	$interval = 24 * 60 * 60 * 7;
		
    	if (!$rec->dateFld) return FALSE;

    	$startTs = dt::mysql2timestamp($rec->dateFld);
    	$diff = $nowTs - $startTs;
    	if ($diff < 0) {
    		$nextStartTimeTs = $startTs;
    	} else {
    		if (!$rec->repeat) {
                if ($rec->dateFld == date('Y-m-d')) {
                    return $rec->dateFld;
                } else {
    			    return FALSE;
                }
    		}
    		
    		$repeat = $rec->repeat / (60 * 60 * 24 * 7);
    		$interval = $interval * $repeat;
    		$nextStartTimeTs = (floor(($diff)/$interval) + 1) * $interval;
    		$nextStartTimeTs = $startTs + $nextStartTimeTs;
    	}
    	
    	$date = dt::timestamp2mysql($nextStartTimeTs + 10 * 60 * 60);
    	$date = dt::verbal2mysql($date, FALSE);
    	
    	return  $date;
    }
    
    
	/**
     * Рендираме информацията за маршрутите
     */
	public function renderRoutes($data)
    {
    	$tpl = getTplFromFile("sales/tpl/SingleLayoutRoutes.shtml");
    	$title = $this->title;
    	$listFields = arr::make('salesmanId=Търговец,repeat=Период,nextVisit=Следващо посещение');
    	
    	if(!Mode::isReadOnly()){
    		if($this->haveRightFor('list')){
    			$title = ht::createLink($title, array($this, 'list'), FALSE, 'title=Всички търговски маршрути');
    		}
    	} else {
    		unset($listFields['tools']);
    	}
    	
    	if ($data->addUrl) {
	    	$title .= ht::createLink('', $data->addUrl, NULL, array('ef_icon' => 'img/16/add.png', 'class' => 'addRoute', 'title'=>'Създаване на нов търговски маршрут')); 
	    }

    	$tpl->replace($title, 'title');
    	
    	$table = cls::get('core_TableView');
    	
    	$data->listFields = $listFields;
    	$this->invoke('BeforeRenderListTable', array($data, $data));
    	
    	$tableTpl = $table->get($data->rows, $data->listFields);
    	$tpl->append($tableTpl, 'content');

    	return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'edit' && $rec->id) {
			if ($rec->state == 'rejected') {
				$res = 'no_one';
			}
		}
		
		if(($action == 'add' || $action == 'restore') && isset($rec->locationId)) {
			if (crm_Locations::fetchField($rec->locationId, 'state') == 'rejected') {
				$res = 'no_one';
			}
		}
		
		if($action == 'reject' && isset($rec)){
			if($rec->state == 'closed'){
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
		while ($rec = $query->fetch()) {
			$state = ($locationState == 'rejected') ? 'rejected' : 'active';
			$rec->state = $state;
			$this->save($rec);
		}
	}
	
	
	/**
	 * Връща търговеца с най-близък маршрут
	 * 
	 * @param int $locationId - ид на локация
	 * @param string $date    - дата, NULL за текущата дата
	 * @return $salesmanId    - ид на търговец
	 */
	public static function getSalesmanId($locationId, $date = NULL)
	{
		$date = (isset($date)) ? $date : dt::today();
		$date2 = new DateTime($date);
		$cu = core_Users::getCurrent();
		
		$salesmanId = NULL;
		$arr = array();
		
		// Намираме и подреждаме всички маршрути към локацията
		$query = self::getQuery();
		$query->where("#locationId = '{$locationId}'");
		$query->orderBy("createdOn", 'DESC');
		
		// За всяка
		while($rec = $query->fetch()){
			
			// Ако маршрута е от текущия потребител, винаги е с приоритет
			if($rec->salesmanId == $cu){
				$date1 = $date;
			} else {
				// Ако има дата на доставка, нея, ако няма слагаме -10 години, за да излезе най-отдолу
				$date1 = (isset($rec->nextVisit)) ? $rec->nextVisit : dt::verbal2mysql(dt::addMonths(-1 * 10 * 12, $date), FALSE);
			}
			
			// Колко е разликата между датите
			$date1 = new DateTime($date1);
			$interval = date_diff($date1, $date2);
			
			// Добавяме в масива
			$arr[] = (object)array('diff' => $interval->days, 'salesmanId' => $rec->salesmanId, 'id' => $rec->id);
		}
		
		// Ако няма маршрути, връщаме
		if(!count($arr)) return $salesmanId;
		
		// Сортираме по разликата
		arr::order($arr, 'diff', 'ASC');
		$first = $arr[key($arr)];
		$salesmanId = $first->salesmanId;
		
		// Връщаме най-новия запис с най-малка разлика
		return $salesmanId;
	}
	
	
	/**
	 * Изчисляване на следващото посещение по разписание
	 */
	function cron_calcNextVisit()
	{
		$today = dt::today();
		
		$delayDays = sales_Setup::get('ROUTES_CLOSE_DELAY');
		$before = dt::addSecs(-1 * 60 * 60* 24 * $delayDays, $today);
		$before = dt::verbal2mysql($before, FALSE);
		
		$updateState = $updateNextVisit = array();
		$query = self::getQuery();
		$query->where("#state = 'active'");
		$query->where("#nextVisit IS NULL OR #nextVisit != '{$today}'");
		
		// Дигане на тайм лимита
		$count = $query->count();
		core_App::setTimeLimit(0.7 * $count);
		
		// За всеки запис
		while($rec = $query->fetch()){
			if(empty($rec->repeat) && $rec->dateFld < $before){
				
				// Ако е еднократен и е минал, затваря се
				$rec->state = 'closed';
				$updateState[$rec->id] = $rec;
			} else {
				
				// Ако има повторение прави се опит за изчисляване на следващото посещение
				if($next = $this->getNextVisit($rec)){
    				$rec->nextVisit = $next;
    				$updateNextVisit[$rec->id] = $rec;
    			}
			}
		}
		
		// Обновяване на състоянията
		if(count($updateState)){
			$this->saveArray($updateState, 'id,state');
		}
		
		// Обновяване на следващото изпълнение
		if(count($updateNextVisit)){
			$this->saveArray($updateNextVisit, 'id,nextVisit');
		}
	}
	
	
	/**
	 * Преди запис на документ, изчислява стойността на полето `isContable`
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 */
	protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		if($rec->state != 'rejected' || $rec->state != 'closed'){
			if($next = $mvc->getNextVisit($rec)){
				$rec->nextVisit = $next;
			}
		}
	}
}