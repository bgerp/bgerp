<?php

/**
 * Търсене в обхват с временни таблици и истинските филтри на plg_Search и doc_Threads.
 * Повтори с вградените MyISAM стопдуми и с ft_stopword_file, съдържащ about и kashon.
 * @copyright 2026 Experta EOOD
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php plg/tests/searchScopeProbe.php
 * Използва изолирана MariaDB с база test и root без парола.
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

$root = dirname(__DIR__, 2);
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
    public $className = 'ScopeProbeDocs';
    public $dbIndexes = array();
    public static function getDbTableName() { return cls::get(get_called_class())->dbTableName; }
}
// Като core_Manager::callOnReplica(): връзката е на репликата само докато трае извикването
class core_Manager extends core_Mvc
{
    public static function callWithoutReplica($callback)
    {
        global $db;
        $role = $db->role;
        $db->role = 'primary';
        try {
            return $callback();
        } finally {
            $db->role = $role;
        }
    }

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
    public static $models = array();
    public static function &get($name) { return self::$models[$name]; }
    public static function load($name, $silent = false) { return false; }
    public static function getClassName($object) { return get_class($object); }
}
// Прогнозният брой редове на таблицата - plg_Search::isBigTable() го чете оттук преди метаданните
class core_Permanent
{
    public static $rows = 2000000;
    public static function get($key) { return $key === 'tableRows|ScopeProbeDocs' ? self::$rows : null; }
    public static function set($key, $value, $lifetime) {}
}
class core_Cache
{
    public static $stopWords;
    public static $plans = array();
    public static $now = 0;

    public static function get($type, $handler, $keep = null)
    {
        if ($type === 'sqlStopWord') return self::$stopWords;
        if ($type !== 'searchFtLimit' || $GLOBALS['db']->role !== 'primary' || $keep !== null) {
            throw new RuntimeException('Планът се чете от основния кеш, без удължаване на срока');
        }
        $plan = self::$plans[$handler] ?? null;

        return $plan && $plan['expires'] > self::$now ? $plan['value'] : false;
    }

    public static function set($type, $handler, $value, $keep)
    {
        if ($type !== 'searchFtLimit' || $GLOBALS['db']->role !== 'primary' || !is_int($value) || $keep !== 7 * 24 * 60) {
            throw new RuntimeException('Кешира се само решението за плана, за една седмица на основната база');
        }
        self::$plans[$handler] = array('value' => $value, 'expires' => self::$now + $keep * 60);
    }
}
class ScopeProbeDb
{
    public $connection;
    public $log = array();
    public $role = 'primary';
    public $engine = 'MyISAM';
    public $dbHost = 'test-myisam';
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

$db = new ScopeProbeDb($socket);
// Както на сървъра: PHP списъкът съответства на конфигурацията на тестовия MyISAM индекс.
if ($db->connection->query('SELECT @@ft_stopword_file')->fetch_row()[0] === '(built-in)') {
    require $root . '/core/data/sqlStopWords.inc.php';
    core_Cache::$stopWords = $stopWordsArr;
} else {
    core_Cache::$stopWords = array('about' => 'about', 'kashon' => 'kashon');
}
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
function checkScope($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
$every = function ($n) { return array_values(array_filter(range(1, 12000), function ($i) use ($n) { return $i % $n === 0; })); };
// Търсене в обхват (id до $maxId) през restrictToScope(): приложен ли е, колко пъти е четен обхватът,
// таваните на FULLTEXT преброяванията и намерените id-та
function scopeFixture($search, $maxId, $hint = null, $folder = false, $limit = 5000)
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
    $query->searchPrefixesInScope = $folder;
    plg_Search::applySearch($search, $query);
    $db->log = array();
    $restricted = plg_Search::restrictToScope($query, 'id', $limit);
    $scopeReads = 0;
    $limits = array();
    foreach ($db->log as $entry) {
        if (strpos($entry[0], 'SELECT DISTINCT') !== false) $scopeReads++;
        if (!preg_match('/^SELECT 1 FROM .* MATCH\(.* LIMIT (\d+),1$/', $entry[0], $m)) continue;
        checkScope($entry[1] === 'replica', 'Броенето преди обхвата е на репликата');
        $limits[] = 1 + (int) $m[1];
    }
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }
    sort($ids);

    return array($restricted, $scopeReads, $limits, $ids, $query->where);
}
$upTo = function ($ids, $maxId) { return array_values(array_filter($ids, function ($id) use ($maxId) { return $id <= $maxId; })); };

// Рядка дума: FULLTEXT индексът и така води по малко редове - обхватът не се чете
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000);
checkScope($restricted === false && $scopeReads === 0 && $limits === array(5001), 'При до 5000 съвпадения на думата обхватът не се чете');
checkScope($ids === $upTo($every(30), 3000), 'Без обхвата намереното е същото');

// Честа дума: обхватът се чете и при до 5000 документа ограничава търсенето
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('kartonena', 3000);
checkScope($restricted === true && $scopeReads === 1 && $limits === array(5001) && $ids === range(1, 3000), 'Честа дума в малък обхват - търси се в обхвата');
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('kartonena', 6000);
checkScope($restricted === false && $scopeReads === 1 && !$limits && $ids === range(1, 6000), 'Честа дума в голям обхват - както досега');

// Без преброяване - обхватът се чете както досега
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000, 'PRIMARY');
checkScope($restricted === true && $scopeReads === 1 && $limits === array(), 'С индекс-подсказка FULLTEXT не води - не се брои');
core_Permanent::$rows = 1000000;
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000);
checkScope($restricted === true && $scopeReads === 1 && $limits === array() && $ids === $upTo($every(30), 3000), 'Малка таблица не се брои');
core_Permanent::$rows = 2000000;


$db->engine = 'InnoDB';
$db->dbHost = 'test-innodb';
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('hartia', 3000);
checkScope($restricted && $scopeReads === 1 && !$limits, 'Без допълнителна FULLTEXT проба при InnoDB');
$db->engine = 'MyISAM';
$db->dbHost = 'test-myisam';

// Еднакъв префикс на индексирана дума и на дума над ft_max_word_len: остава досегашното MATCH/LOCATE.
$maxWord = (int) $db->connection->query('SELECT @@ft_max_word_len')->fetch_row()[0];
$db->query("INSERT INTO docs VALUES (13001, CONCAT(' kartonena qax7', REPEAT('a', " . ($maxWord + 1) . "), ' ')), (13002, ' kartonena qax7normal ')");
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('kartonena qax7', 14000);
checkScope($ids === array(13001, 13002), 'Намират се документите и с двата вида код');

// Точно на прага се пропуска четенето на обхвата. Едно допълнително попадение връща обичайния план.
$db->query("UPDATE docs SET search_keywords=CONCAT(search_keywords,' borderword ') WHERE id<=5000");
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('borderword', 3000);
checkScope(!$restricted && !$scopeReads && $ids === range(1, 3000), 'Точно 5000 глобални попадения');
$db->query("UPDATE docs SET search_keywords=CONCAT(search_keywords,' borderword ') WHERE id=5001");
core_Cache::$now = 7 * 86400 - 1;
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('borderword', 6000);
checkScope(!$restricted && !$scopeReads && !$limits && $ids === range(1, 5001),
    'До края на седмицата новото попадение се вижда без нова FULLTEXT проба');
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('borderword', 3000, null, false, 4000);
checkScope($restricted && $limits === array(4001) && $ids === range(1, 3000), 'Различен лимит има своя проба');
$db->dbHost = 'another-test-myisam';
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('borderword', 3000);
checkScope($restricted && $limits === array(5001), 'Различен сървър има своя проба');
$db->dbHost = 'test-myisam';
core_Cache::$now++;
list($restricted, $scopeReads, $limits, $ids) = scopeFixture('borderword', 3000);
checkScope($restricted && $scopeReads === 1 && $limits === array(5001) && $ids === range(1, 3000),
    'След точно една седмица пробата улавя 5001-вото попадение');

// Един префикс в папка включва и прекалено дългия код при всяко изпълнение.
for ($seed = 1; $seed <= 3; $seed++) {
    mt_srand($seed);
    list($restricted, $scopeReads, $limits, $ids, $where) = scopeFixture('qax7', 14000, null, true);
    checkScope($ids === array(13001, 13002) && !$scopeReads && !$limits && !preg_grep('/^MATCH/', $where),
        'Префикс в папка намира и двата кода без проби');
}
list(, , , $ids, $where) = scopeFixture('"qax7normal"', 14000, null, true);
checkScope($ids === array(13002) && preg_grep('/^MATCH/', $where), 'Кавичките в голяма папка запазват точното условие');

foreach (array('about', 'because', 'kashon', 'etiket') as $i => $word) {
    $id = 14000 + $i * 10;
    $db->query("INSERT INTO docs VALUES ({$id}, ' kartonena {$word} '), (" . ($id + 1) . ", ' kartonena {$word}zz '), ("
        . ($id + 2) . ", ' kartonena xx{$word} ')");
    foreach (array($word, "kartonena {$word}") as $search) {
        list(, , , $ids) = scopeFixture($search, 15000);
        checkScope($ids === array($id, $id + 1), "Пълни резултати за {$search}");
    }
}

// Списък и брояч през филтъра на действителния мениджър, включително прага от 5000 нишки.
require $root . '/doc/Threads.class.php';
class doc_Containers extends core_Manager {}
class doc_Folders
{
    public static $threads = 5010;
    public static function fetchField($folder, $field) { return self::$threads; }
}
foreach (array('doc_Threads' => array('id', 'folderId', 'state', 'last', 'firstDocClass'),
    'doc_Containers' => array('id', 'folderId', 'threadId', 'searchKeywords')) as $class => $fields) {
    $model = new $class();
    $model->db = $db;
    $model->dbTableName = strtolower($class);
    $model->className = $class;
    foreach ($fields as $field) $model->FLD($field, 'varchar');
    cls::$models[$class] = $model;
}
$db->query("CREATE TEMPORARY TABLE doc_threads (id INT PRIMARY KEY, folder_id INT, state VARCHAR(12), last INT,
    first_doc_class INT, visible_for_partners INT DEFAULT 0, KEY(folder_id,last),
    KEY(folder_id,visible_for_partners,state,first_doc_class), KEY(first_doc_class)) ENGINE=MyISAM");
$db->query("CREATE TEMPORARY TABLE doc_containers (id INT PRIMARY KEY, folder_id INT, thread_id INT, search_keywords TEXT,
    KEY(folder_id), KEY(thread_id), FULLTEXT(search_keywords)) ENGINE=MyISAM");
$db->query("INSERT INTO doc_threads(id,folder_id,state,last,first_doc_class) SELECT id,7,'opened',id,67 FROM docs WHERE id<=5010");
$db->query("INSERT INTO doc_threads(id,folder_id,state,last,first_doc_class) VALUES (6000,8,'opened',6000,67),(6001,7,'opened',6001,90),(6002,7,'rejected',6002,67)");
$db->query("INSERT INTO doc_containers SELECT id,folder_id,id,IF(id<=51 OR id>=6000,' textilna gusset 40 ',' textilna 41 ') FROM doc_threads");
$db->query("INSERT INTO doc_containers VALUES (9000,7,51,' textilna gusset 40 ')");
$db->query("UPDATE doc_containers SET search_keywords=CONCAT(search_keywords,'qaflag',IF(id=1,REPEAT('a',90),'norm'),' ') WHERE id IN (1,2)");
// Глобалната честота на qaflag е близка до броя документи в папката.
$db->query("INSERT INTO doc_containers SELECT 20000+id,8,6000,' qaflagnorm ' FROM docs WHERE id<=5010");
function threadQuery($search)
{
    $query = new core_Query(array('mvc' => cls::get('doc_Threads')));
    doc_Threads::applyFilter((object) array('folderId' => 7, 'search' => $search, 'documentClassId' => 67), $query);
    return $query;
}
function threadSearch($search, $rejected = false)
{
    $query = threadQuery($search);
    $query->where($rejected ? "#state = 'rejected'" : "#state != 'rejected' OR #state IS NULL");
    checkScope(!plg_Search::restrictToScope($query, 'searchContainerId'), 'Папката вече ограничава префиксното търсене');
    $count = clone $query;
    $count->limit(1000);
    $query->show('id');
    $query->limit(30);
    $ids = array();
    while ($row = $query->fetch()) $ids[] = (int) $row->id;

    return array($ids, (int) $count->count());
}
checkScope(threadSearch('textil gus 40') === array(range(51, 22), 51), 'Точно първите 30 нишки в правилния ред и 51 различни нишки');
checkScope(threadSearch('textil gus 40', true) === array(array(6002), 1), 'Кошът спазва папката, класа и състоянието');
for ($seed = 1; $seed <= 40; $seed++) {
    mt_srand($seed);
    checkScope(threadSearch('qaflag') === array(array(2, 1), 2), 'Повторяеми резултати със и без индексиран дълъг код');
}

doc_Folders::$threads = 5000;
checkScope(threadQuery('qaflag')->dontUseFts, 'Малка папка запазва досегашното търсене');
doc_Folders::$threads = 5010;
$db->engine = 'InnoDB';
$db->dbHost = 'test-innodb';
$innodb = threadQuery('qaflag');
checkScope(!$innodb->searchPrefixesInScope && preg_grep('/^MATCH/', $innodb->where), 'InnoDB запазва плана за голяма папка');

echo "OK: {$checks} scope checks with MariaDB.\n";
