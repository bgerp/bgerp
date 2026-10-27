<?php

/**
 * Изпълнява публикуваните входове/очаквани изходи от официалните библиотеки.
 *
 * @category tests
 * @package minify
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
require __DIR__ . '/versions.php';
$cssRoot = $argv[1] ?? '';
$jsRoot = $argv[2] ?? '';
expect(is_dir($cssRoot . '/tests/fixtures') && is_dir($jsRoot . '/tests/Resources/jshrink/input'), 'Pass CSSMin 4.1.1 and a JShrink v1.8.1 checkout with tests.');
require $jsRoot . '/src/JShrink/Minifier.php';
$upstreamChecks = 0;
foreach (glob($cssRoot . '/tests/fixtures/*.css') as $input) {
    // Тази upstream проба изисква опция, която bgERP адаптерът не задава.
    if (basename($input) === 'linebreak-position.css') continue;
    $expected = $cssRoot . '/tests/expectations/' . basename($input);
    if (!is_file($expected)) continue;
    expect(minify_Css::process(file_get_contents($input)) === file_get_contents($expected), 'CSSMin: ' . basename($input));
    $upstreamChecks++;
}
// Както upstream phpunit.xml.dist: requests и development са изключени.
foreach (array('jshrink', 'uglify') as $group) {
    foreach (glob($jsRoot . '/tests/Resources/' . $group . '/input/*.js') as $input) {
        $expected = $jsRoot . '/tests/Resources/' . $group . '/output/' . basename($input);
        if (!is_file($expected)) continue;
        $source = file_get_contents($input);
        $actual = minify_Js::process($source);
        expect($actual === \JShrink\Minifier::minify($source), 'JShrink differs from upstream: ' . basename($input));
        // Някои официални очаквания са с CRLF, докато библиотеката връща LF.
        expect(str_replace("\r\n", "\n", $actual) === str_replace("\r\n", "\n", file_get_contents($expected)), 'JShrink: ' . $group . '/' . basename($input));
        $upstreamChecks++;
    }
}
echo "PASS: {$upstreamChecks} upstream fixtures\n";
