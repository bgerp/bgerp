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
    	try{
    		$productId = $this->createProduct($marketingRec, $Cover, $document);
    	} catch(core_exception_Expect $e){
    		$productId = NULL;
    		reportException($e);
    	}
    	
    	if(!$productId){
    		marketing_Inquiries2::logDebug("Проблем при опит за създаване на автоматичен артикул към запитване", $marketingRec->id);
    		return;
    	} else {
    		marketing_Inquiries2::logInfo("Успешно създаден артикул от автоматизация '{$event}'", $marketingRec->id);
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
    		
    		if(haveRole('partner', $marketingRec->createdBy)){
    			$profileRec = crm_Profiles::getProfile($marketingRec->createdBy);
    			if(!empty($profileRec->buzEmail)){
    				$emails = type_Emails::toArray($profileRec->buzEmail);
    				$fields['email'] = $emails[0];
    			}
    			
    			if(!empty($profileRec->buzTel)){
    				$tels = drdata_PhoneType::toArray($profileRec->buzTel);
    				$fields['tel'] = $tels[0]->number;
    			}
    		}
    		
    		if(!empty($marketingRec->deliveryAdress)){
    			$fields['deliveryAdress'] = $marketingRec->deliveryAdress;
    		}
    		
    		$quoteId = sales_Quotations::createNewDraft($Cover->getInstance()->getClassId(), $Cover->that, NULL, $fields);
    		sales_Quotations::logWrite("Създаване от запитване", $quoteId);
    		
    		if(empty($quoteId)){
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
                if(haveRole('partner', $marketingRec->createdBy)) {
                    $qRec = (object)array('id' => $quoteId, 'state' => 'active');
                    cls::get('sales_Quotations')->invoke('BeforeActivation', array($qRec));
                    $qRec->_isActivated = TRUE;
                    sales_Quotations::save($qRec, 'state,modifiedOn,modifiedBy,activatedOn,date');
                    sales_Quotations::logWrite("Активиране на автоматично създадена оферта към запитване", $quoteId);
                }
    		}
    		
    		core_Users::cancelSystemUser();
    	}
    	
    	doc_Threads::doUpdateThread($marketingRec->threadId);
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
    	$form->rec->innerClass = $Driver->getClassId();
        
        $iForm = marketing_Inquiries2::getForm();

    	$form->rec->originId  = $marketingRec->containerId;
        $form->rec->threadId  = $marketingRec->threadId;
        $form->rec->proto     = $marketingRec->proto;
    	$form->rec->name      = $marketingRec->title;
    	$form->rec->driverRec = $marketingRec->title;

    	$Driver->addFields($form);
        foreach($form->fields as $name => $fld) {
            if(isset($marketingRec->{$name}) && !$iForm->fields[$name]) {
                $form->rec->{$name} = $marketingRec->{$name};
            }
        }
        
        // Определяме мярката за продукта, ако липсва
        if(!$form->rec->measureId) {
            // Ако има дефолтна мярка, избираме я
    	    if(is_object($Driver) && $Driver->getDefaultUomId()){
    		    $form->rec->measureId = $Driver->getDefaultUomId();
    	    } elseif($defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID')){
    			$form->rec->measureId = $defMeasure;
    		}
        }

    	$rec = $form->rec; 
    	$productId = $Products->save($rec);
    	$Products->logWrite("Създаване от запитване", $productId);
    	doc_HiddenContainers::showOrHideDocument($rec->containerId, TRUE, FALSE, $marketingRec->createdBy);
    	
    	return $productId;
    }
}