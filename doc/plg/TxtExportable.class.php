<?php


/**
 * Плъгин позволяващ експорт на документи в текстов вид
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_TxtExportable extends core_Plugin
{

    /**
     * Дефолтна имплементация на функцията `getTxtContent` за plain text експорт.
     *
     * @param core_Mvc $mvc
     * @param null|string $text
     * @param int $id
     * @param array $params
     * @return string|void
     */
    public static function on_AfterGetTxtContent($mvc, &$text, $id, $params = array())
    {
        if (!empty($text)) return;

        $rec = $mvc->fetchRec($id);

        $content = self::renderDocumentHtml($mvc, $id);

        // Премахваме <style> и <script> блоковете с тяхното съдържание преди strip_tags,
        // защото strip_tags маха тага, но оставя CSS/JS текста в изхода
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $content);
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $content);

        $string = strip_tags($content);
        $string = preg_replace("/:\s*[\r\n]\s*/", ": ", $string);
        $string = preg_replace("/\s*[\r\n]+\s*/", "\n", $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = trim($string);

        $row = self::getVerbalRow($mvc, $rec);
        $authorName = self::getAuthorName($mvc, $rec);

        $text = self::buildInfoHeader($mvc, $id, $row, $authorName) . $string;

        // Кои са прикачените файлове + текстовото им съдържание, ако имат
        $linkedFiles = $mvc->getLinkedFiles($rec);
        $text .= fileman_Indexes::getShortTextSummary($linkedFiles);

        $mvc->invoke('AfterAfterGetTxtExport', array(&$text, $rec, $params));
    }


    /**
     * Рендира тялото на документа и връща HTML стринга.
     * Управлява Mode::push/pop за renderForTxtExport и forceDownload.
     * Извикващият е отговорен за допълнителни режими (напр. renderForAI).
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @return string
     */
    public static function renderDocumentHtml($mvc, $id)
    {
        Mode::set('ONLY_ATTACHED_FILES', true);
        Mode::push('renderForTxtExport', true);
        Mode::push('forceDownload', true);
        $docHtml = $mvc->getInlineDocumentBody($id, 'plain');
        Mode::pop('forceDownload');
        Mode::pop('renderForTxtExport');
        return $docHtml->getContent();
    }


    /**
     * Връща вербалния ред на записа с Mode 'text'='plain'.
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return stdClass
     */
    public static function getVerbalRow($mvc, $rec)
    {
        $selectedFields = $mvc->selectFields();
        $selectedFields['-single'] = true;
        Mode::push('text', 'plain');
        $row = $mvc->recToVerbal($rec, $selectedFields);
        Mode::pop('text');
        return $row;
    }


    /**
     * Извлича изписаното име на автора на документа или null ако е системен потребител.
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return string|null
     */
    public static function getAuthorName($mvc, $rec)
    {
        if ($rec->createdBy == core_Users::SYSTEM_USER) return null;
        $docRow = $mvc->getDocumentRow($rec->id);
        if (!empty($docRow->authorName)) {
            return strip_tags((string) $docRow->authorName);
        }
        $createdName = core_Users::fetchField($rec->createdBy, 'names');
        $createdName = core_Lg::transliterate($createdName);
        return strip_tags((string) $docRow->author) . " ({$createdName})";
    }


    /**
     * Изгражда хедъра с метаданни на документа: заглавие, хендъл, автор, състояние.
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $row    вербален ред (трябва да съдържа $row->state)
     * @param string|null $authorName
     * @return string
     */
    public static function buildInfoHeader($mvc, $id, $row, $authorName)
    {
        $singleTitle = tr($mvc->singleTitle);
        $state = trim(html_entity_decode(strip_tags((string) $row->state), ENT_QUOTES, 'UTF-8'));

        $str = "## {$singleTitle} #{$mvc->getHandle($id)}\n";
        if ($authorName !== null) {
            $str .= tr('Създаден от||Created by') . ": {$authorName} | ";
        }
        $str .= tr('Състояние||State') . ": {$state}\n\n";

        return $str;
    }
}
