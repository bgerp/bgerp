<?php

/**
 * Проверки на реалния new-design.scss със старите и новите Sass компилатори.
 *
 * @category tests
 * @package sass
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 4 || $argc > 5) exit("Usage: php newDesign.php phpsass|1.0.4|1.13.0|2.1.0 LIBRARY_DIR_OR_AUTOLOAD OUTPUT_DIR [BEFORE_SCSS_OR_CSS]\n");
require __DIR__ . '/compiler.inc.php';

$css = minify_Css::process(sass_Converter::convert(dirname(__DIR__, 2) . '/css/new-design.scss', true));
expect(!core_Debug::$timers, 'Compiler timer leaked');
if (!is_dir($output)) mkdir($output, 0775, true);
file_put_contents($output . '/new-design.css', $css);
copy(__DIR__ . '/newDesign.html', $output . '/index.html');

// Селекторите трябва да останат type-qualified, със същия ред и вложени връзки.
$colors = array(
    'lightgray' => array('f1f1f1', '000', '333'), 'darkgray' => array('333', 'eee', 'bbb'),
    'lightgreen' => array('7ac15b', '222', '444'), 'darkgreen' => array('375d26', 'eee', 'ccc'),
    'darkblue' => array('261e75', 'fff', 'ccc'), 'lightblue' => array('2085ca', 'fff', 'ccc'),
    'lightred' => array('ea5757', 'fff', 'ccc'), 'darkred' => array('6b1e1e', 'fff', 'ccc'),
    'purple' => array('6d417b', 'fff', 'ccc'), 'pink' => array('c147aa', 'fff', 'ccc'),
    'yellow' => array('eaea6b', '333', '555'), 'orange' => array('e48027', 'fff', '444'),
);
$normalized = str_replace(array(':white', ':black'), array(':#fff', ':#000'), strtolower($css));
foreach ($colors as $name => $values) {
    $class = '.color-' . $name;
    $selector = 'span' . $class . ',' . $class . ' .legend,li' . $class;
    $background = $name === 'lightgray' ? 'background' : 'background-color';
    expect(strpos($normalized, $selector . '{' . $background . ':#' . $values[0] . ';color:#' . $values[1] . '}') !== false, 'Color targets changed: ' . $name);
    expect(strpos($normalized, 'span' . $class . ' a,' . $class . ' .legend a,li' . $class . ' a{color:#' . $values[2] . '}') !== false, 'Link targets changed: ' . $name);
    $portalPosition = strpos($css, $class . ' .portal{');
    expect($portalPosition !== false && $portalPosition < strpos($css, $selector . '{'), 'Portal cascade order changed: ' . $name);
}
expect(strpos($css, '1top:') === false, 'Invalid disabled declarations remain');
expect(!preg_match('/\.center-block(?: \.notificationsCnt)?\{[^{}]*\btop:-(?:4|2)px/', $css), 'Disabled top declarations became active');
if ($mode !== 'phpsass') {
    expect(strpos($css, 'var(--themeColorD30)') !== false && strpos($css, 'var(--themeColorD100)') !== false, 'Theme variables corrupted');
    expect(strpos($css, 'transition:transform 300ms ease-in-out,opacity 300ms') !== false, 'Transition units lost');
}

// Редът на правилата е значим; редът на селекторите в една група не е.
if (isset($argv[4])) {
    $before = minify_Css::process(substr($argv[4], -5) === '.scss' ? sass_Converter::convert($argv[4], true) : file_get_contents($argv[4]));
    file_put_contents($output . '/before.css', $before);
    if ($mode === 'phpsass') {
        foreach ($colors as $name => $values) {
            $class = '.color-' . $name;
            foreach (array('', ' a') as $suffix) {
                $before = str_replace($class . ' .legend' . $suffix . ',span' . $class . $suffix . ',li' . $class . $suffix . '{', 'span' . $class . $suffix . ',' . $class . ' .legend' . $suffix . ',li' . $class . $suffix . '{', $before);
            }
        }
    }
    $inactive = '@media handheld,only screen and (max-width:1280px){@-moz-document url-prefix(){.center-block{1top:-4px}.center-block .notificationsCnt{1top:-2px}}}';
    expect(strpos($before, $inactive) !== false, 'Baseline does not contain the original disabled declarations');
    // 2.1 премахва незадължителните кавички около простите стойности на атрибути.
    $unquote = '/\[([a-z]+)="([a-z]+)"\]/';
    expect(preg_replace($unquote, '[$1=$2]', str_replace($inactive, '', $before)) === preg_replace($unquote, '[$1=$2]', $css), 'Other CSS or cascade order changed');
}
echo json_encode(array('compiler' => $mode, 'php' => PHP_VERSION, 'checks' => $checks, 'legacyDeprecations' => $deprecations, 'cssSha256' => hash('sha256', $css)), JSON_UNESCAPED_UNICODE) . "\n";
