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
     * 
     */
    function prepareAddForm(&$data)
    {
    	if($this->haveRightFor('add')) {
    		$url = getCurrentUrl();
	    	$form = static::getForm();
	    	$form->layout= new ET(getFileContent("pos/tpl/DetailsForm.shtml"));
	    	$form->fieldsLayout= $this->createFormFieldsLayout();
	    	$form->action = array($this, 'save', 'ret_url' => TRUE);
	    	$form->setDefault('receiptId', $data->masterId);
	    	$data->form = $form;
	    	$this->invoke('AfterPrepareEditForm', array($data));
	    	
	    }
    }
    
    
    /**
     * 
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
	    			$mvc->getProductInfo($rec);
	    			break;
	    		case 'payment':
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Не сте въвели валидно число');
	    			}
	    			$rec->amount = $rec->ean;
	    			break;
	    		case 'discount':
	    			$param = ucfirst(strtolower($action[1]));
	    			$rec->{"discount{$param}"} = (double)$rec->ean;
	    			break;
	    		case 'client':
	    			$mvc->getClientInfo($rec);
	    			break;
	    	}
	    }
    }
    
    
    /**
     * @TODO
     * @param stdClass $rec
     */
    function getClientInfo(&$rec)
    {
    	$rec->param = $rec->ean;	
    }
    
    
    /**
     * @TODO
     * @param stdClass $rec
     */
    function getProductInfo(&$rec)
    {
    	expect($rec->productId = cat_Products::fetchField(array("#code='[#1#]'", $rec->ean), 'id'), 'Няма продукт с такъв код');
    				$priceCls = cls::get('cat_PricePolicyMockup');
    				$receiptRec = pos_Receipts::fetch($rec->receiptId);
    				if($receiptRec->contragentClass) {
    					$price = $priceCls->getPriceInfo($receiptRec->contragentClass,
    													 $receiptRec->contragentObjectId, 
    													 $rec->productId,
    													 NULL, $rec->quantity, $receiptRec->date
    													 );
    				} else {
    					$price = $priceCls->getPriceInfo(NULL, NULL, $rec->productId);
    				}
    				
    	$rec->price = $this->applyDiscount($price->price, $rec->receiptId);
    	$rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * @TODO
     */
    function applyDiscount($price, $receiptId)
    {
    	
    	$query = $this->getQuery();
    	$query->where("#receiptId = {$receiptId}");
    	$query->where("#action LIKE '%discount%'");
    	$query->orderBy("#id", "DESC");
    	if($dRec = $query->fetch()) {
    		$action = explode("|", $dRec->action);
    		if($action[1] == 'percent') {
    			$disc = round(($price * $dRec->discountPercent / 100), 2);
    		} else {
    			$disc = $dRec->discountSum;
    		}
    		
    		return ($price - $disc);
    		}
    	 
    	return $price;
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
     */
    function getActionOptions()
    {
    	$params = array();
    	
    	$params[] = (object)array('title' => 'Продажба', 'group' =>TRUE);
    	$params['sale|code'] = 'Продукт';
    	$params['sale|barcod'] = 'Баркод';
    	$params[] = (object)array('title' => tr('Намаление'), 'group' => TRUE);
    	$params['discount|percent'] = 'Процент';
    	$params['discount|sum'] = 'Сума';
    	$params[] = (object)array('title' => tr('Плащане'), 'group' => TRUE);
    	$patyments = pos_Payments::fetchSelected();
	    foreach($patyments as $payment) {
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