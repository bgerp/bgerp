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
    var $listFields = 'tools=Пулт, locationId, salesmanId, date1, repeatWeeks1, date2, repeatWeeks2, date3, repeatWeeks3, date4, repeatWeeks4, state, createdOn, createdBy';
    
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '30';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper,plg_Created, plg_State2,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'sales, admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'sales, admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Локация,width=15em,mandatory,silent');
    	$this->FLD('salesmanId', 'user(role=salesman)', 'caption=Търговец,width=15em,mandatory');
    	$this->FLD('date1', 'date', 'caption=Посещение 1->Дата,hint=Кога е първото посещение,width=6em,mandatory');
    	$this->FLD('repeatWeeks1', 'int', 'caption=Посещение 1->Период, unit=седмици, hint=На колко седмици се повтаря посещението,width=6em,mandatory');
    	$this->FLD('date2', 'date', 'caption=Посещение 2->Дата,hint=Кога е второто посещение,width=6em');
    	$this->FLD('repeatWeeks2', 'int', 'caption=Посещение 2->Период, unit=седмици, hint=На колко седмици се повтаря посещението,width=6em');
    	$this->FLD('date3', 'date', 'caption=Посещение 3->Дата,hint=Кога е третото посещение,width=6em');
    	$this->FLD('repeatWeeks3', 'int', 'caption=Посещение 3->Период, unit=седмици, hint=На колко седмици се повтаря посещението,width=6em');
    	$this->FLD('date4', 'date', 'caption=Посещение 4->Дата,hint=Кога е четвъртото посещение,width=6em');
    	$this->FLD('repeatWeeks4', 'int', 'caption=Посещение 4->Период, unit=седмици, hint=На колко седмици се повтаря посещението,width=6em');
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
		$data->listFilter->FNC('user', 'user(role=salesman,allowEmpty)', 'input,caption=Търговец,width=15em,silent');
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
    	$row->locationId = ht::createLink($row->locationId, array('crm_Locations', 'single', $rec->id, 'ret_url' => TRUE), NULL, array('style' => "background-image:url({$locIcon})", 'class' => 'linkWithIcon'));
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($date = $data->listFilter->rec->date){
    		
    		// За всяка една от четирите дати проверяваме отговаряли на
    		// посочената дата, ако поне една отговаря то и записа отговаря
    		foreach(range(1,4) as $i){
    			
    			// Изчисляваме дните между датата от модела и търсената
    			$data->query->XPR("dif{$i}", 'int', "DATEDIFF (#date{$i} , '{$date}')");
    			
    			// Записа отговаря ако разликата е 0 и повторението е 0
    			$data->query->orWhere("#dif{$i} = 0 && #repeatWeeks{$i} = 0");
    			
    			// Ако ралзиката се дели без остатък на 7 * броя повторения
    			$data->query->orWhere("MOD(#dif{$i}, (7 * #repeatWeeks{$i})) = 0");
    		}
    		
    		if($salesmanId = $data->listFilter->rec->user){
    			
    			// Филтриране по продавач
    			$data->query->where(array("#salesmanId = [#1#]", $salesmanId));
    		}
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
    	
    		$results = array();
    		while ($rec = $query->fetch()){
    			$row = static::recToVerbal($rec,'id,salesmanId,tools,-list');
    			$routeArr['tools'] = $row->tools;
    			$routeArr['salesmanId'] = $row->salesmanId;
    			$routeArr['nextVisit'] = $this->calcNextVisit($rec);
    			$results[] = (object)$routeArr;
    		}
    		
    		$data->masterData->row->routes = $results;
    	
    }
    
    
    /**
     * 
     * Изчислява кога е следващото посещение на обекта
     * @param stdClass $rec - запис от модела
     * @return string $date - вербално име на следващата дата
     */
    public function calcNextVisit($rec)
    {
    	$nowTs = dt::mysql2timestamp(dt::now());
    	$interval = 24 * 60 * 60 * 7;
    	foreach (range(1, 4) as $i){
    		if(!$rec->{"date{$i}"}) break;
    		$startTs = dt::mysql2timestamp($rec->{"date{$i}"});
    		$diff = $nowTs - $startTs;
    		if($diff < 0){
    			$nextStartTimeTs = $startTs;
    		} else {
    			$interval = $interval * $rec->{"repeatWeeks{$i}"};
    			$nextStartTimeTs = (floor(($diff)/$interval) + 1) * $interval;
    			$nextStartTimeTs = $startTs + $nextStartTimeTs;
    		}
    		
    		if($i == 1) {
    			$nextVisit = $nextStartTimeTs;
    		} else {
    			if($nextStartTimeTs <= $nextVisit){
    				$nextVisit = $nextStartTimeTs;
    			}
    		}
    	}
    	
    	$date = dt::timestamp2mysql($nextVisit);
    	$date = dt::mysql2verbal($date, "m.Y D");
    	
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
    		foreach($data->masterData->row->routes as $route){
    			$cl = $tpl->getBlock("ROW");
    			$cl->placeObject($route);
    			$cl->removeBlocks();
    			$cl->append2master();
    		}
    		}
    	return $tpl;
    }
}