<?php
/**
 * Изолирани проверки на избора и маркъпа на OrgChart.
 *
 * @category bgerp
 * @package orgchart
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
class core_BaseClass {}
class core_ProtoSetup {
    public static $selected = 'legacy';
    /** Представя избора без достъп до база. */
    public static function get($key) { return self::$selected; }
}
/** Представя дефинирането на default без framework bootstrap. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class ET {
    public $html = '';
    public $assets = array();
    public $script = '';
    /** Събира маркъпа на адаптера. */
    public function append($html) { $this->html .= $html; }
    /** Записва заявените ресурси. */
    public function push($path, $type) { $this->assets[$type][] = $path; }
}
class jquery_Jquery {
    /** Събира инициализацията без изпълнение на JavaScript. */
    public static function run($tpl, $script, $once) { $tpl->script .= $script; }
}
$checks = 0;
/** Прекъсва при различен резултат от публичния контракт. */
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); $GLOBALS['checks']++; }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Adapter.class.php';
check(ORGCHART_VERSION === 'legacy', 'Old copy remains default');
check((new orgchart_Setup())->version === '0.1', 'Setup version retained');
$tree = array(array('id' => 1, 'parent_id' => 'NULL', 'title' => 'Централа'), array('id' => 2, 'parent_id' => 1, 'title' => 'Склад'), array('id' => 3, 'parent_id' => 2, 'title' => 'Доставки'));
$expected = "\n<ul>\n<li>Централа\n<ul>\n<li>Склад\n<ul>\n<li>Доставки</li>\n</ul>\n</li>\n</ul>\n</li>\n</ul>\n";
check(orgchart_Adapter::transformArrayToNestedLists($tree, 'NULL') === $expected, 'Nested markup retained');
check(orgchart_Adapter::transformArrayToNestedLists(array(), 'NULL') === '', 'Empty tree');
$ids = array();
foreach (array('legacy', '1.0.0', 'legacy') as $version) {
    core_ProtoSetup::$selected = $version;
    $tpl = orgchart_Adapter::render_($tree);
    preg_match("/id='(orgChart\d+)Source'/", $tpl->html, $match);
    $id = $match[1] ?? '';
    check($id !== '' && !in_array($id, $ids, true), 'Unique chart/source pair');
    $ids[] = $id;
    check(strpos($tpl->script, "$('#{$id}Source > ul')") !== false, 'Scoped source selection');
    check(strpos($tpl->script, "container: $('#{$id}')") !== false, 'Matching target');
    check(strpos($tpl->html, $expected) !== false, 'Same data across versions');
    $dir = $version === 'legacy' ? 'lib' : $version;
    foreach (array('CSS' => 'css', 'JS' => 'js') as $type => $extension) {
        $path = "orgchart/{$dir}/jquery.orgchart.{$extension}";
        check($tpl->assets[$type] === array($path), 'Selected ' . $type);
        check(is_file(dirname(__DIR__, 2) . '/' . $path), 'Asset exists');
    }
}
echo "PASS {$checks} checks on PHP " . PHP_VERSION . "\n";
