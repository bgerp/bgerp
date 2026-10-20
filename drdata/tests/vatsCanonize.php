<?php

/**
 * Изолирани проверки на каноничния ДДС номер при липсващ номер (PHP 7.4 и 8.2).
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/vatsCanonize.php - без база; на 8.2 депрекациите също са грешка.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class core_Manager {}
class cls { public static function get($class) { return new $class(); } }

require dirname(__DIR__) . '/Vats.class.php';

$Vats = cls::get('drdata_Vats');

// "Моята фирма" без ЕИК и ДДС номер: офертите и декларациите подават null
check($Vats->canonize(null) === '' && drdata_Vats::getUicByVatNo(null) === null, 'A missing VAT number canonizes to an empty string without a PHP 8.1+ deprecation');
check($Vats->canonize(' bg 123-456 789 ') === 'BG123456789' && drdata_Vats::getUicByVatNo('BG123456789') === '123456789', 'An existing VAT number keeps its canonical form and UIC');
check($Vats->canonize('be123456789') === 'BE0123456789', 'The BE legacy leading zero is still added');

echo "OK: {$checks} VAT canonize checks\n";
