<?php

/**
 * Изолирани проверки на опресняването на кеша на ДДС номерата при недостъпен VIES (PHP 7.4 и 8.2).
 *
 * @category bgerp
 * @package drdata
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php drdata/tests/vatsRefresh.php - без база и без VIES; на 8.2 депрекациите също са грешка.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class core_Manager {}
class cls { public static function get($class) { return new $class(); } }
class drdata_Setup { public static function get($name) { return 60 * 86400; } }
class dt
{
    const NOW = '2026-10-30 12:00:00';
    public static function verbal2mysql() { return self::NOW; }
    public static function addSecs($secs) { return date('Y-m-d H:i:s', strtotime(self::NOW) + $secs); }
}

require dirname(__DIR__) . '/Vats.class.php';

class TestVats extends drdata_Vats
{
    public $answer;
    public $saved = array();
    public function checkStatus($vat) { return $this->answer; }
    public function save(&$rec, $fields = null, $mode = null) { $this->saved[] = array(clone $rec, $fields); }
}

// Връща записа след опресняване с даден отговор на VIES
function refresh($status, $info, $answer)
{
    $Vats = new TestVats();
    $Vats->answer = $answer;
    $Vats->updateOnShutdown[] = (object) array('vat' => 'BG207975633', 'status' => $status, 'info' => $info, 'lastChecked' => '2026-08-01 00:00:00');
    drdata_Vats::on_Shutdown($Vats);
    check(countR($Vats->saved) === 1 && $Vats->saved[0][1] === 'status, info, lastChecked', 'Each refreshed number is saved once with its check fields');

    return $Vats->saved[0][0];
}
function countR($value) { return is_array($value) ? count($value) : 0; }

$retry = date('Y-m-d H:i:s', strtotime(dt::NOW) - 60 * 86400 + 86400);

$rec = refresh('valid', "EXTRAPACK AD\nVeliko Tarnovo", array('unknown', null));
check($rec->status === 'valid' && $rec->info === "EXTRAPACK AD\nVeliko Tarnovo", 'An unavailable VIES keeps a valid number valid with its VIES data');
check($rec->lastChecked === $retry, 'The kept result is checked again after a day, like an unknown one');

$rec = refresh('invalid', null, array('unknown', null));
check($rec->status === 'invalid' && $rec->lastChecked === $retry, 'An unavailable VIES keeps an invalid number invalid');

$rec = refresh('unknown', null, array('unknown', null));
check($rec->status === 'unknown' && $rec->lastChecked === dt::NOW, 'A number never confirmed stays unknown');

$rec = refresh('valid', 'old', array('invalid', null));
check($rec->status === 'invalid' && $rec->info === null && $rec->lastChecked === dt::NOW, 'A VIES answer replaces the cached result');

$rec = refresh('invalid', null, array('valid', 'new'));
check($rec->status === 'valid' && $rec->info === 'new' && $rec->lastChecked === dt::NOW, 'A number that became valid is saved as valid');

echo "OK: {$checks} VAT cache refresh checks\n";
