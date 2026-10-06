<?php

// Real redirect, Mode and translation; dictionary persistence is replaced in memory.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/redirectFixture.php';
class core_Manager extends core_Master {}
class core_ProtoSetup { public static $dbInit = false; }
function haveRole($role) { return $role === 'translate'; }
require dirname(__DIR__) . '/Lg.class.php';
require dirname(__DIR__) . '/Type.class.php';
require dirname(__DIR__, 2) . '/type/Varchar.class.php';
class RedirectStatusLg extends core_Lg {
    public $writes = 0;
    protected function prepareDictForLg($lg = null) { $this->dict[$lg] = $this->dict[$lg] ?? array(); }
    public static function prepareKey($key) { return $key; }
    public static function getCurrent() { return Mode::get('lg'); }
    public function save($rec, $fields = null, $mode = null) { $this->writes++; }
}
$lg = new RedirectStatusLg();
$translations = 0;
$GLOBALS['redirectTestTranslator'] = function ($value) use ($lg, &$translations) { $translations++; return $lg->translate($value); };
$checks = 0;
function statusCheck($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
foreach (array(
    'Създаден <b>документ</b>' => array('Създаден <b>документ</b>', 'Създаден <b>документ</b>'),
    '|Създаден||Created|* <b>document</b>' => array('Създаден <b>document</b>', 'Created <b>document</b>'),
    '|*<b>Raw & literal</b>' => array('<b>Raw & literal</b>', '<b>Raw & literal</b>'),
) as $message => $expected) {
    Mode::set('lg', 'en');
    $before = $translations;
    try { redirect('/destination', false, $message); }
    catch (core_exception_Redirect $redirect) {
        statusCheck($redirect->statusMessage === $message, 'Redirect keeps the original status, markup and translation markers');
        statusCheck($translations === $before && !$lg->writes, 'Redirect neither translates nor writes to the dictionary');
        foreach (array('bg', 'en') as $i => $language) {
            Mode::set('lg', $language);
            // status_Messages::getStatuses() applies this translation at display time.
            statusCheck(tr('|*' . $redirect->statusMessage) === $expected[$i], 'Status renders HTML in the display language: ' . $language);
        }
    }
}

Mode::push('outer', 'kept');
core_Users::sudo(17);
$stack = Mode::$stack;
try { Mode::pop('outer'); statusCheck(false, 'Mismatched pop must fail'); }
catch (RuntimeException $e) { statusCheck(Mode::$stack === $stack && core_Users::getCurrent() === 17, 'Rejected pop does not discard the saved identity'); }
core_Users::exitSudo();
Mode::pop('outer');
statusCheck(core_Users::getCurrent() === 7 && Mode::$stack === array(), 'The intact stack can be unwound normally');
echo "OK: {$checks} redirect status and Mode checks.\n";
