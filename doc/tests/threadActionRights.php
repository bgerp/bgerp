<?php

/**
 * CLI проверки на правата за действия с документ; без база данни.
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

$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function countR($value) { return is_countable($value) ? count($value) : 0; }
function expect($condition) { if (!$condition) throw new RuntimeException('Expectation failed'); }

class core_Users
{
    const SYSTEM_USER = -1;
    public static $current = 11;
    public static $partners = array();
    public static $powerPartners = array();
    public static function getCurrent() { return self::$current; }
    public static function haveRole($role, $userId = null)
    {
        $userId = $userId ?? self::$current;

        return $role == 'powerPartner' ? isset(self::$powerPartners[$userId]) : isset(self::$partners[$userId]);
    }
}
function haveRole($role, $userId = null) { return core_Users::haveRole($role, $userId); }
class core_Packs
{
    public static $colab = false;
    public static function isInstalled($pack) { return self::$colab; }
}
class doc_Threads
{
    public static $rights = array();
    public static $records = array();
    public static $checkedUsers = array();
    public static function haveRightFor($action, $id, $userId)
    {
        self::$checkedUsers[] = $userId;

        return self::$rights[$userId][$id] ?? false;
    }
    public static function fetch($id) { return self::$records[$id] ?? false; }
}
class colab_Folders
{
    public static $shared = array();
    public static function getSharedFolders($userId) { return self::$shared[$userId] ?? array(); }
}
class keylist
{
    public static function isIn($id, $list) { return strpos($list ?? '', "|{$id}|") !== false; }
}
class Mode
{
    public static $allowed = array();
    public static function get($name) { return self::$allowed[$name] ?? null; }
    public static function is($name) { return false; }
}

$root = dirname(__DIR__, 2);
require $root . '/core/BaseClass.class.php';
require $root . '/core/Plugin.class.php';
require $root . '/doc/DocumentPlg.class.php';
class core_Manager extends core_BaseClass
{
    public static $checkedUsers = array();
    public static function haveRightFor($action, $rec, $userId)
    {
        self::$checkedUsers[] = $userId;
        $mvc = new static();
        $roles = $mvc->canSingle;
        static::on_AfterGetRequiredRoles($mvc, $roles, $action, $rec, $userId);

        return $roles != 'no_one' && core_Users::haveRole($roles, $userId);
    }
}
require $root . '/colab/Threads.class.php';

class ThreadActionDocumentFixture extends core_BaseClass
{
    public $record;
    public $lateChecks = 0;
    public function __construct()
    {
        $this->_plugins = array(new doc_DocumentPlg());
        $this->record = (object) array('id' => 1, 'threadId' => 10, 'createdBy' => 11, 'state' => 'draft');
    }
    public function fetch($id) { return $id == 1 ? clone $this->record : false; }
    public function getRequiredRoles_($action, $rec = null, $userId = null) { return 'no_one'; }
    public static function on_AfterGetRequiredRoles($mvc, &$roles)
    {
        // Моделът има последната дума само ако общата проверка допуска действието.
        $mvc->lateChecks++;
        $roles = 'powerUser';
    }
}

$mvc = new ThreadActionDocumentFixture();
$actions = array('edit', 'changerec', 'pickup', 'assign', 'reject', 'restore', 'activate',
    'activateagain', 'pending', 'changestate', 'conto', 'reconto', 'debugreconto',
    'stop', 'start', 'close', 'wakeup');

foreach (array('draft', 'pending', 'active', 'rejected', 'closed', 'stopped') as $state) {
    $mvc->record->state = $state;
    foreach ($actions as $action) {
        $before = $mvc->lateChecks;
        check($mvc->getRequiredRoles($action, $mvc->record, 11) === 'no_one', "{$state}/{$action} needs thread access");
        check($mvc->lateChecks === $before, 'Later hooks must not restore denied rights');
    }
}

foreach (array('single', 'psingle', 'viewpsingle', 'read', 'exportdoc', 'like', 'dislike', 'tag', 'link') as $action) {
    check($mvc->getRequiredRoles($action, $mvc->record, 11) === 'powerUser', "{$action} retains its existing checks");
}
check($mvc->getRequiredRoles('activate', null, 11) === 'powerUser', 'General action rights remain unchanged');
check($mvc->getRequiredRoles('activate', (object) array('threadId' => 10), 11) === 'powerUser', 'New-document checks remain unchanged');
check($mvc->getRequiredRoles('activate', $mvc->record, core_Users::SYSTEM_USER) === 'powerUser', 'System activation retains its existing checks');

doc_Threads::$rights[12][10] = true;
foreach ($actions as $action) {
    check($mvc->getRequiredRoles($action, $mvc->record, 12) === 'powerUser', "{$action} retains its checks with thread access");
}
check(end(doc_Threads::$checkedUsers) === 12, 'The requested user is checked, not the session user');
check($mvc->getRequiredRoles('edit', (object) array('id' => 1, 'threadId' => 99), 11) === 'no_one', 'Caller-supplied thread does not replace the stored thread');
check($mvc->getRequiredRoles('edit', (object) array('id' => 1), 12) === 'powerUser', 'Partial records resolve the stored thread');
unset($mvc->record->threadId);
check($mvc->getRequiredRoles('edit', $mvc->record, 12) === 'no_one', 'Missing thread does not become a general rights check');
$mvc->record->threadId = 10;

core_Packs::$colab = true;
core_Users::$partners[13] = true;
doc_Threads::$records[10] = (object) array('id' => 10, 'folderId' => 20, 'visibleForPartners' => 'yes', 'createdBy' => 13);
check($mvc->getRequiredRoles('activate', $mvc->record, 13) === 'no_one', 'A partner without thread access cannot activate');
colab_Folders::$shared[13] = array(20);
foreach ($actions as $action) {
    check($mvc->getRequiredRoles($action, $mvc->record, 13) === 'powerUser', "Authorized partner {$action} retains existing checks");
}
check(end(colab_Threads::$checkedUsers) === 13, 'Partner access uses the requested user');
doc_Threads::$records[10]->visibleForPartners = 'no';
check($mvc->getRequiredRoles('activate', $mvc->record, 13) === 'no_one', 'A thread hidden from partners cannot be activated');
doc_Threads::$records[10]->visibleForPartners = 'yes';
doc_Threads::$records[10]->createdBy = 14;
check($mvc->getRequiredRoles('edit', $mvc->record, 13) === 'no_one', 'An ordinary partner cannot modify an unshared foreign thread');
doc_Threads::$records[10]->shared = '|13|';
check($mvc->getRequiredRoles('edit', $mvc->record, 13) === 'powerUser', 'A shared thread retains the existing document checks');
doc_Threads::$records[10]->shared = '';
core_Users::$powerPartners[13] = true;
check($mvc->getRequiredRoles('edit', $mvc->record, 13) === 'powerUser', 'A power partner retains access to visible threads in shared folders');
colab_Folders::$shared[13] = array();
check($mvc->getRequiredRoles('edit', $mvc->record, 13) === 'no_one', 'A power partner still needs a shared folder');
doc_Threads::$rights[13][10] = true;
check($mvc->getRequiredRoles('edit', $mvc->record, 13) === 'powerUser', 'A user with both internal and partner roles retains authorized internal access');
doc_Threads::$rights[13][10] = false;
unset(doc_Threads::$records[10]);
check($mvc->getRequiredRoles('activate', $mvc->record, 13) === 'no_one', 'A missing partner thread is denied');

class ThreadReadDocumentFixture extends ThreadActionDocumentFixture
{
    public $canViewpsingle = 'storeAll';
    public function getRequiredRoles_($action, $rec = null, $userId = null)
    {
        return $action == 'viewpsingle' ? $this->canViewpsingle : 'powerUser';
    }
    public function getAllowedContainerName() { return 'allowedContainers'; }
    public static function on_AfterGetRequiredRoles($mvc, &$roles) { return false; }
}
core_Packs::$colab = false;
$reader = new ThreadReadDocumentFixture();
$reader->record->createdBy = 12;
$reader->record->containerId = 30;
check($reader->getRequiredRoles('single', $reader->record, 11) === 'no_one', 'A foreign document is not readable without thread or pSingle access');
check($reader->getRequiredRoles('psingle', $reader->record, 11) === 'no_one', 'pSingle still needs an allowed container');
check($reader->getRequiredRoles('viewpsingle', $reader->record, 11) === 'storeAll', 'Configured All-role list preview rights retain their existing roles');
Mode::$allowed['allowedContainers'][30] = 30;
check($reader->getRequiredRoles('psingle', $reader->record, 11) === 'powerUser', 'An allowed pSingle remains readable');
check($reader->getRequiredRoles('single', $reader->record, 11) === 'powerUser', 'pSingle access continues to permit the restricted single');
check($reader->getRequiredRoles('changerec', $reader->record, 11) === 'no_one', 'pSingle access does not authorize document changes');

echo "OK: {$checks} checks\n";
