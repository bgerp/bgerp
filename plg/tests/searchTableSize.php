<?php

// CLI regression: table size must use metadata and never read application records.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$checks = 0;
function check($ok, $message)
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_Plugin {}
class core_Permanent
{
    public static $values = array();
    public static function get($key) { return self::$values[$key] ?? null; }
    public static function set($key, $value, $lifetime) { self::$values[$key] = $value; }
}
class TableSizeDb
{
    public $rows;
    public $calls = 0;
    public function getTableInfo($tableName, $part = null)
    {
        check($tableName === 'table_size_fixture' && $part === 'TABLE_ROWS', 'Read the row estimate of the physical table');
        $this->calls++;

        return $this->rows;
    }
    public function query($sql) { throw new RuntimeException('Application SQL must not run: ' . $sql); }
}
class TableSizeModel
{
    public $className = 'TableSizeModel';
    public $dbTableName = 'table_size_fixture';
    public $db;
    public function getQuery() { throw new RuntimeException('Application queries must not run'); }
}
require dirname(__DIR__) . '/Search.class.php';

$db = new TableSizeDb();
$mvc = new TableSizeModel();
$mvc->db = $db;
$query = (object) array('mvc' => $mvc);
foreach (array(0, 1000000, 1000001, false) as $rows) {
    core_Permanent::$values = array('tableMaxId|TableSizeModel' => 9000000);
    $db->rows = $rows;
    $db->calls = 0;
    $expected = $rows !== false && $rows > 1000000;
    check(plg_Search::isBigTable($query) === $expected, 'Classify by row estimate, ignoring the old maximum-ID cache');
    check($db->calls === 1, 'Read metadata once on a cache miss');
    check(core_Permanent::$values['tableRows|TableSizeModel'] === (int) $rows, 'Cache row counts under a separate key');
    check(plg_Search::isBigTable($query) === $expected && $db->calls === 1, 'Reuse cached metadata, including zero rows');
}
check(plg_Search::isBigTable((object) array('mvc' => null)) === false, 'No model means no large table');
check(plg_Search::isBigTable(new stdClass()) === false, 'A missing model does not emit a warning');
$mvc->dbTableName = '';
check(plg_Search::isBigTable($query) === false, 'A model without a table is not large');
$mvc->dbTableName = 'table_size_fixture';
$mvc->db = null;
check(plg_Search::isBigTable($query) === false, 'A model without a database is not large');

echo "PASS: {$checks} table size checks\n";
