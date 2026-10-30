<?php

/**
 * CLI проверки без база данни; всеки legacy парсер е в отделен процес.
 *
 * @category  tests
 * @package   markdown
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 2 || $argc > 3) exit("Usage: php versions.php VERSION|bootstrap [BASELINE_RENDER_FILE]\n");
$version = $argv[1];
$bootstrap = $version === 'bootstrap';
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (!(error_reporting() & $level)) return false;
    throw new ErrorException($message, 0, $level, $file, $line);
});
/** Изолирана проверка без framework bootstrap. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Запазва вече зададените defaults. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
class core_ProtoSetup
{
    /** Симулира избраната версия; забранява четене на настройки преди база. */
    public static function get($key) { expect(!$GLOBALS['bootstrap'], 'Bootstrap must not access core_Packs'); return $GLOBALS['version']; }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Render.class.php';
require __DIR__ . '/Cases.class.php';
expect(MARKDOWN_VERSION === 'extra-1.2.5', 'Default changed');
chdir(sys_get_temp_dir());
if ($bootstrap) {
    require dirname(__DIR__, 2) . '/setup/Controller.class.php';
    $res = new stdClass();
    (new setup_Controller())->form1($res);
    expect(strpos($res->body, 'GNU GENERAL PUBLIC LICENSE') !== false, 'Bootstrap license');
    expect(!class_exists('Michelf\\MarkdownExtra', false), 'Bootstrap uses legacy default');
    echo "PHP " . PHP_VERSION . ": bootstrap license OK, no core_Packs access\n";
    exit;
}
$result = markdown_tests_Cases::run();
if (isset($argv[2])) {
    expect($version !== '2.0.0', 'Baseline requires legacy version');
    $source = str_replace(array('class markdown_Render', '$filePath = MARKDOWN_VERSION'), array('class markdown_BaselineRender', '$filePath = ' . var_export($version, true)), file_get_contents($argv[2]));
    $baselineFile = tempnam(sys_get_temp_dir(), 'markdown-baseline-');
    file_put_contents($baselineFile, $source);
    require $baselineFile;
    unlink($baselineFile);
    set_include_path(dirname(__DIR__) . PATH_SEPARATOR . get_include_path());
    $before = markdown_tests_Cases::run('markdown_BaselineRender', true);
    expect($before['snapshot'] === $result['snapshot'], 'Legacy output changed');
    $result['baselineIdentical'] = true;
}
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__) . '/RichTextPlg.class.php';
$mvc = new class {
    public $_htmlBoard = array();
    /** Детерминиран placeholder за изолирания тест на плъгина. */
    public function getPlace() { return 'MD' . count($this->_htmlBoard); }
};
$plugin = new markdown_RichTextPlg();
$text = '[md]![Картинка](https://example.invalid/a b.png)[/md]';
$plugin->on_BeforeCatchRichElements($mvc, $text);
$plugin->on_AfterCatchRichElements($mvc, $text);
expect($text === '[#MD0#]', 'Richtext placeholder');
expect(strpos($mvc->_htmlBoard['MD0'], 'src="https://example.invalid/a%20b.png"') !== false, 'Image URL spaces');
$empty = '[md][/md]';
$plugin->on_AfterCatchRichElements($mvc, $empty);
expect($empty === '', 'Empty Markdown block');
$result['checks'] += 3;
$fenced = "[md]```php\necho 'код';\n```[/md]";
$plugin->on_BeforeCatchRichElements($mvc, $fenced);
if ($version === '2.0.0') {
    expect($fenced === '[#MD1#]' && strpos($mvc->_htmlBoard['MD1'], '<code class="php">') !== false, 'Code fence before Richtext inline parsing');
} else {
    expect(strpos($fenced, '```php') !== false, 'Legacy hook order unchanged');
}
$result['checks']++;
expect((new markdown_Setup())->checkConfig() === null, 'Compatible runtime');
unset($result['snapshot']);
echo json_encode(array('php' => PHP_VERSION, 'version' => $version) + $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
