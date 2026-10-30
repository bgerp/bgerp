<?php

/**
 * CLI проверки на правата за управление на документни връзки, без база данни
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});

/** Проверява очаквания резултат на сценарий. */
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
/** Брой елементи за реалния диспечер на hooks. */
function countR($value) { return is_countable($value) ? count($value) : 0; }

require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
class core_Mvc extends core_BaseClass
{
    public static $records = array();
    /** Чете само синтетичните записи на връзките. */
    public static function fetch($id) { return self::$records[$id] ?? false; }
}
class core_Master extends core_Mvc {}
class core_Users { const SYSTEM_USER = -1; }
require dirname(__DIR__, 2) . '/core/Manager.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__, 2) . '/plg/Rejected.class.php';
require dirname(__DIR__) . '/Linked.class.php';

class LinkedRightsFixture extends doc_Linked
{
    /** Запазва реалната проверка на състоянието на връзката. */
    public function __construct() { $this->_plugins = array(new plg_Rejected()); }
    /** Изтриването е забранено, както в doc_Linked. */
    public static function haveRightFor($action, $rec = null, $userId = null) { return false; }
}
class LinkedRightsDocument
{
    public $id;
    public static $rights = array();
    public static $missing = array();
    /** Задава идентификатора на синтетичния документ. */
    public function __construct($id) { $this->id = $id; }
    /** Връща документа или липсващ запис. */
    public function fetch() { return isset(self::$missing[$this->id]) ? false : (object) array('id' => $this->id); }
    /** Правата са отделни за всяко действие и потребител. */
    public function haveRightFor($action, $userId = null) { return self::$rights[$userId ?? 11][$this->id][$action] ?? false; }
}
class doc_Containers
{
    public static $records = array();
    /** Чете синтетичен контейнер. */
    public static function fetch($id) { return self::$records[$id] ?? false; }
    /** Създава документна препратка от контейнера. */
    public static function getDocument($rec) { return new LinkedRightsDocument($rec->docId ?? null); }
    /** Изолира заявката за избор на цитиран документ. */
    public static function getQuery() { return new LinkedRightsQuery(); }
}
class LinkedRightsQuery
{
    public $userId, $id;
    /** Запомня избрания контейнер. */
    public function where($condition) { $this->id = $condition[1]; }
    /** Връща запис само при нормален достъп през папката/нишката. */
    public function fetch() { return doc_Threads::$selectable[$this->userId ?? 11][$this->id] ?? false; }
}
class doc_Threads
{
    public static $selectable = array();
    /** Предава проверявания потребител на ограничения избор. */
    public static function restrictAccess($query, $userId = null) { $query->userId = $userId; }
}
class fileman_Files
{
    public static $records = array(), $rights = array();
    /** Чете синтетичен файл. */
    public static function fetch($id) { return self::$records[$id] ?? false; }
    /** Прилага стандартните права за файла. */
    public static function haveRightFor($action, $rec, $userId = null) { return self::$rights[$userId ?? 11][$rec->id ?? null] ?? false; }
}

$checks = 0;
$mvc = new LinkedRightsFixture();
$rec = (object) array('id' => 1, 'outType' => 'doc', 'outVal' => 101, 'inType' => 'doc', 'inVal' => 102, 'state' => 'active', 'createdBy' => 11);
core_Mvc::$records[1] = $rec;
foreach (array(101, 102) as $id) {
    doc_Containers::$records[$id] = (object) array('id' => $id, 'docClass' => 'fixture', 'docId' => $id);
}

foreach (array('edit', 'restore') as $action) {
    $rec->state = $action == 'restore' ? 'rejected' : 'active';
    check($mvc->getRequiredRoles($action, $rec, 12) === 'no_one', "$action denied without access to either endpoint");
    check($mvc->getRequiredRoles($action, $rec, 11) === 'no_one', "$action denied even for the link creator without document access");
}
$rec->state = 'active';
check($mvc->getRequiredRoles('reject', $rec, 12) === 'powerUser', 'Withdrawal is allowed without access to either endpoint');
check($mvc->getRequiredRoles('reject', $rec, 11) === 'powerUser', 'The creator can withdraw without document access');
$mvc->canReject = 'no_one';
check($mvc->getRequiredRoles('reject', $rec, 12) === 'no_one', 'Withdrawal retains the configured role restriction');
$mvc->canReject = 'powerUser';
foreach (array(101, 102) as $id) {
    foreach (array('single', 'psingle') as $right) {
        LinkedRightsDocument::$rights = array(12 => array($id => array($right => true)));
        check($mvc->getRequiredRoles('edit', $rec, 12) === 'powerUser', "Editing allowed from endpoint $id via $right");
        check($mvc->getRequiredRoles('edit', $rec, 11) === 'no_one', 'Editing checks the requested user');
        check($mvc->getRequiredRoles('reject', $rec, 12) === 'powerUser', "Withdrawal allowed from endpoint $id via $right");
    }
}
check($mvc->getRequiredRoles('edit', (object) array('id' => 1), 12) === 'powerUser', 'Partial records resolve stored endpoints');
$forged = clone $rec;
$forged->inVal = 999;
check($mvc->getRequiredRoles('edit', $forged, 12) === 'powerUser', 'Caller-supplied endpoints do not replace stored endpoints');
$mvc->canEdit = 'no_one';
check($mvc->getRequiredRoles('edit', $rec, 12) === 'no_one', 'Earlier denial stays denied');
$mvc->canEdit = 'powerUser';
LinkedRightsDocument::$rights[12][999]['single'] = true;
$forged->inVal = 999;
unset(LinkedRightsDocument::$rights[12][102]);
check($mvc->getRequiredRoles('edit', $forged, 12) === 'no_one', 'Forged accessible endpoints cannot grant rights');
LinkedRightsDocument::$rights[12][102]['psingle'] = true;
check($mvc->getRequiredRoles('restore', $rec, 12) === 'no_one', 'Active links cannot be restored');
$rec->state = 'rejected';
check($mvc->getRequiredRoles('reject', $rec, 12) === 'no_one', 'Rejected links cannot be rejected again');
check($mvc->getRequiredRoles('restore', $rec, 12) === 'no_one', 'One accessible endpoint cannot restore a link');
LinkedRightsDocument::$rights[12][101]['single'] = true;
check($mvc->getRequiredRoles('restore', $rec, 12) === 'no_one', 'pSingle alone for the cited document cannot restore a link');
doc_Threads::$selectable[12][102] = (object) array('id' => 102);
check($mvc->getRequiredRoles('restore', $rec, 12) === 'powerUser', 'Restoration retains access when the cited document can be selected');
check($mvc->getRequiredRoles('restore', $rec, 11) === 'no_one', 'Restoration checks both endpoints for the requested user');
LinkedRightsDocument::$rights[12][101] = array('psingle' => true);
check($mvc->getRequiredRoles('restore', $rec, 12) === 'powerUser', 'The source may retain pSingle access, as in the add-link flow');

$rec->state = 'active';
LinkedRightsDocument::$rights = array();
$rec->inType = 'file';
$rec->inVal = 201;
fileman_Files::$records[201] = (object) array('id' => 201);
check($mvc->getRequiredRoles('edit', $rec, 12) === 'no_one', 'An inaccessible file does not grant access');
fileman_Files::$rights[12][201] = true;
check($mvc->getRequiredRoles('edit', $rec, 12) === 'powerUser', 'An accessible file permits editing its link');
$rec->state = 'rejected';
check($mvc->getRequiredRoles('restore', $rec, 12) === 'no_one', 'Restoration also requires the source');
LinkedRightsDocument::$rights[12][101]['psingle'] = true;
check($mvc->getRequiredRoles('restore', $rec, 12) === 'powerUser', 'An accessible source and file allow restoration');
unset(fileman_Files::$records[201]);
LinkedRightsDocument::$missing[101] = true;
check($mvc->getRequiredRoles('edit', $rec, 12) === 'no_one', 'Missing records do not grant general manager rights');
check($mvc->getRequiredRoles('edit', (object) array('id' => 999), 12) === 'no_one', 'A missing link is denied');
check($mvc->getRequiredRoles('edit', null, 12) === 'powerUser', 'General role checks remain unchanged');
check($mvc->getRequiredRoles('viewlist', $rec, 12) === 'powerUser', 'Display rights remain unchanged');
check($mvc->getRequiredRoles('addlink', $rec, 12) === 'powerUser', 'The separate add-link flow remains unchanged');

echo "OK: {$checks} linked rights checks\n";
