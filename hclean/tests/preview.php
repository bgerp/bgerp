<?php

/**
 * Синтетични HTML входове за проверка на браузърното пречистване.
 *
 * @category vendors
 * @package hclean
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class core_Manager {}
/** Замества само адреса на локалния JavaScript ресурс. */
function sbf($path, $quote, $absolute) { return "'sanitizer.js'"; }
require dirname(__DIR__) . '/JSSanitizer.class.php';

$im = imagecreatetruecolor(40, 20);
imagefill($im, 0, 0, imagecolorallocate($im, 25, 125, 205));
$images = array();
foreach (array('png', 'jpeg', 'gif') as $format) {
    ob_start();
    call_user_func('image' . $format, $im);
    $images[$format] = 'data:image/' . $format . ';base64,' . base64_encode(ob_get_clean());
}
imagedestroy($im);
// Синтетична 40x20 WebP картинка, независима от WebP поддръжката в GD.
$images['webp'] = 'data:image/webp;base64,UklGRkoAAABXRUJQVlA4ID4AAABwAwCdASooABQAPm02l0ikIyIhJWgAgA2JZwB2APwAAFfvhuAA/uns///ziWOp3ekH//zsHsu/erDJgIQAAA==';
$normal = '<h2>Име: Иван Тестов</h2><table><tr><td style="color:blue">12,50 EUR &amp; 4</td></tr></table>'
    . '<a href="https://example.test/a?x=1&amp;y=2">Link</a><img src="/ordinary.png" alt="Ordinary">';
$cases = array('normal' => $normal);
$cases['wwwLinks'] = '<a href="www.example.test">Plain</a>'
    . '<A HREF=\'WWW.example.test/path?x=1&amp;y=2#part\'>Uppercase</A>'
    . '<a href=www.example.test/unquoted>Unquoted</a>'
    . '<a href="  www.example.test:8443/path  ">Port</a>'
    . '<a href="www&#46;example.test/entities">Entities</a>'
    . '<map name="links"><area href="www.example.test/map" alt="Map"></map>';
$cases['unchangedLinks'] = '<a href="http://www.example.test">HTTP</a>'
    . '<a href="https://www.example.test">HTTPS</a><a href="//www.example.test">Scheme relative</a>'
    . '<a href="mailto:info@example.test">Email</a><a href="#www.part">Anchor</a>'
    . '<a href="/www.example.test">Root relative</a><a href="../www.example.test">Parent relative</a>'
    . '<a href="./www.example.test">Relative</a><a href="document.html">Document</a>'
    . '<a href="?url=www.example.test">Query</a><a>No URL</a><a href="">Empty</a>'
    . '<img src="www.example.test/image.png"><form action="www.example.test/submit"></form>'
    . '<div title="www.example.test" style="background-image:url(www.example.test/bg.png)">www.example.test</div>';
foreach ($images as $format => $uri) {
    $cases[$format] = $normal . '<img src="' . $uri . '" alt="' . $format . '" width="40" height="20" onload="qaUnsafe()">';
}
$cases['whitespace'] = '<img src="' . chunk_split($images['png'], 72, "\n") . '">';
$cases['uppercase'] = '<IMG SRC="' . str_replace('data:image/png;base64,', 'DATA:IMAGE/PNG;BASE64,', $images['png']) . '">';
$svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" onload="qaUnsafe()"/>');
$active = 'data:text/html;base64,' . base64_encode('<script>qaUnsafe()</script>');
$cases['denied'] = '<script>qaUnsafe()</script><iframe src="' . $active . '"></iframe><object data="' . $active . '"></object>'
    . '<svg onload="qaUnsafe()"><a href="javascript:qaUnsafe()">x</a></svg>'
    . '<img src="' . $svg . '" onerror="qaUnsafe()"><img src="' . $active . '">'
    . '<img src="javascript:qaUnsafe()"><img src="java&#x73;cript:qaUnsafe()">'
    . '<img src="data:image/png;base64,' . base64_encode('<svg onload="qaUnsafe()"/>') . '">'
    . '<img src="data:image/png;base64,invalid!"><img src="data:image/png;base64,AAAA">'
    . '<a href="' . $images['png'] . '">Data link</a><input type="image" src="' . $images['png'] . '">'
    . '<div style="background-image:url(' . $images['png'] . ');color:blue" onclick="qaUnsafe()">Text</div>'
    . '<img src="' . $images['png'] . '" onerror="qaUnsafe()" srcset="javascript:qaUnsafe() 1x">';
$cases['escaping'] = '<p>"Quote" \'apostrophe\' &amp; &lt;/script&gt; Кирилица</p><script>qaUnsafe()</script>';
$cases['duplicates'] = '<img src="' . $images['png'] . '" src="javascript:qaUnsafe()">';

$scripts = array();
foreach ($cases as $name => $html) {
    $scripts[$name] = str_replace(array('[#SANITIZEID#]', '[#SANITIZEHTML#]'),
        array('qa-preview', hclean_JSSanitizer::htmlToJsText($html)), hclean_JSSanitizer::JSSanitizer());
}
echo json_encode(array('php' => PHP_VERSION, 'html' => $cases, 'scripts' => $scripts, 'images' => $images), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
