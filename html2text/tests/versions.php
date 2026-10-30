<?php

/**
 * CLI регресии с истинските библиотеки и минимални заместители на настройките
 *
 * @category  tests
 * @package   html2text
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 2 || $argc > 3) exit("Usage: php versions.php VERSION [BASELINE_DIRECTORY]\n");
$version = $argv[1];
$baselineDirectory = isset($argv[2]) ? realpath($argv[2]) : null;
$counter = true;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (!(error_reporting() & $level)) return false;
    throw new ErrorException($message, 0, $level, $file, $line);
});
/** Изолирано очакване. */
function expect($ok, $message = 'Assertion failed') { if (!$ok) throw new RuntimeException($message); }
/** Запазва дефинираните defaults. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
/** Брой без framework bootstrap. */
function countR($value) { return is_array($value) ? count($value) : 0; }
class core_ProtoSetup
{
    /** Симулира само стойностите от core_Packs. */
    public static function get($key) { return $key === 'VERSION' ? $GLOBALS['version'] : $GLOBALS['counter']; }
}
class type_Richtext
{
    const URL_PATTERN = '~https?://[^\s<]+~i';
}
require dirname(__DIR__, 2) . '/core/Url.class.php';
require dirname(__DIR__, 2) . '/core/String.class.php';
class_alias('core_String', 'str');
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Converter.class.php';
require dirname(__DIR__) . '/Html2Text.class.php';
require __DIR__ . '/Cases.class.php';
expect(in_array($version, array('1.0.0', '4.3.2'), true), 'Unsupported test version');
expect(HTML2TEXT_VERSION === '1.0.0' && HTML2TEXT_BUILD_LINK_COUNTER === true, 'Defaults changed');
if ($version === '4.3.2') require dirname(__DIR__) . '/Modern.class.php';
chdir(sys_get_temp_dir());
foreach (array(true, false) as $counter) {
    $result = html2text_tests_Cases::run();
    if (isset($argv[2])) {
        expect($version === '1.0.0', 'Baseline requires old version');
        foreach (array('Converter', 'Html2Text') as $name) {
            $class = 'html2text_Baseline' . $name;
            if (!class_exists($class, false)) {
                expect($baselineDirectory !== false, 'Baseline directory does not exist');
                $path = $baselineDirectory . '/' . $name . '.baseline.php';
                $source = str_replace('html2text_' . $name, $class, file_get_contents($path));
                $temp = tempnam(sys_get_temp_dir(), 'html2text-baseline-');
                file_put_contents($temp, $source);
                require $temp;
                unlink($temp);
            }
        }
        $old = html2text_tests_Cases::run('html2text_BaselineConverter', 'html2text_BaselineHtml2Text', true);
        expect($old['snapshot'] === $result['snapshot'], 'Legacy output changed');
        $result['baselineIdentical'] = true;
    }
    unset($result['snapshot']);
    echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'linkCounter' => $counter) + $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
expect((new html2text_Setup())->checkConfig() === null, 'Compatible configuration');
$temp = tempnam(sys_get_temp_dir(), 'html2text-input-');
try {
    file_put_contents($temp, '<p>Текст от файл</p>');
    expect(trim((new html2text_Converter($temp, true))->get_text()) === 'Текст от файл', 'Rich input from file');
    $plain = new html2text_Html2Text();
    $plain->set($temp, true);
    expect(trim($plain->get_text()) === 'Текст от файл', 'Plain input from file');
} finally {
    unlink($temp);
}
if ($version === '4.3.2') {
    $originalEncoding = mb_internal_encoding();
    mb_internal_encoding('ISO-8859-1');
    $failing = new class extends html2text_Converter {
        /** Проверява възстановяването на глобалната encoding настройка при грешка. */
        public function convertStyles($text, $preserveTags = false) { throw new RuntimeException('Expected failure'); }
    };
    try {
        try { $failing->get_text(); } catch (RuntimeException $e) { expect($e->getMessage() === 'Expected failure'); }
        expect(mb_internal_encoding() === 'ISO-8859-1', 'Encoding leaked after error');
    } finally {
        mb_internal_encoding($originalEncoding);
    }
}
