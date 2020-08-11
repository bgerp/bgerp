<?php


/**
 * Клас 'speedy_plg_BillOfLading' за изпращане на товарителница към SPEEDY
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_plg_BillOfLading extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canMakebilloflading, 'debug');//speedy,ceo
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        // Бутон за Товарителница
        if ($mvc->haveRightFor('makebilloflading', $rec)) {
            $data->toolbar->addBtn('Speedy', array($mvc, 'makebilloflading', 'documentId' => $rec->id, 'ret_url' => true), "id=btnSpeedy", 'ef_icon = img/16/tick-circle-frame.png,title=Изпращане на товарителница към Speedy');
        }
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'makebilloflading') {
            $mvc->requireRightFor('makebilloflading');
            expect($id = Request::get('documentId', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('makebilloflading', $rec);
            
            // Адаптер към библиотеката на speedy
            $adapter = new speedy_Adapter();
            $connectResult = $adapter->connect();
            if($connectResult->success !== true){
                
                // Има ли връзка с тяхната услуга
                followRetUrl(null, $connectResult->errorMsg, 'error');
            }
            
            // Подготовка на формата
            $form = self::getBillOfLadingForm($mvc, $rec, $adapter);
            $form->FLD('senderAddress', 'varchar', 'after=senderName,caption=Данни за подател->Адрес,hint=Адресът е настроен в профика в Speedy');
            $senderAddress = $adapter->getSenderAddress();
            $form->setReadOnly('senderAddress', $senderAddress);
            
            $form->input();
            
            if($form->isSubmitted()){
                $fRec = $form->rec;
                
                if(empty($fRec->receiverSpeedyOffice) && (mb_strlen($fRec->receiverAddress) < 5 || is_numeric($fRec->receiverAddress))){
                    $form->setError('receiverAddress', 'Адреса трябва да е поне от 5 символа и да съдържа буква');
                }
                
                if($fRec->isFragile == 'yes' && empty($fRec->amountInsurance)){
                    $form->setError('amountInsurance,isFragile', 'Чупливата папка, трябва да има обявена стойност');
                }
                
                if($fRec->isDocuments == 'yes' && !empty($fRec->amountInsurance)){
                    $form->setError('isDocuments,amountInsurance', 'Документите не може да имат обявена стойност');
                }
                
                if($fRec->isDocuments == 'yes' && $fRec->isPaletize == 'yes'){
                    $form->setError('isDocuments,isPaletize', 'Документите не могат да са на палети');
                }
                
                if(isset($fRec->amountInsurance) && $fRec->totalWeight > 32){
                    $form->setError('amountInsurance,totalWeight', 'Не може да има обявена стойност, на пратки с тегло над 32 кг');
                }
                
                if(!$form->gotErrors()){
                    
                    // Опит за създаване на товарителница
                    try{
                        $bolId = $adapter->getBol($form->rec);
                    } catch(ServerException $e){
                        $mvc->logErr("Проблем при генериране на товарителница", $id);
                        $fields = null;
                        $msg = $adapter->handleException($e, $fields);
                        $form->setError($fields, $msg);
                    }
                    
                    // Записване на товарителницата като PDF
                    if(!$form->gotErrors()){
                        try{
                            $bolFh = $adapter->getBolPdf($bolId);
                            $fileId = fileman::fetchByFh($bolFh, 'id');
                            doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', 'Товарителница');
                            
                        } catch(ServerException $e){
                            reportException($e);
                            $mvc->logErr("Проблем при генериране на PDF на товарителница", $id);
                            
                            core_Statuses::newStatus('Проблем при генериране на PDF на товарителница', 'error');
                        }
                    }
                }
                
                if(!$form->gotErrors()){
                    followRetUrl(null, "Успешно генерирана товарителница|*: №{$bolId}");
                }
            }
            
            $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Изпращане на товарителницата');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Записваме, че потребителя е разглеждал този списък
            $mvc->logInfo('Разглеждане на формата за генериране на товарителница');
            
            $res = $mvc->renderWrapping($form->renderHtml());
          
            
            return false;
        }
    }
    
    
    /**
     * Подготвя формата за товарителницата
     * 
     * @param core_Mvc $mvc
     * @param stdClass $documentRec
     * @param speedy_Adapter $adapter
     * 
     * @return core_Form
     */
    private static function getBillOfLadingForm($mvc, $documentRec, $adapter)
    {
        $form = cls::get('core_Form');
        $rec = &$form->rec;
        $form->title = 'Попълване на товарителница за Speedy към|* ' . $mvc->getFormTitleLink($documentRec);
        
        $form->FLD('senderPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Данни за подател->Телефон,mandatory');
        $form->FLD('senderName', 'varchar', 'caption=Данни за подател->Фирма/Име,mandatory');
        $form->FLD('senderNotes', 'text(rows=2)', 'caption=Данни за подател->Уточнение');
        
        $form->FLD('isPrivatePerson', 'enum(no=Фирма,yes=Частно лице)', 'caption=Данни за получател->Получател,silent,removeAndRefreshForm=receiverPerson|receiverName,maxRadio=2,mandatory');
        $form->FLD('receiverName', 'varchar', 'caption=Данни за получател->Фирма/Име,mandatory');
        $form->FLD('receiverPerson', 'varchar', 'caption=Данни за получател->Лице за конт,mandatory');
        $form->FLD('receiverPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Данни за получател->Телефон,mandatory');
        
        $form->FLD('receiverSpeedyOffice', 'customKey(mvc=speedy_Offices,key=num,select=extName,allowEmpty)', 'caption=Адрес на получател->Офис на Спиди,removeAndRefreshForm=service|date|receiverCountryId|receiverPlace|receiverAddress|receiverPCode,silent');
        $form->FLD('receiverCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес на получател->Държава,removeAndRefreshForm=service|date|receiverPlace|receiverPCode|receiverAddress,silent,mandatory');
        $form->FLD('receiverPCode', 'varchar', 'caption=Адрес на получател->Пощ. код,removeAndRefreshForm=service,silent,mandatory');
        $form->FLD('receiverPlace', 'varchar', 'caption=Адрес на получател->Нас. място,removeAndRefreshForm=service,silent,mandatory');
        $form->FLD('receiverAddress', 'varchar', 'caption=Адрес на получател->Адрес,mandatory');
        $form->FLD('receiverBlock', 'varchar', 'caption=Адрес на получател->Блок');
        $form->FLD('receiverEntrance', 'varchar', 'caption=Адрес на получател->Вход');
        $form->FLD('receiverFloor', 'int', 'caption=Адрес на получател->Етаж');
        $form->FLD('receiverApp', 'varchar', 'caption=Адрес на получател->Апартамент');
        $form->FLD('receiverNotes', 'text(rows=2)', 'caption=Адрес на получател->Уточнение');
        $form->FLD('floorNum', 'int', 'caption=Адрес на получател->Качване до етаж');
        
        $form->FLD('service', 'varchar', 'caption=Параметри на пратката 1.->Услуга,mandatory,removeAndRefreshForm=date,silent');
        $form->FLD('date', 'varchar', 'caption=Параметри на пратката 1.->Изпращане на,mandatory');
        
        $form->FLD('payer', 'enum(sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 1.->Платец,mandatory');
        $form->FLD('payerPackaging', 'enum(same=Както куриерска услуга,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 1.->Платец опаковка,mandatory');
        
        $form->FLD('isDocuments', 'set(yes=Документи)', 'caption=Параметри на пратката 2.->,inlineTo=payerPackaging,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('palletCount', 'int(min=0,Max=10)', 'caption=Параметри на пратката 2.->Бр. пакети,mandatory');
        $form->FLD('content', 'text(rows=2)', 'caption=Параметри на пратката 2.->Съдържание,mandatory,recently');
        $form->FLD('packaging', 'varchar', 'caption=Параметри на пратката 2.->Опаковка,mandatory,recently');
        $form->FLD('totalWeight', 'double(min=0,max=50)', 'caption=Параметри на пратката 2.->Общо тегло,unit=кг,mandatory');
        $form->FLD('isPaletize', 'set(yes=Да)', 'caption=Параметри на пратката 2.->Палетизирана,after=amountInsurance');
        
        $form->FLD('amountCODBase', 'double(min=0)', 'caption=Параметри на пратката 3.->Наложен платеж,unit=BGN,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('codType', 'set(post=Като паричен превод,including=Вкл. цената на куриерска услуга в НП)', 'caption=Параметри на пратката 3.->Вид,after=amountCODBase,input=none');
        
        $form->FLD('amountInsurance', 'double', 'caption=Параметри на пратката 3.->Обявена стойност,unit=BGN,silent,removeAndRefreshForm=insurancePayer|isFragile');
        $form->FLD('insurancePayer', 'enum(same=Както куриерска услуга,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 3.->Платец обявена ст.,input=none');
        $form->FLD('isFragile', 'set(yes=Да)', 'caption=Параметри на пратката 3.->Чупливост,after=amountInsurance,input=none');
        
        $form->FLD('options', 'enum(no=Няма,open=Отвори преди плащане/получаване,test=Тествай преди плащане/получаване)', 'caption=Параметри на пратката 3.->Опции,silent,removeAndRefreshForm=returnServiceId|returnPayer,maxRadio=3');
        $form->FLD('returnServiceId', 'varchar', 'caption=Параметри на пратката 3.->Услуга за Връщане,input=none,after=options');
        $form->FLD('returnPayer', 'enum(same=Както куриерска услуга,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 3.->Платец на Връщането,input=none,after=returnServiceId');
        
        $Cover = doc_Folders::getCover($documentRec->folderId);
        $isPrivatePerson = ($Cover->haveInterface('crm_PersonAccRegIntf')) ? 'yes' : 'no';
        $form->setDefault('isPrivatePerson', $isPrivatePerson);
        
        $form->input(null, 'silent');
        
        if($rec->isDocuments == 'yes'){
            $form->setField('amountInsurance', 'input=none');
            $form->setField('isFragile', 'input=none');
            $form->setField('insurancePayer', 'input=none');
        }
        
        $logisticData = $mvc->getLogisticData($documentRec);
        $toPerson = null;
       
        if($mvc instanceof sales_Sales){
            $paymentType = $documentRec->paymentMethodId;
            $amountCod = $documentRec->amountDeal;
            if($documentRec->deliveryTermId){
                if($DeliveryCalc = cond_DeliveryTerms::getTransportCalculator($documentRec->deliveryTermId)){
                    if($form->cmd != 'refresh' && $DeliveryCalc->class instanceof speedy_interface_DeliveryToOffice){
                        $officeNum = speedy_Offices::fetchField($documentRec->deliveryData['officeId'], 'num');
                        $form->setDefault('receiverSpeedyOffice', $officeNum);
                    }
                }
            }
            
            if($cartRec = eshop_Carts::fetch("#saleId = {$documentRec->id}", 'personNames,tel')){
                $toPerson = $cartRec->personNam;
                $form->setDefault('receiverPhone', $cartRec->tel);
            } elseif($documentRec->deliveryLocationId){
                $locationRec = crm_Locations::fetch($documentRec->deliveryLocationId, 'mol,tel');
                if(!empty($locationRec->mol)){
                    $toPerson = $locationRec->mol;
                }
                
                if(!empty($locationRec->tel)){
                    $form->setDefault('receiverPhone', $locationRec->tel);
                }
            }
            
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($documentRec->threadId);
            $paymentType = $firstDocument->fetchField('paymentMethodId');
            $amountCod = ($documentRec->chargeVat == 'separate') ? $documentRec->amountDelivered + $documentRec->amountDeliveredVat : $documentRec->amountDelivered;
        
            if($documentRec->locationId){
                $locationRec = crm_Locations::fetch($documentRec->locationId, 'mol,tel');
                if(!empty($locationRec->mol)){
                    $toPerson = $locationRec->mol;
                }
                if(!empty($locationRec->tel)){
                    $form->setDefault('receiverPhone', $locationRec->tel);
                }
            } elseif(!empty($documentRec->tel)){
                $toPerson =  $locationRec->person;
                $form->setDefault('receiverPhone', $documentRec->tel);
            }
        }
        
        if(empty($toPerson) && $Cover->haveInterface('crm_PersonAccRegIntf')){
            $toPerson = $Cover->fetchField('name');
            $form->setDefault('receiverPhone', $Cover->fetchField('tel'));
        }
        
        if($rec->isPrivatePerson == 'yes'){
            $form->setDefault('receiverName', $toPerson);
            $form->setField('receiverPerson', 'input=none');
        } else {
            $form->setDefault('receiverName', $logisticData['toCompany']);
            $form->setDefault('receiverPerson', $toPerson);
        }
        
        if(isset($rec->receiverSpeedyOffice)){
            foreach (array('receiverCountryId', 'receiverPlace', 'receiverAddress', 'receiverPCode', 'receiverBlock', 'receiverEntrance', 'receiverFloor', 'receiverApp') as $addressField){
                $form->setField($addressField, 'input=none');
            }
        }
        
        if(isset($paymentType)){
            if(cond_PaymentMethods::isCOD($paymentType)){
                $form->setDefault('amountCODBase', round($amountCod, 2));
            }
        }
        
        if($rec->amountCODBase){
            $form->setField('codType', 'input');
        }
        
        if(isset($rec->amountInsurance)){
            $form->setField('isFragile', 'input');
            $form->setField('insurancePayer', 'input');
            $form->setDefault('insurancePayer', 'same');
        }
       
        
        
        
        
        if($form->cmd != 'refresh'){
            $Cover = doc_Folders::getCover($documentRec->folderId);
            if($Cover->haveInterface('crm_PersonAccRegIntf')){
                bp($toPerson);
                $form->setDefault('receiverName', $toPerson);
                $form->setDefault('isPrivatePerson', 'yes');
            } else{
                $form->setDefault('isPrivatePerson', 'no');
                $form->setDefault('receiverName', $logisticData['toCompany']);
                $form->setDefault('receiverPerson', $toPerson);
            }
        }
        
        
        
        
        
        $form->setDefault('options', 'no');
        if($rec->isPrivatePerson == 'yes'){
            $form->setField('receiverPerson', 'input=none');
        }
        
        $receiverCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $form->setDefault('palletCount', 1);
        $form->setDefault('payerPackaging', 'same');
        
        $profile = crm_Profiles::getProfile();
        $phones = drdata_PhoneType::toArray($profile->tel);
        $phone = $phones[0]->original;
        $form->setDefault('senderName', $profile->name);
        $form->setDefault('senderPhone', $phone);
        $form->setDefault('declare', 'yes');
        $form->setDefault('totalWeight', $logisticData['totalWeight']);
        
        if(!isset($rec->receiverSpeedyOffice)){
            $form->setDefault('receiverCountryId', drdata_Countries::getIdByName($logisticData['toCountry']));
            
            if($rec->receiverCountryId == $receiverCountryId){
                $form->setDefault('receiverPlace', $logisticData['toPlace']);
                $form->setDefault('receiverAddress', $logisticData['toAddress']);
                $form->setDefault('receiverPCode', $logisticData['toPCode']);
            }
        }
        
        $serviceOptions = array();
        if((isset($form->rec->receiverCountryId) && !empty($form->rec->receiverPCode)) || !empty($form->rec->receiverSpeedyOffice)){
           try{
                $serviceOptions = $adapter->getServicesBySites($form->rec->receiverCountryId, $form->rec->receiverPlace, $form->rec->receiverPCode, $form->rec->receiverSpeedyOffice);
           } catch(ServerException $e){
               $fields = null;
               $msg = $adapter->handleException($e, $fields);
               $form->setError($fields, $msg);
           }
        }
        
        if(countR($serviceOptions)){
            $form->setOptions('service', $serviceOptions);
            $form->setDefault('service', key($serviceOptions));
            $form->input('service', 'silent');
        } else {
            $form->setError('service', 'Няма налична услуга за доставка');
            $form->rec->service = null;
            $form->setReadOnly('service');
        }
        $form->input('service', 'silent');
        
        if(!isset($form->rec->service)){
            $form->setField('date', 'input=none');
        } else {
            try{
                $takingDates =  $adapter->getAllowedTakingDays($form->rec->service);
                $form->setOptions('date', $takingDates);
                $form->setDefault('date', key($takingDates));
                
            } catch(ServerException $e){
                $serviceOptions = array();
                $msg = $adapter->handleException($e);
                $form->setError('receiverCountryId,receiverPCode', $msg);
            }
        }
        
        if(!empty($rec->options)){
            $form->setField('returnServiceId', 'input');
            $form->setOptions('returnServiceId', array('same' => 'Както куриерска услуга') + $serviceOptions);
            $form->setField('returnPayer', 'input');
        }
        
        return $form;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'makebilloflading' && isset($rec)){
            if($rec->state != 'active'){
                $requiredRoles = 'no_one';
            }
        }
    }
}