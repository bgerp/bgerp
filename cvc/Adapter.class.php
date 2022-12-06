<?php


/**
 * Връзка с CVC
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 * @docVersion 1.49
 *
 * @since     v 0.1
 */
class cvc_Adapter
{


    /**
     * Държава по подразиране
     * id = 100 - BG
     */
    const DEFAULT_COUNTRY_ID = 100;


    /**
     * Калкилиране на товарителница
     *
     * @param array $params
     * ['parcel_type'] * enum(parcel=пакетна,pallet=палетна,tires=гуми) * - Типа на пратката
     * ['pickup_date'] * date(YYYY-MM-DD) - дата, на която пратката да се вземе (дата на изпращане)
     * ['description'] * string - описание/съдържание на пратката
     * ['payer'] * enum(contract=по договор,sender=при изпращане, rec=при получаване) - плащане на услугата
     * ['total_kgs'] * double - тегло в кг
     * ['total_parcels'] * int - общ брой пакети, които се изпращат
     * ['parcels'] * array - списък с размерите на всики палети/гуми. Не е задължително за parcel (пакетни пратки)
     *  - за палетни пратки
     *   - ['parcels'][]->dim_w * int - широчина
     *   - ['parcels'][]->dim_d * int - дълбочина
     *   - ['parcels'][]->dim_h * int - височина
     *   - ['parcels'][]->kgs * int - килограми
     *  - за гуму - kgs
     *   - ['parcels'][]->kgs * int - килограми
     * ['sender'] * - данни за изпращача
     * ['sender']->custom_location_id int - id от getCustomLocations
     * ['sender']->hub_id int - id от getHubs
     * ['sender']->office_id int - id от getOffices
     * ['sender']->name string * - имена
     * ['sender']->phone string * - телефон
     * ['sender']->email email * - имейл
     * ['sender']->city_id int * - id от getCities
     * ['sender']->street string * - име на улица
     * ['sender']->zip string  * - пощенски код
     * ['sender']->country_id int - id от getCountries
     * ['sender']->street_id int - id ot getStreets
     * ['sender']->num string - улица номер
     * ['sender']->qt string - квартал
     * ['sender']->qt_id int - id от getQts
     * ['sender']->block string - блок номер
     * ['sender']->entr string - вход номер
     * ['sender']->floor string - етаж номер
     * ['sender']->ap string - апартамент номер
     * ['sender']->latlng string - координати
     * ['sender']->notes string - бележка към получателя
     *
     * ['rec'] * - данни за получателя - стркутурата е идентична като на sender
     * Ако се подаде custom_location_id, hub_id или office_id, няма нужда от country_id, city_id, street, street_id, num, zip, qt_id, block, entr, floor, ap, latlng
     * Ако не може да се парсира адреса, всичко може да се подаде в street
     *
     * ['ref1'] string - референция към пратката - показва се в етикета на товарителницата
     * ['ref2'] string -референция към пратката
     * ['fix_time'] time(HH:MM) - час на доставка
     * ['is_observe'] boolean - флаг - преглед на пратката при получаване
     * ['is_test'] boolean - флаг - тест на пратката при получаване
     * ['reject_payer'] enum(contract=по договор,sender=при изпращане, rec=при получаване) - кой ще плати върнатата пратка
     * ['cod_amount'] double - сумата на наложен платеж
     * ['is_cod_ppp'] boolean - флаг, наложения платеж да се изплати с пощенски паричен превод
     * ['os_value'] double - обявена стойност
     * ['is_fragile'] boolean - флаг, дали пратката е чуплива
     * ['is_sat'] boolean - флаг, дали може да се направи доставка в съботен ден
     * ['is_sms'] boolean - флаг, дали да се прати SMS (или друго известие) до получател (за сметка на платеца на услугата)
     * ['rent_pallet_60x80'] int - брой палатеи от съответния размер, които ще се наемат
     * ['rent_pallet_120x80'] int - брой палатеи от съответния размер, които ще се наемат
     * ['is_return_amb'] boolean - флаг, дали да се върне амбалаж след доставката
     * ['is_return_receipt'] boolean - флаг, далу да се върне обратна разписка след доставката
     * ['is_return_docs'] boolean - флаг, дали да се върнат документи след доставката
     *
     * @param boolean $create - ако не се подаде, няма да създава товарителница, само ще направи изчисления
     *
     * @return array|boolean
     * ['price'] - цена без ДДС
     * ['priceWithVAT'] - цена с ДДС
     * ['details'] - Масив с детайли за поръчката - такса гориво, цена на тегло, различните добавки
     * При създаване - $create === true
     * ['wb'] - id на създадената товарителница
     * ['wbItems'] - масив с id-та на разлините пакети в пратката
     * ['pickupDate'] - дата на вземане на пратката
     * ['deliveryDate'] - дата на доставка на пратката
     * ['pdf'] - PDF файл с товарителниците на пакетите - fileHnd
     * ['pdfOrig'] - PDF файл с товарителниците на пакетите - линк към тяхната система
     */
    public static function createWb($params = array(), $create = true)
    {
        $method = 'calculate_wb';
        if ($create) {
            $method = 'create_wb';
        }

        $res = self::makeCall($method, array('postFields' => $params));

        if (!$res) {

            return $res;
        }

        $resArr = array();
        $resArr['price'] = $res->price;
        $resArr['priceWithVAT'] = $res->price_with_vat;
        $resArr['details'] = $res->details;

        if ($create) {
            $resArr['wb'] = $res->wb;
            $resArr['wbItems'] = $res->wb_items;
            $resArr['pickupDate'] = $res->pickup_date;
            $resArr['deliveryDate'] = $res->delivery_date;
            if ($res->pdf) {
                $resArr['pdfOrig'] = $res->pdf;
                $resArr['pdf'] = self::getFileFromServer($res->pdf);
            }
        }

        return $resArr;
    }


    /**
     * Генериране на товарителница
     *
     * @param array $params
     * @param boolean $create
     *
     * @return array|boolean
     *
     * @see self::createWb() - параметрите и резултата са същите
     */
    public static function calculateWb($params = array())
    {

        return self::createWb($params, false);
    }


    /**
     * Отказване на създадена товарителница
     *
     * @param integer $wbId
     */
    public static function cancelWb($wbId)
    {
        setIfNot($params['postFields'], true);

        $res = self::makeCall('cancel_wb', array('postFields' => array('wb' => $wbId)));

        if (!$res) {

            return $res;
        }

        return $res->wb;
    }


    /**
     * Връща статуса на една товарителница
     * Подобна на getStatuses()
     *
     * @param integer $wbId - номер на товарителница за проследяване
     *
     * @return array|boolean
     * ['statusNum'] - техен номер на статуса
     * ['status'] - вербална стойност на статуса
     * ['statusDate'] - време на промяна на статуса
     * ['wb'] - $wbId, което сме подали
     * ['byPerson'] - лицето, което е направило действието - видимо само за доставените/отказаните пратки
     */
    public static function getStatus($wbId)
    {
        $res = self::makeCall('get_status', array('wb' => $wbId));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        $resArr['statusNum'] = $res->status_num;
        $resArr['status'] = $res->status;
        $resArr['statusDate'] = $res->status_date;
        $resArr['wb'] = $res->wb;
        if (isset($res->by_person)) {
            $resArr['byPerson'] = $res->by_person;
        }

        return $resArr;
    }


    /**
     * Връща статуса на много товарителници заедно
     * Подобна на getStatus()
     *
     * @param array $wbArr
     *
     * @return array|boolean
     * []['statusNum'] - техен номер на статуса
     * []['status'] - вербална стойност на статуса
     * []['statusDate'] - време на промяна на статуса
     * []['wb'] - $wbId, което сме подали
     * []['byPerson'] - лицето, което е направило действието - видимо само за доставените/отказаните пратки
     */
    public static function getStatuses($wbArr)
    {
        $res = self::makeCall('get_statuses', array('postFields' => $wbArr));

        if (!$res) {

            return $res;
        }

        $resArr = array();

        foreach ((array)$res->wbs as $k => $resWb) {
            $resArr[$k]['statusNum'] = $resWb->status_num;
            $resArr[$k]['status'] = $resWb->status;
            $resArr[$k]['statusDate'] = $resWb->status_date;
            $resArr[$k]['wb'] = $resWb->wb;
            if (isset($resWb->by_person)) {
                $resArr[$k]['byPerson'] = $resWb->by_person;
            }
        }

        return $resArr;
    }


    /**
     * Връща историята на статусите на една товарителница
     *
     * @param integer $wbId - номер на товарителница за проследяване
     *
     * @return array|boolean
     * ['statusNum'] - техен номер на статуса
     * ['status'] - вербална стойност на статуса
     * ['statusDate'] - време на промяна на статуса
     * ['byPerson'] - лицето, което е направило действието - видимо само за доставените/отказаните пратки
     */
    public static function getStatusHistory($wbId)
    {
        $res = self::makeCall('get_status_history', array('wb' => $wbId));

        if ($res === false) {

            return $res;
        }

        $resArr = array();

        foreach ((array)$res->history as $k => $history) {
            $resArr[$k]['statusDate'] = $history->status_date;
            $resArr[$k]['statusNum'] = $history->status_num;
            $resArr[$k]['status'] = $history->status;
            if (isset($history->by_person)) {
                $resArr[$k]['byPerson'] = $history->by_person;
            }
        }

        return $resArr;
    }


    /**
     * Връща всички налични държанив в системата
     *
     * @return array|boolean
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['nameEn'] - името на EN в тяхната система
     * ['code'] - двубоквен код от тяхната системата
     */
    public static function getCountries()
    {
        $res = self::makeCall('get_countries');

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->countries as $country) {
            $resArr[$country->id]['id'] = $country->id;
            $resArr[$country->id]['nameBg'] = $country->name_bg;
            $resArr[$country->id]['nameEn'] = $country->name_en;
            $resArr[$country->id]['code'] = $country->code;
        }

        return $resArr;
    }


    /**
     * Връща всички области от държавата
     *
     * @param null|integer $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return array|boolean
     * Масив с id (за ключ) и името на областта
     */
    public static function getCounties($countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $res = self::makeCall('get_counties', array('country_id' => $countryId));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->counties as $county) {
            $resArr[$county->id] = $county->name_bg ? $county->name_bg : $county->name_en;
        }

        return $resArr;
    }


    /**
     * Намира общините по зададен критерий
     *
     * @param string $q - стринг от името на общината
     * @param null|int $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return boolean|array
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['countryId'] - id на държавата
     * ['countyId'] - id на областта
     *
     */
    public static function getMunicipalities($q, $countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $q = mb_strtolower($q);
        $q = trim($q);

        expect($q);

        $res = self::makeCall('search_munis', array('country_id' => $countryId, 'search_for' => $q));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->munis as $munis) {
            $resArr[$munis->id]['nameBg'] = $munis->name_bg;
            $resArr[$munis->id]['countryId'] = $munis->country_id;
            $resArr[$munis->id]['countyId'] = $munis->county_id;
        }

        return $resArr;
    }


    /**
     * Намира населено място
     *
     * @param string $q - стринг от името на населеното място
     * @param null|int $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     * @param null|int $countyId - id на областта от getCounties
     * @param null|int $municipalityId - id na общината от getMunicipalities
     *
     * @return false|array
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['countyBg'] - името на обласста на БГ в тяхната система
     * ['muniBg'] - името на общината на БГ в тяхната система
     * ['zip'] - пощенски код на насаленото място
     * ['deliveryDays'] - масив с дни на доставка - 1 - понедилки, 5 - петък и т.н.
     * ['isTown'] - флаг, дали е град
     * ['isRegionalTown'] - флаг, дали е областен град
     * ['tpBg'] - съкращение за типа на населеното място - с., гр., к.
     * ['isThereQts'] - Флаг, който индикира дали разполага с номенклатура с квартали/ж.к., които евентуално да се изполват чрез searchQts функцията
     */
    public static function getCities($q, $countryId = null, $countyId = null, $municipalityId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $q = mb_strtolower($q);
        $q = trim($q);

        expect($q);

        $paramsArr = array('country_id' => $countryId, 'search_for' => $q);
        if (isset($countyId)) {
            $paramsArr['county_id'] = $countyId;
        }

        if (isset($municipalityId)) {
            $paramsArr['muni_id'] = $municipalityId;
        }

        $res = self::makeCall('search_cities', $paramsArr);

        if ($res === false) {

            return $res;
        }

        $resArr = array();

        foreach ((array)$res->cities as $city) {
            $resArr[$city->id]['id'] = $city->id;
            $resArr[$city->id]['nameBg'] = $city->name_bg;
            $resArr[$city->id]['countyBg'] = $city->muni_bg;
            $resArr[$city->id]['muniBg'] = $city->muni_bg;
            $resArr[$city->id]['zip'] = $city->zip;
            $resArr[$city->id]['deliveryDays'] = explode(',', $city->delivery_days);
            $resArr[$city->id]['isTown'] = $city->is_town;
            $resArr[$city->id]['isRegionalTown'] = $city->is_regional_town;
            $resArr[$city->id]['isThereQts'] = $city->is_there_qts;
            $resArr[$city->id]['tpBg'] = $city->tp_bg;
        }

        return $resArr;
    }


    /**
     * Помощна функция, която използва getCities и търси пълно съвпадение на името
     *
     * @param string $q - стринг от името на населеното място
     * @param null|int $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     * @param null|int $countyId - id на областта от getCounties
     * @param null|int $municipalityId - id na общината от getMunicipalities
     *
     * @return false|array
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['countyBg'] - името на обласста на БГ в тяхната система
     * ['muniBg'] - името на общината на БГ в тяхната система
     * ['zip'] - пощенски код на насаленото място
     * ['deliveryDays'] - масив с дни на доставка - 1 - понедилки, 5 - петък и т.н.
     * ['isTown'] - флаг, дали е град
     * ['isRegionalTown'] - флаг, дали е областен град
     * ['tpBg'] - съкращение за типа на населеното място - с., гр., к.
     * ['isThereQts'] - Флаг, който индикира дали разполага с номенклатура с квартали/ж.к., които евентуално да се изполват чрез searchQts функцията
     */
    public static function getCity($q, $countryId = null, $countyId = null, $municipalityId = null)
    {
        $citiesArr = self::getCities($q, $countryId, $countyId, $municipalityId);

        if (!$citiesArr) {

            return $citiesArr;
        }

        $resArr = array();
        foreach ($citiesArr as $k => $cArr) {
            if (mb_strtolower($cArr['nameBg']) == mb_strtolower($q)) {
                $resArr[$k] = $cArr;
            }
        }

        return $resArr;
    }


    /**
     * Връща всички хъбове на CVC
     *
     * @param null|integer $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return array|boolean
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['countryId'] - id на държавата от getCountries
     * ['zip'] - пощенски код
     * ['coord'] - координати - latlng
     */
    public static function getHubs($countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $res = self::makeCall('get_hubs', array('country_id' => $countryId));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->hubs as $hubs) {
            $resArr[$hubs->id]['id'] = $hubs->id;
            $resArr[$hubs->id]['nameBg'] = $hubs->name_bg;
            $resArr[$hubs->id]['zip'] = $hubs->zip;
            $resArr[$hubs->id]['coord'] = $hubs->latlng;
        }

        return $resArr;
    }


    /**
     * Връща всички офиси на CVC
     *
     * @param null|integer $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return array|boolean
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['countryId'] - id на държавата от getCountries
     * ['zip'] - пощенски код
     * ['coord'] - координати - latlng
     */
    public static function getOffices($countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $res = self::makeCall('get_offices', array('country_id' => $countryId));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->offices as $offices) {
            $resArr[$offices->id]['id'] = $offices->id;
            $resArr[$offices->id]['nameBg'] = $offices->name_bg;
            $resArr[$offices->id]['zip'] = $offices->zip;
            $resArr[$offices->id]['coord'] = $offices->latlng;
        }

        return $resArr;
    }


    /**
     * Връща личните списъци с обекти и запазени адреси
     *
     * @param null|integer $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return array|boolean
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['name'] - името обекта
     * ['cityBg'] - име на града
     * ['zip'] - пощенски код
     * ['street'] - име на улицата
     * ['num'] - номер на улицата
     * ['qt'] - квартал
     * ['block'] - блок номер
     * ['phone'] - телефонен номер
     * ['coord'] - координати на обекта latlng
     * ['notes'] - бележка към обекта
     * ['isMain'] - флаг, дали обекта е главният офис
     */
    public static function getCustomLocations()
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $res = self::makeCall('get_custom_locations');

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->locations as $locations) {
            $resArr[$locations->id]['name'] = $locations->name;
            $resArr[$locations->id]['cityBg'] = $locations->city;
            $resArr[$locations->id]['zip'] = $locations->zip;
            $resArr[$locations->id]['qt'] = $locations->qt;
            $resArr[$locations->id]['street'] = $locations->street;
            $resArr[$locations->id]['num'] = $locations->num;
            $resArr[$locations->id]['block'] = $locations->block;
            $resArr[$locations->id]['phone'] = $locations->phone;
            $resArr[$locations->id]['coord'] = $locations->latlng;
            $resArr[$locations->id]['notes'] = $locations->notes;
            $resArr[$locations->id]['isMain'] = $locations->is_main;
        }

        return $resArr;
    }


    /**
     * Намира кварталите със зададените параметри
     *
     * @param string $q - стринг от името на квартала
     * @param int $cityId - id на града от getCities
     * @param null|int $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return boolean|array
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['cityId'] - id на населеното място
     * ['cityBg'] - име на БГ на населеното място
     */
    public static function getQts($q, $cityId, $countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $q = mb_strtolower($q);
        $q = trim($q);

        expect($q);

        $res = self::makeCall('search_qts', array('city_id' => $cityId, 'country_id' => $countryId, 'search_for' => $q));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->qts as $qts) {
            $resArr[$qts->id]['nameBg'] = $qts->name_bg;
            $resArr[$qts->id]['cityId'] = $qts->city_id;
            $resArr[$qts->id]['cityBg'] = $qts->city_bg;
        }

        return $resArr;
    }


    /**
     * Намира улиците със зададените параметри
     *
     * @param string $q - стринг от името на улицата
     * @param int $cityId - id на града от getCities
     * @param null|int $countryId - id на държавата от getCountries - по подразбиран DEFAULT_COUNTRY_ID
     *
     * @return boolean|array
     * ключ - id, от тяхната системата, което се използва в заявките
     * ['nameBg'] - името на БГ в тяхната система
     * ['cityId'] - id на населеното място
     * ['cityBg'] - име на БГ на населеното място
     */
    public static function getStreets($q, $cityId, $countryId = null)
    {
        setIfNot($countryId, self::DEFAULT_COUNTRY_ID);

        $q = mb_strtolower($q);
        $q = trim($q);

        expect($q);

        $res = self::makeCall('search_streets', array('city_id' => $cityId, 'country_id' => $countryId, 'search_for' => $q));

        if ($res === false) {

            return $res;
        }

        $resArr = array();
        foreach ((array)$res->streets as $streets) {
            $resArr[$streets->id]['nameBg'] = $streets->name_bg;
            $resArr[$streets->id]['cityId'] = $streets->city_id;
            $resArr[$streets->id]['cityBg'] = $streets->city_bg;
        }

        return $resArr;
    }


    /**
     * Връща опциите за избор на изпращач
     *
     * @return array
     */
    public static function getSenderOptions()
    {
        $options = array();
        try {
            $cvcCustoms = cvc_Adapter::getCustomLocations();
            foreach ($cvcCustoms as $customerId => $customObj){
                $options[$customerId] = $customObj['name'];
            }
        } catch(core_exception_Expect $e){}

        if (countR($options)){
            $options = array('' => '') + $options;
        }

        return $options;
    }


    /**
     * Връща ид-то на държавата по подаденото име
     *
     * @param mixed $country
     *
     * @return null|int - ид-то на държавата
     */
    public static function getCountryIdByName($country)
    {
        // Извличане на кода на държавата
        $countryId = is_numeric($country) ? $country : drdata_Countries::getIdByName($country);
        $letterCode2 = drdata_Countries::fetchField($countryId, 'letterCode2');

        // Извличане на всички държави и търсене на тази с този код
        $countries = cvc_Adapter::getCountries();
        $found = array_filter($countries, function($a) use ($letterCode2) {return $a['code'] == $letterCode2;});
        if (countR($found) == 1){

            return key($found);
        }

        return null;
    }


    /**
     * Помощна функция, която сваля файла от сървъра чрез CURL
     *
     * @param string $url - URL към файла от тяхната система
     *
     * @return string
     */
    protected static function getFileFromServer($url)
    {
        $bucketId = fileman_Buckets::fetchByName('engView');
        $fh = fileman_Get::getFile((object) array('url' => $url, 'bucketId' => $bucketId));

        return $fh;
    }


    /**
     * Помощна функция, която прави CURL заявките и връща резултата
     *
     * @param string $url
     * @param array $paramsArr
     *
     * @return mixed
     */
    protected static function makeCall($url, $paramsArr = array())
    {
        $url = self::prepareUrl($url);

        if (!$paramsArr['postFields']) {
            $getParams = '';
            foreach ((array) $paramsArr as $fName => $fVal) {
                $getParams .= $getParams ? '&' : '';
                $getParams .= $fName . '=' . urlencode($fVal);
//                $getParams .= $fName . '=' . rawurlencode($fVal);
            }

            $url = rtrim($url, '/') . '?' . $getParams;
        }

        $curl = self::prepareCurl($url);

        if (!$paramsArr['postFields']) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        if ($paramsArr['postFields']) {
            curl_setopt($curl, CURLOPT_POST, 1);
            $pField = $paramsArr['postFields'];
            if ($paramsArr['jsonEncode'] !== false) {
                $pField = json_encode($pField);
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $pField);
        }

        $responseJson = @curl_exec($curl);

        $res = self::prepareRes($responseJson);

        return $res;
    }


    /**
     * Помощна фунцкия за подготвяне на URL за заявка
     *
     * @param string $urlParam
     *
     * @return string
     */
    protected static function prepareUrl($urlParam)
    {
        $url = cvc_Setup::get('URL');
        $url = rtrim($url, '/');
        $url .= '/' . ltrim($urlParam, '/');

        return $url;
    }


    /**
     * Помощна функция за подготвяне на curl ресурса от URL-то
     *
     * @param string $url
     *
     * @return resource
     */
    protected static function prepareCurl($url)
    {
        $token = cvc_Setup::get('TOKEN');

        expect($url && $token);

        $curl = curl_init($url);

        // Да не се проверява сертификата
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Хедъри
        $headersArr = array("Content-Type: application/json", "Accept: application/json", 'Charset=UTF-8');

        $headersArr[] = "Authorization: Bearer {$token}";

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headersArr);

        return $curl;
    }


    /**
     * Помощна фунмкция за подготвяне на JSON резултата
     *
     * @param string $json
     *
     * @return mixed
     */
    protected static function prepareRes($json)
    {
        if (!trim($json)) {

            return false;
        }

        $response = @json_decode($json);

        if (!$response) {
            self::logErr("Празен отговор от сървъра");

            return false;
        }

        if (is_object($response) && !$response->success) {
            self::logErr("Грешка в сървъра: {$response->error}");

            if (haveRole('debug')) {
                core_Statuses::newStatus("Грешка в сървъра: {$response->error}", 'error');
            }

            return false;
        }

        return $response;
    }


    /**
     * Помощна функция за логване на грешките
     *
     * @param string $msg
     *
     * @return void
     */
    protected static function logErr($msg)
    {
        $className = get_called_class();
        log_System::add($className, $msg, null, 'err', 7);

        if (haveRole('debug')) {
            status_Messages::newStatus($msg, 'error');
        }
    }
}
