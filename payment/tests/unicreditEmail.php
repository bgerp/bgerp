<?php
/**
 * php payment/tests/unicreditEmail.php
 * Изрично включване на Unicredit XML в имейл правило; без база или изпращане.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/mt940.php';

$ucRule = (object) array('includeUnicredit' => 'yes', 'notifyUserId' => 77);
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($ucFile)), $ucRule) === 'Банков импорт'
    && count(bank_Register::$imports) === $before + 1, 'Unicredit email opt-in imports one operation');
$imported = end(bank_Register::$imports);
check($imported->amount == 27.56 && $imported->type === 'incoming' && $imported->serviceId === 'EMAIL Unicredit', 'Unicredit email preserves parsed amount and direction');
check($imported->sourceFileHnd === $ucFile && $imported->sourceEmailHnd === 900, 'Unicredit email retains attachment and original message');

foreach (array((object) array(), (object) array('includeUnicredit' => 'no'), (object) array('includeMt940' => 'yes')) as $oldRule) {
    $before = count(bank_Register::$imports);
    check($parser->process(new attachment_TestMime(array($ucFile)), $oldRule) === false
        && count(bank_Register::$imports) === $before, 'Unicredit remains disabled for existing rules');
}
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($mtFile)), $ucRule) === false
    && count(bank_Register::$imports) === $before, 'Unicredit option does not enable MT940');

$ucBundle = archiveFixture('all-bank-formats.zip', array('camt.xml' => $xml, 'legacy.xml' => $uc, 'statement.sta' => $mt), $secret);
foreach (array('no' => 2, 'yes' => 4) as $includeMt940 => $expected) {
    $ucRule->archivePassword = $secret;
    $ucRule->includeMt940 = $includeMt940;
    $before = count(bank_Register::$imports);
    check($parser->process(new attachment_TestMime(array($ucBundle)), $ucRule) === 'Банков импорт'
        && count(bank_Register::$imports) === $before + $expected, 'encrypted mixed ZIP honors both format options');
}
$ucRule->archivePassword = 'wrong';
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($ucBundle)), $ucRule) === false
    && count(bank_Register::$imports) === $before, 'wrong ZIP password produces no imports');

bgerp_Notifications::$items = array();
$badUC = fixture('invalid-unicredit.xml', str_replace('27.56', '-1', $uc));
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($badUC, $plain)), $ucRule) === false
    && count(bank_Register::$imports) === $before + 1 && count(bgerp_Notifications::$items) === 1,
    'invalid Unicredit keeps mail for review, notifies and preserves independent valid CAMT');
echo "Unicredit email checks passed\n";
