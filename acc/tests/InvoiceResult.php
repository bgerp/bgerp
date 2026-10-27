<?php

/**
 * php acc/tests/InvoiceResult.php — порции, независими от периода, и снимки на части.
 */
require __DIR__ . '/InvoicesByContragent.php';

function resultFixtureInvoice($kind, $id, $folder, $name, $amount, $paid, $date = '2026-10-01')
{
    InvoiceTestData::invoice($kind, $id, $name, '2026-10-05', $amount, $paid, $date);
    end(InvoiceTestData::$records[$kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices'])->folderId = $folder;
    end(InvoiceTestData::$records[$kind == 'out' ? 'sales_Sales' : 'purchase_Purchases'])->folderId = $folder;
}

function emptyResultJob($rec, $generation)
{
    return (object) array('settings' => clone $rec, 'generation' => $generation,
        'filterHash' => acc_reports_InvoicesByContragent::getResultFilterHash($rec),
        'cursor' => 0, 'groups' => array(), 'totals' => array(), 'invoiceTotals' => array());
}

InvoiceTestData::reset();
foreach (array('out', 'in') as $kind) {
    resultFixtureInvoice($kind, 1, 1, 'Alpha', 100.005, 30, '2020-10-01');
    resultFixtureInvoice($kind, 2, 1, 'Alpha', 200.005, 150);
    resultFixtureInvoice($kind, 3, 1, 'Alpha', -25, 0);
    end(InvoiceTestData::$records[$kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices'])->type = 'dc_note';
    InvoiceTestData::combine($kind, array(2, 3, 1));
    InvoiceTestData::$records[$kind == 'out' ? 'sales_Sales' : 'purchase_Purchases'][0]->state = 'closed';
    articleFixture($kind, 1, $kind == 'out' ? 1386 : 1387, 60, '2026-10-02');
    resultFixtureInvoice($kind, 4, 2, 'Old name', 129.995, 100, '2021-10-01');
    resultFixtureInvoice($kind, 5, 2, 'New name', 200.005, 100);
    resultFixtureInvoice($kind, 6, 3, 'Same name', 150.005, 180);
    resultFixtureInvoice($kind, 7, 4, 'Same name', 50, 0);
    resultFixtureInvoice($kind, 8, 5, 'Zeta', 32.125, 2.115);
}
// Един МО с аналитични пера на две различни фирми: всяка получава само своята страна.
articleFixture('in', 4, 1400, 10, '2026-10-03');
articleFixture('in', 8, 1401, 0, '2026-10-03');
array_pop(InvoiceTestData::$records['acc_JournalDetails']);
$entry = end(InvoiceTestData::$records['acc_JournalDetails']);
$entry->creditAccId = 401;
$entry->creditItem2 = 20008;

$partitionCases = 0;
foreach (array('out', 'in', 'all') as $kind) {
    foreach (array('all', 'unpaid', 'overdue') as $status) {
        foreach (array('no', 'yes') as $mo) {
            foreach (array(null, '2026-01-01') as $from) {
                foreach (array(null, 'bank', 'cash') as $method) {
                    $rec = reportRec($kind, $status);
                    $rec->fromDate = $from;
                    $rec->checkDate = '2026-10-08';
                    $rec->accountingCorrections = $mo;
                    $rec->paymentType = $method;
                    $fullRec = clone $rec;
                    $full = $driver->calculateResult($fullRec);
                    $job = emptyResultJob($rec, 'partition-' . ++$partitionCases);
                    $plan = acc_reports_InvoiceResult::makePlan($rec, 1);
                    $foldersToBatch = array();
                    foreach ($plan as $batchId => $folders) {
                        foreach ($folders as $folder) $foldersToBatch[$folder] = $batchId;
                        $batchRec = clone $rec;
                        $batchRec->contragent = '|' . implode('|', $folders) . '|';
                        $batchRec->_invoiceResultFolders = true;
                        $batch = $driver->calculateResult($batchRec);
                        acc_reports_InvoiceResult::appendBatch($job, $batchRec, $batch);
                        $job->cursor++;
                    }
                    check($foldersToBatch[3] === $foldersToBatch[4], 'Еднаквите имена запазват старото общо обобщение');
                    $snapshot = acc_reports_InvoiceResult::finish($job);
                    $rows = acc_reports_InvoiceResult::readRange($snapshot, 0, $snapshot->invoiceRowCount);
                    check(array_keys($rows) === array_keys($full->recs), 'Еднакъв ред на фирмите и фактурите при порции и малка справка');
                    $dates = array();
                    $lastGroup = null;
                    foreach ($rows as $row) {
                        $group = $row->invoiceGroup ?? $row->contragent ?? '';
                        check($group === $lastGroup || !isset($dates[$group]), 'Изходящи и входящи остават в отделни последователни групи');
                        $date = $row->invoiceDate ?? '';
                        check(!isset($dates[$group]) || $dates[$group] >= $date, 'Най-новите фактури са първи във всяка група, включително с МО1387');
                        $dates[$group] = $date;
                        $lastGroup = $group;
                    }
                    $fullRows = $full->recs;
                    ksort($rows);
                    ksort($fullRows);
                    check($rows == $fullRows, 'Същите редове, плащания, МО и обобщения след разделяне: ' . json_encode(array($kind, $status, $mo, $from, $method)));
                    foreach ($job->totals as $field => $amount) {
                        check(closeAmount($amount, $fullRec->{$field} ?? 0), 'Същият общ резултат след разделяне: ' . $field);
                    }
                    foreach ($rows as $key => $row) {
                        check($driver->exportRow($rec, $row) == $driver->exportRow($fullRec, $fullRows[$key]), 'Същият експорт след разделяне');
                        check($driver->verbal($rec, $row) == $driver->verbal($fullRec, $fullRows[$key]), 'Същите надписи, цветове и обобщения след разделяне');
                    }
                    check($snapshot->invoiceTotals == ($full->invoiceTotals ?? array()), 'Същите отделни суми за входящи и изходящи');
                }
            }
        }
    }
}

// Граници вътре във фирма и между два блока: не четем другите страници.
$rec = reportRec('all', 'all');
$job = emptyResultJob($rec, 'pages');
$data = (object) array('recs' => array());
for ($i = 0; $i < 1001; $i++) {
    $data->recs['row|' . $i] = (object) array('contragent' => 'Large company', 'className' => $i % 2 ? 'sales_Invoices' : 'purchase_Invoices');
}
acc_reports_InvoiceResult::appendBatch($job, $rec, $data);
$snapshot = acc_reports_InvoiceResult::finish($job);
core_Permanent::$reads = array();
$page = acc_reports_InvoiceResult::readRange($snapshot, 197, 30, $ends);
check(array_keys($page) === array_keys(array_slice($data->recs, 197, 30, true)), 'Страница през граница на два блока');
check(count(core_Permanent::$reads) == 2, 'Само двата нужни блока се четат за страницата');
check(!array_intersect_key($page, $ends), 'Общото за фирмата не се появява преди истинския ѝ край');
$page = acc_reports_InvoiceResult::readRange($snapshot, 990, 30, $ends);
check(count($page) === 11 && $ends['row|1000'] === 2, 'Последната непълна страница пази обобщението за двата вида');
$saved = core_Permanent::$values[$snapshot->invoiceBlocks[0]->key];
core_Permanent::remove($snapshot->invoiceBlocks[0]->key);
$missingRejected = false;
try { acc_reports_InvoiceResult::readRange($snapshot, 0, 30); } catch (RuntimeException $e) { $missingRejected = true; }
check($missingRejected, 'Липсващ блок се съобщава, без частични суми или мълчаливо пропуснати редове');
core_Permanent::set($snapshot->invoiceBlocks[0]->key, $saved);

// Реален цикъл start/checkpoint/publish с повече от една порция в двата режима.
foreach (array('no', 'yes') as $mo) {
    InvoiceTestData::reset();
    core_Permanent::$values = core_CallOnTime::$calls = array();
    for ($i = 1; $i <= 2100; $i++) resultFixtureInvoice('out', $i, $i, 'Company ' . $i, 10.005, 3.335);
    articleFixture('out', 1, 1387, 1, '2026-10-02');
    $rec = reportRec('all', 'all');
    $rec->accountingCorrections = $mo;
    $rec->checkDate = '2026-10-08';
    $fullRec = clone $rec;
    $full = $driver->calculateResult($fullRec);
    $rec->id = 9000;
    $rec->state = 'active';
    $beforeBulk = count(deals_Helper::$bulkThreads);
    $rec->data = acc_reports_InvoiceResult::start($rec, acc_reports_InvoicesByContragent::getResultFilterHash($rec));
    check($rec->data->invoiceResultState === 'pending' && !$rec->data->recs, 'Тежката справка се отлага без стари или непълни редове: ' . $mo);
    check(count(deals_Helper::$bulkThreads) === $beforeBulk, 'При Запис не се разпределят плащанията на тежката справка');
    frame2_Reports::$records[$rec->id] = clone $rec;
    $same = clone $rec;
    $same->data = acc_reports_InvoiceResult::start($same, acc_reports_InvoicesByContragent::getResultFilterHash($same));
    check($same->resultGeneration === $rec->resultGeneration, 'Повторно Обновяване продължава същата генерация');
    $worker = new acc_reports_InvoiceResult();
    $worker->callback_Continue((object) array('generation' => $rec->resultGeneration, 'cursor' => 0, 'attempt' => 0));
    $progress = acc_reports_InvoiceResult::progress($rec->data);
    check($progress->done === 5, 'Петте малки порции не чакат пет отделни cron изпълнения');
    $worker->callback_Continue((object) array('generation' => $rec->resultGeneration, 'cursor' => 0, 'attempt' => 1));
    check(acc_reports_InvoiceResult::progress($rec->data)->done === 5, 'Повторен завършен callback не дублира порция');
    $published = frame2_Reports::$records[9000];
    check($published->data->invoiceResultState === 'complete' && $published->data->invoiceRowCount === 2100, 'Публикуван е целият резултат');
    check(!isset($published->_invoiceResultReady), 'Самият job не влиза в резултата или историята на версиите');
    check($driver->exportRows($published) == $full->recs, 'Пълният експорт е същият след фоново изчисление: ' . $mo);
    check(closeAmount($published->totalInvoiceValueAll, $fullRec->totalInvoiceValueAll), 'Общата сума е точният сбор след фоново изчисление');
    check(closeAmount($published->totalAccountingCorrectionAll, $fullRec->totalAccountingCorrectionAll ?? 0), 'МО1387 не се губи или повтаря');
    check(strlen(serialize($published->data)) < strlen(serialize($full)) / 3, 'Записът на справката съдържа малък индекс, без целия резултат');
    $identical = clone $published;
    $identical->data = acc_reports_InvoiceResult::start($identical, acc_reports_InvoicesByContragent::getResultFilterHash($identical));
    frame2_Reports::$records[9000] = $identical;
    $worker->callback_Continue((object) array('generation' => $identical->resultGeneration, 'cursor' => 0, 'attempt' => 0));
    $identicalResult = frame2_Reports::$records[9000];
    check(serialize($identicalResult->data) === serialize($published->data)
        && $identicalResult->resultGeneration === $published->resultGeneration, 'Еднакъв резултат запазва снимката и не създава разлика само заради новата задача');
    $worker->callback_Cleanup(9000);
    check($driver->exportRows($identicalResult) == $full->recs, 'Почистване на повторно изчисление не изтрива споделената стара снимка');
    // Нова настройка обезсилва callback на старото изчисление.
    $changed = clone $rec;
    $changed->accountingCorrections = $mo == 'yes' ? 'no' : 'yes';
    $changed->data = acc_reports_InvoiceResult::start($changed, acc_reports_InvoicesByContragent::getResultFilterHash($changed));
    frame2_Reports::$records[9000] = $changed;
    $worker->callback_Continue((object) array('generation' => $rec->resultGeneration, 'cursor' => 0, 'attempt' => 9));
    check(frame2_Reports::$records[9000]->resultGeneration === $changed->resultGeneration
        && frame2_Reports::$records[9000]->data->invoiceResultState === 'pending', 'Стара генерация не връща стар филтър или резултат');
    $worker->callback_Cleanup(9000);
    check($driver->exportRows($published) == $full->recs, 'Почистването запазва снимка, използвана от стара версия');
    InvoiceTestData::$records['frame2_ReportVersions'] = array();
    $worker->callback_Cleanup(9000);
    check(core_Permanent::get($published->data->invoiceBlocks[0]->key) === null, 'Почистват се само части без текуща справка или запазена версия');
    check(core_Permanent::get('invoiceResult|' . $changed->resultGeneration . '|job') !== null, 'Незавършената текуща генерация остава запазена');
    // Прекъсване след първата записана порция: възобновяваме от нея, не отначало.
    cls::$invoiceCallsUntilFailure = 1;
    try {
        $worker->callback_Continue((object) array('generation' => $changed->resultGeneration, 'cursor' => 0, 'attempt' => 0));
    } catch (RuntimeException $e) {}
    $checkpoint = core_Permanent::get('invoiceResult|' . $changed->resultGeneration . '|job');
    check($checkpoint->state === 'failed' && $checkpoint->cursor === 1, 'При прекъсване остава точният последен checkpoint');
    cls::$invoiceCallsUntilFailure = null;
    $retry = clone $changed;
    $retry->data = acc_reports_InvoiceResult::start($retry, acc_reports_InvoicesByContragent::getResultFilterHash($retry));
    check($retry->resultGeneration === $changed->resultGeneration, 'Повторението запазва завършените порции');
    $worker->callback_Continue((object) array('generation' => $retry->resultGeneration, 'cursor' => 1, 'attempt' => 1));
    $retried = frame2_Reports::$records[9000];
    check($retried->data->invoiceResultState === 'complete' && $retried->data->invoiceRowCount === 2100, 'Възстановяването не дублира или губи редове');
}

// Преминаване през полунощ не сменя датата/просрочието между порциите.
InvoiceTestData::reset();
$savedToday = dt::$date;
dt::$date = '2026-10-08';
resultFixtureInvoice('out', 1, 1, 'Midnight', 100, 0);
InvoiceTestData::$records['sales_Invoices'][0]->dueDate = '2026-10-08';
$rec = reportRec('out', 'overdue');
$rec->id = 9003;
$rec->state = 'active';
$rec->accountingCorrections = 'yes';
$rec->checkDate = '2026-10-08';
$rec->useTodayAsCheckDate = 'yes';
$rec->data = acc_reports_InvoiceResult::start($rec, acc_reports_InvoicesByContragent::getResultFilterHash($rec));
frame2_Reports::$records[9003] = $rec;
frame2_Reports::$records[9003]->state = 'closed';
frame2_Reports::$logs[9003] = array(array('msg' => 'Предишна диагностика', 'time' => '2026-10-08 10:00:00'));
dt::$date = '2026-10-09';
$worker->callback_Continue((object) array('generation' => $rec->resultGeneration, 'cursor' => 0, 'attempt' => 0));
$published = frame2_Reports::$records[9003];
check($published->checkDate === '2026-10-08' && $published->data->invoiceRowCount === 0, 'Датата и просрочието остават тези от началото на фоновото изчисление');
check($published->log === frame2_Reports::$logs[9003], 'Фоновото завършване запазва предишната кратка диагностика');
check($published->state === 'closed', 'Завършването на започнато изчисление не променя състоянието на справката');
dt::$date = $savedToday;

echo "OK: {$partitionCases} partition comparisons; {$checks} total checks\n";

// Границата се проверява с ограничен COUNT, без десериализация/разпределение.
InvoiceTestData::reset();
$candidate = (object) array('id' => 1, 'folderId' => 1, 'contragentName' => 'Count only',
    'number' => 1, 'state' => 'active', 'date' => '2026-10-01', 'paymentType' => 'bank');
InvoiceTestData::$records['sales_Invoices'] = array_fill(0, 30000, $candidate);
$rec = reportRec('all', 'all');
$rec->id = 9001;
$beforeBulk = count(deals_Helper::$bulkThreads);
check(!acc_reports_InvoiceResult::needsBackground($rec), 'Точно 30 000 кандидати остават в досегашния режим');
InvoiceTestData::$records['sales_Invoices'][] = $candidate;
check(acc_reports_InvoiceResult::needsBackground($rec), '30 001 кандидати включват новия режим');
$rec->paymentType = 'cash';
check(!acc_reports_InvoiceResult::needsBackground($rec), 'Броенето отчита избрания начин на плащане');
check(count(deals_Helper::$bulkThreads) === $beforeBulk, 'Броенето не изчислява плащания');

$rec->paymentType = null;
$rec->state = 'active';
$rec->data = (object) array('recs' => array('old' => 'стар огромен резултат'));
frame2_Reports::$records[9001] = $rec;
Request::$values['id'] = 9001;
frame2_Reports::$rightsChecks = array();
$versionCount = count(InvoiceTestData::$records['frame2_ReportVersions'] ?? array());
list($status, $redirect) = $driver->beforeAction('refresh');
check($status === false && isset($redirect->url), 'Ръчното тежко Обновяване се връща без стандартния синхронен refresh');
check(count(frame2_Reports::$rightsChecks) === 2, 'Бързото Обновяване проверява общите права и правата за конкретната справка');
check(frame2_Reports::$records[9001]->data->invoiceResultState === 'pending', 'Обновяването записва малък междинен резултат');
check(count(InvoiceTestData::$records['frame2_ReportVersions'] ?? array()) === $versionCount, 'Преди връщането към браузъра не се сравнява старата огромна версия');
Request::$values = array();
$rec->id = 9002;
$rec->refreshData = true;
$driver->beforeSave($rec);
check($rec->refreshData === false && $rec->data->invoiceResultState === 'pending', 'Записът на тежка редакция не стартира стандартното синхронно преизчисление');
$rec->id = 9004;
$rec->resultGeneration = null;
$rec->_invoiceResultEdit = true;
$rec->refreshData = false;
$driver->beforeSave($rec);
check($rec->data->invoiceResultState === 'pending' && !empty($rec->resultGeneration), 'Първият запис от бърза редакция преобразува стар голям резултат и без промяна на филтъра');
$rec->id = 9005;
check($driver->tryToAutoRefresh($rec) === false
    && frame2_Reports::$records[9005]->data->invoiceResultState === 'pending', 'Автоматичното обновяване също не създава междинна счетоводна версия');
check(count(deals_Helper::$bulkThreads) === $beforeBulk, 'Бързите действия не извличат и не изчисляват плащания');

// Истинският алгоритъм от ядрото (само прочит) с две различни фирми и финансово
// прехвърляне. Общото зареждане и зареждането по фирми трябва да дадат едно и също.
$allocatorSource = file_get_contents(dirname(__DIR__, 2) . '/deals/Helper.class.php');
$allocatorSource = str_replace('abstract class deals_Helper', 'abstract class InvoiceResultCoreAllocator', $allocatorSource);
eval('?>' . $allocatorSource);
class currency_Currencies {
    public static function getCodeById($id) { return is_numeric($id) ? 'EUR' : $id; }
}
class currency_CurrencyRates { public static function getRate($date, $from, $to) { return 1; } }
class InvoiceResultTransferPayment {
    public function getPaymentData($rec) {
        return (object) array('currencyId' => 'EUR', 'dealCurrencyId' => 'EUR');
    }
}
$calculatePayments = new ReflectionMethod('InvoiceResultCoreAllocator', 'calcInvoicePayments');
$calculatePayments->setAccessible(true);
$invoiceA = (object) array('containerId' => 1, 'date' => '2026-10-01', 'dueDate' => '2026-10-05',
    'dealValue' => 200, 'discountAmount' => 0, 'vatAmount' => 0, 'rate' => 1, 'currencyId' => 'EUR', 'type' => 'invoice');
$invoiceB = clone $invoiceA;
$invoiceB->containerId = 2;
$invoiceB->dealValue = 150;
$records = array(1 => $invoiceA, 2 => $invoiceB);
$documentA = (object) array('containerId' => 31, 'amount' => 60, 'amountDeal' => 60, 'isReverse' => 'no', 'valior' => '2026-10-02');
$documentB = clone $documentA;
$documentB->containerId = 32;
$paymentsA = array(array('findeals_DebitDocuments', new InvoiceResultTransferPayment(), $documentA));
$paymentsB = array(array('findeals_CreditDocuments', new InvoiceResultTransferPayment(), $documentB));
$links = array(31 => array((object) array('containerId' => 1, 'amount' => 60, 'currencyId' => 'EUR')),
    32 => array((object) array('containerId' => 2, 'amount' => 60, 'currencyId' => 'EUR')));
foreach (array(false, true) as $reverse) {
    $documentA->isReverse = $documentB->isReverse = $reverse ? 'yes' : 'no';
    foreach (array(1 => $paymentsA, 2 => $paymentsB) as $id => $payments) {
        $all = $calculatePayments->invoke(null, array($id), $records, $payments, $links, array(), false, false);
        $localLinks = array_intersect_key($links, array($id == 1 ? 31 : 32 => true));
        $part = $calculatePayments->invoke(null, array($id), array($id => $records[$id]), $payments, $localLinks, array(), false, false);
        check($all == $part, 'Прехвърляне между различни фирми: еднакво реално разпределение общо и по фирми');
        check(closeAmount($part[$id]->payout, $reverse ? -60 : 60), 'Прехвърлянето и сторното имат правилната сума и знак');
    }
}
echo "OK: cross-company transfers and 30000/30001 boundary; {$checks} total checks\n";
