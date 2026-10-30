<?php

namespace ViesTest;

use ErrorException;
use Exception;
use stdClass;
use Throwable;

/**
 * Изолирани проверки на VIES входа и отговорите, без мрежа и база.
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/vies.php [път до Vats.class.php]
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class core_Manager
{
    public static $warnings = array();
    public static function logWarning($message) { self::$warnings[] = $message; }
}
class arr
{
    public static function make($value, $assoc = false)
    {
        $values = explode(',', $value);
        return $assoc ? array_combine($values, $values) : $values;
    }
}
class core_exception_Redirect extends Exception {}
class SoapFault extends Exception
{
    public $faultstring;
    public function __construct($message) { $this->faultstring = $message; parent::__construct($message); }
}
class SoapClient
{
    public static $response, $params, $options, $wsdl, $reports = array(), $calls = 0;
    public function __construct($wsdl, $options)
    {
        self::$calls++;
        self::$wsdl = $wsdl;
        self::$options = $options;
    }
    public function checkVat($params)
    {
        self::$params = $params;
        if (self::$response instanceof Throwable) throw self::$response;
        return self::$response;
    }
}
function reportException($e) { SoapClient::$reports[] = $e; }

// Същият код с изолиран SOAP клиент, както в тестовете за EORI.
$source = file_get_contents($argv[1] ?? dirname(__DIR__) . '/Vats.class.php');
eval('namespace ViesTest; use Exception; use Throwable; use stdClass;' . substr($source, 5));
$checks = $failures = 0;
function check($ok, $message)
{
    global $checks, $failures;
    $checks++;
    if (!$ok) { $failures++; echo "FAIL: {$message}\n"; }
}
function runCase($vat, $response, $status, $expectedVat = null)
{
    SoapClient::$response = $response;
    SoapClient::$calls = 0;
    SoapClient::$params = null;
    SoapClient::$reports = core_Manager::$warnings = array();
    $result = (new drdata_Vats())->checkStatus($vat);
    check($result[0] === $status && count($result) === 4, (string) $vat . ': status and return contract');
    check(SoapClient::$calls === ($expectedVat === null ? 0 : 1), (string) $vat . ': request count');
    if ($expectedVat !== null) {
        check(SoapClient::$params === array('countryCode' => substr($expectedVat, 0, 2), 'vatNumber' => substr($expectedVat, 2)), (string) $vat . ': SOAP parameters');
    }
    return $result;
}

$valid = (object) array('valid' => true, 'name' => 'Example', 'address' => 'Example address');
$numbers = array('ATU00000000', 'BE0000000000', 'BG000000000', 'CY00000000A', 'CZ00000000',
    'DE000000000', 'DK00000000', 'EE000000000', 'EL000000000', 'ESX0000000A', 'FI00000000',
    'FR00000000000', 'HR00000000000', 'HU00000000', 'IE0000000A', 'IT00000000000', 'LT000000000',
    'LU00000000', 'LV00000000000', 'MT00000000', 'NL000000000B00', 'PL0000000000', 'PT000000000',
    'RO00', 'SE000000000000', 'SI00000000', 'SK0000000000', 'XI000000000');
foreach ($numbers as $vat) {
    $result = runCase($vat, $valid, 'valid', $vat);
    check($result[1] === "Example\nExample address" && $result[2] === 'Example' && $result[3] === 'Example address', $vat . ': company details');
}
check(SoapClient::$wsdl === 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl'
    && SoapClient::$options === array('connection_timeout' => 4, 'location' => 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService'), 'Preserve HTTPS and connection timeout');

runCase(" \tbg 000 000 000\n", $valid, 'valid', 'BG000000000');
runCase('ie0+00000a', $valid, 'valid', 'IE0+00000A');
runCase('ie0*00000a', $valid, 'valid', 'IE0*00000A');
runCase('BG0000000000000', $valid, 'valid', 'BG000000000');
$result = runCase('FR000000000000', $valid, 'valid', 'FR000000000000');
check(strpos($result[1] ?? '', 'локални модели') !== false, 'VIES remains authoritative for plausible new national formats');

foreach (array('GB123456789', 'NO000000000', ' gb123456789 ') as $vat) {
    $result = runCase($vat, $valid, 'unknown');
    check(strpos($result[1] ?? '', 'VIES не поддържа') !== false && !core_Manager::$warnings, $vat . ': explain unsupported registry without service warnings');
    check(drdata_Vats::isHaveVatPrefix($vat), $vat . ': retain VAT prefix recognition');
}
runCase(null, $valid, 'not_vat');
runCase('BG', $valid, 'syntax');
runCase('BG1', $valid, 'syntax');
runCase('BG000000000', (object) array('valid' => false), 'invalid', 'BG000000000');
foreach (array(null, new stdClass(), (object) array('valid' => 'false')) as $response) {
    runCase('BG000000000', $response, 'unknown', 'BG000000000');
}
runCase('BG000000000', (object) array('valid' => true), 'valid', 'BG000000000');

foreach (array('INVALID_INPUT', 'MS_MAX_CONCURRENT_REQ', 'GLOBAL_MAX_CONCURRENT_REQ', 'MS_UNAVAILABLE',
    'SERVICE_UNAVAILABLE', 'TIMEOUT', 'IP_BLOCKED', 'VAT_BLOCKED') as $fault) {
    runCase('bg000000000', new SoapFault($fault), 'unknown', 'BG000000000');
    check(count(core_Manager::$warnings) === 1 && strpos(core_Manager::$warnings[0], 'BG000000000') !== false
        && strpos(core_Manager::$warnings[0], $fault) !== false && !SoapClient::$reports, $fault . ': diagnostic number and warning without exception report');
}
runCase('BG000000000', new SoapFault('UNEXPECTED_FAULT'), 'unknown', 'BG000000000');
check(count(SoapClient::$reports) === 1 && !core_Manager::$warnings, 'Unexpected faults are still reported');
SoapClient::$response = new core_exception_Redirect('test redirect');
try {
    (new drdata_Vats())->checkStatus('BG000000000');
    check(false, 'Redirect propagates');
} catch (core_exception_Redirect $e) {
    check(true, 'Redirect propagates');
}

echo "{$checks} checks; {$failures} failures\n";
exit($failures ? 1 : 0);
