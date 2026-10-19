<?php


/**
 * Връзка с клиентското API на Transpress (STEPS)
 *
 * @category  bgerp
 * @package   transpress
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class transpress_Adapter extends core_BaseClass
{
    /**
     * Тип на изключението, когато API-то не е отговорило
     */
    const NO_RESPONSE_TYPE = 'Без отговор';


    /**
     * Тип на изключението, когато API-то е върнало грешка
     */
    const ERROR_TYPE = 'Грешка';


    /**
     * Колко минути се пазят каталозите в кеша
     */
    const CATALOG_CACHE_MINUTES = 720;


    /**
     * Изпълнява заявка към API-то
     *
     * @param string   $path   - път след базовия адрес
     * @param array    $params - параметри; масив се праща като повтарящ се параметър
     * @param bool     $auth   - дали е с потребител и парола
     * @param string   $method - GET или POST
     * @param int|null $userId - чии данни за вход (null за текущия)
     * @param string|null $env - production или test (null за текущата)
     * @return stdClass $res   - code, body, contentType, headers (ключове в малки букви), error
     * @throws core_exception_Expect - ако не е настроен потребител
     */
    public static function call($path, $params = array(), $auth = true, $method = 'GET', $userId = null, $env = null)
    {
        $url = self::getBaseUrl($env) . '/' . ltrim($path, '/');
        $query = self::buildQuery($params);
        if($method == 'GET' && strlen($query)){
            $url .= '?' . $query;
        }

        $res = (object)array('code' => 0, 'body' => null, 'contentType' => null, 'headers' => array(), 'error' => null);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'));
        }

        if($auth){
            $login = self::getLoginData($userId);
            expect(!empty($login['userName']) && !empty($login['password']), 'Не са настроени потребителят и паролата за Transpress');
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, "{$login['userName']}:{$login['password']}");
        }

        // Хедърите се пазят отделно: при прекъснато тяло номерът на пратката е в Manifest-ID
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($ch, $line) use ($res) {
            $parts = explode(':', $line, 2);
            if(countR($parts) == 2){
                $res->headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }

            return strlen($line);
        });

        $body = @curl_exec($curl);
        $res->code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $res->contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $res->body = is_string($body) ? $body : null;
        if($body === false || !$res->code){
            $res->error = curl_error($curl);
            $time = round(curl_getinfo($curl, CURLINFO_TOTAL_TIME), 1);
            log_System::add(get_called_class(), "Няма отговор от {$path}: {$res->error} (HTTP {$res->code}, {$time} s)", null, 'warning', 7);
        }
        curl_close($curl);

        return $res;
    }


    /**
     * Текущата среда от настройките
     *
     * @return string - production или test
     */
    public static function getEnvironment()
    {
        return (transpress_Setup::get('ENVIRONMENT') == 'test') ? 'test' : 'production';
    }


    /**
     * Базовият адрес на средата
     *
     * @param string|null $env - production или test (null за текущата)
     * @return string
     */
    public static function getBaseUrl($env = null)
    {
        $env = $env ?? self::getEnvironment();
        $url = ($env == 'test') ? transpress_Setup::get('TEST_URL') : transpress_Setup::get('PRODUCTION_URL');

        return rtrim((string)$url, '/');
    }


    /**
     * Публичният адрес за проследяване на пратка
     *
     * @param string      $manifest - номер на пратката
     * @param string|null $env      - средата, в която е издадена (null за текущата)
     * @return string
     */
    public static function getTrackingUrl($manifest, $env = null)
    {
        return self::getBaseUrl($env) . '/integration/tracking.do?' . self::buildQuery(array('manifestID' => $manifest, 'full' => true, 'locale' => 'bg'));
    }


    /**
     * Данните за вход в Transpress
     *
     * @param int|null $userId - ид на потребител (null за текущия)
     * @return array           - userName и password
     */
    public static function getLoginData($userId = null)
    {
        return array('userName' => transpress_Setup::get('USERNAME', false, $userId),
                     'password' => transpress_Setup::get('PASSWORD', false, $userId));
    }


    /**
     * Сглобява параметрите като form-urlencoded; масивите стават повтарящ се параметър без []
     *
     * @param array $params
     * @return string
     */
    private static function buildQuery($params)
    {
        $parts = array();
        foreach ($params as $name => $value){
            if(is_null($value) || $value === '') continue;

            foreach ((is_array($value) ? $value : array($value)) as $val){
                if(is_bool($val)){
                    $val = $val ? 'true' : 'false';
                }
                $parts[] = rawurlencode($name) . '=' . rawurlencode((string)$val);
            }
        }

        return implode('&', $parts);
    }


    /**
     * JSON тялото на отговора като масив или null
     *
     * @param stdClass $res - резултат от call()
     * @return array|null
     */
    private static function decode($res)
    {
        if(!is_string($res->body) || trim($res->body) === '') return null;
        $decoded = json_decode($res->body, true);

        return is_array($decoded) ? $decoded : null;
    }


    /**
     * Съобщенията за грешка от отговора
     *
     * calculate.do връща errors[].text, import.do - errors[].error, print.svc и states.svc - error/value
     *
     * @param array|null $decoded
     * @return array $errors - ключ пътят на грешката (или число), стойност съобщението
     */
    public static function getErrors($decoded)
    {
        $errors = array();
        if(!is_array($decoded)) return $errors;

        if(is_array($decoded['errors'] ?? null)){
            foreach ($decoded['errors'] as $err){
                if(!is_array($err)) continue;
                // cancel.do връща съобщенията HTML-кодирани (&#039;)
                $msg = html_entity_decode((string)($err['error'] ?? $err['text'] ?? ''), ENT_QUOTES, 'UTF-8');
                $path = (string)($err['path'] ?? '');
                if(strlen($path) && !isset($errors[$path])){
                    $errors[$path] = $msg;
                } else {
                    $errors[] = $msg;
                }
            }
        } elseif(isset($decoded['error']) && is_string($decoded['error'])){
            $value = (string)($decoded['value'] ?? '');
            $errors[] = $decoded['error'] . (strlen($value) ? ": {$value}" : '');
        }

        return $errors;
    }


    /**
     * Хвърля изключение, ако отговорът е без тяло или с грешки
     *
     * @param stdClass   $res     - резултат от call()
     * @param array|null $decoded - декодираното тяло
     * @param string     $path    - заявката, за лога
     * @throws core_exception_Expect|transpress_Exception
     */
    private static function expectSuccess($res, $decoded, $path)
    {
        if(!empty($res->error)){
            throw new core_exception_Expect('Няма отговор от Transpress', self::NO_RESPONSE_TYPE);
        }

        $errors = self::getErrors($decoded);
        if($res->code >= 400 || countR($errors)){
            if(!countR($errors)){
                $errors[] = "HTTP {$res->code}";
            }
            $msg = implode('; ', $errors);
            log_System::add(get_called_class(), "Грешка от {$path}: {$msg}", null, 'warning', 7);
            $Exception = new transpress_Exception($msg, self::ERROR_TYPE);
            $Exception->errors = $errors;

            throw $Exception;
        }

        if(!is_array($decoded)){
            throw new core_exception_Expect('Неразпознат отговор от Transpress', self::ERROR_TYPE);
        }
    }


    /**
     * При действие с последствия: доказан отказ е само 4xx; иначе изходът е неясен
     *
     * @param stdClass   $res     - резултат от call()
     * @param array|null $decoded - декодираното тяло
     * @param string     $path    - заявката, за лога
     * @throws core_exception_Expect - ERROR_TYPE при отказ, NO_RESPONSE_TYPE при неясен изход
     */
    private static function throwRejectedOrUnknown($res, $decoded, $path)
    {
        if(empty($res->error) && $res->code >= 400 && $res->code < 500){
            self::expectSuccess($res, $decoded, $path);
        }

        $errors = self::getErrors($decoded);
        $details = "HTTP {$res->code}" . (countR($errors) ? ': ' . implode('; ', $errors) : '');
        log_System::add(get_called_class(), "Неясен изход от {$path} ({$details})", null, 'warning', 7);

        throw new core_exception_Expect("Не е ясно дали заявката е изпълнена ({$details})", self::NO_RESPONSE_TYPE);
    }


    /**
     * Изчислява цената на пратка, без да я създава
     *
     * @param array $params - параметрите на пратката (@see transpress.md)
     * @return array        - price, vat, price_vat (обекти amount/currency/display) и components
     * @throws core_exception_Expect
     */
    public static function calculate($params)
    {
        $params = array('view' => 'json', 'locale' => 'bg') + $params;
        $res = self::call('clients/generic/calculate.do', $params, true, 'POST');
        $decoded = self::decode($res);
        self::expectSuccess($res, $decoded, 'calculate.do');

        return $decoded;
    }


    /**
     * Създава пратка
     *
     * Номерът се взима и от хедъра Manifest-ID, ако тялото е прекъснато.
     * Отказ е само 4xx; 5xx, прекъсване и неразпознат отговор са неясен изход.
     *
     * @param array $params - параметрите на пратката
     * @return stdClass     - manifest, barcodes, price (total, net, vat, currency) и noBody
     * @throws core_exception_Expect - NO_RESPONSE_TYPE, ако не е ясно дали е създадена
     */
    public static function import($params)
    {
        $params = array('print_format' => 'nil', 'view' => 'json', 'include_price' => true, 'locale' => 'bg') + $params;
        $res = self::call('clients/generic/import.do', $params, true, 'POST');
        $decoded = self::decode($res);

        $manifest = (string)($decoded['manifest'] ?? $res->headers['manifest-id'] ?? '');
        if(strlen($manifest) && ($res->code < 400 || $res->code >= 500)){
            $barcodes = $decoded['barcodes'] ?? array_filter(array_map('trim', explode(',', $res->headers['barcodes'] ?? '')));
            $price = null;
            if(is_array($decoded['price_with_vat'] ?? null)){
                $price = (object)array('total' => $decoded['price_with_vat']['value'] ?? null,
                                       'net' => $decoded['price']['value'] ?? null,
                                       'vat' => $decoded['price_vat']['value'] ?? null,
                                       'currency' => self::getCurrencyCode($decoded['price_with_vat']['currency'] ?? null));
            }

            return (object)array('manifest' => $manifest, 'barcodes' => array_values((array)$barcodes), 'price' => $price, 'noBody' => !is_array($decoded));
        }

        self::throwRejectedOrUnknown($res, $decoded, 'import.do');
    }


    /**
     * Сваля PDF на съществуваща пратка и го записва като файл
     *
     * @param string      $manifest - номер на пратката
     * @param string      $format   - pdf, pdf-label или pdf-label-manifest
     * @param string|null $env      - средата, в която е издадена (null за текущата)
     * @return string $fh           - файлов манипулатор
     * @throws core_exception_Expect
     */
    public static function printPdf($manifest, $format = 'pdf-label', $env = null)
    {
        $res = self::call('clients/generic/print.svc', array('manifestId' => $manifest, 'printFormat' => $format), true, 'GET', null, $env);
        if(empty($res->error) && $res->code == 200 && stripos($res->contentType, 'pdf') !== false && strlen((string)$res->body)){
            $fileName = 'transpress_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $manifest) . '.pdf';

            return fileman::absorbStr($res->body, 'transpress', $fileName);
        }

        self::expectSuccess($res, self::decode($res), 'print.svc');

        throw new core_exception_Expect('Transpress не върна PDF', self::ERROR_TYPE);
    }


    /**
     * Отказва пратка, която още не е взета от куриер
     *
     * @param string      $manifest - номер на пратката
     * @param string|null $reason   - причина
     * @param string|null $env      - средата, в която е издадена (null за текущата)
     * @return void
     * @throws core_exception_Expect - NO_RESPONSE_TYPE, ако не е ясно дали е отказана
     */
    public static function cancel($manifest, $reason = null, $env = null)
    {
        $res = self::call('clients/generic/cancel.do', array('barcode' => $manifest, 'reason' => $reason, 'view' => 'json', 'locale' => 'bg'), true, 'GET', null, $env);
        $decoded = self::decode($res);
        if(empty($res->error) && $res->code == 200 && !empty($decoded['success'])) return;

        self::throwRejectedOrUnknown($res, $decoded, 'cancel.do');
    }


    /**
     * Състоянията на една пратка
     *
     * Празен резултат не доказва, че пратката не съществува.
     *
     * @param string $key  - номер, '#' + вътрешно ид или 'референция/ид на партньора'
     * @param bool   $full - цялата история или само последното състояние
     * @param string|null $env - средата (null за текущата)
     * @return array       - state, display, date (epoch ms), description, station
     * @throws core_exception_Expect
     */
    public static function track($key, $full = true, $env = null)
    {
        $res = self::call('integration/tracking.do', array('manifestID' => $key, 'full' => $full, 'view' => 'json', 'locale' => 'bg'), false, 'GET', null, $env);
        $decoded = self::decode($res);
        self::expectSuccess($res, $decoded, 'tracking.do');

        return is_array($decoded['states'] ?? null) ? $decoded['states'] : array();
    }


    /**
     * Офисите на Transpress
     *
     * @return array - код на офиса => име и адрес
     */
    public static function getOfficeOptions()
    {
        $options = array();
        foreach (self::getCatalog('station.do') as $item){
            $details = (string)($item['details'] ?? '');
            $options[(string)$item['value']] = $item['name'] . (strlen($details) ? " - {$details}" : '');
        }

        return $options;
    }


    /**
     * Опаковките на Transpress
     *
     * @return array - код на опаковката => име
     */
    public static function getPackagingOptions()
    {
        $options = array();
        foreach (self::getCatalog('packaging.do') as $item){
            $options[(string)$item['value']] = (string)$item['name'];
        }

        return $options;
    }


    /**
     * Атрибутите на опаковките (standard, package_x, package_y, package_z)
     *
     * @return array - код на опаковката => атрибути
     */
    public static function getPackagingAttributes()
    {
        $res = array();
        foreach (self::getCatalog('packaging.do') as $item){
            $res[(string)$item['value']] = (array)($item['attributes'] ?? array());
        }

        return $res;
    }


    /**
     * Наличните услуги между две държави
     *
     * @param string $fromCountry - код на държавата на подателя (alpha-2)
     * @param string $toCountry   - код на държавата на получателя (alpha-2)
     * @return array - код на услугата => описание
     */
    public static function getServiceOptions($fromCountry, $toCountry)
    {
        $options = array();
        foreach (self::getCatalog('service-by-endpoints.do', array('sourceCountryCode' => $fromCountry, 'targetCountryCode' => $toCountry)) as $item){
            $options[(string)$item['value']] = (string)$item['name'];
        }

        return $options;
    }


    /**
     * Запазените адреси за вземане на акаунта
     *
     * @return array - код на адреса => описание
     */
    public static function getWarehouseOptions()
    {
        $login = self::getLoginData();
        $cacheKey = md5(self::getBaseUrl() . '|addresses|' . $login['userName']);
        $options = core_Cache::get('transpress', $cacheKey);
        if(is_array($options)) return $options;

        $options = array();
        try{
            $res = self::call('clients/generic/address/list.svc', array('type' => 'source', 'length' => 200));
            $decoded = self::decode($res);
            self::expectSuccess($res, $decoded, 'address/list.svc');
        } catch(core_exception_Expect $e){

            return $options;
        }

        foreach ((array)($decoded['addresses'] ?? array()) as $address){
            $code = (string)($address['code'] ?? '');
            if(!strlen($code)) continue;

            $settlement = $address['address']['settlement'] ?? array();
            $street = (string)($address['address']['street_display'] ?? '');
            $place = trim(($settlement['post_code'] ?? '') . ' ' . ($settlement['name'] ?? ''));
            $options[$code] = implode(', ', array_filter(array($code, $place, $street)));
        }
        core_Cache::set('transpress', $cacheKey, $options, 60);

        return $options;
    }


    /**
     * Елементите на каталог от integration/suggest, кеширани
     *
     * @param string $name   - име на каталога (station.do, packaging.do, ...)
     * @param array  $params - допълнителни параметри
     * @return array - items (name, value, details, attributes); празен масив при грешка
     */
    private static function getCatalog($name, $params = array())
    {
        // Без locale каталозите са на английски и търсят само по английските имена
        $params = array('pattern' => '?', 'view' => 'json', 'locale' => 'bg') + $params;
        $cacheKey = md5(self::getBaseUrl() . "|{$name}|" . serialize($params));
        $items = core_Cache::get('transpress', $cacheKey);
        if(is_array($items)) return $items;

        try{
            $res = self::call("integration/suggest/{$name}", $params, false);
            $decoded = self::decode($res);
            self::expectSuccess($res, $decoded, $name);
        } catch(core_exception_Expect $e){

            return array();
        }

        $items = array();
        foreach ((array)($decoded['items'] ?? array()) as $item){
            if(is_array($item) && isset($item['value'])){
                $items[] = $item;
            }
        }
        core_Cache::set('transpress', $cacheKey, $items, self::CATALOG_CACHE_MINUTES);

        return $items;
    }


    /**
     * Кодът на валутата от отговора
     *
     * calculate.do връща символа (€), а не кода.
     *
     * @param string|null $currency
     * @return string|null
     */
    public static function getCurrencyCode($currency)
    {
        $currency = trim((string)$currency);
        $map = array('€' => 'EUR', 'лв' => 'BGN', 'лв.' => 'BGN');

        return $map[$currency] ?? (strlen($currency) ? strtoupper($currency) : null);
    }
}
