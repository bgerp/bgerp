<?php



/**
 * Клас за автоматично създаване на оферта от запитване
 * 
 * @category  bgerp
 * @package   auto
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class auto_handler_CreateQuotationFromInquiry {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'auto_AutomationIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Автоматично създаване на оферта от запитване";
    
    
    /**
     * Можели класа да обработи събититето
     */
    public function canHandleEvent($event)
    {
    	return (strtolower($event) == strtolower('createdInquiryByPartner'));
    }
    
    
    /**
     * Изпълняване на автоматизация по събитието
     */
    public function doAutomation($event, $data)
    {
    	$marketingRec = $data;
    	expect(marketing_Inquiries2::fetch($marketingRec->id));
    	$document = doc_Containers::getDocument($marketingRec->containerId);
    	expect($document->isInstanceOf('marketing_Inquiries2'));
    	
    	// Проверка на корицата
    	$Cover = doc_Folders::getCover($marketingRec->folderId);
    	expect($Cover->haveInterface('crm_ContragentAccRegIntf'));
    	
    	// Ако има артикул към запитването не се прави нищо
    	if(cat_Products::fetchField("#originId = {$marketingRec->containerId}")) {
    		marketing_Inquiries2::logDebug("Не може да се създаде автоматично артикул към запитването защото има вече такъв", $marketingRec->id);
    		return;
    	}
    	
    	// Опит за създаване на артикул от запитване
    	$productId = $this->createProduct($marketingRec, $Cover, $document);
    	if(!$productId){
    		marketing_Inquiries2::logDebug("Проблем при опит за създаване на автоматичен артикул към запитване", $marketingRec->id);
    		return;
    	} else {
    		marketing_Inquiries2::logInfo("Успешно създаден артикул от автоматизация '{$rec->event}'", $marketingRec->id);
    	}
    	
    	// Имали подадени количества
    	$quantities = array();
    	foreach (range(1, 3) as $i){
    		$q = $marketingRec->{"quantity{$i}"};
    		if(empty($q)) continue;
    		$quantities[$q] = $q;
    	}
    	
    	// За всяко
    	if(count($quantities)){
    		
    		// Създаване на оферта към артикула
    		core_Users::forceSystemUser();
    		$fields = array('originId' => cat_Products::fetchField($productId, 'containerId'));
    		$quoteId = sales_Quotations::createNewDraft($Cover->getInstance()->getClassId(), $Cover->that, NULL, $fields);
    		if(!$quoteId){
    			cat_Products::logDebug("Проблем при опит за създаване на автоматичен оферта към артикул", $productId);
    			return;
    		} else {
    			sales_Quotations::logInfo("Успешно създаване на оферта към артикул от запитване", $quoteId);
    		}
    		
    		// Добавяне на редоввете на офертата
    		if(!empty($quoteId)){
    			foreach ($quantities as $q){
    				sales_Quotations::addRow($quoteId, $productId, $q);
    				sales_Quotations::logInfo("Добавяне на ред към автоматично създадена оферта към запитване", $quoteId);
    			}
    			
    			// Активиране на офертата
    			$qRec = (object)array('id' => $quoteId, 'state' => 'active');
    			cls::get('sales_Quotations')->invoke('BeforeActivation', array($qRec));
    			sales_Quotations::save($qRec, 'state');
    			sales_Quotations::logInfo("Активиране на автоматично създадена оферта към запитване", $quoteId);
    		}
    		
    		core_Users::cancelSystemUser();
    	}
    }
    
    
    /**
     * Създаване на артикул от запитване
     * 
     * @param stdClass $marketingRec - запитване
     * @param core_ObjectReference $Cover - корица
     * @param core_ObjectReference $document - референция към обекта
     * @param int - ид на създадения артикул
     */
    private function createProduct($marketingRec, $Cover, $document)
    {
		$Driver = $document->getDriver();
    	if(!$Driver) return;
    	
    	// Може ли да се намери дефолтната цена за артикула
    	if($Driver->canAutoCalcPrimeCost($marketingRec) !== TRUE) {
    		marketing_Inquiries2::logDebug("Не може да се създава артикул от запитването, защото драйвера не връща цена", $marketingRec->id);
    		return;
    	}
    	
    	$Products = cls::get('cat_Products');
    	$form = $Products->getForm();
    	$form->method = 'POST';
    	$form->rec->innerClass = $Driver->getClassId();
    	$form->rec->proto = $marketingRec->proto;
    	$form->rec->originId = $marketingRec->containerId;
    	$form->rec->name =  $marketingRec->title;
    	
    	// Полето за ид не е тихо за да не се обърка и да инпутва ид-то на крон процеса
    	$idField = $form->getField('id');
    	unset($idField->silent);
    	
    	$isSystemUser = core_Users::isSystemUser();
    	if ($isSystemUser) {
    		core_Users::cancelSystemUser();
    	}
    	core_Users::sudo($marketingRec->createdBy);
    	
    	$data = (object)array('form' => &$form);
    	$Products->invoke('AfterPrepareEditForm', array($data, $data));
    	$clone = clone $form->rec;
    	
    	$arr = $popArray = array();
    	foreach ((array)$clone as $k => $v){
    		if($k == 'groups'){
    			$v = type_Keylist::toArray($v);
    		}
    		
    		$arr[$k] = $v;
    		$popArray[$k] = $k;
    	}
    	
    	// За всеки случай не се пушват допълнителните параметри, защото са много големи
    	unset($arr['_params']);
    	unset($popArray['_params']);
    	
    	Request::push($arr);
    	$form->cmd = 'save';
    	
    	// Ид-то не трябва да се инпутва, защото ще вземе ид-то на крон процеса и ще се обърка
    	$fields = $form->selectFields();
    	unset($fields['id']);
    	$form->input(implode(',', array_keys($fields)));
    	
    	$Products->invoke('AfterInputEditForm', array($form));
    	core_Users::exitSudo();
    	if ($isSystemUser) {
    		core_Users::forceSystemUser();
    	}
    	
    	// Попване на пушнатите стойности, за да няма объркване при следваща автоматизация
    	if(is_array($popArray)){
    		foreach ($popArray as $popVar){
    			core_Request::pop($popVar);
    		}
    	}
    	
    	if(!($form->isSubmitted() && !$form->gotErrors())) return;
    	
    	$rec = $form->rec;
    	$productId = $Products->save($rec);
    	doc_HiddenContainers::showOrHideDocument($rec->containerId, TRUE, FALSE, $marketingRec->createdBy);
    	
    	return $productId;
    }
}