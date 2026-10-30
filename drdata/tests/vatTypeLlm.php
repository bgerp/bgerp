<?php

/**
 * Изолирани проверки на статуса на ДДС номера в LLM експорта (PHP 7.4 и 8.2).
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/vatTypeLlm.php - без база и без VIES; на 8.2 депрекациите също са грешка.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class cls { public static function get($class) { return new $class(); } }
class Mode
{
    public static $modes = array();
    public static function is($name, $value = true) { return (self::$modes[$name] ?? null) === $value; }
}
class type_Varchar
{
    public $params = array();
    public function init($params = array()) { $this->params = $params; }
    public static function escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}
class doc_plg_LlmExportable { public static function systemNote($text) { return '[!bgERP: ' . trim((string) $text) . ']'; } }
class ht { public static function createElement($tag, $attr, $body) { return "<{$tag} class=\"{$attr['class']}\">{$body}</{$tag}>"; } }
class drdata_Vats
{
    public static $calls = 0;
    public static $statuses = array('BG207975633' => 'valid', 'BG999999999' => 'invalid', 'GB123456789' => 'unknown',
        'BG12' => 'syntax', '207975633' => 'bulstat', '91310000MA1FL' => 'not_vat', 'BG000000000' => '');
    public function check($vat) { self::$calls++; return array(self::$statuses[$vat], null); }
}

require dirname(__DIR__) . '/VatType.class.php';

$Type = cls::get('drdata_VatType');

Mode::$modes = array('text' => 'plain', 'renderForLlm' => true);
check($Type->toVerbal_('BG207975633') === 'BG207975633', 'A valid number gets no note for the model');
check($Type->toVerbal_('BG999999999') === 'BG999999999 [!bgERP: Invalid VAT number]', 'An invalid number is marked invalid');
check($Type->toVerbal_('GB123456789') === 'GB123456789 [!bgERP: VAT number not confirmed]', 'A number VIES could not check is marked not confirmed');
check($Type->toVerbal_('BG000000000') === 'BG000000000 [!bgERP: VAT number not confirmed]', 'A failed check is marked not confirmed');
check($Type->toVerbal_('91310000MA1FL') === '91310000MA1FL [!bgERP: VAT number not confirmed: no EU prefix]', 'A number without an EU prefix cannot be confirmed');
check($Type->toVerbal_('BG12') === 'BG12 [!bgERP: Malformed VAT number]', 'A wrong format is marked malformed');
check($Type->toVerbal_('207975633') === '207975633 [!bgERP: Malformed VAT number: UIC without the BG prefix]', 'A UIC in the VAT field is marked malformed');

$calls = drdata_Vats::$calls;
$Type->toVerbal_('BG999999999');
check(drdata_Vats::$calls === $calls, 'The status is checked once per number in a request');

Mode::$modes = array('text' => 'plain');
check($Type->toVerbal_('BG999999999') === 'BG999999999', 'Plain text outside the LLM export stays without a note');

Mode::$modes = array();
check($Type->toVerbal_('BG999999999') === '<span class="red">BG999999999</span>', 'The screen keeps the coloured number');

echo "OK: {$checks} VAT type LLM export checks\n";
