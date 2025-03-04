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
     * Дефолтна имплементация на функцията `getTxtContent`
     *
     * @param core_Mvc $mvc
     * @param null|string $text
     * @param int $id
     * @param array $params
     * @return string|void
     */
    public static function on_AfterGetTxtContent($mvc, &$text, $id, $params = array())
    {
        $rec = $mvc->fetchRec($id);
        if(empty($text)) {
            Mode::set('ONLY_ATTACHED_FILES', true);

            // Рендиране на цялото представяне на документа в текстов вид
            Mode::push('renderForTxtExport', true);
            $docHtml = $mvc->getInlineDocumentBody($id, 'plain');
            Mode::pop('renderForTxtExport');

            $content = $docHtml->getContent();
            $content = str_replace(array('</td>', '</th>'), ' | ', $content);
            $string = strip_tags($content);
            $string = preg_replace("/\s*[\r\n]+\s*/", "\n", $string);

            $string = str_replace('&nbsp;', ' ', $string);
            $string = trim($string);

            $selectedFields = $mvc->selectFields();
            $selectedFields['-single'] = true;
            Mode::push('text', 'plain');
            $row = $mvc->recToVerbal($rec, $selectedFields);
            Mode::pop('text');

            // Допълване с антетката на документа
            $singleTitle = tr($mvc->singleTitle);
            $startStr = tr('Документ') . ": {$singleTitle} {$mvc->getHandle($id)}";
            if(!in_array($rec->createdBy, array(core_Users::SYSTEM_USER, core_Users::ANONYMOUS_USER))){
                $createdName = core_Lg::transliterate(core_Users::fetchField($rec->createdBy, 'names'));
                $startStr .= " " . tr('създаден от||created by') . " {$row->createdBy} ({$createdName})";
            }
            $startStr .= " " . tr('в състояние') . " {$row->state}" . "\n";

            $string = $startStr . $string;

            // Кои са прикачените файлове + текстовото им съдържание, ако имат
            if($params['addAttachedTextFiles']){
                Mode::push('text', 'plain');
                $linkedFiles = $mvc->getLinkedFiles($rec);
                $string .= fileman_Indexes::getShortTextSummary($linkedFiles);
                Mode::pop('text');
            }

            $text = $string;
        }

        $mvc->invoke('AfterAfterGetTxtExport', array(&$text, $rec, $params));
    }
}