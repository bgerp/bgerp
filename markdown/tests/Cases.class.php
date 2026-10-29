<?php


/**
 * Повторяеми проверки на Markdown адаптера със синтетични текстове
 *
 * @category  tests
 * @package   markdown
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class markdown_tests_Cases
{
    /** Пример за директния конвертор и за [md] в реален Richtext. */
    public static function preview()
    {
        return "# Проверка на Markdown\n\nКирилица: **важен текст** и *акцент*.\n\n"
            . "Продукт | Количество | Сума\n:--- | ---: | ---:\nХартия | 2 | 12.50\nПапка | 1 | 3.40\nОбщо | 3 | 15.90\n\n"
            . "- Първи ред\n- Втори ред\n\n1. Първа стъпка\n2. Втора стъпка\n\n"
            . "[Примерен линк](https://example.invalid/?a=1&b=2)\n\n"
            . "~~~\nif (a < 2) {\n    echo \"кирилица\";\n}\n~~~\n\n"
            . "Бележка[^test].\n\n[^test]: Текст на бележката.\n\n"
            . "Термин\n: Определение на термина.\n\n<div>Вграден HTML</div>\n";
    }


    /** Проверява реалния framework и инсталирания Richtext плъгин. */
    public static function runRichtext()
    {
        $type = cls::get('type_Richtext');
        $input = '[md]' . self::preview() . "\n![Картинка](https://example.invalid/a b.png)\n[/md]";
        $html = (string) $type->toHtml($input);
        expect(strpos($html, '<h1>Проверка на Markdown</h1>') !== false, 'Installed Markdown plugin');
        expect(strpos($html, '<strong>важен текст</strong>') !== false, 'Richtext Cyrillic and emphasis');
        expect(strpos($html, 'src="https://example.invalid/a%20b.png"') !== false, 'Richtext image URL spaces');
        expect(strpos($html, '<div>Вграден HTML</div>') === false && strpos($html, '&lt;div>Вграден HTML&lt;/div>') !== false, 'Richtext escapes embedded HTML');
        expect(strpos($html, 'href="https://example.invalid/?a=1&amp;b=2"') !== false, 'Richtext URL entities');
        $checks = 5;
        if (markdown_Setup::get('VERSION') !== '1.0.1') {
            expect(strpos($html, '<table>') !== false && strpos($html, '15.90') !== false, 'Richtext table and total');
            expect(strpos($html, 'id="fn:test"') !== false, 'Richtext footnotes');
            $checks += 2;
        }
        if (markdown_Setup::get('VERSION') === '2.0.0') {
            $html = (string) $type->toHtml("[md]```php\necho 'код';\n```\n\n`inline`[/md]");
            expect(strpos($html, '<pre><code class="php">') !== false, 'Richtext code fence');
            expect(strpos($html, '<code>inline</code>') !== false, 'Richtext inline code');
            $checks += 2;
        }

        return $checks;
    }


    /** Проверява резултата; snapshot позволява сравнение със стария адаптер. */
    public static function run($renderer = 'markdown_Render', $baseline = false)
    {
        $version = markdown_Setup::get('VERSION');
        $extra = $version !== '1.0.1';
        $modern = $version === '2.0.0';
        $checks = 0;
        $snapshot = array();
        $assert = function ($condition, $message) use (&$checks) {
            expect($condition, $message);
            $checks++;
        };
        $cases = array(
            'empty' => array('', array()),
            'cyrillic' => array("# Заглавие\n\n**Български** и *акцент*.", array('<h1>Заглавие</h1>', '<strong>Български</strong>', '<em>акцент</em>')),
            'lists' => array("- Първи\n- Втори\n\n1. Едно\n2. Две", array('<ul>', '<li>Първи</li>', '<ol>', '<li>Две</li>')),
            'quotes' => array("> Цитат\n\nСлед него", array('<blockquote>', '<p>Цитат</p>', '<p>След него</p>')),
            'links' => array('[Линк](https://example.invalid/?a=1&b=2 "Надпис")', array('href="https://example.invalid/?a=1&amp;b=2"', 'title="Надпис"', '>Линк</a>')),
            'image' => array('![Снимка](https://example.invalid/a%20b.png)', array('src="https://example.invalid/a%20b.png"', 'alt="Снимка"')),
            'reference' => array("[Линк][ref]\n\n[ref]: https://example.invalid/", array('href="https://example.invalid/"')),
            'code' => array("    <b>кирилица</b>\n\n`a < b`", array('<pre><code>&lt;b&gt;кирилица&lt;/b&gt;', '<code>a &lt; b</code>')),
            'html' => array('<div>Вграден <b>HTML</b></div>', array('<div>Вграден <b>HTML</b></div>')),
            'entities' => array('A & B &amp; C', array('A &amp; B &amp; C')),
            'table' => array("Име | Сума\n:--- | ---:\nПродукт | 12.50\nОбщо | 12.50", $extra ? array('<table>', '<th align="right">Сума</th>', '<td align="right">12.50</td>') : array('Име | Сума')),
            'footnote' => array("Текст[^a].\n\n[^a]: Бележка.", $extra ? array('class="footnotes"', 'id="fn:a"', 'Бележка.') : array('Текст<a href="Бележка.">^a</a>')),
            'definition' => array("Термин\n: Описание", $extra ? array('<dl>', '<dt>Термин</dt>', '<dd>Описание</dd>') : array('Термин')),
            'abbreviation' => array("HTML текст\n\n*[HTML]: Hyper Text", $extra ? array('<abbr title="Hyper Text">HTML</abbr>') : array('HTML текст')),
            'tilde-code' => array("~~~\n<b>код</b>\n~~~", $extra ? array('<pre><code>&lt;b&gt;код&lt;/b&gt;') : array('~~~')),
        );
        foreach ($cases as $name => $case) {
            $html = $renderer::Convert($case[0]);
            $snapshot[$name] = $html;
            foreach ($case[1] as $part) {
                $assert(strpos($html, $part) !== false, $name . ': ' . $part);
            }
        }
        $assert(trim($snapshot['empty']) === '', 'Empty document');
        $assert($renderer::Convert('Ново съдържание') === "<p>Ново съдържание</p>\n", 'Parser state does not leak');
        if (!$baseline) {
            $assert($renderer::Convert(null) === $snapshot['empty'], 'Null document');
        }
        if ($modern) {
            $html = $renderer::Convert("```php\necho 'тест';\n```\n\n[Линк](https://example.invalid/){#link .custom data-test=yes}\n\n<details><summary>Резюме</summary>Текст</details>");
            $assert(strpos($html, '<pre><code class="php">') !== false, 'Backtick code fence and language');
            $assert(strpos($html, 'id="link"') !== false && strpos($html, 'class="custom"') !== false, 'Link attributes');
            $assert(strpos($html, 'data-test="yes"') !== false, 'Custom attribute');
            $assert(strpos($html, '<p><details>') === false, 'HTML5 block');
            $assert(strpos($snapshot['footnote'], 'role="doc-noteref"') !== false, 'Accessible footnotes');
        }
        $large = "Име | Сума\n--- | ---:\n";
        for ($i = 0; $i < 3000; $i++) {
            $large .= "Продукт $i | 12.50\n";
        }
        $large .= "\nКрай на документа\n";
        $start = microtime(true);
        $html = $renderer::Convert($large);
        $seconds = microtime(true) - $start;
        $assert(strpos($html, 'Продукт 2999') !== false && strpos($html, 'Край на документа') !== false, 'Large document is complete');
        $assert(substr_count($html, '<tr>') === ($extra ? 3001 : 0), 'Large table row count');
        $snapshot['large'] = hash('sha256', $html);
        $snapshot['license'] = hash('sha256', $renderer::Convert(file_get_contents(dirname(__DIR__, 2) . '/LICENSE.md')));

        return array('checks' => $checks, 'largeSeconds' => round($seconds, 4), 'snapshot' => $snapshot);
    }
}
