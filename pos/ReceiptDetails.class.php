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
    //var $listFields = 'tools=Пулт, surveyId, label, image';
    
    
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
    	$this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em');
    	$this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, width=14em');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
    	$this->FLD('price', 'float(minDecimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'int', 'caption=К-во,placeholder=К-во,width=3em');
    	$this->FLD('amount', 'float(minDecimals=2)', 'caption=Сума, input=none,input=none');
    	$this->FLD('value', 'varchar(32)', 'caption=Стойност, input=hidden');
    	$this->FLD('discountPercent', 'percent', 'caption=Отстъпка->Процент,input=none');
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
	    	$form->toolbar->addSbBtn('Запис');
	    	$this->invoke('AfterPrepareEditForm', array($form));
	    	$data->addForm = $form;
	    }
    }
    
    
    /**
     * 
     */
    function createFormFieldsLayout()
    {
    	$tpl = new ET(getFileContent("pos/tpl/DetailsFormFields.shtml"));
    	$tpl->append(ht::createFnBtn('+1', '','', array('id'=>'incBtn','class'=>'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('-1', '','', array('id'=>'decBtn','class'=>'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('Баркод', '','', array('id'=>'barkod','class'=>'buttonForm')), 'FIRST_ROW');
	    $tpl->append(ht::createFnBtn('Маса', '','', array('class'=>'paymentBtn', 'data-type'=>'client')), 'THIRD_ROW');
	    $tpl->append(ht::createFnBtn('Стая', '','', array('class'=>'paymentBtn', 'data-type'=>'client')), 'THIRD_ROW');
	    $tpl->append(ht::createFnBtn('Кл. Карта', '','', array('class'=>'paymentBtn', 'data-type' =>'client')), 'THIRD_ROW');
    	
	    $paymentQuery = pos_Payments::getQuery();
	    $paymentQuery->where("#show = 'yes'");
	    while($pRec = $paymentQuery->fetch()) {
	    	$attr = array('class' => 'paymentBtn', 'value'=>$pRec, 'data-type'=>'payment');
	    	$tpl->append(ht::createFnBtn($pRec->title, '','', $attr), 'SECOND_ROW');
	    }
	    
	    return $tpl;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET(getFileContent('pos/tpl/ReceiptDetail.shtml'));
    	$tplAlt = $tpl->getBlock('ROW');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$rowTpl = clone($tplAlt);
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	}
    	
    	$tpl->append($data->addForm->renderHtml(), 'ADD_FORM');
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
    		$mvc->parseEan($form);
    	}
    }

    
    /**
     * @TODO
     */
    function parseEan(&$form)
    {
    	$rec = &$form->rec;
    	switch($rec->param) {
    			case 'sale':
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
    				$rec->price = $price->price;
    				$rec->amount = $rec->price * $rec->quantity;
    				break;
    			case 'payment':
    				if(!is_numeric($rec->ean)) {
    					$form->setError('ean', 'Не сте въвели валидно число');
    				}
    				
    				$query = $this->getQuery();
    				$query->where("#param = 'payment'");
    				$query->where("#receiptId = '{$rec->receiptId}'");
    				if($r = $query->fetch()) {
    					$rec->id = $r->id;
    				}
    				$rec->amount = $rec->ean;
    				break;
    			case 'discount':
    				break;
    			case 'client':
    				break;
    		}
    	
    }
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	if($data->form) {
    		$form = &$data->form;
    	} else {
    		$form = &$data;
    	}
    	
    	$params = array();
    	$params['sale'] = tr('Продажба');
    	$params['discount'] = tr('Намаление');
    	$params['payment'] = tr('Плащане');
    	$params['client'] = tr('Клиент');
    	$form->setOptions('param', $params);
    	$form->setDefault('quantity', '1');
    }
    
    
    /**
	 *  Показваме Детайлите които са за продажба на стоки
	 */
    function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$data->query->where("#param = 'sale'");
	}
	
	
	/**
	 * След като създадем елемент, ъпдейтваме Бележката
	 */
	static function on_AfterCreate($mvc, $rec)
    {
     	$mvc->Master->updateReceipt($rec);
    }
}