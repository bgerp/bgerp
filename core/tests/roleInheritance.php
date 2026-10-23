<?php

/**
 * Regression checks for role inheritance and user role recalculation.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// CLI only: real role/user methods and keylists, with in-memory persistence.
// Run: php core/tests/roleInheritance.php [path/to/historical/Roles.class.php]
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($ok) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
define('EF_SALT', 'synthetic-role-test');
class core_Type {}
class arr
{
    public static function make($value) { return is_array($value) ? $value : explode(',', $value); }
}
class core_Manager
{
    public static $records = array();
    public static $user;
    public $core_Roles;
    public static function fetch($condition, $fields = '*', $cache = true)
    {
        if (get_called_class() === 'core_Users') return clone self::$user;
        // Allow the previous implementation's name lookup when comparing historical source.
        if (is_string($condition) && preg_match("/^#role = '(.*)'$/s", $condition, $match)) {
            foreach (self::$records as $rec) {
                if (($rec->role ?? null) === $match[1]) return clone $rec;
            }
            return false;
        }
        if (is_array($condition)) {
            check($condition[0] === "#role = '[#1#]'", 'Role names must use query substitution');
            foreach (self::$records as $rec) {
                if (($rec->role ?? null) === $condition[1]) return clone $rec;
            }
            return false;
        }
        return isset(self::$records[$condition]) ? clone self::$records[$condition] : false;
    }
    public static function count() { return count(self::$records); }
    public static function getQuery()
    {
        return new RoleFixtureQuery(get_called_class() === 'core_Users' ? array(clone self::$user) : array_values(self::$records));
    }
    public static function save($rec, $fields = null)
    {
        expect(get_called_class() === 'core_Users');
        $id = null;
        core_Users::on_BeforeSave(cls::get('core_Users'), $id, $rec, $fields);
        self::$user = clone $rec;
    }
    public function save_($rec, $fields = null) { self::$records[$rec->id] = clone $rec; }
}
class RoleFixtureQuery
{
    private $records;
    public function __construct($records) { $this->records = $records; }
    public function fetch() { return array_shift($this->records); }
}
class cls
{
    public static $objects = array();
    public static function get($class)
    {
        if (!isset(self::$objects[$class])) self::$objects[$class] = new $class();
        return self::$objects[$class];
    }
}
require dirname(__DIR__, 2) . '/type/Keylist.class.php';
class_alias('type_Keylist', 'keylist');
require $argv[1] ?? dirname(__DIR__) . '/Roles.class.php';
require dirname(__DIR__) . '/Users.class.php';

$checks = 0;
function roleFixture($id, $name, $input = '', $inherit = '')
{
    return (object) array('id' => $id, 'role' => $name, 'inheritInput' => $input, 'inherit' => $inherit);
}
function resetRoles()
{
    core_Manager::$records = array(
        1 => roleFixture(1, 'user'),
        2 => roleFixture(2, 'powerUser'),
        3 => roleFixture(3, 'executive', '|2|', '|2|'),
        4 => roleFixture(4, 'officer', '|2|3|', '|2|3|'),
        5 => roleFixture(5, 'manager', '|2|4|', '|2|3|4|'),
        6 => roleFixture(6, 'ceo', '|2|5|', '|2|3|4|5|'),
        7 => roleFixture(7, 'partner'),
        8 => roleFixture(8, 'powerPartner', '|7|', '|7|'),
        9 => roleFixture(9, 'team'),
        10 => roleFixture(10, 'job', '|3|', '|2|3|'),
        11 => roleFixture(11, 'admin'),
        13 => roleFixture(13, 'ai'),
        14 => roleFixture(14, 'aiMaster', '|13|', '|13|'),
        15 => roleFixture(15, 'demo'),
    );
    core_Roles::$rolesArr = array();
    foreach (core_Manager::$records as $rec) {
        core_Roles::$rolesArr[$rec->id] = $rec->role;
        core_Roles::$rolesArr[$rec->role] = $rec->id;
    }
    cls::$objects = array();
    cls::get('core_Users')->core_Roles = cls::get('core_Roles');
}
function expanded($input) { return keylist::fromArray(core_Roles::expand($input)); }
function recalculateUser($input, $oldRoles)
{
    core_Manager::$user = (object) array('id' => 100, 'state' => 'active', 'rolesInput' => $input, 'roles' => $oldRoles);
    $rec = clone core_Manager::$user;
    $id = null;
    core_Users::on_BeforeSave(cls::get('core_Users'), $id, $rec, 'roles');
    return $rec;
}

resetRoles();
foreach (array(null, '', array()) as $empty) check(expanded($empty) === '', 'Empty input must stay empty');
check(expanded('|3|9|') === '|2|3|9|', 'Direct inheritance and independent team');
check(expanded('|6|9|10|') === '|2|3|4|5|6|9|10|', 'Multilevel inheritance with shared descendants');
check(expanded(array(3, 3, 9)) === '|2|3|9|', 'Duplicate input must not duplicate roles');
check(expanded(array('executive', 'team')) === '|2|3|9|', 'Names resolve to the same role set');
check(expanded(array(core_Manager::$records[3])) === '|2|3|', 'Record input uses its declared inheritance');
check(expanded('|999|3|') === '|2|3|', 'Missing role references are skipped');
core_Manager::$records[12] = roleFixture(12, "quoted'role", '|2|', '|2|');
check(expanded(array("quoted'role")) === '|2|12|', 'Quoted name stays a data value');
core_Manager::$records[3]->state = 'closed';
check(expanded('|3|') === '|2|3|', 'Existing assignment of a closed role retains its inheritance');
$current = array(9 => 9);
core_Roles::expand('|3|', $current);
check(keylist::fromArray($current) === '|2|3|9|', 'Caller accumulator is updated and preserved');
check(expanded('|7|') === '|7|', 'External partner receives no powerUser');
check(expanded('|8|') === '|7|8|', 'Power partner receives no internal access');
check(recalculateUser('|3|9|', '|1|2|3|9|')->roles === '|1|2|3|9|', 'Correct user roles survive a save');
check(recalculateUser('|6|9|11|', '|1|2|3|4|5|6|9|11|')->roles === '|1|2|3|4|5|6|9|11|', 'CEO/admin/team roles survive a save');
check(recalculateUser('|7|', '|1|7|')->roles === '|1|7|', 'Partner remains external after a save');
check(recalculateUser('|8|', '|1|7|8|')->roles === '|1|7|8|', 'Power partner remains external after a save');
check(recalculateUser('|3|9|14|15|', '|1|2|3|9|13|14|')->roles === '|1|2|3|9|13|14|15|', 'Adding demo preserves both inherited powerUser and ai');
check(recalculateUser('|3|9|14|', '|1|2|3|9|13|14|15|')->roles === '|1|2|3|9|13|14|', 'Removing demo from a correct graph removes only demo');
echo "PASS: correct graphs, input variants and existing user recalculation\n";

// The user still has powerUser; only the role's previously calculated inheritance is incomplete.
core_Manager::$records[3]->inherit = '';
$rec = recalculateUser('|3|9|11|', '|1|2|3|9|11|');
check($rec->roles === '|1|2|3|9|11|', 'Existing powerUser must survive profile save with incomplete calculated inheritance');
check(empty($rec->__updateRoleLogs), 'Unchanged effective roles must not create a role-change log');
check(expanded('|10|') === '|2|3|10|', 'Incomplete intermediate inheritance is recovered');
check(recalculateUser('|2|3|9|', '|1|2|3|9|')->roles === '|1|2|3|9|', 'Explicit powerUser remains assigned');
check(recalculateUser('|3|9|', '|1|3|9|')->roles === '|1|2|3|9|', 'A previously damaged user is repaired on save');
foreach (core_Manager::$records as $role) $role->inherit = '';
check(recalculateUser('|3|9|14|', '|1|2|3|9|13|14|15|')->roles === '|1|2|3|9|13|14|', 'Removing demo with empty calculated inheritance preserves powerUser and ai');
echo "PASS: incomplete inheritance preserves and restores indirect powerUser\n";

resetRoles();
core_Manager::$records[3]->inheritInput = '';
check(expanded('|3|') === '|3|', 'Removed inheritance is not resurrected from stale calculated roles');
check(recalculateUser('|3|9|', '|1|2|3|9|')->roles === '|1|3|9|', 'A real removal revokes powerUser');
check(recalculateUser('|2|3|9|', '|1|2|3|9|')->roles === '|1|2|3|9|', 'An explicit role survives removal of an inherited path');
core_Manager::$records[3]->inherit = '';
check(expanded('|3|') === '|3|', 'Missing declared and calculated inheritance must not invent access');
echo "PASS: stale extra roles are revoked; missing declared links do not grant rights\n";

resetRoles();
core_Manager::$records[3]->inheritInput = '|4|';
check(expanded('|3|') === '|2|3|4|', 'A pre-existing cycle terminates with a unique role set');
core_Manager::$records[3]->inheritInput = '|3|';
check(expanded('|3|') === '|3|', 'A self-reference terminates');
resetRoles();
foreach (core_Manager::$records as $rec) $rec->inherit = '';
core_Roles::rebuildRoles();
check(core_Manager::$records[6]->inherit === '|2|3|4|5|', 'Rebuild restores a complete multilevel graph');
$before = serialize(core_Manager::$records);
core_Roles::rebuildRoles();
check(serialize(core_Manager::$records) === $before, 'Rebuild is idempotent');
core_Manager::$records[3]->inheritInput = '';
core_Roles::rebuildRoles();
check(core_Manager::$records[10]->inherit === '|3|', 'Rebuild removes stale transitive inheritance');
resetRoles();
foreach (core_Manager::$records as $role) $role->inherit = '';
core_Manager::$user = (object) array('id' => 100, 'state' => 'active', 'rolesInput' => '|3|9|14|', 'roles' => '|1|3|9|14|');
$rolesMvc = cls::get('core_Roles');
$rolesMvc->recalcRoles = true;
core_Roles::on_Shutdown($rolesMvc);
check(core_Manager::$user->roles === '|1|2|3|9|13|14|', 'Role shutdown rebuild repairs existing users as well as the role graph');
check(!$rolesMvc->recalcRoles, 'Role shutdown clears the pending rebuild flag');
echo "PASS: cycles, full rebuild, removal and idempotence\n";

// Compare with an independent iterative reachability oracle, including cycles and missing nodes.
mt_srand(20261023);
for ($case = 0; $case < 60; $case++) {
    core_Manager::$records = array();
    $edges = array();
    for ($id = 1; $id <= 15; $id++) {
        $edges[$id] = array();
        for ($j = 0; $j < 4; $j++) $edges[$id][mt_rand(1, 17)] = true;
        core_Manager::$records[$id] = roleFixture($id, 'node' . $id, keylist::fromArray($edges[$id]), '|999|');
    }
    for ($root = 1; $root <= 15; $root++) {
        $queue = array($root); $expected = array();
        while ($queue) {
            $node = array_shift($queue);
            if (isset($expected[$node]) || !isset($edges[$node])) continue;
            $expected[$node] = $node;
            foreach ($edges[$node] as $child => $unused) $queue[] = $child;
        }
        check(expanded(array($root)) === keylist::fromArray($expected), 'Reachability mismatch in generated graph ' . $case);
    }
}
echo "PASS: 900 independent graph comparisons\n";
echo "OK: {$checks} checks\n";
