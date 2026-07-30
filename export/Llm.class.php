<?php


/**
 * Експортиране на документи като текстов файл за изкуствен интелект
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Llm extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като текстов файл за изкуствен интелект';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'export_ExportTypeIntf';


    /**
     * Проверка дали може да се използва експорта
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        $res = export_Export::canUseExport($clsId, $objId);
        if ($res) {
            if (!cls::haveInterface('export_LlmExportIntf', $clsId)) return false;
        }

        return $res;
    }


    /**
     * Връща заглавието на експорта
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'Файл за LLM';
    }


    /**
     * Извършва експорта и добавя бутон за сваляне
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $Cls = cls::get($clsId);
        $rec = $Cls->fetchRec($objId);
        expect($rec);
        $threadId = $rec->threadId ?? null;
        expect($threadId);
        $firstDocInThread = doc_Threads::getFirstDocument($threadId);

        $whole = ($form->rec->exportWholeThread ?? null) === 'yes';
        if (($rec->id ?? null) == ($firstDocInThread->that ?? null) && $whole) {
            $threadRec = doc_Threads::fetch($threadId);
            expect($threadRec);
            $threadName = doc_Threads::getThreadTitle($threadRec->id);
            $threadName = ai_utility_Helper::cleanText($threadName);
            $threadName = str_replace('"', '', $threadName);
            $threadText = "Нишка '{$threadName}' с id {$threadRec->id}" . "\n";
        
            $params = array('addAttachedTextFilesAsRichText' => true);
            $threadText .= doc_Threads::getAsText($threadRec->id, $params, true);
            $fileName = $Cls->getHandle($threadId) . '_LLM_Export.txt';
            $fileHnd = fileman::absorbStr($threadText, 'exportFiles', $fileName);
        } else{
            $Impl = cls::getInterface('export_LlmExportIntf', $Cls);
            $txtContent = $Impl->getLlmContent($objId, array(), true);   
            $fileHnd = null;
            if (!empty($txtContent)) {
                $fileName = $Cls->getHandle($objId) . '_LLM_Export.txt';
                $fileHnd = fileman::absorbStr($txtContent, 'exportFiles', $fileName);
            }
        }

        if ($fileHnd) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/txt.png, title=Сваляне на документа');
            $form->info = ($form->info ?? '') . '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        } else {
            $form->info = ($form->info ?? '') . "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }

        $Cls->logWrite('Генериране на LLM експорт', $objId);

        return $fileHnd;
    }


    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        $link = ht::createLink('LLM TXT', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks', 'ef_icon' => 'fileman/icons/16/txt.png', 'title' => 'Сваляне на документа като|* AI TXT|* файл'));

        return $link;
    }


    /**
     * Няма допълнителни параметри - всичко е hardcoded
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL
     */
    public function addParamFields($form, $clsId, $objId)
    {
        $Cls = cls::get($clsId);
        $rec = $Cls->fetch($objId);
        if (!$rec || empty($rec->threadId)) {
            return;
        }

        $threadId = $rec->threadId;
        $threadRec = doc_Threads::fetch($threadId);
        if (!$threadRec) {
            return;
        }

        // Ако е първи документ в нишка, с повече от 1 документ да се появи възможност за избор на цялата нишка
        if (($threadRec->allDocCnt ?? null) != 1) {
            $firstDocInThread = doc_Threads::getFirstDocument($threadId);
            if (($rec->id ?? null) == ($firstDocInThread->that ?? null)) {
                $form->FLD('exportWholeThread', 'enum(no=Не,yes=Да)', 'caption=Да се експортира ли цялата нишка?->Избор,autohide,silent');
                $form->setDefault('exportWholeThread', 'no');
            }
        }
    }
}
