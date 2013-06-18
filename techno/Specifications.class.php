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
    public $interfaces = 'doc_DocumentIntf, price_PolicyIntf, 
        acc_RegisterIntf=techno_specifications_Register, cat_ProductAccRegIntf';
    
    
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
    var $singleIcon = 'img/16/specification.png';
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,title,folderId,prodTehnoClassId,createdOn,createdBy';
    
    
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
    var $canEdit = 'no_one';
    
    
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
     * Кой може да го отхвърли?
     */
    var $canAjust = 'admin,techno';
    
    
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
		$this->FLD('prodTehnoClassId', 'class(interface=techno_ProductsIntf,select=title)', 'caption=Технолог,input=hidden,silent');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
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
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне на документ
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
        $jsEnabled = Mode::is('javascript', 'yes');
        
        $tpl = new ET("");
        $options = core_Classes::getOptionsByInterface('techno_ProductsIntf');
	    foreach($options as $id => $cls){
	    	$title = cls::getTitle($cls);
	    	$url = array($mvc, 'Ajust', 'technoId' => $id);
	    	if($folderId = Request::get('folderId')){
	    		$url['folderId'] = $folderId;
	    	} elseif($originId = Request::get('originId')){
	    		$url['originId'] = $originId;
	    	}
	    	$tpl->append(ht::createBtn($title, $url));
	    	$tpl->append("<br />");
	    }
      	
	    $tpl = $mvc->renderWrapping($tpl);
        
        return FALSE;
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
	    	
	    	$technoClass = cls::get($rec->prodTehnoClassId);
	    	$row->data = $technoClass->getVerbal($rec->data);
	    	$pInfo = $mvc->getProductInfo($rec->id);
	    	$row->measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
	    }
	    
	    if($fields['-list']){
	    	if($mvc->haveRightFor('ajust', $rec)){
	    		$img = "<img src=" . sbf('img/16/testing.png') . ">";
	    		$row->tools = ht::createLink($img, array($mvc, 'ajust', $rec->id));
	    	}
	    	
	    	if(doc_Folders::haveRightFor('single', $rec->folderId)){
	    		$img = doc_Folders::getIconImg($rec->folderId);
	    		$attr['class'] = 'linkWithIcon';
	    		$attr['style'] = 'background-image:url(' . $img . ');';
	    		$link = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
            	$row->folderId = ht::createLink($row->folderId, $link, NULL, $attr);
	    	}
	    }
    }
    
    
    /**
     * Екшън който показва формата за въвеждане на характеристики
     * на продукта, спрямо избрания продуктов технолог
     */
    function act_Ajust()
    {
    	$this->requireRightFor('add');
    	if($id = Request::get('id', 'int')){
    		expect($rec = $this->fetch($id));
    	} else {
    		expect($technoId = Request::get('technoId', 'int'));
	    	$folderId = Request::get('technoId', 'int');
	    	$originId = Request::get('originId', 'int');
	    	$rec = new stdClass();
	    	$rec->prodTehnoClassId = $technoId;
	    	if($folderId){
	    		$rec->folderId = $folderId;
	    	}
	    	if($originId){
	    		$rec->origin = $originId;
	    	}
    	}
    	
        // Връщаме формата от технологовия клас
        expect($technoClass = cls::get($rec->prodTehnoClassId));
    	$form = $technoClass->getEditForm();
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), array('class' => 'btn-cancel'));
        
    	$fRec = $form->input();
        if($form->isSubmitted()) {
        	if($this->haveRightFor('add')){
        		
        		// Записваме въведените данни в пропъртито data на река
	            $rec->title = $fRec->title;
	            $fRec = (object)array_merge((array) unserialize($rec->data), (array) $fRec);
        		
	            // Записваме мастър - данните
	            $this->save($rec);
        		$fRec->specificationId = $rec->id;
        		$rec->data = $technoClass->serialize($fRec);
        		
        		// Записваме данните въведени от технолога
        		$this->save($rec);
        		
        		$quantities = $technoClass->getFollowingQuoteInfo($rec->data);
        		// Нова оферта се създава само ако има въведено количество
	        	if(count($quantities) > 1){
	    			return Redirect(array($this, 'newQuote', $rec->id));
	    		} else {
	    			return Redirect(array($this, 'single', $rec->id));
	    		}
	        }
        }
        
        if($rec->data){
        	
        	// При вече въведени характеристики, слагаме ги за дефолт
        	$form->rec = unserialize($rec->data);
        }
        
        $params = array('doc_Threads', 'single', 'folderId' => $rec->folderId);
        $folderLink = doc_Folders::getVerbalLink($params);
        $form->title = "Спецификация на универсален продукт в|* {$folderLink}";
        
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
    	if($mvc->haveRightFor('ajust', $data->rec)){
    		
        	// Може да се променят с само на чернова
        	$url = array($mvc, 'Ajust', 'id' => $data->rec->id, 'ret_url' => toUrl($data->retUrl, 'local'));
        	$data->toolbar->addBtn("Характеристики", $url, 'class=btn-settings');
    	}
    	
    	// Добавяне на бутон за ъпдейт на съществуваща оферта
    	if(sales_Quotations::haveRightFor('add') && $data->rec->state == 'draft'){
    		$tData = unserialize($data->rec->data);
    		if(isset($tData->quantity1) || isset($tData->quantity2) || isset($tData->quantity3)){
    			$qId = sales_Quotations::fetchField("#originId = {$data->rec->containerId}", 'id');
    			$data->toolbar->addBtn("Оферта", array('sales_QuotationsDetails', 'quotationId' => $qId, 'updateData', 'originId' => $data->rec->containerId), 'ef_icon=img/16/document_quote.png');
    		}
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
    	$qInfo = $this->getFollowingQuoteInfo($id);
    	
    	$Quotations = cls::get('sales_Quotations');
    	$qId = $Quotations->fetchField("#originId = {$rec->containerId}", 'id');
    	
    	$data = new stdClass();
	    $data->form = sales_Quotations::getForm();
	    if($qId){
	    	 $data->form->rec->id = $qId;
	    }
	    $data->form->rec->folderId = $rec->folderId;
		$data->form->rec->originId = $rec->containerId;
		$data->form->rec->threadId = $rec->threadId;
	    $Quotations->invoke('AfterPrepareEditForm', array($data));
	    $data->form->rec->rate = round(currency_CurrencyRates::getRate($data->form->rec->date, $qInfo['currencyId'], NULL), 4);
	    
	    $data->form->rec->paymentCurrencyId = $qInfo['currencyId'];
	    $data->form->rec->vat = 'yes';
	    $data->form->rec->paymentMethodId = salecond_PaymentMethods::fetchField('#name="1 m"', 'id');
	    $data->form->rec->deliveryTermId = salecond_DeliveryTerms::fetchField('#codeName="CFR"', 'id');
	    $qId = $Quotations->save($data->form->rec);
    	
	    return Redirect(array($this, 'single', $id));
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
    	return $technoClass->getPrice($customerClass, $customerId, $rec->data, $packagingId, $quantity, $datetime);
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
    		
    		if(!isset($rec) || (isset($rec) && $rec->state != 'draft') || (isset($rec) && !$rec->data)){
    			$res = 'no_one';
    		}else {
    			$res = 'admin, techno';
    		}
    	}
    	
    	if($action == 'ajust' && isset($rec)){
    		if($rec->state != 'draft'){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'configure' && isset($rec)){
    		if($rec->state == 'draft'){
    			$res = 'admin, techno';
    		} else {
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
     * Помага за генериране на последваща оферта
     * Връща масив от вида [име_на_поле] => [количество]
     * Първия елемент е задължително [currencyId] - валута в която
     * е цената на артикула
     * За всяка една от тези стойностти в генерираната оферта
     * се добавя по един ред
     * @return array
     */
	function getFollowingQuoteInfo($id)
	{
    	$rec = static::fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getFollowingQuoteInfo($rec->data);
    }
}
