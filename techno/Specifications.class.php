<?php



/**
 * Документ "Спецификация" - нестандартен продукт или услуга
 * изготвена според изискванията на даден клиент
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
                    doc_DocumentPlg, doc_ActivatePlg, doc_plg_BusinessDoc, plg_Search, doc_SharablePlg';

    
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
    var $searchFields = 'title, folderId';
    
    
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
    var $canRead = 'ceo,techno';
    
    
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
    var $canAdd = 'ceo,techno,cat';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'ceo,techno';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,techno';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,techno';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canAjust = 'ceo,techno';
    
    
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
		$this->FLD('prodTehnoClassId', 'class(interface=techno_ProductsIntf,select=title)', 'caption=Тип,input=hidden,silent');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
		$this->FLD('common', 'enum(no=Не,yes=Общо)', 'input=none,value=no');
    	$this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители,input=none');
    	$this->FLD('isOfferable', 'enum(no=Не,yes=Да)', 'input=none,value=no');
    	
    	// В кой тред е пораждащата фактура
    	$this->FLD('invThread', 'int', 'input=none');
    }
    
    
    /**
     * Извиква се преди запис в модела
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->state != 'active'){
    		$cover = doc_Folders::fetchCoverClassName($rec->folderId);
		    if($cover == 'doc_UnsortedFolders'){
		        $rec->common = 'yes';
		    }
	    	
	    	$technoClass = cls::get($rec->prodTehnoClassId);
	    	$price = $technoClass->getPrice($rec->data)->price;
	    	$rec->isOfferable = ($price && $rec->common != 'yes') ? 'yes' : 'no';
    	}
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->view = 'horizontal';
    	 $data->listFilter->FNC('prodTehnoClass', 'class(interface=techno_ProductsIntf,allowEmpty,select=title)', 'placeholder=Технолог');
    	 $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png'); 
    	 $data->listFilter->showFields = 'search,prodTehnoClass';
    	 $data->listFilter->input();
    	 if($technoClass = $data->listFilter->rec->prodTehnoClass){
    	 	$data->query->orWhere("#prodTehnoClassId = {$technoClass}");
    	 }
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     * Показва се меню с достъпните драйвери и след като се избере
     * един се преминава към създаването на спецификация
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
	    	if($threadId = Request::get('threadId')){
	    		$url['threadId'] = $threadId;
	    	}
	    	$url['ret_url'] = array($mvc, 'list');
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
     * Връща продуктие, които могат да се продават на посочения клиент
     * Това са всички спецификации от неговата папка, както и
     * всички общи спецификации (създадени в папка "Проект")
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
    	$Class = cls::get($customerClass);
    	$customer = $Class->fetch($customerId);
    	$folderId = $Class->forceCoverAndFolder($customer, FALSE);
    	
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	$query->where("#folderId = {$folderId}");
    	$query->orWhere("#common = 'yes'");
    	
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
       
        return $cover->haveInterface('doc_ContragentDataIntf') || $cover->className == 'doc_UnsortedFolders';
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
	    	$row->data = $technoClass->getVerbal($rec->data, $rec->id, $rec->state);
	    	$pInfo = $mvc->getProductInfo($rec->id);
	    	$row->measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
	    
	    	if($rec->common == 'no'){
	    		unset($row->common);
	    	}
    	}
	    
	    if($fields['-list']){
	    	if($mvc->haveRightFor('ajust', $rec)){
	    		$img = "<img src=" . sbf('img/16/testing.png') . ">";
	    		$row->tools = ht::createLink($img, array($mvc, 'ajust', $rec->id, 'ret_url' => TRUE)) . $row->tools . " " . $rec->id;
	    	}
	    	
	    	if(doc_Folders::haveRightFor('single', $rec->folderId)){
	    		$img = doc_Folders::getIconImg($rec->folderId);
	    		$attr = array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . $img . ');');
	    		$link = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
            	$row->folderId = ht::createLink($row->folderId, $link, NULL, $attr);
	    	}
	    }
    }
    
    
    /**
     * Екшън който показва формата за въвеждане на характеристики
     * на продукта, спрямо избрания продуктов технолог
     * формата за характеристиките се взима от драйвера
     */
    function act_Ajust()
    {
    	$this->requireRightFor('add');
    	
    	// Извличане на записа от рекуеста
    	$rec = $this->getRec();
    	
        // Връщаме формата от технологовия клас
        expect($technoClass = cls::get($rec->prodTehnoClassId));
    	$form = $technoClass->getEditForm($rec);
    	if($rec->threadId){
    		$form->rec->threadId = $rec->threadId;
    	}
    	
    	$form->FNC('sharedUsers', 'userList', "caption=Споделяне->Потребители,input");
    	$this->invoke('AfterPrepareEditForm', array((object)array('form' => $form)));
    	
    	$this->prepareEditToolbar((object)array('form' => $form, 'retUrl' => getretUrl()));
        if($rec->id){
        	
        	// Ако се редактира се маха бутона за записване в нов-тред
        	unset($form->toolbar->buttons['btnNewThread']);
        }
    	
    	$form->input();
        if($form->isSubmitted()) {
        	if($this->haveRightFor('add')){
        		
        		// Записване на данните за спецификацията
        		$this->saveData($form, $rec);
        	}
        }
        
        if($rec->id){
        	$form->title = "Промяна на спецификация|* {$this->recToVerbal($rec, 'id,title,-list')->title}";
        } else {
	        if($rec->folderId){
	        	$params = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
	        	$link = doc_Folders::getVerbalLink($params);
	        } elseif($rec->threadId){
	        	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
	        	$handle = $firstDoc->getHandle();
	        	$params = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
	        	$params['#'] = $handle;
	        	$link = ht::createLink($handle, $params);
	        }
	        
        	$form->title = "Спецификация на универсален продукт в|* {$link}";
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Помощна ф-я за извличане на записа от рекуеста
     */
    private function getRec()
    {
    	if($id = Request::get('id', 'int')){
    		expect($rec = $this->fetch($id));
    		expect($rec->state == 'draft');
    	} else {
    		expect($technoId = Request::get('technoId', 'int'));
	    	$folderId = Request::get('folderId', 'int');
	    	$originId = Request::get('originId', 'int');
	    	$threadId = Request::get('threadId', 'int');
	    	
	    	$rec = new stdClass();
	    	$rec->prodTehnoClassId = $technoId;
	    	if($folderId){
	    		$rec->folderId = $folderId;
	    	}
	    	if($originId){
	    		$rec->origin = $originId;
	    	}
    		if($threadId){
	    		$rec->threadId = $threadId;
	    	}
    	}
    	
    	return $rec;
    }
    
    
    /**
     * Помощна ф-я за записване на спецификация
     * @param core_Form $form - форма
     * @param stdClass $rec - записа на спецификацията
     */
    private function saveData(core_Form &$form, &$rec)
    {
    	$fRec = &$form->rec;
    	
    	// Записваме въведените данни в пропъртито data на река
	    $rec->title = $fRec->title;
	    $fRec = (object)array_merge((array) unserialize($rec->data), (array) $fRec);
        $rec->sharedUsers = $fRec->sharedUsers;
        		
        if($form->cmd == 'save_new_thread' && $rec->threadId){
        	$rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
			unset($rec->threadId);
		}
	            
	    // Записваме данните въведени от технолога
        unset($fRec->threadId);
        $technoClass = cls::get($rec->prodTehnoClassId);
        $rec->data = $technoClass->serialize($fRec);
        $this->save($rec);
        			
        // Ако няма к-ва и оферта -> отива се в single
        return Redirect(array($this, 'single', $rec->id));
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
        	$url = array($mvc, 'Ajust', 'id' => $data->rec->id, 'ret_url' => TRUE);
        	$data->toolbar->addBtn("Характеристики", $url, 'class=btn-settings,title=Промяна на характеристиките на спецификацията');
    	}
    	
    	if($mvc->haveRightFor('add') && $data->rec->state == 'active'){
    		$data->toolbar->addBtn("Копие", array($mvc, 'copy', $data->rec->id), 'ef_icon=img/16/page_2_copy.png,title=Копиране на спецификацията,warning=Сигурнили сте че искате да копирате документа ?');
    	}
    	
    	if(sales_Quotations::haveRightFor('add') && $data->rec->isOfferable == 'yes'){
    		$qId = sales_Quotations::fetchField(("#originId = {$data->rec->containerId} AND #state='draft'"), 'id');
    		if($qId){
    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qId), 'ef_icon=img/16/document_quote.png,title=Промяна на съществуваща оферта');
    		} else {
    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $data->rec->containerId), 'ef_icon=img/16/document_quote.png,title=Създава оферта за спецификацията');
    		}
    	}
    }
    
    
    /**
     * Екшън копиращ дадена спецификация в същия тред. Ако името
     * на старата спецификация завършва на число го инкрементира,
     * в новата, ако няма добавя "v2" в заглавието на спецификация
     */
    function act_Copy()
    {
    	$this->requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active');
    	$originId = $rec->containerId;
    	
    	// Копието е нов документ(чернова), в същата папка в нов тред
    	unset($rec->id, $rec->containerId, $rec->createdOn, $rec->modifiedOn, $rec->createdBy, $rec->modifiedBy);
    	$rec->state = 'draft';
    	$rec->originId = $originId;
    	
    	// Промяна на името на копието
    	$data = unserialize($rec->data);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	$newTitle = $rec->title;
    	if(!str::increment($newTitle)){
    		$newTitle .= " v2";
    	}
    	while($this->fetch("#title = '{$newTitle}'")){
    		$newTitle = str::increment($newTitle);
    	}
    	$rec->title = $data->title = $newTitle;
    	$rec->data = $technoClass->serialize($data);
    	
    	// Запис и редирект
    	$this->save($rec);
    	return Redirect(array($this, 'single', $rec->id), FALSE, 'Спецификацията е успешно копирана');
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на спецификацията
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	$rec = static::fetchRec($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getVat($rec->data, $date);
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения
     * клиент на посочената дата
     * Цената се изчислява по формулата формулата:
     * ([начални такси] * (1 + [максимална надценка]) + [количество] * 
     *  [единична себестойност] *(1 + [минимална надценка])) / [количество]
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $id, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	$rec = $this->fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	$priceInfo = $technoClass->getPrice($rec->data, $packagingId, $quantity, $datetime);
    	
    	if($priceInfo->price){
    		$price = new stdClass();
    		if($priceInfo->discount){
    			$price->discount = $priceInfo->discount;
    		}
    		
    		$minCharge =  salecond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    		$maxCharge = salecond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    		if(!$quantity){
    			$quantity = 1;
    		}
    		$calcPrice = ($priceInfo->tax * (1 + $maxCharge) 
    					+ $quantity * $priceInfo->price * (1 + $minCharge)) / $quantity;
    		
    		$price->price = currency_CurrencyRates::convertAmount($calcPrice, NULL, $data->currencyId, NULL);
    		return $price;
    	}
    	
    	return FALSE;
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
     public static function getTitleById($id, $escaped = TRUE, $full = FALSE)
     {
    	if(!$full) {
    		return parent::getTitleById($id, $escaped);
    	}
    	
    	$rec = static::fetch($id);
	    $technoClass = cls::get($rec->prodTehnoClassId);
	    return $technoClass->getVerbal($rec->data, $rec->id, $rec->state, TRUE);
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
    			$res = 'ceo, techno';
    		}
    	}
    	
    	if($action == 'ajust' && isset($rec)){
    		if($rec->state != 'draft'){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'edit'){
    		$res = 'no_one';
    	}
    	
    	if($action == 'configure' && isset($rec)){
    		
    		if($rec->state == 'draft'){
    			$res = 'ceo, techno';
    		} else {
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
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
     * Връща изпозлваните документи
     * (@see techno_ProductsIntf::getUsedDocs)
     */
    function getUsedDocs_($id)
    {
    	$rec = static::fetch($id);
    	$technoClass = cls::get($rec->prodTehnoClassId);
    	return $technoClass->getUsedDocs($rec->data);
    }
}