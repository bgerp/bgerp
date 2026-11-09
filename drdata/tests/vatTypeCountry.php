<?php

/**
 * Изолирани проверки на ДДС номера според държавата и префикса (PHP 7.4 и 8.2).
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/vatTypeCountry.php - без база и без VIES.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($value) { if (!$value) throw new RuntimeException('Unexpected missing fixture'); }
class core_BaseClass {}
class core_Manager
{
    public static function fetch($id)
    {
        $countries = array(101 => 'AU', 102 => 'BG', 103 => 'GB', 104 => 'GR');
        return isset($countries[$id]) ? (object) array('letterCode2' => $countries[$id]) : false;
    }
    public static function fetchField($id, $field) { return self::fetch($id)->{$field} ?? null; }
}
class cls { public static function get($class) { return $class === 'drdata_Vats' ? new TestVats() : new $class(); } }
class arr
{
    public static function make($value, $keys = false)
    {
        $values = explode(',', $value);
        return $keys ? array_combine($values, $values) : $values;
    }
}
class dt { public static function now($full = true) { return '2026-01-01'; } }
class Mode
{
    public static $modes = array();
    public static function is($name, $value = true) { return (self::$modes[$name] ?? null) === $value; }
}
class ht
{
    public static function createElement($tag, $attr, $body)
    {
        return '<' . $tag . ' class="' . $attr['class'] . '" title="' . htmlspecialchars($attr['title'], ENT_QUOTES, 'UTF-8') . '">' . $body . '</' . $tag . '>';
    }
}
class doc_plg_LlmExportable { public static function systemNote($text) { return '[!bgERP: ' . $text . ']'; } }

require dirname(__DIR__, 2) . '/core/Type.class.php';
require dirname(__DIR__, 2) . '/type/Varchar.class.php';
require dirname(__DIR__) . '/Countries.class.php';
require dirname(__DIR__) . '/Vats.class.php';
require dirname(__DIR__) . '/VatType.class.php';

class TestVats extends drdata_Vats
{
    public static $calls = array();
    public function check($vat, $force = false)
    {
        $vat = $this->canonize($vat);
        self::$calls[] = array($vat, $force);
        $statuses = array('DE123456789' => 'invalid', 'XI123456789' => 'valid', 'EL12' => 'syntax',
            'GB123456789' => 'unknown', 'NO123456789' => 'unknown', '123456789' => 'bulstat');
        return array($statuses[$vat] ?? 'not_vat', null);
    }
}

$Type = new drdata_VatType(array('countryId' => 101));
$number = '12345678901';
$warning = $Type->statuses['unsupported'][0];
$result = $Type->isValid($number);
check(($result['warning'] ?? null) === $warning && empty($result['error']), 'A non-EU local number produces an ignorable warning');
check($result['value'] === $number, 'The number receives no added country prefix');
$html = $Type->toVerbal_($number);
check(strpos($html, 'class="quiet"') !== false && strpos($html, $warning) !== false, 'The saved number has a neutral colour and the same explanation');
check(TestVats::$calls === array(), 'An unsupported number never enters the shared VAT registry or VIES check');
check(($Type->isValid('AU12345678901')['warning'] ?? null) === $warning, 'An unrecognised prefix is also unsupported for a non-EU country');
check(($Type->isValid('123456789')['warning'] ?? null) === $warning, 'A foreign identifier is not labelled a Bulgarian UIC');

foreach (array(102, 104, null, 999) as $countryId) {
    $Type->params['countryId'] = $countryId;
    check(($Type->isValid($number)['warning'] ?? null) === 'Липсва двубуквен префикс', 'EU, absent and nonexistent countries retain the prefix warning');
    check(strpos($Type->toVerbal_($number), 'class="red"') !== false, 'The display uses the current record country even for the same number');
}
$Type->params['countryId'] = 101;
check(strpos($Type->toVerbal_($number), 'class="quiet"') !== false, 'A cached result without country does not leak into the non-EU display');
$Type->params['countryId'] = 103;
check(($Type->isValid($number)['warning'] ?? null) === $warning, 'The country helper treats GB as outside the EU today');

$Type->params['countryId'] = 101;
foreach (array('DE123456789' => 'invalid', 'XI123456789' => 'valid', 'EL12' => 'syntax',
    'GB123456789' => 'unknown', 'NO123456789' => 'unknown') as $vat => $status) {
    $before = count(TestVats::$calls);
    $result = $Type->isValid(strtolower($vat));
    check(count(TestVats::$calls) === $before + 1 && end(TestVats::$calls) === array($vat, true), 'A known prefix keeps the forced registry check regardless of address country');
    $expectedWarning = $Type->statuses[$status][1] === 'red' ? $Type->statuses[$status][0] : null;
    check(($result['warning'] ?? null) === $expectedWarning && $result['value'] === $vat, 'The existing validation result and normalisation are preserved');
    check(strpos($Type->toVerbal_($vat), 'class="' . $Type->statuses[$status][1] . '"') !== false, 'The existing display status is preserved');
}

Mode::$modes = array('renderForLlm' => true, 'text' => 'plain');
check(strpos($Type->toVerbal_($number), 'Tax number not confirmed: automatic validation is not supported') !== false, 'LLM export does not call an unsupported number malformed or valid');
foreach (array(array('text' => 'plain'), array('pdf' => true), array('printing' => true)) as $mode) {
    Mode::$modes = $mode;
    check($Type->toVerbal_($number) === $number, 'Printed and ordinary text exports retain the original number');
}
Mode::$modes = array();
check($Type->isValid(null) === null && $Type->toVerbal_(null) === null, 'Empty optional values remain empty');
check(!empty($Type->isValid(str_repeat('1', 19))['error']), 'Unsupported validation still enforces the field length');

echo "OK: {$checks} VAT country checks\n";
