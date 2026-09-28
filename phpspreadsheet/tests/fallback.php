<?php

/**
 * Изолирани проверки на избора и резервния XLS конвертор, без база данни
 *
 * @category  bgerp
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

class core_BaseClass {}
class core_Mvc extends core_BaseClass
{
    public static function logWarning($message) {}
}
class export_Setup
{
    public static $converter = '';
    public static function get($key) { return self::$converter; }
}
class core_Packs
{
    public static $installed = false;
    public static function isInstalled($package) { return self::$installed; }
}
class cls
{
    public static function getInterface($interface, $class)
    {
        $result = new $interface();
        $result->class = new $class();

        return $result;
    }
}
class test_XlsConverter
{
    public static $available = true;
    public static $fail = false;
    public static $result = 'new-xls';
    public function isAvailable() { return self::$available; }
    public function convertToXls($fileHnd, $data)
    {
        if (self::$fail) throw new RuntimeException('Synthetic conversion failure');

        return self::$result;
    }
}
class fileman
{
    public static function fetchByFh($fh) { return (object) array('fileHnd' => $fh); }
    public static function absorb($path, $bucket) { return 'legacy-xls'; }
}
class fileman_webdrv_Office
{
    public static $calls = 0;
    public static $directory;
    public static function convertToFile($rec, $ext, $async, $callback, $type)
    {
        self::$calls++;
        self::$directory = sys_get_temp_dir() . '/xls-fallback-' . uniqid();
        mkdir(self::$directory);
        $path = self::$directory . '/result.xls';
        file_put_contents($path, 'synthetic legacy result');

        return $path;
    }
}
class core_Os
{
    public static function deleteDir($path)
    {
        unlink($path . '/result.xls');
        rmdir($path);
    }
}
function reportException($e) {}
function checkResult($expected, $calls)
{
    fileman_webdrv_Office::$calls = 0;
    $actual = export_Xls::convertToXls('synthetic-csv');
    if ($actual !== $expected || fileman_webdrv_Office::$calls !== $calls
        || (fileman_webdrv_Office::$directory && is_dir(fileman_webdrv_Office::$directory))) {
        throw new RuntimeException('Wrong converter, result or temporary-file cleanup');
    }
}

require dirname(__DIR__, 2) . '/export/XlsConverterIntf.class.php';
require dirname(__DIR__, 2) . '/export/OfficeXls.class.php';
require dirname(__DIR__, 2) . '/export/Xls.class.php';

// Няма дори дефиниция на адаптера: старият експорт не бива да я изисква.
checkResult('legacy-xls', 1);
echo "Package absent: OK\n";

core_Packs::$installed = true;
class_alias('test_XlsConverter', 'phpspreadsheet_Adapter');
checkResult('new-xls', 0);
echo "Automatic converter: OK\n";

test_XlsConverter::$available = false;
checkResult('legacy-xls', 1);
echo "Library absent: OK\n";

test_XlsConverter::$available = true;
test_XlsConverter::$fail = true;
checkResult('legacy-xls', 1);
echo "Conversion exception: OK\n";

test_XlsConverter::$fail = false;
test_XlsConverter::$result = null;
checkResult('legacy-xls', 1);
echo "Empty conversion result: OK\n";

test_XlsConverter::$result = 'new-xls';
export_Setup::$converter = 'export_OfficeXls';
checkResult('legacy-xls', 1);
echo "Explicit legacy converter: OK\n";

export_Setup::$converter = 'test_XlsConverter';
checkResult('new-xls', 0);
echo "Explicit converter through interface: OK\n";

export_Setup::$converter = 'missing_XlsConverter';
checkResult('legacy-xls', 1);
echo "Removed converter code: OK\n";
