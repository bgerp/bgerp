<?php


/**
 * Драйвер за връзка с API на CVC
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 * @title  CVC API
 *
 * @since     v 0.1
 */
class cvc_interface_CourierImpl extends core_Manager
{
    /**
     * Роли по дефолт, които изисква драйвера
     */
    public $requireRoles = 'ceo,cvc';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_CourierApiIntf';


    /**
     * Заглавие
     */
    public $title = 'CVC API';


    /**
     * Заглавие на  бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnCaption = 'CVC';


    /**
     * Иконка за бутон за създаване на товарителница
     */
    public $requestBillOfLadingBtnIcon = 'img/16/cvc.png';


    /**
     * Кой може да взима опциите от стринг
     */
    public $canGetplacesbystring = 'cvc,ceo';


    /**
     * Коментар към връзката на прикачения файл
     */
    public $billOfLadingComment = 'Товарителница (CVC)';


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
            $token = cvc_Setup::get('TOKEN', false, $userId);
            if(empty($token)){
                $res = false;
            } else {
                $senderId = cvc_Setup::get('SENDER_ID');
                if(empty($senderId)){
                    $res = false;
                }
            }
        }

        return $res;
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

        $form->class = 'cvcBillOfLading';
        $form->title = 'Попълване на товарителница за CVC към|* ' . $mvc->getFormTitleLink($rec);

        $form->FLD('parcelType', 'enum(parcel=Пакетна,pallet=Палетна,tires=Гуми)', 'caption=Тип на пратката,silent,removeAndRefreshForm=fixedTime,mandatory');
        $form->FLD('customerId', 'int','caption=Подател->Подател');

        $defaultParcelType = isset($cacheArr['parcelType']) ? $cacheArr['parcelType'] : 'parcel';
        $form->setDefault('parcelType', $defaultParcelType);
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);

        try{
            $senderOptions = cvc_Adapter::getSenderOptions();
            $form->setOptions('customerId', $senderOptions);
        } catch(core_exception_Expect $e){
        }

        $defaultCustomerId = isset($cacheArr['customerId']) ? $cacheArr['customerId'] : cvc_Setup::get('SENDER_ID');
        $form->setDefault('customerId', $defaultCustomerId);
        $form->setReadOnly('customerId');
        $form->FLD('senderName', 'varchar','caption=Подател->Лице за контакт,mandatory');
        $form->FLD('senderPhone', 'drdata_PhoneType(type=tel,unrecognized=error)','caption=Подател->Данни за връзка,placeholder=Телефон,class=w25,mandatory');
        $form->FLD('senderEmail', 'email','caption=Подател->-,inlineTo=senderPhone,placeholder=Имейл,class=w75');
        $form->FLD('senderDeliveryType', 'enum(address=Адрес,hub=Хъб)','caption=Приемане от->Избор,silent,removeAndRefreshForm=senderHubId|senderCountryId|senderOfficeId|senderPcode|senderPlace|senderAddress|senderAddressNum|senderEntrance|senderFloor|senderApp,maxRadio=2');
        $form->FLD('senderHubId', 'key(mvc=cvc_Hubs, select=name, allowEmpty)','caption=Приемане от->Хъб,input=none');
        if(is_array($cacheArr)){
            $form->setDefault('senderName', $cacheArr['senderName']);
            $form->setDefault('senderPhone', $cacheArr['senderPhone']);
            $form->setDefault('senderEmail', $cacheArr['senderEmail']);
        }

        $form->FLD('recipientPhone', 'drdata_PhoneType(type=tel,unrecognized=error)','caption=Получател->Данни за връзка,placeholder=Телефон,class=w25,mandatory');
        $form->FLD('recipientEmail', 'email','caption=Получател->-,inlineTo=recipientPhone,placeholder=Имейл,class=w75');
        $form->FLD('recipientPersonName', 'varchar','caption=Получател->Лице за контакт,mandatory');

        $form->FLD('recipientDeliveryType', 'enum(address=Адрес,office=Офис,hub=Хъб)','caption=Доставка->До,silent,removeAndRefreshForm=recipientCountryId|recipientOfficeId|recipientHubId|recipientPcode|recipientPlace|recipientAddress|recipientAddressNum|recipientEntrance|recipientFloor|recipientApp,maxRadio=3,columns=3');
        $form->FLD('recipientHubId', 'key(mvc=cvc_Hubs, select=name, allowEmpty)','caption=Доставка->Хъб');
        $form->FLD('recipientOfficeId', 'key(mvc=cvc_Offices, select=name, allowEmpty)','caption=Доставка->Офис');

        $form->FLD('recipientCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава,silent,removeAndRefreshForm=recipientPcode|recipientPlace|recipientAddress|recipientAddressNum|recipientEntrance|recipientFloor|recipientApp');
        $form->FLD('recipientPcode', 'varchar','caption=Доставка->Населено място,class=w25,placeholder=П.К');
        $form->FLD('recipientPlace', 'varchar','caption=Доставка->-,class=w75,placeholder=Населено място,inlineTo=recipientPcode,autocomplete=off');
        $form->FLD('recipientAddress', 'varchar','caption=Доставка->Адрес,class=w50,placeholder=Адрес');
        $form->FLD('recipientAddressNum', 'varchar(size=3)','caption=Доставка->-,class=w10,placeholder=№,inlineTo=recipientAddress');
        $form->FLD('recipientEntrance', 'varchar(size=3)','caption=Доставка->->Вход,class=w10,placeholder=Вход');
        $form->FLD('recipientFloor', 'int(size=3)','caption=Доставка->-,class=w10,placeholder=Етаж,inlineTo=recipientEntrance');
        $form->FLD('recipientApp', 'int(size=3)','caption=Доставка->-,class=w10,placeholder=Апарт.,inlineTo=recipientFloor');
        $form->FLD('recipientNotes', 'text(rows=2)','caption=Доставка->Уточнения');

        $form->FLD('payment', 'enum(contract=По договор,sender=При изпращане, rec=При получаване)','caption=Параметри на пратката->Плащане');
        $form->FLD('pickupDate', 'date','caption=Параметри на пратката->Дата на изпращане,mandatory');
        $form->FLD('deliveryDate', 'date','caption=Параметри на пратката->Дата на доставка,mandatory');
        $form->FLD('fixedTime', 'hour','caption=Параметри на пратката->Фиксиран час за доставка,input=none');

        $form->FLD('palletCount', 'int(max=100,min=0)', 'mandatory');
        $form->FLD("parcelInfo", "table(columns=width|depth|height|weight,captions=Ширина|Дълбочина|Височина|Тегло,validate=speedy_interface_ApiImpl::validatePallets)");
        $form->FLD('totalWeight', 'double(min=0)');

        $form->FLD('description', 'varchar','caption=Описание на пратката->Описание / Съдържание,mandatory,recently');
        $form->FLD('reff1', 'varchar','caption=Описание на пратката->Клиентски референции,class=w50%,placeholder=Референция 1');
        $form->FLD('reff2', 'varchar','caption=Описание на пратката->-,inlineTo=reff1,class=w50%,placeholder=Референция 2');

        $form->FLD('test', 'enum(no=Без,observe=Преглед,test=Преглед и тест)','caption=Допълнителни услуги и добавки->Тест,maxRadio=3,columns=3,silent,removeAndRefreshForm=rejectPayer,input=hidden');
        $form->FLD('rejectPayer', 'enum(contract=По договор,sender=От изпращача, rec=От получателя)','caption=Допълнителни услуги и добавки->При отказ - плащане,input=none');

        $form->FLD('haveCodPayment', 'enum(no=Без,yes=Да)','caption=Допълнителни услуги и добавки->Наложен платеж,silent,removeAndRefreshForm=codAmount|isCodPpp');
        $form->FLD('codAmount', 'double(min=0)',"caption=Допълнителни услуги и добавки->Сума за събиране,input=none,unit={$baseCurrencyCode}");
        $form->FLD('isCodPpp', 'enum(no=Не,yes=Да)',"caption=Допълнителни услуги и добавки->Като ППП,input=none");

        $form->FLD('haveInsurance', 'enum(no=Без,yes=Да)','caption=Допълнителни услуги и добавки->Обявена стойност,silent,removeAndRefreshForm=insuranceAmount|isFragile');
        $form->FLD('insuranceAmount', 'double(min=0)',"caption=Допълнителни услуги и добавки->Обявена стойност (Сума),input=none,unit={$baseCurrencyCode}");
        $form->FLD('isFragile', 'enum(no=Не,yes=Да)','caption=Допълнителни услуги и добавки->Чупливо,input=none');

        $form->FLD('isSms', 'enum(no=Не,yes=Да)','caption=Допълнителни услуги и добавки->SMS известие');
        $form->FLD('returnPackagings', 'enum(no=Не,yes=Да)','caption=Обратна пратка и връщане->Обратен амбалаж,maxRadio=2,input=none');
        $form->FLD('returnReceipt', 'enum(no=Не,yes=Да)','caption=Обратна пратка и връщане->Обратна разписка,maxRadio=2');
        $form->FLD('returnDocuments', 'enum(no=Не,yes=Да)','caption=Обратна пратка и връщане->Обратни документи,maxRadio=2');

        $form->input(null, 'silent');
        $form->setDefault('senderDeliveryType', 'address');

        $form->setDefault('returnReceipt', 'no');
        $form->setDefault('returnDocuments', 'no');
        $form->setDefault('palletCount', 1);

        $deliveryData = array();
        $deliveryTermId = null;

        // Кои са данните за доставка от условието на договора
        if($mvc instanceof sales_Sales){
            $deliveryTermId = $rec->deliveryTermId;
            $deliveryData = $rec->deliveryData;
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
            if($firstDocument->isInstanceOf('sales_Sales')) {
                if(empty($rec->locationId) && empty($rec->tel)){
                    $deliveryTermId = $firstDocument->fetchField('deliveryTermId');
                    $deliveryData = $firstDocument->fetchField('deliveryData');
                }
            }
        }

        // Ако има намерено условие на доставка и то е с калкулатор за офис на CVC доставката да е до там
        if(isset($deliveryTermId)){
            if($DeliveryCalc = cond_DeliveryTerms::getTransportCalculator($deliveryTermId)){
                if($DeliveryCalc->class instanceof cvc_interface_DeliveryToOffice){
                    if($form->cmd != 'refresh'){
                        $form->setDefault('recipientDeliveryType', 'office');
                        $form->setDefault('recipientOfficeId', $deliveryData['officeId']);
                    }
                }
            }
        }

        $form->setDefault('recipientDeliveryType', 'address');

        if($formRec->parcelType == 'parcel'){
            $form->setField('fixedTime', 'input');
            $form->setField('palletCount', "caption=Брой пакети и тегло->Брой пакети");
            $form->setFieldTypeParams('palletCount', array('max' => 100));
            $form->setField('palletCount', "caption=Брой пакети и тегло->Брой пакети");
            $form->setField('totalWeight', "caption=Брой пакети и тегло->Общо тегло,unit=кг");
            $form->setField('parcelInfo', "caption=Брой пакети и тегло->Описание");
            $form->setFieldType('parcelInfo', 'table(columns=width|depth|height|weight,captions=Ширина [см]|Дълбочина [см]|Височина [см]|Тегло [кг],validate=cvc_interface_CourierImpl::validatePallets,parcelType=parcel)');
            $form->setField('test', 'input');
            $form->setDefault('test', 'no');
            if($formRec->test != 'no'){
                $form->setField('rejectPayer', 'input');
                $form->setDefault('rejectPayer', 'contract');
            }

        } elseif($formRec->parcelType == 'pallet'){
            $form->setField('returnPackagings', 'input');
            $form->setDefault('returnPackagings', 'no');
            $form->setField('palletCount', "caption=Брой палети и тегло->Брой палети");
            $form->setFieldTypeParams('palletCount', array('max' => 16));
            $form->setField('totalWeight', "caption=Брой палети и тегло->Общо тегло");
            $form->setField('parcelInfo', "caption=Брой палети и тегло->Описание");
            $form->setFieldType('parcelInfo', 'table(columns=width|depth|height|weight,captions=Основа [см]|x [см]|Височина [см]|Тегло [кг],validate=cvc_interface_CourierImpl::validatePallets,parcelType=pallet)');
        } else {
            $form->setField('returnPackagings', 'input');
            $form->setDefault('returnPackagings', 'no');
            $form->setField('palletCount', "caption=Брой гуми и тегло->Брой гуми");
            $form->setFieldTypeParams('palletCount', array('max' => 10));
            $form->setField('totalWeight', "caption=Брой гуми и тегло->Общо тегло");
            $form->setField('parcelInfo', "caption=Брой гуми и тегло->Описание");
            $form->setFieldType('parcelInfo', 'table(columns=weight,captions=Тегло [кг],validate=cvc_interface_CourierImpl::validatePallets,parcelType=tires)');
        }

        $customLocations = cvc_Adapter::getCustomLocations();
        $senderData = $customLocations[$formRec->customerId];

        $form->FLD('senderPcode', 'varchar','caption=Приемане от->Населено място,class=w25,placeholder=П.К');
        $form->FLD('senderPlace', 'varchar','caption=Приемане от->-,class=w75,placeholder=Наименование,inlineTo=senderPcode');
        $form->FLD('senderAddress', 'varchar','caption=Приемане от->Адрес,class=w50,placeholder=Наименование');
        $form->FLD('senderAddressNum', 'varchar(size=3)','caption=Приемане от->-,class=w10,placeholder=№,inlineTo=senderAddress');
        $form->FLD('senderEntrance', 'varchar(size=3)','caption=Приемане от->Вход,class=w10,placeholder=Вход');
        $form->FLD('senderFloor', 'int(size=3)','caption=Приемане от->-,class=w10,placeholder=Етаж,inlineTo=senderEntrance');
        $form->FLD('senderApp', 'int(size=3)','caption=Приемане от->-,class=w10,placeholder=Апарт.,inlineTo=senderFloor');
        $form->FLD('senderNotes', 'text(rows=2)','caption=Приемане от->Уточнения');
        $hideFields = array();
        if($formRec->senderDeliveryType == 'address'){
            foreach (array('zip' => 'senderPcode', 'cityBg' => 'senderPlace', 'street' => 'senderAddress', 'num' => 'senderAddressNum') as $theirFld => $ourFld){
                $form->setDefault($ourFld, $senderData[$theirFld]);
            }
        } else {
            $form->setField('senderHubId', 'input,mandatory');
            $hideFieldSender = array('senderPcode','senderPlace','senderAddress','senderAddressNum','senderEntrance','senderFloor','senderApp');
            $hideFields = array_merge($hideFields, $hideFieldSender);
        }

        $logisticData = $mvc->getLogisticData($rec);

        $defaultIsPostal = 'no';
        if($mvc instanceof sales_Sales){
            $paymentType = $rec->paymentType;
            $amountCod = $rec->amountDeal;
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
            $paymentType = $firstDocument->fetchField('paymentType');
            $amountCod = $rec->amountDelivered;
        }

        if(isset($amountCod) && isset($paymentType)){
            if(in_array($paymentType, array('postal', 'cash'))){
                $defaultCodAmount = round($amountCod, 2);
            }
            if($paymentType == 'postal'){
                $defaultIsPostal = 'yes';
            }
        }

        if(isset($defaultCodAmount)){
            $form->setDefault('codAmount', $defaultCodAmount);
            $form->setField('codAmount', 'mandatory');
            $form->setSuggestions('codAmount', array('' => '', "{$defaultCodAmount}" => $defaultCodAmount));
            $form->setDefault('haveCodPayment', 'yes');
            $form->setDefault('isCodPpp', $defaultIsPostal);
            $form->setSuggestions('insuranceAmount', array('' => '', "{$defaultCodAmount}" => $defaultCodAmount));
        } else {
            $form->setDefault('haveCodPayment', 'no');
        }

        $form->setDefault('haveInsurance', 'no');
        if($formRec->haveCodPayment == 'yes'){
            $form->setField('codAmount', 'input');
            $form->setField('isCodPpp', 'input');
            $form->setDefault('isCodPpp', 'no');
        }

        if($formRec->haveInsurance == 'yes'){
            $form->setField('insuranceAmount', 'input,mandatory');
            $form->setField('isFragile', 'input');
            $form->setDefault('isCodPpp', 'no');
        }

        $form->setDefault('recipientPhone', $logisticData['toPersonPhones']);
        $form->setDefault('recipientName', $logisticData['toCompany']);
        $form->setDefault('recipientNotes', $logisticData['instructions']);
        $form->setDefault('recipientPersonName', $logisticData['toPerson']);

        if($formRec->recipientDeliveryType == 'hub'){
            $form->setField('recipientHubId', 'input,mandatory');
            $hideFieldRecipient = array('recipientOfficeId', 'recipientCountryId', 'recipientPcode', 'recipientPlace', 'recipientAddress', 'recipientAddressNum', 'recipientEntrance', 'recipientFloor', 'recipientApp');
        } elseif($formRec->recipientDeliveryType == 'office'){
            $form->setField('recipientOfficeId', 'input,mandatory');
            $hideFieldRecipient = array('recipientHubId', 'recipientCountryId', 'recipientPcode', 'recipientPlace', 'recipientAddress', 'recipientAddressNum', 'recipientEntrance', 'recipientFloor', 'recipientApp');
        } else {
            $hideFieldRecipient = array('recipientHubId', 'recipientOfficeId');
            $logisticCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
            $form->setDefault('recipientCountryId', $logisticCountryId);
            $form->setField('recipientPcode', 'mandatory');
            $form->setField('recipientPlace', 'mandatory');

            if($form->rec->recipientCountryId == $logisticCountryId){
                $form->setDefault('recipientPlace', $logisticData['toPlace']);
                $form->setDefault('recipientAddress', $logisticData['toAddress']);
                $form->setDefault('recipientPcode', $logisticData['toPCode']);
            }
        }
        $hideFields = array_merge($hideFields, $hideFieldRecipient);

        foreach ($hideFields as $hideFld){
            $form->setField($hideFld, 'input=none');
        }

        $profile = crm_Profiles::getProfile();
        $phones = drdata_PhoneType::toArray($profile->tel);
        $phone = $phones[0]->original;
        $form->setDefault('senderName', $profile->name);
        $form->setDefault('senderPhone', $phone);
        $form->setDefault('totalWeight', $logisticData['totalWeight']);
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
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            $today = dt::today();
            if($rec->pickupDate < $today){
                $form->setError('pickupDate', "Датата не може да е в миналото|*!");
            }

            if($rec->deliveryDate < $today){
                $form->setError('pickupDate', "Датата не може да е в миналото|*!");
            }

            if($rec->parcelType != 'parcel' && empty($rec->parcelInfo)){
                $form->setError('parcelInfo', "За непакетни пратки описанието на палетите е задължително|*!");
            }

            $parcelInfoArr = type_Table::toArray($rec->parcelInfo);
            $parcelWeight = arr::sumValuesArray($parcelInfoArr, 'weight');

            if(!empty($rec->parcelInfo)){
                if(countR($parcelInfoArr) != $rec->palletCount){
                    $form->setError('palletCount,parcelInfo', "Броят и описанието на палетите се разминава|*!");
                }
            }

            if(empty($rec->totalWeight)){
                if(!countR($parcelInfoArr)){
                    $form->setError('palletCount,parcelInfo', "Трябва да се посочи общо тегло|*!");
                }
            } else {
                if(countR($parcelInfoArr)){
                    if($parcelWeight != $rec->totalWeight){
                        $form->setError('totalWeight,parcelInfo', "Има разминаване между посоченото тегло и изчисленото|*!");
                    }
                }
            }

            if(!empty($rec->recipientPlace)){
                if(mb_strlen($rec->recipientPlace) < 3){
                    $form->setError('recipientPlace', "Населеното място трябва да има минимум три символа|*!");
                } else {
                    $foundPlaces = static::getPlacesByString($rec->recipientPlace, $rec->recipientCountryId, $rec->recipientPcode);

                    // Проверка на мястото за доставка
                    $foundPlacesCount = countR($foundPlaces);
                    if(!$foundPlacesCount){
                        $form->setError('recipientPlace', "Има проблем при разпознаване на населеното място|*! Моля проверете наименованието и пощенския код. Пробвайте да напишете името без съкращения!");
                    } elseif($foundPlacesCount != 1){
                        $form->setError('recipientPlace', "Населеното място не може да бъде определено еднозначно от тяхната система|*!");
                    } elseif(is_array($foundPlaces)) {
                        $rec->_cityId = key($foundPlaces);
                    }
                }
            }

            if(!$form->gotErrors()){
                if(empty($rec->totalWeight) && !empty($parcelWeight)){
                    $rec->totalWeight = $parcelWeight;
                }
            }
        }
    }


    /**
     * Помощна ф-я връщаща населените места отговарящи на посочените критерии
     *
     * @param string $string
     * @param int $ourCountry
     * @return array|false
     */
    private static function getPlacesByString($string, $ourCountry, $pCode)
    {
        try{
            $theirCountryId = cvc_Adapter::getCountryIdByName($ourCountry);

            return cvc_Adapter::getCity($string, $pCode, $theirCountryId);

        } catch(core_exception_Expect $e){

            return false;
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
        $columns = arr::make(explode('|', $Type->params['columns']), true);

        foreach($TableArr as $i => $obj){
            foreach ($columns as $field){
                if(!empty($obj->{$field})){
                    if(!$Double->fromVerbal($obj->{$field}) || $obj->{$field} < 0){
                        $error[] = 'Невалидно число';
                        $errorFields[$field][$i] = 'Невалидно число';
                    }
                }
            }

            if(empty($obj->weight)){
                $error['sizeError'] = 'Трябва да са въведени размерите';
                $errorFields['weight'][$i] = 'Трябва да е въведено тегло';
            }

            if(array_key_exists('width', $columns)){
               if(empty($obj->width)){
                   $error['sizeError'] = 'Трябва да са въведени размерите';
                   $errorFields['width'][$i] = 'Трябва да е въведена ширина';
               }
            }

            if(array_key_exists('depth', $columns)){
                if(empty($obj->depth)){
                    $error['sizeError'] = 'Трябва да са въведени размерите';
                    $errorFields['depth'][$i] = 'Трябва да е въведена дълбочина';
                }
            }

            if(array_key_exists('height', $columns)){
                if(empty($obj->height)){
                    $error['sizeError'] = 'Трябва да са въведени размерите';
                    $errorFields['height'][$i] = 'Трябва да е въведена височина';
                }
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
        $haveError = false;
        $res = null;
        try{
            $preparedBolParams = static::prepareBolData($form->rec, 'calculate');
            $res = cvc_Adapter::calculateWb($preparedBolParams);
        } catch(core_exception_Expect $e){
            bp($e, $preparedBolParams);
            $haveError = true;
        }

        if($haveError || !$res){
            $errorTpl = new core_ET('<div class="richtext-message richtext-error">[#1#]</div>');
            $errorTpl->replace(tr('Цената за изпращане не може да бъде изчислена'), '1');

            return $errorTpl;
        }

        // Рендиране на ценовата информация
        $tpl = getTplFromFile('cvc/tpl/CalculationResult.shtml');
        $tpl->replace($res['price'], 'price');
        $tpl->replace($res['priceWithVAT'], 'priceWithVat');
        foreach ($res['details'] as $additionalText){
            $tpl->append("<div>{$additionalText}</div>", 'ADDITIONAL');
        }

        return $tpl;
    }


    /**
     * Подготвя данните за товарителницата
     *
     * @param stdClass $rec
     * @param string $action
     * @return array $res (@see cvc_Adapter::createWb)
     */
    private static function prepareBolData($rec, $action = 'shipment')
    {
        $res = array('parcel_type' => $rec->parcelType,
                     'pickup_date' => $rec->pickupDate,
                     'description' => $rec->description,
                     'payer' => $rec->payment,
                     'total_kgs' => $rec->totalWeight,
                     'total_parcels' => $rec->palletCount,
        );

        if(!empty($rec->parcelInfo)){
            $parcelInfo = type_Table::toArray($rec->parcelInfo);
            foreach ($parcelInfo as $parcel){
                if($rec->parcelType == 'tires'){
                    $res['parcels'][] = (object)array('kgs' => $parcel->weight);
                } else {
                    $res['parcels'][] = (object)array('kgs' => $parcel->weight, 'dim_w' => $parcel->width, 'dim_d' => $parcel->depth, 'dim_h' => $parcel->height);
                }
            }
        }

        $senderObj = (object)array('custom_location_id' => $rec->customerId,
                                   'name' => $rec->senderName,
                                   'phone' => $rec->senderPhone,
                                   'email' => $rec->senderEmail);

        if(!empty($rec->senderNotes)){
            $senderObj->notes = $rec->senderNotes;
        }
        if($rec->senderDeliveryType == 'hub'){
            $senderObj->hub_id = cvc_Hubs::fetchField($rec->senderHubId, 'num');
        } else {
            foreach (array('zip' => 'senderPcode', 'num' => 'senderAddressNum', 'entr' => 'senderEntrance', 'ap' => 'senderApp', 'floor' => 'senderFloor') as $theirFld => $oursFld){
                if(!empty($rec->{$oursFld})){
                    $senderObj->{$theirFld} = $rec->{$oursFld};
                }
            }
            $streetStr = '';
            if(!empty($rec->senderPlace)){
                $streetStr = $rec->senderPlace;
            }
            if(!empty($rec->senderAddress)){
                $streetStr .= (!empty($streetStr) ? ", " : '') . $rec->senderAddress;
            }
            if(!empty($streetStr)){
                $senderObj->street = $streetStr;
            }
        }
        $res['sender'] = $senderObj;

        $recipientName = $rec->recipientPersonName;
        if(!empty($rec->recipientPersonName)){
            $recipientName .= ", {$rec->recipientName}";
        }
        $recipientObj = (object)array(
            'name' => $recipientName,
            'phone' => $rec->recipientPhone,
            'email' => $rec->recipientEmail,
        );

        if($rec->recipientDeliveryType == 'hub'){
            $recipientObj->hub_id = cvc_Hubs::fetchField($rec->recipientHubId, 'num');
        } elseif($rec->recipientDeliveryType == 'office'){
            $recipientObj->office_id = cvc_Offices::fetchField($rec->recipientOfficeId, 'num');
        } else {
            $recipientObj->city_id = $rec->_cityId;
            foreach (array('zip' => 'recipientPcode', 'num' => 'recipientAddressNum', 'entr' => 'recipientEntrance', 'ap' => 'recipientApp', 'floor' => 'recipientFloor', 'street' => 'recipientAddress') as $theirFld => $oursFld){
                if(!empty($rec->{$oursFld})){
                    $recipientObj->{$theirFld} = $rec->{$oursFld};
                }
            }
        }

        if(!empty($rec->recipientNotes)){
            $recipientObj->notes = $rec->recipientNotes;
        }
        $res['rec'] = $recipientObj;
        $res['ref1'] = $rec->reff1;
        $res['ref2'] = $rec->reff2;
        $res['fix_time'] = $rec->fixedTime;
        if($rec->test == 'test'){
            $res['is_observe'] = true;
            $res['is_test'] = true;
            $res['reject_payer'] = $rec->rejectPayer;
        } elseif($rec->test == 'observe'){
            $res['is_observe'] = true;
            $res['is_test'] = false;
            $res['reject_payer'] = $rec->rejectPayer;
        } else {
            $res['is_observe'] = false;
            $res['is_test'] = false;
        }

        if($rec->haveCodPayment == 'yes'){
            $res['cod_amount'] = $rec->codAmount;
            $res['is_cod_ppp'] = ($rec->isCodPpp == 'yes');
        }

        if($rec->haveInsurance == 'yes'){
            $res['os_value'] = $rec->insuranceAmount;
            $res['is_fragile'] = ($rec->isFragile == 'yes');
        }
        $res['is_sms'] = ($rec->isSms == 'yes');
        $res['is_return_amb'] = ($rec->returnPackagings == 'yes');
        $res['is_return_receipt'] = ($rec->returnReceipt == 'yes');
        $res['is_return_docs'] = ($rec->returnDocuments == 'yes');

        return $res;
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
            $res = cvc_Adapter::createWb($preparedBolParams);
        } catch(core_exception_Expect $e){
            $form->setError('parcelType', "Проблем при генериране на товарителницата");
            return;
        }

        if(empty($res)){
            $form->setError('parcelType', "Проблем при генериране на товарителницата");
            return;
        }

        if(!$form->gotErrors()){

            // Ако е разпечатана записва се в помощния модел
            $wayBillRec = (object)array('containerId' => $documentRec->containerId, 'number' => $res['wb'], 'pickupDate' => $res['pickupDate'], 'deliveryDate' => $res['deliveryDate'], 'state' => 'pending', 'data' => $preparedBolParams);
            if(empty($res['pdf'])){
                $form->setError('parcelType', "Проблем при сваляне на товарителницата|*: <b>{$res['wb']}</b>");
                return;
            }

            $wayBillRec->file = $res['pdf'];
            cvc_WayBills::save($wayBillRec);

            // Кеш на избраните полета от формата
            $cacheArr = array('parcelType' => $form->rec->parcelType, 'customerId' => $form->rec->customerId, 'senderName' => $form->rec->senderName, 'senderPhone' => $form->rec->senderPhone, 'senderEmail' => $form->rec->senderEmail);
            core_Permanent::set(self::getUserDataCacheKey($documentRec->folderId), $cacheArr, 4320);

            return $res['pdf'];
        }

        return null;
    }


    /**
     * Какъв е ключа на потребителския кеш
     *
     * @param int $folderId
     *
     * @return string $key
     */
    private static function getUserDataCacheKey($folderId)
    {
        $cu = core_Users::getCurrent('id', false);
        $key = "CVC_{$folderId}_{$cu}";

        return $key;
    }


    /**
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @param core_ET $tpl           - шаблона на формата
     * @return void
     */
    public function afterPrepareBillOfLadingForm($mvc, $documentRec, $form, &$tpl)
    {
        $url = '';
        if($this->haveRightFor('getplacesbystring')){
            $url = toUrl(array($this, 'getplacesbystring'), 'local');
            $url = urlencode($url);
        }

        $tpl->push('cvc/js/BillOfLadingForm.js', 'JS');
        jquery_Jquery::run($tpl, "enableApi('{$url}');");
        jquery_Jquery::runAfterAjax($tpl, "enableApi");
        jqueryui_Ui::enable($tpl);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'getplacesbystring'){
            if(empty(cvc_Setup::get('TOKEN'))){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Екшън връщащ опциите за избор на място по даден стринг
     */
    function act_getplacesbystring()
    {
        $errorMsg = null;
        if(!$this->haveRightFor('getplacesbystring')){
            $errorMsg = '|Нямате права|*!';
        }
        $q = Request::get('string', 'varchar');
        $countryId = Request::get('countryId', 'int');
        if(empty($q) || empty($countryId)){
            $errorMsg = '|Невалидни параметри|*!';
        }

        $cities = array();
        if(!empty($errorMsg)){
            // Ако е имало грешки се показват
            core_Statuses::newStatus($errorMsg, 'error');
        } else {
            try{
                $theirCountryId = cvc_Adapter::getCountryIdByName($countryId);
                $cities = cvc_Adapter::getCities($q, $theirCountryId);
            } catch(core_exception_Expect $e){
                if(haveRole('debug')){
                    core_Statuses::newStatus("Проблем при свързване с АПИ-то", 'error');
                }
            }
        }

        $citySuggestions = array();
        if (is_array($cities)) {
            foreach ($cities as $cityObj){
                $citySuggestions[$cityObj['nameBg']] = $cityObj['nameBg'];
            }
        }

        $resObj = new stdClass();
        $resObj->func = 'citysuggestions';
        $resObj->arg = array('cities' => array_values($citySuggestions), 'searchText' => $q);
        $res = array($resObj);

        return $res;
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
            if ($foundRec = self::getLastWayBill($rec->containerId)) {
                $urlTpl = new core_ET(cvc_Setup::get('TRACKING_URL'));
                $urlTpl->replace($foundRec->number, 'NUM');
                $url = $urlTpl->getContent();

                $date = dt::mysql2verbal($foundRec->pickupDate, 'd.m.Y');
                $bolTpl = new ET(tr("|*\n|Вашата пратка е подготвена за изпращане на|* [#date#] |с товарителница|* [#number#].\n|Може да проследите получаването ѝ от тук|*: [#URL#]"));
                $bolTpl->replace($url, 'URL');
                $bolTpl->replace($foundRec->number, 'number');
                $bolTpl->replace($date, 'date');

                return $bolTpl;
            }
        }
    }


    /**
     * Връща коя е последната товарителница издадена към документа
     *
     * @param int $containerId
     * @return stdClass|false
     */
    private static function getLastWayBill($containerId)
    {
        $spQuery = cvc_WayBills::getQuery();
        $spQuery->where("#containerId = {$containerId}");
        $spQuery->orderBy('id', 'DESC');
        $spQuery->limit(1);

        return $spQuery->fetch();
    }
}

