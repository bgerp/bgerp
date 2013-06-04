<?php



/**
 * Документ "Спецификации"
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Specifications extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, price_PolicyIntf, acc_RegisterIntf, cat_ProductAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Спецификации";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, techno_Wrapper, plg_Printing, bgerp_plg_Blank,
                    doc_DocumentPlg, doc_ActivatePlg, doc_plg_BusinessDoc, plg_Search';

	
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Спецификация";
    
    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/toggle1.png';
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, contragentName';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,title,contragentName,prodTehnoClassId,createdOn,createdBy';
    
    
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
     * Брой записи на страница
     */
    var $listItemsPerPage = '40';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.7|Търговия";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, input=hidden');
		$this->FLD('prodTehnoClassId', 'class(interface=techno_ProductsIntf,select=title)', 'caption=Технолог,mandatory');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
		$this->FLD('contragentName', 'varchar(136)', 'caption=Контрагент,input=hidden');
		$this->FNC('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)');
		$this->FNC('measureId', 'key(mvc=cat_UoM, select=name)');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->view = 'horizontal';
    	 $data->listFilter->FNC('prodTehnoClass', 'class(interface=techno_ProductsIntf,allowEmpty)', 'placeholder=Технолог');
    	 $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
    	 $data->listFilter->showFields = 'search,prodTehnoClass';
    	 $data->listFilter->input();
    	 if($technoClass = $data->listFilter->rec->prodTehnoClass){
    	 	$data->query->orWhere("#prodTehnoClassId = {$technoClass}");
    	 }
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
    	$form = &$data->form;
    	if($contragentData = doc_Folders::getContragentData($form->rec->folderId)){
    		if($contragentData->person) {
    			$form->rec->contragentName = $contragentData->person;
    		} elseif($contragentData->company) {
    			$form->rec->contragentName = $contragentData->company;
    		} 
    	}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$title = $this->recToVerbal($rec, 'title')->title;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' "' . $title . '"';
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->title;
		
        return $row;
    }
    
    
	/**
     * Заглавие на политиката
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
     * Връща продуктие, които могат да се продават на
     * посочения клиент. Това са всички спецификации
     * от неговата папка, ако няма спецификации 
     * редиректваме с подходящо съобщение
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
    	$query->where("#state = 'active'");
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
    		$double = cls::get('type_Double');
	    	$double->params['decimals'] = 2;
	    	
	    	if($rec->data){
	    		$technoClass = cls::get($rec->prodTehnoClassId);
	    		$row->data = $technoClass->getVerbal($rec->data);
	    	} else {
	    		$row->noData = tr('Няма данни');
	    	}
	    	
	    	if($rec->data && $rec->state == 'draft'){
	    		$previewRow = $technoClass->preparePricePreview($rec->data);
	    		$row = (object)array_merge((array) $row, (array) $previewRow);
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
        	$url = array($mvc, 'Ajust', $rec->id, 'ret_url' => toUrl($data->retUrl, 'local'));
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
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'draft');
        
        // Връщаме формата от технологовия клас
        $technoClass = cls::get($rec->prodTehnoClassId);
        $form = $technoClass->getEditForm();
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), array('class' => 'btn-cancel'));
        
    	$fRec = $form->input();
        if($form->isSubmitted()) {
        	if($this->haveRightFor('add')){
        		
        		// Записваме въведените данни в пропъртито data на река
	            $rec->title = $fRec->title;
	            $fRec->specificationId = $rec->id;
	            $fRec = (object)array_merge((array) unserialize($rec->data), (array) $fRec);
        		$rec->data = $technoClass->serialize($fRec);
        		
	            $this->save($rec);
	            return  Redirect(array($this, 'single', $rec->id));
        	}
        }
        
        if($rec->data){
        	
        	// При вече въведени характеристики, слагаме ги за дефолт
        	$form->rec = unserialize($rec->data);
        }
        
        $form->title = "Характеристики на ". $this->getTitleById($rec->id);
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
    		
        	// Може да се променят с само на чернова
        	$url = array($mvc, 'Ajust', 'id' => $data->rec->id, 'ret_url' => toUrl($data->retUrl, 'local'));
        	$data->toolbar->addBtn("Характеристики", $url, 'class=btn-settings');
    	}
    	
    	if(sales_Quotations::haveRightFor('add') && $data->rec->state == 'active'){
    		$data->toolbar->addBtn("Оферта", array($mvc, 'newQuote', $data->rec->id), 'ef_icon=img/16/document_quote.png');
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
    	$data->form->rec->rate = round(currency_CurrencyRates::getRate($data->form->rec->date, $rec->currencyId, NULL), 4);
    	$data->form->rec->paymentCurrencyId = $rec->currencyId;
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
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getVat($id, $date);
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения
     * клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $id, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	$rec = $this->fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getPrice($rec->data, $packagingId, $quantity, $datetime);
    }
    
    
    /**
     * Предефинираме метода getTitleById да връща вербалното
     * представяне на продукта
     * @param int $id - id на спецификацията
     * @param boolean $full 
     * 	      		FALSE - връща само името на спецификацията
     * 		        TRUE - връща целия шаблон на спецификацията
     * @return core_ET - шаблон с представянето на спецификацията
     */
     static function getTitleById($id, $escaped = TRUE, $full = FALSE)
     {
    	if(!$full) {
    		return parent::getTitleById($id, $escaped);
    	}
    	
    	//$cache = core_Cache::get('techno_Specifications', "products");
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
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - Ид на продукта
     * @param int $packagingId - Ид на опаковката, по дефолт NULL
     * @return stdClass $res - Обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($id, $packagingId = NULL)
    {
    	$rec = static::fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getProductInfo($rec->data, $packagingId);
    }
    
    
	/**
     * 
     * Enter description here ...
     */
    function act_Configure()
    {
    	$this->requireRightFor('edit');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$data = unserialize($rec->data);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	
    	if($paramId = Request::get('delete')){
    		unset($data->params[$paramId]);
    		$rec->data = $technoClass->serialize($data);
	        $this->save($rec);
	        return followRetUrl();
    	}
    	
    	$form = $technoClass->getAddParamForm();
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', array('techno_Specifications', 'single', $id), array('class' => 'btn-cancel'));
    	
    	$fRec = $form->input();
        if($form->isSubmitted()) {
        	if($this->haveRightFor('edit')){
        		
        		// Записваме въведените данни в пропъртито data на река
	            $data->params[$fRec->paramId] = $fRec->paramValue;
        		$rec->data = $technoClass->serialize($data);
	            $this->save($rec);
	            return  Redirect(array($this, 'single', $rec->id));
        	}
        }
        
    	if($paramId = Request::get('edit')){
        	$form->rec->paramValue = $data->params[$paramId];
        	$form->rec->paramId = $paramId;	
        }
        
        $form->title = "Добавяне на параметри към ". $this->getTitleById($rec->id);
    	return $this->renderWrapping($form->renderHtml());
    }
}