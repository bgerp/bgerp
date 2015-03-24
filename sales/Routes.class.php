<?php



/**
 * Модел  за търговски маршрути
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
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
    public $listFields = 'id,contragent=Клиент,locationId,salesmanId,dateFld=Посещения->Начало,repeat=Посещения->Период,nextVisit=Посещения->Следващо,tools=Пулт';
    
    
	/**
	 * Брой рецепти на страница
	 */
	public $listItemsPerPage = '30';
	
	
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Created,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, plg_Rejected, plg_State';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата.
     */
    public $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    public $canRead = 'sales,ceo';
    
    
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
    	$this->FLD('state',
    			'enum(active=Активен,rejected=Оттеглен)',
    			'caption=Видимост,input=none,notSorting,notNull,value=active');
    	
    	// Изчислимо поле за кога е следващото посещение
    	$this->FNC('nextVisit', 'date(format=d.m.Y D)', 'caption=Посещения->Следващо');
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	}
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
     * Изчисление на следващото посещение ако може
     */
    protected static function on_CalcNextVisit(core_Mvc $mvc, $rec) 
    {
    	if (empty($rec->dateFld)) return;
    	
    	if($next = $mvc->getNextVisit($rec)){
    		$rec->nextVisit = $next;
    	}
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
    	if ($lastRoute) {
    	    
    		return $lastRoute->salesmanId;
    	}
    	
    	// Текущия потребител ако има права
    	if (self::haveRightFor('add', NULL, $currentUserId)) {
            // ... има право да създава продажби
            return $currentUserId;
        }
        
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
		$data->listFilter->FNC('user', 'user(roles=sales|ceo,allowEmpty)', 'input,caption=Търговец,placeholder=Потребител,silent,refreshForm');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,silent');

        $data->listFilter->showFields = 'search,user, date';
		
        $data->listFilter->input();
		
		$data->query->orderBy("#state");
		
    	if ($data->listFilter->rec->date) {
    			
    		// Изчисляваме дните между датата от модела и търсената
    		$data->query->XPR("dif", 'int', "DATEDIFF (#dateFld , '{$data->listFilter->rec->date}')");
    		$data->query->where("#dateFld = '{$data->listFilter->rec->date}'");
    		$data->query->orWhere("MOD(#dif, round(#repeat / 86400 )) = 0");
    	}
    	
    	if ($data->listFilter->rec->user) {
    			
    		// Филтриране по продавач
    		$data->query->where(array("#salesmanId = [#1#]", $data->listFilter->rec->user));
    	}
	}
	
	
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {   
    	$locIcon = sbf("img/16/location_pin.png");
    	$row->locationId = ht::createLink($row->locationId, array('crm_Locations', 'single', $rec->locationId, 'ret_url' => TRUE), NULL, array('style' => "background-image:url({$locIcon})", 'class' => 'linkWithIcon'));
    	$locationState = crm_Locations::fetchField($rec->locationId, 'state');
    	
    	$locationRec = crm_Locations::fetch($rec->locationId);
    	
    	$row->contragent = cls::get($locationRec->contragentCls)->getHyperLink($locationRec->contragentId); 
    	
    	if($rec->state == 'active'){
    		if(sales_Sales::haveRightFor('add')){
    			$folderId = cls::get($locationRec->contragentCls)->forceCoverAndFolder($locationRec->contragentId);
    			$row->btn = ht::createBtn('ПР', array('sales_Sales', 'add', 'folderId' => $folderId, 'deliveryLocationId' => $rec->locationId), FALSE, FALSE, 'ef_icon=img/16/view.png,title=Създаване на нова продажба към локацията');
    			$row->contragent = "{$row->btn} " . $row->contragent;
    		}
    	} else {
    		unset($row->nextVisit);
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
            if(!isset($rec->nextVisit)) continue;
			$data->rows[$rec->id] = static::recToVerbal($rec);
    	}

        if(is_array($data->rows) && count($data->rows) > 1) {
            arr::order($data->rows, 'nextVisit');
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
    	
    	$date = dt::timestamp2mysql($nextStartTimeTs);
    	
    	return  $date;
    }
    
    
	/**
     * Рендираме информацията за маршрутите
     */
	public function renderRoutes($data)
    {
    	$tpl = getTplFromFile("sales/tpl/SingleLayoutRoutes.shtml");
    	$title = $this->title;
    	if($this->haveRightFor('list')){
    		$title = ht::createLink($title, array($this, 'list'), FALSE, 'title=Всички търговските маршрути');
    	}
    	
    	if ($data->addUrl) {
	    	$img = sbf('img/16/add.png');
	    	$title .= ht::createLink('', $data->addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addRoute', 'title'=>'Създаване на нов търговски маршрут')); 
	    }

    	$tpl->replace($title, 'title');
    	
    	$table = cls::get('core_TableView');
    	$tableTpl = $table->get($data->rows, 'salesmanId=Търговец,repeat=Период,nextVisit=Следващо посещение,tools=Пулт');
    	$tpl->append($tableTpl, 'content');

    	return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if ($action == 'edit' && $rec->id) {
			if ($rec->state != 'active') {
				$res = 'no_one';
			}
		}
		
		if (($action == 'add' || $action == 'restore') && isset($rec->locationId)) {
			if (crm_Locations::fetchField($rec->locationId, 'state') == 'rejected') {
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
}