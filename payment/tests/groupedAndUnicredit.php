<?php
/**
 * php payment/tests/groupedAndUnicredit.php
 * Синтетични проверки на групирани операции, сторнирания и Unicredit XML.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/importValidation.php';

$txOne = '<TxDtls><Amt Ccy="EUR">40.15</Amt><RltdPties><Dbtr><Nm>First payer</Nm></Dbtr><DbtrAcct><Id><IBAN>BG00TEST11111111111111</IBAN></Id></DbtrAcct></RltdPties><RmtInf><Ustrd>Invoice 000001</Ustrd></RmtInf></TxDtls>';
$txTwo = '<TxDtls><AmtDtls><TxAmt><Amt Ccy="EUR">83.30</Amt></TxAmt></AmtDtls><RltdPties><Dbtr><Nm>Second payer</Nm></Dbtr><DbtrAcct><Id><IBAN>BG00TEST22222222222222</IBAN></Id></DbtrAcct></RltdPties><RmtInf><Ustrd>Invoice 000002</Ustrd></RmtInf></TxDtls>';
$grouped = preg_replace('~<NtryDtls>.*?</NtryDtls>~', '<NtryDtls><Btch><NbOfTxs>2</NbOfTxs></Btch>' . $txOne . $txTwo . '</NtryDtls>', $xml);
$result = payment_ParserIso20022::getRecs($grouped);
check(count($result->recs) === 2 && !$result->warnings, 'grouped CAMT splits into two operations');
check($result->recs[0]->amount == 40.15 && $result->recs[1]->amount == 83.30, 'individual transaction amounts');
check($result->recs[0]->contragentName === 'First payer' && $result->recs[1]->contragentName === 'Second payer', 'individual counterparties');
check($result->recs[0]->reason === 'Invoice 000001' && $result->recs[1]->reason === 'Invoice 000002', 'individual reasons');

$separateDetails = str_replace('<Btch><NbOfTxs>2</NbOfTxs></Btch>', '', $grouped);
$separateDetails = str_replace('</TxDtls><TxDtls>', '</TxDtls></NtryDtls><NtryDtls><TxDtls>', $separateDetails);
check(count(payment_ParserIso20022::getRecs($separateDetails)->recs) === 2, 'multiple NtryDtls are read');
$group52 = str_replace(array('camt.053', 'BkToCstmrStmt', '<Stmt>', '</Stmt>'), array('camt.052', 'BkToCstmrAcctRpt', '<Rpt>', '</Rpt>'), $grouped);
check(count(payment_ParserIso20022::getRecs($group52)->recs) === 2, 'grouped camt.052');
$groupPrefix = preg_replace('/<(\/?)([A-Za-z][A-Za-z0-9]*)(?=[ >\/])/', '<$1n:$2', str_replace('xmlns=', 'xmlns:n=', $grouped));
check(count(payment_ParserIso20022::getRecs($groupPrefix)->recs) === 2, 'grouped prefixed namespace');

$invalidGroups = array(
    str_replace('83.30', '83.31', $grouped),
    str_replace('<Amt Ccy="EUR">40.15</Amt>', '', $grouped),
    str_replace('<Amt Ccy="EUR">40.15</Amt>', '<Amt Ccy="USD">40.15</Amt>', $grouped),
    str_replace('<Amt Ccy="EUR">40.15</Amt>', '<Amt>40.15</Amt>', $grouped),
    str_replace('40.15', '-40.15', $grouped),
    str_replace('40.15', '40.151', $grouped),
    str_replace('123.45', '123.454', $grouped),
    str_replace('<NbOfTxs>2</NbOfTxs>', '<NbOfTxs>3</NbOfTxs>', $grouped),
    str_replace('<NbOfTxs>2</NbOfTxs>', '<NbOfTxs>x</NbOfTxs>', $grouped),
    str_replace('<TxDtls>', '<TxDtls><CdtDbtInd>UNKNOWN</CdtDbtInd>', $grouped),
    str_replace('<TxDtls>', '<TxDtls><CdtDbtInd>0</CdtDbtInd>', $grouped),
    str_replace('<Btch>', '<Btch><CdtDbtInd>0</CdtDbtInd>', $grouped),
);
foreach ($invalidGroups as $index => $bad) {
    $result = payment_ParserIso20022::getRecs($bad);
    check(!$result->recs && count($result->warnings) === 1, 'ambiguous group is not partially imported ' . $index);
}
$mixed = str_replace(array('40.15', '83.30'), array('150.00', '26.55'), $grouped);
$mixed = str_replace('<AmtDtls>', '<CdtDbtInd>DBIT</CdtDbtInd><AmtDtls>', $mixed);
$result = payment_ParserIso20022::getRecs($mixed);
check(count($result->recs) === 2 && $result->recs[1]->type === 'outgoing', 'mixed debit and credit details reconcile to net entry amount');
$batchDebit = str_replace(array('<CdtDbtInd>CRDT</CdtDbtInd>', '<Btch>'), array('<CdtDbtInd>DBIT</CdtDbtInd>', '<Btch><CdtDbtInd>DBIT</CdtDbtInd>'), $grouped);
check(payment_ParserIso20022::getRecs($batchDebit)->recs[1]->type === 'outgoing', 'batch direction inherited');

check(!empty(payment_ParserIso20022::getRecs(str_replace('<Amt Ccy="EUR">', '<Amt Ccy="USD">', $xml))->warnings), 'unqualified amount currency attribute checked within default namespace');
$noDetails = preg_replace('~<NtryDtls>.*?</NtryDtls>~', '', $xml);
check(count(payment_ParserIso20022::getRecs($noDetails)->recs) === 1, 'ordinary entry without transaction details retained');

$creditor = '<Cdtr><Nm>Original creditor</Nm></Cdtr><CdtrAcct><Id><IBAN>BG00TEST33333333333333</IBAN></Id></CdtrAcct>';
$reverseCredit = str_replace('</RltdPties>', $creditor . '</RltdPties>', $xml);
$reverseCredit = str_replace('</CdtDbtInd>', '</CdtDbtInd><RvslInd>true</RvslInd>', $reverseCredit);
$result = payment_ParserIso20022::getRecs($reverseCredit);
check($result->recs[0]->type === 'incoming' && $result->recs[0]->amount == 123.45, 'credit reversal is still incoming');
check($result->recs[0]->contragentName === 'Original creditor' && strpos($result->recs[0]->reason, '[Сторно] ') === 0, 'reversal keeps original creditor and is marked');
$reverseDebit = str_replace('CRDT', 'DBIT', $reverseCredit);
$result = payment_ParserIso20022::getRecs($reverseDebit);
check($result->recs[0]->type === 'outgoing' && $result->recs[0]->contragentName === 'Test sender', 'debit reversal uses original debtor');
foreach (array('1', 'True', 'TRUE') as $flag) {
    check(strpos(payment_ParserIso20022::getRecs(str_replace('<RvslInd>true', '<RvslInd>' . $flag, $reverseDebit))->recs[0]->reason, '[Сторно] ') === 0, 'true reversal flag ' . $flag);
}
foreach (array('0', 'false') as $flag) {
    check(payment_ParserIso20022::getRecs(str_replace('</CdtDbtInd>', '</CdtDbtInd><RvslInd>' . $flag . '</RvslInd>', $xml))->recs[0]->reason === 'Test invoice 12345', 'false reversal preserves old result');
}
check(!payment_ParserIso20022::getRecs(str_replace('<RvslInd>true', '<RvslInd>invalid', $reverseCredit))->recs, 'unknown reversal flag rejected');
$groupReverse = str_replace('</CdtDbtInd>', '</CdtDbtInd><RvslInd>true</RvslInd>', $batchDebit);
check(count(payment_ParserIso20022::getRecs($groupReverse)->recs) === 2, 'grouped reversal');

bank_Register::$legacy = (object) array('id' => 99);
check(!payment_ParserIso20022::getRecs($grouped)->recs, 'existing aggregate prevents duplicate split import');
check(!payment_ParserIso20022::getRecs($reverseCredit)->recs, 'existing reversal with old counterparty requires review');
check(count(payment_ParserIso20022::getRecs($xml)->recs) === 1, 'legacy check does not alter ordinary entries');
bank_Register::$legacy = false;

preg_match('~<Ntry>.*?</Ntry>~', $xml, $ordinaryEntry);
$partialGroup = str_replace('</Stmt>', $ordinaryEntry[0] . '</Stmt>', str_replace('83.30', '83.31', $grouped));
$result = payment_ParserIso20022::getRecs($partialGroup);
check(count($result->recs) === 1 && $result->recs[0]->amount == 123.45 && count($result->warnings) === 1, 'bad group does not discard an independent valid entry');
$emailGroup = fixture('grouped.camt', $grouped);
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array($emailGroup)), (object) array()) === 'ISO20022' && count(bank_Register::$imports) === $before + 2, 'grouped email uses both transactions');
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array(fixture('partial-group.camt', $partialGroup))), (object) array()) === false && count(bank_Register::$imports) === $before + 1, 'partial email imports only independent valid entries');

$original = payment_ParserUC::getRecs($uc);
$debitUC = str_replace('<MovementType>2', '<MovementType>1', $uc);
check(payment_ParserUC::getRecs($debitUC)->recs[0]->type === 'outgoing', 'Unicredit debit retained');
check(payment_ParserUC::getRecs("\xEF\xBB\xBF \n" . $uc)->recs == $original->recs, 'Unicredit BOM and whitespace');
foreach (array('', 'text', '-1', '1,23', 'NaN', 'INF', '1e3', '2000000001') as $amount) {
    $result = payment_ParserUC::getRecs(str_replace('27.56', $amount, $uc));
    check(!$result->recs && !empty($result->warnings), 'Unicredit invalid amount rejected');
}
foreach (array('', '0', '3', 'unknown') as $direction) {
    $result = payment_ParserUC::getRecs(str_replace('<MovementType>2', '<MovementType>' . $direction, $uc));
    check(!$result->recs && !empty($result->warnings), 'Unicredit invalid direction rejected');
}
foreach (array('', 'invalid', '2026-02-30', '2025-02-29', '2026-00-10') as $date) {
    $result = payment_ParserUC::getRecs(str_replace('2026-10-26', $date, $uc));
    check(!$result->recs && !empty($result->warnings), 'Unicredit invalid date rejected');
}
foreach (array('0', '12.30', '+12.30', '2000000000') as $amount) {
    check(count(payment_ParserUC::getRecs(str_replace('27.56', $amount, $uc))->recs) === 1, 'Unicredit valid amount retained');
}
check(count(payment_ParserUC::getRecs(str_replace('2026-10-26', '2024-02-29', $uc))->recs) === 1, 'valid leap day');
check(!payment_ParserUC::getRecs(str_replace('BG00TEST00000000000000', '', $uc))->recs, 'empty own IBAN rejected');
check(!payment_ParserUC::getRecs(str_replace('<SWIFTCode>EUR', '<SWIFTCode>USD', $uc))->recs, 'wrong Unicredit currency rejected');
check(!payment_ParserUC::getRecs(str_replace('<SWIFTCode>EUR', '<SWIFTCode>0', $uc))->recs, 'invalid zero currency rejected');
check(count(payment_ParserUC::getRecs(str_replace('<SWIFTCode>EUR</SWIFTCode>', '', $uc))->recs) === 1, 'missing currency retains own-account fallback');
bank_OwnAccounts::$historicalCurrency = true;
$oldBgn = str_replace(array('2026-10-26', '<SWIFTCode>EUR'), array('2025-12-31', '<SWIFTCode>BGN'), $uc);
check(count(payment_ParserUC::getRecs($oldBgn)->recs) === 1, 'historical BGN statement retained');
check(count(payment_ParserUC::getRecs($uc)->recs) === 1, 'EUR after account currency transition');
check(!payment_ParserUC::getRecs(str_replace('2025-12-31', '2026-01-02', $oldBgn))->recs, 'BGN after transition rejected');
bank_OwnAccounts::$historicalCurrency = false;
foreach (array('<APAccounts>', '<Other/>', '<!DOCTYPE APAccounts [<!ENTITY test SYSTEM "file:///nonexistent">]>' . $uc) as $bad) {
    $result = payment_ParserUC::getRecs($bad);
    check(!$result->recs && !empty($result->errors), 'Unicredit invalid XML controlled');
}
$previousLibxml = libxml_use_internal_errors(false);
payment_ParserUC::getRecs('<APAccounts>');
check(libxml_use_internal_errors() === false, 'libxml error mode restored');
libxml_use_internal_errors($previousLibxml);
echo "Grouped CAMT and Unicredit checks passed\n";
