<?php
/**
 * php payment/tests/importValidation.php
 * Самостоятелни синтетични проверки без база данни и счетоводни документи.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/iso20022Attachments.php';

foreach (array('', 'UNKNOWN', 'crdt') as $direction) {
    $result = payment_ParserIso20022::getRecs(str_replace('CRDT', $direction, $xml));
    check(empty($result->recs) && count($result->warnings) === 1, 'invalid direction skipped');
}
$result = payment_ParserIso20022::getRecs(str_replace('<CdtDbtInd>CRDT</CdtDbtInd>', '', $xml));
check(empty($result->recs) && !empty($result->warnings), 'missing direction skipped');
foreach (array('', 'text', '123.45x', '-1', '1,20', 'NaN', 'INF', '1e3', '2000000001') as $amount) {
    $result = payment_ParserIso20022::getRecs(str_replace('123.45', $amount, $xml));
    check(empty($result->recs) && count($result->warnings) === 1, 'invalid amount skipped');
}
foreach (array('0', '0.00', '12', '12.30', '+12.30', '2000000000') as $amount) {
    $result = payment_ParserIso20022::getRecs(str_replace('123.45', $amount, $xml));
    check(count($result->recs) === 1 && $result->recs[0]->amount == (float) $amount, 'valid decimal amount retained');
}
$result = payment_ParserIso20022::getRecs(str_replace('CRDT', 'DBIT', $xml));
check($result->recs[0]->type === 'outgoing', 'debit remains outgoing');
$result = payment_ParserIso20022::getRecs($xml);
check($result->recs[0]->reason === 'Test invoice 12345', 'existing reason unchanged');

$remittance = '<RmtInf><Ustrd>Invoice 0000123456</Ustrd><Ustrd>Second line</Ustrd><Ustrd>Second line</Ustrd><Strd><CdtrRefInf><Ref>RF123456</Ref></CdtrRefInf><RfrdDocInf><Nb>0000777777</Nb></RfrdDocInf><AddtlRmtInf>Additional reference</AddtlRmtInf></Strd></RmtInf>';
$withReason = str_replace('</TxDtls>', $remittance . '</TxDtls>', $xml);
$result = payment_ParserIso20022::getRecs($withReason);
check($result->recs[0]->reason === 'Test invoice 12345 Invoice 0000123456 Second line RF123456 0000777777 Additional reference', 'remittance lines and structured references preserve leading zeros');
$prefixedReason = preg_replace('/<(\/?)([A-Za-z][A-Za-z0-9]*)(?=[ >\/])/', '<$1n:$2', str_replace('xmlns=', 'xmlns:n=', $withReason));
check(payment_ParserIso20022::getRecs($prefixedReason)->recs[0]->reason === $result->recs[0]->reason, 'prefixed namespace remittance');
$onlyRemittance = str_replace('<AddtlTxInf>Test invoice 12345</AddtlTxInf>', '', $withReason);
check(strpos(payment_ParserIso20022::getRecs($onlyRemittance)->recs[0]->reason, 'Invoice 0000123456') === 0, 'remittance without additional transaction text');

class currency_Currencies {
    public static function getCodeById($id) { return 'EUR'; }
}
require dirname(__DIR__) . '/ParserUC.class.php';
$uc = '<?xml version="1.0"?><APAccounts><ArrayOfAPAccounts><APAccount><BankClient><NAME>Test owner</NAME></BankClient><BankAccount><IBAN>BG00TEST00000000000000</IBAN><BIC-Code>TESTBIC</BIC-Code><CCY><SWIFTCode>EUR</SWIFTCode></CCY><BankAccountMovements><ArrayOfBankAccountMovements><BankAccountMovement><ValDate>2026-10-26T00:00:00</ValDate><MovementAmount>27.56</MovementAmount><MovementType>2</MovementType><PayeeIBAN>BG00TEST11111111111111</PayeeIBAN><PayeeName>Legacy test sender</PayeeName><Reason>Legacy invoice</Reason><Reason2>12345</Reason2><Narrative>Test</Narrative><NarrativeI02>Entry</NarrativeI02></BankAccountMovement></ArrayOfBankAccountMovements></BankAccountMovements></BankAccount></APAccount></ArrayOfAPAccounts></APAccounts>';
$ucFile = fixture('legacy.xml', $uc);
$result = payment_ImportFiles::getRecs($ucFile);
check($result->status === 'success' && count($result->recs) === 1 && $result->recs[0]->amount == 27.56, 'legacy Unicredit XML retained');
check($result->recs[0]->serviceId === 'Import Unicredit' && $result->recs[0]->type === 'incoming', 'legacy service and direction unchanged');
check(payment_ImportFiles::getRecs($ucFile, null, 'EMAIL', false)->status === 'unsupported', 'ISO email rule retains its format scope');
$longUC = fixture('legacy-long.xml', str_replace('?><APAccounts', '?><!--' . str_repeat('header ', 50) . '--><APAccounts', $uc));
check(payment_ImportFiles::getRecs($longUC)->status === 'success', 'long Unicredit header');
check(payment_ImportFiles::getRecs(fixture('bad-legacy.xml', '<APAccounts>'))->status === 'error', 'invalid legacy XML produces controlled error');

$bundle = archiveFixture('mixed-bank.zip', array('statement.xml' => $longHeader, 'legacy.xml' => $uc, 'readme.txt' => 'Not a bank statement'), $secret);
$result = payment_ImportFiles::getRecs($bundle, $secret);
check($result->status === 'success' && count($result->recs) === 2 && count($result->batches) === 2, 'manual mixed ZIP imports recognized files only');
$wrong = payment_ImportFiles::getRecs($bundle, 'wrong');
check($wrong->status === 'error' && $wrong->passwordError && empty($wrong->recs), 'wrong password has a distinct result');
$corrupt = payment_ImportFiles::getRecs(fixture('corrupt.zip', 'Not a ZIP'));
check($corrupt->status === 'error' && !$corrupt->passwordError, 'corrupt ZIP distinguished from password error');
check(payment_ImportFiles::getRecs(fixture('other.xml', '<Other/>'))->status === 'unsupported', 'unrelated XML distinguished');
check(payment_ImportFiles::getRecs(fixture('statement.dat', str_repeat(' ', 600) . $xml))->status === 'success', 'CAMT without standard extension and with long whitespace prefix');
$largePdf = fixture('large.pdf', '%PDF' . str_repeat('x', payment_ImportFiles::MAX_XML_BYTES));
check(payment_ImportFiles::getRecs($largePdf)->status === 'unsupported', 'unrelated oversized PDF ignored');
$partial = archiveFixture('partial.zip', array('valid.xml' => $xml, 'invalid.xml' => str_replace('CRDT', 'INVALID', $xml)));
$result = payment_ImportFiles::getRecs($partial);
check($result->status === 'partial' && count($result->recs) === 1 && !empty($result->warnings), 'partial result retains only valid operations');

class core_Toolbar {
    public $buttons = array();
    public function addSbBtn($title, $cmd, $attrs = array()) { $this->buttons[$cmd] = $title; }
}
class core_FieldSet {
    public $toolbar;
    public $fields = array();
    public function __construct() { $this->toolbar = new core_Toolbar(); }
    public function FLD($name, $type, $params) { $this->fields[$name] = array($type, $params); }
    public function setField($name, $params) { $this->fields[$name] = $params; }
    public function input($field) {}
    public $rec;
    public $cmd = 'save';
    public $info;
    public $ignore = false;
    public $importButtonCaption;
    public $styles = array();
    public $errors = array();
    public $warnings = array();
    public function setError($field, $message) { $this->errors[$field] = $message; }
    public function setWarning($field, $message) { $this->warnings[$field] = $message; }
    public function isSubmitted() { return $this->cmd !== 'refresh' && !$this->errors && (!$this->warnings || $this->ignore); }
}
class core_Manager {}
class core_Users {
    public static $id = 1;
    public static $active = true;
    public static function isActiveUserId($id) { return self::$active; }
    public static function getCurrent() { return self::$id; }
}
define('EF_SALT', 'synthetic-test-salt');
function tr($text) { return $text; }
class core_Type {
    public static function getByName($name) { return new self(); }
    public function toVerbal($value) { return (string) $value; }
}
class cls {
    public static function get($name) { return new $name(); }
}
class core_TableView {
    public function get($rows, $fields) {
        $html = '<table>';
        foreach ($rows as $row) $html .= '<tr><td>' . implode('</td><td>', (array) $row) . '</td></tr>';
        return $html . '</table>';
    }
}
require dirname(__DIR__, 2) . '/import2/AbstractDriver.class.php';
require dirname(__DIR__) . '/ImportDriver.class.php';
$driver = new payment_ImportDriver();
foreach (array($plain, $ucFile, $bundle) as $file) {
    $form = new core_FieldSet();
    $form->rec = (object) array('xmlFile' => $file, 'archivePassword' => $secret);
    $driver->checkImportForm(null, $form);
    check(!$form->errors && !$form->warnings && count($form->rec->recs) === ($file === $bundle ? 2 : 1), 'manual import validates XML/CAMT/ZIP');
    $before = count(bank_Register::$batches);
    $driver->doImport(new core_Manager(), $form->rec);
    check(count(bank_Register::$batches) - $before === count($form->rec->importBatches), 'manual import preserves statement boundaries for deduplication');
}
$form->rec = (object) array('xmlFile' => $bundle, 'archivePassword' => 'wrong', 'recs' => array('stale'));
$driver->checkImportForm(null, $form);
check(!empty($form->errors) && empty($form->rec->recs), 'failed validation clears stale import records');
$form = new core_FieldSet();
$form->rec = (object) array('xmlFile' => $partial);
$driver->checkImportForm(null, $form);
check(!$form->errors && !empty($form->warnings) && count($form->rec->recs) === 1, 'manual partial import requires warning confirmation');

$parser = new attachment_TestParser();
check($parser->process(new attachment_TestMime(array($plain, $largePdf)), (object) array()) === 'ISO20022', 'unrelated attachment does not fail valid CAMT email');
check($parser->process(new attachment_TestMime(array($partial)), (object) array()) === false, 'partial CAMT email remains for review');
check(strpos(implode("\n", fileman_Files::$logs), 'Частичен импорт') !== false, 'partial import explicitly reported');
echo "Import validation checks passed\n";
