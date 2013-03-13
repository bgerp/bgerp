<?php



/**
 * Модел Отчети
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Reports extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Отчети за бързи продажби';
    
    
    /**
     * Плъгини за зареждане
     */
   var $loadList = 'pos_Wrapper, plg_Printing, doc_DocumentPlg, 
   					bgerp_plg_Blank, doc_ActivatePlg, plg_Sorting';
   
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Отчет за бързи продажби";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/report.png';
    

    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "20";
    
    
    /**
     * Брой продажби на страница
     */
    var $listDetailsPerPage = '40';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo, admin';
    
    
	/**
     * Абревиатура
     */
    var $abbr = "Otch";
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'pos, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'pos/tpl/SingleReport.shtml';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title=Заглавие, pointId, cashier, total, paid, change, productCount, state, createdOn, createdBy';
    
    
	/**
     * Групиране на документите
     */
    var $newBtnGroup = "3.4|Търговия";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=title)', 'caption=Точка, width=9em, mandatory');
    	$this->FLD('cashier', 'user(roles=pos|admin)', 'caption=Касиер, width=9em');
    	$this->FLD('paid', 'float(minDecimals=2)', 'caption=Сума->Платено, input=none, value=0');
    	$this->FLD('change', 'float(minDecimals=2)', 'caption=Сума->Ресто, input=none, value=0');
    	$this->FLD('total', 'float(minDecimals=2)', 'caption=Сума->Продадено, input=none, value=0');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    	$this->FLD('details', 'blob(serialize,compress)', 'caption=Данни,input=none');
    	$this->FLD('productCount', 'int', 'caption=Продукти, input=none, value=0');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$data->form->setDefault('cashier', core_Users::getCurrent());
    	$data->form->setDefault('pointId', pos_Points::getCurrent());
    	$data->form->setReadOnly('pointId');
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $filterTpl = new ET(tr('|*' . getFileContent('pos/tpl/FilterForm.shtml')));
		$data->query->orderBy('#createdOn', 'DESC');
		$data->listFilter->layout = $filterTpl->getBlock('FORM');
		$data->listFilter->fieldsLayout = $filterTpl->getBlock('FIELDS');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('user', 'user(roles=pos|admin, allowEmpty)', 'caption=Касиер,width=12em,silent');
		$data->listFilter->FNC('point', 'key(mvc=pos_Points, select=title, allowEmpty)', 'caption=Точка,width=12em,silent');
        $data->listFilter->FNC('from', 'date', 'width=6em,caption=От,silent');
		$data->listFilter->FNC('to', 'date', 'width=6em,caption=До,silent');
		$data->listFilter->setDefault('from', date('Y-m-01'));
		$data->listFilter->setDefault('to', date("Y-m-t", strtotime(dt::now())));
		$data->listFilter->showFields = 'user,point,from,to';
        
        // Активиране на филтъра
        $data->listFilter->input('user,point,from,to', 'silent');
		
		if($filter = $data->listFilter->rec) {
			
			if($filter->from) {
    			$data->query->where("#createdOn >= '{$filter->from}'");
    		}
    		
			if($filter->to) {
    			$data->query->where("#createdOn <= '{$filter->to} 23:59:59'");
    		}
    		
    		if($filter->user) {
    			$data->query->where("#createdBy = {$filter->user}");
    		}
    		
    		if($filter->point) {
    			$data->query->where("#pointId = {$filter->point}");
    		}
    	}
	}

	
	/**
	 * Рендираме обобщаващата информация на отчетите
	 */
	static function on_AfterRenderListSummary($mvc, $tpl, $data)
    {
    	$tpl = static::renderSummaryData($data->query);
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
	}
    
	
	/**
	 * Рендира съмаризираната информация на списъчния изглед
	 * @param core_Query $query - заявка към модел
	 * @return core_ET $tpl - шаблон с съмаризираната информация
	 */
	static function renderSummaryData(core_Query $query)
	{
		$queryCopy = clone $query;
    	$queryCopy->show = array();
    	$queryCopy->groupBy = array();
    	$queryCopy->executed = FALSE;
    	$queryCopy->XPR('sumTotal', 'double', 'SUM(#total)');
    	$queryCopy->XPR('sumPaid', 'double', 'SUM(#paid)');
    	$queryCopy->XPR('sumChange', 'double', 'SUM(#change)');
    	$queryCopy->XPR('count', 'int', 'count(#id)');
    	$queryCopy->XPR('products', 'int', 'SUM(#productCount)');
    	$queryCopy->show('sumTotal,sumPaid,count,sumChange,products');
    	$queryCopy->where("#state = 'active'");
    	if(!$rec = $queryCopy->fetch()) {
    		
    		// Ако няма резултати, всичките стават 0
    		foreach($queryCopy->show as $el) {
    			$rec->$el = 0;
    		}
    	}
    	
    	// Вербална обработка на извлечените суми
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$rec->sumTotal = $double->toVerbal($rec->sumTotal);
    	$rec->sumPaid = $double->toVerbal($rec->sumPaid);
    	$rec->sumChange = $double->toVerbal($rec->sumChange);
    	$rec->currency = acc_Periods::getBaseCurrencyCode();
    	
    	// Зареждаме и подготвяме шаблона
    	$tpl = new ET(tr('|*' . getFileContent("pos/tpl/Summary.shtml")));
    	$tpl->placeObject($rec);
    	
    	return $tpl;
	}
	
	
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	
    	$storeRec = pos_Points::fetchField($rec->pointId, 'storeId');
    	$storeRow = pos_Points::recToVerbal($storeRec, 'storeId');
    	$row->storeId = $storeRow->storeId;
    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->total = $double->toVerbal($row->total);
    	$row->paid = $double->toVerbal($row->paid);
    	$row->change = $double->toVerbal($row->change);
    	$row->title = "Отчет за бърза продажба №{$rec->id}";
    	
    	if($fields['-list']){
    		$icon = sbf($mvc->singleIcon);
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, array('style' => "background-image:url({$icon})", 'class' => 'linkWithIcon'));
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$reportData = $mvc->fetchData($form->rec->pointId, $form->rec->cashier);
    		
    		// Проверяваме все пак дали има данни за репорта
    		if(!count($reportData->receiptDetails)){
    			$form->setError('cashier, pointId', 'Няма активни бележки');
    			return;
    		}
    	}
    }
    
    
    /**
     * Функция която обновява информацията на репорта
     * извиква се след изпращането на формата и при
     * активация на документа
     * @param stdClass $rec - запис от модела
     */
    private function extractData(&$rec)
    {
    	// Извличаме информацията от бележките
    	$reportData = $this->fetchData($rec->pointId, $rec->cashier);
    	
    	$rec->details = $reportData;
    	$rec->productCount = $rec->change = $rec->total = $rec->paid = 0;
    	if(count($reportData->receiptDetails)){
		    foreach($reportData->receiptDetails as $detail) {
		    	
		    	// Изчисляваме общата и платената сума на всички 
		    	($detail->action == 'sale') ? $rec->total += $detail->amount : $rec->paid += $detail->amount;	
			}
   	 	}
   	 	
   	 	foreach($reportData->receipts as $receipt){
   	 		
   	 		// Изчисляваме рестото и броя на продуктите от бележките
   	 	 	$rec->change += $receipt->change;
   	 	 	$rec->productCount += $receipt->productCount;
   	 	}
    }
    
    
    /**
     * Пушваме css и рендираме "детайлите"
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	// Рендираме продажбите
    	$tpl->append($mvc->renderListTable($data->rec->details), "SALES");
    	if($data->rec->details->pager){
    		$tpl->append($data->rec->details->pager->getHtml(), "SALE_PAGINATOR");
    	}
    	
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    }
    
    
    /**
     * Обработка детайлите на репорта
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
    	if(!$data->rec->details){
    		
    		// Ако няма записани детайли извличаме актуалните
    		$mvc->extractData($data->rec);
    		$iconStyle = $data->row->iconStyle;
    		$header = $data->row->header;
    		$data->row = static::recToVerbal($data->rec);
    		$data->row->iconStyle = $iconStyle;
    		$data->row->header = $header;
    	}
    	
    	$detail = &$data->rec->details;
    	arr::order($detail->receiptDetails, 'action');
	   
	    // Табличната информация и пейджъра на плащанията
	    $detail->listFields = "value=Действие, pack=Мярка, quantity=Количество, amount=Сума ({$data->row->baseCurrency})";
    	$detail->rows = $detail->receiptDetails;
    	$mvc->prepareDetail($detail);
	}
    
    
	/**
	 * Инстанциране на пейджъра и модификации по данните спрямо него
	 * @param stdClass $detail - Масив с детайли на отчета (плащания или продажби)
	 */
    function prepareDetail(&$detail)
    {
    	$newRows = array();
    	
    	// Инстанцираме пейджър-а
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    	$Pager->itemsCount = count($detail->rows);
    	$Pager->calc();
    	
    	// Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
    	if($detail->rows){
    		 $start = $Pager->rangeStart;
    		 $end = $Pager->rangeEnd - 1;
    		 for($i = 0; $i < count($detail->rows); $i++){
    		 	if($i >= $start && $i <= $end){
    		 		$newRows[] = $this->getVerbalDetail($detail->rows[$i]);
    		 	}
    		 }
    		 
    		 // Заместваме стария масив с новия филтриран
    		 $detail->rows = $newRows;
    		 
    		 // Добавяме пейджъра
    		 $detail->pager = $Pager;
    	}
    }
    
    
    /**
     * Функция обработваща детайл на репорта във вербален вид
     * @param stdClass $rec-> запис на продажба или плащане
     * @return stdClass $row-> вербалния вид на записа
     */
    private function getVerbalDetail($rec)
    {
    	$row = new stdClass();
    	$varchar = cls::get("type_Varchar");
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($rec->amount); 
    	$currencyCode = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$double->params['decimals'] = 0;
    	if($rec->action == 'sale') {
    		
    		// Ако детайла е продажба
    		$row->ROW_ATTR['class'] = 'report-sale';
    		$info = cat_Products::getProductInfo($rec->value, $rec->pack);
    		$product = $info->productRec;	
    		if($rec->pack){
    			$row->pack = cat_Packagings::fetchField($rec->pack, 'name');
    		} else {
    			$row->pack = cat_UoM::fetchField($product->measureId, 'name');
    		}
    		$icon = sbf("img/16/package-icon.png");
    		$row->value = $product->code . " - " . $product->name;
    		$row->value = ht::createLink($row->value, array("cat_Products", 'single', $rec->value), NULL, array('style' => "background-image:url({$icon})", 'class' => 'linkWithIcon'));
    		$row->quantity = $double->toVerbal($rec->quantity);
    	} else {
    		
    		// Ако детайла е плащане
    		$row->pack = $currencyCode;
    		$value = pos_Payments::fetchField($rec->value, 'title');
    		$row->value = tr("Плащания") . ": &nbsp;<i>" . $varchar->toVerbal($value) . "</i>";
    		$row->quantity = $double->toVerbal($rec->quantity);
    		$row->ROW_ATTR['class'] = 'report-payment';
    	}
    	
    	return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет за бърза продажба №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
    /**
     * Подготвя информацията за направените продажби и плащания
     * от всички бележки за даден период от време на даден потребител
     * на дадена точка
     * @param int $pointId - Ид на точката на продажба
     * @param int $userId - Ид на потребител в системата
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId, $userId)
    {
    	$details = $receipts = array();
    	$query = pos_Receipts::getQuery();
    	$query->where("#pointId = {$pointId}");
    	$query->where("#createdBy = {$userId}");
    	$query->where("#state = 'active'");
    	
    	// извличаме нужната информация за продажбите и плащанията
    	$this->fetchReceiptData($query, $details, $receipts);
    	
    	return (object)array('receipts' => $receipts, 'receiptDetails' => $details);
    }
    
    
    /**
     * Връща продажбите и плащанията направени в търсените бележки групирани
     * @param core_Query $query - Заявка към модела
     * @param array $results - Масив в който ще връщаме резултатите
     * @param array $receipts - Масив от бележките които сме обходили
     */
    private function fetchReceiptData($query, &$results, &$receipts)
    {
    	while($rec = $query->fetch()) {
	    	
    		// запомняме кои бележки сме обиколили
    		$receipts[] = $rec;
    		
    		// Добавяме детайлите на бележката
	    	$data = pos_ReceiptDetails::fetchReportData($rec->id);
	    	foreach($data as $obj) {
		    
		    	// проверяваме дали в новия масив има обект с value и pack равни на текущия обект
				$object = $this->findDetail($results, array('value' => $obj->value, 'pack' => $obj->pack), $obj->action);
		    	if(!$object) {
				    		
				    // Ако няма такъв обект то добавяме първия уникален детайл
				    $results[] = $obj;
				} else {
					
				    // Ако вече има обект с това value и pack (Ако детайла е продажба)
					// ние сумираме неговите количество и сума към вече добавения елемент
					$object->quantity += $obj->quantity;
					$object->amount = $object->amount + $obj->amount;
				}
	    	}
    	}
    }
    
    
    /**
     * Помощна функция проверяваща дали в масив от детайли има обект с
     * value и pack (ако детайла е продажба) и връщащ негова референция
     * @param array $array - Масив в който ще проверяваме обектите
     * @param aray $value - Масив от стойности, които ще проверяваме
     */
    private function findDetail($array, $value, $action){
    	$id = $value['value'];
    	$pId = $value['pack'];
	    foreach ($array as $element) {
		     if ($id == $element->value && $pId == $element->pack && $element->action == $action) {
		           
		     	// Ако в масива има търсения обект ние го връщаме
		     	return $element;
		     }
	    }
	
	    return FALSE;
	}
	
	
	/**
	 * След като документа се активира, обновяваме
	 * данните му и после затваряме всички бележки, които
	 * включва
	 */
	public static function on_Activation($mvc, &$rec)
    {
    	$rRec = $mvc->fetch($rec->id);
    	
    	// Обновяваме информацията в репорта, ако има промени
    	$mvc->extractData($rRec);
    	$mvc->save($rRec);
    	
    	// Всяка бележка в репорта се "затваря"
    	foreach($rRec->details->receipts as $receiptRec){
    		$receiptRec->state = 'closed';
    		pos_Receipts::save($receiptRec);
    	}
    }
    
    
    /**
     * След обработка на ролите
     */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		// Никой неможе да редактира бележка
		if($action == 'activate' && !$rec) {
			$res = 'no_one';
		}
	}
}