<?php


/**
 * Синтетични проверки на HTML към Richtext и обикновен текст
 *
 * @category  tests
 * @package   html2text
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class html2text_tests_Cases
{
    /** Пример за файлов преглед, писмо и проверка през частен пакет. */
    public static function preview()
    {
        return '<html><head><meta charset="UTF-8"><title>Пример</title><style>.x{color:red}</style></head><body>'
            . '<h2>Оферта на кирилица</h2><p><b>Важно</b> и <i>акцент</i>.</p>'
            . '<span style="color:rgb(255,0,0);font-weight:bold">Цветен текст</span>'
            . '<ul><li>Първи ред</li><li>Втори ред</li></ul>'
            . '<table><tr><th>Продукт</th><th>Сума</th></tr><tr><td>Хартия</td><td>12.50</td></tr>'
            . '<tr><td>Папка</td><td>3.40</td></tr><tr><td>Общо</td><td>15.90</td></tr></table>'
            . '<blockquote><p>Цитирано писмо</p><blockquote>Вложен цитат</blockquote></blockquote>'
            . '<p><a href="https://example.invalid/?a=1&amp;b=2">Примерен линк</a> '
            . '<a href="mailto:office@example.invalid">Поща</a></p>'
            . "<pre>първи ред\n    втори ред &lt; 5</pre>"
            . '<p>Цена &euro; &amp; апостроф &#39;</p><script>ignored_script()</script></body></html>';
    }


    /** Проверява смисъла на резултата и събира точен snapshot за старите реализации. */
    public static function run($richClass = 'html2text_Converter', $plainClass = 'html2text_Html2Text', $baseline = false)
    {
        $modern = html2text_Setup::get('VERSION') === '4.3.2';
        $checks = 0;
        $assert = function ($ok, $message) use (&$checks) { expect($ok, $message); $checks++; };
        $inputs = array(
            'cyrillic' => '<p>Български <b>текст</b> и <i>акцент</i>.</p>',
            'links' => '<a href="https://example.invalid/first">Първи</a> <a href="mailto:a@example.invalid">Поща</a>',
            'relative' => '<base href="https://example.invalid/"><a href="/test">Относителен линк</a>',
            'lists' => '<ol><li>Едно</li><li>Две</li></ol><ul><li>Три</li></ul>',
            'quotes' => '<blockquote>Външен<blockquote>Вътрешен</blockquote>Край</blockquote>',
            'pre' => "<pre>line1\n  line2</pre><pre>separate</pre>",
            'styles' => '<span style="color:rgb(255,0,0);background-color:rgba(0,0,0,0.5);font-weight:bold;font-style:italic;text-decoration:underline">Цветове</span>',
            'font' => '<font color="blue">Син</font>',
            'entities' => '<p>&#1041; &amp; &lt; &quot; &#39; &euro;</p>',
            'malformed' => '<p>Текст<b>незатворен<br>следващ',
            'preview' => self::preview(),
        );
        $snapshot = array();
        foreach ($inputs as $name => $input) {
            $rich = new $richClass($input);
            $plain = new $plainClass();
            $plain->set($input);
            $snapshot[$name] = array($rich->get_text(), $plain->get_text(), $plain->convert2text($input, true));
            $assert(is_string($snapshot[$name][0]) && $snapshot[$name][0] !== '', $name . ' rich result');
            $assert(is_string($snapshot[$name][1]) && $snapshot[$name][1] !== '', $name . ' plain result');
        }
        $assert(strpos($snapshot['cyrillic'][0], '[b]текст[/b]') !== false, 'Bold marker');
        $assert(strpos($snapshot['cyrillic'][0], '[i]акцент[/i]') !== false, 'Italic marker');
        foreach (array('[color=#ff0000]', '[bg=#808080]', '[b]', '[i]', '[u]') as $marker) {
            $assert(strpos($snapshot['styles'][0], $marker) !== false, 'Style ' . $marker);
        }
        $assert(strpos($snapshot['font'][0], '[color=blue]Син[/color]') !== false, 'FONT compatibility');
        $assert(substr_count($snapshot['quotes'][0], '[bQuote]') === 2, 'Nested quotes');
        foreach (array('Хартия', '12.50', 'Папка', '3.40', '15.90', 'office@example.invalid') as $value) {
            $assert(strpos($snapshot['preview'][0], $value) !== false, 'Preview ' . $value);
        }
        $assert(strpos($snapshot['preview'][0], 'ignored_script') === false && strpos($snapshot['preview'][1], 'ignored_script') === false, 'Scripts removed');
        $assert(strpos($snapshot['links'][0], '[link=https://example.invalid/first]') !== false, 'bgERP link');
        $counter = html2text_Setup::get('BUILD_LINK_COUNTER');
        $assert(strpos($snapshot['links'][0], $counter ? '[1]' : '[link=https://example.invalid/first]Първи[/link]') !== false, 'Link counter setting');
        $alert = (new $richClass('<a href="https://other.invalid/">https://example.invalid/</a>'))->get_text();
        $assert(strpos($alert, '[em=alert]') !== false, 'Visible domain mismatch marker');
        $reuse = new $richClass('<a href="https://example.invalid/">Линк</a>');
        $reuse->get_text();
        $reuse->set_html('<p>Нов текст</p>');
        $assert(trim($reuse->get_text()) === 'Нов текст', 'Reset text and link state');
        $allowed = new $richClass('<small>Допустим таг</small>');
        $allowed->set_allowed_tags('<small>');
        $assert(trim($allowed->get_text()) === '<small>Допустим таг</small>', 'Legacy allowed_tags mode');
        $wap = new $plainClass();
        $wap->wapSafe = true;
        $wap->set('<a href="https://example.invalid/">WAP</a>');
        $snapshot['wap'] = $wap->get_text();
        $assert(strpos($snapshot['wap'], '{[{a href=') !== false, 'Legacy WAP mode');
        if (!$baseline) {
            foreach (array('', null, '0') as $input) {
                $assert(trim((new $richClass($input))->get_text()) === (string) $input, 'Empty/null/zero rich input');
                $plain = new $plainClass();
                $plain->set($input);
                $assert(trim($plain->get_text()) === (string) $input, 'Empty/null/zero plain input');
            }
        }
        if ($modern) {
            $value = (new $richClass("<span style=\"color:red\">Цветен\nред</span>"))->get_text();
            $assert(strpos($value, '[color=red]') !== false, 'Multiline style');
            $value = (new $richClass('<p style="color:red">Първи</p><p style="color:blue">Втори</p>'))->get_text();
            $assert(strpos($value, "[/color]\n\n[color=blue]") !== false, 'Styled paragraph boundaries');
            $value = (new $richClass("<pre style=\"color:red\">first\n    second</pre>"))->get_text();
            $assert(strpos($value, '[bQuote]') !== false && strpos($value, "\n") !== false, 'Styled PRE block');
            $html = '<p>C:\\temp\\file</p><p><em>Акцент</em> <ins>Добавено</ins> <del>Изтрито</del></p>';
            $value = (new $richClass($html))->get_text();
            foreach (array('C:\\temp\\file', '[i]Акцент[/i]', '[u]Добавено[/u]', '[s]Изтрито[/s]') as $part) {
                $assert(strpos($value, $part) !== false, 'Modern ' . $part);
            }
            $value = (new $richClass('<h2>Едно</h2><h2>Две</h2>'))->get_text();
            $assert(substr_count($value, '[h2]') === 2, 'Separate headings');
            $assert(substr_count($snapshot['pre'][0], '[bQuote]') === 2, 'Separate PRE blocks');
            $plain = new $plainClass();
            $assert($plain->convert2text('<p>&#1041; &#128522;</p>', true) === "Б 😊\n", 'Plain Unicode entities');
        }
        $html = '<table>';
        for ($i = 0; $i < 3000; $i++) $html .= '<tr><td>Продукт ' . $i . '</td><td>12.50</td></tr>';
        $html .= '</table><p>Край на документа</p>';
        $start = microtime(true);
        $large = (new $richClass($html))->get_text();
        $seconds = microtime(true) - $start;
        $assert(substr_count($large, '12.50') === 3000 && strpos($large, 'Продукт 2999') !== false, 'Large document rows');
        $assert(strpos($large, 'Край на документа') !== false, 'Large document end');
        $snapshot['large'] = hash('sha256', $large);

        return array('checks' => $checks, 'largeSeconds' => round($seconds, 4), 'snapshot' => $snapshot);
    }
}
