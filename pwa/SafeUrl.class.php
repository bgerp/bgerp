<?php


/**
 * Безопасно сваляне на публичен HTTP(S) ресурс за PWA share target
 *
 * Методът download() може да се извиква само от логнат потребител. При успех
 * собствеността върху върнатия tmpPath преминава към извикващия код и той е
 * длъжен да извика cleanup() във finally, след като файлът бъде абсорбиран.
 * При изключение временният файл винаги се изтрива от този клас.
 *
 * @package pwa
 */
class pwa_SafeUrl
{
    const ERROR_AUTH = 1;
    const ERROR_INVALID_URL = 2;
    const ERROR_BLOCKED_DESTINATION = 3;
    const ERROR_DNS = 4;
    const ERROR_REDIRECT = 5;
    const ERROR_SIZE = 6;
    const ERROR_NETWORK = 7;
    const ERROR_RESPONSE = 8;
    const ERROR_TEMP_FILE = 9;

    const MAX_BYTES = 104857600;
    const MAX_DISCARDED_BODY_BYTES = 1048576;
    const MAX_URL_LENGTH = 2048;
    const MAX_REDIRECTS = 3;
    const MAX_HEADER_BYTES = 65536;
    const CONNECT_TIMEOUT_SECONDS = 5;
    const TOTAL_TIMEOUT_SECONDS = 30;
    const LOW_SPEED_LIMIT = 1024;
    const LOW_SPEED_SECONDS = 10;


    /**
     * Сваля публичен URL във временен файл.
     *
     * Върнатият tmpPath е собственост на извикващия код. Той трябва да бъде
     * подаден на cleanup() във finally след fileman::absorb().
     *
     * @param string $url
     *
     * @return stdClass tmpPath,fileName,mimeType,size,finalUrl
     * @throws RuntimeException Кодът на изключението е една от ERROR_* константите
     */
    public static function download($url)
    {
        if (core_Users::getCurrent() <= 0) {
            self::fail(self::ERROR_AUTH, 'Свалянето на споделен URL изисква логнат потребител');
        }

        if (!function_exists('curl_init') || !defined('CURLOPT_RESOLVE')) {
            self::fail(self::ERROR_NETWORK, 'Липсва необходимата HTTP поддръжка');
        }

        $current = self::validateUrl($url);
        $deadline = microtime(true) + self::TOTAL_TIMEOUT_SECONDS;
        $seen = array();
        $tmpPath = @tempnam(fileman_Files::getTempDir(), 'pwa_url_');
        if (!$tmpPath) {
            self::fail(self::ERROR_TEMP_FILE, 'Не може да се създаде временен файл');
        }

        $keepFile = false;
        try {
            for ($redirectCount = 0; $redirectCount <= self::MAX_REDIRECTS; $redirectCount++) {
                $urlKey = hash('sha256', $current->url);
                if (isset($seen[$urlKey])) {
                    self::fail(self::ERROR_REDIRECT, 'Открит е цикъл при пренасочване');
                }
                $seen[$urlKey] = true;

                $ips = self::resolvePublicIps($current->host, $current->isIp);
                $hop = null;
                $lastNetworkError = null;
                foreach ($ips as $pinnedIp) {
                    $remaining = (int) ceil($deadline - microtime(true));
                    if ($remaining <= 0) {
                        self::fail(self::ERROR_NETWORK, 'Изтече времето за сваляне');
                    }

                    try {
                        // Свързваме се само с вече проверен IP. DNS не се
                        // резолва втори път и не може да се използва за rebinding.
                        $hop = self::requestHop($current, $pinnedIp, $tmpPath, $remaining);

                        break;
                    } catch (RuntimeException $e) {
                        if ((int) $e->getCode() !== self::ERROR_NETWORK) {
                            throw $e;
                        }
                        $lastNetworkError = $e;
                    }
                }
                if (!$hop) {
                    throw $lastNetworkError ?: new RuntimeException('Няма достъпен публичен IP адрес', self::ERROR_NETWORK);
                }

                if (in_array($hop->status, array(301, 302, 303, 307, 308), true)) {
                    if ($redirectCount >= self::MAX_REDIRECTS || !$hop->redirectUrl) {
                        self::fail(self::ERROR_REDIRECT, 'Невалидно или прекалено много пренасочвания');
                    }

                    $next = self::validateUrl($hop->redirectUrl);
                    if ($current->scheme === 'https' && $next->scheme !== 'https') {
                        self::fail(self::ERROR_REDIRECT, 'Не е позволено пренасочване от HTTPS към HTTP');
                    }
                    $current = $next;

                    continue;
                }

                if ($hop->status < 200 || $hop->status >= 300) {
                    self::fail(self::ERROR_RESPONSE, 'Отдалеченият сървър върна неуспешен отговор');
                }

                $size = @filesize($tmpPath);
                if ($size === false || $size > self::MAX_BYTES) {
                    self::fail(self::ERROR_SIZE, 'Отдалеченият файл е по-голям от разрешеното');
                }
                if ($size <= 0) {
                    self::fail(self::ERROR_RESPONSE, 'Отдалеченият сървър върна празен файл');
                }

                $mimeType = fileman_Files::getMimeTypeFromFilePath($tmpPath);
                if (!$mimeType) {
                    $mimeType = self::normalizeMimeType($hop->contentType);
                }
                if (!$mimeType) {
                    $mimeType = 'application/octet-stream';
                }

                $fileName = self::buildFileName($hop->headers, $current->url, $mimeType);
                $keepFile = true;

                return (object) array(
                    'tmpPath' => $tmpPath,
                    'fileName' => $fileName,
                    'mimeType' => $mimeType,
                    'size' => (int) $size,
                    'finalUrl' => $current->url
                );
            }

            self::fail(self::ERROR_REDIRECT, 'Прекалено много пренасочвания');
        } catch (RuntimeException $e) {
            if ((int) $e->getCode() >= self::ERROR_AUTH && (int) $e->getCode() <= self::ERROR_TEMP_FILE) {
                throw $e;
            }

            throw new RuntimeException('Свалянето на отдалечения файл е неуспешно', self::ERROR_NETWORK, $e);
        } catch (Throwable $t) {
            throw new RuntimeException('Свалянето на отдалечения файл е неуспешно', self::ERROR_NETWORK, $t);
        } finally {
            if (!$keepFile && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }


    /**
     * Изтрива временния файл, върнат от download()
     *
     * @param stdClass|string $download
     *
     * @return bool
     */
    public static function cleanup($download)
    {
        $path = is_object($download) ? ($download->tmpPath ?? null) : $download;
        if (!is_string($path) || !is_file($path) || strpos(basename($path), 'pwa_url_') !== 0) {

            return false;
        }

        $tempDir = realpath(fileman_Files::getTempDir());
        $pathDir = realpath(dirname($path));
        if (!$tempDir || !$pathDir || $tempDir !== $pathDir) {

            return false;
        }

        return @unlink($path);
    }


    /**
     * Връща стабилен machine-readable тип за хвърлено от този клас изключение
     *
     * @param Throwable $exception
     *
     * @return string
     */
    public static function getErrorType($exception)
    {
        $types = array(
            self::ERROR_AUTH => 'auth',
            self::ERROR_INVALID_URL => 'url',
            self::ERROR_BLOCKED_DESTINATION => 'blocked',
            self::ERROR_DNS => 'dns',
            self::ERROR_REDIRECT => 'redirect',
            self::ERROR_SIZE => 'size',
            self::ERROR_NETWORK => 'network',
            self::ERROR_RESPONSE => 'response',
            self::ERROR_TEMP_FILE => 'temp'
        );

        $code = is_object($exception) && method_exists($exception, 'getCode') ? (int) $exception->getCode() : 0;

        return $types[$code] ?? 'network';
    }


    /**
     * Проверява и нормализира един URL преди DNS/HTTP заявка
     *
     * @param string $url
     *
     * @return stdClass
     */
    protected static function validateUrl($url)
    {
        if (!is_string($url)) {
            self::fail(self::ERROR_INVALID_URL, 'Невалиден URL');
        }

        if (!$url || $url !== trim($url) || strlen($url) > self::MAX_URL_LENGTH ||
            preg_match('/[\\x00-\\x20\\x7f\\\\]/', $url)) {
            self::fail(self::ERROR_INVALID_URL, 'Невалиден URL');
        }

        try {
            $parts = @parse_url($url);
        } catch (Throwable $t) {
            $parts = false;
        }
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass'])) {
            self::fail(self::ERROR_INVALID_URL, 'Невалиден URL');
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, array('http', 'https'), true)) {
            self::fail(self::ERROR_INVALID_URL, 'Позволени са само HTTP и HTTPS адреси');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            self::fail(self::ERROR_INVALID_URL, 'Не е позволен нестандартен порт');
        }

        $host = strtolower($parts['host']);
        if (substr($host, 0, 1) === '[' && substr($host, -1) === ']') {
            $host = substr($host, 1, -1);
        }
        if (!$host || strpos($host, '%') !== false) {
            self::fail(self::ERROR_INVALID_URL, 'Невалиден host в URL');
        }

        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
        if ($isIp) {
            $packed = @inet_pton($host);
            $host = $packed !== false ? inet_ntop($packed) : $host;
            if (!self::isGlobalIp($host)) {
                self::fail(self::ERROR_BLOCKED_DESTINATION, 'Адресът не е публичен');
            }
        } else {
            if (!self::isValidDnsHost($host) || self::looksLikeObscureIp($host)) {
                self::fail(self::ERROR_INVALID_URL, 'Невалиден публичен host');
            }
        }

        $urlHost = strpos($host, ':') !== false ? '[' . $host . ']' : $host;
        $normalizedUrl = $scheme . '://' . $urlHost;
        $normalizedUrl .= isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
        if (isset($parts['query'])) {
            $normalizedUrl .= '?' . $parts['query'];
        }

        return (object) array(
            'url' => $normalizedUrl,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'isIp' => $isIp
        );
    }


    /**
     * DNS име с поне една точка, без двусмислени или encoded етикети
     *
     * @param string $host
     *
     * @return bool
     */
    protected static function isValidDnsHost($host)
    {
        if (strlen($host) > 253 || strpos($host, '.') === false || substr($host, -1) === '.') {

            return false;
        }

        $labels = explode('.', $host);
        foreach ($labels as $label) {
            if (!$label || strlen($label) > 63 ||
                !preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/iD', $label)) {

                return false;
            }
        }

        return true;
    }


    /**
     * Отхвърля integer/octal/hex и съкратени IPv4 представяния
     *
     * @param string $host
     *
     * @return bool
     */
    protected static function looksLikeObscureIp($host)
    {
        $parts = explode('.', $host);
        foreach ($parts as $part) {
            if (!preg_match('/^(?:0x[0-9a-f]+|[0-9]+)$/iD', $part)) {

                return false;
            }
        }

        return true;
    }


    /**
     * Резолвира всички A/AAAA от CNAME веригата и отказва целия host, ако
     * дори един от върнатите адреси не е глобален.
     *
     * @param string $host
     * @param bool   $isIp
     *
     * @return array
     */
    protected static function resolvePublicIps($host, $isIp = false)
    {
        if ($isIp) {

            return array($host);
        }

        if (!function_exists('dns_get_record')) {
            self::fail(self::ERROR_DNS, 'DNS резолюцията не е достъпна');
        }

        $queue = array($host);
        $visited = array();
        $ips = array();
        while ($queue) {
            $name = strtolower(rtrim(array_shift($queue), '.'));
            if (isset($visited[$name])) {
                continue;
            }
            if (count($visited) >= 8 || !self::isValidDnsHost($name)) {
                self::fail(self::ERROR_DNS, 'Невалидна DNS верига');
            }
            $visited[$name] = true;

            $records = @dns_get_record($name, DNS_A | DNS_AAAA | DNS_CNAME);
            if (!is_array($records)) {
                self::fail(self::ERROR_DNS, 'DNS резолюцията е неуспешна');
            }

            foreach ($records as $record) {
                $type = strtoupper($record['type'] ?? '');
                if ($type === 'A' && !empty($record['ip'])) {
                    $ips[] = $record['ip'];
                } elseif ($type === 'AAAA' && !empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                } elseif ($type === 'CNAME' && !empty($record['target'])) {
                    $queue[] = $record['target'];
                }
            }
        }

        $result = array();
        foreach ($ips as $ip) {
            $packed = @inet_pton($ip);
            if ($packed === false) {
                self::fail(self::ERROR_DNS, 'DNS върна невалиден адрес');
            }
            $ip = inet_ntop($packed);
            if (!self::isGlobalIp($ip)) {
                self::fail(self::ERROR_BLOCKED_DESTINATION, 'DNS върна непубличен адрес');
            }
            $result[$ip] = $ip;
        }

        if (!$result) {
            self::fail(self::ERROR_DNS, 'DNS не върна A или AAAA адрес');
        }

        return array_values($result);
    }


    /**
     * Изпълнява само един HTTP hop към предварително фиксиран IP
     *
     * @param stdClass $urlInfo
     * @param string   $pinnedIp
     * @param string   $tmpPath
     * @param int      $timeout
     *
     * @return stdClass
     */
    protected static function requestHop($urlInfo, $pinnedIp, $tmpPath, $timeout)
    {
        $fp = @fopen($tmpPath, 'w+b');
        if (!$fp) {
            self::fail(self::ERROR_TEMP_FILE, 'Не може да се отвори временният файл');
        }

        $state = (object) array(
            'status' => 0,
            'headers' => array(),
            'headerBytes' => 0,
            'bodyBytes' => 0,
            'sizeExceeded' => false,
            'headerExceeded' => false,
            'invalidEncoding' => false,
            'writeError' => false
        );

        $headerFunction = function ($ch, $line) use ($state) {
            $length = strlen($line);
            $state->headerBytes += $length;
            if ($state->headerBytes > self::MAX_HEADER_BYTES) {
                $state->headerExceeded = true;

                return 0;
            }

            if (preg_match('/^HTTP\\/\\S+\\s+(\\d{3})(?:\\s|$)/i', trim($line), $matches)) {
                $state->status = (int) $matches[1];
                $state->headers = array();

                return $length;
            }

            $pair = explode(':', $line, 2);
            if (count($pair) !== 2) {

                return $length;
            }

            $name = strtolower(trim($pair[0]));
            $value = trim($pair[1]);
            $state->headers[$name] = $value;

            if ($name === 'content-length') {
                $bodyLimit = ($state->status >= 200 && $state->status < 300)
                    ? self::MAX_BYTES
                    : self::MAX_DISCARDED_BODY_BYTES;
                if (self::isDeclaredSizeTooLarge($value, $bodyLimit)) {
                    $state->sizeExceeded = true;

                    return 0;
                }
            }
            if ($state->status >= 200 && $state->status < 300) {
                if ($name === 'content-encoding' && $value !== '' && strtolower($value) !== 'identity') {
                    $state->invalidEncoding = true;

                    return 0;
                }
            }

            return $length;
        };

        $writeFunction = function ($ch, $data) use ($state, $fp) {
            $length = strlen($data);
            $isSuccess = $state->status >= 200 && $state->status < 300;
            $bodyLimit = $isSuccess ? self::MAX_BYTES : self::MAX_DISCARDED_BODY_BYTES;
            if ($state->bodyBytes + $length > $bodyLimit) {
                $state->sizeExceeded = true;

                return 0;
            }
            $state->bodyBytes += $length;
            if (!$isSuccess) {

                return $length;
            }

            $written = @fwrite($fp, $data);
            if ($written === false || $written !== $length) {
                $state->writeError = true;

                return 0;
            }
            return $written;
        };

        $ch = curl_init();
        if ($ch === false) {
            fclose($fp);
            self::fail(self::ERROR_NETWORK, 'Не може да се стартира HTTP заявка');
        }

        $resolveIp = strpos($pinnedIp, ':') !== false ? '[' . $pinnedIp . ']' : $pinnedIp;
        $options = array(
            CURLOPT_URL => $urlInfo->url,
            CURLOPT_HTTPGET => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(self::CONNECT_TIMEOUT_SECONDS, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_LOW_SPEED_LIMIT => self::LOW_SPEED_LIMIT,
            CURLOPT_LOW_SPEED_TIME => self::LOW_SPEED_SECONDS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROXY => '',
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_NETRC => CURL_NETRC_IGNORED,
            CURLOPT_USERAGENT => 'bgERP PWA safe URL fetch',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/pdf,image/*,application/octet-stream,text/html;q=0.8,*/*;q=0.5',
                'Accept-Encoding: identity',
                'Connection: close'
            ),
            CURLOPT_HEADERFUNCTION => $headerFunction,
            CURLOPT_WRITEFUNCTION => $writeFunction
        );
        // При literal IP самият URL вече фиксира адреса. CURLOPT_RESOLVE за
        // IPv6 literal host не е преносим преди libcurl 8.13.0; за DNS име
        // фиксираме избрания и вече проверен A/AAAA адрес.
        if (!$urlInfo->isIp) {
            $options[CURLOPT_RESOLVE] = array($urlInfo->host . ':' . $urlInfo->port . ':' . $resolveIp);
        }
        if (defined('CURLOPT_NOPROXY')) {
            $options[CURLOPT_NOPROXY] = '*';
        }
        if (defined('CURLOPT_PROTOCOLS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        if (defined('CURLOPT_MAXFILESIZE_LARGE')) {
            $options[CURLOPT_MAXFILESIZE_LARGE] = self::MAX_BYTES;
        }
        if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
            $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        }
        if (strpos($pinnedIp, ':') !== false && defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V6')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V6;
        } elseif (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        try {
            if (!curl_setopt_array($ch, $options)) {
                self::fail(self::ERROR_NETWORK, 'Не могат да се зададат HTTP ограниченията');
            }

            $success = curl_exec($ch);
            $curlError = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $primaryIp = (string) curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            $redirectUrl = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        } finally {
            @fflush($fp);
            fclose($fp);
            curl_close($ch);
        }

        if ($state->sizeExceeded) {
            self::fail(self::ERROR_SIZE, 'Отдалеченият файл е по-голям от разрешеното');
        }
        if ($state->headerExceeded) {
            self::fail(self::ERROR_RESPONSE, 'Отдалеченият отговор има прекалено големи headers');
        }
        if ($state->invalidEncoding) {
            self::fail(self::ERROR_RESPONSE, 'Отдалеченият отговор използва непозволено кодиране');
        }
        if ($state->writeError) {
            self::fail(self::ERROR_TEMP_FILE, 'Не може да се запише временният файл');
        }
        if (defined('CURLE_FILESIZE_EXCEEDED') && $curlError === CURLE_FILESIZE_EXCEEDED) {
            self::fail(self::ERROR_SIZE, 'Отдалеченият файл е по-голям от разрешеното');
        }
        if ($success === false || $curlError) {
            self::fail(self::ERROR_NETWORK, 'HTTP заявката е неуспешна');
        }

        $primaryPacked = @inet_pton($primaryIp);
        $pinnedPacked = @inet_pton($pinnedIp);
        if ($primaryPacked === false || $pinnedPacked === false || $primaryPacked !== $pinnedPacked ||
            !self::isGlobalIp($primaryIp)) {
            self::fail(self::ERROR_BLOCKED_DESTINATION, 'HTTP връзката не използва проверения публичен адрес');
        }

        return (object) array(
            'status' => $status ?: (int) $state->status,
            'headers' => $state->headers,
            'redirectUrl' => $redirectUrl,
            'contentType' => $contentType
        );
    }


    /**
     * Проверява дали Content-Length надхвърля лимита без integer overflow
     *
     * @param string $value
     * @param int    $maxBytes
     *
     * @return bool
     */
    protected static function isDeclaredSizeTooLarge($value, $maxBytes = self::MAX_BYTES)
    {
        $value = trim(explode(',', $value, 2)[0]);
        if (!ctype_digit($value)) {

            return false;
        }

        $value = ltrim($value, '0');
        $value = $value === '' ? '0' : $value;
        $max = (string) max(0, (int) $maxBytes);

        return strlen($value) > strlen($max) ||
            (strlen($value) === strlen($max) && strcmp($value, $max) > 0);
    }


    /**
     * Име от Content-Disposition, URL path и реално разпознат MIME тип
     *
     * @param array  $headers
     * @param string $url
     * @param string $mimeType
     *
     * @return string
     */
    protected static function buildFileName($headers, $url, $mimeType)
    {
        $candidate = '';
        $disposition = $headers['content-disposition'] ?? '';
        if ($disposition && preg_match("/filename\\*\\s*=\\s*UTF-8''([^;]+)/i", $disposition, $matches)) {
            $candidate = rawurldecode(trim($matches[1], " \\t\\\"'"));
        } elseif ($disposition && preg_match('/filename\\s*=\\s*"([^"]*)"/i', $disposition, $matches)) {
            $candidate = stripcslashes($matches[1]);
        } elseif ($disposition && preg_match('/filename\\s*=\\s*([^;]+)/i', $disposition, $matches)) {
            $candidate = trim($matches[1], " \\t\\\"'");
        }

        if (!$candidate) {
            $parts = @parse_url($url);
            $candidate = isset($parts['path']) ? rawurldecode(basename($parts['path'])) : '';
        }

        $candidate = str_replace(array("\0", "\r", "\n", '/', '\\'), '_', $candidate);
        $candidate = fileman_Files::normalizeFileName($candidate);
        $candidate = trim($candidate, '._-');
        if (!$candidate) {
            $candidate = 'shared_file';
        }

        $candidate = fileman_Mimes::addCorrectFileExt($candidate, $mimeType);
        $candidate = fileman_Files::normalizeFileName($candidate);

        return self::limitFileName($candidate, 200);
    }


    /**
     * Ограничава името, като пази разширението
     *
     * @param string $fileName
     * @param int    $maxLength
     *
     * @return string
     */
    protected static function limitFileName($fileName, $maxLength)
    {
        if (strlen($fileName) <= $maxLength) {

            return $fileName;
        }

        $ext = fileman_Files::getExt($fileName);
        if (strlen($ext) > 20) {
            $ext = '';
        }
        $suffix = $ext ? '.' . $ext : '';
        $baseLength = max(1, $maxLength - strlen($suffix));
        $base = $suffix ? substr($fileName, 0, -(strlen($suffix))) : $fileName;

        return substr(substr($base, 0, $baseLength) . $suffix, 0, $maxLength);
    }


    /**
     * Нормализира MIME от HTTP header; реалният MIME от файла е с приоритет
     *
     * @param string $mimeType
     *
     * @return string|null
     */
    protected static function normalizeMimeType($mimeType)
    {
        $mimeType = strtolower(trim(explode(';', (string) $mimeType, 2)[0]));

        return preg_match('/^[a-z0-9!#$&^_.+-]+\\/[a-z0-9!#$&^_.+-]+$/D', $mimeType) ? $mimeType : null;
    }


    /**
     * Допуска само глобално routable IPv4/IPv6 адреси
     *
     * @param string $ip
     *
     * @return bool
     */
    protected static function isGlobalIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $blocked = array(
                '0.0.0.0/8',
                '10.0.0.0/8',
                '100.64.0.0/10',
                '127.0.0.0/8',
                '169.254.0.0/16',
                '172.16.0.0/12',
                '192.0.0.0/24',
                '192.0.2.0/24',
                '192.88.99.0/24',
                '192.168.0.0/16',
                '198.18.0.0/15',
                '198.51.100.0/24',
                '203.0.113.0/24',
                '224.0.0.0/4',
                '240.0.0.0/4'
            );
            foreach ($blocked as $cidr) {
                if (self::isInCidr($ip, $cidr)) {

                    return false;
                }
            }

            return true;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ||
            !self::isInCidr($ip, '2000::/3')) {

            return false;
        }

        // Специални/documentation/transition диапазони вътре в 2000::/3.
        foreach (array('2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20') as $cidr) {
            if (self::isInCidr($ip, $cidr)) {

                return false;
            }
        }

        return true;
    }


    /**
     * Двоична CIDR проверка, еднаква за IPv4 и IPv6
     *
     * @param string $ip
     * @param string $cidr
     *
     * @return bool
     */
    protected static function isInCidr($ip, $cidr)
    {
        list($network, $prefix) = explode('/', $cidr, 2);
        $ipBin = @inet_pton($ip);
        $networkBin = @inet_pton($network);
        $prefix = (int) $prefix;
        if ($ipBin === false || $networkBin === false || strlen($ipBin) !== strlen($networkBin) ||
            $prefix < 0 || $prefix > strlen($ipBin) * 8) {

            return false;
        }

        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;
        if ($bytes && substr($ipBin, 0, $bytes) !== substr($networkBin, 0, $bytes)) {

            return false;
        }
        if (!$bits) {

            return true;
        }

        $mask = (0xff << (8 - $bits)) & 0xff;

        return (ord($ipBin[$bytes]) & $mask) === (ord($networkBin[$bytes]) & $mask);
    }


    /**
     * Хвърля категоризирано, но непоказващо URL/IP изключение
     *
     * @param int    $code
     * @param string $message
     *
     * @throws RuntimeException
     */
    protected static function fail($code, $message)
    {
        throw new RuntimeException($message, $code);
    }
}
