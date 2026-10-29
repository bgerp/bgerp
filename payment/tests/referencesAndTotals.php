<?php
/**
 * php payment/tests/referencesAndTotals.php
 * Синтетични проверки на банкови референции и контроли, без база данни.
 * @copyright 2026 Experta EOOD
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/statusAndPreview.php';

$referenceXml = str_replace('<TxDtls>', '<TxDtls><Refs><AcctSvcrRef>Bank-001</AcctSvcrRef><EndToEndId>Invoice-1</EndToEndId></Refs>', $xml);
$referenceRec = payment_ParserIso20022::getRecs($referenceXml)->recs[0];
check($referenceRec->bankReference === 'CAMT:EUR:N:Bank-001', 'bank servicer reference read');
$entryRef = str_replace('<Ntry>', '<Ntry><AcctSvcrRef>Bank-001</AcctSvcrRef>', $xml);
check(payment_ParserIso20022::getRecs($entryRef)->recs[0]->bankReference === $referenceRec->bankReference, 'entry and transaction reference use same identity');
foreach (array('NOTPROVIDED', 'Not Provided', 'NONREF', 'NOTAVAILABLE', 'N/A', 'UNKNOWN', '000', '', str_repeat('x', 101)) as $ref) {
    $result = payment_ParserIso20022::getRecs(str_replace('Bank-001', $ref, $referenceXml));
    check(!isset($result->recs[0]->bankReference), 'placeholder or oversized bank reference retains legacy path');
}
$customerRef = str_replace('AcctSvcrRef', 'TxId', $referenceXml);
check(!isset(payment_ParserIso20022::getRecs($customerRef)->recs[0]->bankReference), 'customer reference and TxId are not bank servicer identity');
$groupRef = str_replace('<Ntry>', '<Ntry><AcctSvcrRef>Group-1</AcctSvcrRef>', $grouped);
check(!isset(payment_ParserIso20022::getRecs($groupRef)->recs[0]->bankReference), 'group entry reference not shared by all details');
$groupRef = str_replace(array('<TxDtls><Amt ', '<TxDtls><AmtDtls>'), array('<TxDtls><Refs><AcctSvcrRef>Part-1</AcctSvcrRef></Refs><Amt ', '<TxDtls><Refs><AcctSvcrRef>Part-2</AcctSvcrRef></Refs><AmtDtls>'), $groupRef);
$parts = payment_ParserIso20022::getRecs($groupRef)->recs;
check(count($parts) === 2 && $parts[0]->bankReference !== $parts[1]->bankReference, 'grouped details have distinct references');
$duplicateRefs = payment_ParserIso20022::getRecs(str_replace('Part-2', 'Part-1', $groupRef))->recs;
check(!isset($duplicateRefs[0]->bankReference) && !isset($duplicateRefs[1]->bankReference), 'nonunique detail references use old comparison without losing parts');
$refReversal = payment_ParserIso20022::getRecs(str_replace('<Ntry>', '<Ntry><RvslInd>true</RvslInd>', $referenceXml))->recs[0];
check($refReversal->bankReference !== $referenceRec->bankReference, 'reversal reference scope is separate');
$escapedRef = payment_ParserIso20022::getRecs(str_replace('Bank-001', 'A&apos;&quot;\\&lt;script&gt;[#x#]', $referenceXml))->recs[0];
check($escapedRef->bankReference === 'CAMT:EUR:N:A\'"\\<script>[#x#]', 'reference text preserved as data');

function totalsFixture($source, $controls) { return str_replace('</Acct>', '</Acct>' . $controls, $source); }
$summary = '<TxsSummry><TtlNtries><NbOfNtries>1</NbOfNtries><Sum>123.4500</Sum><TtlNetNtryAmt>123.45</TtlNetNtryAmt><CdtDbtInd>CRDT</CdtDbtInd></TtlNtries><TtlCdtNtries><NbOfNtries>1</NbOfNtries><Sum>123.45</Sum></TtlCdtNtries><TtlDbtNtries><NbOfNtries>0</NbOfNtries><Sum>0</Sum></TtlDbtNtries></TxsSummry>';
foreach (array($xml, $xml52, $grouped) as $source) {
    $base = payment_ParserIso20022::getRecs($source);
    $result = payment_ParserIso20022::getRecs(totalsFixture($source, $summary));
    check(!$result->errors && $result->recs == $base->recs, 'counts and sums use original entries, including grouped CAMT');
}
$newSummary = str_replace('<TtlNetNtryAmt>123.45</TtlNetNtryAmt><CdtDbtInd>CRDT</CdtDbtInd>', '<TtlNetNtry><Amt>123.45</Amt><CdtDbtInd>CRDT</CdtDbtInd></TtlNetNtry>', $summary);
check(!payment_ParserIso20022::getRecs(totalsFixture($xml, $newSummary))->errors, 'new net amount representation');
$badControls = array(
    str_replace('<NbOfNtries>1', '<NbOfNtries>2', $summary),
    str_replace('<NbOfNtries>1', '<NbOfNtries>x', $summary),
    str_replace('<NbOfNtries>1', '<NbOfNtries>-1', $summary),
    str_replace('<Sum>123.4500', '<Sum>123.46', $summary),
    str_replace('<Sum>123.4500', '<Sum>123.451', $summary),
    str_replace('<Sum>123.4500', '<Sum>1e3', $summary),
    str_replace('<Sum>123.4500', '<Sum>99999999999999999999999999999999999', $summary),
    str_replace('<TtlNetNtryAmt>123.45', '<TtlNetNtryAmt>123.46', $summary),
    str_replace('<CdtDbtInd>CRDT', '<CdtDbtInd>DBIT', $summary),
    str_replace('<TtlDbtNtries><NbOfNtries>0', '<TtlDbtNtries><NbOfNtries>1', $summary),
    str_replace('<Sum>0', '<Sum>1', $summary),
    str_replace('<Sum>0</Sum>', '<Sum>0</Sum><Sum>1</Sum>', $summary),
);
foreach ($badControls as $index => $control) {
    $result = payment_ParserIso20022::getRecs(totalsFixture($xml, $control));
    check(!$result->recs && $result->errors && $result->skippedEntries === 1, 'control mismatch blocks entire statement ' . $index);
}
$zeroSummary = '<TxsSummry><TtlNtries><NbOfNtries>0</NbOfNtries><Sum>0</Sum></TtlNtries></TxsSummry>';
$empty = preg_replace('~<Ntry>.*?</Ntry>~', '', $xml);
check(!payment_ParserIso20022::getRecs(totalsFixture($empty, $zeroSummary))->errors, 'empty statement with zero totals');
check(payment_ParserIso20022::getRecs(totalsFixture($empty, $summary))->errors, 'missing entries detected by control');
$pendingSummary = str_replace(array('<NbOfNtries>1', '123.45'), array('<NbOfNtries>2', '246.90'), $summary);
$result = payment_ParserIso20022::getRecs(totalsFixture($mixedStatus, $pendingSummary));
check(count($result->recs) === 1 && !$result->errors, 'summary counts pending entries even though they are not imported');
$badEntry = str_replace('<Amt Ccy="EUR">123.45', '<Amt Ccy="EUR">invalid', $xml);
check(!payment_ParserIso20022::getRecs(totalsFixture($badEntry, $summary))->recs, 'invalid entry cannot be ignored when checking totals');
$partialControls = totalsFixture($xml, '<TxsSummry><TtlNtries><NbOfNtries>1</NbOfNtries></TtlNtries></TxsSummry>');
check(!payment_ParserIso20022::getRecs($partialControls)->errors, 'only provided control fields required');

function balanceFixture($code, $amount, $direction = 'CRDT', $currency = 'EUR', $date = '2026-10-26') {
    return '<Bal><Tp><CdOrPrtry><Cd>' . $code . '</Cd></CdOrPrtry></Tp><Amt Ccy="' . $currency . '">' . $amount . '</Amt><CdtDbtInd>' . $direction . '</CdtDbtInd><Dt><Dt>' . $date . '</Dt></Dt></Bal>';
}
$balances = balanceFixture('OPBD', '100.00') . balanceFixture('CLBD', '223.45');
check(!payment_ParserIso20022::getRecs(totalsFixture($xml, $balances))->errors, 'opening plus booked net equals closing');
check(!payment_ParserIso20022::getRecs(totalsFixture($mixedStatus, $balances))->errors, 'pending entries do not affect booked balances');
$debitBalances = balanceFixture('OPBD', '100') . balanceFixture('CLBD', '23.45', 'DBIT');
check(!payment_ParserIso20022::getRecs(totalsFixture(str_replace('CRDT', 'DBIT', $xml), $debitBalances))->errors, 'negative closing balance and debits');
check(!payment_ParserIso20022::getRecs(totalsFixture($xml, balanceFixture('OPBD', '9999999999.00') . balanceFixture('CLBD', '10000000122.45')))->errors, 'balance limit exceeds individual transfer limit');
foreach (array(str_replace('223.45', '223.46', $balances), str_replace('223.45', 'bad', $balances), $balances . balanceFixture('OPBD', '1')) as $bad) {
    $result = payment_ParserIso20022::getRecs(totalsFixture($xml, $bad));
    check(!$result->recs && $result->errors, 'bad or ambiguous balances block statement');
}
$incomparable = payment_ParserIso20022::getRecs(totalsFixture($xml, str_replace('Ccy="EUR"', 'Ccy="USD"', $balances)));
check(count($incomparable->recs) === 1 && $incomparable->warnings && !$incomparable->errors, 'incomparable balances explicitly unverified');
$pagination = '<StmtPgntn><PgNb>1</PgNb><LastPgInd>false</LastPgInd></StmtPgntn>';
$paged = payment_ParserIso20022::getRecs(totalsFixture($xml, $pagination . $badControls[0]));
check(count($paged->recs) === 1 && !$paged->errors && $paged->warnings, 'multi-page totals not incorrectly compared with current page');
$singlePage = payment_ParserIso20022::getRecs(totalsFixture($xml, str_replace('false', 'true', $pagination) . $badControls[0]));
check(!$singlePage->recs && $singlePage->errors, 'complete single page checked');
$prefixedTotals = preg_replace('/<(\/?)([A-Za-z][A-Za-z0-9]*)(?=[ >\/])/', '<$1n:$2', str_replace('xmlns=', 'xmlns:n=', totalsFixture($xml, $summary . $balances)));
check(!payment_ParserIso20022::getRecs($prefixedTotals)->errors, 'controls in prefixed namespace');

$badTotalXml = totalsFixture($xml, $badControls[0]);
preg_match('~<Stmt>.*?</Stmt>~', $xml, $validStatement);
$multiStatement = str_replace('</BkToCstmrStmt>', $validStatement[0] . '</BkToCstmrStmt>', $badTotalXml);
$result = payment_ParserIso20022::getRecs($multiStatement);
check(count($result->recs) === 1 && $result->errors && $result->skippedEntries === 1, 'independent statement remains readable after bad controls');
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array(fixture('bad-total.camt', $badTotalXml))), (object) array()) === false && count(bank_Register::$imports) === $before, 'email does not import statement with mismatching totals');
$before = count(bank_Register::$imports);
check($parser->process(new attachment_TestMime(array(fixture('mixed-totals.camt', $multiStatement))), (object) array()) === false && count(bank_Register::$imports) === $before + 1, 'email imports independent valid statement and leaves mail for review');
$badPreview = previewForm(fixture('bad-preview-total.camt', $badTotalXml), null, true);
check(!$badPreview->isSubmitted() && !$badPreview->rec->previewHash && !empty($badPreview->errors), 'control error cannot be acknowledged away in manual preview');

foreach (array('.50', '+.50', '+0.5000', '0000.50') as $decimal) {
    $source = str_replace('123.45', $decimal, $xml);
    $control = '<TxsSummry><TtlNtries><Sum>0.50</Sum></TtlNtries></TxsSummry>';
    check(!payment_ParserIso20022::getRecs(totalsFixture($source, $control))->errors, 'equivalent decimal syntax in controls ' . $decimal);
}
$bothNet = str_replace('</TtlNtries>', '<TtlNetNtry><Amt>123.45</Amt><CdtDbtInd>CRDT</CdtDbtInd></TtlNetNtry></TtlNtries>', $summary);
check(payment_ParserIso20022::getRecs(totalsFixture($xml, $bothNet))->errors, 'conflicting net representations rejected');
$repeatedNet = str_replace('<TtlNetNtryAmt>123.45</TtlNetNtryAmt>', '<TtlNetNtryAmt>123.45</TtlNetNtryAmt><TtlNetNtryAmt>1</TtlNetNtryAmt>', $summary);
check(payment_ParserIso20022::getRecs(totalsFixture($xml, $repeatedNet))->errors, 'repeated net amount rejected');
