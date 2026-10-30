<?php

/**
 * php acc/tests/InvoiceFamilies.php — групи, исторически срез и обобщения.
 */
define('INVOICE_REPORT_FIXTURE_ONLY', true);
require __DIR__ . '/InvoicesByContragent.php';

function familyInvoice($kind, $id, $amount, $paid, $date, $due, $origin = null)
{
    InvoiceTestData::invoice($kind, $id, 'Invoice family', $due, $amount, $paid, $date);
    $class = $kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
    $note = end(InvoiceTestData::$records[$class]);
    if (!$origin) return $note;
    $base = null;
    foreach (InvoiceTestData::$records[$class] as $candidate) if ($candidate->id == $origin) $base = $candidate;
    $note->type = 'dc_note';
    $note->originId = $base->containerId;
    $payment = InvoiceTestData::$payments[$note->threadId][$note->containerId];
    unset(InvoiceTestData::$payments[$note->threadId]);
    $note->threadId = $base->threadId;
    InvoiceTestData::$payments[$base->threadId][$note->containerId] = $payment;

    return $note;
}

foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('no', 'yes') as $mo) {
        foreach (array('all', 'unpaid', 'overdue') as $status) {
            foreach (array('2026-10-08', '2026-10-18', '2026-10-28') as $cutoff) {
                InvoiceTestData::reset();
                foreach ($direction == 'all' ? array('out', 'in') : array($direction) as $kind) {
                    familyInvoice($kind, 1, 2000, 600, '2026-10-01', '2026-10-15');
                    familyInvoice($kind, 2, -500, 0, '2026-10-10', '2026-10-15', 1);
                    familyInvoice($kind, 3, 200, 0, '2026-10-20', '2026-11-15', 1);
                }
                $rec = reportRec($direction, $status);
                $rec->checkDate = $cutoff;
                $rec->fromDate = $status == 'all' ? null : '2027-01-01';
                $rec->accountingCorrections = $mo;
                dt::$date = '2026-12-31';
                $rows = $driver->run($rec, $data);
                $expectedCount = $status == 'overdue' && $cutoff == '2026-10-08' ? 0 : ($direction == 'all' ? 2 : 1);
                $case = "$direction/$mo/$status/$cutoff";
                check(count($rows) == $expectedCount, 'Една група, независимо от старото От дата и днешната дата: ' . $case);
                foreach ($rows as $detail) {
                    $expectedValue = $cutoff == '2026-10-08' ? 2000 : ($cutoff == '2026-10-18' ? 1500 : 1700);
                    $expectedBalance = $expectedValue - 600;
                    check(closeAmount($detail->invoiceValue, $expectedValue), 'Стойността включва само издадените известия: ' . $case);
                    $export = $driver->exportRow($rec, $detail);
                    check(closeAmount($export->paidAmount, 600), 'Плащането участва веднъж: ' . $case);
                    if ($status != 'all' || $mo == 'yes') check(closeAmount($export->invoiceCurrentSumm - $export->invoiceOverSumm, $expectedBalance), 'Точният общ остатък: ' . $case);
                    check(closeAmount($detail->invoiceFamilyOverdue, $cutoff == '2026-10-08' ? 0 : 900), 'ДИ с бъдещ падеж не увеличава просрочието: ' . $case);
                    if ($cutoff != '2026-10-08') {
                        check(count($detail->invoiceDocuments) == ($cutoff == '2026-10-18' ? 2 : 3), 'Пълният състав без бъдещи известия: ' . $case);
                        $row = $driver->verbal($rec, $detail);
                        check(strpos($row->invoiceNo, '0000000001') !== false && strpos($row->invoiceNo, '0000000002') !== false, 'Основание и известие в една клетка: ' . $case);
                        check(strpos((string)$row->invoiceValue, 'Общо за фактурата + известията към нея') !== false, 'Общата стойност е подсказка, без отделен ред: ' . $case);
                        check(strpos($export->invoiceDocuments, 'КИ 0000000002') !== false, 'Известието остава достъпно в експорта: ' . $case);
                    }
                }
            }
        }
    }
}

foreach (array('out', 'in') as $kind) {
    foreach (array('no', 'yes') as $mo) {
        InvoiceTestData::reset();
        familyInvoice($kind, 1, 5100, 5100, '2026-09-24', '2026-09-27');
        familyInvoice($kind, 2, 5723.58, 0, '2026-09-28', '2026-10-01');
        familyInvoice($kind, 3, -5100, 0, '2026-09-30', '2026-10-03', 2);
        familyInvoice($kind, 4, 1579.87, 0, '2026-10-08', '2026-11-08');
        $rec = reportRec($kind, 'unpaid');
        $rec->checkDate = '2026-10-28';
        $rec->accountingCorrections = $mo;
        $rows = $driver->run($rec, $data);
        check(count($rows) == 2 && closeAmount($rec->totalInvoiceNotPaydAll, 2203.45), 'КИ е при действителното основание, не при равната сума');
        check(closeAmount($rec->totalInvoiceOverDueAll, 623.58), 'КИ намалява просрочието на собствената си фактура');
        $rec->unpaid = 'overdue';
        $rows = $driver->run($rec, $data);
        check(count($rows) == 1 && reset($rows)->invoiceId == 2 && closeAmount($rec->totalInvoiceNotPaydAll, 623.58), 'Просроченият остатък е след собственото КИ');
        InvoiceTestData::reset();
        familyInvoice($kind, 1, 100, 100, '2026-09-01', '2026-09-10');
        familyInvoice($kind, 2, -20, 0, '2026-10-01', '2026-10-05', 1);
        $rec->unpaid = 'unpaid';
        $rows = $driver->run($rec, $data);
        check(count($rows) == 1 && closeAmount(reset($rows)->invoiceValue, 80)
            && closeAmount($rec->totalInvoicePayoutAll, 100) && closeAmount($rec->totalInvoiceOverPaidAll, -20), 'Платена основа с последващо КИ е кредитен остатък, без повторно плащане');
        InvoiceTestData::reset();
        familyInvoice($kind, 1, 100, 0, '2026-09-01', '2026-09-10');
        familyInvoice($kind, 2, -100, 0, '2026-10-01', '2026-10-05', 1);
        check(!$driver->run($rec, $data), 'Изцяло кредитирана фактура отпада от неплатени');
    }
}

// КИ погасява стария падеж; бъдещото ДИ не прави групата просрочена.
foreach (array('out', 'in') as $kind) {
    foreach (array('no', 'yes') as $mo) {
        InvoiceTestData::reset();
        familyInvoice($kind, 1, 100, 0, '2026-09-01', '2026-09-10');
        familyInvoice($kind, 2, -100, 0, '2026-10-01', '2026-10-05', 1);
        familyInvoice($kind, 3, 50, 0, '2026-10-02', '2026-11-05', 1);
        $rec = reportRec($kind, 'unpaid');
        $rec->accountingCorrections = $mo;
        $rows = $driver->run($rec, $data);
        $detail = reset($rows);
        check(closeAmount($detail->invoiceFamilyOverdue, 0), 'Погасеният с КИ стар падеж не остава просрочен');
        check(strpos($driver->verbal($rec, $detail)->dueDate, 'фактурата е просрочена') === false, 'Подсказката следва остатъка след собственото КИ');
        check($driver->exportRow($rec, $detail)->dueDateStatus === '', 'CSV не маркира бъдещото ДИ като просрочено');
        $rec->unpaid = 'overdue';
        check(!$driver->run($rec, $data), 'Групата с единствен бъдещ остатък отпада от Просрочени');
        InvoiceTestData::reset();
        familyInvoice($kind, 1, 100, 0, '2026-09-01', '');
        check(!$driver->run($rec, $data), 'Липсващият падеж не означава просрочие');
    }
}

// Периодът избира КИ; основата и по-старите известия са само контекст.
foreach (array('out', 'in') as $kind) {
    InvoiceTestData::reset();
    familyInvoice($kind, 1, 100, 20, '2026-09-01', '2026-09-10');
    familyInvoice($kind, 2, -10, 0, '2026-09-15', '2026-09-20', 1);
    familyInvoice($kind, 3, -30, 0, '2026-10-01', '2026-10-05', 1);
    $rec = reportRec($kind, 'all');
    $rec->fromDate = '2026-10-01';
    $rows = $driver->run($rec, $data);
    $detail = reset($rows);
    check(count($rows) == 1 && count($detail->invoiceDocuments) == 3, 'Известие в периода показва цялата история на фактурата до датата');
    check(closeAmount($rec->totalInvoiceValueAll, -30) && closeAmount($detail->invoiceFamilyTotal, 60), 'Помощните документи не променят оборота за периода');
    check(!empty($detail->invoiceDocuments[0]->invoiceContextOnly) && !empty($detail->invoiceDocuments[1]->invoiceContextOnly), 'Основата и старото КИ са изрично помощни');
    check(strpos($driver->verbal($rec, $detail)->invoiceNo, 'invoice-report-context') !== false, 'Помощните документи се различават визуално');
}

// МО към единствената фактурна група се отчита веднъж, дори тя да има известия.
foreach (array('out', 'in') as $kind) {
    InvoiceTestData::reset();
    familyInvoice($kind, 1, 100, 20, '2026-10-01', '2026-10-05');
    familyInvoice($kind, 2, -10, 0, '2026-10-02', '2026-10-05', 1);
    familyInvoice($kind, 3, 15, 0, '2026-10-03', '2026-11-05', 1);
    articleFixture($kind, 1, 1387, 25, '2026-10-04');
    $rec = reportRec($kind, 'unpaid');
    $rec->accountingCorrections = 'yes';
    $rows = $driver->run($rec, $data);
    $detail = reset($rows);
    check(count($rows) == 1 && closeAmount($rec->totalAccountingCorrectionAll, 25), 'МО1387 се приспада веднъж от фактурата с известия');
    check(closeAmount($rec->totalInvoiceNotPaydAll, 60) && closeAmount($rec->totalInvoiceOverDueAll, 45), 'МО запазва точния остатък и бъдещия падеж на ДИ');
    $before = serialize($detail);
    $export = $detail;
    $method = new ReflectionMethod('acc_reports_InvoicesByContragent', 'on_AfterGetExportRec');
    $method->setAccessible(true);
    $method->invokeArgs(null, array($driver, &$export, $rec, $detail, null));
    check(serialize($detail) === $before && is_array($detail->invoiceDocuments), 'Експортът не променя групата в запазения резултат');
    check(closeAmount($export->invoiceValueBaseCurr - $export->paidAmount - $export->accountingCorrection, $export->invoiceCurrentSumm - $export->invoiceOverSumm), 'Експортът на групата се затваря до цент');
}

// Порциите и блоковете пазят групите цели и дават същите записи като общото изчисление.
foreach (array('no', 'yes') as $mo) {
    foreach (array('all', 'unpaid', 'overdue') as $status) {
        InvoiceTestData::reset();
        foreach (array('out', 'in') as $kind) {
            familyInvoice($kind, 1, 2000, 600, '2026-10-01', '2026-10-05');
            familyInvoice($kind, 2, -500, 0, '2026-10-02', '2026-10-05', 1);
            familyInvoice($kind, 3, 200, 0, '2026-10-03', '2026-11-05', 1);
            familyInvoice($kind, 4, 100, 0, '2026-10-01', '2026-10-05');
            $class = $kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
            $dealClass = $kind == 'out' ? 'sales_Sales' : 'purchase_Purchases';
            end(InvoiceTestData::$records[$class])->folderId = 2;
            end(InvoiceTestData::$records[$class])->contragentName = 'Second company';
            end(InvoiceTestData::$records[$dealClass])->folderId = 2;
        }
        $rec = reportRec('all', $status);
        $rec->accountingCorrections = $mo;
        $rec->fromDate = '2026-10-02';
        $fullRec = clone $rec;
        $full = $driver->calculateResult($fullRec);
        $job = (object) array('settings' => clone $rec, 'generation' => 'family-' . $mo . $status,
            'filterHash' => acc_reports_InvoicesByContragent::getResultFilterHash($rec), 'cursor' => 0,
            'groups' => array(), 'totals' => array(), 'invoiceTotals' => array());
        foreach (acc_reports_InvoiceResult::makePlan($rec, 1) as $folders) {
            $part = clone $rec;
            $part->contragent = '|' . implode('|', $folders) . '|';
            $part->_invoiceResultFolders = true;
            $partData = $driver->calculateResult($part);
            acc_reports_InvoiceResult::appendBatch($job, $part, $partData);
            $job->cursor++;
        }
        $stored = acc_reports_InvoiceResult::finish($job);
        $restored = acc_reports_InvoiceResult::readRange($stored, 0, $stored->invoiceRowCount);
        check($restored == $full->recs, 'Групите с КИ/ДИ са 1:1 при общо и порционно изчисление: ' . $mo . '/' . $status);
        foreach ($restored as $key => $detail) {
            $position = array_search($key, array_keys($restored), true);
            $page = acc_reports_InvoiceResult::readRange($stored, $position, 1);
            check($page == array($key => $detail), 'Страница от един ред запазва цялата фактура с известията');
        }
        foreach ($job->totals as $field => $amount) check(closeAmount($amount, $fullRec->{$field} ?? 0), 'Порционните обобщения съвпадат до цент: ' . $field);
    }
}

foreach (array('all', 'unpaid', 'overdue') as $status) {
    $rec = reportRec('all', $status);
    $rec->fromDate = '2027-01-01';
    $form = new InvoiceDateForm($rec);
    $driver->prepareForm($form);
    check(($status == 'all') === !isset($form->fieldParams['fromDate']), 'Началната дата е поле само за Всички');
    $head = array('title' => array('val' => 'AI'));
    $handled = InvoiceReportTest::on_AfterGetFieldForLetterHead($driver, new embed_Manager(), $head, $rec, new stdClass());
    check($handled === false && isset($head['fromDate']) === ($status == 'all'), 'Базовият обработчик не връща премахнатата начална дата');
    check(strpos(strip_tags($head['checkDate']['name']), $status == 'all' ? 'До дата' : 'Към дата') !== false, 'Ясно наименование на крайната дата');
    $hash = acc_reports_InvoicesByContragent::getResultFilterHash($rec);
    $rec->fromDate = '2020-01-01';
    check(($hash === acc_reports_InvoicesByContragent::getResultFilterHash($rec)) === ($status != 'all'), 'Игнорираната начална дата не влияе на отпечатъка');
}

// При планирането приключената преди среза сделка отпада; бъдещо приключване не отпада.
InvoiceTestData::reset();
familyInvoice('out', 1, 100, 0, '2020-01-01', '2020-01-10');
familyInvoice('out', 2, 200, 0, '2020-01-01', '2020-01-10');
InvoiceTestData::$records['sales_Invoices'][1]->folderId = 2;
InvoiceTestData::$records['sales_Sales'][0]->state = InvoiceTestData::$records['sales_Sales'][1]->state = 'closed';
InvoiceTestData::$records['sales_Sales'][0]->closedOn = '2026-10-01';
InvoiceTestData::$records['sales_Sales'][1]->closedOn = '2026-10-20';
$rec = reportRec('out', 'unpaid');
$rec->checkDate = '2026-10-08';
$rec->fromDate = '2026-01-01';
check(acc_reports_InvoiceResult::makePlan($rec) === array(array(2)), 'Планът отсява приключените към датата, а не към днес');

dt::$date = '2026-10-29';
echo "OK: {$checks} invoice family checks\n";
