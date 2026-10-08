<?php


/**
 * Помощен клас за синхронизиране между две bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Помощен клас за синхронизиране между две bgERP системи
 */
class sync_Helper extends core_Manager
{
    /**
     * Максимално време за изчакване на export от master-а
     */
    const EXPORT_REQUEST_TIMEOUT = 3600;


    /**
     * Горна граница за файл, свалян от изрично доверен product-push origin
     */
    const TRUSTED_FILE_MAX_SIZE = 52428800;


    /**
     * Кеш на идентифицирания клиент за текущата заявка
     */
    protected static $requestSettings = array();


    
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array();
    
    
    /**
     * На кои класове да се търси аналог в системата
     */
    public $mapClass = array();
    
    
    /**
     * Глобални уникални ключове
     */
    public $globalUniqKeys = array(
            'drdata_Countries' => 'letterCode2',
            'core_Roles' => 'role',
            'core_Classes' => 'name',
            'currency_Currencies' => 'code',
    );
    
    
    /**
     * Полета от моделите, които не трябва да се експортират
     */
    public $fixedExport = array(
            '*::createdOn' => null,
            '*::createdBy' => null,
            '*::modifiedOn' => null,
            '*::modifiedBy' => null,
            '*::searchKeywords' => null,
            '*::folderId' => null,
            'cat_Products::folderId' => 'sync_Helper::fixFolderId',
            'price_Lists::folderId' => 'sync_Helper::fixFolderId',
            '*::containerId' => null,
            '*::originId' => null,
            '*::threadId' => null,
            '*::ps5Enc' => null,
            '*::exSysId' => null,
            '*::lastLoginTime' => null,
            '*::lastLoginTime' => null,
            '*::lastLoginIp' => null,
            '*::lastActivityTime' => null,
            '*::lastUsedOn' => null,
            '*::id' => null,
    );
    
    
    /**
     * Префикс за имената на променливите
     */
    protected static $fNewNamePref = '__';
    
    
    /**
     * Проверка за права за пускане
     */
    public static function requireRight($type = 'export', $allowLegacy = false)
    {
        expect(core_Packs::isInstalled('sync'));

        if ($type != 'export') {
            requireRole('admin');

            return;
        }

        // Локален администратор може да стартира ръчните действия. Външните
        // заявки винаги минават през идентификацията на конкретен клиент.
        if (haveRole('user')) {
            requireRole('admin');

            return;
        }

        return self::getRequestSettings(true, $allowLegacy);
    }


    /**
     * Намира и валидира настройките на клиента за текущата входяща заявка
     *
     * При нормален режим се изискват ID, парола, активно състояние и, ако е
     * зададен, IP адресът на клиента. Идентификация само по IP не се допуска.
     *
     * @param bool $throwIfMissing
     * @param bool $allowLegacy Не се използва; запазен за съвместимост на извикванията
     *
     * @return stdClass|null
     */
    public static function getRequestSettings($throwIfMissing = true, $allowLegacy = false)
    {
        $cacheKey = 'credentialsOnly';

        if (haveRole('user')) {
            if (!haveRole('admin')) {
                if ($throwIfMissing) {
                    expect(false, 'Само администратор може да избира sync настройки');
                }

                return null;
            }

            if (!array_key_exists($cacheKey, self::$requestSettings)) {
                $settingsId = Request::get('syncSettingsId', 'int');
                if ($settingsId) {
                    $rec = sync_Settings::fetch($settingsId);
                    expect(($rec->state ?? null) == 'active', 'Невалидна или неактивна sync настройка');
                } else {
                    $query = sync_Settings::getQuery();
                    $query->where("#state = 'active'");
                    $query->limit(2);
                    $records = array();
                    while ($rec = $query->fetch()) {
                        $records[] = $rec;
                    }

                    if (countR($records) > 1) {
                        if ($throwIfMissing) {
                            expect(false, 'Посочете syncSettingsId за локалния export');
                        }
                        $rec = null;
                    } else {
                        $rec = $records[0] ?? null;
                    }
                }

                // Локалният избор важи и след forceSystemUser().
                self::$requestSettings['credentialsOnly'] = $rec;
            }

            if (!self::$requestSettings[$cacheKey] && $throwIfMissing) {
                expect(false, 'Липсва активна sync настройка');
            }

            return self::$requestSettings[$cacheKey];
        }

        if (!array_key_exists($cacheKey, self::$requestSettings)) {
            self::$requestSettings[$cacheKey] = null;

            $sysId = trim((string) Request::get('syncSysId'));
            // Паролата е opaque credential и не трябва да минава през
            // heuristic payload проверките на Request.
            $pass = (string) Request::get('syncPass', false);
            $remoteAddr = self::getRemoteAddress();

            if ($sysId !== '') {
                $rec = sync_Settings::fetch(array("#offlineSysId = '[#1#]'", $sysId));
                $authType = $rec ? ($rec->authType ?? 'credentials') : null;

                if ($rec &&
                    ($rec->state ?? null) == 'active' &&
                    $authType == 'credentials' &&
                    strlen((string) ($rec->pass ?? '')) > 0 &&
                    hash_equals((string) $rec->pass, $pass) &&
                    self::isAllowedIp($remoteAddr, $rec->allowedIps ?? null)) {
                    self::$requestSettings[$cacheKey] = $rec;
                } else {
                    // Подаден е ID, тоест опитът е нарочен - оставяме следа.
                    self::logAuthFailure($sysId, $remoteAddr, $rec, $authType, $pass);
                }
            }
        }

        if (!self::$requestSettings[$cacheKey] && $throwIfMissing) {
            expect(false, 'Невалидна идентификация на системата');
        }

        return self::$requestSettings[$cacheKey];
    }


    /**
     * Записва в лога защо е отказана идентификация. Паролата не се логва.
     *
     * @param string        $sysId
     * @param string|null   $remoteAddr
     * @param stdClass|null $rec
     * @param string|null   $authType
     * @param string        $pass
     */
    protected static function logAuthFailure($sysId, $remoteAddr, $rec, $authType, $pass)
    {
        if (!$rec) {
            $reason = 'непознато ID';
        } elseif (($rec->state ?? null) != 'active') {
            $reason = 'неактивен запис';
        } elseif ($authType != 'credentials') {
            $reason = 'записът е в legacy IP режим';
        } elseif (!strlen((string) ($rec->pass ?? ''))) {
            $reason = 'записът няма зададена парола';
        } elseif (!hash_equals((string) $rec->pass, $pass)) {
            $reason = 'грешна парола';
        } else {
            $reason = 'непозволен IP адрес';
        }

        self::logWarning(
            "Отказана sync идентификация ({$reason}): ID '{$sysId}' от " .
                ($remoteAddr ?: 'неизвестен адрес'),
            $rec->id ?? null
        );
    }


    /**
     * Връща адреса на клиента, като уважава X-Forwarded-For единствено когато
     * непосредственият източник е в изрично зададения trusted proxy списък
     *
     * @return string|null
     */
    protected static function getRemoteAddress()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $trustedProxies = trim((string) sync_Setup::get('TRUSTED_PROXIES'));

        if (!$remoteAddr || !$trustedProxies ||
            !self::isAllowedIp($remoteAddr, $trustedProxies, true, false) ||
            empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            return $remoteAddr;
        }

        $chain = preg_split('/\s*,\s*/', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $chain[] = $remoteAddr;

        $leftmostValidIp = null;

        // Вземаме най-десния адрес преди доверената proxy верига.
        for ($i = countR($chain) - 1; $i >= 0; $i--) {
            $ip = trim($chain[$i]);
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {

                // Не прескачаме malformed hop: иначе адрес вляво от него
                // може да бъде избран като spoof-нат client IP.
                return null;
            }

            $leftmostValidIp = $ip;
            if (!self::isAllowedIp($ip, $trustedProxies, true, false)) {

                return $ip;
            }
        }

        // Ако и самият client е в trusted диапазона, връщаме най-левия
        // валиден адрес от веригата, а не адреса на непосредственото proxy.
        return $leftmostValidIp ?: $remoteAddr;
    }


    /**
     * Проверява IP адрес спрямо списък от точни IP адреси и IPv4 CIDR мрежи
     *
     * Стойността "private" се използва единствено от мигрирания legacy запис,
     * за да запази временно старото поведение за частни мрежи.
     *
     * @param string|null $remoteAddr
     * @param string|null $allowedIps
     * @param bool        $requireRestriction
     * @param bool        $allowPrivateKeyword
     *
     * @return bool
     */
    protected static function isAllowedIp(
        $remoteAddr,
        $allowedIps,
        $requireRestriction = false,
        $allowPrivateKeyword = false
    )
    {
        if (!$remoteAddr || !filter_var($remoteAddr, FILTER_VALIDATE_IP)) {

            return false;
        }

        $allowedIps = trim((string) $allowedIps);
        if ($allowedIps === '') {

            return !$requireRestriction;
        }

        $entries = preg_split('/[\s,;]+/', $allowedIps);
        foreach ($entries as $entry) {
            $entry = trim($entry);

            if ($allowPrivateKeyword &&
                strtolower($entry) === 'private' &&
                core_Url::isPrivate($remoteAddr)) {

                return true;
            }

            // IPv4/IPv6 адресите се сравняват точно.
            if (filter_var($entry, FILTER_VALIDATE_IP) &&
                inet_pton($entry) === inet_pton($remoteAddr)) {

                return true;
            }
        }

        // type_Ip поддържа IPv4 CIDR мрежи.
        return type_Ip::isInIps($remoteAddr, type_Ip::extractIps(implode(',', $entries)));
    }


    /**
     * Създава общ controller с обединени правила за export/import
     *
     * @param array $controllers
     *
     * @return stdClass
     */
    public static function getCompositeController($controllers = array())
    {
        array_unshift($controllers, cls::get('sync_Helper'));

        $res = new stdClass();
        $res->fixedExport = array();
        $res->mapClass = array();
        $res->globalUniqKeys = array();
        $res->exportAlso = array();
        $exportAlsoKeys = array();

        foreach ($controllers as $controller) {
            $controller = is_object($controller) ? $controller : cls::get($controller);

            foreach (array('fixedExport', 'mapClass', 'globalUniqKeys') as $property) {
                foreach ((array) ($controller->{$property} ?? array()) as $key => $value) {
                    if (array_key_exists($key, $res->{$property})) {
                        expect(
                            serialize($res->{$property}[$key]) === serialize($value),
                            "Конфликтно sync правило {$property}::{$key}"
                        );
                    }

                    $res->{$property}[$key] = $value;
                }
            }

            foreach ((array) ($controller->exportAlso ?? array()) as $sourceClass => $rules) {
                foreach ((array) $rules as $rule) {
                    $ruleKey = md5(serialize($rule));
                    if (isset($exportAlsoKeys[$sourceClass][$ruleKey])) {

                        continue;
                    }

                    $res->exportAlso[$sourceClass][] = $rule;
                    $exportAlsoKeys[$sourceClass][$ruleKey] = true;
                }
            }
        }

        return $res;
    }


    /**
     * Валидира URL адреса за връзка към master системата
     *
     * Допуска HTTP и HTTPS; идентификацията се проверява отделно.
     *
     * @param string $url
     */
    public static function requireSecureUrl($url)
    {
        $parts = parse_url($url ?? '');
        expect(is_array($parts), 'Невалиден URL към sync master');
        $scheme = strtolower($parts['scheme'] ?? '');

        expect(
            in_array($scheme, array('http', 'https'), true) && !empty($parts['host']) &&
            !isset($parts['user']) && !isset($parts['pass']),
            'Посочете HTTP или HTTPS адрес на системата. ID и паролата се попълват отделно'
        );
    }


    /**
     * Проверява съвпадението на протокол, хост и порт и забранява userinfo
     *
     * @param string $url
     * @param string $trustedSourceUrl
     *
     * @return bool
     */
    public static function isSameTrustedOrigin($url, $trustedSourceUrl)
    {
        if (!self::isTrustedOriginUrl($url) || !self::isTrustedOriginUrl($trustedSourceUrl)) {

            return false;
        }

        $urlParts = parse_url((string) $url);
        $trustedParts = parse_url((string) $trustedSourceUrl);

        $urlScheme = strtolower($urlParts['scheme']);
        $trustedScheme = strtolower($trustedParts['scheme']);
        $urlPort = isset($urlParts['port']) ? (int) $urlParts['port'] : ($urlScheme === 'https' ? 443 : 80);
        $trustedPort = isset($trustedParts['port']) ? (int) $trustedParts['port'] : ($trustedScheme === 'https' ? 443 : 80);

        return $urlScheme === $trustedScheme &&
            strtolower($urlParts['host']) === strtolower($trustedParts['host']) &&
            $urlPort === $trustedPort;
    }


    /**
     * Дали URL-ът е годен за доверен origin: HTTP/HTTPS, с хост и без userinfo
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isTrustedOriginUrl($url)
    {
        $parts = parse_url((string) $url);

        return is_array($parts) &&
            in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true) &&
            !empty($parts['host']) &&
            !isset($parts['user']) &&
            !isset($parts['pass']);
    }


    /**
     * Сваля ограничен по размер файл само от изрично доверен HTTP/HTTPS origin
     *
     * @param string $url
     * @param string $trustedSourceUrl
     *
     * @return string
     */
    public static function fetchFileFromTrustedOrigin($url, $trustedSourceUrl)
    {
        expect(
            self::isSameTrustedOrigin($url, $trustedSourceUrl),
            'Недоверен URL в sync payload'
        );

        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 120,
                'follow_location' => 0,
                'max_redirects' => 0,
                'ignore_errors' => false,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));
        $stream = @fopen($url, 'rb', false, $context);
        expect($stream, 'Не може да се свали файл от доверения sync source');

        try {
            $data = stream_get_contents($stream, self::TRUSTED_FILE_MAX_SIZE + 1);
            $meta = stream_get_meta_data($stream);
        } finally {
            fclose($stream);
        }

        $headers = $meta['wrapper_data'] ?? array();
        $statusLine = is_array($headers) ? reset($headers) : null;
        $statusCode = null;
        if (is_string($statusLine) &&
            preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', $statusLine, $matches)) {
            $statusCode = (int) $matches[1];
        }

        expect(
            $statusCode >= 200 && $statusCode < 300,
            'Невалиден HTTP отговор при сваляне на sync файл'
        );
        expect($data !== false, 'Не може да се прочете sync файл');
        expect(
            strlen($data) <= self::TRUSTED_FILE_MAX_SIZE,
            'Sync файлът надвишава разрешения размер'
        );

        return $data;
    }
    
    
    /**
     * Отпечатва резултата
     * 
     * @param array $resArr
     * @param boolean $usersFirst
     */
    public static function outputRes(&$resArr, $usersFirst = true)
    {
        if (Request::get('_bp') && haveRole('admin')) {
            bp($resArr);
        }

        $resArr = array_reverse($resArr, true);

        if ($usersFirst) {
            if (!empty($resArr['core_Users']) && (countR($resArr) > 1)) {
                $users = $resArr['core_Users'];
                unset($resArr['core_Users']);
                $resArr = array('core_Users' => $users) + $resArr;
                unset($users);
            }
        }

        // Обединеният export държи целия граф в паметта. Освобождаваме всяко
        // междинно представяне веднага щом следващото е готово, за да не се
        // трупат граф + сериализиран низ + компресиран низ едновременно.
        $data = serialize($resArr);
        $resArr = null;

        $data = gzcompress($data);

        echo $data;
        $data = null;

        shutdown();
    }
    
    
    /**
     * Проверява връзката и идентификацията с общ лимит от 5 секунди
     *
     * @return array
     */
    public static function checkConnection()
    {
        $url = trim((string) sync_Setup::get('EXPORT_URL'));
        if ($url === '') {

            return array('status' => 'warning', 'message' => 'Няма зададен URL за импортиране');
        }
        try {
            self::requireSecureUrl($url);
        } catch (core_exception_Expect $e) {

            return array('status' => 'error', 'message' => 'Посочете HTTP или HTTPS адрес на системата. ID и паролата се попълват отделно');
        }
        $parts = parse_url($url);
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {

            return array('status' => 'error', 'message' => 'Посочете основния URL на системата без парола, query параметри или фрагмент');
        }
        if (!function_exists('curl_init')) {

            return array('status' => 'error', 'message' => 'Бързата проверка изисква PHP разширението cURL');
        }

        $params = array('syncSysId' => trim((string) sync_Setup::get('SYS_ID')), 'syncPass' => sync_Setup::getSyncPass());
        if ($params['syncSysId'] === '' || $params['syncPass'] === '') {

            return array('status' => 'error', 'message' => 'За импортиране са задължителни ID и парола за идентификация пред експортиращата система');
        }
        $body = '';
        // Самостоятелен малък отговор; никога не извикваме тежкия export endpoint.
        $curl = curl_init(rtrim($url, '/') . '/sync_Settings/checkConnection');
        curl_setopt_array($curl, array(
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 8192) {

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        try {
            $sent = curl_exec($curl);
            $errno = curl_errno($curl);
            $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($curl);
        }

        if ($code === 404) {

            return array('status' => 'warning', 'message' => 'Адресът не поддържа бързата проверка. Проверете URL и обновете sync и на експортиращата система');
        }
        if ($code === 401 || $code === 403) {

            return array('status' => 'error', 'message' => 'Отказан достъп (HTTP ' . $code . '). Проверете ID, паролата и разрешените IP адреси');
        }
        if ($code && $code !== 200) {

            return array('status' => 'error', 'message' => 'Експортиращата система върна HTTP ' . $code . '. Проверете URL и сървъра');
        }
        if ($sent === false) {
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                $message = 'Проверката не завърши до 5 секунди. Проверете достъпността и натоварването на експортиращата система';
            } elseif ($errno === CURLE_COULDNT_RESOLVE_HOST) {
                $message = 'Адресът на експортиращата система не може да се намери в DNS';
            } elseif ($errno === CURLE_COULDNT_CONNECT) {
                $message = 'Не може да се установи връзка с експортиращата система';
            } elseif ($errno === CURLE_SSL_CACERT || $errno === CURLE_SSL_CONNECT_ERROR) {
                $message = 'Неуспешна защитена TLS връзка. Проверете сертификата на експортиращата система';
            } else {
                $message = 'Неуспешна проверка на връзката (cURL ' . $errno . ')';
            }

            return array('status' => 'error', 'message' => $message);
        }
        $response = json_decode($body, true);
        if (!is_array($response) || ($response['protocol'] ?? null) !== 'sync-connection-v1' ||
            ($response['authenticated'] ?? null) !== true) {

            return array('status' => 'error', 'message' => 'Полученият отговор не потвърждава sync идентификацията');
        }

        return array('status' => 'success', 'message' => 'Връзката и идентификацията са успешни');
    }


    /**
     * Връща данните от експорт адреса
     * 
     * @param string $expAdd
     * 
     * @return array
     */
    public static function getDataFromUrl($expAdd)
    {
        ini_set('default_socket_timeout', self::EXPORT_REQUEST_TIMEOUT);

        $url = sync_Setup::get('EXPORT_URL');
        expect($url);
        $url = rtrim($url, '/') . '/' . $expAdd . '/export';
        self::requireSecureUrl($url);

        $params = array(
            'syncSysId' => trim((string) sync_Setup::get('SYS_ID')),
            'syncPass' => sync_Setup::getSyncPass(),
        );
        expect($params['syncSysId'] !== '' && $params['syncPass'] !== '', 'Импортирането изисква ID и парола за експортиращата система');
        $httpOptions = array(
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($params),
            'timeout' => self::EXPORT_REQUEST_TIMEOUT,
            'follow_location' => 0,
            'max_redirects' => 0,
        );
        $context = stream_context_create(array(
            'http' => $httpOptions,
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));

        // Запазваме transport причината, която @ и общото съобщение скриваха.
        $requestErrors = array();
        $http_response_header = array();
        set_error_handler(function ($severity, $message) use (&$requestErrors) {
            // PHP маскира userinfo в URL, но оставя query параметрите видими.
            $requestErrors[] = preg_replace('/^file_get_contents\(.*\):\s*/s', '', $message);

            return true;
        }, E_WARNING);
        try {
            $res = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        $statusCode = null;
        foreach ($http_response_header as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', (string) $headerLine, $matches)) {
                $statusCode = (int) $matches[1];
            }
        }
        if ($res === false || ($statusCode !== null && ($statusCode < 200 || $statusCode >= 300))) {
            $urlParts = parse_url($url);
            $safeUrl = ($urlParts['scheme'] ?? '') . '://' . ($urlParts['host'] ?? '') .
                (isset($urlParts['port']) ? ':' . $urlParts['port'] : '') . ($urlParts['path'] ?? '');
            $detail = $httpOptions['method'] . ' ' . $safeUrl . '; ' .
                ($statusCode !== null ? 'HTTP ' . $statusCode : 'без HTTP отговор');
            if ($statusCode === null && $requestErrors) {
                $detail .= '; ' . substr(str_replace($url, '[URL]', implode('; ', array_unique($requestErrors))), 0, 1000);
            }
            $message = 'Грешка при свързване с експортиращата система: ' . $detail;
            self::logErr($message);
            expect(false, $message);
        }

        $uncompressed = @gzuncompress($res);
        // Компресираният отговор може да е стотици мегабайти; не го държим
        // успоредно с разкомпресирания и с готовия обектен граф.
        $res = null;
        expect($uncompressed !== false, 'Невалиден отговор от експортиращата система');

        $resArr = @unserialize($uncompressed, array('allowed_classes' => array('stdClass')));
        $uncompressed = null;
        expect(is_array($resArr), 'Невалидни данни от експортиращата система');

        if (Request::get('_bp') && haveRole('admin')) {
            bp($resArr);
        }
        
        return $resArr;
    }
    
    
    /**
     * Експортиране на folderId
     * 
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     * @param stdClass|null $exportState
     */
    public static function fixFolderIdExport(&$rec, $fName, $field, &$res, $controller, $exportState = null)
    {
        if (!isset($rec->{$fName})) {
            unset($rec->{$fName});
            
            return ;
        }
        
        $fRec = doc_Folders::fetch($rec->{$fName});
        
        if (!$fRec) {
            unset($rec->{$fName});
            
            return ;
        }
        
        $coverClassName = self::$fNewNamePref . 'coverClass';
        $coverIdName = self::$fNewNamePref . 'coverId';
        
        $rec->{$coverClassName} = cls::get($fRec->coverClass ?? null)->className;
        $rec->{$coverIdName} = $fRec->coverId ?? null;
        
        $rec->{$fName} = null;
        
        sync_Map::exportRec($fRec->coverClass ?? null, $fRec->coverId ?? null, $res, $controller, $exportState);
    }
    
    
    /**
     * Импортиране на folderId
     * 
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     */
    public static function fixFolderIdImport(&$rec, $fName, $field, &$res, $controller, $update = null)
    {
        $coverClassName = self::$fNewNamePref . 'coverClass';
        $coverIdName = self::$fNewNamePref . 'coverId';
        
        if (!isset($rec->{$coverClassName}) || !isset($rec->{$coverIdName})) {
            unset($coverClassName);
            unset($coverIdName);
            
            return ;
        }
        
        $iRecId = sync_Map::importRec($rec->{$coverClassName}, $rec->{$coverIdName}, $res, $controller, $update);
        if ($iRecId) {
            if (cls::load($rec->{$coverClassName}, true) && cls::haveInterface('doc_FolderIntf', $rec->{$coverClassName})) {
                $inst = cls::get($rec->{$coverClassName});
                
                if (($iRecFetch = $inst->fetch($iRecId)) && ($folderId = $inst::forceCoverAndFolder($iRecFetch))) {
                    $rec->folderId = $folderId;
                }
            }
        }
        
        
        unset($coverClassName);
        unset($coverIdName);
    }
}
