<?php

/**
 * Проверки без база: php acc/tests/InvoicesByContragent.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class core_BaseClass extends stdClass {}
class frame2_driver_Proto extends stdClass {
    public static function on_AfterGetFieldForLetterHead(frame2_driver_Proto $driver, embed_Manager $embedder, &$head, $rec, $row) {}
    protected function logWhilePreparing($message) {}
    public function tryToAutoRefresh($rec) { return true; }
}
class embed_Manager {}
class frame2_Reports extends embed_Manager {
    const LIGHT_FETCH_MODE = 'lightFetch';
    public static $selectedVersion = null;
    public static $logs = array();
    public static $logReads = 0;
    public static $records = array();
    public static $rightsChecks = array(), $writes = array();
    public function requireRightFor($action, $rec = null) { self::$rightsChecks[] = array($action, $rec->id ?? null); }
    public function save_(&$rec, $fields) {
        $stored = clone (self::$records[$rec->id] ?? new stdClass());
        foreach (get_object_vars($rec) as $name => $value) $stored->{$name} = $value;
        self::$records[$rec->id] = unserialize(serialize($stored));
    }
    public function logWrite($message, $id) { self::$writes[] = array($message, $id); }
    public static function fetch($id, $fields = '*', $cache = false) {
        $rec = isset(self::$records[$id]) ? clone self::$records[$id] : null;
        if ($rec && Mode::is(self::LIGHT_FETCH_MODE)) unset($rec->data, $rec->log);
        return $rec ? unserialize(serialize($rec)) : null;
    }
    public static function refresh($rec) {
        $rec->data = (new InvoiceReportTest())->prepareData($rec);
        self::$records[$rec->id] = unserialize(serialize($rec));
        InvoiceTestData::$records['frame2_ReportVersions'][] = (object) array('id' => ++frame2_ReportVersions::$lastId,
            'reportId' => $rec->id, 'oldRec' => unserialize(serialize($rec)));
    }
    public static function getSelectedVersionId($id) { return self::$selectedVersion; }
    public static function fetchField($id, $field) {
        if ($field !== 'log') throw new RuntimeException('Не се допуска четене на резултата');
        self::$logReads++;
        return self::$logs[$id] ?? null;
    }
}
class core_App { public static function setTimeLimit($seconds) {} }
class core_Debug {
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class dt {
    public static $date = '2026-10-15';
    public static function today($unused = true) { return self::$date; }
    public static function now() { return self::$date . ' 12:00:00'; }
    public static function addSecs($seconds) { return date('Y-m-d H:i:s', strtotime(self::now()) + $seconds); }
    public static function mysql2verbal($date, $mask) { return date($mask, strtotime($date)); }
}
class Mode {
    public static $plain = false;
    public static $values = array();
    public static function set($name, $value = true) { self::$values[$name] = $value; }
    private static $stack = array();
    public static function push($name, $value) { self::$stack[$name][] = self::$values[$name] ?? null; self::$values[$name] = $value; }
    public static function pop($name) { self::$values[$name] = array_pop(self::$stack[$name]); }
    public static function is($name, $value = null) {
        return (self::$plain && $name == 'text' && $value == 'plain') || ($value === null && !empty(self::$values[$name]));
    }
}
class Request {
    public static $values = array();
    public static function get($name, $type = null) { return self::$values[$name] ?? null; }
}
class core_Permanent {
    const FOREVER_VALUE = 0;
    public static $values = array(), $reads = array();
    public static function get($key) { self::$reads[] = $key; return isset(self::$values[$key]) ? unserialize(serialize(self::$values[$key])) : null; }
    public static function set($key, $value, $lifetime = 1) { self::$values[$key] = unserialize(serialize($value)); }
    public static function remove($key) { unset(self::$values[$key]); }
}
class core_Locks {
    public static $blocked = array();
    public static function obtain($key, ...$args) { return empty(self::$blocked[$key]); }
    public static function release($key) {}
}
class core_CallOnTime {
    public static $calls = array();
    public static function setOnce($class, $method, $data, $date) { self::$calls[] = array($class, $method, $data, $date); }
}
class core_Cron { public static function getTimeLeft() { return 50; } }
function reportException($exception) { throw $exception; }
function followRetUrl() { return (object) array('url' => 'return'); }
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
        if (strpos($this->name, 'enum(') === 0) {
            foreach (explode(',', rtrim(substr($this->name, 5), ')')) as $option) {
                $parts = explode('=', trim($option), 2);
                if ($parts[0] === (string)$value) return $parts[1] ?? $parts[0];
            }
        }
        if (strpos($this->name, 'keylist') === 0 && preg_match('/mvc=(\w+)/', $this->name, $match)) {
            $names = array();
            foreach (keylist::toArray($value) as $id) {
                $record = InvoiceTestData::$records[$match[1]][$id] ?? new stdClass();
                $names[] = htmlspecialchars($record->title ?? $record->name ?? $id, ENT_QUOTES, 'UTF-8');
            }
            return implode(', ', $names);
        }
        return strpos($this->name, 'double') === 0 ? number_format($value, 2, '.', '') : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
class core_FieldSet {
    public $fields = array();
    public $params = array();
    public function FLD($name, $type, $params) { $this->fields[$name] = $type; $this->params[$name] = $params; }
    public function FNC($name, $type, $params) { $this->FLD($name, $type, $params); }
    public function getField($name) {
        preg_match('/(?:^|,)caption=([^,]*)/', $this->params[$name], $caption);
        return (object)array('caption' => $caption[1] ?? '', 'type' => new InvoiceTestType($this->fields[$name]));
    }
}
class cls {
    public static $invoiceCallsUntilFailure = null;
    public static function load($name, $silent = false) { return true; }
    public static function get($name) {
        if ($name == 'frame2_Reports') return new frame2_Reports();
        if (is_subclass_of($name, 'InvoiceTestModel')) return new $name();
        if ($name == 'acc_reports_InvoicesByContragent') {
            if (self::$invoiceCallsUntilFailure === 0) throw new RuntimeException('Тестово прекъсване между две порции');
            if (isset(self::$invoiceCallsUntilFailure)) self::$invoiceCallsUntilFailure--;
            return new InvoiceReportTest();
        }
        return $name == 'core_FieldSet' ? new core_FieldSet() : new InvoiceTestType($name);
    }
}
class core_ET {
    private $content;
    public $assets = array();
    public function __construct($content) { $this->content = $content; }
    public function getContent() { return $this->content; }
    public function __toString() { return $this->getContent(); }
    public function append($value, $place) {
        if ($value instanceof self) $value = $value->getContent();
        $marker = '[#' . $place . '#]';
        $this->content = str_replace($marker, $value . $marker, $this->content);
    }
    public function replace($value, $place) { $this->content = str_replace('[#' . $place . '#]', $value, $this->content); }
    public function push($value, $place) { $this->assets[$place][] = $value; }
    public function removeBlock($name) { $this->content = preg_replace('/<!--ET_BEGIN ' . preg_quote($name, '/') . '-->.*?<!--ET_END ' . preg_quote($name, '/') . '-->/s', '', $this->content); }
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
    public static function createLink($title, $url, $warning = false, $attr = array()) { return '<a href="/' . $url[0] . '/single/' . $url[2] . '">' . $title . '</a>'; }
    public static function createHint($title, $hint, $type, $append = true, $hintAttr = array(), $elementAttr = array()) {
        return $type == 'noicon' ? '<span class="' . ($elementAttr['class'] ?? '') . '"><span class="textHint" title="'
            . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '">' . $title . '</span></span>' : $title;
    }
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
    public static function toArray($value) { $ids = array_values(array_filter(explode('|', $value ?? ''))); return array_combine($ids, $ids); }
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
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException($args[0] ?? 'Expectation failed'); }
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($value, $encoding) { return strtolower($value); }
}

class InvoiceTestQuery {
    public $onReplica = false;
    private $recs;
    public function __construct($recs) { $this->recs = array_values($recs); }
    public function show($fields) {}
    public function groupBy($field) {}
    public function orderBy($field, $direction) {}
    public function selectOnReplica() {}
    public function numRec() { return count($this->recs); }
    public function count($condition = null, $limit = 0) { return $limit ? min($limit, count($this->recs)) : count($this->recs); }
    public function limit($count) { $this->recs = array_slice($this->recs, 0, $count); }
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
            preg_match('/`closed_on` (<=|<) \'([^\']+)\'/', $condition, $cutoff);
            preg_match('/`id` NOT IN \(([^)]+)\)/', $condition, $united);
            $excludedIds = isset($united[1]) ? explode(',', $united[1]) : array();
            $this->filter(function ($invoice) use ($cutoff, $excludedIds) {
                foreach (array('sales_Sales', 'purchase_Purchases') as $class) {
                    foreach (InvoiceTestData::$records[$class] ?? array() as $deal) {
                        if (($deal->threadId ?? null) != ($invoice->threadId ?? null) || ($deal->state ?? '') != 'closed' || in_array($deal->id, $excludedIds)) continue;
                        if (($cutoff[1] == '<' && ($deal->closedOn ?? '') < $cutoff[2]) || ($cutoff[1] == '<=' && ($deal->closedOn ?? '') <= $cutoff[2])) return false;
                    }
                }
                return true;
            });

            return;
        }
        $this->recs = array_values(array_filter($this->recs, function ($rec) use ($condition) {
            if (is_array($condition)) {
                if (strpos($condition[0], '#date >=') === 0) return $rec->date >= $condition[1];
                if (strpos($condition[0], '#date <=') === 0) return $rec->date <= $condition[1];
                if (strpos($condition[0], '#valior <=') === 0) return ($rec->valior ?? '') <= $condition[1];
                if (strpos($condition[0], '#classId =') === 0) return ($rec->classId ?? null) == $condition[1];
                if (strpos($condition[0], '#reportId =') === 0) return ($rec->reportId ?? null) == $condition[1];
                if (strpos($condition[0], '#id >=') === 0) return $rec->id >= $condition[1];
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
class frame2_ReportVersions extends InvoiceTestModel {
    public static $lastId = 100;
    public static function getLatestVersionId($reportId) { return self::$lastId; }
    public static function unSelectVersion($reportId) { frame2_Reports::$selectedVersion = null; }
}
class sales_Proformas extends InvoiceTestModel {}
class cash_Pko extends InvoiceTestModel {}
class bank_IncomeDocuments extends InvoiceTestModel {}
class deals_InvoicesToDocuments extends InvoiceTestModel {}
class doc_Containers {
    public static function getDocument($container) {
        InvoiceTestData::$documentReads++;
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
class acc_Articles extends InvoiceTestModel {
    public static function getHandle($id) { return 'Mo' . $id; }
}
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
    public static $documentReads = 0;
    public static function reset() {
        self::$records = self::$payments = self::$sqlConditions = self::$queries = array();
        self::$documentReads = 0;
        deals_Helper::$baseConversions = array();
    }
    public static function invoice($direction, $id, $name, $due, $amount, $paid, $date = '2026-10-01', $currency = 'EUR', $rate = 1) {
        $invoiceClass = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
        $dealClass = $direction == 'out' ? 'sales_Sales' : 'purchase_Purchases';
        $thread = ($direction == 'out' ? 1000 : 2000) + $id;
        $container = $thread + 10000;
        self::$records[$invoiceClass][] = (object)array(
            'id' => $id, 'threadId' => $thread, 'containerId' => $container, 'number' => $id, 'folderId' => 1,
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
require dirname(__DIR__) . '/reports/InvoiceResult.class.php';

class InvoiceReportTest extends acc_reports_InvoicesByContragent {
    public $prices = true;
    public function run($rec, &$data) { $data = $this->prepareData($rec); return $data->recs; }
    public function verbal($rec, &$dRec) { return $this->detailRecToVerbal($rec, $dRec); }
    public function fields($rec, $export = false) { return $this->getTableFieldSet($rec, $export)->fields; }
    public function fieldParams($rec, $export = false) { return $this->getTableFieldSet($rec, $export)->params; }
    public function prepareForm($form) {
        $data = (object)array('form' => $form);
        self::on_AfterPrepareEditForm($this, new embed_Manager(), $data);
    }
    public function inputForm($form) { self::on_AfterInputEditForm($this, new embed_Manager(), $form); }
    public function beforeAction($action) {
        $res = null;
        $status = self::on_BeforeAction($this, new frame2_Reports(), $res, $action);
        return array($status, $res);
    }
    public function beforeSave($rec) { self::on_BeforeSave($this, new frame2_Reports(), null, $rec); }
    public function exportRows($rec) { return $this->getRecsForExport($rec, null); }
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

class InvoiceDateForm {
    public $rec, $layout;
    public $errors = array();
    public $values = array();
    public $fieldParams = array();
    public $suggestions = array();
    public $submitted = false;
    public function __construct($rec) { $this->rec = $rec; }
    public function renderLayout() { return new core_ET(''); }
    public function setDefault($name, $value) { if (!isset($this->rec->{$name})) $this->rec->{$name} = $value; }
    public function setField($name, $value) { $this->fieldParams[$name] = $value; }
    public function appendSuggestions($name, $suggestions) { $this->suggestions[$name] = array_merge($this->suggestions[$name] ?? array(), $suggestions); }
    public function setFieldTypeParams($name, $params) { $this->fieldParams[$name] = array_merge($this->fieldParams[$name] ?? array(), $params); }
    public function input($names = null, $mode = null) {
        foreach ($this->values as $name => $value) {
            if ($names === null || $names === $name) $this->rec->{$name} = $value;
        }
    }
    public function isSubmitted() { return $this->submitted; }
    public function setError($names, $message) { $this->errors[$names] = $message; }
    public function gotErrors() { return count($this->errors) > 0; }
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
if (defined('INVOICE_REPORT_FIXTURE_ONLY')) return;
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
    check(count($driver->run($rec, $data)) == 5, 'Неплатени и надплатени без погасените до стотинка: ' . $direction);
    $rec->unpaid = 'all';
    check(count($driver->run($rec, $data)) == 7, 'Запазен режим всички плащания: ' . $direction);
}

InvoiceTestData::reset();
InvoiceTestData::invoice('out', 1, 'Acme', '2026-10-14', 100, 40);
InvoiceTestData::invoice('out', 2, 'Acme', '2026-10-14', 200, 20, '2026-10-02');
InvoiceTestData::invoice('in', 1, 'Acme', '2026-10-14', 50, 10, '2026-10-03');
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
    check(array_keys($recs) == ($payment == 'all' ? array('out|2S', 'out|1S', 'in|1') : array('out|2', 'out|1', 'in|1')), 'Отделни списъци с най-новите фактури първи: ' . $payment);
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

// Плащането по проформа и последващата фактура се показва отделно, но се сумира само веднъж.
foreach (array('cash_Pko', 'bank_IncomeDocuments') as $paymentClass) {
    foreach (array('out', 'in', 'all') as $direction) {
        foreach (array('no', 'yes') as $corrections) {
            foreach (array('2026-10-07', '2026-10-08') as $cutoff) {
                InvoiceTestData::reset();
                $paid = $cutoff == '2026-10-08' ? 1000 : 0;
                InvoiceTestData::invoice('out', 1, 'Proforma client', '2026-10-10', 1000, $paid, '2026-10-06');
                InvoiceTestData::invoice('in', 1, 'Proforma client', '2026-10-10', 300, 60, '2026-10-06');
                $invoice = reset(InvoiceTestData::$records['sales_Invoices']);
                foreach (array(1 => 1000, 2 => 200, 3 => 500) as $id => $amount) {
                    $proforma = clone $invoice;
                    $proforma->id = $proforma->number = $id;
                    $proforma->containerId = 20000 + $id;
                    $proforma->dealValue = $amount;
                    InvoiceTestData::$records['sales_Proformas'][] = $proforma;
                }
                foreach (array(
                    array(1, 1, 1000, '2026-10-08', 'active'),
                    array(2, 2, 70, '2026-10-05', 'active'),
                    array(3, 2, 130, '2026-10-08', 'active'),
                    array(4, 2, 500, '2026-10-06', 'rejected'),
                    array(5, 2, 500, '2026-10-09', 'active'),
                ) as $payment) {
                    list($id, $proformaId, $amount, $date, $state) = $payment;
                    InvoiceTestData::$records[$paymentClass][] = (object)array('id' => $id, 'containerId' => 30000 + $id, 'threadId' => $invoice->threadId ?? null, 'state' => $state, 'amount' => $amount, 'valior' => $date);
                    InvoiceTestData::$records['deals_InvoicesToDocuments'][] = (object)array('id' => $id, 'documentContainerId' => 30000 + $id, 'containerId' => 20000 + $proformaId);
                }
                $rec = reportRec($direction, 'all');
                $rec->checkDate = $cutoff;
                $rec->fromDate = '2026-10-06';
                $rec->accountingCorrections = $corrections;
                $data = new stdClass();
                $withoutProformas = $driver->run($rec, $data);
                $baseline = clone $rec;
                $rec->{'seeProformаs'} = 'yes';
                InvoiceTestData::$queries = array();
                $recs = $driver->run($rec, $data);
                $case = $paymentClass . '/' . $direction . '/' . $corrections . '/' . $cutoff;
                $expectedProformas = $direction == 'in' ? array() : ($cutoff == '2026-10-07' ? array(2 => 70) : array(1 => 1000, 2 => 200));
                $actualProformas = $actualInvoices = array();
                foreach ($recs as $detail) {
                    if (($detail->className ?? null) == 'sales_Proformas') {
                        $id = $detail->invoiceId ?? null;
                        check(isset($expectedProformas[$id]), 'Само проформи с плащане до датата: ' . $case);
                        check(($detail->invoicePayout ?? null) == $expectedProformas[$id], 'Пълна или частична платена сума към датата: ' . $case);
                        $expectedPayments = $id == 1 ? array(30001) : ($cutoff == '2026-10-07' ? array(30002) : array(30002, 30003));
                        check(array_column($detail->payDocuments ?? array(), 'containerId') === $expectedPayments, 'Без бъдещи и отхвърлени плащания, включително на самата дата: ' . $case);
                        $export = $driver->exportRow($rec, $detail);
                        check(($export->paidAmount ?? null) == $expectedProformas[$id], 'Същото платено в експорта: ' . $case);
                        $actualProformas[$id] = true;
                    } else {
                        $actualInvoices[$detail->className ?? ''][$detail->invoiceId ?? 0] = array($detail->invoiceValue ?? null, $detail->invoicePayout ?? null);
                    }
                }
                check(count($actualProformas) == count($expectedProformas), 'Без липсващи проформи, платени до датата: ' . $case);
                foreach ($withoutProformas as $detail) {
                    check(($actualInvoices[$detail->className ?? ''][$detail->invoiceId ?? 0] ?? null) === array($detail->invoiceValue ?? null, $detail->invoicePayout ?? null), 'Непроменени суми и плащания по истинските фактури: ' . $case);
                }
                foreach (array('totalInvoiceValueAll', 'totalInvoicePayoutAll', 'totalInvoiceNotPaydAll', 'totalInvoiceOverPaidAll', 'totalInvoiceOverDueAll', 'totalAccountingCorrectionAll') as $field) {
                    check(($rec->{$field} ?? 0) === ($baseline->{$field} ?? 0), 'Без повторно сумиране заради проформите: ' . $field . '/' . $case);
                }
                check((InvoiceTestData::$queries['deals_InvoicesToDocuments'] ?? 0) == ($direction == 'in' ? 0 : 1), 'Връзките за всички плащания се четат с една заявка: ' . $case);
            }
        }
    }
}

// Извънпериодните проформи не се обработват, но авансите и плащанията от друга нишка остават.
foreach (array('cash_Pko', 'bank_IncomeDocuments') as $paymentClass) {
    foreach (array('no', 'yes') as $corrections) {
        InvoiceTestData::reset();
        foreach (array(1 => '2026-10-01', 2 => '2026-10-08', 3 => '2026-09-30', 4 => '2026-10-09') as $id => $date) {
            InvoiceTestData::invoice('out', $id, 'Proforma period', '2026-10-10', 100, 0, $date);
            $proforma = array_pop(InvoiceTestData::$records['sales_Invoices']);
            $proforma->folderId = 1;
            InvoiceTestData::$records['sales_Proformas'][] = $proforma;
            InvoiceTestData::$records[$paymentClass][] = (object)array('id' => $id, 'containerId' => 30000 + $id,
                'threadId' => $proforma->threadId ?? null, 'state' => 'active', 'amount' => 20 * $id, 'valior' => '2026-09-20');
            InvoiceTestData::$records['deals_InvoicesToDocuments'][] = (object)array('id' => $id,
                'documentContainerId' => 30000 + $id, 'containerId' => $proforma->containerId ?? null);
        }
        // Плащане от старата обединена нишка към проформата на горната граница.
        InvoiceTestData::$records[$paymentClass][1]->threadId = 1003;
        InvoiceTestData::combine('out', array(2, 3));
        foreach (array(5 => null, 6 => 6, 7 => 7) as $id => $number) {
            $proforma = clone InvoiceTestData::$records['sales_Proformas'][0];
            $proforma->id = $id;
            $proforma->number = $number;
            $proforma->containerId = 11000 + $id;
            $proforma->state = $id == 6 ? 'rejected' : 'active';
            $proforma->folderId = $id == 7 ? 2 : 1;
            InvoiceTestData::$records['sales_Proformas'][] = $proforma;
            InvoiceTestData::$records['deals_InvoicesToDocuments'][] = (object)array('id' => $id,
                'documentContainerId' => 30001, 'containerId' => $proforma->containerId ?? null);
        }
        $rec = reportRec('out', 'all');
        $rec->fromDate = '2026-10-01';
        $rec->checkDate = '2026-10-08';
        $rec->contragent = '|1|';
        $rec->accountingCorrections = $corrections;
        $rec->{'seeProformаs'} = 'yes';
        $rows = $driver->run($rec, $data);
        $paid = array();
        foreach ($rows as $detail) $paid[$detail->invoiceId ?? 0] = $detail->invoicePayout ?? 0;
        ksort($paid);
        check($paid === array(1 => 20, 2 => 40), 'Аванс преди началото и плащане от обединена нишка: ' . $paymentClass . '/' . $corrections);
        check(InvoiceTestData::$documentReads == 2, 'Само двете проформи от периода и избрания контрагент се разпознават');
        check(($rec->totalInvoiceValueAll ?? null) === 0 && ($rec->totalInvoicePayoutAll ?? null) === 0, 'Проформите не удвояват сумите');

        InvoiceTestData::$queries = array();
        InvoiceTestData::$documentReads = 0;
        $rec->fromDate = '2026-10-10';
        $rec->checkDate = '2026-10-11';
        check(acc_reports_InvoicesByContragent::getProformsWithPaymant($rec) === array(), 'Празен период без проформи');
        check(empty(InvoiceTestData::$queries[$paymentClass]) && empty(InvoiceTestData::$queries['deals_InvoicesToDocuments']) && InvoiceTestData::$documentReads == 0,
            'Без заявки за плащания и връзки, когато няма проформи от периода');
    }
}

// Старите фактури участват в разпределението на плащанията, но не и в сумите за избрания период.
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        foreach (array('no', 'yes') as $corrections) {
            foreach (array(null, '2026-10-01', '2026-10-02') as $from) {
                InvoiceTestData::reset();
                $expectedIds = $expectedGroups = array();
                $expectedTotals = array_fill(0, 5, 0);
                foreach ($direction == 'all' ? array('out', 'in') : array($direction) as $kind) {
                    $class = $kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
                    $sign = $direction == 'all' && $kind == 'in' ? -1 : 1;
                    $factor = $kind == 'out' ? 1 : 0.5;
                    foreach (array(array(1, '2026-10-01', 100, 40), array(2, '2026-09-30', 200, 50)) as $invoice) {
                        list($id, $date, $amount, $paid) = $invoice;
                        InvoiceTestData::invoice($kind, $id, 'Acme', '2026-10-14', $amount * $factor, $paid * $factor, $date);
                        if ($payment == 'all' && isset($from) && $date < $from) continue;
                        $expectedIds[] = $class . '|' . $id;
                        $amounts = array($amount, $paid, $amount - $paid, 0, $amount - $paid);
                        foreach ($amounts as $i => $value) {
                            $expectedTotals[$i] += $sign * $value * $factor;
                            $expectedGroups[$class][$i] = ($expectedGroups[$class][$i] ?? 0) + $value * $factor;
                        }
                    }
                    InvoiceTestData::combine($kind, array(2, 1));
                }
                $rec = reportRec($direction, $payment);
                $rec->accountingCorrections = $corrections;
                $rec->fromDate = $from;
                $data = new stdClass();
                $recs = $driver->run($rec, $data);
                $case = $direction . '/' . $payment . '/' . $corrections . '/' . ($from ?? 'от началото');
                $actualIds = array();
                foreach ($recs as $detail) {
                    $detailClass = $detail->className ?? '';
                    $actualIds[] = $detailClass . '|' . ($detail->invoiceId ?? null);
                    foreach (array('totalInvoiceValue', 'totalInvoicePayout', 'totalInvoiceNotPayd', 'totalInvoiceOverPaid', 'totalInvoiceOverDue') as $i => $field) {
                        if ($payment == 'all' && $i > 0 && $corrections == 'no') continue;
                        check(isset($detail->{$field}) && abs(($detail->{$field} ?? 0) - ($expectedGroups[$detailClass][$i] ?? 0)) < 0.00001, 'Обобщение на контрагента само за периода: ' . $field . '/' . $case);
                    }
                }
                sort($actualIds);
                sort($expectedIds);
                check($actualIds === $expectedIds, 'Точните фактури, включително на началната дата: ' . $case);
                foreach (array('totalInvoiceValueAll', 'totalInvoicePayoutAll', 'totalInvoiceNotPaydAll', 'totalInvoiceOverPaidAll', 'totalInvoiceOverDueAll') as $i => $field) {
                    if ($payment == 'all' && $i > 0 && $corrections == 'no') continue;
                    check(isset($rec->{$field}) && abs(($rec->{$field} ?? 0) - $expectedTotals[$i]) < 0.00001, 'Обща сума само на показаните фактури: ' . $field . '/' . $case);
                }
            }
        }
    }
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
        check(deals_Helper::$conversions <= ($direction == 'all' ? 80 : 40), 'Отпадналите фактурни групи не се обработват повторно: ' . $case);
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
        $rec->fromDate = '2026-09-16';
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
            check(strpos($row->accountingDocuments, '#Mo1387') !== false, 'Корекцията сочи към МО1387');
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
        check($row->documentType == 'МО' && $row->invoiceNo == 'по #Pur2' && $row->paidAmount === '', 'Отделен ред, без измислено плащане по фактура');
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
    check(count($legacy) == 0, 'При Не погасените до стотинка не остават като неплатени с 0.00: ' . $direction);
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
    $expected = 'по #' . ($direction == 'out' ? 'Sal2' : 'Pur2');
    check($driver->verbal($rec, $detail)->invoiceNo == $expected && $driver->exportRow($rec, $detail)->invoiceNo == 'Неразпред. ' . $expected, 'По-кратък екранен хендъл при непроменен експорт: ' . $direction);
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

// И при Не обобщенията са сбор на показаните стотинки, без промяна във филтрите.
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $payment) {
        foreach (array(1, 1.95583) as $rate) {
            foreach (array(1, 101) as $count) {
                InvoiceTestData::reset();
                foreach (array('out', 'in') as $kind) {
                    for ($id = 1; $id <= $count; $id++) {
                        $values = array(100.004, 100.006, 100.006, 100.004, -30.006);
                        $payments = array(20.001, 20.004, 120.006, 100.001, 0.004);
                        $index = ($id - 1) % count($values);
                        InvoiceTestData::invoice($kind, $id, 'No cents ' . ($id % 2), '2026-10-05', $values[$index], $payments[$index], '2026-10-01', 'USD', $rate);
                    }
                }
                $rec = reportRec($direction, $payment);
                $rec->accountingCorrections = 'no';
                $rows = $driver->run($rec, $data);
                $totals = array_fill(0, 5, 0);
                $groups = array();
                $fields = array('totalInvoiceValue', 'totalInvoicePayout', 'totalInvoiceNotPayd', 'totalInvoiceOverPaid', 'totalInvoiceOverDue');
                $case = implode('/', array($direction, $payment, $rate, $count));
                foreach ($rows as $detail) {
                    $row = $driver->verbal($rec, $detail);
                    $cents = function ($value) { return (int)round((float)strip_tags($value ?? '0') * 100); };
                    $value = $cents($row->invoiceValueBaseCurr ?? '');
                    $paid = $cents($row->paidAmount ?? '');
                    $unpaid = $cents($row->invoiceCurrentSumm ?? '');
                    $overpaid = -$cents($row->invoiceOverSumm ?? '');
                    if ($payment != 'all') check($value - $paid === $unpaid + $overpaid, 'При Не остатъкът следва показаните стойност и платено: ' . $case);
                    $overdue = !empty($detail->dueDate) && $detail->dueDate < ($rec->checkDate ?? '') ? $unpaid : 0;
                    $amounts = $payment == 'all' ? array($value, 0, 0, 0, 0) : array($value, $paid, $unpaid, $overpaid, $overdue);
                    $sign = $direction == 'all' && ($detail->className ?? '') == 'purchase_Invoices' ? -1 : 1;
                    $group = $detail->invoiceGroup ?? $detail->contragent ?? '';
                    foreach ($amounts as $i => $amount) {
                        $totals[$i] += $sign * $amount;
                        $groups[$group][$i] = ($groups[$group][$i] ?? 0) + $amount;
                    }
                    $export = $driver->exportRow($rec, $detail);
                    check($value === (int)round(($export->invoiceValueBaseCurr ?? 0) * 100) && $paid === (int)round(($export->paidAmount ?? 0) * 100), 'При Не стойността и платеното в експорта са същите стотинки: ' . $case);
                }
                foreach ($rows as $detail) {
                    $group = $detail->invoiceGroup ?? $detail->contragent ?? '';
                    foreach ($fields as $i => $field) check((int)round(($detail->{$field} ?? 0) * 100) === $groups[$group][$i], 'При Не фирменото обобщение е сбор на редовете: ' . $field . '/' . $case);
                }
                foreach ($fields as $i => $field) check((int)round(($rec->{$field . 'All'} ?? 0) * 100) === $totals[$i], 'При Не общото обобщение е сбор на редовете: ' . $field . '/' . $case);
                check(empty(InvoiceTestData::$queries['acc_Items']) && empty(InvoiceTestData::$queries['acc_JournalDetails']), 'Закръгляването при Не не чете МО: ' . $case);
            }
        }
    }
}

// Стойността след отстъпка включва начисления ДДС; нулев ДДС не се добавя служебно.
foreach (array('out', 'in') as $direction) {
    foreach (array('no', 'yes') as $corrections) {
        foreach (array(0, 19) as $vat) {
            InvoiceTestData::reset();
            InvoiceTestData::invoice($direction, 1, 'VAT', '2026-10-05', 95 + $vat, 0);
            $class = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
            InvoiceTestData::$records[$class][0]->dealValue = 100;
            InvoiceTestData::$records[$class][0]->discountAmount = 5;
            InvoiceTestData::$records[$class][0]->vatAmount = $vat;
            $rec = reportRec($direction, 'all');
            $rec->accountingCorrections = $corrections;
            $rows = $driver->run($rec, $data);
            check(closeAmount($rec->totalInvoiceValueAll ?? 0, 95 + $vat), 'Стойност минус отстъпка плюс действителния ДДС: ' . $direction . '/' . $corrections . '/' . $vat);
            check(strpos($driver->info($rec, $data), 'Стойностите на фактурите са с ДДС (когато има начислено).') !== false, 'Изрично уточнение за ДДС в обобщението');
        }
    }
}

// Еднаква екранна антетка и седем настройки в реда на формата за всеки режим.
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all' => 'Всички', 'unpaid' => 'Неплатени', 'overdue' => 'Просрочени') as $payment => $paymentLabel) {
        foreach (array('no' => 'Не', 'yes' => 'Да') as $flag => $flagLabel) {
            InvoiceTestData::reset();
            InvoiceTestData::invoice($direction == 'in' ? 'in' : 'out', 1, 'Layout', '2026-10-05', 100, 20);
            $rec = reportRec($direction, $payment);
            $rec->accountingCorrections = $flag;
            $rec->sill = 2.5;
            $rec->seeProformаs = 'yes';
            $rows = $driver->run($rec, $data);
            $case = $direction . '/' . $payment . '/' . $flag;
            $params = $driver->fieldParams($rec);
            check($params['invoiceValue'] == 'caption=Стойност,tdClass=invoice-report-value' && $params['invoiceValueBaseCurr'] == 'caption=|Стойност|*<br>EUR,tdClass=invoice-report-base-value invoice-report-value', 'Общи заглавия и клас за помощната стойност: ' . $case);
            if ($payment != 'all' || $flag == 'yes') check(strpos($params['invoiceCurrentSumm'], 'caption=Състояние->|Неплатено|*<br>EUR') === 0, 'Общо заглавие за остатъка: ' . $case);
            $info = $driver->info($rec, $data);
            check(strpos($info, 'Обобщени резултати') < strpos($info, '>Филтър<'), 'Обобщението е преди филтъра: ' . $case);
            preg_match_all('/class="invoice-report-filter-row"[^>]*>(.*?)<\/div>/s', $info, $matches);
            $filters = array_map('strip_tags', $matches[1]);
            $expected = array('Конкретни: Всички', 'Групи: Всички', 'Фактури: ' . array('out' => 'Изходящи', 'in' => 'Входящи', 'all' => 'Всички')[$direction], 'Платено: ' . $paymentLabel,
                'Плащане: Всички', $payment == 'all' ? 'Разширена: Да' : 'По-малко от: 2.50 €', 'Корекции с МО: ' . $flagLabel);
            check($filters === $expected, 'Седем настройки, правилни стойности и ред: ' . $case);
            check(strpos($info, 'От дата') === false && strpos($info, 'До дата') === false, 'Датите не се повтарят във филтъра: ' . $case);
        }
    }
}

InvoiceTestData::reset();
InvoiceTestData::$records['doc_Folders'][42] = (object)array('id' => 42, 'title' => 'Тест & фирма');
InvoiceTestData::$records['crm_Groups'][7] = (object)array('id' => 7, 'name' => 'Доставчици');
$rec = reportRec('all', 'all');
$rec->contragent = '|42|';
$rec->crmGroup = '|7|';
$rec->paymentType = 'bank';
$info = $driver->info($rec);
check(strpos($info, 'Тест &amp; фирма') !== false && strpos($info, 'Доставчици') !== false && strpos($info, 'По банков път') !== false, 'Избрани контрагент, група и начин на плащане се вербализират чрез типовете');
check(strpos(strip_tags($info), 'Разширена: Не') !== false && strpos(strip_tags($info), 'Корекции с МО: Не') !== false, 'Старите записи показват Не по подразбиране');
$rec->contragent = '|';
check(strpos(strip_tags($driver->info($rec)), 'Конкретни: Всички') !== false, 'Празен keylist се изписва като Всички');
$rec->checkDate = '2025-12-31';
check(strpos($driver->fieldParams($rec)['invoiceValueBaseCurr'], 'Стойност|*<br>BGN') !== false, 'Заглавието следва основната валута за периода');
$driver->prices = false;
$info = $driver->info($rec);
check(strpos($info, 'invoice-report-results') === false && strpos($info, 'invoice-report-filter') !== false, 'Без права за цени остават само настройките');
$driver->prices = true;

// Датите са след общите полета в сивото каре; скритата антетка се уважава.
foreach (array(null, '2026-01-01') as $from) {
    $rec = reportRec('all', 'all');
    $rec->fromDate = $from;
    $head = array('title' => array('val' => 'AI'), 'lastRefreshed' => array('val' => 'сега'), 'checkDate' => array('val' => 'старо'));
    InvoiceReportTest::on_AfterGetFieldForLetterHead($driver, new embed_Manager(), $head, $rec, new stdClass());
    check(array_keys($head) === array('title', 'lastRefreshed', 'fromDate', 'checkDate'), 'Ред на датите и запазени общи полета в антетката');
    check($head['fromDate']['val'] == ($from ?? 'от началото') && $head['checkDate']['val'] == '2026-10-15', 'Верни дати или от началото');
}
$head = array();
InvoiceReportTest::on_AfterGetFieldForLetterHead($driver, new embed_Manager(), $head, $rec, new stdClass());
check($head === array(), 'Скритата антетка не се възстановява');
$head = array('title' => array('val' => 'AI'));
unset($rec->checkDate);
InvoiceReportTest::on_AfterGetFieldForLetterHead($driver, new embed_Manager(), $head, $rec, new stdClass());
check($head['checkDate']['val'] == dt::today(false), 'Липсващата крайна дата означава текуща дата');

// Нативен хендъл на МО като самия линк, при непроменено CSV означение.
InvoiceTestData::reset();
InvoiceTestData::invoice('in', 1, 'MO link', '2026-10-05', 100, 0);
articleFixture('in', 1, 1387, 20);
$rec = reportRec('in', 'unpaid');
$rec->accountingCorrections = 'yes';
$rows = $driver->run($rec, $data);
$detail = reset($rows);
$row = $driver->verbal($rec, $detail);
check(strpos($row->accountingDocuments, '<a href="/acc_Articles/single/1387">#Mo1387</a>') !== false, 'МО е директен линк с нативен хендъл');
check(strpos($row->accountingDocuments, 'anchor-arrow') === false, 'МО няма допълнителна стрелка');
check(strpos($driver->exportRow($rec, $detail)->accountingDocuments, 'МО1387 / 2026-10-01') !== false, 'CSV запазва старото означение на МО');
Mode::$plain = true;
check($driver->verbal($rec, $detail)->accountingDocuments == '#Mo1387 / 2026-10-01', 'МО в текстов режим без HTML');
Mode::$plain = false;

// „Днес“ се запазва като избор, а конкретна въведена дата остава фиксирана.
check(strpos($fieldset->params['checkDate'], 'placeholder=днес') !== false, 'Празната крайна дата се изписва като днес');
check(strpos($fieldset->params['useTodayAsCheckDate'], 'input=none,single=none') !== false, 'Служебният избор за днес не е отделно поле във формата');
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('all', 'unpaid', 'overdue') as $status) {
        foreach (array('no', 'yes') as $corrections) {
            foreach (array(null, '') as $blank) {
                dt::$date = '2026-10-26';
                InvoiceTestData::reset();
                foreach (array('out', 'in') as $kind) {
                    InvoiceTestData::invoice($kind, 1, 'Today', '2026-10-25', 100, 0, '2026-10-26');
                    InvoiceTestData::invoice($kind, 2, 'Today', '2026-10-25', 200, 0, '2026-10-27');
                    articleFixture($kind, 1, 1387, 10, '2026-10-26');
                    articleFixture($kind, 1, 1388, 20, '2026-10-27');
                }
                $rec = reportRec($direction, $status);
                $rec->checkDate = $blank;
                $rec->accountingCorrections = $corrections;
                $case = $direction . '/' . $status . '/' . $corrections . '/' . var_export($blank, true);
                $form = new InvoiceDateForm($rec);
                $driver->prepareForm($form);
                check(empty($rec->checkDate), 'Подготовката не попълва конкретна дата: ' . $case);
                $form->submitted = true;
                $driver->inputForm($form);
                check(($rec->useTodayAsCheckDate ?? null) == 'yes' && !$form->errors, 'Записва се изборът днес: ' . $case);
                $firstData = $driver->prepareData($rec);
                check(($rec->checkDate ?? null) == '2026-10-26', 'Първо изчисление към днес: ' . $case);
                $oldCount = count($firstData->recs ?? array());
                check($oldCount == ($direction == 'all' ? 2 : 1), 'Показват се само фактурите до първия ден: ' . $case);
                if ($corrections == 'yes') {
                    foreach ($firstData->recs ?? array() as $row) check(closeAmount($row->accountingCorrection ?? 0, 10), 'МО след първия ден не участва: ' . $case);
                }
                $saved = unserialize(serialize($rec));
                dt::$date = '2026-10-27';
                // Самото отваряне не преизчислява и не сменя датата на стария резултат.
                $head = array('title' => array('val' => 'AI'));
                InvoiceReportTest::on_AfterGetFieldForLetterHead($driver, new embed_Manager(), $head, $saved, new stdClass());
                check(strip_tags($head['checkDate']['val']) == '2026-10-26', 'Антетката пази датата на показвания резултат: ' . $case);
                $secondData = $driver->prepareData($saved);
                check(($saved->checkDate ?? null) == '2026-10-27' && ($saved->useTodayAsCheckDate ?? null) == 'yes', 'Обновяване на следващия ден без редакция: ' . $case);
                check(count($secondData->recs ?? array()) == 2 * $oldCount, 'Включват се и фактурите от новия ден: ' . $case);
                if ($corrections == 'yes') {
                    foreach ($secondData->recs ?? array() as $row) check(closeAmount($row->accountingCorrection ?? 0, ($row->invoiceId ?? 0) == 1 ? 30 : 0), 'МО от новия ден участва след обновяване: ' . $case);
                }
                $edit = new InvoiceDateForm(unserialize(serialize($saved)));
                $driver->prepareForm($edit);
                check(empty($edit->rec->checkDate), 'При повторна редакция остава днес: ' . $case);
                $fixed = clone $saved;
                $fixed->useTodayAsCheckDate = 'no';
                $fixed->checkDate = '2026-10-26';
                $fixedForm = new InvoiceDateForm($fixed);
                $driver->prepareForm($fixedForm);
                $fixedForm->submitted = true;
                $driver->inputForm($fixedForm);
                $fixedData = $driver->prepareData($fixed);
                check(($fixed->checkDate ?? null) == '2026-10-26' && ($fixed->useTodayAsCheckDate ?? null) == 'no' && count($fixedData->recs ?? array()) == $oldCount,
                    'Изрично въведената дата остава фиксирана: ' . $case);
            }
        }
    }
}

dt::$date = '2026-10-27';
$form = new InvoiceDateForm((object)array('fromDate' => '2026-10-28', 'checkDate' => null));
$form->submitted = true;
$driver->inputForm($form);
check(isset($form->errors['fromDate,checkDate']), 'Началната дата не може да е след днес при празна крайна дата');
$rec = reportRec('all', 'all');
$rec->checkDate = '2026-10-26';
$form = new InvoiceDateForm($rec);
$driver->prepareForm($form);
$driver->prepareData($rec);
check(($rec->checkDate ?? null) == '2026-10-26' && !isset($rec->useTodayAsCheckDate), 'Стара справка с конкретна дата не става автоматично динамична');
$rec->useTodayAsCheckDate = 'yes';
$form = new InvoiceDateForm($rec);
$form->values['checkDate'] = '2026-10-27';
$form->input(); // Тихото въвеждане преди prepare hook, както в embed_Manager.
$driver->prepareForm($form);
$form->input(); // Пълното въвеждане след prepare hook, както в core_Manager.
$form->submitted = true;
$driver->inputForm($form);
dt::$date = '2026-10-28';
$driver->prepareData($rec);
check(($rec->checkDate ?? null) == '2026-10-27' && ($rec->useTodayAsCheckDate ?? null) == 'no', 'Въведената днешна дата става фиксирана и след предишен избор днес');
$form = new InvoiceDateForm($rec);
$form->values['checkDate'] = null;
$driver->prepareForm($form);
$form->input();
$form->submitted = true;
$driver->inputForm($form);
$driver->prepareData($rec);
check(($rec->checkDate ?? null) == '2026-10-28' && ($rec->useTodayAsCheckDate ?? null) == 'yes', 'Изчистването на фиксирана дата отново включва днес');
dt::$date = '2026-10-15';

// Настройките се четат без стария резултат; не променяме режима на други действия.
foreach (array('edit', 'save', 'manage', 'refresh', 'EDIT', 'single', 'list', 'clonerec') as $action) {
    Mode::$values = array();
    $driver->beforeAction($action);
    check(!empty(Mode::$values[frame2_Reports::LIGHT_FETCH_MODE]) === in_array(strtolower($action), array('edit', 'save', 'manage', 'refresh')),
        'Леко четене само за настройките и обновяването: ' . $action);
}
Mode::$values = array();
check(strpos($fieldset->params['resultFilterHash'], 'input=none,single=none') !== false, 'Отпечатъкът не е потребителска настройка');

class InvoiceFailingReportTest extends InvoiceReportTest {
    protected function prepareRecs($rec, &$data = null) { throw new RuntimeException('Прекъснато изчисление'); }
}
foreach (array('no', 'yes') as $corrections) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice('out', 1, 'Guard out', '2026-10-03', 100, 20);
    InvoiceTestData::invoice('in', 1, 'Guard in', '2026-10-03', 80, 10);
    articleFixture('out', 1, 1387, 15);
    articleFixture('in', 1, 1388, 5);
    $complete = reportRec('all', 'all');
    $complete->accountingCorrections = $corrections;
    $complete->data = $driver->prepareData($complete);
    $expectedRows = serialize($complete->data->recs);
    $expectedValue = $complete->totalInvoiceValueAll;
    check(serialize($driver->exportRows($complete)) === $expectedRows, 'Потвърденият резултат се експортира непроменен: ' . $corrections);
    check(strpos($driver->info($complete, $complete->data), 'invoice-report-results') !== false, 'Потвърдените обобщения са видими: ' . $corrections);

    $changes = array('contragent' => '|999|', 'crmGroup' => '|999|', 'typeOfInvoice' => 'in', 'unpaid' => 'unpaid',
        'fromDate' => '2026-10-02', 'checkDate' => '2026-10-14', 'paymentType' => 'bank', 'sill' => 2.5,
        'seeProformаs' => 'yes', 'accountingCorrections' => $corrections == 'yes' ? 'no' : 'yes', 'useTodayAsCheckDate' => 'yes');
    foreach ($changes as $name => $value) {
        $changed = unserialize(serialize($complete));
        $changed->{$name} = $value;
        if ($name === 'useTodayAsCheckDate') $changed->checkDate = null;
        $case = $corrections . '/' . $name;
        check(strpos($driver->renderData($changed)->getContent(), acc_reports_InvoicesByContragent::RESULT_FILTER_WARNING) !== false,
            'Чуждият филтър не показва старата таблица: ' . $case);
        check(strpos($driver->info($changed, $changed->data), 'invoice-report-results') === false, 'Чуждият филтър не показва стари суми: ' . $case);
        $rejectedExport = false;
        try { $driver->exportRows($changed); } catch (RuntimeException $e) { $rejectedExport = $e->getMessage() === acc_reports_InvoicesByContragent::RESULT_FILTER_WARNING; }
        check($rejectedExport, 'Не се експортират данни за друг филтър: ' . $case);

        $form = new InvoiceDateForm($changed);
        $form->submitted = true;
        $driver->inputForm($form);
        $persisted = unserialize(serialize($form->rec)); // Записът на настройките преди frame2_Reports::refresh().
        check(empty($persisted->data->recs) && empty($persisted->resultFilterHash) && !empty($persisted->refreshData),
            'Преди изчислението новият филтър се записва без старите редове: ' . $case);
        $failed = new InvoiceFailingReportTest();
        try { $failed->prepareData(unserialize(serialize($persisted))); } catch (RuntimeException $e) {}
        check(strpos($driver->renderData($persisted)->getContent(), acc_reports_InvoicesByContragent::RESULT_FILTER_WARNING) !== false,
            'Прекъсването не връща стария резултат: ' . $case);
    }

    $form = new InvoiceDateForm(unserialize(serialize($complete)));
    $form->rec->title = 'Само ново заглавие';
    $form->rec->listItemsPerPage = 50;
    $form->submitted = true;
    $driver->inputForm($form);
    check(serialize($form->rec->data->recs) === $expectedRows && empty($form->rec->refreshData), 'Заглавието и размерът на страницата не обезсилват резултата: ' . $corrections);
    $form->rec->fromDate = '2026-10-16';
    $driver->inputForm($form);
    check($form->errors && serialize($form->rec->data->recs) === $expectedRows, 'Невалидна форма не премахва резултата: ' . $corrections);

    $normal = clone $complete;
    $normal->contragent = '|2|1|';
    $normal->crmGroup = null;
    $normal->sill = null;
    $normal->paymentType = null;
    $normal->data = $driver->prepareData($normal);
    $form = new InvoiceDateForm($normal);
    $form->rec->contragent = '|1|2|';
    $form->rec->crmGroup = '|';
    $form->rec->sill = '0.00';
    $form->rec->paymentType = ' ';
    $form->submitted = true;
    $driver->inputForm($form);
    check(empty($form->rec->refreshData) && isset($form->rec->data->resultFilterHash), 'Еквивалентните настройки имат един отпечатък: ' . $corrections);

    $legacy = unserialize(serialize($complete));
    $legacy->id = 700;
    unset($legacy->resultFilterHash, $legacy->data->resultFilterHash);
    check(strpos($driver->renderData($legacy)->getContent(), acc_reports_InvoicesByContragent::RESULT_FILTER_WARNING) !== false,
        'Непотвърден стар текущ резултат изисква обновяване: ' . $corrections);
    frame2_Reports::$selectedVersion = 1;
    check(serialize($driver->exportRows($legacy)) === $expectedRows, 'Избраната стара версия остава достъпна: ' . $corrections);
    check(strpos($driver->info($legacy, $legacy->data), 'invoice-report-results') !== false, 'Сумите на старата версия остават достъпни: ' . $corrections);
    frame2_Reports::$selectedVersion = null;
    $form = new InvoiceDateForm($legacy);
    $form->submitted = true;
    $driver->inputForm($form);
    check(!empty($form->rec->refreshData) && empty($form->rec->data->recs), 'Записът на стар текущ резултат го потвърждава чрез ново изчисление: ' . $corrections);

    // Грешка от нормалния обработчик на frame2 също не трябва да оставя стари обобщения.
    $error = clone $complete;
    $error->data = 'ERROR';
    check(strpos($driver->info($error, $error->data), 'invoice-report-results') === false, 'Грешката не показва стари суми: ' . $corrections);
    check($complete->totalInvoiceValueAll === $expectedValue && serialize($complete->data->recs) === $expectedRows,
        'Проверките не променят завършения резултат: ' . $corrections);

    $complete->id = 701;
    unset($complete->log);
    frame2_Reports::$logs[701] = array(array('time' => '2026-10-14 12:00:00', 'msg' => 'Предишен лог'));
    frame2_Reports::$logReads = 0;
    $complete->data = $driver->prepareData($complete);
    check($complete->log === frame2_Reports::$logs[701] && frame2_Reports::$logReads === 1, 'Лекото обновяване запазва предишния лог: ' . $corrections);
    $complete->data = $driver->prepareData($complete);
    check(frame2_Reports::$logReads === 1, 'Вече зареденият лог не се чете повторно: ' . $corrections);
}

// КИ намаляват неплатеното, без да скриват реална надплата или да променят нетния остатък.
$noteCases = array(
    1 => array(100, 30, 'invoice', 70, 0),
    2 => array(-20, 0, 'dc_note', -20, 0),
    3 => array(10, 0, 'dc_note', 10, 0),
    4 => array(50, 60, 'invoice', 0, 10),
    5 => array(-12, -12, 'dc_note', 0, 0),
    6 => array(-25, -10, 'dc_note', -15, 0),
    7 => array(15, 5, 'dc_note', 10, 0),
    8 => array(15, 20, 'dc_note', 0, 5),
);
foreach (array('out', 'in', 'all') as $direction) {
    foreach (array('no', 'yes') as $corrections) {
        foreach (array('all', 'unpaid', 'overdue') as $status) {
            foreach (array(array('2025-10-15', 'BGN', 1), array('2026-10-15', 'EUR', 1), array('2026-10-15', 'USD', 2)) as $currencyCase) {
                list($cutoff, $currency, $rate) = $currencyCase;
                InvoiceTestData::reset();
                $date = substr($cutoff, 0, 8) . '01';
                $due = substr($cutoff, 0, 8) . '05';
                foreach ($direction == 'all' ? array('out', 'in') : array($direction) as $kind) {
                    foreach ($noteCases as $id => $noteCase) {
                        InvoiceTestData::invoice($kind, $id, 'Credit balance', $due, $noteCase[0], $noteCase[1], $date, $currency, $rate);
                        $class = $kind == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
                        end(InvoiceTestData::$records[$class])->type = $noteCase[2];
                    }
                    InvoiceTestData::combine($kind, array(2, 3, 4, 5, 6, 7, 8, 1));
                }
                $rec = reportRec($direction, $status);
                $rec->checkDate = $cutoff;
                $rec->accountingCorrections = $corrections;
                $rows = $driver->run($rec, $data);
                $totals = array_fill(0, 5, 0);
                $case = implode('/', array($direction, $corrections, $status, $cutoff, $currency));
                $expectedCount = $status == 'all' ? 8 : ($status == 'unpaid' ? 7 : 3);
                check(count($rows) == $expectedCount * ($direction == 'all' ? 2 : 1), 'КИ/ДИ не добавят или премахват документи: ' . $case);
                foreach ($rows as $detail) {
                    list($value, $paid, $type, $notPaid, $overPaid) = $noteCases[$detail->invoiceId];
                    $correction = 0;
                    if ($status == 'all' && $corrections == 'no') $notPaid = $overPaid = 0;
                    $row = $driver->verbal($rec, $detail);
                    $number = function ($value) { return (float)strip_tags($value ?? '0'); };
                    check(closeAmount($number($row->invoiceCurrentSumm ?? 0), $notPaid * $rate)
                        && closeAmount($number($row->invoiceOverSumm ?? 0), $overPaid * $rate), 'Точната колона и знак за КИ/ДИ/надплатена фактура: ' . $detail->invoiceId . '/' . $case);
                    $export = $driver->exportRow($rec, $detail);
                    check(closeAmount($export->paidAmount ?? 0, $paid * $rate), 'Експортът пази реалното платено и по известията: ' . $case);
                    check(closeAmount($export->invoiceCurrentSumm ?? 0, $notPaid * $rate)
                        && closeAmount($export->invoiceOverSumm ?? 0, $overPaid * $rate), 'Екран и експорт разнасят КИ/ДИ и надплатите еднакво: ' . $case);
                    if ($status != 'all' || $corrections == 'yes') {
                        check(closeAmount($value * $rate - $paid * $rate - $correction, ($notPaid - $overPaid) * $rate), 'КИ/ДИ запазват равенството стойност - платено - МО = остатък: ' . $case);
                        $amounts = array($value * $rate, $paid * $rate, $notPaid * $rate, -$overPaid * $rate, max(0, $notPaid * $rate));
                        $sign = $direction == 'all' && $detail->className == 'purchase_Invoices' ? -1 : 1;
                        foreach ($amounts as $i => $amount) $totals[$i] += $sign * $amount;
                    }
                }
                if ($status != 'all' || $corrections == 'yes') {
                    foreach (array('totalInvoiceValue', 'totalInvoicePayout', 'totalInvoiceNotPayd', 'totalInvoiceOverPaid', 'totalInvoiceOverDue') as $i => $field) {
                        check(closeAmount($rec->{$field . 'All'} ?? 0, $totals[$i]), 'Общото обобщение сумира показаните колони след КИ: ' . $field . '/' . $case);
                    }
                    foreach ($rows as $detail) {
                        $expected = $status == 'overdue' ? 90 : 55;
                        check(closeAmount($detail->totalInvoiceNotPayd, $expected * $rate)
                            && closeAmount($detail->totalInvoiceOverPaid, $status == 'overdue' ? 0 : -15 * $rate), 'Фирменият сбор отчита КИ и отделя само истинската надплата: ' . $case);
                    }
                }
            }
        }
    }
}

// МО променя остатъка по КИ, но не го превръща в надплата по фактура.
foreach (array(5, -25) as $correction) {
    InvoiceTestData::reset();
    InvoiceTestData::invoice('in', 1, 'Credit with MO', '2026-10-14', -20, 0);
    InvoiceTestData::$records['purchase_Invoices'][0]->type = 'dc_note';
    articleFixture('in', 1, 803, $correction);
    $rec = reportRec('in', 'all');
    $rec->accountingCorrections = 'yes';
    $rows = $driver->run($rec, $data);
    $detail = reset($rows);
    $row = $driver->verbal($rec, $detail);
    check(count($rows) == 1 && closeAmount($rec->totalAccountingCorrectionAll, $correction)
        && closeAmount($rec->totalInvoiceNotPaydAll, -20 - $correction) && closeAmount($rec->totalInvoiceOverPaidAll, 0), 'КИ запазва точния остатък след МО: ' . $correction);
    check(closeAmount(strip_tags($row->invoiceCurrentSumm ?? '0'), -20 - $correction)
        && empty($row->invoiceOverSumm), 'Остатъкът след МО по КИ е в правилната колона: ' . $correction);
}

// Петте КИ от потребителския пример намаляват точно неплатеното с 64.29 EUR.
foreach (array('out', 'in') as $direction) {
    foreach (array('no', 'yes') as $corrections) {
        InvoiceTestData::reset();
        InvoiceTestData::invoice($direction, 1, 'Credit example', '2026-10-14', 46210.98, 17330.18);
        foreach (array(2 => -4.01, 3 => -11.54, 4 => -10.02, 5 => -21.04, 6 => -17.68) as $id => $value) {
            InvoiceTestData::invoice($direction, $id, 'Credit example', '2026-10-14', $value, 0);
            $class = $direction == 'out' ? 'sales_Invoices' : 'purchase_Invoices';
            end(InvoiceTestData::$records[$class])->type = 'dc_note';
        }
        InvoiceTestData::combine($direction, array(2, 3, 4, 5, 6, 1));
        $rec = reportRec($direction, 'unpaid');
        $rec->accountingCorrections = $corrections;
        $rows = $driver->run($rec, $data);
        check(closeAmount($rec->totalInvoiceValueAll, 46146.69) && closeAmount($rec->totalInvoicePayoutAll, 17330.18)
            && closeAmount($rec->totalInvoiceNotPaydAll, 28816.51) && closeAmount($rec->totalInvoiceOverPaidAll, 0), 'Точните суми от потребителския пример: ' . $direction . '/' . $corrections);
        $credit = $rows[2];
        unset($credit->creditNoteBalance);
        $old = $driver->verbal($rec, $credit);
        check(empty($old->invoiceCurrentSumm) && closeAmount(strip_tags($old->invoiceOverSumm ?? '0'), 4.01), 'Стара снимка не смесва нови колони със стари обобщения');
    }
}

// Реален BCKP пример: CSV трябва да съдържа показаните стотинки, без скрити дробни части.
foreach (array('no', 'yes') as $corrections) {
    $rec = reportRec('in', 'all');
    $rec->accountingCorrections = $corrections;
    $detail = (object) array('className' => 'purchase_Invoices', 'invoiceId' => 1, 'invoiceNo' => '0417542287',
        'invoiceDate' => '2025-11-13', 'dueDate' => '2025-11-24', 'currencyId' => 'BGN', 'rate' => 1.95583,
        'invoiceValue' => 1858.505085, 'invoicePayout' => 1858.505085 / 1.95583, 'invoiceCurrentSumm' => 0,
        'type' => 'invoice', 'contragent' => 'CSV precision');
    $export = $driver->exportRow($rec, $detail);
    check(closeAmount($export->invoiceValue, 3634.92), 'CSV закръгля и стойността във валутата на фактурата: ' . $corrections);
    check(closeAmount($export->paidAmount, 1858.51) && closeAmount($export->invoiceValueBaseCurr, 1858.51), 'CSV няма скрита точност в стойността и платеното: ' . $corrections);
    check(closeAmount($export->paidAmount + $export->paidAmount, 3717.02), 'Сборът на изнесените плащания е сбор на показаните стотинки: ' . $corrections);
}

echo "OK: {$checks} invoice report checks\n";
