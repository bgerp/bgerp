<?php

/**
 * Проверки без база: php acc/tests/InvoicesByContragent.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class frame2_driver_Proto extends stdClass {}
class embed_Manager {}
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
class Mode {
    public static $plain = false;
    public static function is($name, $value = null) { return self::$plain && $name == 'text' && $value == 'plain'; }
}
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
        if (is_subclass_of($name, 'InvoiceTestModel')) return new $name();
        return $name == 'core_FieldSet' ? new core_FieldSet() : new InvoiceTestType($name);
    }
}
class core_ET {
    private $content;
    public function __construct($content) { $this->content = $content; }
    public function getContent() { return $this->content; }
    public function append($value, $place) {
        if ($value instanceof self) $value = $value->getContent();
        $marker = '[#' . $place . '#]';
        $this->content = str_replace($marker, $value . $marker, $this->content);
    }
    public function replace($value, $place) { $this->content = str_replace('[#' . $place . '#]', $value, $this->content); }
    public function removeBlocks() {}
    public function removePlaces() {}
}
class ht {
    public static function styleNumber($verbal, $amount, $colorIfPositive = null) {
        if ($amount == 0) return "<span class='quiet'>{$verbal}</span>";
        if ($amount < 0) return "<span class='red'>{$verbal}</span>";
        return isset($colorIfPositive) ? "<span style='color:{$colorIfPositive}'>{$verbal}</span>" : $verbal;
    }
    public static function createLinkRef($title, $url) { return $title; }
    public static function createHint($title, $hint, $type) { return $title; }
    public static function createElement($tag, $attr, $content) {
        return new core_ET('<' . $tag . ' class="' . ($attr['class'] ?? '') . '">' . $content . '</' . $tag . '>');
    }
}
class str {
    public static function phpToMysqlName($name) { return strtolower(preg_replace('/([A-Z])/', '_$1', $name)); }
}
class InvoiceTestDb {
    public function escape($value) { return addslashes($value); }
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
function tr($value) { return str_replace(array('|*', '|'), '', $value); }
function countR($value) { return is_countable($value) ? count($value) : 0; }
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($value, $encoding) { return strtolower($value); }
}

class InvoiceTestQuery {
    private $recs;
    public function __construct($recs) { $this->recs = array_values($recs); }
    public function show($fields) {}
    public function groupBy($field) {}
    public function orderBy($field, $direction) {}
    public function selectOnReplica() {}
    public function numRec() { return count($this->recs); }
    public function fetch() { return array_shift($this->recs); }
    public function fetchAll() { return array_map(function ($rec) { return clone $rec; }, $this->recs); }
    public function filter($callback) { $this->recs = array_values(array_filter($this->recs, $callback)); }
    public function in($field, $values, $exclude = false) {
        $values = is_array($values) ? $values : array_map('trim', explode(',', $values));
        $this->recs = array_values(array_filter($this->recs, function ($rec) use ($field, $values, $exclude) {
            return in_array($rec->{$field} ?? null, $values) != $exclude;
        }));
    }
    public function where($condition) {
        // Изключването на приключените сделки в SQL се проверява и по фактура
        if (is_string($condition) && strpos($condition, '#threadId NOT IN (SELECT ') === 0) {
            InvoiceTestData::$sqlConditions[] = $condition;

            return;
        }
        $this->recs = array_values(array_filter($this->recs, function ($rec) use ($condition) {
            if (is_array($condition)) {
                if (strpos($condition[0], '#date >=') === 0) return $rec->date >= $condition[1];
                if (strpos($condition[0], '#date <=') === 0) return $rec->date <= $condition[1];
                if (strpos($condition[0], '#valior <=') === 0) return ($rec->valior ?? '') <= $condition[1];
                if (strpos($condition[0], '#classId =') === 0) return ($rec->classId ?? null) == $condition[1];
                if (strpos($condition[0], 'IF(#paymentType') === 0) return (($rec->paymentType ?? null) ?: ($rec->autoPaymentType ?? null)) == $condition[1];
            }
            if ($condition == "#type = 'invoice'") return ($rec->type ?? '') == 'invoice';
            if ($condition == "#state = 'active'") return ($rec->state ?? '') == 'active';
            if ($condition == '#number IS NOT NULL') return isset($rec->number);
            if ($condition == "#closedDocuments != '' OR #contoActions LIKE '%pay%'") {
                return !empty($rec->closedDocuments) || strpos($rec->contoActions ?? '', 'pay') !== false;
            }
            throw new RuntimeException('Неподдържано тестово условие: ' . json_encode($condition));
        }));
    }
}
class InvoiceTestModel {
    public $dbTableName = 'test_deals';
    public $db;
    public function __construct() { $this->db = new InvoiceTestDb(); }
    public static function getQuery() {
        InvoiceTestData::$queries[static::class] = (InvoiceTestData::$queries[static::class] ?? 0) + 1;
        return new InvoiceTestQuery(InvoiceTestData::$records[static::class] ?? array());
    }
    public static function getClassId() { return static::class; }
    public static function fetch($id) {
        foreach (InvoiceTestData::$records[static::class] ?? array() as $rec) {
            if ($rec->id == $id) return clone $rec;
        }
        return null;
    }
}
class sales_Invoices extends InvoiceTestModel {}
class sales_Proformas extends InvoiceTestModel {}
class cash_Pko extends InvoiceTestModel {}
class bank_IncomeDocuments extends InvoiceTestModel {}
class deals_InvoicesToDocuments extends InvoiceTestModel {}
class doc_Containers {
    public static function getDocument($container) {
        foreach (InvoiceTestData::$records['sales_Proformas'] ?? array() as $rec) {
            if ($rec->containerId == $container) return (object)array('className' => 'sales_Proformas', 'that' => $rec->id);
        }
        return null;
    }
}
class purchase_Invoices extends InvoiceTestModel {}
class sales_Sales extends InvoiceTestModel {
    public static function getHandle($id) { return 'Sal' . $id; }
}
class purchase_Purchases extends InvoiceTestModel {
    public static function getHandle($id) { return 'Pur' . $id; }
}
class acc_Items extends InvoiceTestModel {}
class acc_Articles extends InvoiceTestModel {}
class acc_Accounts {
    public static function getRecBySystemId($id) { return (object)array('id' => (int)$id); }
}
class acc_Lists {
    public static function getPosition($account, $interface) { return 2; }
}
class acc_JournalDetails extends InvoiceTestModel {
    public static function filterQuery($query, $from, $to, $account, $all, $items1, $items2, $items3, $strict, $documents) {
        $query->filter(function ($row) use ($to, $account, $items2, $documents) {
            return ($row->state ?? null) == 'active' && (!isset($to) || ($row->valior ?? '') <= $to) && in_array($row->docType ?? null, $documents)
                && ((($row->debitAccId ?? null) == $account && in_array($row->debitItem2 ?? null, $items2))
                    || (($row->creditAccId ?? null) == $account && in_array($row->creditItem2 ?? null, $items2)));
        });
    }
}
class doc_Threads extends InvoiceTestModel {}
class core_Classes { public static function getName($name) { return $name; } }
class deals_Helper {
    public static $combined;
    public static $bulkThreads = array(), $conversions = 0, $baseConversions = array();
    public static function setCombinedThreads($value) { self::$combined = $value; }
    public static function getCombinedThreads($thread) { return self::$combined[$thread] ?? array($thread => $thread); }
    public static function getSmartBaseCurrency($amount, $date, $checkDate) {
        self::$conversions++;
        return $amount * (self::$baseConversions[$date . '|' . $checkDate] ?? 1);
    }
    public static function getInvoicePayments($thread, $date, $a, $b) {
        $payments = array();
        foreach (self::getCombinedThreads($thread) as $groupThread) $payments += InvoiceTestData::$payments[$groupThread] ?? array();
        return $payments;
    }
    public static function getInvoicePaymentsBulk($threads, $date, $a, $b) {
        self::$bulkThreads[] = $threads;
        $payments = array();
        foreach ($threads as $thread) $payments[$thread] = self::getInvoicePayments($thread, $date, $a, $b);
        return $payments;
    }
}
class InvoiceTestData {
    public static $records = array();
    public static $payments = array();
    public static $sqlConditions = array();
    public static $queries = array();
    public static function reset() {
        self::$records = self::$payments = self::$sqlConditions = self::$queries = array();
        deals_Helper::$baseConversions = array();
    }
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
    public static function combine($direction, $ids) {
        $dealClass = $direction == 'out' ? 'sales_Sales' : 'purchase_Purchases';
        $base = end($ids);
        $closed = array();
        foreach (self::$records[$dealClass] as $deal) {
            if (!in_array($deal->id, $ids)) continue;
            if ($deal->id == $base) continue;
            $deal->state = 'closed';
            $deal->closedOn = '2026-10-01';
            $closed[] = $deal->id;
        }
        foreach (self::$records[$dealClass] as $deal) {
            if ($deal->id == $base) $deal->closedDocuments = '|' . implode('|', $closed) . '|';
        }
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
    public function info($rec, $reportData = null) {
        $rec = clone $rec;
        $rec->data = $reportData ?? new stdClass();
        $tpl = new core_ET('[#DRIVER_FIELDS#]');
        self::on_AfterRenderSingle($this, new embed_Manager(), $tpl, (object)array('rec' => $rec));
        return $tpl->getContent();
    }
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
    $balanceColor = $direction == 'out' ? "<span style='color:#080'>60.00</span>" : "<span class='red'>60.00</span>";
    check(strpos($row->invoiceSummary, $balanceColor) !== false, 'Недоплатени продажби в зелено, покупки в червено: ' . $direction);
    $valueColor = $direction == 'out' ? "<span style='color:#080'>100.00</span>" : "<span class='red'>100.00</span>";
    $paidColor = $direction == 'out' ? "<span style='color:#080'>40.00</span>" : "<span class='red'>40.00</span>";
    check(strpos($row->invoiceSummary, $valueColor) !== false && strpos($row->invoiceSummary, $paidColor) !== false, 'Стойността и платеното следват посоката на фактурите: ' . $direction);
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
    $closedConds = implode(' ', InvoiceTestData::$sqlConditions);
    InvoiceTestData::$sqlConditions = array();
    check(($payment == 'all') == ($closedConds === ''), 'Приключените сделки се изключват в заявката само при остатъци: ' . $payment);
    check($payment == 'all' || (substr_count($closedConds, "`state` = 'closed' AND `closed_on` < '2026-10-15'") >= 2
        && substr_count($closedConds, '#threadId NOT IN (SELECT `thread_id` FROM') == substr_count($closedConds, '`thread_id` IS NOT NULL')), 'Изключване и за продажби, и за покупки: ' . $payment);
    check(array_keys($recs) == ($payment == 'all' ? array('out|1S', 'out|2S', 'in|1') : array('out|1', 'out|2', 'in|1')), 'Последователни списъци: ' . $payment);
    check($rec->totalInvoiceValueAll == 250, 'Нетна стойност без повторение: ' . $payment);
    check($data->invoiceTotals == array('out' => 300, 'in' => 50), 'Отделни суми за разбивката без повторение: ' . $payment);
    $detail = end($recs);
    check($detail->combinedSummary->totalInvoiceValue == 250, 'Изходящи минус входящи: ' . $payment);
    check($detail->combinedSummary->invoiceTotals == array('out' => 300, 'in' => 50), 'Фирмената разбивка не повтаря суми по фактури: ' . $payment);
    if ($payment != 'all') {
        check($detail->combinedSummary->totalInvoiceNotPayd == 200, 'Нетен неплатен остатък: ' . $payment);
        check($detail->combinedSummary->totalInvoicePayout == 50 && $rec->totalInvoicePayoutAll == 50, 'Нетно платено: ' . $payment);
        check($detail->combinedSummary->totalInvoiceOverDue == 200 && $rec->totalInvoiceOverDueAll == 200, 'Нетно просрочено: ' . $payment);
    }
    $data->groupedFieldOnNewRow = true;
    $fields = $driver->fields($rec);
    $rows = array();
    foreach ($recs as $key => $detail) $rows[$key] = $driver->verbal($rec, $detail);
    check(strpos(end($rows)->combinedSummary, "<span style='color:#080'>250.00</span>") !== false, 'Положителна нетна стойност в зелено: ' . $payment);
    check(strpos(end($rows)->combinedSummary, "<span style='color:#080'>300.00</span> - <span class='red'>50.00</span> = <span style='color:#080'>250.00</span>") !== false, 'Цветна разбивка за фирмата: ' . $payment);
    if ($payment != 'all') check(strpos(end($rows)->combinedSummary, "<span style='color:#080'>200.00</span>") !== false, 'Нетно вземане в зелено: ' . $payment);
    $driver->grouped($recs, $rows, $fields, $data);
    check(count($rows) == 8, 'Разделител, едно име, две отделни и трето нетно обобщение: ' . $payment);
    check(substr_count(implode('', array_map(function ($row) { return $row instanceof core_ET ? $row->getContent() : ''; }, $rows)), 'Acme') == 1, 'Името на фирмата е само веднъж: ' . $payment);
    check(strpos(strip_tags(end($rows)->getContent()), 'ИЗХОДЯЩИ минус ВХОДЯЩИ') !== false, 'Нетният ред е последен: ' . $payment);
    check(isset($driver->fields($rec, true)['invoiceDirection']), 'Посока при експорт: ' . $payment);
    check($driver->exportRow($rec, $detail)->invoiceDirection == 'Входящи', 'Правилна посока при експорт: ' . $payment);
    $pageRows = array();
    foreach (array_slice($recs, 0, 1, true) as $key => $detail) $pageRows[$key] = $driver->verbal($rec, $detail);
    $driver->grouped($recs, $pageRows, $fields, $data);
    check(count($pageRows) == 4, 'Без преждевременен общ ред при странициране: ' . $payment);
    $pageRows = array();
    foreach (array_slice($recs, -1, 1, true) as $key => $detail) $pageRows[$key] = $driver->verbal($rec, $detail);
    $driver->grouped($recs, $pageRows, $fields, $data);
    check(count($pageRows) == 5 && strpos(strip_tags(end($pageRows)->getContent()), 'ИЗХОДЯЩИ минус ВХОДЯЩИ') !== false, 'Нетно обобщение на последната страница: ' . $payment);
    $driver->prices = false;
    $detail = end($recs);
    $row = $driver->verbal($rec, $detail);
    check(strpos($row->combinedSummary, '250') === false && strpos($row->invoiceSummary, '50.00') === false, 'Скрити цени в обобщенията: ' . $payment);
    $driver->prices = true;
}
check(deals_Helper::$combined === null, 'Освободен кеш за обединени сделки');
Mode::$plain = true;
$detail = end($recs);
$row = $driver->verbal($rec, $detail);
check(strpos(strip_tags($row->combinedSummary), '300.00 - 50.00 = 250.00') !== false && strpos($row->combinedSummary, 'style=') === false, 'Фирмена разбивка без оцветяване в обикновен текст');
Mode::$plain = false;
$legacyDetail = clone $detail;
$legacyDetail->combinedSummary = clone $detail->combinedSummary;
unset($legacyDetail->combinedSummary->invoiceTotals);
$row = $driver->verbal($rec, $legacyDetail);
check(strpos($row->combinedSummary, '250.00') !== false && strpos($row->combinedSummary, ' - ') === false, 'Старите запазени резултати показват нетната сума до обновяване');

InvoiceTestData::reset();
$rec = reportRec('all', 'overdue');
$data = new stdClass();
check($driver->run($rec, $data) === array() && $rec->totalInvoiceValueAll == 0, 'Празни два списъка');
check($data->invoiceTotals == array('out' => 0, 'in' => 0), 'Нулеви суми за двата празни списъка');
InvoiceTestData::invoice('in', 1, 'Only purchase', '2026-10-14', 80, 20);
check(count($driver->run($rec, $data)) == 1 && $rec->totalInvoiceValueAll == -80, 'Само покупките дават отрицателна нетна стойност');
check($data->invoiceTotals == array('out' => 0, 'in' => 80), 'Разбивка при налични само покупки');

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
check(strpos($row->combinedSummary, "<span class='red'>-50.00</span>") !== false && strpos($row->combinedSummary, "<span style='color:#080'>20.00</span>") !== false, 'Цветът на нетния остатък е независим от общата стойност');
check(strpos($row->combinedSummary, "<span style='color:#080'>100.00</span> - <span class='red'>150.00</span> = <span class='red'>-50.00</span>") !== false, 'Разбивка с покупки над продажбите и отрицателен резултат');
check($detail->invoiceValue == 150 && $row->paidAmount == '110.00', 'Редовете за покупки запазват собствените суми');
InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 100, 40);
$rec = reportRec('all', 'overdue');
$recs = $driver->run($rec, $data);
check($rec->totalInvoiceValueAll == 0 && $rec->totalInvoiceNotPaydAll == 0, 'Равните суми се компенсират');
$detail = end($recs);
$row = $driver->verbal($rec, $detail);
check(strpos($row->combinedSummary, "<span style='color:#080'>100.00</span> - <span class='red'>100.00</span> = <span class='quiet'>0.00</span>") !== false, 'Нулевият резултат остава неутрален при цветни отделни суми');
InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 200, 50);
$recs = $driver->run($rec, $data);
$detail = end($recs);
$row = $driver->verbal($rec, $detail);
check(strpos($row->combinedSummary, "<span class='red'>-90.00</span>") !== false, 'Нетно задължение в червено');
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 120);
    $rec = reportRec('all', 'unpaid');
    $recs = $driver->run($rec, $data);
    $detail = end($recs);
    $row = $driver->verbal($rec, $detail);
    check($rec->totalInvoiceOverPaidAll == ($direction == 'out' ? -20 : 20), 'Знак на нетното надплащане: ' . $direction);
    check(strpos($row->combinedSummary, 'Надплатено') !== false, 'Нетното надплащане се показва и с отрицателен знак: ' . $direction);
    $overpaidColor = $direction == 'out' ? "<span class='red'>-20.00</span>" : "<span style='color:#080'>-20.00</span>";
    check(strpos($row->invoiceSummary, $overpaidColor) !== false, 'Надплатени продажби се дължат, надплатени покупки се получават: ' . $direction);
    $netColor = $direction == 'out' ? "<span class='red'>-20.00</span>" : "<span style='color:#080'>20.00</span>";
    check(strpos($row->combinedSummary, $netColor) !== false, 'Цвят на нетното надплащане: ' . $direction);
}

Mode::$plain = true;
$row = $driver->verbal($rec, $detail);
check(strpos($row->invoiceSummary, 'color:') === false && strpos($row->invoiceSummary, "class='red'") === false, 'Без цветно форматиране в обикновен текст');
Mode::$plain = false;

// Изричният начин има предимство; автоматичният се използва само при празен изричен.
$paymentCases = array(
    array('bank', null, 0.4), array('cash', 'bank', 0.2),
    array(null, 'bank', 0.3), array('', 'bank', 0.1),
    array(null, null, 0), array(null, 'mixed', 0.5),
    array('card', null, 0.1), array('intercept', null, 0.2),
    array('factoring', null, 0.3), array('postal', null, 0.4),
    array('bank', 'cash', 1), array('bank', 'cash', 1.2),
);
InvoiceTestData::reset();
foreach (array('out', 'in') as $direction) {
    foreach ($paymentCases as $i => $paymentCase) {
        list($explicit, $automatic, $paidRatio) = $paymentCase;
        $amount = ($i + 1) * ($direction == 'out' ? 100 : 50);
        InvoiceTestData::invoice($direction, $i + 1, $i < 4 ? 'Acme' : 'Beta', '2026-10-14', $amount, $amount * $paidRatio);
        $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
        $invoice = end(InvoiceTestData::$records[$invoiceClass]);
        $invoice->paymentType = $explicit;
        $invoice->autoPaymentType = $automatic;
    }
}
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        foreach (array(null, 'bank', 'cash', 'intercept', 'card', 'factoring', 'postal', 'missing') as $filter) {
            $rec = reportRec($direction, $payment);
            $rec->paymentType = $filter;
            $data = new stdClass();
            $recs = $driver->run($rec, $data);
            $expectedIds = $expectedGroups = array();
            $expectedValue = $expectedPaid = $expectedUnpaid = $expectedOverpaid = 0;
            foreach ($direction == 'all' ? array('out', 'in') : array($direction) as $invoiceDirection) {
                $sign = $direction == 'all' && $invoiceDirection == 'in' ? -1 : 1;
                foreach ($paymentCases as $i => $paymentCase) {
                    list($explicit, $automatic, $paidRatio) = $paymentCase;
                    if ($filter && $filter != ($explicit ?: $automatic)) continue;
                    if ($payment == 'unpaid' && $paidRatio == 1) continue;
                    if ($payment == 'overdue' && $paidRatio >= 1) continue;
                    $amount = ($i + 1) * ($invoiceDirection == 'out' ? 100 : 50);
                    $expectedIds[] = ($invoiceDirection == 'out' ? 'sales_Invoices' : 'purchase_Invoices') . '|' . ($i + 1);
                    $expectedValue += $sign * $amount;
                    $expectedPaid += $sign * $amount * $paidRatio;
                    $expectedUnpaid += $sign * max(0, $amount * (1 - $paidRatio));
                    $expectedOverpaid += $sign * min(0, $amount * (1 - $paidRatio));
                    $group = ($invoiceDirection == 'out' ? 'sales_Invoices' : 'purchase_Invoices') . '|' . ($i < 4 ? 'Acme' : 'Beta');
                    $expectedGroups[$group] = ($expectedGroups[$group] ?? 0) + $amount;
                }
            }
            $actualIds = array();
            $actualGroups = array();
            $actualPaid = 0;
            foreach ($recs as $detail) {
                $actualIds[] = $detail->className . '|' . $detail->invoiceId;
                $actualGroups[$detail->className . '|' . $detail->contragent] = $detail->totalInvoiceValue;
                $sign = $direction == 'all' && $detail->className == 'purchase_Invoices' ? -1 : 1;
                $actualPaid += $sign * ($detail->invoicePayout ?? $detail->paidAmount);
            }
            sort($actualIds);
            sort($expectedIds);
            $case = $direction . '/' . $payment . '/' . ($filter ?? 'без филтър');
            check($actualIds == $expectedIds, 'Точните фактури след филтъра: ' . $case);
            check(abs($rec->totalInvoiceValueAll - $expectedValue) < 0.001, 'Обща стойност само на избраните фактури: ' . $case);
            check($actualGroups == $expectedGroups, 'Суми по контрагент и вид само на избраните фактури: ' . $case);
            check(abs($actualPaid - $expectedPaid) < 0.001, 'Цялото плащане остава към избраната фактура: ' . $case);
            if ($payment != 'all') {
                check(abs($rec->totalInvoicePayoutAll - $expectedPaid) < 0.001, 'Общо платено след филтриране: ' . $case);
                check(abs($rec->totalInvoiceNotPaydAll - $expectedUnpaid) < 0.001, 'Неплатен остатък след филтриране: ' . $case);
                check(abs($rec->totalInvoiceOverPaidAll - $expectedOverpaid) < 0.001, 'Надплатен остатък след филтриране: ' . $case);
            }
        }
    }
}

// Общото разпределение на плащанията може да върне и фактура от друга нишка.
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 40);
    InvoiceTestData::invoice($direction, 2, 'Acme', '2026-10-14', 200, 50);
    $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
    $invoice = end(InvoiceTestData::$records[$invoiceClass]);
    $invoice->paymentType = 'cash';
    $thread = $direction == 'out' ? 1001 : 2001;
    InvoiceTestData::$payments[$thread] += InvoiceTestData::$payments[$thread + 1];
    foreach (array('unpaid', 'overdue') as $payment) {
        $rec = reportRec($direction, $payment);
        $rec->paymentType = 'bank';
        $data = new stdClass();
        $recs = $driver->run($rec, $data);
        check(count($recs) == 1 && reset($recs)->invoiceId == 1, 'Без фактура с друг начин от общото разпределение: ' . $direction . '/' . $payment);
        check($rec->totalInvoiceValueAll == 100 && $rec->totalInvoicePayoutAll == 40 && $rec->totalInvoiceNotPaydAll == 60, 'Коректни суми при общо разпределение: ' . $direction . '/' . $payment);
    }
}

InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('out', 2, 'Acme', '2026-10-14', 200, 0);
$proforma = array_pop(InvoiceTestData::$records['sales_Invoices']);
$proforma->paymentType = 'cash';
InvoiceTestData::$records['sales_Proformas'][] = $proforma;
InvoiceTestData::$records['cash_Pko'][] = (object)array('id' => 1, 'containerId' => 30000, 'threadId' => $proforma->threadId, 'state' => 'active', 'amount' => 80, 'valior' => '2026-10-10');
InvoiceTestData::$records['deals_InvoicesToDocuments'][] = (object)array('id' => 1, 'documentContainerId' => 30000, 'containerId' => $proforma->containerId);
foreach (array('out', 'all') as $direction) {
    foreach (array(null, 'bank', 'cash') as $filter) {
        $rec = reportRec($direction, 'all');
        $rec->paymentType = $filter;
        $rec->{'seeProformаs'} = 'yes';
        $data = new stdClass();
        $recs = $driver->run($rec, $data);
        check(count($recs) == ($filter ? 1 : 2), 'Филтърът важи и за показаните проформи: ' . $direction . '/' . ($filter ?? 'всички'));
        check($rec->totalInvoiceValueAll == ($filter == 'cash' ? 0 : 100), 'Проформа без избрана реална фактура не влиза в общата стойност: ' . $direction . '/' . ($filter ?? 'всички'));
        if ($filter == 'cash') check(reset($recs)->invoicePayout == 80, 'Насоченото плащане по проформа се запазва: ' . $direction);
    }
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

foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        InvoiceTestData::reset();
        foreach (array('out', 'in') as $invoiceDirection) {
            for ($id = 1; $id <= 20; $id++) {
                InvoiceTestData::invoice($invoiceDirection, $id, 'Combined', '2026-10-14', $invoiceDirection == 'out' ? 100 : 50, 0);
            }
            InvoiceTestData::combine($invoiceDirection, range(1, 20));
        }
        $rec = reportRec($direction, $payment);
        $rec->paymentType = 'bank';
        $data = new stdClass();
        deals_Helper::$bulkThreads = array();
        $recs = $driver->run($rec, $data);
        $case = $direction . '/' . $payment;
        check(count($recs) == ($direction == 'all' ? 40 : 20), 'Всички фактури от обединени сделки: ' . $case);
        check($rec->totalInvoiceValueAll == ($direction == 'out' ? 2000 : 1000), 'Суми без повторения в обединени сделки: ' . $case);
        check(array_sum(array_map('count', deals_Helper::$bulkThreads)) == ($direction == 'all' ? 2 : 1), 'Едно разпределение за всяка обединена група: ' . $case);
        $rec->paymentType = 'cash';
        deals_Helper::$conversions = 0;
        $recs = $driver->run($rec, $data);
        check(!$recs && $rec->totalInvoiceValueAll == 0, 'Отпадналите по начин фактури не влизат в обобщението: ' . $case);
        check(deals_Helper::$conversions <= ($direction == 'all' ? 80 : 40), 'Отпадналите фактури не се обработват повторно: ' . $case);
        foreach (array('sales_Sales', 'purchase_Purchases') as $dealClass) {
            InvoiceTestData::$records[$dealClass][0]->contoActions = 'pay';
        }
        $rec->paymentType = 'bank';
        $recs = $driver->run($rec, $data);
        check(count($recs) == ($direction == 'all' ? 40 : 20), 'Бърза първа сделка не скрива фактурите на цялата група: ' . $case);
        check($rec->totalInvoiceValueAll == ($direction == 'out' ? 2000 : 1000), 'Запазени суми при бърза сделка в обединението: ' . $case);
    }
}

foreach (array('out', 'in') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        InvoiceTestData::reset();
        $amount = $direction == 'out' ? 100 : 50;
        for ($id = 1; $id <= 6; $id++) {
            $paid = $id % 3 == 1 ? $amount : ($id % 3 == 2 ? $amount / 4 : 0);
            InvoiceTestData::invoice($direction, $id, 'Two groups', '2026-10-14', $amount, $paid, $id == 3 ? '2026-10-20' : '2026-10-01');
        }
        InvoiceTestData::combine($direction, array(1, 2, 3));
        InvoiceTestData::combine($direction, array(4, 5, 6));
        $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
        InvoiceTestData::$records[$invoiceClass][4]->paymentType = 'cash';
        $rec = reportRec($direction, $payment);
        $rec->paymentType = 'bank';
        deals_Helper::$bulkThreads = array();
        $data = new stdClass();
        $recs = $driver->run($rec, $data);
        $case = $direction . '/' . $payment;
        check(count($recs) == ($payment == 'all' ? 4 : 2), 'Независими групи с платени, частично платени и филтрирани фактури: ' . $case);
        check($rec->totalInvoiceValueAll == $amount * ($payment == 'all' ? 4 : 2), 'Запазени стойности при частично избрани групи: ' . $case);
        check(array_sum(array_column($recs, 'invoicePayout')) == $amount * ($payment == 'all' ? 2.25 : 0.25), 'Запазени реални плащания в отделните групи: ' . $case);
        check(array_sum(array_map('count', deals_Helper::$bulkThreads)) == 2, 'Две разпределения за две групи: ' . $case);
    }
}

// SQL изключването трябва да съвпада с PHP проверката closedOn <= checkDate (сравнение на низове)
$exclude = Closure::bind(function ($query, $dealClass, $checkDate, $united) {
    self::excludeClosedDealThreads($query, $dealClass, $checkDate, $united);
}, null, 'acc_reports_InvoicesByContragent');
$closedCases = array(
    array('2026-10-17', '2026-10-17 00:00:00'),
    array('2026-10-17', '2026-10-17 12:30:00'),
    array('2026-10-17', '2026-10-16 23:59:59'),
    array('2026-10-17', '2026-10-18 00:00:00'),
    array('2026-10-17 12:00:00', '2026-10-17 12:00:00'),
    array('2026-10-17 12:00:00', '2026-10-17 12:00:01'),
    array('2026-10-17 12:00:00', '2026-10-17 00:00:00'),
);
foreach ($closedCases as $case) {
    list($checkDate, $closedOn) = $case;
    InvoiceTestData::$sqlConditions = array();
    $exclude(sales_Invoices::getQuery(), 'sales_Sales', $checkDate, array());
    check(preg_match("/`closed_on` (<=?) '([^']+)'/", InvoiceTestData::$sqlConditions[0] ?? '', $m), 'Условие по дата на приключване: ' . $checkDate);

    // MySQL сравнява DATETIME с дата без час като с полунощ
    $sqlExcludes = $m[1] == '<' ? strtotime($closedOn) < strtotime($m[2]) : strtotime($closedOn) <= strtotime($m[2]);
    check($sqlExcludes === ($closedOn <= $checkDate), "SQL съвпада с PHP: {$closedOn} към {$checkDate}");
}

// МО са самостоятелни счетоводни корекции, а не банкови/касови плащания.
function articleFixture($direction, $dealId, $id, $amount, $date = '2026-10-01', $state = 'active', $journalState = 'active') {
    $incoming = $direction == 'in';
    $itemId = ($incoming ? 20000 : 10000) + $dealId;
    InvoiceTestData::$records['acc_Items'][$itemId] = (object)array('id' => $itemId, 'classId' => $incoming ? 'purchase_Purchases' : 'sales_Sales', 'objectId' => $dealId);
    InvoiceTestData::$records['acc_Articles'][$id] = (object)array('id' => $id, 'state' => $state, 'reason' => 'предоставена компенсация');
    InvoiceTestData::$records['acc_JournalDetails'][] = (object)array(
        'id' => $id * 10 + count(InvoiceTestData::$records['acc_JournalDetails'] ?? array()),
        'docId' => $id, 'docType' => 'acc_Articles', 'state' => $journalState, 'valior' => $date, 'amount' => abs($amount),
        'debitAccId' => ($incoming == ($amount >= 0)) ? ($incoming ? 401 : 411) : 708,
        'creditAccId' => ($incoming == ($amount >= 0)) ? 708 : ($incoming ? 401 : 411),
        'debitItem2' => $itemId, 'creditItem2' => $itemId,
    );
}
function closeAmount($actual, $expected) { return abs($actual - $expected) < 0.00001; }

function checkReconciliation($text, $case) {
    $text = strip_tags($text);
    preg_match_all('/(Стойност|Корекции|Платено|Остатък): \(?(-?\d+\.\d{2})\)?/u', $text, $matches, PREG_SET_ORDER);
    $amounts = array();
    foreach ($matches as $match) $amounts[$match[1]] = (int)str_replace('.', '', $match[2]);
    check(isset($amounts['Стойност'], $amounts['Корекции'], $amounts['Платено'], $amounts['Остатък']), 'Пълен разчет: ' . $case);
    check($amounts['Стойност'] - $amounts['Корекции'] - $amounts['Платено'] === $amounts['Остатък'], 'Точно равенство на изписаните стотинки: ' . $case);
    check(strpos($text, 'Закръгляне') === false, 'Без допълнителен член в разчета: ' . $case);
}

check(isset($fieldset->fields['accountingCorrections']) && strpos($fieldset->fields['accountingCorrections'], 'no=Не,yes=Да') !== false, 'Опция Да/Не за счетоводни корекции');
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Energo', '2026-09-26', 219112.78, 0, '2026-09-16');
    articleFixture($direction, 1, 1387, 352689.13, '2026-09-16');
    articleFixture($direction, 1, 1379, 430099.75, '2026-09-16', 'rejected');
    articleFixture($direction, 1, 1400, 900, '2026-10-09');
    articleFixture($direction, 1, 1401, 800, '2026-09-16', 'active', 'revert');
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        $rec = reportRec($direction, $payment);
        $rec->checkDate = '2026-10-08';
        $data = new stdClass();
        $legacy = $driver->run($rec, $data);
        $rec->accountingCorrections = 'no';
        $disabled = $driver->run($rec, $data);
        check(serialize($legacy) === serialize($disabled), 'Липсваща опция и Не са еднакви: ' . $direction . '/' . $payment);
        check(empty(InvoiceTestData::$queries['acc_Items']) && empty(InvoiceTestData::$queries['acc_JournalDetails']), 'При Не няма счетоводни заявки');
        $rec->accountingCorrections = 'yes';
        $corrected = $driver->run($rec, $data);
        if ($payment == 'overdue') {
            check(count($corrected) == 0 && closeAmount($rec->totalInvoiceOverDueAll, 0), 'МО1387 премахва просрочието: ' . $direction);
        } else {
            $detail = reset($corrected);
            check(count($corrected) == 1 && closeAmount($detail->invoiceCurrentSumm, -133576.35), 'МО1387: точен остатък към 08.10.2026: ' . $direction . '/' . $payment);
            check(closeAmount($detail->accountingCorrection, 352689.13) && count($detail->accountingDocuments) == 1, 'Само контирани корекции до датата');
            check(closeAmount($rec->totalInvoicePayoutAll, 0) && closeAmount($rec->totalInvoiceOverPaidAll, -133576.35), 'Компенсацията не става парично плащане');
            $row = $driver->verbal($rec, $detail);
            check(strpos($row->accountingDocuments, 'МО1387') !== false, 'Корекцията сочи към МО1387');
            $export = $driver->exportRow($rec, $detail);
            check(closeAmount($export->invoiceOverSumm, 133576.35) && closeAmount($export->accountingCorrection, 352689.13), 'Експортът съвпада с коригирания остатък');
        }
        InvoiceTestData::$queries = array();
    }
    $rec = reportRec('all', 'unpaid');
    $rec->checkDate = '2026-10-08';
    $rec->accountingCorrections = 'yes';
    $corrected = $driver->run($rec, $data);
    check(closeAmount($rec->totalInvoiceNotPaydAll + $rec->totalInvoiceOverPaidAll, ($direction == 'in' ? 1 : -1) * 133576.35), 'Нетният знак следва посоката на фактурите');
}

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Acme', '2026-10-14', 100, 40, '2026-10-01', 'USD', 2);
    articleFixture($direction, 1, 20, 50);
    $rec = reportRec($direction, 'unpaid');
    $rec->accountingCorrections = 'yes';
    $corrected = $driver->run($rec, $data);
    $detail = reset($corrected);
    check(closeAmount($detail->invoiceCurrentSumm * $detail->rate, 70), 'Корекцията е в основна валута и курсът се прилага веднъж: ' . $direction);
    check(closeAmount($rec->totalInvoicePayoutAll, 80) && closeAmount($rec->totalInvoiceNotPaydAll, 70), 'Платеното се запазва във валута');
    articleFixture($direction, 1, 21, -20);
    $corrected = $driver->run($rec, $data);
    check(closeAmount($rec->totalAccountingCorrectionAll, 30) && closeAmount($rec->totalInvoiceNotPaydAll, 90), 'Обратна корекция увеличава остатъка');
}

InvoiceTestData::reset();
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 100, 0);
articleFixture('in', 1, 30, 195.583, '2025-12-31');
deals_Helper::$baseConversions['2025-12-31|2026-10-15'] = 1 / 1.95583;
$rec = reportRec('in', 'all');
$rec->accountingCorrections = 'yes';
$corrected = $driver->run($rec, $data);
check(closeAmount($rec->totalAccountingCorrectionAll, 100) && closeAmount(reset($corrected)->invoiceCurrentSumm, 0), 'Корекцията се превалутира от основната валута към датата на МО');
$rec->unpaid = 'unpaid';
check(count($driver->run($rec, $data)) == 0, 'Напълно погасената фактура отпада от Неплатени');

InvoiceTestData::reset();
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 100, 100);
InvoiceTestData::invoice('in', 2, 'Acme', '2026-10-14', 200, 200);
InvoiceTestData::combine('in', array(1, 2));
articleFixture('in', 1, 40, 60);
foreach (array('all', 'unpaid', 'overdue') as $payment) {
    $rec = reportRec('in', $payment);
    $rec->accountingCorrections = 'yes';
    $corrected = $driver->run($rec, $data);
    check(count($corrected) == ($payment == 'all' ? 3 : ($payment == 'unpaid' ? 1 : 0)), 'Неразпределена корекция по обединена сделка: ' . $payment . ' / ' . json_encode(array_keys($corrected)));
    check(closeAmount($rec->totalAccountingCorrectionAll, $payment == 'overdue' ? 0 : 60), 'Корекцията не се повтаря по нишки/фактури');
    if ($payment != 'overdue') {
        $correctionRows = array_filter($corrected, function ($row) { return !empty($row->isAccountingCorrection); });
        $detail = reset($correctionRows);
        $row = $driver->verbal($rec, $detail);
        check($row->documentType == 'МО' && $row->invoiceNo == 'Неразпред. по #Pur2' && $row->paidAmount === '', 'Отделен ред, без измислено плащане по фактура');
        check(closeAmount($driver->exportRow($rec, $detail)->invoiceOverSumm, 60), 'Неразпределената корекция влиза в експорта');
    }
}
$rec = reportRec('in', 'all');
$rec->accountingCorrections = 'yes';
$rec->paymentType = 'cash';
InvoiceTestData::$records['purchase_Invoices'][0]->paymentType = 'cash';
$corrected = $driver->run($rec, $data);
check(count($corrected) == 1 && closeAmount($rec->totalAccountingCorrectionAll, 0) && $rec->accountingIncompleteGroups == 1, 'Корекция на цяла сделка не се отнася към частично избрани фактури');

// Без МО включването на опцията запазва фактурите и реалните плащания при всички филтри.
InvoiceTestData::reset();
foreach (array('out', 'in') as $direction) {
    foreach ($paymentCases as $i => $paymentCase) {
        list($explicit, $automatic, $paidRatio) = $paymentCase;
        $amount = ($i + 1) * 100;
        InvoiceTestData::invoice($direction, $i + 1, $i < 4 ? 'Acme' : 'Beta', '2026-10-14', $amount, $amount * $paidRatio);
        $invoice = end(InvoiceTestData::$records[$direction == 'in' ? 'purchase_Invoices' : 'sales_Invoices']);
        $invoice->paymentType = $explicit;
        $invoice->autoPaymentType = $automatic;
    }
}
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        foreach (array(null, 'bank', 'cash', 'intercept', 'card', 'factoring', 'postal', 'missing') as $filter) {
            $rec = reportRec($direction, $payment);
            $rec->paymentType = $filter;
            $legacy = $driver->run($rec, $data);
            $before = array();
            foreach ($legacy as $row) $before[$row->className . '|' . $row->invoiceId] = array($row->invoiceValue, $driver->exportRow($rec, $row)->paidAmount);
            $rec->accountingCorrections = 'yes';
            $corrected = $driver->run($rec, $data);
            $after = array();
            foreach ($corrected as $row) $after[$row->className . '|' . $row->invoiceId] = array($row->invoiceValue, $driver->exportRow($rec, $row)->paidAmount);
            ksort($before);
            ksort($after);
            check($before == $after, 'Без МО: същите фактури, стойности и плащания: ' . $direction . '/' . $payment . '/' . ($filter ?? 'всички'));
        }
    }
}

InvoiceTestData::reset();
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 100, 40);
articleFixture('in', 1, 50, 40);
end(InvoiceTestData::$records['acc_JournalDetails'])->docType = 'bank_SpendingDocuments';
articleFixture('in', 99, 51, 1000);
$rec = reportRec('in', 'unpaid');
$rec->accountingCorrections = 'yes';
$corrected = $driver->run($rec, $data);
check(closeAmount($rec->totalAccountingCorrectionAll, 0) && closeAmount($rec->totalInvoiceNotPaydAll, 60), 'Банково плащане и МО на чужда сделка не се добавят повторно');

articleFixture('in', 1, 52, 20);
$entry = end(InvoiceTestData::$records['acc_JournalDetails']);
$entry->creditAccId = 401;
$corrected = $driver->run($rec, $data);
check(closeAmount($rec->totalAccountingCorrectionAll, 0), 'Двете страни на вътрешен трансфер в една сделка се зануляват');

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Closed', '2026-10-14', 100, 0);
    $dealClass = $direction == 'in' ? 'purchase_Purchases' : 'sales_Sales';
    InvoiceTestData::$records[$dealClass][0]->state = 'closed';
    InvoiceTestData::$records[$dealClass][0]->closedOn = '2026-10-01';
    $rec = reportRec($direction, 'unpaid');
    $rec->accountingCorrections = 'yes';
    check(count($driver->run($rec, $data)) == 0, 'Приключените необединени сделки остават изключени: ' . $direction);
}

InvoiceTestData::reset();
InvoiceTestData::invoice('in', 1, 'Fast', '2026-10-14', 100, 0);
InvoiceTestData::$records['purchase_Invoices'][0]->id = 500;
InvoiceTestData::$records['purchase_Purchases'][0]->contoActions = 'ship,pay';
$rec = reportRec('in', 'unpaid');
$rec->accountingCorrections = 'yes';
check(count($driver->run($rec, $data)) == 0, 'Бърза покупка не става неплатена при различни номера на покупката и фактурата');

// Един МО може да попадне в две партиди при прехвърляне между сделки.
InvoiceTestData::reset();
for ($id = 1; $id <= 501; $id++) InvoiceTestData::invoice('in', $id, 'Batch', '2026-10-14', 100, 0);
articleFixture('in', 1, 60, 60);
articleFixture('in', 501, 61, 0);
array_pop(InvoiceTestData::$records['acc_JournalDetails']);
$entry = end(InvoiceTestData::$records['acc_JournalDetails']);
$entry->creditAccId = 401;
$entry->creditItem2 = 20501;
$rec = reportRec('in', 'all');
$rec->accountingCorrections = 'yes';
$corrected = $driver->run($rec, $data);
check(count($corrected) == 501 && closeAmount($rec->totalAccountingCorrectionAll, 0), 'Прехвърлянето между партиди не се дублира');
check(closeAmount($corrected[1]->accountingCorrection, 60) && closeAmount($corrected[501]->accountingCorrection, -60), 'Дебитът и кредитът са при правилните сделки');

// Приключената обединяваща сделка участва чрез общото разпределение на групата.
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Closed combined', '2026-10-05', 100, 40);
    InvoiceTestData::invoice($direction, 2, 'Closed combined', '2026-10-05', 200, 150);
    InvoiceTestData::invoice($direction, 3, 'Closed combined', '2026-10-05', -25, 0);
    InvoiceTestData::invoice($direction, 4, 'Closed combined', '2026-10-05', 25, 0);
    $invoiceClass = $direction == 'in' ? 'purchase_Invoices' : 'sales_Invoices';
    $dealClass = $direction == 'in' ? 'purchase_Purchases' : 'sales_Sales';
    InvoiceTestData::$records[$invoiceClass][2]->type = InvoiceTestData::$records[$invoiceClass][3]->type = 'dc_note';
    InvoiceTestData::combine($direction, array(2, 3, 4, 1));
    InvoiceTestData::$records[$dealClass][0]->state = 'closed';
    InvoiceTestData::$records[$dealClass][0]->closedOn = '2026-10-02';

    foreach (array($direction, 'all') as $reportDirection) {
        foreach (array('all', 'unpaid', 'overdue') as $payment) {
            $rec = reportRec($reportDirection, $payment);
            $rec->checkDate = '2026-10-08';
            $rec->accountingCorrections = 'no';
            $legacy = $driver->run($rec, $data);
            $legacyTotals = clone $rec;
            $legacyValues = array();
            foreach ($legacy as $key => $row) {
                $legacyValues[$row->invoiceId] = array($row->invoiceValue, $row->invoicePayout, $driver->exportRow($rec, $row)->paidAmount ?? null, $row->invoiceCurrentSumm);
            }
            ksort($legacyValues);
            $rec->accountingCorrections = 'yes';
            $corrected = $driver->run($rec, $data);
            $values = array();
            foreach ($corrected as $row) {
                $values[$row->invoiceId] = array($row->invoiceValue, $row->invoicePayout, $driver->exportRow($rec, $row)->paidAmount ?? null, $payment == 'all' ? 0 : $row->invoiceCurrentSumm);
            }
            ksort($values);
            $case = $direction . '/' . $reportDirection . '/' . $payment;
            check($legacyValues == $values, 'Без МО: същите фактури, КИ/ДИ, плащания и остатъци от приключено обединение: ' . $case);
            check(count($corrected) == ($payment == 'overdue' ? 3 : 4), 'Обединяващата фактура не отпада: ' . $case);
            if ($payment != 'all') {
                foreach (array('totalInvoiceValueAll', 'totalInvoicePayoutAll', 'totalInvoiceNotPaydAll', 'totalInvoiceOverPaidAll', 'totalInvoiceOverDueAll') as $field) {
                    check(closeAmount($rec->{$field}, $legacyTotals->{$field}), 'Без МО: запазени обобщения ' . $field . ': ' . $case);
                }
            }
        }
    }

    articleFixture($direction, 1, 1388, 60, '2026-10-01');
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        $rec = reportRec($direction, $payment);
        $rec->checkDate = '2026-10-08';
        $rec->accountingCorrections = 'yes';
        $corrected = $driver->run($rec, $data);
        check(count($corrected) == ($payment == 'overdue' ? 3 : 5), 'МО по цялата приключена обединена група: ' . $direction . '/' . $payment);
        check(closeAmount($rec->totalAccountingCorrectionAll, $payment == 'overdue' ? 0 : 60), 'Груповата корекция участва само веднъж: ' . $direction . '/' . $payment);
        if ($payment != 'overdue') {
            check($rec->accountingIncompleteGroups == 0 && $rec->accountingUnallocatedGroups == 1, 'Цялата група е налична за МО: ' . $direction . '/' . $payment);
            check(closeAmount($rec->totalInvoiceNotPaydAll + $rec->totalInvoiceOverPaidAll, 50), 'Точен нетен остатък след груповото МО: ' . $direction . '/' . $payment);
            check(closeAmount($rec->totalInvoicePayoutAll, 190), 'Груповото МО не променя реалните плащания: ' . $direction . '/' . $payment);
        }
    }
}

// МО1387 се прилага към текущата фактура, без да променя старите позиции и техния остатък 0.10.
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Energo history', '2021-05-20', 100, 40, '2021-05-12');
    InvoiceTestData::invoice($direction, 2, 'Energo history', '2021-05-20', 100, 100.10, '2021-05-12');
    InvoiceTestData::invoice($direction, 3, 'Energo history', '2021-05-20', -60, 0, '2021-05-12');
    InvoiceTestData::invoice($direction, 4, 'Energo history', '2021-05-20', 0.20, 0, '2021-05-12');
    $invoiceClass = $direction == 'in' ? 'purchase_Invoices' : 'sales_Invoices';
    $dealClass = $direction == 'in' ? 'purchase_Purchases' : 'sales_Sales';
    InvoiceTestData::$records[$invoiceClass][2]->type = InvoiceTestData::$records[$invoiceClass][3]->type = 'dc_note';
    InvoiceTestData::combine($direction, array(2, 3, 4, 1));
    InvoiceTestData::$records[$dealClass][0]->state = 'closed';
    InvoiceTestData::$records[$dealClass][0]->closedOn = '2021-06-01';
    InvoiceTestData::invoice($direction, 5, 'Energo history', '2026-09-26', 219112.78, 0, '2026-09-16');
    articleFixture($direction, 5, 1387, 352689.13, '2026-09-16');
    foreach (array('all', 'unpaid') as $payment) {
        $rec = reportRec('all', $payment);
        $rec->checkDate = '2026-10-08';
        $rec->accountingCorrections = 'yes';
        $corrected = $driver->run($rec, $data);
        $sign = $direction == 'in' ? -1 : 1;
        check(count($corrected) == 5 && closeAmount($rec->totalAccountingCorrectionAll, $sign * 352689.13), 'МО1387 и цялата приключена историческа група: ' . $direction . '/' . $payment);
        check(closeAmount($rec->totalInvoiceNotPaydAll + $rec->totalInvoiceOverPaidAll, $sign * -133576.25), 'Историческите 0.10 остават извън корекцията с МО1387: ' . $direction . '/' . $payment);
        foreach ($corrected as $row) {
            if ($row->invoiceId == 5) check(closeAmount($row->invoiceCurrentSumm, -133576.35) && count($row->accountingDocuments) == 1, 'Само текущата фактура се коригира от МО1387');
            else check(closeAmount($row->accountingCorrection, 0) && empty($row->accountingDocuments), 'Старите позиции не получават чужда корекция');
        }
    }
}

// Филтрирането по остатък/просрочие се извършва след МО, включително за платени фактури.
foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Paid then corrected', '2026-10-05', 100, 100);
    articleFixture($direction, 1, 1390, -20, '2026-10-01');
    foreach (array('unpaid', 'overdue') as $payment) {
        $rec = reportRec($direction, $payment);
        $rec->checkDate = '2026-10-08';
        $rec->accountingCorrections = 'no';
        check(count($driver->run($rec, $data)) == 0, 'Без МО платената фактура е скрита: ' . $direction . '/' . $payment);
        $rec->accountingCorrections = 'yes';
        $corrected = $driver->run($rec, $data);
        check(count($corrected) == 1 && closeAmount(reset($corrected)->invoiceCurrentSumm, 20), 'МО възстановява неплатен/просрочен остатък: ' . $direction . '/' . $payment);
        check(closeAmount($rec->totalInvoicePayoutAll, 100), 'Платената сума остава 100 след МО: ' . $direction . '/' . $payment);
    }

    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Threshold', '2026-10-05', 100, 95);
    InvoiceTestData::invoice($direction, 2, 'Threshold', '2026-10-05', 100, 75);
    articleFixture($direction, 1, 1391, -20, '2026-10-01');
    articleFixture($direction, 2, 1392, 20, '2026-10-01');
    $rec = reportRec($direction, 'unpaid');
    $rec->checkDate = '2026-10-08';
    $rec->accountingCorrections = 'yes';
    $rec->sill = 10;
    $corrected = $driver->run($rec, $data);
    check(count($corrected) == 1 && reset($corrected)->invoiceId == 1 && closeAmount(reset($corrected)->invoiceCurrentSumm, 25), 'Прагът се прилага към коригирания остатък: ' . $direction);

    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Precision', '2026-10-05', 100, 99.999);
    $rec = reportRec($direction, 'unpaid');
    $rec->checkDate = '2026-10-08';
    $legacy = $driver->run($rec, $data);
    $rec->accountingCorrections = 'yes';
    $corrected = $driver->run($rec, $data);
    check(count($legacy) == 1 && closeAmount(reset($legacy)->invoiceCurrentSumm, 0.001), 'Не запазва стария остатък под стотинка: ' . $direction);
    check(count($corrected) == 0, 'При Да платените до стотинка фактури не остават като неплатени с 0.00: ' . $direction);
}

// Пълният разчет присъства и при Всички, с еднакви суми/цветове в инфото и обобщенията.
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        InvoiceTestData::reset();
        InvoiceTestData::invoice('out', 1, 'Formula', '2026-10-05', 100, 20);
        InvoiceTestData::invoice('in', 1, 'Formula', '2026-10-05', 200, 50);
        articleFixture('out', 1, 1500, 10);
        articleFixture('in', 1, 1501, 30);
        $rec = reportRec($direction, $payment);
        $rec->accountingCorrections = 'yes';
        $data = new stdClass();
        $rows = $driver->run($rec, $data);
        $detail = end($rows);
        $row = $driver->verbal($rec, $detail);
        $summary = $direction == 'all' ? $row->combinedSummary : $row->invoiceSummary;
        $info = $driver->info($rec, $data);
        $plainSummary = strip_tags($summary);
        $plainInfo = strip_tags($info);
        $formula = $direction == 'out' ? 'Стойност: 100.00 - Корекции: 10.00 - Платено: 20.00 = Остатък: 70.00 EUR'
            : ($direction == 'in' ? 'Стойност: 200.00 - Корекции: 30.00 - Платено: 50.00 = Остатък: 120.00 EUR'
                : 'Стойност: -100.00 - Корекции: (-20.00) - Платено: (-30.00) = Остатък: -50.00 EUR');
        $case = $direction . '/' . $payment;
        check(strpos($plainSummary, $formula) !== false && strpos($plainInfo, $formula) !== false, 'Един и същ пълен разчет в инфото и обобщението: ' . $case);
        $result = $direction == 'out' ? "<span style='color:#080'>70.00</span>" : ($direction == 'in' ? "<span class='red'>120.00</span>" : "<span class='red'>-50.00</span>");
        check(strpos($summary, $result) !== false && strpos($info, $result) !== false, 'Резултатът е в цвета на вземането/задължението: ' . $case);
        check(strpos($summary, $direction == 'out' ? "<span class='red'>10.00</span>" : ($direction == 'in' ? "<span style='color:#080'>30.00</span>" : "<span style='color:#080'>-20.00</span>")) !== false, 'Запазен цвят на корекцията: ' . $case);
        if ($direction == 'all') check(strpos($summary, "<span style='color:#080'>100.00</span> - <span class='red'>200.00</span>") !== false, 'Запазена разбивка изходящи/входящи');
        Mode::$plain = true;
        $plainRow = $driver->verbal($rec, $detail);
        $textSummary = $direction == 'all' ? $plainRow->combinedSummary : $plainRow->invoiceSummary;
        check(strpos($textSummary, $formula) !== false && strpos($textSummary, 'style=') === false, 'Разчет в текстов режим без цветен HTML: ' . $case);
        Mode::$plain = false;
        $driver->prices = false;
        $hidden = $driver->verbal($rec, $detail);
        check(strpos($hidden->invoiceSummary, '100.00') === false && strpos($driver->info($rec, $data), '70.00') === false, 'Разчетът спазва правата за цени: ' . $case);
        $driver->prices = true;
    }
}

foreach (array('out', 'in') as $direction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice($direction, 1, 'Short handle', '2026-10-05', 100, 100);
    InvoiceTestData::invoice($direction, 2, 'Short handle', '2026-10-05', 200, 200);
    InvoiceTestData::combine($direction, array(1, 2));
    articleFixture($direction, 1, 1510, 20);
    $rec = reportRec($direction, 'unpaid');
    $rec->accountingCorrections = 'yes';
    $rows = $driver->run($rec, $data);
    $detail = reset($rows);
    $expected = 'Неразпред. по #' . ($direction == 'out' ? 'Sal2' : 'Pur2');
    check($driver->verbal($rec, $detail)->invoiceNo == $expected && $driver->exportRow($rec, $detail)->invoiceNo == $expected, 'Еднакъв кратък хендъл на екрана и в експорта: ' . $direction);
    Mode::$plain = true;
    check($driver->verbal($rec, $detail)->invoiceNo == $expected, 'Кратък хендъл и в текстов режим: ' . $direction);
    Mode::$plain = false;
}

// Калкулатор по фактури: същите стотинки в редовете, експорта и всички обобщения.
foreach (array('out', 'in') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        foreach (array(1, 1.95583) as $rate) {
            foreach (array(0.004, 0.006) as $fraction) {
                foreach (array(1, -1) as $polarity) {
                    foreach (array(1, 101) as $count) {
                        InvoiceTestData::reset();
                        $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
                        for ($i = 1; $i <= $count; $i++) {
                            InvoiceTestData::invoice($direction, $i, 'Cents ' . ($i % 2), '2026-10-05', $polarity * (100 + $fraction), $polarity * 20.001, '2026-10-01', 'USD', $rate);
                            if ($polarity < 0) InvoiceTestData::$records[$invoiceClass][$i - 1]->type = 'dc_note';
                            articleFixture($direction, $i, 2000 + $i, $polarity * 10.009);
                        }
                        $opposite = $direction == 'out' ? 'in' : 'out';
                        foreach (array(1, 2) as $i) {
                            InvoiceTestData::invoice($opposite, $i, 'Cents ' . ($i % 2), '2026-10-05', 5, 2);
                            articleFixture($opposite, $i, 3000 + $i, 0.01);
                        }
                        foreach (array($direction, 'all') as $reportDirection) {
                            $rec = reportRec($reportDirection, $payment);
                            $rec->accountingCorrections = 'yes';
                            $rows = $driver->run($rec, $data);
                            $case = implode('/', array($direction, $reportDirection, $payment, $rate, $fraction, $polarity, $count));
                            checkReconciliation($driver->info($rec, $data), 'инфо ' . $case);
                            $totals = array_fill(0, 4, 0);
                            $groups = array();
                            foreach ($rows as $detail) {
                                $row = $driver->verbal($rec, $detail);
                                $export = $driver->exportRow($rec, $detail);
                                $amounts = array();
                                foreach (array('invoiceValueBaseCurr', 'accountingCorrection', 'paidAmount') as $field) {
                                    $amount = (int)str_replace('.', '', strip_tags($row->{$field}));
                                    check($amount == (int)round($export->{$field} * 100), 'Екран и експорт до стотинка ' . $field . ': ' . $case);
                                    $amounts[] = $amount;
                                }
                                $amounts[] = (int)round(($export->invoiceCurrentSumm - $export->invoiceOverSumm) * 100);
                                check($amounts[0] - $amounts[1] - $amounts[2] === $amounts[3], 'Остатъкът е разликата от видимите суми по фактура: ' . $case);
                                $sign = $reportDirection == 'all' && $detail->className == 'purchase_Invoices' ? -1 : 1;
                                foreach ($amounts as $i => $amount) $totals[$i] += $sign * $amount;
                                $group = $detail->invoiceGroup ?? $detail->contragent;
                                if (isset($groups[$group])) continue;
                                $groups[$group] = true;
                                checkReconciliation($row->invoiceSummary, 'фирма/вид ' . $case);
                                if ($reportDirection == 'all') checkReconciliation($row->combinedSummary, 'фирма/нетно ' . $case);
                            }
                            $actualTotals = array($rec->totalInvoiceValueAll, $rec->totalAccountingCorrectionAll, $rec->totalInvoicePayoutAll, $rec->totalInvoiceNotPaydAll + $rec->totalInvoiceOverPaidAll);
                            foreach ($actualTotals as $i => $amount) check((int)round($amount * 100) === $totals[$i], 'Обобщението е точният сбор от изписаните редове: ' . $case);
                            Mode::$plain = true;
                            checkReconciliation($driver->info($rec, $data), 'текст ' . $case);
                            Mode::$plain = false;
                        }
                    }
                }
            }
        }
    }
}

echo "OK: {$checks} invoice report checks\n";
