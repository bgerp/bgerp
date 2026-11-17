<?php

/**
 * Изолирани проверки на фирмените идентификатори при поръчка (PHP 7.4 и 8.2).
 *
 * @category bgerp
 * @package eshop
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php eshop/tests/invoiceCompany.php - без база, външни регистри и AI заявки.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

class core_Manager {}
class core_Master {}
class drdata_Countries
{
    /** Фиксирана тестова държава. */
    public static function getIdByName($name) { return 1; }
}
class drdata_VatRegister
{
    public static $actual = true, $reads = 0, $state = 'registered';
    /** Синтетична актуалност на регистъра. */
    public static function isActual() { return self::$actual; }
    /** Синтетична регистрирана фирма. */
    public static function fetch($query) { self::$reads++; return (object) array('name'=>'QA Company', 'state'=>self::$state); }
    /** Сравнява имена без външни зависимости. */
    public static function isSameCompany($name, $registered) { return $name === $registered; }
    /** Запазва фиксираното име от регистъра. */
    public static function getInvoiceName($name) { return $name; }
}
class cls
{
    /** Използва реалната нормализация на ДДС номера. */
    public static function get($class) { return $class === 'eshop_Carts' ? new InvoiceCartFixture() : new $class(); }
}
class InvoiceFormFixture
{
    public $rec, $errors = array(), $warnings = array();
    /** Събира грешките вместо HTML форма. */
    public function setError($fields, $message) { $this->errors[$fields] = $message; }
    /** Събира предупрежденията вместо HTML форма. */
    public function setWarning($fields, $message) { $this->warnings[$fields] = $message; }
}

require dirname(__DIR__, 2) . '/drdata/Vats.class.php';
require dirname(__DIR__) . '/Carts.class.php';

class InvoiceCartFixture extends eshop_Carts
{
    public static $calls = 0, $decision = 'warning';
    /** Симулира незадължителния плъгин за различно изписване на името. */
    public function resolveVatNameMismatch($name, $registered, $vat, $uic = null) { self::$calls++; return self::$decision; }
}

$checks = 0;
/** Прекратява теста при неочакван резултат. */
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }

/** Извиква реалната проверка на поръчката със синтетична форма. */
function invoiceForm($values = array())
{
    $form = new InvoiceFormFixture();
    $form->rec = (object) ($values + array('makeInvoice'=>'company', 'invoiceCountry'=>1,
        'invoiceNames'=>'QA Company', 'invoiceVatNo'=>'BG123456786', 'invoiceUicNo'=>'123456786'));
    drdata_VatRegister::$reads = InvoiceCartFixture::$calls = 0;
    $method = new ReflectionMethod('eshop_Carts', 'checkInvoiceCompany');
    $method->setAccessible(true);
    $method->invoke(null, $form);

    return $form;
}

// Синтетичните ЕИК са с валидни контролни цифри, без зависимост от реални записи.
foreach (array('123456786', '987654322', '1234567860001', '9876543220004') as $uic) {
    check(drdata_Vats::isBulstat($uic), 'Valid synthetic UIC: ' . $uic);
}
foreach (array(true, false) as $actual) {
    drdata_VatRegister::$actual = $actual;
    foreach (array('987654322', '9876543220004') as $uic) {
        $form = invoiceForm(array('invoiceUicNo'=>$uic));
        check(isset($form->errors['invoiceVatNo,invoiceUicNo']), 'Different UIC is rejected regardless of registry freshness');
        check(drdata_VatRegister::$reads === 0 && InvoiceCartFixture::$calls === 0, 'Identifier mismatch needs neither registry lookup nor AI');
        check($form->rec->invoiceUicNo === $uic && $form->rec->invoiceNames === 'QA Company', 'Rejected data are not silently replaced');
    }
}
drdata_VatRegister::$actual = true;
foreach (array(
    array(), array('invoiceVatNo'=>' bg 123-456-786 '),
    array('invoiceUicNo'=>'1234567860001'),
    array('invoiceVatNo'=>'BG1234567860001'),
    array('invoiceVatNo'=>'BG1234567860001', 'invoiceUicNo'=>'1234567860001'),
    array('invoiceUicNo'=>null), array('invoiceUicNo'=>''),
    array('invoiceVatNo'=>'BG012345673', 'invoiceUicNo'=>'012345673'),
    array('invoiceVatNo'=>'BG1234567890'), array('invoiceUicNo'=>'1234567890'),
    array('makeInvoice'=>'person', 'invoiceUicNo'=>'987654322'),
    array('makeInvoice'=>'none', 'invoiceUicNo'=>'987654322'),
    array('invoiceCountry'=>2, 'invoiceUicNo'=>'987654322'),
    array('invoiceVatNo'=>'DE123456789', 'invoiceUicNo'=>'987654322')
) as $values) {
    $form = invoiceForm($values);
    check(!$form->errors && !$form->warnings, 'Existing accepted form remains accepted: ' . json_encode($values));
    check(InvoiceCartFixture::$calls === 0, 'Exact name does not need AI');
}
$form = invoiceForm(array('invoiceNames'=>'QA Typo', 'invoiceUicNo'=>'987654322'));
check($form->errors && InvoiceCartFixture::$calls === 0, 'AI cannot override inconsistent identifiers');
$form = invoiceForm(array('invoiceNames'=>'QA Typo'));
check(!$form->errors && $form->warnings && $form->rec->invoiceNames === 'QA Company', 'Name correction with matching identifiers remains available');
foreach (array(null, 'error') as $decision) {
    InvoiceCartFixture::$decision = $decision;
    $form = invoiceForm(array('invoiceNames'=>'Other company'));
    check((bool) $form->errors, 'Unavailable AI or another company still refuses the name');
}
drdata_VatRegister::$state = 'deregistered';
check((bool) invoiceForm()->errors, 'Deregistered VAT remains rejected');
drdata_VatRegister::$actual = false;
check(!invoiceForm()->errors && drdata_VatRegister::$reads === 0, 'Stale registry still skips name verification for matching identifiers');

echo "OK: {$checks} invoice company checks\n";
