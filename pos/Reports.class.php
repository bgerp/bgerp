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
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo, admin';
    
    
    /**
	 * Детайли на репорта
	 */
	var $details = 'pos_ReportDetails';
	
	
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
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	
    	// Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    	
    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->total = $double->toVerbal($row->total);
    	$row->paid = $double->toVerbal($row->paid);
    }
    
    
    static function on_AfterCreate($mvc, $rec)
    {
    	$reportData = $mvc->fetchData($rec->pointId, $rec->cashier);
    	foreach($reportData as $detail){
    		$detail->reportId = $rec->id;
    		$mvc->pos_ReportDetails->save($detail);
    	}
    		
    	$saleAmount = $paymentAmount = 0;
    	foreach($reportData as $detail) {
    		($detail->action == 'sale') ? $saleAmount += $detail->amount : $paymentAmount += $detail->amount;	
    	}
    		
    	$rec->total = $saleAmount;
    	$rec->paid = $paymentAmount;
    	static::save($rec);
    }
    
    
    /**
     * Пушваме css 
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
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
     * @param boolean $onlyReceipts 
     * 					           FALSE - дали да извлече обобщението
     * 							   на детайлите на бележките 
     * 							   TRUE - извлича списък от ид-та на бележките
     * 							   отговарящи на условието
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId, $userId, $onlyReceipts = FALSE)
    {
    	expect(pos_Points::fetch($pointId));
    	expect(core_Users::fetch($userId));
    	$results = array();
    	$query = pos_Receipts::getQuery();
    	$query->where("#pointId = {$pointId}");
    	$query->where("#createdBy = {$userId}");
    	$query->where("#state = 'active'");
    	if(!$onlyReceipts){
    		
    		// извличаме нужната информация за продажбите и плащанията
    		$this->fetchReceiptData($query, &$results);
    	} else {
    		
    		// Ако искаме само беелжките, намираме ид-та на тези отговарящи на условието
	    	while($rec = $query->fetch()) {
	    		$results[] = $rec->id;
	    	}
    	}
    	
    	return $results;
    }
    
    
    /**
     * Връща продажбите и плащанията направени в търсените бележки групирани
     * @param core_Query $query - Заявка към модела
     * @param array $results - Масив в който ще връщаме резултатите
     */
    private function fetchReceiptData($query, &$results)
    {
    	while($rec = $query->fetch()) {
	    	
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
    	$receipts = $mvc->fetchData($rRec->pointId, $rRec->cashier, TRUE);
    	foreach($receipts as $receiptId){
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
		
	}
}