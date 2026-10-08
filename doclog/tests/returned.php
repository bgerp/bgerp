<?php

// php doclog/tests/returned.php - return persistence/rights with isolated in-memory records.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok, $message = null) { if (!$ok) throw new RuntimeException('Expectation'); }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function tr($text) { return $text; }
class core_Manager {}
class core_exception_Expect extends RuntimeException {}
class core_Locks {
    public static $held = false;
    public static function obtain($key, $duration, $tries) { check(!self::$held, 'Lock not leaked'); return self::$held = true; }
    public static function release($key) { self::$held = false; }
}
class dt { public static function now() { return '2026-01-02 12:00:00'; } }
class core_Users {
    public static $user = 1, $partner = false;
    public static function getCurrent() { return self::$user; }
    public static function haveRole($role, $user) { return self::$partner; }
}
class bgerp_Notifications { public static $count = 0; public static function add($message, $url, $user, $level) { self::$count++; } }
class FixtureDocument {
    public static $allowed = true;
    public $that = 1;
    public function getInstance() { return $this; }
    public function logInfo($message, $id, $days) {}
    public function haveRightFor($action, $user) { return self::$allowed; }
}
class doc_Containers {
    public static function getDocument($id) { return new FixtureDocument(); }
    public static function getDocTitle($id) { return 'Synthetic note'; }
}
require dirname(__DIR__) . '/Documents.class.php';
class ReturnHistoryFixture extends doclog_Documents {
    public static $records = array(), $writes = 0;
    public static function fetch($condition, $fields = '*', $cache = true) {
        check($cache === false, 'Refreshes records inside lock');
        foreach (self::$records as $rec) {
            $matches = strpos($condition[0], '#mid') !== false
                ? ($rec->mid ?? '') === $condition[1] && $rec->action === $condition[2]
                : ($rec->parentId ?? 0) === $condition[1] && $rec->containerId === $condition[2] && $rec->action === $condition[3];
            if ($matches) return unserialize(serialize($rec));
        }
        return null;
    }
    public static function save($rec) {
        check(core_Locks::$held, 'Serializes return writes');
        if (empty($rec->id)) $rec->id = count(self::$records) + 1;
        self::$records[$rec->id] = unserialize(serialize($rec));
        self::$writes++;
    }
    public static function getLinkToSingle($id, $action = null) { return array('document', $id); }
}
ReturnHistoryFixture::$records[1] = (object) array('id' => 1, 'mid' => 'testmid', 'action' => 'send', 'containerId' => 10,
    'threadId' => 20, 'createdBy' => 1, 'data' => (object) array('to' => 'first@example.test,second@example.test'));
$first = array('first' => array('recipient' => 'first@example.test', 'text' => '550 User unknown'));
check(ReturnHistoryFixture::returned('testmid', null, null, $first), 'Records first return');
check(count(ReturnHistoryFixture::$records) === 2 && bgerp_Notifications::$count === 1, 'Creates one marker and one notification');
$writes = ReturnHistoryFixture::$writes;
ReturnHistoryFixture::returned('testmid', null, null, $first);
check(ReturnHistoryFixture::$writes === $writes, 'Duplicate report does not rewrite or duplicate details');
ReturnHistoryFixture::returned('testmid', '2026-01-03 12:00:00', null, array('second' => array('recipient' => 'second@example.test', 'text' => '552 Mailbox full')));
check(count(ReturnHistoryFixture::$records[2]->data->returnDetails) === 2, 'Keeps a later report for another recipient');
check(ReturnHistoryFixture::$records[1]->data->returnedOn === '2026-01-02 12:00:00' && bgerp_Notifications::$count === 1, 'Preserves first date and avoids repeat notification');
check(ReturnHistoryFixture::returned('testmid') && ReturnHistoryFixture::returned('missing') === false, 'Legacy signature and unmatched MID');
check(!core_Locks::$held, 'Releases lock after early returns');
$rec = ReturnHistoryFixture::$records[2];
foreach (array(array(1, false, true, 'user'), array(1, false, false, 'no_one'), array(0, false, true, 'no_one'), array(1, true, true, 'no_one')) as $case) {
    list(core_Users::$user, core_Users::$partner, FixtureDocument::$allowed, $expected) = $case;
    $roles = 'user';
    doclog_Documents::on_AfterGetRequiredRoles(null, $roles, 'returndetails', $rec);
    check($roles === $expected, 'Details follow internal document permissions');
}
echo "returned: {$checks} checks passed.\n";
