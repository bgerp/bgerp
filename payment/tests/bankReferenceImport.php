<?php
/**
 * php payment/tests/bankReferenceImport.php
 * Изпълнява реалния bank_Register::importRecs с изолирана памет вместо база данни.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
class core_Manager {
    public static $rows = array();
    public static $race;
    public static function fetch($where, $fields = '*', $cache = true) {
        if (!is_array($where) || !preg_match('/^#(transactionId|bankReferenceId) =/', $where[0], $match)) throw new Exception('Unexpected query');
        foreach (self::$rows as $row) {
            if (($row->{$match[1]} ?? null) === $where[1]) return clone $row;
        }
        return false;
    }
    public static function save(&$rec, $fields = null, $mode = null) {
        if (self::$race) {
            $callback = self::$race; self::$race = null; $callback($rec);
        }
        foreach (self::$rows as $row) {
            if ($row->transactionId === $rec->transactionId || (!empty($rec->bankReferenceId) && ($row->bankReferenceId ?? null) === $rec->bankReferenceId)) return 0;
        }
        $rec->id = count(self::$rows) + 1;
        self::$rows[] = clone $rec;
        return $rec->id;
    }
}
require dirname(__DIR__, 2) . '/bank/Register.class.php';
function check($condition, $name) { if (!$condition) { fwrite(STDERR, "FAIL $name\n"); exit(1); } echo "PASS $name\n"; }
function payment($reference = null, $amount = 15.25, $account = 1, $type = 'incoming') {
    return (object) array('serviceId' => 'Import ISO20022', 'valior' => '2026-10-27', 'amount' => $amount, 'ownAccountId' => $account, 'type' => $type,
        'contragentIban' => 'BG00TEST00000000000001', 'contragentName' => 'Test payer', 'reason' => 'Synthetic invoice', 'bankReference' => $reference);
}
$warnings = array();
$a = payment('CAMT:EUR:N:A'); $b = payment('CAMT:EUR:N:B'); $c = payment('CAMT:EUR:N:C');
check(strpos(bank_Register::importRecs(array($a, $b), null, $warnings), 'Импортирани 2') === 0 && !$warnings, 'equal payments with different bank references imported separately');
check(!isset($a->id) && !isset($a->transactionId), 'input records are not mutated');
bank_Register::$rows[0]->state = 'active'; bank_Register::$rows[0]->matches = array('document' => 42);
$existing = serialize(bank_Register::$rows);
bank_Register::importRecs(array($b, $a, $a), null, $warnings);
check(serialize(bank_Register::$rows) === $existing && !$warnings, 'reordered and repeated references preserve rows, state and matches');
bank_Register::importRecs(array($b, $c), 'EMAIL ISO20022', $warnings);
check(count(bank_Register::$rows) === 3 && !$warnings, 'overlapping statements insert only new reference');
bank_Register::importRecs(array(payment('CAMT:EUR:N:A', 15.25, '1')), null, $warnings);
check(count(bank_Register::$rows) === 3 && !$warnings, 'database string account and integer account use identical reference key');
foreach (array('amount' => 999, 'valior' => '2026-10-28', 'contragentIban' => 'BG00TEST00000000000002') as $field => $value) {
    $different = clone $a; $different->{$field} = $value;
    bank_Register::importRecs(array($different), null, $warnings);
    check(count(bank_Register::$rows) === 3 && count($warnings) === 1, 'reference with changed ' . $field . ' left for review');
}
$changedText = clone $a; $changedText->reason = 'Additional bank text'; $changedText->contragentName = 'TEST PAYER';
bank_Register::importRecs(array($changedText), null, $warnings);
check(count(bank_Register::$rows) === 3 && !$warnings, 'text corrections do not duplicate payment');
bank_Register::importRecs(array(payment('CAMT:EUR:N:A', 15.25, 2), payment('CAMT:EUR:N:A', 15.25, 1, 'outgoing'), payment('CAMT:EUR:R:A')), null, $warnings);
check(count(bank_Register::$rows) === 6 && !$warnings, 'account, direction and reversal scopes are independent');
bank_Register::importRecs(array(payment('CAMT:EUR:N:a')), null, $warnings);
check(count(bank_Register::$rows) === 7, 'references are case sensitive despite database collation');
bank_Register::importRecs(array(payment("CAMT:EUR:N:A'\\\"<script>[#1#]")), null, $warnings);
check(count(bank_Register::$rows) === 8 && !$warnings, 'special reference bytes do not enter SQL condition');

bank_Register::$rows = array();
bank_Register::importRecs(array(payment(), payment()));
check(count(bank_Register::$rows) === 2, 'legacy equal transfers in same batch preserved');
foreach (bank_Register::$rows as $i => $row) {
    check($row->transactionId === md5('2026-10-27|incoming|15.25|BG00TEST00000000000001|1|' . $i), 'legacy identifier unchanged');
}
$oldRows = serialize(bank_Register::$rows);
bank_Register::importRecs(array(payment(), payment()), null, $warnings);
check(serialize(bank_Register::$rows) === $oldRows && !$warnings, 'legacy reimport preserves existing data');
bank_Register::importRecs(array($a, $b), null, $warnings);
check(serialize(bank_Register::$rows) === $oldRows && count($warnings) === 2, 'new references cannot duplicate old ambiguous rows or assign guessed identities');
bank_Register::$rows = array();
bank_Register::importRecs(array($a, $b));
$oldRows = serialize(bank_Register::$rows);
bank_Register::importRecs(array(payment(), payment()), null, $warnings);
check(serialize(bank_Register::$rows) === $oldRows && !$warnings, 'reference-less reimport after referenced file still deduplicates');

// Симулира конкурентен INSERT след отрицателен fetch, без нишки и реална база.
bank_Register::$rows = array();
bank_Register::$race = function ($rec) { $copy = clone $rec; $copy->id = 1; bank_Register::$rows[] = $copy; };
bank_Register::importRecs(array($a), null, $warnings);
check(count(bank_Register::$rows) === 1 && !$warnings, 'unique reference collision is skipped after concurrent insert');
bank_Register::$rows = array();
bank_Register::$race = function ($rec) { $copy = clone $rec; $copy->id = 1; $copy->bankReference = 'another'; $copy->bankReferenceId = md5('another'); bank_Register::$rows[] = $copy; };
bank_Register::importRecs(array($a), null, $warnings);
check(count(bank_Register::$rows) === 2 && !$warnings, 'occupied legacy slot retried without losing distinct referenced payment');
bank_Register::$rows = array();
bank_Register::$race = function ($rec) { $copy = clone $rec; $copy->id = 1; $copy->amount = 999; bank_Register::$rows[] = $copy; };
bank_Register::importRecs(array($a), null, $warnings);
check(count(bank_Register::$rows) === 1 && $warnings, 'concurrent conflicting reference is reported');
