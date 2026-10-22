<?php

/**
 * Изолирани проверки на входните стойности: php type/tests/inputArrays.php
 *
 * @category  bgerp
 * @package   type
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });
function countR($value) { return is_countable($value) ? count($value) : 0; }
class dt
{
    public static function verbal2mysql($value, $withTime) { return $value === '20.10.2026' ? '2026-10-20' : false; }
    public static function mysql2verbal(...$args) { return '20-10-2026'; }
}
class core_HackDetector { public static function check($value, $tolerance) {} }
class core_Packs { public static function isInstalled($pack) { return false; } }
class ht { public static function createSelect($name, $options, $value, $attr) { return array('name' => $name, 'value' => $value); } }
class arr { public static function combine(...$arrays) { $res = array(); foreach ($arrays as $a) $res += $a; return $res; } }
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/Type.class.php';
foreach (array('Varchar', 'Identifier', 'Date', 'Keylist', 'Users', 'Int', 'Key2') as $name) require dirname(__DIR__) . '/' . $name . '.class.php';
class InputUsers extends type_Users
{
    public $prepared = 0;
    public function prepareOptions($value = null)
    {
        $this->prepared++;
        $this->options = array('1_7' => (object) array('keylist' => '|7|'));
    }
}
class InputKey extends type_Key2
{
    public $lookups = 0;
    public function getOptions($limit = null, $search = '', $ids = null, $includeHiddens = false)
    {
        $this->lookups++;
        return (string) $ids === '7' ? array(7 => 'Synthetic group') : array();
    }
}
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
foreach (array('type_Varchar', 'type_Date', 'InputUsers', 'InputKey') as $class) {
    foreach (array(array(), array('1'), array('a' => array('1'))) as $value) {
        $type = new $class();
        $result = $type instanceof type_Date ? $type->fromVerbal($value) : $type->fromVerbal_($value);
        check($result === false && $type->error !== '', $class . ': invalid arrays become field errors');
        if ($type instanceof InputUsers) check($type->prepared === 0, 'Invalid users input does not load user options');
        if ($type instanceof InputKey) check($type->lookups === 0, 'Invalid key input does not query options');
    }
}
$date = new type_Date();
check($date->fromVerbal(array('d' => '20.10.2026')) === '2026-10-20', 'The supported structured date input remains valid');
check($date->fromVerbal(array('d' => array('20.10.2026'))) === false, 'A nested array is refused inside the date structure');
$date = new type_Date();
check($date->fromVerbal('20.10.2026') === '2026-10-20' && $date->fromVerbal('') === null, 'Scalar and empty dates are unchanged');
$users = new InputUsers();
check($users->fromVerbal_('1_7') === '|7|' && $users->fromVerbal_('7') === '|7|' && $users->fromVerbal_('|7|8|') === '|7|8|',
    'User options, numeric users and keylists still work');
$key = new InputKey();
check($key->fromVerbal_('7') === 7 && $key->fromVerbal_('Synthetic group (7)') === 7 && $key->fromVerbal_('') === null,
    'Key ids, titles and empty values still work');
$key = new InputKey();
$key->params['maxSuggestions'] = 10;
$attr = array();
check($key->renderInput_('groupId', array('1'), $attr)['value'] === '', 'The form with a refused array renders an empty key field');
$attr = array();
check($key->renderInput_('groupId', '7', $attr)['value'] === '7', 'A valid key is still rendered as selected');
$text = new type_Varchar();
check($text->fromVerbal_('  abc  ') === 'abc' && $text->fromVerbal_(null) === '', 'Text trimming and empty input remain unchanged');
foreach (array(array(), array('noTrim' => true), array('nullIfEmpty' => true)) as $params) {
    $identifier = new type_Identifier();
    $identifier->params = $params;
    foreach (array(null, '', '  ') as $value) {
        check($identifier->fromVerbal($value) === null && empty($identifier->error), 'Empty identifiers do not raise PHP warnings');
    }
    check($identifier->fromVerbal('  valid_name2  ') === 'valid_name2', 'Identifier trimming is preserved');
    check(empty($identifier->isValid(null)['error']), 'Direct validation accepts an empty identifier');
    foreach (array(array('name'), array('key' => 'name')) as $value) {
        check($identifier->fromVerbal($value) === false && $identifier->error === 'Очаква се текст', 'Identifier arrays become field errors');
    }
}
echo "PASS: {$checks} input checks\n";
