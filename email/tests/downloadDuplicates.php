<?php

// php email/tests/downloadDuplicates.php [email-source-directory] - no database or network.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function expect($condition) { if (!$condition) throw new RuntimeException('Unexpected failed expectation'); }

class core_Mvc {}
class core_Manager extends core_Mvc
{
    public static $records = array();
    public static function getQuery() { return new FingerprintQuery(); }
    public static function fetch($condition, $fields = '*', $cache = false)
    {
        foreach (self::$records as $rec) {
            if (in_array($rec->hash, array_slice($condition, 1), true)) return clone $rec;
        }
        return false;
    }
    public static function fetchField($condition, $field)
    {
        preg_match('/[a-f0-9]{32}/', $condition, $matches);
        $rec = self::fetch(array('', $matches[0]));
        return $rec->{$field} ?? null;
    }
    public static function save($rec)
    {
        $rec->id = count(self::$records) + 1;
        self::$records[$rec->id] = clone $rec;
        email_Fingerprints::on_AfterSave(new email_Fingerprints(), $rec->id, $rec);
    }
}
class core_Master extends core_Manager {}
class FingerprintQuery
{
    private $accountId;
    public function show($fields) {}
    public function orderBy($field, $direction) {}
    public function limit($limit) {}
    public function where($condition) { $this->accountId = $condition[1]; }
    public function fetch()
    {
        $result = null;
        foreach (core_Manager::$records as $rec) {
            if ($rec->accountId == $this->accountId && $rec->uid > ($result->uid ?? 0)) $result = $rec;
        }
        return $result;
    }
}
class core_Permanent
{
    const FOREVER_VALUE = 0;
    public static $records = array();
    public static $clock = 1000;
    public static function get($key)
    {
        $rec = self::$records[$key] ?? null;
        return $rec && (!$rec[1] || $rec[1] > self::$clock) ? $rec[0] : null;
    }
    public static function set($key, $value, $minutes)
    {
        self::$records[$key] = array($value, $minutes ? self::$clock + $minutes * 60 : 0);
    }
}
class core_Cache { public static function get($type, $key) { return false; } }
class core_Locks
{
    public static function obtain($key, $ttl, $tries, $sleep) { return true; }
    public static function release($key) {}
}
class dt { public static function now() { return '2026-01-01 12:00:00'; } }
class core_Packs
{
    public static function getConfig($package) { return (object) array('EMAIL_MAX_TEXT_LEN' => 10000); }
}
class email_Accounts
{
    public static $logs = array();
    public static function logInfo($message, $id = null, $days = null) { self::$logs[] = array($id, $message); }
    public static function logWarning($message, $id = null, $days = null) { self::logInfo($message, $id, $days); }
}
class email_Mime
{
    public $textPart = 'Synthetic message';
    private $headers;
    public function parseAll($raw) { $this->headers = $raw; }
    public function getHeadersStr() { return $this->headers; }
    public static function parseHeaders($headers)
    {
        $result = array();
        foreach (explode("\n", $headers) as $line) {
            if (strpos($line, ':') === false) continue;
            list($name, $value) = explode(':', $line, 2);
            $result[strtolower($name)] = trim($value);
        }
        return $result;
    }
    public static function getHeadersFromArr($headers, $name, $part = null, $decode = true)
    {
        return $headers[strtolower($name)] ?? '';
    }
}
class cls
{
    public static $connections = array();
    public static function get($class, $params = array())
    {
        if ($class === 'email_Imap') return self::$connections[$params['accRec']->id];
        if ($class === 'email_Mime') return new email_Mime();
        throw new RuntimeException('Unexpected class: ' . $class);
    }
}
class ImapFixture
{
    public $accRec, $messages, $bodyReads = 0, $uidReads = 0;
    public function __construct($id, $messages, $protocol = 'imap')
    {
        $this->accRec = (object) array('id' => $id, 'email' => 'account' . $id . '@example.test',
            'protocol' => $protocol, 'deleteAfterPeriod' => null, 'imapFlag' => '');
        $this->messages = $messages;
        cls::$connections[$id] = $this;
    }
    public function connect() { return true; }
    public function close() {}
    public function getStatistic($name) { return count($this->messages); }
    public function getMsgNo($uid)
    {
        foreach ($this->messages as $number => $message) if ($message[0] == $uid) return $number;
        return 0;
    }
    public function getUid($number) { $this->uidReads++; return $this->messages[$number][0]; }
    public function getHeaders($number) { return $this->messages[$number][1]; }
    public function getEml($number) { $this->bodyReads++; return $this->messages[$number][2] ?? $this->getHeaders($number); }
}

$source = $argv[1] ?? dirname(__DIR__);
require $source . '/Fingerprints.class.php';
require $source . '/Incomings.class.php';

/**
 * Проверки на IMAP дубликати с реалните методи за избор, сваляне и отпечатъци.
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class DownloadFixture extends email_Incomings
{
    public $incomingCount = 0;
    protected function doProcessEmail($mime, $accId, $uid, &$sId = null)
    {
        $this->incomingCount++;
        return 'incoming';
    }
}

$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function headers($id)
{
    return "From: sender@example.test\nTo: recipient@example.test\nSubject: Synthetic {$id}\nMessage-ID: <{$id}@example.test>\n";
}
function runAccount($mvc, $connection)
{
    email_Accounts::$logs = array();
    ob_start();
    try {
        $mvc->fetchAccount($connection->accRec, time() + 30, 30);
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}
function duplicates()
{
    return array_values(array_filter(email_Accounts::$logs, function ($log) {
        return strpos($log[1], 'Дублиран имейл:') !== false;
    }));
}
function checkUnchanged($mvc, $connection)
{
    $output = runAccount($mvc, $connection);
    check(strpos($output, 'Total=') !== false, 'Unchanged mailbox still reports total');
    check(strpos($output, 'First=') === false && strpos($output, 'Duplicated=') === false, 'Unchanged mailbox does not recount duplicates');
    check(!duplicates(), 'Unchanged mailbox does not log duplicates again');
}

$mvc = new DownloadFixture();
$a = new ImapFixture(1, array(1 => array(100, headers('shared'))));
$b = new ImapFixture(2, array(1 => array(400, headers('shared'))));
check(strpos(runAccount($mvc, $a), 'Incoming=1') !== false, 'First account downloads new mail');
$output = runAccount($mvc, $b);
check(strpos($output, 'First=1; Duplicated=1') !== false, 'Second account reports a duplicate in the same run');
check($mvc->incomingCount === 1 && $b->bodyReads === 0, 'Duplicate creates no incoming mail and does not fetch the body');
$logs = duplicates();
check(count($logs) === 1 && $logs[0][0] === 2, 'Duplicate log belongs to the receiving account');
foreach (array('писмо №1, UID 400', 'отпечатък #1', 'акаунт #1', 'UID 100', 'статус incoming', '2026-01-01 12:00:00') as $part) {
    check(strpos($logs[0][1], $part) !== false, 'Duplicate log identifies source: ' . $part);
}
check(core_Permanent::get('IMAP_MAX_UID_2') === 400, 'Duplicate advances this account UID');
email_Accounts::$logs = array();
check($mvc->fetchEmail($b, 1) === 'duplicated' && !duplicates(), 'Explicit recheck of the same UID does not repeat the log');
unset(core_Permanent::$records['IMAP_MAX_UID_1']);
check($mvc->fetchEmail($a, 1) === 'duplicated' && !duplicates(), 'Own fingerprint suppresses an old UID log after cache expiry');
checkUnchanged($mvc, $b);
core_Permanent::$clock += 60;
checkUnchanged($mvc, $b);
$b->messages[2] = array(401, headers('shared'));
check(strpos(runAccount($mvc, $b), 'First=2; Duplicated=1') !== false, 'A new UID with identical headers counts once');
checkUnchanged($mvc, $b);

// Старият UID е изтрит от сървъра; по-ранно ново писмо не бива да се скрие зад последен дубликат.
email_Fingerprints::saveMaxUID(3, 10);
$c = new ImapFixture(3, array(1 => array(5, headers('shared')), 2 => array(8, headers('shared')),
    3 => array(11, headers('new')), 4 => array(12, headers('shared'))));
$output = runAccount($mvc, $c);
check(strpos($output, 'First=3; Incoming=1; Duplicated=1') !== false, 'Missing anchor UID finds all newer messages, including before a duplicate');
check($mvc->incomingCount === 2, 'Only the genuinely new message is imported');
checkUnchanged($mvc, $c);

// Проверява и второто разпознаване по хедърите от изтегленото MIME съдържание.
$d = new ImapFixture(4, array(1 => array(700, headers('transport'), headers('shared'))));
check(strpos(runAccount($mvc, $d), 'Duplicated=1') !== false, 'MIME header duplicate is counted');
check(count(duplicates()) === 1 && $d->bodyReads === 1 && $mvc->incomingCount === 2, 'MIME duplicate is logged without a second incoming mail');
check(email_Fingerprints::fetchByHeaders(headers('transport'))->status === 'incoming', 'MIME duplicate preserves the original fingerprint status');
checkUnchanged($mvc, $d);

$pop = new ImapFixture(5, array(1 => array(0, headers('shared'))), 'pop3');
checkUnchanged($mvc, $pop);
check($pop->uidReads === 0, 'POP3 fingerprint selection remains unchanged');
$empty = new ImapFixture(6, array());
checkUnchanged($mvc, $empty);
check($empty->uidReads === 0, 'Empty mailbox does not request a UID');

echo "Passed {$checks} download duplicate checks.\n";
