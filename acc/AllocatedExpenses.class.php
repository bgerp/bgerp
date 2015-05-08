<?php



/**
 * Документ за Корекция на стойности
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_AllocatedExpenses extends core_Master
{
    
	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=acc_transaction_AllocatedExpense';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Корекции на стойности";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_ExpenseAllocations';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing,acc_plg_DocumentSummary,plg_Search, doc_plg_HidePrices';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.9|Счетоводни";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
   public $listFields = "tools=Пулт, valior, title=Документ, amount, dealOriginId=Сделка->Основна, correspondingDealOriginId=Сделка->Кореспондент, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, acc';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Корекция на стойности';
    
    
    /**
     * Икона на единичния изглед
     */
    //var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Aex";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    public $canRevert = 'acc, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleAllocatedExpensesLayout.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'contragentFolderId, notes';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
    	$this->FLD('amount', 'double(decimals=2,Min=0)', 'caption=Сума');
    	$this->FLD('action', 'enum(increase=Увеличаване,decrease=Намаляване)', 'caption=Корекция,notNull,value=increase,maxRadio=2');
    	$this->FLD('allocateBy', 'enum(value=Стойност,quantity=Количество,weight=Тегло,volume=Обем)', 'caption=Разпределяне по,notNull,value=value');
    	$this->FNC('contragentFolderId', 'key(mvc=doc_Folders,select=title)', 'caption=Кореспондираща сделка->Контрагент,refreshForm,silent,input');
    	$this->FNC('dealHandler', 'varchar', 'caption=Кореспондираща сделка->Номер,silent,input');
    	$this->FLD('correspondingDealOriginId', 'int', 'input=none,tdClass=leftColImportant');
    	
    	// Функционално поле за избор на артикули
    	$this->FNC('chosenProducts', 'text', 'caption=Корекция на стойността на->Артикули,mandatory,input');
    	
    	// Кеш поле за цялата информация на възможните артикули
    	$this->FLD('productsData', 'blob(serialize, compress)', 'input=none');
    	
    	$this->FLD('notes', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	
    	// Поставяне на уникален индекс
    	$this->setDbIndex('correspondingDealOriginId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	
    	$row->title = $mvc->getLink($rec->id, 0);
    	$row->dealOriginId = $firstDoc->getLink(0);
    	$row->correspondingDealOriginId = doc_Containers::getDocument($rec->correspondingDealOriginId)->getLink(0);
    	$row->baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
    }
    
    
    /**
     * Връща вербалното представяне за артикула с коригирана стойност
     */
    private function getVerbalDetail($pRec)
    {
    	$row = new stdClass();
    	$row->name = cat_Products::getShortHyperlink($pRec->productId);
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	
    	foreach (array('amount', 'allocated', 'quantity') as $fld){
    		if(isset($pRec->$fld)){
    			$row->$fld = $Double->toVerbal($pRec->$fld);
    		}
    	}
    	
    	if(isset($pRec->transportWeight)){
    		$row->transportWeight = cls::get('cat_type_Weight')->toVerbal($pRec->transportWeight);
    	}
    	
    	if(isset($pRec->transportVolume)){
    		$row->transportVolume = cls::get('cat_type_Volume')->toVerbal($pRec->transportVolume);
    	}
    	
    	return $row;
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	if(!count($data->rec->productsData)) return;
    	
    	$productRows = array();
    	$count = 1;
    	foreach ($data->rec->productsData as $pRec){
    		$row = $mvc->getVerbalDetail($pRec);
    		$row->count = cls::get('type_Int')->toVerbal($count);
    		$productRows[] = $row;
    		$count++;
    	}
    	
    	$listFields = arr::make('count=№,name=Артикул,amount=Сума,allocated=Разпределено', TRUE);
    	
    	switch($data->rec->allocateBy){
    		case 'weight':
    			arr::placeInAssocArray($listFields, array('transportWeight' => 'Тегло'), 'allocated');
    			break;
    		case 'volume':
    			arr::placeInAssocArray($listFields, array('transportVolume' => 'Обем'), 'allocated');
    			break;
    		case 'quantity':
    			arr::placeInAssocArray($listFields, array('quantity' => 'К-во'), 'allocated');
    			break;
    	}
    	
    	// Показваме таблица със артикулите и разпределените им суми
    	$fs = new core_FieldSet();
    	$fs->FNC('amount', 'double');
    	$fs->FNC('allocated', 'double');
    	$fs->FNC('transportWeight', 'double');
    	$fs->FNC('transportVolume', 'double');
    	$fs->FNC('quantity', 'double');
    	$table = cls::get('core_TableView', array('mvc' => $fs));
    	$details = $table->get($productRows, $listFields);
    	$tpl->append($details, 'PRODUCTS_TABLE');
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
    	$form->setDefault('valior', dt::today());
    	
    	if(isset($rec->id)){
    		if($rec->correspondingDealOriginId){
    			$corespondent = doc_Containers::getDocument($rec->correspondingDealOriginId);
    			$form->setDefault('dealHandler', $corespondent->getHandle());
    		}
    	}
    	
    	// Намираме ориджина и подготвяме опциите за избор на папки на контрагенти
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$form->setOptions('contragentFolderId', array('' => '') + doc_Folders::getOptionsByCoverInterface('crm_ContragentAccRegIntf'));

    	// Ако има избрана папка на контрагент, зареждаме всички достъпни сделки като предложение
    	if(isset($rec->contragentFolderId)){
    		$suggestions = $mvc->getContragentDealSuggestions($rec->contragentFolderId);
    		$form->setSuggestions('dealHandler', $suggestions);
    	}
    	
    	// Намираме имали артикули, на които да се разпределят разходите
    	$products = $mvc->getChosenProducts($firstDoc);
    	$form->allProducts = $products;
    	
    	if(count($products)){
    		
    		// Добавяме всички възможни артикули като опции в SET поле
    		$nProducts = array();
    		foreach ($products as $p){
    			$nProducts[$p->productId] = $p->name;
    		}
    		$form->fields['chosenProducts']->type = cls::get('type_Set', array('suggestions' => $nProducts));
    		$form->setField('chosenProducts', 'columns=1');
    		
    		// Ако имаме запис оставяме само тези, които са в кешираното блоб поле
    		if($rec->id && $rec->productsData){
    			$products = array_intersect_key($products, $rec->productsData);
    		}
    		
    		// Задаваме ги за избрани по дефолт, така двете полета се синхронизирват
    		$defaults = cls::get('type_Set')->fromVerbal($products);
    		$form->setDefault('chosenProducts', $defaults);
    	}
    	
    	$data->form->origin = $firstDoc;
    }
    
    
    /**
     * Извличаме артикулите върху които ще се коригират стойностите
     * 
     * @param core_ObjectReference $firstDoc - първи документ в нишката
     * @return array $products - масив с опции за избор на артикули
     */
    private function getChosenProducts(core_ObjectReference $firstDoc)
    {
    	// Aко първия документ е продажба
    	if($firstDoc->getInstance() instanceof sales_Sales){
    		
    		// Взимаме артикулите от сметка 701
    		$shipped = sales_transaction_Sale::getShippedProducts($firstDoc->that, '701');
    	
    	  // Ако е покупка
    	} elseif($firstDoc->getInstance() instanceof purchase_Purchases){
    		
    		// Вземаме всички заскладени артикули
    		$shipped = purchase_transaction_Purchase::getShippedProducts($firstDoc->that, '321');
    	} else {
    		
    		// Иначе няма
    		$shipped = array();
    	}
    	
    	$products = array();
    	if(count($shipped)){
    		foreach ($shipped as $p){
    			$params = cls::get($p->classId)->getParams($p->productId);
    			
    			$products[$p->productId] = (object)array('productId'    => $p->productId, 
    												     'name'         => cls::get($p->classId)->getTitleById($p->productId), 
    													 'quantity'     => $p->quantity,
    													 'amount' => $p->amount,
    			);
    			
    			if(isset($params['transportWeight'])){
    				$products[$p->productId]->transportWeight = $params['transportWeight'];
    			}
    			
    			if(isset($params['transportVolume'])){
    				$products[$p->productId]->transportVolume = $params['transportVolume'];
    			}
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Подготвяме предложенията за избор на сделки на контрагент
     * 
     * @param int $folderId - папка на контрагента
     * @return array $suggestions - масив с предложенията
     */
    private function getContragentDealSuggestions($folderId)
    {
    	$suggestions = array();
    	
    	$after = dt::addMonths(-3, dt::today());
    	$after = dt::verbal2mysql($after, FALSE);
    	
    	// За всички финансови сделки и покупки
    	foreach (array('findeals_Deals', 'purchase_Purchases') as $cls){
    		$Cls = cls::get($cls);
    		
    		// Намираме тези в папката на контрагента за един месец назад
    		$fQuery = $Cls->getQuery();
    		$fQuery->where("#folderId = {$folderId}");
    		$fQuery->where("#state = 'active'");
    		$fQuery->where("#createdOn >= '{$after}'");
    		
    		// За всеки запис подготвяме опциите показвайки за име вида 'хендлър / дата / сума валута'
    		while($fRec = $fQuery->fetch()){
    			$handle = $Cls->getHandle($fRec->id);
    			$date = dt::mysql2verbal($fRec->{$Cls->filterDateField}, "d.m.Y");
    			$amount = round($fRec->amountDeal / $fRec->currencyRate, 2);
    		
    			$suggestions[$handle] = "{$handle} / {$date} / $amount {$fRec->currencyId}";
    		}
    	}
    	
    	// Връщаме предложенията
    	if(count($suggestions)){
    		$suggestions = array('' => '') + $suggestions;
    	}
    	
    	return $suggestions;
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
    	$originTitle = $form->origin->getHyperLink(TRUE);
    	$form->title = "|Корекция на стойностите в|* " . $originTitle;
    	
    	// Ако е събмитната формата
    	if($form->isSubmitted()){
    		
    		// Намираме контейнера на първия документ в нишката
    		$doc = doc_Threads::getFirstDocument($rec->threadId);
    		$baseContainerId = $doc->fetchField('containerId');
    		$firstDocumentHandle = $doc->getHandle();
    		
    		// Ако има избран хендлър на сделка и тя не е текущата сделка
    		if($rec->dealHandler && $rec->dealHandler != $firstDocumentHandle){
    			
    			 // Намираме документа по хендлъра
    			 $doc = doc_Containers::getDocumentByHandle($rec->dealHandler);
    			 
    			 // Трябва да има такава сделка
    			 if($doc){
    			 	
    			 	// и да може да бъде избрана
    			 	if(!$mvc->checkCorespondingDocument($doc)){
    			 		$form->setError('dealHandler', 'Трябва да е избрана активна финансова сделка или покупка само с услуги');
    			 	}
    			 } else {
    			 	
    			 	// Не може да е въведена невалидна сделка
    			 	$form->setError('dealHandler', 'Няма сделка с такъв номер');
    			 }
    		}
    		
    		$correpspondingContainerId = $doc->fetchField('containerId');
    		
    		// Ако текущата сделка е кореспондент, трябва да се попълнена сумата
    		if($correpspondingContainerId === $baseContainerId && empty($rec->amount)){
    			$form->setError('amount', 'Трябва да е попълнена сумата, aко е текущата сделка кореспондент');
    		}
    		
    		if(!$form->gotErrors()){
    			$rec->correspondingDealOriginId = $correpspondingContainerId;
    			if(empty($rec->amount)){
    				$rec->amount = $mvc->getDefaultAmountToAllocate($rec->correspondingDealOriginId);
    				if(empty($rec->amount)){
    					$form->setError('amount', 'Не може автоматично да се определи сумата, Моля задайте');
    				}
    			}
    			
    			// Kешираме от всички възможни продукти, тези които са били избрани във функционалното поле
    			$rec->productsData = array_intersect_key($form->allProducts, type_Set::toArray($rec->chosenProducts));
    			$msg = $mvc->allocateAmount($rec->productsData, $rec->amount, $rec->allocateBy);
    			if(isset($msg)){
    				$form->setError('allocateBy', $msg);
    			}
    		}
    	}
    }
    
    
    /**
     * Разпределяне на разходите според посочения метод
     * 
     * Цените на продуктите: P1, P2, ..., Pn
     * Количествата на продуктите: Q1, Q2, ..., Qn
     * Единичния транспортен обем: V1, V2, ..., Vn
     * Единичните транспортни тегла: M1, M2, ...., Mn
     * 
     * Тогава коефициентите за разпределение на разходите/излишъците са:
     * 1. По стойност:
     * 		K(i) = P(i)*Q(i) / SUM(P*Q);
     * 		Ако общата сума е 0, не може да се разпредели разхода
     * 2. По количество:
     * 		K(i) = Q(i) / SUM(Q);
     * 3. По обем:
     * 		K(i) = V(i)*Q(i) / SUM(V*Q);
     * 		Ако някой артикул няма Транспортен обем не може да се разпредели
     * 4. По тегло:
     * 		K(i) = М(i)*Q(i) / SUM(М*Q); 
     * 		Ако някой артикул няма тегло не може да се разпредели
     * 
     * @param array $products - масив с информация за артикули
     * @param double $amount - сумата за разпределяне
     * @param value|quantity|volume|weight - режим на разпределяне
     * @return mixed
     */
    private function allocateAmount(&$products, $amount, $allocateBy)
    {
    	$denominator = 0;
    	
    	// Първо обхождаме записите и изчисляваме знаменателя чрез който ще изчислим коефициента
    	switch ($allocateBy){
    		case 'value':
    			foreach ($products as $p){
    				$denominator += $p->amount;
    			}
    			if($denominator == 0) return 'Не може да се разпредели по стойност, когато общата стойност на артикулите е нула';
    			
    			break;
    		case 'quantity':
    			foreach ($products as $p){
    				$denominator += $p->quantity;
    			}
    			break;
    		case 'weight':
    			foreach ($products as $p){
    				if(!isset($p->transportWeight)) return 'Не може да се разпредели по тегло, докато има артикул без транспортно тегло';
    				
    				$denominator += $p->transportWeight * $p->quantity;
    			}
    			break;
    		case 'volume':
    			foreach ($products as $p){
    				if(!isset($p->transportVolume)) return 'Не може да се разпредели по обем, докато има артикул без транспортен обем';
    				$denominator += $p->transportVolume * $p->quantity;
    			}
    			break;
    	}
    	
    	// Изчисляваме коефициента, според указания начин за разпределяне
    	foreach ($products as &$p){
	    	switch ($allocateBy){
	    		case 'value':
	    			$coefficient = $p->amount / $denominator;
	    			break;
	    		case 'quantity':
	    			$coefficient = $p->quantity / $denominator;
	    			break;
	    		case 'weight':
	    			$coefficient = ($p->transportWeight * $p->quantity) / $denominator;
	    			
	    			break;
	    		case 'volume':
	    			$coefficient = ($p->transportVolume * $p->quantity) / $denominator;
	    			break;
	    	}
	    	
	    	// Изчисляваме сумата за разпределяне (коефициент * сума за разпределение)
	    	$p->allocated = round($coefficient * $amount, 2);
    	}
    }
    
    
    /**
     * Връща дефолтната сума за разпределяне
     * 
     * @param int $correspondingOriginId - ориджин на документ кореспондент
     * @return double $amount - дефолтната сума
     */
    private function getDefaultAmountToAllocate($correspondingOriginId)
    {
    	$doc = doc_Containers::getDocument($correspondingOriginId);
    	$amount = 0;
    	if($doc->getInstance() instanceof findeals_Deals){
    		$amount = $doc->fetchField('amountDeal');
    	} elseif($doc->getInstance() instanceof purchase_Purchases){
    		$amount = $doc->fetchField('amountDeal');
    	}
    	
    	return $amount;
    }
    
    
    /**
     * Проверяваме дали избрания кореспондиращ документ е валиден
     * 
     * Трябва да е активен
     * и да е финансова сделка или покупка само с услуги
     * 
     * @param core_ObjectReference $doc - кореспондиращ документ
     * @return boolean - валиден ли е или не
     */
    private function checkCorespondingDocument(core_ObjectReference $doc)
    {
    	// Дали е активен ?
    	if($doc->fetchField('state') != 'active'){
    		return FALSE;
    	}
    	
    	// Ако е финансова сделка, винаги може
    	if($doc->getInstance() instanceof findeals_Deals){
    		
    		return TRUE;
    		
    		// Ако е покупка
    	} elseif($doc->getInstance() instanceof purchase_Purchases){
    		
    		// Намираме  артикулите
    		$pQuery = purchase_PurchasesDetails::getQuery();
    		$pQuery->where("#requestId = {$doc->that}");
    		$pQuery->show('classId,productId');
    		 
    		// Ако има поне един складируем артикул не може да се създаде
    		while($dRec = $pQuery->fetch()){
    			$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
    			if(isset($pInfo->meta['canStore'])){
    				return FALSE;
    			}
    		}
    		
    		// Иначе може
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		if($firstDoc->fetchField('state') != 'active'){
    			
    			// Ако ориджина не е активен, не може да се създава документ към него
    			$requiredRoles = 'no_one';
    		} else {
    			
    			// Ако няма артикули за разпределяне, не може да се създава документа
    			$products = $mvc->getChosenProducts($firstDoc);
    			if(!count($products)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може да се добави само към тред на покупка/продажба
    	if($firstDoc->getInstance() instanceof sales_Sales || $firstDoc->getInstance() instanceof purchase_Purchases){
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    
    	$row->title    = $this->singleTitle . " №{$rec->id}";
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state    = $rec->state;
    
    	return $row;
    }
}