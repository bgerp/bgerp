<?php

/**
 * Проверки на setup изискванията, без промяна на системната Java/Office среда.
 *
 * @category tests
 * @package docoffice
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
/** Проверява условие извън framework-а. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); }
/** Зарежда оригиналните defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_ProtoSetup
{
    public static $selectedVersion = '5.1.1', $converter = 'docoffice_Jodconverter';
    /** Изолира избраните стойности от реалния пакетен конфиг. */
    public static function get($name, $absolute = false) { return $name === 'OFFICE_CONVERTER_CLASS' ? self::$converter : ($name === 'OFFICE_UNOSERVER_PYTHON' ? 'python3' : self::$selectedVersion); }
    /** Пропуска моделите и базата данни. */
    public function install() { return ''; }
}
class cls
{
    /** Тестът използва име на клас вместо запис в core_Classes. */
    public static function getClassName($name) { return $name; }
    /** OFFICE_PACKET_PATH е дефиниран от самия тест. */
    public static function load($name) { expect($name === 'docoffice_Office'); }
}
require dirname(__DIR__) . '/Setup.class.php';
$dir = sys_get_temp_dir() . '/docoffice-config-' . uniqid();
expect(mkdir($dir, 0755));
define('OFFICE_PACKET_PATH', $dir . '/soffice');
$previousPath = getenv('PATH');
$setup = new docoffice_Setup();
try {
    putenv('PATH=' . $dir);
    expect(strpos($setup->checkConfig(), 'Java 17') !== false, 'Missing Java accepted');
    expect(strpos($setup->install(), 'debug-error') !== false, 'Missing Java must stop before Composer');
    foreach (array('java version "1.8.0_472"', 'openjdk version "11.0.29"', 'unrecognized Java') as $version) {
        file_put_contents($dir . '/java', "#!/bin/sh\nprintf '%s\\n' " . escapeshellarg($version) . " >&2\n");
        chmod($dir . '/java', 0755);
        expect(strpos($setup->checkConfig(), 'Java 17') !== false, 'Unsupported Java accepted');
    }
    file_put_contents($dir . '/java', "#!/bin/sh\nprintf '%s\\n' 'openjdk version \"17.0.21\"' >&2\n");
    expect(strpos($setup->checkConfig(), 'LibreOffice/OpenOffice') !== false, 'Missing Office accepted');
    expect(strpos($setup->install(), 'debug-error') !== false, 'Missing Office must stop before Composer');
    file_put_contents($dir . '/soffice', "#!/bin/sh\nprintf '%s\\n' 'LibreOffice 7.4'\n");
    chmod($dir . '/soffice', 0755);
    expect($setup->checkConfig() === null, 'Java 17 + Office rejected');
    file_put_contents($dir . '/java', "#!/bin/sh\nprintf '%s\\n' 'openjdk version \"21.0.12\"' >&2\n");
    expect($setup->checkConfig() === null, 'Java 21 + Office rejected');
    unlink($dir . '/java');
    core_ProtoSetup::$selectedVersion = '3.0b4';
    expect($setup->checkConfig() === null, 'New requirement applied to old JODConverter');
    core_ProtoSetup::$selectedVersion = '5.1.1';
    core_ProtoSetup::$converter = 'docoffice_Unoconv';
    expect($setup->checkConfig() === null, 'Java requirement applied to Unoconv');
    core_ProtoSetup::$converter = 'docoffice_Unoserver';
    expect(strpos($setup->checkConfig(), 'Python 3.8+') !== false, 'Missing Python accepted');
    expect(strpos($setup->install(), 'debug-error') !== false, 'Missing Python must stop before Composer');
    file_put_contents($dir . '/python3', "#!/bin/sh\nexit 1\n");
    chmod($dir . '/python3', 0755);
    expect(strpos($setup->checkConfig(), 'python3-uno') !== false, 'Failed UNO import accepted');
    file_put_contents($dir . '/python3', "#!/bin/sh\nexit 0\n");
    expect($setup->checkConfig() === null, 'Python/UNO + Office rejected or requires Java');
    unlink($dir . '/soffice');
    expect(strpos($setup->checkConfig(), 'LibreOffice/OpenOffice') !== false, 'Unoserver without Office accepted');
    expect(OFFICE_JODCONVERTER_VERSION === '3.0b4' && OFFICE_CONVERTER_CLASS === 'docoffice_Jodconverter', 'Defaults changed');
    expect(OFFICE_CONVERTER_PYTHON === '' && OFFICE_CONVERTER_UNOCONV === 'unoconv', 'Unoconv defaults changed');
} finally {
    putenv($previousPath === false ? 'PATH' : 'PATH=' . $previousPath);
}
echo 'PHP ' . PHP_VERSION . "; requirements and defaults: OK\n";
