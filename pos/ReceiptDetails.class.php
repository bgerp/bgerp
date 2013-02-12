<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_ReceiptDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на бележката';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'receiptId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'tools=Пулт';
    
    
	/**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "20";

    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
    	$this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em');
    	$this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
    	$this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, width=14em');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
    	$this->FLD('price', 'float(minDecimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'int', 'caption=К-во,placeholder=К-во,width=3em');
    	$this->FLD('amount', 'float(minDecimals=2)', 'caption=Сума, input=none,input=none');
    	$this->FLD('value', 'varchar(32)', 'caption=Стойност, input=hidden');
    	$this->FLD('discountPercent', 'percent(Max=1)', 'caption=Отстъпка->Процент,input=none');
        $this->FLD('discountSum', 'float(minDecimals=2)', 'caption=Отстъпка->Сума,input=none');
        $this->FLD('fixbon', 'enum(yes=Да,no=Не)', 'caption=Фискален Бон,input=none,value=yes');
    }
    
    
	/**
     * Подготовка на Детайлите
     */
    function prepareDetail_($data)
    {
    	$this->prepareAddForm($data);
    	parent::prepareDetail_($data);
    }
    
    
    /**
     * Подготвя формата за добавяне на запис под бележката
     */
    function prepareAddForm(&$data)
    {
    	if($this->haveRightFor('add')) {
	    	$form = static::getForm();
	    	$form->method = 'POST';
	    	$form->layout = new ET(getFileContent("pos/tpl/DetailsForm.shtml"));
	    	$form->fieldsLayout = $this->createFormFieldsLayout();
	    	$form->setField('id', 'input=none');
	    	$form->setDefault('receiptId', $data->masterId);
	    	$data->form = $form;
	    	$this->invoke('AfterPrepareEditForm', array($data));
	    }
    }
    
    
    /**
     * Подготвя лейаута на полетата на формата и добавя допълнителни бутони
     * @return core_ET $tpl
     */
    function createFormFieldsLayout()
    {
    	$tpl = new ET(getFileContent("pos/tpl/DetailsFormFields.shtml"));
    	$tpl->append(ht::createSbBtn('Запис', 'default', NULL, NULL, array('class' =>  'buttonForm')), 'FIRST_ROW');
    	$tpl->append(ht::createFnBtn('+1', '','', array('id'=>'incBtn','class'=>'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('-1', '','', array('id'=>'decBtn','class'=>'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('Баркод', "window.WebScan.scanThenLoadURL('[SCANVALUE]')", '', array('class'=>'webscan')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('Маса', '','', array('class'=>'actionBtn', 'data-type'=>'client|table')), 'THIRD_ROW');
	    $tpl->append(ht::createFnBtn('Стая', '','', array('class'=>'actionBtn', 'data-type'=>'client|room')), 'THIRD_ROW');
	    $tpl->append(ht::createFnBtn('Кл. Карта', '','', array('class'=>'actionBtn', 'data-type' =>'client|ccard')), 'THIRD_ROW');
    	$payments = pos_Payments::fetchSelected();
	    foreach($payments as $payment) {
	    	$attr = array('class' => 'actionBtn', 'data-type' =>"payment|" . $payment->id);
	    	$tpl->append(ht::createFnBtn($payment->title, '','', $attr), 'SECOND_ROW');
	    }
	    
	    return $tpl;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	$blocksTpl = new ET(getFileContent('pos/tpl/ReceiptDetail.shtml'));
    	$saleTpl = $blocksTpl->getBlock('sale');
    	$discountTpl = $blocksTpl->getBlock('discount');
    	$paymentTpl = $blocksTpl->getBlock('payment');
    	$clientTpl = $blocksTpl->getBlock('client');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$action = explode('|', $data->recs[$row->id]->action);
	    		$rowTpl = clone(${"{$action[0]}Tpl"});
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	}
    	
    	$tpl->append($data->form->renderHtml(), 'ADD_FORM');
    	
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$double = cls::get('type_Double');
    	$varchar = cls::get('type_Varchar');
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($rec->amount);
    	$row->price = $double->toVerbal($rec->price);
    	$double->params['decimals'] = 0;
    	$row->quantity = $double->toVerbal($rec->quantity);
    	
    	if($rec->productId) {
    		$row->productId = cat_Products::fetchField($rec->productId, 'name');
    		$row->productId = $varchar->toVerbal($row->productId);
    	} 
    	
    	if($rec->discountPercent) {
    		$row->discountPercent = $double->toVerbal($rec->discountPercent) . " %";
    	}
    	
    	if($rec->discountSum) {
    		$row->discountSum = $double->toVerbal($rec->discountSum);
    	}
    	
    	$action = explode("|", $rec->action);
    	$row->actionType = $action[0];
    	if($row->actionType == 'payment') {
    		$value = pos_Payments::fetchField($action[1], 'title');
    		$row->actionValue = $varchar->toVerbal($value);
    	}
    	
    	//@TODO
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$rec->ean = trim($rec->ean);
    		$action = explode("|", $rec->action);
	    	switch($action[0]) {
	    		case 'sale':
	    			
	    			//Ако действието е "продажба"
	    			$mvc->getProductInfo($rec);
	    			if(!$rec->productId) {
	    				$form->setError('ean', 'Няма такъв продукт в системата');
	    			}
	    			break;
	    		case 'payment':
	    			
	    			// Ако действието е "плащане"
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Не сте въвели валидно число');
	    			}
	    			$rec->amount = $rec->ean;
	    			break;
	    		case 'discount':
	    			
	    			// Ако действието е "отстъпка"
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Не сте въвели валидно число');
	    			}
	    			$param = ucfirst(strtolower($action[1]));
	    			$rec->{"discount{$param}"} = (double)$rec->ean;
	    			break;
	    		case 'client':
	    			
	    			// Ако действието е "клиент"
	    			$mvc->getClientInfo($rec);
	    			break;
	    	}
	    }
    }
    
    
    /**
     * Изчлича информацията за клиента
     * @param stdClass $rec
     */
    function getClientInfo(&$rec)
    {
    	//@TODO
    	$rec->param = $rec->ean;	
    }
    
    
    /**
     * Намира продукта по подаден номер и изчислява неговата цена
     * и отстъпка спрямо клиента, и ценоразписа
     * @param stdClass $rec
     */
    function getProductInfo(&$rec)
    {
    	try{
    		$rec->productId = cat_Products::fetchField(array("#code='[#1#]'", $rec->ean), 'id');
    	} catch(Exception $e) {
    		$rec->productid = NULL;
    		
    		return;
    	}
    	
    	$priceCls = cls::get('cat_PricePolicyMockup');
    	$receiptRec = pos_Receipts::fetch($rec->receiptId);
    	if($receiptRec->contragentClass) {
    	$price = $priceCls->getPriceInfo($receiptRec->contragentClass,
    									 $receiptRec->contragentObjectId, 
    									 $rec->productId,
    									 NULL, $rec->quantity, $receiptRec->date);
    	} else {
    		$price = $priceCls->getPriceInfo(NULL, NULL, $rec->productId);
    	}
    				
    	$price = $this->applyDiscount($price, $rec->receiptId);
    	
    	$rec->price = $price->price;
    	if($price->discount != 0.00) {
    		$rec->discountPercent = $price->discount;
    	}
    	
    	$rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изчислява  и прилага отстъпката от цената на продукта
     * @param stdClass $price - Обект върнат от ценоразписа
     * @param int $receiptId - Ид на бележката
     * @return stdClass $finalPrice - Отстъпката  
     * и сумата с приспадната отстъпка 
     */
    function applyDiscount($price, $receiptId)
    {
    	$pPrice = $price->price;
    	$pDiscount = $price->discount;
    	$finalPrice = new stdClass();
    	$clientDiscount = round(($price->price * $price->discount / 100), 2);
    	
    	// Проверяваме дали има последно зададена отстъпка от касиера
    	$query = $this->getQuery();
    	$query->where("#receiptId = {$receiptId}");
    	$query->where("#action LIKE '%discount%'");
    	$query->orderBy("#id", "DESC");
    	if($dRec = $query->fetch()) {
    		$action = explode("|", $dRec->action);
    		if($action[1] == 'percent') {
    			
    			// Ако остъпката е в процент то изчисляваме каква част
    			// от цената е тя
    			$lastDisc = round(($price->price * $dRec->discountPercent / 100), 2);
    			$procent = $dRec->discountPercent;
    		} else {
    			
    			// Ако отстъпката е сума, изчисляваме на колко процента е равна
    			$lastDisc = $dRec->discountSum;
    			$procent = round(($lastDisc * 100 / $price->price), 2);
    		}
    			// Връщаме цената с приложената по-голяма отстъпка
    			$finalDiscount = max($clientDiscount, $lastDisc);
    			$finalPrice->price = $price->price - $finalDiscount;
    			$finalPrice->discount = max($procent, $pDiscount);
    			
    			return $finalPrice;
    		}
    	 
    	// Ако няма ръчно зададена остъпка използваме тази която е
    	// зададена в ценоразписа
    	$finalPrice->price = $price->price - $clientDiscount;
    	$finalPrice->discount = $pDiscount;
    	
    	return $finalPrice;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setOptions('action', $mvc->getActionOptions());
    	$data->form->setDefault('quantity', '1');
    }
    
    
    /**
     * Подготвяме позволените операции
     * @return array $params - Списък с позволените действия
     */
    function getActionOptions()
    {
    	$params = array();
    	
    	$params[] = (object)array('title' => 'Продажба', 'group' =>TRUE);
    	$params['sale|code'] = 'Продукт';
    	$params['sale|barcod'] = 'Баркод';
    	$params[] = (object)array('title' => tr('Отстъпка'), 'group' => TRUE);
    	$params['discount|percent'] = 'Процент';
    	$params['discount|sum'] = 'Сума';
    	$params[] = (object)array('title' => tr('Плащане'), 'group' => TRUE);
    	$payments = pos_Payments::fetchSelected();
	    foreach($payments as $payment) {
	    	$params["payment|{$payment->id}"] = $payment->title;
	    }
    	$params[] = (object)array('title' => tr('Клиент'), 'group' => TRUE);
    	$params['client|ccard'] = tr('Кл. карта');
    	$params['client|table'] = tr('Маса');
    	$params['client|room'] = tr('Стая');
    	
    	return $params;
    }
	
	
	/**
	 * След като създадем елемент, ъпдейтваме Бележката
	 */
	static function on_AfterCreate($mvc, $rec)
    {
     	$mvc->Master->updateReceipt($rec);
    }
}