<?php
/**
 * Регресии на PHPColors и адаптера без база данни или Composer.
 *
 * @category bgerp
 * @package phpcolor
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$checks = 0;
/** Проверява резултат, без да използва настройките на инсталацията. */
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); $GLOBALS['checks']++; }
/** Запазва глобалните defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_Mvc {}
class core_ProtoSetup {
    public static $selected = '0.3';
    /** Изолира настройката при превключване. */
    public static function get($name) { return self::$selected; }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__, 2) . '/color/Object.class.php';
require dirname(__DIR__) . '/Adapter.class.php';
check(PHPCOLOR_VERSION === '0.3', 'Default remains 0.3');
$baseline = array();
foreach (array('0.3', '1.0.4', '0.3') as $version) {
    core_ProtoSetup::$selected = $version;
    $result = array();
    for ($n = 0; $n < 128; $n++) {
        $color = sprintf('%06x', ($n * 129983) % 16777216);
        foreach (array(0, 1, 5, 10, 25, 80, 100, 12.5) as $amount) {
            foreach (array('lighten', 'darken', 'mix') as $op) {
                $hex = phpcolor_Adapter::changeColor($color, $op, $amount, 'aabbcc');
                check((bool) preg_match('/^[0-9a-f]{6}$/', $hex), 'Six-digit result');
                $result[] = $hex;
            }
        }
        $result[] = phpcolor_Adapter::checkColor($color);
        $result[] = phpcolor_Adapter::checkColor($color, 'dark');
    }
    if ($version === '0.3') {
        check(hash('sha256', json_encode($result)) === 'a2d961f2b3c1ab6150529fe18ee5e95915ee30d5d33a1cad0e6fdbe78da180a8', '3328 results match the previous adapter');
        $baseline = $result;
    } else {
        foreach ($result as $i => $value) {
            if (is_bool($value)) {
                check($value === $baseline[$i], 'Unchanged light/dark classification');
            } else {
                foreach (array(0, 2, 4) as $offset) {
                    check(abs(hexdec(substr($value, $offset, 2)) - hexdec(substr($baseline[$i], $offset, 2))) <= 1, 'Only upstream rounding difference');
                }
            }
        }
    }
    check(phpcolor_Adapter::changeColor('#36c', 'object')->getHex() === '3366cc', 'Short hexadecimal input');
    check(phpcolor_Adapter::changeColor('white', 'object')->getHex() === 'ffffff', 'Named color');
    check(phpcolor_Adapter::checkColor('white') === true && phpcolor_Adapter::checkColor('black', 'dark') === true, 'Contrast extremes');
    check(phpcolor_Adapter::checkColor('828282') === false && phpcolor_Adapter::checkColor('838383') === true, 'Contrast threshold');
    check(phpcolor_Adapter::checkColor('336699', 'unknown') === false, 'Unknown query unchanged');
    check(phpcolor_Adapter::changeColor(null, 'object')->getHex() === '000000', 'Empty optional color retains black normalization');
    $gradient = phpcolor_Adapter::changeColor('#336699', 'gradient', 25);
    $object = phpcolor_Adapter::changeColor('#336699', 'object');
    check($gradient === $object->getCssGradient(25), 'Gradient receives percentage');
    check(strpos($gradient, 'linear-gradient(to bottom, #') !== false, 'Standard CSS gradient');
    check($gradient !== phpcolor_Adapter::changeColor('#336699', 'gradient', 5), 'Gradient respects amount');
    check(phpcolor_Adapter::changeColor('#336699', 'lighten', 12.5) !== phpcolor_Adapter::changeColor('#336699', 'lighten', 12), 'Fractional percentage retained');
    check($object instanceof Color || $object instanceof \Mexitek\PHPColors\Color, 'Selected object API');
}
echo "PASS {$checks} checks on PHP " . PHP_VERSION . "; 3328 baseline results and round-trip\n";
