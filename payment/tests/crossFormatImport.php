<?php
/**
 * php payment/tests/crossFormatImport.php
 * CAMT/MT940 съвпадения с реалния импорт и изолирано хранилище.
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

$camt = payment('CAMT:EUR:N:CROSS-CAMT');
$mt = payment('MT940:EUR:N:CROSS-MT'); $mt->serviceId = 'EMAIL MT940';
foreach (array(array($camt, $mt), array($mt, $camt)) as $pair) {
    bank_Register::$rows = array();
    bank_Register::importRecs(array($pair[0]));
    $before = serialize(bank_Register::$rows);
    $preview = bank_Register::previewImport(array(array($pair[1])));
    check($preview[0]->status === 'crossFormat', 'opposite format with different reference needs review');
    bank_Register::importRecs(array($pair[1]), null, $warnings);
    check(serialize(bank_Register::$rows) === $before && count($warnings) === 1, 'automatic cross-format import skips and warns');
}
bank_Register::$rows = array();
$input = array(array($camt), array($mt));
$preview = bank_Register::previewImport($input);
check(array_column($preview, 'status') === array('new', 'crossFormat') && $preview[1]->crossFormatMatches[0]->duplicateRow === 1 && !bank_Register::$rows, 'mixed ZIP preview finds an earlier row without writes');
$confirmed = array($preview[1]->referenceId => $preview[1]->crossFormatToken);
foreach ($input as $batch) bank_Register::importRecs($batch, null, $warnings, $confirmed);
check(count(bank_Register::$rows) === 2 && !$warnings, 'explicit confirmation works between virtual preview and persisted ZIP rows');
$before = serialize(bank_Register::$rows);
bank_Register::importRecs(array($mt), null, $warnings, $confirmed);
check(serialize(bank_Register::$rows) === $before && !$warnings, 'confirmation never bypasses exact-reference duplicate');
$conflict = clone $mt; $conflict->amount = 99;
bank_Register::importRecs(array($conflict), null, $warnings, $confirmed);
check(serialize(bank_Register::$rows) === $before && $warnings, 'confirmation never bypasses conflicting reference');

foreach (array('', strtolower('BG00 TEST00000000000001')) as $iban) {
    bank_Register::$rows = array();bank_Register::importRecs(array($camt));
    $test = clone $mt; $test->contragentIban = $iban;
    check(bank_Register::previewImport(array(array($test)))[0]->status === 'crossFormat', 'missing or normalized IBAN still requires review');
    bank_Register::importRecs(array($test), null, $warnings);
    check(count(bank_Register::$rows) === 1 && $warnings, 'missing or normalized IBAN cannot bypass automatic check');
}
foreach (array('contragentIban' => 'BG00OTHER0000000000001', 'ownAccountId' => 2, 'valior' => '2026-10-28',
    'type' => 'outgoing', 'amount' => 16.25, 'bankReference' => 'MT940:EUR:R:CROSS-MT') as $field => $value) {
    bank_Register::$rows = array();bank_Register::importRecs(array($camt));
    $test = clone $mt; $test->{$field} = $value;
    check(bank_Register::previewImport(array(array($test)))[0]->status === 'new', 'different ' . $field . ' remains independent');
    bank_Register::importRecs(array($test), null, $warnings);
    check(count(bank_Register::$rows) === 2 && !$warnings, 'different ' . $field . ' still imports');
}
bank_Register::$rows = array();bank_Register::importRecs(array($camt));
$test = clone $mt; $test->bankReference = 'MT940:USD:N:CROSS-MT';
check(bank_Register::previewImport(array(array($test)))[0]->status === 'new', 'reference currency scopes stay independent');
$preview = bank_Register::previewImport(array(array($mt)));
$confirmed = array($preview[0]->referenceId => $preview[0]->crossFormatToken);
$another = clone $camt; $another->bankReference = 'CAMT:EUR:N:NEW-CANDIDATE';
bank_Register::importRecs(array($another));
$changed = bank_Register::previewImport(array(array($mt)));
check(count($changed[0]->crossFormatMatches) === 2 && $changed[0]->crossFormatToken !== $preview[0]->crossFormatToken, 'all candidates shown and new candidate invalidates confirmation');
bank_Register::importRecs(array($mt), null, $warnings, $confirmed);
check(count(bank_Register::$rows) === 2 && $warnings, 'stale confirmation cannot authorize unseen candidate');
$confirmed = array($changed[0]->referenceId => $changed[0]->crossFormatToken);
bank_Register::importRecs(array($mt), null, $warnings, $confirmed);
check(count(bank_Register::$rows) === 3 && !$warnings, 'current confirmation adds separate transfer');

bank_Register::$rows = array();
bank_Register::$race = function ($rec) {
    $copy = clone $rec; $copy->id = 1; $copy->bankReference = 'MT940:EUR:N:CONCURRENT';
    $copy->bankReferenceId = md5(serialize(array('1', 'incoming', $copy->bankReference)));
    bank_Register::$rows[] = $copy;
};
bank_Register::importRecs(array($camt), null, $warnings);
check(count(bank_Register::$rows) === 1 && $warnings, 'opposite format inserted in occupied slot is rechecked');
bank_Register::$rows = array();
$third = clone $camt; $third->bankReference = 'CAMT:EUR:N:THIRD';
$batches = array(array($camt, $mt, $third));
$preview = bank_Register::previewImport($batches, array(2 => true));
check(array_column($preview, 'status') === array('new', 'crossFormatConfirmed', 'crossFormat'), 'confirmed preview row participates in checking later rows');
check($preview[2]->crossFormatMatches[0]->duplicateRow === 2, 'later match points to confirmed preview row');
$confirmed = array($preview[1]->referenceId => $preview[1]->crossFormatToken);
bank_Register::importRecs($batches[0], null, $warnings, $confirmed);
check(count(bank_Register::$rows) === 2 && count($warnings) === 1, 'mixed confirmed and skipped preview agrees with actual import');
echo "Cross-format import checks passed\n";
