<?php


/**
 * Документ "Заявка за продажба"
 *
 * Мениджър на документи за Заявки за продажба, от оферта
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SaleRequests extends core_Master
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Заявки за продажба';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_SaleRequest';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Srq';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, doc_DocumentPlg, plg_Printing, doc_ActivatePlg,
    					bgerp_plg_Blank, acc_plg_DocumentSummary, plg_Sorting, doc_plg_HidePrices';
    
    
    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Поле за валута
     */
    public $filterCurrencyField = 'currencyId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales,contractor';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_SaleRequestDetails' ;
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,sales'; 


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, folderId, amountDeal, state, createdOn, createdBy';
    
    
	/**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заявка за продажба';
    
    
    /**
     * Работен кеш за вече извлечените продукти
     */
    protected static $cache;

    
    /**
     * Шаблон за еденичен изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutSaleRequest.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент,fromOffer');
        $this->FLD('contragentId', 'int', 'input=hidden,fromOffer');
		$this->FLD('others', 'text(rows=4)', 'caption=Условия');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=description)','caption=Плащане->Метод,fromOffer');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,fromOffer,oldFieldName=paymentCurrencyId');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Плащане->Курс,fromOffer,oldFieldName=rate');
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)','caption=Плащане->ДДС,oldFieldName=vat,fromOffer');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,fromOffer');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,fromOffer');
    	$this->FLD('amountDeal', 'double(decimals=2)', 'caption=Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountVat', 'double(decimals=2)', 'input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
    	$this->FLD('data', 'blob(serialize,compress)', 'input=none,caption=Данни');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
 	function act_CreateFromOffer()
 	{
 		$this->requireRightFor('add');
 		if($id = Request::get('id', 'int')){
 			expect($this->fetchField($id, 'state') == 'draft');
 		}
 		expect($originId = Request::get('originId'));
        $origin = doc_Containers::getDocument($originId);
    	expect($origin->className == 'sales_Quotations');
    	$originRec = $origin->fetch();
    	expect($originRec->state == 'active');
    	
    	// Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($origin->that, $id);
        
 		if ($this->haveRightFor('activate')) {
            $form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=9.9999', 'ef_icon = img/16/lightning.png, title = Активиране на документа');
        }
        
        $fRec = $form->input();
        if($form->isSubmitted()){
        	$rec = (object)array('originId' => $originId,
        						 'threadId' => $originRec->threadId,
        						 'folderId' => $originRec->folderId);
        	if(Request::get('edit')){
        		$rec->id = $id;
        		unset($rec->threadId);
        	}
        	
        	// Подготовка на данните
        	$rec->data = (array)$fRec;
        	$id = $this->saveData($rec, $fRec, $originRec, $form->cmd);
        	
        	return Redirect(array($this, 'single', $id));
        }
        
        return $this->renderWrapping($form->renderHtml());
 	}
    
    
    /**
     * Записване на данните от офертата в заявката
     * @param stdClass $rec - запис на заявката
     * @param stdClass $dRec - въведените детайли
     * @param stdClass $quoteRec - офертата пораждаща заявката
     * @param string $cmd - командата от формата
     * @return int $id - ид на записа
     */
    private function saveData($rec, $dRec, $quoteRec, $cmd)
    {
    	$fields = $this->selectFields("#fromOffer");
    	foreach($fields as $name => $fld){
    		if(isset($quoteRec->{$name})){
    			$rec->{$name} = $quoteRec->{$name};
    		}
    	}
    	
    	if(!$rec->id){
    		unset($rec->threadId);
    	}
    	
    	$rec->others = $quoteRec->others;
    	$this->save($rec);
    	$this->sales_SaleRequestDetails->delete("#requestId = {$rec->id}");
    	
    	$items = $this->prepareProducts($dRec);
    	
    	foreach ($items as $item){
    		$item->requestId = $rec->id;
    		$this->sales_SaleRequestDetails->save($item);
    	}
    	
    	deals_Helper::fillRecs($this, $items, $rec, sales_SaleRequestDetails::$map);
    	$amountDeal = ($rec->chargeVat == 'no') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amountDeal -= $this->_total->discount;
        $rec->amountDeal = $amountDeal * $rec->currencyRate;
        $rec->amountVat  = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
        
    	if($cmd == 'active'){
    		$rec->state = 'active';
        }
    	
        $this->save($rec);
        if($rec->state == 'active'){
        	$this->invoke('AfterActivation', array($rec));
        }
        
    	return $rec->id;
    }
    
    
    /**
     * Подготовка на продуктите от формата с вече уточнените к-ва в подходящ вид
     * @param array $products - продуктите върнати от формата
     * @param double $amount - сума на заявката
     * @return array $items - масив от продукти готови за запис
     */
    private function prepareProducts($products)
    {
    	$items = array();
    	$products = (array)$products;
    	foreach ($products as $index => $quantity){
    		list($productId, $classId, $optional) = explode("|", $index);
    		
    		// При опционален продукт без к-во се продължава
    		if($optional == 'yes' && empty($quantity)) continue;
    		
    		// Намира се кой детайл отговаря на този продукт
    		$obj = (object)$this->findDetail($productId, $classId, $quantity, $optional);
            $items[] = (object)array('classId'        => $obj->classId,
        					         'productId'      => $obj->productId,
        					 		 'discount'       => $obj->discount,
        					 		 'quantity'       => $obj->quantity,
        					 		 'price'          => $obj->price,
            						 'quantityInPack' => 1,
            );
    	}
    	
    	return $items;
    }
    
    
    /**
     * Помощна ф-я за намиране на записа съответстващ на избраното к-во
     * @param int $productId - ид на продукт
     * @param int $classId - продуктов мениджър
     * @param int $quantity - к-во
     * @param enum(yes/no) $optional - дали продукта е опционален
     * @return stdClass $val - обект съответсващ на детайл
     */
    private function findDetail($productId, $classId, $quantity, $optional)
    {
    	// Първо се проверява имали запис за този продукт с това к-во
    	$val = array_values( array_filter(static::$cache, 
    		function ($val) use ($productId, $classId, $quantity, $optional) {
           				if($val->optional == $optional && $val->productId == $productId && $val->classId == $classId && ($val->quantity == $quantity && $quantity)){
            				return $val;
            			}}));
            			
        // Ако к-то е ръчно въведено, се връща първия запис съответстващ на първото срещане на продукта
        if(!$val){
        	$val = array_values( array_filter(static::$cache, 
    		function ($val) use ($productId, $classId, $optional) {
           				if($val->optional == $optional && $val->productId == $productId && $val->classId == $classId){
            				return $val;
            			}}));
            			
            // Присвояване на к-то
            $val[0]->quantity = $quantity;
        }
    	
        return $val[0];
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * @param int $quotationId - ид на офертата
     * @param int $id - ид на записа ако има
     * @return core_Form - готовата форма
     */
    private function getFilterForm($quotationId, $id)
    {
    	$form = cls::get('core_Form');
    	$form->info = tr('Уточнете точните количества');
    	$filteredProducts = $this->filterProducts($quotationId);
    	
    	foreach ($filteredProducts as $index => $product){
    		
    		if($product->optional == 'yes') {
    			$product->title = "|Опционални|*->|*{$product->title}";
    			$product->options = array('' => '') + $product->options;
    			$mandatory = '';
    		} else {
    			$product->title = "|Оферирани|*->|*{$product->title}";
	    		if(count($product->options) > 1) {
	    			$product->options = array('' => '') + $product->options;
	    			$mandatory = 'mandatory';
	    		} else {
	    			$mandatory = '';
	    		}
    		}
    		
    		$form->FNC($index, "double(decimals=2)", "input,caption={$product->title},{$mandatory}");
    		if($product->suggestions){
    			$form->setSuggestions($index, $product->options);
    		} else {
    			$form->setOptions($index, $product->options);
    		}
    	}
    	
    	if($id && Request::get('edit')){
    		if($fRec = (object)$this->fetchField($id, 'data')){
    			$form->rec = $fRec;
    		}
    		$form->title = "|Редактиране на|*&nbsp; <b>{$this->singleTitle} №{$id}</b>";
    	} else {
    		$form->title = "|Заявка към|*&nbsp;<b>" . sales_Quotations::getRecTitle($quotationId) . "</b>";
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title = Прекратяване на действията');
    	
    	return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     * @param int $quoteId - ид на оферта
     * @return array $products - филтрираните продукти
     */
    private function filterProducts($quoteId)
    {
    	$products = array();
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$quoteId}");
    	$query->orderBy('optional', 'ASC');
    	static::$cache = $query->fetchAll();
    	while ($rec = $query->fetch()){
    		$index = "{$rec->productId}|{$rec->classId}|{$rec->optional}";
    		if(!array_key_exists($index, $products)){
    			$title = cls::get($rec->classId)->getTitleById($rec->productId);
    			$products[$index] = (object)array('title' => $title, 'options' => array(), 'optional' => $rec->optional, 'suggestions' => FALSE);
    		}
    		if($rec->optional == 'yes'){
    			$products[$index]->suggestions = TRUE;
    		}
    		if($rec->quantity){
    			$products[$index]->options[$rec->quantity] = $rec->quantity;
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Екшън генериращ продажба от оферта
     */
    function act_CreateSale()
    {
    	sales_Sales::requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetchRec($id));
    	 
    	// Опитваме се да намерим съществуваща чернова продажба
    	if(!Request::get('dealId', 'key(mvc=sales_Sales)') && !Request::get('stop')){
    		Redirect(array('sales_Sales', 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => TRUE));
    	}
    	 
    	// Ако няма създаваме нова
    	if(!$sId = Request::get('dealId', 'key(mvc=sales_Sales)')){
    
    		// Подготвяме данните на мастъра на генерираната продажба
    		$fields = array('currencyId'         => $rec->currencyId,
    						'currencyRate'       => $rec->currencyRate,
    						'paymentMethodId'    => $rec->paymentMethodId,
    						'deliveryTermId'     => $rec->deliveryTermId,
    						'chargeVat'          => $rec->chargeVat,
    						'originId'			 => $rec->containerId,
    						'note'				 => $rec->others,
    						'deliveryLocationId' => crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id'),
    		);
    		 
    		// Създаваме нова продажба от офертата
    		$sId = sales_Sales::createNewDraft($rec->contragentClassId, $rec->contragentId, $fields);
    	}
    	 
    	$query = sales_SaleRequestDetails::getQuery();
    	$query->where("#requestId = {$id}");
    	while($dRec = $query->fetch()){
    		sales_Sales::addRow($sId, $dRec->classId, $dRec->productId, $dRec->quantity, $dRec->price, NULL, $dRec->discount);
    	}
    	 
    	// Редирект към новата продажба
    	return Redirect(array('sales_Sales', 'single', $sId), tr('Успешно е създадена продажба от заявка'));
    }
    
    
	/**
     * След проверка на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if(($action == 'add') && isset($rec)){
    		if(!$rec->originId){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit') && isset($rec)){
    		$res = 'no_one';
    	}
    	
    	if(($action == 'activate') && $rec->state == 'draft'){
    		$res = 'ceo,sales';
    	}
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
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Заявка №" . $this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->createdOn, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    	}
    }
    
    
    /**
     * Обработка на завката
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {	
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	// Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();
        
    	$row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        $row->MyAddress      = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId);;
        $row->MyCompanyVatNo = $ownCompanyData->vatNo;
        
        $ContragentClass = cls::get($rec->contragentClassId);
        $row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId);
       	$cData = $ContragentClass->getContragentData($rec->contragentId);
    	$row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
	    if($fields['-list']){
    		$id = $row->id;
    		$singleImg = "<img src=" . sbf($mvc->singleIcon) . ">";
            $row->id = ht::createLink($singleImg, array($mvc, 'single', $rec->id));
    	    $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    	
	    	if($rec->state == 'draft'){
	    		$img = "<img src=" . sbf('img/16/edit-icon.png') . "/>";
	    		$row->id .= " " . ht::createLink($img, array('sales_SaleRequests', 'CreateFromOffer', $rec->id, 'originId' => $rec->originId, 'ret_url' => TRUE, 'edit' => TRUE));
	    	}
	    	$row->id .= " {$id}";
	    	@$rec->amountDeal = $rec->amountDeal / $rec->currencyRate;
	    	$row->amountDeal = "<span class='cCode' style='float:left;margin-right:3px'>{$rec->currencyId}</span>" . $mvc->getFieldType('amountDeal')->toVerbal($rec->amountDeal);
    	}
	    
	    if($fields['-single']){
	    	$origin = doc_Containers::getDocument($rec->originId);
	    	$row->originLink = $origin->getHyperLink();
	    	
	    	if($rec->others){
				$others = explode('<br>', $row->others);
				$row->others = '';
				foreach ($others as $other){
					$row->others .= "<li>{$other}</li>";
				}
			}
			
			// Взависимост начислява ли се ддс-то се показва подходящия текст
			switch($rec->chargeVat){
				case 'yes':
					$fld = 'withVat';
					break;
				case 'separate':
					$fld = 'sepVat';
					break;
				default:
					$fld = 'noVat';
					break;
			}
			$row->$fld = ' ';
			
			if($rec->currencyRate == 1){
				unset($row->currencyRate);
			}
	    }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if ($data->rec->state == 'active') {
    		$data->toolbar->addBtn('Продажба', array($mvc, 'createSale', $data->rec->id, 'ret_url' => TRUE), 'warning=Сигурнили сте че искате да създадете продажба?', 'order=22,ef_icon = img/16/cart_go.png,title=Създаване на нова продажба по заявката');
    	}
    	
    	if($data->rec->state == 'draft') {
	       	$data->toolbar->addBtn('Редакция', array('sales_SaleRequests', 'CreateFromOffer', $data->rec->id ,'originId' => $data->rec->originId, 'ret_url' => TRUE, 'edit' => TRUE), NULL, 'ef_icon=img/16/edit-icon.png,title=Редактиране на заявката');	
	   }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec = static::fetchRec($rec);
    	$me = cls::get(get_called_class());
    	
    	return $me->singleTitle . "  №{$rec->id}";
    }
}