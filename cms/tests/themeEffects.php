<?php
/**
 * Isolated checks for optional theme settings and section assets.
 * Run: php cms/tests/themeEffects.php
 *
 * @category  bgerp
 * @package   cms
 * @author    nevena <nevena@experta.bg>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit(1);
error_reporting(E_ALL);
set_error_handler(function ($n, $m, $f, $l) { throw new ErrorException($m, 0, $n, $f, $l); });
class core_BaseClass { public function __construct($params = null) {} }
class core_FieldSet {
    public $fields = array();
    public function FLD($name, $type, $params) { $this->fields[$name] = array($type, $params); }
    public function setFieldType($name, $type) { $this->fields[$name][0] = $type; }
    public function setField($name, $params) {}
}
class EffectTemplate {
    public $assets = array();
    public $scripts = array();
    public function push($file, $type) { $this->assets[$file] = $type; }
    public function appendOnce($value, $place) {}
}
class jquery_Jquery {
    public static function run($tpl, $script) { $tpl->scripts[] = $script; }
}
function getFullPath($path) { return is_file(dirname(__DIR__, 2) . '/' . $path); }
function tr($value) { return $value; }
function countR($value) { return is_countable($value) ? count($value) : 0; }
require dirname(__DIR__, 2) . '/core/Array.class.php';
class_alias('core_Array', 'arr');
require dirname(__DIR__, 2) . '/core/ProtoInner.class.php';
require dirname(__DIR__) . '/ProtoTheme.class.php';
require dirname(__DIR__) . '/DefaultTheme.class.php';
require dirname(__DIR__) . '/FancyTheme.class.php';
require dirname(__DIR__) . '/themes/cards/Theme.class.php';
require dirname(__DIR__) . '/themes/editorial/Theme.class.php';
$checks = 0;
function check($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    $GLOBALS['checks']++;
}
$sections = array('page', 'shop', 'blog', 'article', 'checkout', 'forum');
foreach (array('cms_DefaultTheme', 'cms_FancyTheme', 'cms_themes_cards_Theme', 'cms_themes_editorial_Theme') as $class) {
    $theme = new $class();
    $form = new core_FieldSet();
    $theme->addEmbeddedFields($form);
    foreach (array('buttonEffect', 'productHoverEffect', 'imageRevealEffect', 'productRevealEffect') as $field) {
        check(strpos($form->fields[$field][1], 'value=none') !== false, "$class default off");
    }
    foreach ($sections as $section) {
        $tpl = new EffectTemplate();
        $theme->addAssets($tpl, $section);
        check(!isset($tpl->assets['cms/js/ThemeEffects.js']), "$class legacy $section has no effects");
    }
}
$theme = new cms_ProtoTheme();
$values = array('buttonEffect' => 'underline', 'productHoverEffect' => 'border', 'imageRevealEffect' => 'left', 'productRevealEffect' => 'right');
$theme->setInnerForm((object) $values);
foreach ($sections as $section) {
    $tpl = new EffectTemplate();
    $theme->addAssets($tpl, $section);
    $enabled = in_array($section, array('page', 'shop', 'blog', 'article'));
    check(isset($tpl->assets['cms/js/ThemeEffects.js']) === $enabled, "Section $section scoped assets");
    if ($enabled) {
        check(isset($tpl->assets['cms/css/ThemeEffects.css']), 'CSS accompanies JS');
        $options = json_decode(substr($tpl->scripts[0], strlen('cmsThemeEffects('), -2), true);
        $expected = $section === 'page' ? array('buttonEffect') : ($section === 'shop' ? array('productHoverEffect', 'productRevealEffect') : array('imageRevealEffect'));
        check(array_keys($options) === $expected, "Only $section settings passed");
        foreach ($options as $field => $value) check($value === $values[$field], 'Stored selection preserved');
    }
}
foreach (array('none', '', 'invalid', '\"><script>', null, array('glow')) as $invalid) {
    $theme->setInnerForm((object) array_fill_keys(array_keys($values), $invalid));
    foreach ($sections as $section) {
        $tpl = new EffectTemplate();
        $theme->addAssets($tpl, $section);
        check(!$tpl->assets && !$tpl->scripts, 'Invalid or disabled settings load nothing');
    }
}
echo "$checks checks passed on PHP " . PHP_VERSION . "\n";
