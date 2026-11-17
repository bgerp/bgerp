<?php

/**
 * Проверява DataMatrix ECC200, включително неравните блокове при 144x144.
 *
 * @category tests
 * @package tcpdf
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */

// php tcpdf/tests/datamatrix.php [6.2.13|6.11.4] - без база данни и декодер.
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
/** Превръща предупрежденията за липсващи кодови думи в провален тест. */
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$version = $argv[1] ?? '6.2.13';
if (!in_array($version, array('6.2.13', '6.11.4'), true)) {
    throw new InvalidArgumentException('Expected TCPDF 6.2.13 or 6.11.4');
}
require dirname(__DIR__) . ($version === '6.11.4' ? '/6.11.4' : '') . '/tcpdf_barcodes_2d.php';

// Синтетичен текст от повтарящи се „a“. Матриците са разчетени независимо със
// ZXing-C++ 2.3.0 до точния входен текст; малките съвпадат и с предишния код.
$cases = array(
    3 => array(10, '6ee2b838395693f528217983d8600acbcc96110ca8fc644df27b4322ba5dc78c'),
    600 => array(80, '65411aee1aab897175b6c47dea6bc407ecbe282841fe23a1e65a889ef9f3b90c'),
    1860 => array(132, '4723bebef0455df1e0418381537c0f099a2dbdf45aa75ffcae79005469cf6861'),
    2000 => array(144, 'b87ed95f6e43934bf3c67108dcc1291c26fe7f3e4f390347580ecb6e982e0508'),
    2334 => array(144, 'f1072767a7163833bdef8a29a7b54bda6b28cdd94097e138c98c3db6ba5f6595'),
);
foreach ($cases as $length => $expected) {
    $matrix = (new TCPDF2DBarcode(str_repeat('a', $length), 'DATAMATRIX'))->getBarcodeArray();
    if (($matrix['num_cols'] ?? null) !== $expected[0] || ($matrix['num_rows'] ?? null) !== $expected[0]
        || hash('sha256', json_encode($matrix)) !== $expected[1]) {
        throw new RuntimeException("Incorrect DataMatrix for {$length} characters");
    }
}
$oversized = (new TCPDF2DBarcode(str_repeat('a', 2400), 'DATAMATRIX'))->getBarcodeArray();
if (!empty($oversized['bcode'])) {
    throw new RuntimeException('Content above the symbol capacity must remain rejected');
}
echo "PASS: 6 DataMatrix cases (TCPDF {$version}, PHP " . PHP_VERSION . ")\n";
