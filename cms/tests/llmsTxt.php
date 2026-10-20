<?php

/**
 * Isolated regression checks for public CMS text and llms.txt generation.
 * Run: php cms/tests/llmsTxt.php
 *
 * @category  bgerp
 * @package   cms
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
error_reporting(E_ALL);
set_error_handler(function ($n, $m, $f, $l) { throw new ErrorException($m, 0, $n, $f, $l); });
function countR($v) { return is_countable($v) ? count($v) : 0; }
function haveRole($r) { return $r === 'debug'; }
function expect($v) { if (!$v) throw new RuntimeException('expect'); }
function tr($s) { return $s; }
function toUrl($v, $type = null) { return is_array($v) ? 'https://' . (Mode::$values['BGERP_CURRENT_DOMAIN'] ?? 'outer.example') . '/' . implode('/', array_filter($v, 'is_scalar')) : $v; }
class ReviewQuery {
    private $rows; private $offset = 0;
    public function __construct($rows) { $this->rows = $rows; }
    public function where($condition) {
        $sql = is_array($condition) ? $condition[0] : $condition;
        $this->rows = array_values(array_filter($this->rows, function ($r) use ($sql, $condition) {
            foreach (array('state','llmsTxt') as $f) if (preg_match('/#' . $f . " = '([^']+)'/", $sql, $m) && ($r->$f ?? null) !== $m[1]) return false;
            if (strpos($sql, '#domain = ') !== false && ($r->domain ?? null) !== $condition[1]) return false;
            if (strpos($sql, '#domainId = ') !== false && ($r->domainId ?? null) != ($condition[strpos($sql, '[#2#]') !== false ? 2 : 1] ?? null)) return false;
            if (strpos($sql, '#source = ') !== false && ($r->source ?? null) != $condition[1]) return false;
            if (strpos($sql, '#menuId = ') !== false && ($r->menuId ?? null) != $condition[1] && strpos($r->sharedMenus ?? '', '|' . $condition[1] . '|') === false) return false;
            if (preg_match('/#groupId = (\d+|\[#1#\])/', $sql, $m)) {
                $groupId = $m[1] === '[#1#]' ? $condition[1] : $m[1];
                if (($r->groupId ?? null) != $groupId && strpos($r->sharedInGroups ?? '', '|' . $groupId . '|') === false) return false;
            }
            if (strpos($sql, "#saleState != 'closed'") !== false && ($r->saleState ?? null) === 'closed') return false;
            return true;
        }));
    }
    public function orderBy($f, $d = null) {}
    public function XPR($a, $b, $c) {}
    public function fetch() { return isset($this->rows[$this->offset]) ? clone $this->rows[$this->offset++] : false; }
    public function likeKeylist($f, $id) { $this->rows = array_values(array_filter($this->rows, function ($r) use ($f, $id) { return strpos($r->$f ?? '', '|' . $id . '|') !== false; })); }
}
class core_BaseClass { public function invoke($event) { return -1; } }
class core_Master extends core_BaseClass {
    public static $tables = array();
    public static function getQuery() { return new ReviewQuery(self::$tables[get_called_class()] ?? array()); }
    public static function fetch($id) { foreach (self::$tables[get_called_class()] ?? array() as $r) if (($r->id ?? null) == $id) return clone $r; return false; }
    public static function fetchField($id, $f) { $r = static::fetch($id); return $r->$f ?? null; }
    public static function fetchRec($r) { return is_object($r) ? $r : static::fetch($r); }
}
class cms_Domains extends core_Master {
    public static $settings = array();
    public static $requestedDomain;
    public static function getReal($d) { return $d; }
    public static function getSettings($id = null) { self::$requestedDomain = $id; return (object) (self::$settings[$id] ?? array()); }
}
class Mode {
    public static $values = array('BGERP_CURRENT_DOMAIN' => 'outer.example'); public static $stack = array();
    public static function push($k, $v) { self::$stack[] = array($k, self::$values[$k] ?? null); self::$values[$k] = $v; }
    public static function pop($k) { list($key, $value) = array_pop(self::$stack); if ($key !== $k) throw new RuntimeException('mode stack mismatch'); self::$values[$k] = $value; }
}
class core_Lg { public static function getCurrent() { return Mode::$values['lg'] ?? 'outer'; } public static function push($v) { Mode::push('lg', $v); } public static function pop() { Mode::pop('lg'); } }
class crm_Companies { public static function fetchOwnCompany() { return (object) array('company' => 'Synthetic Company'); } }
class type_Emails { public static function toArray($v) { return $v ? explode(',', $v) : array(); } }
class core_Classes { public static function getId($c) { return $c; } }
class cls {
    public static function getClassName($c, $silent = false) { return $c; }
    public static function existsMethod($c, $m) { return method_exists($c, $m); }
    public static function get($c) { return new $c(); }
}
class ReviewSource {
    public static $throw = false; public static $languages = array();
    public function getLlmsEntries($id) { self::$languages[$id] = core_Lg::getCurrent(); if (self::$throw) throw new RuntimeException('synthetic source error'); return array((object) array('title' => 'Page ' . $id, 'loc' => 'https://example.test/page/' . $id)); }
}
class core_App { public static function setTimeLimit($s) {} }
class core_Webroot {
    public static $files = array(); public static function register($t, $h, $f, $id) { self::$files[$f] = $t; }
    public static function isExists($f, $id) { return isset(self::$files[$f]); }
    public static function getContents($f, $id) { return self::$files[$f]; }
    public static function remove($f, $id) { unset(self::$files[$f]); }
}
class core_Permanent { const FOREVER_VALUE = 0; public static $values = array(); public static function set($k, $v, $t) { self::$values[$k] = $v; } public static function get($k) { return self::$values[$k] ?? null; } public static function remove($k) { unset(self::$values[$k]); } }
require $root . '/core/ET.class.php';
class_alias('core_ET', 'ET');
class type_Richtext { public static $template = false; public function toVerbal($s) { return self::$template ? new ET($s) : $s; } }
class blogm_Categories { public static function getCategoriesByDomain($d, $m, $c, $a) { return array(5 => 'Category 5', 6 => 'Category 6'); } }
require $root . '/cms/Content.class.php';
require $root . '/cms/Articles.class.php';
require $root . '/eshop/Groups.class.php';
require $root . '/eshop/Products.class.php';
require $root . '/blogm/Articles.class.php';
require $root . '/jsonld/Helper.class.php';
$passes = $fails = 0;
function check($ok, $name) { global $passes, $fails; if ($ok) $passes++; else $fails++; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; }
function domain($id, $lang, $state = 'active', $enabled = 'yes') { return (object) array('id'=>$id,'domain'=>'example.test','lang'=>$lang,'state'=>$state,'llmsTxt'=>$enabled); }
function menu($id, $domainId, $source = 'ReviewSource') { return (object) array('id'=>$id,'menu'=>'Menu ' . $id,'domainId'=>$domainId,'source'=>$source,'state'=>'active'); }
$d = domain(10, 'bg');
core_Master::$tables['cms_Domains'] = array($d, domain(11,'en'));
core_Master::$tables['cms_Content'] = array(menu(20,10), menu(21,11));
$text = cms_Content::getLlmsTxt($d);
check(strpos($text, '## Menu 20 (bg)') !== false && strpos($text, '## Menu 21 (en)') !== false, 'Two enabled languages share one host file');
check(ReviewSource::$languages === array(20=>'bg',21=>'en'), 'Every source runs in its own language');
check(core_Lg::getCurrent() === 'outer' && Mode::$values['BGERP_CURRENT_DOMAIN'] === 'outer.example', 'Language and host restored after success');
ReviewSource::$throw = true;
try { cms_Content::getLlmsTxt($d); } catch (RuntimeException $e) { check($e->getMessage() === 'synthetic source error', 'Source failure propagates'); }
ReviewSource::$throw = false;
check(core_Lg::getCurrent() === 'outer' && Mode::$values['BGERP_CURRENT_DOMAIN'] === 'outer.example', 'Language and host restored after exception');
core_Master::$tables['cms_Domains'][1]->state = 'closed';
$text = cms_Content::getLlmsTxt($d);
check(strpos($text, 'Page 21') === false, 'Closed language/domain is excluded');
core_Master::$tables['cms_Domains'][1]->llmsTxt = 'no';
check(strpos(cms_Content::getLlmsTxt($d), 'Page 21') === false, 'Disabled language is excluded');
cms_Content::registerLlmsTxt($d);
check(isset(core_Webroot::$files['llms.txt']), 'Enabled host registers a file');
core_Master::$tables['cms_Domains'][0]->state = 'closed';
check(cms_Content::getLlmsTxt($d) === null, 'All closed languages return null');
core_Master::$tables['cms_Domains'][0]->state = 'active';
core_Master::$tables['cms_Domains'][0]->llmsTxt = 'no';
check(cms_Content::getLlmsTxt($d) === null, 'All disabled returns null');
cms_Content::registerLlmsTxt($d);
check(!isset(core_Webroot::$files['llms.txt']), 'Disable removes generated file');
core_Webroot::$files['llms.txt'] = 'Manual content';
core_Permanent::$values['cms_LlmsTxt|example.test'] = md5('Prior generated content');
cms_Content::registerLlmsTxt($d);
check(core_Webroot::$files['llms.txt'] === 'Manual content', 'Disable preserves manual replacement');
check(cms_Content::getCleanText('<b>A</b>&nbsp;&amp; B') === 'A & B', 'HTML and entities converted to text');
check(cms_Content::getCleanText(null) === null, 'Missing scalar safely handled');
check(cms_Content::getPlainText('<p>One</p><p>Two</p><script>hidden</script>') === 'One Two', 'Block separation and script removal');
check(cms_Content::getPlainText('[#TOP_PAGE#]<p>Public text</p>') === 'Public text', 'Unresolved placeholders are removed from string richtext');
check(cms_Content::getPlainText('&#91;#TOP_PAGE#]<p>Public text</p>') === 'Public text', 'HTML-escaped richtext placeholders are removed');
type_Richtext::$template = true;
check(cms_Content::getPlainText('[#TOP_PAGE#]<p>Public text</p>') === 'Public text', 'Unresolved placeholders are removed from template richtext');
type_Richtext::$template = false;
check(jsonld_Helper::getPlainText('<p>One</p>') === cms_Content::getPlainText('<p>One</p>'), 'JSON-LD delegation matches CMS');
core_Master::$tables['cms_Domains'] = array(domain(10,'fr'));
core_Master::$tables['cms_Content'] = array(menu(20,10,'cms_Articles'));
core_Master::$tables['cms_Articles'] = array(
    (object) array('id'=>30,'menuId'=>20,'state'=>'active','title'=>'Public','body'=>'Body','vid'=>'кирилица тест'),
    (object) array('id'=>31,'menuId'=>20,'state'=>'closed','title'=>'Closed','body'=>'Body'),
    (object) array('id'=>32,'menuId'=>20,'state'=>'active','title'=>'Empty','body'=>' ')
);
$entries = (new cms_Articles())->getLlmsEntries(20);
check(count($entries) === 1 && ($entries[0]->title ?? null) === 'Public', 'CMS excludes closed and empty articles');
check(($entries[0]->loc[2] ?? null) === 'кирилица тест', 'CMS A/a URL is decoded before final URL encoding');
core_Master::$tables['blogm_Articles'] = array(
    (object) array('id'=>35,'state'=>'active','categories'=>'|5|6|','vid'=>'кирилица тест'),
    (object) array('id'=>36,'state'=>'draft','categories'=>'|5|','vid'=>'draft')
);
$entries = (new blogm_Articles())->getLlmsEntries(20);
check(count($entries) === 2 && ($entries[1]->loc[2] ?? null) === 'кирилица тест', 'Blog excludes drafts, deduplicates categories and decodes the URL once');
check(($entries[1]->richtext ?? null) === null && ($entries[1]->date ?? null) === null, 'Blog accepts absent optional text and date fields');
core_Master::$tables['eshop_Groups'] = array((object) array('id'=>40,'menuId'=>20,'state'=>'active','name'=>'Group','vid'=>'group','showProductsWithoutPrices'=>'no'));
core_Master::$tables['eshop_Products'] = array((object) array('id'=>50,'groupId'=>40,'state'=>'active','saleState'=>'single','name'=>'Hidden product','vid'=>'hidden','detailActions'=>'onlySell','haveProductsWithPrice'=>'no','coDriver'=>null,'showProductsWithoutPrices'=>'no','info'=>'Hidden product description'));
$method = new ReflectionMethod('eshop_Products','isHiddenWithoutPrices'); $method->setAccessible(true);
check($method->invoke(null, core_Master::$tables['eshop_Products'][0]) === true, 'Fixture is hidden under existing public product rule');
$entries = (new eshop_Groups())->getLlmsEntries(20);
check(count($entries) === 1, 'llms excludes product hidden by public visibility rule');
check(cms_Domains::$requestedDomain == 10, 'Settings use the menu domain');
$group = core_Master::$tables['eshop_Groups'][0];
$product = core_Master::$tables['eshop_Products'][0];
$group->showProductsWithoutPrices = 'auto';
cms_Domains::$settings[10] = array('showProductsWithoutPrices'=>'no');
check(count((new eshop_Groups())->getLlmsEntries(20)) === 1, 'Auto inherits the domain visibility setting');
cms_Domains::$settings[10] = array('showProductsWithoutPrices'=>'yes');
check(count((new eshop_Groups())->getLlmsEntries(20)) === 2, 'Auto allows a product when the domain allows it');
$group->showProductsWithoutPrices = 'no';
$product->haveProductsWithPrice = 'yes';
check(count((new eshop_Groups())->getLlmsEntries(20)) === 2, 'Priced product remains included');
$product->haveProductsWithPrice = 'no';
$product->detailActions = 'both';
check(count((new eshop_Groups())->getLlmsEntries(20)) === 2, 'Product allowing inquiries remains included');
$product->detailActions = 'onlySell';
$product->coDriver = 'SyntheticDriver';
check(count((new eshop_Groups())->getLlmsEntries(20)) === 2, 'Product with inquiry driver remains included');
$product->coDriver = null;
$shared = clone $group;
$shared->id = 41;
$shared->showProductsWithoutPrices = 'yes';
$product->sharedInGroups = '|41|';
core_Master::$tables['eshop_Groups'][] = $shared;
$entries = (new eshop_Groups())->getLlmsEntries(20);
check(count($entries) === 3 && ($entries[2]->loc['groupId'] ?? null) == 41, 'Product hidden in its own group can be listed under a visible shared group');
$group->showProductsWithoutPrices = 'yes';
check(count((new eshop_Groups())->getLlmsEntries(20)) === 3, 'Visible product shared in two groups appears once');
echo "TOTAL {$passes} passed, {$fails} failed (" . PHP_VERSION . ")\n";
exit($fails ? 1 : 0);
