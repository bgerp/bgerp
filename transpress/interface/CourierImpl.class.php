<?php


/**
 * Драйвер за връзка с API на Transpress
 *
 * @category  bgerp
 * @package   transpress
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 * @title  Transpress API
 *
 * @since     v 0.1
 */
class transpress_interface_CourierImpl extends core_BaseClass
{
    /**
     * Роли по дефолт, които изисква драйвера
     */
    public $requireRoles = 'ceo,courier';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_CourierApiIntf';


    /**
     * Заглавие
     */
    public $title = 'Transpress API';


    /**
     * Заглавие на  бутон за създаване на товарителница
     */
    public $requestWayBillBtnCaption = 'Transpress';


    /**
     * Иконка за бутон за създаване на товарителница
     */
    public $requestWayBillBtnIcon = 'img/16/transpress.png';


    /**
     * Коментар към връзката на прикачения файл
     */
    public $wayBillComment = 'Товарителница (Transpress)';


    /**
     * Полетата от формата, които се помнят за следващата товарителница
     */
    private static $rememberFields = array('senderName', 'senderPerson', 'senderPhone', 'pickupType', 'warehouse', 'sourceStation', 'service', 'printFormat');


    /**
     * Сървърните пътища на грешките => поле от формата
     */
    private static $errorFields = array('sourceCity' => 'sourcePCode', 'sourcePostCode' => 'sourcePCode', 'sourceStation' => 'sourceStation',
                                        'warehouse' => 'warehouse', 'targetCity' => 'targetPCode', 'targetPostCode' => 'targetPCode',
                                        'targetStation' => 'targetStation', 'POD/CMR' => 'services', 'weight' => 'parcelInfo', 'volume' => 'parcelInfo');


    /**
     * Може ли потребителя да създава товарителница от документа
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param int|null $userId - ид на потребител (null за текущия)
     * @return bool
     */
    public function canRequestWayBill($mvc, $id, $userId = null)
    {
        $res = haveRole($this->requireRoles, $userId);
        if($res){
            $login = transpress_Adapter::getLoginData($userId);
            if(empty($login['userName']) || empty($login['password'])){
                $res = false;
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
    public function addFieldToWayBillForm($mvc, $rec, &$form)
    {
        $cacheArr = core_Permanent::get(self::getUserDataCacheKey($rec->folderId));
        $formRec = &$form->rec;

        $form->class = 'transpressWayBill';
        $form->title = '|Попълване на товарителница за|* Transpress |към|* ' . $mvc->getFormTitleLink($rec);
        $form->info = self::getTestNotice();

        $form->FLD('senderName', 'varchar', 'caption=Подател->Фирма/Име');
        $form->FLD('senderPerson', 'varchar', 'caption=Подател->Лице за контакт,mandatory');
        $form->FLD('senderPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Подател->Телефон,mandatory');
        $form->FLD('senderPersonalNumber', 'varchar(10)', 'caption=Подател->ЕГН,hint=Само ако се изисква от куриера');

        $form->FLD('pickupType', 'enum(address=От адрес,warehouse=Запазен адрес,office=Предаване в офис)', 'caption=Приемане->Вид,silent,removeAndRefreshForm=service,maxRadio=3,columns=3');
        $form->FLD('warehouse', 'varchar', 'caption=Приемане->Адрес');
        $form->FLD('sourceStation', 'varchar', 'caption=Приемане->Офис');
        $form->FLD('sourceCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Приемане->Държава,silent,removeAndRefreshForm=service');
        $form->FLD('sourcePCode', 'varchar(16)', 'caption=Приемане->Населено място,class=w25,placeholder=П.К');
        $form->FLD('sourcePlace', 'varchar', 'caption=Приемане->-,class=w75,placeholder=Населено място,inlineTo=sourcePCode');
        $form->FLD('sourceAddress', 'varchar', 'caption=Приемане->Адрес');
        $form->FLD('sourceInstructions', 'text(rows=2)', 'caption=Приемане->Указания');

        $form->FLD('recipientName', 'varchar', 'caption=Получател->Фирма/Име,mandatory');
        $form->FLD('recipientPerson', 'varchar', 'caption=Получател->Лице за контакт,mandatory');
        $form->FLD('recipientPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Получател->Телефон,mandatory');

        $form->FLD('deliveryType', 'enum(address=До адрес,office=До офис)', 'caption=Доставка->Вид,silent,removeAndRefreshForm=service,maxRadio=2');
        $form->FLD('targetStation', 'varchar', 'caption=Доставка->Офис');
        $form->FLD('targetCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава,silent,removeAndRefreshForm=service|targetPCode|targetPlace|targetAddress');
        $form->FLD('targetPCode', 'varchar(16)', 'caption=Доставка->Населено място,class=w25,placeholder=П.К');
        $form->FLD('targetPlace', 'varchar', 'caption=Доставка->-,class=w75,placeholder=Населено място,inlineTo=targetPCode');
        $form->FLD('targetAddress', 'varchar', 'caption=Доставка->Адрес');
        $form->FLD('targetInstructions', 'text(rows=2)', 'caption=Доставка->Указания');

        $form->FLD('service', 'varchar', 'caption=Параметри на пратката->Услуга');
        $form->FLD('pickupDate', 'date', 'caption=Параметри на пратката->Дата на вземане');
        $form->FLD('payer', 'enum(sender=Подател,recipient=Получател)', 'caption=Параметри на пратката->Транспортът се плаща от,maxRadio=2');
        $form->FLD('parcelInfo', 'table(columns=count|packaging|weight|length|width|height,captions=Брой|Опаковка|Общо тегло [кг]|Дължина [см]|Ширина [см]|Височина [см],widths=4em|14em|6em|6em|6em|6em,validate=transpress_interface_CourierImpl::validateParcels)', 'caption=Параметри на пратката->Пакети,mandatory');
        $form->FLD('description', 'varchar', 'caption=Параметри на пратката->Съдържание,mandatory,recently');
        $form->FLD('referenceCode', 'varchar(64)', 'caption=Параметри на пратката->Референция');

        $form->FLD('haveCodPayment', 'enum(no=Без,yes=Да)', 'caption=Допълнителни услуги->Наложен платеж,silent,removeAndRefreshForm=codAmount|codPaymentType|isPostal,maxRadio=2');
        $form->FLD('codAmount', 'double(min=0)', 'caption=Допълнителни услуги->Сума за събиране,input=none,unit=€');
        $form->FLD('codPaymentType', 'enum(contract=По договор,CASH=В брой,BANK_TRANSFER=По банка,POS_PAYMENT=С карта)', 'caption=Допълнителни услуги->Изплащане на НП,input=none');
        $form->FLD('isPostal', 'enum(no=Не,yes=Да)', 'caption=Допълнителни услуги->Пощенски паричен превод,input=none,maxRadio=2');
        $form->FLD('haveInsurance', 'enum(no=Без,yes=Да)', 'caption=Допълнителни услуги->Застраховка,silent,removeAndRefreshForm=insuranceAmount|isFragile,maxRadio=2');
        $form->FLD('insuranceAmount', 'double(min=0)', 'caption=Допълнителни услуги->Застрахователна сума,input=none,unit=€');
        $form->FLD('isFragile', 'enum(no=Не,yes=Да)', 'caption=Допълнителни услуги->Чупливо,input=none,maxRadio=2');
        $form->FLD('services', 'set(return_pod=Обратна разписка,return_doc=Обратни документи,return_pkg=Обратен амбалаж,review_on_delivery=Преглед при доставка,test_on_delivery=Тест при доставка,call_required=Обаждане преди доставка,saturday=Доставка в събота)', 'caption=Допълнителни услуги->Избор,columns=2');

        $form->FLD('printFormat', 'enum(pdf-label=Етикет,pdf=Манифест,pdf-label-manifest=Етикет и манифест)', 'caption=Печат->Документ');

        $form->input(null, 'silent');

        // Запомнените от предишната товарителница
        if(is_array($cacheArr)){
            foreach (self::$rememberFields as $fld){
                if(isset($cacheArr[$fld])){
                    $form->setDefault($fld, $cacheArr[$fld]);
                }
            }
        }

        $logisticData = $mvc->getLogisticData($rec);
        $ourCompany = crm_Companies::fetchOurCompany();
        $profile = crm_Profiles::getProfile();
        $phones = drdata_PhoneType::toArray(!empty($profile->buzTel) ? $profile->buzTel : ($profile->tel ?? null));
        $form->setDefault('senderName', $ourCompany->name ?? null);
        $form->setDefault('senderPerson', $profile->name ?? null);
        $form->setDefault('senderPhone', $phones[0]->original ?? null);

        // Приемане: запазен адрес, офис или адрес от документа
        $warehouseOptions = transpress_Adapter::getWarehouseOptions();
        $officeOptions = transpress_Adapter::getOfficeOptions();
        if(!countR($warehouseOptions)){
            $form->setOptions('pickupType', array('address' => 'От адрес', 'office' => 'Предаване в офис'));
            if(($formRec->pickupType ?? null) == 'warehouse'){
                $formRec->pickupType = null;
            }
        }
        $form->setDefault('pickupType', countR($warehouseOptions) ? 'warehouse' : 'address');

        $hideFields = array();
        $sourceAddressFields = array('sourceCountryId', 'sourcePCode', 'sourcePlace', 'sourceAddress');
        if($formRec->pickupType == 'warehouse'){
            $form->setOptions('warehouse', array('' => '') + $warehouseOptions);
            $form->setField('warehouse', 'mandatory');
            if(countR($warehouseOptions) == 1){
                $form->setDefault('warehouse', key($warehouseOptions));
            }
            $hideFields = array_merge($hideFields, array('sourceStation'), $sourceAddressFields);
        } elseif($formRec->pickupType == 'office'){
            $form->setOptions('sourceStation', array('' => '') + $officeOptions);
            $form->setField('sourceStation', 'mandatory');
            $hideFields = array_merge($hideFields, array('warehouse'), $sourceAddressFields);
        } else {
            foreach ($sourceAddressFields as $fld){
                $form->setField($fld, 'mandatory');
            }
            $hideFields = array_merge($hideFields, array('warehouse', 'sourceStation'));
            $form->setDefault('sourceCountryId', drdata_Countries::getIdByName($logisticData['fromCountry'] ?? null));
            $form->setDefault('sourcePCode', $logisticData['fromPCode'] ?? null);
            $form->setDefault('sourcePlace', $logisticData['fromPlace'] ?? null);
            $form->setDefault('sourceAddress', $logisticData['fromAddress'] ?? null);
        }

        // Получател и доставка
        $form->setDefault('recipientName', $logisticData['toCompany'] ?? null);
        $form->setDefault('recipientPerson', $logisticData['toPerson'] ?? null);
        $form->setDefault('recipientPhone', $logisticData['toPersonPhones'] ?? null);
        $form->setDefault('targetInstructions', $logisticData['instructions'] ?? null);
        $form->setDefault('deliveryType', 'address');
        $targetAddressFields = array('targetCountryId', 'targetPCode', 'targetPlace', 'targetAddress');
        if($formRec->deliveryType == 'office'){
            $form->setOptions('targetStation', array('' => '') + $officeOptions);
            $form->setField('targetStation', 'mandatory');
            $hideFields = array_merge($hideFields, $targetAddressFields);
        } else {
            foreach ($targetAddressFields as $fld){
                $form->setField($fld, 'mandatory');
            }
            $hideFields[] = 'targetStation';
            $logisticCountryId = drdata_Countries::getIdByName($logisticData['toCountry'] ?? null);
            $form->setDefault('targetCountryId', $logisticCountryId);
            if(($formRec->targetCountryId ?? null) == $logisticCountryId){
                $form->setDefault('targetPCode', $logisticData['toPCode'] ?? null);
                $form->setDefault('targetPlace', $logisticData['toPlace'] ?? null);
                $form->setDefault('targetAddress', $logisticData['toAddress'] ?? null);
            }
        }

        foreach ($hideFields as $fld){
            $form->setField($fld, 'input=none');
        }

        // Услугите зависят от държавите; офисите са в България
        $fromCountry = ($formRec->pickupType == 'address') ? self::getCountryCode($formRec->sourceCountryId ?? null) : 'BG';
        $toCountry = ($formRec->deliveryType == 'address') ? self::getCountryCode($formRec->targetCountryId ?? null) : 'BG';
        $serviceOptions = transpress_Adapter::getServiceOptions($fromCountry ?? 'BG', $toCountry ?? 'BG');
        $form->setOptions('service', array('' => '') + $serviceOptions);
        $form->setField('service', 'placeholder=По договор');

        // Пакети: опаковките са от каталога на Transpress
        $form->setFieldTypeParams('parcelInfo', array('packaging_opt' => transpress_Adapter::getPackagingOptions()));
        if(!empty($logisticData['totalWeight'])){
            $form->setDefault('parcelInfo', json_encode(array('count' => array('1'), 'weight' => array((string)round($logisticData['totalWeight'], 2)))));
        }
        $form->setDefault('referenceCode', $mvc->getHandle($rec->id));
        $form->setDefault('payer', 'sender');

        // Наложен платеж по начина на плащане от сделката
        $paymentType = $amountCod = null;
        if($mvc instanceof sales_Sales){
            $paymentType = $rec->paymentType ?? null;
            $amountCod = $rec->amountDeal ?? null;
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
            if(is_object($firstDocument) && $firstDocument->isInstanceOf('sales_Sales')){
                $paymentType = $firstDocument->fetchField('paymentType');
                $amountCod = $rec->amountDelivered ?? null;
            }
        }

        if(isset($amountCod) && in_array($paymentType, array('postal', 'cash'))){
            // Сумите на документа са в основната валута към датата му, а Transpress иска евро
            $valior = $rec->{$mvc->valiorFld ?? 'valior'} ?? null;
            $defaultCodAmount = round(currency_CurrencyRates::convertAmount($amountCod, $valior, null, 'EUR'), 2);
            $form->setDefault('haveCodPayment', 'yes');
            $form->setDefault('codAmount', $defaultCodAmount);
            $form->setDefault('isPostal', ($paymentType == 'postal') ? 'yes' : 'no');
            $form->setSuggestions('insuranceAmount', array('' => '', "{$defaultCodAmount}" => $defaultCodAmount));
        }
        $form->setDefault('haveCodPayment', 'no');
        $form->setDefault('haveInsurance', 'no');

        if($formRec->haveCodPayment == 'yes'){
            $form->setField('codAmount', 'input,mandatory');
            $form->setField('codPaymentType', 'input');
            $form->setDefault('codPaymentType', 'contract');
            $form->setField('isPostal', 'input');
            $form->setDefault('isPostal', 'no');
        }

        if($formRec->haveInsurance == 'yes'){
            $form->setField('insuranceAmount', 'input,mandatory');
            $form->setField('isFragile', 'input');
            $form->setDefault('isFragile', 'no');
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
    public function inputWayBillForm($mvc, $documentRec, &$form)
    {
        if(!$form->isSubmitted()) return;

        $rec = $form->rec;
        if(!empty($rec->pickupDate) && $rec->pickupDate < dt::today()){
            $form->setError('pickupDate', 'Датата не може да е в миналото|*!');
        }
    }


    /**
     * Проверка на редовете с пакети
     *
     * @param array     $tableData
     * @param core_Type $Type
     * @return array
     */
    public static function validateParcels($tableData, $Type)
    {
        $res = $error = $errorFields = array();
        $Double = core_Type::getByName('double');
        $packagingAttr = transpress_Adapter::getPackagingAttributes();

        foreach (type_Table::toArray($tableData) as $i => $obj){
            foreach (array('count', 'weight', 'length', 'width', 'height') as $field){
                $value = $obj->{$field} ?? '';
                if(strlen((string)$value) && ($Double->fromVerbal($value) === false || $Double->fromVerbal($value) <= 0)){
                    $error['number'] = 'Невалидно число';
                    $errorFields[$field][$i] = 'Невалидно число';
                }
            }

            foreach (array('count' => 'Трябва да е въведен брой', 'weight' => 'Трябва да е въведено тегло') as $field => $msg){
                if(!strlen((string)($obj->{$field} ?? ''))){
                    $error[$field] = $msg;
                    $errorFields[$field][$i] = $msg;
                }
            }

            // При палетите размерите, които не са фиксирани от опаковката, определят цената и са задължителни
            $attr = $packagingAttr[$obj->packaging ?? ''] ?? array();
            if(($attr['standard'] ?? null) == 'true'){
                foreach (array('length' => 'package_x', 'width' => 'package_y', 'height' => 'package_z') as $field => $attrName){
                    if(!isset($attr[$attrName]) && !strlen((string)($obj->{$field} ?? ''))){
                        $error['dimensions'] = 'Опаковката изисква размерите, които не са фиксирани от нея (за европалет - височина)';
                        $errorFields[$field][$i] = 'Задължително за опаковката';
                    }
                }
            }
        }

        if(countR($error)){
            $res['error'] = implode('<li>', $error);
        }
        if(countR($errorFields)){
            $res['errorFields'] = $errorFields;
        }

        return $res;
    }


    /**
     * Подготвя параметрите на пратката за API-то
     *
     * @param stdClass $rec    - записа на формата
     * @param string   $action - calculate или shipment
     * @return array
     */
    private static function prepareBolData($rec, $action = 'shipment')
    {
        $res = array();

        // Подател и приемане
        $res['sender'] = $rec->senderName ?? null;
        $res['source_person'] = $rec->senderPerson ?? null;
        $res['source_phone'] = $rec->senderPhone ?? null;
        $res['source_instructions'] = $rec->sourceInstructions ?? null;
        if($action == 'shipment'){
            $res['source_person_personal_number'] = $rec->senderPersonalNumber ?? null;
        }

        if($rec->pickupType == 'warehouse'){
            $res['warehouse'] = $rec->warehouse;
        } elseif($rec->pickupType == 'office'){
            $res['source_courier'] = false;
            $res['source_station'] = $rec->sourceStation;
        } else {
            $res['source_country'] = self::getCountryCode($rec->sourceCountryId);
            $res['source_post_code'] = $rec->sourcePCode;
            $res['source_settlement'] = $rec->sourcePlace;
            $res['source_street'] = $rec->sourceAddress;
        }

        // Получател и доставка
        $res['recipient'] = $rec->recipientName;
        $res['target_recipient_person'] = $rec->recipientPerson;
        $res['target_recipient_phone'] = $rec->recipientPhone;
        $res['target_instructions'] = $rec->targetInstructions ?? null;
        if($rec->deliveryType == 'office'){
            $res['target_courier'] = false;
            $res['target_station'] = $rec->targetStation;
        } else {
            $res['target_country'] = self::getCountryCode($rec->targetCountryId);
            $res['target_post_code'] = $rec->targetPCode;
            $res['target_settlement'] = $rec->targetPlace;
            $res['target_street'] = $rec->targetAddress;
        }

        // Групите пакети: теглото е общо за групата, размерите са на един пакет в метри
        $Double = core_Type::getByName('double');
        foreach (array_values(type_Table::toArray($rec->parcelInfo)) as $i => $group){
            $res["piece[{$i}].parcels"] = (int)$Double->fromVerbal($group->count ?? '');
            $res["piece[{$i}].weight"] = $Double->fromVerbal($group->weight ?? '');
            $res["piece[{$i}].packaging"] = $group->packaging ?? null;
            foreach (array('length' => 'dim_x', 'width' => 'dim_y', 'height' => 'height') as $ourFld => $theirFld){
                $value = $group->{$ourFld} ?? '';
                if(strlen((string)$value)){
                    $res["piece[{$i}].{$theirFld}"] = round($Double->fromVerbal($value) / 100, 3);
                }
            }
        }

        $res['description'] = $rec->description;
        $res['reference_code'] = $rec->referenceCode ?? null;
        $res['shipment_service_type'] = $rec->service ?? null;
        if(!empty($rec->pickupDate)){
            $res['pickup_date'] = dt::mysql2timestamp($rec->pickupDate) * 1000;
        }
        $res['paid_on_delivery'] = ($rec->payer == 'recipient');

        if(($rec->haveCodPayment ?? null) == 'yes'){
            $res['cash_on_delivery'] = true;
            $res['declared_value'] = $rec->codAmount;
            $res['cod_payment_type'] = (($rec->codPaymentType ?? null) == 'contract') ? null : ($rec->codPaymentType ?? null);
            $res['postal_money_transfer'] = (($rec->isPostal ?? null) == 'yes');
        }

        if(($rec->haveInsurance ?? null) == 'yes'){
            $res['insurance_liability'] = $rec->insuranceAmount;
            $res['insurance_fragile'] = (($rec->isFragile ?? null) == 'yes');
        }

        foreach (type_Set::toArray($rec->services ?? '') as $service){
            $res[$service] = true;
        }

        return $res;
    }


    /**
     * Калкулира цената на товарителницата
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return object $obj           - информация за шаблона и цената
     */
    public function calculateShipmentRes($mvc, $documentRec, &$form)
    {
        $obj = (object)array('price' => null, 'tpl' => null);

        try{
            $res = transpress_Adapter::calculate(self::prepareBolData($form->rec, 'calculate'));
        } catch(core_exception_Expect $e){
            self::setApiErrors($form, $e, 'Цената не може да бъде изчислена');

            return $obj;
        }

        $currencyId = transpress_Adapter::getCurrencyCode($res['price_vat']['currency'] ?? null);
        $Double = core_Type::getByName('double(decimals=2)');
        $decorate = function ($amount) use ($Double, $currencyId) {
            return currency_Currencies::decorate($Double->toVerbal($amount), $currencyId);
        };

        $tpl = getTplFromFile('transpress/tpl/CalculationResult.shtml');

        // Изчислението заменя инфото на формата, затова предупреждението се повтаря
        $tpl->prepend(self::getTestNotice());
        $tpl->replace($decorate($res['price']['amount'] ?? null), 'price');
        $tpl->replace($decorate($res['vat']['amount'] ?? null), 'vat');
        $tpl->replace($decorate($res['price_vat']['amount'] ?? null), 'priceWithVat');

        $componentNames = array('TRANSPORT' => 'Транспорт', 'FUEL_TAX' => 'Горивна такса', 'CASH_ON_DELIVERY' => 'Наложен платеж', 'RETURN_POD' => 'Обратна разписка', 'VA_TAX' => 'ДДС');
        foreach ((array)($res['components'] ?? array()) as $component){
            $type = (string)($component['type'] ?? '');
            $name = isset($componentNames[$type]) ? tr($componentNames[$type]) : type_Varchar::escape($type);
            $tpl->append("<div>{$name}: " . $decorate($component['quantity']['amount'] ?? null) . '</div>', 'ADDITIONAL');
        }

        $obj->tpl = $tpl;
        $obj->price = (object)array('total' => $res['price_vat']['amount'] ?? null, 'currency' => $currencyId);

        return $obj;
    }


    /**
     * Връща файл хендлъра на генерираната товарителница след Request-а
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @return object $obj           - price, fh, number и status (cond_CourierApiIntf::BOL_*)
     */
    public function getRequestedShipmentRes($mvc, $documentRec, &$form)
    {
        $preparedBolParams = self::prepareBolData($form->rec);
        $obj = (object)array('price' => null, 'fh' => null, 'number' => null, 'status' => cond_CourierApiIntf::BOL_REJECTED);

        try{
            $res = transpress_Adapter::import($preparedBolParams);
        } catch(core_exception_Expect $e){
            if($e->getType() == transpress_Adapter::NO_RESPONSE_TYPE){
                $obj->status = cond_CourierApiIntf::BOL_UNKNOWN;
            }
            self::setApiErrors($form, $e, 'Товарителницата не можа да се създаде');

            return $obj;
        }

        // Номерът се записва преди PDF-а, за да не се издаде втора при неуспешен печат
        $obj->number = $res->manifest;
        $obj->status = cond_CourierApiIntf::BOL_ISSUED_NO_PDF;
        $obj->price = $res->price;
        unset($preparedBolParams['source_person_personal_number']);
        $preparedBolParams['printFormat'] = $form->rec->printFormat;

        $bolRec = (object)array('containerId' => $documentRec->containerId, 'number' => $res->manifest, 'barcodes' => implode(', ', $res->barcodes),
                                'price' => $res->price->total ?? null, 'currencyId' => $res->price->currency ?? null, 'state' => 'active', 'data' => $preparedBolParams,
                                'environment' => transpress_Adapter::getEnvironment(), 'account' => transpress_Adapter::getLoginData()['userName'] ?? null);
        transpress_WayBills::save($bolRec);

        $cacheArr = array();
        foreach (self::$rememberFields as $fld){
            $cacheArr[$fld] = $form->rec->{$fld} ?? null;
        }
        core_Permanent::set(self::getUserDataCacheKey($documentRec->folderId), $cacheArr, core_Permanent::FOREVER_VALUE);

        try{
            $fh = transpress_Adapter::printPdf($res->manifest, $form->rec->printFormat);
        } catch(core_exception_Expect $e){
            $form->setError('printFormat', "Товарителница|* <b>{$res->manifest}</b> |е създадена, но PDF-ът ѝ не можа да се изтегли|*!");

            return $obj;
        }

        $bolRec->file = $fh;
        transpress_WayBills::save($bolRec, 'file');
        $obj->fh = $fh;
        $obj->status = cond_CourierApiIntf::BOL_ISSUED;

        return $obj;
    }


    /**
     * Показва грешките от API-то при съответните полета на формата
     *
     * @param core_Form             $form
     * @param core_exception_Expect $e
     * @param string                $title - общо съобщение
     */
    private static function setApiErrors($form, $e, $title)
    {
        $errors = ($e instanceof transpress_Exception) ? $e->errors : array();
        if(!countR($errors)){
            $errors[] = $e->getMessage();
        }

        $general = array();
        foreach ($errors as $path => $msg){
            $field = is_string($path) ? (self::$errorFields[$path] ?? null) : null;
            if(isset($field) && $form->getField($field, false) && ($form->fields[$field]->input ?? null) != 'none'){
                $form->setError($field, '|*' . type_Varchar::escape($msg));
            } else {
                $general[] = type_Varchar::escape($msg);
            }
        }

        if(countR($general) || !$form->gotErrors()){
            $form->setError('service', "|{$title}|*: " . implode('; ', $general));
        }
    }


    /**
     * Предупреждение, че заявките отиват в тестовата среда
     *
     * @return string
     */
    private static function getTestNotice()
    {
        if(transpress_Adapter::getEnvironment() != 'test') return '';

        return "<div class='richtext-message richtext-warning'><b>" . tr('ТЕСТОВА СРЕДА') . '</b>: ' . tr('|товарителниците се създават в тестовата система на|* <b>Transpress</b> |и не се изпълняват|*.') . '</div>';
    }


    /**
     * Двубуквеният код на държавата
     *
     * @param int|null $countryId
     * @return string|null
     */
    private static function getCountryCode($countryId)
    {
        if(empty($countryId)) return null;
        $code = drdata_Countries::fetchField($countryId, 'letterCode2');

        return strlen((string)$code) ? strtoupper($code) : null;
    }


    /**
     * Какъв е ключа на потребителския кеш
     *
     * @param int $folderId
     * @return string $key
     */
    private static function getUserDataCacheKey($folderId)
    {
        // Каталожните кодове не са гарантирано еднакви между средите и акаунтите
        $cu = core_Users::getCurrent('id', false);
        $login = transpress_Adapter::getLoginData();
        $account = md5(transpress_Adapter::getEnvironment() . '|' . ($login['userName'] ?? ''));

        return "transpress_{$folderId}_{$cu}_{$account}";
    }


    /**
     * След подготовка на формата за товарителница
     *
     * @param core_Mvc $mvc          - модел
     * @param stdClass $documentRec  - запис на документа от който ще се генерира
     * @param core_Form $form        - формата за генериране на товарителница
     * @param core_ET $tpl           - шаблона на формата
     * @return void
     */
    public function afterPrepareWayBillForm($mvc, $documentRec, $form, &$tpl)
    {
    }


    /**
     * Текст за имейла с проследяването на последната товарителница
     *
     * @param core_Mvc $mvc
     * @param int|stdClass $id
     * @return core_ET|null
     */
    public function getDefaultEmailBody($mvc, $id)
    {
        if(!($mvc instanceof store_ShipmentOrders)) return null;

        $rec = $mvc->fetchRec($id);
        $bolRec = self::getLastBolRec($rec->containerId);
        if(empty($bolRec)) return null;

        $url = transpress_Adapter::getTrackingUrl($bolRec->number, $bolRec->environment);

        $bolTpl = new ET(tr("|*\n|Вашата пратка е подготвена за изпращане с товарителница|* [#number#].\n|Може да проследите получаването ѝ от тук|*: [#URL#]"));
        $bolTpl->replace($url, 'URL');
        $bolTpl->replace($bolRec->number, 'number');

        return $bolTpl;
    }


    /**
     * Издадените товарителници към документа, от последната към първата
     *
     * @param int $containerId
     * @return array $res - обекти с number, date, file и state
     */
    public function getWayBills($containerId)
    {
        $res = array();
        $query = transpress_WayBills::getQuery();
        $query->where(array('#containerId = [#1#]', $containerId));
        $query->orderBy('id', 'DESC');
        while($bolRec = $query->fetch()){
            $res[] = (object)array('number' => $bolRec->number, 'date' => $bolRec->createdOn, 'file' => $bolRec->file, 'state' => $bolRec->state);
        }

        return $res;
    }


    /**
     * Отказва издадена товарителница към документа при куриера
     *
     * @param int    $containerId - контейнер на документа
     * @param string $number      - номер на товарителницата
     * @param string $reason      - причина
     * @return stdClass {status, error}
     */
    public function cancelWayBill($containerId, $number, $reason)
    {
        $rec = transpress_WayBills::fetch(array("#containerId = [#1#] AND #number = '[#2#]'", $containerId, $number));
        if(empty($rec)){

            return (object)array('status' => 'notFound', 'error' => null);
        }

        if($rec->state == 'rejected'){

            return (object)array('status' => cond_CourierApiIntf::BOL_CANCELLED, 'error' => null, 'already' => true);
        }

        if(!transpress_WayBills::haveRightFor('reject', $rec)){

            return (object)array('status' => 'noRights', 'error' => null);
        }

        return cls::get('transpress_WayBills')->cancel($rec, $reason, 'Отказана товарителница от Аида');
    }


    /**
     * Последната неотказана товарителница към документа
     *
     * @param int $containerId
     * @return stdClass|false
     */
    private static function getLastBolRec($containerId)
    {
        $query = transpress_WayBills::getQuery();
        $query->where(array("#containerId = [#1#] AND #state = 'active'", $containerId));
        $query->orderBy('id', 'DESC');
        $query->limit(1);

        return $query->fetch();
    }
}
