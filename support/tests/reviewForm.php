<?php
// php support/tests/reviewForm.php. No private packages, database or installation records.
error_reporting(E_ALL);
set_error_handler(function ($n, $message, $file, $line) { throw new ErrorException($message, 0, $n, $file, $line); });
spl_autoload_register(function ($name) { throw new RuntimeException('Unexpected dependency: ' . $name); });
class core_Master {}
class Request {
    public static $data = array();
    public static function get($name) { return self::$data[$name] ?? null; }
}
function tr($text) { return $text; }
function haveRole($roles) { return ReviewFormFixture::$canEdit; }
class ReviewFormFixture {
    public static $canEdit = true;
    public $rec, $fields, $cmd = 'refresh';
    public function __construct($input, $rec = array()) {
        $this->rec = (object) $rec;
        $this->fields = array('reviewUrl' => (object) array('input' => $input));
    }
    public function FNC($name, $type, $params) { $this->fields[$name] = (object) array('kind' => 'FNC', 'input' => 'hidden'); }
    public function setDefault($name, $value) { if (!isset($this->rec->{$name})) $this->rec->{$name} = $value; }
}
class ReviewStoredFixture {
    public function fetch($id) { return (object) array('reviewUrl' => 'https://example.org/stored'); }
}
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "OK: {$message}\n"; }
require dirname(__DIR__) . '/Systems.class.php';
$mvc = new ReviewStoredFixture();
$fields = array('reviewUrl');
check(array_keys((new support_Systems())->getReviewModes_()) === array('off', 'always'), 'Core modes exist without an AI package or evaluator');
foreach (array(array(), array('id' => 1)) as $record) {
    foreach (array('https://example.org/unsaved?x=1&y=2', '', 'not a valid url') as $value) {
        Request::$data = array('reviewUrl' => $value);
        $hidden = new ReviewFormFixture('none', $record);
        support_Systems::preserveReviewFormFields($mvc, $hidden, $fields);
        check($hidden->rec->reviewUrlFormValue === $value && !isset($hidden->rec->reviewUrl), 'Disabling preserves the draft separately from stored settings');
        check($hidden->fields['reviewUrlFormValue']->kind === 'FNC', 'Draft carrier is not a persistent field');
        Request::$data = array('reviewUrlFormValue' => $hidden->rec->reviewUrlFormValue);
        $visible = new ReviewFormFixture('input', $record);
        support_Systems::preserveReviewFormFields($mvc, $visible, $fields);
        check($visible->rec->reviewUrl === $value, 'Re-enabling restores the draft, including an explicitly cleared value');
    }
}
Request::$data = array();
$existing = new ReviewFormFixture('input', array('id' => 1));
support_Systems::preserveReviewFormFields($mvc, $existing, $fields);
check($existing->rec->reviewUrl === 'https://example.org/stored', 'Existing settings remain the fallback when no draft was submitted');
$new = new ReviewFormFixture('none');
support_Systems::preserveReviewFormFields($mvc, $new, $fields);
check(!isset($new->fields['reviewUrlFormValue']), 'An unset new value does not introduce an empty default');
Request::$data = array('reviewUrl' => array('invalid'));
$invalid = new ReviewFormFixture('none');
support_Systems::preserveReviewFormFields($mvc, $invalid, $fields);
check(!isset($invalid->fields['reviewUrlFormValue']), 'Non-scalar input is not propagated');
ReviewFormFixture::$canEdit = false;
Request::$data = array('reviewUrl' => 'https://example.org/unauthorized');
$restricted = new ReviewFormFixture('none', array('id' => 1));
support_Systems::preserveReviewFormFields($mvc, $restricted, $fields);
check(!isset($restricted->fields['reviewUrlFormValue']), 'Users without settings rights do not get draft carriers');
echo "PASS: review form drafts without private packages\n";
