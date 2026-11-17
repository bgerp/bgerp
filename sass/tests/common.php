<?php

/**
 * Проверки на common.scss без тихо преминаване към резервен компилатор.
 *
 * @category tests
 * @package sass
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 4 || $argc > 5) exit("Usage: php common.php phpsass|1.0.4|1.13.0|2.1.0 LIBRARY_DIR_OR_AUTOLOAD OUTPUT_DIR [BEFORE_CSS]\n");
require __DIR__ . '/compiler.inc.php';

$css = minify_Css::process(sass_Converter::convert(dirname(__DIR__, 2) . '/css/common.scss', true));
expect(!core_Debug::$timers, 'Compiler timer leaked');
if (!is_dir($output)) mkdir($output, 0775, true);
file_put_contents($output . '/common.css', $css);
copy(__DIR__ . '/common.html', $output . '/index.html');
if (isset($argv[4])) copy($argv[4], $output . '/before.css');

// Тестът фиксира реално действащите правила преди смяната на компилатора.
$normalized = str_replace(array('"', "'"), '', strtolower($css));
$normalized = str_replace(array(':white', ':black'), array(':#fff', ':#000'), $normalized);
expect(strpos($normalized, '@media screen and (-webkit-min-device-pixel-ratio:0),(-ms-high-contrast:none){') !== false, 'Active media branches changed');
expect(strpos($normalized, '-ms-high-contrast:active') === false, 'Previously invalid media branch became active');
expect(strpos($normalized, 'select.readonly{max-width:36em}') !== false, 'Readonly select lost its width limit');
expect(strpos($normalized, '.readonly select{') === false, 'Readonly selector became a descendant');
expect(strpos($normalized, '.flashelem{background-color:#fffa99}') !== false, 'Flash color or inactive transition changed');
foreach (array('.flasheleminput.combo', '.flasheleminput[type=checkbox]', '.flasheleminput[type=radio]', '.flashelem label', 'small.flashelem') as $selector) {
    expect(preg_match('/(?:^|[},])' . preg_quote($selector, '/') . '(?:,[^{}]+)*\{background:none\}/', $normalized), 'Legacy flash target lost: ' . $selector);
}
expect(strpos($normalized, 'input.flashelem') === false, 'Legacy suffix selector changed its target');
expect(strpos($normalized, '.trip-sheet .trip-type,.domestic-waybill .trip-type{margin-bottom:4px;font-size:.95em;letter-spacing:0}') !== false, 'Trip label spacing changed');
expect(strpos($normalized, 'input[type=reset],input[type=submit]{cursor:pointer}') !== false, 'Cursor rule gained specificity');
foreach (array('notice' => '1976d2', 'warning' => 'b75b00', 'error' => 'a30000') as $severity => $color) {
    $selector = '.ef-confirm-modal.ef-confirm-' . $severity;
    expect(strpos($normalized, $selector . '{border-top-color:#' . $color . '}') !== false, 'Modal border color changed: ' . $severity);
    expect(strpos($normalized, $selector . ' .ef-confirm-title{color:#' . $color . '}') !== false, 'Modal title color changed: ' . $severity);
    expect(strpos($normalized, $selector . ' .ef-confirm-ok{background:#' . $color . ';border-color:#' . $color . '}') !== false, 'Modal button color changed: ' . $severity);
}
expect(strpos($normalized, '@media print{') !== false, 'Print rules lost');
echo json_encode(array('compiler' => $mode, 'php' => PHP_VERSION, 'checks' => $checks, 'legacyDeprecations' => $deprecations, 'cssSha256' => hash('sha256', $css)), JSON_UNESCAPED_UNICODE) . "\n";
