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

        Mode::push('renderForAI', true);
        $content = doc_plg_TxtExportable::renderDocumentHtml($mvc, $id);
        $string = self::convertHtmlToLlmMarkdown($content);
        $row = doc_plg_TxtExportable::getVerbalRow($mvc, $rec);
        Mode::pop('renderForAI');

        $authorName = doc_plg_TxtExportable::getAuthorName($mvc, $rec);
        $text = doc_plg_TxtExportable::buildInfoHeader($mvc, $id, $row, $authorName) . $string;
        $text = cms_GalleryRichTextPlg::replaceImageTagsWithFileTag($text);

        $mvc->invoke('AfterAfterGetLlmExport', array(&$text, $rec, $params));
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

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        $result = $body ? self::llmDomWalk($body, false) : strip_tags($html);

        $result = str_replace("\xc2\xa0", ' ', $result);
        $result = preg_replace('/[ \t]+/', ' ', $result);
        $result = preg_replace('/(\n[ \t]*){3,}/', "\n\n", $result);

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
                } else {
                    if (self::llmCellIsSimpleCaption($cells[0]) && self::llmCellIsSimpleCaption($cells[1])) {
                        // Ред ключ-стойност: **Caption:** Value
                        // rtrim(':') премахва двойно двоеточие ако caption вече завършва с ':'
                        $caption = rtrim(trim(preg_replace('/\s+/', ' ', self::llmNodeText($cells[0]))), ':');
                        $value   = trim(preg_replace('/\s+/', ' ', self::llmDomWalk($cells[1], true)));
                        // При 3+ колони — добавяме и останалите
                        for ($ci = 2; $ci < count($cells); $ci++) {
                            $extra = trim(preg_replace('/\s+/', ' ', self::llmNodeText($cells[$ci])));
                            if ($extra) $value .= " {$extra}";
                        }
                        if ($caption && $value) {
                            $result .= "**{$caption}:** {$value}\n";
                        }
                    } else {
                        // Сложен ред (адресни блокове, summary) — всяка клетка самостоятелно.
                        // Клетки с rowspan > 1 са layout елементи (напр. центровата колона
                        // тип/номер на документа) — съдържанието им идва от $startStr, пропускаме.
                        foreach ($cells as $cell) {
                            $rowspan = (int) $cell->getAttribute('rowspan');
                            if ($rowspan > 1) continue;
                            $cellOut = trim(self::llmDomWalk($cell, false));
                            $cellOut = preg_replace('/(\n[ \t]*){3,}/', "\n\n", $cellOut);
                            if ($cellOut) $result .= $cellOut . "\n";
                        }
                    }
                }
            }
            return $result ? "\n" . $result : '';
        }

        $result = '';
        foreach ($node->childNodes as $child) {
            $result .= self::llmDomWalk($child, $inCell);
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
