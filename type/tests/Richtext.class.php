<?php


/**
 * Клас  'type_Richtext' - Тип за форматиран (като BBCode) текст
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_tests_Richtext extends unit_Class
{
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function test_ToVerbal()
    {
        $rt = new type_Richtext();
        
        $sample1 = 'За: tasdasdasd';
        $sample2 = 'отНосНо: tasdasdasd';
        $sample3 = 'не трябва да има отНосНо: tasdasdasd';
        
        $res1 = $rt->toVerbal($sample1);
        $res2 = $rt->toVerbal($sample2);
        $res3 = $rt->toVerbal($sample3);
        
        UT::expectEqual(stripos(' '. $res1, '<b>' . $sample1 . '</b>'), true);
        UT::expectEqual(stripos(' '. $res2, '<b>' . $sample2 . '</b>'), true);
        UT::expectEqual(stripos(' '. $res3, '<b>' . $sample3 . '</b>'), false);
    }


    /**
     * Markdown заглавия, подравняване и отделни таблици в един текст.
     */
    public function test_MarkdownTables()
    {
        $rt = cls::get('type_Richtext');
        $input = "|Model|Input|Output|Note|\r\n| --- | ---: | :---: | :--- |\r\n|Example|10|20|Text|\r\n\r\n|Legacy|Value|\r\n";
        $html = $rt->replaceTables($input);

        UT::expectEqual(strpos($html, '<th style=\'text-align: left\'>Model</th>') !== false, true);
        UT::expectEqual(strpos($html, '<th style=\'text-align: right\'>Input</th>') !== false, true);
        UT::expectEqual(strpos($html, '<td style=\'text-align: right\'>10</td>') !== false, true);
        UT::expectEqual(strpos($html, '<td style=\'text-align: center\'>20</td>') !== false, true);
        UT::expectEqual(strpos($html, '<td style=\'text-align: left\'>Text</td>') !== false, true);
        UT::expectEqual(strpos($html, '---') === false, true);
        UT::expectEqual(substr_count($html, '<tr>'), 3);
        UT::expectEqual(strpos($html, '<tr><td>Legacy</td><td>Value</td></tr>') !== false, true);

        $html = $rt->replaceTables("|A|B\n|---|---:\n|x|2\n");
        UT::expectEqual(substr_count($html, '<th '), 2);
        UT::expectEqual(strpos($html, '<td style=\'text-align: right\'>2</td>') !== false, true);
    }


    /**
     * Обикновените редове и невалидните разделители запазват съдържанието си.
     */
    public function test_LegacyTables()
    {
        $rt = cls::get('type_Richtext');
        $html = $rt->replaceTables("Before\n|A|B|\n|x||\nAfter");
        UT::expectEqual($html, "\nBefore\n<div class='overflow-scroll'><table class='inlineRichTable listTable'><tr><td>A</td><td>B</td></tr><tr><td>x</td></tr></table></div>\nAfter");

        // Таблица в края на текста също затваря обвиващия div
        $html = $rt->replaceTables("Before\n|A|B|");
        UT::expectEqual(substr($html, -14), '</table></div>');

        $cases = array(
            "|A|B|\n|---|Text|\n" => '<tr><td>---</td><td>Text</td></tr>',
            "|A|B|\n|---:|\n" => '<tr><td>---:</td></tr>',
            "|A|B|\n|--|--:|\n" => '<tr><td>--</td><td>--:</td></tr>',
            "|A|B|\n|---||---:|\n" => '<tr><td>---</td><td></td><td>---:</td></tr>',
            "|A|B|\n|x|y|\n|---|---:|\n" => '<tr><td>---</td><td>---:</td></tr>',
            "|---|---:|\n" => '<tr><td>---</td><td>---:</td></tr>',
        );
        foreach ($cases as $input => $row) {
            $html = $rt->replaceTables($input);
            UT::expectEqual(strpos($html, $row) !== false, true);
            UT::expectEqual(strpos($html, '<th') === false, true);
            UT::expectEqual(strpos($html, 'text-align') === false, true);
        }
    }


    /**
     * Plain text режимът запазва оригиналния текст на таблицата.
     */
    public function test_PlainTextTables()
    {
        $rt = cls::get('type_Richtext');
        $input = "|Model|Value|\r\n|---|---:|\r\n|Example|10|";
        Mode::push('text', 'plain');
        try {
            UT::expectEqual($rt->replaceTables($input), $input);
        } finally {
            Mode::pop('text');
        }
    }


    /**
     * Незатворен или разместен BB таг не засяга HTML-а след текста
     */
    public function test_UnbalancedTags()
    {
        $rt = cls::get('type_Richtext');
        $render = function ($text) use ($rt) {
            $html = $rt->toVerbal($text);

            return ($html instanceof core_ET) ? $html->getContent() : (string) $html;
        };

        // Коректният текст не се променя
        UT::expectEqual($render('[b]a[/b] [bInfo]b[/bInfo]'), "<div class=\"richtext\"><b>a</b> <div class=\"richtext-message richtext-info\">b</div>\n\n</div>");

        UT::expectEqual($render('[b]удебелен'), "<div class=\"richtext\"><b>удебелен\n\n</b></div>");
        UT::expectEqual($render('текст[/bInfo] след'), "<div class=\"richtext\">текст след\n\n</div>");
        UT::expectEqual($render('[i]а [b]б[/i] в[/b]'), "<div class=\"richtext\"><i>а <b>б</b></i><b> в</b>\n\n</div>");

        // Блокът в клетката се затваря в нея, а затварящият таг след таблицата отпада
        $html = $render("|[bInfo]a|b|\n[/bInfo] след");
        UT::expectEqual(strpos($html, '<td><div class="richtext-message richtext-info">a</div></td>') !== false, true);
        UT::expectEqual(substr_count($html, '<div'), substr_count($html, '</div>'));

        // Затварящ таг в цитат може да затваря форматиране отвън - оставя се на браузъра
        UT::expectEqual($render('[b]Важно: [bQuote]цитат[/b][/bQuote] край'), "<div class=\"richtext\"><b>Важно: <div class='richtext-quote no-spell-check'>цитат</b></div> край\n\n</div>");

        // Скритият текст и картинката запазват HTML-а си
        UT::expectEqual((bool) preg_match("~<div class='clearfix21 richtextHide' id='hide\d+'>текст</div>~", $render('[hide=Покажи]текст[/hide]')), true);
        UT::expectEqual(strpos($render('[img=http://example.com/a.png]Надпис[/img]'), '<div><img src="http://example.com/a.png" alt="Надпис"><br><small>Надпис </small></div>') !== false, true);
    }


    /**
     * [color] и [bg] приемат само допустим CSS цвят
     */
    public function test_Colors()
    {
        // Цветовете от бутоните и празната стойност се показват както досега
        $cases = array(
            '[color]a[/color]' => '<span style="color:black">a</span>',
            '[bg]a[/bg]' => '<span style="background-color:black">a</span>',
        );
        foreach (array('blue', 'red', 'green', '#888') as $color) {
            $cases["[color={$color}]a[/color]"] = "<span style=\"color:{$color}\">a</span>";
        }
        foreach (array('yellow', 'lightgreen') as $color) {
            $cases["[bg={$color}]a[/bg]"] = "<span style=\"background-color:{$color}\">a</span>";
        }
        foreach (array('red', 'black') as $color) {
            $cases["[bg={$color}][color=white]a[/color][/bg]"] = "<span style=\"background-color:{$color}\"><span style=\"color:white\">a</span></span>";
        }

        $cases += array(
            '[color=#3c4043!important]a[/color]' => '<span style="color:#3c4043!important">a</span>',
            '[color=rgb(10, 20, 30)]a[/color]' => '<span style="color:rgb(10, 20, 30)">a</span>',
            '[bg=hsla(120,100%,50%,0.3)]a[/bg]' => '<span style="background-color:hsla(120,100%,50%,0.3)">a</span>',

            // Цветът свършва на кавичка или ';', а недопустимата стойност става черна
            '[color=#73747b"data-x="1]a[/color]' => '<span style="color:#73747b">a</span>',
            '[color=red" title="x]a[/color]' => '<span style="color:red">a</span>',
            '[bg=red;width:100px]a[/bg]' => '<span style="background-color:red">a</span>',
            "[color=red'x]a[/color]" => '<span style="color:black">a</span>',
            '[bg=url(x.png)]a[/bg]' => '<span style="background-color:black">a</span>',
        );

        $this->expectRendered($cases);
    }


    /**
     * [html] показва пречистен HTML
     */
    public function test_Html()
    {
        $this->expectRendered(array(
            // Форматирането, картинките и таблиците остават
            '[html]<div style="color: red;">Текст</div>[/html]' => '<div style="color:#FF0000;">Текст</div>',
            'Преди [html]<img src="http://example.com/q.png" alt="q" width="87" height="87" class="ticketPdf">[/html] след' => 'Преди <img src="http://example.com/q.png" alt="q" width="87" height="87" class="ticketPdf" /> след',
            '[html]<table border="1"><tr><td valign="top">a</td></tr></table>[/html]' => '<table border="1"><tr><td valign="top">a</td></tr></table>',

            // Атрибутите за събития и data-*, javascript: връзките и iframe отпадат
            '[html]<span onclick="f()" data-x="1">a</span><a href="javascript:void(0)">b</a><iframe src="https://example.com/"></iframe>[/html]' => '<span>a</span><a>b</a>',
            '[html]<script>window.qa = 1;</script><p data-id="sample">Text</p>[/html]' => '<p>Text</p>',

            // Незатворените тагове се затварят в блока
            '[html]<div><b>a[/html] b' => '<div><b>a</b></div> b',
        ));
    }


    /**
     * Само изрично доверени полета запазват CSS, JavaScript и data-*.
     */
    public function test_TrustedHtml()
    {
        $html = '<style>.qa td:nth-child(2) { color:red !important; } @media (max-width:600px) { .qa { padding:0; } }</style>'
            . '<div class="qa" data-id="sample"><button onclick="qa()">Text</button></div>'
            . '<script src="https://example.com/widget.js"></script><script>window.qa = 1 < 2;</script>';
        $this->expectRendered(array('[html]' . $html . '[/html]' => $html), 'richtext(trustedHtml=yes)');

        $plain = '[html]<p data-id="sample">Text</p><script>window.qa = 1;</script>[/html]';
        foreach (array('richtext', 'richtext(trustedHtml=no)', 'richtext(trustedHtml=1)') as $type) {
            $this->expectRendered(array($plain => '<p>Text</p>'), $type);
        }

        Mode::push('text', 'plain');
        try {
            $trusted = core_Type::getByName('richtext(trustedHtml=yes)')->toVerbal($plain);
            $regular = core_Type::getByName('richtext')->toVerbal($plain);
            UT::expectEqual((string) $trusted, (string) $regular);
        } finally {
            Mode::pop('text');
        }
    }


    /**
     * Сравнява рендирания текст с очаквания HTML в обвивката на ричтекста
     */
    protected function expectRendered($cases, $type = 'richtext')
    {
        $rt = core_Type::getByName($type);
        foreach ($cases as $text => $res) {
            $html = $rt->toVerbal($text);
            $html = ($html instanceof core_ET) ? $html->getContent() : (string) $html;
            UT::expectEqual($html, "<div class=\"richtext\">{$res}\n\n</div>");
        }
    }
    
    
    /**
     * Тест за парсира вътрешното URL
     */
    public function test_parseInternalUrl()
    {
        // Всеки елемент от масива е масив със стрингове, които ще се сравняват
        
        $pArr = array();
        $pArr[] = array('/L/S/1/m/xxx/', '/L/S/1/?m=xxx/', '/L/S/?id=1&m=xxx/', '/L/S/?m=xxx&id=1');
        $pArr[] = array('/L/S/?id=1', '/L/S/1');
        $pArr[] = array('/L/S/m/xxx', '/L/S/?m=xxx');
        
        foreach ($pArr as $p) {
            $parseArr = array();
            $key = '';
            foreach ($p as $v) {
                $vArr = type_Richtext::parseInternalUrl($v);
                ksort($vArr);
                $parseArr[] = $key = serialize($vArr);
            }
            
            foreach ($parseArr as $c) {
                UT::expectEqual($key, $c);
            }
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
    }
    
    
    /**
     * Преобразува текст, форматиран с мета тагове (BB) в HTML
     *
     * Преобразованията са следните:
     * o Новите редове ("\n") се заменят с <br/>
     * o Интервалите в началото на реда се заменят с &nbsp;
     * o BB таговете се заменят според значението си
     *
     * Таговете, които се поддържат са:
     *
     * o [b]...[/b],
     * [i]...[/i],
     * [u]...[/u],
     * [h1-4]...[/h1-4]
     * [hr] - както съответните HTML тагове
     * o [strike]...[/strike] - задраскан текст
     * o [color=#XXX]...[/color] - цвят на текста
     * o [bg=#XXX]...[/bg] - цвят на фона
     * o [img{=caption}]url[/img] - изображение с опционално заглавие
     * o [code{=syntax}]...[/code] - преформатиран текст с опционално езиково оцветяване
     * o [em={code}] - емотикони
     *
     * @param string $richtext
     *
     * @return string
     */
    public function toHtml($html)
    {
    }
    
    
    /**
     * Връща уникален стринг, който се използва за име на плейсхолдер
     */
    public function getPlace()
    {
    }
    
    
    /**
     * Обработва [html] ... [/html]
     */
    public function _catchHtml($match)
    {
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    public function _catchLi($match)
    {
    }
    
    
    /**
     * Вкарва текста който е в следната последователност:
     * \n и/или интервали \n или в началото[Главна буква][една или повече малки букви и или интервали и или големи букви]:[интервал][произволен текст]\n или край на текста
     * в болд таг на richText
     */
    public function _catchBold($match)
    {
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    public function _catchImage($match)
    {
    }
    
    
    /**
     * Заменя [gread=????] ... [/gread]
     */
    public function _catchGread($match)
    {
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    public function _catchCode($match)
    {
    }
    
    
    /**
     * Заменя елементите [link=?????]......[/link]
     */
    public function _catchLink($match)
    {
    }
    
    
    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към вътрешни URL
     *
     * @param string $url   URL, където трябва да сочи връзката
     * @param string $text  текст под връзката
     * @param string $place
     *
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalLink_($url, $title, $place, $rest)
    {
    }
    
    
    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към външни URL
     *
     * Може да бъде прихванат в плъгин на `type_Richtext` с on_AfterExternalLink()
     *
     * @param string $url   URL, където трябва да сочи връзката
     * @param string $text  текст под връзката
     * @param string $place
     *
     * @return string HTML елемент <a href="...">...</a>
     */
    public function externalLink_($url, $title, $place)
    {
    }
    
    
    /**
     * Заменя елементите [hide=?????]......[/hide]
     */
    public function _catchHide($match)
    {
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    public function _catchColor($match)
    {
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    public function _catchBg($match)
    {
    }
    
    
    /**
     * Замества [em=????] елементите
     */
    public function _catchEmoticons($match)
    {
    }
    
    
    /**
     * Обработва хедъри-те [h1..6] ... [/h..]
     */
    public function _catchHeaders($matches)
    {
    }
    
    
    /**
     * Прави субституция на хипервръзките
     */
    public function _catchUrls($html)
    {
    }
    
    
    /**
     * Конвертира вътрешен URL към подходящо HTML представяне.
     *
     * @param string $url
     * @param string $title
     *
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalUrl_($url, $title, $rest)
    {
    }
    
    
    /**
     * Конвертира въшнен URL към подходящо HTML представяне
     *
     * @param string $url
     * @param string $title
     * @param string HTML код
     */
    public function externalUrl_($url, $title)
    {
    }
    
    
    /**
     * Прави субституция на имейлите
     */
    public function _catchEmails($match)
    {
    }
    
    
    /**
     * Връща масив с html код, съответстващ на бутоните на Richedit компонента
     */
    public function getToolbar(&$attr)
    {
    }
    
    
    /**
     * Парсира вътрешното URL
     *
     * @param URL $res - Вътрешното URL, което ще парсираме
     *
     * @return array $params - Масив с парсираното URL
     */
    public static function parseInternalUrl($rest)
    {
    }
}
