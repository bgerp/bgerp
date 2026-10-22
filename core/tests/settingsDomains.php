<?php

// php core/tests/settingsDomains.php — real settings/configuration paths without a database.
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function expect($condition) { check($condition, 'Missing configuration value'); }
class core_BaseClass
{
    public function invoke($event, $args) {
        check($event === 'BeforeGetConfConst', 'Unexpected configuration event');
        return (new crm_PersonalConfigPlg())->on_BeforeGetConfConst($this, $args[0], $args[1]);
    }
}
class core_Plugin extends core_BaseClass {}
class core_Manager extends core_BaseClass
{
    public static function getQuery() { return new SettingsDomainQuery(); }
}
class str
{
    public static function convertToFixedKey($key, $length, $hashLength) { return substr(md5($key), 0, $length); }
}
class core_Users
{
    public static $current = 7;
    public static function getCurrent() { return self::$current; }
    public static function getRoles($user) { return '|3|'; }
}
class type_Keylist
{
    public static function toArray($list) { return array(3 => 3); }
}
class type_UserOrRole
{
    public static function getAllSysTeamId() { return -100; }
    public static function getSysRoleId($id, $type = 'role') {
        return $type === 'domain' ? -2000 - $id : ($id ? -100 - $id : 0);
    }
    public static function getRoleIdFromSys($id, &$type) {
        $type = $id <= -2000 ? 'domain' : 'role';
        return -$id - ($type === 'domain' ? 2000 : 100);
    }
}
class crm_Profiles
{
    public static function getSettingsKey() { return 'login-profile'; }
}
class cms_Domains
{
    public static $failure;
    public static $calls = 0;
    public static function getCurrent($part, $force) {
        self::$calls++;
        check($part === 'id' && $force === false, 'Unexpected current domain request');
        if (self::$failure) throw self::$failure;
        return 42;
    }
}
class SettingsDomainQuery
{
    private $roles = array();
    private $offset = 0;
    private $rows;
    public function __construct() {
        $this->rows = array(
            (object) array('userOrRole' => 7, 'data' => array('title' => 'User title', 'userOnly' => true)),
            (object) array('userOrRole' => -103, 'data' => array('roleOnly' => true)),
            (object) array('userOrRole' => 0, 'data' => array('title' => 'System title', 'systemOnly' => true)),
            (object) array('userOrRole' => -2042, 'data' => array('title' => 'Domain title', 'domainOnly' => true)),
        );
    }
    public function where($condition, $or = false) {
        $sql = is_array($condition) ? $condition[0] : $condition;
        if (strpos($sql, '#userOrRole') === false) return;
        if (is_array($condition)) {
            $this->roles[] = (int) $condition[1];
        } elseif (preg_match('/IN \(([^)]+)\)/', $sql, $match)) {
            $this->roles = array_merge($this->roles, array_map('intval', explode(',', $match[1])));
        } elseif (preg_match('/= (-?\d+)/', $sql, $match)) {
            $this->roles[] = (int) $match[1];
        }
    }
    public function orWhere($condition) { $this->where($condition, true); }
    public function XPR($name, $type, $expression) {}
    public function orderBy($name, $direction = null) {}
    public function fetch() {
        while (isset($this->rows[$this->offset])) {
            $rec = $this->rows[$this->offset++];
            if (in_array($rec->userOrRole ?? null, $this->roles, true)) return $rec;
        }
        return false;
    }
}
require dirname(__DIR__) . '/exception/Expect.class.php';
require dirname(__DIR__) . '/exception/Db.class.php';
require dirname(__DIR__) . '/Settings.class.php';
require dirname(__DIR__) . '/ObjectConfiguration.class.php';
require dirname(__DIR__, 2) . '/crm/PersonalConfigPlg.class.php';

foreach (array(1054, 1146) as $code) {
    cms_Domains::$failure = new core_exception_Db('CMS schema unavailable', 'DB', array('mysqlErrCode' => $code));
    $values = core_Settings::fetchKey('missing-' . $code, 7);
    check($values === array('title' => 'User title', 'userOnly' => true, 'roleOnly' => true, 'systemOnly' => true), 'Non-domain settings must survive missing CMS schema');
}
echo "PASS: missing CMS columns/tables preserve user, role and system settings\n";

cms_Domains::$failure = null;
$values = core_Settings::fetchKey('missing-1054', 7);
check(!empty($values['domainOnly']) && $values['title'] === 'User title', 'Fallback must not be cached after the CMS schema becomes available');
$beforeCalls = cms_Domains::$calls;
check(core_Settings::fetchKey('missing-1054', 7) === $values && cms_Domains::$calls === $beforeCalls, 'Successful settings must remain cached');
echo "PASS: domain settings recover after initialization and normal caching is preserved\n";

cms_Domains::$failure = new core_exception_Db('CMS schema unavailable', 'DB', array('mysqlErrCode' => 1054));
$beforeCalls = cms_Domains::$calls;
$values = core_Settings::fetchKey('explicit-domain', -2042);
check(!empty($values['domainOnly']) && cms_Domains::$calls === $beforeCalls, 'An explicit domain must not trigger automatic domain lookup');
echo "PASS: explicit domain settings do not depend on current domain lookup\n";

foreach (array(1045, 2013) as $code) {
    $error = new core_exception_Db('Database failure', 'DB', array('mysqlErrCode' => $code));
    cms_Domains::$failure = $error;
    for ($attempt = 0; $attempt < 2; $attempt++) {
        try {
            core_Settings::fetchKey('failure-' . $code, 7);
            throw new RuntimeException('An unrelated database failure was swallowed or cached');
        } catch (core_exception_Db $caught) {
            check($caught === $error, 'The original database exception must propagate');
        }
    }
}
echo "PASS: unrelated database failures propagate and do not leave an empty cached result\n";

core_Users::$current = 0;
cms_Domains::$failure = new core_exception_Db('Missing navigation_position', 'DB', array('mysqlErrCode' => 1054));
$config = new core_ObjectConfiguration();
$config->init(array(array('title' => array('varchar', 'customizeBy=user')), array('title' => 'Package title')));
check($config->title === 'System title', 'Anonymous login configuration must remain readable before CMS initialization');
cms_Domains::$failure = null;
check($config->title === 'System title', 'Configuration priority must remain unchanged after initialization');
echo "PASS: login configuration resolves through the real personalization plugin before and after CMS initialization\n";
