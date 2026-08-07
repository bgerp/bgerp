<?php


/**
 * Плъгин за експорт на документи в маркдаун формат подходящ за ЛЛМ.
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author   Mustafa Mustafov <mmustafov084@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_LlmExportable extends core_Plugin
{

    /**
     * Рендира документа в LLM-четим маркдаун формат.
     *
     * @param core_Mvc $mvc
     * @param null|string $text
     * @param int $id
     * @param array $params
     * @param bool $forLlm
     */
    public static function on_AfterGetLlmContent($mvc, &$text, $id, $params = array(), $forLlm = false)
    {
        if (!empty($text)) return;

        $rec = $mvc->fetchRec($id);

        // Рендираме веднъж нормалния `plain` HTML, без renderForAI, за да останат
        // групиращите редове, междинните суми и останалата таблична структура.
        $content = doc_plg_TxtExportable::renderDocumentHtml($mvc, $id);
        $content = self::prepareHtmlForMarkitdown($content);

        $string = '';
        $useMarkitdown = core_Packs::isInstalled('markitdown') && cls::load('markitdown_Converter', true);

        // MarkItDown трябва да получи истинската HTML таблица. В renderForAI режим
        // core_TableView вече я е превърнал в pipe-текст и colspan/rowspan контекстът е загубен.
        if ($useMarkitdown) {
            $string = markitdown_Converter::convertHtml($content);
        }

        // При липсващ пакет, програма или резултат вътрешният конвертор получава
        // същия пълен HTML. Не рендираме повторно с renderForAI, защото някои
        // справки пропускат групиращите записи още при създаването му.
        if ($string === '') {
            $string = self::convertHtmlToLlmMarkdown($content);
        }
        $row = doc_plg_TxtExportable::getVerbalRow($mvc, $rec);

        $authorName = doc_plg_TxtExportable::getAuthorName($mvc, $rec);
        $text = doc_plg_TxtExportable::buildInfoHeader($mvc, $id, $row, $authorName) . $string;
        $text = cms_GalleryRichTextPlg::replaceImageTagsWithFileTag($text);

        $mvc->invoke('AfterAfterGetLlmExport', array(&$text, $rec, $params));
    }


    /**
     * Подготвя HTML от `plain` режима за MarkItDown чрез allowlist на структурните тагове.
     *
     * Запазва текста, таблиците и сумите, но премахва линкове, изображения и визуално
     * форматиране, което иначе се превръща в URL-и, ![] и слепени *** маркери.
     *
     * @param string $html
     *
     * @return string
     */
    protected static function prepareHtmlForMarkitdown($html)
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $html = preg_replace('/<(script|style|noscript|template)\b[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/&#(?:xa0|160);|&nbsp;|\xc2\xa0/i', ' ', $html);

        // Както при вътрешния fallback, от изображенията запазваме само смислен tooltip.
        // Декоративни и sort икони без title изчезват, вместо да стават ![](URL).
        $html = preg_replace_callback('/<img\b[^>]*>/i', function ($match) {
            if (!preg_match('/\btitle\s*=\s*(["\'])(.*?)\1/is', $match[0], $titleMatch)) return '';

            return htmlspecialchars(html_entity_decode($titleMatch[2], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        }, $html);

        // Съседните inline елементи често са визуално разделени чрез CSS. След като
        // махнем презентационните тагове, оставяме интервал между тях, за да не се слепят.
        $html = preg_replace('/(<\/(?:a|b|strong|span|i|em)>)(?=\s*<)/i', '$1 ', $html);

        // `plain` е режим за рендиране, а не краен MIME тип: резултатът все още е HTML.
        // Оставяме само структурните тагове, нужни на MarkItDown. strip_tags() разопакова
        // линкове и визуално форматиране до текста им, а таблиците и colspan/rowspan остават.
        $structuralTags = '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col>'
            . '<h1><h2><h3><h4><h5><h6><p><div><br><hr><ul><ol><li><dl><dt><dd>'
            . '<blockquote><pre><code>';

        return strip_tags($html, $structuralTags);
    }


    /**
     * Конвертира HTML съдържанието на документ в маркдаун формат подходящ за ЛЛМ.
     *
     * Използва DOMDocument за правилно парсиране на вложен HTML — независимо от CSS класове.
     * Детайлните таблици са вече маркдаун текст (plain text с |) и се запазват непроменени.
     */
    public static function convertHtmlToLlmMarkdown($html)
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Нормализираме nbsp към обикновен интервал още преди парсването - иначе декоративни
        // nbsp-разделители (напр. <div>&nbsp;</div>) минават trim()-овете по-долу като непразно
        // съдържание и се превръщат в самостоятелни "празни" редове.
        $html = preg_replace('/&#(?:xa0|160);|&nbsp;|\xc2\xa0/i', ' ', $html);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        $result = $body ? self::llmDomWalk($body, false) : strip_tags($html);

        $result = str_replace("\xc2\xa0", ' ', $result);
        $result = preg_replace('/[ \t]+/', ' ', $result);

        // Разделящ HTML whitespace между съседни блокови елементи (напр. между </table> и
        // следващ <div>, или между </div> и "гол" <span>) остава като самостоятелен ред само
        // с интервал, защото не достига прага "3+" на колапса по-долу. Изчистваме водещите/
        // крайните интервали на всеки ред, за да не се показват такива "призрачни" редове.
        $result = preg_replace('/^[ \t]+|[ \t]+$/m', '', $result);

        $result = preg_replace('/\n{2,}/', "\n", $result);

        return trim($result);
    }


    /**
     * Рекурсивен обход на DOM дърво за генериране на LLM-четим текст.
     *
     * @param DOMNode $node
     * @param bool $inCell  true когато сме вътре в <td> стойност — таблиците се изглаждат до текст
     */
    protected static function llmDomWalk(DOMNode $node, $inCell)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $tag = strtolower($node->nodeName);

        if (in_array($tag, array('script', 'style'))) {
            return '';
        }
        if ($tag === 'br') {
            return $inCell ? ' ' : "\n";
        }

        if (preg_match('/^h[1-6]$/', $tag)) {
            if ($inCell) {
                return self::llmNodeText($node);
            }
            $level = min((int) $tag[1] + 1, 4);
            $text = trim(self::llmNodeText($node));
            return $text ? "\n" . str_repeat('#', $level) . " {$text}\n" : '';
        }

        if (!$inCell && in_array($tag, array('div', 'p', 'li'))) {
            $inner = '';
            foreach ($node->childNodes as $child) {
                $inner .= self::llmDomWalk($child, false);
            }
            $inner = trim($inner);
            // Нормализираме whitespace само ако съдържанието е inline (без вложени структурни \n)
            if ($inner !== '' && strpos($inner, "\n") === false) {
                $inner = preg_replace('/\s+/', ' ', $inner);
            }
            return $inner ? "\n" . $inner . "\n" : '';
        }

        if ($tag === 'hr' && !$inCell) {
            return "\n";
        }

        if ($tag === 'table') {
            // Вложена таблица вътре в клетка → изглаждаме до inline текст
            if ($inCell) {
                return ' ' . trim(preg_replace('/\s+/', ' ', self::llmNodeText($node))) . ' ';
            }

            // Обхождаме редовете на таблицата
            $result = '';
            // Заглавия на колоните - научени от първия "сложен" ред от кратки прости етикети
            // (напр. Получател/Доставчик), за да могат следващите редове със същия брой
            // видими клетки да се надписват по колона, без да знаем нищо конкретно за тях.
            $columnHeaders = null;
            foreach (self::llmTableRows($node) as $row) {
                $cells = self::llmRowCells($row);
                if (!$cells) continue;

                if (count($cells) === 1) {
                    if ($cells[0]->hasAttribute('colspan')) {
                        $text = trim(preg_replace('/\s+/', ' ', self::llmNodeText($cells[0])));
                        // Групово заглавие само ако е кратък inline текст (≤30 символа)
                        // По-дълъг текст или block-level съдържание → plain text ред
                        if ($text && strlen($text) <= 30 && self::llmCellIsSimpleCaption($cells[0])) {
                            $result .= "\n### {$text}\n";
                        } elseif ($text) {
                            $result .= $text . "\n";
                        }
                    } else {
                        // Клетка-обвивка (layout wrapper) — рекурсираме вътре
                        $result .= self::llmDomWalk($cells[0], false);
                    }
                    continue;
                }

                // Клетки с rowspan > 1 са layout елементи (напр. центровата колона
                // тип/номер на документа) — съдържанието им идва отделно, пропускаме ги
                // навсякъде по-долу, за да не разместват подредбата на колоните.
                $visibleCells = array();
                foreach ($cells as $cell) {
                    if ((int) $cell->getAttribute('rowspan') > 1) continue;
                    $visibleCells[] = $cell;
                }
                if (count($visibleCells) < 2) {
                    foreach ($visibleCells as $cell) {
                        $cellOut = trim(self::llmDomWalk($cell, false));
                        if ($cellOut) $result .= $cellOut . "\n";
                    }
                    continue;
                }

                // Търсим първата проста клетка, чийто текст завършва на ':' — това е истинският
                // caption. По-ранни клетки (ако има) са само контекст/квалификатор пред него
                // (напр. "Ставка" пред "Данъчна основа:").
                $capIdx = null;
                foreach ($visibleCells as $i => $cell) {
                    if (!self::llmCellIsSimpleCaption($cell)) break;
                    $text = trim(preg_replace('/\s+/', ' ', self::llmNodeText($cell)));
                    if ($text !== '' && substr($text, -1) === ':') {
                        $capIdx = $i;
                        break;
                    }
                }

                $value = null;
                if ($capIdx !== null && $capIdx < count($visibleCells) - 1) {
                    $valueParts = array();
                    for ($i = $capIdx + 1; $i < count($visibleCells); $i++) {
                        $part = trim(preg_replace('/\s+/', ' ', self::llmDomWalk($visibleCells[$i], true)));
                        if ($part !== '') $valueParts[] = $part;
                    }
                    $value = implode(' ', $valueParts);
                }

                // Ако "стойността" сама завършва на ':', това всъщност са два паралелни
                // етикета (напр. "Получил:" / "Съставил:"), а не caption:value чифт.
                if ($value !== null && $value !== '' && substr($value, -1) !== ':') {
                    // Ред ключ-стойност: [контекст] **Caption:** Value
                    $caption = rtrim(trim(preg_replace('/\s+/', ' ', self::llmNodeText($visibleCells[$capIdx]))), ':');
                    $prefix = '';
                    if ($capIdx > 0) {
                        $prefixParts = array();
                        for ($i = 0; $i < $capIdx; $i++) {
                            $t = trim(preg_replace('/\s+/', ' ', self::llmNodeText($visibleCells[$i])));
                            if ($t !== '') $prefixParts[] = $t;
                        }
                        if ($prefixParts) $prefix = implode(' ', $prefixParts) . ' ';
                    }
                    if ($caption && $value) {
                        $result .= "{$prefix}**{$caption}:** {$value}\n";
                    }
                    continue;
                }

                // Сложен ред (адресни блокове, паралелни заглавия, summary) — всяка клетка
                // самостоятелно. Ако все още нямаме заглавия на колони и този ред е съставен
                // само от кратки прости етикети, приемаме го за заглавен ред на колоните.
                $justSetHeaders = false;
                if ($columnHeaders === null) {
                    $headerTexts = array();
                    $isHeaderRow = true;
                    foreach ($visibleCells as $cell) {
                        $t = trim(preg_replace('/\s+/', ' ', self::llmNodeText($cell)));
                        if ($t === '' || strlen($t) > 30 || !self::llmCellIsSimpleCaption($cell)) {
                            $isHeaderRow = false;
                            break;
                        }
                        // rtrim(':') - за да не се получи двойно двоеточие при префикса по-долу
                        $headerTexts[] = rtrim($t, ':');
                    }
                    if ($isHeaderRow) {
                        $columnHeaders = $headerTexts;
                        $justSetHeaders = true;
                    }
                }
                $useHeaders = !$justSetHeaders && $columnHeaders !== null && count($columnHeaders) === count($visibleCells);

                foreach ($visibleCells as $i => $cell) {
                    $cellOut = trim(self::llmDomWalk($cell, false));
                    $cellOut = preg_replace('/(\n[ \t]*){3,}/', "\n\n", $cellOut);
                    if ($cellOut === '') continue;
                    if ($useHeaders) $cellOut = "{$columnHeaders[$i]}: {$cellOut}";
                    $result .= $cellOut . "\n";
                }
            }
            return $result ? "\n" . $result : '';
        }

        $result = '';
        foreach ($node->childNodes as $child) {
            $result .= self::llmDomWalk($child, $inCell);
        }
        // Елемент без видим текст (напр. <img>), чието единствено съдържание е в
        // title-атрибута (честа употреба за tooltip-и в bgERP - endTooltip/frontTooltip
        // икони) - вадим title-а, за да не се губи информацията при експорта.
        if (trim($result) === '' && $node instanceof DOMElement && $node->getAttribute('title') !== '') {
            $result = $node->getAttribute('title');
        }
        return $result;
    }


    /** Извлича чист текст от DOM възел (без HTML тагове, <br> → интервал) */
    protected static function llmNodeText(DOMNode $node)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }
        $tag = strtolower($node->nodeName);
        if (in_array($tag, array('script', 'style'))) {
            return '';
        }
        if ($tag === 'br') {
            return ' ';
        }
        $t = '';
        foreach ($node->childNodes as $c) {
            $t .= self::llmNodeText($c);
        }
        if (trim($t) === '' && $node instanceof DOMElement && $node->getAttribute('title') !== '') {
            $t = $node->getAttribute('title');
        }
        return $t;
    }


    /** Връща директните <tr> редове на таблица (обхожда и tbody/thead/tfoot) */
    protected static function llmTableRows(DOMNode $table)
    {
        $rows = array();
        foreach ($table->childNodes as $c) {
            if ($c->nodeType !== XML_ELEMENT_NODE) continue;
            $t = strtolower($c->nodeName);
            if ($t === 'tr') {
                $rows[] = $c;
            } elseif (in_array($t, array('tbody', 'thead', 'tfoot'))) {
                foreach ($c->childNodes as $tr) {
                    if ($tr->nodeType === XML_ELEMENT_NODE && strtolower($tr->nodeName) === 'tr') {
                        $rows[] = $tr;
                    }
                }
            }
        }
        return $rows;
    }


    /** Връща директните <td>/<th> клетки на ред */
    protected static function llmRowCells(DOMNode $row)
    {
        $cells = array();
        foreach ($row->childNodes as $c) {
            if ($c->nodeType === XML_ELEMENT_NODE && in_array(strtolower($c->nodeName), array('td', 'th'))) {
                $cells[] = $c;
            }
        }
        return $cells;
    }


    /**
     * Проверява дали клетката е "прост" caption: без block-level елементи или вложени таблици.
     * Само inline съдържание (<b>, <span>, <a>, <small>, текст) → true.
     */
    protected static function llmCellIsSimpleCaption(DOMNode $cell)
    {
        static $blockTags = array('div', 'p', 'ul', 'ol', 'li', 'table', 'blockquote', 'pre', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');
        foreach ($cell->childNodes as $c) {
            if ($c->nodeType !== XML_ELEMENT_NODE) continue;
            if (in_array(strtolower($c->nodeName), $blockTags)) return false;
            // Рекурсивно за inline елементи (напр. <span><table>)
            if (!self::llmCellIsSimpleCaption($c)) return false;
        }
        return true;
    }
}
