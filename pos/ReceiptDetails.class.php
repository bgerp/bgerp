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
    var $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    

    /**
     * Кой може да променя?
     */
    var $canDelete = 'pos, ceo';
    
    
    /**
     * Полета за листов изглед
     */
    var $listFields = 'productId,value,quantity,price,discountPercent,amount';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discountPercent';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
    	$this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em;top:1px;position:relative');
    	$this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
    	$this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, class=ean-text');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
    	$this->FLD('value', 'varchar(32)', 'caption=Мярка, input=hidden,smartCenter');
    	$this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка,input=none');
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderReceiptDetail($data)
    {
    	$tpl = new ET("");
    	$lastRow = Mode::get('lastAdded');
    	
    	if(!Mode::is('printing')){
    		$blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetail.shtml');
    	} else {
    		$blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetailPrint.shtml');
    	}
    	
    	$saleTpl = $blocksTpl->getBlock('sale');
    	$paymentTpl = $blocksTpl->getBlock('payment');
    	if($data->rows) {
	    	foreach($data->rows as $id => $row) {
	    		$row->id = $id;
	    		$action = $this->getAction($data->rows[$id]->action);
                $at = ${"{$action->type}Tpl"};
                if(is_object($at)) {
                    $rowTpl = clone(${"{$action->type}Tpl"});
                    $rowTpl->placeObject($row);
                    if($lastRow == $row->id) {
                        $rowTpl->replace("pos-hightligted", 'lastRow');
                    }
                    $rowTpl->removeBlocks();
                    $tpl->append($rowTpl);
                }
	    	}
    	} else {
    		$tpl->append(new ET("<tr><td colspan='3' class='receipt-sale'>" . tr('Няма записи') . "</td></tr>"));
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Добавя отстъпка на избран продукт
     */
    function act_setDiscount()
    {
    	$this->requireRightFor('add');
    	
    	if(!$recId = Request::get('recId', 'int')){
    		core_Statuses::newStatus('|Не е избран ред|*!', 'error');
    		return $this->returnError($recId);
    	}
    	
    	if(!$rec = $this->fetch($recId)){
    		return $this->returnError($recId);
    	}
    	
    	// Трябва да може да се редактира записа
    	$this->requireRightFor('add', $rec);
    	
    	$discount = Request::get('amount');
    	$this->getFieldType('discountPercent')->params['Max']=1;
    	$discount = $this->getFieldType('discountPercent')->fromVerbal($discount);
    	if(!isset($discount)){
    		core_Statuses::newStatus('|Не е въведено валидна процентна отстъпка|*!', 'error');
    		return $this->returnError($rec->receiptId);
    	}
    	
    	if($discount > 1){
    		core_Statuses::newStatus('|Отстъпката не може да е над|* 100%!', 'error');
    		return $this->returnError($rec->receiptId);
    	}
    	
    	// Записваме променената отстъпка
    	$rec->discountPercent = $discount;
    	
    	if($this->save($rec)){
    		
    		core_Statuses::newStatus('|Отстъпката е зададена успешно|*!');
    		
    		return $this->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus('|Проблем при задаване на отстъпка|*!', 'error');
    	}
    	
    	return $this->returnError($rec->receiptId);
    }
    
    
    /**
     * При грешка, ако е в Ajax режим, връща празен масив, иначе редиректва към бележката
     */
    public function returnError($id)
    {
    	if (Request::get('ajax_mode')) {
    		$hitTime = Request::get('hitTime', 'int');
    		$idleTime = Request::get('idleTime', 'int');
    		$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
    		
    		// Връщаме статусите ако има
    		return (array)$statusData;
    	} else {
    		if(!$id) redirect(array('pos_Receipts', 'list'));
    		
    		redirect(array('pos_Receipts', 'terminal', $id));
    	}
    }
    
    
    /**
     * Връщане на отговор, при успех
     */
    public function returnResponse($receiptId)
    {
    	// Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
        	$receiptTpl = $this->Master->getReceipt($receiptId);
        	$toolsTpl = $this->Master->renderToolsTab($receiptId);
		    $paymentTpl = $this->Master->renderPaymentTab($receiptId);
		    	
		    // Ще реплейснем само бележката
		    $resObj = new stdClass();
			$resObj->func = "html";
			$resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => TRUE);
			
			// Ще реплесйнем и таба за плащанията
			$resObj1 = new stdClass();
			$resObj1->func = "html";
			$resObj1->arg = array('id' => 'tools-payment', 'html' => $paymentTpl->getContent(), 'replace' => TRUE);
        	
			// Ще реплесйнем и пулта
			$resObj2 = new stdClass();
			$resObj2->func = "html";
			$resObj2->arg = array('id' => 'tools-form', 'html' => $toolsTpl->getContent(), 'replace' => TRUE);
			
			// Ще реплесйнем и таба за плащанията
			$resObj3 = new stdClass();
			$resObj3->func = "html";
			$resObj3->arg = array('id' => 'result_contragents', 'html' => ' ', 'replace' => TRUE);
			
			// Показваме веднага и чакащите статуси
			$hitTime = Request::get('hitTime', 'int');
			$idleTime = Request::get('idleTime', 'int');
			$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        	
			$res = array_merge(array($resObj, $resObj1, $resObj2, $resObj3), (array)$statusData);
			
			return $res;
        } else {
        	
        	// Ако не сме в Ajax режим пренасочваме към терминала
        	redirect(array($this->Master, 'Terminal', $receiptId));
        }
    }
    
    
    /**
     * Промяна на количество на избран продукт
     */
    function act_setQuantity()
    {
    	$this->requireRightFor('add');
    	
    	// Трябва да има избран ред
    	if(!$recId = Request::get('recId', 'int')){
    		core_Statuses::newStatus('|Не е избран ред|*!', 'error');
    		return $this->returnError($rec->receiptId);
    	}
    	
    	// Трябва да има такъв запис
    	if(!$rec = $this->fetch($recId)) return $this->returnError($rec->receiptId);
    	
    	// Трябва да може да се редактира записа
    	$this->requireRightFor('add', $rec);
    	
    	$quantityId = Request::get('amount');
    	
    	// Трябва да е подадено валидно количество
    	$quantityId = $this->getFieldType('quantity')->fromVerbal($quantityId);
    	
    	if($quantityId === FALSE){
    		core_Statuses::newStatus('|Въведеното количество не е валидно|*!', 'error');
    		return $this->returnError($rec->receiptId);
    	}
    	
    	// Ако е въведено '0' за количество изтриваме реда
    	if($quantityId === (double)0){
    		$this->delete($recId);
    		core_Statuses::newStatus('|Артикулът е изтрит успешно|*!');
    		
    		return $this->returnResponse($rec->receiptId);
    	}
    	
    	// Преизчисляваме сумата
    	$rec->quantity = $quantityId;
    	$rec->amount = $rec->price * $rec->quantity;
    	
    	// Запис на новото количество
    	if($this->save($rec)){
    		
    		core_Statuses::newStatus('|Количеството е променено успешно|*!');
    		
    		return $this->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus('|Проблем при редакция на количество|*!', 'error');
    	}
    	
    	return $this->returnError($rec->receiptId);
    }
    
    
    /**
     * Добавяне на плащане към бележка
     */
    function act_makePayment()
    {
    	$this->requireRightFor('add');
    	
    	// Трябва да е избрана бележка
    	if(!$recId = Request::get('receiptId', 'int')) return $this->returnError($recId);
    	
    	// Можем ли да направим плащане към бележката
    	$this->Master->requireRightFor('pay', $recId);
    	
    	// Трябва да има избран запис на бележка
    	if(!$receipt = $this->Master->fetch($recId)) return $this->returnError($recId);
    	
    	// Трябва да е подаден валидно ид на начин на плащане
    	$type = Request::get('type', 'int');
    	
    	if(!cond_Payments::fetch($type) && $type != -1)  return $this->returnError($recId);
    	
    	// Трябва да е подадена валидна сума
    	$amount = Request::get('amount');
    	$amount = $this->getFieldType('amount')->fromVerbal($amount);
    	if(!$amount || $amount <= 0){
    		core_Statuses::newStatus('|Сумата трябва да е положителна|*!', 'error');
	    	return $this->returnError($recId);
    	}
    	
    	$diff = abs($receipt->paid - $receipt->total);
    	
    	if($type != -1){
    		// Ако платежния метод не поддържа ресто, не може да се плати по-голяма сума
    		if(!cond_Payments::returnsChange($type) && (string)$amount > (string)$diff){
    			core_Statuses::newStatus('|Платежния метод не позволява да се плати по-голяма сума от общата|*!', 'error');
    			return $this->returnError($recId);
    		}
    	}
    	
    	// Подготвяме записа на плащането
    	$rec = new stdClass();
    	$rec->receiptId = $recId;
    	$rec->action = "payment|{$type}";
    	$rec->amount = $amount;
    	
    	// Отбелязваме, че на това плащане ще има ресто
    	$paid = $receipt->paid + $amount;
    	if(($paid) > $receipt->total){
    		$rec->value = 'change';
    	}
    	
    	// Запис на плащанетo
    	if($this->save($rec)){
    		core_Statuses::newStatus('|Плащането е направено успешно|*!');
    		
    		return $this->returnResponse($recId);
    	} else {
    		core_Statuses::newStatus('|Проблем при плащането|*!', 'error');
    	}
    	
    	return $this->returnError($recId);
    }
    
    
    /**
     * Изтриване на запис от бележката
     */
    function act_DeleteRec()
    {
    	$this->requireRightFor('delete');
    	
    	// Трябва да има ид на ред за изтриване
    	if(!$id = Request::get('recId', 'int')) return $this->returnError($receiptId);
    	
    	// Трябва да има такъв запис
    	if(!$rec = $this->fetch($id)) return $this->returnError($receiptId);
    	
    	// Трябва да можем да изтриваме от бележката
    	$this->requireRightFor('delete', $rec);
    	
    	$receiptId = $rec->receiptId;
    	
    	if($this->delete($rec->id)){
    		core_Statuses::newStatus('|Успешно изтриване|*!');
    		
    		// Ъпдейт на бележката след изтриването
    		$this->Master->updateReceipt($receiptId);
    		
    		return $this->returnResponse($receiptId);
    	} else {
    		core_Statuses::newStatus('|Проблем при изтриването на ред|*!', 'error');
    	}
    	
    	return $this->returnError($receiptId);
    }
    
    
    /**
     * Подготвя детайла на бележката
     */
    public function prepareReceiptDetails($receiptId)
    {
    	$res = new stdClass();
    	$query = $this->getQuery();
    	$query->where("#receiptId = '{$receiptId}'");
    	while($rec = $query->fetch()){
    		$res->recs[$rec->id] = $rec;
    		$res->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$varchar = cls::get('type_Varchar');
    	$Double = cls::get('type_Double');
    	$Double->params['smartRound'] = TRUE;
    	$receiptDate = $mvc->Master->fetchField($rec->receiptId, 'createdOn');
    	$row->currency = acc_Periods::getBaseCurrencyCode($receiptDate);
    	
    	$action = $mvc->getAction($rec->action);
    	switch($action->type) {
    		case "sale":
    			$mvc->renderSale($rec, $row, $receiptDate, $fields);
    			if($fields['-list']){
    				$row->quantity = ($rec->value) ? $row->quantity : $row->quantity;
    			}
    			break;
    		case "payment":
    			$row->actionValue = ($action->value != -1) ? cond_Payments::getTitleById($action->value) : tr("В брой");
    			
    			if($fields['-list']){
    				$row->productId = tr('Плащане') . ": " . $row->actionValue;
    				unset($row->quantity,$row->value);
    			}
    			break;
    	}
    	
    	// Ако може да изтриваме ред и не сме в режим принтиране
    	if($mvc->haveRightFor('delete', $rec) && !Mode::is('printing')){
    		$delUrl = toUrl(array($mvc->className, 'deleteRec'), 'local');
    		$row->DEL_BTN = ht::createElement('img', array('src' => sbf('img/16/deletered.png', ''), 
    													   'class' => 'pos-del-btn', 'data-recId' => $rec->id, 
    													   'title' => tr('Изтриване на реда'),
    													   'data-warning' => tr('|Наистина ли искате да изтриете записа|*?'), 
    													   'data-url' => $delUrl));
    	}
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    function renderSale($rec, &$row, $receiptDate, $fields = array())
    {
    	$Varchar = cls::get('type_Varchar');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$productInfo = cat_Products::getProductInfo($rec->productId);
    	$perPack = ($productInfo->packagings[$rec->value]) ? $productInfo->packagings[$rec->value]->quantity : 1;
    	
    	$rec->price = $rec->price * (1 + $rec->param) * (1 - $rec->discountPercent);
    	$rec->price = round($rec->price, 2);
    	$row->price = $Double->toVerbal($rec->price);
    	$row->amount = $Double->toVerbal($rec->price * $rec->quantity);
    	if($rec->discountPercent < 0){
    		$row->discountPercent = "+" . trim($row->discountPercent, '-');
    	}
    	
    	$row->code = $Varchar->toVerbal($productInfo->productRec->code);
    	if($productInfo->productRec->measureId){
    		$row->uomId = cat_UoM::getShortName($productInfo->productRec->measureId);
    	}
    	
    	$row->perPack = $Double->toVerbal($perPack);
    	
    	if($rec->value) {
    		$row->value = cat_UoM::getTitleById($rec->value);
    	} else {
    		if($fields['-list']){
    			$row->value = cat_UoM::getTitleById($productInfo->productRec->measureId);
    		} else {
    			$row->value = $row->uomId;
    		}
    		
    		unset($row->uomId);
    	}
    	
    	// Ако отстъпката е нула да не се показва
    	if($rec->discountPercent == 0){
    		unset($row->discountPercent);
    	}
    	
    	if($fields['-list']){
    		$row->value .= " <small class='quiet'>" . $row->perPack  . $row->uomId .  "</span>";
    		$row->productId = cat_Products::getHyperLink($rec->productId, TRUE);
    	} else {
    		$row->productId = cat_Products::getTitleById($rec->productId, TRUE);
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
    	$allowed = array('sale', 'discount', 'payment');
    	expect(in_array($actionArr[0], $allowed), 'Не е позволена такава операция');
    	expect(count($actionArr) == 2, 'Стрингът не е в правилен формат');
    	
    	$action = new stdClass();
    	$action->type = $actionArr[0];
    	$action->value = $actionArr[1];
    	
    	return $action;
    }
    
    
    /**
     * Намира продукта по подаден номер и изчислява неговата цена
     * и отстъпка спрямо клиента, и ценоразписа
     * @param stdClass $rec
     */
    public function getProductInfo(&$rec)
    {
    	if($rec->productId){
    		expect($productId = cat_Products::fetch($rec->productId));
    		$product = (object)array('productId' => $rec->productId);
    	} elseif($rec->ean){
    		$product = cat_Products::getByCode($rec->ean);
    	}
    	
    	if(!$product) return $rec->productid = NULL;
    	
    	$info = cat_Products::getProductInfo($product->productId);
    	if(empty($info->meta['canSell'])){
    		
    		return $rec->productid = NULL;
    	}
    	
    	if(!$product->packagingId){
    		if(isset($rec->value)){
    			$basePackId = $rec->value;
    		} else {
    			$basePackId = key(cat_Products::getPacks($product->productId));
    		}
    	} else {
    		$basePackId = $product->packagingId;
    	}
    	
    	$perPack = ($info->packagings[$basePackId]) ? $info->packagings[$basePackId]->quantity : 1;
    	$rec->value = ($basePackId) ? $basePackId : $info->productRec->measureId;
    	
    	$rec->productId = $product->productId;
    	$receiptRec = pos_Receipts::fetch($rec->receiptId);
    	$listId = pos_Points::fetchField($receiptRec->pointId, 'policyId');
    	
    	$Policy = cls::get('price_ListToCustomers');
    	$price = $Policy->getPriceInfo($receiptRec->contragentClass, $receiptRec->contragentObjectId, $product->productId, $rec->value, 1, $receiptRec->createdOn, 1, 'no', $listId);
    	
    	$rec->price = $price->price * $perPack;
    	$rec->param = cat_Products::getVat($rec->productId, $receiptRec->valior);
    	$rec->amount = $rec->price * $rec->quantity;
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
    	} else {
    		$query->where("#value IS NULL");
    	}
    	
    	$query->orderBy('#id', 'DESC');
    	$query->limit(1);
    	if($rec = $query->fetch()){
    		
    		return $rec;
    	} 
    	
    	return FALSE;
    }
    
    
	/**
	 * След като създадем елемент, ъпдейтваме Бележката
	 */
	static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
     	Mode::setPermanent('lastAdded', $id);
    	$mvc->Master->updateReceipt($rec->receiptId);
    }
    
    
	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if(($action == 'add' || $action == 'delete') && isset($rec->receiptId)) {
			$masterRec = $mvc->Master->fetch($rec->receiptId);
			
			if($masterRec->state != 'draft') {
				$res = 'no_one';
			} else {
				
				// Ако редактираме/добавяме/изтриваме ред с продукт, проверяваме имали направено плащане
				if(!($action == 'delete' && !$rec->productId)){
					if($masterRec->paid){
						$res = 'no_one';
					}
				}
			}
		}
	}
	
	
    /**
     * Използва се от репортите за извличане на данни за продажбата
     * 
     * @param int $receiptId - ид на бележка
     * @return array $result - масив от всички плащания и продажби на бележката;
     */
    static function fetchReportData($receiptId)
    {
    	expect($masterRec = pos_Receipts::fetch($receiptId));
    	$storeId = pos_Points::fetchField($masterRec->pointId, 'storeId');
    	$caseId = pos_Points::fetchField($masterRec->pointId, 'caseId');
    	
    	$result = array();
    	$query = static::getQuery();
    	$query->EXT('contragentClsId', 'pos_Receipts', 'externalName=contragentClass,externalKey=receiptId');
    	$query->EXT('contragentId', 'pos_Receipts', 'externalName=contragentObjectId,externalKey=receiptId');
    	$query->where("#receiptId = {$receiptId}");
    	$query->where("#action LIKE '%sale%' || #action LIKE '%payment%'");
    	
    	while($rec = $query->fetch()) {
    		$arr = array();
    		$obj = new stdClass();
    		if($rec->productId) {
    			$obj->action  = 'sale';
    			$obj->pack    = ($rec->value) ?  $rec->value : NULL;
    			$pInfo = cat_Products::getProductInfo($rec->productId);
    			$obj->quantityInPack = ($pInfo->packagings[$obj->pack]) ? $pInfo->packagings[$obj->pack]->quantity : 1;
    			
    			$obj->value   = $rec->productId;
    			$obj->storeId = $storeId;
    			$obj->param   = $rec->param;
    		} else {
    			if(!$rec->amount) continue;
    			if($rec->value == 'change'){
    				$rec->amount -= $masterRec->change;
    			}
    			
    			$obj->action = 'payment';
    			list(, $obj->value) = explode('|', $rec->action);
    			$obj->pack = NULL;
    			$obj->caseId = $caseId;
    		}
    		$obj->contragentClassId = $rec->contragentClsId;
    		$obj->contragentId      = $rec->contragentId;
    		$obj->quantity          = $rec->quantity;
    		$obj->amount            = ($rec->amount) * (1 - $rec->discountPercent);
    		$obj->date              = $masterRec->createdOn;
    		
    		$result[] = $obj;
    	}
    	
    	return $result;
    }
}
