<?php

/**
 * BGERP_SUMMARY_TEST_SOCKET=/tmp/test.sock php acc/tests/DocumentSummary.php
 * Изолирана MariaDB с база test; използва само временни таблици, без конфигурацията на инсталацията.
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
$socket = getenv('BGERP_SUMMARY_TEST_SOCKET');
if (!$socket) exit("Set BGERP_SUMMARY_TEST_SOCKET to an isolated MariaDB socket.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($value) { if (!$value) throw new RuntimeException('Expectation failed'); }
function error($message) { throw new RuntimeException($message); }
function tr($value) { return $value; }
function setIfNot(&$value, $default) { if (!isset($value)) $value = $default; }

$root = dirname(__DIR__, 2);
foreach (array('BaseClass', 'FieldSet', 'Query', 'Plugin', 'Pager', 'Array', 'String') as $class) {
    require $root . '/core/' . $class . '.class.php';
}
class_alias('core_Array', 'arr');
class_alias('core_String', 'str');
class core_Type
{
    public $params = array();
    public static function getByName($name) { return new self(); }
    public function fromMysql($value) { return $value; }
    public function defVal() { return 0; }
}
class core_Mvc extends core_FieldSet
{
    public $db;
    public $dbTableName = 'sales_sales';
    public $className = 'SummaryDeal';
}
class core_Manager extends core_Mvc
{
    public $replicaCalls = 0;
    public function callOnReplica($callback) { $this->replicaCalls++; return $callback(); }
}
class core_Master extends core_Manager {}
class core_Debug
{
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class_alias('core_Debug', 'DEBUG');
class Mode { public static function is($name) { return false; } }
class Request { public static function get($name) { return null; } }
class doc_Setup
{
    public static $vat = 'no';
    public static function get($name) { return self::$vat; }
}
class acc_Setup
{
    public static $oldCurrency = 'BGN';
    public static function getEurozoneDate() { return '2026-01-01'; }
    public static function getDefaultCurrencyCode($date) { return $date < self::getEurozoneDate() ? self::$oldCurrency : 'EUR'; }
}
class acc_Periods
{
    public static function getBaseCurrencyCode($date = null) { return acc_Setup::getDefaultCurrencyCode($date ?? dt::today()); }
}
class dt
{
    public static function today() { return '2026-11-19'; }
    public static function addDays($days, $date, $time) { return date('Y-m-d', strtotime($date . ' ' . $days . ' days')); }
    public static function verbal2mysql($date, $time = true) { return substr($date ?? self::today(), 0, 10); }
}
class cls
{
    public static $models = array();
    public static function &get($name) { return self::$models[$name]; }
    public static function getClassName($object) { return get_class($object); }
}
require $root . '/deals/DealBase.class.php';
require $root . '/deals/DealMaster.class.php';
require $root . '/deals/Helper.class.php';
require $root . '/acc/plg/DocumentSummary.class.php';

class SummaryDeal extends deals_DealMaster
{
    public $valiorFld = 'valior';
    public $amountIsInNotInBaseCurrency = false;
    public function summaryPlugin($plugin) { $this->_plugins = array($plugin); }
}
class SummaryRowDeal extends SummaryDeal
{
    public $summaryRecFields = 'state,createdOn,valior';
    public function fillSummaryRec(&$rec, &$fields) {}
}
class SummaryCustomPlugin extends core_Plugin
{
    public static function on_AfterFillSummaryRec($mvc, $res, &$rec, &$fields) {}
}
class SummaryTestDb
{
    public $connection;
    public $resultRows = 0;
    public $queries = 0;
    public function __construct($socket) { $this->connection = new mysqli('localhost', 'root', '', 'test', 0, $socket); }
    public function query($sql)
    {
        $result = $this->connection->query($sql);
        if ($result === false) throw new RuntimeException($this->connection->error . "\n" . $sql);
        if (is_object($result)) { $this->queries++; $this->resultRows = $result->num_rows; }
        return $result;
    }
    public function escape($value) { return $this->connection->real_escape_string($value); }
    public function fetchObject($result) { return $result->fetch_object(); }
    public function fetchArray($result) { return $result->fetch_assoc(); }
    public function freeResult($result) { $result->free(); }
}

$db = new SummaryTestDb($socket);
$db->query("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY'");
$db->query("CREATE TEMPORARY TABLE sales_sales (
    id INT PRIMARY KEY, state VARCHAR(16), created_on DATETIME, valior DATE,
    folder_id INT, thread_id INT, charge_vat VARCHAR(16), amount_deal DOUBLE, amount_vat DOUBLE,
    amount_delivered DOUBLE, amount_paid DOUBLE, amount_bl DOUBLE, amount_invoiced DOUBLE)");
$db->query('CREATE TEMPORARY TABLE doc_folders (id INT PRIMARY KEY, shared VARCHAR(64))');
$db->query('CREATE TEMPORARY TABLE doc_threads (id INT PRIMARY KEY, shared VARCHAR(64))');
$db->query("INSERT INTO doc_folders VALUES (1, '|198|'), (2, '')");
$db->query("INSERT INTO doc_threads VALUES (1, ''), (2, '|198|'), (3, '')");

// Всички състояния, двата периода, липсващ вальор и варианти на ДДС; третата нишка е недостъпна.
$states = array('draft', 'pending', 'active', 'closed', 'stopped', null, 'rejected');
$vats = array('yes', 'separate', 'no', 'exempt');
$values = array();
for ($i = 1; $i <= 420; $i++) {
    $state = $states[$i % count($states)];
    $valior = $i % 3 === 0 ? 'NULL' : ($i % 3 === 1 ? "'2025-12-31'" : "'2026-01-01'");
    $created = $i % 2 ? '2025-06-01 12:00:00' : '2026-06-01 12:00:00';
    $net = ($i % 2 ? -1 : 1) * $i * 0.13;
    $vat = $i % 5 === 0 ? 'NULL' : ($i % 5 === 1 ? '0' : (string) ($net * 0.2));
    $amount = $i % 11 === 0 ? 'NULL' : (string) ($net * 1.2);
    $paid = $i % 7 === 0 ? 'NULL' : (string) ($net * 0.7);
    $stateSql = $state === null ? 'NULL' : "'{$state}'";
    $folder = $i % 2 + 1;
    $thread = $i % 3 + 1;
    $values[] = "({$i}, {$stateSql}, '{$created}', {$valior}, {$folder}, {$thread}, '{$vats[$i % 4]}', " . ($net * 1.2) . ", {$vat}, {$amount}, {$paid}, {$paid}, {$amount})";
}
$db->query('INSERT INTO sales_sales VALUES ' . implode(',', $values));

foreach (array('doc_Folders' => 'doc_folders', 'doc_Threads' => 'doc_threads') as $class => $table) {
    $model = new core_Mvc();
    $model->dbTableName = $table;
    $model->FLD('shared', 'varchar');
    cls::$models[$class] = $model;
}
$checks = 0;
function checkSummary($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
function summaryFixture($rowMode, $filter = null, $replica = false, $variant = null)
{
    global $db;
    $mvc = $rowMode ? new SummaryRowDeal() : new SummaryDeal();
    $mvc->db = $db;
    $mvc->summaryPlugin(new acc_plg_DocumentSummary());
    foreach (array('id', 'state', 'createdOn', 'valior', 'folderId', 'threadId', 'chargeVat', 'amountDeal', 'amountVat', 'amountDelivered', 'amountPaid', 'amountBl', 'amountInvoiced') as $name) {
        $mvc->FLD($name, 'double');
    }
    $query = new core_Query(array('mvc' => $mvc));
    $query->EXT('folderShared', 'doc_Folders', 'externalName=shared,externalKey=folderId');
    $query->EXT('threadShared', 'doc_Threads', 'externalName=shared,externalKey=threadId');
    $query->where("LOCATE('|198|', #folderShared) OR LOCATE('|198|', #threadShared)");
    $query->where("#state != 'rejected' OR #state IS NULL");
    if ($filter) $query->where($filter);
    if ($variant === 'having') {
        $query->XPR('dealRound', 'double', 'ROUND(COALESCE(#amountDeal, 0), 2)');
        $query->where('#dealRound > 10');
    } elseif ($variant === 'union' || $variant === 'unionAll') {
        $query->setUnion('#id <= 210');
        $query->setUnion('#id > 100');
        $query->useUnionAll = $variant === 'unionAll';
    } elseif ($variant === 'addId') {
        $query->addId = 5;
    }
    $query->onReplica = $replica;
    $data = (object) array('query' => $query);
    $mvc->prepareListSummary_($data);
    $res = null;
    acc_plg_DocumentSummary::on_AfterPrepareListSummary($mvc, $res, $data);

    return array($mvc, $data);
}

foreach (array('yes', 'no') as $showVat) {
    doc_Setup::$vat = $showVat;
    $cases = array(array(null, null), array("#valior < '2026-01-01'", null), array("#valior >= '2026-01-01'", null),
        array('#valior IS NULL', null), array('#id = -1', null), array('#amountDeal > 0', null),
        array(null, 'having'), array(null, 'union'), array(null, 'unionAll'), array(null, 'addId'));
    foreach ($cases as $case) {
        list($filter, $variant) = $case;
        list($mvc, $data) = summaryFixture(false, $filter, true, $variant);
        checkSummary($db->resultRows === 1, 'SQL връща един ред, включително при празна селекция');
        $actual = $data->listSummary->summary;
        $count = $data->query->knownCount->cnt;
        list($rowMvc, $rowData) = summaryFixture(true, $filter, false, $variant);
        $expected = $rowData->listSummary->summary;
        checkSummary(array_keys($actual) === array_keys($expected), 'Еднакви обобщени полета');
        foreach ($expected as $name => $field) {
            checkSummary(($actual[$name]->quantity ?? null) === ($field->quantity ?? null), 'Еднакви бройки: ' . $name);
            checkSummary(abs(($actual[$name]->amount ?? 0) - ($field->amount ?? 0)) < 0.000001, 'Еднакви суми до 6 знака: ' . $name);
            checkSummary(($actual[$name]->measure ?? null) === ($field->measure ?? null), 'Еднаква мярка: ' . $name);
        }
        checkSummary($count === $rowData->query->knownCount->cnt, 'Пълният брой е запазен за страницирането');
        checkSummary($data->hasDocumentBeforeEu === $rowData->hasDocumentBeforeEu, 'Еднакъв индикатор за документи преди еврото');
        checkSummary($mvc->replicaCalls === 1, 'Агрегирането използва репликата');
    }
}

// Нестандартни обработвачи и валутни настройки остават при обработката по записи.
list($mvc, $data) = summaryFixture(false);
$method = new ReflectionMethod('acc_plg_DocumentSummary', 'fetchDealSummary');
$method->setAccessible(true);
$fields = $data->listSummary->mvc->selectFields('#summary');
$query = $data->listSummary->query;
$mvc->summaryPlugin(new SummaryCustomPlugin());
checkSummary($method->invoke(null, $mvc, $query, $fields, 'EUR', '2026-01-01') === null, 'Плъгин с обработка на записи използва стария път');
$mvc->summaryPlugin(new acc_plg_DocumentSummary());
acc_Setup::$oldCurrency = 'USD';
checkSummary($method->invoke(null, $mvc, $query, $fields, 'EUR', '2026-01-01') === null, 'Други валути използват стария път');
acc_Setup::$oldCurrency = 'EUR';
list($mvc, $data) = summaryFixture(false);
list($rowMvc, $rowData) = summaryFixture(true);
foreach ($rowData->listSummary->summary as $name => $field) {
    checkSummary(abs(($data->listSummary->summary[$name]->amount ?? 0) - ($field->amount ?? 0)) < 0.000001, 'Система без смяна на основната валута: ' . $name);
}
echo "OK: {$checks} checks against per-document summaries, real Query builder and MariaDB.\n";
