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
    var $title = 'Отчети';
    
    
    /**
     * Плъгини за зареждане
     */
   var $loadList = 'plg_RowTools, pos_Wrapper, plg_Printing,
     	  doc_DocumentPlg, bgerp_plg_Blank, doc_ActivatePlg';
   
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Отчет";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/report.png';
    

    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "20";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
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
    var $listFields = 'tools=Пулт, title=Заглавие, pointId, cashier, paid, total, state, createdOn, createdBy';
    
    
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
    	$this->FLD('total', 'float(minDecimals=2)', 'caption=Сума->Продадено, input=none, value=0');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    	$this->FLD('details', "blob(serialize,compress)", 'caption=Данни,input=none');
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
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('user', 'user(roles=pos|admin, allowEmpty)', 'width=12em,silent');
		$data->listFilter->FNC('point', 'key(mvc=pos_Points, select=title, allowEmpty)', 'width=12em,silent');
        $data->listFilter->FNC('totalSum', 'float', 'width=6em,placeholder=Сума,silent');
		$data->listFilter->FNC('paidSum', 'float', 'width=6em,placeholder=Платено,silent');
		$data->listFilter->FNC('from', 'date', 'width=6em,placeholder=От,silent');
		$data->listFilter->FNC('to', 'date', 'width=6em,silent');
		$data->listFilter->setDefault('to', dt::now());
		$data->listFilter->showFields = 'user,point,totalSum,paidSum,from,to';
        
        // Активиране на филтъра
        $data->listFilter->input('user,point,totalSum,paidSum,from,to', 'silent');
	 }
	 
	 
	/**
     *  Филтрираме репорта
     */
	public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy('#createdOn', 'DESC');
    	if($filter = $data->listFilter->rec) {
    		if($filter->to) {
    			$data->query->where("#createdOn <= '{$filter->to} 23:59:59'");
    		}
    		
    		if($filter->from) {
    			$data->query->where("#createdOn >= '{$filter->from}'");
    		}
    		
    		if($filter->paidSum) {
    			$data->query->where("#paid <= {$filter->paidSum}");
    		}
    		
    		if($filter->totalSum) {
    			$data->query->where("#total <= {$filter->totalSum}");
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
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	
    	// Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    	$storeRec = pos_Points::fetchField($rec->pointId, 'storeId');
    	$storeRow = pos_Points::recToVerbal($storeRec, 'storeId');
    	
    	$row->storeId = $storeRow->storeId;
    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->total = $double->toVerbal($row->total);
    	$row->paid = $double->toVerbal($row->paid);
    	$row->title = "POS Отчет №{$rec->id}";
    	if($fields['-list']){
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id));
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$reportData = $mvc->fetchData($rec->pointId, $rec->cashier);
    		$rec->details = $reportData;
    		$rec->total = $rec->paid = 0;
    		
    		if(count($reportData->receiptDetails)){
		    	foreach($reportData->receiptDetails as $detail) {
		    		($detail->action == 'sale') ? $rec->total += $detail->amount : $rec->paid += $detail->amount;	
		    	}
    		} else {
    			$form->setError('cashier, pointId', 'Няма активни бележки');
    		}
    	}
    }
    
    
    /**
     * Пушваме css 
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    	
    	// Рендираме плащанията
    	$tpl->append($mvc->renderListTable($data->rec->details->paymentsRow), "PAYMENTS");
    	$tpl->append($data->rec->details->paymentsRow->pager->getHtml(), "PAY_PAGINATOR");
    	
    	// Рендираме продажбите
    	$tpl->append($mvc->renderListTable($data->rec->details->salesRow), "SALES");
    	$tpl->append($data->rec->details->salesRow->pager->getHtml(), "SALE_PAGINATOR");
    }
    
    
    /**
     * @TODO
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$detail = &$data->rec->details;
    	$salesRow = array();
    	$paymentsRow = array();
    	foreach($detail->receiptDetails as $rec) {
	    		
	    		// Обработваме вербалното представяне на детайла
	    		$row = $mvc->getVerbalDetails($rec);
	    		($rec->action == 'payment') ? $paymentsRow[] = $row : $salesRow[] = $row;
	    	}
	    	
	    	// Подготвяме табличната информация и пейджъра на плащанията
	    	$detail->paymentsRow->listFields = "value=Плащане, amount=Сума";
    		$detail->paymentsRow->rows = $paymentsRow;
    		$mvc->prepareDetail($detail->paymentsRow);
    		
    		// Подготвяме табличната информация и пейджъра на продажбите
    		$detail->salesRow->listFields = "value=Продукт, quantity=Количество, amount=Сума";
	    	$detail->salesRow->rows = $salesRow;
	    	$mvc->prepareDetail($detail->salesRow);
	}
    
    
	/**
	 * @TODO 
	 */
    function prepareDetail(&$rows)
    {
    	$newRows = array();
    	
    	// Инстанцираме пейджър-а
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => '7'));
    	$Pager->itemsCount = count($rows->rows);
    	$Pager->calc();
    	if($rows->rows){
    		 $start = $Pager->rangeStart;
    		 $end = $Pager->rangeEnd - 1;
    		 for($i=0;$i<count($rows->rows);$i++){
    		 	if($i >= $start && $i <= $end){
    		 		
    		 		// Добавяме всеки елемент отговарящ на условието на
    		 		// пейджъра в нов масив
    		 		$newRows[] = $rows->rows[$i];
    		 	}
    		 }
    		 
    		 // Заместваме стария масив с новия филтриран
    		 $rows->rows = $newRows;
    		 
    		 // Добавяме пейджъра
    		 $rows->pager = $Pager;
    	}
    }
    
    
    /**
     * Функция обработваща детайл на репорта във вербален вид
     * @param stdClass $rec-> запис на продажба или плащане
     * @return stdClass $row-> вербалния вид на записа
     */
    private function getVerbalDetails($rec)
    {
    	$row = new stdClass();
    	$varchar = cls::get("type_Varchar");
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($rec->amount); 
    	
    	if($rec->action == 'sale') {
    		
    		// Ако детайла е продажба
    		$info = cat_Products::getProductInfo($rec->value, $rec->pack);
    		$product = $info->productRec;	
    		if($rec->pack){
    			$pack = cat_Packagings::fetchField($rec->pack, 'name');
    		} else {
    			$pack = cat_UoM::fetchField($product->measureId, 'shortName');
    		}
    		$row->value = $product->code . " - " . $product->name;
    		$row->value = ht::createLink($row->value, array("cat_Products", 'single', $rec->value));
    		$double->params['decimals'] = 0;
    		$row->quantity = $pack . " - " . $double->toVerbal($rec->quantity);
    	} else {
    		
    		// Ако детайла е плащане
    		$value = pos_Payments::fetchField($rec->value, 'title');
    		$row->value = $varchar->toVerbal($value);
    	}
    	$currencyCode = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->amount .= " <span class='cCode'>{$currencyCode}</span>";
		
    	return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет №{$rec->id}";
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
     * на дадена точка (@see pos_Reports)
     * @param int $pointId - Ид на точката на продажба
     * @param int $userId - Ид на потребител в системата
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId, $userId)
    {
    	expect(pos_Points::fetch($pointId));
    	expect(core_Users::fetch($userId));
    	$details = $receipts = array();
    	$query = pos_Receipts::getQuery();
    	$query->where("#pointId = {$pointId}");
    	$query->where("#createdBy = {$userId}");
    	$query->where("#state = 'active'");
    	
    	// извличаме нужната информация за продажбите и плащанията
    	$this->fetchReceiptData($query, $details, $receipts);
    	
    	return (object)array('receipts' => $receipts, 'receiptDetails' =>$details);
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
    		$receipts[] = $rec->id;
    		
    		// Добавяме детайлите на бележката запазвайки уникалните им ид-та 
	    	$data = pos_ReceiptDetails::fetchReportData($rec->id);
	    	foreach($data as $obj) {
		    		
	    	// проверяваме дали в новия масив има обект с value и pack
			// равни на текущия обект
			$object = $this->findDetail($results, array('value' => $obj->value, 'pack' => $obj->pack), $obj->action);
			if(!$object) {
			    		
			    // Ако няма такъв обект то добавяме първия уникален детайл
			    $results[] = $obj;
			} else {
			    		
				    // Ако вече има обект с това value и pack (Ако детайла е продажба)
				    // ние сумираме неговите количество и сума към вече добавения елемент
				    $object->quantity += $obj->quantity;
				    $object->amount = (float)(string)$object->amount + (string)$obj->amount;
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
	 * След като документа се активира, намираме кои бележки включва
	 * и ги затваряме
	 */
	public static function on_Activation($mvc, &$rec)
    {
    	$rRec = $mvc->fetch($rec->id);
    	foreach($rRec->details->receipts as $receiptId){
    		$receiptRec = pos_Receipts::fetch($receiptId);
    		$receiptRec->state = 'closed';
    		pos_Receipts::save($receiptRec);
    	}
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'activate'){
		}
	}
}