<?php
/**
 * Regression for support reply recipients with deferred original-document previews.
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
// php support/tests/replyRecipient.php - synthetic records, no database or mail.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$root = dirname(__DIR__, 2);
class core_Mvc {}
class core_Manager extends core_Mvc {
    public static function getQuery() { return new FixtureQuery(Fixture::$salutations); }
}
class core_Master extends core_Manager {
    public static function getQuery() { return new FixtureQuery(Fixture::$outgoings); }
}
class core_Fieldset { public function FLD($name, $type, $params) {} }
class FixtureQuery {
    private $rows, $filters = array(), $offset = 0;
    public function __construct($rows) { $this->rows = $rows; }
    public function where($condition) { $this->filters[] = $condition; }
    public function orderBy($field, $direction) {}
    public function limit($count) {}
    public function fetch() {
        while (isset($this->rows[$this->offset])) {
            $row = $this->rows[$this->offset++];
            foreach ($this->filters as $condition) {
                if (is_array($condition)) {
                    if (!preg_match('/#(\w+) = /', $condition[0], $match)) throw new RuntimeException('Unexpected condition');
                    if (($row->{$match[1]} ?? null) != $condition[1]) continue 2;
                } elseif (preg_match("/#(\w+) = '([^']*)'/", $condition, $match)) {
                    if (($row->{$match[1]} ?? null) != $match[2]) continue 2;
                } elseif ($condition === "#state != 'rejected'") {
                    if (($row->state ?? null) === 'rejected') continue 2;
                } elseif ($condition === '#originId IS NOT NULL') {
                    if (!isset($row->originId)) continue 2;
                } else { throw new RuntimeException('Unexpected condition: ' . $condition); }
            }
            return $row;
        }
        return false;
    }
}
class Fixture {
    public static $origin, $previous, $driver, $outgoings, $salutations;
}
class FixtureDocument {
    public $className = 'cal_Tasks', $that = 10, $getContragentDataFromLastDoc, $getContragentDataCheckFields, $isSignal = true;
    public function getContragentData($unused = null) {
        $data = new stdClass();
        Fixture::$driver->prepareContragentData((object) array('createdBy' => -1, 'email' => 'reporter@example.test', 'name' => 'Подател'), $data);
        if (!$this->isSignal) unset($data->_getContragentDataFromLastDoc);
        return $data;
    }
}
class doc_Containers {
    public static function getDocument($id) { return $id === 100 ? Fixture::$origin : Fixture::$previous; }
}
class doc_Threads {
    public static function getContragentData($id) { return Fixture::$origin->getContragentData(); }
}
class doc_Folders {
    public static function getCover($id) { return null; }
    public static function fetchCoverClassName($id) { return 'support_Systems'; }
}
class crm_Companies { public static function removeOwnCompanyData($data) {} }
class core_Users { public static function getCurrent() { return 1; } }
class core_Lg { public static function getCurrent() { return 'bg'; } }
class core_Packs { public static function getConfig($name) { return (object) array('EMAIL_SALUTATION_EMAIL_TIME_LIMIT' => 1000); } }
class dt {
    public static function now() { return '2026-01-03'; }
    public static function secsBetween($a, $b) { return 1; }
}
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
require $root . '/support/TaskType.class.php';
require $root . '/email/Salutations.class.php';
require $root . '/email/Outgoings.class.php';
Fixture::$driver = new support_TaskType();
Fixture::$previous = new FixtureDocument();
Fixture::$outgoings = array((object) array(
    'folderId' => 1, 'threadId' => 999, 'originId' => 200, 'state' => 'closed',
    'recipient' => null, 'attn' => 'Друг получател', 'tel' => null, 'fax' => null,
    'country' => null, 'pcode' => null, 'place' => null, 'address' => null,
    'email' => 'other@example.test', 'emailCc' => null
));
Fixture::$salutations = array();
foreach (array(
    array(2, 'other@example.test', 'Здравейте, Друг получател!'),
    array(1, 'other@example.test', 'Здравейте, Колега!'),
    array(1, 'reporter@example.test', 'Здравейте, Подател!')
) as $data) {
    Fixture::$salutations[] = (object) array('folderId' => 1, 'threadId' => 999, 'userId' => $data[0], 'createdBy' => $data[0], 'toEmail' => $data[1], 'salutation' => $data[2], 'state' => 'closed', 'createdOn' => '2026-01-02');
}
$method = new ReflectionMethod('email_Outgoings', 'prepareContragentData');
$method->setAccessible(true);
$mvc = new email_Outgoings();
$fields = new core_Fieldset();
$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
foreach (array(false, true) as $earlyPreview) {
    Fixture::$origin = new FixtureDocument();
    Fixture::$driver->Embedder = Fixture::$origin;
    if ($earlyPreview) Fixture::$driver->addFields($fields);
    $rec = (object) array('folderId' => 1, 'threadId' => 10, 'originId' => 100);
    $data = $method->invoke(null, $rec);
    email_Outgoings::setContragentDataToRec($data, $rec);
    check(($rec->email ?? null) === 'reporter@example.test', 'Reply must keep the reporter address before and after preview preparation');
    check(($rec->attn ?? null) === 'Подател', 'Reply must keep the reporter name');
    check($mvc->getHeader(array('name' => $data->person ?? null), $rec) === 'Здравейте, Подател!', 'Salutation must belong to the reporter');
    Fixture::$driver->addFields($fields);
    check(Fixture::$origin->getContragentDataFromLastDoc === null, 'Preview must not change the shared manager policy');
}
foreach (array((object) array('createdBy' => 1), new stdClass()) as $record) {
    $data = new stdClass();
    Fixture::$driver->prepareContragentData($record, $data);
    check(($data->_getContragentDataFromLastDoc ?? null) === false, 'Every signal must disable recipient reuse');
    check(empty($data->email) && empty($data->person), 'Missing contact fields remain empty');
}
Fixture::$origin = new FixtureDocument();
Fixture::$origin->isSignal = false;
$data = $method->invoke(null, (object) array('folderId' => 1, 'threadId' => 10, 'originId' => 100));
check(($data->email ?? null) === 'other@example.test', 'Documents without the signal policy retain the existing recipient reuse');
check($mvc->getHeader(array('name' => $data->person ?? null), (object) array('folderId' => 1, 'threadId' => 10, 'email' => $data->email ?? null)) === 'Здравейте, Колега!', 'Personal salutation still takes precedence over another user salutation');
echo "OK: {$checks} checks\n";
