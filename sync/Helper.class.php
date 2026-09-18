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
     * зададен, IP адресът на клиента. Преходният legacyIp режим е изричен запис
     * в sync_Settings и работи само без подадени credentials.
     *
     * @param bool $throwIfMissing
     * @param bool $allowLegacy
     *
     * @return stdClass|null
     */
    public static function getRequestSettings($throwIfMissing = true, $allowLegacy = false)
    {
        $cacheKey = $allowLegacy ? 'withLegacy' : 'credentialsOnly';

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
                    expect($rec && $rec->state == 'active', 'Невалидна или неактивна sync настройка');
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

                // Локалният избор важи и след forceSystemUser(), когато
                // колекторите поискат настройката с другия legacy режим.
                self::$requestSettings['credentialsOnly'] = $rec;
                self::$requestSettings['withLegacy'] = $rec;
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
                    $rec->state == 'active' &&
                    $authType == 'credentials' &&
                    strlen((string) $rec->pass) > 0 &&
                    hash_equals((string) $rec->pass, $pass) &&
                    self::isAllowedIp($remoteAddr, $rec->allowedIps ?? null)) {
                    self::$requestSettings[$cacheKey] = $rec;
                } else {
                    // Подаден е ID, тоест опитът е нарочен - оставяме следа.
                    self::logAuthFailure($sysId, $remoteAddr, $rec, $authType, $pass);
                }
            } elseif ($pass === '' && $allowLegacy) {
                $query = sync_Settings::getQuery();
                $query->where("#state = 'active' AND #authType = 'legacyIp'");

                $matchedRec = null;
                $isAmbiguous = false;
                while ($rec = $query->fetch()) {
                    if (self::isAllowedIp($remoteAddr, $rec->allowedIps ?? null, true, true)) {
                        if ($matchedRec) {
                            $isAmbiguous = true;

                            break;
                        }

                        $matchedRec = $rec;
                    }
                }

                // При припокриващи се legacy allowlists не избираме
                // недетерминирано запис с потенциално по-широки права.
                if ($isAmbiguous) {
                    self::logWarning(
                        'Повече от една legacy sync настройка разрешава ' .
                            ($remoteAddr ?: 'адреса на заявката')
                    );
                    $matchedRec = null;
                }

                self::$requestSettings[$cacheKey] = $matchedRec;

                if ($isAmbiguous && $throwIfMissing) {
                    expect(false, 'Повече от една legacy sync настройка разрешава този IP адрес');
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
        } elseif ($rec->state != 'active') {
            $reason = 'неактивен запис';
        } elseif ($authType != 'credentials') {
            $reason = 'записът е в legacy IP режим';
        } elseif (!strlen((string) $rec->pass)) {
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
     * Credentials не се изпращат по обикновен HTTP. HTTP без credentials е
     * възможен само с изричния временен ALLOW_LEGACY_HTTP opt-in.
     *
     * @param string $url
     */
    public static function requireSecureUrl($url)
    {
        $parts = parse_url($url);
        expect(is_array($parts), 'Невалиден URL към sync master');
        $scheme = strtolower($parts['scheme'] ?? '');

        expect(
            in_array($scheme, array('http', 'https'), true) && !empty($parts['host']),
            'Невалиден URL към sync master'
        );

        if ($scheme != 'https') {
            $haveCredentials = strlen((string) sync_Setup::get('SYS_ID')) ||
                strlen(sync_Setup::getSyncPass());
            expect(!$haveCredentials, 'Sync credentials могат да се изпращат само по HTTPS');
            expect(
                sync_Setup::get('ALLOW_LEGACY_HTTP') == 'yes',
                'HTTP без credentials е изключен; разрешете го само временно за legacy rollout'
            );
        }
    }


    /**
     * Проверява HTTPS scheme/host/effective port и забранява userinfo
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

        $urlPort = isset($urlParts['port']) ? (int) $urlParts['port'] : 443;
        $trustedPort = isset($trustedParts['port']) ? (int) $trustedParts['port'] : 443;

        return strtolower($urlParts['host']) === strtolower($trustedParts['host']) &&
            $urlPort === $trustedPort;
    }


    /**
     * Дали URL-ът е годен за доверен origin: HTTPS, с хост и без userinfo
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isTrustedOriginUrl($url)
    {
        $parts = parse_url((string) $url);

        return is_array($parts) &&
            strtolower($parts['scheme'] ?? '') === 'https' &&
            !empty($parts['host']) &&
            !isset($parts['user']) &&
            !isset($parts['pass']);
    }


    /**
     * Сваля ограничен по размер файл само от изрично доверен HTTPS origin
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
            'syncSysId' => sync_Setup::get('SYS_ID'),
            'syncPass' => sync_Setup::getSyncPass(),
        );
        $context = stream_context_create(array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($params),
                'timeout' => self::EXPORT_REQUEST_TIMEOUT,
                'follow_location' => 0,
                'max_redirects' => 0,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));

        $res = @file_get_contents($url, false, $context);
        if ($res === false) {
            self::logErr('Грешка при синхронизиране с ' . $url);
            expect(false, 'Грешка при свързване с експортиращата система');
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
        
        $rec->{$coverClassName} = cls::get($fRec->coverClass)->className;
        $rec->{$coverIdName} = $fRec->coverId;
        
        $rec->{$fName} = null;
        
        sync_Map::exportRec($fRec->coverClass, $fRec->coverId, $res, $controller, $exportState);
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
