<?php

/**
 * Проверки на кодировките в HTML прегледа без база данни и мрежа.
 *
 * @category vendors
 * @package hclean
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require dirname(__DIR__, 2) . '/csstoinline/tests/bootstrap.php';
class core_MVC
{
    /** Насочва към реалното преобразуване без плъгините на framework-а. */
    public static function __callStatic($name, $args) { return forward_static_call_array(array(static::class, $name . '_'), $args); }
}
class core_Os
{
    /** Не променя memory_limit при изолирания тест. */
    public static function getBytesFromMemoryLimit($value = null) { return PHP_INT_MAX; }
}
class core_Url
{
    /** Синтетичните HTML файлове нямат външни CSS ресурси. */
    public static function isValidUrl($url) { return false; }
}
require dirname(__DIR__, 2) . '/core/Array.class.php';
class arr extends core_Array {}
require dirname(__DIR__, 2) . '/i18n/Charset.class.php';
require dirname(__DIR__, 2) . '/csstoinline/Setup.class.php';
require dirname(__DIR__, 2) . '/csstoinline/Emogrifier.class.php';
require dirname(__DIR__, 2) . '/csstoinline/CssToInline.class.php';
require dirname(__DIR__, 2) . '/csstoinline/ToInline.class.php';
require dirname(__DIR__) . '/JSSanitizer.class.php';

$checks = 0;
/** Проверява резултата и брои успешните проверки. */
function check($value, $message) { global $checks; expect($value, $message); $checks++; }
/** Създава HTML с изрично зададена кодировка и без външни ресурси. */
function encodingHtml($text, $charset = 'UTF-8', $meta = true)
{
    $declaration = $meta ? '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">' : '';
    return '<!doctype html><html><head>' . $declaration . '<style>p {color:blue}</style></head><body><p>'
        . $text . '</p><img src="data:image/png;base64,iVBORw0KGgo="></body></html>';
}
$cases = array();
foreach (array(1100, 10000, 100000) as $length) {
    foreach (array(true, false) as $meta) {
        $text = str_repeat('А', $length) . ' КРАЕН_КОД Име: Иван Тестов';
        $cases['utf8-' . $length . '-' . (int) $meta] = array(encodingHtml($text, 'UTF-8', $meta), $text);
    }
}
$normal = 'България, София. Име: Иван Тестов. 00123 EUR.';
$cases['utf8-normal'] = array(encodingHtml($normal), $normal);
$cases['utf8-bom'] = array("\xEF\xBB\xBF" . encodingHtml($normal), $normal);
$cases['utf8-languages'] = array(encodingHtml('Ελληνικά 日本語 Français 😀'), 'Ελληνικά 日本語 Français 😀');
$cases['ascii-entities'] = array(encodingHtml('12.50 EUR &amp; &#1048;&#1084;&#1077;'), '12.50 EUR & Име');
foreach (array(
    'Windows-1251' => 'Това е нормален български текст за проверка на кодировката. България, София.',
    'ISO-8859-1' => 'Café français, élève et garçon. Crème brûlée.',
    'Windows-1252' => 'Prix: 12,50 € - Café français et crème brûlée.',
    'Windows-1253' => 'Αυτό είναι ελληνικό κείμενο για δοκιμή της κωδικοποίησης.',
    'KOI8-R' => 'Это русский текст для проверки правильного отображения документа.',
) as $charset => $text) {
    $cases[$charset] = array(iconv('UTF-8', $charset, encodingHtml($text, $charset)), $text);
}
$text = 'Това е български текст без обявена кодировка. Документ за проверка на системата.';
$cases['cp1251-no-meta'] = array(iconv('UTF-8', 'Windows-1251', encodingHtml($text, 'Windows-1251', false)), $text);
$greek = 'Αυτό είναι ελληνικό κείμενο για δοκιμή της κωδικοποίησης.';
$greekHtml = encodingHtml($greek, 'Windows-1253', false);
foreach (array(
    '<meta charset="Windows-1253">',
    "<META CHARSET='windows-1253'>",
    '<meta charset=Windows-1253>',
    '<meta data-note="1 > 0" charset="Windows-1253">',
    "<meta content='text/html;charset=Windows-1253' http-equiv='Content-Type'>",
    "<meta\nhttp-equiv='Content-Type'\ncontent='text/html; charset=Windows-1253'>",
) as $i => $meta) {
    $input = str_replace('<head>', '<head>' . $meta, $greekHtml);
    $cases['cp1253-meta-' . $i] = array(iconv('UTF-8', 'Windows-1253', $input), $greek);
}
// Вече преобразуван UTF-8 със стара декларация трябва да остане четим.
$cases['utf8-stale-meta'] = array(encodingHtml($greek, 'Windows-1253'), $greek);
$japanese = 'これは日本語の文字コードを確認するためのテストです。商品と数量を正しく表示します。';
foreach (array('Shift_JIS', 'shift-jis', 'SJIS', 'MS_KANJI', 'CP932', 'EUC-JP') as $charset) {
    $encoding = i18n_Charset::getCanonical($charset);
    $cases['japanese-' . $charset] = array(iconv('UTF-8', $encoding, encodingHtml($japanese, $charset)), $japanese);
}
foreach (array($japanese, '日本語のテストです。', 'カタカナとひらがな、漢字を表示します。',
    '日本の商品一覧と数量を確認してください。', 'お問い合わせありがとうございます。') as $i => $text) {
    $cases['sjis-no-meta-' . $i] = array(iconv('UTF-8', 'SJIS', encodingHtml($text, 'Shift_JIS', false)), $text);
}
foreach (array('<meta charset="Shift_JIS">', "<META CHARSET='shift-jis'>", '<meta charset=SJIS>') as $i => $meta) {
    $input = str_replace('<head>', '<head>' . $meta, encodingHtml($japanese, 'Shift_JIS', false));
    $cases['sjis-meta-' . $i] = array(iconv('UTF-8', 'SJIS', $input), $japanese);
}
$cases['utf8-stale-sjis-meta'] = array(encodingHtml($japanese, 'Shift_JIS'), $japanese);
$cases['sjis-long'] = array(iconv('UTF-8', 'SJIS', encodingHtml(str_repeat($japanese, 1000), 'Shift_JIS')), str_repeat($japanese, 1000));
// Невалидни Shift_JIS байтове не трябва да се отхвърлят само заради добър рейтинг на остатъка.
$invalidSjis = iconv('UTF-8', 'SJIS', $japanese) . "\xFF";
check(i18n_Charset::detect($invalidSjis) !== 'SJIS', 'Invalid Shift_JIS is not an automatic candidate');
$cases['empty'] = array('', '');

$temp = tempnam(sys_get_temp_dir(), 'hclean-encoding-');
try {
    $converters = array('off' => array(''), 'csstoinline_Emogrifier' => array('2011.10.26', '2014.11.10', '3.0.0', '8.3.0'),
        'csstoinline_CssToInline' => array('1.0.3', '2.2.2', '2.4.0'));
    foreach ($converters as $class => $versions) {
        foreach ($versions as $version) {
            core_Packs::$config = (object) array('HCLEAN_JSSANITIZER_PLACE_CSS_TO_INLINE' => $class === 'off' ? 'no' : 'yes',
                'CSSTOINLINE_CONVERTER_CLASS' => $class, 'CSSTOINLINE_EMOGRIFIER_VERSION' => $version,
                'CSSTOINLINE_CSSTOINLINE_VERSION' => $version);
            foreach ($cases as $name => $case) {
                list($input, $expected) = $case;
                file_put_contents($temp, $input);
                $js = hclean_JSSanitizer::prepareHtml($temp);
                check(mb_check_encoding($js, 'UTF-8'), "$class $version $name: valid UTF-8");
                check($expected === '' ? $js === '' : strpos($js, $expected) !== false, "$class $version $name: exact text preserved");
                if ($input !== '') check(strpos($js, 'data:image\\/png;base64,iVBORw0KGgo=') !== false, "$class $version $name: embedded data unchanged");
                check(file_get_contents($temp) === $input, "$class $version $name: source unchanged");
            }
        }
    }
} finally {
    unlink($temp);
}
echo "HTML encoding: {$checks} checks passed (PHP " . PHP_VERSION . ").\n";
