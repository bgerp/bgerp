<?php
/**
 * php payment/tests/diagnosticsAndNotifications.php
 * Синтетични проверки без база данни и външни известия.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/previewSources.php';

$before = count(bank_Register::$imports);
$first = previewForm($manyFile);
$second = previewForm($manyFile, $first->rec->previewHash, false, null, 'all', 2);
check(!$second->isSubmitted() && substr_count($second->info, '<tr>') === 1 && strpos($second->info, '<td>201</td>') !== false, 'page 2 displays row 201 without importing');
check(count($second->rec->recs) === 201 && count(bank_Register::$imports) === $before, 'paging retains full import and writes nothing');
check(previewForm($manyFile, $second->rec->previewHash, false, null, 'all', 2)->isSubmitted(), 'page 2 confirmation applies to full input');
check(!previewForm($manyFile, $second->rec->previewHash, false, null, 'all', 2, 'preview')->isSubmitted(), 'refresh preview never imports even with unchanged signed result');
$clamped = previewForm($manyFile, null, false, null, 'all', PHP_INT_MAX);
check($clamped->rec->previewPage === 2 && substr_count($clamped->info, '<tr>') === 1, 'page number clamped to available pages');

$differences = array('amount' => array('existing' => 15.25, 'incoming' => 999),
    'valior' => array('existing' => '2026-10-27', 'incoming' => '2026-10-28'),
    'contragentIban' => array('existing' => 'OLD<script>[#TOKEN#]', 'incoming' => 'NEW'));
bank_Register::$previewRows = array_fill(0, 200, (object) array('status' => 'new'));
bank_Register::$previewRows[] = (object) array('status' => 'conflict', 'existingId' => 42, 'differences' => $differences);
$filtered = previewForm($manyFile, null, false, null, 'problems');
check(substr_count($filtered->info, '<tr>') === 1 && strpos($filtered->info, '<td>201</td>') !== false, 'problem filter searches all rows before pagination');
check(strpos($filtered->info, 'налично 15.25; във файла 999') !== false && strpos($filtered->info, '2026-10-27') !== false && strpos($filtered->info, '2026-10-28') !== false, 'conflict details contain both original and incoming values');
check(strpos($filtered->info, '<script>') === false && strpos($filtered->info, '[#TOKEN#]') === false && strpos($filtered->info, '&lt;script&gt;') !== false, 'stored conflict values escaped for HTML and templates');
check(!previewForm($manyFile, $filtered->rec->previewHash, true, null, 'all')->isSubmitted(), 'changed filter requires another preview');
$filteredConfirmed = previewForm($manyFile, $filtered->rec->previewHash, true, null, 'problems');
check($filteredConfirmed->isSubmitted() && count($filteredConfirmed->rec->recs) === 201, 'problem-only view does not discard other import rows');
$before = count(bank_Register::$imports);
$driver->doImport(new core_Manager(), $filteredConfirmed->rec);
check(count(bank_Register::$imports) === $before + 201, 'import receives every row regardless of display filter');
bank_Register::$previewRows = null;
check(strpos(previewForm($manyFile, null, false, null, 'problems')->info, '<tr>') === false, 'empty problem filter is safe');

class core_Classes { public static function getId($class) { return 123; } }
class bgerp_Notifications {
    public static $items = array();
    public static $fail = false;
    public static function add($message, $url, $user, $priority, $custom, $once) {
        if (self::$fail) throw new RuntimeException('Synthetic notification failure');
        check($once === true && $priority === 'warning', 'notifications use native deduplication and warning priority');
        self::$items[serialize(array($url, $user))] = array($message, $url, $user);
    }
}
class core_exception_Redirect extends Exception {}
function reportException($e) { fileman_Files::$logs[] = 'Reported test exception'; }
$notifyRule = (object) array('notifyUserId' => 77);
$badFile = fixture('bad-notify.camt', '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02">');
check($parser->process(new attachment_TestMime(array($badFile)), $notifyRule) === false && count(bgerp_Notifications::$items) === 1, 'invalid CAMT alerts configured responsible user');
$item = reset(bgerp_Notifications::$items);
check($item[1] === payment_ImportDriver::getImportUrl($badFile) && $item[2] === 77 && $item[1]['Cmd'] === 'refresh', 'alert links to authorized ordinary preview form with source file');
$parser->process(new attachment_TestMime(array($badFile)), $notifyRule);
check(count(bgerp_Notifications::$items) === 1, 'same problematic file uses stable notification identity');

bgerp_Notifications::$items = array();
$notifyRule->archivePassword = 'wrong';
$parser->process(new attachment_TestMime(array($encrypted)), $notifyRule);
check(count(bgerp_Notifications::$items) === 1 && strpos(serialize(bgerp_Notifications::$items), 'wrong') === false && strpos(serialize(bgerp_Notifications::$items), $secret) === false, 'ZIP password failures alert without copying passwords into messages or URLs');
bgerp_Notifications::$items = array();
$before = count(bank_Register::$imports);
$parser->process(new attachment_TestMime(array($mixedFile)), $notifyRule);
check(count(bgerp_Notifications::$items) === 1 && count(bank_Register::$imports) === $before + 1, 'partial mail still imports valid rows and emits one summary');
bank_Register::$importWarnings = array('Conflict 1', 'Conflict 2', 'Conflict 1');
bgerp_Notifications::$items = array();
$parser->process(new attachment_TestMime(array($plain)), $notifyRule);
$item = reset(bgerp_Notifications::$items);
check(count(bgerp_Notifications::$items) === 1 && strpos($item[0], '2') !== false, 'all import conflicts summarized once per file');
bank_Register::$importWarnings = array();

foreach (array('disabled', 'fileRights', 'bankRights', 'healthy', 'unrelated') as $case) {
    bgerp_Notifications::$items = array();
    $rule = $case === 'disabled' ? (object) array() : $notifyRule;
    fileman_Files::$canView = $case !== 'fileRights'; bank_Register::$canList = $case !== 'bankRights';
    $fh = $case === 'healthy' ? $plain : ($case === 'unrelated' ? fixture('unrelated.txt', 'Text') : $badFile);
    $parser->process(new attachment_TestMime(array($fh)), $rule);
    check(!bgerp_Notifications::$items, 'no alert for ' . $case);
}
fileman_Files::$canView = bank_Register::$canList = true;
bgerp_Notifications::$fail = true;
check($parser->process(new attachment_TestMime(array($mixedFile)), $notifyRule) === false, 'notification failure does not change partial import outcome');
bgerp_Notifications::$fail = false;

$form = new core_FieldSet();$form->rec = (object) array('notifyUserId' => 77);
core_Users::$active = false;
payment_ParserIso20022::on_AfterInputEditForm(null, null, $form);
check(isset($form->errors['notifyUserId']), 'inactive responsible user rejected');
core_Users::$active = true;bank_Register::$canList = false;
$form = new core_FieldSet();$form->rec = (object) array('notifyUserId' => 77);
payment_ParserIso20022::on_AfterInputEditForm(null, null, $form);
check(isset($form->errors['notifyUserId']), 'responsible user without import rights rejected');
bank_Register::$canList = true;
echo "Diagnostics and notification checks passed\n";
