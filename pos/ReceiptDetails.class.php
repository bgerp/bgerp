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
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'receiptId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    

  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
    	$this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em');
    	$this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
    	$this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, class=ean-text');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'int', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
    	$this->FLD('value', 'varchar(32)', 'caption=Стойност, input=hidden');
    	$this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка->Процент,input=none');
        $this->FLD('discountSum', 'double(decimals=2)', 'caption=Отстъпка->Сума,input=none');
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
    	$rRec = (object) array('receiptId' => $data->masterId); 
    	if($this->haveRightFor('add', $rRec)) {
	    	$form = $this->getForm();
	    	$form->method = 'POST';
	    	$form->layout = getTplFromFile("pos/tpl/DetailsForm.shtml");
	    	$form->action = array($this->Master, 'single', $data->masterId, '#'=>'form');
	    	$form->fieldsLayout = $this->createFormFieldsLayout($data);
	    	$form->setField('id', 'input=none');
	    	$form->setDefault('receiptId', $data->masterId);
	    	$data->form = $form;
	    	$this->invoke('AfterPrepareEditForm', array($data));
	    }
    }
    
    
    /**
     * Подготвя лейаута на полетата на форма
     * та и добавя допълнителни бутони
     * @return core_ET $tpl
     */
    function createFormFieldsLayout($data)
    {
    	$tpl = getTplFromFile("pos/tpl/DetailsFormFields.shtml");
    	$tpl->append(ht::createSbBtn('Запис', 'default', NULL, NULL, array('class' => 'buttonForm')), 'FIRST_ROW');
    	$tpl->append(ht::createFnBtn('+1', '','', array('id' => 'incBtn','class' => 'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('-1', '','', array('id' => 'decBtn','class' => 'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('Баркод', "window.WebScan.scanThenLoadURL('[SCANVALUE]')", '', array('class' => 'webscan')), 'THIRD_ROW');
	    $tpl->append(ht::createFnBtn('Кл. Карта', '','', array('class' => 'actionBtn', 'data-type' => 'client|ccard')), 'THIRD_ROW');
	    $payments = pos_Payments::fetchSelected();
	    $cPayments = count($payments);
	    foreach($payments as $payment) {
	    	$attr = array('class' => 'actionBtn', 'data-type' => "payment|" . $payment->id);
	    	$tpl->append(ht::createFnBtn($payment->title, '', '', $attr), 'SECOND_ROW');
	    }
	    
        // Търсим бутон "Контиране" в тулбара на мастъра, добавен от acc_plg_Contable
	    if (!empty($data->masterData->toolbar->buttons['btnConto'])) {
	    	$contoUrl = $data->masterData->toolbar->buttons['btnConto']->url;
	        $contoUrl = array('ret_url' => array($this->Master, 'new')) + $contoUrl;
	        
	        if($client = $this->hasClient($data->masterData->rec->id)){
	        	$confInvUrl = $contoUrl;
	        	$contragentClass = $client->class;
    			$contragentRec = $contragentClass::fetch($client->id);
	        	$invArray = array('sales_Invoices', 'add',
    					 'folderId' => $contragentRec->folderId, 
    					 'docType' => pos_Receipts::getClassId(), 
    					 'docId' => $data->masterData->rec->id);
	        	$confInvUrl = array('ret_url' => $invArray) + $confInvUrl;
	        }
	        
	        // Скриваме бутона "Контиране"
	        unset($data->masterData->toolbar->buttons['btnConto']);
	    }
	    
	    $tpl->append(ht::createBtn('Приключи', $contoUrl, '', '', array('class' => 'actionBtn btnEnd', 'title' => 'приключи продажбата')), 'FIRST_ROW');
	    $tpl->append(ht::createBtn('Фактурирай', $confInvUrl, '', '', array('class' => 'actionBtn btnEnd', 'title' => 'приключи и издай фактура')), 'SECOND_ROW');
	   
		return $tpl;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	$lastRow = Mode::get('lastAdded');
    	$blocksTpl = getTplFromFile('pos/tpl/ReceiptDetail.shtml');
    	$saleTpl = $blocksTpl->getBlock('sale');
    	$discountTpl = $blocksTpl->getBlock('discount');
    	$paymentTpl = $blocksTpl->getBlock('payment');
    	$clientTpl = $blocksTpl->getBlock('client');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$action = $this->getAction($data->rows[$row->id]->action);
	    		$rowTpl = clone(${"{$action->type}Tpl"});
	    		$rowTpl->placeObject($row);
	    		if($lastRow == $row->id) {
	    			$rowTpl->replace("id='last-row'", 'lastRow');
	    			unset($lastRow);
	    			Mode::setPermanent('lastAdded', NULL);
	    		}
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	} else {
    		$tpl->append(new ET("<tr><td colspan='3' class='receipt-sale'>" . tr('Няма записи') . "</td></tr>"));
    	}
    	
    	if($data->form) {
    		$tpl->append($data->form->renderHtml(), 'ADD_FORM');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$varchar = cls::get('type_Varchar');
    	$receiptDate = $mvc->Master->fetchField($rec->receiptId, 'createdOn');
    	$row->currency = acc_Periods::getBaseCurrencyCode($receiptDate);
    	$action = $mvc->getAction($rec->action);
    	switch($action->type) {
    		case "sale":
    			$mvc->renderSale($rec, $row, $receiptDate);
    			break;
    		case "payment":
    			$row->actionValue = pos_Payments::getTitleById($action->value);
    			break;
    		case "client":
    			$clientArr = explode("|", $rec->param);
    			$row->clientName = $clientArr[1]::getTitleById($clientArr[0]);
    			break;
    		case 'discount':
    			if($rec->discountPercent || $rec->discountPercent == 0){
    				$discRec = $rec->discountPercent;
    				$discRow = $row->discountPercent;
    				unset($row->currency);
    				if($discRec == 0){
    					$row->discountPercent = tr('Без отстъпка');
    				}
    			}else {
    				$discRec = $rec->discountSum;
    				$discRow = $row->discountSum;
    			}
    			
    			if($discRec != 0){
	    			$discRow = abs($discRec);
	    			($discRec < 0) ? $row->discountType = tr("Надценка") : $row->discountType = tr("Отстъпка");
    			}
    			break;
    	}
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    function renderSale($rec, &$row, $receiptDate)
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$percent = cls::get('type_Percent');
    	$percent->params['decimals'] = $double->params['decimals'] = 2;
    	
    	$productInfo = cat_Products::getProductInfo($rec->productId, $rec->value);
    	
    	$vat = cat_Products::getVat($rec->productId, $receiptDate);
    	$row->price = $double->toVerbal($rec->price + ($rec->price * $vat));
    	$row->amount = $double->toVerbal($rec->amount + ($rec->amount * $vat));
    	
    	$row->productId = $varchar->toVerbal($productInfo->productRec->name);
    	$row->code = $varchar->toVerbal($productInfo->productRec->code);
    	$row->uomId = cat_UoM::getShortName($productInfo->productRec->measureId);
    	
    	$row->perPack = $double->toVerbal($productInfo->packagingRec->quantity);
    	if($rec->value) {
    		$row->packagingId = cat_Packagings::getTitleById($rec->value);
    	} else {
    		$row->packagingId = $row->uomId;
    		unset($row->uomId);
    	}
    	
    	if($rec->discountPercent){
    		$rec->discountPercent = $rec->discountPercent * -1;
    		$row->discountPercent = $percent->toVerbal($rec->discountPercent);
    		if($rec->discountPercent > 0) {
    			$row->discountPercent = "+" . $row->discountPercent;
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$rec->ean = trim($rec->ean);
    		
    		if(strlen($rec->ean) == 0) {
    			$form->setError('ean', 'Имате празно поле');
    			return;
    		}
    		
    		if($rec->quantity == 0) {
	    		$form->setError('quantity', 'Неможе да въведете нулево количество');
	    		return;
	    	}
	    	
    		$action = $mvc->getAction($rec->action);
	    	switch($action->type) {
	    		case 'sale':
	    			$mvc->getProductInfo($rec);
	    			if(!$rec->productId) {
	    				$form->setError('ean', 'Няма такъв продукт в системата');
	    				return;
	    			}
	    			
	    			if(!$rec->price) {
	    				$form->setError('ean', 'Продукта няма цена в системата');
	    				return;
	    			}
	    			
				    // Намираме дали този проект го има въведен 
				    $sameProduct = $mvc->findSale($rec->productId, $rec->receiptId, $rec->value);
					if((string)$sameProduct->price == (string)$rec->price) {
				    				
				    		// Ако цената и опаковката му е същата като на текущия продукт,
				    		// не добавяме нов запис а ъпдейтваме стария
				    		$newQuantity = $rec->quantity + $sameProduct->quantity;
				    		$rec->quantity = $newQuantity;
				    		$rec->amount += $sameProduct->amount;
				    		$rec->id = $sameProduct->id;
				    }
				    
				    if($rec->quantity < 0) {
				    	
				    	// Количеството на оставащия продукт не бива да е под 0
				    	$form->setError('quantity', 'Въвели сте неправилно количество');
				    }
				    
	    			if($rec->price < 0) {
				    	
				    	// Небива да се записвая продукт с отрицателна цена (след приложена отстъпка)
				    	$form->setError('ean', 'Не може продукта да е с отрицателна цена !');
				    }
	    			break;
	    		case 'payment':
	    			
	    			// Ако действието е "плащане"
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Полето приема само цифри');
	    				return;
	    			}
	    			
	    			if($rec->ean <= 0) {
	    				$form->setError('ean', 'Не може да се плати с неположителна стойност');
	    				return;
	    			}
	    			$recRec = $mvc->Master->fetch($rec->receiptId);
	    			if(!pos_Payments::returnsChange($action->value)
	    			 && (string)$rec->ean > (string)abs($recRec->paid - $recRec->total)) {
	    			 	$form->setError('ean', 'Неможе с този платежен метод да се плати по-голяма сума от общата');
	    			}
	    			$rec->amount = $rec->ean;
	    			break;
	    		case 'discount':
	    			
	    			// Ако действието е "отстъпка"
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Полето приема само цифри');
	    				return;
	    			}
	    			$param = ucfirst(strtolower($action->value));
	    			$rec->{"discount{$param}"} = $rec->ean/100;
	    			if($param == 'Sum'){
	    				$total = $mvc->Master->fetchField($rec->receiptId, 'total');
	    				if($total < abs($rec->ean)){
	    					$form->setError('ean', 'Въведената сума е по-голяма от крайната !');
	    				}
	    			} else {
	    				if($rec->ean/100 > 1) {
	    					$form->setError('ean', 'Отстъпката неможе да е по-голяма от 100% !');
	    				}
	    			}
	    			
	    			break;
	    		case 'client':
	    			if(!is_numeric($rec->ean)) {
	    				$form->setError('ean', 'Полето приема само цифри');
	    				return;
	    			}
	    			
	    			// Ако действието е "клиент"
	    			$mvc->getClientInfo($rec);
	    			if(!$rec->param) {
	    				$form->setError('ean', 'Няма такъв клиент');
	    			}
	    			break;
	    	}
	    }
    }
    
    
    /**
     * Метод връщаш обект с информация за избраното действие
     * и неговата стойност
     * @param string $string - стринг където от вида "action|value"
     * @return stdClass $action - обект съдържащ ид и стойноста извлечени
     * от стринга
     */
    function getAction($string)
    {
    	$actionArr = explode("|", $string);
    	$allowed = array('sale', 'discount', 'client', 'payment');
    	expect(in_array($actionArr[0], $allowed), 'Не е позволена такава операция');
    	expect(count($actionArr) == 2, 'Стрингът не е в правилен формат');
    	
    	$action = new stdClass();
    	$action->type = $actionArr[0];
    	$action->value = $actionArr[1];
    	
    	return $action;
    }
    
    
    /**
     * Изчлича информацията за клиента, по зададен параметър
     * записва информацията за клиента във вида на стринг:
     * ид на клиента и неговия клас разделени с "|"
     * @param stdClass $rec
     */
    function getClientInfo(&$rec)
    {
    	//@TODO Функцията е прототипна
    	$action = static::getAction($rec->action);
    	
    	if($action->value == 'ccard') {
    		
    			// временно връща името на клиента, по подадено негово Id
	    		if($rec->param = crm_Persons::fetchField(array("#id = [#1#]", $rec->ean), 'id')){
	    			$rec->param .= "|crm_Persons";
	    		} else {
	    			return NULL;
	    	} 
	    }	
    }
    
    
    /**
     * Намира продукта по подаден номер и изчислява неговата цена
     * и отстъпка спрямо клиента, и ценоразписа
     * @param stdClass $rec
     */
    function getProductInfo(&$rec)
    {
    	if(!$product = cat_Products::getByCode($rec->ean)) {
    		return $rec->productid = NULL;
    	}
    	
    	$info = cat_Products::getProductInfo($product->productId, $product->packagingId);
    	if($info->packagingRec){
    		$rec->value = $info->packagingRec->packagingId;
    		$perPack = $info->packagingRec->quantity;
    	} else {
    		$perPack = 1;
    	}
    	$rec->productId = $product->productId;
    	$receiptRec = pos_Receipts::fetch($rec->receiptId);
    	$policyId = pos_Points::fetchField($receiptRec->pointId, 'policyId');
    	$price = new stdClass();
    	$price->price = price_ListRules::getPrice($policyId, $product->productId, $product->packagingId, $receiptRec->createdOn);
    	
    	$price = $this->applyDiscount($price, $rec->receiptId);
    	$rec->price = $price->price;
    	if($price->discount != 0.00) {
    		$rec->discountPercent = $price->discount;
    	}
    	
    	$rec->param = cat_Products::getVat($rec->productId, $receiptRec->createdOn);
    	$rec->amount = $rec->price * $rec->quantity * $perPack;	
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
    	$query->where("#action LIKE '%discount|percent%'");
    	$query->orderBy("#id", "DESC");
    	if($dRec = $query->fetch()) {
    		
    		$lastDisc = round(($price->price * $dRec->discountPercent), 2);
    		$procent = $dRec->discountPercent;
    		
    		if($lastDisc > 0){
    			$finalDiscount = max($clientDiscount, $lastDisc);
    			$finalPrice->discount = max($procent, $pDiscount);
    		} else {
    			$finalDiscount = $lastDisc;
    			$finalPrice->discount = $procent;
    		}
    			
    		// Връщаме цената с приложената по-голяма отстъпка
    		$finalPrice->price = $price->price - $finalDiscount;
    		
    		return $finalPrice;
    	}
    	 
    	// Ако няма ръчно зададена остъпка използваме тази която е
    	// зададена в ценоразписа
    	$finalPrice->price = $price->price - $clientDiscount;
    	$finalPrice->discount = $pDiscount;
    	
    	return $finalPrice;
    }
    
    
	/**
     *  Намира последната продажба на даден продукт в текущата бележка
     *  @param int $productId - ид на продукта
     *  @param int $receiptId - ид на бележката
     *  @param int $packId - ид на опаковката
     *  @return mixed $rec/FALSE - Последния запис или FALSE ако няма
     */
    function findSale($productId, $receiptId, $packId)
    {
    	$query = $this->getQuery();
    	$query->where(array("#productId = [#1#]", $productId));
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	if($packId) {
    		$query->where(array("#value = [#1#]", $packId));
    	}
    	$query->orderBy('#id', 'DESC');
    	$query->limit(1);
    	if($rec = $query->fetch()){
    		
    		return $rec;
    	} 
    	
    	return FALSE;
    }
    
    
    /**
     * Определяме кой е клиента на бележката
     * @param int $receiptId - id на бележка
     * @return mixed $rec - запис на клиента, FALSE ако няма
     */
    public function hasClient($receiptId)
    {
    	$query = $this->getQuery();
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	$query->where("#action = 'client|ccard'");
    	$query->orderBy("#id", "DESC");
    	
    	$rec = $query->fetch();
    	if(!$rec) return FALSE;
    	
    	$res = new stdClass();
    	list($res->id, $res->class) = explode('|', $rec->param);
    	
    	return $res;
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
     * @return array $params - Масив от позволените действия
     */
    function getActionOptions()
    {
    	$params = array();
    	$params[] = (object)array('title' => tr('Продажба'), 'group' => TRUE);
    	$params['sale|code'] = tr('Продукт');
    	$params[] = (object)array('title' => tr('Отстъпка'), 'group' => TRUE);
    	$params['discount|percent'] = tr('Процент');
    	$params['discount|sum'] = tr('Сума');
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
	static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
     	Mode::setPermanent('lastAdded', $id);
    	$mvc->Master->updateReceipt($rec);
    }
    
    
	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'add' && isset($rec->receiptId)) {
			$materRec = $mvc->Master->fetch($rec->receiptId);
			
			if($materRec->state == 'draft') {
				$res = 'pos, ceo';
			}
		}
	}

	
	/**
     * Не показваме продажбите с количество "0"
     */
    static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
    	if($data->recs) {
    		foreach($data->recs as $rec) {
    			if($rec->quantity == 0) {
    				unset($data->recs[$rec->id]);
    			}
    		}
    	}
    }
    
    
    /**
     * @param int $receiptId - ид на бележка
     * @return array $result - масив от всички
     * плащания и продажби на бележката;
     */
    static function fetchReportData($receiptId)
    {
    	expect($masterRec = pos_Receipts::fetch($receiptId));
    	$result = array();
    	$query = static::getQuery();
    	$query->where("#receiptId = {$receiptId}");
    	$query->where("#action LIKE '%sale%' || #action LIKE '%payment%'");
    	while($rec = $query->fetch()) {
    		$arr = array();
    		if($rec->productId) {
    			$arr['action'] = 'sale';
    			$arr['value'] = $rec->productId;
    			($rec->value) ? $arr['pack'] = $rec->value : $arr['pack'] = 0;
    		} else {
    			$arr['action'] = 'payment';
    			list(, $arr['value']) = explode('|', $rec->action);
    			$arr['pack'] = 0;
    		}
    		$index = implode('|', $arr);
    		$obj = new stdClass();
    		$obj->action = $arr['action'];
    		$obj->quantity = $rec->quantity;
    		$obj->amount = $rec->amount + ($rec->amount * $rec->param);
    		$obj->date = $masterRec->createdOn;
    		$result[$index] = $obj;
    	}
    	
    	return $result;
    }
}