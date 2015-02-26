<?php



/**
 * Модел  Търговски маршрути
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
    var $title = 'Търговски маршрути';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Търговски маршрут';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'contragent=Клиент,locationId,salesmanId,dateFld=Посещения->Начало,repeatWeeks=Посещения->Период,nextVisit=Посещения->Следващо,tools=Пулт,state';
    
    
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '30';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Created,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_State2';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'sales,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да пише
     */
    var $canAdd = 'sales,ceo';
    
    
    /**
     * Кой може да изтрива
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да оттегля
     */
    var $canReject = 'sales,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Локация,mandatory,silent');
    	$this->FLD('salesmanId', 'user(roles=sales,select=nick)', 'caption=Търговец,mandatory');
    	$this->FLD('dateFld', 'date', 'caption=Посещения->Дата,hint=Кога е първото посещение,mandatory');
    	$this->FLD('repeatWeeks', 'int', 'caption=Посещения->Период, unit=седмици, hint=На колко седмици се повтаря посещението');
    	$this->FNC('nextVisit', 'date(format=d.m.Y D)', 'caption=Посещения->Следващо');
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
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
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
    	$locQuery->where("#state = 'active'");
    	if ($locId = Request::get('locationId', 'int')) {
    		$locQuery->where("#id = {$locId}");
    	}	
    	
    	while ($locRec = $locQuery->fetch()) {
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
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->FNC('user', 'user(roles=sales,allowEmpty)', 'input,caption=Търговец,placeholder=Потребител,silent');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,silent');

        $data->listFilter->showFields = 'user, date';
		
        $data->listFilter->input();
		
		$data->query->orderBy("#state");
		
    	if ($data->listFilter->rec->date) {
    			
    		// Изчисляваме дните между датата от модела и търсената
    		$data->query->XPR("dif", 'int', "DATEDIFF (#dateFld , '{$data->listFilter->rec->date}')");
    		
    		// Записа отговаря ако разликата е 0 и повторението е 0
    		$data->query->where("#dif = 0 && (#repeatWeeks = 0 || #repeatWeeks IS NULL)");
    		
    		// Ако разликата се дели без остатък на 7 * броя повторения
    		$data->query->orWhere("MOD(#dif, (7 * #repeatWeeks)) = 0");
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
    			$folderId = cls::get($locationRec->contragentCls)->forceCoverAndFolder($locationRec->contragentId, FALSE);
    			$row->btn = ht::createBtn('ПР', array('sales_Sales', 'add', 'folderId' => $folderId, 'deliveryLocationId' => $rec->locationId), FALSE, FALSE, 'ef_icon=img/16/view.png,title=Създаване на нова продажба към локацията');
    			$row->contragent .= " {$row->btn}";
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
    function prepareRoutes($data)
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
    		if (!$rec->repeatWeeks) {
                if ($rec->dateFld == date('Y-m-d')) {
                    return $rec->dateFld;
                } else {
    			    return FALSE;
                }
    		}
    		$interval = $interval * $rec->repeatWeeks;
    		$nextStartTimeTs = (floor(($diff)/$interval) + 1) * $interval;
    		$nextStartTimeTs = $startTs + $nextStartTimeTs;
    	}
    	
    	$date = dt::timestamp2mysql($nextStartTimeTs);
    	
    	return  $date;
    }
    
    
	/**
     * Рендираме информацията за маршрутите
     */
	function renderRoutes($data)
    {
    	$tpl = getTplFromFile("crm/tpl/ContragentDetail.shtml");
    	$title = $this->title;
    	
    	if ($data->addUrl) {
	    	$img = sbf('img/16/add.png');
	    	$title .= ht::createLink('', $data->addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addRoute', 'title'=>'Създаване на нов търговски маршрут')); 
	    }

    	$tpl->replace($title, 'title');
    	
    	$table = cls::get('core_TableView');
    	$tableTpl = $table->get($data->rows, 'salesmanId=Търговец,nextVisit=Следващо посещение,tools=Пулт');
    	$tpl->append($tableTpl, 'content');

    	return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if ($action == 'edit' && $rec->id) {
			if ($rec->state != 'active') {
				$res = 'no_one';
			}
		}
		
		if (($action == 'add' || $action == 'changestate') && isset($rec->locationId)) {
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
			$state = ($locationState == 'rejected') ? 'closed' : 'active';
			$rec->state = $state;
			$this->save($rec);
		}
	}
}