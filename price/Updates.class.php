<?php



/**
 * Правила за обновяване на себестойностите
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_Updates extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Правила за обновяване на себестойностите';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Правилo за обновяване на себестойност";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, name=Правило,costSource1,costSource2,costSource3,costAdd,costValue=Себестойност->Сума,updateMode=Себестойност->Обновяване';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го промени?
     */
    public $canWrite = 'price,ceo';
    
    
    /**
     * Кой може да го промени?
     */
    public $canRead = 'price,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'price,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'price,ceo';


	/**
	 * Кой може ръчно да обновява себестойностите?
	 */
	public $canSaveprimecost = 'price,ceo';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('objectId', 'int', 'caption=Обект,input=none');
    	$this->FLD('type', 'enum(category,product)', 'caption=Обект вид,input=none');
    	$this->FLD('costSource1', 'enum(,accCost=Счетоводна себестойност,
    									lastDelivery=Последна доставна,
    									activeDelivery=Текуща поръчка,
    									lastQuote=Последна оферта,
    									bom=Последна рецепта)', 'caption=Себестойност->Източник 1,mandatory');
    	$this->FLD('costSource2', 'enum(,accCost=Счетоводна себестойност,
    									lastDelivery=Последна доставна,
    									activeDelivery=Текуща поръчка,
    									lastQuote=Последна оферта,
    									bom=Последна рецепа)', 'caption=Себестойност->Източник 2');
    	$this->FLD('costSource3', 'enum(,accCost=Счетоводна себестойност,
    									lastDelivery=Последна доставна,
    									activeDelivery=Текуща поръчка,
    									lastQuote=Последна оферта,
    									bom=Последна рецепа)', 'caption=Себестойност->Източник 3');
    	$this->FLD('costAdd', 'percent(Min=0,max=1)', 'caption=Себестойност->Процент');
    	$this->FLD('costValue', 'double', 'input=none,caption=Себестойност');
    	$this->FLD('updateMode', 'enum(manual=Ръчно,now=Ежечасно,nextDay=Следващия ден,nextWeek=Следващата седмица,nextMonth=Следващия месец)', 'caption=Себестойност->Обновяване');
    
    	$this->setDbUnique('objectId,type');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Добавяме функционални полета за по-хубав избор на категория или артикул
    	$form->FNC('categoryId', 'key(mvc=cat_Categories,select=name,allowEmpty)', 'caption=Категория,input,before=costSource1,silent');
    	$form->FNC('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,input,before=categoryId,silent');
    	
    	$form->input(NULL, 'silent');
    	
    	// Намираме всички активни стандартни, продаваеми или купуваеми артикули
    	$products = cat_Products::getStandartProducts();
    	
    	// Задаваме намерените артикули за опции на полето
    	$form->setOptions('productId', array('' => '') + $products);
    	if($rec->type == 'category'){
    		$rec->categoryId = $rec->objectId;
    	} elseif($rec->type == 'product') {
    		$rec->productId = $rec->objectId;
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	if($form->isSubmitted()){
    		$rec->costSource2 = (!$rec->costSource2) ? NULL : $rec->costSource2;
    		$rec->costSource3 = (!$rec->costSource3) ? NULL : $rec->costSource3;
    		
    		// Трябва поне категория или артикул да е избран
    		if((isset($rec->productId) && isset($rec->categoryId)) || (empty($rec->categoryId) && empty($rec->productId))){
    			$form->setError('categoryId,productId', 'Точно едно поле трябва да е попълнено');
    		} 
    		
    		$error = FALSE;
    		if($rec->costSource1 == $rec->costSource2 || $rec->costSource1 == $rec->costSource3){
    			$error = TRUE;
    		}
    		if(isset($rec->costSource2) && ($rec->costSource2 == $rec->costSource1 || $rec->costSource2 == $rec->costSource3)) {
    			$error = TRUE;
    		}
    		if(isset($rec->costSource3) && ($rec->costSource3 == $rec->costSource1 || $rec->costSource3 == $rec->costSource2)) {
    			$error = TRUE;
    		}
    		
    		// Ако източниците се повтарят, сетваме грешка във формата
    		if($error === TRUE){
    			$form->setError('costSource1,costSource2,costSource3', 'Стойностите се повтарят');
    		}
    		
    		// Попълваме скритите полета с данните от функционалните
    		if(!$form->gotErrors()){
    			if(isset($rec->categoryId)){
    				$rec->objectId = $rec->categoryId;
    				$rec->type = 'category';
    			} elseif(isset($rec->productId)) {
    				$rec->objectId = $rec->productId;
    				$rec->type = 'product';
    			}
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
    	// Показваме името на правилото
    	$row->name = ($rec->type == 'category') ? cat_Categories::getHyperlink($rec->objectId, TRUE) : cat_Products::getHyperlink($rec->objectId, TRUE);
    	
    	if($rec->type == 'product'){
    		if(price_ListRules::haveRightFor('add')){
    			$row->updateMode = ht::createBtn('Обнови', array('price_ListRules', 'add', 'type' => 'value', 'listId' => price_ListRules::PRICE_LIST_COST, 'price' => $rec->costValue, 'productId' => $rec->objectId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/arrow_refresh.png,title=Ръчно обновяване на себестойностите');
    			$row->updateMode = "<span style='float:right'>{$row->updateMode}</span>";
    		}
    	} else {
    		if($mvc->haveRightFor('saveprimecost', $rec)){
    			$row->updateMode = ht::createBtn('Обнови', array($mvc, 'saveprimecost', $rec->id, 'ret_url' => TRUE), '|Сигурни ли сте, че искате да обновите себестойностите на всички артикули в категорията|*?', FALSE, 'ef_icon=img/16/arrow_refresh.png,title=Ръчно обновяване на себестойностите');
    			$row->updateMode = "<span style='float:right'>{$row->updateMode}</span>";
    		}
    	}
    	
    	$row->ROW_ATTR['class'] = 'state-active';
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'saveprimecost' && isset($rec)){
    		if($rec->updateMode != 'manual' || $rec->type == 'product'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if(($action == 'add' || $action == 'edit' || $action == 'delete' || $action == 'read') && isset($rec)){
    		if(isset($rec->categoryId)){
    			if(!cat_Categories::haveRightFor('single', $rec->categoryId)){
    				$requiredRoles = 'no_one';
    			}
    		} elseif(isset($rec->productId)){
    			if(!cat_Products::haveRightFor('single', $rec->productId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Записва себестойноста според правилото с ръчно обновяване
     */
    function act_Saveprimecost()
    {
    	$this->requireRightFor('saveprimecost');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('saveprimecost', $rec);
    	
    	// Записва себестойноста
    	$this->savePrimeCost($rec);
    	
    	// Редирект към списъчния изглед
    	return followRetUrl(NULL, 'Себестойностите са обновени успено');
    }
    
    
    /**
     * Намира на кои артикули да се обновят себестойностите
     * 
     * @param stdClass $rec - записа
     * @return array $products - артикулите
     */
    private function getProductsToUpdatePrimeCost($rec)
    {
    	$products = array();
    	 
    	// Ако е избран продукт, ще обновим само неговата себестойност
    	if($rec->type == 'product'){
    		$products[$rec->objectId] = $rec->objectId;
    	} else {
    	
    		// Ако е категория, всички артикули в папката на категорията
    		$folderId = cat_Categories::fetchField($rec->objectId, 'folderId');
    	
    		$pQuery = cat_Products::getQuery();
    		$pQuery->where("#folderId = {$folderId}");
    		$pQuery->show('id');
    		while($pRec = $pQuery->fetch()){
    			$products[$pRec->id] = $pRec->id;
    		}
    	}
    	
    	// Връща намерените артикули
    	return $products;
    }
    
    
    /**
     * Обновява всички себестойностти според записа
     * 
     * @param stdClass $rec - запис
     * @param boolean  $saveInPriceList - искаме ли да запишем изчислената себестойност в 'Себестойности'
     * @return void
     */
    private function savePrimeCost($rec, $saveInPriceList = TRUE)
    {
    	// На кои продукти ще обновяваме себестойностите
    	$products = $this->getProductsToUpdatePrimeCost($rec);
    	 
    	// Подготвяме датата от която ще е валиден записа
    	$validFrom = $this->getValidFromDate($rec->updateMode);
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($validFrom);
    	
    	// За всеки артикул
    	foreach ($products as $productId){
    		
    		// Опитваме се да му изчислим себестойноста според източниците
    		$primeCost = self::getPrimeCost($productId, $rec->costSource1, $rec->costSource2, $rec->costSource3, $rec->costAdd);
    		
    		//@TODO debug !!!
    		$primeCost = 7;
    		
    		// Намираме старата мус ебестойност (ако има)
    		$oldPrimeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId);
    		
    		// Ако имаме изчислена себестойност
    		if($primeCost){
    			
    			// Добавяме надценката, ако има
    			$primeCost = $primeCost * (1 + $rec->costAdd);
    			
    			// Ако старата себестойност е различна от новата
    			if($primeCost != $oldPrimeCost){
    				
    				// Кешираме себестойноста, ако правилото не е за категория
    				if($rec->type != 'category'){
    					$rec->costValue = $primeCost;
    					self::save($rec, 'costValue');
    				}
    				 
    				// Ако е указано, обновяваме я в ценовите политики
    				if($saveInPriceList === TRUE){
    			
    					// Записваме новата себестойност на продукта
    					price_ListRules::savePrimeCost($productId, $primeCost, $validFrom, $baseCurrencyCode);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * От коя дата да е валиден записа
     * 
     * @param manual|now|nextDay|nextWeek|nextMonth $updateMode
     * @return date $validFrom
     */
    private function getValidFromDate($updateMode)
    {
    	// Според избрания начин на обновление
    	switch($updateMode){
    		case 'manual':
    		case 'now':
    			
    			// Влиза в сила веднага
    			$date = dt::now();
    			break;
    		case 'nextDay':
    			
    			// Влиза в сила от 00:00 на следващия ден
    			$date = dt::addDays(1, dt::today());
    			break;
    		case 'nextWeek':
    			
    			// Влиза в сила от 00:00 в следващия понеделник
    			$date = dt::timestamp2Mysql(strtotime("next Monday"));
    			break;
    		case 'nextMonth':
    			
    			// Влиза в сила от 01 на следващия месец
    			$date = dt::mysql2verbal(dt::addMonths(1, dt::today()), "Y-m-01 00:00:00");
    			break;
    	}
    	
    	// Връща датата, от която да е валиден записа
    	return $date;
    }
    
    
    /**
     * Намира себестойността на един артикул, според зададените приоритети
     * 
     * @param int $productId - ид на артикул
     * @param accCost|lastDelivery|activeDelivery|lastQuote|bom $costSource1      - първи източник
     * @param accCost|lastDelivery|activeDelivery|lastQuote|bom|NULL $costSource2 - втори източник
     * @param accCost|lastDelivery|activeDelivery|lastQuote|bom|NULL $costSource3 - трети източник
     * @param double $costAdd - процент надценка
     * @return double|FALSE $price - намерената себестойност или FALSE ако няма
     */
    public static function getPrimeCost($productId, $costSource1, $costSource2 = NULL, $costSource3 = NULL, $costAdd = NULL)
    {
    	$date = dt::now();
    	$quantity = 1;
    	$allSources = array('accCost', 'lastDelivery', 'activeDelivery', 'lastQuote', 'bom');
    	$sources = array($costSource1, $costSource2, $costSource3);
    	foreach ($sources as $source){
    		if(isset($source)){
    			expect(in_array($source, $allSources));
    			
    			switch($source){
    				case 'accCost':
    					//$price = cat_Products::getWacAmountInStore($quantity, $productId, $date);
    					break;
    				case 'lastDelivery':
    					//@TODO
    					break;
    				case 'activeDelivery':
    					//@TODO
    					break;
    				case 'lastQuote':
    					//@TODO
    					break;
    				case 'bom':
    					//$bomRec = cat_Products::getLastActiveBom($productId);
    					//if(!empty($bomRec)){
    						//$price = cat_Boms::getBomPrice($bomRec, $quantity, 0, 0, $date, price_ListRules::PRICE_LIST_COST);
    					//}
    					//bp($bomId);
    					//@TODO
    					break;
    			}
    			
    			if(isset($price)) return $price;
    		}
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    function act_Recalc()
    {
    	expect(haveRole('debug'));
    	$this->cron_SavePrimeCosts();
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    function cron_SavePrimeCosts()
    {
    	// Взимаме всички записи
    	$now = dt::now();
    	$query = $this->getQuery();
    	
    	// За всеки
    	while($rec = $query->fetch()){
    		try{
    			// Ако не може да се изпълни, пропускаме го
    			if(!$this->canBeApplied($rec, $now)) continue;
    			
    			// Ще обновяваме себестойностите в модела, освен за записите на които ръчно ще трябва да се обнови
    			$saveInPriceList = ($rec->updateMode == 'manual') ? FALSE : TRUE;
    			
    			// Изчисляваме и записваме себестойностите
    			$this->savePrimeCost($rec, $saveInPriceList);
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Дали времето за активиране на условието може да се изпълни
     * 
     * @param stdClass $rec  - запис
     * @param datetime $date - към коя дата сме
     * @return boolean $res  - може или не може да се изпълни условието
     */
    private function canBeApplied($rec, $date)
    {
    	//$rec->updateMode = 'nextMonth';
    	//$date = '2015-12-11 15:00:00';
    	//bp($date);
    	
    	$res = FALSE;
    	switch($rec->updateMode){
    		case 'manual':
    		case 'now':
    			// При ежечасовото условие, изпълняваме го винаги
    			$res = TRUE;
    			break;
    		case 'nextDay':
    			
    			// Дали часа от датата е 15:00
    			$hour = dt::mysql2verbal($date, 'H');
    			$res = ($hour == '15');
    			break;
    		case 'nextWeek':
    			
    			// Дали датата е петък 15:00 часа
    			$day = dt::mysql2verbal($date, 'D:H','en');
    			$res = ($day == 'Fri:15');
    			break;
    		case 'nextMonth':
    			
    			// Дали датата е 5 дена преди края на текущия месец в 15:00 часа
    			$lastDayOfMonth = dt::getLastDayOfMonth($date);
    			$dateToCompare = dt::addDays(-5, $lastDayOfMonth);
    			$dateToCompare = dt::addSecs(60*60*15, $dateToCompare);
    			$dateToCompare = dt::mysql2verbal($dateToCompare, 'd:H');
    			$date = dt::mysql2verbal($date, 'd:H');
    			
    			$res = ($date == $dateToCompare);
    			break;
    	}
    	
    	// Връщаме резултата
    	return $res;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Преизчисли', array($mvc, 'recalc'), NULL, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
    	}
    }
    
    
    /**
     * Подготовка на себестойностите
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareUpdates(&$data)
    {
    	// Можем ли да виждаме таба?
    	$key = ($data->masterMvc instanceof cat_Categories) ? 'categoryId' : 'productId';
    	if(!$this->haveRightFor('read', (object)array($key => $data->masterId))){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	// Как да се казва таба
    	$data->TabCaption = 'Обновяване';
    	$data->rows = $data->recs = array();
    	
    	// Извличаме записа за артикула
    	$query = $this->getQuery();
    	$type = ($data->masterMvc instanceof cat_Categories) ? 'category' : 'product';
    	$query->where("#type = '{$type}'");
    	$query->where("#objectId = {$data->masterId}");
    	
    	// За всеки запис (може да е максимум един)
    	while($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    		$row = $this->recToVerbal($rec);
    		$row->sources = "<ol style='margin:0px'>";
    		foreach (array('costSource1', 'costSource2', 'costSource3') as $fld){
    			if(isset($rec->{$fld})){
    				$row->sources .= "<li style='text-align:left'>{$row->{$fld}}</li>";
    			}
    		}
    		$row->sources .= "</ol>";
    		
    		$data->rows[$rec->id] = $row;
    	}
    }
    
    
    /**
     * Рендиране на дата за себестойностите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderUpdates($data)
    {
    	 // Ако трябва не рендираме таба
    	 if($data->hide === TRUE) return;
    	
    	 // Взимаме шаблона
    	 $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	 $title = tr('Правило за обновяване на себестойност');
    	 $tpl->append($title, 'title');
    	 
    	 // Добавяме бутон ако трябва
    	 if(!count($data->recs)){
    	 	$ht = ht::createLink('', array($this, 'add', 'categoryId' => $data->masterData->rec->id, 'ret_url' => TRUE), FALSE, 'title=Задаване на ново правило,ef_icon=img/16/add.png');
    	 	$tpl->append($ht, 'title');
    	 }
    	 
    	 // Рендираме таблицата
    	 $table = cls::get('core_TableView', array('mvc' => $this));
    	 $details = $table->get($data->rows, 'tools=Пулт,sources=Източници,costAdd=Надценка,updateMode=Обновяване');
    	 $tpl->append($details, 'content');
    	 
    	 // Връщаме шаблона
    	 return $tpl;
    }
}