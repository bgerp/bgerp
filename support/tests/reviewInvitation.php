<?php
// php support/tests/reviewInvitation.php. Isolated persistence, identity and form boundaries.
error_reporting(E_ALL);
set_error_handler(function ($n, $message, $file, $line) { throw new ErrorException($message, 0, $n, $file, $line); });
class core_BaseClass { public function __call($name, $args) { return $this->{$name . '_'}(...$args); } }
class cls { public static function get($name) { return new $name(); } }
class core_Users {
    public static $id = 0, $power = false;
    public static function getCurrent() { return self::$id; }
    public static function isPowerUser() { return self::$power; }
    public static function isSystemUser() { return self::$id < 0; }
}
class Mode {
    public static $data = array();
    public static function get($key) { return self::$data[$key] ?? null; }
    public static function setPermanent($key, $value) { self::$data[$key] = $value; }
}
class Request { public static $data = array(); public static function get($key, $type) { return self::$data[$key] ?? null; } }
class core_Permanent {
    public static $data = array();
    public static function get($key) { return self::$data[$key] ?? null; }
    public static function set($key, $value, $ttl) { self::$data[$key] = $value; }
}
class core_Locks {
    public static $held = array();
    public static function obtain($key, ...$options) { if (isset(self::$held[$key])) return false; return self::$held[$key] = true; }
    public static function release($key) { unset(self::$held[$key]); }
}
class support_Systems {
    public static $rec, $modes = array('off' => '', 'always' => '');
    public static function fetch($id) { return $id === 1 ? self::$rec : null; }
    public function getReviewModes() { return self::$modes; }
}
class cal_Tasks {
    public static $saved = array(), $logs = array(), $fail = false;
    public static function save($rec) { if (self::$fail) throw new RuntimeException('Save failed'); $rec->id = count(self::$saved) + 1; self::$saved[] = clone $rec; }
    public static function logInfo($message, $id) { self::$logs[] = array($message, $id); }
}
class core_Lg { public static function getCurrent() { return 'en'; } }
class str { public static function limitLen($text, $length) { return substr($text, 0, $length); } }
class vislog_History { public static function add($message) {} }
class Redirect { public $url; public function __construct($url) { $this->url = $url; } }
class ReviewTestForm {
    public $rec;
    public function __construct() { $this->rec = (object) array('description' => 'Great "source" — [#TEXT#] <script>literal</script>'); }
    public function FLD(...$args) {}
    public function setDefault($key, $value) { $this->rec->{$key} = $value; }
}
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Denied'); }
function expect404($condition, ...$args) { expect($condition); }
function reportException($e) {}
function check($condition, $message) { static $count = 0; if (!$condition) throw new RuntimeException($message); echo 'OK ' . ++$count . ': ' . $message . "\n"; }
function denied($callback) { try { $callback(); } catch (RuntimeException $e) { return true; } return false; }
require dirname(__DIR__) . '/ReviewInvitation.class.php';
class ReviewTest extends support_ReviewInvitation {
    public $calls = 0, $result = array('state' => 'pending');
    public function advanceTest($token) { return $this->advance($token)[0]; }
    public function evaluate_($system, $context) { $this->calls++; if ($this->result instanceof Throwable) throw $this->result; return $this->result; }
}
support_Systems::$rec = (object) array('reviewMode' => 'always', 'reviewUrl' => 'https://example.org/review', 'state' => 'active');
$form = new ReviewTestForm();
support_ReviewInvitation::prepareForm($form, 1);
$token = $form->rec->reviewToken;
$receipt = support_ReviewInvitation::submit($form->rec, 1);
$receipt2 = support_ReviewInvitation::submit($form->rec, 1);
check(count(cal_Tasks::$saved) === 1 && $receipt->url === $receipt2->url, 'Replayed POST saves once and returns the same receipt');
check(support_ReviewInvitation::context($token)['text'] === $form->rec->description, 'Original source text is preserved');
$test = new ReviewTest();
check($test->advanceTest($token)['state'] === 'allow' && $test->calls === 0, 'Always mode works without an evaluator');
$owner = Mode::$data['supportReviewOwner'];
Mode::$data['supportReviewOwner'] = 'another-session';
check(denied(function () use ($token) { support_ReviewInvitation::context($token); }), 'A different session cannot read the receipt');
Mode::$data['supportReviewOwner'] = $owner;
core_Users::$id = 7;
check(denied(function () use ($token) { support_ReviewInvitation::context($token); }), 'A different logged-in identity cannot reuse the receipt');
check(support_ReviewInvitation::isExternalVisitor(), 'External collaborator is eligible');
core_Users::$power = true;
check(!support_ReviewInvitation::isExternalVisitor(), 'Power users and mixed collaborator/power roles are excluded');
core_Users::$id = -1; core_Users::$power = false;
check(!support_ReviewInvitation::isExternalVisitor(), 'System users are excluded');
core_Users::$id = 0;
check(denied(function () { support_ReviewInvitation::context('../invalid'); }), 'Malformed receipt is rejected');
support_Systems::$rec->reviewUrl = 'https://example.org/changed';
check($test->advanceTest($token)['state'] === 'deny', 'Changed URL revokes a previously allowed invitation');
support_Systems::$rec->reviewUrl = 'https://example.org/review';
support_Systems::$rec->reviewMode = 'veryPositive';
support_Systems::$modes['veryPositive'] = '';
$base = support_ReviewInvitation::context($token);
$base['mode'] = 'veryPositive'; $base['state'] = 'new';
core_Permanent::$data['supportReview:' . $token] = $base;
$test->result = array('state' => 'pending', 'providerData' => array('sessionId' => 1));
check($test->advanceTest($token)['state'] === 'pending', 'Async evaluation stores its running session');
$test->result = array('state' => 'allow');
check($test->advanceTest($token)['state'] === 'allow', 'Completed qualifying evaluation displays an invitation');
unset(support_Systems::$modes['veryPositive']);
check($test->advanceTest($token)['state'] === 'deny', 'Removing optional module revokes an already allowed conditional invitation');
support_Systems::$modes['veryPositive'] = '';
foreach (array('off', 'timeout', 'error', 'malformed', 'power', 'closed') as $case) {
    $ctx = $base; $calls = $test->calls;
    support_Systems::$rec->reviewMode = 'veryPositive'; support_Systems::$rec->state = 'active';
    $test->result = array('state' => 'bogus');
    if ($case === 'off') $ctx['mode'] = support_Systems::$rec->reviewMode = 'off';
    if ($case === 'timeout') $ctx['deadline'] = time() - 1;
    if ($case === 'error') $test->result = new RuntimeException('Provider down');
    if ($case === 'power') core_Users::$power = true;
    if ($case === 'closed') support_Systems::$rec->state = 'closed';
    core_Permanent::$data['supportReview:' . $token] = $ctx;
    check($test->advanceTest($token)['state'] === 'deny', $case . ' fails closed');
    if (in_array($case, array('off', 'timeout', 'power', 'closed'))) check($test->calls === $calls, $case . ' does not call AI');
    core_Users::$power = false;
}
$base['expires'] = time() - 1;
core_Permanent::$data['supportReview:' . $token] = $base;
check(denied(function () use ($token) { support_ReviewInvitation::context($token); }), 'Expired receipt is rejected');
$form2 = new ReviewTestForm(); support_ReviewInvitation::prepareForm($form2, 1);
cal_Tasks::$fail = true;
check(denied(function () use ($form2) { support_ReviewInvitation::submit($form2->rec, 1); }), 'Failed save never evaluates feedback');
check(!core_Locks::$held && count(cal_Tasks::$saved) === 1, 'Locks released and no duplicate after failed save');
foreach (array('javascript:alert(1)', '//evil.example', 'https://user:pass@example.org', "https://example.org/\r\nLocation:x", '') as $url) check(!support_ReviewInvitation::isValidUrl($url), 'Unsafe or incomplete URL rejected');
check(support_ReviewInvitation::isValidUrl('https://example.org/review?q=one%20two'), 'Complete HTTP(S) destination accepted');
echo "PASS: invitation lifecycle\n";
