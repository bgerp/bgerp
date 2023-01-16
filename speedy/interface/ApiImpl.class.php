<?php


/**
 * Драйвер за връзка с API на Speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 * @title  Speedy API
 *
 * @since     v 0.1
 */
class speedy_interface_ApiImpl extends core_BaseClass
{
    /**
     * Роли по дефолт, които изисква драйвера
     */
    public $requireRoles = 'ceo,speedy';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_CourierApiIntf';


    /**
     * Заглавие
     */
    public $title = 'Speedy API';


    /**
     * Заглавие на  бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnCaption = 'Speedy';


    /**
     * Иконка за бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnIcon = 'img/16/speedy.png';


    /**
     * Коментар към връзката на прикачения файл
     */
    public $billOfLadingComment = 'Товарителница (Speedy)';


    /**
     * Какъв е ключа на потребителския кеш
     *
     * @param int $folderId
     *
     * @return string $key
     */
    private static function getUserDataCacheKey($folderId)
    {
        $userName = speedy_Setup::get('DEFAULT_ACCOUNT_USERNAME');
        $cu = core_Users::getCurrent('id', false);
        $key = "speedy_{$cu}_{$userName}";

        return $key;
    }


    /**
     * Модифициране на формата за създаване на товарителница към документ
     *
     * @param core_Mvc $mvc   - Документ
     * @param stdClass $rec   - Запис на документ
     * @param core_Form $form - Форма за създаване на товарителница
     * @return void
     */
    public function addFieldToBillOfLadingForm($mvc, $rec, &$form)
    {
        $cacheArr = core_Permanent::get(self::getUserDataCacheKey($rec->folderId));
        $formRec = &$form->rec;

        $form->class = 'speedyBillOfLading';
        $form->title = 'Попълване на товарителница за Speedy към|* ' . $mvc->getFormTitleLink($rec);

        $form->FLD('accountName', 'varchar', 'caption=Подател->Акаунт');
        $form->setReadOnly('accountName', speedy_Setup::get('DEFAULT_ACCOUNT_USERNAME'));

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
        $form->FLD('date', 'date', 'caption=Описание на пратката->Изпращане на,mandatory');
        $form->FLD('payer', 'enum(sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Описание на пратката->Платец,mandatory,silent,removeAndRefreshForm=thirdPayerRefId');
        $form->FLD('thirdPayerRefId', 'int', 'caption=Описание на пратката->Платец Офис,input=none');
        $form->FLD('payerPackaging', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец опаковка,mandatory');
        $form->FLD('isDocuments', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Документи,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer|palletCount,maxRadio=2');
        $form->FLD('palletCount', 'int(min=0,max=10)', 'caption=Описание на пратката->Бр. пакети');
        $form->FLD("parcelInfo", "table(columns=width|depth|height|weight,captions=Ширина [см]|Дълбочина [см]|Височина [см]|Тегло [кг],validate=speedy_interface_ApiImpl::validatePallets)", 'caption=Описание на пратката->Описание,after=palletCount');
        $form->FLD('content', 'varchar', 'caption=Описание на пратката->Съдържание,mandatory,recently');
        $form->FLD('packaging', 'varchar', 'caption=Описание на пратката->Опаковка,mandatory,recently');
        $form->FLD('exciseGoods', 'set(yes=Декларирам че не изпращам акцизна стока с неплатен акциз!)', 'caption=Описание на пратката->Акциз');
        $form->FLD('totalWeight', 'double(min=0)', 'caption=Описание на пратката->Общо тегло,unit=кг');
        $form->FLD('isPaletize', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Палетизиране,maxRadio=2');
        $form->FLD('amountCODBase', 'double(min=0)', 'caption=Описание на пратката->Наложен платеж,unit=BGN,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('codType', 'set(post=Като паричен превод,including=Вкл. цената на куриерска услуга в НП,cardPaymentAllowed=Разрешено плащане на НП с карта)', 'caption=Описание на пратката->Вид,after=amountCODBase,input=none');
        $form->FLD('amountInsurance', 'double', 'caption=Описание на пратката->Обявена стойност,unit=BGN,silent,removeAndRefreshForm=insurancePayer|isFragile');
        $form->FLD('insurancePayer', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец обявена ст.,input=none');
        $form->FLD('isFragile', 'enum(no=Не,yes=Да)', 'caption=Описание на пратката->Чупливост,input=none,maxRadio=2');
        $form->FLD('options', 'enum(no=Няма,open=Отваряне,test=Тест)', 'caption=Описание на пратката->Преди получаване/плащане,silent,removeAndRefreshForm=returnServiceId|returnPayer,maxRadio=3');
        $form->FLD('returnServiceId', 'varchar', 'caption=Описание на пратката->Услуга за връщане,input=none,after=options');
        $form->FLD('returnPayer', 'enum(same=Както куриерската услуга,sender=1.Подател,receiver=2.Получател)', 'caption=Описание на пратката->Платец на връщането,input=none,after=returnServiceId');
        $form->FLD('backRequest', 'set(document=Документи,receipt=Разписка)', 'caption=Заявка за обратни документи->Избор');
        $form->FLD('wrappingReturnServiceId', 'enum(601=Европалет,605=Нестандартен палет)', 'caption=Заявка за обратен амбалаж->Палет,autohide');
        $form->FLD('wrappingReturnQuantity', 'int(min=0)', 'caption=Заявка за обратен амбалаж->К-во,autohide,inlineTo=wrappingReturnServiceId');
        $form->FLD('returnShipmentWrappingServiceId', 'varchar', 'caption=Заявка за обратна пратка->Услуга,autohide');
        $form->FLD('returnShipmentParcelCount', 'int(min=0)', 'caption=Заявка за обратна пратка->Брой пакети,autohide');
        $form->FLD('returnShipmentAmountInsurance', 'double(min=0)', 'caption=Заявка за обратна пратка->Обявена стойност,autohide,unit=BGN');
        $form->FLD('returnShipmentIsFragile', 'enum(no=Не,yes=Да)', 'caption=Заявка за обратна пратка->Чупливост,autohide,maxRadio=2');
        $form->FLD('pdfPrinterType', 'enum(A4=A4, A6=A6, A4_4xA6=A4_4xA6)', 'caption=Печат на PDF->Принтер,autohide');

        $dateOptions = array();
        $cDate = dt::today();
        foreach (range(0, 9) as $i){
            $cDate = dt::addDays($i, $cDate, false);
            $dateOptions[$cDate] = dt::mysql2verbal($cDate, 'd.m.Y');
        }
        $form->setOptions('date', $dateOptions);
        $form->setDefault('date', dt::today());
        $form->setDefault('exciseGoods', 'yes');

        // Зареждане на наличните локации на изпращача
        $senderObjects = speedy_Adapter::getSenderClientOptions();
        $form->FLD('senderClientId', 'varchar', 'after=senderName,caption=Подател->Обект,silent,removeAndRefreshForm=serviceId');
        $form->setOptions('senderClientId', $senderObjects);
        if(!countR($senderObjects)){
            $form->setReadOnly('senderClientId');
            $form->setError('senderClientId', 'Няма налични обекти');
        } else {

            // Избиране на запомнения в сесията обект или първия наличен
            if(array_key_exists($cacheArr['senderClientId'], $senderObjects)){
                $form->setDefault('senderClientId', $cacheArr['senderClientId']);
            }
            $form->setDefault('senderClientId', key($senderObjects));
        }

        $Cover = doc_Folders::getCover($rec->folderId);
        $isPrivatePerson = ($Cover->haveInterface('crm_PersonAccRegIntf')) ? 'yes' : 'no';
        $form->setDefault('isPrivatePerson', $isPrivatePerson);
        $form->setDefault('isDocuments', 'no');
        $form->setDefault('isPaletize', 'no');
        $form->setDefault('palletCount', 1);
        $form->setFieldTypeParams('parcelInfo', array('width_sgt' => '80=80,100=100,120=120,150=150,175=175,200=200', 'depth_sgt' => '60=60,120=120'));
        $form->input(null, 'silent');

        // Ако има документи се скриват определени полета
        if($formRec->isDocuments == 'yes'){
            $form->setField('amountInsurance', 'input=none');
            $form->setField('isFragile', 'input=none');
            $form->setField('insurancePayer', 'input=none');
            $form->setField('palletCount', 'input=hidden');
        }

        $logisticData = $mvc->getLogisticData($rec);
        $logisticCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $toPerson = null;

        // Ако документа е бърза продажба
        if($mvc instanceof sales_Sales){
            $paymentType = $rec->paymentMethodId;
            $amountCod = $rec->amountDeal;

            // и условието на доставка е до офис на спиди - попълва се то
            if($rec->deliveryTermId){
                if($DeliveryCalc = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId)){
                    if($form->cmd != 'refresh' && $form->cmd != 'save' && $DeliveryCalc->class instanceof speedy_interface_DeliveryToOffice){
                        $officeNum = speedy_Offices::fetchField($rec->deliveryData['officeId'], 'num');
                        $form->setDefault('receiverSpeedyOffice', $officeNum);
                    }
                }
            }

        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
            if($firstDocument->isInstanceOf('sales_Sales')){
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
                $amountCod = $rec->amountDelivered;
            }
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

        if($formRec->payer == 'third'){
            $form->setField('thirdPayerRefId', 'input');
        }

        if($formRec->isPrivatePerson == 'yes'){
            $form->setDefault('receiverName', $toPerson);
            $form->setField('receiverPerson', 'input=none');
        } else {
            $form->setDefault('receiverName', $logisticData['toCompany']);
            $form->setDefault('receiverPerson', $toPerson);
        }

        if(isset($formRec->receiverSpeedyOffice)){
            foreach (array('receiverCountryId', 'receiverPlace', 'receiverAddress', 'receiverPCode', 'receiverBlock', 'receiverEntrance', 'receiverFloor', 'receiverApp', 'receiverNotes') as $addressField){
                $form->setField($addressField, 'input=none');
            }
        } else {
            foreach (array('receiverCountryId', 'receiverPlace', 'receiverAddress', 'receiverPCode') as $addressField){
                $form->setField($addressField, 'mandatory');
            }
        }

        if(isset($paymentType) && (cond_PaymentMethods::isCOD($paymentType) || cond_PaymentMethods::fetchField($paymentType, 'type') == 'postal')){
            $form->setDefault('amountCODBase', round($amountCod, 2));
        }

        $form->setSuggestions('amountCODBase', array('' => '', "{$amountCod}" => $amountCod));
        if($formRec->amountCODBase){
            $form->setDefault('isFragile', 'no');
            $form->setField('codType', 'input');
            $form->setDefault('codType', 'post,cardPaymentAllowed');
            $form->setSuggestions('amountInsurance', array('' => '', "{$amountCod}" => $amountCod));
        }
        $form->setDefault('returnShipmentIsFragile', 'no');

        if(isset($formRec->amountInsurance)){
            $form->setField('isFragile', 'input');
            $form->setField('insurancePayer', 'input');
            $form->setDefault('insurancePayer', 'same');
        }

        $form->setDefault('options', 'no');
        if($formRec->isPrivatePerson == 'yes'){
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

        if(!isset($formRec->receiverSpeedyOffice)){
            $form->setDefault('receiverCountryId', drdata_Countries::getIdByName($logisticCountryId));
            if($formRec->receiverCountryId == $logisticCountryId){
                $form->setDefault('receiverPlace', $logisticData['toPlace']);
                $form->setDefault('receiverAddress', $logisticData['toAddress']);
                $form->setDefault('receiverPCode', $logisticData['toPCode']);
            }
        }

        $serviceOptions = array();
        if((isset($formRec->receiverCountryId) && !empty($formRec->receiverPCode)) || !empty($formRec->receiverSpeedyOffice)){
            try{
                $serviceOptions = speedy_Adapter::getServiceOptions($formRec->senderClientId, $formRec->receiverCountryId, $formRec->receiverPCode, $formRec->receiverSpeedyOffice, $formRec->isPrivatePerson);
            } catch(core_exception_Expect $e){
                $serviceOptions = array();
            }
        }

        $form->setDefault('pdfPrinterType', $cacheArr['pdfPrinterType']);

        if(countR($serviceOptions)){
            $form->setOptions('service', $serviceOptions);
            if(array_key_exists($cacheArr['service'], $serviceOptions)){
                $form->setDefault('service', $cacheArr['service']);
            }
            $form->setDefault('service', key($serviceOptions));
            $form->input('service', 'silent');
            $form->setOptions('returnShipmentWrappingServiceId', array('' => '') + $serviceOptions);
        } else {
            $form->setError('service', 'Няма налична услуга за доставка');
            $form->rec->service = null;
            $form->setReadOnly('service');
            $form->setReadOnly('returnShipmentWrappingServiceId');
        }
        $form->input('service', 'silent');

        if(!empty($formRec->options)){
            $form->setField('returnServiceId', 'input');
            $form->setOptions('returnServiceId', array('same' => 'Както куриерската услуга') + $serviceOptions);
            $form->setField('returnPayer', 'input');
        }

        if($formRec->payer == 'third'){
            $form->setField('thirdPayerRefId', 'input');
            $form->setOptions('thirdPayerRefId', $senderObjects);
            $form->setDefault('thirdPayerRefId', $rec->senderClientId);
        }
    }


    /**
     * Инпут на формата за изпращане на товарителница
     *
     * @param core_Mvc $mvc         - Документ
     * @param stdClass $documentRec - Запис на документ
     * @param core_Form $form       - Форма за създаване на товарителница
     * @return void
     */
    public function inputBillOfLadingForm($mvc, $documentRec, &$form)
    {
        if($form->isSubmitted()) {
            $rec = $form->rec;

            if(empty($rec->receiverSpeedyOffice) && (mb_strlen($rec->receiverAddress) < 5 || is_numeric($rec->receiverAddress))){
                $form->setError('receiverAddress', 'Адреса трябва да е поне от 5 символа и да съдържа буква');
            }

            if($rec->isFragile == 'yes' && empty($rec->amountInsurance)){
                $form->setError('amountInsurance,isFragile', 'Чупливата папка, трябва да има обявена стойност');
            }

            if($rec->isDocuments == 'yes' && !empty($rec->amountInsurance)){
                $form->setError('isDocuments,amountInsurance', 'Документите не може да имат обявена стойност');
            }

            if($rec->isDocuments == 'yes'){
                if($rec->isPaletize == 'yes'){
                    $form->setError('isDocuments,isPaletize', 'Документите не могат да са на палети');
                }
            }

            if(isset($rec->amountInsurance) && $rec->totalWeight > 32){
                $form->setError('amountInsurance,totalWeight', 'Не може да има обявена стойност, на пратки с тегло над 32 кг');
            }

            $parcelInfo = type_Table::toArray($rec->parcelInfo);
            $parcelCount = countR($parcelInfo);
            $parcelCalcWeight = arr::sumValuesArray($parcelInfo, 'weight');

            if($parcelCount && !empty($rec->palletCount)){
                if($parcelCount != $rec->palletCount){
                    $form->setError('parcelInfo,palletCount', 'Има разминаване между броя на палетите');
                }
            }

            if(empty($parcelCalcWeight) && empty($rec->totalWeight)){
                $form->setError('totalWeight,parcelInfo', 'Задължително е да има тегло');
            }
        }
    }


    /**
     * Подготовка на данните от формата във формат подходящ за API-то
     *
     * @param stdClass $formRec - запис от формата за създаване на товарителница
     * @param string $action - дали директно да се създаде товарителница или само да се калкулира цена
     * @return array $res (@see speedy_Adapter::requestShipment)
     */
    private function prepareBolData($formRec, $action = 'shipment')
    {
        $senderArr = array(
            'clientId' => $formRec->senderClientId,
            'phone1' => array('number' => $formRec->senderPhone),
            'contactName' => $formRec->senderName,);

        $recipientArr = array(
            'privatePerson' => ($formRec->isPrivatePerson == 'yes'),
            'clientName' => $formRec->receiverName,
            'contactName' => $formRec->receiverPerson,
            'phone1' => array('number' => $formRec->receiverPhone),
        );

        if(isset($formRec->receiverSpeedyOffice)){
            $recipientArr['pickupOfficeId'] = $formRec->receiverSpeedyOffice;
        } else {
            $theirCountryId = speedy_Adapter::getCountryId($formRec->receiverCountryId);
            if($action == 'calculate'){
                $sites = speedy_Adapter::getSites($theirCountryId, $formRec->receiverPCode);
                $recipientArr['addressLocation'] = array('countryId' => $theirCountryId, 'siteId' => key($sites));
            } else {
                $recipientAddressArray = array('countryId' => $theirCountryId);
                foreach (array('postCode' => 'receiverPCode', 'blockNo' => 'receiverBlock', 'entranceNo' => 'receiverEntrance', 'floorNo' => 'receiverFloor', 'apartmentNo' => 'apartmentNo') as $theirFld => $ourFld){
                    if(!empty($formRec->{$ourFld})){
                        $recipientAddressArray[$theirFld] = $formRec->{$ourFld};
                    }
                }
                $addressNote = $formRec->receiverAddress . (!empty($formRec->receiverNotes) ? ", {$formRec->receiverNotes}" : "");
                if(!empty($addressNote)){
                    $recipientAddressArray['addressNote'] = $addressNote;
                }
                $recipientArr['address'] = $recipientAddressArray;
            }
        }

        $serviceArray = array('pickupDate' => $formRec->date, 'autoAdjustPickupDate' => true);
        if($action == 'calculate'){
            $serviceArray['serviceIds'] = array($formRec->service);
        } else {
            $serviceArray['serviceId'] = $formRec->service;
        }

        $serviceArray['additionalServices'] = array();
        if(!empty($formRec->amountCODBase)){
            $serviceArray['additionalServices']['cod'] = array('amount' => $formRec->amountCODBase, 'currencyCode' => 'BGN');
            $codOptions = type_Set::toArray($formRec->codType);

            if(isset($codOptions['post'])){
                $serviceArray['additionalServices']['cod']['processingType'] = 'POSTAL_MONEY_TRANSFER';
            } else {
                $serviceArray['additionalServices']['cod']['processingType'] = 'CASH';
            }
            if(isset($codOptions['including'])){
                $serviceArray['additionalServices']['cod']['includeShippingPrice'] = true;
            }
            $serviceArray['additionalServices']['cod']['cardPaymentForbidden'] = isset($codOptions['cardPaymentAllowed']);
        }

        if(!empty($formRec->amountInsurance)){
            $serviceArray['additionalServices']['declaredValue'] = array();
            $serviceArray['additionalServices']['declaredValue']['amount'] = $formRec->amountInsurance;

            if($formRec->isFragile == 'no'){
                $serviceArray['additionalServices']['declaredValue']['fragile'] = ($formRec->isFragile == 'yes');
            }
        }

        $serviceArray['additionalServices']['obpd'] = array();
        if($formRec->options == 'test'){
            $serviceArray['additionalServices']['obpd']['option'] = 'TEST';
        } elseif($formRec->options == 'open'){
            $serviceArray['additionalServices']['obpd']['option'] = 'OPEN';
        }

        if(isset($formRec->returnServiceId)){
            $returnServiceId = ($formRec->returnServiceId == 'same') ? $formRec->service : $formRec->returnServiceId;
            $serviceArray['additionalServices']['obpd']['returnShipmentServiceId'] = $returnServiceId;
        }

        $payer = ($formRec->payer == 'sender') ? 'SENDER' : (($formRec->payer == 'receiver') ? 'RECIPIENT' : 'THIRD_PARTY');
        if(isset($formRec->returnPayer)){
            $returnPayer = ($formRec->returnPayer == 'same') ? $payer : (($formRec->returnPayer == 'sender') ? 'SENDER' : (($formRec->returnPayer == 'receiver') ? 'RECIPIENT' : 'THIRD_PARTY'));
            $serviceArray['additionalServices']['obpd']['returnShipmentPayer'] = $returnPayer;
        }

        if(!empty($formRec->floorNum)){
            $serviceArray['additionalServices']['deliveryToFloor'] = $formRec->floorNum;
        }

        $paymentArr = array('courierServicePayer' => $payer);
        if(isset($formRec->insurancePayer)){
            $paymentArr['declaredValuePayer'] = ($formRec->insurancePayer == 'same') ? $payer : (($formRec->insurancePayer == 'sender') ? 'SENDER' : (($formRec->insurancePayer == 'receiver') ? 'RECIPIENT' : 'THIRD_PARTY'));
        }
        if(isset($formRec->payerPackaging)){
            $paymentArr['packagePayer'] = ($formRec->payerPackaging == 'same') ? $payer : (($formRec->payerPackaging == 'sender') ? 'SENDER' : (($formRec->payerPackaging == 'receiver') ? 'RECIPIENT' : 'THIRD_PARTY'));
        }

        $contentArr = array('package' => $formRec->packaging, 'contents' => $formRec->content, 'parcelsCount' => $formRec->palletCount, 'totalWeight' => $formRec->totalWeight);
        $contentArr['documents'] = ($formRec->isDocuments == 'yes');
        $contentArr['palletized'] = ($formRec->isPaletize == 'yes');
        $contentArr['exciseGoods'] = ($formRec->exciseGoods == 'yes');

        $parcels = type_Table::toArray($formRec->parcelInfo);
        $seqNo = 1;
        foreach ($parcels as $parcel){
            $contentArr['parcels'][] = array('seqNo' => $seqNo, 'weight' => $parcel->weight, 'size' => array('width' => $parcel->width, 'height' => $parcel->height, 'depth' => $parcel->depth));
            $seqNo++;
        }

        $backRequest = type_Set::toArray($formRec->backRequest);
        if(isset($backRequest['document'])){
            $serviceArray['additionalServices']['returns']['rod'] = array('enabled' => true);
        }
        if(isset($backRequest['receipt'])){
            $serviceArray['additionalServices']['returns']['returnReceipt'] = array('enabled' => true);
        }
        if(!empty($formRec->wrappingReturnQuantity)){
            $serviceArray['additionalServices']['returns']['rop']['pallets'][] = array('serviceId' => $formRec->wrappingReturnServiceId,
                                                                                     'parcelsCount' => $formRec->wrappingReturnQuantity);
        }

        if(!empty($formRec->returnShipmentWrappingServiceId)){
            $serviceArray['additionalServices']['returns']['swap'] = array('serviceId' => $formRec->returnShipmentWrappingServiceId,
                                                                           'parcelsCount' => $formRec->returnShipmentParcelCount,
                                                                           'declaredValue' => $formRec->returnShipmentAmountInsurance,
                                                                           'fragile' => ($formRec->returnShipmentIsFragile == 'yes'),
            );
        }

        $res = array('sender'    => $senderArr,
                     'recipient' => $recipientArr,
                     'service'   => $serviceArray,
                     'content'   => $contentArr,
                     'payment'   => $paymentArr);

        return $res;
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
        $Int = core_Type::getByName('int');

        foreach($TableArr as $i => $obj){
            foreach (array('weight', 'depth', 'height', 'width') as $field){
                $type = ($field == 'weight') ? $Double : $Int;
                if(!empty($obj->{$field})){
                    if(!$type->fromVerbal($obj->{$field}) || $obj->{$field} < 0){
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
     * Калкулира цената на товарителницата
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return core_ET|null $tpl     - хтмл с рендиране на информацията за плащането
     * @throws core_exception_Expect
     */
    public function calculateShipmentTpl($mvc, $documentRec, &$form)
    {
        $preparedBolParams = static::prepareBolData($form->rec, 'calculate');

        try{
            $res = speedy_Adapter::calculateShipment($preparedBolParams);
        } catch(core_exception_Expect $e){
            $form->info = "<div style='color:red;font-weight:bold;'>" . tr('Цената не може да се калкулира|*!') . "<br>" . tr($e->getMessage()) . "</div>";
            return;
        }

        $priceObj = $res->calculations[0];

        // Ако има грешка при калкулацията - визуализира се!
        if(!empty($priceObj->error)){
            $form->setError('service', $priceObj->error->message);
            return;
        }

        $Double = core_Type::getByName('double(decimals=2)');
        $row = new stdClass();
        $row->deadlineDelivery = dt::mysql2verbal($priceObj->deliveryDeadline, 'd.m.Y H:i:s');

        $priceFields = array(
            'net' => $priceObj->price->details->netAmount->amount,
            'addrPickupSurcharge' => $priceObj->price->details->addressPickupSurcharge->amount,
            'addrDeliverySurcharge' => $priceObj->price->details->addressDeliverySurcharge->amount,
            'discPcntFixed' => $priceObj->price->details->fixedDiscount->amount,
            'discPcntAdditional' => $priceObj->price->details->additionalDiscount->amount,
            'pcntFuelSurcharge' => $priceObj->price->details->fuelSurcharge->amount,
            'nonStdDeliveryDateSurcharge' => $priceObj->price->details->nonStandardDeliveryDateSurcharge->amount,
            'tro' => $priceObj->price->details->loadUnload->amount,
            'islandSurcharge' => $priceObj->price->details->islandSurcharge->amount,
            'testBeforePayment' => $priceObj->price->details->optionsBeforePaymentSurcharge->amount,
            'tollSurcharge' => $priceObj->price->details->tollSurcharge->amount,
            'heavyPackageFee' => $priceObj->price->details->heavyParcelSurcharge->amount,
            'codPremium' => $priceObj->price->details->codPremium->amount,
            'insurancePremium' => $priceObj->price->details->insurancePremium->amount,
            'totalNoVat' => $priceObj->price->amount,
            'vat' => $priceObj->price->vat,
            'total' => $priceObj->price->total,
        );

        foreach ($priceFields as $fld => $fldVal){
            $valueVerbal = $Double->toVerbal($fldVal);
            $valueVerbal = ht::styleNumber($valueVerbal, $fldVal);
            $row->{$fld} = $valueVerbal;
        }

        $row->net = currency_Currencies::decorate($row->net);
        $row->total = currency_Currencies::decorate($row->total);
        $row->totalNoVat = currency_Currencies::decorate($row->totalNoVat);

        $tpl = getTplFromFile('speedy/tpl/CalculatedAmounts.shtml');
        $tpl->placeObject($row);

        return $tpl;
    }


    /**
     * Връща файл хендлъра на генерираната товарителница след Request-а
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return string|null $fh       - хендлър на готовата товарителница
     * @throws core_exception_Expect
     */
    public function getRequestedShipmentFh($mvc, $documentRec, &$form)
    {
        // Подготовка на данните за товарителницата
        $preparedBolParams = static::prepareBolData($form->rec);

        try{
            $res = speedy_Adapter::requestShipment($preparedBolParams);
        } catch(core_exception_Expect $e){
            $form->setError('service', $e->getMessage());
            return;
        }

        if(empty($res->id)){
            $form->setError('service', 'Товарителницата не можа да се генерира');
        }

        if(!$form->gotErrors()){

            // Ако е генерирана успешно, прави се опит за разпечатването ѝ
            $parcelIds = array();
            array_walk($res->parcels, function($a) use (&$parcelIds) {$parcelIds[] = $a->id;});
            $fh = speedy_Adapter::printWaybillPdf($parcelIds, $form->rec->pdfPrinterType);

            if(empty($fh)){
                $form->setError('service', 'Проблем при генериране на PDF на товарителница');
            }

            if(!$form->gotErrors()){

                // Ако е разпечатана записва се в помощния модел
                $bolRec = (object)array('containerId' => $documentRec->containerId, 'number' => $parcelIds[0], 'takingDate' => $res->pickupDate, 'data' => $preparedBolParams);
                $bolRec->file = $fh;
                speedy_BillOfLadings::save($bolRec);

                // Кеш на избраните полета от формата
                $cacheArr = array('senderClientId' => $form->rec->senderClientId, 'service' => $form->rec->service, 'pdfPrinterType' => $form->rec->pdfPrinterType);
                core_Permanent::set(self::getUserDataCacheKey($documentRec->folderId), $cacheArr, core_Permanent::FOREVER_VALUE);

                return $fh;
            }
        }

        return null;
    }


    /**
     * Може ли потребителя да създава товарителница от документа
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param int|null $userId - ид на потребител (null за текущия)
     * @return bool
     */
    public function canRequestBillOfLading($mvc, $id, $userId = null)
    {
        $res = haveRole($this->requireRoles, $userId);
        if($res){
            $loginData = speedy_Adapter::getLoginData($userId);
            if(empty($loginData['userName']) || empty($loginData['password'])){
                $res = false;
            }
        }

        return $res;
    }


    /**
     * След подготовка на формата за товарителница
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return core_ET|null $tpl     - хтмл с рендиране на информацията за плащането
     * @throws core_exception_Expect
     */
    public function afterPrepareBillOfLadingForm($mvc, $documentRec, $form, &$tpl)
    {

    }


    /**
     * Може ли потребителя да създава товарителница от документа
     *
     * @param core_Mvc $mvc
     * @param int|stdClass $id
     * @return core_ET|null
     */
    public function getDefaultEmailBody($mvc, $id)
    {
        if($mvc instanceof store_ShipmentOrders) {
            $rec = $mvc->fetchRec($id);
            if($foundRec = self::getLastBolRec($rec->containerId)){

                $urlTpl = new core_ET(speedy_Setup::get('TRACKING_URL'));
                $urlTpl->replace($foundRec->number, 'NUM');
                $url = $urlTpl->getContent();

                $date = dt::mysql2verbal($foundRec->takingDate, 'd.m.Y');
                $bolTpl = new ET(tr("|*\n|Вашата пратка е подготвена за изпращане на|* [#date#] |с товарителница|* [#number#].\n|Може да проследите получаването ѝ от тук|*: [#URL#]"));
                $bolTpl->replace($url, 'URL');
                $bolTpl->replace($foundRec->number, 'number');
                $bolTpl->replace($date, 'date');

                return $bolTpl;
            }
        }

        return null;
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
}