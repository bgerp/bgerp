<?php

/**
 * Пейджърът с истинския core_Query и MariaDB:
 * - записът, добавян към списъка при търсене по номер, минава през достъпа и филтрите на заявката;
 * - simplePaging брои резултатите, освен при skipItemsCount
 *
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php core/tests/pagerAddId.php
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

$root = getenv('BGERP_TEST_ROOT') ?: dirname(__DIR__, 2);
foreach (array('BaseClass', 'FieldSet', 'Query', 'Pager', 'Array', 'String') as $class) {
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
    public $dbTableName = 'docs';
    public $className = 'PagerDocs';
}
class core_Debug
{
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class_alias('core_Debug', 'DEBUG');
class Mode { public static function is($name) { return false; } }
class Request { public static function get($name) { return null; } }
// Кешът на броя винаги пропуска - като при празен core_Cache (getFromChache връща null)
class core_QueryCnts
{
    public static $lookups = 0;
    public static function getFromChache($query) { self::$lookups++; return null; }
}
class cls
{
    public static $models = array();
    public static function &get($name) { return self::$models[$name]; }
    public static function getClassName($object) { return get_class($object); }
}
// core_Pager чете свойства, които получава от извикващия код
class PagerUnderTest extends core_Pager
{
    public $exactPaging = false;
}
class PagerTestDb
{
    public $connection;
    public $log = array();
    public function __construct($socket) { $this->connection = new mysqli('localhost', 'root', '', 'test', 0, $socket); }
    public function query($sql)
    {
        $this->log[] = $sql;
        $result = $this->connection->query($sql);
        if ($result === false) throw new RuntimeException($this->connection->error . "\n" . $sql);
        return $result;
    }
    public function escape($value) { return $this->connection->real_escape_string($value); }
    public function fetchObject($result) { return $result->fetch_object(); }
    public function fetchArray($result) { return $result->fetch_assoc(); }
    public function freeResult($result) { $result->free(); }
}

$db = new PagerTestDb($socket);
$db->query('CREATE TEMPORARY TABLE docs (id INT PRIMARY KEY, state VARCHAR(16), folder_id INT, search_keywords VARCHAR(64))');
$db->query('CREATE TEMPORARY TABLE folders (id INT PRIMARY KEY, shared VARCHAR(64))');
$db->query("INSERT INTO folders VALUES (1, '|198|'), (2, '')");
// Папка 1 е достъпна: нечетните документи са с "alpha", четните с "beta"; 29 и 30 са чернови. Папка 2 е недостъпна.
$values = array();
for ($i = 1; $i <= 30; $i++) {
    $values[] = "({$i}, '" . ($i > 28 ? 'draft' : 'active') . "', 1, ' " . ($i % 2 ? 'alpha' : 'beta') . "')";
}
$db->query('INSERT INTO docs VALUES ' . implode(',', $values) . ", (101, 'active', 2, ' alpha'), (102, 'active', 2, ' beta')");

$folders = new core_Mvc();
$folders->dbTableName = 'folders';
$folders->FLD('shared', 'varchar');
cls::$models['PagerFolders'] = $folders;

$checks = 0;
function checkPager($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
// Списък на активните документи с "alpha", достъпни за потребител 198; връща id-тата на страницата и общия брой
function pagerFixture($addId, $page = 1)
{
    global $db;
    $mvc = new core_Mvc();
    $mvc->db = $db;
    foreach (array('id', 'state', 'folderId', 'searchKeywords') as $name) {
        $mvc->FLD($name, 'varchar');
    }
    $query = new core_Query(array('mvc' => $mvc));
    $query->EXT('folderShared', 'PagerFolders', 'externalName=shared,externalKey=folderId');
    $query->where("LOCATE('|198|', #folderShared)");
    $query->where("#state = 'active'");
    // Условието от търсенето по думи, както го отбелязва plg_Search::applySearch()
    $search = "LOCATE(' alpha', #searchKeywords)";
    $query->where($search);
    $query->searchConditions[$search] = $search;
    $query->orderBy('id', 'ASC');
    $query->addId = $addId;

    $pager = new PagerUnderTest();
    $pager->itemsPerPage = 10;
    $pager->pageVar = 'P';
    $pager->page = $page;
    $pager->setLimit($query);
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }
    sort($ids);

    return array($ids, (int) $pager->itemsCount);
}

// 14 активни документа с "alpha" в достъпната папка: 1, 3, ..., 27 - остатъкът под половин страница остава на последната
list($ids, $count) = pagerFixture(null);
checkPager($count === 14 && $ids === range(1, 27, 2), 'Без номер: намерените документи');

// Достъпен документ, който не съдържа думата, се добавя по номер - това е смисълът на търсенето по номер
list($ids, $count) = pagerFixture(2);
checkPager(in_array(2, $ids) && $count === 15, 'Достъпният документ се добавя по номер и се брои');

// Документ в недостъпна папка не се добавя и не се брои - със или без думата в него
foreach (array(101, 102) as $hiddenId) {
    list($ids, $count) = pagerFixture($hiddenId);
    checkPager(!in_array($hiddenId, $ids), "Документ {$hiddenId} от недостъпна папка не се показва по номер");
    checkPager($count === 14, "Документ {$hiddenId} от недостъпна папка не се брои");
}

// Останалите филтри важат и за записа по номер: чернова не влиза при филтър по активни
list($ids, $count) = pagerFixture(30);
checkPager(!in_array(30, $ids) && $count === 14, 'Документ извън филтъра не се добавя и не се брои');

// Несъществуващ номер
list($ids, $count) = pagerFixture(999);
checkPager(!in_array(999, $ids) && $count === 14, 'Несъществуващ номер не променя резултата');

// Страница след края: диапазонът се преизчислява и номерът се добавя отново - пак само ако е достъпен
list($ids, $count) = pagerFixture(102, 9);
checkPager(!in_array(102, $ids) && $count === 14, 'След преизчисляване на страницата недостъпният документ пак не се показва');
list($ids, $count) = pagerFixture(2, 9);
checkPager(in_array(2, $ids) && $count === 15, 'След преизчисляване на страницата достъпният документ остава');

// simplePaging: активните документи по id, страници по 10; връща id-тата, pager-а и броя изпълнени COUNT заявки
function simplePagingFixture($skipItemsCount, $page)
{
    global $db;
    $mvc = new core_Mvc();
    $mvc->db = $db;
    $mvc->simplePaging = true;
    foreach (array('id', 'state', 'folderId', 'searchKeywords') as $name) {
        $mvc->FLD($name, 'varchar');
    }
    $query = new core_Query(array('mvc' => $mvc));
    $query->where("#state = 'active'");
    $query->orderBy('id', 'ASC');

    $pager = new PagerUnderTest();
    $pager->itemsPerPage = 10;
    $pager->pageVar = 'P';
    $pager->exactPaging = true;
    $pager->page = $page;
    $pager->skipItemsCount = $skipItemsCount;
    core_QueryCnts::$lookups = 0;
    $db->log = array();
    $pager->setLimit($query);
    $ids = array();
    while ($rec = $query->fetch()) {
        $ids[] = (int) $rec->id;
    }

    return array($ids, $pager, countR(preg_grep('/count\(\*\)/i', $db->log)));
}

// 30 активни документа (1-28, 101, 102). Без skipItemsCount се брои и страницата след края става последната
list($ids, $pager, $counts) = simplePagingFixture(false, 5);
checkPager($counts === 1 && (int) $pager->itemsCount === 30 && $pager->page == 3 && $ids === array_merge(range(21, 28), array(101, 102)), 'simplePaging брои и ограничава страницата');

// Със skipItemsCount: без COUNT(*) и без кеша; половин страница отгоре показва, че има още
list($ids, $pager, $counts) = simplePagingFixture(true, 2);
checkPager($counts === 0 && core_QueryCnts::$lookups === 0 && $pager->itemsCount === PHP_INT_MAX, 'skipItemsCount не брои');
checkPager($pager->page == 2 && $ids === range(11, 25), 'skipItemsCount връща поисканата страница с допълнителните редове');

// Страница след края не се сменя с последната - идва празна и извикващият я разпознава
list($ids, $pager, $counts) = simplePagingFixture(true, 5);
checkPager($counts === 0 && $pager->page == 5 && $ids === array(), 'skipItemsCount оставя страницата след края празна');

echo "OK: {$checks} pager checks with the real Query builder, core_Pager and MariaDB.\n";
