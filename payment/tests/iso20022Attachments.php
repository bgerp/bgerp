<?php
/**
 * Самостоятелни проверки: php payment/tests/iso20022Attachments.php
 * Използва временни синтетични файлове; не зарежда приложение или база данни.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

class core_BaseClass {}
class core_App {
    public static function setTimeLimit($seconds) {}
}
class log_System {
    public static function add($class, $message, $id = null, $type = null, $life = null) { fileman_Files::$logs[] = $message; }
}
class fileman_Exception extends Exception {}
class email_Spam {
    public static function getSpamScore($headers, $force, $mime) { return 0; }
}
class keylist {
    public static function toArray($value) { return $value; }
}
class fileman_Files {
    public static $files = array();
    public static $logs = array();
    public static function fetchByFh($fh, $field) { return self::$files[$fh][$field] ?? null; }
    public static function fetchField($id, $field) { return $id; }
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
    public static function logWarning($msg, $id) { self::$logs[] = $msg; }
    public static function logNotice($msg, $id) { self::$logs[] = $msg; }
}
class fileman extends fileman_Files {}
class fileman_webdrv_Generic {
    public static $passwords = array();
    public static function getPassword($fh) { return self::$passwords[$fh] ?? null; }
}
class bank_Accounts {
    public static function fetch($where) { return (object) array('id' => 1, 'currencyId' => 1); }
}
class bank_OwnAccounts {
    public static function fetch($where) { return (object) array('id' => 2); }
    public static function getDefaultCurrency($id, $date, $code) { return 'EUR'; }
}
class bank_Register {
    public static $imports = array();
    public static $batches = array();
    public static function importRecs($recs) { self::$batches[] = $recs; self::$imports = array_merge(self::$imports, $recs); return 'test'; }
    public static function findMatches() { return 0; }
}
require dirname(__DIR__) . '/ImportFiles.class.php';
require dirname(__DIR__) . '/ParserIso20022.class.php';
class attachment_TestParser extends payment_ParserIso20022 {
    public static function read($fh, $password = null) { return parent::getAttachmentContents($fh, $password); }
}
class attachment_TestMime {
    private $files;
    public function __construct($files) { $this->files = $files; }
    public function saveFiles() {}
    public function getFiles() { return $this->files; }
}

function check($condition, $name) {
    if (!$condition) { fwrite(STDERR, "FAIL $name\n"); exit(1); }
    echo "PASS $name\n";
}
function rejected($fh, $password = null) {
    try { attachment_TestParser::read($fh, $password); } catch (fileman_Exception $e) { return true; }
    return false;
}
function fixture($name, $contents) {
    global $dir;
    $path = $dir . '/' . $name;
    file_put_contents($path, $contents);
    fileman_Files::$files[$name] = array('path' => $path, 'name' => $name);
    return $name;
}
function archiveFixture($name, $entries, $password = null) {
    global $dir;
    $path = $dir . '/' . $name;
    $zip = new ZipArchive();
    check($zip->open($path, ZipArchive::CREATE) === true, 'create ZIP fixture');
    foreach ($entries as $entry => $contents) {
        $zip->addFromString($entry, $contents);
        if ($password !== null) check($zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $password), 'encrypt ZIP fixture');
    }
    $zip->close();
    fileman_Files::$files[$name] = array('path' => $path, 'name' => $name);
    return $name;
}

if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
    fwrite(STDERR, "Requires PHP zip and SimpleXML\n"); exit(1);
}
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$dir = sys_get_temp_dir() . '/camt-attachments-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
$xml = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02"><BkToCstmrStmt><Stmt><Acct><Id><IBAN>BG00TEST00000000000000</IBAN></Id><Ccy>EUR</Ccy></Acct><Ntry><Amt Ccy="EUR">123.45</Amt><CdtDbtInd>CRDT</CdtDbtInd><ValDt><Dt>2026-10-26</Dt></ValDt><NtryDtls><TxDtls><RltdPties><Dbtr><Nm>Test sender</Nm></Dbtr><DbtrAcct><Id><IBAN>BG00TEST11111111111111</IBAN></Id></DbtrAcct></RltdPties><AddtlTxInf>Test invoice 12345</AddtlTxInf></TxDtls></NtryDtls></Ntry></Stmt></BkToCstmrStmt></Document>';
$parsed = payment_ParserIso20022::getRecs($xml);
check(count($parsed->recs ?? array()) === 1 && $parsed->recs[0]->amount == 123.45, 'ordinary camt.053 unchanged');
$xml52 = str_replace(array('camt.053', 'BkToCstmrStmt', '<Stmt>', '</Stmt>'), array('camt.052', 'BkToCstmrAcctRpt', '<Rpt>', '</Rpt>'), $xml);
check(count(payment_ParserIso20022::getRecs($xml52)->recs ?? array()) === 1, 'camt.052');
$prefixed = preg_replace('/<(\/?)([A-Za-z][A-Za-z0-9]*)(?=[ >\/])/', '<$1n:$2', str_replace('xmlns=', 'xmlns:n=', $xml));
check(count(payment_ParserIso20022::getRecs($prefixed)->recs ?? array()) === 1, 'prefixed CAMT namespace');
$longHeader = "\xEF\xBB\xBF" . str_replace('?><Document', '?><!--' . str_repeat('header ', 40) . '--><Document', $xml);
check(count(payment_ParserIso20022::getRecs($longHeader)->recs ?? array()) === 1, 'BOM and long header');
check(payment_ParserIso20022::getRecs('<other/>') === null, 'unrelated XML ignored');
check(!empty(payment_ParserIso20022::getRecs('<broken>')->warnings), 'invalid XML reported');
check(!empty(payment_ParserIso20022::getRecs('<!DOCTYPE Document [<!ENTITY test "value">]>' . $xml)->errors), 'DTD rejected');
$plain = fixture('statement.camt', $longHeader);
check(attachment_TestParser::read($plain) === array($longHeader), 'plain CAMT read');
$zip = archiveFixture('statements.ZIP', array('a.xml' => $xml, 'nested/b.camt' => $xml52));
check(attachment_TestParser::read($zip) === array($xml, $xml52), 'multiple XML and CAMT entries');
$secret = "qa'\"\\\$()&;[#INPUTF#]";
$encrypted = archiveFixture('encrypted.zip', array('statement.xml' => $xml), $secret);
check(rejected($encrypted) && rejected($encrypted, 'wrong'), 'missing and wrong passwords rejected');
check(attachment_TestParser::read($encrypted, $secret) === array($xml), 'special characters in rule password');
$zero = archiveFixture('zero.zip', array('statement.camt' => $xml), '0');
check(attachment_TestParser::read($zero, '0') === array($xml), 'rule password zero');
fileman_webdrv_Generic::$passwords[$zero] = '0';
check(attachment_TestParser::read($zero, 'wrong') === array($xml), 'file password zero takes precedence');
$maskedExt = fixture('archive.dat', file_get_contents(fileman_Files::$files[$encrypted]['path']));
check(attachment_TestParser::read($maskedExt, $secret) === array($xml), 'ZIP signature without ZIP extension');
check(rejected(fixture('broken.zip', 'invalid')), 'corrupt ZIP reported');
$traversal = archiveFixture('paths.zip', array('../../outside.camt' => $xml));
check(attachment_TestParser::read($traversal) === array($xml) && !file_exists($dir . '/outside.camt'), 'entry paths are never extracted');
check(rejected(archiveFixture('many.zip', array_fill_keys(range(0, 100), 'test'))), 'archive entry limit');
check(rejected(archiveFixture('large.zip', array('large.camt' => str_repeat('x', payment_ParserIso20022::MAX_XML_BYTES + 1)))), 'uncompressed size limit');

$parser = new attachment_TestParser();
$service = (object) array('archivePassword' => $secret);
check($parser->process(new attachment_TestMime(array($plain)), $service) === 'ISO20022', 'email with CAMT and long header');
check($parser->process(new attachment_TestMime(array($encrypted)), $service) === 'ISO20022', 'email with encrypted ZIP');
$service->archivePassword = 'wrong';
check($parser->process(new attachment_TestMime(array($plain, $encrypted)), $service) === false, 'partial failure keeps email in normal flow');
check(strpos(implode("\n", fileman_Files::$logs), $secret) === false, 'password absent from logs');
check($parser->process(new attachment_TestMime(array(fixture('other.pdf', '%PDF test'))), $service) === false, 'non-CAMT attachment ignored');

class type_Varchar {
    public $params = array();
    public $maxFieldSize;
    public function renderInput_($name, $value = '', &$attr = array()) { return array('value' => $value, 'attr' => $attr); }
    public static function escape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
class core_Setup {
    public static function get($name) { return 'yes'; }
}
require dirname(__DIR__, 2) . '/type/Password.class.php';
$passwordType = new type_Password();
$input = $passwordType->renderInput_('archivePassword', '0');
check($input['value'] === type_Password::EF_PASS_NO_CHANGE && $input['attr']['type'] === 'password', 'zero password renders as unchanged');
check($passwordType->toVerbal('0') === '********' && $passwordType->fromVerbal('0') === '0', 'zero password is masked and accepted');
check($passwordType->fromVerbal(type_Password::EF_PASS_NO_CHANGE) === null && $passwordType->fromVerbal('') === '', 'unchanged and cleared passwords distinguished');
echo "All checks passed\n";
