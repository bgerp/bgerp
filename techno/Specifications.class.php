<?php



/**
 * Документ "Спецификации"
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Specifications extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, price_PolicyIntf, acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Спецификации";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, techno_Wrapper, plg_Printing, bgerp_plg_Blank,
                    doc_DocumentPlg, doc_ActivatePlg, doc_plg_BusinessDoc';

	
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Спецификация";
    
    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/toggle1.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,title,prodTehnoClassId,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,techno';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'admin,techno';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'techno/tpl/SingleLayoutSpecifications.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Sp";
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'admin,techno,broker';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'admin,techno,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,techno';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin,techno';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
	
	
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.7|Търговия";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, mandatory,remember=info,width=100%');
		$this->FLD('prodTehnoClassId', 'class(interface=techno_ProductsIntf)', 'caption=Технолог,mandatory');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
		$this->FLD('quantity1', 'int', 'caption=Превю цени->К-во 1,mandatory,width=4em');
    	$this->FLD('quantity2', 'int', 'caption=Превю цени->К-во 2,width=4em');
    	$this->FLD('quantity3', 'int', 'caption=Превю цени->К-во 3,width=4em');
		$this->FNC('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', '');
    	$this->FNC('measureId', 'key(mvc=cat_UoM, select=name)', '');
    }
    
    
    /**
     * Изчисляваме мярката според това въведено в детайла
     */
    function on_CalcMeasureId($mvc, $rec)
    {
    	if($rec->data){
    		$data = unserialize($rec->data);
    		$rec->measureId = $data->measureId;
    	}
    }
    
    
	/**
     * Изчисляваме мярката според това въведено в детайла
     */
    function on_CalcCurrencyId($mvc, $rec)
    {
    	if($rec->data){
    		$data = unserialize($rec->data);
    		$rec->currencyId = $data->currencyId;
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setDefault('quantity1', '1');
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . ' "' . $rec->title . '"';
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
	/**
     * Заглавие на ценоразписа за конкретен клиент 
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->singleTitle;
    }
    
    
    /**
     * Връща продуктие, които могат да се продават
     * на посочения клиент. Това са всички спецификации от
     * неговата папка, ако няма спецификации редиректваме с
     * подходящо съобщение
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
    	$Class = cls::get($customerClass);
    	$customer = $Class->fetch($customerId);
    	$folderId = $Class->forceCoverAndFolder($customer, FALSE);
    	
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->where("#data IS NOT NULL");
    	//$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$products[$rec->id] = $this->recToVerbal($rec, 'title')->title;
    	}
    	if(!count($products)) followRetUrl(NULL, 'Няма спецификации за този клиент');
    	
    	return $products;
    }

    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Можем да добавяме или ако корицата е контрагент или сме в папката на текущата каса
        $cover = doc_Folders::getCover($folderId);
        
        return $cover->haveInterface('doc_ContragentDataIntf');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	}
    	
    	if($fields['-single']){
	    	if($rec->data){
	    		
	    		// Подготвяме изгледа на изделието
	    		$technoClass = cls::get($rec->prodTehnoClassId);
	    		$row->data = $technoClass->getVerbal($rec->data);
	    	}
	    	
	    	$double = cls::get('type_Double');
	    	$double->params['decimals'] = 2;
	    	$row->shortMeasureId = cat_UoM::fetchField($rec->measureId, 'shortName');
	    	foreach(range(1,3) as $num){
	    		$quantity = "quantity{$num}";
		    	$price = "price{$num}";
	    		if($rec->$quantity){
		    		$pPrice = $mvc->getPriceInfo(NULL, NULL, $rec->id, NULL, $rec->$quantity);
		    		$row->$price = $pPrice->price - ($pPrice->price * $pPrice->discount);
		    		$row->$price = $double->toVerbal($row->$price);
		    		$row->$price .=" &nbsp;<span class='cCode'>{$rec->currencyId}</span>";
		    		$row->$quantity .= " {$row->shortMeasureId}";
	    		}
	    	}
    	}
    }
    
    
	/**
     * Подменя URL-то да сочи направо към формата на технологовия клас
     */
    static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if($data->form && $data->form->isSubmitted()) {
        	$rec = $data->form->rec;
        	$url = array($mvc, 'Ajust', 'id' => $rec->id, 'ret_url' => toUrl($data->retUrl, 'local'));
            $data->retUrl = $url;
        }
    }
    
    
    /**
     * Екшън който показва формата за въвеждане на характеристики
     * на продукта, спрямо избрания продуктов технолог
     */
    function act_Ajust()
    {
    	$this->requireRightFor('add');
    	expect($id = Request::get('id'));
        $rec = $this->fetch($id);
        
        // Връщаме формата от технологовия клас
        $technoClass = cls::get($rec->prodTehnoClassId);
        $form = $technoClass->getEditForm();
        
    	$fRec = $form->input();
        if($form->isSubmitted()) {
        	if($this->haveRightFor('add')){
        		
        		// Записваме въведените данни в пропъртито data на река
	            $fRec->title = $rec->title;
        		$rec->data = $technoClass->serialize($fRec);
	            $this->save($rec);
	            return  Redirect(array($this, 'single', $rec->id));
        	}
        }
        
        if($rec->data){
        	
        	// При вече въведени характеристики, слагаме ги за дефолт
        	$data = unserialize($rec->data);
        	$form->setDefaults($data);
        }
        
        $form->title = "Характеристики на ". $rec->title;
        return $this->renderWrapping($form->renderHtml());
    }
    
    
	/**
     * Връща мениджъра на продуктите
     * @return core_Classes $class - инстанция на мениджъра
     */
    public function getProductMan()
    {
        return cls::get(get_called_class());
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'draft'){
    		$url = array($mvc, 'Ajust', 'id' => $data->rec->id, 'ret_url' => toUrl($data->retUrl, 'local'));
        	
        	// Може да се променят с само на чернова
        	$data->toolbar->addBtn("Характеристики", $url, 'class=btn-settings');
    	}
    	
    	if(sales_Quotations::haveRightFor('add') && $data->rec->state == 'active'){
    		$data->toolbar->addBtn("Оферирай", array($mvc, 'newQuote', $data->rec->id), 'ef_icon=img/16/document_quote.png');
    	}
    }
    
    
    /**
     * Създаване на нова оферта, с попълнени дефолт данни
     */
    function act_newQuote()
    {
    	sales_Quotations::requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active');
    	
    	$Quotations = cls::get('sales_Quotations');
    	$data = new stdClass();
    	$data->form = sales_Quotations::getForm();
    	$data->form->rec->folderId = $rec->folderId;
    	$data->form->rec->originId = $rec->containerId;
    	$data->form->rec->threadId = $rec->threadId;
    	$Quotations->invoke('AfterPrepareEditForm', array($data));
    	$Quotations->invoke('AfterInputEditForm', array($data->form));
    	$data->form->rec->vat = 'yes';
    	$data->form->rec->paymentMethodId = salecond_PaymentMethods::fetchField('#name="1 m"', 'id');
    	$data->form->rec->deliveryTermId = salecond_DeliveryTerms::fetchField('#codeName="CFR"', 'id');
    	$qId = $Quotations->save($data->form->rec);
    	
    	return Redirect(array($Quotations, 'single', $qId));
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на спецификацията
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	$rec = static::fetch($id);
    	if($rec->data){
    		$data = unserialize($rec->data);
    		if($data->vat) return $data->vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	return $period->vatRate;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	$rec = $this->fetch($productId);
    	if($rec->data){
    		$data = unserialize($rec->data);
    		
    		if($data->price){
    			$price = new stdClass();
    			if($data->price){
    				$price->price = $data->price;
    			}
    			if($data->discount){
    				$price->discount = $data->discount;
    			}
    			if($price->price) {
    				if($quantity){
    					$price->price = $price->price * $quantity;
    					return $price;
    				} else {
    					return $price;
    				}
    			}
    		}
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Предефинираме метода getTitleById да връща вербалното
     * представяне на продукта
     * @param int $id - id на спецификацията
     * @return core_ET - шаблон сunknown_type представянето на спецификацията
     */
     static function getTitleById($id, $escaped = TRUE)
     {
    	$rec = static::fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getVerbal($rec->data, TRUE);
     }
     
     
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'activate'){
    		if(!isset($rec) || (isset($rec) && !$rec->data)){
    			$res = 'no_one';
    		}
    	}
    }
}