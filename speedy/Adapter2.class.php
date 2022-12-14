<?php


/**
 * Адаптер за връзка с REST API на Speedy
 *
 * Пълна документация
 * @see https://api.speedy.bg/web-api.html
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 * @docVersion 1.49
 *
 * @since     v 0.1
 */
class speedy_Adapter2 extends core_BaseClass
{
    /**
     * Изпълнение на заявка към API-то на спиди
     *
     * @param string $param           - параметрите на АПИ-то
     * @param array $jsonData         - данните, които се подават на АПИ-то
     * @param boolean $parseResponse  - да се парсира ли отговора или директно да се сервива
     * @return bool|mixed|string      - резултата от рекуеста към АПИ-то
     * @throws core_exception_Expect
     */
    public static function call($param, $jsonData = array(), $parseResponse = true)
    {
        // Подготовка на урл-то и параметрите
        $url = static::prepareUrl($param);
        $loginData = static::getLoginData();
        $jsonData = $loginData + $jsonData;
        $jsonDataEncoded = json_encode($jsonData);

        // Подготвяне на curl обекта
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1); // Tell cURL that we want to send a POST request.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Verify the peer's SSL certificate.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Stop showing results on the screen.
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); // The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Set the content type to application/json
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonDataEncoded); // Attach our encoded JSON string to the POST fields.

        // Изпълняване и взимане на резултат
        $jsonResponse = @curl_exec($curl);
        if(!$parseResponse) return $jsonResponse;

        // Парсиране на резултат
        $errorMsg = null;
        $res = static::parseResponse($jsonResponse,$errorMsg);

        if(!empty($errorMsg)){
            // Ако е върната грешла да се сетне
            throw new core_exception_Expect($errorMsg, 'Несъответствие');
        }

        return $res;
    }


    /**
     * Помощна фунмкция за подготвяне на JSON резултата
     *
     * @param string $json     - данни за парсиране
     * @param string $errorMsg - съобщение за грешка
     * @return mixed
     */
    protected static function parseResponse($json, &$errorMsg = null)
    {
        if (!trim($json)) {
            $errorMsg = 'Празен отговор от сървъра';

            return false;
        }

        $response = @json_decode($json);
        if(isset($response->error->message)){
            if($response->error->message == 'bg.error.invalid.credentials'){
                $errorMsg = 'Невалидно потребителско име и/или парола';
            }

            $errorMsg = $response->error->message;
            log_Data::logErr($errorMsg);

            return false;
        }

        if(empty($response)){
            $errorMsg = 'Празен отговор от сървъра';

            return false;
        }

        return $response;
    }



    /**
     * Помощна фунцкия за подготвяне на URL за заявка
     *
     * @param string $param
     * @return string $url
     */
    private static function prepareUrl($param)
    {
        $url = speedy_Setup::get('API_BASE_URL');
        $url = rtrim($url, '/');
        $url .= '/' . ltrim($param, '/');

        return $url;
    }


    /**
     * Помощна ф-я връщаща данните за логин
     *
     * @return array
     */
    public static function getLoginData($userId = null)
    {
        $res = array('userName' => speedy_Setup::get('DEFAULT_ACCOUNT_USERNAME', false, $userId),
                     'password' => speedy_Setup::get('DEFAULT_ACCOUNT_PASSWORD', false, $userId),
                     'language' => 'BG',
        );

        return $res;
    }


    /**
     * Връща клиентските обекти
     *
     * @return array
     * ключ - id, от тяхната системата
     * ['clientId'] (int) - id, от тяхната системата
     * ['clientName'] (string) - име на клиентския обект
     * ['contactName'] (string) - име на лицето за контакт
     * ['address'] stdClass - адреса на обекта
     *      countryId : (int) ид на държава от тяхната система
     *      siteId : (int) ид на обект от тяхната система
     *      siteType : (string) тип на обекта
     *      siteName : (string) име на обекта
     *      postCode : (string) П.код на обекта
     *      streetId : (int) ид на улица от тяхната система
     *      streetType : (string) тип на улицата
     *      streetName : (string) име на обекта
     *      streetNo : (string) номер на улицата
     *      x : (double) координати x
     *      y : (double) координати y
     *      fullAddressString : (string) пълно име на адреса
     *      siteAddressString : (string) пълно име на локацията
     *      localAddressString : (string) локално име на локацията
     * ['email'] string - имейл
     * ['privatePerson'] boolean - дали е частно лице
     * ['phones'] array - телефони за контакт
     */
    public static function getClients()
    {
        $clientRes = static::call('client/contract');
        if(is_array($clientRes->clients)) return $clientRes->clients;

        return array();
    }


    /**
     * Ид-то отговарящо на държавата от номенклатурата на Speedy
     *
     * @param mixed $country
     *
     * @return array $res
     *  ключ - id, от тяхната системата
     * ['id'] (int) - id, от тяхната системата
     * ['name'] (string) - име на държавата
     * ['nameEn'] (string) - име на държавата на английски
     * ['isoAlpha2'] (string) - двубуквен код на държавата
     * ['isoAlpha3'] (string) - трибуквен код на държавата
     * ['currencyCode'] (string) - код на валутата
     */
    public static function getCountries($name = null)
    {
        $jsonData = array();
        if(isset($name)){
            $name = is_numeric($name) ? drdata_Countries::getTitleById($name) : $name;
            $jsonData['name'] = $name;
        }

        $res = array();
        $clientRes = static::call('location/country/', $jsonData);
        foreach ((array)$clientRes->countries as $country) {
            $res[$country->id] = array();
            $res[$country->id]['id'] = $country->id;
            $res[$country->id]['name'] = $country->name;
            $res[$country->id]['nameEn'] = $country->nameEn;
            $res[$country->id]['isoAlpha2'] = $country->isoAlpha2;
            $res[$country->id]['isoAlpha3'] = $country->isoAlpha3;
            $res[$country->id]['currencyCode'] = $country->currencyCode;
        }

        return $res;
    }


    /**
     * Ид-то отговарящо на държавата от номенклатурата на Speedy
     *
     * @param mixed $country
     * @return int
     */
    public static function getCountryId($country)
    {
        $countryArr = static::getCountries($country);

        return key($countryArr);
    }


    /**
     * Връща опциите за избор на клиентска локация
     *
     * @return array $options
     */
    public static function getSenderClientOptions()
    {
        $options = array();
        try{
            $clientIds = static::getClients();
        } catch(core_exception_Expect $e){
            return $options;
        }

        foreach ($clientIds as $clientObj){
            $options[$clientObj->clientId] = "{$clientObj->address->postCode} {$clientObj->address->fullAddressString}";
        }

        return $options;
    }

    /**
     * Списък с офисите в подадената държава
     *
     * @param int $theirCountryId - ид на държава в тяхната система
     *
     * @return array $res
     *  ключ - id, от тяхната системата
     * ['id'] (int) - id, от тяхната системата
     * ['name'] (string) - име на офиса
     * ['nameEn'] (string) - п. код на офиса
     * ['address'] (string) - адрес
     * ['pickUpAllowed'] (string) - позволено ли е взимане на пратка
     * ['dropOffAllowed'] (string) - позволено ли е оставяне на пратка
     * ['cashPaymentAllowed'] (string) - позволено ли е плащане в брой
     * ['cardPaymentAllowed'] (string) - позволено ли е картово плащане
     */
    public static function getOffices($theirCountryId)
    {
        $res = array();
        $response = static::call('location/office/', array('countryId' => $theirCountryId));

        foreach ((array)$response->offices as $office) {
            $res[$office->id] = array();
            $res[$office->id]['id'] = $office->id;
            $res[$office->id]['name'] = $office->name;
            $res[$office->id]['pCode'] = $office->address->postCode;
            $res[$office->id]['address'] = $office->address->fullAddressString;
            $res[$office->id]['pickUpAllowed'] = $office->pickUpAllowed;
            $res[$office->id]['dropOffAllowed'] = $office->dropOffAllowed;
            $res[$office->id]['cashPaymentAllowed'] = $office->cashPaymentAllowed;
            $res[$office->id]['cardPaymentAllowed'] = $office->cardPaymentAllowed;
        }

        return $res;
    }


    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $senderClientId               - ид на обекта от който ще се взима
     * @param int $toCountry                    - към коя държава
     * @param int|null $toPCode                 - към кой пощенски код
     * @param int|null $toOfficeId              - до кой офис
     * @param boolean $isRecepientPrivatePerson - дали получателя е частно лице
     * @param date|null $date                   - към коя дата
     *
     * @return array $res
     *  ключ - id, от тяхната системата
     * ['id'] (int) - id, от тяхната системата
     * ['name'] (string) - име на български на услугата
     * ['nameEn'] (string) - име на услугата на английски
     * ['requireParcelWeight'] (boolean) - изисква ли се тегло на всеки парцел
     * ['requireParcelSize'] (boolean) - изисква ли се размера на всеки парцел
     * ['cargoType'] (string) - тип на стоката [“PARCEL”, “PALLET”, “TYRE”]
     * ['additionalServices'] stdClass - допълнителни услуги
     *      -> cod (string) - наложен платеж Forbidden/Allowed/REQUIRED
     *      -> obpd (stdClass) - опции преди доставка Forbidden/Allowed/REQUIRED
     *      -> declaredValue (stdClass) - обявена стойност  Forbidden/Allowed/REQUIRED
     *      -> fixedTimeDelivery (stdClass) - обявено време Forbidden/Allowed/REQUIRED
     *      -> specialDelivery (stdClass) - специална доставка Forbidden/Allowed/REQUIRED
     *      -> deliveryToFloor (stdClass) - доставка до етаж Forbidden/Allowed/REQUIRED
     *      -> rod (stdClass) - връщане на документи Forbidden/Allowed/REQUIRED
     *      -> returnReceipt (stdClass) - обратна бележка Forbidden/Allowed/REQUIRED
     *      -> swap (stdClass) - SWAP Forbidden/Allowed/REQUIRED
     *      -> rop (stdClass) - връщане на палети Forbidden/Allowed/REQUIRED
     *      -> returnVoucher (stdClass) - връщане на ваучер Forbidden/Allowed/REQUIRED
     *
     */
    public static function getServicesBySites($senderClientId, $toCountry, $toPCode, $toOfficeId, $isRecepientPrivatePerson = false, $date = null)
    {
        $date = isset($date) ? $date : date('Y-m-d');
        $jsonData = array(
            'date' => $date,
            'sender' => array('clientId' => $senderClientId),
            'recipient' => array('privatePerson' => ($isRecepientPrivatePerson == 'yes')),
        );

        if(isset($toOfficeId)){
            $jsonData['recipient']['pickupOfficeId'] = $toOfficeId;
            unset($jsonData['recipient']['addressLocation']);
        } else {
            $toCountryId = static::getCountryId($toCountry);
            $jsonData['recipient']['addressLocation'] = array('countryId' => $toCountryId, 'postCode' => $toPCode);
        }

        $res = array();
        try{
            $response = static::call('services/destination', $jsonData);
        } catch (core_exception_Expect $e){
            return $res;
        }

        foreach ((array)$response->services as $service) {
            $res[$service->id] = array();
            foreach (array('id', 'name', 'nameEn', 'requireParcelWeight', 'requireParcelSize', 'cargoType', 'requireParcelWeight', 'requireParcelSize') as $fld){
                $res[$service->id][$fld] = $service->{$fld};
            }

            $res[$service->id]['additionalServices'] = array();
            foreach ((array)$service->additionalServices as $key => $additionalService){
                $res[$service->id]['additionalServices'][$key] = $additionalService->allowance;
            }
        }

        return $res;
    }


    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $senderClientId               - ид на обекта от който ще се взима
     * @param int $toCountry                    - към коя държава
     * @param int|null $toPCode                 - към кой пощенски код
     * @param int|null $toOfficeId              - до кой офис
     * @param boolean $isRecepientPrivatePerson - дали получателя е частно лице
     * @param date|null $date                   - към коя дата
     *
     * @return array $res
     *
     */
    public static function getServiceOptions($senderClientId, $toCountry, $toPCode, $toOfficeId, $isRecepientPrivatePerson = false, $date = null)
    {
        $res = array();
        $services = static::getServicesBySites($senderClientId, $toCountry, $toPCode, $toOfficeId, $isRecepientPrivatePerson, $date);
        $lg = core_Lg::getCurrent();
        $nameFld = ($lg == 'bg') ? 'name' : 'nameEn';
        foreach ($services as $serviceId => $service){
            $res[$serviceId] = $service[$nameFld];
        }

        return $res;
    }


    /**
     * Печата пдф от подадените товарителници
     *
     * @param array $parcels         - масив с ид-та от създадени в спиди товарителници
     * @param string $paperSize      - размер на печата
     * @return string|null $fh       - файл хендлър или null, ако не се е създал
     * @throws core_exception_Expect
     */
    public static function printWaybillPdf($parcels, $paperSize = 'A4')
    {
        $jsonData = array('paperSize' => $paperSize);
        foreach ($parcels as $parcelId){
            $jsonData['parcels'][] = array('parcel' => array('id' => $parcelId));
        }

        // Опит за печат на товарителниците
        $res = static::call('print/', $jsonData, false);
        if(empty($res)) return null;

        // Абсорбиране и генериране на име на файла
        $userName = speedy_Setup::get('DEFAULT_ACCOUNT_USERNAME');
        $fileNameOnly = $userName . '_picking_' . $parcels[0] . '_' . dt::mysql2verbal(null, 'd.m.Y.H.i') . '.pdf';
        $fh = fileman::absorbStr($res, 'billOfLadings', $fileNameOnly);

        return $fh;
    }


    /**
     * Кои са наличните услуги за доставка до мястото
     *
     * @param int $theirCountryId - ид на държава в тяхната система
     * @param string $pCode       - п.код
     * @param string|null $name   - име на обекта
     *
     * @return array $res
     *  ключ - id, от тяхната системата
     * ['id'] (int) - id, от тяхната системата
     * ['countryId'] (int) - ид на държава
     * ['mainSiteId'] (int) - ид на главното място
     * ['type'] (string) - тип на обекта (бг)
     * ['typeEn'] (string) - тип на обекта (ен)
     * ['name'] (string) - наименование (бг)
     * ['nameEn'] (string) - наименование (ен)
     * ['municipality'] (string) - област (бг)
     * ['municipalityEn'] (string) - област (ен)
     * ['region'] (string) - регион (бг)
     * ['regionEn'] (string) - регион (ен)
     * ['postCode'] (string) - пощенски код
     * ['servingDays'] (string) - работни дни
     * ['addressNomenclature'] (string) - номенклатура на адреса
     * ['x'] double - координати по x
     * ['y'] double - координати по y
     * ['servingOfficeId'] (int) - ид на обслужващ офис
     * ['servingHubOfficeId'] (int) - ид на главен офис
     */
    public static function getSites($theirCountryId, $pCode, $name = null)
    {
        $res = array();
        $params = array('countryId' => $theirCountryId, 'postCode' => $pCode, 'name' => $name);

        try{
            $response = static::call('location/site/', $params);
        } catch (core_exception_Expect $e){
            return $res;
        }

        foreach ((array)$response->sites as $site) {
            $res[$site->id] = $site;
        }

        return $res;
    }










    /**
     * Генерира товарителница с подадените данни
     *
     *
     * @param $params
     *
     * ['clientSystemId'] (int) - ид на клиента
     * ['sender'] (array) - ид на клиента
     *      ['clientId'] (int) - ид на клиента
     *      ['phone1'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['phone2'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['phone3'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['clientName'] (string) - име на клиента
     *      ['contactName'] (string) - име на контакта
     *      ['email'] (string) - имейл на изпращача
     *      ['privatePerson'] (boolean) - дали е частно лице или не
     *      ['address'] (array) - адрес на изпращача
     *          @see https://api.speedy.bg/web-api.html#href-ds-shipment-address
     *      ['dropoffOfficeId'] (int) - офис в който да остави пратката изпращача
     * ['recipient'] (array) - ид н клиента
     *      ['phone1'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['phone2'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['phone3'] (array) - телефони
     *          ['number'] (string) - номер
     *      ['clientName'] (string) - име на получателя
     *      ['objectName'] (string) - обект на получателя
     *      ['contactName'] (string) - лице на контакт за получателя
     *      ['email'] (string) - имейл на получателя
     *      ['privatePerson'] (bool) - дали е частно лице
     *      ['address'] (array) - адрес на получателя
     *          @see https://api.speedy.bg/web-api.html#href-ds-shipment-address
     *      ['pickupOfficeId'] (int) - ид на офис за получаване
     * ['service'] (array) - използваните услуги
     *      ['pickupDate'] (date) - дата за вземане
     *      ['autoAdjustPickupDate'] (bool) - дали да се поправи датата с първата налична
     *      ['serviceId'] (int) - ид на услуга
     *      ['additionalServices'] (array) - допълнителни услуги
     *          ['cod'] (array) - наложен платеж
     *              ['amount'] (double) - сума наложен платеж
     *              ['currencyCode'] (double) - код на валута
     *              ['processingType'] (string) - като пощенски пар. превод или като плащане в брой CASH/POSTAL_MONEY_TRANSFER
     *              ['payoutToThirdParty'] (bool) - плащане на трето лице
     *              ['payoutToLoggedClient'] (bool) - плащане на логнат клиент
     *              ['includeShippingPrice'] (bool) - включена ли е доставката в НП
     *              ['cardPaymentForbidden'] (bool) - разрешено ли е плащането с карта
     *          ['obpd'] (array) - опции преди получаване
     *              ['option'] (string) - отваряне или тест OPEN/TEST
     *              ['returnShipmentServiceId'] (int) - използвана услуга за връщане
     *              ['returnShipmentPayer'] (string) - платец при връщането SENDER/RECIPIENT/THIRD_PARTY
     *          ['declaredValue'] (array) - обявена стойност
     *              ['amount'] (double) - сума
     *              ['fragile'] (bool) - чупливо ли е
     *              ['ignoreIfNotApplicable'] (bool) - игнориране ако не може
     *          ['returns'] (array) - услуги за връщане
     *              ['rod'] (array) - връщане на документи
     *                  ['enabled'] (bool) - разрешено ли е
     *                  ['comment'] (string) - разрешено ли е
     *                  ['returnToClientId'] (int) - връщане на клиент
     *                  ['returnToOfficeId'] (int) - връщане до ид на офис
     *                  ['thirdPartyPayer'] (bool) - трето лице платец?
     *              ['returnReceipt'] (array) - връщане на бележка
     *                  ['enabled'] (bool) - разрешено ли е
     *                  ['returnToClientId'] (int) - връщане на клиент
     *                  ['returnToOfficeId'] (int) - връщане до ид на офис
     *                  ['thirdPartyPayer'] (bool) - трето лице платец?
     *              ['swap'] (array) - връщане на обратна пратка
     *                  ['serviceId'] (bool) - използвана услуга
     *                  ['parcelsCount'] (string) - палети за връщане
     *                  ['declaredValue'] (int) - обявена стойност
     *                  ['fragile'] (int) - чупливо ли е
     *                  ['returnToOfficeId'] (bool) -  връщане до ид на офис
     *                  ['thirdPartyPayer'] (bool) - трето лице платец?
     *              ['rop'] (array) - връщане на палети
     *                  ['pallets'] (array) - палети за връщане
     *                      ['serviceId'] (int) - ид на услуга
     *                      ['parcelsCount'] (int) - брой палети
     *                  ['thirdPartyPayer'] (bool) - трето лице платец?
     *              ['returnVoucher'] - ваучер за връщане
     *                  ['serviceId'] (int) - ид на услуга
     *                  ['payer'] (int) - платец SENDER/RECIPIENT/THIRD_PARTY
     *                  ['validityPeriod'] (int) - период на валидност
     *          ['fixedTimeDelivery'] (string) - фиксирано време на доставка
     *          ['deliveryToFloor'] (int) - доставка до етаж
     *      ['deferredDays'] (int) - отлагане преди вземане
     *      ['saturdayDelivery'] (bool) - доставка в събота
     * ['content'] (array) - съдържание на пратката
     *      ['parcelsCount'] (int) - брой палети
     *      ['totalWeight'] (double) - общо тегло
     *      ['contents'] (string) - съдържание
     *      ['package'] (string) - опаковка
     *      ['documents'] (bool) - има ли документи
     *      ['palletized'] (bool) - палетизирано ли е
     *      ['parcels'] (array) - описание на индивидуалните палети
     *          ['id'] (string) - ид на палета
     *          ['seqNo'] (int) - пореден номер на палета
     *          ['size'] (array) - размери на палета
     *              ['height'] (double) - височина на палета
     *              ['width'] (double) - широчина на палета
     *              ['depth'] (double) - дълбочина на палета
     *          ['externalCarrierParcelNumber'] (string) - външен номер на палета
     *          ['ref1'] (string) - референция 1
     *          ['ref2'] (string) - референция 2
     *      ['pendingParcels'] (bool) -
     *      ['exciseGoods'] (bool) -
     * ['payment'] (array) - плащания
     *      [`courierServicePayer`] (string) - платец на обратната услуга SENDER/RECIPIENT/THIRD_PARTY
     *      [`declaredValuePayer`] (string) - платец на обявената стойностSENDER/RECIPIENT/THIRD_PARTY
     *      [`packagePayer`] (string) - платец на опаковката SENDER/RECIPIENT/THIRD_PARTY
     *      ['thirdPartyClientId'] (int) - ид на третата страна
     *      ['administrativeFee'] (bool) - администратична такса
     *      ['discountCardId'] (array) - карта за отстъпка
     *          ['contractId'] (int) - ид на договор
     *          ['cardId'] (int) - ид на картата
     *      ['senderBankAccount'] (array) - банкова информация за изпращача
     *          ['iban'] (string) - ибан
     *          ['accountHolder'] (string) - титуляр
     * ['shipmentNote'] (string) - забележка към товарителницата
     * ['ref1'] (string) - реф 1
     * ['ref2'] (string) - реф 2
     * ['consolidationRef'] (string) - референс
     * ['requireUnsuccessfulDeliveryStickerImage'] (boolean)
     *
     * @return bool|mixed|string $res - обект с генерираната товарителница в тяхната система
     * @throws core_exception_Expect
     */
    public static function requestShipment($params)
    {
        $res = speedy_Adapter2::call('shipment/', $params);

        return $res;
    }


    /**
     * Калкулиране на прогнозна цена за изпращане на пратката
     *
     * @param $params (@see https://api.speedy.bg/web-api.html#href-calculation-service)
     *
     * @return mixed - обект с информация за калкулираните цени
     * @throws core_exception_Expect
     */
    public static function calculateShipment($params)
    {
        $res = speedy_Adapter2::call('calculate/', $params);

        return $res;
    }
}