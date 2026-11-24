<?php

/**
 * Търсене в обхват (папка), когато водещата дума е по-масова от него - с истинските core_Query, plg_Search и MariaDB:
 * - prefix дума, оценена на повече редове от обхвата, остава LOCATE: без MATCH води индексът на обхвата,
 *   а намереното е същото;
 * - по-рядка дума, фраза, заявка без обхват и заявка без FULLTEXT остават както досега и не се оценяват;
 * - оценката е по до 1000 номера, пръснати по таблицата на полето (и когато то е от друг модел) - в малка
 *   таблица е точният брой; на репликата е само за заявка, която се изпълнява там;
 * - където се избира най-рядката дума (голяма MyISAM таблица с FULLTEXT индекс по полето), с обхвата се сравнява тя
 *
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php plg/tests/searchWiderThanScope.php
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
    public $dbTableName;
    public $className;
    public $dbIndexes = array();
}
// Като core_Manager::callOnReplica(): връзката е на репликата само докато трае извикването
class core_Manager extends core_Mvc
{
    public static $replicaCalls = 0;
    public function callOnReplica($callback)
    {
        self::$replicaCalls++;
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
    public static function getClassName($object) { return get_class($object); }
}
// Прогнозният брой редове и двигателят на таблицата - plg_Search ги чете оттук преди метаданните
class core_Permanent
{
    public static $values = array();
    public static function get($key) { return self::$values[$key] ?? 0; }
    public static function set($key, $value, $lifetime) {}
}
class core_Cache
{
    public static function get($type, $handler, $keep = null) { return array('about' => 'about'); }
    public static function set($type, $handler, $value, $keep) {}
}
class ScopeDb
{
    public $connection;
    public $log = array();
    public $role = 'primary';
    public function __construct($socket) { $this->connection = new mysqli('localhost', 'root', '', 'test', 0, $socket); }
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

$checks = 0;
function checkScope($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
function model($name, $table, $fields)
{
    global $db;
    $mvc = new core_Manager();
    $mvc->db = $db;
    $mvc->dbTableName = $table;
    $mvc->className = $name;
    foreach ($fields as $field) {
        $mvc->FLD($field, 'varchar');
    }

    return cls::$models[$name] = $mvc;
}

$db = new ScopeDb($socket);
$db->query('CREATE TEMPORARY TABLE scope_threads (id INT PRIMARY KEY, folder_id INT NOT NULL, KEY (folder_id)) ENGINE=MyISAM');
foreach (array('scope_containers', 'scope_small', 'scope_empty') as $table) {
    $db->query("CREATE TEMPORARY TABLE {$table} (id INT PRIMARY KEY, folder_id INT NOT NULL, thread_id INT NOT NULL, "
        . 'search_keywords TEXT NOT NULL, KEY (folder_id), KEY (thread_id), FULLTEXT (search_keywords)) ENGINE=MyISAM');
}

// 4000 нишки с по 3 документа; папка 7 държи към една десета. Във всеки втори документ има textilna,
// във всеки тридесетина - gusset. Номерата са разредени, както след изтриване
mt_srand(20261124);
$threadRows = $containerRows = $smallRows = array();
$inFolder = $withTextil = $withGusset = 0;
$expected = array('textil' => array(), 'gusset' => array(), 'both' => array());
for ($thread = 1; $thread <= 4000; $thread++) {
    $folder = mt_rand(1, 10) == 1 ? 7 : mt_rand(1, 5);
    $threadRows[] = "({$thread}, {$folder})";
    for ($n = 0; $n < 3; $n++) {
        $id = ($thread - 1) * 4 + $n + 1;
        $textil = mt_rand(1, 100) <= 50;
        $gusset = mt_rand(1, 100) <= 3;
        $words = " doc{$id}" . ($textil ? ' textilna' : '') . ($gusset ? ' gusset' : '') . ' chanta ';
        $containerRows[] = "({$id}, {$folder}, {$thread}, '{$words}')";
        $withTextil += $textil;
        $withGusset += $gusset;
        if ($folder != 7) continue;
        $inFolder++;
        if ($textil) $expected['textil'][$thread] = $thread;
        if ($gusset) $expected['gusset'][$thread] = $thread;
        if ($textil && $gusset) $expected['both'][$thread] = $thread;
    }
}
foreach ($expected as $key => $ids) {
    sort($ids);
    $expected[$key] = $ids;
}
$db->query('INSERT INTO scope_threads VALUES ' . implode(',', $threadRows));
$db->query('INSERT INTO scope_containers VALUES ' . implode(',', $containerRows));
// Малка таблица - под размера на извадката: 300 документа, думата е в първите 120
for ($id = 1; $id <= 300; $id++) {
    $smallRows[] = "({$id}, 7, {$id}, ' doc{$id}" . ($id <= 120 ? ' textilna' : '') . " chanta ')";
}
$db->query('INSERT INTO scope_small VALUES ' . implode(',', $smallRows));
checkScope($withTextil > 4 * $inFolder && $withGusset * 2 < $inFolder && $expected['both'], 'Данните: честата дума е в много повече документи от папката, рядката - в по-малко');

model('ScopeThreads', 'scope_threads', array('id', 'folderId'));
foreach (array('ScopeContainers' => 'scope_containers', 'ScopeSmall' => 'scope_small', 'ScopeEmpty' => 'scope_empty') as $name => $table) {
    model($name, $table, array('id', 'folderId', 'threadId', 'searchKeywords'));
}

// Търсене в папка 7 като doc_Threads::applyFilter(): нишките по думите на документите им.
// Връща условията, намерените нишки и заявките на оценката (SQL, роля на връзката)
function folderSearch($search, $scope, $containers = 'ScopeContainers', $onReplica = false, $dontUseFts = false)
{
    global $db;
    $table = cls::get($containers)->dbTableName;
    $query = new core_Query(array('mvc' => cls::get('ScopeThreads')));
    $query->onReplica = $onReplica;
    $query->EXT('containerFolderId', $containers, 'externalName=folderId');
    $query->where('#containerFolderId = 7');
    $query->where('#folderId = 7');
    $query->EXT('containerSearchKeywords', $containers, 'externalName=searchKeywords');
    $query->where("`{$table}`.`thread_id` = `scope_threads`.`id`");
    if ($scope !== null) $query->searchScopeRows = $scope;
    if ($dontUseFts) $query->dontUseFts = true;

    $db->log = array();
    core_Manager::$replicaCalls = 0;
    plg_Search::applySearch($search, $query, 'containerSearchKeywords');
    $probes = $db->log;
    checkScope($db->role === 'primary', 'След оценката връзката е на основната база');

    $where = array_slice($query->where, 3);
    foreach ($where as $condition) {
        checkScope(isset($query->searchConditions[$condition]), 'Условията на търсенето са отбелязани за restrictToScope()');
    }
    $query->groupBy('`scope_threads`.`id`');
    $query->show('id');
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }
    sort($ids);

    return array($where, $ids, $probes);
}
$match = function ($word) { return "MATCH(#containerSearchKeywords) AGAINST('+{$word}*' IN BOOLEAN MODE)"; };
$locate = function ($word) { return "LOCATE(' {$word}', #containerSearchKeywords)"; };

// Без обхват всичко е както досега: води най-дългата дума и нищо не се оценява
list($where, $ids, $probes) = folderSearch('textil chant', null);
checkScope($where === array($match('textil'), $locate('chant')) && $probes === array(), 'Без обхват води най-дългата дума, без оценка');
checkScope($ids === $expected['textil'], 'Намереното по FULLTEXT');

// Честата дума е в много повече документи, отколкото има в папката: остава LOCATE и MATCH няма
list($where, $ids, $probes) = folderSearch('textil chant', $inFolder);
checkScope($where === array($locate('textil'), $locate('chant')), 'По-масова от папката дума не води FULLTEXT');
checkScope($ids === $expected['textil'], 'Намереното е същото и без FULLTEXT');
checkScope(count($probes) === 2 && strpos($probes[0][0], 'SELECT MAX(`id`) AS `maxId` FROM `scope_containers`') === 0,
    'Оценката е по таблицата на документите, не на нишките');
checkScope(preg_match('/^SELECT COUNT\(\*\) AS `cnt` FROM `scope_containers` WHERE `id` IN \(([0-9,]+)\) AND LOCATE\(\' textil\', `search_keywords`\)$/', $probes[1][0], $m) === 1,
    'Извадката е по първичния ключ и по началото на думата');
$sampleIds = array_map('intval', explode(',', $m[1]));
checkScope(count($sampleIds) === 1000 && count(array_unique($sampleIds)) === 1000 && min($sampleIds) >= 1 && max($sampleIds) <= 15999
    && min($sampleIds) < 100 && max($sampleIds) > 15900, 'Хиляда различни номера, пръснати от началото до края на таблицата');
checkScope($probes[0][1] === 'primary' && $probes[1][1] === 'primary' && core_Manager::$replicaCalls === 0, 'Заявка на основната база се оценява там');

// Заявка, която се изпълнява на репликата, се оценява на репликата
list($where, $ids, $probes) = folderSearch('textil chant', $inFolder, 'ScopeContainers', true);
checkScope($where === array($locate('textil'), $locate('chant')) && $ids === $expected['textil'], 'Същото решение на репликата');
checkScope($probes[0][1] === 'replica' && $probes[1][1] === 'replica', 'Оценката на заявка от репликата е на репликата');

// Рядката дума е в по-малко документи от папката: FULLTEXT остава
list($where, $ids, $probes) = folderSearch('gusset', $inFolder);
checkScope($where === array($match('gusset')) && count($probes) === 2 && $ids === $expected['gusset'], 'По-рядка от папката дума води FULLTEXT');
list($where, $ids, $probes) = folderSearch('gusset text', $inFolder);
checkScope($where === array($match('gusset'), $locate('text')) && $ids === $expected['both'], 'Рядка най-дълга дума води, другата е LOCATE');

// Една дума, която е по-масова от папката
list($where, $ids, $probes) = folderSearch('textil', $inFolder);
checkScope($where === array($locate('textil')) && $ids === $expected['textil'], 'И единствената дума остава LOCATE');

// Фразите и целите думи остават в MATCH - LOCATE не им е равностоен, затова не се оценяват
list($where, $ids, $probes) = folderSearch('"textilna chanta" doc', $inFolder);
checkScope(in_array("MATCH(#containerSearchKeywords) AGAINST('+\"textilna chanta\"' IN BOOLEAN MODE)", $where, true) && $probes === array(),
    'Фраза води FULLTEXT без оценка');

// Без FULLTEXT (малка папка) няма какво да се оценява
list($where, $ids, $probes) = folderSearch('textil chant', $inFolder, 'ScopeContainers', false, true);
checkScope($where === array($locate('textil'), $locate('chant')) && $probes === array() && $ids === $expected['textil'], 'Без FULLTEXT няма оценка');

// Таблица под размера на извадката: оценката е точният брой (120) и се сравнява строго с обхвата
list($where, $byFullText, $probes) = folderSearch('textil', 120, 'ScopeSmall');
checkScope($where === array($match('textil')) && $byFullText, 'Колкото обхвата - FULLTEXT остава');
checkScope(preg_match('/IN \(([0-9,]+)\)/', $probes[1][0], $m) === 1 && explode(',', $m[1]) == range(1, 300), 'В малка таблица извадката са всички номера');
list($where, $ids, $probes) = folderSearch('textil', 119, 'ScopeSmall');
checkScope($where === array($locate('textil')) && $ids === $byFullText, 'С един над обхвата - LOCATE, със същото намерено');

// Празна таблица: няма какво да се оценява и нищо не се променя
list($where, $ids, $probes) = folderSearch('textil', 50, 'ScopeEmpty');
checkScope($where === array($match('textil')) && count($probes) === 1 && $ids === array(), 'Празна таблица не се оценява');

// Поле на самия модел - търсене направо в документите
$query = new core_Query(array('mvc' => cls::get('ScopeContainers')));
$query->where('#folderId = 7');
$query->searchScopeRows = $inFolder;
$db->log = array();
plg_Search::applySearch('textil chant', $query);
checkScope(array_slice($query->where, 1) === array("LOCATE(' textil', #searchKeywords)", "LOCATE(' chant', #searchKeywords)")
    && count($db->log) === 2, 'Поле на самия модел се оценява по неговата таблица');

// Голяма MyISAM таблица с FULLTEXT индекс по полето: води най-рядката дума и с обхвата се сравнява тя.
// По най-дългата (textilna) търсенето би останало без FULLTEXT, а gusset е по-рядка от папката
core_Permanent::$values = array('tableRows|ScopeIndexed' => 2000000, 'tableEngine|ScopeIndexed' => 'MYISAM');
$indexed = model('ScopeIndexed', 'scope_containers', array('id', 'folderId', 'threadId', 'searchKeywords'));
$indexed->dbIndexes['search_keywords'] = (object) array('fields' => 'searchKeywords', 'type' => 'FULLTEXT');
$indexedSearch = function ($search) use ($indexed, $inFolder) {
    $query = new core_Query(array('mvc' => $indexed));
    $query->where('#folderId = 7');
    $query->searchScopeRows = $inFolder;
    plg_Search::applySearch($search, $query);

    return array_slice($query->where, 1);
};
checkScope($indexedSearch('textilna gusset') === array("MATCH(#searchKeywords) AGAINST('+gusset*' IN BOOLEAN MODE)", "LOCATE(' textilna', #searchKeywords)"),
    'С обхвата се сравнява най-рядката дума - тя води FULLTEXT');
checkScope($indexedSearch('textilna chanta') === array("LOCATE(' textilna', #searchKeywords)", "LOCATE(' chanta', #searchKeywords)"),
    'И най-рядката дума е по-масова от папката - без FULLTEXT');

echo "OK: {$checks} wider-than-scope checks with the real Query builder, plg_Search and MariaDB.\n";
