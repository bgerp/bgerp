<?php
/**
 * Самостоятелни vCard/vCalendar регресии без база и мрежа.
 *
 * @category  bgerp
 * @package   pear
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) throw new ErrorException($message, 0, $level, $file, $line);
    return false;
});
$version = $argv[1] ?? 'legacy';
/** Проверка без framework. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Брои масивите в адаптера. */
function countR($value) { return is_array($value) ? count($value) : 0; }
/** Изолира пакетните defaults. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
class core_ProtoSetup
{
    /** Избор само за текущия CLI процес. */
    public static function get($key) { return $GLOBALS['version']; }
}
class arr
{
    /** Минимална реализация за списъците от vCard типове. */
    public static function make($value, $assoc = false)
    {
        $value = is_array($value) ? $value : array_filter(array_map('trim', explode(',', $value ?? '')));
        return $assoc && $value ? array_combine($value, $value) : $value;
    }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Vcard.class.php';
expect(in_array($version, array('legacy', '0.6.1'), true));
expect(PEAR_FILE_IMC_VERSION === 'legacy', 'Default changed');
$checks = 0;
$check = function ($value, $message) use (&$checks) { expect($value, $message); $checks++; };
$c = pear_Vcard::createEmpty();
$c->setFormattedName('QA Кирилица Тест');
$c->setName(array('given' => 'Кирилица', 'surname' => 'Тест'));
$c->setBday('2000-01-02');
$c->setOrganisation(array('Тестова фирма', 'Отдел'));
$c->addTel('+35920000000', array('TYPE' => array('WORK', 'VOICE')));
$c->addTel('00123', array('TYPE' => 'CELL'));
$c->addEmail('test@example.invalid', array('TYPE' => 'WORK'));
$c->addAddress(array('street' => 'Тестова 1', 'locality' => 'София', 'country' => 'BG', 'code' => '00123', 'pobox' => '42', 'ext' => 'Етаж 2'), array('TYPE' => 'HOME'));
$c->addAddressLabel(null, array('TYPE' => 'WORK'));
$c->setNote("Кирилица; запетая, апостроф ' и C:\\temp\nВтори ред");
$text = (string) $c;
$cards = pear_Vcard::parseString($text);
$check(count($cards) === 1, 'One card');
$p = $cards[0];
$check($p->getVersion() === '3.0', 'Version');
$check($p->getFormattedName() === 'QA Кирилица Тест', 'Cyrillic name');
$check($p->getName('surname') === 'Тест', 'Structured name');
$check($p->getBday() === '2000-01-02', 'Birthday');
$check($p->getTel('work') === array($version === 'legacy' ? '00123' : '+35920000000'), 'Typed phone (legacy overwrites the previous phone)');
$check($p->getTel('cell') === array('00123'), 'Text phone zeros');
$check($p->getEmails()['work'] === array('test@example.invalid'), 'Email');
$check($p->getAddress('locality', 'home') === array('София'), 'Address');
$check($p->getAddress('code', 'home') === array('00123') && $p->getAddress('pobox', 'home') === array('42') && $p->getAddress('ext', 'home') === array('Етаж 2'), 'Public address part names survive export');
$check($p->getOrganisation(true) === array('Тестова фирма', 'Отдел'), 'Organisation');
$check(count(pear_Vcard::parseString($text . "\n" . $text)) === 2, 'Multiple cards');
$lower = preg_replace_callback('/^(BEGIN|END|VERSION|FN|N):/m', function ($m) { return strtolower($m[1]) . ':'; }, str_replace('VCARD', 'vcard', $text));
$check(pear_Vcard::parseString($lower)[0]->getFormattedName() === 'QA Кирилица Тест', 'Local lowercase compatibility');
$check(pear_Vcard::parseString('') === array(), 'Empty input');
$check(pear_Vcard::parseString('not a vCard') === array(), 'Invalid input');
$untyped = "BEGIN:VCARD\nVERSION:3.0\nFN:QA\nN:Test;QA;;;\nADR:;;Street;Town;;;BG\nEND:VCARD";
$check(pear_Vcard::parseString($untyped)[0]->getAddress(null, 'work') === array(), 'Typed address skips untyped entries');
$qp = "BEGIN:VCARD\r\nVERSION:2.1\r\nFN;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:" . quoted_printable_encode('Кирилица') . "\r\nN:Test;;;;\r\nEND:VCARD";
$check(pear_Vcard::parseString($qp)[0]->getFormattedName() === 'Кирилица', 'Quoted printable vCard 2.1');
$folded = "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Кири\r\n лица\r\nN:Test;;;;\r\nPHOTO;VALUE=URL:https://example.invalid/photo.jpg\r\nEND:VCARD";
$fold = pear_Vcard::parseString($folded)[0];
$check($fold->getFormattedName() === 'Кирилица', 'Folded line');
$check($fold->getPhotoUrl() === array('https://example.invalid/photo.jpg'), 'Photo URL read without fetching');
$raw = File_IMC::parse('vCard')->fromText("begin:vcard\nversion:3.0\nfn:QA\nN:Test;;;;\nNOTE;X-REF=CaseSensitive:Text\nend:vcard");
$check($raw['VCARD'][0]['NOTE'][0]['param']['X-REF'][0] === ($version === 'legacy' ? 'CASESENSITIVE' : 'CaseSensitive'), 'Parameter values retain their case in the new version');
$file = tempnam(sys_get_temp_dir(), 'vcard-');
try {
    file_put_contents($file, $text);
    $check(pear_Vcard::parseFile($file)[0]->getFormattedName() === $p->getFormattedName(), 'File input');
} finally { unlink($file); }
$many = pear_Vcard::parseString(str_repeat($text . "\n", 1000));
$check(count($many) === 1000 && $many[999]->getFormattedName() === $p->getFormattedName(), '1000 cards');
ob_start();
pear_Vcard::httpRespond(array($c));
$check(ob_get_clean() === $text, 'HTTP export body retains all UTF-8 bytes');
$calendar = File_IMC::parse('vCalendar');
$calendar->fromText("BEGIN:VCALENDAR\nVERSION:1.0\nBEGIN:VEVENT\nDTSTART:20261029T120000Z\nDTEND:20261029T130000Z\nSUMMARY:Тест\nDESCRIPTION:Описание\nEND:VEVENT\nEND:VCALENDAR");
$events = $calendar->getEvents();
$check($calendar->getVersion() === '1.0' && count($events) === 1, 'vCalendar event count');
foreach ($events as $event) {
    $check($event->getSummary() === 'Тест' && $event->getDescription() === 'Описание', 'vCalendar Cyrillic');
    $check($event->getStart() === '20261029T120000Z' && $event->getEnd() === '20261029T130000Z', 'vCalendar timestamps');
}
class core_Master
{
    public static $saved;
    /** Прихваща само записването от реалния CRM importer. */
    public static function save($rec) { self::$saved = $rec; return 1; }
}
class fileman_Files
{
    /** Синтетичен VCF с два адреса от един тип. */
    public static function getContent($handle)
    {
        return "BEGIN:VCARD\nVERSION:3.0\nFN:QA Кирилица\nN:Тест;QA;;;\nADR;TYPE=HOME:;;Първа 1;София;;00123;Bulgaria\nADR;TYPE=HOME:;;Втора 2;Варна;;9000;Bulgaria\nTEL;TYPE=CELL:+359890000000\nEMAIL;TYPE=HOME:qa@example.com\nEND:VCARD";
    }
}
class drdata_Countries
{
    /** Заменя само речника на държавите. */
    public static function fetchField($condition, $field) { return ($condition[1] ?? '') === 'Bulgaria' ? 1 : null; }
}
require dirname(__DIR__, 2) . '/crm/Persons.class.php';
crm_Persons::import((object) array('file' => 'synthetic'));
$imported = core_Master::$saved;
$check($imported->name === 'QA Кирилица', 'CRM import name');
$check($imported->place === 'София' && $imported->address === 'Първа 1', 'CRM imports the first address of the first type');
$check($imported->pCode === '00123' && $imported->country === 1, 'CRM postcode and country');
$check($imported->mobile === '+359890000000' && $imported->email === 'qa@example.com', 'CRM phone and email');
echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'checks' => $checks), JSON_PRETTY_PRINT) . "\n";
