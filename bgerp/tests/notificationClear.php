<?php

/**
 * Изолирани проверки на прочитането на известия и кеша на портала.
 *
 * @category bgerp
 * @package bgerp
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta OOD
 * @license GPL 3
 * @since v 0.1
 */
// php -n bgerp/tests/notificationClear.php - без база и външни услуги.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class Request
{
    public static $values = array();
    public static function get($name) { return self::$values[$name] ?? null; }
}
class core_Users
{
    public static function getCurrent() { return 7; }
}
class dt
{
    public static function now() { return '2026-01-01 10:00:00'; }
}
function toUrl($url, $type, $protect) { return 'test/' . implode('/', $url); }
class core_Manager
{
    public static $records = array();
    public static function getQuery() { return new NotificationQuery(); }
    public static function save($rec, $fields)
    {
        $rec->modifiedOn = dt::now();
        self::$records[$rec->id ?? 0] = clone $rec;
    }
}
class NotificationQuery
{
    private $rows = array();
    public function where($condition)
    {
        $hasUser = preg_match('/#userId = (\d+)/', $condition[0], $match);
        foreach (core_Manager::$records as $rec) {
            if (($rec->url ?? null) === $condition[1] && ($rec->state ?? null) === 'active'
                && (!$hasUser || ($rec->userId ?? null) == $match[1])) {
                $this->rows[] = clone $rec;
            }
        }
    }
    public function show($fields) {}
    public function fetch() { return array_shift($this->rows); }
}
class bgerp_Portal
{
    public static $cache = array(), $invalidated = array();
    public static function invalidateCache($userId, $driver)
    {
        check($driver === 'bgerp_drivers_Notifications', 'Only the notification block is invalidated');
        check((core_Manager::$records[$userId]->state ?? null) === 'closed', 'Invalidate after persisting the read state');
        unset(self::$cache[$userId]);
        self::$invalidated[] = $userId;
    }
}
require dirname(__DIR__) . '/Notifications.class.php';

$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
$url = array('ai_Dialogs', 'single', 42);
foreach (array(7, 8) as $userId) {
    core_Manager::$records[$userId] = (object) array('id' => $userId, 'userId' => $userId,
        'url' => toUrl($url, 'local', false), 'state' => 'active', 'modifiedOn' => dt::now());
    // Различните езици/страници на портала имат отделни кеширани блокове.
    bgerp_Portal::$cache[$userId] = array('bg-page1' => 'unread', 'en-page2' => 'unread');
}

foreach (array('ajax_mode', 'OnlyMeet') as $flag) {
    Request::$values = array($flag => true);
    bgerp_Notifications::clear($url);
    check((core_Manager::$records[7]->state ?? null) === 'active', $flag . ' must not mark the notification read');
    check(isset(bgerp_Portal::$cache[7]), $flag . ' must keep the cached block');
}
Request::$values = array();
bgerp_Notifications::clear(array('ai_Dialogs', 'single', 43));
check(bgerp_Portal::$invalidated === array(), 'Opening another conversation does not invalidate notifications');

bgerp_Notifications::clear($url);
check((core_Manager::$records[7]->state ?? null) === 'closed', 'The current reader marks the notification read');
check((core_Manager::$records[7]->modifiedOn ?? null) === dt::now(), 'Reading can leave the timestamp unchanged');
check(!isset(bgerp_Portal::$cache[7]), 'No unread block survives reading in the same second');
check((core_Manager::$records[8]->state ?? null) === 'active' && isset(bgerp_Portal::$cache[8]), 'Other readers keep their notification and cache');
bgerp_Notifications::clear($url);
check(bgerp_Portal::$invalidated === array(7), 'An already read notification needs no further invalidation');

bgerp_Notifications::clear($url, '*');
check((core_Manager::$records[8]->state ?? null) === 'closed' && !isset(bgerp_Portal::$cache[8]), 'Clearing for all users invalidates each affected reader');
check(bgerp_Portal::$invalidated === array(7, 8), 'Only changed readers are invalidated');

echo "PASS: {$checks} notification read/cache checks\n";
