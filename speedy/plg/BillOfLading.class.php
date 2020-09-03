<?php


/**
 * Клас 'speedy_plg_BillOfLading' за изпращане на товарителница към Speedy
 *
 *
 * @category  bgerp
 * @package   speedy
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
        setIfNot($mvc->canMakebilloflading, 'speedy,ceo');
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        // Бутон за Товарителница
        if ($mvc->haveRightFor('makebilloflading', $rec)) {
            $data->toolbar->addBtn('Speedy', array($mvc, 'makebilloflading', 'documentId' => $rec->id, 'ret_url' => true), "id=btnSpeedy", 'ef_icon = img/16/speedy.png,title=Изпращане на товарителница към Speedy');
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
            
            $cacheArr = core_Permanent::get(self::getUserDataCacheKey($rec->folderId, $adapter));
            
            // Подготовка на формата
            $form = self::getBillOfLadingForm($mvc, $rec, $adapter, $cacheArr);
            
            $senderObjects = $adapter->getSenderObjects();
            $form->FLD('senderClientId', 'varchar', 'after=senderName,caption=Подател->Обект');
            $form->setOptions('senderClientId', $senderObjects);
            if(array_key_exists($cacheArr['senderClientId'], $senderObjects)){
                $form->setDefault('senderClientId', $cacheArr['senderClientId']);
            }
            $form->setDefault('senderClientId', $adapter->getDefaultClientId());
            
            if($form->rec->payer == 'third'){
                $form->setField('thirdPayerRefId', 'input');
                $form->setOptions('thirdPayerRefId', $senderObjects);
                $form->setDefault('thirdPayerRefId', $rec->senderClientId);
            }
            
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
                
                if($fRec->isDocuments == 'yes'){
                    if($fRec->isPaletize == 'yes'){
                        $form->setError('isDocuments,isPaletize', 'Документите не могат да са на палети');
                    }
                }
                
                if(isset($fRec->amountInsurance) && $fRec->totalWeight > 32){
                    $form->setError('amountInsurance,totalWeight', 'Не може да има обявена стойност, на пратки с тегло над 32 кг');
                }
                
                $parcelInfo = type_Table::toArray($fRec->parcelInfo);
                $parcelCount = countR($parcelInfo);
                $parcelCalcWeight = arr::sumValuesArray($parcelInfo, 'weight');
                
                if($parcelCount && !empty($fRec->palletCount)){
                    if($parcelCount != $fRec->palletCount){
                        $form->setError('parcelInfo,palletCount', 'Има разминаване между броя на палетите');
                    }
                }
                
                if(empty($parcelCalcWeight) && empty($fRec->totalWeight)){
                    $form->setError('totalWeight,parcelInfo', 'Задължително е да има тегло');
                }
                
                if(!$form->gotErrors()){
                    
                    // Ако само ще се калкулира
                    if($form->cmd == 'calc'){
                        try{
                            $tpl = $adapter->calculate($form->rec);
                            $form->info = $tpl;
                            core_Statuses::newStatus('Цената е изчислена');
                        } catch(ServerException $e){
                            $fields = $isHandled = null;
                            $msg = $adapter->handleException($e, $fields, $isHandled);
                            $form->setError($fields, $msg);
                            
                            if(!$isHandled){
                                reportException($e);
                                $mvc->logErr("Проблем при изчисление на цената на товарителницата", $id);
                                $mvc->logErr($e->getMessage(), $id);
                            }
                        }
                    } else {
                        $picking = null;
                        
                        // Опит за създаване на товарителница
                        try{
                            $bolId = $adapter->getBol($form->rec, $picking);
                        } catch(ServerException $e){
                            $isHandled = $fields = null;
                            $msg = $adapter->handleException($e, $fields, $isHandled);
                            $form->setError($fields, $msg);
                            
                            if(!$isHandled){
                                reportException($e);
                                $mvc->logErr("Проблем при генериране на товарителница", $id);
                                $mvc->logErr($e->getMessage(), $id);
                            }
                        }
                        
                        // Записване на товарителницата като PDF, ако е създадеба
                        if(!$form->gotErrors() && !empty($bolId)){
                            $bolRec = (object)array('containerId' => $rec->containerId, 'number' => $bolId, 'takingDate' => $picking->getTakingDate());
                            
                            try{
                                $bolFh = $adapter->getBolPdf($bolId);
                                $fileId = fileman::fetchByFh($bolFh, 'id');
                                doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', 'Товарителница');
                                $bolRec->file = $bolFh;
                                
                            } catch(ServerException $e){
                                reportException($e);
                                $mvc->logErr("Проблем при генериране на PDF на товарителница", $id);
                                $mvc->logErr($e->getMessage(), $id);
                                core_Statuses::newStatus('Проблем при генериране на PDF на товарителница', 'error');
                            }
                        }
                       
                        if(!$form->gotErrors() && !empty($bolId)){
                            $mvc->logWrite("Генерирана товарителница на Speedy", $id);
                            
                            // Кеш на последно избраните стойностти
                            $cacheArr = array('senderClientId' => $fRec->senderClientId, 'service' => $fRec->service);
                            core_Permanent::set(self::getUserDataCacheKey($rec->folderId, $adapter), $cacheArr, 4320);
                            
                            if(is_object($bolRec)){
                                speedy_BillOfLadings::save($bolRec);
                            }
                            
                            followRetUrl(null, "Успешно генерирана товарителница|*: №{$bolId}");
                        }
                    }
                }
            }
            
            $form->toolbar->addSbBtn('Изпращане', 'save', 'ef_icon = img/16/speedy.png, title = Изпращане на товарителницата,id=save');
            $form->toolbar->addSbBtn('Изчисли', 'calc', 'ef_icon = img/16/calculator.png, title = Изчисляване на на товарителницата');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Записваме, че потребителя е разглеждал този списък
            $mvc->logInfo('Форма за генериране на товарителница на Speedy');
            
            $res = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($res, $form);
            
            return false;
        }
    }
    
    
    /**
     * Какъв е ключа на потребителския кеш
     * 
     * @param int $folderId
     * @param speedy_Adapter $adapter
     * 
     * @return string $key
     */
    private static function getUserDataCacheKey($folderId, speedy_Adapter $adapter)
    {
        $cu = core_Users::getCurrent('id', false);
        $key = "speedy_{$folderId}_{$cu}_{$adapter->getAccountName()}";
       
        return $key;
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
    private static function getBillOfLadingForm($mvc, $documentRec, $adapter, $cacheArr)
    {
        $form = cls::get('core_Form');
        $form->class = 'speedyBillOfLading';
        
        $rec = &$form->rec;
        $form->title = 'Попълване на товарителница за Speedy към|* ' . $mvc->getFormTitleLink($documentRec);
        
        $form->FLD('senderPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Подател->Телефон,mandatory');
        $form->FLD('senderName', 'varchar', 'caption=Подател->Фирма/Име,mandatory');
        $form->FLD('senderNotes', 'text(rows=2)', 'caption=Подател->Уточнение');
        
        $form->FLD('isPrivatePerson', 'enum(no=Фирма,yes=Частно лице)', 'caption=Получател->Получател,silent,removeAndRefreshForm=receiverPerson|receiverName,maxRadio=2,mandatory');
        $form->FLD('receiverName', 'varchar', 'caption=Получател->Фирма/Име,mandatory');
        $form->FLD('receiverPerson', 'varchar', 'caption=Получател->Лице за контакт,mandatory');
        $form->FLD('receiverPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Получател->Телефон,mandatory');
        
        $form->FLD('receiverSpeedyOffice', 'customKey(mvc=speedy_Offices,key=num,select=extName,allowEmpty)', 'caption=Адрес за доставка->Офис на Спиди,removeAndRefreshForm=service|date|receiverCountryId|receiverPlace|receiverAddress|receiverPCode,silent');
        $form->FLD('receiverCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава,removeAndRefreshForm=service|date|receiverPlace|receiverPCode|receiverAddress,silent');
        $form->FLD('receiverPCode', 'varchar', 'caption=Адрес за доставка->Пощ. код,removeAndRefreshForm=service,silent');
        $form->FLD('receiverPlace', 'varchar', 'caption=Адрес за доставка->Нас. място,removeAndRefreshForm=service,silent');
        $form->FLD('receiverAddress', 'varchar', 'caption=Адрес за доставка->Адрес');
        $form->FLD('receiverBlock', 'varchar', 'caption=Адрес за доставка->Блок');
        $form->FLD('receiverEntrance', 'varchar', 'caption=Адрес за доставка->Вход');
        $form->FLD('receiverFloor', 'int', 'caption=Адрес за доставка->Етаж');
        $form->FLD('receiverApp', 'varchar', 'caption=Адрес за доставка->Апартамент');
        $form->FLD('receiverNotes', 'text(rows=2)', 'caption=Адрес за доставка->Уточнение');
        $form->FLD('floorNum', 'int', 'caption=Адрес за доставка->Качване до етаж');
        
        $form->FLD('service', 'varchar', 'caption=Описание на пратката->Услуга,mandatory,removeAndRefreshForm=date,silent');
        $form->FLD('date', 'varchar', 'caption=Описание на пратката->Изпращане на,mandatory');
        
        $form->FLD('payer', 'enum(sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Описание на пратката->Платец,mandatory,silent,removeAndRefreshForm=thirdPayerRefId');
        $form->FLD('thirdPayerRefId', 'int', 'caption=Описание на пратката->Платец Офис,input=none');
        
        $form->FLD('payerPackaging', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец опаковка,mandatory');
        
        $form->FLD('isDocuments', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Документи,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer|palletCount,maxRadio=2');
        $form->FLD('palletCount', 'int(min=0,Max=10)', 'caption=Описание на пратката->Бр. пакети');
        $form->FLD("parcelInfo", "table(columns=width|depth|height|weight,captions=Ширина|Дълбочина|Височина|Тегло,validate=speedy_plg_BillOfLading::validatePallets)", 'caption=Описание на пратката->Палети,after=palletCount');
        $form->FLD('content', 'varchar', 'caption=Описание на пратката->Съдържание,mandatory,recently');
        $form->FLD('packaging', 'varchar', 'caption=Описание на пратката->Опаковка,mandatory,recently');
        $form->FLD('totalWeight', 'double(min=0)', 'caption=Описание на пратката->Общо тегло,unit=кг');
        $form->FLD('isPaletize', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Палетизиране,maxRadio=2');
        
        $form->FLD('amountCODBase', 'double(min=0)', 'caption=Описание на пратката->Наложен платеж,unit=BGN,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('codType', 'set(post=Като паричен превод,including=Вкл. цената на куриерска услуга в НП)', 'caption=Описание на пратката->Вид,after=amountCODBase,input=none');
        
        $form->FLD('amountInsurance', 'double', 'caption=Описание на пратката->Обявена стойност,unit=BGN,silent,removeAndRefreshForm=insurancePayer|isFragile');
        $form->FLD('insurancePayer', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец обявена ст.,input=none');
        $form->FLD('isFragile', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Чупливост,input=none,maxRadio=2');
        
        $form->FLD('options', 'enum(no=Няма,open=Отваряне,test=Тест)', 'caption=Описание на пратката->Преди получаване/плащане,silent,removeAndRefreshForm=returnServiceId|returnPayer,maxRadio=3');
        $form->FLD('returnServiceId', 'varchar', 'caption=Описание на пратката->Услуга за връщане,input=none,after=options');
        $form->FLD('returnPayer', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец на връщането,input=none,after=returnServiceId');
        $form->FLD('backRequest', 'set(document=Документи,receipt=Разписка)', 'caption=Заявка за обратни документи->Избор');
       
        $Cover = doc_Folders::getCover($documentRec->folderId);
        $isPrivatePerson = ($Cover->haveInterface('crm_PersonAccRegIntf')) ? 'yes' : 'no';
        $form->setDefault('isPrivatePerson', $isPrivatePerson);
        $form->setDefault('isDocuments', 'no');
        $form->setDefault('isPaletize', 'no');
        
        $form->input(null, 'silent');
        
        if($rec->isDocuments == 'yes'){
            $form->setField('amountInsurance', 'input=none');
            $form->setField('isFragile', 'input=none');
            $form->setField('insurancePayer', 'input=none');
            
            $form->setField('palletCount', 'input=hidden');
            $form->setDefault('palletCount', 1);
        }
        
        $logisticData = $mvc->getLogisticData($documentRec);
        $logisticCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $toPerson = null;
      
        if($mvc instanceof sales_Sales){
            $paymentType = $documentRec->paymentMethodId;
            $amountCod = $documentRec->amountDeal;
            
            if($documentRec->deliveryTermId){
                if($DeliveryCalc = cond_DeliveryTerms::getTransportCalculator($documentRec->deliveryTermId)){
                    if($form->cmd != 'refresh' && $form->cmd != 'save' && $DeliveryCalc->class instanceof speedy_interface_DeliveryToOffice){
                        $officeNum = speedy_Offices::fetchField($documentRec->deliveryData['officeId'], 'num');
                        $form->setDefault('receiverSpeedyOffice', $officeNum);
                    }
                }
            }
            
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($documentRec->threadId);
            $deliveryTermId = $firstDocument->fetchField('deliveryTermId');
            
            if($deliveryTermId && empty($documentRec->locationId) && empty($documentRec->tel)){
                if($DeliveryCalc = cond_DeliveryTerms::getTransportCalculator($deliveryTermId)){
                    if($form->cmd != 'refresh' && $form->cmd != 'save' && $DeliveryCalc->class instanceof speedy_interface_DeliveryToOffice){
                        $deliveryData = $firstDocument->fetchField('deliveryData');
                        $officeNum = speedy_Offices::fetchField($deliveryData['officeId'], 'num');
                        $form->setDefault('receiverSpeedyOffice', $officeNum);
                    }
                }
            }
            
            $paymentType = $firstDocument->fetchField('paymentMethodId');
            $amountCod = ($documentRec->chargeVat == 'separate') ? $documentRec->amountDelivered + $documentRec->amountDeliveredVat : $documentRec->amountDelivered;
        }
        
        $form->setDefault('receiverPhone', $logisticData['toPersonPhones']);
        $form->setDefault('receiverNotes', $logisticData['instructions']);
        $form->setDefault('receiverCountryId', $logisticCountryId);
        $toPerson = $logisticData['toPerson'];
        
        if($form->rec->receiverCountryId == $logisticCountryId){
            $form->setDefault('receiverPlace', $logisticData['toPlace']);
            $form->setDefault('receiverAddress', $logisticData['toAddress']);
            $form->setDefault('receiverPCode', $logisticData['toPCode']);
        }
        
        $amountCod = round($amountCod, 2);
        if(empty($toPerson) && $Cover->haveInterface('crm_PersonAccRegIntf')){
            $toPerson = $Cover->fetchField('name');
            $form->setDefault('receiverPhone', $Cover->fetchField('tel'));
        }
        
        if($rec->payer == 'third'){
            $form->setField('thirdPayerRefId', 'input');
        }
        
        
        if($rec->isPrivatePerson == 'yes'){
            $form->setDefault('receiverName', $toPerson);
            $form->setField('receiverPerson', 'input=none');
        } else {
            $form->setDefault('receiverName', $logisticData['toCompany']);
            $form->setDefault('receiverPerson', $toPerson);
        }
        
        if(isset($rec->receiverSpeedyOffice)){
            foreach (array('receiverCountryId', 'receiverPlace', 'receiverAddress', 'receiverPCode', 'receiverBlock', 'receiverEntrance', 'receiverFloor', 'receiverApp', 'receiverNotes') as $addressField){
                $form->setField($addressField, 'input=none');
            }
        } else {
            foreach (array('receiverCountryId', 'receiverPlace', 'receiverAddress', 'receiverPCode') as $addressField){
                $form->setField($addressField, 'mandatory');
            }
        }
        
        if(isset($paymentType) && cond_PaymentMethods::isCOD($paymentType)){
            $form->setDefault('amountCODBase', round($amountCod, 2));
        }
        
        $form->setSuggestions('amountCODBase', array('' => '', "{$amountCod}" => $amountCod));
        if($rec->amountCODBase){
            $form->setDefault('isFragile', 'no');
            $form->setField('codType', 'input');
            $form->setSuggestions('amountInsurance', array('' => '', "{$amountCod}" => $amountCod));
        }
        
        if(isset($rec->amountInsurance)){
            $form->setField('isFragile', 'input');
            $form->setField('insurancePayer', 'input');
            $form->setDefault('insurancePayer', 'same');
        }
        
        $form->setDefault('options', 'no');
        if($rec->isPrivatePerson == 'yes'){
            $form->setField('receiverPerson', 'input=none');
        }
        
        $form->setDefault('payerPackaging', 'same');
        $profile = crm_Profiles::getProfile();
        $phones = drdata_PhoneType::toArray($profile->tel);
        $phone = $phones[0]->original;
        $form->setDefault('senderName', $profile->name);
        $form->setDefault('senderPhone', $phone);
        $form->setDefault('declare', 'yes');
        $form->setDefault('totalWeight', $logisticData['totalWeight']);
        
        if(!isset($rec->receiverSpeedyOffice)){
            $form->setDefault('receiverCountryId', drdata_Countries::getIdByName($logisticCountryId));
            
            if($rec->receiverCountryId == $logisticCountryId){
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
               $isHandled = $errorFields = null;
               $msg = $adapter->handleException($e, $errorFields, $isHandled);
               $form->setError($errorFields, $msg);
               
               // хак да се покажат стойностите
               $fields = arr::make($errorFields, true);
               foreach ($fields as $fld){
                   Request::push(array($fld => $form->rec->{$fld}));
               }
               
               if(!$isHandled){
                   reportException($e);
                   $mvc->logErr($e->getMessage(), $form->rec->id);
               }
           }
        }
        
        if(countR($serviceOptions)){
            $form->setOptions('service', $serviceOptions);
            if(array_key_exists($cacheArr['service'], $serviceOptions)){
                $form->setDefault('service', $cacheArr['service']);
            }
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
                $isHandled = $errorFields = null;
                $serviceOptions = array();
                $msg = $adapter->handleException($e, $errorFields, $isHandled);
                $form->setError($errorFields, $msg);
                
                if(!$isHandled){
                    reportException($e);
                    $mvc->logErr($e->getMessage(), $form->rec->id);
                }
            }
        }
        
        if(!empty($rec->options)){
            $form->setField('returnServiceId', 'input');
            $form->setOptions('returnServiceId', array('same' => 'Както куриерската услуга') + $serviceOptions);
            $form->setField('returnPayer', 'input');
        }
        
        return $form;
    }
    
    
    /**
     * Дефолти от количката
     * 
     * @param core_Form $form
     * @param stdClass $cartRec
     * @return void
     */
    private static function setDefaultsFromCart($form, $cartRec)
    {
        $form->setDefault('receiverPhone', $cartRec->tel);
        $form->setDefault('receiverNotes', $cartRec->instruction);
        $form->setDefault('receiverCountryId', $cartRec->deliveryCountry);
        if($form->rec->receiverCountryId == $cartRec->deliveryCountry){
            $form->setDefault('receiverPlace', $cartRec->deliveryPlace);
            $form->setDefault('receiverAddress', $cartRec->deliveryAddress);
            $form->setDefault('receiverPCode', $cartRec->deliveryPCode);
        }
    }
    
    
    /**
     * Проверка на данните за палетите
     *
     * @param array     $tableData
     * @param core_Type $Type
     *
     * @return array
     */
    public static function validatePallets($tableData, $Type)
    {
        $res = $error = $errorFields = array();
        $TableArr = type_Table::toArray($tableData);
        
        $Double = core_Type::getByName('double');
       
        foreach($TableArr as $i => $obj){
            foreach (array('weight', 'depth', 'height', 'width') as $field){
                if(!empty($obj->{$field})){
                    if(!$Double->fromVerbal($obj->{$field}) || $obj->{$field} < 0){
                        $error[] = 'Невалидни числа';
                        $errorFields[$field][$i] = 'Невалидно число';
                    }
                }
            }
            
            if(empty($obj->weight)){
                $error['sizeError'] = 'Трябва да са въведени размерите';
                $errorFields['weight'][$i] = 'Трябва да е въведено тегло';
            }
            
            if(empty($obj->width)){
                $error['sizeError'] = 'Трябва да са въведени размерите';
                $errorFields['width'][$i] = 'Трябва да е въведена ширина';
            }
            
            if(empty($obj->depth)){
                $error['sizeError'] = 'Трябва да са въведени размерите';
                $errorFields['depth'][$i] = 'Трябва да е въведена дълбочина';
            }
            
            if(empty($obj->height)){
                $error['sizeError'] = 'Трябва да са въведени размерите';
                $errorFields['height'][$i] = 'Трябва да е въведена височина';
            }
        }
        
        if (countR($error)) {
            $error = implode('<li>', $error);
            $res['error'] = $error;
        }
        
        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }
        
        return $res;
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
            } else {
                if($mvc instanceof sales_Sales){
                    $actions = type_Set::toArray($rec->contoActions);
                    if (!isset($actions['ship'])) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     */
    public function on_AfterGetDefaultEmailBody($mvc, &$tpl, $id, $isForwarding = false)
    {
        if($mvc instanceof store_ShipmentOrders){
            $rec = $mvc->fetchRec($id);
           
            if($foundRec = self::getLastBolRec($rec->containerId)){
                $url = self::getTrackingUrl($foundRec->number);
                $date = dt::mysql2verbal($foundRec->takingDate, 'd.m.Y');
                $bolTpl = new ET(tr("|*\n|Вашата пратка е подготвена за изпращане на|* [#date#] |с товарителница|* [#number#].\n|Може да проследите получаването ѝ от тук|*: [#URL#]"));
                $bolTpl->replace($url, 'URL');
                $bolTpl->replace($foundRec->number, 'number');
                $bolTpl->replace($date, 'date');
                $tpl->append($bolTpl);
            }
        }
    }
    
    
    private static function getTrackingUrl($number)
    {
        $urlTpl = new core_ET(speedy_Setup::get('TRACKING_URL'));
        $urlTpl->replace($number, 'NUM');
        
        return $urlTpl->getContent();
    }
    
    
    /**
     * Връща коя е последната товарителница издадена към документа
     * 
     * @param int $containerId
     * 
     * @return stdClass|false
     */
    private static function getLastBolRec($containerId)
    {
        $spQuery = speedy_BillOfLadings::getQuery();
        $spQuery->where("#containerId = {$containerId}");
        $spQuery->orderBy('id', 'DESC');
        $spQuery->limit(1);
        
        return $spQuery->fetch();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if($mvc instanceof store_ShipmentOrders){
            if(isset($fields['-single'])){
                if($bolRec = self::getLastBolRec($rec->containerId)){
                    $bolId = ht::createLinkRef($bolRec->number, self::getTrackingUrl($bolRec->number));
                    $row->note .= tr("|* <span class='quiet'>|Товарителница|*:</span> {$bolId}");
                }
            }
        }
    }
}