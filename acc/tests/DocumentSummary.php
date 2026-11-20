<?php

/**
 * Сравнява обобщението, сумирано в базата, с обхождането документ по документ
 *
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php acc/tests/DocumentSummary.php
 * Иска изолирана MariaDB с празна база test; ползва само временни таблици, без конфигурацията на инсталацията.
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
function reportException($e) { $GLOBALS['reported'][] = $e; }

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
class core_exception_Db extends Exception
{
    public $dump = array();
    public function getDump() { return $this->dump; }
}
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
}
class acc_Periods
{
    // Границата между валутите; различна от датата на еврозоната само в теста за смесена група
    public static $border = '2026-01-01';
    public static function getBaseCurrencyCode($date = null) { return ($date ?? dt::today()) < self::$border ? acc_Setup::$oldCurrency : 'EUR'; }
}
class currency_CurrencyRates
{
    public static function convertAmount($amount, $date, $from, $to) { return $amount * 0.9; }
}
class dt
{
    public static function today() { return '2026-11-19'; }
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
    public function summaryPlugins($plugins) { $this->_plugins = $plugins; }
}
// Собственият fillSummaryRec налага обхождането документ по документ - еталонът за сравнение
class SummaryRowDeal extends SummaryDeal
{
    public $summaryRecFields = 'state,createdOn,valior';
    public function fillSummaryRec(&$rec, &$fields) {}
}
class SummaryNoValiorDeal extends SummaryDeal { public $valiorFld; }
class SummaryNoValiorRowDeal extends SummaryRowDeal { public $valiorFld; }
class SummaryFillPlugin extends core_Plugin
{
    public static function on_AfterFillSummaryRec($mvc, $res, &$rec, &$fields) {}
}
class SummaryReadPlugin extends core_Plugin
{
    public static function on_AfterRead($mvc, &$rec) {}
}
class SummarySelectPlugin extends core_Plugin
{
    public static $cancel = false;
    public static function on_BeforeSelect($mvc, &$res, $query)
    {
        if (self::$cancel) return false;
        if (!in_array('#id <= 100', $query->where)) $query->where('#id <= 100');
    }
}
class SummaryTestDb
{
    public $connection;
    public $resultRows = 0;
    public $aggregates = 0;
    public $rowQueries = 0;
    public function __construct($socket) { $this->connection = new mysqli('localhost', 'root', '', 'test', 0, $socket); }
    public function query($sql)
    {
        $result = $this->connection->query($sql);
        if ($result === false) throw new RuntimeException($this->connection->error . "\n" . $sql);
        if (is_object($result)) {
            $this->resultRows = $result->num_rows;
            if (strpos($sql, 'AS `dsRows`') !== false) {
                $this->aggregates++;
            } elseif (strpos($sql, 'amountDealCalc') !== false) {
                $this->rowQueries++;
            }
        }
        return $result;
    }
    public function escape($value) { return $this->connection->real_escape_string($value); }
    public function fetchObject($result) { return $result->fetch_object(); }
    public function fetchArray($result) { return $result->fetch_assoc(); }
    public function freeResult($result) { $result->free(); }
}

$db = new SummaryTestDb($socket);
$db->query("SET SESSION sql_mode = '" . (getenv('BGERP_SUMMARY_TEST_SQL_MODE') ?: 'ONLY_FULL_GROUP_BY') . "'");
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

// Случайни суми в голям брой документи - за отклонението от закръгляването документ по документ
mt_srand(20261120);
$values = array();
for ($i = 1001; $i <= 21000; $i++) {
    $valior = mt_rand(0, 9) === 0 ? 'NULL' : "'" . date('Y-m-d', mt_rand(strtotime('2014-01-01'), strtotime('2026-11-19'))) . "'";
    $created = date('Y-m-d H:i:s', mt_rand(strtotime('2014-01-01'), strtotime('2026-11-19')));
    $deal = mt_rand(100, 5000000) / 100;
    $vat = mt_rand(0, 4) === 0 ? 0 : round($deal / 6, 2);
    $part = mt_rand(0, 100) / 100;
    $values[] = "({$i}, '" . $states[mt_rand(0, 4)] . "', '{$created}', {$valior}, 1, 1, '{$vats[mt_rand(0, 3)]}', {$deal}, {$vat}, " . round($deal * $part, 2) . ', ' . round($deal * $part, 2) . ', ' . round($deal * (1 - $part), 2) . ', ' . round($deal * $part, 2) . ')';
    if (count($values) === 1000) {
        $db->query('INSERT INTO sales_sales VALUES ' . implode(',', $values));
        $values = array();
    }
}

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
function summaryFixture($class, $filter = null, $replica = false, $variant = null, $plugins = array())
{
    global $db;
    $mvc = new $class();
    $mvc->db = $db;
    $mvc->summaryPlugins(array_merge(array(new acc_plg_DocumentSummary()), $plugins));
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
    } elseif ($variant === 'addIdVisible' || $variant === 'addIdHidden') {
        // Търсене по номер: думите не намират нищо; документ 8 е достъпен, документ 5 е в недостъпна папка и нишка
        $search = "LOCATE(' nothing', #chargeVat)";
        $query->where($search);
        $query->searchConditions[$search] = $search;
        $query->addId = $variant === 'addIdVisible' ? 8 : 5;
    }
    $query->onReplica = $replica;
    $data = (object) array('query' => $query);
    $mvc->prepareListSummary_($data);
    $res = null;
    $db->aggregates = $db->rowQueries = 0;
    acc_plg_DocumentSummary::on_AfterPrepareListSummary($mvc, $res, $data);

    return array($mvc, $data, $db->aggregates, $db->rowQueries, $db->resultRows);
}
// Сумирането в базата срещу обхождането: еднакви полета, броячи, брой за страницирането и суми
function compareSummaries($class, $rowClass, $filter = null, $variant = null, $plugins = array(), $expectAggregate = true, $expectRowQueries = 1, $expectAttempts = 0)
{
    list($mvc, $data, $aggregates, $rowQueries, $resultRows) = summaryFixture($class, $filter, true, $variant, $plugins);
    if ($expectAggregate) {
        checkSummary($aggregates === 1 && $rowQueries === 0, 'Сумира се в базата с една заявка, без да се четат документите');
        checkSummary($resultRows <= 2, 'Най-много по един ред за валутен период');
        checkSummary($mvc->replicaCalls === 1, 'Сумирането спазва репликата на заявката');
    } else {
        checkSummary($aggregates === $expectAttempts && $rowQueries === $expectRowQueries, 'Остава обхождането документ по документ');
    }
    list($rowMvc, $rowData, $rowAggregates) = summaryFixture($rowClass, $filter, false, $variant, $plugins);
    checkSummary($rowAggregates === 0, 'Еталонът обхожда документите');
    $actual = $data->listSummary->summary;
    $expected = $rowData->listSummary->summary;
    $count = $rowData->query->knownCount->cnt;
    checkSummary(array_keys($actual) === array_keys($expected), 'Еднакви обобщени полета');
    $maxDiff = 0;
    foreach ($expected as $name => $field) {
        checkSummary(($actual[$name]->quantity ?? null) === ($field->quantity ?? null), 'Еднакви бройки: ' . $name);
        checkSummary(($actual[$name]->measure ?? null) === ($field->measure ?? null), 'Еднаква мярка: ' . $name);
        checkSummary(isset($actual[$name]->amount) === isset($field->amount), 'Еднакво наличие на сума: ' . $name);
        // Обхождането закръгля всеки документ до 6 знака, а тук се закръгля сумата на периода
        $diff = abs(($actual[$name]->amount ?? 0) - ($field->amount ?? 0));
        checkSummary($diff <= $count * 0.0000005 + 0.000001, "Сумите се различават само в закръглянето: {$name} ({$diff})");
        $maxDiff = max($maxDiff, $diff);
    }
    checkSummary($data->query->knownCount->cnt === $count, 'Пълният брой е запазен за страницирането');
    checkSummary($data->query->knownCount->signature === $rowData->query->knownCount->signature, 'Броят е за същата заявка');
    checkSummary($data->hasDocumentBeforeEu === $rowData->hasDocumentBeforeEu, 'Еднакъв индикатор за документи преди еврото');

    return array($count, $maxDiff);
}

$maxDiff = $maxCount = 0;
foreach (array('yes', 'no') as $showVat) {
    doc_Setup::$vat = $showVat;
    $cases = array(array(null, null), array("#valior < '2026-01-01'", null), array("#valior >= '2026-01-01'", null),
        array('#valior IS NULL', null), array('#id = -1', null), array('#amountDeal > 0', null), array('#id <= 420', null),
        array(null, 'having'), array(null, 'union'), array(null, 'unionAll'), array(null, 'addId'), array('#id = -1', 'addId'));
    foreach ($cases as $case) {
        list($count, $diff) = compareSummaries('SummaryDeal', 'SummaryRowDeal', $case[0], $case[1]);
        $maxDiff = max($maxDiff, $diff);
        $maxCount = max($maxCount, $count);
    }
}

// Документът, намерен по номер, влиза в обобщението само ако минава достъпа и останалите филтри
list($count) = compareSummaries('SummaryDeal', 'SummaryRowDeal', null, 'addIdVisible');
checkSummary($count === 1, 'Достъпният документ, намерен по номер, е в обобщението');
list($count) = compareSummaries('SummaryDeal', 'SummaryRowDeal', null, 'addIdHidden');
checkSummary($count === 0, 'Документ от недостъпна папка не влиза в обобщението по номер');

// Модел без вальор: един ред, без преизчисляване по валути
compareSummaries('SummaryNoValiorDeal', 'SummaryNoValiorRowDeal');
list(, $data) = summaryFixture('SummaryNoValiorDeal');
checkSummary($data->hasDocumentBeforeEu === false, 'Без вальор няма индикатор за документи преди еврото');

// Събитието преди четене се изпълнява и върху сумиращата заявка (напр. границата на Аида за секретни папки)
list($count) = compareSummaries('SummaryDeal', 'SummaryRowDeal', null, null, array(new SummarySelectPlugin()));
checkSummary($count > 0 && $count <= 100, 'Условието от събитието преди четене стеснява и сумите');
// Отказано четене: няма заявка към базата и обобщението е празно и по двата начина
SummarySelectPlugin::$cancel = true;
list($count) = compareSummaries('SummaryDeal', 'SummaryRowDeal', null, null, array(new SummarySelectPlugin()), false, 0);
checkSummary($count === 0, 'Отказаното четене не връща документи');
SummarySelectPlugin::$cancel = false;

// Обработка на отделните документи в PHP оставя обхождането
compareSummaries('SummaryDeal', 'SummaryRowDeal', null, null, array(new SummaryFillPlugin()), false);
compareSummaries('SummaryDeal', 'SummaryRowDeal', null, null, array(new SummaryReadPlugin()), false);

// Система без смяна на основната валута и система с друга стара валута
foreach (array('EUR', 'USD') as $oldCurrency) {
    acc_Setup::$oldCurrency = $oldCurrency;
    compareSummaries('SummaryDeal', 'SummaryRowDeal');
}
acc_Setup::$oldCurrency = 'BGN';

// Група с документи в две валути не се преизчислява наведнъж: сумите се отхвърлят и документите се обхождат
acc_Periods::$border = '2025-09-01';
compareSummaries('SummaryDeal', 'SummaryRowDeal', null, null, array(), false, 1, 1);
acc_Periods::$border = '2026-01-01';

// Опцията на филтъра добавя оттеглените само към собственото си условие, не към условието за достъп преди нея
$filterWhere = function ($option) {
    $mvc = new SummaryDeal();
    $query = new core_Query(array('mvc' => $mvc));
    $query->where("#folderId = 1");
    $mvc->filterListFilterByOption($option, $query);

    return $query->where;
};
checkSummary($filterWhere('all') === array('#folderId = 1'), 'Опцията "Всички" не добавя оттеглените към предходното условие');
checkSummary($filterWhere('active') === array('#folderId = 1', "(#state = 'active') OR (#state = 'rejected')"), 'Опция със собствено условие добавя оттеглените към него');
checkSummary($filterWhere('overdueAndAct') === array('#folderId = 1', "#paymentState = 'overdue'", "(#state = 'active') OR (#state = 'rejected')"), 'Просрочени (активни) не се смесват с приключените');

checkSummary(empty($GLOBALS['reported']), 'Няма докладвани грешки от базата');
echo "OK: {$checks} checks against per-document summaries, real Query builder and MariaDB.\n";
echo 'Largest difference from per-document rounding: ' . number_format($maxDiff, 6) . " over up to {$maxCount} documents.\n";
