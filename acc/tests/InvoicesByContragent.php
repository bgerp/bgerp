<?php

/**
 * Проверки без база: php acc/tests/InvoicesByContragent.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class frame2_driver_Proto extends stdClass {}
class core_App { public static function setTimeLimit($seconds) {} }
class core_Debug {
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class dt {
    public static function today($unused = true) { return '2026-10-15'; }
    public static function now() { return '2026-10-15 12:00:00'; }
    public static function mysql2verbal($date, $mask) { return date($mask, strtotime($date)); }
}
class Mode { public static function is($name, $value = null) { return false; } }
class acc_Setup { public static function getEurozoneDate() { return '2026-01-01'; } }
class acc_Periods {
    public static function getBaseCurrencyCode($date) { return $date < '2026-01-01' ? 'BGN' : 'EUR'; }
}
class core_Type {
    public static function getByName($name) { return new InvoiceTestType($name); }
}
class InvoiceTestType {
    private $name;
    public function __construct($name) { $this->name = $name; }
    public function toVerbal($value) {
        return strpos($this->name, 'double') === 0 ? number_format($value, 2, '.', '') : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
class core_FieldSet {
    public $fields = array();
    public function FLD($name, $type, $params) { $this->fields[$name] = $type; }
    public function FNC($name, $type, $params) { $this->FLD($name, $type, $params); }
}
class cls {
    public static function load($name, $silent = false) { return true; }
    public static function get($name) {
        return $name == 'core_FieldSet' ? new core_FieldSet() : new InvoiceTestType($name);
    }
}
class core_ET {
    private $content;
    public function __construct($content) { $this->content = $content; }
    public function getContent() { return $this->content; }
    public function removeBlocks() {}
    public function removePlaces() {}
}
class ht {
    public static function styleNumber($verbal, $amount) { return $verbal; }
    public static function createLinkRef($title, $url) { return $title; }
    public static function createHint($title, $hint, $type) { return $title; }
    public static function createElement($tag, $attr, $content) {
        return new core_ET('<' . $tag . ' class="' . ($attr['class'] ?? '') . '">' . $content . '</' . $tag . '>');
    }
}
class keylist {
    public static function toArray($value) { return array_filter(explode('|', $value ?? '')); }
}
class arr {
    public static function make($value, $keys = false) {
        $items = is_array($value) ? $value : array_map('trim', explode(',', $value));
        return $keys ? array_combine($items, $items) : $items;
    }
    public static function extractValuesFromArray($recs, $field) {
        $values = array();
        foreach ($recs as $rec) $values[$rec->{$field}] = $rec->{$field};
        return $values;
    }
    public static function sortObjects(&$recs, $field, $direction, $mode = null) {
        uasort($recs, function ($a, $b) use ($field) { return strcmp($a->{$field}, $b->{$field}); });
    }
}
function tr($value) { return $value; }
function countR($value) { return is_countable($value) ? count($value) : 0; }
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($value, $encoding) { return strtolower($value); }
}

class InvoiceTestQuery {
    private $recs;
    public function __construct($recs) { $this->recs = array_values($recs); }
    public function show($fields) {}
    public function groupBy($field) {}
    public function selectOnReplica() {}
    public function numRec() { return count($this->recs); }
    public function fetch() { return array_shift($this->recs); }
    public function fetchAll() { return array_map(function ($rec) { return clone $rec; }, $this->recs); }
    public function in($field, $values, $exclude = false) {
        $values = is_array($values) ? $values : array_map('trim', explode(',', $values));
        $this->recs = array_values(array_filter($this->recs, function ($rec) use ($field, $values, $exclude) {
            return in_array($rec->{$field} ?? null, $values) != $exclude;
        }));
    }
    public function where($condition) {
        $this->recs = array_values(array_filter($this->recs, function ($rec) use ($condition) {
            if (is_array($condition)) {
                if (strpos($condition[0], '#date >=') === 0) return $rec->date >= $condition[1];
                if (strpos($condition[0], '#date <=') === 0) return $rec->date <= $condition[1];
            }
            if ($condition == "#type = 'invoice'") return ($rec->type ?? '') == 'invoice';
            if ($condition == '#number IS NOT NULL') return isset($rec->number);
            if ($condition == "#closedDocuments != '' OR #contoActions LIKE '%pay%'") {
                return !empty($rec->closedDocuments) || strpos($rec->contoActions ?? '', 'pay') !== false;
            }
            throw new RuntimeException('Неподдържано тестово условие: ' . json_encode($condition));
        }));
    }
}
class InvoiceTestModel {
    public static function getQuery() { return new InvoiceTestQuery(InvoiceTestData::$records[static::class] ?? array()); }
}
class sales_Invoices extends InvoiceTestModel {}
class purchase_Invoices extends InvoiceTestModel {}
class sales_Sales extends InvoiceTestModel {}
class purchase_Purchases extends InvoiceTestModel {}
class doc_Threads extends InvoiceTestModel {}
class core_Classes { public static function getName($name) { return $name; } }
class deals_Helper {
    public static $combined;
    public static function setCombinedThreads($value) { self::$combined = $value; }
    public static function getCombinedThreads($thread) { return array($thread => $thread); }
    public static function getSmartBaseCurrency($amount, $date, $checkDate) { return $amount; }
    public static function getInvoicePayments($thread, $date, $a, $b) { return InvoiceTestData::$payments[$thread] ?? array(); }
    public static function getInvoicePaymentsBulk($threads, $date, $a, $b) {
        return array_intersect_key(InvoiceTestData::$payments, $threads);
    }
}
class InvoiceTestData {
    public static $records = array();
    public static $payments = array();
    public static function reset() { self::$records = self::$payments = array(); }
    public static function invoice($direction, $id, $name, $due, $amount, $paid, $date = '2026-10-01', $currency = 'EUR', $rate = 1) {
        $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
        $dealClass = $direction == 'out' ? 'sales_Sales' : 'purchase_Purchases';
        $thread = ($direction == 'out' ? 1000 : 2000) + $id;
        $container = $thread + 10000;
        self::$records[$invoiceClass][] = (object)array(
            'id' => $id, 'threadId' => $thread, 'containerId' => $container, 'number' => $id,
            'date' => $date, 'dueDate' => $due, 'currencyId' => $currency, 'rate' => $rate,
            'dealValue' => $amount * $rate, 'discountAmount' => 0, 'vatAmount' => 0,
            'contragentName' => $name, 'type' => 'invoice', 'state' => 'active',
            'paymentType' => 'bank', 'autoPaymentType' => null,
        );
        self::$records[$dealClass][] = (object)array(
            'id' => $id, 'threadId' => $thread, 'folderId' => 1, 'state' => 'active', 'closedOn' => null,
        );
        self::$records['doc_Threads'][] = (object)array('id' => $thread, 'firstDocClass' => $dealClass, 'firstDocId' => $id);
        self::$payments[$thread] = array($container => (object)array('amount' => $amount, 'payout' => $paid, 'date' => $date, 'used' => array()));
    }
}

require dirname(__DIR__, 2) . '/frame2/driver/TableData.class.php';
require dirname(__DIR__) . '/reports/InvoicesByContragent.class.php';

class InvoiceReportTest extends acc_reports_InvoicesByContragent {
    public $prices = true;
    public function run($rec, &$data) { return $this->prepareRecs($rec, $data); }
    public function verbal($rec, &$dRec) { return $this->detailRecToVerbal($rec, $dRec); }
    public function fields($rec, $export = false) { return $this->getTableFieldSet($rec, $export)->fields; }
    public function grouped($recs, &$rows, $fields, $data) { $this->groupRows($recs, $rows, $fields, $data->groupByField, $data); }
    public function canSeePriceFields($rec) { return $this->prices; }
    protected function logReportStats(&$data, $startedOn, $count = 0) {}
    public function exportRow($rec, $dRec) {
        $res = clone $dRec;
        self::on_AfterGetExportRec($this, $res, $rec, $dRec, null);
        return $res;
    }
}

$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function reportRec($direction, $payment) {
    return (object)array('typeOfInvoice' => $direction, 'unpaid' => $payment, 'checkDate' => '2026-10-15');
}
$driver = new InvoiceReportTest();
$fieldset = new core_FieldSet();
$driver->addFields($fieldset);
check(strpos($fieldset->fields['typeOfInvoice'], 'all=Всички') !== false, 'Опция за двата вида');
check(strpos($fieldset->fields['unpaid'], 'overdue=Просрочени') !== false, 'Опция за просрочени');

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 40);
    InvoiceTestData::invoice($direction, 2, 'Acme', '2026-10-15', 100, 0);
    InvoiceTestData::invoice($direction, 3, 'Acme', '2026-10-16', 100, 0);
    InvoiceTestData::invoice($direction, 4, 'Acme', null, 100, 0);
    InvoiceTestData::invoice($direction, 5, 'Acme', '2026-10-14', 100, 100);
    InvoiceTestData::invoice($direction, 6, 'Acme', '2026-10-14', 100, 120);
    InvoiceTestData::invoice($direction, 7, 'Acme', '2026-10-14', 100, 99.999);
    $rec = reportRec($direction, 'overdue');
    $data = new stdClass();
    $recs = $driver->run($rec, $data);
    check(count($recs) == 1, 'Само просрочена частично платена фактура: ' . $direction);
    $detail = reset($recs);
    check($detail->invoiceCurrentSumm == 60 && $rec->totalInvoiceNotPaydAll == 60, 'Остатък и обобщение: ' . $direction);
    check($rec->totalInvoiceValueAll == 100 && $rec->totalInvoicePayoutAll == 40, 'Без суми от отхвърлените фактури: ' . $direction);
    $row = $driver->verbal($rec, $detail);
    check($row->invoiceCurrentSumm == '60.00' && $row->paidAmount == '40.00', 'Показване на частично плащане: ' . $direction);
    check(isset($driver->fields($rec)['invoiceCurrentSumm']), 'Колона за остатък: ' . $direction);
    check($driver->exportRow($rec, $detail)->dueDateStatus == 'Просрочен', 'Просрочен статус при експорт: ' . $direction);
    $data->groupByField = 'contragent';
    $data->groupedFieldOnNewRow = true;
    $rows = array(key($recs) => $row);
    $driver->grouped($recs, $rows, $driver->fields($rec), $data);
    check(count($rows) == 4 && strip_tags($rows['|Acme']->getContent()) == 'Acme', 'Отделен ред само с името: ' . $direction);
    check(key($rows) == 'spacer|Acme' && strip_tags(reset($rows)->getContent()) == '', 'Празен разделител и преди първата фирма: ' . $direction);
    check(strpos($row->invoiceSummary, $direction == 'out' ? 'ПРОДАЖБИ' : 'ПОКУПКИ') !== false, 'Видът е в реда със сумите: ' . $direction);
    $rec->unpaid = 'unpaid';
    check(count($driver->run($rec, $data)) == 6, 'Запазен режим неплатени и надплатени: ' . $direction);
    $rec->unpaid = 'all';
    check(count($driver->run($rec, $data)) == 7, 'Запазен режим всички плащания: ' . $direction);
}

InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('out', 2, 'Acme', '2026-10-14', 200, 20);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 50, 10);
foreach (array('all', 'unpaid', 'overdue') as $payment) {
    $rec = reportRec('all', $payment);
    $data = new stdClass();
    $recs = $driver->run($rec, $data);
    check(count($recs) == 3, 'Еднакви id на продажба и покупка: ' . $payment);
    check(array_keys($recs) == ($payment == 'all' ? array('out|1S', 'out|2S', 'in|1') : array('out|1', 'out|2', 'in|1')), 'Последователни списъци: ' . $payment);
    check($rec->totalInvoiceValueAll == 250, 'Нетна стойност без повторение: ' . $payment);
    $detail = end($recs);
    check($detail->combinedSummary->totalInvoiceValue == 250, 'Изходящи минус входящи: ' . $payment);
    if ($payment != 'all') {
        check($detail->combinedSummary->totalInvoiceNotPayd == 200, 'Нетен неплатен остатък: ' . $payment);
        check($detail->combinedSummary->totalInvoicePayout == 50 && $rec->totalInvoicePayoutAll == 50, 'Нетно платено: ' . $payment);
        check($detail->combinedSummary->totalInvoiceOverDue == 200 && $rec->totalInvoiceOverDueAll == 200, 'Нетно просрочено: ' . $payment);
    }
    $data->groupedFieldOnNewRow = true;
    $fields = $driver->fields($rec);
    $rows = array();
    foreach ($recs as $key => $detail) $rows[$key] = $driver->verbal($rec, $detail);
    $driver->grouped($recs, $rows, $fields, $data);
    check(count($rows) == 8, 'Разделител, едно име, две отделни и трето нетно обобщение: ' . $payment);
    check(substr_count(implode('', array_map(function ($row) { return $row instanceof core_ET ? $row->getContent() : ''; }, $rows)), 'Acme') == 1, 'Името на фирмата е само веднъж: ' . $payment);
    check(strpos(end($rows)->getContent(), 'ИЗХОДЯЩИ минус ВХОДЯЩИ') !== false, 'Нетният ред е последен: ' . $payment);
    check(isset($driver->fields($rec, true)['invoiceDirection']), 'Посока при експорт: ' . $payment);
    check($driver->exportRow($rec, $detail)->invoiceDirection == 'Входящи', 'Правилна посока при експорт: ' . $payment);
    $pageRows = array();
    foreach (array_slice($recs, 0, 1, true) as $key => $detail) $pageRows[$key] = $driver->verbal($rec, $detail);
    $driver->grouped($recs, $pageRows, $fields, $data);
    check(count($pageRows) == 4, 'Без преждевременен общ ред при странициране: ' . $payment);
    $pageRows = array();
    foreach (array_slice($recs, -1, 1, true) as $key => $detail) $pageRows[$key] = $driver->verbal($rec, $detail);
    $driver->grouped($recs, $pageRows, $fields, $data);
    check(count($pageRows) == 5 && strpos(end($pageRows)->getContent(), 'ИЗХОДЯЩИ минус ВХОДЯЩИ') !== false, 'Нетно обобщение на последната страница: ' . $payment);
    $driver->prices = false;
    $detail = end($recs);
    $row = $driver->verbal($rec, $detail);
    check(strpos($row->combinedSummary, '250') === false && strpos($row->invoiceSummary, '50.00') === false, 'Скрити цени в обобщенията: ' . $payment);
    $driver->prices = true;
}
check(deals_Helper::$combined === null, 'Освободен кеш за обединени сделки');

InvoiceTestData::reset();
$rec = reportRec('all', 'overdue');
$data = new stdClass();
check($driver->run($rec, $data) === array() && $rec->totalInvoiceValueAll == 0, 'Празни два списъка');
InvoiceTestData::invoice('in', 1, 'Only purchase', '2026-10-14', 80, 20);
check(count($driver->run($rec, $data)) == 1 && $rec->totalInvoiceValueAll == -80, 'Само покупките дават отрицателна нетна стойност');

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 40);
    InvoiceTestData::invoice($direction, 2, 'Acme', '2026-10-14', 200, 20);
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        $rec = reportRec('all', $payment);
        $data = new stdClass();
        $recs = $driver->run($rec, $data);
        $data->groupedFieldOnNewRow = true;
        $rows = array();
        foreach ($recs as $key => $detail) $rows[$key] = $driver->verbal($rec, $detail);
        $driver->grouped($recs, $rows, $driver->fields($rec), $data);
        check(count($rows) == 5 && !($rows[array_key_last($rows)] instanceof core_ET), 'Без нетен ред при един вид фактури: ' . $direction . '/' . $payment);
    }
}

InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40, '2026-10-01', 'USD', 2);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 50, 10);
InvoiceTestData::invoice('out', 2, 'Beta', '2026-10-14', 30, 5);
$rec = reportRec('all', 'overdue');
$data = new stdClass();
$recs = $driver->run($rec, $data);
$rows = array();
foreach ($recs as $key => $detail) $rows[$key] = $driver->verbal($rec, $detail);
$data->groupedFieldOnNewRow = true;
$driver->grouped($recs, $rows, $driver->fields($rec), $data);
check(count($rows) == 11 && isset($rows['combined|in|1']) && !isset($rows['combined|out|2']), 'Нетен ред само за фирмата с двата вида фактури');
check(isset($rows['spacer|Acme'], $rows['spacer|Beta']), 'Разделител пред всяка фирма');
check(array_keys($recs) == array('out|1', 'in|1', 'out|2'), 'Двата вида са последователни за всяка фирма');
check($rec->totalInvoiceValueAll == 180 && $rec->totalInvoiceNotPaydAll == 105, 'Нетни суми в основна валута');
check($recs['in|1']->combinedSummary->totalInvoiceNotPayd == 80, 'Нетното обобщение включва курсовете');

InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 150, 110);
$rec = reportRec('all', 'overdue');
$data = new stdClass();
$recs = $driver->run($rec, $data);
$detail = end($recs);
$row = $driver->verbal($rec, $detail);
check($rec->totalInvoiceValueAll == -50 && $rec->totalInvoicePayoutAll == -70, 'Покупките могат да надвишават продажбите');
check($rec->totalInvoiceNotPaydAll == 20 && strpos($row->combinedSummary, '-50.00') !== false, 'Отрицателна стойност с положителен нетен остатък');
check($detail->invoiceValue == 150 && $row->paidAmount == '110.00', 'Редовете за покупки запазват собствените суми');
InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 100, 40);
$rec = reportRec('all', 'overdue');
$recs = $driver->run($rec, $data);
check($rec->totalInvoiceValueAll == 0 && $rec->totalInvoiceNotPaydAll == 0, 'Равните суми се компенсират');
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 120);
    $rec = reportRec('all', 'unpaid');
    $recs = $driver->run($rec, $data);
    $detail = end($recs);
    $row = $driver->verbal($rec, $detail);
    check($rec->totalInvoiceOverPaidAll == ($direction == 'out' ? -20 : 20), 'Знак на нетното надплащане: ' . $direction);
    check(strpos($row->combinedSummary, 'Надплатено') !== false, 'Нетното надплащане се показва и с отрицателен знак: ' . $direction);
}

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 40);
    InvoiceTestData::invoice($direction, 2, 'Acme', '2026-10-14', 200, 0, '2026-09-01');
    $thread = $direction == 'out' ? 1001 : 2001;
    InvoiceTestData::$payments[$thread] += InvoiceTestData::$payments[$thread + 1];
    $rec = reportRec($direction, 'overdue');
    $rec->fromDate = '2026-10-01';
    $data = new stdClass();
    $recs = $driver->run($rec, $data);
    check(count($recs) == 1 && $rec->totalInvoiceValueAll == 100, 'Без суми от обединена фактура преди периода: ' . $direction);
}

echo "OK: {$checks} invoice report checks\n";
