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
    var $listFields = 'tools=Пулт, locationId, salesmanId, date1, repeatWeeks1, date2, repeatWeeks2, date3, repeatWeeks3, date4, repeatWeeks4, state, openSale=Продажба, createdOn, createdBy';
    
	
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
		$data->listFilter->FNC('user', 'user(role=salesman)', 'input,caption=Търговец,width=15em,silent');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,width=6em,silent');
		$data->listFilter->showFields = 'user, date';
		$data->listFilter->input();
	}
	
	
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
    	//@TODO да заменя NULL с валиден УРЛ
    	$row->openSale = ht::createBtn('Продажба', NULL);
    	
    	$locIcon = sbf("img/16/location_pin.png");
    	$row->locationId = ht::createLink($row->locationId, array('crm_Locations', 'single', $rec->id, 'ret_url' => TRUE), NULL, array('style' => "background-image:url({$locIcon})", 'class' => 'linkWithIcon'));
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($salesmanId = $data->listFilter->rec->user){
    		$data->query->where(array("#salesmanId = [#1#]", $salesmanId));
    	}
    }
    
    
    /**
     *  Извиква се след подготовка на резултатите
     */
    static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
    	// Ако филтрираме по дата
    	if($date = $data->listFilter->rec->date){
    		if($data->recs){
	    		foreach($data->recs as $rec) {
					$check = 0;
					foreach(range(1, 4) as $i){
						$dateFld = $rec->{"date{$i}"};
						$weekFld = $rec->{"repeatWeeks{$i}"};
						
						// За всяка от датите в записа изчисляваме отговаряли на търсената
						$res = $mvc->calcDaysDiff($date, $dateFld, $weekFld);
						
						// Ако поне една от датите отговаря инкрементираме $check
						if($res) $check++;
					}

					// Ако никоя от датите не отговаря на условието ънсетваме записа
					if($check == 0){
						unset($data->recs[$rec->id]);
					}
				}
    		}
    	}
    }
    
    
    /**
     * Функция проверяваща дали дата от модела отговаря на търсенето по дата.
     * @param date $date - търсената дата
     * @param date $dateRec - датата от модела
     * @param int $repeat - брой повторения на седмици
     * @return boolean TRUE/FALSE - дали датата отговаря или не
     */
    private function calcDaysDiff($date, $dateRec, $repeat)
    {
    	// Намираме дните между двете дати
    	$daysBetween = dt::daysBetween($date, $dateRec);
     	if($repeat == 0 && $daysBetween == 0) return TRUE;
    	$weeks = 7 * $repeat;
    	
    	// Ако дните са кратни на броя на седмиците значи датата отговаря
     	if($daysBetween % $weeks == 0) return TRUE;
	    
     	// Ако ние от горните не е изпълнено значи датата не отговаря
	    return FALSE;
    }
    
    
    /**
     * Връща всички маршрути за дадена локация, FALSE ако няма записи
     * @param int $locationId - id на локация
     * @return mixed array/FALSE - резултата от заявката
     */
    public static function fetchByLocation($locationId)
    {
    	expect(crm_Locations::fetch($locationId), "Няма такава локация");
    	
    	$query = static::getQuery();
    	$query->where(array("#locationId = [#1#]", $locationId));
    	if($query->count() == 0) return FALSE;
    	
    	$results = array();
    	while($rec = $query->fetch()){
    		$results[$rec->id] = static::recToVerbal($rec);
    	}
    	
    	return $results;
    }
    
    
    /**
     * 
     */
    function prepareRoutes($data)
    {
    	// Подготвяме маршрутите ако има налични за тази локация
    	if($routes = sales_Routes::fetchByLocation($data->masterData->rec->id)){
    		$data->masterData->row->routes = $routes;
    	}
    }
    
    
	/**
     * След рендирането на Единичния изглед
     */
	function renderRoutes($data)
    {
    	if($data->masterData->row->routes){
    		$tpl = new ET(tr("|*" . getFileContent("sales/tpl/Routes.shtml")));
    		
    		// Рендираме информацията за маршрутите
    		$img = sbf('img/16/add.png');
    		$addUrl = array('sales_Routes', 'add', 'locationId' => $data->rec->id, 'ret_url' => TRUE);
    		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon'));
    		
    		$tpl->replace($addBtn, 'BTN');
    		foreach($data->masterData->row->routes as $route){
    			$cl = $tpl->getBlock("ROW");
    			$cl->placeObject($route);
    			$cl->removeBlocks();
    			$cl->append2master();
    		}
    		
    		return $tpl;
    	}
    }
}