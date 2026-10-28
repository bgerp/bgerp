<?php
/**
 * php payment/tests/mt940.php
 * Синтетични MT940 извлечения; без приложение, база данни или изпращане на известия.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/diagnosticsAndNotifications.php';

$mt = ":20:SYNTHETIC-940\n:25:BG00TEST00000000000000\n:28C:001/001\n:60F:C261027EUR1000,00\n"
    . ":61:2610281027C123,45NTRFCLIENT1//BANKREF1\n:86:105?00Transfer?20Фактура 0000123456?31BG00TEST11111111111111?32Test Client?33OOD\n"
    . ":61:261028D23,45NCHGNONREF//BANKREF2\n:86:Bank fee\n:62F:C261028EUR1100,00\n:64:C261028EUR1100,00\n";
$read = payment_ParserMt940::getRecs($mt);
check(!$read->errors && count($read->recs) === 2, 'MT940 reads balanced statement');
check($read->recs[0]->amount === 123.45 && $read->recs[0]->type === 'incoming' && $read->recs[1]->type === 'outgoing', 'MT940 preserves amounts and direction');
check($read->recs[0]->valior === '2026-10-28', 'MT940 uses value date instead of booking date');
check($read->recs[0]->contragentIban === 'BG00TEST11111111111111' && $read->recs[0]->contragentName === 'Test Client OOD', 'MT940 reads structured counterparty');
check(strpos($read->recs[0]->reason, 'Фактура 0000123456') !== false && strpos($read->recs[0]->reason, 'CLIENT1') !== false, 'MT940 retains full details and customer reference');
check($read->recs[0]->bankReference === 'MT940:EUR:N:BANKREF1' && $read->recordSources[1]['entry'] === 2, 'MT940 supplies scoped bank reference and source position');
check(payment_ParserMt940::getRecs(str_replace('?', '+', $mt))->recs[0]->contragentName === 'Test Client OOD', 'MT940 plus-separated details');
check(payment_ParserMt940::getRecs(str_replace(':25:BG', ':25:TESTBGSF/BG', $mt))->recs[0]->ownAccountId === 2, 'MT940 BIC and IBAN account field');
check(count(payment_ParserMt940::getRecs("\xEF\xBB\xBF" . str_replace("\n", "\r\n", $mt))->recs) === 2, 'MT940 BOM and CRLF');
check(count(payment_ParserMt940::getRecs(str_replace("\n", "\r", $mt))->recs) === 2, 'MT940 CR line endings');
$wrapped = '{1:F01SYNTHETICXXX0000000000}{2:O940SYNTHETIC}{3:{108:TEST}}{4:' . "\n" . $mt . '-}{5:{CHK:123456789ABC}}';
check(count(payment_ParserMt940::getRecs($wrapped)->recs) === 2, 'MT940 SWIFT 940 envelope');
check(count(payment_ParserMt940::getRecs($wrapped . $wrapped)->recs) === 4, 'MT940 multiple SWIFT messages');
check(!payment_ParserMt940::getRecs(str_replace('O940', 'O942', $wrapped))->recs, 'MT942 envelope is rejected');
check(!empty(payment_ParserMt940::getRecs($mt . str_repeat("\n", 100001))->errors), 'MT940 text line limit');
check(!empty(payment_ParserMt940::getRecs($mt . str_repeat(":86:text\n", 50001))->errors), 'MT940 field limit');
$pcreLimit = ini_set('pcre.backtrack_limit', '1000');
try {
    $largeEnvelope = str_replace('Bank fee', str_repeat('x', 10000), $wrapped);
    check(!empty(payment_ParserMt940::getRecs($largeEnvelope)->errors), 'MT940 oversized SWIFT envelope produces a controlled error');
} finally { ini_set('pcre.backtrack_limit', $pcreLimit); }
$multi = $mt . str_replace('SYNTHETIC-940', 'SECOND', $mt);
check(count(payment_ParserMt940::getRecs($multi)->recs) === 4, 'MT940 multiple statements');
$interim = str_replace(array(':60F:', ':62F:'), array(':60M:', ':62M:'), $mt);
check(count(payment_ParserMt940::getRecs($interim)->recs) === 2, 'MT940 interim balances checked per message');
$reverse = str_replace(array(':62F:C261028EUR1100,00', ':64:C261028EUR1100,00'), array(":61:261028RC10,00NTRFNONREF//REV1\n:86:Original payer\n:61:261028RDD5,00NTRFNONREF//REV2\n:86:Original payee\n:62F:C261028EUR1095,00", ':64:C261028EUR1095,00'), $mt);
$rr = payment_ParserMt940::getRecs($reverse);
check(!$rr->errors && $rr->recs[2]->type === 'outgoing' && $rr->recs[3]->type === 'incoming', 'MT940 RC and RD reverse the financial direction');
check(strpos($rr->recs[2]->reason, 'Сторно:') === 0 && $rr->recs[2]->bankReference === 'MT940:EUR:R:REV1', 'MT940 reversal is marked and has separate reference scope');
$totals = $mt . ":90C:1EUR123,45\n:90D:1EUR23,45\n";
check(count(payment_ParserMt940::getRecs($totals)->recs) === 2, 'MT940 optional count and amount controls');
$free = str_replace('105?00Transfer?20Фактура 0000123456?31BG00TEST11111111111111?32Test Client?33OOD', "Unstructured <script>[#TOKEN#] invoice 000001\nSecond line", $mt);
$fr = payment_ParserMt940::getRecs($free);
check($fr->recs[0]->contragentIban === '' && strpos($fr->recs[0]->reason, "\nSecond line") !== false, 'MT940 unstructured details preserved without guessing counterparty');
$withoutRef = preg_replace('~//BANKREF[12]~', '//NONREF', $mt);
check(!isset(payment_ParserMt940::getRecs($withoutRef)->recs[0]->bankReference), 'MT940 client reference and NONREF are not deduplication keys');
$negative = str_replace(array('C261027EUR1000,00', 'C261028EUR1100,00'), array('D261027EUR200,00', 'D261028EUR100,00'), $mt);
check(count(payment_ParserMt940::getRecs($negative)->recs) === 2, 'MT940 debit balances');
$empty = ":20:EMPTY\n:25:BG00TEST00000000000000\n:28:1\n:60F:C261028EUR0,\n:62F:C261028EUR0,\n";
check(!payment_ParserMt940::getRecs($empty)->errors && !payment_ParserMt940::getRecs($empty)->recs, 'MT940 empty statement');

$badCases = array(
    'closing mismatch' => str_replace('C261028EUR1100,00', 'C261028EUR1100,01', $mt),
    'closing currency' => str_replace(':62F:C261028EUR', ':62F:C261028USD', $mt),
    'missing close' => preg_replace('/^:62F:.*\n/m', '', $mt),
    'missing opening' => preg_replace('/^:60F:.*\n/m', '', $mt),
    'repeated opening' => str_replace(':60F:', ":60F:C261027EUR1000,00\n:60F:", $mt),
    'invalid direction' => str_replace('1027C123', '1027X123', $mt),
    'invalid value date' => str_replace(':61:261028', ':61:260231', $mt),
    'invalid booking date' => str_replace('1027C', '1331C', $mt),
    'dot decimal' => str_replace('123,45', '123.45', $mt),
    'negative amount' => str_replace('C123,45', 'C-123,45', $mt),
    'too much precision' => str_replace('123,45', '123,451', $mt),
    'amount overflow' => str_replace('123,45', '2000000001,00', $mt),
    'overlong reference' => str_replace('BANKREF1', str_repeat('R', 17), $mt),
    'unknown field' => $mt . ":99:UNKNOWN\n",
    'broken field' => str_replace(':61:', ':61;', $mt),
    'operation after close' => $mt . ":61:261028C1,00NTRFNONREF\n",
    'duplicate narrative' => str_replace(':86:Bank fee', ":86:Fee\n:86:Bank fee", $mt),
    'wrong count' => str_replace(':90C:1', ':90C:2', $totals),
    'wrong total' => str_replace('1EUR123,45', '1EUR123,46', $totals),
    'count currency' => str_replace(':90C:1EUR', ':90C:1USD', $totals),
    'repeated total' => $totals . ":90C:1EUR123,45\n",
    'unexpected prefix' => "Some text\n" . $mt,
    'binary data' => str_replace('Bank fee', "Fee\0", $mt),
    'unknown encoding' => str_replace('Bank fee', "Fee\xff", $mt),
);
foreach ($badCases as $case => $bad) {
    $result = payment_ParserMt940::getRecs($bad);
    check($result && $result->errors && !$result->recs, 'MT940 rejects ' . $case);
}
$badAndGood = payment_ParserMt940::getRecs($badCases['closing mismatch'] . $mt);
check($badAndGood->errors && count($badAndGood->recs) === 2 && $badAndGood->recordSources[0]['entry'] === 3, 'MT940 bad statement does not discard independent valid statement');
bank_OwnAccounts::$historicalCurrency = true;
check(!payment_ParserMt940::getRecs(str_replace('2610', '2510', $mt))->recs, 'MT940 own-account currency checked at value date');
bank_OwnAccounts::$historicalCurrency = false;

$mtFile = fixture('statement.mt940', $mt);
foreach (array($mtFile, fixture('statement.sta', $mt), fixture('statement.txt', $mt), fixture('statement.bin', $mt), fixture('swift.txt', $wrapped)) as $file) {
    $result = payment_ImportFiles::getRecs($file);
    check($result->status === 'success' && count($result->recs) === 2 && $result->recs[0]->sourceFileHnd === $file && $result->previewRows[0]->currency === 'EUR', 'MT940 common import recognizes content and retains source ' . $file);
}
$mtZip = archiveFixture('mt940-mixed.zip', array('statement.sta' => $mt, 'camt.xml' => $xml));
check(count(payment_ImportFiles::getRecs($mtZip)->recs) === 3, 'MT940 shares ZIP reader with CAMT');
$mtLocked = archiveFixture('mt940-locked.zip', array('private.sta' => $mt), 'synth-pass');
check(count(payment_ImportFiles::getRecs($mtLocked, 'synth-pass')->recs) === 2, 'MT940 encrypted ZIP uses existing password support');
$view = previewForm(fixture('mt940-unsafe.txt', $free));
check(!$view->isSubmitted() && strpos($view->info, '<script>') === false && strpos($view->info, '[#TOKEN#]') === false, 'MT940 preview escapes narrative and requires confirmation');
check(previewForm($view->rec->xmlFile, $view->rec->previewHash)->isSubmitted(), 'MT940 unchanged preview can be confirmed');
check(!previewForm(fixture('bad-balance.mt940', $badCases['closing mismatch']), null, true)->isSubmitted(), 'MT940 balance error cannot be acknowledged away');

$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($mtFile)), (object) array()) === false && count(bank_Register::$imports) === $before, 'existing email rules do not start importing MT940');
$mtRule = (object) array('includeMt940' => 'yes', 'notifyUserId' => 77);
check($parser->process(new attachment_TestMime(array($mtFile)), $mtRule) !== false && count(bank_Register::$imports) === $before + 2, 'email rule explicitly enables MT940');
check(end(bank_Register::$imports)->sourceEmailHnd === 900, 'MT940 email retains original source');
bgerp_Notifications::$items = array();
$badMtFile = fixture('notify-bad.mt940', $badCases['closing mismatch']);
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($badMtFile)), $mtRule) === false && count(bank_Register::$imports) === $before && count(bgerp_Notifications::$items) === 1, 'MT940 invalid balance alerts responsible user without importing');
echo "MT940 checks passed\n";
