<?php


/**
 * Клас за автоматично създаване на оферта от запитване
 *
 * @category  bgerp
 * @package   auto
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class auto_handler_CreateQuotationFromInquiry
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'auto_AutomationIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Автоматично създаване на оферта от запитване';
    
    
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
        if (cat_Products::fetchField("#originId = {$marketingRec->containerId}")) {
            marketing_Inquiries2::logDebug('Не може да се създаде автоматично артикул към запитването защото има вече такъв', $marketingRec->id);
            
            return;
        }
        
        // Опит за създаване на артикул от запитване
        try {
            $productId = $this->createProduct($marketingRec, $Cover, $document);
        } catch (core_exception_Expect $e) {
            $productId = null;
            reportException($e);
        }
        
        if (!$productId) {
            marketing_Inquiries2::logDebug('Проблем при опит за създаване на автоматичен артикул към запитване', $marketingRec->id);
            
            return;
        }
        marketing_Inquiries2::logInfo("Успешно създаден артикул от автоматизация '{$event}'", $marketingRec->id);
        
        
        // Имали подадени количества
        $quantities = array();
        foreach (range(1, 3) as $i) {
            $q = $marketingRec->{"quantity{$i}"};
            if (empty($q)) {
                continue;
            }
            $quantities[$q] = $q;
        }
        
        // За всяко
        if (countR($quantities)) {
            
            // Създаване на оферта към артикула
            core_Users::forceSystemUser();
            $fields = array('originId' => cat_Products::fetchField($productId, 'containerId'));
            
            if (haveRole('partner', $marketingRec->createdBy)) {
                $profileRec = crm_Profiles::getProfile($marketingRec->createdBy);
                if (!empty($profileRec->buzEmail)) {
                    $emails = type_Emails::toArray($profileRec->buzEmail);
                    $fields['email'] = $emails[0];
                }
                
                if (!empty($profileRec->buzTel)) {
                    $tels = drdata_PhoneType::toArray($profileRec->buzTel);
                    if (is_object($tels[0])) {
                        $fields['tel'] = '+' . $tels[0]->countryCode . $tels[0]->areaCode . $tels[0]->number;
                    }
                }
            }
            
            // Ако има адрес за доставка и има друго условие за доставка за тази държава се избира то
            if (!empty($marketingRec->deliveryAdress)) {
                $fields['deliveryAdress'] = $marketingRec->deliveryAdress;
                $place = drdata_Address::parsePlace($marketingRec->deliveryAdress);
                if(isset($place->countryId)){
                    $cCountryId = $Cover->getContragentData()->country;
                    if($cCountryId != $place->countryId){
                        $deliveryTermId = cond_Countries::getParameterByCountryId($place->countryId, 'deliveryTermSale');
                        if($deliveryTermId){
                            $fields['deliveryTermId'] = $deliveryTermId;
                        }
                    }
                }
            }
            
            $quoteId = sales_Quotations::createNewDraft($Cover->getInstance()->getClassId(), $Cover->that, null, $fields);
            sales_Quotations::logWrite('Създаване на автоматична оферта запитване', $quoteId);
            
            if (empty($quoteId)) {
                cat_Products::logDebug('Проблем при опит за създаване на автоматична оферта към артикул', $productId);
                
                return;
            }
            sales_Quotations::logInfo('Успешно създаване на автоматична оферта към артикул от запитване', $quoteId);

            // Добавяне на редовете на офертата
            foreach ($quantities as $q) {
                sales_Quotations::addRow($quoteId, $productId, $q);
                sales_Quotations::logInfo('Добавяне на ред към автоматично създадена оферта от запитване', $quoteId);
            }

            $isPartner = haveRole('partner', $marketingRec->createdBy);
            $activate = $isPartner;

            // Дали може да се генерира текст за клиентска оферта?
            $lang = ($marketingRec->_domainId) ? cms_Domains::fetchField($marketingRec->_domainId, 'lang') : null;
            $Driver = cat_Products::getDriver($productId);
            $body = $Driver->getQuotationEmailText($productId, $quoteId, $lang);
            if(!empty($body)){
                $activate = true;
            }

            // Активиране на офертата
            if ($activate) {
                $qRec = (object) array('id' => $quoteId, 'state' => 'active');
                cls::get('sales_Quotations')->invoke('BeforeActivation', array($qRec));
                $qRec->_isActivated = true;
                sales_Quotations::save($qRec, 'state,modifiedOn,modifiedBy,activatedOn,date');
                sales_Quotations::logWrite('Активиране на автоматично създадена оферта към запитване', $quoteId);
            }

            // Ако има данни за изпращане на клиентската оферта
            if(!empty($body) && !$isPartner && !empty($marketingRec->_domainId)){
                $settings = cms_Domains::getSettings($marketingRec->_domainId);
                if(!empty($settings->inboxId)){

                    // Изпращане на имейл за офертата
                    $body = core_Type::getByName('richtext')->fromVerbal($body);
                    $this->sendEmail($body, $quoteId, $marketingRec, $settings->inboxId, $lang);
                }
            }
            
            core_Users::cancelSystemUser();
        }
        
        doc_Threads::doUpdateThread($marketingRec->threadId);
    }
    
    
    /**
     * Създаване на артикул от запитване
     *
     * @param stdClass             $marketingRec - запитване
     * @param core_ObjectReference $Cover        - корица
     * @param core_ObjectReference $document     - референция към обекта
     * @param int - ид на създадения артикул
     */
    private function createProduct($marketingRec, $Cover, $document)
    {
        $Driver = $document->getDriver();
        if (!$Driver) {
            
            return;
        }
        
        // Може ли да се намери дефолтната цена за артикула
        if ($Driver->canAutoCalcPrimeCost($marketingRec) !== true) {
            marketing_Inquiries2::logDebug('Не може да се създава артикул от запитването, защото драйвера не връща цена', $marketingRec->id);
            
            return;
        }
        
        $Products = cls::get('cat_Products');
        $form = $Products->getForm();
        $form->rec->innerClass = $Driver->getClassId();
        
        $iForm = marketing_Inquiries2::getForm();
        
        $form->rec->originId = $marketingRec->containerId;
        $form->rec->threadId = $marketingRec->threadId;
        $form->rec->proto = $marketingRec->proto;
        if(strpos($marketingRec->title, '||') !== false){
            list($form->rec->name, $form->rec->nameEn) = explode('||', $marketingRec->title);
        } else {
            $form->rec->name = $marketingRec->title;
        }
        
        $Driver->addFields($form);
        foreach ($form->fields as $name => $fld) {
            if (isset($marketingRec->{$name}) && !$iForm->fields[$name]) {
                $form->rec->{$name} = $marketingRec->{$name};
            }
        }
        
        // Определяме мярката за продукта, ако липсва
        if (!$form->rec->measureId) {
            // Ако има дефолтна мярка, избираме я
            $driverMeasureId = $Driver->getDefaultUomId($form->rec);
            if (is_object($Driver) && isset($driverMeasureId)) {
                $form->rec->measureId = $Driver->getDefaultUomId($driverMeasureId);
            } elseif ($defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID')) {
                $form->rec->measureId = $defMeasure;
            }
        }
        
        $rec = $form->rec;
        $productId = $Products->save($rec);
        $Products->logWrite('Създаване от запитване', $productId);
        doc_HiddenContainers::showOrHideDocument($rec->containerId, true, false, $marketingRec->createdBy);
        
        // Намираме се в шътдаун. Шътдауна на cat_Products е минал, и ако очакваме да има нещо за правене от текущите действия,
        // то трябва да го викаме ръчно
        $Products->on_ShutDown($Products);
        
        return $productId;
    }


    /**
     * Изпраща имейл към офертата
     *
     * @param $body          - тяло
     * @param $quotationId   - ид на оферта
     * @param $marketingRec  - запис на запитване
     * @param $inboxId       - ид на кутия
     * @param $lang          - език
     */
    private function sendEmail($body, $quotationId, $marketingRec, $inboxId, $lang)
    {
        $quotationRec = sales_Quotations::fetch($quotationId);

        // Подготовка на имейла
        $emailRec = (object) array('subject' => tr('Оферта за поръчка') . " #Q{$quotationRec->id}",
                                   'body' => $body,
                                   'folderId' => $quotationRec->folderId,
                                   'originId' => $quotationRec->containerId,
                                   'threadId' => $quotationRec->threadId,
                                   'state' => 'active',
                                   'email' => $marketingRec->email, 'recipient' => $marketingRec->personNames);

        // Активиране на изходящия имейл
        $cu = core_Users::getCurrent('id', false);
        Mode::set('isSystemCanSingle', true);
        email_Outgoings::save($emailRec);

        email_Outgoings::logWrite('Създаване от автоматична оферта', $emailRec->id, 360, $cu);
        cls::get('email_Outgoings')->invoke('AfterActivation', array(&$emailRec));
        email_Outgoings::logWrite('Активиране', $emailRec->id, 360, $cu);

        // Изпращане на имейла
        $options = (object) array('encoding' => 'utf-8', 'boxFrom' => $inboxId, 'emailsTo' => $emailRec->email);

        $attachedDocs = array();
        $documents = doc_RichTextPlg::getAttachedDocs("#" . sales_Quotations::getHandle($quotationId));
        $documents = array_keys($documents);
        foreach ($documents as $name) {
            $attachedDocs[$name] = "{$name}.pdf";
        }
        $options->documentsSet = implode(',', $attachedDocs);

        $cu = core_Users::getCurrent();
        email_Outgoings::send($emailRec, $options, $lang);
        email_Outgoings::logWrite('Изпращане на автоматичен имейл за оферта', $emailRec->id, 360, $cu);
        Mode::set('isSystemCanSingle', false);
    }
}
