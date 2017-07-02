<?php



/**
 * Абстрактен клас за наследяване от класове сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DealBase extends core_Master
{

	
	/**
	 * Работен кеш
	 */
	protected $historyCache = array();
	
	
	/**
	 * Колко записи от журнала да се показват от историята
	 */
	protected $historyItemsPerPage = 6;
	
	
	/**
	 * Колко записи от репорта да се показват от отчета
	 */
	protected $reportItemsPerPage = 10;
	
	
	/**
	 * Колко записи от репорта да се показват от отчета
	 * в csv-то
	 */
	protected $csvReportItemsPerPage = 1000;
	
	
	/**
	 * Документа продажба може да бъде само начало на нишка
	 */
	public $onlyFirstInThread = TRUE;
	
	
	/**
	 * В коя номенклатура да се вкара след активиране
	 */
	public $addToListOnActivation = 'deals';
	
	
	/**
	 * Кой има права да експортира
	 */
	public $canExport = 'powerUser';
	
	
	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'tools';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Кой може да обединява сделките
	 */
	public $canClosewith = 'ceo,dealJoin';
	
	
	/**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		if(empty($mvc->fields['closedDocuments'])){
			$mvc->FLD('closedDocuments', "keylist(mvc={$mvc->className})", 'input=none,notNull');
		}
		$mvc->FLD('closedOn', 'datetime', 'input=none');
	}
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		$rec = $mvc->fetchRec($rec);
		 
		if($rec->state == 'active'){
			$Cover = doc_Folders::getCover($rec->folderId);
			
			if($Cover->haveInterface('crm_ContragentAccRegIntf')){
				
				// Добавяме контрагента като перо, ако не е
				$listId = acc_Lists::fetchBySystemId('contractors')->id;
				acc_Items::force($Cover->getClassId(), $Cover->that, $listId);
			}
		}
	}


	/**
	 * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
	 * Генерира агрегираната бизнес информация за тази сделка
	 *
	 * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
	 * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
	 * момента.
	 *
	 * Списъка с въпросните документи, имащи отношение към бизнес информацията за продажбата е
	 * сечението на следните множества:
	 *
	 *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
	 *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
	 *  * Документите, в състояние различно от `draft` и `rejected`
	 *
	 * @return bgerp_iface_DealAggregator
	 */
	public function getAggregateDealInfo($id)
	{
		$dealRec = $this->fetchRec($id);
	
		$dealDocuments = $this->getDescendants($dealRec->id);
	
		$aggregateInfo = new bgerp_iface_DealAggregator;
	
		// Извличаме dealInfo от самата сделка
		$this->pushDealInfo($dealRec->id, $aggregateInfo);
	
		foreach ($dealDocuments as $d) {
			$dState = $d->rec('state');
			if ($dState == 'draft' || $dState == 'rejected') {
				// Игнорираме черновите и оттеглените документи
				continue;
			}
			
			if ($d->haveInterface('bgerp_DealIntf')) {
				try{
					$d->getInstance()->pushDealInfo($d->that, $aggregateInfo);
					
				} catch(core_exception_Expect $e){
					reportException($e);
				}
			}
		}
	
		return $aggregateInfo;
	}
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($res == 'no_one') return;
    	
    	// Ако няма документи с които може да се затвори или е чернова не може да се приключи с друга сделка
    	if($action == 'closewith' && isset($rec)){
    		$options = $mvc->getDealsToCloseWith($rec);
    		if(!count($options) || ($rec->state != 'draft' && $rec->state != 'pending')){
    			$res = 'no_one';
    		}
    	}
    	
        // Ако документа е активен, може да се експортва
    	if($action == 'export' && isset($rec)){ 
    		$state = (!isset($rec->state)) ? $mvc->fetchField($rec->id, 'state') : $rec->state;
    		if($state != 'active'){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако има документи в нишката на договора, не може да се затваря
    	if($action == 'close' && isset($rec)){
    		$docCountInThread = doc_Threads::fetch($rec->threadId)->allDocCnt;
    		
    		// Ако има повече от 1 документ в нишката или има контировка документа, не може да се затваря
    		if($docCountInThread != 1 || acc_Journal::fetchByDoc($mvc, $rec->id)){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($mvc->haveRightFor('closeWith', $rec)) {
    		$data->toolbar->addBtn('Обединяване', array($mvc, 'closeWith', $rec->id), "id=btnCloseWith{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Обединяване на сделката с други сделки');
    	}
    }
    
    
    /**
     * Кои сделки ще могатд а се приключат с документа
     *
     * @param object $rec
     * @return array $options - опции
     */
    public function getDealsToCloseWith($rec)
    {
    	// Избираме всички други активни сделки от същия тип и валута, като началния документ в същата папка
    	$docs = array();
    	$dealQuery = $this->getQuery();
    	$dealQuery->where("#id != {$rec->id}");
    	$dealQuery->where("#folderId = {$rec->folderId}");
    	$dealQuery->where("#currencyId = '{$rec->currencyId}'");
    	$dealQuery->where("#state = 'active'");
    	$dealQuery->where("#closedDocuments = ''");
    	
    	$valiorFld = ($this->valior) ? $this->valior : 'createdOn';
    	while($dealRec = $dealQuery->fetch()){
    		$title = $this->getRecTitle($dealRec) . " / " . (($this->valiorFld) ? $this->getVerbal($dealRec, $this->valiorFld) : '');
    		$docs[$dealRec->id] = $title;
    	}
    	 
    	return $docs;
    }


    /**
     * Преди да се проверят имали приключени пера в транзакцията
     *
     * Обхождат се всички документи в треда и ако един има приключено перо, документа начало на нишка
     * не може да се оттегля/възстановява/контира
     */
    public static function on_BeforeGetClosedItemsInTransaction($mvc, &$res, $id)
    {
    	$closedItems = array();
    	$rec = $mvc->fetchRec($id);
    	$dealItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
    	 
    	// Записите от журнала засягащи това перо
    	$entries = acc_Journal::getEntries(array($mvc, $rec->id));
    	
    	// Към тях добавяме и самия документ
    	$entries[] = (object)array('docType' => $mvc->getClassId(), 'docId' => $rec->id);
    	
    	$entries1 = array();
    	foreach ($entries as $ent){
    		$index = $ent->docType . "|" . $ent->docId;
    		if(!isset($entries1[$index])){
    			$entries1[$index] = $ent;
    		}
    	}
    	
    	// За всеки запис
    	foreach ($entries1 as $ent){
    		
    		// Ако има метод 'getValidatedTransaction'
    		$Doc = cls::get($ent->docType);
    		
    		// Ако транзакцията е направена от друг тред запомняме от кой документ е направена
    		$threadId = $Doc->fetchField($ent->docId, 'threadId');
    		if($threadId != $rec->threadId){
    			$mvc->usedIn[$dealItem->id][] = $Doc->getHandle($ent->docId);
    		}
    		
    		if(cls::existsMethod($Doc, 'getValidatedTransaction')){
    			
    			// Ако има валидна транзакция, проверяваме дали има затворени пера
    			$transaction = $Doc->getValidatedTransaction($ent->docId);
    			
    			if($transaction){
    				// Добавяме всички приключени пера
    				$closedItems += $transaction->getClosedItems();
    			}
    		}
    	}
    	
    	if($rec->state != 'closed'){
    		unset($closedItems[$dealItem->id]);
    	}
    	 
    	// Връщаме намерените пера
    	$res = $closedItems;
    }
    
    
    /**
     * Екшън за приключване на сделка с друга сделка
     */
    public function act_Closewith()
    {
    	core_App::setTimeLimit(2000);
    	$id = Request::get('id', 'int');
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'draft' || $rec->state == 'pending');
    
    	// Трябва потребителя да може да контира
    	$this->requireRightFor('conto', $rec);
    
    	$options = $this->getDealsToCloseWith($rec);
    	expect(count($options));
    
    	// Подготовка на формата за избор на опция
    	$form = cls::get('core_Form');
    	$form->title = "|Активиране на|* <b>" . $this->getTitleById($id). "</b>" . " ?";
    	$form->info = 'Посочете кои сделки желаете да обедините с тази сделка';
    	$form->FLD('closeWith', "keylist(mvc={$this->className})", 'caption=Приключи и,column=1,mandatory');
    	$form->setSuggestions('closeWith', $options);
    	$form->input();
    
    	// След като формата се изпрати
    	if($form->isSubmitted()){
    		
    		$rec->contoActions = 'activate';
    		$rec->state = 'active';
    		if(!empty($form->rec->closeWith)){
    			$rec->closedDocuments = $form->rec->closeWith;
    		}
    		$this->save($rec);
    		$this->invoke('AfterActivation', array($rec));
    	   
    		if(!empty($form->rec->closeWith)){
    			core_App::setTimeLimit(1000);
    			
    			$CloseDoc = cls::get($this->closeDealDoc);
    			$deals = keylist::toArray($form->rec->closeWith);
    			foreach ($deals as $dealId){
    					 
    				// Създаване на приключващ документ-чернова
    				$dRec = $this->fetch($dealId);
    				$clId = $CloseDoc->create($this->className, $dRec, $id);
    				$CloseDoc->conto($clId);
    			}
    		}
    	   
    		// Записваме, че потребителя е разглеждал този списък
    		$this->logWrite("Приключване на сделка с друга сделка", $id);
    		
    		return new Redirect(array($this, 'single', $id));
    	}
    
    	$form->toolbar->addSbBtn('Обединяване', 'save', 'ef_icon = img/16/tick-circle-frame.png');
    	$form->toolbar->addBtn('Отказ', array($this, 'single', $id),  'ef_icon = img/16/close-red.png');
    	
    	// Рендиране на формата
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->closedDocuments){
    		$docs = keylist::toArray($rec->closedDocuments);
    		$row->closedDocuments = '';
    		
    		foreach ($docs as $docId){
    			$row->closedDocuments .= ht::createLink($mvc->getHandle($docId), array($mvc, 'single', $docId)) . ", ";
    		}
    		$row->closedDocuments = trim($row->closedDocuments, ", ");
    	}
    	
    	if($fields['-list']){
    		$row->title = $mvc->getLink($rec->id, 0) . "<div style='font-size:0.7em;min-width:20em;'>" . doc_Folders::getTitleById($rec->folderId) . "</div>";
    	}
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     * 
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected function renderDealHistory(&$tpl, $data)
    {
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('debitAcc', 'varchar', 'tdClass=articleCell');
    	$tableMvc->FLD('creditAcc', 'varchar', 'tdClass=articleCell');
    		
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	$fields = "valior=Вальор,debitAcc=Дебит->Сметка,debitQuantity=Дебит->К-во,debitPrice=Дебит->Цена,creditAcc=Кредит->Сметка,creditQuantity=Кредит->К-во,creditPrice=Кредит->Цена,amount=Сума";
    	
    	$tpl->append($table->get($data->DealHistory, $fields), 'DEAL_HISTORY');
    	$tpl->append($data->historyPager->getHtml(), 'DEAL_HISTORY');
    	$tpl->removeBlock('STATISTIC_BAR');
    }
    
    
    /**
     * Рендира информацията за доставеното/полученото по сделката
     * 
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected function renderDealReport(&$tpl, $data)
    {
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('code', 'varchar');
    	$tableMvc->FLD('productId', 'varchar');
    	$tableMvc->FLD('measure', 'varchar', 'tdClass=accToolsCell nowrap');
    	$tableMvc->FLD('quantity', 'varchar', 'tdClass=aright');
    	$tableMvc->FLD('shipQuantity', 'varchar', 'tdClass=aright');
    	$tableMvc->FLD('bQuantity', 'varchar', 'tdClass=aright');
    	
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	$fields = $this->getExportFields();
    	
    	$tpl->append($table->get($data->DealReport, $fields), 'DEAL_REPORT');
    	$tpl->append($data->reportPager->getHtml(), 'DEAL_REPORT');
    	
    	if($this->haveRightFor('export', $data->rec) && count($data->DealReport)){
    		$expUrl = getCurrentUrl();;
    		$expUrl['export'] = TRUE;
    	
    		$btn = cls::get('core_Toolbar');
    		$btn->addBtn('Експорт в CSV', $expUrl, NULL, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат');
    		$btnCSV = 'export';
    		$btnCSVHtml = $btn->renderHtml("", $btnCSV);
    		
    		$tpl->replace($btnCSVHtml, 'TABEXP');
    	}
    	$tpl->removeBlock('STATISTIC_BAR');
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	// Ако има табове
    	if(isset($data->tabs)){
    		
    		if(isset($data->rec->tplLang)){
    			core_Lg::pop();
    		}
    		
    		$tabHtml = $data->tabs->renderHtml("", $data->selectedTab);
    		$tpl->replace($tabHtml, 'TABS');
    		
    		// Ако има избран таб и това не е статистиката, рендираме го
    		if(isset($data->{$data->selectedTab}) && $data->selectedTab != 'Statistic'){
    			$method = "render{$data->selectedTab}";
    			$mvc->$method($tpl, $data);
    		}
    		
    		if(isset($data->rec->tplLang)){
    			core_Lg::push($data->rec->tplLang);
    		}
    	}
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    		$tpl->removeBlock('STATISTIC_BAR');
    	}
    }
    
    
    /**
     * Подготвя табовете на задачите
     */
    public function prepareDealTabs_(&$data)
    {
    	$tabs = cls::get('core_Tabs', array('htmlClass' => 'deal-history-tab', 'urlParam' => 'dealTab'));
    	$url = getCurrentUrl();
    	unset($url['export']);
    	
    	$url['dealTab'] = 'Statistic';
    	$tabs->TAB('Statistic', 'Статистика' , $url);
    	
    	if(haveRole('ceo,acc')){
    		if($data->rec->state != 'draft'){
    			$url['dealTab'] = 'DealHistory';
    			$tabs->TAB('DealHistory', 'Обороти' , $url);
    		}
    	}
    	
    	$data->tabs = $tabs;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')) return;
    	
    	$mvc->prepareDealTabs($data);
    	
    	$data->selectedTab = $data->tabs->getSelected();
    	if(!$data->selectedTab){
    		$data->selectedTab = $data->tabs->getFirstTab();
    	}
    	
    	// Ако е само един таба не показваме статистиката
    	if($data->tabs->count() == 1){
    		unset($data->tabs);
    	}
    	
    	// Ако има селектиран таб викаме му метода за подготовка на данните
    	if(isset($data->selectedTab) && $data->selectedTab != 'Statistic'){
    		$method = "prepare{$data->selectedTab}";
    		$mvc->$method($data);
    		
    		// Ако е зареден флаг в урл-то за експорт експортираме
    		if(Request::get('export', 'int') && $data->selectedTab == 'DealReport' && $mvc->haveRightFor('export', $data->rec)){
    			$mvc->еxportReport($data);
    		}
    	}
    }
    
    
    /**
     * Екшън който експортира данните
     */
    protected function еxportReport(&$data)
    {
        expect(Request::get('export', 'int'));
  
    	expect($rec = $data->rec);

    	// Проверка за права
    	$this->requireRightFor('export', $rec);

    	$title = $this->title . " Поръчано/Доставено";
  
    	$Double = cls::get('type_Double');
    	foreach ($data->dealReportCSV as $rec) { 
    	    foreach(array("code", "productId", "measure", "quantity", "shipQuantity", "bQuantity") as $fld) {
    	       $rec->{$fld} = html_entity_decode(strip_tags($rec->{$fld}));
    	    }
    	}

    	$csv = $this->prepareCsvExport($data->dealReportCSV);
    
    	$fileName = str_replace(' ', '_', str::utf2ascii($title));
    	 
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	 
    	echo $csv;
    
    	shutdown();
    }
    
    
    /**
     * Екшън подготвя данните за експортира 
     */
    protected function prepareCsvExport(&$data)
    { 	
    	$exportFields = $this->getExportFields();
    	$fields = $this->getFields();

    	$csv = csv_Lib::createCsv($data, $fields, $exportFields);
    	
    	return $csv;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
    	// Кои полета ще се показват
		$f = new core_FieldSet;
    	$f->FLD('code', 'varchar');
    	$f->FLD('productId', 'richtext(bucket=Notes)');
    	$f->FLD('measure', 'varchar');
    	$f->FLD('quantity', 'double');
    	$f->FLD('shipQuantity', 'double');
    	$f->FLD('bQuantity', 'double');
    
    	return $f;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getExportFields_()
    {
    	// Кои полета ще се показват
    	$fields = arr::make("code=Код,
    					     productId=Артикул,
    					     measure=Мярка,
    					     quantity=Количество -> Поръчано,
    					     shipQuantity=Количество -> Доставено,
    					     bQuantity=Количество -> Остатък", TRUE);
    
    	return $fields;
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    protected function prepareDealReport(&$data)
    {
    	$rec = $data->rec;
		if($rec->state == 'draft') return;
    	
    	// обобщената информация за цялата нищка
    	$dealInfo = self::getAggregateDealInfo($rec->id);

    	$report = array();
    	$Double = cls::get('type_Double', array('params' => array('decimals' => '2')));

    	// Ако има записи където участва артикула подготвяме ги за показване
    	if(count($dealInfo->products) || count ($dealInfo->shippedProducts)){
    		foreach ($dealInfo->products as $id => $product) { 
		    	// ако можем да го покажем на страницата	
				$obj = new stdClass();
			    // информацията за продукта
			    $productInfo = cat_Products::getProductInfo($product->productId);
				// кода на продукта	
			    $obj->code = $productInfo->productRec->code;
			    // името на продукта с линк
				$obj->productId = $product->productId;
				// мярката му
				$measureId = $productInfo->productRec->measureId;
				$obj->measure = $measureId;
				    
				// поръчаното количество
				$obj->quantity = $product->quantity;
				
				if (!$dealInfo->shippedProducts[$id]) {
					$obj->bQuantity = $obj->quantity;
				}
	
				$report[$id] = $obj;			    	
		    }
		    
		    // за всеки един масив от доставени продукти
		    foreach ($dealInfo->shippedProducts as $idShip => $shipProduct) {

		    	// ако ид-то на продукта не е добавен в резултатния масив до сега
			    if (!array_key_exists($idShip, $report)) {

			    	// извличаме информацията за продукта
			    	$shipProductInfo = cat_Products::getProductInfo($shipProduct->productId);
			    	
			    	// намираме му мярката
			    	$shipMeasureId = $shipProductInfo->productRec->measureId;
			    	// и правим обект с новия продукт		
			    	$report[$idShip] = (object) array ( "code" => $shipProductInfo->productRec->code,
			    					"productId" => $shipProduct->productId,
			    					"measure" => $shipMeasureId,
			    					"quantity" => 0,
			    					"shipQuantity" => $shipProduct->quantity,
			    					"bQuantity" => NULL
			    					
			    	);
			    // ако вече е добавен		
			    } else { 
			    	// ще го ъпдейтнем		
			    	$shipObj = &$report[$idShip];
			    
			    	if($shipStoreId = $dealInfo->storeId){
			    		$shipQuantityInStore = (double) store_Products::fetchField("#productId = {$shipProduct->productId} AND #storeId = {$shipStoreId}", 'quantity');
			    			
			    		// като добавим доставеното количесто
			    		$shipObj->shipQuantity = $shipProduct->quantity;
			    		// и намерим остатъка за доставяне
			    		$shipObj->bQuantity = $shipObj->quantity - $shipObj->shipQuantity;
			    	} else { 
			    		// като добавим доставеното количесто
			    		$shipObj->shipQuantity = $shipProduct->quantity;
			    		// и намерим остатъка за доставяне
			    		$shipObj->bQuantity = $shipObj->quantity - $shipObj->shipQuantity;
			    	}
			    }
		    }
    	}

    	$data->dealReportCSV = array();
    	
    	foreach ($report as $k => $v) {
    	    $data->dealReportCSV[$k] = clone $v;
    	    $data->dealReportCSV[$k]->productId = cat_Products::getShortHyperLink($v->productId);
    	    $data->dealReportCSV[$k]->measure = cat_UoM::getShortName($v->measure);
    	}

    	
    	foreach ($report as $id =>  $r) { 
        	foreach (array('shipQuantity', 'bQuantity') as $fld){
        	    $r->{$fld} =  $Double->toVerbal($r->{$fld});
        	}

        	if($r->bQuantity > 0){
        	    $r->quantity = "<span class='row-negative' title = '" . tr('Количеството в склада е отрицателно') . "'>{$Double->toVerbal($r->quantity)}</span>";
        	} else {
        	    $r->quantity = $Double->toVerbal($r->quantity);
        	}
        	
        	if (isset($r->bQuantity)) {
        	    $r->bQuantity = ($r->bQuantity < 0) ? "<span style='color:red'>{$r->bQuantity}</span>" : $r->bQuantity;
        	}
        	
        	if (isset($r->productId)) {
        	   $r->productId = cat_Products::getShortHyperLink($r->productId);
        	}
        	
        	if (isset($r->measure)) {
        	   $r->measure = cat_UoM::getShortName($r->measure);
        	}
    	}

    	// правим странициране
    	$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $this->className,'itemsPerPage' => $this->reportItemsPerPage)); 

    	$cnt = count($report);
    	$pager->itemsCount = $cnt;
    	$data->reportPager = $pager;
    	 
    	$pager->calc();
    	
    	$start = $data->reportPager->rangeStart;
    	$end = $data->reportPager->rangeEnd - 1;
    
    	// проверяваме дали може да се сложи на страницата
    	$data->DealReport = array_slice ($report, $start, $end - $start + 1);
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    protected function prepareDealHistory(&$data)
    {
    	$rec = $data->rec;
    	if(!haveRole('ceo,acc')) return;
    	if($rec->state == 'draft') return;
    	
    	// Извличаме всички записи от журнала където сделката е в дебита или в кредита
    	$entries = acc_Journal::getEntries(array($this->className, $rec->id));
    	
    	$history = array();
    	$Date = cls::get('type_Date');
    	$Double = cls::get('type_Double', array('params' => array('decimals' => '2')));
    	
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->historyItemsPerPage));
    	$Pager->setPageVar($this->className, $rec->id);
    	$Pager->itemsCount = count($entries);
    	$Pager->calc();
    	$data->historyPager = $Pager;
    	
    	$start = $data->historyPager->rangeStart;
    	$end = $data->historyPager->rangeEnd - 1;
    	
    	// Ако има записи където участва перото подготвяме ги за показване
    	if(count($entries)){
    		$count = 0;
    		
    		foreach ($entries as $ent){
    			
    			if($count >= $start && $count <= $end){
    				$obj = new stdClass();
    				$obj->valior = $Date->toVerbal($ent->valior);
    				$docHandle = cls::get($ent->docType)->getLink($ent->docId, 0);
    				
    				$obj->valior .= "<br>{$docHandle}";
    				$obj->valior = "<span style='font-size:0.8em;'>{$obj->valior}</span>";
    				if(empty($this->historyCache[$ent->debitAccId])){
    					$this->historyCache[$ent->debitAccId] = acc_Balances::getAccountLink($ent->debitAccId);
    				}
    				 
    				if(empty($this->historyCache[$ent->creditAccId])){
    					$this->historyCache[$ent->creditAccId] = acc_Balances::getAccountLink($ent->creditAccId);
    				}
    				$obj->debitAcc = $this->historyCache[$ent->debitAccId];
    				$obj->creditAcc = $this->historyCache[$ent->creditAccId];
    				 
    				foreach (range(1, 3) as $i){
    					if(!empty($ent->{"debitItem{$i}"})){
    						$obj->debitAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"debitItem{$i}"}, 'titleLink') . "</div>";
    					}
    				
    					if(!empty($ent->{"creditItem{$i}"})){
    						$obj->creditAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"creditItem{$i}"}, 'titleLink') . "</div>";
    					}
    				}
    				 
    				foreach (array('debitQuantity', 'debitPrice', 'creditQuantity', 'creditPrice', 'amount') as $fld){
    					$obj->{$fld} = "<span style='float:right'>" . $Double->toVerbal($ent->{$fld}) . "</span>";
    				}
    				 
    				$history[] = $obj;
    			}
    			
    			$count++;
    		}
    	}
    	
    	$data->DealHistory = $history;
    }
}