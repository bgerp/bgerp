<?php



/**
 * Документ за Корекция на стойности
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ValueCorrections extends core_Master
{
    
	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=acc_transaction_ValueCorrection';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Корекции на стойности";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_AllocatedExpenses';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing,acc_plg_DocumentSummary,plg_Search, doc_plg_HidePrices, bgerp_plg_Blank,deals_plg_SelectDeal';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "6.9|Счетоводни";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "valior, title=Документ, amount, dealOriginId=Сделка->Основна, correspondingDealOriginId=Сделка->Кореспондент, state, createdOn, createdBy";
    
    
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
     * Абревиатура
     */
    public $abbr = "Crv";
    
    
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
     * Поле в което ще се записва контейнера на избраната сделка
     * 
     * @see deals_plg_SelectDeal
     */
    public $selectedDealOriginFieldName = 'correspondingDealOriginId';
    
    
    /**
     * След кое поле да се покаже секцията за избор на сделка
     *
     * @see deals_plg_SelectDeal
     */
    public $selectDealAfterField = 'allocateBy';
    
    
    /**
     * От кои класове на сделки може да се избира
     *
     * @see deals_plg_SelectDeal
     */
    public $selectedDealClasses = 'findeals_Deals,purchase_Purchases';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,removeAndRefreshForm=rate,silent');
    	$this->FLD('rate', 'double(decimals=5)', 'caption=Курс');
    	
    	$this->FLD('action', 'enum(increase=Увеличаване,decrease=Намаляване)', 'caption=Корекция,notNull,value=increase,maxRadio=2');
    	$this->FLD('allocateBy', 'enum(value=Стойност,quantity=Количество,weight=Тегло,volume=Обем)', 'caption=Разпределяне по,notNull,value=value');
    	
    	// Функционално поле за избор на артикули
    	$this->FNC('chosenProducts', 'text', 'caption=Корекция на стойността на->Артикули,mandatory,input');
    	
    	// Кеш поле за цялата информация на възможните артикули
    	$this->FLD('productsData', 'blob(serialize, compress)', 'input=none');
    	
    	$this->FLD('notes', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    }
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
    	// Поставяне на уникален индекс
    	$mvc->setDbIndex('correspondingDealOriginId');
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
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	
    	$row->title = $mvc->getLink($rec->id, 0);
    	$row->dealOriginId = $firstDoc->getLink(0);
    	$row->baseCurrencyCode = $rec->currencyId;
    	
    	$chargeVat = $firstDoc->fetchField('chargeVat');
    	$rec->amount /= $rec->rate;
    	$row->realAmount = $mvc->getFieldType('amount')->toVerbal($rec->amount);
    	
    	if($chargeVat == 'yes' || $chargeVat == 'separate'){
    		$amount = $rec->amount * (1 + acc_Periods::fetchByDate($rec->valior)->vatRate);
    		$row->amount = $mvc->getFieldType('amount')->toVerbal($amount);
    		$row->vatType = tr('с ДДС');
    	} else {
    		$row->vatType = tr('без ДДС');
    	}
    	
    	if($rec->amount < 0){
    		$row->amount = "<span class='red'>{$row->amount}</span>";
    	} elseif($rec->action == 'decrease'){
    		$row->amount = "<span class='red'>-{$row->amount}</span>";
    	}
    }
    
    
    /**
     * Връща вербалното представяне за артикула с коригирана стойност
     */
    private function getVerbalDetail($pRec, $rec)
    {
    	$row = new stdClass();
    	$row->name = cat_Products::getShortHyperlink($pRec->productId);
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	$pRec->amount /= $rec->rate;
    	$pRec->allocated /= $rec->rate;
    	
    	foreach (array('amount', 'allocated', 'quantity', 'allocated') as $fld){
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
    	
    	if($pRec->allocated < 0){
    		$row->allocated = "<span class='red'>{$row->allocated}</span>";
    		
    	} elseif($rec->action == 'decrease'){
    		$row->allocated = "<span class='red'>-{$row->allocated}</span>";
    	} else {
    		$row->allocated = "<span class='green'>+{$row->allocated}</span>";
    	}
    	
    	$measureShort = cat_UoM::getShortName(cat_Products::fetchField($pRec->productId, 'measureId'));
    	$row->quantity = "{$row->quantity} {$measureShort}";
    	
    	return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	if(!count($data->rec->productsData)) return;
    	
    	// Подговяме кешираната информация за артикулите във вербален вид
    	$productRows = array();
    	$count = 1;
    	foreach ($data->rec->productsData as $pRec){
    		$row = $mvc->getVerbalDetail($pRec, $data->rec);
    		$row->count = cls::get('type_Int')->toVerbal($count);
    		$productRows[] = $row;
    		$count++;
    	}
    	
    	$listFields = arr::make("count=№,name=Артикул,amount=Сума,allocated=|Разпределено|* ({$data->row->baseCurrencyCode}) |без ДДС|*", TRUE);
    	
    	// Взависимост от признака на разпределяне, показваме колоната възоснова на която е разпределено
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
    	
    	// Показваме под таблицата обобщената информация
    	$colspan = count($listFields) - 1;
    	$lastRowTpl = new core_ET(tr("|*<tr style='background-color: #eee'><td colspan='[#colspan#]' style='text-align:right'>|Общо|*</td><td style='text-align:right'><b>[#amount#]</b></td></tr>"));
    	$lastRowTpl->replace($colspan, 'colspan');
    	
    	if($data->rec->amount < 0){
    		$data->row->realAmount = "<span class='red'>{$data->row->realAmount}</span>";
    	} elseif($data->rec->action == 'decrease'){
    		$data->row->realAmount = "<span class='red'>-{$data->row->realAmount}</span>";
    	}
    	
    	$lastRowTpl->replace($data->row->realAmount, 'amount');
    	$details->append($lastRowTpl, 'ROW_AFTER');
    	
    	$tpl->append($details, 'PRODUCTS_TABLE');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	$form->setDefault('valior', dt::today());
    	
    	// Намираме ориджина и подготвяме опциите за избор на папки на контрагенти
    	expect($firstDoc = doc_Threads::getFirstDocument($rec->threadId));
    	
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
    	
    	$chargeVat = $firstDoc->fetchField('chargeVat');
    	$form->setDefault('currencyId', $firstDoc->fetchField('currencyId'));
    	
    	if($form->cmd !== 'refresh'){
    		$form->setDefault('rate', $firstDoc->fetchField('currencyRate'));
    	}
    	
    	if(isset($rec->id) && isset($rec->rate)){
    		$rec->amount /= $rec->rate;
    		$rec->amount = round($rec->amount, 2);
    	}
    	
    	if($chargeVat == 'yes' || $chargeVat == 'separate'){
    		if($form->rec->amount){
    			$form->rec->amount = $form->rec->amount * (1 + acc_Periods::fetchByDate($rec->valior)->vatRate);
    			$form->rec->amount = round($form->rec->amount, 2);
    		}
    		$form->setField('amount', "unit=с ДДС");
    	} else {
    		$form->setField('amount', "unit=без ДДС");
    	}
    	
    	$data->form->origin = $firstDoc;
    	$data->form->chargeVat =  $chargeVat;
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
    	if($firstDoc->isInstanceOf('sales_Sales')){
    		
    		// Взимаме артикулите от сметка 701
    		$entries = sales_transaction_Sale::getEntries($firstDoc->that);
    		$shipped = sales_transaction_Sale::getShippedProducts($entries, '701');
    		
    	  // Ако е покупка
    	} elseif($firstDoc->isInstanceOf('purchase_Purchases')){
    		
    		// Вземаме всички заскладени артикули
    		$entries = purchase_transaction_Purchase::getEntries($firstDoc->that);
    		$shipped = purchase_transaction_Purchase::getShippedProducts($entries, '321', TRUE);
    	} else {
    		
    		// Иначе няма
    		$shipped = array();
    	}
    	
    	$products = array();
    	if(count($shipped)){
    		foreach ($shipped as $p){
    			$products[$p->productId] = (object)array('productId' => $p->productId, 
    												     'name'      => cat_Products::getTitleById($p->productId), 
    													 'quantity'  => $p->quantity,
    													 'amount'    => $p->amount,
    			);
    			
    			if(isset($p->inStores)){
    				$products[$p->productId]->inStores = $p->inStores;
    			}
    			
    			$transportWeight = cat_Products::getParams($p->productId, 'transportWeight');
    			if(!empty($transportWeight)){
    				$products[$p->productId]->transportWeight = $transportWeight;
    			}
    			
    			$transportVolume = cat_Products::getParams($p->productId, 'transportVolume');
    			if(!empty($transportVolume)){
    				$products[$p->productId]->transportVolume = $transportVolume;
    			}
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr($self->singleTitle) . " №{$rec->id}";
    }
    
    
    /**
     * Проверява хендлъра дали може да се избере
     * 
     * @param core_Mvc $mvc  - класа
     * @param string $error  - текста на грешката
     * @param string $handle - хендлъра на сделката
     * @param stdClass $rec  - текущия запис
     */
    public static function on_AfterCheckSelectedHandle($mvc, &$error = NULL, $handle, $rec)
    {
    	if($error) return $error;
    	
    	$firstDocumentHandle = doc_Threads::getFirstDocument($rec->threadId)->getHandle();
    	if($rec->dealHandler && $rec->dealHandler != $firstDocumentHandle){
    		$doc = doc_Containers::getDocumentByHandle($handle);
    		if($doc){
    			 
    			// и да може да бъде избрана
    			if(!$mvc->checkCorespondingDocument($doc)){
    				$error = 'Трябва да е избрана активна финансова сделка или покупка само с услуги';
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	// Ако е събмитната формата
    	if($form->isSubmitted()){
    		
    		if(!isset($rec->rate)){
    			// Изчисляваме курса към основната валута ако не е дефиниран
    			$rec->rate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
    			if(!$rec->rate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		}
    		
    		// Намираме контейнера на първия документ в нишката
    		$doc = doc_Threads::getFirstDocument($rec->threadId);
    		$firstDocument = $doc;
    		$baseContainerId = $doc->fetchField('containerId');
    		$firstDocumentHandle = $doc->getHandle();
    		
    		// Ако има избран хендлър на сделка и тя не е текущата сделка
    		if($rec->dealHandler && $rec->dealHandler != $firstDocumentHandle){
    			
    			 // Намираме документа по хендлъра
    			 $doc = doc_Containers::getDocumentByHandle($rec->dealHandler);
    		}
    		
    		$correpspondingContainerId = $doc->fetchField('containerId');
    		
    		// Ако текущата сделка е кореспондент, трябва да се попълнена сумата
    		if($correpspondingContainerId === $baseContainerId && empty($rec->amount)){
    			$form->setError('amount', 'Трябва да е попълнена сумата, aко е текущата сделка кореспондент');
    		}
    		
    		if(!$form->gotErrors()){
    			$rec->correspondingDealOriginId = $correpspondingContainerId;
    			if(!isset($rec->amount)){
    				$chargeVat = $firstDocument->fetchField('chargeVat');
    				$rec->amount = $mvc->getDefaultAmountToAllocate($rec->correspondingDealOriginId, $chargeVat);
    				$rec->amount /= $rec->rate;
    				
    				if(empty($rec->amount)){
    					$form->setError('amount', 'Не може автоматично да се определи сумата, Моля задайте ръчно');
    				}
    			}
    			
    			$rec->amount *= $rec->rate;
    			
    			if($form->chargeVat == 'yes' || $form->chargeVat == 'separate'){
    				$rec->amount /= 1 + acc_Periods::fetchByDate($rec->valior)->vatRate;
    				$rec->amount = round($rec->amount, 2);
    			}
    			
    			// Kешираме от всички възможни продукти, тези които са били избрани във функционалното поле
    			$rec->productsData = array_intersect_key($form->allProducts, type_Set::toArray($rec->chosenProducts));
    			$mvc->allocateAmount($rec->productsData, $rec->amount, $rec->allocateBy, $msg);
    			
    			if(isset($msg)){
    				$form->setError('allocateBy', $msg);
    			}
    		}
    		
    		if($rec->amount == 0){
    			$form->setError('amount', 'Сумата не може да е 0');
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
    private function allocateAmount(&$products, $amount, $allocateBy, &$msg)
    {
    	$denominator = 0;
    	
    	// Първо обхождаме записите и изчисляваме знаменателя чрез който ще изчислим коефициента
    	switch ($allocateBy){
    		case 'value':
    			foreach ($products as $p){
    				$denominator += $p->amount;
    			}
    			break;
    		case 'quantity':
    			foreach ($products as $p){
    				$denominator += $p->quantity;
    			}
    			break;
    		case 'weight':
    			foreach ($products as $p){
    				if(!isset($p->transportWeight)) return $msg = 'Не може да се разпредели по тегло, докато има артикул(и) без транспортно тегло';
    				
    				$denominator += $p->transportWeight * $p->quantity;
    			}
    			break;
    		case 'volume':
    			foreach ($products as $p){
    				if(!isset($p->transportVolume)) return $msg = 'Не може да се разпредели по обем, докато има артикул(и) без транспортен обем';
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
    private function getDefaultAmountToAllocate($correspondingOriginId, $chargeVat)
    {
    	$doc = doc_Containers::getDocument($correspondingOriginId);
    	$amount = 0;
    	
    	if($doc->isInstanceOf('findeals_Deals')){
    		$amount = $doc->fetchField('amountDeal');
    	} elseif($doc->isInstanceOf('purchase_Purchases')){
    		$dRec = $doc->fetch();
    		$amount = $dRec->amountDeal;
    		if($chargeVat != 'yes' && $chargeVat != 'separate'){
    			$amount -= $dRec->amountVat;
    		}
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
    	if($doc->isInstanceOf('findeals_Deals')){
    		
    		return TRUE;
    		
    		// Ако е покупка
    	} elseif($doc->isInstanceOf('purchase_Purchases')){
    		
    		// Намираме  артикулите
    		$pQuery = purchase_PurchasesDetails::getQuery();
    		$pQuery->where("#requestId = {$doc->that}");
    		$pQuery->show('productId');
    		 
    		// Ако има поне един складируем артикул не може да се създаде
    		while($dRec = $pQuery->fetch()){
    			$pInfo = cat_Products::getProductInfo($dRec->productId);
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
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		if($firstDoc){
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
    	if($firstDoc->isInstanceOf('sales_Sales') || $firstDoc->isInstanceOf('purchase_Purchases')){
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
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if(count($rec->productsData)){
    		$detailsKeywords = '';
    		foreach ($rec->productsData as $product){
    			$detailsKeywords .= " " . plg_Search::normalizeText($product->name);
    		}
    		
    		$res = " " . $res . " " . $detailsKeywords;
    	}
    }
    
    
    public static function getAmountsForCorrection($productId, $itemId, $amount, $allocationType)
    {
    	$res = array();
    	$itemRec = acc_Items::fetch($itemId);
    	$purchaseClassId = purchase_Purchases::getClassId();
    	
    	if($itemRec->classId != sales_Sales::getClassId() && $itemRec->classId != $purchaseClassId) return $res;
    	expect(in_array($allocationType, array('value', 'quantity', 'weight', 'volume')));
    	$firstDoc = new core_ObjectReference($itemRec->classId, $itemRec->objectId);
    	
    	//$allocationType = 'volume';
    	
    	$me = cls::get('acc_ValueCorrections');
    	$products = $me->getChosenProducts($firstDoc);
    	$me->allocateAmount($products, $amount, $allocationType, $msg);
    	
    	foreach ($products as $p){
    		$creditArr = array('60201', $itemId, array('cat_Products', $productId), 'quantity' => $p->quantity);
    		
    		if($itemRec->classId == $purchaseClassId){
    			bp();
    		} else {
    			$canStore = cat_Products::fetchField($p->productId, 'canStore');
    			$accountSysId = (isset($pInfo->meta['canStore'])) ? '701' : '703';
    			$dealRec = cls::get($itemRec->classId)->fetch($itemRec->objectId, 'contragentClassId, contragentId');
    			
    			$res[] = array('amount' => $p->allocated, 
    					       'debit' => array($accountSysId, 
    					       					array($dealRec->contragentClassId, $dealRec->contragentId),
    					       					$itemId, array('cat_Products', $p->productId), 
    					       					'quantity' => 0), 
    						   'credit' => $creditArr, 'reason' => 'Корекция на стойности');
    		}
    	}
    	
    	return $res;
    }
}