<?php

namespace EoriTest;

use ErrorException;
use Exception;
use stdClass;

/**
 * Изолирани проверки на EORI услугите и кеша, без мрежа и база.
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/eori.php [път до Eori.class.php] [път до type/Eori.class.php]
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class EoriTransport
{
    public static $soap, $json, $httpCode, $options, $soapCalls, $curlCalls, $closed, $socketTimeout;
    public static function reset($soap = null, $json = '', $httpCode = 200)
    {
        self::$soap = $soap;
        self::$json = $json;
        self::$httpCode = $httpCode;
        self::$options = array();
        self::$soapCalls = self::$curlCalls = self::$closed = 0;
        self::$socketTimeout = null;
    }
}

foreach (array('CURLOPT_RETURNTRANSFER', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT', 'CURLOPT_HTTPHEADER', 'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLINFO_HTTP_CODE', 'CURLOPT_SSL_VERIFYHOST', 'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_CUSTOMREQUEST') as $index => $name) {
    if (!defined($name)) define($name, $index);
}
function curl_init($url) { EoriTransport::$curlCalls++; return new stdClass(); }
function curl_setopt($curl, $option, $value) { EoriTransport::$options[$option] = $value; }
function curl_exec($curl) { return EoriTransport::$json; }
function curl_getinfo($curl, $option) { return EoriTransport::$httpCode; }
function curl_close($curl) { EoriTransport::$closed++; }
function class_exists($name) { return \class_exists(__NAMESPACE__ . '\\' . $name); }
class SoapClient
{
    public function __construct($wsdl, $options = array())
    {
        EoriTransport::$soapCalls++;
        EoriTransport::$options = $options;
        EoriTransport::$socketTimeout = ini_get('default_socket_timeout');
        if (EoriTransport::$soap instanceof Exception) throw EoriTransport::$soap;
    }
    public function __soapCall($method, $params) { return EoriTransport::$soap; }
}

class core_exception_Redirect extends Exception {}
class core_Debug
{
    public static $timers = 0;
    public static function startTimer($name) { self::$timers++; }
    public static function stopTimer($name) { self::$timers--; }
}
class core_Manager
{
    public $records = array();
    public function fetch($condition) { return isset($this->records[$condition[1]]) ? clone $this->records[$condition[1]] : false; }
    public function save($rec, $fields = null, $mode = null)
    {
        $rec->id = $rec->id ?? 1;
        if ($fields && isset($this->records[$rec->eori])) {
            foreach (explode(',', $fields) as $field) {
                $field = trim($field);
                $this->records[$rec->eori]->$field = $rec->$field;
            }
        } else {
            $this->records[$rec->eori] = clone $rec;
        }
    }
}
class dt
{
    public static function verbal2mysql() { return '2026-01-15 12:00:00'; }
    public static function subtractSecs($seconds) { return gmdate('Y-m-d H:i:s', strtotime(self::verbal2mysql() . ' UTC') - $seconds); }
}
class drdata_Setup { public static function get($name) { return $name == 'EORI_TTL' ? 5184000 : 2592000; } }
function countR($value) { return count($value); }
class cls
{
    public static $eori;
    public static function get($class) { return self::$eori; }
}
class type_Varchar
{
    public $_isRefreshed = false;
    public function isValid($value) { return strlen($value) > $this->dbFieldLen ? array('error' => 'Too long') : array(); }
}

// Пространството от имена изолира реалния код от мрежата и базата чрез горните заместители.
$source = file_get_contents($argv[1] ?? dirname(__DIR__) . '/Eori.class.php');
eval('namespace EoriTest; use Exception; use stdClass;' . substr($source, 5));
$source = file_get_contents($argv[2] ?? dirname(__DIR__) . '/type/Eori.class.php');
eval('namespace EoriTest;' . substr($source, 5));

$checks = $failures = 0;
function check($ok, $message)
{
    global $checks, $failures;
    $checks++;
    if (!$ok) { $failures++; echo "FAIL: {$message}\n"; }
}
function soapResult($eori, $status)
{
    return (object) array('return' => (object) array('result' => (object) array('eori' => $eori, 'status' => $status, 'statusDescr' => $status === 0 ? 'Valid' : 'Not valid')));
}
function nextRequest($checker)
{
    $next = new drdata_Eori();
    $next->records = $checker->records;

    return $next;
}
function checkStatus($label, $eori, $expected, $soap = null, $json = '', $httpCode = 200)
{
    EoriTransport::reset($soap, $json, $httpCode);
    $timeout = ini_get('default_socket_timeout');
    $result = (new drdata_Eori())->checkStatus($eori);
    check($result[0] === $expected, $label);
    check(ini_get('default_socket_timeout') === $timeout && core_Debug::$timers === 0, $label . ': restore timeout/timer');
    if ($expected !== 'syntax') {
        $gb = substr($eori, 0, 2) === 'GB';
        check(EoriTransport::$curlCalls === ($gb ? 1 : 0) && EoriTransport::$soapCalls === ($gb ? 0 : 1), $label . ': correct registry');
        if ($gb) {
            check(EoriTransport::$closed === 1 && (EoriTransport::$options[CURLOPT_TIMEOUT] ?? 0) === 10 && (EoriTransport::$options[CURLOPT_CONNECTTIMEOUT] ?? 0) === 4, $label . ': curl cleanup/timeouts');
        } else {
            check(EoriTransport::$socketTimeout === '10' && (EoriTransport::$options['connection_timeout'] ?? 0) === 4, $label . ': SOAP timeouts');
        }
    }
    return $result;
}

$eu = 'BG000000000000000';
$gb = 'GB000000000000';
checkStatus('EU valid', $eu, 'valid', soapResult($eu, 0));
checkStatus('EU invalid', $eu, 'invalid', soapResult($eu, 1));
checkStatus('XI uses EU', 'XI000000000000', 'valid', soapResult('XI000000000000', 0));
checkStatus('SOAP unavailable', $eu, 'unknown', new Exception('Service unavailable'));
checkStatus('SOAP empty response', $eu, 'unknown');
checkStatus('SOAP missing result', $eu, 'unknown', (object) array('return' => new stdClass()));
checkStatus('SOAP unexpected status', $eu, 'unknown', soapResult($eu, 2));
checkStatus('SOAP missing status', $eu, 'unknown', (object) array('return' => (object) array('result' => (object) array('eori' => $eu))));
checkStatus('SOAP wrong number', $eu, 'unknown', soapResult('DE000000000000000', 0));
$error = soapResult($eu, 1);
$error->return->errorDescription = 'Service unavailable';
checkStatus('SOAP service error', $eu, 'unknown', $error);
$error = soapResult($eu, 1);
$error->return->result->errorReason = 'Temporarily unavailable';
checkStatus('SOAP result error', $eu, 'unknown', $error);
checkStatus('GB valid', $gb, 'valid', null, json_encode(array(array('eori' => $gb, 'valid' => true))));
checkStatus('GB invalid', $gb, 'invalid', null, json_encode(array(array('eori' => $gb, 'valid' => false))));
checkStatus('GB unavailable', $gb, 'unknown', null, false, 0);
checkStatus('GB malformed JSON', $gb, 'unknown', null, '<html>Error</html>');
checkStatus('GB API error', $gb, 'unknown', null, '{"code":"INVALID_REQUEST"}', 400);
checkStatus('GB HTTP error', $gb, 'unknown', null, json_encode(array(array('eori' => $gb, 'valid' => false))), 503);
checkStatus('GB missing valid', $gb, 'unknown', null, json_encode(array(array('eori' => $gb))));
checkStatus('GB wrong valid type', $gb, 'unknown', null, json_encode(array(array('eori' => $gb, 'valid' => 'false'))));
checkStatus('GB wrong number', $gb, 'unknown', null, '[{"eori":"GB111111111111","valid":true}]');
$details = array('traderName' => 'Example', 'address' => array('postcode' => 'AB1', 'cityName' => 'Example city', 'streetAndNumber' => 'Example street'));
$result = checkStatus('GB details', $gb, 'valid', null, json_encode(array(array('eori' => $gb, 'valid' => true, 'companyDetails' => $details))));
check($result[1] === "AB1, Example city\nExample street\nExample" && $result[2]['name'] === 'Example', 'Preserve company details');
checkStatus('Syntax rejected without requests', '123', 'syntax');
check(EoriTransport::$soapCalls === 0 && EoriTransport::$curlCalls === 0, 'No requests on syntax error');

EoriTransport::reset(new core_exception_Redirect('test redirect'));
$timeout = ini_get('default_socket_timeout');
try {
    (new drdata_Eori())->checkStatus($eu);
    check(false, 'Redirect propagates');
} catch (core_exception_Redirect $e) {
    check(ini_get('default_socket_timeout') === $timeout && core_Debug::$timers === 0, 'Redirect cleanup');
}

$checker = new drdata_Eori();
EoriTransport::reset(soapResult($eu, 1));
check($checker->check($eu)[0] === 'invalid' && $checker->records[$eu]->status === 'invalid', 'Cache a new negative result');
$checker = nextRequest($checker);
EoriTransport::reset(soapResult($eu, 0));
check($checker->check($eu)[0] === 'invalid' && EoriTransport::$soapCalls === 0, 'Fresh cache avoids network');
$checker->records[$eu]->lastChecked = '2025-01-01 00:00:00';
check($checker->check($eu)[0] === 'invalid' && count($checker->updateOnShutdown) === 1 && EoriTransport::$soapCalls === 0, 'Expired cache refresh stays deferred');
check($checker->check($eu, true)[0] === 'valid' && $checker->records[$eu]->status === 'valid' && EoriTransport::$soapCalls === 1, 'Forced check returns and saves the current status');
check($checker->records[$eu]->lastChecked === dt::verbal2mysql() && $checker->records[$eu]->lastUsed === dt::verbal2mysql() && !$checker->updateOnShutdown, 'Forced check updates dates and removes duplicate refresh');
check($checker->check(strtolower($eu), true)[0] === 'valid' && EoriTransport::$soapCalls === 1, 'Repeated force reuses the canonical number result in this request');
$checker = nextRequest($checker);
EoriTransport::reset(new Exception('Unavailable'));
check($checker->check($eu, true)[0] === 'unknown' && $checker->records[$eu]->status === 'unknown', 'Force failure caches unknown');
check($checker->check($eu, true)[0] === 'unknown' && EoriTransport::$soapCalls === 1, 'Unknown result is not retried within the same request');
$checker = nextRequest($checker);
$checker->records[$eu]->lastChecked = dt::subtractSecs(86401);
EoriTransport::reset(soapResult($eu, 0));
check($checker->check($eu)[0] === 'unknown' && count($checker->updateOnShutdown) === 1 && EoriTransport::$soapCalls === 0, 'Unknown retries after one day');
drdata_Eori::on_Shutdown($checker);
check($checker->records[$eu]->status === 'valid', 'Deferred refresh still updates the cache');

// Формата чете silent полетата и след това ги валидира повторно.
$method = $_SERVER['REQUEST_METHOD'] ?? null;
$_SERVER['REQUEST_METHOD'] = 'POST';
cls::$eori = new drdata_Eori();
$type = new drdata_type_Eori();
$type->_isRefreshed = true;
EoriTransport::reset(null, json_encode(array(array('eori' => $gb, 'valid' => false))));
check(($type->isValid(strtolower($gb))['value'] ?? null) === $gb, 'Refresh preserves normalization');
$type->isValid($gb);
check(EoriTransport::$curlCalls === 0 && !cls::$eori->records, 'Refresh of an uncached EORI does not query or save the registry');
check(isset($type->isValid(str_repeat('A', 19))['error']), 'Refresh retains basic field validation');
$_SERVER['REQUEST_METHOD'] = 'GET';
$type->_isRefreshed = false;
$type->isValid($gb);
check(EoriTransport::$curlCalls === 0, 'Reloading a populated form does not query the registry');
$_SERVER['REQUEST_METHOD'] = 'POST';
$first = $type->isValid($gb);
$second = $type->isValid($gb);
check(EoriTransport::$curlCalls === 1 && isset($first['warning'], $second['warning']), 'Manual save checks once and retains both validation warnings');
check(cls::$eori->check($gb, true)[0] === 'invalid' && EoriTransport::$curlCalls === 1, 'Manual force in the same request reuses the online result');
cls::$eori = nextRequest(cls::$eori);
EoriTransport::reset(null, json_encode(array(array('eori' => $gb, 'valid' => true))));
check(!isset($type->isValid($gb)['warning']) && EoriTransport::$curlCalls === 1, 'A later manual save checks again despite a fresh database cache');
$type->isValid($gb);
check(EoriTransport::$curlCalls === 1, 'A repeated successful validation does not query again');
if ($method === null) {
    unset($_SERVER['REQUEST_METHOD']);
} else {
    $_SERVER['REQUEST_METHOD'] = $method;
}

echo "{$checks} checks; {$failures} failures\n";
exit($failures ? 1 : 0);
