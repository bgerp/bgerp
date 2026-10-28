<?php
/**
 * php payment/tests/previewSources.php
 * Синтетични проверки на прегледа и източниците без база данни.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/referencesAndTotals.php';
class ht {
    public static function createLink($title, $url) { return '<a href="' . htmlspecialchars(implode('/', $url), ENT_QUOTES, 'UTF-8') . '">' . $title . '</a>'; }
}
$first = previewForm($plain);
bank_Register::$previewRows = array((object) array('status' => 'duplicate', 'existingId' => 42));
$changed = previewForm($plain, $first->rec->previewHash, true);
check(!$changed->isSubmitted() && !$changed->ignore && $changed->rec->previewHash !== $first->rec->previewHash, 'bank change after preview requires refreshed confirmation');
check(strpos($changed->info, 'bank_Register/list/42') !== false && strpos($changed->info, 'Дубликат') !== false, 'duplicate preview links to the existing row');
check(previewForm($plain, $changed->rec->previewHash)->isSubmitted(), 'unchanged duplicate preview can be confirmed');
bank_Register::$canList = false;
check(strpos(previewForm($plain)->info, 'bank_Register/list/') === false, 'register link obeys list rights');
bank_Register::$canList = true;
foreach (array('conflict', 'legacy') as $status) {
    bank_Register::$previewRows = array((object) array('status' => $status, 'existingId' => 42));
    $form = previewForm($plain);
    check($form->warnings && !previewForm($plain, $form->rec->previewHash)->isSubmitted(), 'conflict and ambiguous legacy duplicate require warning acknowledgement');
    check(previewForm($plain, $form->rec->previewHash, true)->isSubmitted(), 'unchanged warning can be acknowledged');
}
bank_Register::$previewRows = array((object) array('status' => 'duplicate', 'duplicateRow' => 1));
check(strpos(previewForm($plain)->info, 'Към ред от този импорт') !== false, 'input duplicate identifies earlier preview row');
bank_Register::$previewRows = null;

foreach (array($plain, $ucFile, $safeZip, $encrypted) as $fh) {
    $result = payment_ImportFiles::getRecs($fh, $secret);
    foreach ($result->recs as $i => $rec) {
        check($rec->sourceFileHnd === $fh && $rec->sourceFileName === $result->previewRows[$i]->sourceFile && $rec->sourceEntry === $result->previewRows[$i]->sourceEntry, 'manual CAMT/UC/ZIP records retain exact preview source');
    }
}
$mime = new attachment_TestMime(array($plain, $encrypted));
$before = count(bank_Register::$imports);
$parser->process($mime, (object) array('archivePassword' => $secret));
check($mime->emlCalls === 1 && count(bank_Register::$imports) === $before + 2, 'email source saved once for all recognized attachments');
foreach (array_slice(bank_Register::$imports, $before) as $rec) check($rec->sourceEmailHnd === 900 && !empty($rec->sourceFileHnd), 'email import retains original mail and attachment');
$mime = new attachment_TestMime(array(fixture('not-bank.txt', 'Not a bank statement')));
$parser->process($mime, (object) array());
check($mime->emlCalls === 0, 'unrelated email does not create an EML source');
echo "Preview and source checks passed\n";
