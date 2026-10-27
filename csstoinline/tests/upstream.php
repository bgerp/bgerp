<?php

/**
 * Сравнява оптимизираните версии с непроменените официални издания.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Emogrifier.class.php';
require dirname(__DIR__) . '/CssToInline.class.php';
require __DIR__ . '/fixtures.php';
// Аргументи: Composer autoload, директория src на Emogrifier 8.3.0, директория src на CssToInlineStyles 2.4.0.
$upstreamPaths = array('Pelago\\Emogrifier\\' => $argv[2] ?? '', 'TijsVerkoyen\\CssToInlineStyles\\' => $argv[3] ?? '');
foreach ($upstreamPaths as $path) {
    expect(is_dir($path), 'Pass both upstream src directories after the Composer autoload path.');
}
spl_autoload_register(function ($class) use ($upstreamPaths) {
    foreach ($upstreamPaths as $prefix => $path) {
        if (strpos($class, $prefix) === 0) {
            $file = $path . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) require_once $file;
        }
    }
});
core_Packs::$config = (object) array('CSSTOINLINE_EMOGRIFIER_VERSION' => '8.3.0', 'CSSTOINLINE_CSSTOINLINE_VERSION' => '2.4.0');
$checks = 0;
foreach (csstoinlineTestFixtures() as $name => $fixture) {
    list($html, $css) = $fixture;
    foreach (array('Emogrifier', 'CssToInline') as $library) {
        $start = microtime(true);
        if ($library === 'Emogrifier') {
            $inliner = \Pelago\Emogrifier\CssInliner::fromHtml($html)->inlineCss($css);
            $expected = \Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromDomDocument($inliner->getDomDocument())
                ->removeElementsWithDisplayNone()->render();
        } else {
            $expected = (new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles())->convert($html, $css);
        }
        $rawTime = microtime(true) - $start;
        $class = 'csstoinline_' . $library;
        $start = microtime(true);
        $actual = (new $class())->convert($html, $css);
        $filteredTime = microtime(true) - $start;
        expect($actual === $expected, $library . ' ' . $name . ': output differs from upstream');
        $checks++;
        printf("PASS: %s %s byte-identical; upstream %.4fs, filtered %.4fs\n", $library, $name, $rawTime, $filteredTime);
    }
}
echo "PASS: {$checks} upstream comparisons\n";
