<?php
/**
 * php payment/tests/statusAndPreview.php
 * Синтетични проверки без приложение и база данни.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/groupedAndUnicredit.php';

foreach (array($xml, $xml52, $grouped) as $source) {
    $old = payment_ParserIso20022::getRecs($source);
    foreach (array('<Sts>BOOK</Sts>', '<Sts><Cd>BOOK</Cd></Sts>') as $status) {
        $booked = str_replace('<Ntry>', '<Ntry>' . $status, $source);
        $result = payment_ParserIso20022::getRecs($booked);
        check($result->recs == $old->recs && !$result->skippedEntries, 'BOOK retains ordinary and grouped records');
        $prefixedBooked = preg_replace('/<(\/?)([A-Za-z][A-Za-z0-9]*)(?=[ >\/])/', '<$1n:$2', str_replace('xmlns=', 'xmlns:n=', $booked));
        check(payment_ParserIso20022::getRecs($prefixedBooked)->recs == $old->recs, 'prefixed status');
    }
    foreach (array('PDNG', 'INFO', 'UNKNOWN', 'book', '', '0') as $status) {
        foreach (array('<Sts>' . $status . '</Sts>', '<Sts><Cd>' . $status . '</Cd></Sts>') as $element) {
            $result = payment_ParserIso20022::getRecs(str_replace('<Ntry>', '<Ntry>' . $element, $source));
            check(!$result->recs && $result->skippedEntries === 1 && count($result->warnings) === 1, 'non-booked entry skipped ' . $status);
        }
    }
}
foreach (array('<Sts><Prtry>BOOK</Prtry></Sts>', '<Sts>BOOK</Sts><Sts>PDNG</Sts>', '<Sts><Cd>BOOK</Cd><Prtry>PDNG</Prtry></Sts>', '<Sts>BOOK<Unknown>PDNG</Unknown></Sts>', '<Sts>PDNG<Cd>BOOK</Cd></Sts>') as $status) {
    check(!payment_ParserIso20022::getRecs(str_replace('<Ntry>', '<Ntry>' . $status, $xml))->recs, 'ambiguous status rejected');
}
$pendingXml = str_replace('<Ntry>', '<Ntry><Sts><Cd>PDNG</Cd></Sts>', $xml);
preg_match('~<Ntry>.*?</Ntry>~', $pendingXml, $pendingEntry);
$mixedStatus = str_replace('</Stmt>', $pendingEntry[0] . '</Stmt>', $xml);
$mixedFile = fixture('status.camt', $mixedStatus);
$result = payment_ImportFiles::getRecs($mixedFile);
check(count($result->recs) === 1 && $result->skippedEntries === 1 && $result->status === 'partial', 'missing status retained beside pending entry');
check(strpos($result->warnings[0], 'status.camt') !== false && strpos($result->warnings[0], '№2') !== false, 'warning identifies file and entry');
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($mixedFile)), (object) array()) === false && count(bank_Register::$imports) === $before + 1, 'email imports valid records only and retains partial mail');
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array(fixture('pending.camt', $pendingXml))), (object) array()) === false && count(bank_Register::$imports) === $before, 'pending-only email writes nothing');

function previewForm($file, $hash = null, $ignore = false, $password = null) {
    $form = new core_FieldSet();
    $form->ignore = $ignore;
    $form->rec = (object) array('xmlFile' => $file, 'previewHash' => $hash, 'archivePassword' => $password, 'recs' => array('untrusted'));
    (new payment_ImportDriver())->checkImportForm(new core_Manager(), $form);
    return $form;
}
foreach (array($plain, $ucFile, $bundle) as $file) {
    $before = count(bank_Register::$imports);
    $first = previewForm($file, null, false, $secret);
    check(!$first->isSubmitted() && !empty($first->rec->previewHash) && count(bank_Register::$imports) === $before, 'first submission previews without writes');
    check(strpos($first->info, 'Нищо не е записано') !== false && strpos($first->info, 'BG00TEST00000000000000') !== false, 'preview includes account and result');
    $second = previewForm($file, $first->rec->previewHash, false, $secret);
    check($second->isSubmitted() && $second->rec->recs == $first->rec->recs, 'confirmation retains parsed XML or ZIP records');
    check(!previewForm($file, 'forged', true, $secret)->isSubmitted(), 'forged preview cannot skip first step');
}
$first = previewForm($plain);
fileman_Files::$canView = false;
$denied = previewForm($plain, $first->rec->previewHash, true);
check(!$denied->isSubmitted() && empty($denied->rec->recs) && empty($denied->rec->previewHash), 'file access checked again before confirmation');
fileman_Files::$canView = true;
check(!previewForm($ucFile, $first->rec->previewHash)->isSubmitted(), 'changing selected file requires new preview');
core_Users::$id = 2;
check(!previewForm($plain, $first->rec->previewHash)->isSubmitted(), 'confirmation bound to current user');
core_Users::$id = 1;
$mutable = fixture('mutable.camt', $xml);
$first = previewForm($mutable);
fixture('mutable.camt', str_replace('123.45', '234.56', $xml));
check(!previewForm($mutable, $first->rec->previewHash)->isSubmitted(), 'changed file contents require new preview');
$first = previewForm($mixedFile);
check(!previewForm($mixedFile, $first->rec->previewHash)->isSubmitted(), 'partial confirmation needs warning acknowledgement');
$confirmed = previewForm($mixedFile, $first->rec->previewHash, true);
check($confirmed->isSubmitted() && count($confirmed->rec->recs) === 1, 'warning acknowledgement imports only valid records');
check(!previewForm($bundle, $first->rec->previewHash, true, 'wrong')->isSubmitted(), 'wrong password cannot confirm');
check(!previewForm(fixture('only-pending.camt', $pendingXml), null, true)->rec->previewHash, 'no import confirmation for empty result');

$unsafe = 'QA <img src=x onerror=alert(1)> [#FORM_HIDDEN#]';
$unsafeXml = str_replace('Test invoice 12345', htmlspecialchars($unsafe, ENT_XML1), $xml);
$safeZip = archiveFixture('preview.zip', array('nested/<img src=x>.camt' => $unsafeXml, 'pending.xml' => $pendingXml));
$preview = previewForm($safeZip);
check(strpos($preview->info, '<img src=x') === false && strpos($preview->info, '[#FORM_HIDDEN#]') === false && strpos($preview->info, '&lt;img') !== false, 'file names and reasons are escaped in preview');
check(strpos(implode(' ', $preview->warnings), 'pending.xml') !== false, 'ZIP diagnostic preserves entry name');
$result = payment_ImportFiles::getRecs($safeZip);
check($result->batches[0][0]->reason === $unsafe && !isset($result->batches[0][0]->sourceFile), 'display escaping and metadata do not alter imported records');
preg_match('~<Ntry>.*?</Ntry>~', $xml, $oneEntry);
$manyFile = fixture('many-entries.camt', str_replace($oneEntry[0], str_repeat($oneEntry[0], 201), $xml));
$manyPreview = previewForm($manyFile);
check(count($manyPreview->rec->recs) === 201 && substr_count($manyPreview->info, '<tr>') === 200 && strpos($manyPreview->info, 'първите 200') !== false, 'preview limit does not truncate import records');
check(count(previewForm($manyFile, $manyPreview->rec->previewHash)->rec->recs) === 201, 'confirmation retains records beyond preview limit');
echo "Status and preview checks passed\n";
