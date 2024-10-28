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
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        $mvc->declareInterface('export_TxtExportIntf');
    }


    /**
     * Дефолтна имплементация на функцията `getTxtContent`
     *
     * @param core_Mvc $mvc
     * @param null|string $text
     * @param $id
     * @return string|void
     */
    public static function on_AfterGetTxtContent($mvc, &$text, $id)
    {
        if(!empty($text)) return '';
        $rec = $mvc->fetchRec($id);

        // Рендиране на цялото представяне на документа в текстов вид
        $docHtml = $mvc->getInlineDocumentBody($id, 'plain');
        $string = strip_tags($docHtml->getContent());
        $string = preg_replace("/\s*[\r\n]+\s*/", "\n", $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = trim($string);

        $selectedFields = $mvc->selectFields();
        $selectedFields['-single'] = true;
        Mode::push('text', 'plain');
        $row = $mvc->recToVerbal($rec, $selectedFields);
        Mode::pop('text');

        // Допълване с антетката на документа
        $createdName = core_Users::fetchField($rec->createdBy, 'names');
        $singleTitle = tr($mvc->singleTitle);
        $startStr = tr('ДОКУМЕНТ') . ": {$singleTitle} {$mvc->getHandle($id)}";
        $startStr .= " " . tr('създаден от||created by') . " {$row->createdBy} ({$createdName})";
        $startStr .= " " . tr('в състояние') . " {$row->state}" . "\n";

        $string = $startStr . $string;
        $text = $string;
    }


    /**
     * Връща текстово представяне на нишката на документите с интерфейс 'export_TxtExportIntf'
     *
     * @param int $threadId
     * @return string $res
     */
    public static function getThreadTxt($threadId)
    {
        $res = "";
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#threadId = {$threadId} AND #state != 'rejected'");
        while($cRec = $cQuery->fetch()){
            $Document = doc_Containers::getDocument($cRec->id);
            if($Document->haveInterface('export_TxtExportIntf')){
                $txtExportIntf = cls::getInterface('export_TxtExportIntf', $Document->getInstance());
                $res .= '======================================================' . "\n";
                $res .= $txtExportIntf->getTxtContent($Document->that);
            }
        }

        return $res;
    }
}