<?php



/**
 * Ценови политики към клиенти
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценови политики към клиенти
 */
class price_ListToCustomers extends core_Manager
{
    
	
    /**
     * Заглавие
     */
    public $title = 'Ценови политики към клиенти';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Ценова политика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, price_Wrapper, plg_RowTools2';
                    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId=Политика, cClass=Контрагент, validFrom=В сила от, createdBy=Създаване->От, createdOn=Създаване->На,state=Състояние';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'price,sales,ceo';
    

    /**
     * Кой има право да листва?
     */
    public $canList = 'price,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'price,sales,ceo';
    

    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'за';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Политика');
        $this->FLD('cClass', 'class(select=title,interface=crm_ContragentAccRegIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');
        $this->FLD('validFrom', 'datetime(format=smartTime)', 'caption=В сила от');
        $this->FLD('state', 'enum(closed=Неактивен,active=Активен)', 'caption=Състояние,input=none');
        $this->EXT('listState', 'price_Lists', 'externalName=state,externalKey=listId');
        
        $this->setDbIndex('cClass,cId');
        $this->setDbIndex('state');
        $this->setDbIndex('listId');
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            $rec = $form->rec;

            $now = dt::verbal2mysql();

            if(!$rec->validFrom) {
                $rec->validFrom = $now;
            }

            if($rec->validFrom && !$form->gotErrors() && $rec->validFrom > $now) {
                Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
            }
        }
    }


    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }

        $rec->listId = self::getListForCustomer($rec->cClass, $rec->cId);
  		
        $data->form->setOptions('listId', price_Lists::getAccessibleOptions($rec->cClass, $rec->cId));
        
        if(price_Lists::haveRightFor('add', (object)array('cClass' => $rec->cClass, 'cId' => $rec->cId))){
        	$data->form->toolbar->addBtn('Нови правила', array('price_Lists', 'add', 'cClass' => $rec->cClass , 'cId' => $rec->cId, 'ret_url' => TRUE), NULL, 'order=10.00015,ef_icon=img/16/page_white_star.png');
        }
    }
    

    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->cClass) && isset($rec->cId)){
    		$data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
    	}
    }
    
    
    /**
     * След подготовка на лентата с инструменти за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
       $data->toolbar->removeBtn('btnAdd');
    }


    /**
     * Връща актуалния към посочената дата набор от ценови правила за посочения клиент
     */
    private static function getValidRec($customerClassId, $customerId, $datetime = NULL)
    { 
    	$datetime = (isset($datetime)) ? $datetime : dt::verbal2mysql();

        $query = self::getQuery();
        $query->where("#cClass = {$customerClassId} AND #cId = {$customerId}");
        $query->where("#validFrom <= '{$datetime}'");
        $query->where("#listState != 'rejected'");
        
        $query->limit(1);
        $query->orderBy("#validFrom,#id", 'DESC');
        $lRec = $query->fetch();
 		
        return $lRec;
    }


    /**
     * Задава ценова политика за определен клиент
     */
    public static function setPolicyTocustomer($policyId, $cClass, $cId, $datetime = NULL)
    {
        if(!$datetime) {
            $datetime = dt::verbal2mysql();
        }

        $rec = new stdClass();
        $rec->cClass = $cClass;
        $rec->cId   = $cId;
        $rec->validFrom = $datetime;
        $rec->listId = $policyId;
 
        self::save($rec);
    }

    
    /**
     * Подготвя ценоразписите на даден клиент
     */
    public function preparePricelists($data)
    { 
    	$data->TabCaption = 'Цени';
    	
    	$data->recs = $data->rows = array();
    	$query = self::getQuery();
        $query->where("#listState != 'rejected'");
        $query->where("#cClass={$data->masterMvc->getClassId()} AND #cId = {$data->masterId}");
        $query->orderBy("#validFrom,#id", 'DESC');
        
    	while($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    		$data->rows[$rec->id] = self::recToVerbal($rec);
    		if($rec->state == 'draft'){
    			$data->displayTools = TRUE;
    		}
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    		if($data->masterMvc->haveRightFor('edit', $data->masterData->rec)){
    			if($this->haveRightFor('add')){
    				$data->addUrl = array($this, 'add', 'cClass' => $data->masterMvc->getClassId(), 'cId' => $data->masterId, 'ret_url' => TRUE);
    			}
    		}
    	}
    }

    
    /**
     * Рендиране на ценоразписите на клиента
     */
    public function renderPricelists($data)
    {
    	$tpl = new core_ET("");
    	
    	$listFields = $this->listFields;
    	$listFields = arr::make($listFields, TRUE);
    	
    	if($data->displayTools === TRUE){
    		$listFields = array('tools' => 'Пулт') + $listFields;
    	}
    	
    	if(!haveRole('debug')){
    		unset($listFields['state']);
    	}
    	unset($listFields['cClass']);
    	
        $table = cls::get('core_TableView', array('mvc' => $this));
        $tpl->append(tr('Ценови политики'), 'priceListTitle');
        $tpl->append($table->get($data->rows, $listFields));
        
        if ($data->addUrl  && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $tpl->append(ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Избор на ценова политика'), 'priceListTitle');
        }
        
        return $tpl;
    }

    
    /**
     * След запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
    	// Ако ценовата политика е бъдеща задаваме
    	if($rec->validFrom > dt::now()){
    		core_CallOnTime::setOnce($mvc->className, 'updateStates', (object)array('cClass' => $rec->cClass, 'cId' => $rec->cId, 'validFrom' => $rec->validFrom), $rec->validFrom);
    	}
    	
    	static::updateStates($rec->cClass, $rec->cId);
    	price_History::removeTimeline();
    }
    

    /**
     * Връща валидните ценови правила за посочения клиент
     */
    public static function getListForCustomer($customerClass, $customerId, &$datetime = NULL)
    {
        static::canonizeTime($datetime);
    	
    	$validRec = self::getValidRec($customerClass, $customerId, $datetime);
    	$listId = ($validRec) ? $validRec->listId : cat_Setup::get('DEFAULT_PRICELIST');
        
        return $listId;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @param mixed $customerClass - клас на контрагента
     * @param int $customerId - ид на контрагента
     * @param int $productId - ид на артикула
     * @param int $packagingId - ид на опаковка
     * @param double $quantity - количество
     * @param datetime $datetime - дата
     * @param double $rate  - валутен курс
     * @param enum(yes=Включено,no=Без,separate=Отделно,export=Експорт) $chargeVat - начин на начисляване на ддс
     * @param int|NULL $listId - ценова политика
     * @param boolean $quotationPriceFirst - Дали първо да търси цена от последна оферта
     * @return stdClass $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no', $listId = NULL, $quotationPriceFirst = TRUE)
    {
        $isProductPublic = cat_Products::fetchField($productId, 'isPublic');
        $rec = (object)array('price' => NULL);
        
        // Проверяваме имали последна цена по оферта
        if($quotationPriceFirst === TRUE){
        	$rec = sales_QuotationsDetails::getPriceInfo($customerClass, $customerId, $datetime, $productId, $packagingId, $quantity);
        }
		
        // Ако има връщаме нея
        if(empty($rec->price)){
        	
        	// Проверяваме дали артикула е частен или стандартен
        	if($isProductPublic == 'no'){
        		 
        		$rec = (object)array('price' => NULL);
        		
        		$defPriceListId = (isset($listId)) ? $listId : self::getListForCustomer($customerClass, $customerId, $datetime);
        		$deltas = price_ListToCustomers::getMinAndMaxDelta($customerClass, $customerId, $defPriceListId);
        		
        		// Ако драйвера може да върне цена, връщаме нея
        		if($Driver = cat_Products::getDriver($productId)){
        			$price = $Driver->getPrice($productId, $quantity, $deltas->minDelta, $deltas->maxDelta, $datetime, $rate, $chargeVat);
        			if(isset($price) && $rate > 0){
        				$newPrice = $price / $rate;
						if($chargeVat == 'yes'){
							$vat = cat_Products::getVat($productId, $datetime);
							$newPrice = $newPrice * (1 + $vat);
						}
		
					    $newPrice = round($newPrice, 4);

						if($chargeVat == 'yes'){
							$newPrice = $newPrice / (1 + $vat);
						}
		
					    $newPrice *= $rate;

        				$rec->price = $newPrice;
        				$rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat);
        		 
        				return $rec;
        			}
        		}
        		 
        		// Търсим първо активната търговска рецепта, ако няма търсим активната работна
        		$bomRec = cat_Products::getLastActiveBom($productId, 'sales');
        		if(empty($bomRec)){
        			$bomRec = cat_Products::getLastActiveBom($productId, 'production');
        		}
        		 
        		// Ако има рецепта връщаме по нея
        		if($bomRec){
        			$defPriceListId = price_ListToCustomers::getListForCustomer($customerClass, $customerId);
        			if($defPriceListId == price_ListRules::PRICE_LIST_CATALOG){
        				$defPriceListId = price_ListRules::PRICE_LIST_COST;
        			}
        	
        			$rec->price = cat_Boms::getBomPrice($bomRec, $quantity, $deltas->minDelta, $deltas->maxDelta, $datetime, $defPriceListId);
        		}
        	} else {
        		$listId = (isset($listId)) ? $listId : self::getListForCustomer($customerClass, $customerId, $datetime);
        		
        		// За стандартните артикули търсим себестойността в ценовите политики
        		$rec = $this->getPriceByList($listId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat);
        	}
        }
        
        // Обръщаме цената във валута с ДДС ако е зададено и се закръгля спрямо ценоразписа
        if(!is_null($rec->price)){
        	$vat = cat_Products::getVat($productId);
        	$rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat);
        }
       
        // Връщаме цената
        return $rec;
    }
    
	
    /**
     * Връща минималната отстъпка и максималната надценка за даден контрагент
     * 
     * @param mixed $customerClass - ид на клас на контрагента
     * @param int $customerId      - ид на контрагента
     * @param int $defPriceListId  - ценоразпис
     * @return object $res		   - масив с надценката и отстъпката
     * 				 o minDelta  - минималната отстъпка
     * 				 o maxDelta  - максималната надценка
     */
    public static function getMinAndMaxDelta($customerClass, $customerId, $defPriceListId)
    {
    	$res = (object)array('minDelta' => 0, 'maxDelta' => 0);
    
    	// Ако контрагента има зададен ценоразпис, който не е дефолтния
    	if($defPriceListId != price_ListRules::PRICE_LIST_CATALOG){
    		 
    		// Взимаме максималната и минималната надценка от него, ако ги има
    		$defPriceList = price_Lists::fetch($defPriceListId);
    		$res->minDelta = $defPriceList->minSurcharge;
    		$res->maxDelta = $defPriceList->maxSurcharge;
    	}
    	
    	// Ако няма мин надценка, взимаме я от търговските условия
    	if(!$res->minDelta){
    		$res->minDelta = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    	}
    	 
    	// Ако няма макс надценка, взимаме я от търговските условия
    	if(!$res->maxDelta){
    		$res->maxDelta = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    	}
    	
    	return $res;
    }
    
    
    /**
     * Опит за намиране на цената според политиката за клиента (ако има такава)
     */
    private function getPriceByList($listId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no')
    {
    	$rec = new stdClass();
    	$rec->price = price_ListRules::getPrice($listId, $productId, $packagingId, $datetime);
    	
    	$listRec = price_Lists::fetch($listId);
    	 
    	// Ако е избрано да се връща отстъпката спрямо друга политика
    	if(!empty($listRec->discountCompared)){
    		 
    		// Намираме цената по тази политика и намираме колко % е отстъпката/надценката
    		$comparePrice = price_ListRules::getPrice($listRec->discountCompared, $productId, $packagingId, $datetime);
    		
    		if($comparePrice && $rec->price){
    			$disc = ($rec->price - $comparePrice) / $comparePrice;
    			$discount = round(-1 * $disc, 4);
    			
    			// Ще показваме цената без отстъпка и отстъпката само ако отстъпката е положителна
    			// Целта е да не показваме надценката а само отстъпката
    			if($discount > 0){
    				
    				// Подменяме цената за да може като се приспадне отстъпката и, да се получи толкова колкото тя е била
    				$rec->discount = round(-1 * $disc, 4);
    				$rec->price  = $comparePrice;
    			}
    		}
    	}
    	
    	return $rec;
    }
    
    
    /**
     * Помощна функция, добавяща 23:59:59 ако е зададена дата без час
     */
	public static function canonizeTime(&$datetime)
	{
		if(!$datetime) {
	       $datetime = dt::verbal2mysql();
	    } else { 
	       if(strlen($datetime) == 10) {
	          list($d, $t) = explode(' ', dt::verbal2mysql());
	          if($datetime == $d) {
	             $datetime = dt::verbal2mysql();
	          } else {
	             $datetime .= ' 23:59:59';
	          }
	      }
	   }
	}
	
	
	/**
	 * Ф-я викаща се по разписание
	 * @see core_CallOnTime
	 * 
	 * @param stdClass $data
	 */
	public function callback_updateStates($data)
	{
		$this->updateStates($data->cClass, $data->cId);
	}
	
	
	/**
	 * Обновяване на състоянието на контрагентски рецепти
	 * 
	 * @param int $cClass - клас на контрагента
	 * @param int $cId - клас Ид
	 */
	public static function updateStates($cClass = NULL, $cId = NULL)
	{
		$self = cls::get(get_called_class());
		$query = self::getQuery();
		
		$query->where("#cClass IS NOT NULL AND #cId IS NOT NULL");
		if(isset($cClass) && isset($cId)){
			$query->where("#cClass = {$cClass} AND #cId = {$cId}");
		}
		
		$count = $query->count();
		if($count > 200){
			core_App::setTimeLimit($count * 0.7);
		}
		
		$recsToSave = array();
		$cache = array();
		while($rec = $query->fetch()){
			$state = 'closed';
			
			$index = "$rec->cClass|$rec->cId";
			if(!array_key_exists($index, $cache)){
				$cache[$index] = self::getValidRec($rec->cClass, $rec->cId);
			}
			$aRec = $cache[$index];
			
			if(!empty($aRec) && $rec->id == $aRec->id){
				$state = 'active';
			}
			
			if($rec->state != $state){
				$recsToSave[] = (object)array('id' => $rec->id, 'state' => $state);
			}
		}
		
		$self->saveArray($recsToSave, 'id,state');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->cClass = cls::get($rec->cClass)->getHyperlink($rec->cId, TRUE);
		if($rec->validFrom > dt::now()){
			$rec->state = 'draft';
			$row->state = tr('Бъдещ');
		}
		
		$row->ROW_ATTR['class'] = "state-{$rec->state}";
		$row->listId = price_Lists::getHyperlink($rec->listId, TRUE);
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$listId = Request::get('listId', 'key(mvc=price_Lists)');
		if(isset($listId)){
			$data->query->where("#listId = {$listId}");
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на листовия изглед
	 */
	protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
	{
		$listId = Request::get('listId', 'key(mvc=price_Lists)');
		if(isset($listId)){
			$data->title = 'Ценова политика|* ' . price_Lists::getHyperlink($listId, TRUE);
		}
	}
	
	
	/**
	 * Връща масив с контрагентите свързани към даден ценоразпис
	 * 
	 * @param int $listId     - ид на политика
	 * @param boolean $links  - дали имената на контрагентите да са линк
	 * @return array $options - масив със свързаните контрагенти
	 */
	public static function getCustomers($listId, $links = FALSE)
	{
		$options = array();
		
		$query = price_ListToCustomers::getQuery();
		$query->where("#listId = {$listId} AND #state = 'active'");
		$count = $query->count();
		if(!empty($count)){
			while($rec = $query->fetch()){
				$title = ($links === TRUE) ? cls::get($rec->cClass)->getHyperlink($rec->cId, TRUE) : cls::get($rec->cClass)->getTitleById($rec->cId, FALSE);
				$options[$rec->id] = $title;
			}
		}
		
		return $options;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if($rec->validFrom <= dt::now()){
				$requiredRoles = 'no_one';
			}
		}
		
		if(($action == 'add' || $action == 'delete') && isset($rec)){
			if(!cls::get($rec->cClass)->haveRightFor('single', $rec->cId)){
				$requiredRoles = 'no_one';
			}
		}
	}
}
