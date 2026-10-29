<?php
/**
 * php payment/tests/mt940Register.php
 * MT940 с реалния банков импорт и изолирано хранилище в паметта.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/bankReferenceImport.php';
require dirname(__DIR__) . '/XmlParser.class.php';
require dirname(__DIR__) . '/ParserMt940.class.php';
class bank_Accounts {
    public static $found = true;
    public static function fetch($condition) { return self::$found ? (object) array('id' => 1) : false; }
}
class bank_OwnAccounts {
    public static $found = true;
    public static function fetch($condition) { return self::$found ? (object) array('id' => 2) : false; }
    public static function getDefaultCurrency($id, $date, $code) { return 'EUR'; }
}
$mt = ":20:SYNTHETIC-REG\n:25:BG00TEST00000000000000\n:28C:1\n:60F:C261028EUR0,00\n"
    . ":61:261028C15,25NTRFNONREF//BANKREF1\n:86:Invoice 001\n"
    . ":61:261028C15,25NTRFNONREF//BANKREF2\n:86:Invoice 002\n:62F:C261028EUR30,50\n";
$parsed = payment_ParserMt940::getRecs($mt);
check(!$parsed->errors && count($parsed->recs) === 2, 'MT940 register fixture parsed');
bank_Register::$rows = array();
$preview = bank_Register::previewImport(array($parsed->recs));
check($preview[0]->status === 'new' && $preview[1]->status === 'new' && !bank_Register::$rows, 'MT940 preview classifies without writing');
bank_Register::importRecs($parsed->recs);
check(count(bank_Register::$rows) === 2, 'MT940 equal payments with different bank references both imported');
$before = serialize(bank_Register::$rows);
$changed = payment_ParserMt940::getRecs(str_replace('Invoice', 'Updated invoice', $mt), 'EMAIL MT940');
bank_Register::importRecs(array_reverse($changed->recs));
check(serialize(bank_Register::$rows) === $before, 'MT940 repeated, reordered and renamed import preserves existing rows');
$conflict = payment_ParserMt940::getRecs(str_replace(array('C15,25', 'C261028EUR30,50'), array('C16,25', 'C261028EUR32,50'), $mt));
check(!$conflict->errors, 'MT940 conflicting file balances internally');
$preview = bank_Register::previewImport(array($conflict->recs));
check($preview[0]->status === 'conflict' && $preview[0]->differences['amount']['incoming'] === 16.25, 'MT940 preview identifies bank reference conflict and amount difference');
bank_Register::importRecs($conflict->recs, null, $warnings);
check(serialize(bank_Register::$rows) === $before && count($warnings) === 2, 'MT940 conflicting references cannot overwrite or add payments');
$ambiguous = payment_ParserMt940::getRecs(str_replace('BANKREF2', 'BANKREF1', $mt));
check($ambiguous->errors && !$ambiguous->recs, 'MT940 repeated bank reference within one balanced statement requires review');
$withoutRef = payment_ParserMt940::getRecs(preg_replace('~//BANKREF[12]~', '', $mt));
bank_Register::$rows = array();
bank_Register::importRecs($withoutRef->recs);
$before = serialize(bank_Register::$rows);
bank_Register::importRecs($withoutRef->recs);
check(count(bank_Register::$rows) === 2 && serialize(bank_Register::$rows) === $before, 'MT940 without references retains old ordinal deduplication');
bank_Accounts::$found = false;
check(!payment_ParserMt940::getRecs($mt)->recs, 'MT940 unknown account rejected');
bank_Accounts::$found = true;bank_OwnAccounts::$found = false;
check(!payment_ParserMt940::getRecs($mt)->recs, 'MT940 counterparty account cannot be used as own account');
bank_OwnAccounts::$found = true;
$bulbank = str_replace(array('Invoice 001', 'Invoice 002'),
    array('TBB+21Invoice 001+31BG00TEST11111111111111+32Test payer', 'TFC+21Invoice 002+31BG00TEST22222222222222+32Other payer'), $mt);
foreach (array(true, false) as $hasReference) {
    $text = $hasReference ? $bulbank : preg_replace('~//BANKREF[12]~', '', $bulbank);
    bank_Register::$rows = array();
    $old = payment_ParserMt940::getRecs($text)->recs;
    foreach ($old as $rec) $rec->contragentIban = $rec->contragentName = '';
    bank_Register::importRecs($old);
    $before = serialize(bank_Register::$rows);
    $parsed = payment_ParserMt940::getRecs($text);
    bank_Register::importRecs($parsed->recs, null, $warnings);
    check(serialize(bank_Register::$rows) === $before && ($warnings || $parsed->warnings),
        'MT940 Bulbank reimport cannot duplicate old blank-IBAN rows ' . ($hasReference ? 'with reference' : 'without reference'));
}
$text = preg_replace('~//BANKREF[12]~', '', $bulbank);
bank_Register::$rows = array();
$fresh = payment_ParserMt940::getRecs($text);
check(count($fresh->recs) === 2 && !$fresh->warnings && !$fresh->skippedEntries, 'MT940 Bulbank without references still imports new transfers');
bank_Register::importRecs($fresh->recs);
$before = serialize(bank_Register::$rows);
$again = payment_ParserMt940::getRecs($text);
bank_Register::importRecs($again->recs);
check(count($again->recs) === 2 && !$again->warnings && serialize(bank_Register::$rows) === $before, 'MT940 Bulbank reimport after fix retains ordinal deduplication');
$old = clone $fresh->recs[0];
$old->contragentIban = $old->contragentName = '';
foreach (array('ownAccountId' => 3, 'valior' => '2026-10-29', 'type' => 'outgoing', 'amount' => 20.25,
    'serviceId' => 'Import ISO20022', 'contragentIban' => 'BG00OTHER0000000000001') as $field => $value) {
    bank_Register::$rows = array();
    $different = clone $old; $different->{$field} = $value;
    bank_Register::importRecs(array($different));
    check(count(payment_ParserMt940::getRecs($text)->recs) === 2, 'MT940 legacy check does not suppress different ' . $field);
}
foreach (array('', null) as $emptyIban) {
    bank_Register::$rows = array();
    $old->contragentIban = $emptyIban;
    bank_Register::importRecs(array($old));
    $partialText = str_replace(array(":61:261028C15,25NTRFNONREF\n:86:TFC", ':62F:C261028EUR30,50'),
        array(":61:261028C20,25NTRFNONREF\n:86:TFC", ':62F:C261028EUR35,50'), $text);
    $parsed = payment_ParserMt940::getRecs($partialText);
    check(count($parsed->recs) === 1 && count($parsed->warnings) === 1 && $parsed->skippedEntries === 1
        && $parsed->recordSources[0]['entry'] === 2 && $parsed->recs[0]->contragentIban === 'BG00TEST22222222222222',
        'MT940 legacy check preserves independent operation and source position');
}
echo "MT940 register checks passed\n";
