<?php
/**
 * php payment/tests/crossFormatPreview.php
 * Потвърждаване на отделни преводи през общия преглед.
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
$match = (object) array('existingId' => 42, 'bankReference' => 'MT940:EUR:N:<script>[#TOKEN#]');
bank_Register::$previewRows = array((object) array('status' => 'crossFormat', 'referenceId' => 'synthetic-ref',
    'crossFormatToken' => 'synthetic-token', 'crossFormatMatches' => array($match)));
$first = previewForm($plain);
check(!$first->isSubmitted() && $first->warnings && !$first->rec->confirmedCrossFormats, 'first cross-format preview warns and clears untrusted confirmation');
check(strpos($first->info, 'bank_Register/list/42') !== false && strpos($first->info, '<script>') === false && strpos($first->info, '[#TOKEN#]') === false, 'candidate link present and reference escaped');
$skip = previewForm($plain, $first->rec->previewHash, true);
check($skip->isSubmitted() && !$skip->rec->confirmedCrossFormats, 'warning acknowledgement alone cannot authorize a duplicate');
$selected = previewForm($plain, $first->rec->previewHash, true, null, 'all', 1, 'save', '1');
check(!$selected->isSubmitted() && !$selected->ignore && strpos($selected->info, 'Потвърден различен превод') !== false, 'selecting a separate transfer requires renewed preview');
$confirmed = previewForm($plain, $selected->rec->previewHash, false, null, 'all', 1, 'save', '1');
check($confirmed->isSubmitted() && $confirmed->rec->confirmedCrossFormats === array('synthetic-ref' => 'synthetic-token'), 'unchanged reviewed selection authorizes exactly shown match');
$driver->doImport(new core_Manager(), $confirmed->rec);
check(bank_Register::$confirmedCrossFormats === $confirmed->rec->confirmedCrossFormats, 'driver passes verified confirmation to real import boundary');
foreach (array('2', '0', '-1', '1<script>', '1,2', '1|2') as $invalid) {
    check(!previewForm($plain, $selected->rec->previewHash, true, null, 'all', 1, 'save', $invalid)->isSubmitted(), 'invalid or unrelated selected row rejected: ' . $invalid);
}
bank_Register::$previewRows[0]->crossFormatToken = 'changed-token';
check(!previewForm($plain, $selected->rec->previewHash, true, null, 'all', 1, 'save', '1')->isSubmitted(), 'candidate change invalidates prior confirmation');
bank_Register::$canList = false;
check(strpos(previewForm($plain)->info, 'bank_Register/list/42') === false, 'cross-format link respects register rights');
bank_Register::$canList = true;
check(strpos(previewForm($plain, null, false, null, 'problems')->info, '<td>1</td>') !== false, 'problem filter includes cross-format rows');
foreach (array('duplicate', 'conflict', 'legacy', 'new') as $status) {
    bank_Register::$previewRows[0]->status = $status;
    check(!previewForm($plain, null, true, null, 'all', 1, 'save', '1')->isSubmitted(), 'cross-format selection cannot override ' . $status);
}
bank_Register::$previewRows = null;
echo "Cross-format preview checks passed\n";
