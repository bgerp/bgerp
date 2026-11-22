<?php

/**
 * Кой prefix термин води FULLTEXT индекса - с истинските core_Query, plg_Search и MariaDB:
 * - в голяма MyISAM таблица с FULLTEXT индекс по полето води най-рядкият термин, а намереното не се променя;
 * - над тавана води коя да е дума под него, при точен брой - само поне 4 пъти по-рядка; под 1000 съвпадения
 *   броенето спира;
 * - броенето е на репликата; малка таблица, InnoDB, индекс-подсказка, поле без собствен FULLTEXT индекс,
 *   един термин и фраза не се броят - води най-дългият;
 * - restrictToScope() не чете обхвата, ако FULLTEXT условието има до 5000 съвпадения
 *
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php plg/tests/searchRarestTerm.php
 * Иска изолирана MariaDB с празна база test; ползва само временни таблици, без конфигурацията на инсталацията.
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
$socket = getenv('BGERP_SUMMARY_TEST_SOCKET');
if (!$socket) exit("Set BGERP_SUMMARY_TEST_SOCKET to an isolated MariaDB socket.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
function defIfNot($key, $val) { if (!defined($key)) define($key, $val); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($value) { if (!$value) throw new RuntimeException('Expectation failed'); }
function setIfNot(&$value, ...$defaults)
{
    if (isset($value)) return;
    foreach ($defaults as $default) {
        if (isset($default)) {
            $value = $default;
            return;
        }
    }
}
define('PLG_SEARCH_MIN_LEN_FTS', 4);

$root = getenv('BGERP_TEST_ROOT') ?: dirname(__DIR__, 2);
foreach (array('BaseClass', 'FieldSet', 'Query', 'Plugin', 'Array', 'String') as $class) {
    require $root . '/core/' . $class . '.class.php';
}
require $root . '/plg/Search.class.php';
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
    public $dbTableName = 'docs';
    public $className = 'RarestTermDocs';
    public $dbIndexes = array();
}
// Като core_Manager::callOnReplica(): връзката е на репликата само докато трае извикването
class core_Manager extends core_Mvc
{
    public $replicaCalls = 0;
    public function callOnReplica($callback)
    {
        $this->replicaCalls++;
        $this->db->role = 'replica';
        try {
            return call_user_func($callback);
        } finally {
            $this->db->role = 'primary';
        }
    }
}
class core_Debug
{
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class_alias('core_Debug', 'DEBUG');
class Mode { public static function is($name) { return false; } }
class cls
{
    public static function getClassName($object) { return get_class($object); }
}
// Прогнозният брой редове на таблицата - plg_Search::isBigTable() го чете оттук преди метаданните
class core_Permanent
{
    public static $rows = 2000000;
    public static function get($key) { return $key === 'tableRows|RarestTermDocs' ? self::$rows : null; }
    public static function set($key, $value, $lifetime) {}
}
class core_Cache
{
    public static function get($type, $handler, $keep = null) { return array('about' => 'about'); }
    public static function set($type, $handler, $value, $keep) {}
}
class RarestTermDb
{
    public $connection;
    public $log = array();
    public $role = 'primary';
    public $engine = 'MyISAM';
    public function __construct($socket) { $this->connection = new mysqli('localhost', 'root', '', 'test', 0, $socket); }
    // Временната таблица я няма в information_schema - двигателят идва от теста
    public function getTableInfo($table, $part) { return $part === 'ENGINE' ? $this->engine : false; }
    public function query($sql)
    {
        $this->log[] = array($sql, $this->role);
        $result = $this->connection->query($sql);
        if ($result === false) throw new RuntimeException($this->connection->error . "\n" . $sql);
        return $result;
    }
    public function escape($value) { return $this->connection->real_escape_string($value); }
    public function fetchObject($result) { return $result->fetch_object(); }
    public function fetchArray($result) { return $result->fetch_assoc(); }
    public function freeResult($result) { $result->free(); }
}

$db = new RarestTermDb($socket);
$db->query('CREATE TEMPORARY TABLE docs (id INT PRIMARY KEY, search_keywords TEXT NOT NULL, FULLTEXT (search_keywords)) ENGINE=MyISAM');
// Брой документи с думата: kartonena - всички 12000 (над тавана от 10000), kartoni - 6000, plik - 4000,
// kutia - 2400, hartia - 400
$values = array();
for ($i = 1; $i <= 12000; $i++) {
    $words = ' kartonena' . ($i % 2 ? '' : ' kartoni') . ($i % 3 ? '' : ' plik') . ($i % 5 ? '' : ' kutia') . ($i % 30 ? '' : ' hartia');
    $values[] = "({$i}, '{$words} doc{$i} ')";
}
$db->query('INSERT INTO docs VALUES ' . implode(',', $values));

$checks = 0;
function checkTerm($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
// Търсене през plg_Search: връща условията, намерените id-та и тавана на всяко преброяване
function searchFixture($search, $fullTextIndex = true, $hint = null)
{
    global $db;
    $mvc = new core_Manager();
    $mvc->db = $db;
    foreach (array('id', 'searchKeywords') as $name) {
        $mvc->FLD($name, 'varchar');
    }
    if ($fullTextIndex) {
        $mvc->dbIndexes['search_keywords'] = (object) array('fields' => 'searchKeywords', 'type' => 'FULLTEXT');
    }
    $query = new core_Query(array('mvc' => $mvc));
    if ($hint) $query->useIndex($hint);
    $db->log = array();
    plg_Search::applySearch($search, $query);
    $limits = array();
    foreach ($db->log as $entry) {
        if (!preg_match('/LIMIT (\d+)\) AS `fts_probe`/', $entry[0], $m)) continue;
        checkTerm($entry[1] === 'replica', 'Броенето е на репликата');
        $limits[] = (int) $m[1];
    }
    checkTerm($db->role === 'primary' && $mvc->replicaCalls === ($limits ? 1 : 0), 'Връзката се връща на основната база след едно превключване');
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }
    sort($ids);

    return array($query->where, $ids, $limits);
}
$every = function ($n) { return array_values(array_filter(range(1, 12000), function ($i) use ($n) { return $i % $n === 0; })); };
$longest = "MATCH(#searchKeywords) AGAINST('+kartonena*' IN BOOLEAN MODE)";
$driver = function ($word) { return "MATCH(#searchKeywords) AGAINST('+{$word}*' IN BOOLEAN MODE)"; };

// Най-дългата дума е над тавана: води коя да е по-рядка под него, намереното е същото
list($where, $ids, $limits) = searchFixture('hartia kartonena');
checkTerm(in_array($driver('hartia'), $where, true) && in_array("LOCATE(' kartonena', #searchKeywords)", $where, true), 'Индексът води по най-рядката дума');
checkTerm($ids === $every(30), 'Намереното не зависи от водещата дума');
checkTerm($limits === array(10001, 10001), 'Над тавана другата дума се брои до същия таван');

// При точен брой друга дума води само ако е поне 4 пъти по-рядка
list($where, $ids, $limits) = searchFixture('kartoni hartia');
checkTerm(in_array($driver('hartia'), $where, true) && $ids === $every(30) && $limits === array(10001, 1500), 'Четири пъти по-рядка дума води');
list($where, $ids, $limits) = searchFixture('kutia plik');
checkTerm(in_array($driver('kutia'), $where, true) && $ids === $every(15) && $limits === array(10001, 600), 'По-малко от четири пъти по-рядка не сменя');

// Три думи: след смяната таванът пада до една четвърт от новия брой
list($where, $ids, $limits) = searchFixture('kartonena plik kutia');
checkTerm(in_array($driver('kutia'), $where, true) && in_array("LOCATE(' plik', #searchKeywords)", $where, true)
    && in_array("LOCATE(' kartonena', #searchKeywords)", $where, true) && $ids === $every(15), 'От три думи води най-рядката');
checkTerm($limits === array(10001, 10001, 600), 'Таванът на следващата пада до една четвърт');

// Рядка най-дълга дума: заявката е бърза и така - другите не се броят
list($where, $ids, $limits) = searchFixture('hartia plik');
checkTerm(in_array($driver('hartia'), $where, true) && $ids === $every(30) && $limits === array(10001), 'Под 1000 съвпадения другите не се броят');

// Дума без съвпадения води и спира броенето на останалите
list($where, $ids, $limits) = searchFixture('kartonena zzzzzz plik');
checkTerm(in_array($driver('zzzzzz'), $where, true) && $ids === array() && $limits === array(10001, 10001), 'Дума без съвпадения води и спира броенето');

// Без преброяване води най-дългата, както досега
core_Permanent::$rows = 1000000;
list($where, $ids, $limits) = searchFixture('hartia kartonena');
checkTerm(in_array($longest, $where, true) && $limits === array() && $ids === $every(30), 'Малка таблица не се брои');
core_Permanent::$rows = 2000000;
$db->engine = 'InnoDB';
list($where, $ids, $limits) = searchFixture('hartia kartonena');
checkTerm(in_array($longest, $where, true) && $limits === array(), 'InnoDB не се брои - там LIMIT не спира FULLTEXT търсенето');
$db->engine = 'MyISAM';
list($where, $ids, $limits) = searchFixture('hartia kartonena', true, 'PRIMARY');
checkTerm(in_array($longest, $where, true) && $limits === array() && $ids === $every(30), 'С индекс-подсказка FULLTEXT не води - не се брои');
list($where, $ids, $limits) = searchFixture('hartia kartonena', false);
checkTerm(in_array($longest, $where, true) && $limits === array(), 'Поле без FULLTEXT индекс не се брои - всяко преброяване би обходило таблицата');
list($where, $ids, $limits) = searchFixture('kartonena');
checkTerm($where === array($longest) && $limits === array() && count($ids) === 12000, 'Една дума не се брои');
list($where, $ids, $limits) = searchFixture('"plik hartia" kartonena');
checkTerm(in_array("MATCH(#searchKeywords) AGAINST('+\"plik hartia\"' IN BOOLEAN MODE)", $where, true) && $limits === array(), 'Фраза води без броене');

// Търсене в обхват (id до $maxId) през restrictToScope(): приложен ли е, колко пъти е четен обхватът,
// таваните на FULLTEXT преброяванията и намерените id-та
function scopeFixture($search, $maxId, $hint = null)
{
    global $db;
    $mvc = new core_Manager();
    $mvc->db = $db;
    foreach (array('id', 'searchKeywords') as $name) {
        $mvc->FLD($name, 'varchar');
    }
    $mvc->dbIndexes['search_keywords'] = (object) array('fields' => 'searchKeywords', 'type' => 'FULLTEXT');
    $query = new core_Query(array('mvc' => $mvc));
    $query->where("#id <= {$maxId}");
    if ($hint) $query->useIndex($hint);
    plg_Search::applySearch($search, $query);
    $db->log = array();
    $restricted = plg_Search::restrictToScope($query);
    $scopeReads = 0;
    $limits = array();
    foreach ($db->log as $entry) {
        if (strpos($entry[0], 'SELECT DISTINCT') !== false) $scopeReads++;
        if (!preg_match('/LIMIT (\d+)\) AS `fts_probe`/', $entry[0], $m)) continue;
        checkTerm($entry[1] === 'replica', 'Броенето преди обхвата е на репликата');
        $limits[] = (int) $m[1];
    }
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }
    sort($ids);

    return array($restricted, $scopeReads, $limits, $ids);
}
$upTo = function ($ids, $maxId) { return array_values(array_filter($ids, function ($id) use ($maxId) { return $id <= $maxId; })); };

// Рядка дума: FULLTEXT индексът и така води по малко редове - обхватът не се чете
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000);
checkTerm($restricted === false && $scopeReads === 0 && $limits === array(5001), 'При до 5000 съвпадения на думата обхватът не се чете');
checkTerm($ids === $upTo($every(30), 3000), 'Без обхвата намереното е същото');

// Честа дума: обхватът се чете и при до 5000 документа ограничава търсенето
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('kartonena', 3000);
checkTerm($restricted === true && $scopeReads === 1 && $limits === array(5001) && $ids === range(1, 3000), 'Честа дума в малък обхват - търси се в обхвата');
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('kartonena', 6000);
checkTerm($restricted === false && $scopeReads === 1 && $ids === range(1, 6000), 'Честа дума в голям обхват - както досега');

// Без преброяване - обхватът се чете както досега
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000, 'PRIMARY');
checkTerm($restricted === true && $scopeReads === 1 && $limits === array(), 'С индекс-подсказка FULLTEXT не води - не се брои');
core_Permanent::$rows = 1000000;
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000);
checkTerm($restricted === true && $scopeReads === 1 && $limits === array() && $ids === $upTo($every(30), 3000), 'Малка таблица не се брои');
core_Permanent::$rows = 2000000;

echo "OK: {$checks} rarest-term and scope checks with the real Query builder, plg_Search and MariaDB.\n";
